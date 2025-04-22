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
    <aside class="sidebar">
    <div class="sidebar-header">
        <a href="../index.php" class="woolify-brand">
            <img src="../public/assets/images/logo.png" alt="Woolify" class="sidebar-logo">
            <span>Woolify</span>
        </a>
        <button class="sidebar-toggle btn btn-link d-lg-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <style>
    /* Enhanced Brand Styling */
    .sidebar-header {
        padding: 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-toggle {
        color: #5F975F;
        transition: color 0.3s ease;
        padding: 0.5rem;
    }

    .sidebar-toggle:hover {
        color: #4C794C;
    }

    .woolify-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
        padding: 0.5rem;
    }

    .woolify-brand img {
        height: 40px;
        width: auto;
        border-radius: 8px;
    }

    .woolify-brand span {
        font-size: 24px;
        color: #5F975F;
        font-weight: 600;
        margin-left: 12px;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.5px;
    }

    .woolify-brand:hover {
        text-decoration: none;
    }

    .woolify-brand:hover span {
        color: #4C794C;
    }
    </style>
        
        <nav class="sidebar-nav">
            <!-- General Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">GENERAL</h6>
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>

            <!-- Wool Management -->
            <div class="nav-section">
                <h6 class="nav-section-title">WOOL MANAGEMENT</h6>
                <a href="browse_batches.php" class="nav-link">
                    <i class="fas fa-box"></i> Browse Batches
                </a>
                <a href="my_purchases.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> My Purchases
                </a>
                <!-- <a href="inventory.php" class="nav-link">
                    <i class="fas fa-warehouse"></i> Inventory
                </a> -->
                <!-- <a href="order_planning.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Order Planning
                </a> -->
            </div>

            <!-- Analytics & Reports -->
            <div class="nav-section">
                <h6 class="nav-section-title">ANALYTICS & REPORTS</h6>
                <a href="purchase_analytics.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Purchase Analytics
                </a>
                <!-- <a href="quality_reports.php" class="nav-link">
                    <i class="fas fa-certificate"></i> Quality Reports
                </a>
                <a href="price_tracking.php" class="nav-link">
                    <i class="fas fa-tags"></i> Price Tracking
                </a>
                <a href="market_analysis.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Market Analysis
                </a> -->
            </div>

            <!-- Supplier Management -->
            <div class="nav-section">
                <h6 class="nav-section-title">SUPPLIER MANAGEMENT</h6>
                <a href="farms_connected.php" class="nav-link">
                    <i class="fas fa-handshake"></i> Connected Farms
                </a>
                <a href="access_requests.php" class="nav-link">
                    <i class="fas fa-user-plus"></i> Access Requests
                </a>
                <a href="supplier_ratings.php" class="nav-link">
                    <i class="fas fa-star"></i> Supplier Ratings
                </a>
            </div>

            <!-- Account Management -->
            <div class="nav-section">
                <h6 class="nav-section-title">ACCOUNT</h6>
                <a href="../profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
                <a href="help.php" class="nav-link">
                    <i class="fas fa-question-circle"></i> Help
                </a>
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <main class="main-content">
        <!-- Top Navigation Bar -->
        <nav class="top-nav shadow-sm">
            <div class="nav-left">
                <button class="sidebar-toggle btn btn-link me-2 d-lg-none"> <!-- Button to toggle sidebar on small screens -->
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="nav-right">
                <!-- User Profile -->
                <div class="nav-item user-profile me-3">
                    <div class="d-flex align-items-center">
                        <div class="user-info me-3">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role">Retailer</div>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="nav-item position-relative me-2">
                    <a href="../notifications.php" class="btn btn-icon position-relative" id="topnav-notification-icon">
                        <i class="fas fa-bell"></i>
                        <span id="topnav-notification-bubble">
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                <?php echo $unreadNotifications; ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        <?php endif; ?>
                        </span>
                    </a>
                </div>

               <!-- Settings Dropdown -->
               <div class="nav-item dropdown">
                    <button class="btn btn-icon" type="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="settingsDropdown">
                        <li><a class="dropdown-item d-flex align-items-center" href="../profile.php">
                            <i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item d-flex align-items-center" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item d-flex align-items-center" href="help.php">
                            <i class="fas fa-question-circle me-2"></i>Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item d-flex align-items-center text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    
        
        <div class="dashboard-content">
            <!-- Quick Stats -->
            <div class="stats-grid mb-4">
                 <div class="stat-card"> <div class="stat-icon bg-primary bg-opacity-10"><i class="fas fa-store text-primary"></i></div> <div class="stat-info"> <div class="stat-label">Store Name</div> <div class="stat-value"><?php echo htmlspecialchars($retailerData['store_name']); ?></div> <div class="stat-change"><?php echo htmlspecialchars($retailerData['store_address']); ?></div> </div> </div>
                 <div class="stat-card"> <div class="stat-icon bg-success bg-opacity-10"><i class="fas fa-shopping-cart text-success"></i></div> <div class="stat-info"> <div class="stat-label">Completed Purchases</div> <div class="stat-value"><?php echo number_format($purchasedBatchesCount); ?></div> <div class="stat-change"><?php echo number_format($pendingPurchasesCount); ?> pending</div> </div> </div>
                 <div class="stat-card"> <div class="stat-icon bg-info bg-opacity-10"><i class="fas fa-boxes text-info"></i></div> <div class="stat-info"> <div class="stat-label">Available Batches</div> <div class="stat-value"><?php echo number_format($availableBatchesCount); ?></div> <div class="stat-change"><a href="#available-batches">Browse Now</a></div> </div> </div>
                 <!-- <div class="stat-card"> <div class="stat-icon bg-warning bg-opacity-10"><i class="fas fa-link text-warning"></i></div> <div class="stat-info"> <div class="stat-label">Connected Farms</div> <div class="stat-value"><?php echo number_format($connectedFarmsCount); ?></div> <div class="stat-change"><?php echo number_format($pendingAccessRequestsCount); ?> pending requests</div> </div> </div> -->
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

