<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('FARMER');

$batchId = $_GET['id'] ?? null;
if (!$batchId) {
    header('Location: batches.php'); // Redirect if no ID is provided
    exit();
}

// TODO: Fetch batch details from the database using $batchId for pre-filling form

$pageTitle = "Edit Batch #" . htmlspecialchars($batchId);
include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p>This page is under construction. A form to edit batch #<?php echo htmlspecialchars($batchId); ?> will appear here.</p>
            <!-- Add batch edit form here -->
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 