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
require_once __DIR__ . '/../models/User.php'; // Needed for farmer user details
require_once __DIR__ . '/../models/Farmer.php'; // Needed for farmer details

// Authentication & Authorization
$auth->requireRole('RETAILER');
$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$userId = $user['id'];

// Get Retailer ID
$dbInstance = Database::getInstance();
$retailerModel = new Retailer();
$retailerData = $retailerModel->findByUserId($userId);
if (!$retailerData) {
    die('Error: Retailer details not found.');
}
$retailerId = $retailerData['id'];

// Get Batch ID from URL
$batchId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$batchId) {
    die('Invalid Batch ID.');
}

// Instantiate models
$woolBatchModel = new WoolBatch();
$transactionModel = new Transaction();
$farmerModel = new Farmer();
$userModel = new User();

// Fetch Batch Details with Farmer Info
$batch = null;
$farmer = null;
$farmerUser = null;
$errorMessage = '';
try {
    // Need a query that joins wool_batches, farmers, and users
    $sqlBatch = "SELECT wb.*, f.farm_name, f.farm_address, u.name as farmer_name, u.email as farmer_email
                 FROM wool_batches wb
                 JOIN farmers f ON wb.farmer_id = f.id
                 JOIN users u ON f.user_id = u.id
                 WHERE wb.id = ?";
    $stmtBatch = $dbInstance->query($sqlBatch, [$batchId]);
    $batch = $stmtBatch->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        $errorMessage = "Wool batch not found.";
    } elseif ($batch['status'] !== 'AVAILABLE' && $batch['status'] !== 'PENDING') {
        // Allow viewing if pending (maybe purchased by this retailer) but maybe not if SOLD to someone else?
        // For now, let's restrict direct viewing if sold to someone else.
        // A retailer should probably only see details if AVAILABLE or if they have a transaction (pending/completed) for it.
        // Let's refine this logic later if needed. For now, primarily for AVAILABLE batches.
         if ($batch['status'] === 'SOLD') {
             // Check if this retailer bought it
             $stmtCheckPurchase = $dbInstance->query(
                "SELECT COUNT(*) FROM transactions WHERE batch_id = ? AND retailer_id = ? AND status = 'COMPLETED'",
                [$batchId, $retailerId]
             );
             if (!$stmtCheckPurchase->fetchColumn()) {
                 $errorMessage = "This batch has already been sold.";
             }
             // If they completed the purchase, they can still view it.
         } else if ($batch['status'] !== 'AVAILABLE' && $batch['status'] !== 'PENDING') {
             $errorMessage = "This batch is not currently available for purchase.";
         }
    }

    // Check if this retailer already has a PENDING transaction for this batch
    $existingPendingTransaction = false;
    if ($batch && $batch['status'] === 'AVAILABLE') { // Only check if it's available
        $stmtPendingCheck = $dbInstance->query(
            "SELECT COUNT(*) FROM transactions WHERE batch_id = ? AND retailer_id = ? AND status = 'PENDING'",
            [$batchId, $retailerId]
        );
        if ($stmtPendingCheck->fetchColumn() > 0) {
            $existingPendingTransaction = true;
        }
    }


} catch (Exception $e) {
    error_log("Error fetching batch details: " . $e->getMessage());
    $errorMessage = "An error occurred while fetching batch details.";
}

