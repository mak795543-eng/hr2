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
                            e.position
                        FROM employees e
                        LEFT JOIN kpi_gap_formulations kgf
                          ON kgf.employee_id = e.employee_id
                        WHERE kgf.employee_id IS NULL
                        ORDER BY e.full_name ASC
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
                         <div class="text-sm opacity-70">Employees that have not undergone gap analysis.</div>
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

                 <div class="card bg-base-100 shadow mb-6">
                     <div class="card-body">
                         <div class="flex items-center justify-between">
                             <h2 class="text-lg font-bold">Action Log: Forwarded to Critical Roles</h2>
                         </div>
                         <ul id="action-log-list" class="mt-3 space-y-2">
                             <?php
                                try {
                                    $stmtLog = $pdo->query("SELECT kgf.employee_id, e.full_name, e.department, e.position, kgf.forwarded_at
                                                         FROM kpi_gap_formulations kgf
                                                         JOIN employees e ON e.employee_id = kgf.employee_id
                                                         WHERE COALESCE(kgf.forwarded_to_critical,0)=1
                                                         ORDER BY kgf.forwarded_at DESC
                                                         LIMIT 50");
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
                                                . '<div class="text-xs text-gray-600">' . htmlspecialchars((string)($log['position'] ?? '')) . ' • ' . htmlspecialchars((string)($log['department'] ?? '')) . ' • ' . htmlspecialchars((string)($log['forwarded_at'] ?? '')) . '</div>'
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
                                         <th class="text-right">Competency</th>
                                         <th>Status</th>
                                         <th class="text-center">Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php if (count($serverEmployees) > 0): ?>
                                         <?php foreach ($serverEmployees as $r):
                                                $status = (string)($r['status'] ?? 'Not Evaluated');
                                                $compVal = (float)($r['overall_competency'] ?? 0);
                                                $competency = (strtolower($status) === 'not evaluated') ? '—' : number_format($compVal, 1) . '%';
                                            ?>
                                             <tr data-employee-id="<?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?>">
                                                 <td>
                                                     <div class="font-semibold"><?php echo htmlspecialchars((string)($r['full_name'] ?? '')); ?></div>
                                                     <div class="text-xs opacity-70"><?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?></div>
                                                 </td>
                                                 <td><?php echo htmlspecialchars((string)($r['department'] ?? '')); ?></td>
                                                 <td><?php echo htmlspecialchars((string)($r['position'] ?? '')); ?></td>
                                                 <td class="text-right font-semibold"><?php echo $competency; ?></td>
                                                 <td>
                                                     <span class="badge <?php
                                                                        $s = strtolower($status);
                                                                        echo $s === 'succession ready' ? 'badge-success'
                                                                            : ($s === 'upskilling' ? 'badge-info'
                                                                                : ($s === 'refresher training' ? 'badge-warning'
                                                                                    : ($s === 'reskilling' ? 'badge-error' : 'badge-neutral')));
                                                                        ?>"><?php echo htmlspecialchars($status); ?></span>
                                                 </td>
                                                 <td class="text-center">
                                                     <button class="btn btn-xs btn-outline" onclick="openViewModal('<?php echo htmlspecialchars((string)($r['employee_id'] ?? '')); ?>')">View</button>
                                                 </td>
                                             </tr>
                                         <?php endforeach; ?>
                                     <?php else: ?>
                                         <tr>
                                             <td colspan="6" class="text-center py-10 opacity-70">No employees found.</td>
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
     </script>
     <script>
         (function() {
                 const els = {
                     refreshBtn: document.getElementById('refreshBtn'),
                     employeeRows: document.getElementById('employeeRows'),
                     // unified table only
                 };

                 function esc(s) {
                     return String(s ?? '')
                         .replace(/&/g, '&amp;')
                         .replace(/</g, '&lt;')
                         .replace(/>/g, '&gt;')
                         .replace(/"/g, '&quot;')
                         .replace(/'/g, '&#039;');
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

                 async function loadAllEmployees() {
                     try {
                         const res = await fetch('get_employees_with_kpi.php');
                         const json = await res.json();
                         const employees = Array.isArray(json.employees) ? json.employees : [];

                         els.employeeRows.innerHTML = employees.map(r => {
                             const status = r.status || 'Unknown';
                             const competencyVal = Number(r.overall_competency || 0);
                             const competency = (String(status).toLowerCase() === 'not evaluated') ? '—' : competencyVal.toFixed(1) + '%';
                             return `
                             <tr>
                                 <td>
                                     <div class="font-semibold">${esc(r.full_name)}</div>
                                     <div class="text-xs opacity-70">${esc(r.employee_id)}</div>
                                 </td>
                                 <td>${esc(r.department)}</td>
                                 <td>${esc(r.position)}</td>
                                 <td class="text-right font-semibold">${competency}</td>
                                 <td><span class="badge ${getStatusClass(status)}">${esc(status)}</span></td>
                                 <td class="text-center">
                                     <button class="btn btn-xs btn-outline" onclick="openViewModal('${esc(r.employee_id)}')">View</button>
                                 </td>
                             </tr>
                         `;
                         }).join('') || `
                        <tr>
                            <td colspan="6" class="text-center py-10 opacity-70">No employees found.</td>
                        </tr>
                    `;
                     } catch (e) {
                         els.employeeRows.innerHTML = `
                         <tr>
                             <td colspan="6" class="text-center py-10 opacity-70">Failed to load employees.</td>
                         </tr>
                     `;
                     }
                 }

                 async function syncEmployees() {
                     try {
                         await fetch('sync_ess_employees.php', {
                             method: 'POST'
                         });
                     } catch (e) {}
                 }

                 async function openViewModal(employeeId) {
                         const modal = document.getElementById('view-modal');
                         const content = document.getElementById('employee-details-content');
                         const subtitle = document.getElementById('employee-subtitle');

                         content.innerHTML = `
                     <div class="text-center py-12">
                         <div class="loading loading-spinner loading-lg text-gray-600"></div>
                         <p class="mt-4 text-gray-600">Loading competency assessment...</p>
                     </div>
                 `;

                         modal.showModal();

                         try {
                             const response = await fetch(\`get_employee_details.php?id=\${encodeURIComponent(employeeId)}\`);
                     const employee = await response.json();
                     if (employee.error) throw new Error(employee.error);
                     
                     subtitle.textContent = \`\${employee.full_name} | \${employee.position} | \${employee.department}\`;
 
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
                             return ` <
                                     tr class = "hover:bg-gray-50" >
                                     <
                                     td class = "py-2 px-4 border-b border-gray-300 text-sm text-gray-900" > $ {
                                         esc(ev.criteria ?? '')
                                     } < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right" >
                                     <
                                     div class = "font-semibold ${getSkillScoreColor(sPct)}" > $ {
                                         sSafe.toFixed(1)
                                     }
                                     / ${maxScore}</div >
                                     <
                                     div class = "text-xs text-gray-500" > $ {
                                         sPct.toFixed(0)
                                     } % < /div> < /
                                     td > <
                                     /tr>
                                     `;
                         }).join('');
 
                         return ` <
                                     div class = "border border-gray-300 rounded-lg overflow-hidden" >
                                     <
                                     div class = "px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3" >
                                     <
                                     div class = "font-semibold text-gray-900" > $ {
                                         esc(name)
                                     } < /div> <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Evaluations < /div> <
                                     div class = "text-xs text-gray-500" > (Analyze to see KPI gaps) < /div> < /
                                     div > <
                                     /div> <
                                     div class = "overflow-x-auto" >
                                     <
                                     table class = "table w-full" >
                                     <
                                     thead >
                                     <
                                     tr class = "bg-white" >
                                     <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300" > Criteria < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Score < /th> < /
                                     tr > <
                                     /thead> <
                                     tbody class = "divide-y divide-gray-200" >
                                     $ {
                                         rows || `
                                                 <tr>
                                                     <td colspan="2" class="py-8 text-center text-gray-500">No evaluations</td>
                                                 </tr>
                                             `
                                     } <
                                     /tbody> < /
                                     table > <
                                     /div> < /
                                     div >
                                     `;
                     }).join('') : ` <
                                     div class = "text-center py-12 text-gray-500" >
                                     <
                                     i data - lucide = "alert-circle"
                                     class = "w-12 h-12 mx-auto mb-4" > < /i> <
                                     p class = "text-lg font-medium" > No KPI evaluations available < /p> < /
                                     div >
                                     `;
 
                     const analysisRows = computed.map((r) => {
                         const kpiName = String(r.kpi_name ?? '');
                         const kpiPct = Number(r.kpi_pct ?? 0);
                         const reqPct = Number(r.required_pct ?? 0);
                         const gapPct = Number(r.gap_pct ?? 0);
                         const badge = gapPct > 0 ? 'badge-error' : 'badge-success';
                         return ` <
                                     tr class = "hover:bg-gray-50" >
                                     <
                                     td class = "py-2 px-4 border-b border-gray-300 text-sm text-gray-900" > $ {
                                         esc(kpiName)
                                     } < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         kpiPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         reqPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right" > < span class = "badge ${badge}" > $ {
                                         gapPct.toFixed(1)
                                     } % < /span></td >
                                     <
                                     /tr>
                                     `;
                     }).join('');
 
                     content.innerHTML = ` <
                                     div class = "space-y-6" >
                                     <
                                     div class = "bg-gray-50 p-5 rounded-lg border border-gray-300" >
                                     <
                                     div class = "flex flex-col md:flex-row md:items-center justify-between gap-4" >
                                     <
                                     div >
                                     <
                                     div class = "flex items-center gap-3" >
                                     <
                                     div class = "w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center" >
                                     <
                                     i data - lucide = "user"
                                     class = "w-8 h-8 text-gray-400" > < /i> < /
                                     div > <
                                     div >
                                     <
                                     h3 class = "text-xl font-bold text-gray-900" > $ {
                                         esc(employee.full_name)
                                     } < /h3> <
                                     div class = "flex flex-wrap gap-3 mt-1" >
                                     <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.employee_id)
                                     } < /span> <
                                     span class = "text-gray-600" > • < /span> <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.position)
                                     } < /span> <
                                     span class = "text-gray-600" > • < /span> <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.department)
                                     } < /span> < /
                                     div > <
                                     /div> < /
                                     div > <
                                     /div> <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Evaluation < /div> <
                                     div class = "text-xs text-gray-500" > Analyze to compute competency and gaps < /div> < /
                                     div > <
                                     /div> < /
                                     div >

                                     <
                                     div class = "flex flex-col md:flex-row gap-2" >
                                     <
                                     button id = "analyze-btn"
                                     class = "btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1" >
                                     <
                                     i data - lucide = "search"
                                     class = "w-4 h-4 mr-2" > < /i>
                                     Analyze <
                                     /button> < /
                                     div >

                                     <
                                     div id = "analysis-block"
                                     class = "hidden space-y-4" >
                                     <
                                     div class = "bg-gray-50 p-5 rounded-lg border border-gray-300" >
                                     <
                                     div class = "flex flex-col md:flex-row md:items-center justify-between gap-3" >
                                     <
                                     div >
                                     <
                                     div class = "text-sm text-gray-600" > Overall Competency < /div> <
                                     div class = "text-3xl font-bold text-gray-900 mt-1" > $ {
                                         overallPct.toFixed(1)
                                     } % < /div> < /
                                     div > <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Status < /div> <
                                     div class = "mt-1" > < span class = "status-badge ${getStatusClass(overallStatus)}" > $ {
                                         esc(overallStatus)
                                     } < /span></div >
                                     <
                                     /div> < /
                                     div > <
                                     /div>

                                     <
                                     div class = "border border-gray-300 rounded-lg overflow-hidden" >
                                     <
                                     div class = "px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3" >
                                     <
                                     div class = "font-semibold text-gray-900" > KPI Analysis < /div> <
                                     div class = "text-xs text-gray-500" > Actual vs Required vs Gap < /div> < /
                                     div > <
                                     div class = "overflow-x-auto" >
                                     <
                                     table class = "table w-full" >
                                     <
                                     thead >
                                     <
                                     tr class = "bg-white" >
                                     <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300" > KPI < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Actual < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Required < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Gap < /th> < /
                                     tr > <
                                     /thead> <
                                     tbody class = "divide-y divide-gray-200" >
                                     $ {
                                         analysisRows || `
                                                     <tr>
                                                         <td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td>
                                                     </tr>
                                                 `
                                     } <
                                     /tbody> < /
                                     table > <
                                     /div> < /
                                     div >

                                     <
                                     div class = "flex flex-col md:flex-row gap-2" >
                                     <
                                     button id = "forward-critical-btn"
                                     class = "btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 flex-1" >
                                     <
                                     i data - lucide = "arrow-right"
                                     class = "w-4 h-4 mr-2" > < /i>
                                     Forward Role to Critical Roles <
                                     /button> < /
                                     div > <
                                     /div>

                                     <
                                     div class = "space-y-6" >
                                     $ {
                                         kpisHtml
                                     } <
                                     /div> < /
                                     div >
                                     `;
 
                     const analyzeBtn = document.getElementById('analyze-btn');
                     const analysisBlock = document.getElementById('analysis-block');
                     if (analyzeBtn && analysisBlock) {
                         analyzeBtn.addEventListener('click', () => {
                             analysisBlock.classList.remove('hidden');
                             analyzeBtn.disabled = true;
                         });
                     }
 
                     const forwardBtn = document.getElementById('forward-critical-btn');
                     if (forwardBtn) {
                         forwardBtn.addEventListener('click', async () => {
                             try {
                                 const evalPeriod = String(employee.evaluation_period || '').trim() || '';
                                 const res = await fetch('forward_to_critical.php', {
                                     method: 'POST',
                                     headers: { 'Content-Type': 'application/json' },
                                     body: JSON.stringify({ employee_id: employee.employee_id, evaluation_period: evalPeriod })
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
 
                     setTimeout(() => lucide.createIcons(), 100);
                 } catch (error) {
                     content.innerHTML = ` <
                                     div class = "text-center py-12" >
                                     <
                                     div class = "inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200" >
                                     <
                                     i data - lucide = "alert-circle"
                                     class = "w-8 h-8 text-red-400" > < /i> < /
                                     div > <
                                     h3 class = "text-lg font-medium text-gray-700 mb-2" > Unable to Load Assessment < /h3> <
                                     p class = "text-gray-500" > $ {
                                         esc(error.message)
                                     } < /p> <
                                     button onclick = "document.getElementById('view-modal').close()"
                                     class = "btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4" >
                                     Close <
                                     /button> < /
                                     div >
                                     `;
                 }
             }
            async function openViewModalClean(employeeId) {
                const modal = document.getElementById('view-modal');
                const content = document.getElementById('employee-details-content');
                const subtitle = document.getElementById('employee-subtitle');
                
                content.innerHTML = ` <
                                     div class = "text-center py-12" >
                                     <
                                     div class = "loading loading-spinner loading-lg text-gray-600" > < /div> <
                                     p class = "mt-4 text-gray-600" > Loading competency assessment... < /p> < /
                                     div >
                                     `;
                
                modal.showModal();
                
                try {
                    const response = await fetch(\`get_employee_details.php?id=\${encodeURIComponent(employeeId)}\`);
                    const employee = await response.json();
                    if (employee.error) throw new Error(employee.error);
                    
                    subtitle.textContent = \`\${employee.full_name} | \${employee.position} | \${employee.department}\`;

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
                            return ` <
                                     tr class = "hover:bg-gray-50" >
                                     <
                                     td class = "py-2 px-4 border-b border-gray-300 text-sm text-gray-900" > $ {
                                         esc(ev.criteria ?? '')
                                     } < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right" >
                                     <
                                     div class = "font-semibold ${getSkillScoreColor(sPct)}" > $ {
                                         sSafe.toFixed(1)
                                     }
                                     / ${maxScore}</div >
                                     <
                                     div class = "text-xs text-gray-500" > $ {
                                         sPct.toFixed(0)
                                     } % < /div> < /
                                     td > <
                                     /tr>
                                     `;
                        }).join('');

                        return ` <
                                     div class = "border border-gray-300 rounded-lg overflow-hidden" >
                                     <
                                     div class = "px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3" >
                                     <
                                     div class = "font-semibold text-gray-900" > $ {
                                         esc(name)
                                     } < /div> <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Evaluations < /div> <
                                     div class = "text-xs text-gray-500" > (Analyze to see KPI gaps) < /div> < /
                                     div > <
                                     /div> <
                                     div class = "overflow-x-auto" >
                                     <
                                     table class = "table w-full" >
                                     <
                                     thead >
                                     <
                                     tr class = "bg-white" >
                                     <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300" > Criteria < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Score < /th> < /
                                     tr > <
                                     /thead> <
                                     tbody class = "divide-y divide-gray-200" >
                                     $ {
                                         rows || `
                                                <tr>
                                                    <td colspan="2" class="py-8 text-center text-gray-500">No evaluations</td>
                                                </tr>
                                            `
                                     } <
                                     /tbody> < /
                                     table > <
                                     /div> < /
                                     div >
                                     `;
                    }).join('') : ` <
                                     div class = "text-center py-12 text-gray-500" >
                                     <
                                     i data - lucide = "alert-circle"
                                     class = "w-12 h-12 mx-auto mb-4" > < /i> <
                                     p class = "text-lg font-medium" > No KPI evaluations available < /p> < /
                                     div >
                                     `;

                    const analysisRows = computed.map((r) => {
                        const kpiName = String(r.kpi_name ?? '');
                        const kpiPct = Number(r.kpi_pct ?? 0);
                        const reqPct = Number(r.required_pct ?? 0);
                        const gapPct = Number(r.gap_pct ?? 0);
                        const badge = gapPct > 0 ? 'badge-error' : 'badge-success';
                        return ` <
                                     tr class = "hover:bg-gray-50" >
                                     <
                                     td class = "py-2 px-4 border-b border-gray-300 text-sm text-gray-900" > $ {
                                         esc(kpiName)
                                     } < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         kpiPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         reqPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right" > < span class = "badge ${badge}" > $ {
                                         gapPct.toFixed(1)
                                     } % < /span></td >
                                     <
                                     /tr>
                                     `;
                    }).join('');

                    content.innerHTML = ` <
                                     div class = "space-y-6" >
                                     <
                                     div class = "bg-gray-50 p-5 rounded-lg border border-gray-300" >
                                     <
                                     div class = "flex flex-col md:flex-row md:items-center justify-between gap-4" >
                                     <
                                     div >
                                     <
                                     div class = "flex items-center gap-3" >
                                     <
                                     div class = "w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center" >
                                     <
                                     i data - lucide = "user"
                                     class = "w-8 h-8 text-gray-400" > < /i> < /
                                     div > <
                                     div >
                                     <
                                     h3 class = "text-xl font-bold text-gray-900" > $ {
                                         esc(employee.full_name)
                                     } < /h3> <
                                     div class = "flex flex-wrap gap-3 mt-1" >
                                     <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.employee_id)
                                     } < /span> <
                                     span class = "text-gray-600" > • < /span> <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.position)
                                     } < /span> <
                                     span class = "text-gray-600" > • < /span> <
                                     span class = "text-gray-700" > $ {
                                         esc(employee.department)
                                     } < /span> < /
                                     div > <
                                     /div> < /
                                     div > <
                                     /div> <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Evaluation < /div> <
                                     div class = "text-xs text-gray-500" > Analyze to compute competency and gaps < /div> < /
                                     div > <
                                     /div> < /
                                     div >

                                     <
                                     div class = "flex flex-col md:flex-row gap-2" >
                                     <
                                     button id = "analyze-btn"
                                     class = "btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1" >
                                     <
                                     i data - lucide = "search"
                                     class = "w-4 h-4 mr-2" > < /i>
                                     Analyze <
                                     /button> < /
                                     div >

                                     <
                                     div id = "analysis-block"
                                     class = "hidden space-y-4" >
                                     <
                                     div class = "bg-gray-50 p-5 rounded-lg border border-gray-300" >
                                     <
                                     div class = "flex flex-col md:flex-row md:items-center justify-between gap-3" >
                                     <
                                     div >
                                     <
                                     div class = "text-sm text-gray-600" > Overall Competency < /div> <
                                     div class = "text-3xl font-bold text-gray-900 mt-1" > $ {
                                         overallPct.toFixed(1)
                                     } % < /div> < /
                                     div > <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Status < /div> <
                                     div class = "mt-1" > < span class = "status-badge ${getStatusClass(overallStatus)}" > $ {
                                         esc(overallStatus)
                                     } < /span></div >
                                     <
                                     /div> < /
                                     div > <
                                     /div>

                                     <
                                     div class = "border border-gray-300 rounded-lg overflow-hidden" >
                                     <
                                     div class = "px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3" >
                                     <
                                     div class = "font-semibold text-gray-900" > KPI Analysis < /div> <
                                     div class = "text-xs text-gray-500" > Actual vs Required vs Gap < /div> < /
                                     div > <
                                     div class = "overflow-x-auto" >
                                     <
                                     table class = "table w-full" >
                                     <
                                     thead >
                                     <
                                     tr class = "bg-white" >
                                     <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300" > KPI < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Actual < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Required < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Gap < /th> < /
                                     tr > <
                                     /thead> <
                                     tbody class = "divide-y divide-gray-200" >
                                     $ {
                                         analysisRows || `
                                                    <tr>
                                                        <td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td>
                                                    </tr>
                                                `
                                     } <
                                     /tbody> < /
                                     table > <
                                     /div> < /
                                     div >

                                     <
                                     div class = "flex flex-col md:flex-row gap-2" >
                                     <
                                     button id = "forward-critical-btn"
                                     class = "btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 flex-1" >
                                     <
                                     i data - lucide = "arrow-right"
                                     class = "w-4 h-4 mr-2" > < /i>
                                     Forward Role to Critical Roles <
                                     /button> < /
                                     div > <
                                     /div>

                                     <
                                     div class = "space-y-6" >
                                     $ {
                                         kpisHtml
                                     } <
                                     /div> < /
                                     div >
                                     `;

                    const analyzeBtn = document.getElementById('analyze-btn');
                    if (analyzeBtn) {
                        analyzeBtn.addEventListener('click', () => openAnalysisModal(employee.employee_id));
                    }

                    const forwardBtn = document.getElementById('forward-critical-btn');
                    if (forwardBtn) {
                        forwardBtn.innerHTML = '<i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>Proceed';
                        forwardBtn.addEventListener('click', () => openAnalysisModal(employee.employee_id));
                    }
                    
                    setTimeout(() => lucide.createIcons(), 100);
                } catch (error) {
                    content.innerHTML = ` <
                                     div class = "text-center py-12" >
                                     <
                                     div class = "inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200" >
                                     <
                                     i data - lucide = "alert-circle"
                                     class = "w-8 h-8 text-red-400" > < /i> < /
                                     div > <
                                     h3 class = "text-lg font-medium text-gray-700 mb-2" > Unable to Load Assessment < /h3> <
                                     p class = "text-gray-500" > $ {
                                         esc(error.message)
                                     } < /p> <
                                     button onclick = "document.getElementById('view-modal').close()"
                                     class = "btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4" >
                                     Close <
                                     /button> < /
                                     div >
                                     `;
                }
                async function openAnalysisModal(employeeId) {
                    const modal = document.getElementById('analysis-modal');
                    const content = document.getElementById('analysis-content');
                    const subtitle = document.getElementById('analysis-subtitle');
                    content.innerHTML = ` <
                                     div class = "text-center py-12" >
                                     <
                                     div class = "loading loading-spinner loading-lg text-gray-600" > < /div> <
                                     p class = "mt-4 text-gray-600" > Computing KPI gaps... < /p> < /
                                     div >
                                     `;
                    modal.showModal();
                    try {
                        const response = await fetch(\`get_employee_details.php?id=\${encodeURIComponent(employeeId)}\`);
                        const employee = await response.json();
                        if (employee.error) throw new Error(employee.error);
                        subtitle.textContent = \`\${employee.full_name} | \${employee.position} | \${employee.department}\`;
                        const analysis = employee.analysis || {};
                        const computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                        const overallObj = analysis.overall || {};
                        const overallPct = Number.isFinite(Number(overallObj.pct)) ? Number(overallObj.pct) : (parseFloat(employee.competency) || 0);
                        const overallStatus = String(overallObj.status || employee.status || 'Retrain');
                        const analysisRows = computed.map((r) => {
                            const kpiName = String(r.kpi_name ?? '');
                            const kpiPct = Number(r.kpi_pct ?? 0);
                            const reqPct = Number(r.required_pct ?? 0);
                            const gapPct = Number(r.gap_pct ?? 0);
                            const badge = gapPct > 0 ? 'badge-error' : 'badge-success';
                            return ` <
                                     tr class = "hover:bg-gray-50" >
                                     <
                                     td class = "py-2 px-4 border-b border-gray-300 text-sm text-gray-900" > $ {
                                         esc(kpiName)
                                     } < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         kpiPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right font-semibold" > $ {
                                         reqPct.toFixed(1)
                                     } % < /td> <
                                     td class = "py-2 px-4 border-b border-gray-300 text-right" > < span class = "badge ${badge}" > $ {
                                         gapPct.toFixed(1)
                                     } % < /span></td >
                                     <
                                     /tr>
                                     `;
                        }).join('');
                        content.innerHTML = ` <
                                     div class = "space-y-6" >
                                     <
                                     div class = "bg-gray-50 p-5 rounded-lg border border-gray-300" >
                                     <
                                     div class = "flex flex-col md:flex-row md:items-center justify-between gap-3" >
                                     <
                                     div >
                                     <
                                     div class = "text-sm text-gray-600" > Overall Competency < /div> <
                                     div class = "text-3xl font-bold text-gray-900 mt-1" > $ {
                                         overallPct.toFixed(1)
                                     } % < /div> < /
                                     div > <
                                     div class = "text-right" >
                                     <
                                     div class = "text-sm text-gray-600" > Status < /div> <
                                     div class = "mt-1" > < span class = "status-badge ${getStatusClass(overallStatus)}" > $ {
                                         esc(overallStatus)
                                     } < /span></div >
                                     <
                                     /div> < /
                                     div > <
                                     /div> <
                                     div class = "border border-gray-300 rounded-lg overflow-hidden" >
                                     <
                                     div class = "px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3" >
                                     <
                                     div class = "font-semibold text-gray-900" > KPI Analysis < /div> <
                                     div class = "text-xs text-gray-500" > Actual vs Required vs Gap < /div> < /
                                     div > <
                                     div class = "overflow-x-auto" >
                                     <
                                     table class = "table w-full" >
                                     <
                                     thead >
                                     <
                                     tr class = "bg-white" >
                                     <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300" > KPI < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Actual < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Required < /th> <
                                     th class = "text-gray-700 py-2 px-4 border-b border-gray-300 text-right" > Gap < /th> < /
                                     tr > <
                                     /thead> <
                                     tbody class = "divide-y divide-gray-200" >
                                     $ {
                                         analysisRows || `
                                                    <tr>
                                                        <td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td>
                                                    </tr>
                                                `
                                     } <
                                     /tbody> < /
                                     table > <
                                     /div> < /
                                     div > <
                                     div class = "flex gap-2" >
                                     <
                                     button id = "proceed-btn"
                                     class = "btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1" >
                                     <
                                     i data - lucide = "arrow-right"
                                     class = "w-4 h-4 mr-2" > < /i>
                                     Proceed <
                                     /button> < /
                                     div > <
                                     /div>
                                     `;
                        const proceedBtn = document.getElementById('proceed-btn');
                        if (proceedBtn) {
                            proceedBtn.addEventListener('click', async () => {
                                try {
                                    const evalPeriod = String(employee.evaluation_period || '').trim() || '';
                                    const res = await fetch('forward_to_critical.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ employee_id: employee.employee_id, evaluation_period: evalPeriod })
                                    });
                                    const json = await res.json();
                                    if (!res.ok || !json || json.success !== true) {
                                        throw new Error(json?.message || 'Failed to forward');
                                    }
                                    document.getElementById('analysis-modal').close();
                                    document.getElementById('view-modal').close();
                                    (function(){
                                        const row = document.querySelector(`
                                     tr[data - employee - id = "${esc(employee.employee_id)}"] `);
                                        if (row && row.parentElement) row.parentElement.removeChild(row);
                                        const na = document.getElementById('count-not-analyzed');
                                        if (na) {
                                            const v = parseInt(na.textContent, 10);
                                            na.textContent = String((isNaN(v) ? 0 : Math.max(0, v - 1)));
                                        }
                                        const fw = document.getElementById('count-forwarded');
                                        if (fw) {
                                            const v2 = parseInt(fw.textContent, 10);
                                            fw.textContent = String((isNaN(v2) ? 1 : v2 + 1));
                                        }
                                        const list = document.getElementById('action-log-list');
                                        if (list) {
                                            const li = document.createElement('li');
                                            li.className = 'flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3';
                                            li.innerHTML = '<div class="flex items-center gap-3"><i data-lucide="arrow-right" class="w-4 h-4 text-emerald-600"></i><span class="font-semibold">' + esc(employee.full_name || '') + '</span><span class="text-xs text-gray-500">' + esc(employee.employee_id || '') + '</span></div><div class="text-xs text-gray-600">' + esc(employee.position || '') + ' • ' + esc(employee.department || '') + ' • ' + new Date().toLocaleString() + '</div>';
                                            list.prepend(li);
                                            window.lucide && window.lucide.createIcons();
                                        }
                                    })();
                                    await Swal.fire({
                                        icon: 'success',
                                        title: 'Forwarded',
                                        text: 'Employee forwarded to Critical Roles.',
                                        showConfirmButton: true,
                                        confirmButtonText: 'OK',
                                        buttonsStyling: false,
                                        customClass: { confirmButton: 'btn bg-gray-900 text-white hover:bg-gray-800 border-0' },
                                        allowOutsideClick: false
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
                        setTimeout(() => window.lucide && window.lucide.createIcons(), 50);
                    } catch (err) {
                        content.innerHTML = ` <
                                     div class = "text-center py-12" >
                                     <
                                     div class = "inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200" >
                                     <
                                     i data - lucide = "alert-circle"
                                     class = "w-8 h-8 text-red-400" > < /i> < /
                                     div > <
                                     h3 class = "text-lg font-medium text-gray-700 mb-2" > Unable to Compute < /h3> <
                                     p class = "text-gray-500" > $ {
                                         esc(err.message)
                                     } < /p> < /
                                     div >
                                     `;
                    }
                }
            }
            window.openAnalysisModal = openAnalysisModal;
            window.openViewModal = openViewModalClean;
 
            els.refreshBtn.addEventListener('click', () => { window.location.reload(); });
         })();
     </script>
     <script src="../../soliera.js"></script>
     <script src="../../sidebar.js"></script>
     <script>
         (function() {
                 function esc(s) {
                     return String(s || '')
                         .replace(/&/g, '&amp;')
                         .replace(/</g, '&lt;')
                         .replace(/>/g, '&gt;')
                         .replace(/"/g, '&quot;')
                         .replace(/'/g, '&#039;');
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
                 window.openViewModal = async function(employeeId) {
                             const modal = document.getElementById('view-modal');
                             const content = document.getElementById('employee-details-content');
                             const subtitle = document.getElementById('employee-subtitle');
                             if (!modal || !content || !subtitle) return;
                             content.innerHTML = '<div class="text-center py-12"><div class="loading loading-spinner loading-lg text-gray-600"></div><p class="mt-4 text-gray-600">Loading competency assessment...</p></div>';
                             modal.showModal();
                             try {
                                 const response = await fetch('get_employee_details.php?id=' + encodeURIComponent(employeeId));
                                 const employee = await response.json();
                                 if (employee.error) throw new Error(employee.error);
                                 subtitle.textContent = (employee.full_name || '') + ' | ' + (employee.position || '') + ' | ' + (employee.department || '');
                                 const kpis = Array.isArray(employee.kpis) ? employee.kpis : [];
                                 const blocks = kpis.map(function(kpi) {
                                     const name = String(kpi.kpi_name ?? kpi.kpi ?? '');
                                     const evaluations = Array.isArray(kpi.evaluations) ? kpi.evaluations : [];
                                     const rows = evaluations.map(function(ev) {
                                         const s = Number(ev.score);
                                         const sSafe = Number.isFinite(s) ? s : 0;
                                         return '<tr class="hover:bg-gray-50">' +
                                             '<td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">' + esc(ev.criteria ?? '') + '</td>' +
                                             '<td class="py-2 px-4 border-b border-gray-300 text-right"><div class="font-semibold">' + sSafe.toFixed(1) + ' / 5</div></td>' +
                                             '</tr>';
                                     }).join('') || '<tr><td colspan="2" class="py-8 text-center text-gray-500">No evaluations</td></tr>';
                                     return '' +
                                         '<div class="border border-gray-300 rounded-lg overflow-hidden">' +
                                         '<div class="px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3">' +
                                         '<div class="font-semibold text-gray-900">' + esc(name) + '</div>' +
                                         '<div class="text-right"><div class="text-sm text-gray-600">Evaluations</div><div class="text-xs text-gray-500">(Analyze to see KPI gaps)</div></div>' +
                                         '</div>' +
                                         '<div class="overflow-x-auto">' +
                                         '<table class="table w-full">' +
                                         '<thead><tr class="bg-white">' +
                                         '<th class="text-gray-700 py-2 px-4 border-b border-gray-300">Criteria</th>' +
                                         '<th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Score</th>' +
                                         '</tr></thead>' +
                                         '<tbody class="divide-y divide-gray-200">' + rows + '</tbody>' +
                                         '</table>' +
                                         '</div>' +
                                         '</div>';
                                 }).join('') || '<div class="text-center py-12 text-gray-500"><i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i><p class="text-lg font-medium">No KPI evaluations available</p></div>';
                                 content.innerHTML = '<div class="space-y-6">' + blocks + '<div class="flex flex-col md:flex-row gap-2"><button id="analyze-btn" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1"><i data-lucide="search" class="w-4 h-4 mr-2"></i>Analyze</button></div></div>';
                                 const analyzeBtn = document.getElementById('analyze-btn');
                                 if (analyzeBtn) {
                                     analyzeBtn.addEventListener('click', async function() {
                                             if (typeof window.openAnalysisModal === 'function') {
                                                 window.openAnalysisModal(employee.employee_id);
                                                 return;
                                             }
                                             const modal = document.getElementById('analysis-modal');
                                             const content = document.getElementById('analysis-content');
                                             const subtitle = document.getElementById('analysis-subtitle');
                                             if (!modal || !content || !subtitle) return;
                                             content.innerHTML = '<div class="text-center py-12"><div class="loading loading-spinner loading-lg text-gray-600"></div><p class="mt-4 text-gray-600">Computing KPI gaps...</p></div>';
                                             modal.showModal();
                                             try {
                                                 const resp = await fetch('get_employee_details.php?id=' + encodeURIComponent(employee.employee_id));
                                                 const emp = await resp.json();
                                                 if (emp.error) throw new Error(emp.error);
                                                 subtitle.textContent = (emp.full_name || '') + ' | ' + (emp.position || '') + ' | ' + (emp.department || '');
                                                 const analysis = emp.analysis || {};
                                                 const computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                                                 const overallObj = analysis.overall || {};
                                                 const overallPct = Number.isFinite(Number(overallObj.pct)) ? Number(overallObj.pct) : (parseFloat(emp.competency) || 0);
                                                 const overallStatus = String(overallObj.status || emp.status || 'Retrain');
                                                 const rows = computed.map(function(r) {
                                                     const kName = String(r.kpi_name || '');
                                                     const kPct = Number(r.kpi_pct || 0);
                                                     const req = Number(r.required_pct || 0);
                                                     const gap = Number(r.gap_pct || 0);
                                                     const badge = gap > 0 ? 'badge-error' : 'badge-success';
                                                     return '<tr class="hover:bg-gray-50">' +
                                                         '<td class="py-2 px-4 border-b border-gray-300 text-sm text-gray-900">' + esc(kName) + '</td>' +
                                                         '<td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">' + kPct.toFixed(1) + '%</td>' +
                                                         '<td class="py-2 px-4 border-b border-gray-300 text-right font-semibold">' + req.toFixed(1) + '%</td>' +
                                                         '<td class="py-2 px-4 border-b border-gray-300 text-right"><span class="badge ' + badge + '">' + gap.toFixed(1) + '%</span></td>' +
                                                         '</tr>';
                                                 }).join('');
                                                 content.innerHTML = '' +
                                                     '<div class="space-y-6">' +
                                                     '<div class="bg-gray-50 p-5 rounded-lg border border-gray-300">' +
                                                     '<div class="flex flex-col md:flex-row md:items-center justify-between gap-3">' +
                                                     '<div><div class="text-sm text-gray-600">Overall Competency</div><div class="text-3xl font-bold text-gray-900 mt-1">' + overallPct.toFixed(1) + '%</div></div>' +
                                                     '<div class="text-right"><div class="text-sm text-gray-600">Status</div><div class="mt-1"><span class="status-badge ' + getStatusClass(overallStatus) + '">' + esc(overallStatus) + '</span></div></div>' +
                                                     '</div></div>' +
                                                     '<div class="border border-gray-300 rounded-lg overflow-hidden">' +
                                                     '<div class="px-4 py-3 bg-gray-50 border-b border-gray-300 flex items-center justify-between gap-3">' +
                                                     '<div class="font-semibold text-gray-900">KPI Analysis</div>' +
                                                     '<div class="text-xs text-gray-500">Actual vs Required vs Gap</div>' +
                                                     '</div>' +
                                                     '<div class="overflow-x-auto"><table class="table w-full"><thead><tr class="bg-white">' +
                                                     '<th class="text-gray-700 py-2 px-4 border-b border-gray-300">KPI</th>' +
                                                     '<th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Actual</th>' +
                                                     '<th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Required</th>' +
                                                     '<th class="text-gray-700 py-2 px-4 border-b border-gray-300 text-right">Gap</th>' +
                                                     '</tr></thead><tbody class="divide-y divide-gray-200">' +
                                                     (rows || '<tr><td colspan="4" class="py-8 text-center text-gray-500">No analysis available</td></tr>') +
                                                     '</tbody></table></div></div>' +
                                                     '<div class="flex gap-2"><button id="proceed-btn" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0 flex-1"><i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>Proceed</button></div>' +
                                                     '</div>';
                                                 const proceedBtn = document.getElementById('proceed-btn');
                                                 if (proceedBtn) {
                                                     proceedBtn.addEventListener('click', async function() {
                                                             try {
                                                                 const evalPeriod = String(emp.evaluation_period || '').trim() || '';
                                                                 const res = await fetch('forward_to_critical.php', {
                                                                     method: 'POST',
                                                                     headers: {
                                                                         'Content-Type': 'application/json'
                                                                     },
                                                                     body: JSON.stringify({
                                                                         employee_id: emp.employee_id,
                                                                         evaluation_period: evalPeriod
                                                                     })
                                                                 });
                                                                 const json = await res.json();
                                                                 if (!res.ok || !json || json.success !== true) {
                                                                     throw new Error(json && json.message ? json.message : 'Failed to forward');
                                                                 }
                                                                 document.getElementById('analysis-modal').close();
                                                                 document.getElementById('view-modal').close();
                                                                 (function() {
                                                                     const row = document.querySelector(`tr[data-employee-id="${esc(emp.employee_id)}"]`);
                                                                     if (row && row.parentElement) row.parentElement.removeChild(row);
                                                                     const na = document.getElementById('count-not-analyzed');
                                                                     if (na) {
                                                                         const v = parseInt(na.textContent, 10);
                                                                         na.textContent = String((isNaN(v) ? 0 : Math.max(0, v - 1)));
                                                                     }
                                                                     const fw = document.getElementById('count-forwarded');
                                                                     if (fw) {
                                                                         const v2 = parseInt(fw.textContent, 10);
                                                                         fw.textContent = String((isNaN(v2) ? 1 : v2 + 1));
                                                                     }
                                                                     const list = document.getElementById('action-log-list');
                                                                     if (list) {
                                                                         const li = document.createElement('li');
                                                                         li.className = 'flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3';
                                                                         li.innerHTML = '<div class="flex items-center gap-3"><i data-lucide="arrow-right" class="w-4 h-4 text-emerald-600"></i><span class="font-semibold">' + esc(emp.full_name || '') + '</span><span class="text-xs text-gray-500">' + esc(emp.employee_id || '') + '</span></div><div class="text-xs text-gray-600">' + esc(emp.position || '') + ' • ' + esc(emp.department || '') + ' • ' + new Date().toLocaleString() + '</div>';
                                                                         list.prepend(li);
                                                                         window.lucide && window.lucide.createIcons();
                                                                     }
                                                                 })();
                                                                 await Swal.fire({
                                                                     icon: 'success',
                                                                     title: 'Forwarded',
                                                                     text: 'Employee forwarded to Critical Roles.',
                                                                     showConfirmButton: true,
                                                                     confirmButtonText: 'OK',
                                                                     buttonsStyling: false,
                                                                     customClass: {
                                                                         confirmButton: 'btn bg-gray-900 text-white hover:bg-gray-800 border-0'
                                                                     },

                                                                 });
                                                                 window.location.href = 'criticalgaps.php';
                                                             } catch (err2) {
                                                                 Swal.fire({
                                                                     icon: 'error',
                                                                     title: 'Error',
                                                                     text: (err2 && err2.message) ? err2.message : 'Server error',
                                                                     confirmButtonColor: '#1f2937'
                                                                 });
                                                             }
                                                             setTimeout(function() {
                                                                 window.lucide && window.lucide.createIcons();
                                                             }, 50);
                                                         } catch (err) {
                                                             content.innerHTML = '<div class="text-center py-12"><div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200"><i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i></div><h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Compute</h3><p class="text-gray-500">' + esc(err.message) + '</p></div>';
                                                         }
                                                     });
                                             }
                                             setTimeout(function() {
                                                 window.lucide && window.lucide.createIcons();
                                             }, 50);
                                         } catch (err) {
                                             content.innerHTML = '<div class="text-center py-12"><div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200"><i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i></div><h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Load Assessment</h3><p class="text-gray-500">' + esc(err.message) + '</p><button onclick="document.getElementById(\'view-modal\').close()" class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4">Close</button></div>';
                                         }
                                     };
                                 })();
     </script>
 </body>

 </html>