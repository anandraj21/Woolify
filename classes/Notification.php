<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $conn;
    private $table = 'notifications';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createNotification($userId, $type, $title, $message) {
        try {
            $query = "INSERT INTO " . $this->table . " (user_id, type, title, message, created_at) 
                     VALUES (:userId, :type, :title, :message, NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":message", $message);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getNotifications($userId, $limit = 10) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE user_id = :userId 
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function markAsRead($notificationId, $userId) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET read_at = NOW() 
                     WHERE id = :notificationId AND user_id = :userId";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":notificationId", $notificationId);
            $stmt->bindParam(":userId", $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getUnreadCount($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                     WHERE user_id = :userId AND read_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":userId", $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function deleteNotification($notificationId, $userId) {
        try {
            $query = "DELETE FROM " . $this->table . " 
                     WHERE id = :notificationId AND user_id = :userId";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":notificationId", $notificationId);
            $stmt->bindParam(":userId", $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
} 