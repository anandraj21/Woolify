-- Drop existing tables if they exist
DROP TABLE IF EXISTS processing_records;
DROP TABLE IF EXISTS wool_batches;
DROP TABLE IF EXISTS farms;
DROP TABLE IF EXISTS users;

-- Create database
CREATE DATABASE IF NOT EXISTS woolify;
USE woolify;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('FARMER', 'RETAILER') NOT NULL,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Farmers table
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_name VARCHAR(255) NOT NULL,
    farm_location_lat DECIMAL(10, 8),
    farm_location_lng DECIMAL(11, 8),
    farm_address TEXT NOT NULL,
    total_sheep INT DEFAULT 0,
    registration_number VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Retailers table
CREATE TABLE IF NOT EXISTS retailers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(255) NOT NULL,
    store_location_lat DECIMAL(10, 8),
    store_location_lng DECIMAL(11, 8),
    store_address TEXT NOT NULL,
    business_license VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wool batches table
CREATE TABLE IF NOT EXISTS wool_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    micron DECIMAL(5, 2) NOT NULL,
    grade ENUM('A', 'B', 'C') NOT NULL,
    status ENUM('AVAILABLE', 'PENDING', 'SOLD') DEFAULT 'AVAILABLE',
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    farmer_id INT NOT NULL,
    retailer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('PENDING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    FOREIGN KEY (retailer_id) REFERENCES retailers(id) ON DELETE CASCADE
);

-- Animal health records table
CREATE TABLE IF NOT EXISTS animal_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    sheep_id VARCHAR(50) NOT NULL,
    health_status ENUM('HEALTHY', 'SICK', 'RECOVERING') DEFAULT 'HEALTHY',
    vaccination_date DATE,
    next_vaccination_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
);

-- Weather data table
CREATE TABLE IF NOT EXISTS weather_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    temperature DECIMAL(5, 2),
    humidity DECIMAL(5, 2),
    rainfall DECIMAL(5, 2),
    wind_speed DECIMAL(5, 2),
    forecast_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
);

-- Government schemes table
CREATE TABLE IF NOT EXISTS government_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    eligibility_criteria TEXT NOT NULL,
    application_deadline DATE,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('INFO', 'WARNING', 'SUCCESS', 'ERROR') DEFAULT 'INFO',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_wool_batches_status ON wool_batches(status);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_animal_health_farmer ON animal_health(farmer_id);
CREATE INDEX idx_weather_data_farmer ON weather_data(farmer_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);

-- Insert sample data
-- Insert users
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@woolify.com', 'Admin User', 'admin'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user1@example.com', 'John Doe', 'user'),
('user2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user2@example.com', 'Jane Smith', 'user');

-- Insert wool types
INSERT INTO wool_types (type_name, description) VALUES
('Merino', 'Fine wool from Merino sheep, known for its softness and quality'),
('Alpaca', 'Luxury fiber from Alpacas, known for its warmth and lack of lanolin'),
('Cashmere', 'Ultra-soft, light and warm wool from Cashmere goats'),
('Suffolk', 'Medium wool from Suffolk sheep, good for general use'),
('Romney', 'Long wool from Romney sheep, ideal for crafts and clothing');

-- Insert sample farms
INSERT INTO farms (farm_name, location, owner_name, contact_number, email, registration_number, user_id) VALUES
('Highland Wool Farm', 'Scotland Highlands', 'James MacGregor', '+44 1234 567890', 'james@highland.com', 'HWF001', 1),
('Merino Valley', 'New Zealand Plains', 'Sarah Johnson', '+64 9 123 4567', 'sarah@merinovalley.com', 'MVF002', 1),
('Alpine Wool Co', 'Swiss Alps', 'Hans Mueller', '+41 12 345 6789', 'hans@alpinewool.com', 'AWC003', 1),
('Outback Wool', 'Australian Outback', 'Mike Thompson', '+61 2 9876 5432', 'mike@outbackwool.com', 'OWF004', 1),
('Nordic Sheep Farm', 'Norway Fjords', 'Erik Larsson', '+47 123 45 678', 'erik@nordicsheep.com', 'NSF005', 1);

-- Generate sample batch data with a procedure
DELIMITER //
CREATE PROCEDURE generate_sample_batches()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE farm_count INT;
    DECLARE wool_type_count INT;
    DECLARE batch_number VARCHAR(20);
    DECLARE random_farm_id INT;
    DECLARE random_wool_type_id INT;
    DECLARE random_weight DECIMAL(10,2);
    DECLARE random_quality CHAR(1);
    DECLARE random_status VARCHAR(20);
    
    SELECT COUNT(*) INTO farm_count FROM farms;
    SELECT COUNT(*) INTO wool_type_count FROM wool_types;
    
    WHILE i < 50 DO
        -- Generate random values
        SET random_farm_id = FLOOR(1 + RAND() * farm_count);
        SET random_wool_type_id = FLOOR(1 + RAND() * wool_type_count);
        SET random_weight = ROUND(50 + RAND() * 450, 2);
        SET random_quality = ELT(FLOOR(1 + RAND() * 5), 'A', 'B', 'C', 'D', 'E');
        SET random_status = ELT(FLOOR(1 + RAND() * 4), 'pending', 'processing', 'completed', 'rejected');
        
        -- Generate batch number
        SET batch_number = CONCAT('WB', DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL FLOOR(RAND() * 365) DAY), '%Y%m%d'),
                                 LPAD(i + 1, 4, '0'));
        
        -- Insert batch
        INSERT INTO wool_batches (batch_number, farm_id, wool_type_id, weight_kg, quality_grade, status, created_at)
        VALUES (batch_number, random_farm_id, random_wool_type_id, random_weight, random_quality, random_status,
                DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(RAND() * 365) DAY));
        
        -- Insert batch history
        INSERT INTO batch_history (batch_id, status, notes)
        VALUES (LAST_INSERT_ID(), 'created', 'Batch created');
        
        IF random_status IN ('processing', 'completed', 'rejected') THEN
            INSERT INTO batch_history (batch_id, status, notes)
            VALUES (LAST_INSERT_ID(), 'processing', 'Processing started');
        END IF;
        
        IF random_status IN ('completed', 'rejected') THEN
            INSERT INTO batch_history (batch_id, status, notes)
            VALUES (LAST_INSERT_ID(), random_status, 
                   CASE random_status 
                       WHEN 'completed' THEN 'Processing completed successfully'
                       ELSE 'Batch rejected due to quality issues'
                   END);
        END IF;
        
        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

-- Execute the procedure to generate sample data
CALL generate_sample_batches();
DROP PROCEDURE IF EXISTS generate_sample_batches; 