<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

// jjj
if (!function_exists('h')) {
    function h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function mapTargetDateToReadiness(?string $targetDate): string
{
    if ($targetDate === null || $targetDate === '') {
        return 'Ready Now';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $targetDate);
    if (!$dt) {
        return 'Ready Now';
    }

    $now = new DateTime('today');
    if ($dt <= $now) {
        return 'Ready Now';
    }

    $interval = $now->diff($dt);
    $months = ($interval->y * 12) + $interval->m;

    if ($months <= 3) {
        return '3 Months';
    }
    if ($months <= 6) {
        return '6 Months';
    }
    return '12+ Months';
}

$employeeId = trim((string)($_GET['employee_id'] ?? ''));
$idpId = (int)($_GET['idp_id'] ?? 0);
$existingIdp = null;
if ($idpId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM individual_development_plans WHERE id = ? LIMIT 1");
        $stmt->execute([$idpId]);
        $existingIdp = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($existingIdp && $employeeId === '') {
            $employeeId = trim((string)($existingIdp['employee_id'] ?? ''));
        }
    } catch (Throwable $e) {
        $existingIdp = null;
    }
}
if ($employeeId !== '' && !$existingIdp) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM individual_development_plans WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$employeeId]);
        $existingIdp = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $existingIdp = null;
    }
}
$viewOnly = (string)($_GET['view'] ?? '') === '1';

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

$isApi = (string)($_GET['api'] ?? '') === '1';
if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');
    $action = trim((string)($_GET['action'] ?? ''));

    try {
        if ($action === 'employee_criteria_plans') {
            $empId = trim((string)($_GET['employee_id'] ?? ''));
            $p = trim((string)($_GET['period'] ?? $period));
            $employeeStatusFilter = trim((string)($_GET['employee_status'] ?? ''));
            if ($empId === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing employee_id.'], JSON_UNESCAPED_SLASHES);
                exit;
            }
            if ($p === '') {
                $p = $period;
            }

            $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
            if ($employeeStatusFilter !== '' && !in_array($employeeStatusFilter, $allowedStatuses, true)) {
                $employeeStatusFilter = '';
            }

            $analysis = function_exists('computeEmployeeKpiAnalysis') ? computeEmployeeKpiAnalysis($empId, $p) : ['computed' => []];
            $computed = is_array($analysis['computed'] ?? null) ? $analysis['computed'] : [];

            $kpiNames = [];
            foreach ($computed as $c) {
                $name = trim((string)($c['kpi_name'] ?? ''));
                if ($name !== '') {
                    $kpiNames[$name] = true;
                }
            }
            $kpiNames = array_keys($kpiNames);

            if (count($kpiNames) === 0) {
                echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_SLASHES);
                exit;
            }

            $criteriaByName = [];
            try {
                $placeholders = implode(',', array_fill(0, count($kpiNames), '?'));
                $stmt = $pdo->prepare("SELECT id, name, required_level FROM competency_criteria WHERE name IN ($placeholders)");
                $stmt->execute($kpiNames);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $name = trim((string)($r['name'] ?? ''));
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0 && $name !== '') {
                        $criteriaByName[$name] = [
                            'criteria_id' => $id,
                            'required_level' => is_numeric($r['required_level'] ?? null) ? (float)$r['required_level'] : 80.0,
                        ];
                    }
                }
            } catch (Throwable $e) {
            }

            $criteriaIds = [];
            foreach ($criteriaByName as $v) {
                $criteriaIds[] = (int)($v['criteria_id'] ?? 0);
            }
            $criteriaIds = array_values(array_unique(array_filter($criteriaIds, static fn($v) => (int)$v > 0)));

            $plansByCriteriaId = [];
            if (count($criteriaIds) > 0) {
                try {
                    $placeholders = implode(',', array_fill(0, count($criteriaIds), '?'));
                    $stmt = $pdo->prepare(
                        "SELECT id, criteria_id, status, plan_text, delivery_mode, target_percentage
                         FROM competency_development_plans
                         WHERE criteria_id IN ($placeholders)
                         ORDER BY status ASC, updated_at DESC, id DESC"
                    );
                    $stmt->execute($criteriaIds);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $cid = (int)($r['criteria_id'] ?? 0);
                        if ($cid <= 0) continue;
                        $st = trim((string)($r['status'] ?? ''));
                        if ($st === '') continue;
                        if ($employeeStatusFilter !== '' && $st !== $employeeStatusFilter) continue;
                        if (!isset($plansByCriteriaId[$cid])) $plansByCriteriaId[$cid] = [];
                        if (!isset($plansByCriteriaId[$cid][$st])) $plansByCriteriaId[$cid][$st] = [];
                        $plansByCriteriaId[$cid][$st][] = [
                            'id' => (int)($r['id'] ?? 0),
                            'plan_text' => (string)($r['plan_text'] ?? ''),
                            'delivery_mode' => (string)($r['delivery_mode'] ?? 'Onsite'),
                            'target_percentage' => $r['target_percentage'],
                        ];
                    }
                } catch (Throwable $e) {
                }
            }

            $out = [];
            foreach ($computed as $c) {
                $name = trim((string)($c['kpi_name'] ?? ''));
                if ($name === '') continue;
                $gapPct = is_numeric($c['gap_pct'] ?? null) ? (float)$c['gap_pct'] : 0.0;
                if ($employeeStatusFilter === 'Succession Ready' && $gapPct <= 0.0) {
                    continue;
                }
                $meta = $criteriaByName[$name] ?? null;
                $cid = $meta ? (int)($meta['criteria_id'] ?? 0) : 0;
                $out[] = [
                    'criteria_id' => $cid,
                    'criteria_name' => $name,
                    'kpi_pct' => is_numeric($c['kpi_pct'] ?? null) ? (float)$c['kpi_pct'] : 0.0,
                    'required_pct' => is_numeric($c['required_pct'] ?? null) ? (float)$c['required_pct'] : 80.0,
                    'gap_pct' => $gapPct,
                    'plans_by_status' => ($cid > 0 && isset($plansByCriteriaId[$cid])) ? $plansByCriteriaId[$cid] : new stdClass(),
                ];
            }

            echo json_encode(['success' => true, 'status_filter' => $employeeStatusFilter, 'data' => $out], JSON_UNESCAPED_SLASHES);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.'], JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error.'], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$successMessage = '';
$errorMessage = '';

if ($idpId > 0 && !$existingIdp) {
    http_response_code(404);
    $errorMessage = 'IDP record not found.';
}

