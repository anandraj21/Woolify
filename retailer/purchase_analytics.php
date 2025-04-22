<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Transaction.php';
$auth->requireRole('RETAILER');

$pageTitle = "Purchase Analytics";

// Get retailer ID
$user = $auth->getUser();
$userId = $user['id'];
$dbInstance = Database::getInstance();
$stmt = $dbInstance->query("SELECT id FROM retailers WHERE user_id = ?", [$userId]);
$retailer = $stmt->fetch();
if (!$retailer) {
    die("Error: Retailer record not found.");
}
$retailerId = $retailer['id'];

// Initialize variables
$purchaseStats = [];
$monthlyTrends = [];
$qualityDistribution = [];
$supplierStats = [];
$fetchError = '';

try {
    // Get overall purchase statistics
    $statsQuery = "SELECT 
        COUNT(*) as total_purchases,
        SUM(t.quantity) as total_quantity,
        AVG(t.price_per_kg) as avg_price,
        SUM(t.quantity * t.price_per_kg) as total_spent
        FROM transactions t
        WHERE t.retailer_id = ? AND t.status = 'COMPLETED'";
    $purchaseStats = $dbInstance->query($statsQuery, [$retailerId])->fetch();

    // Get monthly purchase trends
    $trendsQuery = "SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m') as month,
        COUNT(*) as purchase_count,
        SUM(t.quantity) as total_quantity,
        AVG(t.price_per_kg) as avg_price
        FROM transactions t
        WHERE t.retailer_id = ? 
        AND t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
        ORDER BY month DESC";
    $monthlyTrends = $dbInstance->query($trendsQuery, [$retailerId])->fetchAll();

    // Get quality distribution of purchases
    $qualityQuery = "SELECT 
        wb.grade,
        COUNT(*) as batch_count,
        SUM(t.quantity) as total_quantity,
        AVG(t.price_per_kg) as avg_price
        FROM transactions t
        JOIN wool_batches wb ON t.batch_id = wb.id
        WHERE t.retailer_id = ? AND t.status = 'COMPLETED'
        GROUP BY wb.grade";
    $qualityDistribution = $dbInstance->query($qualityQuery, [$retailerId])->fetchAll();

    // Get supplier (farmer) statistics
    $supplierQuery = "SELECT 
        f.farm_name,
        COUNT(*) as transaction_count,
        SUM(t.quantity) as total_quantity,
        AVG(t.price_per_kg) as avg_price
        FROM transactions t
        JOIN wool_batches wb ON t.batch_id = wb.id
        JOIN farmers f ON wb.farmer_id = f.id
        WHERE t.retailer_id = ? AND t.status = 'COMPLETED'
        GROUP BY f.id, f.farm_name
        ORDER BY total_quantity DESC
        LIMIT 5";
    $supplierStats = $dbInstance->query($supplierQuery, [$retailerId])->fetchAll();

} catch (Exception $e) {
    error_log("Error in purchase_analytics.php: " . $e->getMessage());
    $fetchError = "An error occurred while fetching analytics data.";
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Analyze your wool purchasing patterns and trends.</p>

            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php else: ?>
                <!-- Overall Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <div class="stat-card-icon bg-primary">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-card-info">
                                    <div class="stat-card-title">Total Purchases</div>
                                    <div class="stat-card-value"><?php echo number_format($purchaseStats['total_purchases']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <div class="stat-card-icon bg-success">
                                    <i class="fas fa-weight-hanging"></i>
                                </div>
                                <div class="stat-card-info">
                                    <div class="stat-card-title">Total Quantity</div>
                                    <div class="stat-card-value"><?php echo number_format($purchaseStats['total_quantity'], 1); ?> kg</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <div class="stat-card-icon bg-info">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stat-card-info">
                                    <div class="stat-card-title">Avg. Price/kg</div>
                                    <div class="stat-card-value">$<?php echo number_format($purchaseStats['avg_price'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <div class="stat-card-icon bg-warning">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-card-info">
                                    <div class="stat-card-title">Total Spent</div>
                                    <div class="stat-card-value">$<?php echo number_format($purchaseStats['total_spent'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Purchase Trends</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Purchases</th>
                                        <th>Quantity (kg)</th>
                                        <th>Avg. Price/kg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthlyTrends as $trend): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></td>
                                        <td><?php echo $trend['purchase_count']; ?></td>
                                        <td><?php echo number_format($trend['total_quantity'], 1); ?></td>
                                        <td>$<?php echo number_format($trend['avg_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Quality Distribution -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Quality Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Grade</th>
                                                <th>Batches</th>
                                                <th>Quantity</th>
                                                <th>Avg. Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($qualityDistribution as $quality): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php echo getQualityClass($quality['grade']); ?>">
                                                        <?php echo $quality['grade']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $quality['batch_count']; ?></td>
                                                <td><?php echo number_format($quality['total_quantity'], 1); ?> kg</td>
                                                <td>$<?php echo number_format($quality['avg_price'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Suppliers -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top Suppliers</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Farm</th>
                                                <th>Transactions</th>
                                                <th>Total Qty</th>
                                                <th>Avg. Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($supplierStats as $supplier): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($supplier['farm_name']); ?></td>
                                                <td><?php echo $supplier['transaction_count']; ?></td>
                                                <td><?php echo number_format($supplier['total_quantity'], 1); ?> kg</td>
                                                <td>$<?php echo number_format($supplier['avg_price'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
.stat-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.stat-card-body {
    padding: 1.5rem;
    display: flex;
    align-items: center;
}

.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-card-icon i {
    color: white;
    font-size: 1.5rem;
}

.stat-card-info {
    flex-grow: 1;
}

.stat-card-title {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.stat-card-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}
</style>

<?php
// Helper function for quality badge colors
function getQualityClass($grade) {
    switch ($grade) {
        case 'A': return 'success';
        case 'B': return 'primary';
        case 'C': return 'warning';
        default: return 'secondary';
    }
}
?> 