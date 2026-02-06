<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../COMPETENCY/criticalgaps/config.php';

$action = isset($_GET['action']) ? trim((string)$_GET['action']) : 'missing';
$period = isset($_GET['evaluation_period']) ? trim((string)$_GET['evaluation_period']) : '';
if ($period === '') {
    $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
}

try {
    ensureKpiSchema();
    ensureCompetencyCriteriaSchema();
    ensureGapFormulationSchema();

    if ($action === 'criteria') {
        $stmt = $pdo->query('SELECT id, name, description, required_level FROM competency_criteria ORDER BY name ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'processed') {
        $stmt = $pdo->prepare(
            'SELECT g.employee_id, e.full_name, e.department, e.position,
                    g.evaluation_period, g.overall_competency, g.status, g.updated_at
             FROM kpi_gap_formulations g
             JOIN employees e ON e.employee_id = g.employee_id
             WHERE g.evaluation_period = ?
             ORDER BY g.updated_at DESC'
        );
        $stmt->execute([$period]);
        echo json_encode(['success' => true, 'evaluation_period' => $period, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'all_employees') {
        // Return all employees with their computed KPI competency for the period
        $stmt = $pdo->prepare(
            'SELECT e.employee_id, e.full_name, e.department, e.position,
                    COALESCE(g.overall_competency, 0) AS overall_competency,
                    COALESCE(g.status, "Retrain") AS status
             FROM employees e
             LEFT JOIN kpi_gap_formulations g
                ON g.employee_id = e.employee_id AND g.evaluation_period = ?
             ORDER BY e.department ASC, e.full_name ASC'
        );
        $stmt->execute([$period]);
        $rows = $stmt->fetchAll();

        // For employees without saved formulation, compute on-the-fly
        foreach ($rows as &$r) {
            if ((float)($r['overall_competency'] ?? 0) === 0.0) {
                seedMissingKpiEvaluations((string)($r['employee_id'] ?? ''), $period);
                $comp = computeEmployeeCompetency((string)($r['employee_id'] ?? ''));
                $r['overall_competency'] = $comp['competency'];
                $r['status'] = $comp['status'];
            }
        }

        echo json_encode(['success' => true, 'evaluation_period' => $period, 'data' => $rows], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'missing') {
        // Employees missing KPI scores for the period OR missing a saved formulation.
        $stmt = $pdo->prepare(
            'SELECT e.employee_id, e.full_name, e.department, e.position,
                    (CASE WHEN s.employee_id IS NULL THEN 1 ELSE 0 END) AS missing_kpi,
                    (CASE WHEN g.employee_id IS NULL THEN 1 ELSE 0 END) AS missing_formulation
             FROM employees e
             LEFT JOIN (
                SELECT employee_id
                FROM employee_kpi_scores
                WHERE evaluation_period = ?
                GROUP BY employee_id
             ) s ON s.employee_id = e.employee_id
             LEFT JOIN kpi_gap_formulations g
                ON g.employee_id = e.employee_id AND g.evaluation_period = ?
             WHERE s.employee_id IS NULL OR g.employee_id IS NULL
             ORDER BY e.department ASC, e.full_name ASC'
        );
        $stmt->execute([$period, $period]);
        $rows = $stmt->fetchAll();

        // Seed KPI evaluations for those missing KPI scores.
        foreach ($rows as $r) {
            if ((int)($r['missing_kpi'] ?? 0) === 1) {
                $eid = (string)($r['employee_id'] ?? '');
                if ($eid !== '') {
                    seedMissingKpiEvaluations($eid, $period);
                }
            }
        }

        echo json_encode(['success' => true, 'evaluation_period' => $period, 'data' => $rows], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'employee') {
        $employeeId = isset($_GET['employee_id']) ? trim((string)$_GET['employee_id']) : '';
        if ($employeeId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'employee_id is required']);
            exit;
        }

        seedMissingKpiEvaluations($employeeId, $period);

        $stmtEmp = $pdo->prepare('SELECT employee_id, full_name, department, position FROM employees WHERE employee_id = ? LIMIT 1');
        $stmtEmp->execute([$employeeId]);
        $emp = $stmtEmp->fetch();
        if (!$emp) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }

        $criteria = [];
        $stmtC = $pdo->query('SELECT name, required_level FROM competency_criteria');
        $critRows = $stmtC ? $stmtC->fetchAll() : [];
        foreach ($critRows as $c) {
            $criteria[(string)$c['name']] = (float)($c['required_level'] ?? 0);
        }

        $stmtKpis = $pdo->prepare(
            'SELECT k.id, k.kpi_name
             FROM employee_kpi_scores s
             JOIN kpis k ON k.id = s.kpi_id
             WHERE s.employee_id = ? AND s.evaluation_period = ?
             GROUP BY k.id, k.kpi_name
             ORDER BY k.kpi_name ASC'
        );
        $stmtKpis->execute([$employeeId, $period]);
        $kpiRows = $stmtKpis->fetchAll();

        $stmtEvals = $pdo->prepare(
            'SELECT k.id AS kpi_id, s.criteria, s.score
             FROM employee_kpi_scores s
             JOIN kpis k ON k.id = s.kpi_id
             WHERE s.employee_id = ? AND s.evaluation_period = ?
             ORDER BY k.kpi_name ASC, s.id ASC'
        );
        $stmtEvals->execute([$employeeId, $period]);
        $evalRows = $stmtEvals->fetchAll();

        $byKpi = [];
        foreach ($evalRows as $r) {
            $kid = (int)($r['kpi_id'] ?? 0);
            if (!isset($byKpi[$kid])) $byKpi[$kid] = [];
            $byKpi[$kid][] = [
                'criteria' => (string)($r['criteria'] ?? ''),
                'score' => is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0,
            ];
        }

        $maxScore = 5.0;
        $computed = [];
        $overallScores = [];
        foreach ($kpiRows as $k) {
            $kid = (int)($k['id'] ?? 0);
            $kpiName = (string)($k['kpi_name'] ?? '');
            $evals = $byKpi[$kid] ?? [];
            $scores = array_values(array_filter(array_map(static fn($e) => (float)($e['score'] ?? 0), $evals), static fn($v) => is_numeric($v)));
            $avg = count($scores) ? (array_sum($scores) / count($scores)) : 0.0;
            $pct = round(max(0.0, min(100.0, ($avg / $maxScore) * 100.0)), 1);
            $req = isset($criteria[$kpiName]) ? (float)$criteria[$kpiName] : 80.0;
            if ($req < 0) $req = 0.0;
            if ($req > 100) $req = 100.0;
            $gap = round(max(0.0, $req - $pct), 1);

            $computed[] = [
                'kpi_name' => $kpiName,
                'avg' => round($avg, 2),
                'kpi_pct' => $pct,
                'required_pct' => round($req, 1),
                'gap_pct' => $gap,
                'evaluations' => $evals,
            ];

            foreach ($scores as $s) $overallScores[] = $s;
        }

        $overallAvg = count($overallScores) ? (array_sum($overallScores) / count($overallScores)) : 0.0;
        $overallPct = round(max(0.0, min(100.0, ($overallAvg / $maxScore) * 100.0)), 1);

        echo json_encode([
            'success' => true,
            'evaluation_period' => $period,
            'employee' => $emp,
            'computed' => $computed,
            'overall' => [
                'avg' => round($overallAvg, 2),
                'pct' => $overallPct,
                'status' => mapCompetencyToStatus($overallPct),
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'save') {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) $payload = $_POST;

        $employeeId = trim((string)($payload['employee_id'] ?? ''));
        if ($employeeId === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'employee_id is required']);
            exit;
        }

        $overallPctRaw = $payload['overall_competency'] ?? $payload['overall_pct'] ?? null;
        $overallPct = is_numeric($overallPctRaw) ? (float)$overallPctRaw : 0.0;
        if ($overallPct < 0) $overallPct = 0.0;
        if ($overallPct > 100) $overallPct = 100.0;

        $status = trim((string)($payload['status'] ?? mapCompetencyToStatus($overallPct)));
        $detailsJson = isset($payload['details']) ? json_encode($payload['details'], JSON_UNESCAPED_SLASHES) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO kpi_gap_formulations (employee_id, evaluation_period, overall_competency, status, details_json)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                overall_competency = VALUES(overall_competency),
                status = VALUES(status),
                details_json = VALUES(details_json),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$employeeId, $period, $overallPct, $status, $detailsJson]);

        echo json_encode(['success' => true, 'evaluation_period' => $period], JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