if ($idpId > 0 && $existingIdp && !$viewOnly) {
    $st = (string)($existingIdp['idp_status'] ?? '');
    if (!in_array($st, ['under_review', 'approved'], true)) {
        $viewOnly = true;
        if ($errorMessage === '') {
            $errorMessage = 'This IDP cannot be edited in its current status.';
        }
    }
}

if ($employeeId === '' && $errorMessage === '') {
    http_response_code(400);
    $errorMessage = 'Missing employee_id.';
}

if ($errorMessage === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && !$viewOnly) {
    $submitAction = trim((string)($_POST['submit_action'] ?? 'save'));
    $developmentPlan = trim((string)($_POST['development_plan'] ?? ''));
    $targetScoreRaw = trim((string)($_POST['target_score'] ?? ''));
    $targetDateRaw = trim((string)($_POST['target_date'] ?? ''));

    $succTargetRole = trim((string)($_POST['succ_target_role'] ?? ''));
    $succReadinessLevel = trim((string)($_POST['succ_readiness_level'] ?? ''));
    $succLeadershipFocus = trim((string)($_POST['succ_leadership_focus'] ?? ''));
    $succStretchAssignments = trim((string)($_POST['succ_stretch_assignments'] ?? ''));
    $succCoachingPlan = trim((string)($_POST['succ_coaching_plan'] ?? ''));
    $succAssessmentPlan = trim((string)($_POST['succ_assessment_plan'] ?? ''));
    $succFinalOutcome = trim((string)($_POST['succ_final_outcome'] ?? ''));

    $trainingType = trim((string)($_POST['training_type'] ?? ''));
    $trainingMode = trim((string)($_POST['training_mode'] ?? ''));
    $idpDeliveryMode = trim((string)($_POST['idp_delivery_mode'] ?? ''));
    $trainingStartDate = trim((string)($_POST['training_start_date'] ?? ''));
    $trainingStartTime = trim((string)($_POST['training_start_time'] ?? ''));
    $trainingEndDate = trim((string)($_POST['training_end_date'] ?? ''));
    $trainingEndTime = trim((string)($_POST['training_end_time'] ?? ''));

    if ($idpDeliveryMode === '') {
        $idpDeliveryMode = $trainingMode;
    }
    if ($idpDeliveryMode !== 'Online' && $idpDeliveryMode !== 'Onsite' && $idpDeliveryMode !== 'Hybrid') {
        $idpDeliveryMode = 'Onsite';
    }

    $requestedStartStr = null;
    $requestedEndStr = null;
    if ($trainingStartDate !== '' && $trainingStartTime !== '' && $trainingEndDate !== '' && $trainingEndTime !== '') {
        $tmpStart = DateTime::createFromFormat('Y-m-d H:i', $trainingStartDate . ' ' . $trainingStartTime);
        $tmpEnd = DateTime::createFromFormat('Y-m-d H:i', $trainingEndDate . ' ' . $trainingEndTime);
        if ($tmpStart && $tmpEnd) {
            $requestedStartStr = $tmpStart->format('Y-m-d H:i:s');
            $requestedEndStr = $tmpEnd->format('Y-m-d H:i:s');
        }
    }

    $targetScore = null;
    if ($targetScoreRaw !== '' && is_numeric($targetScoreRaw)) {
        $targetScore = (float)$targetScoreRaw;
    }

    $targetDate = null;
    if ($targetDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDateRaw)) {
        $targetDate = $targetDateRaw;
    }

    $autoReadiness = mapTargetDateToReadiness($targetDate);
    $hasActivePlan = $autoReadiness !== 'Ready Now';

    if ($succReadinessLevel === '') {
        $succReadinessLevel = $autoReadiness;
    } elseif ($hasActivePlan && $succReadinessLevel === 'Ready Now') {
        $succReadinessLevel = $autoReadiness;
    }

    if ($isSuccessionReady && $succTargetRole === '') {
        $errorMessage = 'Target role is required.';
    }

    $succMetaLines = [];
    if ($succTargetRole !== '') {
        $succMetaLines[] = 'Target Succession Role: ' . $succTargetRole;
    }
    if ($succReadinessLevel !== '') {
        $succMetaLines[] = 'Readiness Level: ' . $succReadinessLevel;
    }
    if ($succFinalOutcome !== '') {
        $succMetaLines[] = 'Final Succession Validation Outcome: ' . $succFinalOutcome;
    }
    if ($succLeadershipFocus !== '') {
        $succMetaLines[] = 'Leadership & Strategic Competencies: ' . $succLeadershipFocus;
    }
    if ($succStretchAssignments !== '') {
        $succMetaLines[] = 'Stretch Assignments & Exposure: ' . $succStretchAssignments;
    }
    if ($succCoachingPlan !== '') {
        $succMetaLines[] = 'Coaching & Knowledge Transfer: ' . $succCoachingPlan;
    }
    if ($succAssessmentPlan !== '') {
        $succMetaLines[] = 'Readiness Assessment & Evaluation: ' . $succAssessmentPlan;
    }

    if (count($succMetaLines) > 0) {
        $metaBlock = implode("\n", $succMetaLines);
        if ($developmentPlan !== '') {
            $developmentPlan = $metaBlock . "\n\n" . $developmentPlan;
        } else {
            $developmentPlan = $metaBlock;
        }
    }

    if ($developmentPlan === '') {
        try {
            if (function_exists('computeEmployeeKpiAnalysis')) {
                $kpiAnalysisTmp = computeEmployeeKpiAnalysis($employeeId, $period);
                $kpiComputedTmp = is_array($kpiAnalysisTmp['computed'] ?? null) ? $kpiAnalysisTmp['computed'] : [];
                foreach ($kpiComputedTmp as $k) {
                    $gap = is_numeric($k['gap_pct'] ?? null) ? (float)$k['gap_pct'] : 0.0;
                    if ($gap > 0.0) {
                        $errorMessage = 'Development plans are required for KPI areas below standard.';
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
        }
    }

    if ($errorMessage === '') {
        try {
            $stmt = $pdo->prepare(
                "UPDATE succession_submissions
             SET development_plan = ?,
                 target_score = ?,
                 target_date = IFNULL(?, target_date),
                 idp_status = 'Created',
                 idp_created_at = CURRENT_TIMESTAMP
             WHERE employee_id = ?"
            );
            $stmt->execute([$developmentPlan, $targetScore, $targetDate, $employeeId]);

            try {
                $pdo->prepare(
                    "UPDATE requested_to_idp
                 SET status = 'Created',
                     updated_at = CURRENT_TIMESTAMP
                 WHERE employee_id = ?"
                )->execute([$employeeId]);
            } catch (Throwable $ignored) {
            }

            $stmt2 = $pdo->prepare(
                "SELECT ss.employee_id,
                    ss.employee_name,
                    ss.position,
                    ss.department,
                    COALESCE(gs.competency, 0) AS competency,
                    CASE
                        WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                        WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                        WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                        WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                        ELSE 'Succession Ready'
                    END AS status
             FROM succession_submissions ss
             LEFT JOIN (
                 SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
                 FROM employee_kpi_scores s2
                 WHERE s2.evaluation_period = ?
                 GROUP BY s2.employee_id
             ) gs ON gs.employee_id = ss.employee_id
             WHERE ss.employee_id = ?"
            );
            seedMissingKpiEvaluations($employeeId, $period);
            $stmt2->execute([$period, $employeeId]);
            $src = $stmt2->fetch();

            if ($src) {
                $ins = $pdo->prepare(
                    "INSERT INTO individual_development_plans
                     (employee_id, employee_name, position, department, competency, succession_status,
                      development_plan, target_score, target_date, delivery_mode, idp_status)
                 VALUES
                     (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'under_review')
                 ON DUPLICATE KEY UPDATE
                     employee_name = VALUES(employee_name),
                     position = VALUES(position),
                     department = VALUES(department),
                     competency = VALUES(competency),
                     succession_status = VALUES(succession_status),
                     development_plan = VALUES(development_plan),
                     target_score = VALUES(target_score),
                     target_date = IFNULL(VALUES(target_date), target_date),
                     delivery_mode = VALUES(delivery_mode),
                     idp_status = CASE WHEN individual_development_plans.idp_status = 'requested' THEN 'requested' ELSE 'under_review' END"
                );
                $ins->execute([
                    $src['employee_id'],
                    $src['employee_name'],
                    $src['position'],
                    $src['department'],
                    $src['competency'],
                    $src['status'],
                    $developmentPlan,
                    $targetScore,
                    $targetDate,
                    $idpDeliveryMode,
                ]);

                $updReq = $pdo->prepare(
                    "UPDATE individual_development_plans
                 SET requested_training_type = ?,
                     requested_training_mode = ?,
                     requested_start_datetime = ?,
                     requested_end_datetime = ?
                 WHERE employee_id = ?"
                );
                $updReq->execute([
                    $trainingType !== '' ? $trainingType : null,
                    $trainingMode !== '' ? $trainingMode : null,
                    $requestedStartStr,
                    $requestedEndStr,
                    $employeeId
                ]);
            }

            if ($src && (string)($src['status'] ?? '') === 'Succession Ready') {
                try {
                    $stmtPre = $pdo->prepare(
                        "INSERT INTO pre_promotion_employees (employee_id, name, competency_level)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        competency_level = VALUES(competency_level)"
                    );
                    $stmtPre->execute([
                        (string)$src['employee_id'],
                        (string)$src['employee_name'],
                        (string)$src['status'],
                    ]);
                } catch (Throwable $e) {
                    error_log('pre_promotion insert error: ' . $e->getMessage());
                }
            }

            if ($submitAction === 'request_training') {
                if ($idpDeliveryMode === 'Online') {
                    try {
                        $pdo->beginTransaction();

                        $stmtFetch = $pdo->prepare(
                            "SELECT *
                         FROM individual_development_plans
                         WHERE employee_id = ?
                         LIMIT 1
                         FOR UPDATE"
                        );
                        $stmtFetch->execute([$employeeId]);
                        $idpRow = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                        if (!$idpRow) {
                            $pdo->rollBack();
                            $errorMessage = 'IDP record not found.';
                        } else {
                            $now = (new DateTime())->format('Y-m-d H:i:s');

                            $stmtInsert = $pdo->prepare(
                                "INSERT INTO requested_idps_repository
                                (id, employee_id, employee_name, position, department, competency, succession_status,
                                 development_plan, target_score, target_date, delivery_mode,
                                 requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                                 idp_status, training_requested_at, learning_requested_at, created_at, updated_at)
                             VALUES
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                                employee_name = VALUES(employee_name),
                                position = VALUES(position),
                                department = VALUES(department),
                                competency = VALUES(competency),
                                succession_status = VALUES(succession_status),
                                development_plan = VALUES(development_plan),
                                target_score = VALUES(target_score),
                                target_date = VALUES(target_date),
                                delivery_mode = VALUES(delivery_mode),
                                requested_training_type = VALUES(requested_training_type),
                                requested_training_mode = VALUES(requested_training_mode),
                                requested_start_datetime = VALUES(requested_start_datetime),
                                requested_end_datetime = VALUES(requested_end_datetime),
                                idp_status = 'requested',
                                training_requested_at = VALUES(training_requested_at),
                                learning_requested_at = VALUES(learning_requested_at),
                                updated_at = VALUES(updated_at)"
                            );
                            $stmtInsert->execute([
                                (int)$idpRow['id'],
                                $idpRow['employee_id'],
                                $idpRow['employee_name'],
                                $idpRow['position'],
                                $idpRow['department'],
                                $idpRow['competency'],
                                $idpRow['succession_status'],
                                $idpRow['development_plan'],
                                $idpRow['target_score'],
                                $idpRow['target_date'],
                                $idpRow['delivery_mode'],
                                $idpRow['requested_training_type'],
                                $idpRow['requested_training_mode'],
                                $idpRow['requested_start_datetime'],
                                $idpRow['requested_end_datetime'],
                                $idpRow['training_requested_at'],
                                $now,
                                $idpRow['created_at'],
                                $now,
                            ]);

                            $stmtUpd = $pdo->prepare(
                                "UPDATE individual_development_plans
                                 SET idp_status = 'requested',
                                     training_requested_at = ?,
                                     learning_requested_at = ?,
                                     updated_at = ?
                                 WHERE employee_id = ?"
                            );
                            $stmtUpd->execute([$idpRow['training_requested_at'], $now, $now, $employeeId]);
                            $pdo->commit();

                            header('Location: individual_development_plans.php?ok=learning_requested');
                            exit;
                        }
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('learning request move error: ' . $e->getMessage());
                        $errorMessage = 'Failed to request learning.';
                    }
                }

                if ($trainingType === '' || $trainingMode === '' || $trainingStartDate === '' || $trainingStartTime === '' || $trainingEndDate === '' || $trainingEndTime === '') {
                    $errorMessage = 'Please complete the Training Request fields before requesting training.';
                } else {
                    $startDT = DateTime::createFromFormat('Y-m-d H:i', $trainingStartDate . ' ' . $trainingStartTime);
                    $endDT = DateTime::createFromFormat('Y-m-d H:i', $trainingEndDate . ' ' . $trainingEndTime);

                    if (!$startDT || !$endDT) {
                        $errorMessage = 'Invalid training schedule.';
                    } elseif ($endDT <= $startDT) {
                        $errorMessage = 'End date/time must be later than start date/time.';
                    } else {
                        try {
                            require_once __DIR__ . '/../../TRAINING/TRAINING/db.php';

                            $employeeNameForTitle = (string)($src['employee_name'] ?? ($employeeId !== '' ? $employeeId : ''));
                            $trainingTitle = 'IDP Training Request - ' . $employeeNameForTitle;

                            $desc = trim((string)$developmentPlan);
                            if ($desc === '') {
                                $desc = 'IDP Training Request';
                            }

                            $targetAudience = 'Specific Employee';
                            $category = 'IDP';
                            $participantsNeeded = 1;
                            $status = 'Under Review';
                            $needBudget = 0;
                            $needItems = 0;
                            $needFacility = 0;

                            $stmtT = $conn->prepare(
                                "INSERT INTO training_programs
                                (training_title, training_type, training_mode, description, target_audience, category, participants_needed, start_datetime, end_datetime, status, need_budget, need_items, need_facility)
                             VALUES
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                            );

                            $startStr = $startDT->format('Y-m-d H:i:s');
                            $endStr = $endDT->format('Y-m-d H:i:s');

                            $stmtT->bind_param(
                                'ssssssisssiii',
                                $trainingTitle,
                                $trainingType,
                                $trainingMode,
                                $desc,
                                $targetAudience,
                                $category,
                                $participantsNeeded,
                                $startStr,
                                $endStr,
                                $status,
                                $needBudget,
                                $needItems,
                                $needFacility
                            );
                            $stmtT->execute();

                            try {
                                $pdo->beginTransaction();

                                $stmtFetch = $pdo->prepare(
                                    "SELECT *
                                 FROM individual_development_plans
                                 WHERE employee_id = ?
                                 LIMIT 1
                                 FOR UPDATE"
                                );
                                $stmtFetch->execute([$employeeId]);
                                $idpRow = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                                if (!$idpRow) {
                                    $pdo->rollBack();
                                    $errorMessage = 'IDP record not found.';
                                } else {
                                    $now = (new DateTime())->format('Y-m-d H:i:s');
                                    $learningRequestedAt = $idpRow['learning_requested_at'];
                                    $trainingRequestedAt = $now;

                                    if ($idpDeliveryMode === 'Hybrid') {
                                        $learningRequestedAt = $now;
                                    }

                                    $stmtInsert = $pdo->prepare(
                                        "INSERT INTO requested_idps_repository
                                        (id, employee_id, employee_name, position, department, competency, succession_status,
                                         development_plan, target_score, target_date, delivery_mode,
                                         requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                                         idp_status, training_requested_at, learning_requested_at, created_at, updated_at)
                                     VALUES
                                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, ?)
                                     ON DUPLICATE KEY UPDATE
                                        employee_name = VALUES(employee_name),
                                        position = VALUES(position),
                                        department = VALUES(department),
                                        competency = VALUES(competency),
                                        succession_status = VALUES(succession_status),
                                        development_plan = VALUES(development_plan),
                                        target_score = VALUES(target_score),
                                        target_date = VALUES(target_date),
                                        delivery_mode = VALUES(delivery_mode),
                                        requested_training_type = VALUES(requested_training_type),
                                        requested_training_mode = VALUES(requested_training_mode),
                                        requested_start_datetime = VALUES(requested_start_datetime),
                                        requested_end_datetime = VALUES(requested_end_datetime),
                                        idp_status = 'requested',
                                        training_requested_at = VALUES(training_requested_at),
                                        learning_requested_at = VALUES(learning_requested_at),
                                        updated_at = VALUES(updated_at)"
                                    );
                                    $stmtInsert->execute([
                                        (int)$idpRow['id'],
                                        $idpRow['employee_id'],
                                        $idpRow['employee_name'],
                                        $idpRow['position'],
                                        $idpRow['department'],
                                        $idpRow['competency'],
                                        $idpRow['succession_status'],
                                        $idpRow['development_plan'],
                                        $idpRow['target_score'],
                                        $idpRow['target_date'],
                                        $idpRow['delivery_mode'],
                                        $idpRow['requested_training_type'],
                                        $idpRow['requested_training_mode'],
                                        $idpRow['requested_start_datetime'],
                                        $idpRow['requested_end_datetime'],
                                        $trainingRequestedAt,
                                        $learningRequestedAt,
                                        $idpRow['created_at'],
                                        $now,
                                    ]);

                                    $stmtUpd = $pdo->prepare(
                                        "UPDATE individual_development_plans
                                         SET idp_status = 'requested',
                                             training_requested_at = ?,
                                             learning_requested_at = ?,
                                             updated_at = ?
                                         WHERE employee_id = ?"
                                    );
                                    $stmtUpd->execute([$trainingRequestedAt, $learningRequestedAt, $now, $employeeId]);
                                    $pdo->commit();

                                    header('Location: individual_development_plans.php?ok=training_requested');
                                    exit;
                                }
                            } catch (Throwable $e) {
                                if ($pdo->inTransaction()) {
                                    $pdo->rollBack();
                                }
                                error_log('training request move error: ' . $e->getMessage());
                                $errorMessage = 'Failed to create training request.';
                            }
                        } catch (Throwable $e) {
                            error_log('training request insert error: ' . $e->getMessage());
                            $errorMessage = 'Failed to create training request.';
                        }
                    }
                }
            }

            if ($errorMessage === '') {
                header('Location: individual_development_plans.php?ok=created');
                exit;
            }
        } catch (Throwable $e) {
            error_log('individual_dev_plan save error: ' . $e->getMessage());
            $errorMessage = 'Failed to save IDP.';
        }
    }
}

$row = null;
if ($employeeId !== '') {
    $stmt = $pdo->prepare(
        "SELECT ss.id,
                ss.employee_id,
                ss.employee_name,
                ss.position,
                ss.department,
                COALESCE(gs.competency, 0) AS competency,
                CASE
                    WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                    WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                    WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                    WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                    ELSE 'Succession Ready'
                END AS status,
                ss.development_plan,
                ss.target_score,
                ss.target_date,
                ss.idp_status,
                ss.idp_created_at,
                ss.created_at
         FROM succession_submissions ss
         LEFT JOIN (
             SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
             FROM employee_kpi_scores s2
             WHERE s2.evaluation_period = ?
             GROUP BY s2.employee_id
         ) gs ON gs.employee_id = ss.employee_id
         WHERE ss.employee_id = ?"
    );
    seedMissingKpiEvaluations($employeeId, $period);
    $stmt->execute([$period, $employeeId]);
    $row = $stmt->fetch();
}
if (!$row) {
    http_response_code(404);
    $errorMessage = 'Employee record not found in Succession Submissions.';
}

$kpiAnalysis = ['computed' => [], 'overall' => ['avg' => 0.0, 'pct' => 0.0, 'status' => 'Retrain']];
if ($employeeId !== '' && function_exists('computeEmployeeKpiAnalysis')) {
    $kpiAnalysis = computeEmployeeKpiAnalysis($employeeId, $period);
}
$kpiComputed = is_array($kpiAnalysis['computed'] ?? null) ? $kpiAnalysis['computed'] : [];
$kpiOverall = is_array($kpiAnalysis['overall'] ?? null) ? $kpiAnalysis['overall'] : null;

$employeeStatus = (string)($row['status'] ?? '');
$isSuccessionReady = $employeeStatus === 'Succession Ready';
$currentIdpStatusValue = (string)(($existingIdp['idp_status'] ?? null) ?? ($row['idp_status'] ?? ''));

$extractMeta = function (string $plan, string $key): string {
    $plan = (string)$plan;
    $key = preg_quote($key, '/');
    if (preg_match('/^' . $key . '\s*:\s*(.*)$/mi', $plan, $m)) {
        return trim((string)($m[1] ?? ''));
    }
    return '';
};

$prefillPlan = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['development_plan'] ?? '')
    : (string)(($existingIdp['development_plan'] ?? null) ?? ($row['development_plan'] ?? ''));
$succTargetRoleValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_target_role'] ?? '') : $extractMeta($prefillPlan, 'Target Succession Role');
$succReadinessLevelValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_readiness_level'] ?? '') : $extractMeta($prefillPlan, 'Readiness Level');
$succFinalOutcomeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_final_outcome'] ?? '') : $extractMeta($prefillPlan, 'Final Succession Validation Outcome');

$succLeadershipFocusValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_leadership_focus'] ?? '') : $extractMeta($prefillPlan, 'Leadership & Strategic Competencies');
$succStretchAssignmentsValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_stretch_assignments'] ?? '') : $extractMeta($prefillPlan, 'Stretch Assignments & Exposure');
$succCoachingPlanValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_coaching_plan'] ?? '') : $extractMeta($prefillPlan, 'Coaching & Knowledge Transfer');
$succAssessmentPlanValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_assessment_plan'] ?? '') : $extractMeta($prefillPlan, 'Readiness Assessment & Evaluation');

$targetDateValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['target_date'] ?? '')
    : (string)(($existingIdp['target_date'] ?? null) ?? ($row['target_date'] ?? ''));


$suggestedPlans = [];

$trainingTypeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_type'] ?? '') : '';
$succTargetRoleOptions = [];
if ($row && $isSuccessionReady) {
    $deptNameForRole = trim((string)($row['department'] ?? ''));
    $currentDeptRole = trim((string)($row['position'] ?? ''));
    $rolesForDept = [];
    if ($deptNameForRole !== '') {
        try {
            $stmtRoles = $pdo->prepare(
                "SELECT DISTINCT position
                 FROM employees
                 WHERE department = ?
                   AND position IS NOT NULL
                   AND position <> ''
                 ORDER BY position ASC"
            );
            $stmtRoles->execute([$deptNameForRole]);
            $rolesForDept = array_map(
                static fn($r) => trim((string)($r['position'] ?? '')),
                $stmtRoles->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch (Throwable $e) {
            $rolesForDept = [];
        }
    }
    if (empty($rolesForDept) && isset($deptRoles) && is_array($deptRoles[$deptNameForRole] ?? null)) {
        $rolesForDept = array_keys($deptRoles[$deptNameForRole]);
    }
    if (!empty($rolesForDept)) {
        $succTargetRoleOptions = array_values(array_filter($rolesForDept, static function ($r) use ($currentDeptRole) {
            $r = trim((string)$r);
            if ($r === '') return false;
            if ($currentDeptRole !== '' && strcasecmp($r, $currentDeptRole) === 0) return false;
            return true;
        }));
        if (empty($succTargetRoleOptions)) {
            $succTargetRoleOptions = $rolesForDept;
        }
    }
    if (empty($succTargetRoleOptions)) {
        $succTargetRoleOptions = ['Promotion Raise'];
    }
    if ($succTargetRoleValue !== '' && !in_array($succTargetRoleValue, $succTargetRoleOptions, true)) {
        array_unshift($succTargetRoleOptions, $succTargetRoleValue);
    }
}

$trainingModeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_mode'] ?? 'Onsite') : 'Onsite';
$idpDeliveryModeValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['idp_delivery_mode'] ?? $trainingModeValue)
    : (string)(($existingIdp['delivery_mode'] ?? null) ?? $trainingModeValue);
$idpDeliveryModeValue = in_array($idpDeliveryModeValue, ['Online', 'Onsite', 'Hybrid'], true) ? $idpDeliveryModeValue : 'Onsite';
$trainingStartDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_start_date'] ?? '') : '';
$trainingStartDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_start_date'] ?? '') : '';
$trainingStartTimeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_start_time'] ?? '') : '';
$trainingEndDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_end_date'] ?? '') : '';
$trainingEndTimeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_end_time'] ?? '') : '';
require('../../partials/header.php');
?>

<body class="bg-base-200 min-h-screen">

    <div class="flex h-screen">
        <?php include '../../USM/sidebarr.php'; ?>

        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../../USM/navbar.php'; ?>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo $isSuccessionReady ? 'Succession Ready IDP' : 'Individual Development Plan'; ?></h1>
                        <div class="flex items-center gap-3">
                            <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Back to Dashboard</a>
                        </div>
                    </div>

                    <?php if ($errorMessage !== ''): ?>
                        <div class="bg-white border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                            <?php echo h($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($row): ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">Employee</label>
                                    <input type="text" readonly value="<?php echo h($row['employee_name']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">Employee ID</label>
                                    <input type="text" readonly value="<?php echo h($row['employee_id']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">Position</label>
                                    <input type="text" readonly value="<?php echo h($row['position']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">Department</label>
                                    <input type="text" readonly value="<?php echo h($row['department']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">Status</label>
                                    <input type="text" readonly value="<?php echo h($row['status']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500">IDP Status</label>
                                    <input type="text" readonly value="<?php echo h($currentIdpStatusValue); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                                </div>
                            </div>
                        </div>



                        <?php if ($successMessage !== ''): ?>
                            <div class="bg-white border border-emerald-200 text-emerald-700 rounded-lg p-4 mb-6">
                                <?php echo h($successMessage); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm space-y-6">
                            <input type="hidden" name="idp_delivery_mode" id="idp_delivery_mode" value="<?php echo h($idpDeliveryModeValue); ?>" />
                            <input type="hidden" name="employee_status" value="<?php echo h($employeeStatus); ?>" />

                            <?php
                            $overallPct = 0.0;
                            $overallStatus = (string)($row['status'] ?? '');
                            if (is_array($kpiOverall ?? null)) {
                                $overallPct = (float)($kpiOverall['pct'] ?? (float)($row['competency'] ?? 0));
                                $overallStatus = (string)($kpiOverall['status'] ?? $overallStatus);
                            } else {
                                $overallPct = (float)($row['competency'] ?? 0);
                            }
                            ?>
                            <div class="border border-gray-300 rounded-lg p-5 bg-gray-50">
                                <div class="text-sm font-semibold text-gray-900 mb-4">KPI Evaluation Summary</div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Evaluation Period</div>
                                        <div class="mt-1 text-sm text-gray-900"><?php echo h($period); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Overall Competency</div>
                                        <div class="mt-1 text-2xl font-bold text-gray-900"><?php echo number_format($overallPct, 1); ?>%</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Status</div>
                                        <div class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border border-gray-300 bg-gray-50 text-gray-800">
                                            <?php echo h($overallStatus); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($kpiComputed)): ?>
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="table w-full text-sm">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="px-4 py-2 text-left text-gray-700">KPI</th>
                                                    <th class="px-4 py-2 text-right text-gray-700">Actual</th>
                                                    <th class="px-4 py-2 text-right text-gray-700">Required</th>
                                                    <th class="px-4 py-2 text-right text-gray-700">Gap</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <?php foreach ($kpiComputed as $k): ?>
                                                    <?php
                                                    $kpiName = (string)($k['kpi_name'] ?? '');
                                                    $kpiPct = (float)($k['kpi_pct'] ?? 0);
                                                    $reqPct = (float)($k['required_pct'] ?? 0);
                                                    $gapPct = (float)($k['gap_pct'] ?? 0);
                                                    ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 text-gray-900"><?php echo h($kpiName); ?></td>
                                                        <td class="px-4 py-2 text-right font-semibold"><?php echo number_format($kpiPct, 1); ?>%</td>
                                                        <td class="px-4 py-2 text-right font-semibold"><?php echo number_format($reqPct, 1); ?>%</td>
                                                        <td class="px-4 py-2 text-right">
                                                            <?php
                                                            $gapBadgeClass = $gapPct > 0
                                                                ? 'bg-red-50 text-red-700 border border-red-200'
                                                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                                            ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $gapBadgeClass; ?>">
                                                                <?php echo number_format($gapPct, 1); ?>%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($isSuccessionReady): ?>
                                <div class="border border-gray-200 rounded-md p-4">
                                    <div class="text-sm font-semibold text-gray-900 mb-4">Succession Target & Readiness</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-2">Target Role</label>
                                            <select name="succ_target_role" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : 'required'; ?>>
                                                <option value="" <?php echo $succTargetRoleValue === '' ? 'selected' : ''; ?> disabled>Select target role</option>
                                                <?php foreach ($succTargetRoleOptions as $opt): ?>
                                                    <?php $optVal = (string)$opt; ?>
                                                    <option value="<?php echo h($optVal); ?>" <?php echo $succTargetRoleValue === $optVal ? 'selected' : ''; ?>>
                                                        <?php echo h($optVal); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-2">Readiness</label>
                                            <?php
                                            $readinessOptions = ['Ready Now', '3 Months', '6 Months', '12+ Months'];
                                            $currentReadiness = $succReadinessLevelValue !== '' ? $succReadinessLevelValue : 'Ready Now';
                                            ?>
                                            <select name="succ_readiness_level" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                                <?php foreach ($readinessOptions as $opt): ?>
                                                    <option value="<?php echo h($opt); ?>" <?php echo $currentReadiness === $opt ? 'selected' : ''; ?>>
                                                        <?php echo h($opt); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!$viewOnly): ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Strategic Development Activities' : 'Suggested Development Plans'; ?></label>
                                    <div id="suggested_plans" class="space-y-3">
                                        <div class="text-sm text-gray-600">Loading criteria development plans...</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Strategic Development Activity Plan' : 'Development Plan'; ?></label>
                                <div class="w-full border border-gray-200 rounded-md p-3 bg-white mb-2">
                                    <div id="development_plan_bubbles_inner" class="flex flex-wrap gap-2"></div>
                                    <div id="development_plan_bubbles_empty" class="text-xs text-gray-500"><?php echo $isSuccessionReady ? 'No selected activities yet.' : 'No selected trainings yet.'; ?></div>
                                </div>
                                <textarea id="development_plan" name="development_plan" class="hidden" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($prefillPlan); ?></textarea>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <a href="succession_dashboard.php" class="btn btn-outline">Back</a>
                                <?php if (!$viewOnly): ?>
                                    <div class="flex items-center gap-3">

                                        <button type="submit" name="submit_action" value="save" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0">Save IDP</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <script>
                    (function() {
                        var container = document.getElementById('suggested_plans');
                        var planInput = document.getElementById('development_plan');
                        var bubblesInner = document.getElementById('development_plan_bubbles_inner');
                        var bubblesEmpty = document.getElementById('development_plan_bubbles_empty');
                        var deliveryModeHidden = document.getElementById('idp_delivery_mode');
                        var trainingModeSelect = document.querySelector('select[name="training_mode"]');
                        var readinessSelect = document.querySelector('select[name="succ_readiness_level"]');
                        var targetRoleSelect = document.querySelector('select[name="succ_target_role"]');
                        var leadershipTextarea = document.querySelector('textarea[name="succ_leadership_focus"]');
                        var stretchTextarea = document.querySelector('textarea[name="succ_stretch_assignments"]');
                        var coachingTextarea = document.querySelector('textarea[name="succ_coaching_plan"]');
                        var assessmentTextarea = document.querySelector('textarea[name="succ_assessment_plan"]');
                        var finalOutcomeTextarea = document.querySelector('textarea[name="succ_final_outcome"]');
                        if (!planInput || !bubblesInner || !bubblesEmpty) return;

                        var isViewOnly = planInput.hasAttribute('readonly');

                        function escapeHtml(s) {
                            return String(s || '')
                                .replaceAll('&', '&amp;')
                                .replaceAll('<', '&lt;')
                                .replaceAll('>', '&gt;')
                                .replaceAll('"', '&quot;')
                                .replaceAll("'", '&#039;');
                        }

                        function setItemsVisible(skillId, visible) {
                            if (!container) return;
                            var itemsEl = container.querySelector('[data-skill-items="' + skillId + '"]');
                            if (!itemsEl) return;
                            if (visible) {
                                itemsEl.classList.remove('hidden');
                            } else {
                                itemsEl.classList.add('hidden');
                            }
                        }

                        function getTrainingCheckboxes(skillId) {
                            if (!container) return [];
                            return Array.from(container.querySelectorAll('.training-checkbox[data-skill-id="' + skillId + '"]'));
                        }

                        function renderBubbles(items) {
                            bubblesInner.innerHTML = '';
                            items.forEach(function(t) {
                                var el = document.createElement('span');
                                el.className = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border border-gray-200 bg-gray-100 text-gray-800';
                                el.textContent = t;
                                bubblesInner.appendChild(el);
                            });
                            bubblesEmpty.style.display = items.length > 0 ? 'none' : '';
                        }

                        function renderBubblesFromPlanText() {
                            var raw = String(planInput.value || '');
                            var items = raw
                                .split(/\r?\n/)
                                .map(function(l) {
                                    return String(l || '').trim();
                                })
                                .filter(function(l) {
                                    return l.indexOf('- ') === 0;
                                })
                                .map(function(l) {
                                    return l.slice(2).trim();
                                })
                                .filter(function(l) {
                                    return l !== '';
                                });
                            renderBubbles(items);
                        }

                        if (!container) {
                            renderBubblesFromPlanText();
                            return;
                        }

                        async function fetchCriteriaPlans() {
                            if (isViewOnly) return;
                            var employeeId = <?php echo json_encode($employeeId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            var period = <?php echo json_encode($period, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            var employeeStatus = <?php echo json_encode($employeeStatus, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            container.innerHTML = '<div class="text-sm text-gray-600">Loading criteria development plans...</div>';
                            try {
                                var allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
                                employeeStatus = String(employeeStatus || '').trim();
                                if (allowedStatuses.indexOf(employeeStatus) === -1) {
                                    employeeStatus = 'Retrain';
                                }

                                var url = 'individual_dev_plan.php?api=1&action=employee_criteria_plans&employee_id=' + encodeURIComponent(String(employeeId || '')) + '&period=' + encodeURIComponent(String(period || '')) + '&employee_status=' + encodeURIComponent(employeeStatus);
                                var res = await fetch(url);
                                var json = await res.json().catch(function() {
                                    return null;
                                });
                                if (!json || json.success !== true) {
                                    throw new Error((json && json.message) ? json.message : 'Failed to load plans.');
                                }
                                var data = Array.isArray(json.data) ? json.data : [];
                                if (employeeStatus === 'Succession Ready') {
                                    data = data.filter(function(c) {
                                        return Number(c.gap_pct || 0) > 0;
                                    });
                                }
                                if (data.length === 0) {
                                    container.innerHTML = employeeStatus === 'Succession Ready' ?
                                        '<div class="text-sm text-gray-600">No KPI gaps found. Only criteria with gaps can be selected.</div>' :
                                        '<div class="text-sm text-gray-600">No criteria plans available for this employee.</div>';
                                    return;
                                }

                                container.innerHTML = data.map(function(c, idx) {
                                    var cid = parseInt(String(c.criteria_id || '0'), 10);
                                    var skillId = 'c' + (cid > 0 ? String(cid) : String(idx + 1));
                                    var name = String(c.criteria_name || '');
                                    var kpiPct = Number(c.kpi_pct);
                                    var reqPct = Number(c.required_pct);
                                    var gapPct = Number(c.gap_pct);
                                    var meta = (Number.isFinite(kpiPct) && Number.isFinite(reqPct) && Number.isFinite(gapPct)) ?
                                        (' (' + kpiPct.toFixed(1) + '% / ' + reqPct.toFixed(1) + '% • gap ' + gapPct.toFixed(1) + '%)') :
                                        '';

                                    var byStatus = c.plans_by_status || {};
                                    var list = byStatus[employeeStatus];
                                    if (!Array.isArray(list)) list = [];
                                    var plansHtml = list.map(function(p) {
                                        var txt = String(p.plan_text || '').trim();
                                        if (!txt) return '';
                                        var dm = String(p.delivery_mode || 'Onsite');
                                        if (dm !== 'Online' && dm !== 'Onsite') dm = 'Onsite';
                                        return (
                                            '<label class="flex items-center gap-2 text-sm text-gray-800">' +
                                            '<input type="checkbox" class="training-checkbox" data-skill-id="' + escapeHtml(skillId) + '" data-skill-name="' + escapeHtml(name) + '" data-item-text="' + escapeHtml(txt) + '" data-delivery-mode="' + escapeHtml(dm) + '" />' +
                                            '<span>' + escapeHtml(txt) + '</span>' +
                                            '</label>'
                                        );
                                    }).filter(function(x) {
                                        return x !== '';
                                    }).join('');

                                    if (!plansHtml) {
                                        plansHtml = '<div class="text-sm text-gray-600">No ' + escapeHtml(employeeStatus) + ' development plans yet.</div>';
                                    }

                                    return (
                                        '<div class="border border-gray-200 rounded-md p-3">' +
                                        '<label class="flex items-center gap-2 text-sm font-semibold text-gray-900">' +
                                        '<input type="checkbox" class="skill-checkbox" data-skill-id="' + escapeHtml(skillId) + '" data-skill-name="' + escapeHtml(name) + '" />' +
                                        '<span>' + escapeHtml(name) + '</span>' +
                                        '<span class="text-xs text-gray-500">' + escapeHtml(meta) + '</span>' +
                                        '<span class="badge badge-ghost badge-sm ml-auto">' + escapeHtml(employeeStatus) + '</span>' +
                                        '</label>' +
                                        '<div class="mt-2 pl-6 space-y-1 hidden" data-skill-items="' + escapeHtml(skillId) + '">' +
                                        plansHtml +
                                        '</div>' +
                                        '</div>'
                                    );
                                }).join('');
                            } catch (err) {
                                container.innerHTML = '<div class="text-sm text-gray-600">Failed to load development plans.</div>';
                                if (window.Swal && typeof window.Swal.fire === 'function') {
                                    window.Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: String(err && err.message ? err.message : err)
                                    });
                                }
                            }
                        }

                        function buildSuccMetaBlock() {
                            var lines = [];
                            var roleVal = targetRoleSelect ? String(targetRoleSelect.value || '') : '';
                            var readinessVal = readinessSelect ? String(readinessSelect.value || '') : '';
                            var leadershipVal = leadershipTextarea ? String(leadershipTextarea.value || '') : '';
                            var stretchVal = stretchTextarea ? String(stretchTextarea.value || '') : '';
                            var coachingVal = coachingTextarea ? String(coachingTextarea.value || '') : '';
                            var assessmentVal = assessmentTextarea ? String(assessmentTextarea.value || '') : '';
                            var finalOutcomeVal = finalOutcomeTextarea ? String(finalOutcomeTextarea.value || '') : '';

                            if (roleVal !== '') {
                                lines.push('Target Succession Role: ' + roleVal);
                            }
                            if (readinessVal !== '') {
                                lines.push('Readiness Level: ' + readinessVal);
                            }
                            if (finalOutcomeVal !== '') {
                                lines.push('Final Succession Validation Outcome: ' + finalOutcomeVal);
                            }
                            if (leadershipVal !== '') {
                                lines.push('Leadership & Strategic Competencies: ' + leadershipVal);
                            }
                            if (stretchVal !== '') {
                                lines.push('Stretch Assignments & Exposure: ' + stretchVal);
                            }
                            if (coachingVal !== '') {
                                lines.push('Coaching & Knowledge Transfer: ' + coachingVal);
                            }
                            if (assessmentVal !== '') {
                                lines.push('Readiness Assessment & Evaluation: ' + assessmentVal);
                            }

                            return lines.join('\n');
                        }

                        function rebuildTextarea() {
                            if (isViewOnly) return;

                            var lines = [];
                            var bubbles = [];
                            var modes = {};
                            Array.from(container.querySelectorAll('.skill-checkbox')).forEach(function(skillCb) {
                                var skillId = skillCb.getAttribute('data-skill-id') || '';
                                var skillName = skillCb.getAttribute('data-skill-name') || '';
                                if (!skillId || !skillName) return;

                                var selected = getTrainingCheckboxes(skillId).filter(function(cb) {
                                    return cb.checked;
                                });
                                if (selected.length === 0) return;

                                lines.push(skillName + ':');
                                selected.forEach(function(cb) {
                                    var itemText = cb.getAttribute('data-item-text') || '';
                                    var dm = cb.getAttribute('data-delivery-mode') || '';
                                    if (!itemText) return;
                                    lines.push('- ' + itemText);
                                    bubbles.push(itemText);
                                    if (dm === 'Online' || dm === 'Onsite') {
                                        modes[dm] = true;
                                    }
                                });
                                lines.push('');
                            });

                            var meta = buildSuccMetaBlock();
                            var v = meta;
                            if (meta !== '' && lines.length > 0) {
                                v += '\n\n';
                            }
                            v += lines.join('\n').trim();
                            planInput.value = v;
                            renderBubbles(bubbles);

                            var keys = Object.keys(modes);
                            var selectedMode = '';
                            if (keys.length === 1) {
                                selectedMode = keys[0];
                            } else if (keys.length > 1) {
                                selectedMode = 'Hybrid';
                            }
                            if (selectedMode !== '') {
                                if (trainingModeSelect) trainingModeSelect.value = selectedMode;
                                if (deliveryModeHidden) deliveryModeHidden.value = selectedMode;
                            }
                        }

                        container.addEventListener('change', function(e) {
                            var t = e.target;
                            if (!t || t.tagName !== 'INPUT') return;

                            if (t.classList.contains('skill-checkbox')) {
                                var skillId = t.getAttribute('data-skill-id') || '';
                                var visible = !!t.checked;

                                setItemsVisible(skillId, visible);
                                if (!visible) {
                                    getTrainingCheckboxes(skillId).forEach(function(cb) {
                                        cb.checked = false;
                                    });
                                }

                                rebuildTextarea();
                                return;
                            }

                            if (t.classList.contains('training-checkbox')) {
                                var skillId2 = t.getAttribute('data-skill-id') || '';
                                var skillCb = container.querySelector('.skill-checkbox[data-skill-id="' + skillId2 + '"]');
                                if (skillCb && t.checked) {
                                    skillCb.checked = true;
                                    setItemsVisible(skillId2, true);
                                }
                                rebuildTextarea();
                            }
                        });

                        [targetRoleSelect, leadershipTextarea, stretchTextarea, coachingTextarea, assessmentTextarea, finalOutcomeTextarea, readinessSelect].forEach(function(el) {
                            if (!el || isViewOnly) return;
                            var ev = el.tagName === 'SELECT' ? 'change' : 'input';
                            el.addEventListener(ev, function() {
                                rebuildTextarea();
                            });
                        });

                        fetchCriteriaPlans();
                        renderBubblesFromPlanText();
                    })();
                </script>

                <script>
                    (function() {
                        var form = document.querySelector('form[method="post"]');
                        if (!form) return;

                        var deliveryModeHidden = document.getElementById('idp_delivery_mode');

                        var reqFields = [
                            'training_type',
                            'training_mode',
                            'training_start_date',
                            'training_start_time',
                            'training_end_date',
                            'training_end_time'
                        ];

                        function setTrainingRequired(on) {
                            reqFields.forEach(function(name) {
                                var el = form.querySelector('[name="' + name + '"]');
                                if (!el) return;
                                if (on) {
                                    el.setAttribute('required', 'required');
                                } else {
                                    el.removeAttribute('required');
                                }
                            });
                        }

                        form.addEventListener('submit', function(e) {
                            var submitter = e.submitter;
                            var action = submitter ? String(submitter.value || '') : '';
                            var dm = deliveryModeHidden ? String(deliveryModeHidden.value || '') : '';
                            setTrainingRequired(action === 'request_training' && dm !== 'Online');
                        });
                    })();
                </script>
        </div>
        </main>
    </div>
    <?php require('../../partials/footer.php') ?>