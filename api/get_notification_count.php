<?php
header('Content-Type: application/json');

// Ensure session is started *only* if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

require_once __DIR__ . '/../includes/auth.php'; // Auth implicitly requires Database
require_once __DIR__ . '/../models/Notification.php';

// Use the existing auth instance
global $auth; 

$response = ['unread_count' => 0]; // Default response

if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    $userId = $user['id'];
    
    try {
        $notificationModel = new Notification(); // BaseModel handles DB connection
        $unreadCount = $notificationModel->getUnreadCount($userId);
        $response['unread_count'] = (int)$unreadCount; // Ensure it's an integer
    } catch (Exception $e) {
        error_log("API Error (get_notification_count): " . $e->getMessage());
        // Keep count at 0 in case of error, or send an error status
        // http_response_code(500); 
        // $response = ['error' => 'Could not fetch notification count'];
    }
} else {
    // Optional: Handle case where user is not logged in (e.g., session expired)
    // The count will remain 0 based on the default $response
    // http_response_code(401); // Unauthorized
    // $response = ['error' => 'Not authenticated'];
}

echo json_encode($response);
exit();
?> 