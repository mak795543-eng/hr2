<?php

require_once __DIR__ . '/config.php';

function h($v)
{
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
$filterStatus = ($statusFilter !== 'all' && in_array($statusFilter, $allowedStatuses, true)) ? $statusFilter : 'all';

$employees = getEmployees($filterStatus, $search, $departmentFilter);
$criticalTotal = (int)count($employees ?? []);
$criticalAvg = 0.0;
$deptSet = [];
foreach (($employees ?? []) as $e) {
    $criticalAvg += (float)($e['competency'] ?? 0);
    $deptSet[(string)($e['department'] ?? '')] = true;
}
$criticalAvg = $criticalTotal > 0 ? round($criticalAvg / $criticalTotal, 1) : 0.0;
$criticalDeptCount = count(array_filter(array_keys($deptSet), static fn($d) => trim((string)$d) !== ''));
require('../../partials/header.php');
?>

<body class="bg-base-200 min-h-screen">
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
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">Competency Profiles</h1>
                        <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo (int)count($employees); ?></span></div>
                    </div>
                    <div class="flex gap-2">
                        <a href="gap_analysis.php" class="btn btn-outline btn-sm">Gap Analysis</a>

                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Total Critical Roles</div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo $criticalTotal; ?></div>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i data-lucide="target" class="h-6 w-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Average Competency</div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($criticalAvg, 1); ?>%</div>
                            </div>
                            <div class="p-3 bg-emerald-100 rounded-full">
                                <i data-lucide="bar-chart-2" class="h-6 w-6 text-emerald-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Departments Covered</div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo (int)$criticalDeptCount; ?></div>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i data-lucide="building" class="h-6 w-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow mb-6">
                    <div class="card-body">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Competency Status Levels</h2>
                        <p class="text-sm text-gray-600 mb-3">
                            Employees in the competency profile are grouped into these development statuses:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                            <li>Retrain</li>
                            <li>Reskilling</li>
                            <li>Refresher Training</li>
                            <li>Upskilling</li>
                            <li>Succession Ready</li>
                        </ul>
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
                                <a href="criticalgaps.php" class="btn btn-outline">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $emp): ?>
                            <?php
                            $status = (string)($emp['status'] ?? 'Retrain');
                            $statusClass = 'badge-neutral';
                            if ($status === 'Reskilling') $statusClass = 'badge-error';
                            if ($status === 'Refresher Training') $statusClass = 'badge-warning';
                            if ($status === 'Upskilling') $statusClass = 'badge-info';
                            if ($status === 'Succession Ready') $statusClass = 'badge-success';

                            $chartColor = '#6b7280';
                            $progressColor = 'bg-gray-500';
                            if ((float)($emp['competency'] ?? 0) <= 20) {
                                $progressColor = 'bg-gray-500';
                                $chartColor = '#6b7280';
                            } elseif ((float)($emp['competency'] ?? 0) <= 40) {
                                $progressColor = 'bg-red-500';
                                $chartColor = '#dc2626';
                            } elseif ((float)($emp['competency'] ?? 0) <= 60) {
                                $progressColor = 'bg-amber-500';
                                $chartColor = '#d97706';
                            } elseif ((float)($emp['competency'] ?? 0) <= 80) {
                                $progressColor = 'bg-blue-500';
                                $chartColor = '#2563eb';
                            } else {
                                $progressColor = 'bg-emerald-500';
                                $chartColor = '#059669';
                            }

                            $developmentStatus = computeEmployeeDevelopmentStatus((string)($emp['employee_id'] ?? ''));
                            $badgeLabel = $developmentStatus !== '' ? $developmentStatus : $status;
                            $badgeClass = $statusClass;

                            if ($developmentStatus !== '') {
                                if ($developmentStatus === 'Forwarded for IDP') {
                                    $badgeClass = 'badge-info';
                                } elseif ($developmentStatus === 'IDP Created') {
                                    $badgeClass = 'badge-warning';
                                } elseif ($developmentStatus === 'Training Requested') {
                                    $badgeClass = 'badge-primary';
                                } elseif ($developmentStatus === 'On-going Training') {
                                    $badgeClass = 'badge-success';
                                } else {
                                    $badgeClass = 'badge-neutral';
                                }
                            }

                            $forwardDisabled = in_array($developmentStatus, ['Forwarded for IDP', 'IDP Created', 'Training Requested', 'On-going Training'], true);
                            ?>
                            <div class="employee-card bg-white border border-gray-300 rounded-xl shadow-sm" data-employee-id="<?php echo h($emp['employee_id'] ?? ''); ?>">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="flex items-start gap-4">
                                            <div class="employee-avatar bg-gray-100">
                                                <i data-lucide="user" class="w-8 h-8 text-gray-400"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-900 text-lg"><?php echo h($emp['full_name'] ?? ''); ?></h3>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo h($emp['employee_id'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                        <span class="badge badge-sm <?php echo h($badgeClass); ?> employee-status-badge whitespace-normal break-words text-center px-3">
                                            <?php echo h($badgeLabel); ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-center mb-6">
                                        <div class="competency-chart">
                                            <div class="chart-background" style="--percentage: 100"></div>
                                            <div class="chart-fill"
                                                style="--percentage: <?php echo min(100, $emp['competency']); ?>; --chart-color: <?php echo $chartColor; ?>"></div>
                                                <div class="chart-inner">
                                                    <div class="chart-value" style="color: <?php echo $chartColor; ?>">
                                                        <?php echo number_format($emp['competency'], 1); ?>%
                                                    </div>
                                                </div>

                                        </div>
                                    </div>


                                    <!-- Details -->
                                    <div class="space-y-1 mb-6">
                                        <div class="card-detail-item">
                                            <i data-lucide="briefcase" class="w-4 h-4 text-gray-400"></i>
                                            <span class="text-sm text-gray-600 flex-1">Position</span>
                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['position']); ?></span>
                                        </div>
                                        <div class="card-detail-item">
                                            <i data-lucide="building" class="w-4 h-4 text-gray-400"></i>
                                            <span class="text-sm text-gray-600 flex-1">Department</span>
                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['department'] ?? ''); ?></span>
                                        </div>
                                        <div class="card-detail-item">
                                            <i data-lucide="trending-up" class="w-4 h-4 text-gray-400"></i>
                                            <span class="text-sm text-gray-600 flex-1">Progress</span>
                                            <div class="w-24">
                                                <div class="progress-bar h-2 rounded-full overflow-hidden">
                                                    <div class="progress-fill h-full <?php echo $progressColor; ?>"
                                                        style="width: <?php echo min(100, $emp['competency']); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2">
                                            <button class="btn flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800"
                                                onclick="openViewModal('<?php echo $emp['employee_id']; ?>')"
                                                title="View Competency Details">
                                                <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                                View
                                            </button>
                                            <button class="btn flex-1 bg-gray-900 text-white hover:bg-gray-800 border-0 idp-btn"
                                                data-employee-id="<?php echo $emp['employee_id']; ?>"
                                                data-employee-name="<?php echo htmlspecialchars($emp['full_name']); ?>"
                                                title="Forward for Individual Development Plan"
                                                <?php echo $forwardDisabled ? 'disabled' : ''; ?>>
                                                <i data-lucide="clipboard-list" class="w-4 h-4 mr-2"></i>
                                                Forward for IDP
                                            </button>
                                        </div>
                                        <?php if ($developmentStatus === ''): ?>
                                            <div class="text-xs text-gray-500 text-right">
                                                <?php echo h($status); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4 border border-gray-300 mx-auto">
                                <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No employees found</h3>
                            <p class="text-gray-500">Try adjusting your filters or search term</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Total Count -->
            <div class="text-right mt-4 text-sm text-gray-600">
                Total: <span class="font-medium"><?php echo count($employees); ?></span> employees
            </div>
        </div>

        <!-- ============================================
         MODALS SECTION
    ============================================ -->

        <!-- View Details Modal -->
        <dialog id="view-modal" class="modal modal-lg">
            <div class="modal-box bg-white border border-gray-300 p-0 max-w-5xl">
                <div class="p-6 border-b border-gray-300 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                                <i data-lucide="bar-chart" class="w-5 h-5 text-gray-600"></i>
                                Competency Assessment
                            </h3>
                            <p class="text-sm text-gray-600 mt-1" id="employee-subtitle"></p>
                        </div>
                        <button onclick="document.getElementById('view-modal').close()"
                            class="btn btn-sm btn-circle bg-transparent border-0 hover:bg-gray-200 text-gray-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div id="employee-details-content">
                        <div class="text-center py-12">
                            <div class="loading loading-spinner loading-lg text-gray-600"></div>
                            <p class="mt-4 text-gray-600">Loading competency assessment...</p>
                        </div>
                    </div>
                </div>
            </div>
        </dialog>

        <!-- Legend Modal -->
        <dialog id="legend-modal" class="modal">
            <div class="modal-box bg-white border border-gray-300 p-0 max-w-md">
                <div class="p-6 border-b border-gray-300">
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-gray-600"></i>
                        Proficiency Level Legend
                    </h3>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                        <div class="w-3 h-3 rounded-full bg-gray-500 mt-1 mr-3 flex-shrink-0"></div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Retrain (0% - 20%)</h4>
                            <p class="text-sm text-gray-600 mt-1">Employees requiring continued support and monitoring.</p>
                        </div>
                    </div>

                    <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                        <div class="w-3 h-3 rounded-full bg-red-500 mt-1 mr-3 flex-shrink-0"></div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Reskilling (21% - 40%)</h4>
                            <p class="text-sm text-gray-600 mt-1">Employees requiring fundamental skill development and retraining.</p>
                        </div>
                    </div>

                    <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                        <div class="w-3 h-3 rounded-full bg-amber-500 mt-1 mr-3 flex-shrink-0"></div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Refresher Training (41% - 60%)</h4>
                            <p class="text-sm text-gray-600 mt-1">Employees needing reinforcement through refresher training.</p>
                        </div>
                    </div>

                    <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                        <div class="w-3 h-3 rounded-full bg-blue-500 mt-1 mr-3 flex-shrink-0"></div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Upskilling (61% - 80%)</h4>
                            <p class="text-sm text-gray-600 mt-1">Competent employees ready for advanced skill development.</p>
                        </div>
                    </div>

                    <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 mt-1 mr-3 flex-shrink-0"></div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Succession Ready (81% - 100%)</h4>
                            <p class="text-sm text-gray-600 mt-1">High performers ready for leadership roles and succession planning.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-action p-6 border-t border-gray-300 bg-gray-50">
                    <form method="dialog">
                        <button class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">Close</button>
                    </form>
                </div>
            </div>
        </dialog>

        <!-- Success Toast -->
        <div id="success-toast" class="toast toast-top toast-end hidden">
            <div class="alert alert-success bg-emerald-50 border border-emerald-200 text-emerald-800">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span id="toast-message">Operation completed successfully!</span>
            </div>
        </div>

        <script>
            // Initialize Lucide icons
            lucide.createIcons();

            const employeesForBulkPush = <?php echo json_encode($employees ?? []); ?>;

            // Function to get status color class
            function getStatusClass(status) {
                return `status-${status.toLowerCase().replace(' ', '-')}`;
            }

            // Function to get progress bar color
            function getProgressColor(percentage) {
                percentage = parseFloat(percentage) || 0;
                if (percentage <= 20) return "bg-gray-500";
                if (percentage <= 40) return "bg-red-500";
                if (percentage <= 60) return "bg-amber-500";
                if (percentage <= 80) return "bg-blue-500";
                return "bg-emerald-500";
            }

            // Function to get status based on percentage
            function getStatus(percentage) {
                percentage = parseFloat(percentage) || 0;
                if (percentage <= 20) return "Retrain";
                if (percentage <= 40) return "Reskilling";
                if (percentage <= 60) return "Refresher Training";
                if (percentage <= 80) return "Upskilling";
                return "Succession Ready";
            }

            // Function to get skill category icon
            function getSkillCategoryIcon(category) {
                const icons = {
                    'Technical': 'wrench',
                    'Soft Skills': 'users',
                    'Leadership': 'award',
                    'Industry Knowledge': 'book-open',
                    'Safety': 'shield'
                };
                return icons[category] || 'circle';
            }

            // Function to get skill score color
            function getSkillScoreColor(score) {
                score = parseFloat(score) || 0;
                if (score < 40) return 'text-red-600';
                if (score < 70) return 'text-amber-600';
                return 'text-emerald-600';
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatAssessmentDate(dateStr) {
                if (!dateStr) return 'N/A';
                const d = new Date(dateStr);
                return Number.isNaN(d.getTime()) ? 'N/A' : d.toLocaleDateString();
            }

            // ============================================
            // VIEW MODAL FUNCTIONS - FIXED
            // ============================================

            async function openViewModal(employeeId) {
                const modal = document.getElementById('view-modal');
                const content = document.getElementById('employee-details-content');
                const subtitle = document.getElementById('employee-subtitle');

                // Show loading state
                content.innerHTML = `
            <div class="text-center py-12">
                <div class="loading loading-spinner loading-lg text-gray-600"></div>
                <p class="mt-4 text-gray-600">Loading competency assessment...</p>
            </div>
        `;

                modal.showModal();

                try {
                    // Fetch employee data
                    const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    // Set subtitle
                    subtitle.textContent = `${employee.full_name} | ${employee.position} | ${employee.department}`;

                    const maxScore = 5;
                    const kpis = Array.isArray(employee.kpis) ? employee.kpis : [];
                    const analysis = employee.analysis || {};
                    const computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                    const overallObj = analysis.overall || {};
                    const overallPct = Number.isFinite(Number(overallObj.pct)) ? Number(overallObj.pct) : (parseFloat(employee.competency) || 0);
                    const overallStatus = String(overallObj.status || employee.status || 'Retrain');

                    const kpisHtml = kpis.length > 0 ? kpis.map((kpi) => {
                        const name = String(kpi.kpi_name ?? kpi.kpi ?? '');
                        const evaluations = Array.isArray(kpi.evaluations) ? kpi.evaluations : [];

                        const rows = evaluations.map((ev) => {
                            const s = Number(ev.score);
                            const sSafe = Number.isFinite(s) ? s : 0;
                            const sPct = Math.max(0, Math.min(100, (sSafe / maxScore) * 100));
                            return `
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">${escapeHtml(ev.criteria ?? '')}</td>
                            <td class="py-2 px-4 border-b border-gray-300 text-right">
                                <div class="font-semibold ${getSkillScoreColor(sPct)}">${sSafe.toFixed(1)} / ${maxScore}</div>
                                <div class="text-xs text-gray-500">${sPct.toFixed(0)}%</div>
                            </td>
                        </tr>
                    `;
                        }).join('');

                        return `
                    <div class="border border-gray-300 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3">
                            <div class="font-semibold text-gray-900">${escapeHtml(name)}</div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Evaluations</div>
                                <div class="text-xs text-gray-500">(Analyze to see KPI gaps)</div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr class="bg-white">
                                        <th class="text-gray-700 py-2 px-4 border-b border-gray-300">Criteria</th>
                                        <th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    ${rows || `
                                        <tr>
                                            <td colspan="2" class="py-8 text-center text-gray-500">No evaluations</td>
                                        </tr>
                                    `}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                    }).join('') : `
                <div class="text-center py-12 text-gray-500">
                    <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i>
                    <p class="text-lg font-medium">No KPI evaluations available</p>
                </div>
            `;

                    const analysisRows = computed.map((r) => {
                        const kpiName = String(r.kpi_name ?? '');
                        const kpiPct = Number(r.kpi_pct ?? 0);
                        const reqPct = Number(r.required_pct ?? 0);
                        const gapPct = Number(r.gap_pct ?? 0);
                        const badge = gapPct > 0 ? 'badge-error' : 'badge-success';
                        return `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">${escapeHtml(kpiName)}</td>
                        <td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">${kpiPct.toFixed(1)}%</td>
                        <td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">${reqPct.toFixed(1)}%</td>
                        <td class="py-2 px-4 border-b border-gray-300 text-right"><span class="badge ${badge}">${gapPct.toFixed(1)}%</span></td>
                    </tr>
                `;
                    }).join('');

                    content.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-gray-50 p-5 rounded-lg border border-gray-300">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">${escapeHtml(employee.full_name)}</h3>
                                        <div class="flex flex-wrap gap-3 mt-1">
                                            <span class="text-gray-700">${escapeHtml(employee.employee_id)}</span>
                                            <span class="text-gray-600">•</span>
                                            <span class="text-gray-700">${escapeHtml(employee.position)}</span>
                                            <span class="text-gray-600">•</span>
                                            <span class="text-gray-700">${escapeHtml(employee.department)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Evaluation</div>
                            </div>
                        </div>
                    </div>

                    <div id="analysis-block" class="space-y-4">
                        <div class="bg-gray-50 p-5 rounded-lg border border-gray-300">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm text-gray-600">Overall Competency</div>
                                    <div class="text-3xl font-bold text-gray-900 mt-1">${overallPct.toFixed(1)}%</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600">Status</div>
                                    <div class="mt-1"><span class="status-badge ${getStatusClass(overallStatus)}">${escapeHtml(overallStatus)}</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3">
                                <div class="font-semibold text-gray-900">KPI Analysis</div>
                                <div class="text-xs text-gray-500">Actual vs Required vs Gap</div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead>
                                        <tr class="bg-white">
                                            <th class="text-gray-700 py-2 px-4 border-b border-gray-300">KPI</th>
                                            <th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Actual</th>
                                            <th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Required</th>
                                            <th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Gap</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        ${analysisRows || `
                                            <tr>
                                                <td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td>
                                            </tr>
                                        `}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-2">
                            <button id="forward-critical-btn" class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 flex-1">
                                <i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>
                                Forward Role to Critical Roles
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        ${kpisHtml}
                    </div>

                    <div class="flex justify-between items-center pt-6 border-t border-gray-300">
                        <div class="text-sm text-gray-600">
                            Last assessment: ${employee.last_assessment ? new Date(employee.last_assessment).toLocaleDateString() : 'N/A'}
                            ${employee.next_review_date ? ` | Next review: ${new Date(employee.next_review_date).toLocaleDateString()}` : ''}
                        </div>
                        <div class="flex gap-2">
                            <button onclick="showIDPConfirmation('${employee.employee_id}', '${employee.full_name}')" 
                                    class="btn bg-gray-900 text-white hover:bg-gray-800 border-0">
                                <i data-lucide="clipboard-list" class="w-4 h-4 mr-2"></i>
                                Forward for IDP
                            </button>
                            <button onclick="document.getElementById('view-modal').close()" 
                                    class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;

                    const forwardBtn = document.getElementById('forward-critical-btn');
                    if (forwardBtn) {
                        forwardBtn.addEventListener('click', async () => {
                            try {
                                const evalPeriod = String(employee.evaluation_period || '').trim() || '';
                                const res = await fetch('forward_to_critical.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        employee_id: employee.employee_id,
                                        evaluation_period: evalPeriod
                                    })
                                });
                                const json = await res.json();
                                if (!res.ok || !json || json.success !== true) {
                                    throw new Error(json?.message || 'Failed to forward');
                                }

                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Forwarded',
                                    text: 'Employee forwarded to Critical Roles.',
                                    confirmButtonColor: '#1f2937'
                                });

                                const card = document.querySelector(`.employee-card[data-employee-id="${CSS.escape(employee.employee_id)}"]`);
                                if (card) {
                                    card.remove();
                                }

                                document.getElementById('view-modal').close();
                            } catch (err) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: (err && err.message) ? err.message : 'Server error',
                                    confirmButtonColor: '#1f2937'
                                });
                            }
                        });
                    }

                } catch (error) {
                    content.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Load Assessment</h3>
                    <p class="text-gray-500">${error.message}</p>
                    <button onclick="document.getElementById('view-modal').close()" 
                            class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4">
                        Close
                    </button>
                </div>
            `;
                }

                setTimeout(() => lucide.createIcons(), 100);
            }

            function calculateCompetencyBreakdown(skills) {
                let technical = {
                    total: 0,
                    count: 0
                };
                let soft = {
                    total: 0,
                    count: 0
                };
                let other = {
                    total: 0,
                    count: 0
                };

                skills.forEach(skill => {
                    if (skill.category === 'Technical' || skill.category === 'Safety') {
                        technical.total += parseFloat(skill.skill_score) || 0;
                        technical.count++;
                    } else if (skill.category === 'Soft Skills') {
                        soft.total += parseFloat(skill.skill_score) || 0;
                        soft.count++;
                    } else {
                        other.total += parseFloat(skill.skill_score) || 0;
                        other.count++;
                    }
                });

                return {
                    technical: technical.count > 0 ? Math.round(technical.total / technical.count) : 0,
                    soft: soft.count > 0 ? Math.round(soft.total / soft.count) : 0,
                    other: other.count > 0 ? Math.round(other.total / other.count) : 0,
                    technicalDetails: technical,
                    softDetails: soft,
                    otherDetails: other
                };
            }

            // ============================================
            // IDP FUNCTIONS - FIXED
            // ============================================

            // Add event listeners to IDP buttons
            document.addEventListener('DOMContentLoaded', function() {
                var pushAllBtn = document.getElementById('push-all-to-succession');
                if (pushAllBtn) {
                    pushAllBtn.addEventListener('click', async function() {
                        try {
                            var employees = Array.isArray(employeesForBulkPush) ? employeesForBulkPush : [];
                            if (employees.length === 0) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'No employees',
                                    text: 'No employees are currently displayed to push.'
                                });
                                return;
                            }



                            if (!confirmRes.isConfirmed) {
                                return;
                            }

                            var payload = employees.map(function(e) {
                                return {
                                    employee_id: e.employee_id,
                                    employee_name: e.full_name,
                                    position: e.position,
                                    department: e.department
                                };
                            }).filter(function(e) {
                                return e.employee_id && e.employee_name && e.position && e.department;
                            });

                            if (payload.length === 0) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'No valid employees',
                                    text: 'No valid employee records were found to push.'
                                });
                                return;
                            }

                            Swal.fire({
                                title: 'Pushing...',
                                text: 'Please wait while we submit the employees.',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });

                            var res = await fetch('submit_to_succession.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    employees: payload
                                })
                            });
                            var json = await res.json();

                            if (!json || json.success !== true) {
                                throw new Error(json && json.message ? json.message : 'Failed to push employees');
                            }

                            await Swal.fire({
                                icon: 'success',
                                title: 'Pushed to Succession',
                                text: `Inserted/updated ${json.inserted ?? payload.length} employee(s).`,
                                confirmButtonColor: '#1f2937'
                            });
                        } catch (err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (err && err.message) ? err.message : 'Server error',
                                confirmButtonColor: '#1f2937'
                            });
                        }
                    });
                }

                document.querySelectorAll('.idp-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const employeeId = this.getAttribute('data-employee-id');
                        const employeeName = this.getAttribute('data-employee-name');
                        showIDPConfirmation(employeeId, employeeName);
                    });
                });
            });

            // Function to show IDP confirmation dialog
            function showIDPConfirmation(employeeId, employeeName) {
                Swal.fire({
                    title: 'Forward for Individual Development Plan?',
                    html: `Do you want to Forward this to Individual Development Plan for <strong>${employeeName}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Forward for IDP',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#1f2937',
                    cancelButtonColor: '#6b7280',
                    showLoaderOnConfirm: true,
                    preConfirm: async () => {
                        return await createIDP(employeeId, employeeName);
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Success!',
                            text: `Sent to Succession Dashboard for ${employeeName}`,
                            icon: 'success',
                            confirmButtonColor: '#1f2937'
                        });
                    }
                });
            }

            // Function to create IDP
            async function createIDP(employeeId, employeeName) {
                try {
                    // Fetch employee data
                    const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    // Prepare IDP data
                    const idpData = {
                        employee_id: employeeId,
                        employee_name: employeeName,
                        position: employee.position || '',
                        department: employee.department || '',
                        current_competency: parseFloat(employee.competency) || 0,
                        status: employee.status || 'Retrain',
                        skills: employee.skills || [],
                        created_date: new Date().toISOString().split('T')[0]
                    };

                    const submitResponse = await fetch('submit_to_succession.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            employee_id: idpData.employee_id,
                            employee_name: idpData.employee_name,
                            position: idpData.position,
                            department: idpData.department
                        })
                    });

                    const submitResult = await submitResponse.json();
                    if (!submitResult || submitResult.success !== true) {
                        throw new Error(submitResult?.message || 'Failed to submit to Succession Dashboard');
                    }

                    const card = document.querySelector(`.employee-card[data-employee-id="${CSS.escape(employeeId)}"]`);
                    if (card) {
                        const badge = card.querySelector('.employee-status-badge');
                        if (badge) {
                            badge.textContent = 'Forwarded for IDP';
                            badge.className = 'badge badge-sm badge-info employee-status-badge';
                        }
                        const btn = card.querySelector('.idp-btn');
                        if (btn) {
                            btn.disabled = true;
                        }
                    }

                    return true;

                } catch (error) {
                    console.error("Error creating IDP:", error);
                    Swal.fire({
                        title: 'Error',
                        text: `Error creating IDP: ${error.message}`,
                        icon: 'error',
                        confirmButtonColor: '#1f2937'
                    });
                    return false;
                }
            }

            // Function to create IDP for specific skill
            async function createIDPForSkill(employeeId, employeeName, skillName) {
                try {
                    // Fetch employee data
                    const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    // Find the specific skill
                    const skill = (employee.skills || []).find(s => s.skill_name === skillName);

                    // Prepare IDP data with focus on specific skill
                    const idpData = {
                        employee_id: employeeId,
                        employee_name: employeeName,
                        position: employee.position || '',
                        department: employee.department || '',
                        current_competency: parseFloat(employee.competency) || 0,
                        status: employee.status || 'Retrain',
                        focus_skill: skillName,
                        focus_skill_score: skill ? skill.skill_score : 0,
                        all_skills: employee.skills || [],
                        created_date: new Date().toISOString().split('T')[0]
                    };

                    const submitResponse = await fetch('submit_to_succession.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            employee_id: idpData.employee_id,
                            employee_name: idpData.employee_name,
                            position: idpData.position,
                            department: idpData.department
                        })
                    });

                    const submitResult = await submitResponse.json();
                    if (!submitResult || submitResult.success !== true) {
                        throw new Error(submitResult?.message || 'Failed to submit to Succession Dashboard');
                    }

                    const card = document.querySelector(`.employee-card[data-employee-id="${CSS.escape(employeeId)}"]`);
                    if (card) {
                        card.remove();
                    }

                    return true;

                } catch (error) {
                    console.error("Error creating IDP for skill:", error);
                    Swal.fire({
                        title: 'Error',
                        text: `Error creating IDP: ${error.message}`,
                        icon: 'error',
                        confirmButtonColor: '#1f2937'
                    });
                    return false;
                }
            }

            // ============================================
            // UTILITY FUNCTIONS
            // ============================================

            // Close modals when clicking outside
            document.getElementById('view-modal').addEventListener('click', function(event) {
                if (event.target === this) this.close();
            });

            document.getElementById('legend-modal').addEventListener('click', function(event) {
                if (event.target === this) this.close();
            });
        </script>
        <?php require('../../partials/footer.php'); ?>
