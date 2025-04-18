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
require_once __DIR__ . '/../models/Notification.php';
// require_once __DIR__ . '/../models/AccessRequest.php'; // TODO: Create or verify Access Request model/logic

// Authentication & Authorization
$auth->requireRole('RETAILER'); // Redirects if not logged in or not a retailer

$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$userId = $user['id'];

// Instantiate models AND get DB instance for direct queries
$dbInstance = Database::getInstance(); 
$retailerModel = new Retailer(); // Models use singleton internally now
$woolBatchModel = new WoolBatch();
$transactionModel = new Transaction();
$notificationModel = new Notification();
// $accessRequestModel = new AccessRequest();

// --- Fetch Data using Models ---

// Get Retailer's specific data (including retailer ID)
$retailerData = $retailerModel->findByUserId($userId);
if (!$retailerData) {
    error_log("Retailer data not found for user ID: $userId");
    die('Error: Retailer details not found. Please contact support.');
}
$retailerId = $retailerData['id']; // The ID from the 'retailers' table

// --- Process Search/Filter ---
$searchParams = [
    'grade' => filter_input(INPUT_GET, 'grade', FILTER_SANITIZE_STRING),
    'min_micron' => filter_input(INPUT_GET, 'min_micron', FILTER_VALIDATE_FLOAT),
    'max_micron' => filter_input(INPUT_GET, 'max_micron', FILTER_VALIDATE_FLOAT),
    'min_quantity' => filter_input(INPUT_GET, 'min_quantity', FILTER_VALIDATE_FLOAT),
    'max_price' => filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT)
];
// Remove empty filter values
$searchParams = array_filter($searchParams);

// --- Fetch Available Batches ---
try {
    // Base query
    $sqlAvailable = "SELECT wb.*, f.farm_name, u.name as farmer_name
                     FROM wool_batches wb
                     JOIN farmers f ON wb.farmer_id = f.id
                     JOIN users u ON f.user_id = u.id
                     WHERE wb.status = 'AVAILABLE'";

    $queryParams = [];

    // Add filter conditions
    if (!empty($searchParams['grade'])) {
        $sqlAvailable .= " AND wb.grade = :grade";
        $queryParams['grade'] = $searchParams['grade'];
    }
    if (!empty($searchParams['min_micron'])) {
        $sqlAvailable .= " AND wb.micron >= :min_micron";
        $queryParams['min_micron'] = $searchParams['min_micron'];
    }
    if (!empty($searchParams['max_micron'])) {
        $sqlAvailable .= " AND wb.micron <= :max_micron";
        $queryParams['max_micron'] = $searchParams['max_micron'];
    }
    if (!empty($searchParams['min_quantity'])) {
        $sqlAvailable .= " AND wb.quantity >= :min_quantity";
        $queryParams['min_quantity'] = $searchParams['min_quantity'];
    }
     if (!empty($searchParams['max_price'])) {
        $sqlAvailable .= " AND wb.price_per_kg <= :max_price";
        $queryParams['max_price'] = $searchParams['max_price'];
    }

    $sqlAvailable .= " ORDER BY wb.created_at DESC"; // Order by newest first

    $stmtAvailableBatches = $dbInstance->query($sqlAvailable, $queryParams);
    $availableBatches = $stmtAvailableBatches->fetchAll(PDO::FETCH_ASSOC);
    $availableBatchesCount = count($availableBatches); // Update count based on actual results

} catch (Exception $e) {
    error_log("Error fetching available batches: " . $e->getMessage());
    $availableBatches = [];
    $availableBatchesCount = 0; // Reset count on error
}


