<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$exam_id = $_POST['exam_id'] ?? null;

if (!$exam_id) {
    echo json_encode(['success' => false, 'message' => 'No exam ID provided']);
    exit();
}

$sql = "DELETE FROM exam_repository WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Examination deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}

$stmt->close();
$conn->close();
?>