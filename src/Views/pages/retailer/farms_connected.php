<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('RETAILER');
$pageTitle = "Connected Farms";
include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. A list of farms you have access to will appear here.</p>
            <!-- Add connected farm listing here -->
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 