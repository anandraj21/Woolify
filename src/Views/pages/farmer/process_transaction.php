<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // To get farmer ID
require_once __DIR__ . '/../models/Notification.php'; // Include Notification model
require_once __DIR__ . '/../models/User.php'; // Include User model to get retailer user ID

$auth->requireRole('FARMER');

// Get Farmer ID and Name
$user = $auth->getUser();
$farmerUserId = $user['id'];
$farmerName = $user['name']; // Get farmer name for notification message
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($farmerUserId);
if (!$farmerData) {
    header('Location: batches.php?error=farmer_not_found');
    exit();
}
$farmerId = $farmerData['id'];

// Get and validate parameters
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$transactionId = filter_input(INPUT_GET, 'tid', FILTER_VALIDATE_INT);
$batchId = filter_input(INPUT_GET, 'bid', FILTER_VALIDATE_INT);

if (!$action || !$transactionId || !$batchId || !in_array($action, ['approve', 'reject'])) {
    header('Location: batches.php?error=invalid_params');
    exit();
}

// Instantiate models
$transactionModel = new Transaction();
$woolBatchModel = new WoolBatch();
$notificationModel = new Notification(); // Instantiate Notification model
$userModel = new User(); // Instantiate User model
$dbInstance = Database::getInstance();

try {
    // Fetch the transaction to verify ownership and status
    $transaction = $transactionModel->findById($transactionId);

    // --- Verification Checks ---
    if (!$transaction) {
        header('Location: batches.php?error=transaction_not_found');
        exit();
    }
    if ($transaction['farmer_id'] != $farmerId) {
        header('Location: batches.php?error=permission_denied');
        exit();
    }
    if ($transaction['batch_id'] != $batchId) {
        header('Location: batches.php?error=batch_mismatch');
        exit();
    }
    if ($transaction['status'] !== 'PENDING') {
        header('Location: batches.php?error=not_pending');
        exit();
    }

    // --- Process Action ---
    $dbInstance->getConnection()->beginTransaction();

    $retailerUserId = $userModel->getUserIdFromRetailerId($transaction['retailer_id']);
    $notificationMessage = '';
    $notificationType = '';
    $redirectParam = '';

    if ($action === 'approve') {
        $updateTransaction = $transactionModel->updateStatus($transactionId, 'COMPLETED');
        $updateBatch = $woolBatchModel->updateStatus($batchId, 'SOLD');

        if ($updateTransaction && $updateBatch) {
            $dbInstance->getConnection()->commit();
            // Prepare notification for Retailer
            if ($retailerUserId) {
                $notificationMessage = htmlspecialchars($farmerName) . " has approved your purchase request for Batch #{$batchId}.";
                $notificationType = 'TRANSACTION_APPROVED';
                $notificationModel->createNotification($retailerUserId, $notificationType, $notificationMessage, $transactionId);
            } else {
                 error_log("Failed to find retailer user ID for retailer ID: {$transaction['retailer_id']}");
            }
            header('Location: batches.php?success=approved');
            exit();
        } else {
            $dbInstance->getConnection()->rollBack();
            header('Location: batches.php?error=approve_failed');
            exit();
        }

    } elseif ($action === 'reject') {
        $updateTransaction = $transactionModel->updateStatus($transactionId, 'REJECTED');
        $updateBatch = $woolBatchModel->updateStatus($batchId, 'AVAILABLE'); 

        if ($updateTransaction && $updateBatch) {
            $dbInstance->getConnection()->commit();
            // Prepare notification for Retailer
             if ($retailerUserId) {
                $notificationMessage = htmlspecialchars($farmerName) . " has rejected your purchase request for Batch #{$batchId}.";
                $notificationType = 'TRANSACTION_REJECTED';
                $notificationModel->createNotification($retailerUserId, $notificationType, $notificationMessage, $transactionId);
            } else {
                 error_log("Failed to find retailer user ID for retailer ID: {$transaction['retailer_id']}");
            }
            header('Location: batches.php?success=rejected');
            exit();
        } else {
            $dbInstance->getConnection()->rollBack();
            header('Location: batches.php?error=reject_failed');
            exit();
        }
    }

} catch (Exception $e) {
    if ($dbInstance->getConnection()->inTransaction()) {
        $dbInstance->getConnection()->rollBack();
    }
    error_log("Error processing transaction ({$action}, tid:{$transactionId}): " . $e->getMessage());
    header('Location: batches.php?error=server_error');
    exit();
}

header('Location: batches.php?error=unknown');
exit();

?> 