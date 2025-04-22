<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

function getTotalBatches($userId) {
    $db = new Database();
    $query = "SELECT COUNT(*) as total FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ?";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

function getProcessingCount($userId) {
    $db = new Database();
    $query = "SELECT COUNT(*) as count FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ? AND wb.status = 'processing'";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getCompletedCount($userId) {
    $db = new Database();
    $query = "SELECT COUNT(*) as count FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ? AND wb.status = 'completed'";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getRejectedCount($userId) {
    $db = new Database();
    $query = "SELECT COUNT(*) as count FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ? AND wb.status = 'rejected'";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getAverageQuality($userId) {
    $db = new Database();
    $query = "SELECT AVG(CASE 
                WHEN quality_grade = 'A' THEN 5 
                WHEN quality_grade = 'B' THEN 4 
                WHEN quality_grade = 'C' THEN 3 
                WHEN quality_grade = 'D' THEN 2 
                WHEN quality_grade = 'E' THEN 1 
                END) as avg_quality 
              FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ?";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $avgQuality = $row['avg_quality'] ?? 0;
    
    // Convert numeric average back to letter grade
    if ($avgQuality >= 4.5) return 'A';
    if ($avgQuality >= 3.5) return 'B';
    if ($avgQuality >= 2.5) return 'C';
    if ($avgQuality >= 1.5) return 'D';
    return 'E';
}

function getTotalWeight($userId) {
    $db = new Database();
    $query = "SELECT SUM(weight_kg) as total_weight 
              FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ?";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return number_format($row['total_weight'] ?? 0, 2);
}

function getFarmCount($userId) {
    $db = new Database();
    $query = "SELECT COUNT(*) as count FROM farms WHERE user_id = ?";
    $result = $db->query($query, [$userId]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getQualityDistribution($userId) {
    $db = new Database();
    $query = "SELECT quality_grade, COUNT(*) as count 
              FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ? 
              GROUP BY quality_grade 
              ORDER BY quality_grade";
    $result = $db->query($query, [$userId]);
    return $result->fetchAll(PDO::FETCH_ASSOC);
}

function getWeightOverTime($userId, $months = 12) {
    $db = new Database();
    $query = "SELECT 
                DATE_FORMAT(wb.created_at, '%Y-%m') as month,
                SUM(weight_kg) as total_weight
              FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              WHERE f.user_id = ? 
              AND wb.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? MONTH)
              GROUP BY DATE_FORMAT(wb.created_at, '%Y-%m')
              ORDER BY month";
    $result = $db->query($query, [$userId, $months]);
    return $result->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentBatches($userId, $limit = 10) {
    $db = new Database();
    $query = "SELECT 
                wb.batch_number,
                f.farm_name,
                wt.type_name as wool_type,
                wb.weight_kg,
                wb.quality_grade,
                wb.status,
                wb.created_at
              FROM wool_batches wb 
              JOIN farms f ON wb.farm_id = f.id 
              JOIN wool_types wt ON wb.wool_type_id = wt.id
              WHERE f.user_id = ? 
              ORDER BY wb.created_at DESC 
              LIMIT " . (int)$limit;
    $result = $db->query($query, [$userId]);
    return $result->fetchAll(PDO::FETCH_ASSOC);
}

function getTopFarms($userId, $limit = 5) {
    $db = new Database();
    $query = "SELECT 
                f.farm_name,
                COUNT(wb.id) as batch_count,
                SUM(wb.weight_kg) as total_weight,
                AVG(CASE 
                    WHEN quality_grade = 'A' THEN 5 
                    WHEN quality_grade = 'B' THEN 4 
                    WHEN quality_grade = 'C' THEN 3 
                    WHEN quality_grade = 'D' THEN 2 
                    WHEN quality_grade = 'E' THEN 1 
                END) as avg_quality
              FROM farms f
              LEFT JOIN wool_batches wb ON f.id = wb.farm_id
              WHERE f.user_id = ?
              GROUP BY f.id, f.farm_name
              ORDER BY total_weight DESC
              LIMIT " . (int)$limit;
    $result = $db->query($query, [$userId]);
    return $result->fetchAll(PDO::FETCH_ASSOC);
}

// API Endpoints
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    try {
        switch ($_GET['action']) {
            case 'dashboard_stats':
                $response = [
                    'totalBatches' => getTotalBatches($userId),
                    'processingCount' => getProcessingCount($userId),
                    'completedCount' => getCompletedCount($userId),
                    'rejectedCount' => getRejectedCount($userId),
                    'averageQuality' => getAverageQuality($userId),
                    'totalWeight' => getTotalWeight($userId),
                    'farmCount' => getFarmCount($userId),
                    'qualityDistribution' => getQualityDistribution($userId),
                    'weightOverTime' => getWeightOverTime($userId),
                    'recentBatches' => getRecentBatches($userId),
                    'topFarms' => getTopFarms($userId)
                ];
                echo json_encode($response);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
    }
    exit;
}
?> 