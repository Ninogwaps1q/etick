-- eTick Event Ticketing System Database
-- Created: 2026-02-07

CREATE DATABASE IF NOT EXISTS etick;
USE etick;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    profile_pic VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'organizer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    total_tickets INT NOT NULL,
    available_tickets INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_type VARCHAR(50) NOT NULL DEFAULT 'Regular',
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ticket_quantity INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('confirmed', 'cancelled', 'pending') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'Unpaid',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Insert default admin account
-- Password: admin123 (hashed)
INSERT INTO users (full_name, email, password, role)
VALUES ('Admin User', 'admin@etick.com', '$2y$10$8FXf/24vtBU.OHq06ymaFuyYxdEDeTBqFLOzIE7rdzjL2Q8mP.31.', 'admin')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
