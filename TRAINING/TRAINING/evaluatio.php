<?php
session_start();
require_once __DIR__ . '/db.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function parse_plan_items(string $text): array {
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = array_map('trim', explode("\n", $text));
    $lines = array_values(array_filter($lines, fn($l) => $l !== ''));

    $items = [];
    foreach ($lines as $line) {
        if (preg_match('/^[-•]\s*(.+)$/u', $line, $m)) {
            $it = trim((string)$m[1]);
            if ($it !== '') $items[] = $it;
        }
    }

    if (!empty($items)) return $items;

    foreach ($lines as $line) {
        if (substr($line, -1) === ':') continue;
        $items[] = $line;
    }
    return $items;
}

$getEvaluatorName = function (): string {
    $candidates = ['full_name', 'name', 'username', 'email', 'user_id', 'employee_no', 'employee_id'];
    foreach ($candidates as $k) {
        if (isset($_SESSION[$k]) && trim((string)$_SESSION[$k]) !== '') {
            return trim((string)$_SESSION[$k]);
        }
    }
    return 'sess:' . session_id();
};

$evaluatorName = $getEvaluatorName();
$mentorName = '';

try {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS training_program_evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            submission_no INT NOT NULL DEFAULT 1,
            evaluator_name VARCHAR(150) NULL,
            overall_percentage DECIMAL(5,2) NOT NULL,
            remarks TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_tpe (program_id, submission_no, evaluator_name),
            INDEX idx_tpe_program (program_id, submission_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS training_program_evaluation_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evaluation_id INT NOT NULL,
            item_type VARCHAR(30) NOT NULL,
            item_key VARCHAR(64) NOT NULL,
            item_label TEXT NOT NULL,
            rating_percentage DECIMAL(5,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_item (evaluation_id, item_key),
            INDEX idx_item_eval (evaluation_id),
            CONSTRAINT fk_tpei_eval FOREIGN KEY (evaluation_id) REFERENCES training_program_evaluations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Throwable $e) {
}

$cgPdo = null;
try {
    $dbPrefix = getenv('DB_PREFIX') ?: '';
    $cgHost = getenv('CRITICAL_GAPS_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
    $cgUser = getenv('CRITICAL_GAPS_DB_USER') ?: (getenv('DB_USER') ?: 'root');
    $cgPassEnv = getenv('CRITICAL_GAPS_DB_PASS');
    $cgPassGlobal = getenv('DB_PASS');
    $cgPass = $cgPassEnv !== false
        ? $cgPassEnv
        : ($cgPassGlobal !== false
            ? $cgPassGlobal
            : (($cgUser === 'root' && ($cgHost === 'localhost' || $cgHost === '127.0.0.1')) ? '' : 'makmak01'));
    $cgName = getenv('CRITICAL_GAPS_DB_NAME') ?: 'critical_gaps';
    if ($dbPrefix !== '' && strpos($cgName, $dbPrefix) !== 0) {
        $cgName = $dbPrefix . $cgName;
    }
    $cgPdo = new PDO(
        "mysql:host=" . $cgHost . ";dbname=" . $cgName . ";charset=utf8mb4",
        $cgUser,
        $cgPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (Throwable $e) {
    $cgPdo = null;
}

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$submissionNo = isset($_GET['submission_no']) ? (int)$_GET['submission_no'] : 1;
if ($submissionNo <= 0) $submissionNo = 1;

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

if ($programId <= 0) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Evaluate</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Missing program</h1><p class="text-gray-600 mt-2">program_id is required.</p><div class="mt-4"><a class="btn btn-outline" href="posted_trainings.php">Back</a></div></div></div></body></html>';
    exit;
}

$program = null;
try {
    $stmt = $conn->prepare("SELECT id, training_title, training_type, training_mode, target_audience, department_id, target_role, mentor_id, start_datetime, end_datetime, status, category, description FROM training_programs WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
} catch (Throwable $e) {
    $program = null;
}

if (!$program) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Evaluate</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Not found</h1><p class="text-gray-600 mt-2">Training program not found.</p><div class="mt-4"><a class="btn btn-outline" href="posted_trainings.php">Back</a></div></div></div></body></html>';
    exit;
}

try {
    $mid = (int)($program['mentor_id'] ?? 0);
    if ($mid > 0) {
        $stmtMentor = $conn->prepare('SELECT mentor_name FROM mentors WHERE id = ? LIMIT 1');
        $stmtMentor->bind_param('i', $mid);
        $stmtMentor->execute();
        $mentorName = (string)($stmtMentor->get_result()->fetch_assoc()['mentor_name'] ?? '');
        $mentorName = trim($mentorName);
    }
} catch (Throwable $e) {
    $mentorName = '';
}

if ($mentorName !== '') {
    $evaluatorName = $mentorName;
}

$assignedEmployees = [];
try {
    $stmt = $conn->prepare(
        "SELECT e.id, e.employee_no, e.first_name, e.last_name, e.department, e.role
         FROM training_post_assignments a
         JOIN employees e ON e.id = a.employee_id
         WHERE a.program_id = ? AND a.submission_no = ?
         ORDER BY e.last_name, e.first_name"
    );
    $stmt->bind_param('ii', $programId, $submissionNo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $assignedEmployees[] = $row;
    }
} catch (Throwable $e) {
    $assignedEmployees = [];
}

$deptMap = [
    1 => 'Front Office / Reception',
    2 => 'Housekeeping',
    3 => 'Food & Beverage (F&B)',
    4 => 'Kitchen / Culinary',
    5 => 'Human Resources (HR)',
    10 => 'Sales & Marketing',
    12 => 'Finance / Accounting',
    13 => 'Engineering / Maintenance',
    14 => 'Security'
];

$deptName = '';
if (isset($program['department_id'])) {
    $deptId = (int)($program['department_id'] ?? 0);
    if ($deptId > 0 && isset($deptMap[$deptId])) {
        $deptName = (string)$deptMap[$deptId];
    }
}

$developmentPlanItems = parse_plan_items((string)($program['description'] ?? ''));

$generalSkills = [];
if ($cgPdo && $deptName !== '') {
    try {
        $stmt = $cgPdo->prepare("SELECT id, skill_name FROM skills WHERE category = 'General Skills' AND department = ? ORDER BY skill_name");
        $stmt->execute([$deptName]);
        while ($row = $stmt->fetch()) {
            $generalSkills[] = $row;
        }
    } catch (Throwable $e) {
        $generalSkills = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'submit_evaluation') {
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    $devRatingsIn = $_POST['dev_ratings'] ?? null;
    $skillRatingsIn = $_POST['skill_ratings'] ?? null;

    $items = [];
    if (is_array($devRatingsIn)) {
        foreach ($devRatingsIn as $k => $v) {
            $key = trim((string)$k);
            $val = is_numeric($v) ? (float)$v : null;
            if ($key === '' || $val === null) continue;
            if ($val < 0) $val = 0;
            if ($val > 100) $val = 100;
            $items[] = ['type' => 'development_plan', 'key' => $key, 'label' => (string)($developmentPlanItems[(int)$key] ?? ''), 'pct' => $val];
        }
    }

    if (is_array($skillRatingsIn)) {
        $skillById = [];
        foreach ($generalSkills as $gs) {
            $sid = (int)($gs['id'] ?? 0);
            if ($sid > 0) $skillById[(string)$sid] = (string)($gs['skill_name'] ?? '');
        }
        foreach ($skillRatingsIn as $k => $v) {
            $sid = (int)$k;
            $val = is_numeric($v) ? (float)$v : null;
            if ($sid <= 0 || $val === null) continue;
            if ($val < 0) $val = 0;
            if ($val > 100) $val = 100;
            $items[] = ['type' => 'general_skill', 'key' => 'skill:' . $sid, 'label' => $skillById[(string)$sid] ?? ('Skill #' . $sid), 'pct' => $val];
        }
    }

    $items = array_values(array_filter($items, fn($it) => trim((string)($it['label'] ?? '')) !== ''));
    if (empty($items)) {
        header('Location: evaluatio.php?program_id=' . urlencode((string)$programId) . '&submission_no=' . urlencode((string)$submissionNo) . '&err=invalid_rating');
        exit;
    }

    $sum = 0.0;
    foreach ($items as $it) $sum += (float)$it['pct'];
    $overall = $sum / max(1, count($items));

    try {
        $conn->begin_transaction();

        $stmtEval = $conn->prepare(
            "INSERT INTO training_program_evaluations
                (program_id, submission_no, evaluator_name, overall_percentage, remarks)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                overall_percentage = VALUES(overall_percentage),
                remarks = VALUES(remarks),
                created_at = CURRENT_TIMESTAMP"
        );
        $stmtEval->bind_param('iisds', $programId, $submissionNo, $evaluatorName, $overall, $remarks);
        $stmtEval->execute();

        $evalId = (int)$conn->insert_id;
        if ($evalId <= 0) {
            $stmtFind = $conn->prepare("SELECT id FROM training_program_evaluations WHERE program_id = ? AND submission_no = ? AND evaluator_name <=> ? LIMIT 1");
            $stmtFind->bind_param('iis', $programId, $submissionNo, $evaluatorName);
            $stmtFind->execute();
            $evalId = (int)($stmtFind->get_result()->fetch_assoc()['id'] ?? 0);
        }
        if ($evalId <= 0) {
            throw new RuntimeException('Failed to resolve evaluation id');
        }

        $stmtItem = $conn->prepare(
            "INSERT INTO training_program_evaluation_items
                (evaluation_id, item_type, item_key, item_label, rating_percentage)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                item_label = VALUES(item_label),
                rating_percentage = VALUES(rating_percentage),
                created_at = CURRENT_TIMESTAMP"
        );

        foreach ($items as $it) {
            $type = (string)$it['type'];
            $label = (string)$it['label'];
            $key = hash('sha256', $type . '|' . (string)$it['key'] . '|' . $label);
            $pct = (float)$it['pct'];
            $stmtItem->bind_param('isssd', $evalId, $type, $key, $label, $pct);
            $stmtItem->execute();
        }

        $conn->commit();
        header('Location: evaluatio.php?program_id=' . urlencode((string)$programId) . '&submission_no=' . urlencode((string)$submissionNo) . '&ok=saved');
        exit;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        header('Location: evaluatio.php?program_id=' . urlencode((string)$programId) . '&submission_no=' . urlencode((string)$submissionNo) . '&err=save_failed');
        exit;
    }
}

$evaluations = [];
try {
    $stmt = $conn->prepare(
        "SELECT te.id,
                te.evaluator_name,
                te.overall_percentage,
                te.remarks,
                te.created_at
         FROM training_program_evaluations te
         WHERE te.program_id = ? AND te.submission_no = ?
         ORDER BY te.created_at DESC"
    );
    $stmt->bind_param('ii', $programId, $submissionNo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $evaluations[] = $row;
    }
} catch (Throwable $e) {
    $evaluations = [];
}

$evaluationItems = [];
if (!empty($evaluations)) {
    try {
        $stmtItems = $conn->prepare(
            "SELECT item_type, item_label, rating_percentage
             FROM training_program_evaluation_items
             WHERE evaluation_id = ?
             ORDER BY item_type, id"
        );
        foreach ($evaluations as $ev) {
            $eid = (int)($ev['id'] ?? 0);
            if ($eid <= 0) continue;
            $stmtItems->bind_param('i', $eid);
            $stmtItems->execute();
            $resIt = $stmtItems->get_result();
            $evaluationItems[$eid] = [];
            while ($r = $resIt->fetch_assoc()) {
                $evaluationItems[$eid][] = $r;
            }
        }
    } catch (Throwable $e) {
        $evaluationItems = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evaluate</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
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
    
        <main class="container mx-auto px-4 py-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Evaluate</h1>
                    <div class="text-sm text-gray-500">Rate development plans with percentage scores</div>
                </div>
                <div class="flex gap-2">
                    <a class="btn btn-outline btn-sm" href="posted_trainings.php">Back to Posted Trainings</a>
                    <a class="btn btn-outline btn-sm" href="evaluation_repository.php">Evaluation Repository</a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Training</div>
                        <div class="text-xl font-bold text-gray-900"><?php echo h($program['training_title'] ?? ''); ?></div>
                        <div class="text-sm text-gray-600 mt-1"><?php echo h($program['training_type'] ?? ''); ?> · <?php echo h($program['training_mode'] ?? ''); ?> · <?php echo h($program['target_audience'] ?? ''); ?></div>
                        <div class="text-sm text-gray-600 mt-1"><?php echo h($program['start_datetime'] ?? ''); ?> to <?php echo h($program['end_datetime'] ?? ''); ?></div>
                        <div class="text-xs text-gray-500 mt-2">Submission No: <?php echo (int)$submissionNo; ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="badge badge-outline"><?php echo h($program['status'] ?? ''); ?></div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="text-sm font-semibold text-gray-700">Description</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line mt-1"><?php echo h($program['description'] ?? ''); ?></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800">Submit Evaluation</h2>
                <p class="text-sm text-gray-500 mt-1">Rate each development plan and general skill item with a percentage, then review the overall score at the bottom.</p>

                <form class="mt-4 space-y-6" method="POST">
                    <input type="hidden" name="action" value="submit_evaluation" />

                    <div>
                        <div class="font-semibold text-gray-800 mb-2">Development Plans</div>
                        <?php if (empty($developmentPlanItems)): ?>
                            <div class="text-sm text-gray-600">No development plan items found in the training description.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Plan Item</th>
                                            <th class="text-right">Rating (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($developmentPlanItems as $idx => $item): ?>
                                            <tr>
                                                <td class="text-sm text-gray-700"><?php echo h($item); ?></td>
                                                <td class="text-right">
                                                    <input
                                                        type="number"
                                                        name="dev_ratings[<?php echo (int)$idx; ?>]"
                                                        class="input input-bordered w-28 text-right rating-input"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        required
                                                    />
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="font-semibold text-gray-800 mb-2">General Skills<?php echo $deptName !== '' ? (' · ' . h($deptName)) : ''; ?></div>
                        <?php if (empty($generalSkills)): ?>
                            <div class="text-sm text-gray-600">No general skills found<?php echo $deptName !== '' ? (' for ' . h($deptName)) : ''; ?>.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Skill</th>
                                            <th class="text-right">Rating (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($generalSkills as $gs): ?>
                                            <?php $sid = (int)($gs['id'] ?? 0); ?>
                                            <tr>
                                                <td class="text-sm text-gray-700"><?php echo h($gs['skill_name'] ?? ''); ?></td>
                                                <td class="text-right">
                                                    <input
                                                        type="number"
                                                        name="skill_ratings[<?php echo $sid; ?>]"
                                                        class="input input-bordered w-28 text-right rating-input"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        required
                                                    />
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <div class="text-sm text-gray-600">Overall:</div>
                        <div class="text-lg font-bold text-gray-900"><span id="overallPct">0.00</span>%</div>
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Remarks</span></label>
                        <textarea name="remarks" class="textarea textarea-bordered w-full" rows="4" placeholder="Write remarks (optional)"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="submit" class="btn btn-primary">Submit Evaluation</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-800">Submitted Evaluations</h2>
                    <div class="text-sm text-gray-500">Total: <?php echo count($evaluations); ?></div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Evaluator</th>
                                <th>Overall</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($evaluations)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-6">No evaluations submitted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($evaluations as $ev): ?>
                                    <tr>
                                        <td><?php echo h($mentorName !== '' ? $mentorName : ($ev['evaluator_name'] ?? '')); ?></td>
                                        <td class="font-semibold"><?php echo h($ev['overall_percentage'] ?? ''); ?>%</td>
                                        <td class="text-sm text-gray-500"><?php echo h($ev['created_at'] ?? ''); ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <a class="btn btn-xs btn-outline" target="_blank" href="evaluation_repository.php?print=1&eval_id=<?php echo (int)($ev['id'] ?? 0); ?>">Print</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                        $eid = (int)($ev['id'] ?? 0);
                                        $items = $eid > 0 ? ($evaluationItems[$eid] ?? []) : [];
                                    ?>
                                    <?php if (!empty($items)): ?>
                                        <tr>
                                            <td colspan="4" class="bg-base-200">
                                                <div class="text-sm font-semibold">Ratings</div>
                                                <div class="overflow-x-auto mt-2">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Type</th>
                                                                <th>Item</th>
                                                                <th class="text-right">Rating (%)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($items as $it): ?>
                                                                <tr>
                                                                    <td class="text-xs text-gray-600"><?php echo h($it['item_type'] ?? ''); ?></td>
                                                                    <td class="text-sm"><?php echo h($it['item_label'] ?? ''); ?></td>
                                                                    <td class="text-right font-semibold"><?php echo h($it['rating_percentage'] ?? ''); ?>%</td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (trim((string)($ev['remarks'] ?? '')) !== ''): ?>
                                        <tr>
                                            <td colspan="4" class="bg-base-200">
                                                <div class="text-sm"><span class="font-semibold">Remarks:</span> <?php echo h($ev['remarks']); ?></div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    (function () {
        var ok = <?php echo json_encode($flashOk); ?>;
        var err = <?php echo json_encode($flashErr); ?>;

        var errMap = {
            invalid_rating: 'Please provide valid ratings.',
            save_failed: 'Failed to save evaluation.'
        };

        if (ok === 'saved' && window.Swal) {
            Swal.fire({ icon: 'success', title: 'Saved', text: 'Evaluation submitted.', timer: 1200, showConfirmButton: false });
        }
        if (err && window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: errMap[err] || 'Something went wrong.' });
        }
    })();
</script>

<script>
    (function () {
        const out = document.getElementById('overallPct');
        const inputs = Array.from(document.querySelectorAll('.rating-input'));
        if (!out || !inputs.length) return;

        const calc = () => {
            let sum = 0;
            let count = 0;
            inputs.forEach((i) => {
                const v = parseFloat(i.value);
                if (!Number.isFinite(v)) return;
                sum += v;
                count += 1;
            });
            const avg = count ? (sum / count) : 0;
            out.textContent = avg.toFixed(2);
        };

        inputs.forEach((i) => i.addEventListener('input', calc));
        calc();
    })();
</script>
<script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
