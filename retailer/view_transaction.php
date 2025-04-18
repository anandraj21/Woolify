<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('RETAILER');

$transactionId = $_GET['id'] ?? null;
if (!$transactionId) {
    header('Location: my_purchases.php'); // Redirect if no ID is provided
    exit();
}

// TODO: Fetch transaction details from the database using $transactionId

$pageTitle = "View Transaction #" . htmlspecialchars($transactionId);
include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. Details for transaction #<?php echo htmlspecialchars($transactionId); ?> will appear here.</p>
            <!-- Add transaction details display here -->
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 