<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    public function __construct() {
        parent::__construct('users');
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function createUser($data) {
        // Hash the password before storing
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }

    public function updateUser($id, $data) {
        // If password is being updated, hash it
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($id, $data);
    }

    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Get the user_id associated with a specific farmer_id.
     *
     * @param int $farmerId The farmer's ID (from the farmers table).
     * @return int|null The corresponding user_id or null if not found or on error.
     */
    public function getUserIdFromFarmerId(int $farmerId): ?int
    {
        $sql = "SELECT user_id FROM farmers WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$farmerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['user_id'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching user_id for farmer_id {$farmerId}: " . $e->getMessage());
            return null;
        }
    }

    // Similarly, add a method for Retailer if needed
    /**
     * Get the user_id associated with a specific retailer_id.
     *
     * @param int $retailerId The retailer's ID (from the retailers table).
     * @return int|null The corresponding user_id or null if not found or on error.
     */
    public function getUserIdFromRetailerId(int $retailerId): ?int
    {
        $sql = "SELECT user_id FROM retailers WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$retailerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['user_id'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching user_id for retailer_id {$retailerId}: " . $e->getMessage());
            return null;
        }
    }

}
?> 