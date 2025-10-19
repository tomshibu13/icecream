<?php
require_once 'config/database.php';
require_once 'models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Set user property values for test
$user->username = 'testuser';
$user->email = 'test@example.com';
$user->password = 'password123';
$user->first_name = 'Test';
$user->last_name = 'User';
$user->role = 'customer';
$user->status = 'active';

// Try to create the user
try {
    if ($user->create()) {
        echo "User registered successfully!";
    } else {
        echo "Failed to register user.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>