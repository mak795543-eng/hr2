<?php

require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

if (!function_exists('h')) {
    function h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function extractMetaValue(string $plan, string $key): string
{
    $plan = (string)$plan;
    $key = preg_quote($key, '/');
    if (preg_match('/^' . $key . '\s*:\s*(.*)$/mi', $plan, $m)) {
        return trim((string)($m[1] ?? ''));
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'send_promotion') {
        $empId = trim((string)($_POST['employee_id'] ?? ''));
        $letterBody = trim((string)($_POST['letter_body'] ?? ''));
        if ($empId !== '') {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE pre_promotion_employees
                     SET promotion_status = 'promoted',
                         promotion_letter = ?,
                         promotion_sent_at = CURRENT_TIMESTAMP
                     WHERE employee_id = ?"
                );
                $stmt->execute([$letterBody, $empId]);
            } catch (Throwable $e) {
            }
        }
        header('Location: pre-promotion-table.php?sent=1');
        exit;
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$departmentFilter = (string)($_GET['department'] ?? 'all');

$departments = [];
try {
    $departments = getDepartments();
} catch (Throwable $e) {
    $departments = [];
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.employee_id LIKE ? OR e.position LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($departmentFilter !== 'all' && $departmentFilter !== '') {
    $where[] = 'e.department = ?';
    $params[] = $departmentFilter;
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$stmt = $pdo->prepare(
    "SELECT
         p.employee_id,
         p.name,
         p.competency_level,
         p.date_added,
         p.promotion_status,
         p.promotion_sent_at,
         e.position,
         e.department,
         e.competency,
         idp.development_plan,
         idp.idp_status,
         idp.target_date
     FROM pre_promotion_employees p
     JOIN employees e ON e.employee_id = p.employee_id
     JOIN individual_development_plans idp ON idp.employee_id = p.employee_id
     $whereSql
     ORDER BY p.date_added DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrePromotion = count($rows);

require('../../partials/header.php');
?>

<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen">
        <?php include '../../USM/sidebarr.php'; ?>

        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../../USM/navbar.php'; ?>

            <div class="max-w-7xl mx-auto px-6 py-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pre-promotion List</h1>
                        <p class="text-sm text-gray-600">Employees marked Succession Ready with completed IDPs.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white rounded-xl shadow-md px-4 py-3 flex items-center gap-3">
                            <div class="p-2 rounded-full bg-emerald-100">
                                <i data-lucide="award" class="h-5 w-5 text-emerald-600"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Total Candidates</div>
                                <div class="text-lg font-semibold text-gray-900"><?php echo (int)$totalPrePromotion; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow mb-6">
                    <div class="card-body">
                        <form action="pre-promotion-table.php" method="get" class="space-y-0">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[16rem]">
                                    <label class="label"><span class="label-text">Search</span></label>
                                    <input
                                        type="text"
                                        name="search"
                                        value="<?php echo h($search); ?>"
                                        placeholder="Search employee / ID / position"
                                        class="input input-bordered w-full" />
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

                                <div class="flex gap-2">
                                    <button type="submit" class="btn bg-violet-600 text-white hover:bg-violet-700 border-0">Filter</button>
                                    <a href="pre-promotion-table.php" class="btn btn-outline">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card bg-base-100 shadow">
                    <div class="card-body p-0">
                        <div class="overflow-x-auto">
                            <table class="table table-zebra table-sm">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Competency</th>
                                        <th>Target Role</th>
                                        <th>Readiness</th>
                                        <th>Dev Plan Status</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($rows) === 0): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-10 opacity-70">No pre-promotion candidates yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r): ?>
                                            <?php
                                            $planText = (string)($r['development_plan'] ?? '');
                                            $targetRole = extractMetaValue($planText, 'Target Succession Role');
                                            if ($targetRole === '') {
                                                $targetRole = 'Promotion Raise';
                                            }
                                            $readiness = extractMetaValue($planText, 'Readiness Level');
                                            if ($readiness === '') {
                                                $readiness = 'Ready Now';
                                            }
                                            $devPlanStatus = $readiness === 'Ready Now' ? 'Completed' : 'In Progress';
                                            $promotionStatus = (string)($r['promotion_status'] ?? 'pending');
                                            $canPromote = ($readiness === 'Ready Now' && $promotionStatus !== 'promoted');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="font-semibold"><?php echo h($r['name']); ?></div>
                                                    <div class="text-xs opacity-70"><?php echo h($r['employee_id']); ?></div>
                                                </td>
                                                <td><?php echo h($r['position']); ?></td>
                                                <td><?php echo h($r['department']); ?></td>
                                                <td class="font-semibold"><?php echo number_format((float)($r['competency'] ?? 0), 1); ?>%</td>
                                                <td>
                                                    <?php echo h($targetRole); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-sm <?php echo $readiness === 'Ready Now' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo h($readiness); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-sm <?php echo $devPlanStatus === 'In Progress' ? 'badge-info' : 'badge-neutral'; ?>">
                                                        <?php echo h($devPlanStatus); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo h($r['date_added']); ?></td>
                                                <td>
                                                    <?php if ($promotionStatus === 'promoted'): ?>
                                                        <span class="badge badge-sm badge-success">Promoted</span>
                                                    <?php elseif ($canPromote): ?>
                                                        <button
                                                            type="button"
                                                            class="btn btn-primary btn-xs promote-btn"
                                                            data-employee-id="<?php echo h($r['employee_id']); ?>"
                                                            data-employee-name="<?php echo h($r['name']); ?>"
                                                            data-current-role="<?php echo h($r['position']); ?>"
                                                            data-target-role="<?php echo h($targetRole); ?>"
                                                            data-readiness="<?php echo h($readiness); ?>">
                                                            Promote
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-xs text-gray-500">No action</span>
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
            </div>
        </div>
    </div>

    <div
        id="promotionModal"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl max-w-xl w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900" id="promotionModalTitle">Promotion Letter</h2>
                    <p class="text-xs text-gray-500" id="promotionModalSubtitle"></p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" id="promotionModalClose">Close</button>
            </div>
            <form method="post" action="pre-promotion-table.php" class="space-y-4">
                <input type="hidden" name="action" value="send_promotion" />
                <input type="hidden" name="employee_id" id="promotionEmployeeId" />
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Promotion Letter</label>
                    <textarea
                        id="promotionLetterBody"
                        name="letter_body"
                        class="w-full border border-gray-300 rounded-md p-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                        rows="10"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="btn btn-ghost" id="promotionModalCancel">Cancel</button>
                    <button type="submit" class="btn bg-gray-900 text-white hover:bg-gray-800">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            try {
                lucide.createIcons();
            } catch (e) {}

            var modal = document.getElementById('promotionModal');
            var closeBtn = document.getElementById('promotionModalClose');
            var cancelBtn = document.getElementById('promotionModalCancel');
            var empIdInput = document.getElementById('promotionEmployeeId');
            var letterTextarea = document.getElementById('promotionLetterBody');
            var titleEl = document.getElementById('promotionModalTitle');
            var subtitleEl = document.getElementById('promotionModalSubtitle');

            function openModal(data) {
                if (!modal || !empIdInput || !letterTextarea) return;
                empIdInput.value = data.employeeId;

                var name = data.employeeName;
                var currentRole = data.currentRole;
                var targetRole = data.targetRole;
                var readiness = data.readiness;

                if (titleEl) {
                    titleEl.textContent = 'Promotion for ' + name;
                }
                if (subtitleEl) {
                    subtitleEl.textContent = currentRole + ' ➜ ' + targetRole + ' • ' + readiness;
                }

                var body = '';
                body += 'Dear ' + name + ",\n\n";
                body += 'We are pleased to inform you that you have been recommended for promotion based on your performance, competency profile, and completion of your Individual Development Plan.\n\n';
                body += 'Current Role: ' + currentRole + "\n";
                body += 'Target Role: ' + targetRole + "\n";
                body += 'Readiness Status: ' + readiness + "\n\n";
                body += 'Please note that this promotion recommendation is subject to final management approval and completion of any remaining administrative steps.\n\n';
                body += 'Sincerely,\n';
                body += 'Human Resources';

                letterTextarea.value = body;

                modal.classList.remove('hidden');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
            }

            document.addEventListener('click', function(e) {
                var t = e.target;
                if (!t) return;
                if (t.classList.contains('promote-btn')) {
                    var data = {
                        employeeId: t.getAttribute('data-employee-id') || '',
                        employeeName: t.getAttribute('data-employee-name') || '',
                        currentRole: t.getAttribute('data-current-role') || '',
                        targetRole: t.getAttribute('data-target-role') || '',
                        readiness: t.getAttribute('data-readiness') || ''
                    };
                    openModal(data);
                }
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }
        })();
    </script>
</body>
