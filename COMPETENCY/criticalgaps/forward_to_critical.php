<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $employeeId = trim((string)($payload['employee_id'] ?? ''));
    $period = trim((string)($payload['evaluation_period'] ?? ''));

    if ($employeeId === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'employee_id is required']);
        exit;
    }

    if ($period === '') {
        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
    }

    ensureKpiSchema();
    ensureCompetencyCriteriaSchema();
    ensureGapFormulationSchema();

    $analysis = computeEmployeeKpiAnalysis($employeeId, $period);
    $overall = (array)($analysis['overall'] ?? []);

    $overallPct = is_numeric($overall['pct'] ?? null) ? (float)$overall['pct'] : 0.0;
    if ($overallPct < 0) $overallPct = 0.0;
    if ($overallPct > 100) $overallPct = 100.0;

    $status = trim((string)($overall['status'] ?? mapCompetencyToStatus($overallPct)));

    $details = [
        'evaluation_period' => $period,
        'computed' => $analysis['computed'] ?? [],
        'overall' => $overall,
    ];
    $detailsJson = json_encode($details, JSON_UNESCAPED_SLASHES);

    $stmt = $pdo->prepare(
        'INSERT INTO kpi_gap_formulations
            (employee_id, evaluation_period, overall_competency, status, details_json, forwarded_to_critical, forwarded_at)
         VALUES
            (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE
            overall_competency = VALUES(overall_competency),
            status = VALUES(status),
            details_json = VALUES(details_json),
            forwarded_to_critical = 1,
            forwarded_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$employeeId, $period, $overallPct, $status, $detailsJson]);

    echo json_encode(['success' => true, 'employee_id' => $employeeId, 'evaluation_period' => $period], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
