<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // Needed to get farmer ID
require_once __DIR__ . '/../includes/helpers.php'; // For helper functions like getStatusClass

$auth->requireRole('FARMER');
$pageTitle = "Track Batches (Pending/Sold)";

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    die("Error: Farmer record not found.");
}
$farmerId = $farmerData['id'];

// Fetch trackable batches
$woolBatchModel = new WoolBatch();
$trackableBatches = [];
$fetchError = '';

try {
    $trackableBatches = $woolBatchModel->getTrackableBatchesByFarmer($farmerId);
} catch (Exception $e) {
    error_log("Error fetching trackable batches for farmer {$farmerId}: " . $e->getMessage());
    $fetchError = "Could not retrieve batch tracking information.";
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">View the status of batches that are currently pending sale or have been sold.</p>

            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php endif; ?>

            <div class="table-section">
                 <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Batch ID</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th>Qty (kg)</th>
                                <th>Grade</th>
                                <th>Price ($/kg)</th>
                                <th>Retailer</th>
                                <th>Transaction Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trackableBatches)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No batches currently pending or sold.</td></tr>
                            <?php else: ?>
                                <?php foreach ($trackableBatches as $batch): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($batch['id']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php echo getStatusClass($batch['status']); ?>">
                                                <?php echo ucfirst(strtolower(htmlspecialchars($batch['status']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            // Show transaction update time if available and relevant, otherwise batch update time
                                            $displayDate = $batch['transaction_updated_at'] ?? $batch['updated_at'];
                                            echo date('d M Y, H:i', strtotime($displayDate)); 
                                            ?>
                                        </td>
                                        <td><?php echo number_format((float)$batch['quantity'], 1); ?></td>
                                        <td><span class="badge bg-<?php echo getQualityClass($batch['grade']); ?>"><?php echo getQualityLabel($batch['grade']); ?></span></td>
                                        <td><?php echo number_format((float)($batch['price'] ?? 0), 2); ?></td>
                                        <td><?php echo htmlspecialchars($batch['retailer_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($batch['transaction_status']): ?>
                                                <span class="badge rounded-pill bg-<?php echo getStatusClass($batch['transaction_status']); ?>">
                                                     <?php echo ucfirst(strtolower(htmlspecialchars($batch['transaction_status']))); ?>
                                                 </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination could be added if expecting many trackable batches -->
            </div> 

        </div>
    </main>
</div>