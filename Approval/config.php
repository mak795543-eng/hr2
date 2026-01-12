<?php
// Database configuration for Manager Approval System
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'manager_approval');

// Create connection
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Database Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Global connection variable
$conn = getConnection();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start();

// Timezone setting
date_default_timezone_set('Asia/Manila');

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Check if user has specific role
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

// Log function for debugging
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// JSON response helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Database helper functions
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logMessage("SQL Prepare failed: " . $conn->error, 'ERROR');
        return false;
    }
    
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        logMessage("SQL Execute failed: " . $stmt->error, 'ERROR');
        return false;
    }
    
    return $stmt;
}

function fetchAll($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return [];
    }
    
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

function fetchOne($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    return $row ?: null;
}

// Function to get employees for IDP Approval System
function getEmployees($filter = 'all', $search = '', $department = 'all') {
    $sql = "SELECT e.* FROM employees e WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($filter !== 'all') {
        $sql .= " AND e.status = ?";
        $params[] = $filter;
        $types .= "s";
    }
    
    if ($department !== 'all') {
        $sql .= " AND e.department = ?";
        $params[] = $department;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $sql .= " AND (e.full_name LIKE ? OR e.employee_id LIKE ? OR e.position LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY e.full_name";
    
    return fetchAll($sql, $params, $types);
}

// Function to get departments for IDP Approval System
function getDepartments() {
    $sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department";
    $results = fetchAll($sql);
    
    $departments = [];
    foreach ($results as $row) {
        $departments[] = $row['department'];
    }
    
    return $departments;
}

// Function to get IDP approval requests
function getIDPApprovals($status = 'pending', $department = 'all', $position = 'all', $search = '') {
    $sql = "SELECT * FROM idp_approval WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($department !== 'all') {
        $sql .= " AND department = ?";
        $params[] = $department;
        $types .= "s";
    }
    
    if ($position !== 'all') {
        $sql .= " AND position = ?";
        $params[] = $position;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $sql .= " AND (employee_name LIKE ? OR employee_id LIKE ? OR position LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY submitted_date DESC";
    
    return fetchAll($sql, $params, $types);
}

// Function to get summary statistics
function getApprovalSummary() {
    $sql = "
        SELECT 
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'approved' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as approved_this_week,
            SUM(CASE WHEN status = 'declined' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as declined_this_week,
            SUM(CASE WHEN status = 'pending' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as pending_this_week
        FROM idp_approval
    ";
    
    return fetchOne($sql);
}
?>