<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('hr2_learning_db');

function respondJson(int $statusCode, array $payload): void
{
    if (ob_get_length() !== false) {
        ob_clean();
    }
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($conn->connect_error) {
    respondJson(500, ['error' => 'Connection failed']);
}

$conn->set_charset('utf8mb4');

$moduleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT);
if (!$moduleId) {
    respondJson(400, ['error' => 'No module ID provided']);
}

$sql = "SELECT id, title, topic, department, roles, content, status FROM learning_modules WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmtOk = $stmt instanceof mysqli_stmt;
if (!$stmtOk) {
    $conn->close();
    respondJson(500, ['error' => 'Query prepare failed']);
}

$stmt->bind_param("i", $moduleId);
$execOk = $stmt->execute();
if (!$execOk) {
    $stmt->close();
    $conn->close();
    respondJson(500, ['error' => 'Query execution failed']);
}

$stmt->bind_result($id, $title, $topic, $department, $roles, $content, $status);
$fetched = $stmt->fetch();
$module = $fetched ? [
    'id' => $id,
    'title' => $title,
    'topic' => $topic,
    'department' => $department,
    'roles' => $roles,
    'content' => $content,
    'status' => $status,
] : null;
$stmt->close();

if (!$module) {
    $conn->close();
    respondJson(404, ['error' => 'Module not found']);
}

$conn->close();

if (ob_get_length() !== false) {
    ob_clean();
}
echo json_encode($module);
