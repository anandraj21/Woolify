<?php
// Ensure session is started *only* if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../includes/auth.php'; // Auth implicitly requires Database
require_once __DIR__ . '/../models/Retailer.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Farmer.php';

// Authentication & Authorization
$auth->requireRole('RETAILER');
$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$userId = $user['id'];

// Get Retailer ID (Verify logged-in user is the retailer)
$dbInstance = Database::getInstance();
$retailerModel = new Retailer();
$retailerData = $retailerModel->findByUserId($userId);
if (!$retailerData) {
    die('Error: Retailer details not found.');
}
$retailerId = $retailerData['id'];

// Get Transaction ID from URL
$transactionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$transactionId) {
    die('Invalid Transaction ID.');
}

// Fetch Transaction Details with Joins
$transaction = null;
$errorMessage = '';
try {
    // Query to fetch transaction details along with related batch and farmer info
    $sql = "SELECT
                t.*, -- Transaction details
                wb.micron, wb.grade, wb.status as batch_status, -- Batch details
                f.farm_name, -- Farmer details
                u_farmer.name as farmer_name -- Farmer user details
            FROM transactions t
            JOIN wool_batches wb ON t.batch_id = wb.id
            JOIN farmers f ON wb.farmer_id = f.id
            JOIN users u_farmer ON f.user_id = u_farmer.id
            WHERE t.id = ? AND t.retailer_id = ?"; // Ensure transaction belongs to this retailer

    $stmt = $dbInstance->query($sql, [$transactionId, $retailerId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        $errorMessage = "Transaction not found or you do not have permission to view it.";
    }

} catch (Exception $e) {
    error_log("Error fetching transaction details: " . $e->getMessage());
    $errorMessage = "An error occurred while fetching transaction details.";
}

$pageTitle = $transaction ? "Transaction #" . htmlspecialchars($transaction['id']) . " Details" : "Transaction Details";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                <a href="my_purchases.php" class="btn btn-secondary">Back to Purchases</a>
            <?php elseif ($transaction): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Transaction Summary
                        <span class="badge bg-<?php echo getStatusClass($transaction['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($transaction['status']))); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Transaction Info -->
                            <div class="col-md-6 mb-4">
                                <h5 class="card-title">Transaction Details</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Transaction ID:</strong> #<?php echo htmlspecialchars($transaction['id']); ?></li>
                                    <li class="list-group-item"><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($transaction['created_at'])); ?></li>
                                    <li class="list-group-item"><strong>Status:</strong> <?php echo ucfirst(strtolower(htmlspecialchars($transaction['status']))); ?></li>
                                    <li class="list-group-item"><strong>Quantity Purchased:</strong> <?php echo number_format((float)$transaction['quantity'], 1); ?> kg</li>
                                    <li class="list-group-item"><strong>Price per kg:</strong> $<?php echo number_format((float)$transaction['price_per_kg'], 2); ?></li>
                                    <li class="list-group-item"><strong>Total Amount:</strong> $<?php echo number_format((float)$transaction['total_amount'], 2); ?></li>
                                    <li class="list-group-item"><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($transaction['updated_at'])); ?></li>
                                </ul>
                            </div>

                            <!-- Related Batch Info -->
                            <div class="col-md-6 mb-4">
                                <h5 class="card-title">Related Wool Batch Details</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Batch ID:</strong> <a href="view_batch_details.php?id=<?php echo $transaction['batch_id']; ?>">#<?php echo htmlspecialchars($transaction['batch_id']); ?></a></li>
                                    <li class="list-group-item"><strong>Micron:</strong> <?php echo number_format((float)($transaction['micron'] ?? 0), 1); ?></li>
                                    <li class="list-group-item"><strong>Grade:</strong> <?php echo htmlspecialchars($transaction['grade'] ?? 'N/A'); ?></li>
                                     <li class="list-group-item"><strong>Batch Status:</strong> <?php echo ucfirst(strtolower(htmlspecialchars($transaction['batch_status'] ?? 'N/A'))); ?></li>
                                    <li class="list-group-item"><strong>Farmer Name:</strong> <?php echo htmlspecialchars($transaction['farmer_name'] ?? 'N/A'); ?></li>
                                    <li class="list-group-item"><strong>Farm Name:</strong> <?php echo htmlspecialchars($transaction['farm_name'] ?? 'N/A'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Add any other relevant sections like payment details if applicable -->
                        
                    </div>
                    <div class="card-footer text-end">
                        <a href="dashboard.php#recent-purchases" class="btn btn-secondary">Back to Dashboard</a>
                         <a href="my_purchases.php" class="btn btn-outline-secondary">View All Purchases</a>
                        <!-- Add print/invoice button if needed -->
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Could not retrieve transaction details.</div>
                <a href="my_purchases.php" class="btn btn-secondary">Back to Purchases</a>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
// Function to get status class (Consider moving to a helpers file)
function getStatusClass($status) {
    return match(strtolower($status)) {
        'completed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger', 'cancelled' => 'danger',
        'sold' => 'secondary',
        'available' => 'info', // For batch status if shown
        default => 'light'
    };
}
include __DIR__ . '/../includes/footer.php';
?> 