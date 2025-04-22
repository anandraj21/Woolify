=== Wool Batches Status Check ===

Last 5 batches:
ID: 3, Farmer: 1, Status: , Created: 2025-04-18 12:28:04
ID: 2, Farmer: 1, Status: , Created: 2025-04-18 12:21:09
ID: 1, Farmer: 1, Status: , Created: 2025-04-18 11:45:41

Status counts:
: 3

Batches with NULL/empty status: 3
 <?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/WoolBatch.php';

header('Content-Type: text/plain');

try {
    $db = Database::getInstance();
    
    echo "=== Wool Batches Status Check ===\n\n";
    
    // Check all batches
    $sql1 = "SELECT id, farmer_id, status, created_at FROM wool_batches ORDER BY created_at DESC LIMIT 5";
    $stmt1 = $db->query($sql1, []);
    $batches = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Last 5 batches:\n";
    foreach ($batches as $batch) {
        echo sprintf("ID: %d, Farmer: %d, Status: %s, Created: %s\n",
            $batch['id'],
            $batch['farmer_id'],
            $batch['status'],
            $batch['created_at']
        );
    }
    
    echo "\nStatus counts:\n";
    $sql2 = "SELECT status, COUNT(*) as count FROM wool_batches GROUP BY status";
    $stmt2 = $db->query($sql2, []);
    $counts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counts as $count) {
        echo sprintf("%s: %d\n", $count['status'], $count['count']);
    }
    
    // Check for any NULL or empty statuses
    $sql3 = "SELECT COUNT(*) as count FROM wool_batches WHERE status IS NULL OR status = ''";
    $stmt3 = $db->query($sql3, []);
    $nullCount = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    echo "\nBatches with NULL/empty status: " . $nullCount['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 