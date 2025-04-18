<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('FARMER');
$pageTitle = "Reports";
include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. Report generation options will appear here.</p>
            <!-- Add report generation features here -->
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 