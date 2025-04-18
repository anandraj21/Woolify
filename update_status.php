<?php
require_once __DIR__ . '/includes/auth.php';

try {
    $db = Database::getInstance();
    
    // 1. Modify wool_batches table status enum
    $sql1 = "ALTER TABLE wool_batches MODIFY COLUMN status 
             ENUM('AVAILABLE', 'PENDING', 'SOLD', 'CANCELLED') DEFAULT 'AVAILABLE'";
    $db->query($sql1, []);
    
    // 2. Update existing records to AVAILABLE status
    $sql2 = "UPDATE wool_batches SET status = 'AVAILABLE' WHERE status IS NULL OR status = ''";
    $db->query($sql2, []);
    
    // 3. Modify transactions table status enum
    $sql3 = "ALTER TABLE transactions MODIFY COLUMN status 
             ENUM('PENDING', 'COMPLETED', 'REJECTED') DEFAULT 'PENDING'";
    $db->query($sql3, []);
    
    echo "Database updated successfully!\n";
    echo "Checking current status values:\n\n";
    
    // Verify the changes
    $sql4 = "SELECT id, status FROM wool_batches ORDER BY id";
    $stmt = $db->query($sql4, []);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Wool Batches Status:\n";
    foreach ($batches as $batch) {
        echo "Batch #{$batch['id']}: {$batch['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?> 