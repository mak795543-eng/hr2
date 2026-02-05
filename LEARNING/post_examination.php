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
$action = $_POST['action'] ?? 'post';

if (!$exam_id) {
    echo json_encode(['success' => false, 'message' => 'No exam ID provided']);
    exit();
}

$reviewer_id = $_SESSION['user_id'] ?? 1;

if ($action === 'post') {
    $sql = "UPDATE exam_repository SET status = 'posted', posted_by = ?, posted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reviewer_id, $exam_id);
} elseif ($action === 'unpost') {
    $sql = "UPDATE exam_repository SET status = 'approved', posted_by = NULL, posted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exam_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Examination status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>