<!-- <?php include __DIR__ . '/../includes/footer.php'; ?> -->

    <script>
// Add Retailer-specific JS if needed (e.g., chart initialization)

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Purchase Request Modal Logic
    const purchaseModal = new bootstrap.Modal(document.getElementById('purchaseRequestModal'));
    const requestButtons = document.querySelectorAll('.request-purchase-btn');

    requestButtons.forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.dataset.batchId;
            const farmerName = this.dataset.farmerName;
            const quantity = parseFloat(this.dataset.quantity);
            const price = parseFloat(this.dataset.price);
            const totalPrice = (quantity * price).toFixed(2);

            // Enhanced Modal Population
            document.getElementById('modal-batch-id').textContent = batchId;
            document.getElementById('modal-farmer-name').textContent = farmerName;
            document.getElementById('modal-quantity').textContent = quantity.toFixed(1);
            document.getElementById('modal-price').textContent = price.toFixed(2);
            document.getElementById('modal-total-price').textContent = totalPrice;

            // Set form values with validation
            const formBatchId = document.getElementById('form-batch-id');
            const formQuantity = document.getElementById('form-quantity');
            const formPrice = document.getElementById('form-price');

            if (formBatchId && formQuantity && formPrice) {
                formBatchId.value = batchId;
                formQuantity.value = quantity;
                formPrice.value = price;
            }

            purchaseModal.show();
        });
    });

    // Enhanced Notification System
    const notificationIconBubble = document.getElementById('topnav-notification-bubble');
    const sidebarNotificationCount = document.getElementById('sidebar-notification-count');
    
    function updateNotificationCount(count) {
         const bubbleHtml = count > 0 ? 
            `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">${count}<span class="visually-hidden">unread notifications</span></span>` : '';
         const sidebarHtml = count > 0 ? 
            `<span class="badge rounded-pill bg-danger ms-auto">${count}</span>` : '';
         
         if (notificationIconBubble) notificationIconBubble.innerHTML = bubbleHtml;
         if (sidebarNotificationCount) sidebarNotificationCount.innerHTML = sidebarHtml;
    }

    // Enhanced Notification Polling with Error Handling
    function checkNotifications() {
        fetch('../api/get_notification_count.php')
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
                    console.warn('Invalid notification count data received:', data);
                }
            })
            .catch(error => {
                console.error('Error fetching notification count:', error);
            });
    }

    // Initialize notifications
    checkNotifications();
    const notificationInterval = setInterval(checkNotifications, 30000); 

    // Enhanced Mobile Responsiveness
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-shown');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                mainContent.classList.remove('sidebar-shown');
            }
        });
    }

    // Enhanced Table Responsiveness
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.classList.add('table-responsive');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});
</script>

