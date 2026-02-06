<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $period = '';
    if (isset($_GET['period'])) {
        $period = trim((string)$_GET['period']);
    }
    if ($period === '') {
        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
    }

    ensureKpiSchema();

    $sql = "
        SELECT 
            e.employee_id,
            e.full_name,
            e.department,
            e.position,
            COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) AS overall_competency,
            CASE
                WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 20 THEN 'Retrain'
                WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 40 THEN 'Reskilling'
                WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 60 THEN 'Refresher Training'
                WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 80 THEN 'Upskilling'
                ELSE 'Succession Ready'
            END AS status
        FROM employees e
        LEFT JOIN employee_kpi_scores s 
          ON s.employee_id = e.employee_id
         AND s.evaluation_period = ?
        GROUP BY e.employee_id, e.full_name, e.department, e.position
        ORDER BY e.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$period]);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'period' => $period,
        'employees' => $rows ?: []
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
