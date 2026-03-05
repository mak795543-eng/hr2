<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'get_idp_details') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idpId = (int)($_POST['idp_id'] ?? 0);
            if ($idpId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid idp_id']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT * FROM requested_idps_repository WHERE id = ? LIMIT 1");
            $stmt->execute([$idpId]);
            $row = $stmt->fetch();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Not found']);
                exit;
            }
            echo json_encode(['success' => true, 'idp' => $row]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
    } elseif ($action === 'list_idps') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $pdo->prepare(
                "SELECT id, employee_id, employee_name, department, position, competency, succession_status,
                        requested_training_type, requested_training_mode,
                        requested_start_datetime, requested_end_datetime, idp_status,
                        delivery_mode, training_requested_at
                 FROM requested_idps_repository
                 WHERE (delivery_mode IN ('Onsite','Hybrid') AND training_requested_at IS NOT NULL)
                 ORDER BY employee_name ASC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();

            try {
                require_once __DIR__ . '/db.php';
                if (isset($conn) && $conn instanceof mysqli) {
                    foreach ($rows as &$r) {
                        $currentStatus = (string)($r['idp_status'] ?? '');
                        $empName = trim((string)($r['employee_name'] ?? ''));
                        if ($empName === '') {
                            continue;
                        }

                        $title = 'IDP Training - ' . $empName;
                        $stmtTp = $conn->prepare(
                            "SELECT status FROM training_programs
                             WHERE training_title = ?
                             LIMIT 1"
                        );
                        if ($stmtTp) {
                            $stmtTp->bind_param('s', $title);
                            $stmtTp->execute();
                            $resTp = $stmtTp->get_result();
                            $tpRow = $resTp ? $resTp->fetch_assoc() : null;
                            if ($tpRow && isset($tpRow['status'])) {
                                $tpStatus = strtolower((string)$tpRow['status']);
                                $mapped = null;
                                if ($tpStatus === 'approved') {
                                    $mapped = 'approved';
                                } elseif ($tpStatus === 'under review') {
                                    $mapped = 'under_review';
                                } elseif ($tpStatus === 'for compliance') {
                                    $mapped = 'for_compliance';
                                } elseif ($tpStatus === 'on hold') {
                                    $mapped = 'on_hold';
                                } elseif ($tpStatus === 'rejected') {
                                    $mapped = 'rejected';
                                }

                                if ($mapped !== null && $mapped !== $currentStatus) {
                                    $upd = $pdo->prepare("UPDATE requested_idps_repository SET idp_status = ? WHERE id = ?");
                                    $upd->execute([$mapped, (int)$r['id']]);
                                    $r['idp_status'] = $mapped;
                                }
                            }
                        }
                    }
                    unset($r);
                }
            } catch (Throwable $e) {
            }

            echo json_encode(['success' => true, 'items' => $rows]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
    } elseif ($action === 'list_trainees') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            require_once __DIR__ . '/db.php';
            $rows = [];
            $sqlT = "SELECT id, employee_no, first_name, last_name, department, role FROM employees";
            try {
                $chk = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employees' AND column_name = 'employment_status' LIMIT 1");
                if ($chk && $chk->num_rows > 0) {
                    $sqlT .= " WHERE employment_status = 'New Hire'";
                }
            } catch (Throwable $e2) {
            }
            $sqlT .= " ORDER BY last_name, first_name";
            $resT = $conn->query($sqlT);
            if ($resT && $resT->num_rows > 0) {
                while ($row = $resT->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            echo json_encode(['success' => true, 'items' => $rows]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'items' => []]);
            exit;
        }
    }
}

require('../../partials/header.php');

function tr_build_url(array $overrides = []): string
{
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
            continue;
        }
        $params[$k] = $v;
    }
    $qs = http_build_query($params);
    return 'trainingrequest.php' . ($qs ? ('?' . $qs) : '');
}

