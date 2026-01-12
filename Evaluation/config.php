<?php
// config.php

class Config {
    // Database Configuration
    private $db_host = "localhost";
    private $db_name = "performance_evaluation";
    private $db_user = "root";
    private $db_pass = "";
    
    // Application Configuration
    private $app_name = "Hotel Performance Evaluation System";
    private $app_version = "1.0.0";
    private $timezone = "Asia/Manila";
    
    // Path Configuration
    private $base_url = "http://localhost/hotel-performance-system";
    private $upload_path = "uploads/";
    
    // Email Configuration
    private $smtp_host = "smtp.gmail.com";
    private $smtp_port = 587;
    private $smtp_user = "";
    private $smtp_pass = "";
    
    // Security Configuration
    private $jwt_secret = "your_jwt_secret_key_here_change_this";
    private $encryption_key = "your_encryption_key_here_change_this";
    
    // Session Configuration
    private $session_lifetime = 7200; // 2 hours in seconds
    private $session_name = "performance_eval_sess";
    
    // Performance Configuration
    private $max_upload_size = 5242880; // 5MB in bytes
    private $allowed_file_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    // Rating Configuration
    private $rating_scale = [
        1 => ['label' => 'Needs Improvement', 'color' => '#EF4444'],
        2 => ['label' => 'Developing', 'color' => '#F59E0B'],
        3 => ['label' => 'Competent', 'color' => '#3B82F6'],
        4 => ['label' => 'Exceeds', 'color' => '#10B981'],
        5 => ['label' => 'Exceptional', 'color' => '#059669']
    ];
    
