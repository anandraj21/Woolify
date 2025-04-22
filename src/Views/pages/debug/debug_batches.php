<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/WoolBatch.php';

// Initialize DB and models
$dbInstance = Database::getInstance();

try {
    // Query to show all batches with their status and farmer info
    $sql = "SELECT 
                wb.*, 
                f.id as farmer_id,
                u.name as farmer_name,
                u.email as farmer_email
            FROM wool_batches wb
            JOIN farmers f ON wb.farmer_id = f.id
            JOIN users u ON f.user_id = u.id
            ORDER BY wb.created_at DESC
            LIMIT 10";
    
    $stmt = $dbInstance->query($sql, []);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    echo "=== Last 10 Wool Batches ===\n\n";
    foreach ($batches as $batch) {
        echo "Batch ID: " . $batch['id'] . "\n";
        echo "Status: " . $batch['status'] . "\n";
        echo "Farmer: " . $batch['farmer_name'] . " (" . $batch['farmer_email'] . ")\n";
        echo "Created: " . $batch['created_at'] . "\n";
        echo "Quantity: " . $batch['quantity'] . " kg\n";
        echo "Price: $" . $batch['price_per_kg'] . "/kg\n";
        echo "------------------------\n";
    }
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 