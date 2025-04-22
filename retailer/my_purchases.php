<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Retailer.php';

$auth->requireRole('RETAILER');
$pageTitle = "My Purchase History";

// Get retailer ID
$user = $auth->getUser();
if (!$user) {
    die("Error: User not found.");
}
$userId = $user['id'];

// Initialize variables
$dbInstance = Database::getInstance();
$retailerModel = new Retailer();
$transactionModel = new Transaction();
$purchases = [];
$fetchError = null;

try {
    // Get retailer ID
    $retailerData = $retailerModel->findByUserId($userId);
    if (!$retailerData) {
        throw new Exception("Retailer record not found for this user.");
    }
    $retailerId = $retailerData['id'];

    // Fetch transactions with batch and farmer details
    $sql = "SELECT 
                t.*,
                wb.grade as batch_grade,
                wb.quantity as batch_quantity,
                wb.price_per_kg as batch_price,
                f.farm_name,
                u.name as farmer_name
            FROM transactions t
            LEFT JOIN wool_batches wb ON t.batch_id = wb.id
            LEFT JOIN farmers f ON wb.farmer_id = f.id
            LEFT JOIN users u ON f.user_id = u.id
            WHERE t.retailer_id = ?
            ORDER BY t.created_at DESC";

    $stmt = $dbInstance->query($sql, [$retailerId]);
    if ($stmt === false) {
        throw new Exception("Failed to execute purchase history query");
    }
    
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the number of purchases found
    error_log("Found " . count($purchases) . " purchases for retailer ID: " . $retailerId);

} catch (Exception $e) {
    error_log("Error in my_purchases.php: " . $e->getMessage());
    $fetchError = "Could not retrieve purchase history. Error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>

            <?php if (isset($fetchError)): ?>
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading">Error</h5>
                    <p><?php echo $fetchError; ?></p>
                </div>
            <?php endif; ?>

            <div class="table-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Batch ID</th>
                                <th>Grade</th>
                                <th>Farmer</th>
                                <th>Qty (kg)</th>
                                <th>Total Price ($)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <?php if (!isset($fetchError)): ?>
                                            You have not made any purchases yet.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($purchase['id']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($purchase['created_at'])); ?></td>
                                        <td>
                                            <a href="view_batch_details.php?id=<?php echo $purchase['batch_id']; ?>">
                                                #<?php echo htmlspecialchars($purchase['batch_id']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getQualityClass($purchase['batch_grade']); ?>">
                                                <?php echo getQualityLabel($purchase['batch_grade']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($purchase['farmer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format((float)($purchase['quantity'] ?? 0), 1); ?></td>
                                        <td>$<?php echo number_format((float)($purchase['total_amount'] ?? 0), 2); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php echo getStatusClass($purchase['status']); ?>">
                                                <?php echo ucfirst(strtolower(htmlspecialchars($purchase['status']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_transaction_details.php?id=<?php echo $purchase['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
// Helper functions for status and quality classes/labels
function getStatusClass($status) {
    return match(strtolower($status)) {
        'completed' => 'success',
        'pending' => 'warning',
        'failed', 'cancelled' => 'danger',
        'processing' => 'info',
        default => 'secondary'
    };
}

function getQualityClass($grade) {
    return match(strtoupper($grade)) {
        'A' => 'success',
        'B' => 'info',
        'C' => 'warning',
        'D' => 'danger',
        default => 'secondary'
    };
}

function getQualityLabel($grade) {
    return match(strtoupper($grade)) {
        'A' => 'Premium',
        'B' => 'High',
        'C' => 'Standard',
        'D' => 'Basic',
        default => 'Unknown'
    };
}

// include __DIR__ . '/../includes/footer.php';
?>