// --- Fetch Retailer-Specific Data (Stats) - MOVED AFTER available batch count is updated ---
try {
    // Count completed purchases
    $stmtCompleted = $dbInstance->query("SELECT COUNT(*) as count FROM transactions WHERE retailer_id = ? AND status = ?", [$retailerId, 'COMPLETED']);
    $purchasedBatchesCount = $stmtCompleted->fetchColumn() ?: 0;

    // Count pending purchases
    $stmtPending = $dbInstance->query("SELECT COUNT(*) as count FROM transactions WHERE retailer_id = ? AND status = ?", [$retailerId, 'PENDING']);
    $pendingPurchasesCount = $stmtPending->fetchColumn() ?: 0;

    // Placeholder for Access Requests/Connected Farms - assuming an 'access_requests' table for now
    $pendingAccessRequestsCount = 0;
    $connectedFarmsCount = 0;
    // Example query if 'access_requests' table exists:
    /*
    $stmtPendingReq = $dbInstance->query("SELECT COUNT(*) as count FROM access_requests WHERE retailer_id = ? AND status = ?", [$retailerId, 'PENDING']);
    $pendingAccessRequestsCount = $stmtPendingReq->fetchColumn() ?: 0;
    $stmtConnected = $dbInstance->query("SELECT COUNT(DISTINCT farmer_id) as count FROM access_requests WHERE retailer_id = ? AND status = ?", [$retailerId, 'APPROVED']);
    $connectedFarmsCount = $stmtConnected->fetchColumn() ?: 0;
    */

} catch (Exception $e) {
    error_log("Error fetching retailer dashboard stats: " . $e->getMessage());
    // Set defaults if queries fail
    $purchasedBatchesCount = $purchasedBatchesCount ?? 0;
    $pendingPurchasesCount = $pendingPurchasesCount ?? 0;
    // $availableBatchesCount is already set from the fetch above
    $pendingAccessRequestsCount = $pendingAccessRequestsCount ?? 0;
    $connectedFarmsCount = $connectedFarmsCount ?? 0;
}

// Fetch recent purchases (e.g., last 5 transactions with farmer details) using DB instance
try {
    $sqlRecent = "SELECT t.*, wb.grade, wb.micron, u.name as farmer_name
                    FROM transactions t
                    JOIN wool_batches wb ON t.batch_id = wb.id
                    JOIN farmers f ON wb.farmer_id = f.id
                    JOIN users u ON f.user_id = u.id
                    WHERE t.retailer_id = ?
                    ORDER BY t.created_at DESC
                    LIMIT 5"; // Changed from transaction_date to created_at
    $stmtRecent = $dbInstance->query($sqlRecent, [$retailerId]);
    $recentPurchases = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
     error_log("Error fetching recent purchases: " . $e->getMessage());
     $recentPurchases = [];
}

// Fetch recent access requests (if model exists)
$recentRequests = []; // $accessRequestModel->findAll(['retailer_id' => $retailerId], 'request_date DESC', 5);

// Get notification count
try {
    $unreadNotifications = $notificationModel->getUnreadCount($userId);
} catch (Exception $e) {
    error_log("Error fetching notification count: " . $e->getMessage());
    $unreadNotifications = 0;
}


// --- Populate Data Structures for View ---
$analytics = [
    'purchased_batches' => $purchasedBatchesCount,
    'pending_purchases' => $pendingPurchasesCount,
    'available_batches' => $availableBatchesCount, // Batches retailer can potentially buy
    'pending_requests' => $pendingAccessRequestsCount,
    'connected_farms' => $connectedFarmsCount,
];

// Function to get status class (duplicate from farmer dashboard - consider moving to a helper file)
function getStatusClass($status) {
    return match(strtolower($status)) {
        'completed' => 'success', // Transaction status
        'pending' => 'warning',
        'failed' => 'danger',
        'available' => 'info', // Batch status
        'sold' => 'secondary',
        default => 'light'
    };
}

