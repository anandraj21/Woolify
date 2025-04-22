<?php
/**
 * Authentication helper functions
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $baseUrl;
    private $sessionPrefix = 'woolify_'; // Prefix for role-specific sessions

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
        // Dynamically determine base URL - adjust if needed for your setup
        $this->baseUrl = sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/../' // Go up one level from 'includes'
        );
        $this->baseUrl = rtrim($this->baseUrl, '/') . '/'; // Ensure trailing slash
    }

    private function getSessionKey($role = null) {
        if ($role) {
            return $this->sessionPrefix . strtolower($role) . '_user';
        }
        // If no role specified, try to get current role's session
        foreach (['farmer', 'retailer', 'admin'] as $possibleRole) {
            $key = $this->sessionPrefix . $possibleRole . '_user';
            if (isset($_SESSION[$key])) {
                return $key;
            }
        }
        return null;
    }

    public function login($email, $password) {
        try {
            $stmt = $this->db->query(
                "SELECT id, name, email, password, role FROM users WHERE email = ?",
                [$email]
            );
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Store user data in role-specific session
                $sessionKey = $this->getSessionKey($user['role']);
                $_SESSION[$sessionKey] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function logout($role = null) {
        if ($role) {
            // Logout specific role
            $sessionKey = $this->getSessionKey($role);
            if (isset($_SESSION[$sessionKey])) {
                unset($_SESSION[$sessionKey]);
            }
        } else {
            // Logout current role
            $sessionKey = $this->getSessionKey();
            if ($sessionKey) {
                unset($_SESSION[$sessionKey]);
            }
        }
    }

    public function isLoggedIn($role = null) {
        if ($role) {
            // Check if logged in for specific role
            return isset($_SESSION[$this->getSessionKey($role)]);
        }
        // Check if logged in for any role
        return $this->getSessionKey() !== null;
    }

    public function getUser($role = null) {
        $sessionKey = $role ? $this->getSessionKey($role) : $this->getSessionKey();
        return $sessionKey ? ($_SESSION[$sessionKey] ?? null) : null;
    }

    public function getUserRole() {
        $user = $this->getUser();
        return $user ? $user['role'] : null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $this->baseUrl . 'login.php');
            exit();
        }
    }

    public function requireRole($roles) {
        $this->requireLogin();
        
        $userRole = $this->getUserRole();
        $roles = (array)$roles;
        
        // Case-insensitive role check
        $userRoleUpper = strtoupper($userRole);
        $rolesUpper = array_map('strtoupper', $roles);

        if (!$userRole || !in_array($userRoleUpper, $rolesUpper)) {
            header('Location: ' . $this->baseUrl . 'unauthorized.php');
            exit();
        }
    }

    public function switchRole($role) {
        // Check if user is logged in with the requested role
        if ($this->isLoggedIn($role)) {
            // Set the current active role
            $sessionKey = $this->getSessionKey($role);
            $_SESSION['current_role'] = $role;
            return true;
        }
        return false;
    }
}

// Initialize auth handler
$auth = new Auth();

/**
 * Check if user is logged in
 * @param string|null $role Optional role to check
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn($role = null) {
    global $auth;
    return $auth->isLoggedIn($role);
}

/**
 * Get current user's role
 * @return string|null User role or null if not logged in
 */
function getUserRole() {
    global $auth;
    return $auth->getUserRole();
}

/**
 * Check if user has specific role
 * @param string $role Role to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($role) {
    global $auth;
    $user = $auth->getUser($role);
    return $user && strtoupper($user['role']) === strtoupper($role);
}

/**
 * Require specific role to access page
 * @param string|array $requiredRole Role(s) required to access page
 * @return void Redirects to login if not authorized
 */
function requireRole($requiredRole) {
    global $auth;
    $auth->requireRole($requiredRole);
}