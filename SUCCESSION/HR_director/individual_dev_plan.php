<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';
require_once __DIR__ . '/development_plans.php';

// jjj
if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$employeeId = trim((string)($_GET['employee_id'] ?? ''));
$viewOnly = (string)($_GET['view'] ?? '') === '1';

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

$successMessage = '';
$errorMessage = '';

if ($employeeId === '') {
    http_response_code(400);
    $errorMessage = 'Missing employee_id.';
}

if ($errorMessage === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && !$viewOnly) {
    $submitAction = trim((string)($_POST['submit_action'] ?? 'save'));
    $developmentPlan = trim((string)($_POST['development_plan'] ?? ''));
    $targetScoreRaw = trim((string)($_POST['target_score'] ?? ''));

    $succTargetRole = trim((string)($_POST['succ_target_role'] ?? ''));
    $succReadinessLevel = trim((string)($_POST['succ_readiness_level'] ?? ''));
    $targetDateRaw = trim((string)($_POST['target_date'] ?? ''));
    $succLeadershipFocus = trim((string)($_POST['succ_leadership_focus'] ?? ''));
    $succStretchAssignments = trim((string)($_POST['succ_stretch_assignments'] ?? ''));
    $succMentorCoach = trim((string)($_POST['succ_mentor_coach'] ?? ''));
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

    $isSuccessionReadyPost = ((string)($_POST['employee_status'] ?? '')) === 'Succession Ready';
    if ($isSuccessionReadyPost) {
        $allowedReadiness = ['Ready Now', 'Ready in 6 Months', 'Ready in 12 Months'];
        if ($succTargetRole === '') {
            $errorMessage = 'Please provide the Target Succession Role.';
        } elseif (!in_array($succReadinessLevel, $allowedReadiness, true)) {
            $errorMessage = 'Please select a valid Readiness Level.';
        } elseif ($targetDate === null) {
            $errorMessage = 'Please provide the Expected Transition Timeline.';
        }

        if ($errorMessage === '') {
            $norm = function (string $v): string {
                $v = trim($v);
                if ($v === '') return '';
                $v = str_replace(["\r\n", "\r", "\n"], ' | ', $v);
                $v = preg_replace('/\s+/', ' ', $v);
                return trim((string)$v);
            };

            $metaLines = [];
            $metaLines[] = '[Succession Ready IDP]';
            $metaLines[] = 'Target Succession Role: ' . $succTargetRole;
            $metaLines[] = 'Readiness Level: ' . $succReadinessLevel;
            $metaLines[] = 'Expected Transition Timeline: ' . ($targetDate ?? '');
            if ($succMentorCoach !== '') {
                $metaLines[] = 'Assigned Mentor/Coach: ' . $succMentorCoach;
            }
            if ($succFinalOutcome !== '') {
                $metaLines[] = 'Final Succession Validation Outcome: ' . $succFinalOutcome;
            }
            if ($succLeadershipFocus !== '') {
                $metaLines[] = 'Leadership & Strategic Competencies: ' . $norm($succLeadershipFocus);
            }
            if ($succStretchAssignments !== '') {
                $metaLines[] = 'Stretch Assignments & Exposure: ' . $norm($succStretchAssignments);
            }
            if ($succCoachingPlan !== '') {
                $metaLines[] = 'Coaching & Knowledge Transfer: ' . $norm($succCoachingPlan);
            }
            if ($succAssessmentPlan !== '') {
                $metaLines[] = 'Readiness Assessment & Evaluation: ' . $norm($succAssessmentPlan);
            }
            $metaLines[] = '';

            $developmentPlan = implode("\n", $metaLines) . trim((string)$developmentPlan);
        }
    }

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
                     idp_status = CASE WHEN individual_development_plans.idp_status IN ('requested','approved') THEN individual_development_plans.idp_status ELSE 'under_review' END"
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
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, ?)"
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

                        $pdo->prepare("DELETE FROM individual_development_plans WHERE employee_id = ?")->execute([$employeeId]);
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
                                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, ?)"
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

                                $pdo->prepare("DELETE FROM individual_development_plans WHERE employee_id = ?")->execute([$employeeId]);
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