$idpDepartments = [];
$idpStatusCounts = [
    'requested' => 0,
    'under_review' => 0,
    'approved' => 0,
    'on_hold' => 0,
    'for_compliance' => 0,
    'cancelled' => 0,
    'rejected' => 0
];
$idpBaseWhere = "(delivery_mode IN ('Onsite','Hybrid') AND training_requested_at IS NOT NULL)";
try {
    $stmt = $pdo->prepare("SELECT DISTINCT department FROM requested_idps_repository WHERE {$idpBaseWhere} ORDER BY department ASC");
    $stmt->execute();
    $idpDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $stmt = $pdo->prepare("SELECT idp_status, COUNT(*) c FROM requested_idps_repository WHERE {$idpBaseWhere} GROUP BY idp_status");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $k = (string)($row['idp_status'] ?? '');
        if ($k !== '' && array_key_exists($k, $idpStatusCounts)) {
            $idpStatusCounts[$k] = (int)($row['c'] ?? 0);
        }
    }
} catch (Throwable $e) {
}

$traineeHasEmploymentStatus = false;
$traineeDepartments = [];
$traineeStatuses = [];
$traineeDeptCounts = [];
$traineeTotal = 0;
try {
    require_once __DIR__ . '/db.php';
    try {
        $chk = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employees' AND column_name = 'employment_status' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $traineeHasEmploymentStatus = true;
        }
    } catch (Throwable $e2) {
    }

    $tBaseWhere = [];
    if ($traineeHasEmploymentStatus) {
        $tBaseWhere[] = "employment_status = 'New Hire'";
    }
    $tWhereSql = $tBaseWhere ? ('WHERE ' . implode(' AND ', $tBaseWhere)) : '';

    $res = $conn->query("SELECT COUNT(*) c FROM employees {$tWhereSql}");
    if ($res) {
        $r = $res->fetch_assoc();
        $traineeTotal = (int)($r['c'] ?? 0);
    }

    $res = $conn->query("SELECT department, COUNT(*) c FROM employees {$tWhereSql} GROUP BY department ORDER BY c DESC, department ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $dept = (string)($r['department'] ?? '');
            if ($dept === '') continue;
            $traineeDeptCounts[$dept] = (int)($r['c'] ?? 0);
        }
    }
    $traineeDepartments = array_keys($traineeDeptCounts);

    if ($traineeHasEmploymentStatus) {
        $res = $conn->query("SELECT DISTINCT employment_status FROM employees ORDER BY employment_status ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $st = (string)($r['employment_status'] ?? '');
                if ($st !== '') $traineeStatuses[] = $st;
            }
        }
    }
} catch (Throwable $e) {
}
?>

