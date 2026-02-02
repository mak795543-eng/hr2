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
$viewOnlyRequested = (string)($_GET['view'] ?? '') === '1';
$viewOnly = $viewOnlyRequested;

$successMessage = '';
$errorMessage = '';

if ($employeeId === '') {
    http_response_code(400);
    $errorMessage = 'Missing employee_id.';
}

if ($employeeId !== '' && $viewOnlyRequested) {
    try {
        $stmtVO = $pdo->prepare("SELECT idp_status FROM succession_submissions WHERE employee_id = ? LIMIT 1");
        $stmtVO->execute([$employeeId]);
        $voRow = $stmtVO->fetch(PDO::FETCH_ASSOC);
        $viewOnly = $viewOnlyRequested && ((string)($voRow['idp_status'] ?? '') === 'Created');
    } catch (Throwable $ignored) {
        $viewOnly = $viewOnlyRequested;
    }
}

if ($errorMessage === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && !$viewOnly) {
    $submitAction = trim((string)($_POST['submit_action'] ?? 'save'));
    $developmentPlan = trim((string)($_POST['development_plan'] ?? ''));

    $succTargetRole = trim((string)($_POST['succ_target_role'] ?? ''));
    $succReadinessLevel = trim((string)($_POST['succ_readiness_level'] ?? ''));
    $succMentorCoach = trim((string)($_POST['succ_mentor_coach'] ?? ''));

    $idpDeliveryMode = trim((string)($_POST['idp_delivery_mode'] ?? ''));
    if ($idpDeliveryMode !== 'Online' && $idpDeliveryMode !== 'Onsite' && $idpDeliveryMode !== 'Hybrid') {
        $idpDeliveryMode = 'Onsite';
    }

    $targetDate = null;

    $isSuccessionReadyPost = ((string)($_POST['employee_status'] ?? '')) === 'Succession Ready';
    if ($isSuccessionReadyPost) {
        $hasGapForReadiness = false;
        try {
            $hasGapForReadiness = employeeHasGaps($employeeId);
        } catch (Throwable $ignored) {
            $hasGapForReadiness = false;
        }

        $allowedReadiness = $hasGapForReadiness
            ? ['Ready in 6 Months', 'Ready in 12 Months']
            : ['Ready Now'];

        if ($succReadinessLevel === '' && !$hasGapForReadiness) {
            $succReadinessLevel = 'Ready Now';
        }

        $tenureYears = 0;
        try {
            $stmtTenure = $pdo->prepare("SELECT created_at FROM employees WHERE employee_id = ? LIMIT 1");
            $stmtTenure->execute([$employeeId]);
            $createdAt = (string)($stmtTenure->fetchColumn() ?: '');
            if ($createdAt !== '') {
                $ts = strtotime($createdAt);
                if ($ts !== false) {
                    $diffDays = (int)floor((time() - $ts) / 86400);
                    $tenureYears = (int)floor($diffDays / 365);
                }
            }
        } catch (Throwable $ignored) {
            $tenureYears = 0;
        }

        $isHighTargetRole = preg_match('/(manager|director|head|chief|supervisor)/i', $succTargetRole) === 1;

        if ($succTargetRole === '') {
            $errorMessage = 'Please provide the Target Succession Role.';
        } elseif (!in_array($succReadinessLevel, $allowedReadiness, true)) {
            $errorMessage = 'Please select a valid Readiness Level.';
        } elseif ($isHighTargetRole && $tenureYears < 5) {
            $errorMessage = 'For high target roles, employee tenure must be at least 5 years.';
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
            if ($succMentorCoach !== '') {
                $metaLines[] = 'Assigned Mentor/Coach: ' . $succMentorCoach;
            }
            $metaLines[] = '';

            $developmentPlan = implode("\n", $metaLines) . trim((string)$developmentPlan);
        }
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE succession_submissions
             SET development_plan = ?,
                 idp_status = 'Created',
                 idp_created_at = CURRENT_TIMESTAMP
             WHERE employee_id = ?"
        );
        $stmt->execute([$developmentPlan, $employeeId]);

        logAction($employeeId, 'Succession', 'IDP Created/Updated', 'IDP saved from Succession');

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
                    COALESCE(e.competency, ss.competency, 0) AS competency,
                    COALESCE(e.status, ss.status, 'Retrain') AS status
             FROM succession_submissions ss
             LEFT JOIN employees e ON e.employee_id = ss.employee_id
             WHERE ss.employee_id = ?"
        );
        $stmt2->execute([$employeeId]);
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
                null,
                null,
                $idpDeliveryMode,
            ]);

            if ($isSuccessionReadyPost && $errorMessage === '') {
                try {
                    $stmtPre = $pdo->prepare(
                        "INSERT INTO pre_promotion_employees
                             (employee_id, name, department, current_position, competency, succession_status, target_role, readiness_level, expected_transition_date, mentor_coach)
                         VALUES
                             (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                             name = VALUES(name),
                             department = VALUES(department),
                             current_position = VALUES(current_position),
                             competency = VALUES(competency),
                             succession_status = VALUES(succession_status),
                             target_role = VALUES(target_role),
                             readiness_level = VALUES(readiness_level),
                             expected_transition_date = VALUES(expected_transition_date),
                             mentor_coach = VALUES(mentor_coach),
                             promotion_status = CASE WHEN pre_promotion_employees.promotion_status IN ('sent','promoted') THEN pre_promotion_employees.promotion_status ELSE 'pending' END"
                    );
                    $stmtPre->execute([
                        (string)$src['employee_id'],
                        (string)$src['employee_name'],
                        (string)$src['department'],
                        (string)$src['position'],
                        (float)$src['competency'],
                        (string)$src['status'],
                        (string)$succTargetRole,
                        (string)$succReadinessLevel,
                        null,
                        ($succMentorCoach !== '' ? (string)$succMentorCoach : null),
                    ]);
                    logAction($employeeId, 'Succession', 'Pre-Promotion Saved', 'Saved employee to pre-promotion repository');
                } catch (Throwable $ignored) {
                }
            }
        }

        if ($errorMessage === '') {
            if ($isSuccessionReadyPost) {
                header('Location: pre-promotion_table.php?ok=saved');
            } else {
                header('Location: individual_development_plans.php?ok=created');
            }
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
                COALESCE(e.competency, ss.competency, 0) AS competency,
                COALESCE(e.status, ss.status, 'Retrain') AS status,
                e.created_at AS employee_created_at,
                ss.development_plan,
                ss.target_score,
                ss.target_date,
                ss.idp_status,
                ss.idp_created_at,
                ss.created_at
         FROM succession_submissions ss
         LEFT JOIN employees e ON e.employee_id = ss.employee_id
         WHERE ss.employee_id = ?"
    );
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch();
}
if (!$row) {
    http_response_code(404);
    $errorMessage = 'Employee record not found in Succession Submissions.';
}

