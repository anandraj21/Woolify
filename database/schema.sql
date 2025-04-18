-- Create the database
CREATE DATABASE IF NOT EXISTS woolify_db;
USE woolify_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'processor', 'distributor', 'admin', 'retailer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Farms table
CREATE TABLE IF NOT EXISTS farms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    sheep_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wool batches table
CREATE TABLE IF NOT EXISTS wool_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    shearing_date DATE NOT NULL,
    weight_kg DECIMAL(10,2) NOT NULL,
    quality_grade ENUM('A', 'B', 'C', 'D') NOT NULL,
    status ENUM('at_farm', 'in_processing', 'processed', 'in_transit', 'delivered') NOT NULL DEFAULT 'at_farm',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
);

-- Processing facilities table
CREATE TABLE IF NOT EXISTS processing_facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    facility_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity_kg DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Processing records table
CREATE TABLE IF NOT EXISTS processing_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    facility_id INT NOT NULL,
    process_type ENUM('cleaning', 'sorting', 'scouring', 'carding', 'spinning') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    output_weight_kg DECIMAL(10,2),
    quality_check_passed BOOLEAN DEFAULT NULL,
    status ENUM('pending', 'in_processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES processing_facilities(id) ON DELETE CASCADE
);

-- Quality control checks table
CREATE TABLE IF NOT EXISTS quality_checks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    checked_by INT NOT NULL,
    check_date DATE NOT NULL,
    cleanliness_score INT CHECK (cleanliness_score BETWEEN 1 AND 10),
    strength_score INT CHECK (strength_score BETWEEN 1 AND 10),
    color_uniformity_score INT CHECK (color_uniformity_score BETWEEN 1 AND 10),
    overall_grade ENUM('A', 'B', 'C', 'D') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Transportation records table
CREATE TABLE IF NOT EXISTS transportation_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    from_location_type ENUM('farm', 'facility', 'warehouse') NOT NULL,
    from_location_id INT NOT NULL,
    to_location_type ENUM('farm', 'facility', 'warehouse') NOT NULL,
    to_location_id INT NOT NULL,
    departure_date DATETIME NOT NULL,
    estimated_arrival_date DATETIME NOT NULL,
    actual_arrival_date DATETIME,
    transport_method ENUM('truck', 'train', 'ship') NOT NULL,
    tracking_number VARCHAR(100),
    status ENUM('scheduled', 'in_transit', 'delivered', 'delayed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE
);

-- Batch tracking history table
CREATE TABLE IF NOT EXISTS batch_tracking_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    action_type ENUM('created', 'quality_checked', 'processing_started', 'processing_completed', 'shipped', 'delivered') NOT NULL,
    action_by INT NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_batches_number ON wool_batches(batch_number);
CREATE INDEX idx_batches_status ON wool_batches(status);
CREATE INDEX idx_batch_tracking ON batch_tracking_history(batch_id, action_date);

-- Add role column to users table
ALTER TABLE users ADD COLUMN role ENUM('farmer', 'retailer') NOT NULL AFTER email;

-- Create a new table for batch_access to manage retailer access
CREATE TABLE IF NOT EXISTS batch_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    retailer_id INT NOT NULL,
    access_granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    access_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (retailer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create a table for batch processing stages
CREATE TABLE IF NOT EXISTS batch_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    stage_name ENUM('received', 'cleaning', 'grading', 'processing', 'packaging', 'ready_for_sale') NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_batch_access_retailer ON batch_access(retailer_id);
CREATE INDEX idx_batch_stages_batch ON batch_stages(batch_id);

-- Add a view for retailer batch access
CREATE OR REPLACE VIEW retailer_batch_view AS
SELECT 
    wb.*,
    f.farm_name,
    f.location,
    wt.type_name as wool_type,
    bs.stage_name as current_stage,
    ba.access_status
FROM wool_batches wb
JOIN farms f ON wb.farm_id = f.id
JOIN wool_types wt ON wb.wool_type_id = wt.id
LEFT JOIN batch_stages bs ON wb.id = bs.batch_id
LEFT JOIN batch_access ba ON wb.id = ba.batch_id
WHERE bs.id = (
    SELECT MAX(id) 
    FROM batch_stages 
    WHERE batch_id = wb.id
);