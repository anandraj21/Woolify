<?php
// Ensure session is started *only* if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../includes/auth.php'; // Auth implicitly requires Database
require_once __DIR__ . '/../models/Retailer.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php'; // To get farmer user ID

// Authentication & Authorization
$auth->requireRole('RETAILER');
$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$loggedInUserId = $user['id'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php'); // Redirect if not POST
    exit();
}

// --- Input Validation ---
$batchId = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
$postedRetailerId = filter_input(INPUT_POST, 'retailer_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
$pricePerKg = filter_input(INPUT_POST, 'price_per_kg', FILTER_VALIDATE_FLOAT);
$totalAmount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
// Basic CSRF check would go here if implemented
// if (!$auth->verifyCsrfToken('purchase_request', $_POST['_csrf_token'] ?? '')) {
//     $_SESSION['error_message'] = 'Invalid request. Please try again.';
//     header('Location: dashboard.php');
//     exit();
// }

// Instantiate models and DB
$dbInstance = Database::getInstance();
$retailerModel = new Retailer();
$woolBatchModel = new WoolBatch();
$transactionModel = new Transaction();
$notificationModel = new Notification();
$userModel = new User();

// Get retailer ID based on logged-in user ID
$retailerData = $retailerModel->findByUserId($loggedInUserId);
if (!$retailerData || $retailerData['id'] !== $postedRetailerId) {
    $_SESSION['error_message'] = 'Authentication error.';
    header('Location: dashboard.php');
    exit();
}
$retailerId = $retailerData['id'];

// --- Business Logic Validation ---
$errorMessage = '';
try {
    // 1. Fetch the batch
    $batch = $woolBatchModel->read($batchId); // Using BaseModel's read

    if (!$batch) {
        $errorMessage = "Batch #{$batchId} not found.";
    } elseif ($batch['status'] !== 'AVAILABLE') {
        $errorMessage = "Batch #{$batchId} is no longer available for purchase (Status: {$batch['status']}).";
    } elseif ($batch['quantity'] != $quantity || $batch['price_per_kg'] != $pricePerKg) {
        // Potential data mismatch (e.g., farmer changed price/qty after retailer loaded page)
        $errorMessage = "Batch details (quantity or price) have changed. Please review the batch again.";
    } elseif ($totalAmount != round($quantity * $pricePerKg, 2)) {
        $errorMessage = "Total amount calculation mismatch. Please try again.";
    }

    if ($errorMessage) {
        $_SESSION['error_message'] = $errorMessage;
        header('Location: view_batch_details.php?id=' . $batchId);
        exit();
    }

    // --- Database Operations (Transaction) ---
    $dbInstance->beginTransaction();

    // 2. Create Transaction Record
    $transactionData = [
        'batch_id' => $batchId,
        'retailer_id' => $retailerId,
        'quantity' => $quantity,
        'price_per_kg' => $pricePerKg,
        'total_amount' => $totalAmount,
        'status' => 'PENDING', // Initial status
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $transactionSuccess = $transactionModel->create($transactionData);

    if (!$transactionSuccess) {
        throw new Exception("Failed to create transaction record.");
    }
    $transactionId = $dbInstance->lastInsertId();

    // 3. Update Wool Batch Status
    $updateBatchSuccess = $woolBatchModel->update($batchId, ['status' => 'PENDING']);
    if (!$updateBatchSuccess) {
        throw new Exception("Failed to update batch status.");
    }

    // 4. Create Notification for Farmer
    //    a. Get Farmer's user_id from the batch
    $stmtFarmerUser = $dbInstance->query("SELECT user_id FROM farmers WHERE id = ?", [$batch['farmer_id']]);
    $farmerUserId = $stmtFarmerUser->fetchColumn();

    if (!$farmerUserId) {
         throw new Exception("Failed to find farmer user ID for notification.");
    }

    //    b. Create the notification
    $notificationData = [
        'user_id' => $farmerUserId,
        'title' => 'Purchase Request Received',
        'message' => "Retailer '{$retailerData['store_name']}' has requested to purchase Batch #{$batchId}. Transaction ID: #{$transactionId}. Please review and approve/reject.",
        'type' => 'purchase_request',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $notificationSuccess = $notificationModel->create($notificationData);
    if (!$notificationSuccess) {
        // Log error, but don't necessarily fail the whole transaction? Or should we?
        // For now, let's log it but proceed.
        error_log("Failed to create notification for farmer user ID: {$farmerUserId} regarding transaction ID: {$transactionId}");
    }

    // 5. Commit Transaction
    $dbInstance->commit();

    // --- Success Redirect ---
    $_SESSION['success_message'] = "Purchase request for Batch #{$batchId} sent successfully!";
    header('Location: dashboard.php');
    exit();

} catch (Exception $e) {
    // Rollback on error
    if ($dbInstance->inTransaction()) {
        $dbInstance->rollBack();
    }
    error_log("Error processing purchase request: " . $e->getMessage() . " for Batch ID: {$batchId}, Retailer ID: {$retailerId}");
    $_SESSION['error_message'] = "An error occurred while processing your request: " . $e->getMessage();
    // Redirect back to details page if possible, otherwise dashboard
    $redirectUrl = $batchId ? 'view_batch_details.php?id=' . $batchId : 'dashboard.php';
    header('Location: ' . $redirectUrl);
    exit();
}

?> 