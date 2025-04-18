<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // Needed to get farmer ID
require_once __DIR__ . '/../models/Transaction.php'; // Include Transaction model
require_once __DIR__ . '/../includes/helpers.php'; // Include helpers

$auth->requireRole('FARMER');
$pageTitle = "My Wool Batches";

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    die("Error: Farmer record not found.");
}
$farmerId = $farmerData['id'];

// Fetch batches for this farmer
$woolBatchModel = new WoolBatch();
$transactionModel = new Transaction();
$dbInstance = Database::getInstance(); // Get DB instance for direct queries

$statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?: null;
$orderBy = 'created_at DESC';

$batchesInfo = [];
$fetchError = null;
try {
    // Find batches belonging to the farmer
    $conditions = ['farmer_id' => $farmerId];
    if ($statusFilter) {
        $conditions['status'] = $statusFilter;
    }
    $batches = $woolBatchModel->findAll($conditions, $orderBy);

    // Get Batch IDs
    $batchIds = array_column($batches, 'id');
    $pendingTransactionsInfo = [];

    if (!empty($batchIds)) {
        // Query to get pending transaction ID AND retailer store name
        $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
        $sqlPending = "SELECT t.id as transaction_id, t.batch_id, r.store_name
                       FROM transactions t
                       JOIN retailers r ON t.retailer_id = r.id
                       WHERE t.batch_id IN ($placeholders) AND t.status = ?";
        $params = array_merge($batchIds, ['PENDING']);

        $stmtPending = $dbInstance->query($sqlPending, $params);
        $pendingResults = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

        // Map results by batch_id
        foreach ($pendingResults as $row) {
            $pendingTransactionsInfo[$row['batch_id']] = [
                'transaction_id' => $row['transaction_id'],
                'store_name' => $row['store_name' ]
            ];
        }
    }

    // Combine batch data with pending transaction info
    foreach ($batches as $batch) {
        $batch['pending_transaction_info'] = $pendingTransactionsInfo[$batch['id']] ?? null;
        $batchesInfo[] = $batch;
    }

} catch (Exception $e) {
    error_log("Error fetching batches or transactions: " . $e->getMessage());
    $batchesInfo = [];
    $fetchError = "Could not retrieve batch data. Please try again.";
}

// Helper functions (Consider moving to a dedicated helper file)
function getQualityLabel($grade) {
    return match(strtoupper($grade)) {
        'A' => 'Premium',
        'B' => 'High',
        'C' => 'Standard',
        default => 'Unknown'
    };
}
function getStatusClass($status) {
    return match(strtolower($status)) {
        'sold' => 'success',
        'pending' => 'warning', 
        'available' => 'info',
        default => 'secondary'
    };
}
function getQualityClass($grade) {
    return match(strtoupper($grade)) {
        'A' => 'success',
        'B' => 'primary',
        'C' => 'info',
        default => 'secondary'
    };
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $pageTitle; ?></h1>
                <a href="add_batch.php" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Create New Batch</a>
            </div>

            <?php if (isset($_GET['success'])) : ?>
                <div class="alert alert-success">Transaction processed successfully.</div>
            <?php elseif (isset($_GET['error'])) : ?>
                 <div class="alert alert-danger">Error processing transaction: <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($fetchError)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php endif; ?>

            <!-- Optional Filtering -->
            <div class="mb-3">
                <form method="GET" action="batches.php" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="statusFilter" class="col-form-label">Filter by Status:</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <option value="AVAILABLE" <?php echo ($statusFilter === 'AVAILABLE') ? 'selected' : ''; ?>>Available</option>
                            <option value="PENDING" <?php echo ($statusFilter === 'PENDING') ? 'selected' : ''; ?>>Pending</option>
                            <option value="SOLD" <?php echo ($statusFilter === 'SOLD') ? 'selected' : ''; ?>>Sold</option>
                            <!-- Add other statuses if needed -->
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <a href="batches.php" class="btn btn-sm btn-secondary ms-1">Clear</a>
                    </div>
                </form>
            </div>

            <div class="table-section">
                 <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Created</th>
                                <th>Qty (kg)</th>
                                <th>Micron</th>
                                <th>Grade</th>
                                <th>Price ($/kg)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batchesInfo)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No wool batches found<?php echo $statusFilter ? ' matching the filter' : ''; ?>.</td></tr>
                            <?php else: ?>
                                <?php foreach ($batchesInfo as $batch): ?>
                                    <tr class="<?php echo $batch['pending_transaction_info'] ? 'table-warning' : ''; // Highlight pending rows ?>">
                                        <td>#<?php echo htmlspecialchars($batch['id']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($batch['created_at'])); ?></td>
                                        <td><?php echo number_format((float)$batch['quantity'], 1); ?></td>
                                        <td><?php echo htmlspecialchars($batch['micron'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-<?php echo getQualityClass($batch['grade']); ?>"><?php echo getQualityLabel($batch['grade']); ?></span></td>
                                        <td>$<?php echo number_format((float)($batch['price_per_kg'] ?? $batch['price'] ?? 0), 2); // Handle both price/price_per_kg ?></td>
                                        <td><span class="badge rounded-pill bg-<?php echo getStatusClass($batch['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($batch['status']))); ?></span></td>
                                        <td>
                                            <?php if ($batch['pending_transaction_info']):
                                                $pendingInfo = $batch['pending_transaction_info'];
                                                $transactionId = $pendingInfo['transaction_id'];
                                                $storeName = $pendingInfo['store_name'];
                                            ?>
                                                <div class="pending-actions">
                                                    <span class="text-muted me-2" title="Pending request from <?php echo htmlspecialchars($storeName); ?>">Req. by: <?php echo htmlspecialchars($storeName); ?></span>
                                                    <form method="POST" action="process_transaction_action.php" style="display: inline-block;">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                                                        <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                         <!-- Add CSRF token here if implementing -->
                                                        <button type="submit" class="btn btn-sm btn-success ms-1" title="Approve Purchase"
                                                                onclick="return confirm('Approve purchase request from <?php echo htmlspecialchars($storeName); ?> for batch #<?php echo $batch['id']; ?>?');">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="process_transaction_action.php" style="display: inline-block;">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                                                        <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <!-- Add CSRF token here if implementing -->
                                                        <button type="submit" class="btn btn-sm btn-danger ms-1" title="Reject Purchase"
                                                                onclick="return confirm('Reject purchase request from <?php echo htmlspecialchars($storeName); ?> for batch #<?php echo $batch['id']; ?>?');">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <a href="view_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                                <?php if ($batch['status'] === 'AVAILABLE'): ?>
                                                    <a href="edit_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-secondary ms-1" title="Edit Batch"><i class="fas fa-edit"></i></a>
                                                <?php endif; ?>
                                                <!-- Add Delete button or other actions for non-pending batches if needed -->
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- TODO: Add Pagination if many batches -->
            </div> 

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 