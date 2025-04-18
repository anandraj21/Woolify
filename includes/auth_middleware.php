<?php
session_start();

class AuthMiddleware {
    /**
     * Check if user is logged in
     */
    public static function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Woolify/auth/login.php');
            exit;
        }
    }

    /**
     * Check if user has required role
     */
    public static function hasRole($requiredRole) {
        self::isAuthenticated();
        
        if ($_SESSION['user_role'] !== $requiredRole) {
            header('Location: /Woolify/error/unauthorized.php');
            exit;
        }
    }

    /**
     * Get current user's role
     */
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get current user's ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user has access to a specific batch
     */
    public static function canAccessBatch($batchId) {
        self::isAuthenticated();
        
        $db = new Database();
        $role = self::getUserRole();
        $userId = self::getUserId();

        if ($role === 'farmer') {
            // Check if batch belongs to one of the farmer's farms
            $query = "SELECT COUNT(*) FROM wool_batches wb
                     JOIN farms f ON wb.farm_id = f.id
                     WHERE wb.id = ? AND f.user_id = ?";
            $count = $db->query($query, [$batchId, $userId])->fetchColumn();
            
            if ($count === 0) {
                header('Location: /Woolify/error/unauthorized.php');
                exit;
            }
        } elseif ($role === 'retailer') {
            // Check if retailer has access to this batch
            $query = "SELECT access_status FROM batch_access 
                     WHERE batch_id = ? AND retailer_id = ?";
            $status = $db->query($query, [$batchId, $userId])->fetchColumn();
            
            if (!$status || $status !== 'approved') {
                header('Location: /Woolify/error/unauthorized.php');
                exit;
            }
        }

        return true;
    }

    /**
     * Check if user can manage a specific farm
     */
    public static function canManageFarm($farmId) {
        self::isAuthenticated();
        
        if (self::getUserRole() !== 'farmer') {
            header('Location: /Woolify/error/unauthorized.php');
            exit;
        }

        $db = new Database();
        $query = "SELECT COUNT(*) FROM farms WHERE id = ? AND user_id = ?";
        $count = $db->query($query, [$farmId, self::getUserId()])->fetchColumn();

        if ($count === 0) {
            header('Location: /Woolify/error/unauthorized.php');
            exit;
        }

        return true;
    }
} 