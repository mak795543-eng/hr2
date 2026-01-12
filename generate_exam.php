<?php
include 'db.php';
header("Content-Type: application/json");

$role = $_POST['role'] ?? '';
$exam_title = $_POST['exam_title'] ?? "Exam for $role";

// Create Exam
$stmt = $conn->prepare("INSERT INTO exams (exam_title, role) VALUES (?, ?)");
$stmt->bind_param("ss", $exam_title, $role);
$stmt->execute();
$exam_id = $stmt->insert_id;
$stmt->close();

// Static role-based questions
$questions = [];

if ($role === "travel_agent") {
    $questions = [
        ["type"=>"mcq","question"=>"What is the first step when creating a customized travel package?","choices"=>["Book airline tickets","Ask client’s preferences","Reserve hotel rooms","Suggest popular package"],"answer"=>"Ask client’s preferences"],
        ["type"=>"true_false","question"=>"Knowledge of visa requirements is essential for a travel agent.","answer"=>"True"],
        ["type"=>"essay","question"=>"A client’s flight was canceled. How would you handle the situation?"]
    ];
}

foreach($questions as $q) {
    $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, choices, correct_answer) VALUES (?, ?, ?, ?, ?)");
    $choices = isset($q['choices']) ? json_encode($q['choices']) : null;
    $answer = $q['answer'] ?? null;
    $stmt->bind_param("issss", $exam_id, $q['question'], $q['type'], $choices, $answer);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(["exam_id"=>$exam_id,"message"=>"Exam created successfully!"]);
