<?php
require_once __DIR__ . '/BaseModel.php';

class AnimalHealth extends BaseModel {
    public function __construct() {
        parent::__construct('animal_health');
    }

    public function getRecordsByFarmer($farmerId, $status = null, $limit = null) {
        $conditions = ['farmer_id' => $farmerId];
        if ($status) {
            $conditions['health_status'] = $status;
        }
        return $this->findAll($conditions, 'created_at DESC', $limit);
    }

    public function getUpcomingVaccinations($farmerId, $daysAhead = 30) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE farmer_id = :farmer_id 
                  AND next_vaccination_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY next_vaccination_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->bindParam(":days", $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getHealthStats($farmerId) {
        $query = "SELECT 
                    health_status,
                    COUNT(*) as count
                  FROM " . $this->table_name . " 
                  WHERE farmer_id = :farmer_id
                  GROUP BY health_status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch()) {
            $stats[$row['health_status']] = $row['count'];
        }
        return $stats;
    }
}
?> 