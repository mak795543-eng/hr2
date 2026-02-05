<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

$stmt = $pdo->query(
    "SELECT r.employee_id,
            r.employee_name,
            r.position,
            r.department,
            r.status AS request_status,
            r.created_at,
            r.updated_at,
            COALESCE(gs.competency, 0) AS competency_level,
            CASE
                WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                ELSE 'Succession Ready'
            END AS status
     FROM requested_to_idp r
     LEFT JOIN (
         SELECT r2.employee_id,
                r2.department,
                AVG(COALESCE(es2.skill_score, 0)) AS competency
         FROM requested_to_idp r2
         JOIN skills s2
           ON s2.category = 'General Skills'
          AND s2.department = r2.department
         LEFT JOIN employee_skills es2
           ON es2.employee_id = r2.employee_id
          AND es2.skill_id = s2.id
         GROUP BY r2.employee_id, r2.department
     ) gs ON gs.employee_id = r.employee_id AND gs.department = r.department
     WHERE r.status = 'Pending'
     ORDER BY COALESCE(r.updated_at, r.created_at) DESC"
);
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
    
            
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Requested to IDP</h1>
                <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo count($rows); ?></span></div>
            </div>
            <div class="flex gap-2">
                <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Dashboard</a>
                <a href="individual_development_plans.php" class="btn btn-outline btn-sm">IDP Repository</a>
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
            })();
        </script>

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
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-10 opacity-70">No pending requests.</td>
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
                                        <td class="text-sm opacity-70"><?php echo h($r['updated_at'] ?? $r['created_at'] ?? ''); ?></td>
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
