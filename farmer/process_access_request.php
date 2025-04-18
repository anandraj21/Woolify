<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/AccessRequest.php';
require_once __DIR__ . '/../models/Farmer.php'; // To get farmer ID
require_once __DIR__ . '/../models/Notification.php'; // To notify retailer
require_once __DIR__ . '/../models/User.php'; // To get retailer user ID

$auth->requireRole('FARMER');

// Get Farmer ID and Name
$user = $auth->getUser();
$farmerUserId = $user['id'];
$farmerName = $user['name']; // Get farmer name for notification
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($farmerUserId);
if (!$farmerData) {
    header('Location: access_requests.php?error=farmer_not_found');
    exit();
}
$farmerId = $farmerData['id'];

// Get and validate POST parameters
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$action || !$requestId || !in_array($action, ['grant', 'reject'])) {
    header('Location: access_requests.php?error=invalid_action');
    exit();
}

// Instantiate models
$accessRequestModel = new AccessRequest();
$notificationModel = new Notification();
$userModel = new User();

$newStatus = ($action === 'grant') ? 'GRANTED' : 'REJECTED';
$successMessage = ($action === 'grant') ? 'granted' : 'rejected';
$errorMessage = ($action === 'grant') ? 'grant_failed' : 'reject_failed';


try {
    // Fetch the request to get retailer_id for notification
    $request = $accessRequestModel->read($requestId); 
    if (!$request || $request['farmer_id'] != $farmerId) {
         header('Location: access_requests.php?error=permission_denied');
         exit();
    }
    if ($request['status'] !== 'PENDING') {
        header('Location: access_requests.php?error=already_processed');
        exit();
    }
    
    $retailerId = $request['retailer_id'];

    // Attempt to update the status
    if ($accessRequestModel->updateRequestStatus($requestId, $newStatus, $farmerId)) {
        // Success - Create notification for Retailer
        $retailerUserId = $userModel->getUserIdFromRetailerId($retailerId);
        if ($retailerUserId) {
             $notificationMessage = htmlspecialchars($farmerName) . " has {$successMessage} your request for access.";
             $notificationType = ($newStatus === 'GRANTED') ? 'ACCESS_GRANTED' : 'ACCESS_REJECTED';
             $notificationModel->createNotification($retailerUserId, $notificationType, $notificationMessage, $requestId);
        } else {
            error_log("Could not find retailer user ID for retailer ID {$retailerId} to send access request notification.");
        }
        
        header('Location: access_requests.php?success=' . $successMessage);
        exit();
    } else {
        // Update failed (could be concurrent update or DB error)
        header('Location: access_requests.php?error=' . $errorMessage);
        exit();
    }

} catch (Exception $e) {
    error_log("Error processing access request {$requestId} (Action: {$action}) for farmer {$farmerId}: " . $e->getMessage());
    header('Location: access_requests.php?error=server_error');
    exit();
}

?> 