<?php
require_once __DIR__ . '/BaseModel.php';

class Retailer extends BaseModel {
    public function __construct() {
        parent::__construct('retailers');
    }

    public function findByUserId($userId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getAvailableBatches($grade = null, $min_quantity = 0) {
        $query = "SELECT wb.*, f.farm_name 
                  FROM wool_batches wb
                  JOIN farmers f ON wb.farmer_id = f.id
                  WHERE wb.status = 'AVAILABLE' AND wb.quantity >= :min_quantity";
        
        $params = [":min_quantity" => $min_quantity];

        if ($grade) {
            $query .= " AND wb.grade = :grade";
            $params[":grade"] = $grade;
        }
        
        $query .= " ORDER BY wb.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    public function getPurchasedBatches($retailerId, $status = null) {
        $query = "SELECT wb.*, f.farm_name, t.status as transaction_status, t.created_at as purchase_date
                  FROM transactions t
                  JOIN wool_batches wb ON t.batch_id = wb.id
                  JOIN farmers f ON wb.farmer_id = f.id
                  WHERE t.retailer_id = :retailer_id";
        
        $params = [":retailer_id" => $retailerId];

        if ($status) {
            $query .= " AND t.status = :status";
            $params[":status"] = $status;
        }
        
        $query .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    public function getTransactions($retailerId, $status = null) {
        $query = "SELECT t.*, wb.quantity, wb.micron, wb.grade, f.farm_name
                  FROM transactions t
                  JOIN wool_batches wb ON t.batch_id = wb.id
                  JOIN farmers f ON wb.farmer_id = f.id
                  WHERE t.retailer_id = :retailer_id";
        
        if ($status) {
            $query .= " AND t.status = :status";
        }
        
        $query .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":retailer_id", $retailerId);
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTransactionStats($retailerId) {
        $query = "SELECT 
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_transactions,
                    COUNT(CASE WHEN status = 'PENDING' THEN 1 END) as pending_transactions,
                    COUNT(CASE WHEN status = 'FAILED' THEN 1 END) as failed_transactions,
                    SUM(CASE WHEN status = 'COMPLETED' THEN amount ELSE 0 END) as total_amount
                  FROM transactions 
                  WHERE retailer_id = :retailer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":retailer_id", $retailerId);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
?> 