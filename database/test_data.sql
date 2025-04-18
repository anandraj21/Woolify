-- Test data for farms
INSERT INTO farms (user_id, farm_name, location, max_capacity_kg) VALUES
(1, 'Highland Wool Farm', 'Scotland Highlands', 5000),
(1, 'Valley Merino Farm', 'Welsh Valley', 3000),
(1, 'Coastal Wool Farm', 'Cornwall Coast', 4000);

-- Test data for wool batches with different qualities and statuses
INSERT INTO wool_batches (batch_number, farm_id, weight_kg, quality_grade, status, created_at) VALUES
('WB20240301001', 1, 450.5, 'A', 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('WB20240301002', 1, 320.0, 'B', 'processing', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('WB20240301003', 2, 280.5, 'A', 'pending', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('WB20240301004', 3, 550.0, 'C', 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('WB20240301005', 2, 420.0, 'B', 'processing', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Add some historical data for trends
INSERT INTO wool_batches (batch_number, farm_id, weight_kg, quality_grade, status, created_at) VALUES
('WB20240201001', 1, 480.0, 'A', 'completed', DATE_SUB(NOW(), INTERVAL 30 DAY)),
('WB20240201002', 2, 350.0, 'B', 'completed', DATE_SUB(NOW(), INTERVAL 31 DAY)),
('WB20240101001', 1, 420.0, 'A', 'completed', DATE_SUB(NOW(), INTERVAL 60 DAY)),
('WB20240101002', 3, 390.0, 'B', 'completed', DATE_SUB(NOW(), INTERVAL 61 DAY));

-- Test data for batch access requests
INSERT INTO batch_access (batch_id, retailer_id, access_status, requested_at) VALUES
(1, 2, 'approved', NOW()),
(1, 3, 'pending', NOW()),
(2, 2, 'pending', NOW()),
(3, 2, 'approved', NOW());

-- Add quality metrics
INSERT INTO quality_checks (batch_id, micron_count, strength_rating, length_mm, overall_grade, checked_at) VALUES
(1, 18.5, 32.0, 85.0, 'A', NOW()),
(2, 20.0, 30.5, 82.0, 'B', NOW()),
(3, 19.0, 31.5, 84.0, 'A', NOW()),
(4, 22.5, 28.0, 78.0, 'C', NOW()),
(5, 20.5, 29.5, 80.0, 'B', NOW()); 