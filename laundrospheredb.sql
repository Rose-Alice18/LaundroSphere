-- Create the database
CREATE DATABASE laundrospheredb;

-- Use the database
USE laundrospheredb;

-- Create User table
CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'company', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Booking table
CREATE TABLE Booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'InProgress', 'Completed') DEFAULT 'Pending'
);

-- Create BookingDetail table
CREATE TABLE BookingDetail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    service_for_item_id INT NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL
);

-- Create Service table
CREATE TABLE Service (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_type_id INT NOT NULL,
    company_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ServiceForItem table
CREATE TABLE ServiceForItem (
    service_for_item_id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    item_id INT NOT NULL,
    price_for_one DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ServiceType table
CREATE TABLE ServiceType (
    service_type_id INT AUTO_INCREMENT PRIMARY KEY,
    service_type_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Item table
CREATE TABLE Item (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Company table
CREATE TABLE Company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    company_address VARCHAR(255) NOT NULL,
    company_email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_phone_number VARCHAR(15) NOT NULL,
    short_quote VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Feedback table
CREATE TABLE `feedback` (
  `feedback_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT(11) NOT NULL,
  `company_id` INT(11) NOT NULL,
  `feedback_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments table
CREATE TABLE `comment` (
  `comment_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `pickup_delivery_comments` TEXT NOT NULL,
  `clothing_condition` TEXT NOT NULL,
  `additional_comments` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Admin table
CREATE TABLE `admin` (
  `admin_id` INT(1) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample admin insertion
INSERT INTO `admin` (`admin_id`, `email`, `password`) VALUES
(1, 'laundryadmin@gmail.com', '$2y$10$Al.pau8IegpzfY8WgfPrGO8dL9S535jpNU6WT8NhDGJFDWF.VAF3C');
