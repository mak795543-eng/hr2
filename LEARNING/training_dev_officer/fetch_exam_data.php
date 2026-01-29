<?php
session_start();

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get exam ID from request
$examId = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($examId <= 0) {
    die(json_encode(['error' => 'Invalid exam ID']));
}

// Fetch exam data
$stmt = $conn->prepare("SELECT * FROM examinations WHERE id = ?");
$stmt->bind_param("i", $examId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(['error' => 'Examination not found']));
}

$examData = $result->fetch_assoc();
$stmt->close();

// Fetch questions for this exam
$stmt = $conn->prepare("SELECT * FROM examination_questions WHERE examination_id = ? ORDER BY question_number");
$stmt->bind_param("i", $examId);
$stmt->execute();
$questionsResult = $stmt->get_result();

$questions = [];
while ($question = $questionsResult->fetch_assoc()) {
    $questions[] = $question;
}
$stmt->close();

$examData['questions'] = $questions;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($examData);

$conn->close();
?>