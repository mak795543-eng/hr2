<?php
require_once __DIR__ . '/config.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update_actual_scores') {
    header('Content-Type: application/json; charset=utf-8');

    $employeeId = trim((string)($_POST['employee_id'] ?? ''));
    $scoresJson = (string)($_POST['scores'] ?? '');
    $scores = json_decode($scoresJson, true);

    if ($employeeId === '' || !is_array($scores) || empty($scores)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    try {
        $deptStmt = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ? LIMIT 1");
        $deptStmt->execute([$employeeId]);
        $dept = (string)($deptStmt->fetchColumn() ?: '');
        if ($dept === '') {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }

        $skillIds = [];
        foreach ($scores as $skillIdRaw => $scoreRaw) {
            $sid = (int)$skillIdRaw;
            if ($sid > 0) {
                $skillIds[] = $sid;
            }
        }
        $skillIds = array_values(array_unique($skillIds));
        if (empty($skillIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }

        $ph = implode(',', array_fill(0, count($skillIds), '?'));
        $validStmt = $pdo->prepare("SELECT id FROM skills WHERE category = 'General Skills' AND department = ? AND id IN ($ph)");
        $validStmt->execute(array_merge([$dept], $skillIds));
        $validIds = $validStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $validIds = array_map('intval', $validIds ?: []);
        $validLookup = array_fill_keys($validIds, true);

        $up = $pdo->prepare(
            "INSERT INTO employee_skills (employee_id, skill_id, skill_score, assessment_date)
             VALUES (?, ?, ?, CURRENT_DATE)
             ON DUPLICATE KEY UPDATE
                skill_score = VALUES(skill_score),
                assessment_date = CURRENT_DATE"
        );

        $pdo->beginTransaction();
        foreach ($scores as $skillIdRaw => $scoreRaw) {
            $sid = (int)$skillIdRaw;
            if ($sid <= 0 || !isset($validLookup[$sid])) {
                continue;
            }
            if (!is_numeric($scoreRaw)) {
                continue;
            }
            $score = (float)$scoreRaw;
            if ($score < 0) $score = 0;
            if ($score > 100) $score = 100;
            $up->execute([$employeeId, $sid, $score]);
        }
        $pdo->commit();

        $compStmt = $pdo->prepare(
            "SELECT AVG(COALESCE(es.skill_score, 0)) AS competency
             FROM skills s
             LEFT JOIN employee_skills es
               ON es.employee_id = ?
              AND es.skill_id = s.id
             WHERE s.category = 'General Skills'
               AND s.department = ?"
        );
        $compStmt->execute([$employeeId, $dept]);
        $competency = (float)($compStmt->fetchColumn() ?? 0);
        $competency = round($competency, 1);

        $status = 'Retrain';
        if ($competency <= 20) {
            $status = 'Retrain';
        } elseif ($competency <= 40) {
            $status = 'Reskilling';
        } elseif ($competency <= 60) {
            $status = 'Refresher Training';
        } elseif ($competency <= 80) {
            $status = 'Upskilling';
        } else {
            $status = 'Succession Ready';
        }

        echo json_encode(['success' => true, 'competency' => $competency, 'status' => $status]);
        exit;
    } catch (Throwable $e) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $ignored) {
        }
        error_log('gap_analysis update_actual_scores error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Save failed.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update_actual_score') {
    header('Content-Type: application/json; charset=utf-8');

    $employeeId = trim((string)($_POST['employee_id'] ?? ''));
    $skillId = (int)($_POST['skill_id'] ?? 0);
    $scoreRaw = trim((string)($_POST['score'] ?? ''));

    if ($employeeId === '' || $skillId <= 0 || $scoreRaw === '' || !is_numeric($scoreRaw)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    $score = (float)$scoreRaw;
    if ($score < 0) $score = 0;
    if ($score > 100) $score = 100;

    try {
        $deptStmt = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ? LIMIT 1");
        $deptStmt->execute([$employeeId]);
        $dept = (string)($deptStmt->fetchColumn() ?: '');
        if ($dept === '') {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }

        $skillStmt = $pdo->prepare("SELECT id FROM skills WHERE id = ? AND category = 'General Skills' AND department = ? LIMIT 1");
        $skillStmt->execute([$skillId, $dept]);
        $validSkill = (int)($skillStmt->fetchColumn() ?: 0);
        if ($validSkill <= 0) {
            echo json_encode(['success' => false, 'message' => 'Skill not found.']);
            exit;
        }

        $up = $pdo->prepare(
            "INSERT INTO employee_skills (employee_id, skill_id, skill_score, assessment_date)
             VALUES (?, ?, ?, CURRENT_DATE)
             ON DUPLICATE KEY UPDATE
                skill_score = VALUES(skill_score),
                assessment_date = CURRENT_DATE"
        );
        $up->execute([$employeeId, $skillId, $score]);

        echo json_encode(['success' => true, 'score' => $score]);
        exit;
    } catch (Throwable $e) {
        error_log('gap_analysis update_actual_score error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Save failed.']);
        exit;
    }
}

$departmentFilter = (string)($_GET['department'] ?? 'all');
$statusFilter = (string)($_GET['status'] ?? 'all');
$search = trim((string)($_GET['search'] ?? ''));

$departments = [];
try {
    $departments = getDepartments();
} catch (Throwable $e) {
    $departments = [];
}

$allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

$where = ['1=1'];
$params = [];

if ($departmentFilter !== 'all' && $departmentFilter !== '') {
    $where[] = 'e.department = ?';
    $params[] = $departmentFilter;
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(e.full_name LIKE ? OR e.employee_id LIKE ? OR e.position LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT e.employee_id,
            e.full_name AS employee_name,
            e.position,
            e.department,
            s.id AS skill_id,
            s.skill_name,
            COALESCE(es.skill_score, 0) AS actual_score,
            COALESCE(gss.standard_percentage, 80) AS standard_score
     FROM employees e
     JOIN skills s
       ON s.category = 'General Skills'
      AND s.department = e.department
     LEFT JOIN employee_skills es
       ON es.employee_id = e.employee_id
      AND es.skill_id = s.id
     LEFT JOIN general_skill_standards gss
       ON gss.skill_id = s.id
     $whereSql
     ORDER BY e.department ASC, e.full_name ASC, s.skill_name ASC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$employees = [];
foreach ($rows as $r) {
    $eid = (string)($r['employee_id'] ?? '');
    if ($eid === '') {
        continue;
    }
    if (!isset($employees[$eid])) {
        $employees[$eid] = [
            'employee_id' => $eid,
            'employee_name' => (string)($r['employee_name'] ?? ''),
            'position' => (string)($r['position'] ?? ''),
            'department' => (string)($r['department'] ?? ''),
            'skills' => [],
        ];
    }

    $skillId = (int)($r['skill_id'] ?? 0);
    if ($skillId <= 0) {
        continue;
    }

    $actual = (float)($r['actual_score'] ?? 0);
    $standard = (float)($r['standard_score'] ?? 80);
    $gap = $standard - $actual;
    if ($gap < 0) {
        $gap = 0;
    }

    $employees[$eid]['skills'][] = [
        'skill_id' => $skillId,
        'skill_name' => (string)($r['skill_name'] ?? ''),
        'actual' => $actual,
        'standard' => $standard,
        'gap' => $gap,
    ];
}

$calcStatus = function(float $competency): string {
    if ($competency <= 20) return 'Retrain';
    if ($competency <= 40) return 'Reskilling';
    if ($competency <= 60) return 'Refresher Training';
    if ($competency <= 80) return 'Upskilling';
    return 'Succession Ready';
};

$statusBadgeClass = function(string $status): string {
    switch ($status) {
        case 'Retrain':
            return 'badge-neutral';
        case 'Reskilling':
            return 'badge-error';
        case 'Refresher Training':
            return 'badge-warning';
        case 'Upskilling':
            return 'badge-info';
        case 'Succession Ready':
            return 'badge-success';
        default:
            return 'badge-ghost';
    }
};

$cards = [];
foreach ($employees as $eid => $e) {
    $skills = $e['skills'];
    $actualAvg = 0;
    $standardAvg = 0;
    $gapAvg = 0;
    $count = count($skills);
    if ($count > 0) {
        foreach ($skills as $s) {
            $actualAvg += (float)$s['actual'];
            $standardAvg += (float)$s['standard'];
            $gapAvg += (float)$s['gap'];
        }
        $actualAvg /= $count;
        $standardAvg /= $count;
        $gapAvg /= $count;
    }
    $status = $calcStatus($actualAvg);

    if ($statusFilter !== 'all' && in_array($statusFilter, $allowedStatuses, true) && $status !== $statusFilter) {
        continue;
    }

    $cards[] = [
        'employee' => $e,
        'actual_avg' => $actualAvg,
        'standard_avg' => $standardAvg,
        'gap_avg' => $gapAvg,
        'status' => $status,
    ];
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Skill Gap Analysis</title>
           <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Skill Gap Analysis</h1>
                <div class="text-sm opacity-70">Critical Roles employees: <span class="font-semibold"><?php echo (int)count($cards); ?></span></div>
            </div>
            <div class="flex gap-2">
                <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
                <a href="pushed_critical_roles.php" class="btn btn-outline btn-sm">Pushed History</a>
                <a href="general_skills_management.php" class="btn btn-primary btn-sm">General Skills Management</a>
            </div>
        </div>

        <div class="card bg-base-100 shadow mb-6">
            <div class="card-body">
                <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
                    <div class="flex-1">
                        <label class="label"><span class="label-text">Search</span></label>
                        <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Search employee / ID / position" class="input input-bordered w-full" />
                    </div>

                    <div class="w-full md:w-64">
                        <label class="label"><span class="label-text">Department</span></label>
                        <select name="department" class="select select-bordered w-full">
                            <option value="all" <?php echo $departmentFilter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach (($departments ?? []) as $dept): ?>
                                <option value="<?php echo h($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>><?php echo h($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-full md:w-56">
                        <label class="label"><span class="label-text">Status</span></label>
                        <select name="status" class="select select-bordered w-full">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <?php foreach ($allowedStatuses as $st): ?>
                                <option value="<?php echo h($st); ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo h($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="gap_analysis.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            <?php if (count($cards) === 0): ?>
                <div class="card bg-base-100 shadow">
                    <div class="card-body text-center opacity-70">No employees found for analysis.</div>
                </div>
            <?php else: ?>
                <?php foreach ($cards as $c): ?>
                    <?php $e = $c['employee']; ?>
                    <details class="collapse collapse-arrow bg-base-100 shadow">
                        <summary class="collapse-title">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                <div>
                                    <div class="font-semibold"><?php echo h($e['employee_name']); ?></div>
                                    <div class="text-xs opacity-70"><?php echo h($e['employee_id']); ?> · <?php echo h($e['position']); ?> · <?php echo h($e['department']); ?></div>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="badge badge-sm gap-status-badge <?php echo h($statusBadgeClass($c['status'])); ?>" data-status="<?php echo h($c['status']); ?>"><?php echo h($c['status']); ?></span>
                                    <div class="badge badge-outline badge-sm">Standard Avg: <?php echo number_format((float)$c['standard_avg'], 1); ?>%</div>
                                </div>
                            </div>
                        </summary>
                        <div class="collapse-content" data-employee-id="<?php echo h($e['employee_id']); ?>">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>General Skill</th>
                                            <th class="text-right">Actual %</th>
                                            <th class="text-right">Standard %</th>
                                            <th class="text-right">Gap %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (($e['skills'] ?? []) as $s): ?>
                                            <tr>
                                                <td><?php echo h($s['skill_name']); ?></td>
                                                <td class="text-right">
                                                    <input
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        max="100"
                                                        value="<?php echo h(number_format((float)$s['actual'], 1, '.', '')); ?>"
                                                        class="input input-bordered input-sm w-24 text-right gap-actual-input"
                                                        data-employee-id="<?php echo h($e['employee_id']); ?>"
                                                        data-skill-id="<?php echo (int)$s['skill_id']; ?>"
                                                        data-standard="<?php echo h(number_format((float)$s['standard'], 1, '.', '')); ?>"
                                                    />
                                                </td>
                                                <td class="text-right"><?php echo number_format((float)$s['standard'], 1); ?>%</td>
                                                <td class="text-right font-semibold gap-cell <?php echo ((float)$s['gap'] > 0 ? 'text-error' : ''); ?>"><?php echo number_format((float)$s['gap'], 1); ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm opacity-70">Competency Level:</span>
                                    <span class="font-semibold"><span class="gap-competency-val"><?php echo number_format((float)$c['actual_avg'], 1); ?></span>%</span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm gap-save-btn" data-employee-id="<?php echo h($e['employee_id']); ?>" disabled>Save</button>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="gap_save_toast" class="toast toast-top toast-end hidden">
        <div class="alert alert-success">
            <span>Saved.</span>
        </div>
    </div>

    <script>
        (function () {
            var toast = document.getElementById('gap_save_toast');
            var toastTimer = null;

            function showToast() {
                if (!toast) return;
                toast.classList.remove('hidden');
                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    toast.classList.add('hidden');
                }, 1200);
            }

            function statusClass(status) {
                if (status === 'Retrain') return 'badge-neutral';
                if (status === 'Reskilling') return 'badge-error';
                if (status === 'Refresher Training') return 'badge-warning';
                if (status === 'Upskilling') return 'badge-info';
                if (status === 'Succession Ready') return 'badge-success';
                return 'badge-ghost';
            }

            function calcStatus(competency) {
                var v = parseFloat(competency) || 0;
                if (v <= 20) return 'Retrain';
                if (v <= 40) return 'Reskilling';
                if (v <= 60) return 'Refresher Training';
                if (v <= 80) return 'Upskilling';
                return 'Succession Ready';
            }

            function toNumber(val) {
                if (val === '' || val === null || typeof val === 'undefined') return 0;
                var n = parseFloat(val);
                return Number.isFinite(n) ? n : 0;
            }

            function clampScore(n) {
                if (!Number.isFinite(n)) return 0;
                if (n < 0) return 0;
                if (n > 100) return 100;
                return n;
            }

            function updateCard(container) {
                var inputs = container.querySelectorAll('.gap-actual-input');
                var sum = 0;
                var count = 0;
                var dirty = false;

                inputs.forEach(function (el) {
                    var v = clampScore(toNumber(el.value));
                    sum += v;
                    count++;

                    var orig = toNumber(el.getAttribute('data-orig'));
                    if (Math.abs(v - orig) > 0.0001) {
                        dirty = true;
                    }

                    var standard = toNumber(el.getAttribute('data-standard'));
                    var gap = standard - v;
                    if (gap < 0) gap = 0;

                    var row = el.closest('tr');
                    if (row) {
                        var gapCell = row.querySelector('.gap-cell');
                        if (gapCell) {
                            gapCell.textContent = gap.toFixed(1) + '%';
                            if (gap > 0) {
                                gapCell.classList.add('text-error');
                            } else {
                                gapCell.classList.remove('text-error');
                            }
                        }
                    }
                });

                var avg = count > 0 ? (sum / count) : 0;
                avg = Math.round(avg * 10) / 10;

                var competencyEl = container.querySelector('.gap-competency-val');
                if (competencyEl) {
                    competencyEl.textContent = avg.toFixed(1);
                }

                var status = calcStatus(avg);
                var badge = container.closest('details') ? container.closest('details').querySelector('.gap-status-badge') : null;
                if (badge) {
                    badge.textContent = status;
                    badge.classList.remove('badge-neutral', 'badge-error', 'badge-warning', 'badge-info', 'badge-success', 'badge-ghost');
                    badge.classList.add(statusClass(status));
                    badge.setAttribute('data-status', status);
                }

                var saveBtn = container.querySelector('.gap-save-btn');
                if (saveBtn) {
                    saveBtn.disabled = !dirty;
                }
            }

            function initCard(container) {
                var inputs = container.querySelectorAll('.gap-actual-input');
                inputs.forEach(function (el) {
                    el.setAttribute('data-orig', String(toNumber(el.value)));
                    el.addEventListener('input', function () {
                        updateCard(container);
                    });
                    el.addEventListener('change', function () {
                        updateCard(container);
                    });
                });

                var saveBtn = container.querySelector('.gap-save-btn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', function () {
                        var employeeId = saveBtn.getAttribute('data-employee-id') || container.getAttribute('data-employee-id') || '';
                        if (!employeeId) return;

                        var payload = {};
                        var inputs2 = container.querySelectorAll('.gap-actual-input');
                        inputs2.forEach(function (el) {
                            var sid = el.getAttribute('data-skill-id') || '';
                            var v = clampScore(toNumber(el.value));
                            if (sid) {
                                payload[sid] = v;
                            }
                        });

                        var fd = new URLSearchParams();
                        fd.set('action', 'update_actual_scores');
                        fd.set('employee_id', employeeId);
                        fd.set('scores', JSON.stringify(payload));

                        saveBtn.disabled = true;
                        saveBtn.classList.add('loading');

                        fetch(window.location.pathname + window.location.search, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: fd.toString()
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            saveBtn.classList.remove('loading');
                            if (data && data.success) {
                                inputs2.forEach(function (el) {
                                    el.setAttribute('data-orig', String(toNumber(el.value)));
                                });
                                showToast();
                                updateCard(container);
                            } else {
                                saveBtn.disabled = false;
                            }
                        }).catch(function () {
                            saveBtn.classList.remove('loading');
                            saveBtn.disabled = false;
                        });
                    });
                }

                updateCard(container);
            }

            document.querySelectorAll('.collapse-content[data-employee-id]').forEach(function (container) {
                initCard(container);
            });
        })();
    </script>
     <script>
    lucide.createIcons();
  </script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
