<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    ensureKpiSchema();

    $sql = "
        SELECT 
            e.employee_id,
            e.full_name,
            e.department,
            e.position
        FROM employees e
        LEFT JOIN employee_kpi_scores s
          ON s.employee_id = e.employee_id
        WHERE s.employee_id IS NULL
        ORDER BY e.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([]);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'employees' => $rows ?: []
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
