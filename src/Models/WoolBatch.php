<?php
require_once __DIR__ . '/BaseModel.php';

class WoolBatch extends BaseModel {
    public function __construct() {
        parent::__construct('wool_batches');
    }

    public function findByIdWithFarmer($id) {
        $query = "SELECT wb.*, f.farm_name, f.farm_address 
                  FROM " . $this->table_name . " wb
                  JOIN farmers f ON wb.farmer_id = f.id
                  WHERE wb.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        return $this->update($id, ['status' => $status]);
    }

    public function getBatchesByFarmer($farmerId, $status = null) {
        $conditions = ['farmer_id' => $farmerId];
        if ($status) {
            $conditions['status'] = $status;
        }
        return $this->findAll($conditions, 'created_at DESC');
    }

    public function getProductionTrends($farmerId, $months = 6) {
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(quantity) as total_weight,
                    COUNT(*) as batch_count
                 FROM " . $this->table_name . "
                 WHERE farmer_id = :farmer_id
                 AND created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->bindParam(":months", $months);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getQualityMetrics($farmerId) {
        $query = "SELECT 
                    grade,
                    COUNT(*) as batch_count,
                    AVG(micron) as avg_micron,
                    AVG(strength) as avg_strength,
                    AVG(length) as avg_length
                 FROM " . $this->table_name . "
                 WHERE farmer_id = :farmer_id
                 GROUP BY grade
                 ORDER BY grade";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getFarmPerformanceMetrics($farmerId) {
        // Get total weight and active batches
        $query = "SELECT 
                    SUM(quantity) as total_weight,
                    COUNT(*) as total_batches,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_batches,
                    AVG(micron) as avg_micron,
                    AVG(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) 
                        THEN quantity ELSE NULL END) as current_month_production,
                    AVG(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) 
                         AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)
                        THEN quantity ELSE NULL END) as previous_month_production
                 FROM " . $this->table_name . "
                 WHERE farmer_id = :farmer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get overall quality statistics for a specific farmer.
     *
     * @param int $farmerId The farmer's ID (from the farmers table).
     * @return array|null An array containing stats ['total_batches', 'avg_micron', 'grade_counts'] or null on error.
     */
    public function getOverallQualityStats(int $farmerId): ?array
    {
        $sql = "SELECT 
                    COUNT(*) as total_batches,
                    AVG(micron) as avg_micron,
                    SUM(CASE WHEN grade = 'A' THEN 1 ELSE 0 END) as count_a,
                    SUM(CASE WHEN grade = 'B' THEN 1 ELSE 0 END) as count_b,
                    SUM(CASE WHEN grade = 'C' THEN 1 ELSE 0 END) as count_c
                FROM {$this->table_name} 
                WHERE farmer_id = ?";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$farmerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'total_batches' => (int)($result['total_batches'] ?? 0),
                    'avg_micron' => $result['avg_micron'] ? round((float)$result['avg_micron'], 2) : null,
                    'grade_counts' => [
                        'A' => (int)($result['count_a'] ?? 0),
                        'B' => (int)($result['count_b'] ?? 0),
                        'C' => (int)($result['count_c'] ?? 0),
                    ]
                ];
            } else {
                return null; // Or return default structure with zeros
            }
        } catch (PDOException $e) {
            error_log("Error fetching overall quality stats for farmer {$farmerId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get batches for a farmer that are in a trackable state (PENDING or SOLD)
     * and include the associated retailer's name from the latest transaction.
     *
     * @param int $farmerId The farmer's ID.
     * @return array List of trackable batches with retailer_name included.
     */
    public function getTrackableBatchesByFarmer(int $farmerId): array
    {
        // This query assumes the most recent transaction for a PENDING/SOLD batch 
        // determines the relevant retailer.
        // We use a subquery with ROW_NUMBER() to get the latest transaction per batch.
        $sql = "SELECT 
                    wb.*, 
                    u.name as retailer_name,
                    t.status as transaction_status, -- Include transaction status explicitly
                    t.updated_at as transaction_updated_at -- Include transaction update time
                FROM 
                    {$this->table_name} wb
                LEFT JOIN (
                    SELECT 
                        t_inner.*,
                        ROW_NUMBER() OVER(PARTITION BY t_inner.batch_id ORDER BY t_inner.created_at DESC) as rn
                    FROM transactions t_inner
                    WHERE t_inner.status IN ('PENDING', 'COMPLETED', 'REJECTED') -- Consider relevant transaction statuses
                ) t ON wb.id = t.batch_id AND t.rn = 1
                LEFT JOIN retailers r ON t.retailer_id = r.id
                LEFT JOIN users u ON r.user_id = u.id
                WHERE 
                    wb.farmer_id = ? AND wb.status IN ('PENDING', 'SOLD')
                ORDER BY 
                    wb.updated_at DESC"; // Order by batch update time

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$farmerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching trackable batches for farmer {$farmerId}: " . $e->getMessage());
            return [];
        }
    }
}
?> 