<?php
class Database {
    private $host = "localhost";
    private $db_name = "luxury_stays_complaints";
    private $username = "root";
    private $password = "";
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
    
    public function getConnection() {
        return $this->conn;
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
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Generic query execution method
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $exception) {
            error_log("Query error: " . $exception->getMessage());
            return false;
        }
    }
    
    // Get single row
    public function getSingle($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    // Get multiple rows
    public function getAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    // Insert data
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($data);
            return $this->conn->lastInsertId();
        } catch(PDOException $exception) {
            error_log("Insert error: " . $exception->getMessage());
            return false;
        }
    }
    
    // Update data
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = '';
        foreach(array_keys($data) as $key) {
            $setClause .= "$key = :$key, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));
            return $stmt->rowCount();
        } catch(PDOException $exception) {
            error_log("Update error: " . $exception->getMessage());
            return false;
        }
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $exception) {
            error_log("Delete error: " . $exception->getMessage());
            return false;
        }
    }
    
    // Close connection
    public function close() {
        $this->conn = null;
    }
}

// Create global database instance
$database = new Database();
$db = $database->getConnection();

// Complaint Model
class ComplaintModel {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get all complaints with filters
    public function getComplaints($filters = []) {
        $sql = "SELECT 
                    c.*,
                    cat.name as category_name,
                    u.first_name as employee_first_name,
                    u.last_name as employee_last_name,
                    u.employee_id as employee_code,
                    a.first_name as assigned_first_name,
                    a.last_name as assigned_last_name
                FROM complaints c
                LEFT JOIN complaint_categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.employee_id = u.id
                LEFT JOIN users a ON c.assigned_to = a.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND c.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['department'])) {
            $sql .= " AND c.department = :department";
            $params['department'] = $filters['department'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND c.priority = :priority";
            $params['priority'] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND c.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(c.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(c.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.complaint_code LIKE :search OR c.title LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        // Apply pagination
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$filters['limit'];
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET :offset";
                $params['offset'] = (int)$filters['offset'];
            }
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters with types
        foreach($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $type);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get complaint by ID
    public function getComplaintById($id) {
        $sql = "SELECT 
                    c.*,
                    cat.name as category_name,
                    u.first_name as employee_first_name,
                    u.last_name as employee_last_name,
                    u.email as employee_email,
                    u.department as employee_department,
                    u.position as employee_position,
                    a.first_name as assigned_first_name,
                    a.last_name as assigned_last_name,
                    a.email as assigned_email
                FROM complaints c
                LEFT JOIN complaint_categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.employee_id = u.id
                LEFT JOIN users a ON c.assigned_to = a.id
                WHERE c.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // Update complaint (only admin_notes and status can be edited)
    public function updateComplaint($id, $admin_notes, $status, $updated_by) {
        // Get current status
        $current = $this->getComplaintById($id);
        
        $sql = "UPDATE complaints 
                SET admin_notes = :admin_notes, 
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':admin_notes', $admin_notes);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Log status change if it changed
            if ($current['status'] !== $status) {
                $this->logStatusChange($id, $current['status'], $status, $updated_by);
            }
            return true;
        }
        
        return false;
    }
    
    // Log status change
    private function logStatusChange($complaint_id, $old_status, $new_status, $changed_by) {
        $sql = "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by, notes) 
                VALUES (:complaint_id, :old_status, :new_status, :changed_by, :notes)";
        
        $notes = "Status changed from " . str_replace('_', ' ', $old_status) . 
                 " to " . str_replace('_', ' ', $new_status);
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complaint_id', $complaint_id, PDO::PARAM_INT);
        $stmt->bindParam(':old_status', $old_status);
        $stmt->bindParam(':new_status', $new_status);
        $stmt->bindParam(':changed_by', $changed_by, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes);
        
        return $stmt->execute();
    }
    
    // Delete complaint
    public function deleteComplaint($id) {
        $sql = "DELETE FROM complaints WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Get complaint statistics
    public function getComplaintStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as under_investigation,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM complaints";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Get categories
    public function getCategories() {
        $sql = "SELECT * FROM complaint_categories WHERE is_active = 1 ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get status history for a complaint
    public function getStatusHistory($complaint_id) {
        $sql = "SELECT 
                    sh.*,
                    u.first_name,
                    u.last_name
                FROM complaint_status_history sh
                LEFT JOIN users u ON sh.changed_by = u.id
                WHERE sh.complaint_id = :complaint_id
                ORDER BY sh.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complaint_id', $complaint_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Initialize model
$complaintModel = new ComplaintModel($db);
?>