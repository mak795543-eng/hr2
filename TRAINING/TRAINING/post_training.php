<?php
session_start();
require_once __DIR__ . '/db.php';

$tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
};

$ensurePostSchema = function(mysqli $conn) use ($tableHasColumn): void {
    try {
        if (!$tableHasColumn($conn, 'employees', 'role')) {
            $conn->query("ALTER TABLE employees ADD COLUMN role VARCHAR(150) NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS training_posts (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_training_post (program_id, submission_no), INDEX idx_tp_program (program_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS training_post_assignments (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, employee_id INT NOT NULL, assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_tpa (program_id, submission_no, employee_id), INDEX idx_tpa_program (program_id), INDEX idx_tpa_employee (employee_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    // If tables were imported from older SQL dumps, id may be NOT NULL without AUTO_INCREMENT.
    // Fixing this prevents insert failures like "Field 'id' doesn't have a default value".
    try {
        if ($tableHasColumn($conn, 'training_posts', 'id')) {
            $conn->query("ALTER TABLE training_posts MODIFY id INT NOT NULL AUTO_INCREMENT");
        }
    } catch (Throwable $e) {
    }
    try {
        $conn->query("ALTER TABLE training_posts ADD PRIMARY KEY (id)");
    } catch (Throwable $e) {
    }
    try {
        if ($tableHasColumn($conn, 'training_post_assignments', 'id')) {
            $conn->query("ALTER TABLE training_post_assignments MODIFY id INT NOT NULL AUTO_INCREMENT");
        }
    } catch (Throwable $e) {
    }
    try {
        $conn->query("ALTER TABLE training_post_assignments ADD PRIMARY KEY (id)");
    } catch (Throwable $e) {
    }
};

$ensurePostSchema($conn);

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
if ($programId <= 0) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Post Training</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Missing program</h1><p class="text-gray-600 mt-2">program_id is required.</p><div class="mt-4"><a class="btn btn-outline" href="trainingprogram.php">Back</a></div></div></div></body></html>';
    exit;
}

$program = null;
$submissionNo = 1;
try {
    $stmt = $conn->prepare("SELECT id, training_title, training_type, training_mode, target_audience, department_id, target_role, participants_needed, start_datetime, end_datetime, status, submission_no, need_budget, need_items, need_facility FROM training_programs WHERE id = ?");
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    if ($program && isset($program['submission_no'])) {
        $submissionNo = (int)$program['submission_no'];
        if ($submissionNo <= 0) $submissionNo = 1;
    }
} catch (Throwable $e) {
    $program = null;
}

if (!$program) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Post Training</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Not found</h1><p class="text-gray-600 mt-2">Training program not found.</p><div class="mt-4"><a class="btn btn-outline" href="trainingprogram.php">Back</a></div></div></div></body></html>';
    exit;
}

$status = (string)($program['status'] ?? '');
if (!in_array($status, ['Approved', 'POSTED'], true)) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Post Training</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Not ready</h1><p class="text-gray-600 mt-2">Only Approved training programs can be posted.</p><div class="mt-4"><a class="btn btn-outline" href="trainingprogram.php">Back</a></div></div></div></body></html>';
    exit;
}

$needBudget = (int)($program['need_budget'] ?? 0);
$needItems = (int)($program['need_items'] ?? 0);
$needFacility = (int)($program['need_facility'] ?? 0);

