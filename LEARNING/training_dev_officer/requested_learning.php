<?php
session_start();
require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('critical_gaps');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$learningConn = usm_db_connect('learning_db');
if ($learningConn->connect_error) {
    die('Connection failed: ' . $learningConn->connect_error);
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_department_slug(string $v): string {
    $s = mb_strtolower(trim($v));
    if ($s === '') return '';

    if ($s === 'hr' || (str_contains($s, 'human') && str_contains($s, 'resource'))) return 'hr';
    if (str_contains($s, 'front') && str_contains($s, 'office')) return 'front-office';
    if (str_contains($s, 'house')) return 'housekeeping';
    if (str_contains($s, 'food') || str_contains($s, 'beverage') || str_contains($s, 'f&b')) return 'food-beverage';
    if (str_contains($s, 'kitchen') || str_contains($s, 'culinary')) return 'kitchen';
    if (str_contains($s, 'sales') || str_contains($s, 'marketing')) return 'sales-marketing';
    if (str_contains($s, 'finance') || str_contains($s, 'accounting')) return 'finance';
    if (str_contains($s, 'engineering') || str_contains($s, 'maintenance')) return 'engineering';
    if (str_contains($s, 'security')) return 'security';

    $s = preg_replace('/\([^\)]*\)/', '', $s);
    $s = str_replace(['&', '/', '_'], ['and', '-', '-'], $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-');
}

// Ensure assignment table exists
try {
    $learningConn->query(
        "CREATE TABLE IF NOT EXISTS idp_learning_module_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            idp_id INT NOT NULL,
            employee_id VARCHAR(50) NOT NULL,
            module_id INT NOT NULL,
            assigned_by VARCHAR(50) DEFAULT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_idp (idp_id),
            INDEX idx_emp (employee_id),
            INDEX idx_mod (module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Throwable $e) {
}

// Handle assignment
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_module') {
    $idpIdRaw = trim((string)($_POST['idp_id'] ?? ''));
    $employeeId = trim((string)($_POST['employee_id'] ?? ''));
    $moduleIdRaw = trim((string)($_POST['module_id'] ?? ''));

    try {
        if ($idpIdRaw === '' || !ctype_digit($idpIdRaw)) {
            throw new RuntimeException('Invalid IDP.');
        }
        if ($moduleIdRaw === '' || !ctype_digit($moduleIdRaw)) {
            throw new RuntimeException('Please select a module.');
        }
        if ($employeeId === '') {
            throw new RuntimeException('Missing employee.');
        }

        $idpId = (int)$idpIdRaw;
        $moduleId = (int)$moduleIdRaw;
        $assignedBy = (string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? '');

        $stmt = $learningConn->prepare(
            "INSERT INTO idp_learning_module_assignments (idp_id, employee_id, module_id, assigned_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE module_id = VALUES(module_id), assigned_by = VALUES(assigned_by), assigned_at = CURRENT_TIMESTAMP"
        );
        $stmt->bind_param('isis', $idpId, $employeeId, $moduleId, $assignedBy);
        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to assign module.');
        }
        $stmt->close();

        $_SESSION['learning_flash'] = ['type' => 'success', 'message' => 'Module assigned successfully.'];
    } catch (Throwable $e) {
        $_SESSION['learning_flash'] = ['type' => 'error', 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed.')];
    }

    header('Location: ' . ($_SERVER['PHP_SELF'] ?? 'requested_learning.php'));
    exit;
}

if (isset($_SESSION['learning_flash']) && is_array($_SESSION['learning_flash'])) {
    $flash = $_SESSION['learning_flash'];
    unset($_SESSION['learning_flash']);
}

$requests = [];
try {
    $sql = "SELECT id, employee_id, employee_name, position, department, succession_status, idp_status, delivery_mode,
                   learning_requested_at, training_requested_at
            FROM individual_development_plans
            WHERE idp_status IN ('requested','approved')
              AND delivery_mode IN ('Online','Hybrid')
              AND (learning_requested_at IS NOT NULL OR training_requested_at IS NOT NULL)
            ORDER BY COALESCE(learning_requested_at, training_requested_at) DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $requests[] = $row;
        }
    }
} catch (Throwable $e) {
    $requests = [];
}

// Fetch posted modules
$postedModules = [];
try {
    $resM = $learningConn->query("SELECT id, title, topic, department, roles, created_at FROM learning_modules WHERE status = 'posted' ORDER BY created_at DESC");
    if ($resM) {
        while ($r = $resM->fetch_assoc()) {
            $postedModules[] = $r;
        }
    }
} catch (Throwable $e) {
    $postedModules = [];
}

// Fetch existing assignments for displayed IDPs
$assignmentsByIdp = [];
try {
    if (!empty($requests)) {
        $idpIds = array_values(array_filter(array_map(static fn($r) => (int)($r['id'] ?? 0), $requests), static fn($v) => $v > 0));
        if (!empty($idpIds)) {
            $placeholders = implode(',', array_fill(0, count($idpIds), '?'));
            $types = str_repeat('i', count($idpIds));
            $stmtA = $learningConn->prepare("SELECT idp_id, module_id, assigned_at FROM idp_learning_module_assignments WHERE idp_id IN ($placeholders)");
            $stmtA->bind_param($types, ...$idpIds);
            $stmtA->execute();
            $resA = $stmtA->get_result();
            while ($resA && ($row = $resA->fetch_assoc())) {
                $assignmentsByIdp[(int)$row['idp_id']] = $row;
            }
            $stmtA->close();
        }
    }
} catch (Throwable $e) {
    $assignmentsByIdp = [];
}

// Map module_id -> module details for display
$postedById = [];
foreach ($postedModules as $m) {
    $postedById[(int)($m['id'] ?? 0)] = $m;
}

$conn->close();
$learningConn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../USM/sidebarr.php'; 
    ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../USM/navbar.php'; ?>

        <main class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Learning Requests</h1>
                    <p class="text-sm text-gray-500">Requested learnings from approved Individual Development Plans (Online / Hybrid)</p>
                </div>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert <?php echo ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-error'; ?> mb-4">
                    <span><?php echo h($flash['message'] ?? ''); ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-md p-4">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Mode</th>
                            <th>Assigned Module</th>
                            <th>Requested</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="9" class="text-center text-gray-500 py-6">No learning requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($requests as $i => $r): ?>
                                <?php
                                    $idpId = (int)($r['id'] ?? 0);
                                    $assignment = $assignmentsByIdp[$idpId] ?? null;
                                    $assignedModule = null;
                                    if (is_array($assignment)) {
                                        $mid = (int)($assignment['module_id'] ?? 0);
                                        $assignedModule = $postedById[$mid] ?? null;
                                    }
                                    $deptSlug = normalize_department_slug((string)($r['department'] ?? ''));
                                    $roleName = (string)($r['position'] ?? '');
                                ?>
                                <tr>
                                    <td><?= (int)($i + 1) ?></td>
                                    <td><?= h($r['employee_name'] ?? '') ?></td>
                                    <td><?= h($r['employee_id'] ?? '') ?></td>
                                    <td><?= h($r['department'] ?? '') ?></td>
                                    <td><?= h($r['position'] ?? '') ?></td>
                                    <td><?= h($r['delivery_mode'] ?? '') ?></td>
                                    <td>
                                        <?php if (is_array($assignedModule)): ?>
                                            <div class="text-sm font-semibold"><?php echo h($assignedModule['title'] ?? ''); ?></div>
                                            <div class="text-xs opacity-70"><?php echo h($assignedModule['topic'] ?? ''); ?></div>
                                        <?php else: ?>
                                            <span class="text-sm opacity-60">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($r['learning_requested_at'] ?? '') ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            onclick='openRequestModal(<?php echo json_encode([
                                                "idp_id" => $idpId,
                                                "employee_id" => (string)($r["employee_id"] ?? ""),
                                                "employee_name" => (string)($r["employee_name"] ?? ""),
                                                "department" => (string)($r["department"] ?? ""),
                                                "department_slug" => $deptSlug,
                                                "position" => (string)($r["position"] ?? ""),
                                                "role" => $roleName,
                                                "delivery_mode" => (string)($r["delivery_mode"] ?? ""),
                                            ], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                        >View</button>
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

