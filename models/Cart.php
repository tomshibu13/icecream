<?php
class Cart {
    // Database connection and table name
    private $conn;
    private $table_name = "cart";

    // Object properties
    public $id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Add item to cart
    public function addToCart() {
        // Check if item already exists in cart
        if($this->itemExists()) {
            // Update quantity
            return $this->updateQuantity();
        }

        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id=:user_id,
                    product_id=:product_id,
                    quantity=:quantity";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":quantity", $this->quantity);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Check if item exists in cart
    private function itemExists() {
        // Query to check if item exists
        $query = "SELECT id, quantity FROM " . $this->table_name . " 
                WHERE user_id = ? AND product_id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));

        // Bind values
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);

        // Execute query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If item exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->quantity += $row['quantity']; // Add new quantity to existing
            return true;
        }

        return false;
    }

    // Update quantity
    public function updateQuantity() {
        // Query to update quantity
        $query = "UPDATE " . $this->table_name . "
                SET
                    quantity = :quantity
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get user's cart items
    public function getUserCart() {
        // Query to get cart items with product details
        $query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image, p.stock 
                FROM " . $this->table_name . " c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC";

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

    // Remove item from cart
    public function removeFromCart() {
        // Query to delete record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind id of record to delete
        $stmt->bindParam(1, $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Clear user's cart
    public function clearCart() {
        // Query to delete all user's cart items
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind user id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get cart total
    public function getCartTotal() {
        // Query to calculate cart total
        $query = "SELECT SUM(p.price * c.quantity) as total 
                FROM " . $this->table_name . " c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind user id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'];
    }

    // Count items in cart
    public function countItems() {
        // Query to count items
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind user id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['count'];
    }
}
?>