<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get module ID from request
$module_id = $_GET['module_id'] ?? '';

if (empty($module_id)) {
    echo json_encode(['error' => 'Module ID is required']);
    exit;
}

// Fetch module data
$sql = "SELECT * FROM learning_modules WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $module_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $module = $result->fetch_assoc();
    echo json_encode($module);
} else {
    echo json_encode(['error' => 'Module not found']);
}


$stmt->close();
$conn->close();
?>