$reqOk = true;
try {
    if ($needBudget === 1) {
        $stmt = $conn->prepare("SELECT status FROM financial_requests WHERE program_id = ? AND submission_no = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ii', $programId, $submissionNo);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $reqOk = $reqOk && ($r && (string)$r['status'] === 'Approved');
    }
    if ($needItems === 1) {
        $stmt = $conn->prepare("SELECT status FROM logistics_requests WHERE program_id = ? AND submission_no = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ii', $programId, $submissionNo);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $reqOk = $reqOk && ($r && (string)$r['status'] === 'Approved');
    }
    if ($needFacility === 1) {
        $stmt = $conn->prepare("SELECT status FROM admin_requests WHERE program_id = ? AND submission_no = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ii', $programId, $submissionNo);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $reqOk = $reqOk && ($r && (string)$r['status'] === 'Approved');
    }
} catch (Throwable $e) {
    $reqOk = false;
}

if (!$reqOk) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Post Training</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-2xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Not settled</h1><p class="text-gray-600 mt-2">All required department requests must be Approved before posting.</p><div class="mt-4"><a class="btn btn-outline" href="trainingprogram.php">Back</a></div></div></div></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post') {
    header('Content-Type: application/json; charset=utf-8');

    $employeeIds = [];
    if (isset($_POST['employee_ids_json'])) {
        $decoded = json_decode((string)$_POST['employee_ids_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $id) {
                $eid = (int)$id;
                if ($eid > 0) $employeeIds[] = $eid;
            }
        }
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT IGNORE INTO training_posts (program_id, submission_no) VALUES (?, ?)");
        $stmt->bind_param('ii', $programId, $submissionNo);
        $stmt->execute();

        $stmtDel = $conn->prepare("DELETE FROM training_post_assignments WHERE program_id = ? AND submission_no = ?");
        $stmtDel->bind_param('ii', $programId, $submissionNo);
        $stmtDel->execute();

        if (!empty($employeeIds)) {
            $stmtIns = $conn->prepare("INSERT IGNORE INTO training_post_assignments (program_id, submission_no, employee_id) VALUES (?, ?, ?)");
            foreach ($employeeIds as $eid) {
                $stmtIns->bind_param('iii', $programId, $submissionNo, $eid);
                $stmtIns->execute();
            }
        }

        try {
            $stmtUpd = $conn->prepare("UPDATE training_programs SET status = 'POSTED' WHERE id = ?");
            $stmtUpd->bind_param('i', $programId);
            $stmtUpd->execute();
        } catch (Throwable $e) {
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        try {
            $conn->rollback();
        } catch (Throwable $e2) {
        }
        $err = $e->getMessage();
        $msg = (is_string($err) && trim($err) !== '') ? $err : 'Failed to post training.';
        echo json_encode(['success' => false, 'message' => $msg, 'error' => $err]);
        exit;
    }
}

$targetAudience = (string)($program['target_audience'] ?? '');
$deptId = isset($program['department_id']) ? (int)$program['department_id'] : 0;
$targetRole = trim((string)($program['target_role'] ?? ''));

$employees = [];
try {
    $sql = "SELECT id, employee_no, first_name, last_name, department, role FROM employees WHERE 1=1";
    $params = [];
    $types = '';

    $aud = trim($targetAudience);

    // add_training.php uses these values:
    // - By Department, Managers, Trainee, New Hires, Specific Employee, Mentor
    if ($aud === 'By Department' && $deptId > 0) {
        $deptMap = [
            1 => 'Front Office / Reception',
            2 => 'Housekeeping',
            3 => 'Food & Beverage (F&B)',
            4 => 'Kitchen / Culinary',
            5 => 'Sales & Marketing',
            6 => 'Human Resources (HR)',
            7 => 'Finance / Accounting',
            8 => 'Engineering / Maintenance',
            9 => 'Security'
        ];
        $deptName = $deptMap[$deptId] ?? '';

        // Legacy department grouping used in other training files / older DB dumps.
        $legacyDept = '';
        if (in_array($deptId, [1, 2], true)) $legacyDept = 'Hotel';
        elseif (in_array($deptId, [3, 4], true)) $legacyDept = 'Restaurant';
        elseif ($deptId === 6) $legacyDept = 'HR';
        elseif ($deptId === 7) $legacyDept = 'Financial';
        elseif ($deptId === 5) $legacyDept = 'Administrative';

        $deptCandidates = [];
        foreach ([$deptName, $legacyDept] as $v) {
            $v = trim((string)$v);
            if ($v !== '' && !in_array($v, $deptCandidates, true)) $deptCandidates[] = $v;
        }

        $hasDeptIdCol = false;
        try {
            $hasDeptIdCol = $tableHasColumn($conn, 'employees', 'department_id');
        } catch (Throwable $e) {
            $hasDeptIdCol = false;
        }

        if ($hasDeptIdCol && !empty($deptCandidates)) {
            $placeholders = implode(',', array_fill(0, count($deptCandidates), '?'));
            $sql .= " AND (department_id = ? OR department IN (" . $placeholders . "))";
            $types .= 'i' . str_repeat('s', count($deptCandidates));
            $params[] = $deptId;
            foreach ($deptCandidates as $d) $params[] = $d;
        } elseif ($hasDeptIdCol) {
            $sql .= " AND department_id = ?";
            $types .= 'i';
            $params[] = $deptId;
        } elseif (!empty($deptCandidates)) {
            $placeholders = implode(',', array_fill(0, count($deptCandidates), '?'));
            $sql .= " AND department IN (" . $placeholders . ")";
            $types .= str_repeat('s', count($deptCandidates));
            foreach ($deptCandidates as $d) $params[] = $d;
        }

        // If a role/position is set, narrow to that role.
        if ($targetRole !== '') {
            $sql .= " AND role = ?";
            $types .= 's';
            $params[] = $targetRole;
        }
    } elseif ($aud === 'Managers') {
        $sql .= " AND (role LIKE ? OR role LIKE ?)";
        $types .= 'ss';
        $params[] = '%Manager%';
        $params[] = '%Supervisor%';
    } elseif ($aud === 'Trainee') {
        $sql .= " AND role = ?";
        $types .= 's';
        $params[] = 'Trainee';
    } elseif ($aud === 'New Hires') {
        if ($tableHasColumn($conn, 'employees', 'employment_status')) {
            $sql .= " AND employment_status = ?";
            $types .= 's';
            $params[] = 'New Hire';
        }
    } elseif ($aud === 'Specific Employee') {
        $eid = (int)$targetRole;
        if ($eid > 0) {
            $sql .= " AND id = ?";
            $types .= 'i';
            $params[] = $eid;
        }
    }

    $sql .= " ORDER BY last_name, first_name";

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $employees[] = $row;
    }
} catch (Throwable $e) {
    $employees = [];
}

$assignedIds = [];
try {
    $stmt = $conn->prepare("SELECT employee_id FROM training_post_assignments WHERE program_id = ? AND submission_no = ?");
    $stmt->bind_param('ii', $programId, $submissionNo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $assignedIds[] = (int)($row['employee_id'] ?? 0);
    }
} catch (Throwable $e) {
    $assignedIds = [];
}
require('../../partials/header.php');
?>

    <script>
        (function () {
            if (!window.Swal || window.__SWAL_DAISY_PATCHED__) return;
            window.__SWAL_DAISY_PATCHED__ = true;
            const orig = window.Swal.fire.bind(window.Swal);
            window.Swal.fire = function (opts) {
                const inOpts = opts || {};
                const inCustom = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
                const customClass = {
                    popup: 'bg-base-100 text-base-content rounded-box',
                    title: 'text-base-content',
                    htmlContainer: 'text-base-content',
                    actions: 'flex gap-2',
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-ghost',
                    denyButton: 'btn btn-ghost',
                    ...(inCustom || {})
                };
                return orig({
                    returnFocus: false,
                    buttonsStyling: false,
                    ...inOpts,
                    customClass
                });
            };
        })();
    </script>
    <style>
        .swal2-container { z-index: 2147483647 !important; }
        .card-table thead { display: none; }
        .card-table tbody {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
        }
        @media (min-width: 768px) {
            .card-table tbody { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            .card-table tbody { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .card-table tbody tr {
            display: block;
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .card-table tbody tr.card-empty {
            grid-column: 1 / -1;
            text-align: center;
            color: #6b7280;
            padding: 2.25rem 1rem;
        }
        .card-table tbody tr.card-empty td { display: block; }
        .card-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.35rem 0;
            border: 0;
            background: transparent;
            white-space: normal;
        }
        .card-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #374151;
            flex: 0 0 auto;
        }
        .card-table td[data-label=""]::before { display: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-6xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Post Training</h1>
                    <p class="text-gray-600">Assign employees for: <?= htmlspecialchars((string)($program['training_title'] ?? '')) ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <a class="btn btn-ghost" href="trainingprogram.php">Back</a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <div class="bg-base-200 rounded-lg p-4">
                        <div class="font-semibold text-gray-800">Program Info</div>
                        <div class="text-sm text-gray-600 mt-2 space-y-1">
                            <div><span class="font-semibold">Audience:</span> <?= htmlspecialchars((string)$targetAudience) ?></div>
                            <div><span class="font-semibold">Role/Employee:</span> <?= htmlspecialchars((string)$targetRole) ?></div>
                            <div><span class="font-semibold">Participants Needed:</span> <?= (int)($program['participants_needed'] ?? 0) ?></div>
                            <div><span class="font-semibold">Schedule:</span> <?= htmlspecialchars((string)($program['start_datetime'] ?? '')) ?> - <?= htmlspecialchars((string)($program['end_datetime'] ?? '')) ?></div>
                        </div>
                    </div>

                    <button type="button" id="submit-post" class="btn btn-primary w-full mt-4">Post Training</button>
                    <div class="text-xs text-gray-500 mt-2">Selected employees will be assigned to this training posting.</div>
                </div>

                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="font-semibold text-gray-800">Employees</div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline" id="select-all">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline" id="select-none">Select None</button>
                        </div>
                    </div>

                    <div>
                        <table class="table card-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Employee No</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)) : ?>
                                    <tr class="card-empty"><td colspan="5">No employees found for this audience filter.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($employees as $e) : ?>
                                        <?php
                                            $eid = (int)($e['id'] ?? 0);
                                            $checked = in_array($eid, $assignedIds, true);
                                            $name = trim((string)($e['last_name'] ?? '')) . ', ' . trim((string)($e['first_name'] ?? ''));
                                        ?>
                                        <tr>
                                            <td data-label="">
                                                <input type="checkbox" class="checkbox" data-emp-id="<?= $eid ?>" <?= $checked ? 'checked' : '' ?>>
                                            </td>
                                            <td data-label="Name"><?= htmlspecialchars($name) ?></td>
                                            <td data-label="Employee No"><?= htmlspecialchars((string)($e['employee_no'] ?? '')) ?></td>
                                            <td data-label="Department"><?= htmlspecialchars((string)($e['department'] ?? '')) ?></td>
                                            <td data-label="Role"><?= htmlspecialchars((string)($e['role'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const btnPost = document.getElementById('submit-post');
            const selectAll = document.getElementById('select-all');
            const selectNone = document.getElementById('select-none');

            const getSelected = () => {
                const ids = [];
                document.querySelectorAll('input[type="checkbox"][data-emp-id]').forEach((cb) => {
                    if (cb.checked) ids.push(parseInt(cb.getAttribute('data-emp-id'), 10));
                });
                return ids.filter((n) => Number.isFinite(n) && n > 0);
            };

            if (selectAll) {
                selectAll.addEventListener('click', () => {
                    document.querySelectorAll('input[type="checkbox"][data-emp-id]').forEach((cb) => { cb.checked = true; });
                });
            }
            if (selectNone) {
                selectNone.addEventListener('click', () => {
                    document.querySelectorAll('input[type="checkbox"][data-emp-id]').forEach((cb) => { cb.checked = false; });
                });
            }

            if (btnPost) {
                btnPost.addEventListener('click', async () => {
                    const selected = getSelected();
                    const confirmRes = window.Swal ? await Swal.fire({
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-ghost'
                        },
                        icon: 'question',
                        title: 'Post Training?',
                        text: `Assign ${selected.length} employee(s) to this training?`,
                        showCancelButton: true,
                        confirmButtonText: 'Post',
                        cancelButtonText: 'Cancel'
                    }) : { isConfirmed: window.confirm('Post training?') };

                    if (!confirmRes || !confirmRes.isConfirmed) return;

                    const fd = new FormData();
                    fd.append('action', 'post');
                    fd.append('employee_ids_json', JSON.stringify(selected));

                    try {
                        const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
                        const data = await res.json();
                        if (!data || !data.success) {
                            if (window.Swal) Swal.fire({
                                buttonsStyling: false,
                                customClass: { confirmButton: 'btn btn-primary' },
                                icon: 'error',
                                title: 'Failed',
                                text: (data && (data.error || data.message)) ? String(data.error || data.message) : 'Unable to post.'
                            });
                            return;
                        }
                        if (window.Swal) await Swal.fire({
                            buttonsStyling: false,
                            customClass: { confirmButton: 'btn btn-primary' },
                            icon: 'success',
                            title: 'Posted',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        window.location.href = 'posted_trainings.php';
                    } catch (_) {
                        if (window.Swal) Swal.fire({
                            buttonsStyling: false,
                            customClass: { confirmButton: 'btn btn-primary' },
                            icon: 'error',
                            title: 'Failed',
                            text: 'Unexpected error.'
                        });
                    }
                });
            }
        })();
    </script>
 <?php require('../../partials/footer.php') ?>
