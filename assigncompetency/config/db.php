<?php
// config/db.php
header('Content-Type: application/json');
require_once 'db_config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$action = $input['action'] ?? '';
$sql = $input['sql'] ?? '';
$params = $input['params'] ?? [];

try {
    global $pdo;
    
    if ($action === 'query') {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
        
    } elseif ($action === 'execute') {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'affectedRows' => $stmt->rowCount()]);
        
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>