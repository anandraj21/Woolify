<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/AccessRequest.php';
require_once __DIR__ . '/../models/Farmer.php'; // To get farmer ID
require_once __DIR__ . '/../includes/helpers.php'; // For status formatting

$auth->requireRole('FARMER');
$pageTitle = "Retailer Access Requests";

// Get Farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    die("Error: Farmer record not found.");
}
$farmerId = $farmerData['id'];

$accessRequestModel = new AccessRequest();
$requests = [];
$fetchError = '';

// Fetch PENDING requests for this farmer
try {
    // Fetching only PENDING by default, but could add filters later
    $requests = $accessRequestModel->findRequestsByFarmer($farmerId, 'PENDING'); 
} catch (Exception $e) {
    error_log("Error fetching access requests for farmer {$farmerId}: " . $e->getMessage());
    $fetchError = "Could not retrieve access requests.";
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Review requests from retailers seeking access to your farm data or batches.</p>

             <?php if (isset($_GET['success'])) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Access request <?php echo htmlspecialchars($_GET['success']); ?> successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (isset($_GET['error'])) : ?>
                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error processing request: <?php echo htmlspecialchars($_GET['error']); ?>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php endif; ?>

             <!-- TODO: Add tabs or filters to view GRANTED/REJECTED requests -->

            <div class="table-section">
                 <h5 class="mb-3">Pending Requests</h5>
                 <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Retailer Name</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No pending access requests.</td></tr>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($request['id']); ?></td>
                                        <td><?php echo htmlspecialchars($request['retailer_name'] ?? 'Unknown Retailer'); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($request['request_date'])); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php echo getStatusClass($request['status']); ?>">
                                                 <?php echo ucfirst(strtolower(htmlspecialchars($request['status']))); ?>
                                             </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'PENDING'): ?>
                                                <form method="POST" action="process_access_request.php" class="d-inline me-1">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="grant">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Grant Access" onclick="return confirm('Grant access to <?php echo htmlspecialchars($request['retailer_name'] ?? 'this retailer'); ?>?');">
                                                        <i class="fas fa-check"></i> Grant
                                                    </button>
                                                </form>
                                                <form method="POST" action="process_access_request.php" class="d-inline">
                                                     <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                     <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Reject Access" onclick="return confirm('Reject access for <?php echo htmlspecialchars($request['retailer_name'] ?? 'this retailer'); ?>?');">
                                                         <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> 

        </div>
    </main>
</div>