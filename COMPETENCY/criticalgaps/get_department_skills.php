<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['department'])) {
    echo json_encode(['error' => 'Department is required']);
    exit;
}

$department = $_GET['department'];
$skills = getDepartmentSkills($department);

echo json_encode($skills);
?>