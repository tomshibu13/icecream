<?php
class Order {
    // Database connection and table names
    private $conn;
    private $table_name = "orders";
    private $items_table = "order_items";

    // Object properties
    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get total number of orders
    public function getTotalOrders() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    // Get total revenue
    public function getTotalRevenue() {
        $query = "SELECT SUM(total_amount) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Get recent orders with customer info
    public function getRecentOrders($limit = 5) {
        $query = "SELECT o.*, o.created_at as order_date, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
                 FROM " . $this->table_name . " o
                 LEFT JOIN users u ON o.user_id = u.id
                 ORDER BY o.created_at DESC
                 LIMIT " . $limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Create order from cart
    public function createFromCart($cart_items, $cart_total) {
        // Start transaction
        $this->conn->beginTransaction();

        try {
            // Create order
            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        user_id=:user_id,
                        total_amount=:total_amount,
                        status='pending'";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->total_amount = htmlspecialchars(strip_tags($cart_total));

            // Bind values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":total_amount", $this->total_amount);

            // Execute query
            $stmt->execute();

            // Get order ID
            $this->id = $this->conn->lastInsertId();

            // Add order items
            foreach ($cart_items as $item) {
                $query = "INSERT INTO " . $this->items_table . "
                        SET
                            order_id=:order_id,
                            product_id=:product_id,
                            quantity=:quantity,
                            price=:price";

                // Prepare query
                $stmt = $this->conn->prepare($query);

                // Bind values
                $stmt->bindParam(":order_id", $this->id);
                $stmt->bindParam(":product_id", $item['product_id']);
                $stmt->bindParam(":quantity", $item['quantity']);
                $stmt->bindParam(":price", $item['price']);

                // Execute query
                $stmt->execute();

                // Update product stock
                $query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $item['quantity']);
                $stmt->bindParam(2, $item['product_id']);
                $stmt->execute();
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }

    // Get user's orders
    public function getUserOrders() {
        // Query to get user's orders
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ?
                ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind user id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Get order details
    public function getOrderDetails() {
        // Query to get order details
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at, 
                    oi.product_id, oi.quantity, oi.price, p.name, p.image
                FROM " . $this->table_name . " o
                LEFT JOIN " . $this->items_table . " oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind order id
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Update order status (admin)
    public function updateStatus($id = null, $status = null) {
        if ($id !== null) {
            $this->id = $id;
        }
        if ($status !== null) {
            $this->status = $status;
        }
        // Query to update status
        $query = "UPDATE " . $this->table_name . "
                SET
                    status = :status
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get all orders (admin)
    public function getAllOrders($from_record_num = 0, $records_per_page = 10) {
        // Query to get all orders with user info
        $query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.username 
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
                LIMIT ?, ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind pagination params
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Search and filter orders (admin)
    public function searchOrders($search = "", $status = "", $date_from = "", $date_to = "", $from_record_num = 0, $records_per_page = 10) {
        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(CAST(o.id AS CHAR) LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $like = "%" . $search . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== "") {
            $conditions[] = "o.status = ?";
            $params[] = $status;
        }

        if ($date_from !== "") {
            $conditions[] = "o.created_at >= ?";
            $params[] = $date_from;
        }

        if ($date_to !== "") {
            $conditions[] = "o.created_at <= ?";
            $params[] = $date_to;
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.username
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                " . $where . "
                ORDER BY o.created_at DESC
                LIMIT ?, ?";

        $stmt = $this->conn->prepare($query);

        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i, $p);
            $i++;
        }
        $stmt->bindValue($i, (int)$from_record_num, PDO::PARAM_INT);
        $i++;
        $stmt->bindValue($i, (int)$records_per_page, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }

    // Count search results (admin)
    public function countSearchResults($search = "", $status = "", $date_from = "", $date_to = "") {
        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(CAST(o.id AS CHAR) LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $like = "%" . $search . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== "") {
            $conditions[] = "o.status = ?";
            $params[] = $status;
        }

        if ($date_from !== "") {
            $conditions[] = "o.created_at >= ?";
            $params[] = $date_from;
        }

        if ($date_to !== "") {
            $conditions[] = "o.created_at <= ?";
            $params[] = $date_to;
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $query = "SELECT COUNT(*) AS total
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                " . $where;

        $stmt = $this->conn->prepare($query);

        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i, $p);
            $i++;
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    // Get order count by status (admin)
    public function getOrderCountByStatus($status) {
        // Query to count orders by status
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE status = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $status = htmlspecialchars(strip_tags($status));

        // Bind status
        $stmt->bindParam(1, $status);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['count'];
    }

    // Get total sales (admin)
    public function getTotalSales() {
        // Query to calculate total sales
        $query = "SELECT SUM(total_amount) as total FROM " . $this->table_name . " WHERE status != 'cancelled'";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'];
    }
}
?>