<style>
/* Enhanced Dashboard Styles */
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --info-color: #0dcaf0;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --sidebar-width: 280px;
}

/* Dashboard Layout Enhancements */
.dashboard-container {
    display: flex;
    min-height: 100vh;
    background: var(--light-color);
}

/* Enhanced Sidebar */
.sidebar {
    width: var(--sidebar-width);
    background: #fff;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1030;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background: linear-gradient(to right, #f8f9fa, #ffffff);
}

.logo-text {
    text-align: center;
}

.logo-brand {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-color);
    letter-spacing: 1px;
    margin-bottom: 0.25rem;
}

.logo-role {
    display: block;
    font-size: 0.875rem;
    color: var(--secondary-color);
    text-transform: uppercase;
    letter-spacing: 2px;
}

/* Enhanced Navigation */
.nav-section {
    padding: 1rem 0;
}

.nav-section-title {
    padding: 0.75rem 1.5rem;
    color: var(--secondary-color);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

.nav-link {
    padding: 0.75rem 1.5rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    background: rgba(13, 110, 253, 0.05);
    color: var(--primary-color);
    border-left-color: var(--primary-color);
}

.nav-link.active {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
    border-left-color: var(--primary-color);
    font-weight: 500;
}

.nav-link i {
    width: 20px;
    margin-right: 12px;
    font-size: 1.1rem;
}

/* Enhanced Main Content */
.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    padding: 1.5rem;
    transition: all 0.3s ease;
}

/* Enhanced Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    padding: 1.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.25rem;
    font-size: 1.5rem;
}

.stat-info {
    flex-grow: 1;
}

.stat-label {
    color: var(--secondary-color);
    font-size: 0.875rem;
    margin-bottom: 0.375rem;
    font-weight: 500;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.stat-change {
    font-size: 0.875rem;
    color: var(--secondary-color);
}

/* Enhanced Table Sections */
.table-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    margin-bottom: 2rem;
    overflow: hidden;
}

.section-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
    margin: 0;
}

