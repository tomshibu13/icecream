<?php
// Connect to database
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Drop existing tables in reverse order of dependencies
try {
    $conn->exec("DROP TABLE IF EXISTS order_items");
    $conn->exec("DROP TABLE IF EXISTS orders");
    $conn->exec("DROP TABLE IF EXISTS cart");
    $conn->exec("DROP TABLE IF EXISTS products");
    $conn->exec("DROP TABLE IF EXISTS users");
    
    // Read and execute SQL file
    $sql = file_get_contents('database/icecream_db.sql');
    $conn->exec($sql);
    
    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>