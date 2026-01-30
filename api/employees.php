<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../COMPETENCY/criticalgaps/config.php';

try {
    $stmt = $pdo->query('SELECT employee_id, full_name, department, position FROM employees ORDER BY full_name ASC');
    $rows = $stmt ? $stmt->fetchAll() : [];

    echo json_encode([
        'success' => true,
        'data' => $rows,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
