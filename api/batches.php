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
            // Get all batches for the user's farms
            $query = "SELECT wb.*, f.farm_name, pr.status as processing_status
                    FROM wool_batches wb
                    JOIN farms f ON wb.farm_id = f.id
                    LEFT JOIN processing_records pr ON wb.id = pr.batch_id
                    WHERE f.user_id = ?
                    ORDER BY wb.created_at DESC";
            $result = $db->query($query, [$userId]);
            echo json_encode(['batches' => $result->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'POST':
            // Add new batch
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['farmId']) || !isset($data['weight']) || !isset($data['quality'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            // Verify farm belongs to user
            $query = "SELECT id FROM farms WHERE id = ? AND user_id = ?";
            $result = $db->query($query, [$data['farmId'], $userId]);
            $farm = $result->fetch(PDO::FETCH_ASSOC);
            
            if (!$farm) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid farm ID']);
                exit;
            }
            
            // Generate batch number (format: WBYYYYMMDDxxxx)
            $batchNumber = 'WB' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            
            // Begin transaction
            $db->getConnection()->beginTransaction();
            
            try {
                // Insert batch
                $query = "INSERT INTO wool_batches (batch_number, farm_id, weight_kg, quality_grade) 
                         VALUES (?, ?, ?, ?)";
                $db->query($query, [
                    $batchNumber,
                    $data['farmId'],
                    $data['weight'],
                    $data['quality']
                ]);
                
                $batchId = $db->getConnection()->lastInsertId();
                
                // Create processing record
                $query = "INSERT INTO processing_records (batch_id, status) VALUES (?, 'pending')";
                $db->query($query, [$batchId]);
                
                $db->getConnection()->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Batch added successfully',
                    'batchId' => $batchId,
                    'batchNumber' => $batchNumber
                ]);
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                throw $e;
            }
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