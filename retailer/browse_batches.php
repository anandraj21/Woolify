<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php'; // Include helpers
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // To potentially show farmer names

$auth->requireRole('RETAILER');
$pageTitle = "Browse Available Batches";

// Instantiate models and DB
$woolBatchModel = new WoolBatch();
$farmerModel = new Farmer(); 
$dbInstance = Database::getInstance();

$orderBy = 'created_at DESC'; // Or order by price, grade etc.
$availableBatchesInfo = [];
$fetchError = null;

try {
    // Debug log
    error_log("Fetching available batches for retailer view...");
    
    // Modified query to be more explicit and handle case sensitivity
    $sql = "SELECT 
                wb.id,
                wb.quantity,
                wb.micron,
                wb.grade,
                wb.status,
                wb.price_per_kg,
                wb.created_at,
                wb.updated_at,
                f.id as farmer_id,
                u.name as farmer_name,
                u.email as farmer_email
            FROM wool_batches wb
            JOIN farmers f ON wb.farmer_id = f.id
            JOIN users u ON f.user_id = u.id
            WHERE UPPER(wb.status) = 'AVAILABLE'
            ORDER BY wb.created_at DESC";
    
    $stmt = $dbInstance->query($sql, []);
    $availableBatchesInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    error_log("Found " . count($availableBatchesInfo) . " available batches");
    
    if (empty($availableBatchesInfo)) {
        // Additional debug query to check all statuses
        $debugSql = "SELECT status, COUNT(*) as count FROM wool_batches GROUP BY status";
        $debugStmt = $dbInstance->query($debugSql, []);
        $statusCounts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Current batch status counts: " . json_encode($statusCounts));
    }
    
} catch (Exception $e) {
    error_log("Error fetching available batches: " . $e->getMessage());
    $availableBatchesInfo = [];
    $fetchError = "Could not retrieve available batch data. Error: " . $e->getMessage();
}

// Helper functions (Copied - Move to helpers file later)
function getQualityLabel($grade) { /* ... */ }
function getStatusClass($status) { /* ... */ }
function getQualityClass($grade) { /* ... */ }

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Browse wool batches currently available for purchase.</p>

            <?php if (isset($fetchError)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php endif; ?>

            <!-- TODO: Add Filtering/Sorting options (micron range, grade, price, farmer) -->

            <div class="table-section">
                 <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Batch ID</th>
                                <th>Farmer</th>
                                <th>Added</th>
                                <th>Qty (kg)</th>
                                <th>Micron</th>
                                <th>Grade</th>
                                <th>Price ($/kg)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($availableBatchesInfo)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No batches currently available.
                                        <?php if (isset($statusCounts)): ?>
                                            <br>
                                            <small class="text-muted">
                                                System Status: 
                                                <?php foreach ($statusCounts as $count): ?>
                                                    <?php echo $count['status'] . ': ' . $count['count']; ?> |
                                                <?php endforeach; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($availableBatchesInfo as $batch): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($batch['id']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['farmer_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($batch['created_at'])); ?></td>
                                        <td><?php echo number_format((float)$batch['quantity'], 1); ?></td>
                                        <td><?php echo htmlspecialchars($batch['micron'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-<?php echo getQualityClass($batch['grade']); ?>"><?php echo getQualityLabel($batch['grade']); ?></span></td>
                                        <td>$<?php echo number_format((float)$batch['price_per_kg'], 2); ?></td>
                                        <td>
                                            <a href="view_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="initiate_purchase.php?batch_id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-success ms-1" title="Initiate Purchase"><i class="fas fa-shopping-cart"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <!-- TODO: Add Pagination -->
            </div> 

        </div>
    </main>
</div>
<?php 
// Remove helper functions from here
include __DIR__ . '/../includes/footer.php'; 
?> 