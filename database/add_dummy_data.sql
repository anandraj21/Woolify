-- Insert sample wool types
INSERT IGNORE INTO wool_types (type_name, description) VALUES 
('Merino', 'Fine wool from Merino sheep'),
('Suffolk', 'Medium wool from Suffolk sheep'),
('Lincoln', 'Long wool from Lincoln sheep'),
('Dorset', 'Medium wool from Dorset sheep'),
('Romney', 'Long wool from Romney sheep');

-- Insert sample farms if not exists
INSERT IGNORE INTO farms (farm_name, location, owner_name, contact_number, email, registration_number, user_id, status) VALUES 
('Highland Wool Farm', 'Scotland Highlands', 'James MacGregor', '+44 1234 567890', 'james@highland.com', 'HWF001', 1, 'active'),
('Merino Valley', 'New Zealand Plains', 'Sarah Johnson', '+64 9 123 4567', 'sarah@merinovalley.com', 'MVF002', 1, 'active'),
('Alpine Wool Co', 'Swiss Alps', 'Hans Mueller', '+41 12 345 6789', 'hans@alpinewool.com', 'AWC003', 1, 'active'),
('Outback Wool', 'Australian Outback', 'Mike Thompson', '+61 2 9876 5432', 'mike@outbackwool.com', 'OWF004', 1, 'active'),
('Nordic Sheep Farm', 'Norway Fjords', 'Erik Larsson', '+47 123 45 678', 'erik@nordicsheep.com', 'NSF005', 1, 'active');

-- Generate 50 wool batches with different dates
DELIMITER //
CREATE PROCEDURE generate_sample_batches()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE batch_number VARCHAR(20);
    DECLARE farm_id INT;
    DECLARE wool_type_id INT;
    DECLARE weight DECIMAL(10,2);
    DECLARE quality CHAR(1);
    DECLARE status VARCHAR(20);
    DECLARE days_ago INT;
    
    WHILE i < 50 DO
        SET days_ago = FLOOR(RAND() * 365);
        SET farm_id = 1 + FLOOR(RAND() * 5);
        SET wool_type_id = 1 + FLOOR(RAND() * 5);
        SET weight = 100 + RAND() * 900;
        SET quality = ELT(1 + FLOOR(RAND() * 5), 'A', 'B', 'C', 'D', 'E');
        SET status = ELT(1 + FLOOR(RAND() * 4), 'pending', 'processing', 'completed', 'rejected');
        
        SET batch_number = CONCAT('WB', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL days_ago DAY), '%Y%m%d'),
                                 LPAD(i + 1, 4, '0'));
        
        INSERT INTO wool_batches (
            batch_number, farm_id, wool_type_id, weight_kg, 
            quality_grade, status, notes, created_at
        ) VALUES (
            batch_number, farm_id, wool_type_id, weight,
            quality, status,
            CASE status 
                WHEN 'pending' THEN 'Awaiting processing'
                WHEN 'processing' THEN 'Currently being processed'
                WHEN 'completed' THEN 'Processing completed successfully'
                ELSE 'Quality check failed'
            END,
            DATE_SUB(NOW(), INTERVAL days_ago DAY)
        );
        
        -- Add to batch history
        INSERT INTO batch_history (batch_id, status, notes, created_at)
        SELECT 
            LAST_INSERT_ID(),
            'created',
            'Batch created',
            DATE_SUB(NOW(), INTERVAL days_ago DAY);
        
        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

-- Execute the procedure
CALL generate_sample_batches();

-- Clean up
DROP PROCEDURE IF EXISTS generate_sample_batches; 