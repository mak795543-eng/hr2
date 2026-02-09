<?php
require_once 'config.php';

header('Content-Type: application/json');

action();

function action() {
    global $pdo;

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        $items = null;
        if (isset($data['employees']) && is_array($data['employees'])) {
            $items = $data['employees'];
        } elseif (isset($data[0]) && is_array($data[0])) {
            $items = $data;
        }

        $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

        $calcStatus = function (float $competency): string {
            if ($competency <= 20) return 'Retrain';
            if ($competency <= 40) return 'Reskilling';
            if ($competency <= 60) return 'Refresher Training';
            if ($competency <= 80) return 'Upskilling';
            return 'Succession Ready';
        };

        $stmtCompute = $pdo->prepare(
            "SELECT e.department,
                    AVG(COALESCE(s.score, 0)) / 5 * 100 AS competency
             FROM employees e
             LEFT JOIN employee_kpi_scores s
               ON s.employee_id = e.employee_id
              AND s.evaluation_period = ?
             WHERE e.employee_id = ?
             GROUP BY e.employee_id, e.department"
        );

        $stmt = $pdo->prepare(
            "INSERT INTO succession_submissions
                (employee_id, employee_name, position, department, competency, status, idp_status, is_pushed)
             VALUES
                (?, ?, ?, ?, ?, ?, 'Pending', 1)
             ON DUPLICATE KEY UPDATE
                employee_name = VALUES(employee_name),
                position = VALUES(position),
                department = VALUES(department),
                competency = VALUES(competency),
                status = VALUES(status),
                is_pushed = 1,
                updated_at = CURRENT_TIMESTAMP"
        );

        $stmtReq = $pdo->prepare(
            "INSERT INTO requested_to_idp (employee_id, employee_name, position, department, status)
             VALUES (?, ?, ?, ?, 'Pending')
             ON DUPLICATE KEY UPDATE
                employee_name = VALUES(employee_name),
                position = VALUES(position),
                department = VALUES(department),
                status = 'Pending',
                updated_at = CURRENT_TIMESTAMP"
        );

        if ($items !== null) {
            $inserted = 0;
            $skipped = 0;

            $pdo->beginTransaction();
            foreach ($items as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $employeeId = trim((string)($row['employee_id'] ?? ''));
                $employeeName = trim((string)($row['employee_name'] ?? ''));
                $position = trim((string)($row['position'] ?? ''));
                $department = trim((string)($row['department'] ?? ''));

                $competency = 0;
                $status = 'Retrain';
                try {
                    seedMissingKpiEvaluations($employeeId, $period);
                    $stmtCompute->execute([$period, $employeeId]);
                    $compRow = $stmtCompute->fetch(PDO::FETCH_ASSOC);
                    if ($compRow) {
                        $department = $department !== '' ? $department : (string)($compRow['department'] ?? '');
                        $competency = is_numeric($compRow['competency'] ?? null) ? (float)$compRow['competency'] : 0;
                        $status = $calcStatus($competency);
                    }
                } catch (Throwable $e) {
                    $competency = 0;
                    $status = 'Retrain';
                }

                if ($employeeId === '' || $employeeName === '' || $position === '' || $department === '') {
                    $skipped++;
                    continue;
                }

                $stmt->execute([$employeeId, $employeeName, $position, $department, $competency, $status]);
                $stmtReq->execute([$employeeId, $employeeName, $position, $department]);
                $inserted++;
            }
            $pdo->commit();

            echo json_encode(['success' => true, 'inserted' => $inserted, 'skipped' => $skipped]);
            return;
        }

        $employeeId = trim((string)($data['employee_id'] ?? ''));
        $employeeName = trim((string)($data['employee_name'] ?? ''));
        $position = trim((string)($data['position'] ?? ''));
        $department = trim((string)($data['department'] ?? ''));

        $competency = 0;
        $status = 'Retrain';
        try {
            seedMissingKpiEvaluations($employeeId, $period);
            $stmtCompute->execute([$period, $employeeId]);
            $compRow = $stmtCompute->fetch(PDO::FETCH_ASSOC);
            if ($compRow) {
                $department = $department !== '' ? $department : (string)($compRow['department'] ?? '');
                $competency = is_numeric($compRow['competency'] ?? null) ? (float)$compRow['competency'] : 0;
                $status = $calcStatus($competency);
            }
        } catch (Throwable $e) {
            $competency = 0;
            $status = 'Retrain';
        }

        if ($employeeId === '' || $employeeName === '' || $position === '' || $department === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $stmt->execute([$employeeId, $employeeName, $position, $department, $competency, $status]);
        $stmtReq->execute([$employeeId, $employeeName, $position, $department]);

        echo json_encode(['success' => true, 'inserted' => 1, 'skipped' => 0]);
    } catch (Throwable $e) {
        try {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $ignored) {
        }
        error_log('submit_to_succession error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