if ($row && $viewOnlyRequested) {
    $viewOnly = $viewOnlyRequested && ((string)($row['idp_status'] ?? '') === 'Created');
}

$employeeStatus = (string)($row['status'] ?? '');
$isSuccessionReady = $employeeStatus === 'Succession Ready';

$roleRank = function (string $role): int {
    $r = strtolower(trim($role));
    if ($r === '') return 0;
    $rank = 1;
    if (preg_match('/\b(assistant|assoc|junior|jr\.?|entry)\b/i', $r)) $rank = 2;
    if (preg_match('/\b(senior|sr\.?|specialist)\b/i', $r)) $rank = max($rank, 3);
    if (preg_match('/\b(lead)\b/i', $r)) $rank = max($rank, 4);
    if (preg_match('/\b(supervisor)\b/i', $r)) $rank = max($rank, 5);
    if (preg_match('/\b(manager)\b/i', $r)) $rank = max($rank, 6);
    if (preg_match('/\b(head)\b/i', $r)) $rank = max($rank, 7);
    if (preg_match('/\b(director)\b/i', $r)) $rank = max($rank, 8);
    if (preg_match('/\b(vice president|vp)\b/i', $r)) $rank = max($rank, 9);
    if (preg_match('/\b(chief)\b/i', $r)) $rank = max($rank, 10);
    return $rank;
};

