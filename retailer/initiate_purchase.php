<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Retailer.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';

$auth->requireRole('RETAILER');

$batchId = filter_input(INPUT_GET, 'batch_id', FILTER_VALIDATE_INT);

if (!$batchId) {
    header('Location: browse_batches.php?error=invalid_batch');
    exit();
}

// Get retailer ID and name
$user = $auth->getUser();
$retailerUserId = $user['id'];
$retailerName = $user['name'];
$retailerModel = new Retailer();
$retailerData = $retailerModel->findByUserId($retailerUserId);
if (!$retailerData) {
    header('Location: browse_batches.php?error=retailer_not_found');
    exit();
}
$retailerId = $retailerData['id'];

// Get batch details
$woolBatchModel = new WoolBatch();
$batch = $woolBatchModel->read($batchId);

if (!$batch || $batch['status'] !== 'AVAILABLE') {
    header('Location: browse_batches.php?error=batch_unavailable');
    exit();
}

// --- Create Transaction --- 
$transactionModel = new Transaction();
$notificationModel = new Notification();
$userModel = new User();

$transactionData = [
    'batch_id' => $batchId,
    'farmer_id' => $batch['farmer_id'],
    'retailer_id' => $retailerId,
    'quantity' => $batch['quantity'],
    'total_price' => $batch['price'] * $batch['quantity'],
    'status' => 'PENDING',
    'transaction_date' => date('Y-m-d H:i:s')
];

$dbInstance = Database::getInstance();
try {
    $dbInstance->getConnection()->beginTransaction();

    $createdTransactionId = $transactionModel->create($transactionData);

    if ($createdTransactionId) {
        $batchUpdated = $woolBatchModel->updateStatus($batchId, 'PENDING'); 
        
        if (!$batchUpdated) {
            throw new Exception("Failed to update batch status.");
        }

        $farmerUserId = $userModel->getUserIdFromFarmerId($batch['farmer_id']);

        if ($farmerUserId) {
            $message = htmlspecialchars($retailerName) . " has requested to purchase Batch #{$batchId}.";
            $notificationCreated = $notificationModel->createNotification(
                $farmerUserId, 
                'PURCHASE_REQUEST', 
                $message, 
                $createdTransactionId
            );
            if (!$notificationCreated) {
                error_log("Failed to create notification for farmer user ID: {$farmerUserId} regarding transaction ID: {$createdTransactionId}");
            }
        } else {
            error_log("Failed to find farmer user ID for farmer ID: {$batch['farmer_id']} - cannot send notification.");
        }

        $dbInstance->getConnection()->commit();
        header('Location: my_purchases.php?success=purchase_initiated');
        exit();

    } else {
        $dbInstance->getConnection()->rollBack();
        header('Location: browse_batches.php?error=failed_initiate');
        exit();
    }
} catch (Exception $e) {
    if ($dbInstance->getConnection()->inTransaction()) {
        $dbInstance->getConnection()->rollBack();
    }
    error_log("Error initiating purchase for batch {$batchId}: " . $e->getMessage());
    header('Location: browse_batches.php?error=server_error');
    exit();
}

?>