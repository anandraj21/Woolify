<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Farm.php'; // Use the new Farm model

$auth->requireRole('FARMER');
$pageTitle = "My Farm Details";

$user = $auth->getUser();
$userId = $user['id'];

$farmModel = new Farm();
$farmData = null;
$fetchError = '';

try {
    $farmData = $farmModel->findFarmByUserId($userId);
} catch (Exception $e) {
    error_log("Error fetching farm data for user {$userId}: " . $e->getMessage());
    $fetchError = "Could not retrieve farm details. Please try again later.";
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $pageTitle; ?></h1>
                <?php if ($farmData): ?>
                    <a href="edit_farm.php?id=<?php echo $farmData['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Farm Details
                    </a>
                <?php endif; ?>
                <!-- Note: Add New Farm link directs to add_farm.php (as per sidebar) -->
                 <a href="add_farm.php" class="btn btn-success ms-2"> <i class="fas fa-plus me-2"></i>Add New Farm </a>
            </div>

            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php elseif (!$farmData): ?>
                <div class="alert alert-warning" role="alert">
                    No farm details found for your account. 
                    <a href="add_farm.php" class="alert-link">Add your farm details now</a>.
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        Farm Information
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Farm Name</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($farmData['farm_name'] ?? 'N/A'); ?></dd>

                            <dt class="col-sm-3">Location</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($farmData['farm_location'] ?? 'N/A'); ?></dd>

                            <dt class="col-sm-3">Size (Acres)</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($farmData['farm_size_acres'] ?? 'N/A'); ?></dd>

                            <dt class="col-sm-3">Contact Number</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($farmData['contact_number'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-3">Associated User</dt>
                            <dd class="col-sm-9">#<?php echo htmlspecialchars($farmData['user_id']); ?> (<?php echo htmlspecialchars($user['name']); ?>)</dd>

                            <dt class="col-sm-3">Record Created</dt>
                            <dd class="col-sm-9"><?php echo date('d M Y, H:i', strtotime($farmData['created_at'])); ?></dd>

                            <dt class="col-sm-3">Last Updated</dt>
                            <dd class="col-sm-9"><?php echo date('d M Y, H:i', strtotime($farmData['updated_at'])); ?></dd>
                        </dl>
                    </div>
                </div>
                 <div class="alert alert-info mt-3" role="alert">
                    Note: Currently, the system supports one primary farm record per user account. The "Add New Farm" feature is intended for initial setup if details are missing.
                 </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 