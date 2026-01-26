<?php
session_start();

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get module ID
$module_id = $_GET['module_id'] ?? null;

if (!$module_id) {
    echo json_encode(['error' => 'No module ID provided']);
    exit;
}

// Fetch module data
$sql = "SELECT id, title, department, roles, content, key_points FROM learning_modules WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $module_id);
$stmt->execute();
$result = $stmt->get_result();
$module = $result->fetch_assoc();
$stmt->close();

if (!$module) {
    echo json_encode(['error' => 'Module not found']);
    exit;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($module);
?>