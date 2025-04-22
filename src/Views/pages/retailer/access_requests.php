<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('RETAILER');
$pageTitle = "My Access Requests";
include __DIR__ . '/../includes/header.php'; 

// TODO: Fetch access requests for this retailer from the database

?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. Your requests to access farm data will be listed here.</p>
            <!-- Add access request listing and potentially a form to create new requests -->
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 