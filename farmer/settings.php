<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('FARMER'); // Or appropriate role
$pageTitle = "Settings";
include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. Account and farm settings will appear here.</p>
            <!-- Add settings form here -->
        </div>
    </main>
</div>