$targetRoleOptions = [];
$mentorOptions = [];
if ($row && $isSuccessionReady) {
    $dept = (string)($row['department'] ?? '');
    $currentPos = (string)($row['position'] ?? '');
    $currentRank = $roleRank($currentPos);

    try {
        $stmtPos = $pdo->prepare('SELECT DISTINCT position FROM employees WHERE department = ? AND position IS NOT NULL AND position <> ?');
        $stmtPos->execute([$dept, '']);
        $positions = $stmtPos->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($positions as $p) {
            $p = (string)$p;
            if (trim($p) === '') continue;
            if ($roleRank($p) > $currentRank) {
                $targetRoleOptions[] = $p;
            }
        }
        $targetRoleOptions = array_values(array_unique($targetRoleOptions));
        usort($targetRoleOptions, function ($a, $b) use ($roleRank) {
            $ra = $roleRank((string)$a);
            $rb = $roleRank((string)$b);
            if ($ra === $rb) return strcasecmp((string)$a, (string)$b);
            return $ra <=> $rb;
        });
    } catch (Throwable $ignored) {
        $targetRoleOptions = [];
    }

    try {
        $stmtMent = $pdo->prepare('SELECT employee_id, full_name, position FROM employees WHERE department = ? AND employee_id <> ?');
        $stmtMent->execute([$dept, (string)($row['employee_id'] ?? '')]);
        $mentors = $stmtMent->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($mentors as $m) {
            $mPos = (string)($m['position'] ?? '');
            if (trim($mPos) === '') continue;
            if ($roleRank($mPos) > $currentRank) {
                $mentorOptions[] = [
                    'employee_id' => (string)($m['employee_id'] ?? ''),
                    'full_name' => (string)($m['full_name'] ?? ''),
                    'position' => $mPos,
                ];
            }
        }

        if (count($mentorOptions) === 0) {
            $fallbackMentors = [];
            try {
                $stmtFallback = $pdo->prepare(
                    "SELECT employee_id, full_name, position
                     FROM employees
                     WHERE department = ?
                       AND employee_id <> ?
                       AND position REGEXP '(Supervisor|Manager|HR Manager)'
                     ORDER BY position ASC, full_name ASC"
                );
                $stmtFallback->execute([$dept, (string)($row['employee_id'] ?? '')]);
                $fallbackMentors = $stmtFallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $ignored) {
                $fallbackMentors = [];
            }

            if (count($fallbackMentors) === 0) {
                try {
                    $stmtFallbackHr = $pdo->prepare(
                        "SELECT employee_id, full_name, position
                         FROM employees
                         WHERE department = 'Human Resources (HR)'
                           AND employee_id <> ?
                           AND position REGEXP '(Supervisor|Manager|HR Manager)'
                         ORDER BY position ASC, full_name ASC"
                    );
                    $stmtFallbackHr->execute([(string)($row['employee_id'] ?? '')]);
                    $fallbackMentors = $stmtFallbackHr->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable $ignored) {
                    $fallbackMentors = [];
                }
            }

            foreach ($fallbackMentors as $m) {
                $mPos = (string)($m['position'] ?? '');
                if (trim($mPos) === '') continue;
                $mentorOptions[] = [
                    'employee_id' => (string)($m['employee_id'] ?? ''),
                    'full_name' => (string)($m['full_name'] ?? ''),
                    'position' => $mPos,
                ];
            }
        }

        usort($mentorOptions, function ($a, $b) use ($roleRank) {
            $ra = $roleRank((string)($a['position'] ?? ''));
            $rb = $roleRank((string)($b['position'] ?? ''));
            if ($ra === $rb) return strcasecmp((string)($a['full_name'] ?? ''), (string)($b['full_name'] ?? ''));
            return $ra <=> $rb;
        });
    } catch (Throwable $ignored) {
        $mentorOptions = [];
    }
}

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

