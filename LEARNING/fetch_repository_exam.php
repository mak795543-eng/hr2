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
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get exam ID
$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    echo json_encode(['error' => 'No exam ID provided']);
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
    echo json_encode(['error' => 'Examination not found in repository']);
    exit;
}

// Fetch questions from original exam
$questions_sql = "SELECT * FROM examination_questions WHERE examination_id = ? ORDER BY question_number";
$questions_stmt = $conn->prepare($questions_sql);
$questions_stmt->bind_param("i", $exam['original_exam_id']);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = [];

while($question = $questions_result->fetch_assoc()) {
    $questions[] = $question;
}
$questions_stmt->close();

$exam['questions'] = $questions;

$conn->close();

header('Content-Type: application/json');
echo json_encode($exam);
?>