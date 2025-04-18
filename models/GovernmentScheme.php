<?php
require_once __DIR__ . '/BaseModel.php';

class GovernmentScheme extends BaseModel {
    public function __construct() {
        parent::__construct('government_schemes');
    }

    public function getActiveSchemes($limit = null) {
        return $this->findAll(['status' => 'ACTIVE'], 'created_at DESC', $limit);
    }

    public function findByTitle($title) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE title LIKE :title LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$title%";
        $stmt->bindParam(":title", $searchTerm);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function searchSchemes($keyword) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE (title LIKE :keyword OR description LIKE :keyword OR eligibility_criteria LIKE :keyword)
                  AND status = 'ACTIVE'
                  ORDER BY application_deadline DESC, created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$keyword%";
        $stmt->bindParam(":keyword", $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?> 