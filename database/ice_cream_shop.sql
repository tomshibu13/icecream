-- Create database
CREATE DATABASE IF NOT EXISTS ice_cream_shop;
USE ice_cream_shop;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.jpg',
    category_id INT,
    stock INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_zip VARCHAR(20) NOT NULL,
    shipping_country VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, username, password, role) VALUES
('Admin', 'User', 'admin@example.com', 'admin', '$2y$10$8WxhZ7xhxlXwIkD0cRvA9uW7oOgQNs.TQCqVxhBSaQXRdAEKLq4Uy', 'admin');

-- Insert default categories
INSERT INTO categories (name) VALUES
('Classic'),
('Premium'),
('Seasonal'),
('Vegan'),
('Low Sugar');

-- Insert sample products
INSERT INTO products (name, description, price, image, category_id, stock, status) VALUES
('Vanilla Dream', 'Smooth and creamy vanilla ice cream made with real Madagascar vanilla beans.', 3.99, 'vanilla.jpg', 1, 50, 'active'),
('Chocolate Delight', 'Rich and decadent chocolate ice cream made with premium cocoa.', 4.99, 'chocolate.jpg', 1, 45, 'active'),
('Strawberry Sensation', 'Fresh strawberry ice cream with real strawberry chunks.', 4.99, 'strawberry.jpg', 1, 40, 'active'),
('Mint Chocolate Chip', 'Refreshing mint ice cream with chocolate chips throughout.', 5.99, 'mint_chocolate.jpg', 2, 35, 'active'),
('Cookies and Cream', 'Vanilla ice cream with chunks of chocolate cookies.', 5.99, 'cookies_cream.jpg', 2, 30, 'active'),
('Butter Pecan', 'Buttery ice cream with roasted pecans.', 6.99, 'butter_pecan.jpg', 2, 25, 'active'),
('Pumpkin Spice', 'Seasonal favorite with real pumpkin and warm spices.', 6.99, 'pumpkin_spice.jpg', 3, 20, 'active'),
('Peppermint Stick', 'Cool peppermint ice cream with candy cane pieces.', 6.99, 'peppermint.jpg', 3, 15, 'active'),
('Coconut Bliss', 'Dairy-free coconut ice cream that\'s rich and creamy.', 7.99, 'coconut.jpg', 4, 20, 'active'),
('Almond Milk Chocolate', 'Dairy-free chocolate ice cream made with almond milk.', 7.99, 'almond_chocolate.jpg', 4, 18, 'active'),
('No Sugar Added Vanilla', 'Classic vanilla flavor without added sugar.', 6.99, 'no_sugar_vanilla.jpg', 5, 15, 'active'),
('Stevia Sweetened Chocolate', 'Chocolate ice cream sweetened with stevia instead of sugar.', 6.99, 'stevia_chocolate.jpg', 5, 12, 'active');