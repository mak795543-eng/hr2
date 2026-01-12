<?php
// database.php
require_once 'config.php';

class Database {
    private $conn;
    private $config;
    
    public function __construct() {
        $this->config = new Config();
        $this->conn = $this->config->getDatabaseConnection();
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Generic query execution
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->config->logError("Query Error: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }
    
    // Fetch single row
    public function fetchSingle($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Insert data
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->executeQuery($sql, $data);
        
        return $stmt ? $this->conn->lastInsertId() : false;
    }
    
    // Update data
    public function update($table, $data, $where, $where_params = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }
        $set_clause = implode(', ', $set);
        
        $sql = "UPDATE $table SET $set_clause WHERE $where";
        $params = array_merge($data, $where_params);
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
    
    // Count rows
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->fetchSingle($sql, $params);
        return $result ? $result['count'] : 0;
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    // Check if table exists
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetchSingle($sql, [$table]);
        return !empty($result);
    }
    
    // Get table columns
    public function getTableColumns($table) {
        $sql = "DESCRIBE $table";
        return $this->fetchAll($sql);
    }
    
    // Close connection
    public function close() {
        $this->conn = null;
    }
}

// Create global database instance
$db = new Database();
?>