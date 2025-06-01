-- Membuat database ecommerce
CREATE DATABASE IF NOT EXISTS ecommerce_db;
USE ecommerce_db;

-- Tabel products untuk menyimpan data produk
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel cart untuk menyimpan data keranjang belanja
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabel users (opsional - untuk sistem login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel orders untuk menyimpan data pesanan
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel order_items untuk detail item dalam pesanan
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert data contoh produk
INSERT INTO products (name, description, price) VALUES 
('Laptop Gaming ASUS ROG', 'Laptop gaming dengan spesifikasi tinggi, RAM 16GB, RTX 3060', 15000000.00),
('Smartphone Samsung Galaxy S23', 'Smartphone Android terbaru dengan kamera 50MP dan layar AMOLED', 8500000.00),
('Headphone Sony WH-1000XM4', 'Headphone wireless dengan noise cancelling terbaik di kelasnya', 4500000.00),
('Keyboard Mechanical Logitech', 'Keyboard gaming mechanical dengan switch Cherry MX Red', 1200000.00),
('Mouse Gaming Razer DeathAdder', 'Mouse gaming dengan sensor optik 20,000 DPI', 850000.00);

-- Insert data contoh keranjang (opsional)
INSERT INTO cart (product_id, quantity) VALUES 
(1, 1),
(3, 2);

-- Indexes untuk performa yang lebih baik
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_cart_product_id ON cart(product_id);
CREATE INDEX