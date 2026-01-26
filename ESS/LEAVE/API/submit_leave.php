<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
$dbPrefix = getenv('DB_PREFIX') ?: '';
$servername = getenv('LEAVE_DB_HOST') ?: (getenv('DB_HOST') ?: "localhost");
$username = getenv('LEAVE_DB_USER') ?: (getenv('DB_USER') ?: "root");
$passwordEnv = getenv('LEAVE_DB_PASS');
$passwordGlobal = getenv('DB_PASS');
$password = $passwordEnv !== false
    ? $passwordEnv
    : ($passwordGlobal !== false
        ? $passwordGlobal
        : (($username === 'root' && ($servername === 'localhost' || $servername === '127.0.0.1')) ? '' : 'makmak01'));
$dbname = getenv('LEAVE_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'ESS_leave_db') : 'ESS_leave_db');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$leave_type = $_POST['leave_type'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = $_POST['reason'] ?? '';
$days = $_POST['days'] ?? 1;

if (empty($leave_type) || empty($start_date) || empty($end_date)) {
    $response['message'] = 'All required fields must be filled';
    echo json_encode($response);
    exit;
}

try {
    if (!is_numeric($days) || $days < 1) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $end->diff($start)->days + 1;
    }
    
    $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days, reason) VALUES (?, ?, ?, ?, ?, ?)");
    
    $employee_id = $_SESSION['user_id'] ?? 1;
    
    $stmt->bind_param("isssis", 
        $employee_id,
        $leave_type,
        $start_date,
        $end_date,
        $days,
        $reason
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Leave request submitted successfully';
        $response['id'] = $stmt->insert_id;
    } else {
        $response['message'] = 'Database error: ' . $stmt->error;
    }
    
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>