<body class="bg-gray-50 min-h-screen">
    <style>
        .btn-border {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
            transition: all 0.2s ease-in-out;
        }

        .btn-border:hover {
            background-color: #f9fafb;
            border-color: #9ca3af;
        }

        .btn-border:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
        }

        .card-table thead {
            display: none;
        }

        .card-table tbody {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .card-table tbody {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .card-table tbody {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .card-table tbody tr {
            display: block;
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .card-table tbody tr.card-empty {
            grid-column: 1 / -1;
            text-align: center;
            color: #6b7280;
            padding: 2.25rem 1rem;
        }

        .card-table tbody tr.card-empty td {
            display: block;
        }

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

        .card-table td[data-label="Action"] {
            display: block;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid #eef2f7;
        }

        .card-table td[data-label="Action"]::before {
            display: none;
        }
    </style>
    <div class="flex h-screen">
        <?php include '../../USM/sidebarr.php'; ?>
        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../../USM/navbar.php'; ?>
            <main class="container mx-auto px-4 py-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold mb-2 text-gray-800">Training Requests</h1>
                        <p class="text-gray-600">Manage IDP and trainee training requests.</p>
                    </div>
                </div>

                <div class="mb-6 bg-white rounded-xl shadow-md w-full">
                    <div class="flex items-center gap-2 p-3">
                        <button type="button" id="tab-page-idp" class="btn btn-sm btn-active">IDP Requests</button>
                        <button type="button" id="tab-page-trainee" class="btn btn-sm btn-ghost">Trainee Requests</button>
                    </div>
                </div>

                <div id="idp-requests-section" class="w-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <a href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'idp', 'status' => 'under_review'])); ?>" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Under Review</div>
                                    <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$idpStatusCounts['under_review']; ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Pending requests</div>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-full">
                                    <i data-lucide="hourglass" class="h-6 w-6 text-blue-600"></i>
                                </div>
                            </div>
                        </a>
                        <a href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'idp', 'status' => 'approved'])); ?>" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved</div>
                                    <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$idpStatusCounts['approved']; ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Ready to create training</div>
                                </div>
                                <div class="p-3 bg-green-100 rounded-full">
                                    <i data-lucide="check-circle" class="h-6 w-6 text-green-600"></i>
                                </div>
                            </div>
                        </a>
                        <a href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'idp', 'status' => 'for_compliance'])); ?>" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">For Compliance</div>
                                    <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$idpStatusCounts['for_compliance']; ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Needs updates</div>
                                </div>
                                <div class="p-3 bg-yellow-100 rounded-full">
                                    <i data-lucide="clipboard-check" class="h-6 w-6 text-yellow-600"></i>
                                </div>
                            </div>
                        </a>
                        <a href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'idp', 'status' => 'rejected'])); ?>" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rejected</div>
                                    <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$idpStatusCounts['rejected']; ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Requires revision</div>
                                </div>
                                <div class="p-3 bg-purple-100 rounded-full">
                                    <i data-lucide="x-circle" class="h-6 w-6 text-purple-600"></i>
                                </div>
                            </div>
                        </a>
                    </div>

                    <form method="get" class="bg-white p-4 rounded-lg shadow-sm mb-6">
                        <input type="hidden" name="tab" value="idp" />
                        <div class="flex flex-wrap gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Status</span></label>
                                <select name="status" class="select select-bordered w-48">
                                    <?php
                                    $allowedStatuses = ['all', 'requested', 'under_review', 'approved', 'on_hold', 'for_compliance', 'cancelled', 'rejected'];
                                    $status = (string)($_GET['status'] ?? 'all');
                                    if (!in_array($status, $allowedStatuses, true)) $status = 'all';
                                    foreach ($allowedStatuses as $st) {
                                        $sel = $status === $st ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($st) . '" ' . $sel . '>' . htmlspecialchars(ucwords(str_replace('_', ' ', $st))) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Department</span></label>
                                <select name="department" class="select select-bordered w-56">
                                    <?php
                                    $deptVal = (string)($_GET['department'] ?? 'all');
                                    echo '<option value="all"' . ($deptVal === 'all' ? ' selected' : '') . '>All Departments</option>';
                                    foreach ($idpDepartments as $d) {
                                        $d = (string)$d;
                                        $sel = $deptVal === $d ? ' selected' : '';
                                        echo '<option value="' . htmlspecialchars($d) . '"' . $sel . '>' . htmlspecialchars($d) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Search</span></label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars((string)($_GET['search'] ?? '')); ?>" class="input input-bordered w-64" placeholder="Employee name or ID" />
                            </div>
                            <div class="form-control self-end">
                                <button type="submit" class="btn btn-border"><i data-lucide="filter" class="w-4 h-4 mr-2"></i>Apply Filters</button>
                            </div>
                            <div class="form-control self-end">
                                <a class="btn btn-border" href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'idp', 'status' => null, 'department' => null, 'search' => null])); ?>"><i data-lucide="x" class="w-4 h-4 mr-2"></i>Clear</a>
                            </div>
                        </div>
                    </form>

                    <div class="bg-white rounded-xl shadow-md p-4">
                        <table class="table w-full card-table">
                            <thead>
                                <tr>
                                    <th>Employee (ID)</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $deptFilter = trim((string)($_GET['department'] ?? 'all'));
                                    $statusFilter = trim((string)($_GET['status'] ?? 'all'));
                                    $searchFilter = trim((string)($_GET['search'] ?? ''));
                                    $sql = "SELECT id, employee_id, employee_name, department, position, competency, succession_status,
                                                       requested_training_type, requested_training_mode,
                                                       requested_start_datetime, requested_end_datetime, idp_status,
                                                       delivery_mode, training_requested_at
                                                FROM requested_idps_repository";
                                    $conds = [];
                                    $params = [];
                                    if ($deptFilter !== '' && $deptFilter !== 'all') {
                                        $conds[] = "department = ?";
                                        $params[] = $deptFilter;
                                    }
                                    if ($statusFilter !== '' && $statusFilter !== 'all') {
                                        $conds[] = "idp_status = ?";
                                        $params[] = $statusFilter;
                                    }
                                    if ($searchFilter !== '') {
                                        $conds[] = "(employee_name LIKE ? OR employee_id LIKE ?)";
                                        $params[] = '%' . $searchFilter . '%';
                                        $params[] = '%' . $searchFilter . '%';
                                    }
                                    // Only show Onsite/Hybrid requests that have a training request timestamp
                                    $conds[] = "(delivery_mode IN ('Onsite','Hybrid') AND training_requested_at IS NOT NULL)";
                                    if (!empty($conds)) {
                                        $sql .= " WHERE " . implode(" AND ", $conds);
                                    }
                                    $sql .= " ORDER BY employee_name ASC";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute($params);
                                    $rows = $stmt->fetchAll();
                                    if (!$rows) {
                                        echo '<tr class="card-empty"><td colspan="5">No requested IDPs found.</td></tr>';
                                    } else {
                                        foreach ($rows as $r) {
                                            $idpJson = htmlspecialchars(json_encode($r, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
                                            $prefillUrl = 'add_training.php?idp_id=' . urlencode((string)$r['id']);
                                            echo '<tr>';
                                            echo '<td data-label="Employee (ID)"><div class="font-semibold">' . htmlspecialchars((string)$r['employee_name']) . '</div><div class="text-xs opacity-70">' . htmlspecialchars((string)$r['employee_id']) . '</div></td>';
                                            echo '<td data-label="Department">' . htmlspecialchars((string)$r['department']) . '</td>';
                                            echo '<td data-label="Role">' . htmlspecialchars((string)$r['position']) . '</td>';
                                            echo '<td data-label="Status"><span class="badge">' . htmlspecialchars((string)$r['idp_status']) . '</span></td>';
                                            echo '<td data-label="Action" class="whitespace-nowrap">';
                                            echo '<button type="button" class="btn btn-sm btn-outline mr-2" data-view-idp="1" data-idp="' . $idpJson . '">View</button>';
                                            echo '<a href="' . $prefillUrl . '" class="btn btn-sm bg-gray-900 text-white hover:bg-gray-800 border-0">';
                                            echo '<i data-lucide="plus" class="w-4 h-4 mr-2"></i>Create Training</a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                } catch (Throwable $e) {
                                    echo '<tr class="card-empty"><td colspan="5">Failed to load requests.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="trainee-requests-section" class="hidden w-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <a href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'trainee', 't_department' => 'all'])); ?>" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Trainees</div>
                                    <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$traineeTotal; ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Available requests</div>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-full">
                                    <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                                </div>
                            </div>
                        </a>
                        <?php
                        $topDept = array_slice($traineeDeptCounts, 0, 3, true);
                        $deptIcons = [
                            ['bg' => 'bg-green-100', 'icon' => 'building-2', 'color' => 'text-green-600'],
                            ['bg' => 'bg-yellow-100', 'icon' => 'building', 'color' => 'text-yellow-600'],
                            ['bg' => 'bg-purple-100', 'icon' => 'briefcase', 'color' => 'text-purple-600'],
                        ];
                        $i = 0;
                        foreach ($topDept as $deptName => $cnt) {
                            $di = $deptIcons[$i] ?? $deptIcons[0];
                            echo '<a href="' . htmlspecialchars(tr_build_url(['tab' => 'trainee', 't_department' => $deptName])) . '" class="hr2-summary-card rounded-xl shadow-md p-6 bg-white block">';
                            echo '<div class="flex items-start justify-between">';
                            echo '<div>';
                            echo '<div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">' . htmlspecialchars($deptName) . '</div>';
                            echo '<div class="mt-1 text-3xl font-bold text-gray-900">' . (int)$cnt . '</div>';
                            echo '<div class="text-xs text-gray-400 mt-1">Trainees</div>';
                            echo '</div>';
                            echo '<div class="p-3 ' . $di['bg'] . ' rounded-full">';
                            echo '<i data-lucide="' . $di['icon'] . '" class="h-6 w-6 ' . $di['color'] . '"></i>';
                            echo '</div>';
                            echo '</div>';
                            echo '</a>';
                            $i++;
                        }
                        ?>
                    </div>

                    <form method="get" class="bg-white p-4 rounded-lg shadow-sm mb-6">
                        <input type="hidden" name="tab" value="trainee" />
                        <div class="flex flex-wrap gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Status</span></label>
                                <select name="t_status" class="select select-bordered w-56" <?php echo $traineeHasEmploymentStatus ? '' : 'disabled'; ?>>
                                    <?php
                                    $tStatus = (string)($_GET['t_status'] ?? ($traineeHasEmploymentStatus ? 'New Hire' : 'all'));
                                    if (!$traineeHasEmploymentStatus) {
                                        echo '<option value="all" selected>Trainee</option>';
                                    } else {
                                        echo '<option value="all"' . ($tStatus === 'all' ? ' selected' : '') . '>All Status</option>';
                                        foreach ($traineeStatuses as $st) {
                                            $sel = $tStatus === $st ? ' selected' : '';
                                            echo '<option value="' . htmlspecialchars($st) . '"' . $sel . '>' . htmlspecialchars($st) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Department</span></label>
                                <select name="t_department" class="select select-bordered w-56">
                                    <?php
                                    $tDeptVal = (string)($_GET['t_department'] ?? 'all');
                                    echo '<option value="all"' . ($tDeptVal === 'all' ? ' selected' : '') . '>All Departments</option>';
                                    foreach ($traineeDepartments as $d) {
                                        $sel = $tDeptVal === $d ? ' selected' : '';
                                        echo '<option value="' . htmlspecialchars($d) . '"' . $sel . '>' . htmlspecialchars($d) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium">Search</span></label>
                                <input type="text" name="t_search" value="<?php echo htmlspecialchars((string)($_GET['t_search'] ?? '')); ?>" class="input input-bordered w-64" placeholder="Employee name or ID" />
                            </div>
                            <div class="form-control self-end">
                                <button type="submit" class="btn btn-border"><i data-lucide="filter" class="w-4 h-4 mr-2"></i>Apply Filters</button>
                            </div>
                            <div class="form-control self-end">
                                <a class="btn btn-border" href="<?php echo htmlspecialchars(tr_build_url(['tab' => 'trainee', 't_status' => null, 't_department' => null, 't_search' => null])); ?>"><i data-lucide="x" class="w-4 h-4 mr-2"></i>Clear</a>
                            </div>
                        </div>
                    </form>

                    <div class="bg-white rounded-xl shadow-md p-4">
                        <table class="table w-full card-table">
                            <thead>
                                <tr>
                                    <th>Employee (ID)</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    require_once __DIR__ . '/db.php';
                                    $hasEmploymentStatus = $traineeHasEmploymentStatus;
                                    $tStatusFilter = (string)($_GET['t_status'] ?? ($hasEmploymentStatus ? 'New Hire' : 'all'));
                                    $tDeptFilter = trim((string)($_GET['t_department'] ?? 'all'));
                                    $tSearchFilter = trim((string)($_GET['t_search'] ?? ''));

                                    $condsT = [];
                                    if ($hasEmploymentStatus && $tStatusFilter !== '' && $tStatusFilter !== 'all') {
                                        $condsT[] = "employment_status = ?";
                                    }
                                    if ($tDeptFilter !== '' && $tDeptFilter !== 'all') {
                                        $condsT[] = "department = ?";
                                    }
                                    if ($tSearchFilter !== '') {
                                        $condsT[] = "(CONCAT(last_name, ', ', first_name) LIKE ? OR employee_no LIKE ?)";
                                    }
                                    $sqlT = "SELECT id, employee_no, first_name, last_name, department, role" . ($hasEmploymentStatus ? ", employment_status" : "") . " FROM employees";
                                    $paramsT = [];
                                    if ($condsT) {
                                        $sqlT .= " WHERE " . implode(" AND ", $condsT);
                                        if ($hasEmploymentStatus && $tStatusFilter !== '' && $tStatusFilter !== 'all') $paramsT[] = $tStatusFilter;
                                        if ($tDeptFilter !== '' && $tDeptFilter !== 'all') $paramsT[] = $tDeptFilter;
                                        if ($tSearchFilter !== '') {
                                            $paramsT[] = '%' . $tSearchFilter . '%';
                                            $paramsT[] = '%' . $tSearchFilter . '%';
                                        }
                                    }
                                    $sqlT .= " ORDER BY last_name, first_name";

                                    if (!empty($paramsT)) {
                                        $stmtT = $conn->prepare($sqlT);
                                        $types = str_repeat('s', count($paramsT));
                                        $stmtT->bind_param($types, ...$paramsT);
                                        $stmtT->execute();
                                        $resT = $stmtT->get_result();
                                    } else {
                                        $resT = $conn->query($sqlT);
                                    }
                                    if ($resT && $resT->num_rows > 0) {
                                        while ($rowT = $resT->fetch_assoc()) {
                                            $fullName = trim((string)($rowT['last_name'] ?? '') . ', ' . (string)($rowT['first_name'] ?? ''));
                                            $empNo = (string)($rowT['employee_no'] ?? '');
                                            $dept = (string)($rowT['department'] ?? '');
                                            $role = (string)($rowT['role'] ?? '');
                                            $statusLabel = $hasEmploymentStatus ? (string)($rowT['employment_status'] ?? '') : 'Trainee';
                                            $empId = (int)($rowT['id'] ?? 0);
                                            $empJson = htmlspecialchars(json_encode([
                                                'id' => $empId,
                                                'employee_no' => $empNo,
                                                'employee_name' => $fullName,
                                                'department' => $dept,
                                                'role' => $role,
                                                'employment_status' => $statusLabel,
                                            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
                                            $createUrl = 'add_training.php?trainee_id=' . urlencode((string)$empId);
                                            echo '<tr>';
                                            echo '<td data-label="Employee (ID)"><div class="font-semibold">' . htmlspecialchars($fullName) . '</div><div class="text-xs opacity-70">' . htmlspecialchars($empNo) . '</div></td>';
                                            echo '<td data-label="Department">' . htmlspecialchars($dept) . '</td>';
                                            echo '<td data-label="Role">' . htmlspecialchars($role) . '</td>';
                                            echo '<td data-label="Status"><span class="badge">' . htmlspecialchars($statusLabel) . '</span></td>';
                                            echo '<td data-label="Action" class="whitespace-nowrap">';
                                            echo '<button type="button" class="btn btn-sm btn-outline mr-2" data-view-trainee="1" data-trainee="' . $empJson . '">View</button>';
                                            echo '<a href="' . $createUrl . '" class="btn btn-sm bg-gray-900 text-white hover:bg-gray-800 border-0">';
                                            echo '<i data-lucide="plus" class="w-4 h-4 mr-2"></i>Create Training</a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr class="card-empty"><td colspan="5">No trainee records found.</td></tr>';
                                    }
                                } catch (Throwable $e) {
                                    echo '<tr class="card-empty"><td colspan="5">Failed to load trainees.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <dialog id="view-idp-modal" class="modal">
                <div class="modal-box w-11/12 max-w-4xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold" id="view-idp-title">Request Details</h2>
                            <div class="text-sm text-gray-600 mt-1" id="view-idp-subtitle"></div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" data-close-modal="view-idp-modal">✕</button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" id="view-idp-body"></div>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
            </dialog>
            <dialog id="view-trainee-modal" class="modal">
                <div class="modal-box w-11/12 max-w-3xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold" id="view-trainee-title">Trainee Details</h2>
                            <div class="text-sm text-gray-600 mt-1" id="view-trainee-subtitle"></div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" data-close-modal="view-trainee-modal">✕</button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" id="view-trainee-body"></div>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
            </dialog>
        </div>
    </div>
    <script>
        if (window.lucide) lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            var tabIdp = document.getElementById('tab-page-idp');
            var tabTrainee = document.getElementById('tab-page-trainee');
            var idpSection = document.getElementById('idp-requests-section');
            var traineeSection = document.getElementById('trainee-requests-section');
            var viewIdpModal = document.getElementById('view-idp-modal');
            var viewIdpTitle = document.getElementById('view-idp-title');
            var viewIdpSubtitle = document.getElementById('view-idp-subtitle');
            var viewIdpBody = document.getElementById('view-idp-body');
            var viewTraineeModal = document.getElementById('view-trainee-modal');
            var viewTraineeSubtitle = document.getElementById('view-trainee-subtitle');
            var viewTraineeBody = document.getElementById('view-trainee-body');

            function esc(s) {
                return String(s || '').replace(/[&<>"']/g, function(c) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[c];
                });
            }

            function renderInfoCard(label, value) {
                return '<div class="bg-base-200 rounded-lg p-3">' +
                    '<div class="text-xs opacity-70">' + esc(label) + '</div>' +
                    '<div class="font-semibold mt-1 break-words">' + esc(value || '—') + '</div>' +
                    '</div>';
            }

            function setTab(tab) {
                var isIdp = tab === 'idp';
                if (tabIdp) {
                    if (isIdp) {
                        tabIdp.classList.add('btn-active');
                        tabIdp.classList.remove('btn-ghost');
                    } else {
                        tabIdp.classList.remove('btn-active');
                        tabIdp.classList.add('btn-ghost');
                    }
                }
                if (tabTrainee) {
                    if (!isIdp) {
                        tabTrainee.classList.add('btn-active');
                        tabTrainee.classList.remove('btn-ghost');
                    } else {
                        tabTrainee.classList.remove('btn-active');
                        tabTrainee.classList.add('btn-ghost');
                    }
                }
                if (idpSection) idpSection.classList.toggle('hidden', !isIdp);
                if (traineeSection) traineeSection.classList.toggle('hidden', isIdp);

                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', isIdp ? 'idp' : 'trainee');
                    window.history.replaceState({}, '', url.toString());
                } catch (_) {}
            }

            if (tabIdp) {
                tabIdp.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTab('idp');
                });
            }
            if (tabTrainee) {
                tabTrainee.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTab('trainee');
                });
            }
            var initialTab = 'idp';
            try {
                var t0 = new URLSearchParams(window.location.search).get('tab');
                if (t0 && String(t0).toLowerCase() === 'trainee') initialTab = 'trainee';
            } catch (_) {}
            setTab(initialTab);

            document.addEventListener('click', function(e) {
                var t = e.target;
                if (!t) return;
                var closeBtn = t.closest('button[data-close-modal]');
                if (closeBtn) {
                    e.preventDefault();
                    var mid = closeBtn.getAttribute('data-close-modal') || '';
                    var modal = mid ? document.getElementById(mid) : null;
                    if (modal) modal.close();
                    return;
                }

                var btnViewIdp = t.closest('button[data-view-idp="1"]');
                if (btnViewIdp) {
                    e.preventDefault();
                    var raw = btnViewIdp.getAttribute('data-idp') || '';
                    try {
                        var r = JSON.parse(raw);
                        if (viewIdpTitle) viewIdpTitle.textContent = 'Request Details';
                        if (viewIdpSubtitle) viewIdpSubtitle.textContent = String(r.employee_name || '') + ' (' + String(r.employee_id || '') + ')';
                        if (viewIdpBody) {
                            viewIdpBody.innerHTML = [
                                renderInfoCard('Department', r.department),
                                renderInfoCard('Role', r.position),
                                renderInfoCard('Status', r.idp_status),
                                renderInfoCard('Delivery Mode', r.delivery_mode),
                                renderInfoCard('Requested Training Type', r.requested_training_type),
                                renderInfoCard('Requested Training Mode', r.requested_training_mode),
                                renderInfoCard('Requested Start', r.requested_start_datetime),
                                renderInfoCard('Requested End', r.requested_end_datetime),
                            ].join('');
                        }
                        if (viewIdpModal && typeof viewIdpModal.showModal === 'function') {
                            viewIdpModal.showModal();
                        }
                    } catch (_) {}
                    return;
                }

                var btnViewTrainee = t.closest('button[data-view-trainee="1"]');
                if (btnViewTrainee) {
                    e.preventDefault();
                    var rawT = btnViewTrainee.getAttribute('data-trainee') || '';
                    try {
                        var tr = JSON.parse(rawT);
                        if (viewTraineeSubtitle) viewTraineeSubtitle.textContent = String(tr.employee_name || '') + ' (' + String(tr.employee_no || '') + ')';
                        if (viewTraineeBody) {
                            viewTraineeBody.innerHTML = [
                                renderInfoCard('Employee No', tr.employee_no),
                                renderInfoCard('Department', tr.department),
                                renderInfoCard('Role', tr.role),
                                renderInfoCard('Status', tr.employment_status),
                            ].join('');
                        }
                        if (viewTraineeModal && typeof viewTraineeModal.showModal === 'function') {
                            viewTraineeModal.showModal();
                        }
                    } catch (_) {}
                }
            });
        });
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>

</html>