/* Enhanced Filter Form */
.filter-form {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    border-radius: 8px;
    margin: 1rem;
    padding: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

/* Enhanced Table Styles */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: var(--dark-color);
    background: #f8f9fa;
    padding: 1rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

/* Enhanced Buttons */
.btn {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-primary {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Enhanced Badges */
.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Enhanced Responsive Design */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }
}

/* Enhanced Navbar */
.navbar {
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    padding: 1rem 1.5rem;
}

.navbar .dropdown-menu {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    border-radius: 8px;
}

.navbar .dropdown-item {
    padding: 0.75rem 1.25rem;
    font-weight: 500;
}

/* User Profile Dropdown */
.navbar .dropdown-toggle {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.navbar .dropdown-toggle:hover {
    background-color: rgba(0,0,0,0.02);
}

.navbar .dropdown-toggle .fw-bold {
    font-size: 0.95rem;
    line-height: 1.2;
}

.navbar .dropdown-toggle small {
    font-size: 0.8rem;
    opacity: 0.7;
}

/* Enhanced Notifications */
.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Top Navigation Styles */
.top-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #fff;
    margin-bottom: 1.5rem;
}

.nav-left {
    display: flex;
    align-items: center;
}

.nav-right {
    display: flex;
    align-items: center;
}

.page-title {
    font-size: 1.5rem;
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

/* User Profile Styles */
.user-profile {
    padding: 0.5rem;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.user-info {
    text-align: right;
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #2c3e50;
    line-height: 1.2;
}

.user-role {
    font-size: 0.8rem;
    color: #6c757d;
}

/* Button Styles */
.btn-icon {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: #2c3e50;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background-color: rgba(0,0,0,0.05);
    color: #0d6efd;
}

/* Notification Badge */
.notification-badge {
    font-size: 0.65rem !important;
    padding: 0.25em 0.6em !important;
    min-width: 1rem;
    height: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Dropdown Menu */
.dropdown-menu {
    min-width: 200px;
    padding: 0.5rem 0;
    margin: 0;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.7rem 1.2rem;
    font-size: 0.9rem;
    color: #2c3e50;
    transition: all 0.2s ease;
}

.dropdown-item:hover, .dropdown-item:focus {
    background-color: rgba(13, 110, 253, 0.05);
    color: #0d6efd;
}

.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.05);
    color: #dc3545;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.btn-icon {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    color: #2c3e50;
}

.btn-icon:hover, .btn-icon:focus {
    background-color: rgba(0,0,0,0.05);
    color: #0d6efd;
}

.btn-icon.show {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

/* Ensure dropdown is above other elements */
.dropdown-menu.show {
    z-index: 1050;
    animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .user-info {
        display: none;
    }
    
    .page-title {
        font-size: 1.25rem;
    }
}
</style>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    var dropdownElementList = document.querySelectorAll('.dropdown-toggle, [data-bs-toggle="dropdown"]');
    dropdownElementList.forEach(function(dropdownToggle) {
        new bootstrap.Dropdown(dropdownToggle, {
            boundary: 'window'
        });
    });

    // Add click handler for the settings dropdown
    const settingsDropdown = document.getElementById('settingsDropdown');
    if (settingsDropdown) {
        settingsDropdown.addEventListener('click', function(e) {
            const dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
            dropdown.toggle();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('#settingsDropdown, #settingsDropdown *')) {
            const dropdown = bootstrap.Dropdown.getInstance(settingsDropdown);
            if (dropdown && document.querySelector('.dropdown-menu.show')) {
                dropdown.hide();
            }
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    dropdownElementList.forEach(function(dropdownToggle) {
        new bootstrap.Dropdown(dropdownToggle, {
            boundary: 'window'
        });
    });

    // Add click handler for the settings dropdown specifically
    const settingsDropdown = document.getElementById('settingsDropdown');
    if (settingsDropdown) {
        settingsDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = bootstrap.Dropdown.getOrCreateInstance(settingsDropdown);
            dropdown.toggle();
        });
    }
        });
    </script>

<style>
/* Enhanced dropdown styles */
.dropdown-menu {
    margin-top: 0.5rem;
    min-width: 200px;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.7rem 1.2rem;
    font-size: 0.9rem;
    color: #2c3e50;
    transition: all 0.2s ease;
}

.dropdown-item:hover, .dropdown-item:focus {
    background-color: rgba(13, 110, 253, 0.05);
    color: #0d6efd;
}

.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.05);
    color: #dc3545;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(0,0,0,0.1);
}

/* Ensure dropdown is visible */
.dropdown-menu.show {
    display: block;
    opacity: 1;
    visibility: visible;
    transform: translate(0, 0) !important;
    z-index: 1050;
}

/* Button styles */
.btn-icon.dropdown-toggle::after {
    display: none;
}

.btn-icon {
    padding: 0.5rem;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    color: #2c3e50;
}

.btn-icon:hover, .btn-icon:focus {
    background-color: rgba(0,0,0,0.05);
    color: #0d6efd;
}
</style>