<dialog id="request_modal" class="modal">
    <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-lg mb-3">Learning Request</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <div class="bg-base-200 rounded-lg p-3">
                <div class="text-xs opacity-70">Employee</div>
                <div class="font-semibold" id="m_employee"></div>
                <div class="text-xs opacity-70" id="m_employee_id"></div>
            </div>
            <div class="bg-base-200 rounded-lg p-3">
                <div class="text-xs opacity-70">Department / Role</div>
                <div class="font-semibold" id="m_department"></div>
                <div class="text-xs opacity-70" id="m_role"></div>
            </div>
        </div>

        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="assign_module" />
            <input type="hidden" name="idp_id" id="m_idp_id" value="" />
            <input type="hidden" name="employee_id" id="m_employee_id_hidden" value="" />

            <div>
                <label class="label"><span class="label-text">Posted Learning Modules</span></label>
                <select class="select select-bordered w-full" name="module_id" id="m_module_select" required>
                    <option value="" disabled selected>Select module</option>
                    <?php foreach ($postedModules as $m): ?>
                        <option value="<?php echo (int)($m['id'] ?? 0); ?>">
                            <?php echo h($m['title'] ?? ''); ?> â€” <?php echo h($m['topic'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col md:flex-row gap-2 md:items-center md:justify-between">
                <button type="submit" class="btn btn-primary">Assign</button>
                <a id="m_create_link" href="#" class="btn btn-outline">Create Learning Module</a>
            </div>
        </form>

        <div class="modal-action">
            <form method="dialog"><button class="btn">Close</button></form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const requestModal = document.getElementById('request_modal');
    const postedModulesCount = <?php echo json_encode(count($postedModules)); ?>;

    window.openRequestModal = function (payload) {
        if (!payload || !requestModal) return;
        document.getElementById('m_employee').textContent = payload.employee_name || '';
        document.getElementById('m_employee_id').textContent = payload.employee_id || '';
        document.getElementById('m_department').textContent = payload.department || '';
        document.getElementById('m_role').textContent = payload.role || payload.position || '';

        document.getElementById('m_idp_id').value = String(payload.idp_id || '');
        document.getElementById('m_employee_id_hidden').value = String(payload.employee_id || '');

        const createLink = document.getElementById('m_create_link');
        const qs = new URLSearchParams();
        qs.set('open_upload', '1');
        if (payload.department_slug) qs.set('department', payload.department_slug);
        if (payload.role) qs.set('role', payload.role);
        qs.set('from', 'idp');
        if (payload.idp_id) qs.set('idp_id', String(payload.idp_id));
        createLink.href = 'learning_module_repository.php?' + qs.toString();

        if (postedModulesCount === 0) {
            const sel = document.getElementById('m_module_select');
            if (sel) sel.disabled = true;
        }

        requestModal.showModal();
    };
</script>
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>

