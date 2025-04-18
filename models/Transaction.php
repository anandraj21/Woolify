<?php
require_once __DIR__ . '/BaseModel.php';

class Transaction extends BaseModel {
    public function __construct() {
        parent::__construct('transactions');
    }

    public function createTransaction($data) {
        try {
            $this->conn->beginTransaction();
            
            // Create the transaction record
            $this->create($data);
            $transactionId = $this->conn->lastInsertId();
            
            // Update the wool batch status to PENDING or SOLD based on logic
            // Assuming PENDING for now
            $woolBatchModel = new WoolBatch(); 
            $woolBatchModel->updateStatus($data['batch_id'], 'PENDING');
            
            $this->conn->commit();
            return $transactionId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            // Log error or handle it
            error_log("Transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function findByBatchId($batchId) {
        return $this->findAll(['batch_id' => $batchId]);
    }

    public function getTransactionsForUser($userId, $userRole) {
        $field = ($userRole === 'FARMER') ? 'farmer_id' : 'retailer_id';
        
        $query = "SELECT t.*, wb.grade, wb.quantity, wb.micron, u_other.name as other_party_name
                  FROM " . $this->table_name . " t
                  JOIN wool_batches wb ON t.batch_id = wb.id
                  JOIN users u_other ON t." . (($userRole === 'FARMER') ? 'retailer_id' : 'farmer_id') . " = u_other.id
                  WHERE t." . $field . " = :user_id
                  ORDER BY t.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status) {
         return $this->update($id, ['status' => $status]);
    }

    /**
     * Fetches pending transaction IDs for a list of batch IDs owned by a specific farmer.
     * Returns an associative array [batch_id => transaction_id].
     */
    public function getPendingTransactionIdsForBatches(array $batchIds, int $farmerId): array
    {
        if (empty($batchIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
        $sql = "SELECT batch_id, id FROM {$this->table_name} WHERE batch_id IN ($placeholders) AND status = ? AND farmer_id = ?";
        $params = array_merge($batchIds, ['PENDING', $farmerId]);
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            // Fetch as key-value pairs (batch_id => id)
            $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return $results === false ? [] : $results; // Handle fetch failure
        } catch (PDOException $e) {
            error_log("Error fetching pending transaction IDs: " . $e->getMessage());
            // Depending on requirements, you might re-throw, return false, or return empty array
            return [];
        }
    }

    // Simplified: Get a single transaction by ID
    public function findById(int $id): ?array
    {
         // Simplified query - joining logic might be better handled elsewhere
         $sql = "SELECT * FROM {$this->table_name} WHERE id = ?";
         try {
             $stmt = $this->conn->prepare($sql);
             $stmt->execute([$id]);
             $result = $stmt->fetch(PDO::FETCH_ASSOC);
             return $result === false ? null : $result; // Return null if not found
         } catch (PDOException $e) {
             error_log("Error finding transaction by ID: " . $e->getMessage());
             return null; // Or handle error as appropriate
         }
    }
}
?> 