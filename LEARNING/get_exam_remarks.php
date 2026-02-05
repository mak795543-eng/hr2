<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr2_soliera_usm";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    echo json_encode(['success' => false, 'message' => 'No exam ID provided']);
    exit();
}

$sql = "SELECT remarks FROM exam_repository WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'remarks' => $row['remarks']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Examination not found']);
}

$stmt->close();
$conn->close();
?>