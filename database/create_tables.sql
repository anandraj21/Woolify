DROP DATABASE IF EXISTS woolify_db;
CREATE DATABASE woolify_db;
USE woolify_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'processor', 'distributor', 'admin', 'retailer') NOT NULL,
    profile_image VARCHAR(255),
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    notification_preferences JSON,
    last_login TIMESTAMP,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE farms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    sheep_count INT DEFAULT 0,
    eco_score DECIMAL(5,2) DEFAULT 0,
    certification_status ENUM('pending', 'certified', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wool_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    shearing_date DATE NOT NULL,
    weight_kg DECIMAL(10,2) NOT NULL,
    quality_grade ENUM('A', 'B', 'C', 'D') NOT NULL,
    status ENUM('at_farm', 'in_processing', 'processed', 'in_transit', 'delivered') NOT NULL DEFAULT 'at_farm',
    eco_impact_score DECIMAL(5,2) DEFAULT 0,
    processing_time_hours INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
);

CREATE TABLE processing_facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    facility_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity_kg DECIMAL(10,2) NOT NULL,
    eco_certification VARCHAR(100),
    processing_efficiency DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE processing_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    facility_id INT NOT NULL,
    process_type ENUM('cleaning', 'sorting', 'scouring', 'carding', 'spinning') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    output_weight_kg DECIMAL(10,2),
    quality_check_passed BOOLEAN DEFAULT NULL,
    energy_consumed_kwh DECIMAL(10,2) DEFAULT 0,
    water_used_liters DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'in_processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES processing_facilities(id) ON DELETE CASCADE
);

CREATE TABLE quality_checks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    checked_by INT NOT NULL,
    check_date DATE NOT NULL,
    cleanliness_score INT CHECK (cleanliness_score BETWEEN 1 AND 10),
    strength_score INT CHECK (strength_score BETWEEN 1 AND 10),
    color_uniformity_score INT CHECK (color_uniformity_score BETWEEN 1 AND 10),
    overall_grade ENUM('A', 'B', 'C', 'D') NOT NULL,
    eco_compliance_score INT CHECK (eco_compliance_score BETWEEN 1 AND 10),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transportation_records (
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
    carbon_footprint_kg DECIMAL(10,2) DEFAULT 0,
    status ENUM('scheduled', 'in_transit', 'delivered', 'delayed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE
);

CREATE TABLE batch_tracking_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    action_type ENUM('created', 'quality_checked', 'processing_started', 'processing_completed', 'shipped', 'delivered') NOT NULL,
    action_by INT NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (batch_id) REFERENCES wool_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_important BOOLEAN DEFAULT FALSE,
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE support_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE eco_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    criteria JSON NOT NULL,
    icon_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES eco_badges(id) ON DELETE CASCADE
);

CREATE TABLE analytics_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_batches_number ON wool_batches(batch_number);
CREATE INDEX idx_batches_status ON wool_batches(status);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_support_tickets_user ON support_tickets(user_id, status);
CREATE INDEX idx_analytics_entity ON analytics_data(entity_type, entity_id);
CREATE INDEX idx_batch_tracking ON batch_tracking_history(batch_id, action_date);