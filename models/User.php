<?php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $password;
    public $email;
    public $first_name;
    public $last_name;
    public $role;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get total number of users
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Create new user
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    username=:username,
                    password=:password,
                    email=:email,
                    first_name=:first_name,
                    last_name=:last_name,
                    role=:role,
                    status=:status";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Login user
    public function login() {
        // Query to check if username exists
        $query = "SELECT id, username, password, role, status FROM " . $this->table_name . " WHERE username = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));

        // Bind given username value
        $stmt->bindParam(1, $this->username);

        // Execute the query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If user exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user is active
            if($row['status'] !== 'active') {
                return false;
            }
            
            // Verify password
            if(password_verify($this->password, $row['password'])) {
                // Set values to object properties
                $this->id = $row['id'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                return true;
            }
        }

        return false;
    }

    // Check if username exists
    public function usernameExists() {
        // Query to check if username exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));

        // Bind given username value
        $stmt->bindParam(1, $this->username);

        // Execute the query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If username exists
        if($num > 0) {
            return true;
        }

        return false;
    }

    // Check if email exists
    public function emailExists() {
        // Query to check if email exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind given email value
        $stmt->bindParam(1, $this->email);

        // Execute the query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If email exists
        if($num > 0) {
            return true;
        }

        return false;
    }

    // Get user details by ID
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind id of product to be updated
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        $this->username = $row['username'];
        $this->email = $row['email'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->role = $row['role'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    // Update user
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                SET
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    username = :username,
                    status = :status
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update password
    public function updatePassword() {
        // Query to update password
        $query = "UPDATE " . $this->table_name . "
                SET
                    password = :password
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind new values
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get all users (for admin)
    public function readAll() {
        // Query to select all users
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        return $stmt;
    }
    
    // Update user status
    public function updateStatus($user_id, $status) {
        // Query to update status
        $query = "UPDATE " . $this->table_name . "
                SET
                    status = :status
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $status = htmlspecialchars(strip_tags($status));
        $user_id = htmlspecialchars(strip_tags($user_id));

        // Bind new values
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $user_id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Update user role
    public function updateRole($user_id, $role) {
        // Query to update role
        $query = "UPDATE " . $this->table_name . "
                SET
                    role = :role
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $role = htmlspecialchars(strip_tags($role));
        $user_id = htmlspecialchars(strip_tags($user_id));

        // Bind new values
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $user_id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Get paginated users (admin)
    public function getUsers($from_record_num = 0, $records_per_page = 10) {
        $from_record_num = (int)$from_record_num;
        $records_per_page = (int)$records_per_page;
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC LIMIT $from_record_num, $records_per_page";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Search and filter users (admin)
    public function searchUsers($search = "", $role = "", $status = "", $from_record_num = 0, $records_per_page = 10) {
        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $like = "%" . $search . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($role !== "") {
            $conditions[] = "role = ?";
            $params[] = $role;
        }

        if ($status !== "") {
            $conditions[] = "status = ?";
            $params[] = $status;
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $from_record_num = (int)$from_record_num;
        $records_per_page = (int)$records_per_page;
        $query = "SELECT * FROM " . $this->table_name . " " . $where . " ORDER BY created_at DESC LIMIT $from_record_num, $records_per_page";

        $stmt = $this->conn->prepare($query);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i, $p);
            $i++;
        }

        $stmt->execute();
        return $stmt;
    }

    // Count search results (admin)
    public function countSearchResults($search = "", $role = "", $status = "") {
        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $like = "%" . $search . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($role !== "") {
            $conditions[] = "role = ?";
            $params[] = $role;
        }

        if ($status !== "") {
            $conditions[] = "status = ?";
            $params[] = $status;
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " " . $where;

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
}
?>