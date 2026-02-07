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
    ensureGapFormulationSchema();

    $essDb = getenv('ESS_DB_NAME') ?: 'hr2_employee_self_service';
    $hasEss = false;
    try {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
        $chk->execute([$essDb]);
        $hasEss = ((int)$chk->fetchColumn() > 0);
    } catch (Throwable $e) {
        $hasEss = false;
    }

    $parts = [];
    $parts[] = "SELECT e.employee_id, e.full_name, e.department, e.position FROM employees e";
    $parts[] = "SELECT ss.employee_id, ss.employee_name AS full_name, ss.department, ss.position FROM succession_submissions ss WHERE ss.is_pushed = 1";
    if ($hasEss) {
        $parts[] = "SELECT ess.employee_no AS employee_id, CONCAT(ess.first_name, ' ', COALESCE(ess.last_name,'')) AS full_name, ess.department, ess.position FROM `{$essDb}`.`employees` ess";
    }
    $unionBase = implode(' UNION ', $parts);

    $sql = "
        SELECT 
            b.employee_id,
            b.full_name,
            b.department,
            b.position
        FROM (
            {$unionBase}
        ) b
        LEFT JOIN kpi_gap_formulations kgf
          ON kgf.employee_id = b.employee_id
         AND kgf.evaluation_period = ?
        LEFT JOIN (
            SELECT employee_id
            FROM employee_kpi_scores
            WHERE evaluation_period = ?
            GROUP BY employee_id
            HAVING COUNT(*) > 0
        ) sc ON sc.employee_id = b.employee_id
        WHERE kgf.employee_id IS NULL OR sc.employee_id IS NULL
        GROUP BY b.employee_id, b.full_name, b.department, b.position
        ORDER BY b.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$period, $period]);
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
