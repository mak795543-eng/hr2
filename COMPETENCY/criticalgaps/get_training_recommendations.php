<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Employee ID is required']);
    exit;
}

$employeeId = $_GET['id'];
$employee = getEmployeeDetails($employeeId);

if (!$employee) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

echo json_encode($employee);
?>