$pageTitle = $batch ? "Batch #" . htmlspecialchars($batch['id']) . " Details" : "Batch Details";
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
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php elseif ($batch): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Batch Information
                         <span class="badge bg-<?php echo getStatusClass($batch['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($batch['status']))); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">Wool Details</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Batch ID:</strong> #<?php echo htmlspecialchars($batch['id']); ?></li>
                                    <li class="list-group-item"><strong>Quantity:</strong> <?php echo number_format((float)$batch['quantity'], 1); ?> kg</li>
                                    <li class="list-group-item"><strong>Micron:</strong> <?php echo number_format((float)$batch['micron'], 1); ?></li>
                                    <li class="list-group-item"><strong>Grade:</strong> <?php echo htmlspecialchars($batch['grade']); ?></li>
                                    <li class="list-group-item"><strong>Price per kg:</strong> $<?php echo number_format((float)$batch['price_per_kg'], 2); ?></li>
                                    <li class="list-group-item"><strong>Total Value:</strong> $<?php echo number_format((float)$batch['quantity'] * (float)$batch['price_per_kg'], 2); ?></li>
                                     <li class="list-group-item"><strong>Listed On:</strong> <?php echo date('F j, Y, g:i a', strtotime($batch['created_at'])); ?></li>
                                    <li class="list-group-item"><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($batch['updated_at'])); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="card-title">Farmer Details</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Farmer Name:</strong> <?php echo htmlspecialchars($batch['farmer_name']); ?></li>
                                    <li class="list-group-item"><strong>Farm Name:</strong> <?php echo htmlspecialchars($batch['farm_name']); ?></li>
                                    <li class="list-group-item"><strong>Farm Address:</strong> <?php echo nl2br(htmlspecialchars($batch['farm_address'])); ?></li>
                                    <li class="list-group-item"><strong>Contact Farmer:</strong> <a href="mailto:<?php echo htmlspecialchars($batch['farmer_email']); ?>"><?php echo htmlspecialchars($batch['farmer_email']); ?></a></li>
                                    <!-- Add more farmer details if needed/available -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?php if ($batch['status'] === 'AVAILABLE' && !$existingPendingTransaction): ?>
                            <button type="button" class="btn btn-success request-purchase-btn"
                                    data-batch-id="<?php echo $batch['id']; ?>"
                                    data-farmer-name="<?php echo htmlspecialchars($batch['farmer_name']); ?>"
                                    data-quantity="<?php echo htmlspecialchars($batch['quantity']); ?>"
                                    data-price="<?php echo htmlspecialchars($batch['price_per_kg']); ?>">
                                <i class="fas fa-shopping-cart me-2"></i>Request Purchase
                            </button>
                         <?php elseif ($existingPendingTransaction): ?>
                            <button type="button" class="btn btn-warning disabled">
                                <i class="fas fa-clock me-2"></i>Purchase Request Sent
                            </button>
                        <?php endif; ?>
                         <a href="dashboard.php#available-batches" class="btn btn-secondary">Back to Available Batches</a>
                    </div>
                </div>

                 <!-- Purchase Request Modal (Copied from dashboard, ensure IDs are unique if needed, but should be fine here) -->
                <div class="modal fade" id="purchaseRequestModal" tabindex="-1" aria-labelledby="purchaseRequestModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="purchaseRequestModalLabel">Confirm Purchase Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <p>You are about to request purchasing Batch #<strong id="modal-batch-id"></strong> from farmer <strong id="modal-farmer-name"></strong>.</p>
                        <p>Quantity: <strong id="modal-quantity"></strong> kg</p>
                        <p>Price: $<strong id="modal-price"></strong> per kg</p>
                        <p>Total: $<strong id="modal-total-price"></strong></p>
                        <hr>
                        <p>This will send a notification to the farmer for approval.</p>
                         <form id="purchase-request-form" method="POST" action="process_purchase_request.php">
                            <input type="hidden" name="batch_id" id="form-batch-id">
                            <input type="hidden" name="retailer_id" value="<?php echo $retailerId; ?>">
                            <input type="hidden" name="quantity" id="form-quantity">
                            <input type="hidden" name="price_per_kg" id="form-price">
                             <input type="hidden" name="total_amount" id="form-total-price"> <!-- Added total amount -->
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="purchase-request-form" class="btn btn-success">Send Request</button>
                      </div>
                    </div>
                  </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">Could not retrieve batch details.</div>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
// Function to get status class (Consider moving to a helpers file)
function getStatusClass($status) {
    return match(strtolower($status)) {
        'completed' => 'success', 'available' => 'success', // Merged available here for display
        'pending' => 'warning',
        'failed' => 'danger', 'cancelled' => 'danger',
        'sold' => 'secondary',
        default => 'light'
    };
}
include __DIR__ . '/../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure modal exists before trying to initialize
    const modalElement = document.getElementById('purchaseRequestModal');
    if (modalElement) {
        const purchaseModal = new bootstrap.Modal(modalElement);
        const requestButtons = document.querySelectorAll('.request-purchase-btn'); // Button might not exist if not available

        requestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const batchId = this.dataset.batchId;
                const farmerName = this.dataset.farmerName;
                const quantity = parseFloat(this.dataset.quantity);
                const price = parseFloat(this.dataset.price);
                const totalPrice = (quantity * price).toFixed(2);

                // Populate modal text
                document.getElementById('modal-batch-id').textContent = batchId;
                document.getElementById('modal-farmer-name').textContent = farmerName;
                document.getElementById('modal-quantity').textContent = quantity.toFixed(1);
                document.getElementById('modal-price').textContent = price.toFixed(2);
                document.getElementById('modal-total-price').textContent = totalPrice;

                // Populate form values
                document.getElementById('form-batch-id').value = batchId;
                document.getElementById('form-quantity').value = quantity;
                document.getElementById('form-price').value = price;
                document.getElementById('form-total-price').value = totalPrice; // Add total price to form

                purchaseModal.show();
            });
        });
    }
});
</script> 