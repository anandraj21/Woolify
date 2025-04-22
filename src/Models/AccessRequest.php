<?php
require_once __DIR__ . '/BaseModel.php';

class AccessRequest extends BaseModel {
    public function __construct() {
        parent::__construct('access_requests');
    }

    /**
     * Create a new access request.
     * Prevents creating duplicate PENDING requests for the same farmer/retailer pair.
     *
     * @param int $farmerId
     * @param int $retailerId
     * @return int|bool The ID of the new request, or false if creation failed or duplicate pending exists.
     */
    public function createRequest(int $farmerId, int $retailerId): int|bool
    {
        // Check for existing PENDING request
        $existing = $this->findOne(['farmer_id' => $farmerId, 'retailer_id' => $retailerId, 'status' => 'PENDING']);
        if ($existing) {
            error_log("Attempted to create duplicate pending access request between farmer {$farmerId} and retailer {$retailerId}");
            return false; // Or return the existing ID? For now, fail.
        }

        $data = [
            'farmer_id' => $farmerId,
            'retailer_id' => $retailerId,
            'status' => 'PENDING' // Explicitly set status
            // request_date, created_at, updated_at handled by DB
        ];
        
        if (parent::create($data)) {
             return $this->conn->lastInsertId();
        } else {
            return false;
        }
    }

    /**
     * Find requests for a specific farmer, optionally filtered by status.
     * Includes Retailer Name.
     *
     * @param int $farmerId
     * @param string|null $status Filter by status ('PENDING', 'GRANTED', 'REJECTED') or null for all.
     * @return array List of access requests with retailer_name.
     */
    public function findRequestsByFarmer(int $farmerId, ?string $status = null): array
    {
        $params = [$farmerId];
        $sql = "SELECT ar.*, u.name as retailer_name 
                FROM {$this->table_name} ar
                JOIN retailers r ON ar.retailer_id = r.id
                JOIN users u ON r.user_id = u.id
                WHERE ar.farmer_id = ?";
        
        if ($status !== null) {
            $sql .= " AND ar.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ar.request_date DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching access requests for farmer {$farmerId}: " . $e->getMessage());
            return [];
        }
    }
    
     /**
     * Find requests initiated by a specific retailer, optionally filtered by status.
     * Includes Farmer Name.
     *
     * @param int $retailerId
     * @param string|null $status Filter by status ('PENDING', 'GRANTED', 'REJECTED') or null for all.
     * @return array List of access requests with farmer_name.
     */
    public function findRequestsByRetailer(int $retailerId, ?string $status = null): array
    {
        $params = [$retailerId];
        $sql = "SELECT ar.*, u.name as farmer_name 
                FROM {$this->table_name} ar
                JOIN farmers f ON ar.farmer_id = f.id
                JOIN users u ON f.user_id = u.id
                WHERE ar.retailer_id = ?";
        
        if ($status !== null) {
            $sql .= " AND ar.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ar.request_date DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching access requests for retailer {$retailerId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update the status of an access request.
     *
     * @param int $requestId The ID of the request to update.
     * @param string $newStatus The new status ('GRANTED' or 'REJECTED').
     * @param int $farmerId The farmer ID (to ensure ownership).
     * @return bool True on success, false on failure or if status is invalid.
     */
    public function updateRequestStatus(int $requestId, string $newStatus, int $farmerId): bool
    {
        if (!in_array($newStatus, ['GRANTED', 'REJECTED'])) {
            return false; // Invalid status
        }

        $data = [
            'status' => $newStatus,
            'response_date' => date('Y-m-d H:i:s') // Record response time
        ];
        
        // Add condition to ensure the farmer owns the request being updated
        $sql = "UPDATE {$this->table_name} 
                SET status = :status, response_date = :response_date, updated_at = NOW() 
                WHERE id = :id AND farmer_id = :farmer_id AND status = 'PENDING'"; // Only update PENDING requests
                
        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'status' => $newStatus,
                'response_date' => $data['response_date'],
                'id' => $requestId,
                'farmer_id' => $farmerId
            ]);
            return $result && $stmt->rowCount() > 0; // Ensure a row was actually updated
        } catch (PDOException $e) {
            error_log("Error updating access request {$requestId} status for farmer {$farmerId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a retailer has been granted access by a specific farmer.
     *
     * @param int $farmerId
     * @param int $retailerId
     * @return bool True if GRANTED access exists, false otherwise.
     */
    public function hasAccess(int $farmerId, int $retailerId): bool
    {
        $request = $this->findOne(['farmer_id' => $farmerId, 'retailer_id' => $retailerId, 'status' => 'GRANTED']);
        return !empty($request);
    }
    
    // Need to add findOne to BaseModel if it doesn't exist
    // Or adapt createRequest and hasAccess to use findAll with limit 1
     protected function findOne(array $conditions): ?array {
        $results = $this->findAll($conditions, null, 1);
        return !empty($results) ? $results[0] : null;
    }

}
?> 