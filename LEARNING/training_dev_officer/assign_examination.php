<?php
session_start();

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

function normalize_department_slug(string $v): string {
    $s = mb_strtolower(trim($v));
    if ($s === '') {
        return '';
    }

    if ($s === 'hr' || (str_contains($s, 'human') && str_contains($s, 'resource'))) {
        return 'human-resources';
    }
    if (str_contains($s, 'front') && str_contains($s, 'office')) {
        return 'front-office';
    }
    if (str_contains($s, 'house')) {
        return 'housekeeping';
    }
    if (str_contains($s, 'food') || str_contains($s, 'beverage') || str_contains($s, 'f&b')) {
        return 'food-beverage';
    }
    if (str_contains($s, 'kitchen') || str_contains($s, 'culinary')) {
        return 'kitchen';
    }
    if (str_contains($s, 'sales') || str_contains($s, 'marketing')) {
        return 'sales-marketing';
    }
    if (str_contains($s, 'finance') || str_contains($s, 'accounting')) {
        return 'finance';
    }
    if (str_contains($s, 'engineering') || str_contains($s, 'maintenance')) {
        return 'engineering';
    }
    if (str_contains($s, 'security')) {
        return 'security';
    }

    $s = preg_replace('/\([^\)]*\)/', '', $s);
    $s = str_replace(['&', '/', '_'], ['and', '-', '-'], $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-');
}

$mode = $data['mode'] ?? 'employee_assignment';

$exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
$employees = $data['employees'] ?? [];
$due_date = $data['due_date'] ?? null;
$time_limit = $data['time_limit'] ?? 60;
$attempts_allowed = $data['attempts_allowed'] ?? 1;
$instructions = $data['instructions'] ?? '';

$audience = $data['audience'] ?? null;
$department = isset($data['department']) ? normalize_department_slug((string)$data['department']) : null;
$roles = $data['roles'] ?? [];

$employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;

if ($mode === 'list_employees') {
    if (!$department) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    $role = trim((string)($data['role'] ?? ''));
    if ($role === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS employees (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare('SELECT id, first_name, last_name, email FROM employees WHERE status = "active" AND department = ? AND position = ? ORDER BY last_name, first_name');
    $stmt->bind_param('ss', $department, $role);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $out[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'employees' => $out]);
    $conn->close();
    exit;
}

if ($mode === 'role_mapping') {
    if (!$exam_id || !$audience || !$department) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    if (!in_array($audience, ['applicant', 'employee'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid audience']);
        exit;
    }

    if (!is_array($roles)) {
        $roles = [];
    }

    $roles = array_values(array_filter(array_map(static fn($r) => trim((string)$r), $roles), static fn($r) => $r !== ''));
    if (empty($roles)) {
        echo json_encode(['success' => false, 'message' => 'Please select at least one role']);
        exit;
    }
} elseif ($mode === 'specific_employee_mapping') {
    if (!$exam_id || !$department || !$employee_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    if (!is_array($roles)) {
        $roles = [];
    }

    $roles = array_values(array_filter(array_map(static fn($r) => trim((string)$r), $roles), static fn($r) => $r !== ''));
    if (count($roles) !== 1) {
        echo json_encode(['success' => false, 'message' => 'Please select exactly one role']);
        exit;
    }
} else {
    if (!$exam_id || empty($employees) || !$due_date) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    if ($mode === 'role_mapping') {
        $create_table_sql = "CREATE TABLE IF NOT EXISTS exam_repository_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            exam_id INT NOT NULL,
            audience ENUM('applicant', 'employee') NOT NULL,
            department VARCHAR(100) NOT NULL,
            role VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            assigned_by VARCHAR(50),
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_exam_audience_dept_role (exam_id, audience, department, role),
            INDEX idx_audience_dept_role (audience, department, role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $conn->query($create_table_sql);

        $assigned_by = (string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? '');

        $stmt = $conn->prepare("INSERT IGNORE INTO exam_repository_assignments (exam_id, audience, department, role, assigned_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($roles as $role) {
            $stmt->bind_param('issss', $exam_id, $audience, $department, $role, $assigned_by);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Examination assigned successfully'
        ]);
        $conn->close();
        exit;
    }

    if ($mode === 'specific_employee_mapping') {
        $conn->query("CREATE TABLE IF NOT EXISTS employees (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            department VARCHAR(100) NOT NULL,
            position VARCHAR(100) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $create_table_sql = "CREATE TABLE IF NOT EXISTS exam_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            exam_id INT NOT NULL,
            employee_id INT NOT NULL,
            due_date DATETIME NOT NULL,
            time_limit INT DEFAULT 60,
            attempts_allowed INT DEFAULT 1,
            attempts_used INT DEFAULT 0,
            status ENUM('assigned', 'in-progress', 'completed', 'overdue', 'cancelled') DEFAULT 'assigned',
            instructions TEXT,
            assigned_by INT,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME,
            completed_at DATETIME,
            score DECIMAL(5,2),
            FOREIGN KEY (exam_id) REFERENCES exam_repository(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )";

        $conn->query($create_table_sql);

        $user_id = $_SESSION['user_id'] ?? 0;
        $role = $roles[0];

        $stmt = $conn->prepare('INSERT INTO exam_assignments (exam_id, employee_id, due_date, time_limit, attempts_allowed, instructions, assigned_by) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), ?, ?, ?, ?)');
        $stmt->bind_param('iiiisi', $exam_id, $employee_id, $time_limit, $attempts_allowed, $instructions, $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Examination assigned successfully'
        ]);
        $conn->close();
        exit;
    }

    // Create assignments table if not exists
    $create_table_sql = "CREATE TABLE IF NOT EXISTS exam_assignments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_id INT NOT NULL,
        employee_id INT NOT NULL,
        due_date DATETIME NOT NULL,
        time_limit INT DEFAULT 60,
        attempts_allowed INT DEFAULT 1,
        attempts_used INT DEFAULT 0,
        status ENUM('assigned', 'in-progress', 'completed', 'overdue', 'cancelled') DEFAULT 'assigned',
        instructions TEXT,
        assigned_by INT,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        started_at DATETIME,
        completed_at DATETIME,
        score DECIMAL(5,2),
        FOREIGN KEY (exam_id) REFERENCES exam_repository(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )";
    
    $conn->query($create_table_sql);
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Insert assignments for each employee
    foreach ($employees as $employee) {
        $stmt = $conn->prepare("INSERT INTO exam_assignments 
            (exam_id, employee_id, due_date, time_limit, attempts_allowed, instructions, assigned_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiisi", 
            $exam_id,
            $employee['id'],
            $due_date,
            $time_limit,
            $attempts_allowed,
            $instructions,
            $user_id
        );
        $stmt->execute();
        $stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Examination assigned to ' . count($employees) . ' employee(s) successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>