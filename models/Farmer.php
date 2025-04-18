<?php
require_once __DIR__ . '/BaseModel.php';

class Farmer extends BaseModel {
    public function __construct() {
        parent::__construct('farmers');
    }

    public function findByUserId($userId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getFarmStats($farmerId) {
        $query = "SELECT 
                    COUNT(*) as total_sheep,
                    COUNT(CASE WHEN health_status = 'HEALTHY' THEN 1 END) as healthy_sheep,
                    COUNT(CASE WHEN health_status = 'SICK' THEN 1 END) as sick_sheep,
                    COUNT(CASE WHEN health_status = 'RECOVERING' THEN 1 END) as recovering_sheep
                  FROM animal_health 
                  WHERE farmer_id = :farmer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getWoolBatches($farmerId, $status = null) {
        $query = "SELECT * FROM wool_batches WHERE farmer_id = :farmer_id";
        if ($status) {
            $query .= " AND status = :status";
        }
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getWeatherData($farmerId, $limit = 7) {
        $query = "SELECT * FROM weather_data 
                  WHERE farmer_id = :farmer_id 
                  ORDER BY forecast_date DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?> 