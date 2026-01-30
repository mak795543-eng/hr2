<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../COMPETENCY/criticalgaps/config.php';

$evaluationPeriod = isset($_GET['evaluation_period']) ? trim((string)$_GET['evaluation_period']) : '';
if ($evaluationPeriod === '') {
    $evaluationPeriod = '2026-Q1';
}

try {
    if (function_exists('ensureKpiSchema')) {
        ensureKpiSchema();
    }

    $stmt = $pdo->query('SELECT employee_id FROM employees ORDER BY employee_id ASC');
    $employees = $stmt ? $stmt->fetchAll() : [];

    $seeded = 0;
    foreach ($employees as $e) {
        $eid = (string)($e['employee_id'] ?? '');
        if ($eid === '') continue;
        if (function_exists('seedMissingKpiEvaluations')) {
            $did = seedMissingKpiEvaluations($eid, $evaluationPeriod);
            if ($did) $seeded++;
        }
    }

    echo json_encode([
        'success' => true,
        'evaluation_period' => $evaluationPeriod,
        'seeded_employees' => $seeded,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
