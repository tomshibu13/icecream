<?php
class Product {
    // Database connection and table name
    private $conn;
    private $table_name = "products";

    // Object properties
    public $id;
    public $name;
    public $description;
    public $price;
    public $image;
    public $category;
    public $stock;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get total number of products
    public function getTotalProducts() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Read all products
    public function readAll() {
        // Query to select all products
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read products by category
    public function readByCategory() {
        // Query to select products by category
        $query = "SELECT * FROM " . $this->table_name . " WHERE category = ? ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->category = htmlspecialchars(strip_tags($this->category));

        // Bind category value
        $stmt->bindParam(1, $this->category);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read single product
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind id of product to be read
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        $this->name = $row['name'];
        $this->description = $row['description'];
        $this->price = $row['price'];
        $this->image = $row['image'];
        $this->category = $row['category'];
        $this->stock = $row['stock'];
        $this->created_at = $row['created_at'];
    }

    // Create product (admin)
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    name=:name,
                    description=:description,
                    price=:price,
                    image=:image,
                    category=:category,
                    stock=:stock";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->stock = htmlspecialchars(strip_tags($this->stock));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":stock", $this->stock);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update product (admin)
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                SET
                    name = :name,
                    description = :description,
                    price = :price,
                    category = :category,
                    stock = :stock";

        // If image is not empty, update it
        if(!empty($this->image)) {
            $query .= ", image = :image";
        }

        $query .= " WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':id', $this->id);

        // If image is not empty, bind it
        if(!empty($this->image)) {
            $this->image = htmlspecialchars(strip_tags($this->image));
            $stmt->bindParam(':image', $this->image);
        }

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete product (admin)
    public function delete() {
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

    // Search products
    public function search($keywords) {
        // Query to search products
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE name LIKE ? OR description LIKE ? OR category LIKE ?
                ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Update stock
    public function updateStock() {
        // Query to update stock
        $query = "UPDATE " . $this->table_name . "
                SET
                    stock = :stock
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>