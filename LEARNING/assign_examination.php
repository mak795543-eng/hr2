<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

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

$exam_id = $data['exam_id'] ?? null;
$employees = $data['employees'] ?? [];
$due_date = $data['due_date'] ?? null;
$time_limit = $data['time_limit'] ?? 60;
$attempts_allowed = $data['attempts_allowed'] ?? 1;
$instructions = $data['instructions'] ?? '';

if (!$exam_id || empty($employees) || !$due_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
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
        $stmt->bind_param("iisiiii", 
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