$kpiBreakdown = [];
$kpiComputed = [];
$kpiByName = [];
$gapKpiNames = [];
$kpiPeriod = '';
if ($row) {
    try {
        ensureKpiSchema();
        ensureCompetencyCriteriaSchema();
        $kpiPeriod = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
        seedMissingKpiEvaluations((string)($row['employee_id'] ?? ''), $kpiPeriod);

        $criteriaReq = [];
        try {
            $stmtC = $pdo->query('SELECT name, required_level FROM competency_criteria');
            $critRows = $stmtC ? $stmtC->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach (($critRows ?? []) as $c) {
                $criteriaReq[(string)($c['name'] ?? '')] = (float)($c['required_level'] ?? 0);
            }
        } catch (Throwable $ignored) {
            $criteriaReq = [];
        }

        $stmtK = $pdo->prepare(
            "SELECT k.kpi_name,
                    GROUP_CONCAT(CAST(s.score AS CHAR) ORDER BY s.id SEPARATOR ', ') AS scores_text,
                    AVG(COALESCE(s.score, 0)) AS avg_score
             FROM employee_kpi_scores s
             JOIN kpis k ON k.id = s.kpi_id
             WHERE s.employee_id = ? AND s.evaluation_period = ?
             GROUP BY k.kpi_name
             ORDER BY k.kpi_name ASC"
        );
        $stmtK->execute([(string)($row['employee_id'] ?? ''), $kpiPeriod]);
        $kpiBreakdown = $stmtK->fetchAll(PDO::FETCH_ASSOC);

        $maxScore = 5.0;
        foreach (($kpiBreakdown ?? []) as $k) {
            $kpiName = (string)($k['kpi_name'] ?? '');
            $avgScore = is_numeric($k['avg_score'] ?? null) ? (float)$k['avg_score'] : 0.0;
            $kpiPct = round(max(0.0, min(100.0, ($avgScore / $maxScore) * 100.0)), 1);
            $reqPct = isset($criteriaReq[$kpiName]) ? (float)$criteriaReq[$kpiName] : 80.0;
            if ($reqPct < 0) $reqPct = 0.0;
            if ($reqPct > 100) $reqPct = 100.0;
            $gapPct = round(max(0.0, $reqPct - $kpiPct), 1);

            $kpiComputed[] = [
                'kpi_name' => $kpiName,
                'scores_text' => (string)($k['scores_text'] ?? ''),
                'avg_score' => $avgScore,
                'kpi_pct' => $kpiPct,
                'required_pct' => round($reqPct, 1),
                'gap_pct' => $gapPct,
            ];

            $kpiByName[$kpiName] = [
                'kpi_pct' => $kpiPct,
                'required_pct' => round($reqPct, 1),
                'gap_pct' => $gapPct,
            ];

            if ($gapPct > 0) {
                $gapKpiNames[$kpiName] = true;
            }
        }
    } catch (Throwable $e) {
        $kpiBreakdown = [];
        $kpiComputed = [];
        $kpiByName = [];
        $gapKpiNames = [];
        $kpiPeriod = '';
    }
}