$employeeStatus = (string)($row['status'] ?? '');
$isSuccessionReady = $employeeStatus === 'Succession Ready';

$extractMeta = function (string $plan, string $key): string {
    $plan = (string)$plan;
    $key = preg_quote($key, '/');
    if (preg_match('/^' . $key . '\s*:\s*(.*)$/mi', $plan, $m)) {
        return trim((string)($m[1] ?? ''));
    }
    return '';
};

$prefillPlan = (string)($row['development_plan'] ?? '');
$succTargetRoleValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_target_role'] ?? '') : $extractMeta($prefillPlan, 'Target Succession Role');
$succReadinessLevelValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_readiness_level'] ?? '') : $extractMeta($prefillPlan, 'Readiness Level');
$succMentorCoachValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_mentor_coach'] ?? '') : $extractMeta($prefillPlan, 'Assigned Mentor/Coach');
$succFinalOutcomeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_final_outcome'] ?? '') : $extractMeta($prefillPlan, 'Final Succession Validation Outcome');

$succLeadershipFocusValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_leadership_focus'] ?? '') : $extractMeta($prefillPlan, 'Leadership & Strategic Competencies');
$succStretchAssignmentsValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_stretch_assignments'] ?? '') : $extractMeta($prefillPlan, 'Stretch Assignments & Exposure');
$succCoachingPlanValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_coaching_plan'] ?? '') : $extractMeta($prefillPlan, 'Coaching & Knowledge Transfer');
$succAssessmentPlanValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['succ_assessment_plan'] ?? '') : $extractMeta($prefillPlan, 'Readiness Assessment & Evaluation');

$targetDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['target_date'] ?? '') : (string)($row['target_date'] ?? '');

$generalSkillsBreakdown = [];
if ($row) {
    try {
        $stmtSkills = $pdo->prepare(
            "SELECT k.kpi_name AS skill_name,
                    AVG(COALESCE(s.score, 0)) / 5 * 100 AS skill_score,
                    NULL AS assessment_date
             FROM employee_kpi_scores s
             JOIN kpis k
               ON k.id = s.kpi_id
             WHERE s.employee_id = ?
               AND s.evaluation_period = ?
             GROUP BY k.kpi_name
             ORDER BY k.kpi_name ASC"
        );
        seedMissingKpiEvaluations((string)($row['employee_id'] ?? ''), $period);
        $stmtSkills->execute([(string)($row['employee_id'] ?? ''), $period]);
        $generalSkillsBreakdown = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $generalSkillsBreakdown = [];
    }
}

$suggestedPlans = [];
if ($row) {
    $suggestedPlans = getSuggestedPlansForDepartmentStatus(
        (string)($row['department'] ?? ''),
        (string)($row['status'] ?? ''),
        (string)($row['position'] ?? '')
    );
}

$trainingTypeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_type'] ?? '') : '';
$trainingModeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_mode'] ?? 'Onsite') : 'Onsite';
$idpDeliveryModeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['idp_delivery_mode'] ?? $trainingModeValue) : $trainingModeValue;
$idpDeliveryModeValue = in_array($idpDeliveryModeValue, ['Online','Onsite','Hybrid'], true) ? $idpDeliveryModeValue : 'Onsite';
$trainingStartDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_start_date'] ?? '') : '';
$trainingStartTimeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_start_time'] ?? '') : '';
$trainingEndDateValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_end_date'] ?? '') : '';
$trainingEndTimeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['training_end_time'] ?? '') : '';
require('../../partials/header.php');
?>

