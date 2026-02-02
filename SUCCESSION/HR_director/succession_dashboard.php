<?php


require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';


$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

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

$where = [
    'ss.is_pushed = 1'
];
$params = [];

if ($departmentFilter !== 'all' && $departmentFilter !== '') {
    $where[] = 'ss.department = ?';
    $params[] = $departmentFilter;
}

if ($statusFilter !== 'all' && in_array($statusFilter, $allowedStatuses, true)) {
    $where[] = "COALESCE(e.status, ss.status, 'Retrain') = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = '(ss.employee_name LIKE ? OR ss.employee_id LIKE ? OR ss.position LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT ss.id,
            ss.employee_id,
            ss.employee_name,
            ss.position,
            ss.department,
            COALESCE(e.competency, ss.competency, 0) AS competency_level,
            COALESCE(e.status, ss.status, 'Retrain') AS status,
            COALESCE(ss.idp_status, 'Pending') AS idp_status
     FROM succession_submissions ss
     LEFT JOIN employees e ON e.employee_id = ss.employee_id
     $whereSql
     ORDER BY ss.created_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statusCounts = [
    'Retrain' => 0,
    'Reskilling' => 0,
    'Refresher Training' => 0,
    'Upskilling' => 0,
    'Succession Ready' => 0,
];
$idpCounts = [
    'Pending' => 0,
    'Created' => 0,
];
foreach (($rows ?? []) as $r) {
    $st = (string)($r['status'] ?? '');
    if (isset($statusCounts[$st])) {
        $statusCounts[$st]++;
    }
    $is = (string)($r['idp_status'] ?? 'Pending');
    if (!isset($idpCounts[$is])) {
        $idpCounts[$is] = 0;
    }
    $idpCounts[$is]++;
}

$recentLogs = [];
try {
    $stmtLogs = $pdo->prepare(
        "SELECT employee_id, actor_employee_id, actor_role, module, action, details, created_at
         FROM action_logs
         ORDER BY created_at DESC
         LIMIT 30"
    );
    $stmtLogs->execute();
    $recentLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $recentLogs = [];
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function statusBadgeClass($status) {
    $status = (string)$status;
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
}
require('../../partials/header.php');
?>
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

      <div class="max-w-7xl mx-auto p-6">

        <!-- Notification Container -->
        <div id="notificationContainer"></div>

    
        <div class="flex items-center justify-between mb-9">
            <div>
                <h1 class="text-2xl font-bold">Succession Dashboard</h1>
               
            </div>
            <div class="flex gap-2">
                <a href="development_plans_page.php" class="btn btn-outline btn-sm">Development Plans</a>
            </div>
        </div>

        <div class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-8 gap-4">
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Retrain</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Retrain'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Reskilling</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Reskilling'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Refresher Training</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Refresher Training'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Upskilling</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Upskilling'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Succession Ready</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Succession Ready'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">IDP Pending</div><div class="text-3xl font-bold"><?php echo (int)($idpCounts['Pending'] ?? 0); ?></div></div></div>
                <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">IDP Created</div><div class="text-3xl font-bold"><?php echo (int)($idpCounts['Created'] ?? 0); ?></div></div></div>
                <button type="button" class="card bg-base-100 shadow text-left hover:shadow-md transition" onclick="openRecentActivityModal();">
                    <div class="card-body p-5">
                        <div class="text-xs opacity-70">Recent Activities</div>
                        <div class="text-3xl font-bold"><?php echo (int)count($recentLogs); ?></div>
                        <div class="text-xs opacity-60 mt-1">Click to view</div>
                    </div>
                </button>
            </div>
        </div>

        <div class="card bg-base-100 shadow mb-6">
            <div class="card-body">
                <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
                    <div class="flex-1">
                        <label class="label"><span class="label-text">Search</span></label>
                        <input
                            type="text"
                            name="search"
                            value="<?php echo h($search); ?>"
                            placeholder="Search employee / ID / position"
                            class="input input-bordered w-full"
                        />
                    </div>

                    <div class="w-full md:w-64">
                        <label class="label"><span class="label-text">Department</span></label>
                        <select name="department" class="select select-bordered w-full">
                            <option value="all" <?php echo $departmentFilter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach (($departments ?? []) as $dept): ?>
                                <option value="<?php echo h($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
                                    <?php echo h($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-full md:w-56">
                        <label class="label"><span class="label-text">Status</span></label>
                        <select name="status" class="select select-bordered w-full">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <?php foreach ($allowedStatuses as $st): ?>
                                <option value="<?php echo h($st); ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>>
                                    <?php echo h($st); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="succession_dashboard.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Competency Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-10 opacity-70">No records submitted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td>
                                            <div class="font-semibold"><?php echo h($r['employee_name']); ?></div>
                                            <div class="text-xs opacity-70"><?php echo h($r['employee_id']); ?></div>
                                        </td>
                                        <td><?php echo h($r['position']); ?></td>
                                        <td><?php echo h($r['department']); ?></td>
                                        <td class="font-semibold"><?php echo number_format((float)($r['competency_level'] ?? 0), 1); ?>%</td>
                                        <td>
                                            <span class="badge badge-sm <?php echo h(statusBadgeClass($r['status'])); ?>">
                                                <?php echo h($r['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (((string)($r['idp_status'] ?? 'Pending')) === 'Created'): ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline btn-sm"
                                                    data-view-idp="1"
                                                    data-employee-id="<?php echo h($r['employee_id']); ?>"
                                                >
                                                    View IDP
                                                </button>
                                            <?php else: ?>
                                                <a
                                                    class="btn btn-primary btn-sm"
                                                    href="individual_dev_plan.php?employee_id=<?php echo urlencode($r['employee_id']); ?>"
                                                    data-confirm-create="1"
                                                >
                                                    Create IDP
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <dialog id="recent-activity-modal" class="modal">
            <div class="modal-box max-w-5xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">Recent Activities</h3>
                        <div class="text-xs opacity-70 mt-1">Latest <?php echo (int)count($recentLogs); ?> records</div>
                    </div>
                    <form method="dialog"><button class="btn btn-sm">Close</button></form>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Employee</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentLogs) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-6 opacity-70">No activity yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $lg): ?>
                                    <tr>
                                        <td class="text-sm"><?php echo h($lg['created_at'] ?? ''); ?></td>
                                        <td class="text-sm"><?php echo h($lg['employee_id'] ?? ''); ?></td>
                                        <td class="text-sm"><?php echo h($lg['module'] ?? ''); ?></td>
                                        <td class="text-sm"><?php echo h($lg['action'] ?? ''); ?></td>
                                        <td class="text-sm"><?php echo h($lg['details'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <dialog id="idp-view-modal" class="modal">
            <div class="modal-box max-w-6xl p-0">
                <div class="p-4 border-b border-base-200 flex items-center justify-between">
                    <div class="font-semibold">Individual Development Plan</div>
                    <form method="dialog"><button class="btn btn-sm">Close</button></form>
                </div>
                <div class="p-0" style="height: 75vh;">
                    <iframe id="idp-view-frame" src="about:blank" style="width:100%;height:100%;border:0;"></iframe>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

      </div>
    </div>

    <script>
        (function () {
            var ok = <?php echo json_encode($flashOk); ?>;
            var err = <?php echo json_encode($flashErr); ?>;

            if (ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Action completed.',
                    timer: 1600,
                    showConfirmButton: false
                });
            }
            if (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong.'
                });
            }

            document.querySelectorAll('[data-confirm-create="1"]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var href = btn.getAttribute('href') || '';
                    Swal.fire({
                        icon: 'question',
                        title: 'Create IDP?',
                        text: 'This will create a new IDP with status Under Review.',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, create',
                        cancelButtonText: 'No'
                    }).then(function (res) {
                        if (res.isConfirmed) {
                            if (href) {
                                window.location.href = href;
                            }
                        }
                    });
                });
            });

            window.openRecentActivityModal = function () {
                var dlg = document.getElementById('recent-activity-modal');
                if (dlg && typeof dlg.showModal === 'function') {
                    dlg.showModal();
                }
            };

            document.querySelectorAll('[data-view-idp="1"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var empId = btn.getAttribute('data-employee-id') || '';
                    var dlg = document.getElementById('idp-view-modal');
                    var frame = document.getElementById('idp-view-frame');
                    if (!dlg || !frame) {
                        return;
                    }
                    frame.src = 'individual_dev_plan.php?employee_id=' + encodeURIComponent(empId) + '&view=1';
                    if (typeof dlg.showModal === 'function') {
                        dlg.showModal();
                    }
                });
            });
        })();
    </script>
    </div>
  </div>
<?php require('../../partials/footer.php') ?>
