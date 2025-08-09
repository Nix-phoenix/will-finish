-- Corrected script for store_dbb

DROP DATABASE IF EXISTS store_db;

CREATE DATABASE store_db;

USE store_db;

-- Customer table
CREATE TABLE Customer (
    c_id INT AUTO_INCREMENT PRIMARY KEY,
    c_name VARCHAR(255) NOT NULL,
    tel VARCHAR(20),
    address TEXT
);

-- Employee table
CREATE TABLE Employee (
    emp_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_name VARCHAR(255) NOT NULL,
    tel VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    address TEXT,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee'
);

-- Supplier table
CREATE TABLE Supplier (
    sup_id INT AUTO_INCREMENT PRIMARY KEY,
    sup_name VARCHAR(255) NOT NULL,
    address TEXT,
    tel VARCHAR(20)
);

-- Product Type
CREATE TABLE ProductType (
    pt_id INT AUTO_INCREMENT PRIMARY KEY,
    pt_name VARCHAR(255) NOT NULL
);

-- Product Brand
CREATE TABLE ProductBrand (
    pb_id INT AUTO_INCREMENT PRIMARY KEY,
    pb_name VARCHAR(255) NOT NULL
);

-- Product Shelf
CREATE TABLE ProductShelf (
    pslf_id INT AUTO_INCREMENT PRIMARY KEY,
    pslf_location VARCHAR(255) NOT NULL
);

-- Product Unit
CREATE TABLE ProductUnit (
    punit_id INT AUTO_INCREMENT PRIMARY KEY,
    punit_name VARCHAR(100) NOT NULL
);

-- Product table with foreign keys for normalized data
-- Removed the redundant 'type', 'brand', 'unit', and 'shelf' columns
CREATE TABLE Product (
    p_id INT AUTO_INCREMENT PRIMARY KEY,
    p_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    qty INT DEFAULT 0,
    -- Foreign keys to the new lookup tables
    pt_id INT,
    pb_id INT,
    pslf_id INT,
    punit_id INT,
    image_path VARCHAR(255),
    FOREIGN KEY (pt_id) REFERENCES ProductType (pt_id),
    FOREIGN KEY (pb_id) REFERENCES ProductBrand (pb_id),
    FOREIGN KEY (pslf_id) REFERENCES ProductShelf (pslf_id),
    FOREIGN KEY (punit_id) REFERENCES ProductUnit (punit_id)
);

-- Purchase Order (from suppliers)
CREATE TABLE PurchaseOrder (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    sup_id INT,
    emp_id INT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sup_id) REFERENCES Supplier (sup_id),
    FOREIGN KEY (emp_id) REFERENCES Employee (emp_id)
);

-- Purchase Order Details
CREATE TABLE PurchaseOrderDetail (
    pod_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT,
    p_id INT,
    qty INT,
    price DECIMAL(10, 2),
    FOREIGN KEY (po_id) REFERENCES PurchaseOrder (po_id),
    FOREIGN KEY (p_id) REFERENCES Product (p_id)
);

-- Sell (sales to customers)
CREATE TABLE Sell (
    s_id INT AUTO_INCREMENT PRIMARY KEY,
    c_id INT,
    emp_id INT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (c_id) REFERENCES Customer (c_id) ON DELETE RESTRICT,
    FOREIGN KEY (emp_id) REFERENCES Employee (emp_id) ON DELETE RESTRICT
);



-- Sell Details
CREATE TABLE SellDetail (
    sd_id INT AUTO_INCREMENT PRIMARY KEY,
    s_id INT,
    p_id INT,
    qty INT,
    price DECIMAL(10, 2),
    total_price DECIMAL(10, 2),
    FOREIGN KEY (s_id) REFERENCES Sell (s_id) ON DELETE CASCADE,
    FOREIGN KEY (p_id) REFERENCES Product (p_id) ON DELETE RESTRICT
);

-- Payment table (fixed syntax error)
CREATE TABLE Payment (
    pm_id INT AUTO_INCREMENT PRIMARY KEY,
    s_id INT,
    amount DECIMAL(10, 2),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_paid DECIMAL(10, 2),
    FOREIGN KEY (s_id) REFERENCES Sell (s_id)
);

-- Import Products (fixed the foreign key)
CREATE TABLE Import (
    Ip_id INT AUTO_INCREMENT PRIMARY KEY,
    DATE TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    po_id INT, -- Renamed from Od_id to po_id for clarity
    FOREIGN KEY (po_id) REFERENCES PurchaseOrder (po_id)
);

-- Import Details Products (fixed the foreign key)
CREATE TABLE ImportDetail (
    Ipd_id INT AUTO_INCREMENT PRIMARY KEY,
    Ip_id INT,
    p_id INT,
    qty INT,
    price DECIMAL(10, 2),
    FOREIGN KEY (Ip_id) REFERENCES Import (Ip_id),
    FOREIGN KEY (p_id) REFERENCES Product (p_id)
);

-- Default admin and employee
-- Fixed syntax error (extra comma)
INSERT INTO
    Employee (
        emp_name,
        tel,
        password,
        email,
        address,
        role
    )
VALUES (
        'Admin',
        '123456789',
        'password123',
        'admin@example.com',
        '123 Main St',
        'admin'
    ),
    (
        'Employee',
        '123456789',
        'password123',
        'employee@example.com',
        '456 Elm St',
        'employee'
    );

-- The ALTER TABLE statement from your original script is no longer needed
-- as the `Product` table has been restructured for better normalization.
