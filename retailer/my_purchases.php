<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php'; // Include helpers
require_once __DIR__ . '/../models/Transaction.php';

$auth->requireRole('RETAILER');
$pageTitle = "My Purchase History";

// Get retailer ID
$user = $auth->getUser();
$userId = $user['id'];
$dbInstance = Database::getInstance(); 
$stmtRetailer = $dbInstance->query("SELECT id FROM retailers WHERE user_id = ?", [$userId]);
$retailer = $stmtRetailer->fetch();
if (!$retailer) {
    die("Error: Retailer record not found for this user.");
}
$retailerId = $retailer['id'];

// Fetch transactions for this retailer
$transactionModel = new Transaction();
$orderBy = 'transaction_date DESC'; // Show most recent first

$purchases = [];
try {
    // Fetch transactions with farmer details using a JOIN
    // (Ideally, this JOIN logic would be in a Transaction model method)
    $sql = "SELECT t.*, wb.farmer_id, u.name as farmer_name, wb.grade as batch_grade 
            FROM transactions t 
            JOIN wool_batches wb ON t.batch_id = wb.id
            JOIN farmers f ON wb.farmer_id = f.id
            JOIN users u ON f.user_id = u.id
            WHERE t.retailer_id = ? 
            ORDER BY $orderBy"; // Note: Using variable in ORDER BY needs caution/validation if user-controlled
            
    $stmt = $dbInstance->query($sql, [$retailerId]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching purchases: " . $e->getMessage());
    $fetchError = "Could not retrieve purchase history.";
}

// Helpers are included

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>

            <?php if (isset($fetchError)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php endif; ?>

            <!-- TODO: Add Filtering/Sorting options (Date range, status) -->

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
                                <th>Price ($)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">You have not made any purchases yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($purchase['id']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($purchase['transaction_date'])); ?></td>
                                        <td><a href="../view_batch.php?id=<?php echo $purchase['batch_id']; ?>">#<?php echo htmlspecialchars($purchase['batch_id']); ?></a></td>
                                        <td><span class="badge bg-<?php echo getQualityClass($purchase['batch_grade']); ?>"><?php echo getQualityLabel($purchase['batch_grade']); ?></span></td>
                                        <td><?php echo htmlspecialchars($purchase['farmer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format((float)($purchase['quantity'] ?? 0), 1); ?></td>
                                        <td><?php echo number_format((float)($purchase['total_price'] ?? 0), 2); ?></td>
                                        <td><span class="badge rounded-pill bg-<?php echo getStatusClass($purchase['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($purchase['status']))); ?></span></td>
                                        <td>
                                            <a href="view_transaction.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                            <!-- Add other actions if applicable, e.g., print invoice -->
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
<?php include __DIR__ . '/../includes/footer.php'; ?>