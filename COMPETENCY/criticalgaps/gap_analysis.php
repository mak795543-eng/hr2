<?php
require('../../partials/header.php');
?>

<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
        <?php include '../../USM/sidebarr.php'; ?>

        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../../USM/navbar.php'; ?>

            <?php
            require_once __DIR__ . '/config.php';
            try {
                ensureKpiSchema();
                ensureGapFormulationSchema();
                $stmt = $pdo->prepare("
                    SELECT 
                        e.employee_id,
                        e.full_name,
                        e.department,
                        e.position,
                        COALESCE(kgf.overall_competency, 0) AS overall_competency,
                        COALESCE(kgf.status, 'Not Evaluated') AS status
                    FROM employees e
                    LEFT JOIN (
                        SELECT g1.employee_id, g1.overall_competency, g1.status
                        FROM kpi_gap_formulations g1
                        INNER JOIN (
                            SELECT employee_id, MAX(updated_at) AS max_updated_at
                            FROM kpi_gap_formulations
                            GROUP BY employee_id
                        ) g2
                          ON g2.employee_id = g1.employee_id
                         AND g2.max_updated_at = g1.updated_at
                    ) kgf
                      ON kgf.employee_id = e.employee_id
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM kpi_gap_formulations gx
                        WHERE gx.employee_id = e.employee_id
                          AND COALESCE(gx.forwarded_to_critical, 0) = 1
                    )
                    ORDER BY e.department ASC, e.full_name ASC
                ");
                $stmt->execute([]);
                $serverEmployees = $stmt->fetchAll();
                $totalEmployees = (int)($pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn() ?? 0);
                $analyzedEmployees = (int)($pdo->query("SELECT COUNT(DISTINCT employee_id) FROM kpi_gap_formulations")->fetchColumn() ?? 0);
                $forwardedEmployees = (int)($pdo->query("SELECT COUNT(DISTINCT employee_id) FROM kpi_gap_formulations WHERE COALESCE(forwarded_to_critical,0)=1")->fetchColumn() ?? 0);
                $notAnalyzedEmployees = max(0, $totalEmployees - $analyzedEmployees);
            } catch (Throwable $e) {
                $serverEmployees = [];
                $totalEmployees = 0;
                $analyzedEmployees = 0;
                $forwardedEmployees = 0;
                $notAnalyzedEmployees = 0;
            }
            if (!is_array($serverEmployees)) $serverEmployees = [];
            if (count($serverEmployees) === 0) {
                try {
                    $ess = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=hr2_employee_self_service;charset=utf8mb4",
                        DB_USER,
                        DB_PASS,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );
                    $analyzed = [];
                    try {
                        $r = $pdo->query("SELECT DISTINCT employee_id FROM kpi_gap_formulations");
                        $rowsA = $r ? $r->fetchAll() : [];
                        foreach ($rowsA as $ar) {
                            $analyzed[(string)($ar['employee_id'] ?? '')] = true;
                        }
                    } catch (Throwable $e2) {
                    }
                    $resEss = $ess->query("SELECT employee_no, first_name, last_name, department, position FROM employees");
                    $rowsEss = $resEss ? $resEss->fetchAll() : [];
                    foreach ($rowsEss as $er) {
                        $empId = trim((string)($er['employee_no'] ?? ''));
                        if ($empId === '') continue;
                        if (isset($analyzed[$empId])) continue;
                        $full = trim((string)($er['first_name'] ?? '') . ' ' . (string)($er['last_name'] ?? ''));
                        if ($full === '') $full = $empId;
                        $serverEmployees[] = [
                            'employee_id' => $empId,
                            'full_name' => $full,
                            'department' => (string)($er['department'] ?? ''),
                            'position' => (string)($er['position'] ?? ''),
                            'overall_competency' => 0,
                            'status' => 'Not Evaluated',
                        ];
                    }
                    $totalEmployees = max($totalEmployees, (int)count($rowsEss));
                    $notAnalyzedEmployees = max($notAnalyzedEmployees, (int)count($serverEmployees));
                } catch (Throwable $e) {
                }
            }
            usort($serverEmployees, static function ($a, $b) {
                return strcasecmp((string)($a['full_name'] ?? ''), (string)($b['full_name'] ?? ''));
            });
            ?>

            <div class="max-w-7xl mx-auto p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">Skill Gap Analysis</h1>
                        <div class="text-sm opacity-70">All employees with their competency gap status.</div>
                    </div>
                    <div class="flex gap-2">
                        <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Total Employees</div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo (int)$totalEmployees; ?></div>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Not Yet Analyzed</div>
                                <div class="text-2xl font-bold text-gray-900" id="count-not-analyzed"><?php echo (int)$notAnalyzedEmployees; ?></div>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i data-lucide="search" class="h-6 w-6 text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="hr2-summary-card rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Forwarded to Critical Roles</div>
                                <div class="text-2xl font-bold text-gray-900" id="count-forwarded"><?php echo (int)$forwardedEmployees; ?></div>
                            </div>
                            <div class="p-3 bg-emerald-100 rounded-full">
                                <i data-lucide="arrow-right" class="h-6 w-6 text-emerald-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mb-4">
                    <button id="actionLogBtn" class="btn btn-outline">
                        <i data-lucide="list" class="w-4 h-4 mr-2"></i>
                        Action Log
                    </button>
                </div>

                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                            <div class="flex gap-2">
                                <button type="button" id="refreshBtn" class="btn btn-primary">Refresh</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra table-sm">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Dept</th>
                                        <th>Position</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="employeeRows">
                                    <?php if (count($serverEmployees) > 0): ?>
                                        <?php foreach ($serverEmployees as $r): ?>
                                            <tr data-employee-id="<?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?>">
                                                <td>
                                                    <div class="font-semibold"><?php echo htmlspecialchars((string)($r['full_name'] ?? '')); ?></div>
                                                    <div class="text-xs opacity-70"><?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars((string)($r['department'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars((string)($r['position'] ?? '')); ?></td>
                                                <td class="text-center">
                                                    <button
                                                        class="btn btn-xs btn-outline view-employee-btn"
                                                        data-employee-id="<?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?>">
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-10 opacity-70">No employees found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Action Log Modal -->
    <dialog id="action-log-modal" class="modal modal-md">
        <div class="modal-box bg-white border border-gray-300 p-0 max-w-3xl">
            <div class="p-6 border-b border-gray-300 bg-gray-50">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <i data-lucide="list" class="w-5 h-5 text-gray-600"></i>
                        Forwarded to Competency Profile
                    </h3>
                    <button onclick="document.getElementById('action-log-modal').close()"
                        class="btn btn-sm btn-circle bg-transparent border-0 hover:bg-gray-200 text-gray-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                <ul id="action-log-modal-list" class="space-y-2">
                    <?php
                    try {
                        $stmtLog = $pdo->query("SELECT kgf.employee_id, e.full_name, e.department, e.position, kgf.forwarded_at
                                            FROM kpi_gap_formulations kgf
                                            JOIN employees e ON e.employee_id = kgf.employee_id
                                            WHERE COALESCE(kgf.forwarded_to_critical,0)=1
                                            ORDER BY kgf.forwarded_at DESC");
                        $rowsLog = $stmtLog ? $stmtLog->fetchAll() : [];
                        if (count($rowsLog) === 0) {
                            echo '<li class="text-gray-500">No forwarded employees yet</li>';
                        } else {
                            foreach ($rowsLog as $log) {
                                echo '<li class="flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3">'
                                    . '<div class="flex items-center gap-3">'
                                    . '<i data-lucide="arrow-right" class="w-4 h-4 text-emerald-600"></i>'
                                    . '<span class="font-semibold">' . htmlspecialchars((string)($log['full_name'] ?? '')) . '</span>'
                                    . '<span class="text-xs text-gray-500">' . htmlspecialchars((string)($log['employee_id'] ?? '')) . '</span>'
                                    . '</div>'
                                    . '<div class="flex items-center gap-3">'
                                    . '<div class="text-xs text-gray-600">' . htmlspecialchars((string)($log['position'] ?? '')) . ' • ' . htmlspecialchars((string)($log['department'] ?? '')) . ' • ' . htmlspecialchars((string)($log['forwarded_at'] ?? '')) . '</div>'
                                    . '<button class="btn btn-xs btn-outline action-log-view-btn" data-employee-id="' . htmlspecialchars((string)($log['employee_id'] ?? '')) . '">View</button>'
                                    . '</div>'
                                    . '</li>';
                            }
                        }
                    } catch (Throwable $e) {
                        echo '<li class="text-gray-500">Unable to load action log</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </dialog>

    <!-- Analysis Modal -->
    <dialog id="analysis-modal" class="modal modal-lg">
        <div class="modal-box bg-white border border-gray-300 p-0 max-w-4xl">
            <div class="p-6 border-b border-gray-300 bg-gray-50">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                            <i data-lucide="calculator" class="w-5 h-5 text-gray-600"></i>
                            KPI Gap Computation
                        </h3>
                        <p class="text-sm text-gray-600 mt-1" id="analysis-subtitle"></p>
                    </div>
                    <button onclick="document.getElementById('analysis-modal').close()"
                        class="btn btn-sm btn-circle bg-transparent border-0 hover:bg-gray-200 text-gray-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="analysis-content">
                    <div class="text-center py-12">
                        <div class="loading loading-spinner loading-lg text-gray-600"></div>
                        <p class="mt-4 text-gray-600">Computing KPI gaps...</p>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize elements
            const refreshBtn = document.getElementById('refreshBtn');
            const actionLogBtn = document.getElementById('actionLogBtn');
            const viewModal = document.getElementById('view-modal');
            const analysisModal = document.getElementById('analysis-modal');
            const actionLogModal = document.getElementById('action-log-modal');

            // Refresh button
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    window.location.reload();
                });
            }

            // Action Log button
            if (actionLogBtn && actionLogModal) {
                actionLogBtn.addEventListener('click', function() {
                    if (typeof actionLogModal.showModal === 'function') {
                        actionLogModal.showModal();
                    } else {
                        actionLogModal.setAttribute('open', '');
                        actionLogModal.classList.add('modal-open');
                    }
                    setTimeout(() => lucide.createIcons(), 50);
                });
            }

            // View Employee buttons
            document.addEventListener('click', function(e) {
                // Handle table view buttons
                if (e.target.closest('.view-employee-btn')) {
                    const btn = e.target.closest('.view-employee-btn');
                    const employeeId = btn.getAttribute('data-employee-id');
                    if (employeeId) {
                        openViewModal(employeeId);
                    }
                }

                // Handle action log view buttons
                if (e.target.closest('.action-log-view-btn')) {
                    const btn = e.target.closest('.action-log-view-btn');
                    const employeeId = btn.getAttribute('data-employee-id');
                    if (employeeId) {
                        actionLogModal.close();
                        openViewModal(employeeId, {
                            showAnalyze: false
                        });
                    }
                }

                // Handle analyze button (will be added dynamically)
                if (e.target.closest('#analyze-btn')) {
                    const btn = e.target.closest('#analyze-btn');
                    const employeeId = btn.getAttribute('data-employee-id');
                    if (employeeId) {
                        openAnalysisModal(employeeId);
                    }
                }

                // Handle proceed button (will be added dynamically)
                if (e.target.closest('#proceed-btn')) {
                    const btn = e.target.closest('#proceed-btn');
                    const employeeId = btn.getAttribute('data-employee-id');
                    if (employeeId) {
                        forwardToCritical(employeeId);
                    }
                }
            });

            // Open View Modal function
            window.openViewModal = async function(employeeId, options = {}) {
                if (!viewModal) return;

                const content = document.getElementById('employee-details-content');
                const subtitle = document.getElementById('employee-subtitle');
                const showAnalyze = options && options.showAnalyze !== false;

                // Show loading
                content.innerHTML = `
                    <div class="text-center py-12">
                        <div class="loading loading-spinner loading-lg text-gray-600"></div>
                        <p class="mt-4 text-gray-600">Loading competency assessment...</p>
                    </div>
                `;

                // Show modal
                if (typeof viewModal.showModal === 'function') {
                    viewModal.showModal();
                } else {
                    viewModal.setAttribute('open', '');
                    viewModal.classList.add('modal-open');
                }

                try {
                    // Fetch employee details
                    const response = await fetch('get_employee_details.php?id=' + encodeURIComponent(employeeId));
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    // Update subtitle
                    subtitle.textContent = `${employee.full_name || ''} | ${employee.position || ''} | ${employee.department || ''}`;

                    // Build KPI HTML
                    const kpis = Array.isArray(employee.kpis) ? employee.kpis : [];
                    let kpisHtml = '';

                    if (kpis.length > 0) {
                        kpisHtml = kpis.map(kpi => {
                            const name = kpi.kpi_name || kpi.kpi || '';
                            const evaluations = Array.isArray(kpi.evaluations) ? kpi.evaluations : [];
                            const rows = evaluations.map(ev => {
                                const score = Number(ev.score) || 0;
                                const scorePercent = Math.max(0, Math.min(100, (score / 5) * 100));
                                return `
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">${escapeHtml(ev.criteria || '')}</td>
                                        <td class="py-2 px-4 border-b border-gray-300 text-right">
                                            <div class="font-semibold ${getSkillScoreColor(scorePercent)}">${score.toFixed(1)} / 5</div>
                                            <div class="text-xs text-gray-500">${scorePercent.toFixed(0)}%</div>
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
                                                ${rows || '<tr><td colspan="2" class="py-8 text-center text-gray-500">No evaluations</td></tr>'}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        kpisHtml = `
                            <div class="text-center py-12 text-gray-500">
                                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i>
                                <p class="text-lg font-medium">No KPI evaluations available</p>
                            </div>
                        `;
                    }

                    const analyzeButtonHtml = showAnalyze ? `
                        <div class="flex flex-col md:flex-row gap-2">
                            <button id="analyze-btn" data-employee-id="${escapeHtml(employeeId)}" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                Analyze
                            </button>
                        </div>
                    ` : '';

                    // Update content
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
                                                <h3 class="text-xl font-bold text-gray-900">${escapeHtml(employee.full_name || '')}</h3>
                                                <div class="flex flex-wrap gap-3 mt-1">
                                                    <span class="text-gray-700">${escapeHtml(employee.employee_id || '')}</span>
                                                    <span class="text-gray-600">•</span>
                                                    <span class="text-gray-700">${escapeHtml(employee.position || '')}</span>
                                                    <span class="text-gray-600">•</span>
                                                    <span class="text-gray-700">${escapeHtml(employee.department || '')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600">Evaluation</div>
                                        ${showAnalyze ? '<div class="text-xs text-gray-500">Analyze to compute competency and gaps</div>' : ''}
                                    </div>
                                </div>
                            </div>

                            ${analyzeButtonHtml}
                            
                            ${kpisHtml}
                        </div>
                    `;

                    // Recreate icons
                    setTimeout(() => lucide.createIcons(), 100);

                } catch (error) {
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200">
                                <i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Load Assessment</h3>
                            <p class="text-gray-500">${escapeHtml(error.message || 'Unknown error')}</p>
                            <button onclick="document.getElementById('view-modal').close()" class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4">
                                Close
                            </button>
                        </div>
                    `;
                    setTimeout(() => lucide.createIcons(), 100);
                }
            };

            // Open Analysis Modal function
            window.openAnalysisModal = async function(employeeId) {
                if (!analysisModal) return;

                const content = document.getElementById('analysis-content');
                const subtitle = document.getElementById('analysis-subtitle');

                // Show loading
                content.innerHTML = `
                    <div class="text-center py-12">
                        <div class="loading loading-spinner loading-lg text-gray-600"></div>
                        <p class="mt-4 text-gray-600">Computing KPI gaps...</p>
                    </div>
                `;

                // Show modal
                if (typeof analysisModal.showModal === 'function') {
                    analysisModal.showModal();
                } else {
                    analysisModal.setAttribute('open', '');
                    analysisModal.classList.add('modal-open');
                }

                try {
                    // Fetch employee details
                    const response = await fetch('get_employee_details.php?id=' + encodeURIComponent(employeeId));
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    // Update subtitle
                    subtitle.textContent = `${employee.full_name || ''} | ${employee.position || ''} | ${employee.department || ''}`;

                    // Get analysis data
                    const analysis = employee.analysis || {};
                    const computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                    const overallObj = analysis.overall || {};
                    const overallPct = Number.isFinite(Number(overallObj.pct)) ? Number(overallObj.pct) : (parseFloat(employee.competency) || 0);
                    const overallStatus = String(overallObj.status || employee.status || 'Retrain');

                    // Build analysis rows
                    const analysisRows = computed.map(r => {
                        const kpiName = String(r.kpi_name || '');
                        const kpiPct = Number(r.kpi_pct || 0);
                        const reqPct = Number(r.required_pct || 0);
                        const gapPct = Number(r.gap_pct || 0);
                        const badge = gapPct > 0 ? 'badge-error' : 'badge-success';
                        return `
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">${escapeHtml(kpiName)}</td>
                                <td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">${kpiPct.toFixed(1)}%</td>
                                <td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">${reqPct.toFixed(1)}%</td>
                                <td class="py-2 px-4 border-b border-gray-300 text-right">
                                    <span class="badge ${badge}">${gapPct.toFixed(1)}%</span>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Update content
                    content.innerHTML = `
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-5 rounded-lg border border-gray-300">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm text-gray-600">Overall Competency</div>
                                        <div class="text-3xl font-bold text-gray-900 mt-1">${overallPct.toFixed(1)}%</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600">Status</div>
                                        <div class="mt-1">
                                            <span class="status-badge ${getStatusClass(overallStatus)}">${escapeHtml(overallStatus)}</span>
                                        </div>
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
                                            ${analysisRows || '<tr><td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button id="proceed-btn" data-employee-id="${escapeHtml(employeeId)}" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1">
                                    <i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>
                                    Proceed
                                </button>
                            </div>
                        </div>
                    `;

                    // Recreate icons
                    setTimeout(() => lucide.createIcons(), 100);

                } catch (error) {
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200">
                                <i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Compute</h3>
                            <p class="text-gray-500">${escapeHtml(error.message || 'Unknown error')}</p>
                        </div>
                    `;
                    setTimeout(() => lucide.createIcons(), 100);
                }
            };

            // Forward to Critical function
            async function forwardToCritical(employeeId) {
                try {
                    // Get evaluation period from employee data if needed
                    const response = await fetch('get_employee_details.php?id=' + encodeURIComponent(employeeId));
                    const employee = await response.json();

                    if (employee.error) {
                        throw new Error(employee.error);
                    }

                    const evalPeriod = String(employee.evaluation_period || '').trim() || '';

                    // Send forward request
                    const res = await fetch('forward_to_critical.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            employee_id: employeeId,
                            evaluation_period: evalPeriod
                        })
                    });

                    const json = await res.json();

                    if (!res.ok || !json || json.success !== true) {
                        throw new Error(json?.message || 'Failed to forward');
                    }

                    // Close modals
                    if (analysisModal) analysisModal.close();
                    if (viewModal) viewModal.close();

                    // Update UI
                    updateEmployeeCounts(employeeId, employee.full_name || employeeId);

                    // Show success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Forwarded',
                            text: 'Employee forwarded to Critical Roles.',
                            confirmButtonColor: '#1f2937'
                        });
                    } else {
                        alert('Employee forwarded to Critical Roles.');
                    }

                } catch (error) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Server error',
                            confirmButtonColor: '#1f2937'
                        });
                    } else {
                        alert('Error: ' + (error.message || 'Server error'));
                    }
                }
            }

            // Update employee counts after forwarding
            function updateEmployeeCounts(employeeId, employeeName) {
                // Remove row from table
                const row = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                if (row) {
                    row.remove();
                }

                // Update "Not Yet Analyzed" count
                const notAnalyzedElem = document.getElementById('count-not-analyzed');
                if (notAnalyzedElem) {
                    const current = parseInt(notAnalyzedElem.textContent) || 0;
                    notAnalyzedElem.textContent = Math.max(0, current - 1);
                }

                // Update "Forwarded" count
                const forwardedElem = document.getElementById('count-forwarded');
                if (forwardedElem) {
                    const current = parseInt(forwardedElem.textContent) || 0;
                    forwardedElem.textContent = current + 1;
                }

                // Add to action log
                const actionLogList = document.getElementById('action-log-modal-list');
                if (actionLogList) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3';
                    li.innerHTML = `
                        <div class="flex items-center gap-3">
                            <i data-lucide="arrow-right" class="w-4 h-4 text-emerald-600"></i>
                            <span class="font-semibold">${escapeHtml(employeeName)}</span>
                            <span class="text-xs text-gray-500">${escapeHtml(employeeId)}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-xs text-gray-600">${new Date().toLocaleString()}</div>
                            <button class="btn btn-xs btn-outline action-log-view-btn" data-employee-id="${escapeHtml(employeeId)}">View</button>
                        </div>
                    `;
                    actionLogList.prepend(li);
                    setTimeout(() => lucide.createIcons(), 50);
                }
            }

            // Helper functions
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function getStatusClass(status) {
                status = String(status || '').toLowerCase();
                if (status === 'succession ready') return 'badge-success';
                if (status === 'upskilling') return 'badge-info';
                if (status === 'refresher training') return 'badge-warning';
                if (status === 'reskilling') return 'badge-error';
                if (status === 'not evaluated') return 'badge-neutral';
                return 'badge-neutral';
            }

            function getSkillScoreColor(score) {
                score = parseFloat(score) || 0;
                if (score < 40) return 'text-red-600';
                if (score < 70) return 'text-amber-600';
                return 'text-emerald-600';
            }
        });
    </script>

    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>

</html>