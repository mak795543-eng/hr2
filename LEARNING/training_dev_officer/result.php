<?php
session_start();
require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('h2_training');

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$score = 0;
$total = 0;

foreach($_POST as $qid => $ans){
    $id = str_replace("q", "", $qid);
    $query = $conn->query("SELECT correct_answer FROM questions WHERE id = $id");
    if($row = $query->fetch_assoc()){
        $total++;
        if($ans == $row['correct_answer']){
            $score++;
        }
    }
}

$user = "Guest"; // later replace with applicant’s name
$conn->query("INSERT INTO results (user_name, score) VALUES ('$user', $score)");

echo "<h1>You scored $score / $total</h1>";
$conn->close();
?>
