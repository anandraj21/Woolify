-- Create the database
CREATE DATABASE IF NOT EXISTS woolify;
USE woolify;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'farmer', 'retailer') NOT NULL,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create farmers table
CREATE TABLE IF NOT EXISTS farmers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    farm_address TEXT NOT NULL,
    farm_size DECIMAL(10,2),
    sheep_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create retailers table
CREATE TABLE IF NOT EXISTS retailers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    store_name VARCHAR(100) NOT NULL,
    store_address TEXT NOT NULL,
    business_license VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create wool_batches table
CREATE TABLE IF NOT EXISTS wool_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    micron DECIMAL(5,2),
    grade VARCHAR(20),
    status ENUM('AVAILABLE', 'PENDING', 'SOLD', 'CANCELLED') DEFAULT 'AVAILABLE',
    price_per_kg DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id)
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    retailer_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('PENDING', 'COMPLETED', 'REJECTED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id),
    FOREIGN KEY (retailer_id) REFERENCES retailers(id)
);

-- Create animal_health table
CREATE TABLE IF NOT EXISTS animal_health (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    health_status VARCHAR(50) NOT NULL,
    vaccination_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id)
);

-- Create weather_data table
CREATE TABLE IF NOT EXISTS weather_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    rainfall DECIMAL(5,2),
    recorded_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Drop existing indexes if they exist
DROP INDEX IF EXISTS idx_wool_batches_status ON wool_batches;
DROP INDEX IF EXISTS idx_transactions_status ON transactions;
DROP INDEX IF EXISTS idx_users_email ON users;
DROP INDEX IF EXISTS idx_notifications_user_unread ON notifications;

-- Create indexes for better performance
CREATE INDEX idx_wool_batches_status ON wool_batches(status);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read); 