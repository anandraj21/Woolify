<?php
class Database {
    private $host = "localhost";
    private $db_name = "woolify";
    private $username = "root";
    private $password = "";
    public $conn;
    private static $instance = null;

    public function __construct() {
        $this->initializeDatabase();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeDatabase() {
        try {
            // First connect without database name
            $this->conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Connect to the specific database
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Initialize tables if they don't exist
            $this->initializeTables();
            
        } catch(PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }

    private function initializeTables() {
        try {
            $sql = file_get_contents(__DIR__ . '/../setup_database.sql');
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', 
                    explode(';', $sql)
                ),
                function($statement) {
                    return !empty($statement);
                }
            );
            
            // Execute each statement separately
            foreach ($statements as $statement) {
                try {
                    $this->conn->exec($statement);
                } catch (PDOException $e) {
                    // Log the error but continue with other statements
                    // Ignore duplicate index errors (1061)
                    if ($e->getCode() != '42000' || !strpos($e->getMessage(), '1061')) {
                        error_log("SQL Error: " . $e->getMessage() . "\nStatement: " . $statement);
                    }
                }
            }
        } catch(PDOException $e) {
            die("Database Setup Error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Query error: " . $e->getMessage());
        }
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollBack() {
        return $this->conn->rollBack();
    }

    public function inTransaction() {
        return $this->conn->inTransaction();
    }
}
?> 