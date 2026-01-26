<?php
session_start();
require_once __DIR__ . '/db.php';

$tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
};

$ensureSchema = function(mysqli $conn) use ($tableHasColumn): void {
    try {
        if (!$tableHasColumn($conn, 'employees', 'employment_status')) {
            $conn->query("ALTER TABLE employees ADD COLUMN employment_status VARCHAR(50) NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS trainee_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            applied_department VARCHAR(100) NOT NULL,
            applied_position VARCHAR(150) NOT NULL,
            status ENUM('Pending','Sent') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_tr_employee (employee_id),
            INDEX idx_tr_status (status),
            INDEX idx_tr_created (created_at),
            CONSTRAINT fk_tr_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS training_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            trainee_request_id INT NOT NULL,
            employee_id INT NOT NULL,
            applied_department VARCHAR(100) NOT NULL,
            applied_position VARCHAR(150) NOT NULL,
            status ENUM('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_treq_tr (trainee_request_id),
            INDEX idx_treq_employee (employee_id),
            INDEX idx_treq_status (status),
            CONSTRAINT fk_treq_tr FOREIGN KEY (trainee_request_id) REFERENCES trainee_requests(id) ON DELETE CASCADE,
            CONSTRAINT fk_treq_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
};

$ensureSchema($conn);

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

$filterDepartment = trim((string)($_GET['department'] ?? ''));
$filterPosition = trim((string)($_GET['position'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_idp_details') {
    header('Content-Type: application/json; charset=utf-8');

    $idpId = (int)($_POST['idp_id'] ?? 0);
    if ($idpId <= 0 || !$cgPdo) {
        echo json_encode(['success' => false, 'message' => $cgPdo ? 'Missing request.' : 'Critical gaps database connection is not available.']);
        exit;
    }

    try {
        $stmt = $cgPdo->prepare(
            "SELECT idp.id,
                    idp.employee_id,
                    idp.employee_name,
                    idp.position,
                    idp.department,
                    COALESCE(gs.competency, 0) AS competency,
                    idp.succession_status,
                    development_plan, target_score, target_date,
                    requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                    idp_status, delivery_mode, training_requested_at
             FROM requested_idps_repository idp
             LEFT JOIN (
                 SELECT idp2.employee_id,
                        idp2.department,
                        AVG(COALESCE(es2.skill_score, 0)) AS competency
                 FROM requested_idps_repository idp2
                 JOIN skills s2
                   ON s2.category = 'General Skills'
                  AND s2.department = idp2.department
                 LEFT JOIN employee_skills es2
                   ON es2.employee_id = idp2.employee_id
                  AND es2.skill_id = s2.id
                 GROUP BY idp2.employee_id, idp2.department
             ) gs ON gs.employee_id = idp.employee_id AND gs.department = idp.department
             WHERE idp.id = ?
             LIMIT 1"
        );
        $stmt->execute([$idpId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
         if (!$row) {
             echo json_encode(['success' => false, 'message' => 'Request not found.']);
             exit;
         }
         echo json_encode(['success' => true, 'idp' => $row]);
         exit;
     } catch (Throwable $e) {
         echo json_encode(['success' => false, 'message' => 'Failed to load request.']);
         exit;
     }
 }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_group_members') {
    header('Content-Type: application/json; charset=utf-8');

    $dept = trim((string)($_POST['applied_department'] ?? ''));
    $pos = trim((string)($_POST['applied_position'] ?? ''));
    if ($dept === '' || $pos === '') {
        echo json_encode(['success' => false, 'message' => 'Missing department/position.']);
        exit;
    }

    $members = [];
    $total = 0;
    try {
        $stmtCnt = $conn->prepare("SELECT COUNT(*) AS c FROM training_requests WHERE status = 'Pending' AND applied_department = ? AND applied_position = ?");
        $stmtCnt->bind_param('ss', $dept, $pos);
        $stmtCnt->execute();
        $total = (int)($stmtCnt->get_result()->fetch_assoc()['c'] ?? 0);

        $stmt = $conn->prepare("SELECT trq.id AS training_request_id, trq.created_at,
            e.employee_no, e.first_name, e.last_name, e.employment_status, e.role
            FROM training_requests trq
            JOIN employees e ON e.id = trq.employee_id
            WHERE trq.status = 'Pending' AND trq.applied_department = ? AND trq.applied_position = ?
            ORDER BY trq.created_at ASC
            LIMIT 50");
        $stmt->bind_param('ss', $dept, $pos);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load group members.']);
        exit;
    }

    echo json_encode(['success' => true, 'total' => $total, 'members' => $members]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_group_batch') {
    header('Content-Type: application/json; charset=utf-8');

    $dept = trim((string)($_POST['applied_department'] ?? ''));
    $pos = trim((string)($_POST['applied_position'] ?? ''));
    if ($dept === '' || $pos === '') {
        echo json_encode(['success' => false, 'message' => 'Missing department/position.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id AS training_request_id
            FROM training_requests
            WHERE status = 'Pending' AND applied_department = ? AND applied_position = ?
            ORDER BY created_at ASC
            LIMIT 10");
        $stmt->bind_param('ss', $dept, $pos);
        $stmt->execute();
        $ids = [];
        while ($row = $stmt->get_result()->fetch_assoc()) {
            $ids[] = (int)($row['training_request_id'] ?? 0);
        }

        if (count($ids) < 10) {
            echo json_encode(['success' => false, 'message' => 'Need at least 10 trainees in this group to create a training program.']);
            exit;
        }

        $_SESSION['training_request_batch'] = [
            'applied_department' => $dept,
            'applied_position' => $pos,
            'training_request_ids' => $ids,
            'reserved_at' => date('Y-m-d H:i:s')
        ];

        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare batch.']);
        exit;
    }
}

$groups = [];
try {
    $sql = "SELECT applied_department, applied_position, COUNT(*) AS pending_count, MIN(created_at) AS oldest_created
        FROM training_requests
        WHERE status = 'Pending'";
    $types = '';
    $params = [];
    if ($filterDepartment !== '') {
        $sql .= " AND applied_department = ?";
        $types .= 's';
        $params[] = $filterDepartment;
    }
    if ($filterPosition !== '') {
        $sql .= " AND applied_position = ?";
        $types .= 's';
        $params[] = $filterPosition;
    }
    $sql .= " GROUP BY applied_department, applied_position ORDER BY oldest_created ASC";

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    $groups = [];
}

 $idpRequests = [];
 if ($cgPdo) {
     try {
         $stmt = $cgPdo->query(
             "SELECT idp.id,
                    idp.employee_id,
                    idp.employee_name,
                    idp.position,
                    idp.department,
                    COALESCE(gs.competency, 0) AS competency,
                    idp.succession_status,
                    requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                    idp_status, delivery_mode, training_requested_at
             FROM requested_idps_repository idp
             LEFT JOIN (
                 SELECT idp2.employee_id,
                        idp2.department,
                        AVG(COALESCE(es2.skill_score, 0)) AS competency
                 FROM requested_idps_repository idp2
                 JOIN skills s2
                   ON s2.category = 'General Skills'
                  AND s2.department = idp2.department
                 LEFT JOIN employee_skills es2
                   ON es2.employee_id = idp2.employee_id
                  AND es2.skill_id = s2.id
                 GROUP BY idp2.employee_id, idp2.department
             ) gs ON gs.employee_id = idp.employee_id AND gs.department = idp.department
             WHERE idp.training_requested_at IS NOT NULL
               AND idp.idp_status = 'requested'
               AND idp.delivery_mode IN ('Onsite','Hybrid')
             ORDER BY idp.training_requested_at DESC"
         );
         $idpRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
     } catch (Throwable $e) {
         $idpRequests = [];
     }
 }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .card-table td[data-label="Actions"] {
            display: block;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid #eef2f7;
        }
        .card-table td[data-label="Actions"]::before { display: none; }
    </style>
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
        <main class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Training Requests</h1>
                    <p class="text-sm text-gray-500">Trainees sent from Trainee Requests and IDP requests</p>
                </div>
                <div class="flex gap-2">
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-4 mb-4">
                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Department</span></label>
                        <select name="department" id="filter-department" class="select select-bordered">
                            <option value="">All</option>
                            <option value="Hotel" <?= $filterDepartment === 'Hotel' ? 'selected' : '' ?>>Hotel</option>
                            <option value="Restaurant" <?= $filterDepartment === 'Restaurant' ? 'selected' : '' ?>>Restaurant</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Applied Position</span></label>
                        <select name="position" id="filter-position" class="select select-bordered">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="trainingrequest.php" class="btn">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md p-4 mb-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">IDP Training Requests</h2>
                        <p class="text-xs text-gray-500">Requested trainings from Individual Development Plans</p>
                    </div>
                </div>

                <table class="table card-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($idpRequests)): ?>
                            <tr class="card-empty"><td colspan="7">No IDP training requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($idpRequests as $i => $r): ?>
                                <?php
                                    $idpReqId = (int)($r['id'] ?? 0);
                                    $idpEmpName = (string)($r['employee_name'] ?? '');
                                    $idpEmployeeId = (string)($r['employee_id'] ?? '');
                                    $idpDept = (string)($r['department'] ?? '');
                                    $idpPos = (string)($r['position'] ?? '');
                                    $idpReqAt = (string)($r['training_requested_at'] ?? '');
                                ?>
                                <tr>
                                    <td data-label="#"><?= (int)($i + 1) ?></td>
                                    <td data-label="Employee"><?= htmlspecialchars($idpEmpName) ?></td>
                                    <td data-label="Employee ID"><?= htmlspecialchars($idpEmployeeId) ?></td>
                                    <td data-label="Department"><?= htmlspecialchars($idpDept) ?></td>
                                    <td data-label="Position"><?= htmlspecialchars($idpPos) ?></td>
                                    <td data-label="Requested"><?= htmlspecialchars($idpReqAt) ?></td>
                                    <td data-label="Actions">
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-outline btn-xs" data-action="view-idp" data-idp-id="<?= htmlspecialchars((string)$idpReqId) ?>">View</button>
                                            <button type="button" class="btn btn-primary btn-xs" data-action="create-idp-program" data-idp-id="<?= htmlspecialchars((string)$idpReqId) ?>">Create Training Program</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-md p-4">
                <table class="table card-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Applied Department</th>
                            <th>Applied Position</th>
                            <th>Pending</th>
                            <th>Ready Batches</th>
                            <th>Oldest</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($groups)): ?>
                            <tr class="card-empty"><td colspan="7">No training requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($groups as $i => $g): ?>
                                <?php
                                    $dept = (string)($g['applied_department'] ?? '');
                                    $pos = (string)($g['applied_position'] ?? '');
                                    $cnt = (int)($g['pending_count'] ?? 0);
                                    $readyBatches = (int)floor($cnt / 10);
                                    $oldest = (string)($g['oldest_created'] ?? '');
                                    $progress = $cnt . '/10';
                                    $canCreate = $cnt >= 10;
                                ?>
                                <tr>
                                    <td data-label="#"><?= (int)($i + 1) ?></td>
                                    <td data-label="Applied Department"><?= htmlspecialchars($dept) ?></td>
                                    <td data-label="Applied Position"><?= htmlspecialchars($pos) ?></td>
                                    <td data-label="Pending"><span class="badge badge-outline"><?= htmlspecialchars($progress) ?></span></td>
                                    <td data-label="Ready Batches"><?= (int)$readyBatches ?></td>
                                    <td data-label="Oldest"><?= htmlspecialchars($oldest) ?></td>
                                    <td data-label="Actions">
                                        <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-outline btn-xs"
                                            data-action="view"
                                            data-applied-department="<?= htmlspecialchars($dept) ?>"
                                            data-applied-position="<?= htmlspecialchars($pos) ?>"
                                        >
                                            View
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs"
                                            data-action="create-program"
                                            data-applied-department="<?= htmlspecialchars($dept) ?>"
                                            data-applied-position="<?= htmlspecialchars($pos) ?>"
                                            <?= $canCreate ? '' : 'disabled' ?>
                                        >
                                            Create Training Program
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<dialog id="view-training-request-modal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800" id="trm-title">Group</h2>
                <p class="text-sm text-gray-500" id="trm-subtitle"></p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="close-trm">✕</button>
        </div>

        <div class="mt-4 text-sm">
            <div class="font-semibold text-gray-700">Applicants</div>
            <div class="text-xs text-gray-500" id="trm-note"></div>
            <div class="mt-3 overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainee</th>
                            <th>Employment Status</th>
                            <th>Role</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody id="trm-body"></tbody>
                </table>
            </div>
        </div>

        <div class="modal-action">
            <button type="button" class="btn" id="close-trm-2">Close</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<dialog id="view-idp-request-modal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800" id="idpm-title">IDP Training Request</h2>
                <p class="text-sm text-gray-500" id="idpm-subtitle"></p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="close-idpm">✕</button>
        </div>

        <div class="mt-4 text-sm space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <div class="text-xs font-semibold text-gray-500">Employee</div>
                    <div class="font-semibold text-gray-900" id="idpm-employee"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Employee ID</div>
                    <div class="text-gray-900" id="idpm-employee-id"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Department</div>
                    <div class="text-gray-900" id="idpm-department"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Position</div>
                    <div class="text-gray-900" id="idpm-position"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Succession Status</div>
                    <div class="text-gray-900" id="idpm-succession"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Competency</div>
                    <div class="text-gray-900" id="idpm-competency"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Target Score</div>
                    <div class="text-gray-900" id="idpm-target-score"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Target Date</div>
                    <div class="text-gray-900" id="idpm-target-date"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Requested At</div>
                    <div class="text-gray-900" id="idpm-requested-at"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Requested Type</div>
                    <div class="text-gray-900" id="idpm-req-type"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Requested Mode</div>
                    <div class="text-gray-900" id="idpm-req-mode"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Requested Start</div>
                    <div class="text-gray-900" id="idpm-req-start"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500">Requested End</div>
                    <div class="text-gray-900" id="idpm-req-end"></div>
                </div>
            </div>

            <div>
                <div class="font-semibold text-gray-700">Development Plan</div>
                <div id="idpm-plan" class="mt-1 whitespace-pre-wrap bg-base-200 rounded-lg p-3"></div>
            </div>
        </div>

        <div class="modal-action">
            <button type="button" class="btn" id="close-idpm-2">Close</button>
            <button type="button" class="btn btn-primary" id="idpm-create-btn" data-action="create-idp-program" data-idp-id="">Create Training Program</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
const qs = (s) => document.querySelector(s);
const modal = qs('#view-training-request-modal');
const idpModal = qs('#view-idp-request-modal');

const hotelPositions = [
  'Front Desk Agent',
  'Receptionist',
  'Guest Service Associate',
  'Concierge',
  'Bellboy / Porter',
  'reservations Agent',
  'Night Auditor',
  'Room Attendant',
  'Housekeeper',
  'Public Area Attendant',
  'Laundry Staff',
  'Housekeeping Supervisor',
  'Waiter / Waitress'
];

const restaurantPositions = [
  'Service Crew',
  'Captain Waiter',
  'Banquet Staff',
  'Bar Attendant',
  'Bartender',
  'Cook',
  'Commis',
  'Chef de Partie',
  'Sous Chef',
  'Head Chef',
  'Kitchen Helper'
];

const selectedFilterPosition = <?= json_encode($filterPosition, JSON_UNESCAPED_UNICODE) ?>;

const filterDeptEl = qs('#filter-department');
const filterPosEl = qs('#filter-position');

const fillFilterPositions = () => {
  if (!filterPosEl) return;
  const dept = filterDeptEl ? filterDeptEl.value : '';
  let list = [];
  if (dept === 'Hotel') list = hotelPositions;
  else if (dept === 'Restaurant') list = restaurantPositions;
  else list = [...hotelPositions, ...restaurantPositions];

  const currentSelected = filterPosEl.value || selectedFilterPosition || '';
  filterPosEl.innerHTML = '<option value="">All</option>';
  list.forEach((p) => {
    const opt = document.createElement('option');
    opt.value = p;
    opt.textContent = p;
    if (currentSelected && currentSelected === p) opt.selected = true;
    filterPosEl.appendChild(opt);
  });
};

if (filterDeptEl) {
  filterDeptEl.addEventListener('change', () => {
    if (filterPosEl) filterPosEl.value = '';
    fillFilterPositions();
  });
}

fillFilterPositions();

const getOpenDialogTarget = () => {
  const openDialogs = Array.from(document.querySelectorAll('dialog[open]'));
  return openDialogs.length ? openDialogs[openDialogs.length - 1] : undefined;
};

const swalFire = async (opts) => {
  if (!window.Swal) return null;
  const target = getOpenDialogTarget();
  return await window.Swal.fire({
    returnFocus: false,
    buttonsStyling: false,
    customClass: {
      popup: 'bg-base-100 text-base-content rounded-box',
      title: 'text-base-content',
      htmlContainer: 'text-base-content',
      actions: 'flex gap-2',
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-ghost',
      denyButton: 'btn btn-ghost'
    },
    ...(target ? { target } : {}),
    ...opts
  });
};

const closeModal = () => modal && modal.close();
['#close-trm', '#close-trm-2'].forEach((id) => {
  const el = qs(id);
  if (el) el.addEventListener('click', closeModal);
});

const closeIdpModal = () => idpModal && idpModal.close();
['#close-idpm', '#close-idpm-2'].forEach((id) => {
  const el = qs(id);
  if (el) el.addEventListener('click', closeIdpModal);
});

document.addEventListener('click', async (e) => {
  const btn = e.target && e.target.closest ? e.target.closest('button[data-action]') : null;
  if (!btn) return;

  const action = btn.getAttribute('data-action');

  if (action === 'view-idp') {
    const idpId = btn.getAttribute('data-idp-id') || '';
    if (!idpId) return;

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    const createBtn = document.getElementById('idpm-create-btn');
    if (createBtn) createBtn.setAttribute('data-idp-id', String(idpId));

    setText('idpm-subtitle', 'Loading...');
    setText('idpm-employee', 'Loading...');
    setText('idpm-employee-id', '');
    setText('idpm-department', '');
    setText('idpm-position', '');
    setText('idpm-succession', '');
    setText('idpm-competency', '');
    setText('idpm-target-score', '');
    setText('idpm-target-date', '');
    setText('idpm-requested-at', '');
    setText('idpm-req-type', '');
    setText('idpm-req-mode', '');
    setText('idpm-req-start', '');
    setText('idpm-req-end', '');
    setText('idpm-plan', '');

    if (idpModal) idpModal.showModal();

    const fd = new FormData();
    fd.append('action', 'get_idp_details');
    fd.append('idp_id', idpId);
    try {
      const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.success || !data.idp) {
        await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Failed to load IDP request.' });
        return;
      }

      const idp = data.idp;
      setText('idpm-subtitle', `${(idp.department || '').toString()} / ${(idp.position || '').toString()}`);
      setText('idpm-employee', (idp.employee_name || '').toString());
      setText('idpm-employee-id', (idp.employee_id || '').toString());
      setText('idpm-department', (idp.department || '').toString());
      setText('idpm-position', (idp.position || '').toString());
      setText('idpm-succession', (idp.succession_status || '').toString());
      setText('idpm-competency', (typeof idp.competency !== 'undefined' && idp.competency !== null) ? String(idp.competency) : '');
      setText('idpm-target-score', (typeof idp.target_score !== 'undefined' && idp.target_score !== null) ? String(idp.target_score) : '');
      setText('idpm-target-date', (idp.target_date || '').toString());
      setText('idpm-requested-at', (idp.training_requested_at || '').toString());
      setText('idpm-req-type', (idp.requested_training_type || '').toString());
      setText('idpm-req-mode', (idp.requested_training_mode || '').toString());
      setText('idpm-req-start', (idp.requested_start_datetime || '').toString());
      setText('idpm-req-end', (idp.requested_end_datetime || '').toString());
      setText('idpm-plan', (idp.development_plan || '').toString());
    } catch (_) {
      await swalFire({ icon: 'error', title: 'Failed', text: 'Failed to load IDP request.' });
    }
  }

  if (action === 'view') {
    const dept = btn.getAttribute('data-applied-department') || '';
    const pos = btn.getAttribute('data-applied-position') || '';
    const tbody = document.getElementById('trm-body');
    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    setText('trm-title', `${dept} / ${pos}`);
    setText('trm-subtitle', 'Applicants in this group (Pending)');
    setText('trm-note', 'Showing up to 50 applicants. Create Training Program requires at least 10.');
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500">Loading...</td></tr>';

    const fd = new FormData();
    fd.append('action', 'get_group_members');
    fd.append('applied_department', dept);
    fd.append('applied_position', pos);
    try {
      const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.success) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Failed to load.</td></tr>';
      } else {
        setText('trm-subtitle', `Applicants in this group (Pending): ${data.total}`);
        const rows = Array.isArray(data.members) ? data.members : [];
        if (!tbody) return;
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500">No applicants.</td></tr>';
        } else {
          tbody.innerHTML = rows.map((m, idx) => {
            const ln = (m.last_name || '').toString();
            const fn = (m.first_name || '').toString();
            const empno = (m.employee_no || '').toString();
            const name = `${ln}, ${fn}${empno ? ` (${empno})` : ''}`;
            const employment = (m.employment_status || 'New Hire').toString();
            const role = (m.role || 'Trainee').toString();
            const created = (m.created_at || '').toString();
            return `<tr>
              <td>${idx + 1}</td>
              <td class="font-semibold text-gray-900">${name.replace(/</g,'&lt;')}</td>
              <td>${employment.replace(/</g,'&lt;')}</td>
              <td>${role.replace(/</g,'&lt;')}</td>
              <td>${created.replace(/</g,'&lt;')}</td>
            </tr>`;
          }).join('');
        }
      }
    } catch (_) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Failed to load.</td></tr>';
    }

    if (modal) modal.showModal();
  }

  if (action === 'create-idp-program') {
    const idpId = btn.getAttribute('data-idp-id') || '';
    if (!idpId) return;

    const confirmRes = await swalFire({
      icon: 'question',
      title: 'Create Training Program?',
      text: 'This will open the training program form for this IDP request. Continue?',
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel'
    });

    if (!confirmRes.isConfirmed) return;

    window.location.href = `add_training.php?idp_id=${encodeURIComponent(idpId)}`;
  }

  if (action === 'create-program') {
    const dept = btn.getAttribute('data-applied-department') || '';
    const pos = btn.getAttribute('data-applied-position') || '';

    const confirmRes = await swalFire({
      icon: 'question',
      title: 'Create Training Program?',
      text: `This will prepare a batch of 10 trainees for ${dept} / ${pos}. Continue?`,
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel'
    });

    if (!confirmRes.isConfirmed) return;

    const fd = new FormData();
    fd.append('action', 'reserve_group_batch');
    fd.append('applied_department', dept);
    fd.append('applied_position', pos);

    try {
      const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.success) {
        await swalFire({ icon: 'error', title: 'Cannot create yet', text: (data && data.message) ? data.message : 'Need 10 trainees in the same group.' });
        return;
      }
      window.location.href = 'add_training.php';
    } catch (_) {
      await swalFire({ icon: 'error', title: 'Failed', text: 'Failed to prepare batch.' });
    }
  }
});
</script>
 <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>
