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
    $unionBase = implode(" UNION ", $parts);

    $sql = "
        SELECT 
            b.employee_id,
            b.full_name,
            b.department,
            b.position,
            COALESCE(gs.competency, 0) AS overall_competency,
            CASE
                WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                ELSE 'Succession Ready'
            END AS status
        FROM (
            {$unionBase}
        ) b
        LEFT JOIN (
            SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
            FROM employee_kpi_scores s2
            WHERE s2.evaluation_period = ?
            GROUP BY s2.employee_id
        ) gs ON gs.employee_id = b.employee_id
        GROUP BY b.employee_id, b.full_name, b.department, b.position, gs.competency
        ORDER BY b.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$period]);
    $rows = $stmt->fetchAll();

    if (!$rows || count($rows) === 0) {
        $fallback = $pdo->prepare("
            SELECT 
                ss.employee_id,
                ss.employee_name AS full_name,
                ss.department,
                ss.position,
                COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) AS overall_competency,
                CASE
                    WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 20 THEN 'Retrain'
                    WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 40 THEN 'Reskilling'
                    WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 60 THEN 'Refresher Training'
                    WHEN COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) <= 80 THEN 'Upskilling'
                    ELSE 'Succession Ready'
                END AS status
            FROM succession_submissions ss
            LEFT JOIN employee_kpi_scores s
              ON s.employee_id = ss.employee_id
             AND s.evaluation_period = ?
            WHERE ss.is_pushed = 1
            GROUP BY ss.employee_id, ss.employee_name, ss.department, ss.position
            ORDER BY ss.employee_name ASC
        ");
        $fallback->execute([$period]);
        $rows = $fallback->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'period' => $period,
        'employees' => $rows ?: []
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
