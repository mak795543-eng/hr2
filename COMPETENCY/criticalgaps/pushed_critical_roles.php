<?php
require_once __DIR__ . '/config.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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

$where = ['ss.is_pushed = 1'];
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
    $like = '%' . $search . '%';
    $where[] = '(ss.employee_name LIKE ? OR ss.employee_id LIKE ? OR ss.position LIKE ?)';
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
            END AS status,
            ss.idp_status,
            ss.created_at,
            ss.updated_at
     FROM succession_submissions ss
     LEFT JOIN (
         SELECT ss2.employee_id, ss2.department, AVG(COALESCE(es2.skill_score, 0)) AS competency
         FROM succession_submissions ss2
         JOIN skills s2
           ON s2.category = 'General Skills'
          AND s2.department = ss2.department
         LEFT JOIN employee_skills es2
           ON es2.employee_id = ss2.employee_id
          AND es2.skill_id = s2.id
         GROUP BY ss2.employee_id, ss2.department
     ) gs ON gs.employee_id = ss.employee_id AND gs.department = ss.department
     $whereSql
     ORDER BY ss.updated_at DESC, ss.created_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

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
<body class="bg-base-200 min-h-screen">
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
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Pushed Critical Roles (History)</h1>
                <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo count($rows); ?></span></div>
            </div>
            <div class="flex gap-2">
                <a href="gap_analysis.php" class="btn btn-outline btn-sm">Gap Analysis</a>
                <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
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
                        <a href="pushed_critical_roles.php" class="btn btn-outline">Reset</a>
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
                                <th>IDP Status</th>
                                <th>Pushed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-10 opacity-70">No pushed records found.</td>
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
                                            <span class="badge badge-sm <?php echo h(statusBadgeClass($r['status'])); ?>"><?php echo h($r['status']); ?></span>
                                        </td>
                                        <td><?php echo h($r['idp_status'] ?? ''); ?></td>
                                        <td class="text-sm opacity-70"><?php echo h($r['created_at'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
     <?php require('../../partials/footer.php') ?>