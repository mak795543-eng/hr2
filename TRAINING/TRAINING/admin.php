<?php

require_once __DIR__ . '/db.php';

global $TRAINING_DB_NAME, $REQUESTS_DB_NAME;
$trainingDb = (string)($TRAINING_DB_NAME ?? '');
$requestsDb = (string)($REQUESTS_DB_NAME ?? '');
if ($trainingDb === '') {
    $trainingDb = (string)($conn->query('SELECT DATABASE()')->fetch_row()[0] ?? '');
}
if ($requestsDb === '') {
    $requestsDb = $trainingDb;
}

$trainingProgramsTable = "`{$trainingDb}`.`training_programs`";
$requestsTable = "`{$requestsDb}`.`admin_requests`";
$financialTable = "`{$requestsDb}`.`financial_requests`";
$logisticsTable = "`{$requestsDb}`.`logistics_requests`";
$requestLogsTable = "`{$requestsDb}`.`department_request_status_logs`";

try {
    $conn->query("CREATE TABLE IF NOT EXISTS {$requestsTable} (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending', facility_details TEXT NULL, details_json TEXT NULL, rejection_reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_admin_status (status), INDEX idx_admin_program (program_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}
try {
    $conn->query("CREATE TABLE IF NOT EXISTS {$financialTable} (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending', budget_amount DECIMAL(12,2) NULL, details_json TEXT NULL, rejection_reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_financial_status (status), INDEX idx_financial_program (program_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}
try {
    $conn->query("CREATE TABLE IF NOT EXISTS {$logisticsTable} (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending', items_requested TEXT NULL, details_json TEXT NULL, rejection_reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_logistics_status (status), INDEX idx_logistics_program (program_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}
try {
    $conn->query("CREATE TABLE IF NOT EXISTS {$requestLogsTable} (id INT AUTO_INCREMENT PRIMARY KEY, request_type ENUM('financial','logistics','admin') NOT NULL, request_id INT NOT NULL, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, old_status VARCHAR(50) NULL, new_status VARCHAR(50) NOT NULL, reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_drl_program (program_id), INDEX idx_drl_type (request_type), INDEX idx_drl_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}

$tableName = 'admin_requests';
$tableExists = false;
try {
    $stmtCheck = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1");
    $stmtCheck->bind_param('ss', $requestsDb, $tableName);
    $stmtCheck->execute();
    $tableExists = (bool)$stmtCheck->get_result()->fetch_row();
} catch (Throwable $e) {
    $tableExists = false;
}

if (!$tableExists) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin - Facility Requests</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-3xl mx-auto p-6"><div class="bg-white rounded-xl shadow-md p-6"><h1 class="text-xl font-bold text-gray-800">Missing database table</h1><p class="text-gray-600 mt-2">The table <code class="bg-gray-100 px-1 rounded">admin_requests</code> does not exist in the current database (<code class="bg-gray-100 px-1 rounded">' . htmlspecialchars((string)$conn->query('SELECT DATABASE()')->fetch_row()[0]) . '</code>).</p><p class="text-gray-600 mt-2">Import/run <code class="bg-gray-100 px-1 rounded">schema_training_requests.sql</code> in phpMyAdmin (make sure the correct database is selected).</p><div class="mt-4"><a class="inline-block px-4 py-2 bg-blue-600 text-white rounded" href="trainingprogram.php">Back to Training Programs</a></div></div></div></body></html>';
    exit;
}

$ensureRequestSchema = function(mysqli $conn): void {
    global $requestsDb, $requestsTable, $requestLogsTable;

    $tableHasColumn = function(mysqli $conn, string $db, string $table, string $column): bool {
        $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1");
        $stmt->bind_param('sss', $db, $table, $column);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    };

    try {
        $conn->query("ALTER TABLE {$requestsTable} MODIFY status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending'");
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, $requestsDb, 'admin_requests', 'rejection_reason')) {
            $conn->query("ALTER TABLE {$requestsTable} ADD COLUMN rejection_reason TEXT NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, $requestsDb, 'admin_requests', 'submission_no')) {
            $conn->query("ALTER TABLE {$requestsTable} ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS {$requestLogsTable} (id INT AUTO_INCREMENT PRIMARY KEY, request_type ENUM('financial','logistics','admin') NOT NULL, request_id INT NOT NULL, program_id INT NOT NULL, old_status VARCHAR(50) NULL, new_status VARCHAR(50) NOT NULL, reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_drl_program (program_id), INDEX idx_drl_type (request_type), INDEX idx_drl_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, $requestsDb, 'department_request_status_logs', 'submission_no')) {
            $conn->query("ALTER TABLE {$requestLogsTable} ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
    } catch (Throwable $e) {
    }
};

$ensureRequestSchema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $status = $_POST['status'] ?? 'Pending';
    $reason = trim((string)($_POST['reason'] ?? ''));
    $isAjax = isset($_POST['ajax']) && (string)$_POST['ajax'] === '1';

    $oldStatus = '';
    $programIdForLog = 0;
    $submissionNoForLog = 1;
    try {
        $stmtOld = $conn->prepare("SELECT program_id, status, submission_no FROM {$requestsTable} WHERE id = ?");
        $stmtOld->bind_param('i', $requestId);
        $stmtOld->execute();
        $rowOld = $stmtOld->get_result()->fetch_assoc();
        if ($rowOld) {
            $programIdForLog = (int)($rowOld['program_id'] ?? 0);
            $oldStatus = (string)($rowOld['status'] ?? '');
            $submissionNoForLog = isset($rowOld['submission_no']) ? (int)$rowOld['submission_no'] : 1;
            if ($submissionNoForLog <= 0) $submissionNoForLog = 1;
        }
    } catch (Throwable $e) {
    }

    $programStatusForLock = '';
    try {
        if ($programIdForLog > 0) {
            $stmtProg = $conn->prepare("SELECT status FROM {$trainingProgramsTable} WHERE id = ? LIMIT 1");
            $stmtProg->bind_param('i', $programIdForLog);
            $stmtProg->execute();
            $rowProg = $stmtProg->get_result()->fetch_assoc();
            $programStatusForLock = $rowProg ? (string)($rowProg['status'] ?? '') : '';
        }
    } catch (Throwable $e) {
        $programStatusForLock = '';
    }

    if ($programStatusForLock === 'ON HOLD') {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Program is ON HOLD. Requests cannot be approved or rejected.']);
            exit;
        }

        $redirect = 'admin.php';
        if (isset($_POST['program_id']) && (string)$_POST['program_id'] !== '') {
            $redirect .= '?program_id=' . (int)$_POST['program_id'];
        }
        header('Location: ' . $redirect);
        exit;
    }

    $reasonToSave = ($status === 'Rejected') ? $reason : '';
    $stmt = $conn->prepare("UPDATE {$requestsTable} SET status = ?, rejection_reason = NULLIF(?, '') WHERE id = ?");
    $stmt->bind_param('ssi', $status, $reasonToSave, $requestId);
    $stmt->execute();

    try {
        $stmtLog = $conn->prepare("INSERT INTO {$requestLogsTable} (request_type, request_id, program_id, submission_no, old_status, new_status, reason) VALUES ('admin', ?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''))");
        $stmtLog->bind_param('iiisss', $requestId, $programIdForLog, $submissionNoForLog, $oldStatus, $status, $reasonToSave);
        $stmtLog->execute();
    } catch (Throwable $e) {
    }

    if ($status === 'Rejected' && $programIdForLog > 0) {
        try {
            $stmtHold = $conn->prepare("UPDATE {$trainingProgramsTable} SET status = 'ON HOLD' WHERE id = ? AND status = 'Approved'");
            $stmtHold->bind_param('i', $programIdForLog);
            $stmtHold->execute();
        } catch (Throwable $e) {
        }

        try {
            $stmtReqHold = $conn->prepare("UPDATE {$requestsTable} SET status = 'ON HOLD' WHERE program_id = ? AND IFNULL(submission_no, 1) = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programIdForLog, $submissionNoForLog);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmtReqHold = $conn->prepare("UPDATE {$financialTable} SET status = 'ON HOLD' WHERE program_id = ? AND IFNULL(submission_no, 1) = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programIdForLog, $submissionNoForLog);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmtReqHold = $conn->prepare("UPDATE {$logisticsTable} SET status = 'ON HOLD' WHERE program_id = ? AND IFNULL(submission_no, 1) = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programIdForLog, $submissionNoForLog);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }

    $redirect = 'admin.php';
    if (isset($_POST['program_id']) && (string)$_POST['program_id'] !== '') {
        $redirect .= '?program_id=' . (int)$_POST['program_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $status = $_POST['status'] ?? 'Pending';
    $facilityDetails = $_POST['facility_details'] ?? null;

    $stmt = $conn->prepare("UPDATE {$requestsTable} SET status = ?, facility_details = ? WHERE id = ?");
    $stmt->bind_param('ssi', $status, $facilityDetails, $requestId);
    $stmt->execute();

    header('Location: admin.php');
    exit;
}

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;

if ($requestId) {
    $stmt = $conn->prepare("SELECT ar.*, tp.training_title, tp.training_type, tp.category, tp.participants_needed, tp.start_datetime, tp.end_datetime, tp.description, tp.status AS program_status
        FROM {$requestsTable} ar
        JOIN {$trainingProgramsTable} tp ON tp.id = ar.program_id
        WHERE ar.id = ?");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
}

if ($programId) {
    $stmt = $conn->prepare("SELECT ar.id AS request_id, ar.status, ar.created_at, tp.id AS program_id, tp.training_title, tp.start_datetime, tp.end_datetime, tp.participants_needed, tp.status AS program_status
        FROM {$requestsTable} ar
        JOIN {$trainingProgramsTable} tp ON tp.id = ar.program_id
        WHERE tp.id = ? AND ar.status IN ('Pending','ON HOLD') AND tp.status NOT IN ('Under Review','For Compliance') AND IFNULL(ar.submission_no, 1) = IFNULL(tp.submission_no, 1)
        ORDER BY ar.created_at DESC");
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT ar.id AS request_id, ar.status, ar.created_at, tp.id AS program_id, tp.training_title, tp.start_datetime, tp.end_datetime, tp.participants_needed, tp.status AS program_status
        FROM {$requestsTable} ar
        JOIN {$trainingProgramsTable} tp ON tp.id = ar.program_id
        WHERE ar.status IN ('Pending','ON HOLD') AND tp.status NOT IN ('Under Review','For Compliance') AND IFNULL(ar.submission_no, 1) = IFNULL(tp.submission_no, 1)
        ORDER BY ar.created_at DESC");
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

    <main class="max-w-6xl mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Requests</h1>
                    <p class="text-sm text-gray-500">Facility requests created from Training Programs</p>
                </div>
                <div class="flex gap-2">
                    <a class="btn btn-outline btn-sm" href="trainingprogram.php">Back to Training Programs</a>
                    <a class="btn btn-outline btn-sm" href="request_logs.php?type=admin">Logs</a>
                </div>
            </div>

            <?php if ($requestId && !empty($detail)) : ?>
                <dialog id="view-admin-modal" class="modal" open>
                    <div class="modal-box w-11/12 max-w-3xl">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($detail['training_title']); ?></h2>
                                <div class="text-sm text-gray-600 mt-1">
                                    <div>Participants: <?php echo (int)$detail['participants_needed']; ?></div>
                                    <div>Schedule: <?php echo htmlspecialchars($detail['start_datetime']); ?> to <?php echo htmlspecialchars($detail['end_datetime']); ?></div>
                                    <div class="mt-1">Request #: <?php echo (int)$detail['id']; ?></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-ghost btn-sm" id="close-admin-modal">✕</button>
                        </div>

                        <div class="mt-4">
                            <div class="font-semibold text-gray-700 mb-1">Description</div>
                            <div class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($detail['description']); ?></div>
                        </div>

                        <div class="mt-4">
                            <div class="font-semibold text-gray-700 mb-1">Facility Details</div>
                            <div class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars((string)($detail['facility_details'] ?? '')); ?></div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="font-semibold text-gray-700">Status</div>
                                <div class="badge badge-outline"><?php echo htmlspecialchars(((string)($detail['program_status'] ?? '') === 'ON HOLD' && (string)($detail['status'] ?? '') === 'Pending') ? 'ON HOLD' : (string)($detail['status'] ?? '')); ?></div>
                            </div>
                        </div>

                        <?php if ((string)($detail['status'] ?? '') === 'Rejected' && trim((string)($detail['rejection_reason'] ?? '')) !== '') : ?>
                            <div class="mt-4">
                                <div class="font-semibold text-gray-700">Rejection Reason</div>
                                <div class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars((string)$detail['rejection_reason']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="modal-action">
                            <form method="POST" class="inline" id="admin-approve-form">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="request_id" value="<?php echo (int)$detail['id']; ?>">
                                <input type="hidden" name="program_id" value="<?php echo (int)$detail['program_id']; ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" class="btn btn-success" <?php echo ((string)($detail['program_status'] ?? '') === 'ON HOLD') ? 'disabled' : ''; ?>>Approve</button>
                            </form>
                            <form method="POST" class="inline reject-form" id="admin-reject-form">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="request_id" value="<?php echo (int)$detail['id']; ?>">
                                <input type="hidden" name="program_id" value="<?php echo (int)$detail['program_id']; ?>">
                                <input type="hidden" name="status" value="Rejected">
                                <input type="hidden" name="reason" class="reject-reason" value="">
                                <button type="button" class="btn btn-error js-reject" <?php echo ((string)($detail['program_status'] ?? '') === 'ON HOLD') ? 'disabled' : ''; ?>>Reject</button>
                            </form>
                            <a class="btn btn-ghost" href="admin.php<?php echo $programId ? '?program_id=' . (int)$programId : ''; ?>">Close</a>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-700">Facility Requests</h2>
                    </div>
                    <div>
                        <table class="table card-table">
                            <thead>
                                <tr>
                                    <th>Request</th>
                                    <th>Training Program</th>
                                    <th>Schedule</th>
                                    <th>Participants</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)) : ?>
                                    <tr class="card-empty"><td colspan="6">No requests found.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($requests as $r) : ?>
                                        <tr data-request-id="<?php echo (int)$r['request_id']; ?>">
                                            <td data-label="Request">#<?php echo (int)$r['request_id']; ?></td>
                                            <td data-label="Training Program"><?php echo htmlspecialchars($r['training_title']); ?></td>
                                            <td data-label="Schedule"><?php echo htmlspecialchars($r['start_datetime']); ?> to <?php echo htmlspecialchars($r['end_datetime']); ?></td>
                                            <td data-label="Participants"><?php echo (int)$r['participants_needed']; ?></td>
                                            <td data-label="Status"><span class="badge badge-outline"><?php echo htmlspecialchars(((string)($r['program_status'] ?? '') === 'ON HOLD' && (string)($r['status'] ?? '') === 'Pending') ? 'ON HOLD' : (string)($r['status'] ?? '')); ?></span></td>
                                            <td data-label="Actions">
                                                <div class="flex flex-wrap gap-2">
                                                    <a class="btn btn-sm btn-outline" href="admin.php?request_id=<?php echo (int)$r['request_id']; ?>">View</a>
                                                    <form method="POST" class="inline js-status-form" data-status-action="Approved">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                                                        <input type="hidden" name="program_id" value="<?php echo (int)$r['program_id']; ?>">
                                                        <input type="hidden" name="status" value="Approved">
                                                        <button type="submit" class="btn btn-sm btn-success" <?php echo ((string)($r['program_status'] ?? '') === 'ON HOLD') ? 'disabled' : ''; ?>>Approve</button>
                                                    </form>
                                                    <form method="POST" class="inline reject-form js-status-form" data-status-action="Rejected">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                                                        <input type="hidden" name="program_id" value="<?php echo (int)$r['program_id']; ?>">
                                                        <input type="hidden" name="status" value="Rejected">
                                                        <input type="hidden" name="reason" class="reject-reason" value="">
                                                        <button type="button" class="btn btn-sm btn-error js-reject" <?php echo ((string)($r['program_status'] ?? '') === 'ON HOLD') ? 'disabled' : ''; ?>>Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
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
    const closeBtn = document.getElementById('close-admin-modal');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        window.location.href = 'admin.php<?php echo $programId ? '?program_id=' . (int)$programId : ''; ?>';
      });
    }

    const swalBase = {
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-ghost'
      }
    };

    const submitStatusAjax = async (form) => {
      const tr = form.closest('tr');
      const fd = new FormData(form);
      fd.append('ajax', '1');

      try {
        const res = await fetch('admin.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (!data || !data.success) {
          if (window.Swal) await Swal.fire({ ...swalBase, icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to update request.' });
          return;
        }
        if (tr) tr.remove();
        if (window.Swal) await Swal.fire({ ...swalBase, icon: 'success', title: 'Updated', timer: 900, showConfirmButton: false });
      } catch (_) {
        if (window.Swal) await Swal.fire({ ...swalBase, icon: 'error', title: 'Failed', text: 'Unexpected error.' });
      }
    };

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!form || !form.classList || !form.classList.contains('js-status-form')) return;
      e.preventDefault();
      submitStatusAjax(form);
    });

    const forms = Array.from(document.querySelectorAll('form.reject-form'));
    forms.forEach((form) => {
      const btn = form.querySelector('.js-reject');
      const reasonInput = form.querySelector('.reject-reason');
      if (!btn || !reasonInput || !window.Swal) return;
      btn.addEventListener('click', async function () {
        const res = await Swal.fire({
          ...swalBase,
          icon: 'question',
          title: 'Reject Request',
          input: 'textarea',
          inputLabel: 'Reason for rejection',
          inputPlaceholder: 'Type the reason here...',
          inputAttributes: { 'aria-label': 'Reason for rejection' },
          showCancelButton: true,
          confirmButtonText: 'Reject',
          preConfirm: (value) => {
            if (!value || String(value).trim() === '') {
              Swal.showValidationMessage('Reason is required');
              return false;
            }
            return value;
          }
        });

        if (res && res.isConfirmed) {
          reasonInput.value = String(res.value || '').trim();
          submitStatusAjax(form);
        }
      });
    });
  })();
</script>
 <?php require('../../partials/footer.php') ?>
