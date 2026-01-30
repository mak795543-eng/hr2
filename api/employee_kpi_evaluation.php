<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../COMPETENCY/criticalgaps/config.php';

$employeeId = isset($_GET['employee_id']) ? trim((string)$_GET['employee_id']) : '';
if ($employeeId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'employee_id is required']);
    exit;
}

$evaluationPeriod = isset($_GET['evaluation_period']) ? trim((string)$_GET['evaluation_period']) : '';
if ($evaluationPeriod === '') {
    $evaluationPeriod = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
}

try {
    if (function_exists('ensureKpiSchema')) {
        ensureKpiSchema();
    }

    if (function_exists('seedMissingKpiEvaluations')) {
        seedMissingKpiEvaluations($employeeId, $evaluationPeriod);
    }

    $stmtEmp = $pdo->prepare('SELECT employee_id, full_name, department, position FROM employees WHERE employee_id = ? LIMIT 1');
    $stmtEmp->execute([$employeeId]);
    $emp = $stmtEmp->fetch();

    if (!$emp) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    $stmtKpis = $pdo->prepare(
        'SELECT k.id, k.kpi_name
         FROM employee_kpi_scores s
         JOIN kpis k ON k.id = s.kpi_id
         WHERE s.employee_id = ? AND s.evaluation_period = ?
         GROUP BY k.id, k.kpi_name
         ORDER BY k.kpi_name ASC'
    );
    $stmtKpis->execute([$employeeId, $evaluationPeriod]);
    $kpiRows = $stmtKpis->fetchAll();

    $stmtEvals = $pdo->prepare(
        'SELECT k.id AS kpi_id, s.criteria, s.score
         FROM employee_kpi_scores s
         JOIN kpis k ON k.id = s.kpi_id
         WHERE s.employee_id = ? AND s.evaluation_period = ?
         ORDER BY k.kpi_name ASC, s.id ASC'
    );
    $stmtEvals->execute([$employeeId, $evaluationPeriod]);
    $evalRows = $stmtEvals->fetchAll();

    $byKpi = [];
    foreach ($evalRows as $r) {
        $kid = (int)($r['kpi_id'] ?? 0);
        if (!isset($byKpi[$kid])) {
            $byKpi[$kid] = [];
        }
        $byKpi[$kid][] = [
            'criteria' => (string)($r['criteria'] ?? ''),
            'score' => is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0,
        ];
    }

    $kpis = [];
    foreach ($kpiRows as $k) {
        $kid = (int)($k['id'] ?? 0);
        $kpis[] = [
            'kpi_id' => $kid,
            'kpi_name' => (string)($k['kpi_name'] ?? ''),
            'evaluations' => $byKpi[$kid] ?? [],
        ];
    }

    echo json_encode([
        'employee_id' => $emp['employee_id'],
        'employee_name' => $emp['full_name'],
        'department' => $emp['department'],
        'role' => $emp['position'],
        'evaluation_period' => $evaluationPeriod,
        'kpis' => $kpis,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
