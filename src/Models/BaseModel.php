<?php
require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected $conn;
    protected $table_name;

    public function __construct($table_name) {
        // Use the singleton instance
        $database = Database::getInstance(); 
        // Access the public connection property
        $this->conn = $database->conn; 
        $this->table_name = $table_name;

        // Check if connection is valid
        if (!$this->conn) {
            throw new Exception("Database connection not established in BaseModel for table: " . $table_name);
        }
    }

    public function create($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO " . $this->table_name . " ($columns) VALUES ($placeholders)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute($data);
            if (!$success) {
                $error = $stmt->errorInfo();
                error_log("Create Error in BaseModel ({$this->table_name}): " . json_encode($error));
                throw new PDOException("Database error: " . ($error[2] ?? 'Unknown error'));
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Create Error in BaseModel ({$this->table_name}): " . $e->getMessage() . "\nSQL: " . $sql . "\nData: " . json_encode($data));
            throw $e; // Re-throw the exception to be handled by the caller
        }
    }

    public function read($id) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Read Error in BaseModel ({$this->table_name}): " . $e->getMessage());
            return null;
        }
    }

    public function update($id, $data) {
        $set_parts = [];
        foreach (array_keys($data) as $key) {
            $set_parts[] = "`$key` = :$key";
        }
        $set_clause = implode(", ", $set_parts);
        
        $sql = "UPDATE " . $this->table_name . " SET $set_clause WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $data['id'] = $id; // Add id to the data array for execute
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Update Error in BaseModel ({$this->table_name}): " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        $sql = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Delete Error in BaseModel ({$this->table_name}): " . $e->getMessage());
            return false;
        }
    }

    public function findAll($conditions = [], $order_by = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM " . $this->table_name;
        $params = $conditions;

        if (!empty($conditions)) {
            $where_parts = [];
            foreach (array_keys($conditions) as $key) {
                $where_parts[] = "`$key` = :$key";
            }
            $sql .= " WHERE " . implode(" AND ", $where_parts);
        }
        
        if ($order_by) {
            // Basic sanitization - adjust as needed for complexity
            $order_by = preg_replace('/[^a-zA-Z0-9_\s,]/', '', $order_by);
            $sql .= " ORDER BY " . $order_by;
        }
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
        }

        if ($offset !== null && $limit !== null) { // Offset usually requires limit
            $sql .= " OFFSET :offset";
             $params['offset'] = (int)$offset;
        }
        
        try {
            $stmt = $this->conn->prepare($sql);
            // Bind parameters properly based on type (especially for LIMIT/OFFSET)
            foreach ($params as $key => &$value) { // Use reference for bindValue
                if ($key === 'limit' || $key === 'offset') {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }
            unset($value); // Unset reference

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("FindAll Error in BaseModel ({$this->table_name}): " . $e->getMessage());
            return [];
        }
    }

     /**
     * Execute a custom query with parameters.
     * Use this for complex queries not covered by CRUD methods.
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Custom Query Error in BaseModel ({$this->table_name}): " . $e->getMessage() . " SQL: " . $sql);
            // Re-throw the exception to be handled by the calling model or script
            throw $e; 
        }
    }
}
?> 