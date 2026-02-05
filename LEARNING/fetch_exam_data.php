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

// Fetch exam data
$sql = "SELECT * FROM examinations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();
$stmt->close();

if (!$exam) {
    echo json_encode(['error' => 'Examination not found']);
    exit;
}

// Fetch questions for this exam
$questions_sql = "SELECT * FROM examination_questions WHERE examination_id = ? ORDER BY question_number";
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

// Count questions
$count_sql = "SELECT COUNT(*) as question_count FROM examination_questions WHERE examination_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $exam_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$exam['question_count'] = $count_row['question_count'];
$count_stmt->close();

$conn->close();

header('Content-Type: application/json');
echo json_encode($exam);
?>