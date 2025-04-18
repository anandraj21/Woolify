<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all farms for the user
            $query = "SELECT id, farm_name FROM farms WHERE user_id = ? ORDER BY farm_name";
            $result = $db->query($query, [$userId]);
            echo json_encode($result->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Add new farm
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['farmName']) || !isset($data['location'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $query = "INSERT INTO farms (farm_name, location, user_id) VALUES (?, ?, ?)";
            $db->query($query, [
                $data['farmName'],
                $data['location'],
                $userId
            ]);
            
            // Get the newly inserted farm's ID
            $farmId = $db->getConnection()->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Farm added successfully',
                'farmId' => $farmId
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
?> 