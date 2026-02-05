<?php
// application_handler.php
session_start();

include("../db.php");

$db_name = "h2_training";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

// Check connection
if ($conn->connect_error) {
    $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// ✅ Handle applicant info form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "saveApplicant") {
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName  = $conn->real_escape_string($_POST['last_name']);
    $email     = $conn->real_escape_string($_POST['email']);
    $phone     = $conn->real_escape_string($_POST['phone']);
    $position  = $conn->real_escape_string($_POST['position']);

    $sql = "INSERT INTO applicants (first_name, last_name, email, phone, position)
            VALUES ('$firstName', '$lastName', '$email', '$phone', '$position')";

    if ($conn->query($sql)) {
        $_SESSION['applicant_id'] = $conn->insert_id;
        echo json_encode([
            "status" => "success",
            "message" => "Applicant saved",
            "id" => $_SESSION['applicant_id']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $conn->error
        ]);
    }
    exit;
}

// ✅ Handle exam submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "saveExam") {
    $applicantId    = intval($_SESSION['applicant_id'] ?? 0);
    $score          = intval($_POST['score']);
    $correctAnswers = intval($_POST['correct_answers']);
    $totalQuestions = intval($_POST['total_questions']);
    $timeTaken      = $conn->real_escape_string($_POST['time_taken']);

    if ($applicantId > 0) {
        $sql = "UPDATE applicants
                SET exam_score = '$score',
                    correct_answers = '$correctAnswers',
                    total_questions = '$totalQuestions',
                    time_taken = '$timeTaken'
                WHERE id = '$applicantId'";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Exam saved"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } else {
        echo json_encode_
