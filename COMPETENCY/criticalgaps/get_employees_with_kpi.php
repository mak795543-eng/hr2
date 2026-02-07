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
            e.position,
            COALESCE(AVG(COALESCE(s.score, 0)) / 5 * 100, 0) AS overall_competency,
            CASE
                WHEN COUNT(s.id) = 0 THEN 'Not Evaluated'
                WHEN (AVG(COALESCE(s.score, 0)) / 5 * 100) <= 20 THEN 'Retrain'
                WHEN (AVG(COALESCE(s.score, 0)) / 5 * 100) <= 40 THEN 'Reskilling'
                WHEN (AVG(COALESCE(s.score, 0)) / 5 * 100) <= 60 THEN 'Refresher Training'
                WHEN (AVG(COALESCE(s.score, 0)) / 5 * 100) <= 80 THEN 'Upskilling'
                ELSE 'Succession Ready'
            END AS status,
            COUNT(s.id) AS eval_count
        FROM employees e
        LEFT JOIN employee_kpi_scores s 
          ON s.employee_id = e.employee_id
        GROUP BY e.employee_id, e.full_name, e.department, e.position
        ORDER BY e.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([]);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $r) {
        $byId[(string)($r['employee_id'] ?? '')] = $r;
    }

    try {
        $ess = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=hr2_employee_self_service;charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        $resEss = $ess->query("SELECT employee_no, first_name, last_name, department, position FROM employees");
        $rowsEss = $resEss ? $resEss->fetchAll() : [];
        foreach ($rowsEss as $er) {
            $empId = trim((string)($er['employee_no'] ?? ''));
            if ($empId === '') continue;
            if (!isset($byId[$empId])) {
                $full = trim((string)($er['first_name'] ?? '') . ' ' . (string)($er['last_name'] ?? ''));
                $byId[$empId] = [
                    'employee_id' => $empId,
                    'full_name' => ($full !== '' ? $full : $empId),
                    'department' => (string)($er['department'] ?? ''),
                    'position' => (string)($er['position'] ?? ''),
                    'overall_competency' => 0,
                    'status' => 'Not Evaluated',
                    'eval_count' => 0,
                ];
            }
        }
    } catch (Throwable $e) {
        // ignore ESS fallback errors
    }

    $out = array_values($byId);
    usort($out, static function ($a, $b) {
        return strcasecmp((string)($a['full_name'] ?? ''), (string)($b['full_name'] ?? ''));
    });

    echo json_encode([
        'success' => true,
        'employees' => $out
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
