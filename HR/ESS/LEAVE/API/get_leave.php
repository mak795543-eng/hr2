<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Invalid request ID';
    echo json_encode($response);
    exit;
}

$id = intval($_GET['id']);

// Fetch leave request from database
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format dates for better display
    $row['start_date_formatted'] = date('M d, Y', strtotime($row['start_date']));
    $row['end_date_formatted'] = date('M d, Y', strtotime($row['end_date']));
    $row['created_at_formatted'] = date('M d, Y', strtotime($row['created_at']));
    
    $response['success'] = true;
    $response['data'] = $row;
} else {
    $response['message'] = 'Leave request not found';
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>