$hasKpiGap = $row ? (count($gapKpiNames) > 0) : false;
if ($row && function_exists('employeeHasGaps')) {
    try {
        $hasKpiGap = (bool)employeeHasGaps($employeeId);
    } catch (Throwable $ignored) {
        $hasKpiGap = $row ? (count($gapKpiNames) > 0) : false;
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

if ($row && count($suggestedPlans) === 0) {
    try {
        $st = (string)($row['status'] ?? '');
        if ($st !== '') {
            $stmtTpl = $pdo->prepare(
                "SELECT c.name AS criteria_name, t.plan_text
                 FROM development_plan_templates t
                 JOIN development_plan_criteria c ON c.id = t.criteria_id
                 WHERE t.status = ?
                 ORDER BY c.name ASC"
            );
            $stmtTpl->execute([$st]);
            $tplRows = $stmtTpl->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($tplRows as $tr) {
                $critName = trim((string)($tr['criteria_name'] ?? ''));
                $planText = (string)($tr['plan_text'] ?? '');
                if ($critName === '' || trim($planText) === '') {
                    continue;
                }
                $suggestedPlans[$critName] = [
                    'plan_text' => $planText,
                    'delivery_mode' => 'Onsite',
                ];
            }
        }
    } catch (Throwable $e) {
        $suggestedPlans = $suggestedPlans;
    }
}

$idpDeliveryModeValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['idp_delivery_mode'] ?? 'Onsite') : 'Onsite';
$idpDeliveryModeValue = in_array($idpDeliveryModeValue, ['Online','Onsite','Hybrid'], true) ? $idpDeliveryModeValue : 'Onsite';

require('../../partials/header.php');
?>

<body class="bg-gray-50 min-h-screen">

  <main class="p-6">
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
                        <label class="block text-xs font-semibold text-gray-500">KPI %</label>
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
                    <div class="text-sm font-semibold text-gray-900">KPI Evaluation Summary</div>
                    <div class="text-xs text-gray-500"><?php echo $kpiPeriod !== '' ? ('Period: ' . h($kpiPeriod)) : ''; ?></div>
                </div>
                <div class="mt-3 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 border-b">
                                <th class="py-2 pr-3">KPI</th>
                                <th class="py-2 pr-3">Scores</th>
                                <th class="py-2 text-right">Avg</th>
                                <th class="py-2 text-right">KPI %</th>
                                <th class="py-2 text-right">Required %</th>
                                <th class="py-2 text-right">Gap %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kpiComputed)): ?>
                                <tr><td colspan="6" class="py-3 text-gray-500">No KPI evaluations found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($kpiComputed as $k): ?>
                                    <?php
                                        $avgScore = is_numeric($k['avg_score'] ?? null) ? (float)$k['avg_score'] : 0.0;
                                        $kpiPct = is_numeric($k['kpi_pct'] ?? null) ? (float)$k['kpi_pct'] : 0.0;
                                        $reqPct = is_numeric($k['required_pct'] ?? null) ? (float)$k['required_pct'] : 0.0;
                                        $gapPct = is_numeric($k['gap_pct'] ?? null) ? (float)$k['gap_pct'] : 0.0;
                                        $gapClass = $gapPct > 0 ? 'text-red-600 font-semibold' : 'text-emerald-600 font-semibold';
                                    ?>
                                    <tr class="border-b">
                                        <td class="py-2 pr-3"><?php echo h($k['kpi_name'] ?? ''); ?></td>
                                        <td class="py-2 pr-3"><?php echo h($k['scores_text'] ?? ''); ?></td>
                                        <td class="py-2 text-right"><?php echo number_format($avgScore, 2); ?></td>
                                        <td class="py-2 text-right"><?php echo number_format($kpiPct, 1); ?>%</td>
                                        <td class="py-2 text-right"><?php echo number_format($reqPct, 1); ?>%</td>
                                        <td class="py-2 text-right <?php echo $gapClass; ?>"><?php echo number_format($gapPct, 1); ?>%</td>
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
                                <select name="succ_target_role" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                    <option value="" disabled <?php echo $succTargetRoleValue === '' ? 'selected' : ''; ?>>Select target role</option>
                                    <?php
                                        $renderedTargetRoles = [];
                                        if ($succTargetRoleValue !== '') {
                                            $renderedTargetRoles[] = $succTargetRoleValue;
                                        }
                                        foreach ($targetRoleOptions as $opt) {
                                            $renderedTargetRoles[] = (string)$opt;
                                        }
                                        $renderedTargetRoles = array_values(array_unique(array_filter($renderedTargetRoles, function ($v) { return trim((string)$v) !== ''; })));
                                    ?>
                                    <?php foreach ($renderedTargetRoles as $opt): ?>
                                        <option value="<?php echo h($opt); ?>" <?php echo $succTargetRoleValue === $opt ? 'selected' : ''; ?>><?php echo h($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Readiness Level</label>
                                <select name="succ_readiness_level" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                    <option value="" disabled <?php echo $succReadinessLevelValue === '' ? 'selected' : ''; ?>>Select readiness</option>
                                    <?php if (!$hasKpiGap): ?>
                                        <option value="Ready Now" <?php echo $succReadinessLevelValue === 'Ready Now' ? 'selected' : ''; ?>>Ready Now</option>
                                    <?php else: ?>
                                        <option value="Ready in 6 Months" <?php echo $succReadinessLevelValue === 'Ready in 6 Months' ? 'selected' : ''; ?>>Ready in 6 Months</option>
                                        <option value="Ready in 12 Months" <?php echo $succReadinessLevelValue === 'Ready in 12 Months' ? 'selected' : ''; ?>>Ready in 12 Months</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Assigned Mentor or Coach</label>
                                <select name="succ_mentor_coach" class="w-full border border-gray-300 rounded-md p-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900" <?php echo $viewOnly ? 'disabled' : ''; ?>>
                                    <option value="" <?php echo $succMentorCoachValue === '' ? 'selected' : ''; ?>>Select mentor/coach</option>
                                    <?php
                                        $renderedMentors = [];
                                        if ($succMentorCoachValue !== '') {
                                            $renderedMentors[] = ['label' => $succMentorCoachValue, 'value' => $succMentorCoachValue];
                                        }
                                        foreach ($mentorOptions as $m) {
                                            $label = trim((string)($m['full_name'] ?? ''));
                                            $pos = trim((string)($m['position'] ?? ''));
                                            if ($pos !== '') {
                                                $label .= ' (' . $pos . ')';
                                            }
                                            if ($label === '') continue;
                                            $renderedMentors[] = ['label' => $label, 'value' => $label];
                                        }
                                    ?>
                                    <?php
                                        $seenMent = [];
                                        foreach ($renderedMentors as $m) {
                                            $seenMent[(string)($m['value'] ?? '')] = $m;
                                        }
                                        $renderedMentors = array_values($seenMent);
                                    ?>
                                    <?php foreach ($renderedMentors as $m): ?>
                                        <option value="<?php echo h((string)$m['value']); ?>" <?php echo $succMentorCoachValue === (string)$m['value'] ? 'selected' : ''; ?>><?php echo h((string)$m['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!$isSuccessionReady || $hasKpiGap): ?>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-800 mb-2"><?php echo $isSuccessionReady ? 'Strategic Development Activities' : 'Suggested Development Plans'; ?></label>
                        <div id="suggested_plans" class="space-y-3">
                            <?php if (count($suggestedPlans) === 0): ?>
                                <div class="text-sm text-gray-500">No development plans available.</div>
                            <?php else: ?>
                                <?php $skillIndex = 0; ?>
                                <?php foreach ($suggestedPlans as $skillName => $planData): ?>
                                <?php
                                    if (!isset($kpiByName[(string)$skillName])) {
                                        continue;
                                    }
                                    $skillIndex++;
                                    $skillId = 's' . $skillIndex;
                                    $planText = is_array($planData) ? (string)($planData['plan_text'] ?? '') : (string)$planData;
                                    $deliveryMode = is_array($planData) ? (string)($planData['delivery_mode'] ?? 'Onsite') : 'Onsite';
                                    if ($deliveryMode !== 'Online' && $deliveryMode !== 'Onsite') {
                                        $deliveryMode = 'Onsite';
                                    }
                                    $items = function_exists('splitPlanItems') ? splitPlanItems($planText) : [trim((string)$planText)];
                                    $items = array_values(array_filter(array_map('trim', $items), function ($v) { return (string)$v !== ''; }));

                                    $canSelect = isset($gapKpiNames[(string)$skillName]);
                                    $kpiPct = (float)($kpiByName[(string)$skillName]['kpi_pct'] ?? 0);
                                    $reqPct = (float)($kpiByName[(string)$skillName]['required_pct'] ?? 0);
                                ?>
                                <div class="border border-gray-200 rounded-md p-3">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                        <input type="checkbox" class="skill-checkbox" data-skill-id="<?php echo h($skillId); ?>" data-skill-name="<?php echo h($skillName); ?>" data-delivery-mode="<?php echo h($deliveryMode); ?>" <?php echo ($viewOnly || !$canSelect) ? 'disabled' : ''; ?> />
                                        <span><?php echo h($skillName); ?></span>
                                        <span class="ml-auto text-xs font-semibold text-gray-600"><?php echo number_format($kpiPct, 1); ?>% / <?php echo number_format($reqPct, 1); ?>%</span>
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
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Strategic Development Activities</label>
                        <div class="text-sm text-gray-500">No development available.</div>
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

                <div class="flex items-center justify-between">
                    <a href="succession_dashboard.php" class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold bg-white border border-gray-300 text-gray-800 hover:bg-gray-50">Back</a>
                    <?php if (!$viewOnly): ?>
                        <div class="flex items-center gap-3">
                          
                            <button type="submit" name="submit_action" value="save" class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold bg-gray-900 text-white hover:bg-gray-800">Save</button>
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
            var formEl = document.querySelector('form');

            if (formEl && window.Swal && planInput && !planInput.hasAttribute('readonly')) {
                formEl.addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'question',
                        title: 'Save?',
                        text: 'Are you sure you want to save this record?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Save',
                        cancelButtonText: 'Cancel'
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            formEl.submit();
                        }
                    });
                });
            }

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

    </div>
  </main>

 </body>
 </html>
