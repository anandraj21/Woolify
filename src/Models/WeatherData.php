<?php
require_once __DIR__ . '/BaseModel.php';

class WeatherData extends BaseModel {
    public function __construct() {
        parent::__construct('weather_data');
    }

    public function getForecastsByFarmer($farmerId, $limit = 7) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE farmer_id = :farmer_id 
                  AND forecast_date >= CURDATE()
                  ORDER BY forecast_date ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getLatestForecast($farmerId) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE farmer_id = :farmer_id 
                  ORDER BY created_at DESC, forecast_date DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":farmer_id", $farmerId);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
?> 