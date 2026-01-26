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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_trainee') {
    header('Content-Type: application/json; charset=utf-8');

    $employeeNo = trim((string)($_POST['employee_no'] ?? ''));
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $appliedDepartment = trim((string)($_POST['applied_department'] ?? ''));
    $appliedPosition = trim((string)($_POST['applied_position'] ?? ''));

    if ($firstName === '' || $lastName === '' || $appliedDepartment === '' || $appliedPosition === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO employees (employee_no, first_name, last_name, department, role, employment_status) VALUES (NULLIF(?, ''), ?, ?, ?, 'Trainee', 'New Hire')");
        $stmt->bind_param('ssss', $employeeNo, $firstName, $lastName, $appliedDepartment);
        $stmt->execute();
        $employeeId = (int)$conn->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO trainee_requests (employee_id, applied_department, applied_position, status) VALUES (?, ?, ?, 'Pending')");
        $stmt2->bind_param('iss', $employeeId, $appliedDepartment, $appliedPosition);
        $stmt2->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        echo json_encode(['success' => false, 'message' => 'Failed to create trainee.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_to_training') {
    header('Content-Type: application/json; charset=utf-8');

    $traineeRequestId = (int)($_POST['trainee_request_id'] ?? 0);
    if ($traineeRequestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing trainee_request_id.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT tr.id, tr.employee_id, tr.applied_department, tr.applied_position, tr.status FROM trainee_requests tr WHERE tr.id = ? LIMIT 1");
        $stmt->bind_param('i', $traineeRequestId);
        $stmt->execute();
        $tr = $stmt->get_result()->fetch_assoc();
        if (!$tr) {
            echo json_encode(['success' => false, 'message' => 'Trainee request not found.']);
            exit;
        }

        if ((string)($tr['status'] ?? '') === 'Sent') {
            echo json_encode(['success' => true, 'already_sent' => true]);
            exit;
        }

        $employeeId = (int)($tr['employee_id'] ?? 0);
        $dept = (string)($tr['applied_department'] ?? '');
        $pos = (string)($tr['applied_position'] ?? '');

        $conn->begin_transaction();

        $stmt2 = $conn->prepare("INSERT IGNORE INTO training_requests (trainee_request_id, employee_id, applied_department, applied_position, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt2->bind_param('iiss', $traineeRequestId, $employeeId, $dept, $pos);
        $stmt2->execute();

        $stmt3 = $conn->prepare("UPDATE trainee_requests SET status = 'Sent' WHERE id = ?");
        $stmt3->bind_param('i', $traineeRequestId);
        $stmt3->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        echo json_encode(['success' => false, 'message' => 'Failed to send to training.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_trainee_request') {
    header('Content-Type: application/json; charset=utf-8');

    $traineeRequestId = (int)($_POST['trainee_request_id'] ?? 0);
    if ($traineeRequestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing trainee_request_id.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT employee_id FROM trainee_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $traineeRequestId);
        $stmt->execute();
        $tr = $stmt->get_result()->fetch_assoc();
        if (!$tr) {
            echo json_encode(['success' => false, 'message' => 'Trainee request not found.']);
            exit;
        }

        $employeeId = (int)($tr['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid employee record.']);
            exit;
        }

        $conn->begin_transaction();
        $stmtDel = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmtDel->bind_param('i', $employeeId);
        $stmtDel->execute();
        $conn->commit();

        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        echo json_encode(['success' => false, 'message' => 'Failed to delete trainee request.']);
        exit;
    }
}

$rows = [];
try {
    $stmt = $conn->prepare("SELECT tr.id AS trainee_request_id, tr.status AS request_status, tr.applied_department, tr.applied_position, tr.created_at,
        e.id AS employee_id, e.employee_no, e.first_name, e.last_name, e.department, e.role, e.employment_status
        FROM trainee_requests tr
        JOIN employees e ON e.id = tr.employee_id
        ORDER BY tr.created_at DESC");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../../USM/navbar.php'; ?>
        <main class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Trainee Requests</h1>
                    <p class="text-sm text-gray-500">Create trainee profiles and send them to Training Requests</p>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-primary btn-sm" id="btn-add-trainee">+ Add Trainee</button>
                    <a class="btn btn-outline btn-sm" href="trainingrequest.php">Training Requests</a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainee</th>
                            <th>Employment Status</th>
                            <th>Role</th>
                            <th>Applied Department</th>
                            <th>Applied Position</th>
                            <th>Request Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="9" class="text-center text-gray-500">No trainee requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $r): ?>
                                <?php
                                    $traineeName = trim((string)($r['last_name'] ?? '')) . ', ' . trim((string)($r['first_name'] ?? ''));
                                    $empNo = trim((string)($r['employee_no'] ?? ''));
                                    if ($empNo !== '') $traineeName .= ' (' . $empNo . ')';
                                    $reqStatus = (string)($r['request_status'] ?? '');
                                    $badge = 'badge badge-outline';
                                    if ($reqStatus === 'Pending') $badge = 'badge badge-warning';
                                    if ($reqStatus === 'Sent') $badge = 'badge badge-success';
                                ?>
                                <tr>
                                    <td><?= (int)($i + 1) ?></td>
                                    <td class="font-semibold text-gray-900"><?= htmlspecialchars($traineeName) ?></td>
                                    <td><?= htmlspecialchars((string)($r['employment_status'] ?? 'New Hire')) ?></td>
                                    <td><?= htmlspecialchars((string)($r['role'] ?? 'Trainee')) ?></td>
                                    <td><?= htmlspecialchars((string)($r['applied_department'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($r['applied_position'] ?? '')) ?></td>
                                    <td><span class="<?= $badge ?>"><?= htmlspecialchars($reqStatus) ?></span></td>
                                    <td><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></td>
                                    <td class="space-x-2">
                                        <button
                                            type="button"
                                            class="btn btn-outline btn-xs"
                                            data-action="view"
                                            data-employee-no="<?= htmlspecialchars((string)($r['employee_no'] ?? '')) ?>"
                                            data-first-name="<?= htmlspecialchars((string)($r['first_name'] ?? '')) ?>"
                                            data-last-name="<?= htmlspecialchars((string)($r['last_name'] ?? '')) ?>"
                                            data-employment-status="<?= htmlspecialchars((string)($r['employment_status'] ?? 'New Hire')) ?>"
                                            data-role="<?= htmlspecialchars((string)($r['role'] ?? 'Trainee')) ?>"
                                            data-applied-department="<?= htmlspecialchars((string)($r['applied_department'] ?? '')) ?>"
                                            data-applied-position="<?= htmlspecialchars((string)($r['applied_position'] ?? '')) ?>"
                                            data-request-status="<?= htmlspecialchars($reqStatus) ?>"
                                        >
                                            View
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs"
                                            data-action="send"
                                            data-id="<?= (int)($r['trainee_request_id'] ?? 0) ?>"
                                            <?= $reqStatus === 'Sent' ? 'disabled' : '' ?>
                                        >
                                            Send to Training
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-error btn-xs"
                                            data-action="delete"
                                            data-id="<?= (int)($r['trainee_request_id'] ?? 0) ?>"
                                        >
                                            Delete
                                        </button>
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

<dialog id="add-trainee-modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Add Trainee</h2>
                <p class="text-sm text-gray-500">Creates an employee profile with Employment Status = New Hire, Role = Trainee</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="close-add-trainee">✕</button>
        </div>

        <form id="add-trainee-form" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
                <label class="label"><span class="label-text">Employee No (optional)</span></label>
                <input name="employee_no" class="input input-bordered" placeholder="EMP-0001">
            </div>
            <div class="form-control"></div>

            <div class="form-control">
                <label class="label"><span class="label-text">First Name <span class="text-red-500">*</span></span></label>
                <input name="first_name" class="input input-bordered" required>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Last Name <span class="text-red-500">*</span></span></label>
                <input name="last_name" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                <select name="applied_department" id="applied-department" class="select select-bordered" required>
                    <option value="" selected>Select Department</option>
                    <option value="Hotel">Hotel</option>
                    <option value="Restaurant">Restaurant</option>
                </select>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Position Applied <span class="text-red-500">*</span></span></label>
                <select name="applied_position" id="applied-position" class="select select-bordered" required>
                    <option value="" selected>Select Position</option>
                </select>
            </div>
        </form>

        <div class="modal-action">
            <button class="btn btn-ghost" type="button" id="cancel-add-trainee">Cancel</button>
            <button class="btn btn-primary" type="button" id="save-add-trainee">Save</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<dialog id="view-trainee-modal" class="modal">
    <div class="modal-box w-11/12 max-w-xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800" id="vt-name">Trainee</h2>
                <p class="text-sm text-gray-500" id="vt-empno"></p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="close-view-trainee">✕</button>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="font-semibold text-gray-700">Employment Status</div>
                <div id="vt-employment"></div>
            </div>
            <div>
                <div class="font-semibold text-gray-700">Role</div>
                <div id="vt-role"></div>
            </div>
            <div>
                <div class="font-semibold text-gray-700">Applied Department</div>
                <div id="vt-dept"></div>
            </div>
            <div>
                <div class="font-semibold text-gray-700">Applied Position</div>
                <div id="vt-pos"></div>
            </div>
            <div>
                <div class="font-semibold text-gray-700">Request Status</div>
                <div id="vt-status"></div>
            </div>
        </div>

        <div class="modal-action">
            <button type="button" class="btn" id="close-view-trainee-2">Close</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
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

const qs = (s) => document.querySelector(s);

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

const appliedDept = qs('#applied-department');
const appliedPos = qs('#applied-position');

const fillPositions = () => {
  if (!appliedPos) return;
  const dept = appliedDept ? appliedDept.value : '';
  const list = dept === 'Hotel' ? hotelPositions : (dept === 'Restaurant' ? restaurantPositions : []);
  appliedPos.innerHTML = '<option value="" selected>Select Position</option>';
  list.forEach((p) => {
    const opt = document.createElement('option');
    opt.value = p;
    opt.textContent = p;
    appliedPos.appendChild(opt);
  });
};

if (appliedDept) appliedDept.addEventListener('change', fillPositions);
fillPositions();

const addModal = qs('#add-trainee-modal');
const viewModal = qs('#view-trainee-modal');

const openAdd = () => addModal && addModal.showModal();
const closeAdd = () => addModal && addModal.close();
const closeView = () => viewModal && viewModal.close();

const btnAdd = qs('#btn-add-trainee');
if (btnAdd) btnAdd.addEventListener('click', openAdd);

['#close-add-trainee', '#cancel-add-trainee'].forEach((id) => {
  const el = qs(id);
  if (el) el.addEventListener('click', closeAdd);
});

['#close-view-trainee', '#close-view-trainee-2'].forEach((id) => {
  const el = qs(id);
  if (el) el.addEventListener('click', closeView);
});

const saveBtn = qs('#save-add-trainee');
if (saveBtn) {
  saveBtn.addEventListener('click', async () => {
    const form = qs('#add-trainee-form');
    if (!form) return;
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const fd = new FormData(form);
    fd.append('action', 'create_trainee');

    try {
      const res = await fetch('traineerequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.success) {
        await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Failed to create trainee.' });
        return;
      }
      await swalFire({ icon: 'success', title: 'Created', timer: 1200, showConfirmButton: false });
      window.location.reload();
    } catch (_) {
      await swalFire({ icon: 'error', title: 'Failed', text: 'Failed to create trainee.' });
    }
  });
}

const sendToTraining = async (traineeRequestId) => {
  const confirmRes = await swalFire({
    icon: 'question',
    title: 'Send to Training?',
    text: 'This will move the trainee to Training Requests.',
    showCancelButton: true,
    confirmButtonText: 'Send',
    cancelButtonText: 'Cancel'
  });
  if (!confirmRes.isConfirmed) return;

  const fd = new FormData();
  fd.append('action', 'send_to_training');
  fd.append('trainee_request_id', String(traineeRequestId));

  try {
    const res = await fetch('traineerequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.success) {
      await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Failed to send.' });
      return;
    }
    await swalFire({ icon: 'success', title: 'Sent', timer: 1200, showConfirmButton: false });
    window.location.reload();
  } catch (_) {
    await swalFire({ icon: 'error', title: 'Failed', text: 'Failed to send.' });
  }
};

const deleteTraineeRequest = async (traineeRequestId) => {
  const confirmRes = await swalFire({
    icon: 'warning',
    title: 'Delete this trainee request?',
    text: 'This will remove the trainee request and the created employee profile.',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  });
  if (!confirmRes || !confirmRes.isConfirmed) return;

  const fd = new FormData();
  fd.append('action', 'delete_trainee_request');
  fd.append('trainee_request_id', String(traineeRequestId));

  try {
    const res = await fetch('traineerequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.success) {
      await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Failed to delete.' });
      return;
    }
    await swalFire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false });
    window.location.reload();
  } catch (_) {
    await swalFire({ icon: 'error', title: 'Failed', text: 'Failed to delete.' });
  }
};

document.addEventListener('click', (e) => {
  const btn = e.target && e.target.closest ? e.target.closest('button[data-action]') : null;
  if (!btn) return;

  const action = btn.getAttribute('data-action');
  if (action === 'view') {
    const fn = btn.getAttribute('data-first-name') || '';
    const ln = btn.getAttribute('data-last-name') || '';
    const empno = btn.getAttribute('data-employee-no') || '';

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    setText('vt-name', `${ln}, ${fn}`);
    setText('vt-empno', empno ? `Employee No: ${empno}` : '');
    setText('vt-employment', btn.getAttribute('data-employment-status') || 'New Hire');
    setText('vt-role', btn.getAttribute('data-role') || 'Trainee');
    setText('vt-dept', btn.getAttribute('data-applied-department') || '');
    setText('vt-pos', btn.getAttribute('data-applied-position') || '');
    setText('vt-status', btn.getAttribute('data-request-status') || 'Pending');

    if (viewModal) viewModal.showModal();
  }

  if (action === 'send') {
    const id = btn.getAttribute('data-id');
    if (!id) return;
    sendToTraining(id);
  }

  if (action === 'delete') {
    const id = btn.getAttribute('data-id');
    if (!id) return;
    deleteTraineeRequest(id);
  }
});
</script>
</body>
</html>
