<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get notifications for the user
        $stmt = $conn->prepare("
            SELECT id, type, title, message, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($notifications);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['notification_id'])) {
        try {
            // Mark notification as read
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$data['notification_id'], $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing notification_id']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 