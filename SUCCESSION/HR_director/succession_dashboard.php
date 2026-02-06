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

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

try {
    $seedStmt = $pdo->query("SELECT DISTINCT employee_id FROM succession_submissions WHERE is_pushed = 1");
    $seedIds = $seedStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach (($seedIds ?? []) as $eid) {
        seedMissingKpiEvaluations((string)$eid, $period);
    }
} catch (Throwable $e) {
}

$where = [
    'ss.is_pushed = 1'
];
$params = [];

if ($departmentFilter !== 'all' && $departmentFilter !== '') {
    $where[] = 'ss.department = ?';
    $params[] = $departmentFilter;
}

if ($statusFilter !== 'all' && in_array($statusFilter, $allowedStatuses, true)) {
    $where[] = "(
        CASE
            WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
            WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
            WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
            WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
            ELSE 'Succession Ready'
        END
    ) = ?";
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
            COALESCE(gs.competency, 0) AS competency_level,
            CASE
                WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                ELSE 'Succession Ready'
            END AS status
     FROM succession_submissions ss
     LEFT JOIN (
         SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
         FROM employee_kpi_scores s2
         WHERE s2.evaluation_period = ?
         GROUP BY s2.employee_id
     ) gs ON gs.employee_id = ss.employee_id
     $whereSql
     ORDER BY ss.created_at DESC"
);
$stmt->execute(array_merge([$period], $params));
$rows = $stmt->fetchAll();

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

        <!-- Notification Container -->
        <div id="notificationContainer"></div>

    
        <div class="flex items-center justify-between mb-9">
            <div>
                <h1 class="text-2xl font-bold">Succession Dashboard</h1>
               
            </div>
            <div class="flex gap-2">
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
                                            <a
                                                class="btn btn-primary btn-sm"
                                                href="individual_dev_plan.php?employee_id=<?php echo urlencode($r['employee_id']); ?>"
                                                data-confirm-create="1"
                                            >
                                                Create IDP
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
        })();
    </script>
    </div>
  </div>
<?php require('../../partials/footer.php') ?>
