<?php
// db_connection.php

class Database {
    private $host = "localhost";
    private $db_name = "critical_gaps";
    private $username = "root";  // Default XAMPP username
    private $password = "";      // Default XAMPP password (empty)
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dbPrefix = getenv('DB_PREFIX') ?: '';
            $host = getenv('CRITICAL_GAPS_DB_HOST') ?: (getenv('DB_HOST') ?: $this->host);
            $dbName = getenv('CRITICAL_GAPS_DB_NAME') ?: $this->db_name;
            if ($dbPrefix !== '' && strpos($dbName, $dbPrefix) !== 0) {
                $dbName = $dbPrefix . $dbName;
            }
            $user = getenv('CRITICAL_GAPS_DB_USER') ?: (getenv('DB_USER') ?: $this->username);
            $passEnv = getenv('CRITICAL_GAPS_DB_PASS');
            $passGlobal = getenv('DB_PASS');
            $pass = $passEnv !== false
                ? $passEnv
                : ($passGlobal !== false
                    ? $passGlobal
                    : (($user === 'root' && ($host === 'localhost' || $host === '127.0.0.1')) ? '' : 'makmak01'));
            $this->conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . $dbName,
                $user,
                $pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>