$pageTitle = "Retailer Dashboard";
include __DIR__ . '/../includes/header.php'; 
?>

    <div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; // Sidebar should show retailer links now ?>
    
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; // Topnav should show retailer actions ?>
        
        <div class="dashboard-content">
            <!-- Quick Stats -->
            <div class="stats-grid mb-4">
                 <div class="stat-card"> <div class="stat-icon bg-primary bg-opacity-10"><i class="fas fa-store text-primary"></i></div> <div class="stat-info"> <div class="stat-label">Store Name</div> <div class="stat-value"><?php echo htmlspecialchars($retailerData['store_name']); ?></div> <div class="stat-change"><?php echo htmlspecialchars($retailerData['store_address']); ?></div> </div> </div>
                 <div class="stat-card"> <div class="stat-icon bg-success bg-opacity-10"><i class="fas fa-shopping-cart text-success"></i></div> <div class="stat-info"> <div class="stat-label">Completed Purchases</div> <div class="stat-value"><?php echo number_format($purchasedBatchesCount); ?></div> <div class="stat-change"><?php echo number_format($pendingPurchasesCount); ?> pending</div> </div> </div>
                 <div class="stat-card"> <div class="stat-icon bg-info bg-opacity-10"><i class="fas fa-boxes text-info"></i></div> <div class="stat-info"> <div class="stat-label">Available Batches</div> <div class="stat-value"><?php echo number_format($availableBatchesCount); ?></div> <div class="stat-change"><a href="#available-batches">Browse Now</a></div> </div> </div>
                 <div class="stat-card"> <div class="stat-icon bg-warning bg-opacity-10"><i class="fas fa-link text-warning"></i></div> <div class="stat-info"> <div class="stat-label">Connected Farms</div> <div class="stat-value"><?php echo number_format($connectedFarmsCount); ?></div> <div class="stat-change"><?php echo number_format($pendingAccessRequestsCount); ?> pending requests</div> </div> </div>
            </div>

            <!-- Available Batches Table -->
            <div class="table-section mb-4" id="available-batches">
                <div class="section-header">
                     <h3 class="section-title mb-0">Available Wool Batches</h3>
                     <!-- Add Refresh button? -->
                </div>

                <!-- Search/Filter Form -->
                <form method="GET" action="dashboard.php#available-batches" class="filter-form mb-3 p-3 bg-light rounded">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="grade" class="form-label">Grade</label>
                            <select id="grade" name="grade" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="A" <?php echo (isset($searchParams['grade']) && $searchParams['grade'] == 'A') ? 'selected' : ''; ?>>A (Premium)</option>
                                <option value="B" <?php echo (isset($searchParams['grade']) && $searchParams['grade'] == 'B') ? 'selected' : ''; ?>>B (High)</option>
                                <option value="C" <?php echo (isset($searchParams['grade']) && $searchParams['grade'] == 'C') ? 'selected' : ''; ?>>C (Standard)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="min_micron" class="form-label">Min Micron</label>
                            <input type="number" step="0.1" id="min_micron" name="min_micron" class="form-control form-control-sm" value="<?php echo htmlspecialchars($searchParams['min_micron'] ?? ''); ?>" placeholder="e.g., 18.0">
                        </div>
                         <div class="col-md-2">
                            <label for="max_micron" class="form-label">Max Micron</label>
                            <input type="number" step="0.1" id="max_micron" name="max_micron" class="form-control form-control-sm" value="<?php echo htmlspecialchars($searchParams['max_micron'] ?? ''); ?>" placeholder="e.g., 22.5">
                        </div>
                         <div class="col-md-2">
                            <label for="min_quantity" class="form-label">Min Quantity (kg)</label>
                            <input type="number" step="1" id="min_quantity" name="min_quantity" class="form-control form-control-sm" value="<?php echo htmlspecialchars($searchParams['min_quantity'] ?? ''); ?>" placeholder="e.g., 50">
                        </div>
                        <div class="col-md-2">
                            <label for="max_price" class="form-label">Max Price ($/kg)</label>
                            <input type="number" step="0.01" id="max_price" name="max_price" class="form-control form-control-sm" value="<?php echo htmlspecialchars($searchParams['max_price'] ?? ''); ?>" placeholder="e.g., 15.00">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                             <a href="dashboard.php#available-batches" class="btn btn-secondary btn-sm w-100 mt-1">Clear</a>
                        </div>
                    </div>
                </form>

                                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                                            <tr>
                                <th>ID</th>
                                <th>Farmer</th>
                                                <th>Farm</th>
                                <th>Qty (kg)</th>
                                <th>Micron</th>
                                <th>Grade</th>
                                <th>Price ($/kg)</th>
                                <th>Listed On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            <?php if (empty($availableBatches)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">No available batches found matching your criteria.</td></tr>
                            <?php else: ?>
                                <?php foreach ($availableBatches as $batch): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($batch['id']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['farmer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($batch['farm_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format((float)($batch['quantity'] ?? 0), 1); ?></td>
                                        <td><?php echo number_format((float)($batch['micron'] ?? 0), 1); ?></td>
                                        <td><?php echo htmlspecialchars($batch['grade']); ?></td>
                                        <td>$<?php echo number_format((float)($batch['price_per_kg'] ?? 0), 2); ?></td>
                                        <td><?php echo date('d M Y', strtotime($batch['created_at'])); ?></td>
                                        <td>
                                            <a href="view_batch_details.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="View Details"><i class="fas fa-eye"></i></a>
                                            <button type="button" class="btn btn-sm btn-success request-purchase-btn"
                                                    data-batch-id="<?php echo $batch['id']; ?>"
                                                    data-farmer-name="<?php echo htmlspecialchars($batch['farmer_name'] ?? 'N/A'); ?>"
                                                    data-quantity="<?php echo htmlspecialchars($batch['quantity']); ?>"
                                                    data-price="<?php echo htmlspecialchars($batch['price_per_kg']); ?>"
                                                    title="Request Purchase">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                            <?php endif; ?>
                                        </tbody>
                                    </table>
                        </div>
                    </div>

            <!-- Recent Purchases Table -->
            <div class="table-section mb-4">
                <div class="section-header">
                     <h3 class="section-title mb-0">My Recent Purchase History</h3>
                     <a href="my_purchases.php" class="btn btn-sm btn-outline-secondary">View All Purchases</a>
                            </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Batch ID</th>
                                <th>Farmer</th>
                                <th>Qty (kg)</th>
                                <th>Total Price ($)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPurchases)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No recent purchases found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentPurchases as $purchase): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($purchase['id']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($purchase['created_at'])); ?></td>
                                        <td><a href="view_batch_details.php?id=<?php echo $purchase['batch_id']; // Link to details page ?>">#<?php echo htmlspecialchars($purchase['batch_id']); ?></a></td>
                                        <td><?php echo htmlspecialchars($purchase['farmer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format((float)($purchase['quantity'] ?? 0), 1); ?></td>
                                        <td>$<?php echo number_format((float)($purchase['total_price'] ?? 0), 2); ?></td>
                                        <td><span class="badge rounded-pill bg-<?php echo getStatusClass($purchase['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($purchase['status']))); ?></span></td>
                                        <td>
                                            <a href="view_transaction_details.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                            <!-- Add other relevant actions if needed -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- TODO: Recent Access Requests Table -->
             <!-- 
            <div class="table-section"> ... Recent Access Requests ... </div>
             -->

            </div>
        </main>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
// Add Retailer-specific JS if needed (e.g., chart initialization)

document.addEventListener('DOMContentLoaded', function() {
    // --- Purchase Request Modal Logic ---
    const purchaseModal = new bootstrap.Modal(document.getElementById('purchaseRequestModal'));
    const requestButtons = document.querySelectorAll('.request-purchase-btn');

    requestButtons.forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.dataset.batchId;
            const farmerName = this.dataset.farmerName;
            const quantity = parseFloat(this.dataset.quantity);
            const price = parseFloat(this.dataset.price);
            const totalPrice = (quantity * price).toFixed(2);

            document.getElementById('modal-batch-id').textContent = batchId;
            document.getElementById('modal-farmer-name').textContent = farmerName;
            document.getElementById('modal-quantity').textContent = quantity.toFixed(1);
            document.getElementById('modal-price').textContent = price.toFixed(2);
            document.getElementById('modal-total-price').textContent = totalPrice;

            // Set form values
            document.getElementById('form-batch-id').value = batchId;
            document.getElementById('form-quantity').value = quantity;
             document.getElementById('form-price').value = price;

            purchaseModal.show();
        });
    });

    // --- Basic Notification Polling (Copied from farmer dashboard - ensure API path is correct) ---
    const notificationIconBubble = document.getElementById('topnav-notification-bubble');
    const sidebarNotificationCount = document.getElementById('sidebar-notification-count');
    
    function updateNotificationCount(count) {
         const bubbleHtml = count > 0 ? 
            `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em; padding: 0.3em 0.5em;">${count}<span class="visually-hidden">unread messages</span></span>` : '';
         const sidebarHtml = count > 0 ? 
            `<span class="badge rounded-pill bg-danger ms-auto">${count}</span>` : '';
         
         if (notificationIconBubble) notificationIconBubble.innerHTML = bubbleHtml;
         if (sidebarNotificationCount) sidebarNotificationCount.innerHTML = sidebarHtml;
    }

    function checkNotifications() {
         fetch('../api/get_notification_count.php') // Path relative to retailer/dashboard.php
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && typeof data.unread_count !== 'undefined') {
                    updateNotificationCount(data.unread_count);
                } else {
                     console.error('Invalid notification count data received:', data);
                }
            })
            .catch(error => {
                console.error('Error fetching notification count:', error);
            });
    }

    const notificationInterval = setInterval(checkNotifications, 30000); 
    // Initial check can be added if needed
     // checkNotifications(); 
        });
    </script>