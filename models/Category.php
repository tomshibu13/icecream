<?php
class Category {
    // Database connection and table name
    private $conn;
    private $table_name = "categories";
    
    // Object properties
    public $id;
    public $name;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create category
    public function create() {
        // Check if category already exists
        if ($this->categoryExists($this->name)) {
            // Get the existing category ID
            $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $this->name);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            return true;
        }
        
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, 
                    created_at=NOW(), 
                    updated_at=NOW()";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        
        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Read all categories
    public function read() {
        // Query to read records
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read one category
    public function readOne($id) {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(":id", $id);
        
        // Execute query
        $stmt->execute();
        
        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If record exists, set properties
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Update category
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                SET name=:name, 
                    updated_at=NOW()
                WHERE id=:id";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete category
    public function delete() {
        // Query to delete record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize ID
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind ID
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if category exists
    public function categoryExists($name) {
        // Query to check if category exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name LIMIT 0,1";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize name
        $name = htmlspecialchars(strip_tags($name));
        
        // Bind name
        $stmt->bindParam(":name", $name);
        
        // Execute query
        $stmt->execute();
        
        // Get number of rows
        $num = $stmt->rowCount();
        
        return $num > 0;
    }
    
    // Get category count
    public function getCategoryCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    // Get product count by category
    public function getProductCountByCategory() {
        $query = "SELECT c.id, c.name, COUNT(p.id) as product_count 
                FROM " . $this->table_name . " c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY product_count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?>