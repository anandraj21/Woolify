<?php
// Ensure session is started *only* if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../includes/auth.php'; // Auth implicitly requires Database
require_once __DIR__ . '/../models/Farmer.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php'; // To get retailer user ID
require_once __DIR__ . '/../models/Retailer.php'; // To get retailer ID

// Authentication & Authorization
$auth->requireRole('FARMER');
$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$loggedInUserId = $user['id'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: batches.php?error=' . urlencode('Invalid request method'));
    exit();
}

// --- Input Validation ---
$transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$batchId = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'approve' or 'reject'

// Basic CSRF check would go here if implemented

if (!$transactionId || !$batchId || !in_array($action, ['approve', 'reject'])) {
    header('Location: batches.php?error=' . urlencode('Invalid parameters'));
    exit();
}

// Instantiate models and DB
$dbInstance = Database::getInstance();
$farmerModel = new Farmer();
$woolBatchModel = new WoolBatch();
$transactionModel = new Transaction();
$notificationModel = new Notification();
$retailerModel = new Retailer(); // Needed for retailer user ID

// --- Authorization Check: Ensure farmer owns the batch associated with the transaction ---
$errorMessage = '';
$transaction = null;
$batch = null;
$retailerUserId = null;

try {
    // 1. Get Farmer ID
    $farmerData = $farmerModel->findByUserId($loggedInUserId);
    if (!$farmerData) {
        throw new Exception("Farmer record not found for logged-in user.");
    }
    $farmerId = $farmerData['id'];

    // 2. Fetch Transaction and Batch, verifying ownership
    $sqlVerify = "SELECT t.*, wb.farmer_id, wb.status as batch_status, r.user_id as retailer_user_id
                  FROM transactions t
                  JOIN wool_batches wb ON t.batch_id = wb.id
                  JOIN retailers r ON t.retailer_id = r.id
                  WHERE t.id = ? AND t.batch_id = ? AND wb.farmer_id = ?";
    $stmtVerify = $dbInstance->query($sqlVerify, [$transactionId, $batchId, $farmerId]);
    $transaction = $stmtVerify->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception("Transaction not found, batch mismatch, or not authorized.");
    }

    // 3. Check if the transaction is actually PENDING
    if ($transaction['status'] !== 'PENDING') {
        throw new Exception("This transaction is no longer pending (Status: {$transaction['status']}).");
    }

    $retailerUserId = $transaction['retailer_user_id']; // Get retailer user ID for notification

    // --- Perform Action within a Database Transaction ---
    $dbInstance->beginTransaction();

    $newTransactionStatus = '';
    $newBatchStatus = '';
    $notificationTitle = '';
    $notificationMessage = '';

    if ($action === 'approve') {
        $newTransactionStatus = 'COMPLETED';
        $newBatchStatus = 'SOLD';
        $notificationTitle = 'Purchase Approved';
        $notificationMessage = "Your purchase request for Batch #{$batchId} has been approved by the farmer. Transaction ID: #{$transactionId}.";

    } elseif ($action === 'reject') {
        $newTransactionStatus = 'REJECTED';
        $newBatchStatus = 'AVAILABLE'; // Make batch available again
        $notificationTitle = 'Purchase Rejected';
        $notificationMessage = "Your purchase request for Batch #{$batchId} was rejected by the farmer. Transaction ID: #{$transactionId}.";
    }

    // 4. Update Transaction Status
    $updateTransSuccess = $transactionModel->update($transactionId, ['status' => $newTransactionStatus, 'updated_at' => date('Y-m-d H:i:s')]);
    if (!$updateTransSuccess) {
        throw new Exception("Failed to update transaction status.");
    }

    // 5. Update Wool Batch Status
    $updateBatchSuccess = $woolBatchModel->update($batchId, ['status' => $newBatchStatus, 'updated_at' => date('Y-m-d H:i:s')]);
    if (!$updateBatchSuccess) {
        throw new Exception("Failed to update batch status.");
    }

    // 6. Create Notification for Retailer
    if ($retailerUserId) {
        $notificationData = [
            'user_id' => $retailerUserId,
            'title' => $notificationTitle,
            'message' => $notificationMessage,
            'type' => 'transaction_' . $action, // e.g., transaction_approve
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $notificationSuccess = $notificationModel->create($notificationData);
        if (!$notificationSuccess) {
            error_log("Failed to create notification for retailer user ID: {$retailerUserId} regarding transaction action: {$action}, TxID: {$transactionId}");
            // Decide if this should fail the whole operation - probably not.
        }
    } else {
        error_log("Could not find retailer user ID to notify for transaction {$transactionId}.");
    }

    // 7. Commit Transaction
    $dbInstance->commit();

    // --- Success Redirect ---
    $_SESSION['success_message'] = "Transaction #{$transactionId} has been successfully {$action}d.";
    header('Location: batches.php?status=PENDING'); // Redirect back to pending view or just batches.php
    exit();

} catch (Exception $e) {
    // Rollback on error
    if ($dbInstance && $dbInstance->inTransaction()) {
        $dbInstance->rollBack();
    }
    $errorMessage = $e->getMessage();
    error_log("Error processing transaction action ({$action}) by farmer {$loggedInUserId}: " . $errorMessage . " for TxID: {$transactionId}, BatchID: {$batchId}");
    $_SESSION['error_message'] = "Error: " . $errorMessage;
    header('Location: batches.php');
    exit();
}

?> 