<body class="bg-gray-50 min-h-screen">

  <div class="flex h-screen">
   
    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>

      <main class="flex-1 overflow-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><?php echo $isSuccessionReady ? 'Succession Ready IDP' : 'Individual Development Plan'; ?></h1>
                <div class="flex items-center gap-4">
                
                    <a href="succession_dashboard.php" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Back to Dashboard</a>
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
                        <label class="block text-xs font-semibold text-gray-500">General Skills %</label>
                        <input type="text" readonly value="<?php echo number_format((float)$row['competency'], 1); ?>%" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500">Status</label>
                        <input type="text" readonly value="<?php echo h($row['status']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500">IDP Status</label>
                        <input type="text" readonly value="<?php echo h($row['idp_status']); ?>" class="mt-1 w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-50 text-gray-900" />
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-900">General Skills Breakdown</div>
                    <div class="text-xs text-gray-500">Basis (Critical Roles)</div>
                </div>
                <div class="mt-3 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 border-b">
                                <th class="py-2 pr-3">Skill</th>
                                <th class="py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($generalSkillsBreakdown)): ?>
                                <tr><td colspan="2" class="py-3 text-gray-500">No skills found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($generalSkillsBreakdown as $gs): ?>
                                    <tr class="border-b">
                                        <td class="py-2 pr-3"><?php echo h($gs['skill_name'] ?? ''); ?></td>
                                        <td class="py-2 text-right"><?php echo number_format((float)($gs['skill_score'] ?? 0), 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="bg-white border border-emerald-200 text-emerald-700 rounded-lg p-4 mb-6">
                    <?php echo h($successMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <input type="hidden" name="idp_delivery_mode" id="idp_delivery_mode" value="<?php echo h($idpDeliveryModeValue); ?>" />
                <input type="hidden" name="employee_status" value="<?php echo h($employeeStatus); ?>" />

                <?php if ($isSuccessionReady): ?>
                    <div class="mb-6 border border-gray-200 rounded-md p-4">
                        <div class="text-sm font-semibold text-gray-900 mb-4">Target Role Alignment</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Target Succession Role</label>
                                <input type="text" name="succ_target_role" value="<?php echo h($succTargetRoleValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Readiness Level</label>
                                <select name="succ_readiness_level" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                    <option value="" disabled <?php echo $succReadinessLevelValue === '' ? 'selected' : ''; ?>>Select readiness</option>
                                    <option value="Ready Now" <?php echo $succReadinessLevelValue === 'Ready Now' ? 'selected' : ''; ?>>Ready Now</option>
                                    <option value="Ready in 6 Months" <?php echo $succReadinessLevelValue === 'Ready in 6 Months' ? 'selected' : ''; ?>>Ready in 6 Months</option>
                                    <option value="Ready in 12 Months" <?php echo $succReadinessLevelValue === 'Ready in 12 Months' ? 'selected' : ''; ?>>Ready in 12 Months</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Expected Transition Timeline</label>
                                <input type="date" name="target_date" value="<?php echo h($targetDateValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Assigned Mentor or Coach</label>
                                <input type="text" name="succ_mentor_coach" value="<?php echo h($succMentorCoachValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Leadership &amp; Strategic Competencies</label>
                                <textarea name="succ_leadership_focus" rows="3" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($succLeadershipFocusValue); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Stretch Assignments &amp; Exposure (OIC, project leadership, strategic meetings)</label>
                                <textarea name="succ_stretch_assignments" rows="3" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($succStretchAssignmentsValue); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Coaching &amp; Knowledge Transfer Plan</label>
                                <textarea name="succ_coaching_plan" rows="3" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($succCoachingPlanValue); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Readiness Assessment &amp; Validation</label>
                                <textarea name="succ_assessment_plan" rows="3" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($succAssessmentPlanValue); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Final Succession Validation Outcome</label>
                                <select name="succ_final_outcome" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                    <option value="" <?php echo $succFinalOutcomeValue === '' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Validated" <?php echo $succFinalOutcomeValue === 'Validated' ? 'selected' : ''; ?>>Validated</option>
                                    <option value="Not Validated" <?php echo $succFinalOutcomeValue === 'Not Validated' ? 'selected' : ''; ?>>Not Validated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (count($suggestedPlans) > 0): ?>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Strategic Development Activities' : 'Suggested Development Plans'; ?></label>
                        <div id="suggested_plans" class="space-y-3">
                            <?php $skillIndex = 0; ?>
                            <?php foreach ($suggestedPlans as $skillName => $planData): ?>
                                <?php
                                    $skillIndex++;
                                    $skillId = 's' . $skillIndex;
                                    $planText = is_array($planData) ? (string)($planData['plan_text'] ?? '') : (string)$planData;
                                    $deliveryMode = is_array($planData) ? (string)($planData['delivery_mode'] ?? 'Onsite') : 'Onsite';
                                    if ($deliveryMode !== 'Online' && $deliveryMode !== 'Onsite') {
                                        $deliveryMode = 'Onsite';
                                    }
                                    $items = function_exists('splitPlanItems') ? splitPlanItems($planText) : [trim((string)$planText)];
                                    $items = array_values(array_filter(array_map('trim', $items), function ($v) { return (string)$v !== ''; }));
                                ?>
                                <div class="border border-gray-200 rounded-md p-3">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                        <input type="checkbox" class="skill-checkbox" data-skill-id="<?php echo h($skillId); ?>" data-skill-name="<?php echo h($skillName); ?>" data-delivery-mode="<?php echo h($deliveryMode); ?>" <?php echo $viewOnly ? 'disabled' : ''; ?> />
                                        <span><?php echo h($skillName); ?></span>
                                    </label>

                                    <div class="mt-2 pl-6 space-y-1 hidden" data-skill-items="<?php echo h($skillId); ?>">
                                        <?php foreach ($items as $it): ?>
                                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                                <input type="checkbox" class="training-checkbox" data-skill-id="<?php echo h($skillId); ?>" data-skill-name="<?php echo h($skillName); ?>" data-item-text="<?php echo h($it); ?>" data-delivery-mode="<?php echo h($deliveryMode); ?>" <?php echo $viewOnly ? 'disabled' : ''; ?> />
                                                <span><?php echo h($it); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Strategic Development Activity Plan' : 'Development Plan'; ?></label>
                    <div class="w-full border border-gray-200 rounded-md p-3 bg-white mb-2">
                        <div id="development_plan_bubbles_inner" class="flex flex-wrap gap-2"></div>
                        <div id="development_plan_bubbles_empty" class="text-xs text-gray-500"><?php echo $isSuccessionReady ? 'No selected activities yet.' : 'No selected trainings yet.'; ?></div>
                    </div>
                    <textarea id="development_plan" name="development_plan" class="hidden" <?php echo $viewOnly ? 'readonly' : ''; ?>><?php echo h($row['development_plan'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Target Score (%)</label>
                        <input type="number" step="0.1" min="0" max="100" name="target_score" value="<?php echo h($row['target_score'] ?? ''); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 mb-6">
                    <div class="text-sm font-semibold text-gray-900 mb-4"><?php echo $isSuccessionReady ? 'Strategic Development Activity Request' : 'Training Request'; ?></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Activity Type' : 'Training Type'; ?></label>
                            <select name="training_type" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                <option value="" <?php echo $trainingTypeValue === '' ? 'selected' : ''; ?> disabled>Select training type</option>
                                <option value="Orientation" <?php echo $trainingTypeValue === 'Orientation' ? 'selected' : ''; ?>>Orientation</option>
                                <option value="Training" <?php echo $trainingTypeValue === 'Training' ? 'selected' : ''; ?>>Training</option>
                                <option value="Seminar" <?php echo $trainingTypeValue === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                                <option value="Workshop" <?php echo $trainingTypeValue === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                                <option value="Refresher" <?php echo $trainingTypeValue === 'Refresher' ? 'selected' : ''; ?>>Refresher</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Activity Mode' : 'Training Mode'; ?></label>
                            <select name="training_mode" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                <option value="Onsite" <?php echo $trainingModeValue === 'Onsite' ? 'selected' : ''; ?>>Onsite</option>
                                <option value="Online" <?php echo $trainingModeValue === 'Online' ? 'selected' : ''; ?>>Online</option>
                                <option value="Hybrid" <?php echo $trainingModeValue === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Start Date / Time</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="date" name="training_start_date" value="<?php echo h($trainingStartDateValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                                        <input type="time" name="training_start_time" value="<?php echo h($trainingStartTimeValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">End Date / Time</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="date" name="training_end_date" value="<?php echo h($trainingEndDateValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                                        <input type="time" name="training_end_time" value="<?php echo h($trainingEndTimeValue); ?>" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'readonly' : ''; ?> />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <a href="succession_dashboard.php" class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold bg-white border border-gray-300 text-gray-800 hover:bg-gray-50">Back</a>
                    <?php if (!$viewOnly): ?>
                        <div class="flex items-center gap-3">
                          
                            <button type="submit" name="submit_action" value="save" class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold bg-gray-900 text-white hover:bg-gray-800">Save IDP</button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            var container = document.getElementById('suggested_plans');
            var planInput = document.getElementById('development_plan');
            var bubblesInner = document.getElementById('development_plan_bubbles_inner');
            var bubblesEmpty = document.getElementById('development_plan_bubbles_empty');
            var deliveryModeHidden = document.getElementById('idp_delivery_mode');
            var trainingModeSelect = document.querySelector('select[name="training_mode"]');
            if (!container || !planInput || !bubblesInner || !bubblesEmpty) return;

            var isViewOnly = planInput.hasAttribute('readonly');

            function setItemsVisible(skillId, visible) {
                var itemsEl = container.querySelector('[data-skill-items="' + skillId + '"]');
                if (!itemsEl) return;
                if (visible) {
                    itemsEl.classList.remove('hidden');
                } else {
                    itemsEl.classList.add('hidden');
                }
            }

            function getTrainingCheckboxes(skillId) {
                return Array.from(container.querySelectorAll('.training-checkbox[data-skill-id="' + skillId + '"]'));
            }

            function renderBubbles(items) {
                bubblesInner.innerHTML = '';
                items.forEach(function (t) {
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
                    .map(function (l) { return String(l || '').trim(); })
                    .filter(function (l) { return l.indexOf('- ') === 0; })
                    .map(function (l) { return l.slice(2).trim(); })
                    .filter(function (l) { return l !== ''; });
                renderBubbles(items);
            }

            function rebuildTextarea() {
                if (isViewOnly) return;

                var lines = [];
                var bubbles = [];
                var modes = {};
                Array.from(container.querySelectorAll('.skill-checkbox')).forEach(function (skillCb) {
                    var skillId = skillCb.getAttribute('data-skill-id') || '';
                    var skillName = skillCb.getAttribute('data-skill-name') || '';
                    if (!skillId || !skillName) return;

                    var selected = getTrainingCheckboxes(skillId).filter(function (cb) { return cb.checked; });
                    if (selected.length === 0) return;

                    lines.push(skillName + ':');
                    selected.forEach(function (cb) {
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

                var v = lines.join('\n').trim();
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

            container.addEventListener('change', function (e) {
                var t = e.target;
                if (!t || t.tagName !== 'INPUT') return;

                if (t.classList.contains('skill-checkbox')) {
                    var skillId = t.getAttribute('data-skill-id') || '';
                    var visible = !!t.checked;

                    setItemsVisible(skillId, visible);
                    if (!visible) {
                        getTrainingCheckboxes(skillId).forEach(function (cb) {
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

            renderBubblesFromPlanText();
        })();
    </script>

    <script>
        (function () {
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
                reqFields.forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (!el) return;
                    if (on) {
                        el.setAttribute('required', 'required');
                    } else {
                        el.removeAttribute('required');
                    }
                });
            }

            form.addEventListener('submit', function (e) {
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
