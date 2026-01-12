<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Employee ID is required']);
    exit;
}

$employee_id = $_GET['id'];
$employee = getEmployeeDetails($employee_id);

if (!$employee) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

echo json_encode($employee);
?>