    // Review Periods
    private $review_periods = [
        'Q1' => ['name' => 'First Quarter', 'months' => [1, 2, 3]],
        'Q2' => ['name' => 'Second Quarter', 'months' => [4, 5, 6]],
        'Q3' => ['name' => 'Third Quarter', 'months' => [7, 8, 9]],
        'Q4' => ['name' => 'Fourth Quarter', 'months' => [10, 11, 12]],
        'Annual' => ['name' => 'Annual Review', 'months' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]]
    ];
    
    // Performance Categories
    private $performance_categories = [
        'productivity' => [
            'name' => 'Team Productivity',
            'description' => 'Ensure team meets productivity targets and deadlines',
            'weight' => 1.0
        ],
        'development' => [
            'name' => 'Staff Development',
            'description' => 'Coach and develop direct reports with regular feedback',
            'weight' => 1.0
        ],
        'compliance' => [
            'name' => 'Operational Compliance',
            'description' => 'Maintain departmental compliance with policies and procedures',
            'weight' => 1.0
        ]
    ];
    
    // Departments
    private $departments = [
        1 => ['name' => 'Front Office', 'type' => 'hotel'],
        2 => ['name' => 'Housekeeping', 'type' => 'hotel'],
        3 => ['name' => 'Food & Beverage', 'type' => 'both'],
        4 => ['name' => 'Kitchen', 'type' => 'restaurant'],
        5 => ['name' => 'Sales & Marketing', 'type' => 'both'],
        6 => ['name' => 'Human Resources', 'type' => 'both'],
        7 => ['name' => 'Maintenance', 'type' => 'hotel'],
        8 => ['name' => 'Security', 'type' => 'hotel'],
        9 => ['name' => 'Spa & Wellness', 'type' => 'hotel'],
        10 => ['name' => 'Banquets & Events', 'type' => 'both']
    ];
    
    // Initialize configuration
    public function __construct() {
        // Set timezone
        date_default_timezone_set($this->timezone);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->session_name);
            session_set_cookie_params($this->session_lifetime);
            session_start();
        }
        
        // Error reporting (for development)
        if ($this->isDevelopment()) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
    
    // Database Connection
    public function getDatabaseConnection() {
        try {
            $dsn = "mysql:host=" . $this->db_host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $this->db_user, $this->db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $pdo;
        } catch (PDOException $e) {
            $this->logError("Database Connection Error: " . $e->getMessage());
            return null;
        }
    }
    
    // Check if in development environment
    public function isDevelopment() {
        return ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');
    }
    
    // Get configuration values
    public function get($key) {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        return null;
    }
    
    // Set configuration values
    public function set($key, $value) {
        if (property_exists($this, $key)) {
            $this->$key = $value;
            return true;
        }
        return false;
    }
    
    // Get base URL
    public function getBaseUrl() {
        return $this->base_url;
    }
    
    // Get full URL
    public function getFullUrl($path = '') {
        return rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
    }
    
    // Get upload path
    public function getUploadPath() {
        $path = $this->upload_path;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }
    
    // Get rating configuration
    public function getRatingConfig($rating = null) {
        if ($rating && isset($this->rating_scale[$rating])) {
            return $this->rating_scale[$rating];
        }
        return $this->rating_scale;
    }
    
    // Get departments
    public function getDepartments($id = null) {
        if ($id && isset($this->departments[$id])) {
            return $this->departments[$id];
        }
        return $this->departments;
    }
    
    // Get performance categories
    public function getPerformanceCategories($key = null) {
        if ($key && isset($this->performance_categories[$key])) {
            return $this->performance_categories[$key];
        }
        return $this->performance_categories;
    }
    
    // Get review periods
    public function getReviewPeriods($key = null) {
        if ($key && isset($this->review_periods[$key])) {
            return $this->review_periods[$key];
        }
        return $this->review_periods;
    }
    
    // Validate file upload
    public function validateFile($file) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > $this->max_upload_size) {
            $errors[] = "File size exceeds maximum limit of 5MB";
        }
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $this->allowed_file_types)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $this->allowed_file_types);
        }
        
        return $errors;
    }
    
    // Generate CSRF token
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Validate CSRF token
    public function validateCsrfToken($token) {
        if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }
    
    // Log errors
    public function logError($message) {
        $log_dir = 'logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . 'error-' . date('Y-m-d') . '.log';
        $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        
        error_log($log_message, 3, $log_file);
    }
    
    // Sanitize input
    public function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    // Redirect
    public function redirect($url, $statusCode = 303) {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
    
    // JSON response
    public function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return !empty($_SESSION['user_id']);
    }
    
    // Require login
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            $this->redirect('login.php');
        }
    }
    
    // Get current user ID
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user role
    public function getCurrentUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    // Check user permission
    public function hasPermission($required_role) {
        $user_role = $this->getCurrentUserRole();
        
        $permissions = [
            'admin' => ['admin', 'manager', 'supervisor', 'user'],
            'manager' => ['manager', 'supervisor', 'user'],
            'supervisor' => ['supervisor', 'user'],
            'user' => ['user']
        ];
        
        return in_array($user_role, $permissions[$required_role] ?? []);
    }
    
    // Require permission
    public function requirePermission($required_role) {
        $this->requireLogin();
        
        if (!$this->hasPermission($required_role)) {
            $this->redirect('unauthorized.php');
        }
    }
    
    // Format date
    public function formatDate($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }
    
    // Calculate overall rating
    public function calculateOverallRating($ratings) {
        if (empty($ratings)) {
            return 0;
        }
        
        $total = array_sum($ratings);
        $count = count($ratings);
        
        return round($total / $count, 2);
    }
    
    // Get rating label
    public function getRatingLabel($rating) {
        $config = $this->getRatingConfig($rating);
        return $config['label'] ?? 'Unknown';
    }
    
    // Get rating color
    public function getRatingColor($rating) {
        $config = $this->getRatingConfig($rating);
        return $config['color'] ?? '#6B7280';
    }
    
    // Generate evaluation report data
    public function generateEvaluationReport($evaluation_id) {
        // This would generate report data structure
        return [
            'evaluation_id' => $evaluation_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => 'performance_evaluation',
            'version' => $this->app_version
        ];
    }
    
    // Send email notification
    public function sendEmail($to, $subject, $body, $is_html = false) {
        // This is a basic email function - implement proper email sending
        $headers = "From: no-reply@hotel-performance-system.com\r\n";
        
        if ($is_html) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $body, $headers);
    }
    
    // Encrypt data
    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    // Decrypt data
    public function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->encryption_key, 0, $iv);
    }
}

// Create global configuration instance
$config = new Config();

// Database connection
function getDB() {
    global $config;
    return $config->getDatabaseConnection();
}

// Helper functions
function sanitize($input) {
    global $config;
    return $config->sanitize($input);
}

function redirect($url) {
    global $config;
    $config->redirect($url);
}

function jsonResponse($data, $status = 200) {
    global $config;
    $config->jsonResponse($data, $status);
}

function isLoggedIn() {
    global $config;
    return $config->isLoggedIn();
}

function requireLogin() {
    global $config;
    $config->requireLogin();
}

function getCurrentUserId() {
    global $config;
    return $config->getCurrentUserId();
}

function getCurrentUserRole() {
    global $config;
    return $config->getCurrentUserRole();
}

function hasPermission($required_role) {
    global $config;
    return $config->hasPermission($required_role);
}

function requirePermission($required_role) {
    global $config;
    $config->requirePermission($required_role);
}
?>