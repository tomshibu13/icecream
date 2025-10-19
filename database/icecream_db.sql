-- Create database
CREATE DATABASE IF NOT EXISTS icecream_db;
USE icecream_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert admin user
INSERT INTO users (username, password, email, first_name, last_name, role, status) VALUES
('admin', '$2y$10$8WxhJk.MmM7vF2PVpGx5/.YmQzQJNFzHPEFF9O8wOUHYvYHOGVnHK', 'admin@icecream.com', 'Admin', 'User', 'admin', 'active');

-- Insert sample products
INSERT INTO products (name, description, price, image, category, stock) VALUES
('Vanilla Ice Cream', 'Classic vanilla ice cream made with real vanilla beans', 3.99, 'vanilla.jpg', 'Classic', 50),
('Chocolate Ice Cream', 'Rich chocolate ice cream made with premium cocoa', 4.99, 'chocolate.jpg', 'Classic', 45),
('Strawberry Ice Cream', 'Creamy strawberry ice cream with real strawberry chunks', 4.50, 'strawberry.jpg', 'Fruit', 40),
('Mint Chocolate Chip', 'Refreshing mint ice cream with chocolate chips', 5.50, 'mint-choc.jpg', 'Special', 30),
('Cookie Dough', 'Vanilla ice cream with chunks of chocolate chip cookie dough', 5.99, 'cookie-dough.jpg', 'Special', 25);