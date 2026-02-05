<?php
session_start();

header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

// Get exam ID
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

if ($exam_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

// Fetch exam data from repository
$sql = "SELECT er.*, lm.title as module_title FROM exam_repository er 
        LEFT JOIN learning_modules lm ON er.module_id = lm.id 
        WHERE er.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();
$stmt->close();

if (!$exam) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Examination not found in repository']);
    exit;
}

// Fetch questions from repository questions
$questions_sql = "SELECT * FROM exam_repository_questions WHERE exam_id = ? ORDER BY question_number";
$questions_stmt = $conn->prepare($questions_sql);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = [];

while($question = $questions_result->fetch_assoc()) {
    $questions[] = $question;
}
$questions_stmt->close();

$exam['questions'] = $questions;

$conn->close();

echo json_encode(array_merge(['success' => true], $exam));
?>