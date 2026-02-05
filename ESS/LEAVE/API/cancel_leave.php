<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ESS_leave_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    $response['message'] = 'Invalid request ID';
    echo json_encode($response);
    exit;
}

$id = intval($data['id']);

$check = $conn->prepare("SELECT status FROM leave_requests WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->bind_result($status);
$check->fetch();
$check->close();

if (!$status) {
    $response['message'] = 'Leave request not found';
    echo json_encode($response);
    exit;
}

if ($status !== 'pending') {
    $response['message'] = 'Only pending requests can be cancelled';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Leave request cancelled successfully';
} else {
    $response['message'] = 'Failed to cancel request: ' . $stmt->error;
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>