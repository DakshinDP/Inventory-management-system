CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    category ENUM('Computer', 'Component') NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    specifications TEXT,
    quantity INT NOT NULL DEFAULT 0 CHECK (quantity >= 0),
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 CHECK (unit_price >= 0),
    status ENUM('Available', 'In Use', 'Under Maintenance') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_products_name (product_name),
    INDEX idx_products_category (category),
    INDEX idx_products_status (status)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(25) DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suppliers_name (supplier_name),
    INDEX idx_suppliers_email (email)
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
    purchase_order_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0),
    status ENUM('Pending', 'Received', 'Cancelled') NOT NULL DEFAULT 'Pending',
    received_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_orders_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_purchase_orders_supplier (supplier_id),
    INDEX idx_purchase_orders_status (status),
    INDEX idx_purchase_orders_date (order_date)
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(12,2) NOT NULL CHECK (unit_price >= 0),
    subtotal DECIMAL(12,2) NOT NULL CHECK (subtotal >= 0),
    CONSTRAINT fk_poi_order
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(purchase_order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_poi_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_poi_order (purchase_order_id),
    INDEX idx_poi_product (product_id)
) ENGINE=InnoDB;

INSERT INTO users (username, password_hash)
VALUES ('admin', '$2y$12$OFT017KLTgDrmPvqhGoYvOaJWFh85bduy5pgD8u3f2D/8vekrlLye');
