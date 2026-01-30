<?php
 require_once __DIR__ . '/config.php';

 if ((string)($_GET['ajax'] ?? '') === '1' && (string)($_GET['action'] ?? '') === 'seed') {
     header('Content-Type: application/json; charset=utf-8');
     try {
         $period = trim((string)($_GET['evaluation_period'] ?? ''));
         if ($period === '') {
             $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
         }
 
         global $pdo;
         $seeded = 0;
         $empStmt = $pdo->query('SELECT employee_id FROM employees ORDER BY employee_id ASC');
         $empIds = $empStmt ? $empStmt->fetchAll(PDO::FETCH_COLUMN) : [];

         foreach (($empIds ?: []) as $eid) {
             $employeeId = trim((string)$eid);
             if ($employeeId === '') continue;

             $didSeed = seedMissingKpiEvaluations($employeeId, $period);
             if ($didSeed) $seeded++;
         }

         echo json_encode(['success' => true, 'seeded_kpi' => $seeded, 'evaluation_period' => $period]);
         exit;
     } catch (Throwable $e) {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed')]);
         exit;
     }
 }

 if ((string)($_GET['ajax'] ?? '') === '1' && (string)($_GET['action'] ?? '') === 'list') {
     header('Content-Type: application/json; charset=utf-8');
     try {
         $period = trim((string)($_GET['evaluation_period'] ?? ''));
         if ($period === '') {
             $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
         }

         global $pdo;
         $stmt = $pdo->prepare(
             "SELECT e.employee_id, e.full_name, e.department,
                     CASE
                         WHEN EXISTS (
                             SELECT 1
                             FROM employee_kpi_scores s
                             WHERE s.employee_id = e.employee_id AND s.evaluation_period = ?
                             LIMIT 1
                         ) THEN 0
                         ELSE 1
                     END AS missing_kpi,
                     CASE WHEN gf.employee_id IS NULL THEN 1 ELSE 0 END AS missing_formulation,
                     COALESCE(gf.overall_competency, 0) AS overall_competency,
                     COALESCE(gf.status, '') AS status
              FROM employees e
              LEFT JOIN kpi_gap_formulations gf
                ON gf.employee_id = e.employee_id
               AND gf.evaluation_period = ?
              ORDER BY e.full_name ASC"
         );
        $stmt->execute([$period, $period]);
         $rows = $stmt->fetchAll();

         $employees = [];
         foreach (($rows ?: []) as $r) {
             $employees[] = [
                 'employee_id' => (string)($r['employee_id'] ?? ''),
                 'full_name' => (string)($r['full_name'] ?? ''),
                 'department' => (string)($r['department'] ?? ''),
                 'missing_kpi' => (int)($r['missing_kpi'] ?? 0),
                 'missing_formulation' => (int)($r['missing_formulation'] ?? 0),
                 'overall_competency' => (float)($r['overall_competency'] ?? 0),
                 'status' => (string)($r['status'] ?? ''),
             ];
         }

         echo json_encode([
             'success' => true,
             'evaluation_period' => $period,
             'employees' => $employees,
         ]);
         exit;
     } catch (Throwable $e) {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed')]);
         exit;
     }
 }

 if ((string)($_GET['ajax'] ?? '') === '1' && (string)($_GET['action'] ?? '') === 'kpis') {
     header('Content-Type: application/json; charset=utf-8');
     try {
         $period = trim((string)($_GET['evaluation_period'] ?? ''));
         if ($period === '') {
             $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
         }

         $employeeId = trim((string)($_GET['employee_id'] ?? ''));
         if ($employeeId === '') {
             throw new RuntimeException('Missing employee_id');
         }

         global $pdo;
         $empStmt = $pdo->prepare('SELECT employee_id, full_name, department, position AS role FROM employees WHERE employee_id = ? LIMIT 1');
         $empStmt->execute([$employeeId]);
         $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
         if (!$emp) {
             throw new RuntimeException('Employee not found');
         }

         seedMissingKpiEvaluations($employeeId, $period);

         $dept = (string)($emp['department'] ?? '');
         $stmt = $pdo->prepare(
             "SELECT k.kpi_name, s.criteria, s.score
              FROM employee_kpi_scores s
              JOIN kpis k ON k.id = s.kpi_id
              WHERE s.employee_id = ?
                AND s.evaluation_period = ?
                AND (k.department IS NULL OR k.department = '' OR k.department = ?)
              ORDER BY k.kpi_name ASC, s.criteria ASC"
         );
         $stmt->execute([$employeeId, $period, $dept]);
         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

         $items = [];
         foreach (($rows ?: []) as $r) {
             $score = is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0.0;
             if ($score < 0) $score = 0.0;
             if ($score > 5) $score = 5.0;
             $items[] = [
                 'kpi_name' => (string)($r['kpi_name'] ?? ''),
                 'criteria' => (string)($r['criteria'] ?? ''),
                 'score' => round($score, 2),
             ];
         }

         echo json_encode([
             'success' => true,
             'evaluation_period' => $period,
             'employee' => $emp,
             'items' => $items,
         ]);
         exit;
     } catch (Throwable $e) {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed')]);
         exit;
     }
 }

 if ((string)($_GET['ajax'] ?? '') === '1' && (string)($_GET['action'] ?? '') === 'employee') {
     header('Content-Type: application/json; charset=utf-8');
     try {
         $period = trim((string)($_GET['evaluation_period'] ?? ''));
         if ($period === '') {
             $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
         }

         $employeeId = trim((string)($_GET['employee_id'] ?? ''));
         if ($employeeId === '') {
             throw new RuntimeException('Missing employee_id');
         }

         global $pdo;
         $empStmt = $pdo->prepare('SELECT employee_id, full_name, department, position AS role FROM employees WHERE employee_id = ? LIMIT 1');
         $empStmt->execute([$employeeId]);
         $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
         if (!$emp) {
             throw new RuntimeException('Employee not found');
         }

         seedMissingKpiEvaluations($employeeId, $period);

         $stmt = $pdo->prepare(
             "SELECT k.kpi_name,
                     AVG(COALESCE(s.score, 0)) AS avg_rating,
                     COALESCE(cc.required_level, 80) AS required_pct
              FROM employee_kpi_scores s
              JOIN kpis k ON k.id = s.kpi_id
              LEFT JOIN competency_criteria cc ON cc.name = k.kpi_name
              WHERE s.employee_id = ? AND s.evaluation_period = ?
              GROUP BY k.kpi_name, cc.required_level
              ORDER BY k.kpi_name ASC"
         );
         $stmt->execute([$employeeId, $period]);
         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

         $computed = [];
         $sumPct = 0.0;
         $cnt = 0;
         foreach (($rows ?: []) as $r) {
             $avg = is_numeric($r['avg_rating'] ?? null) ? (float)$r['avg_rating'] : 0.0;
             if ($avg < 0) $avg = 0.0;
             if ($avg > 5) $avg = 5.0;
             $kpiPct = ($avg / 5.0) * 100.0;
             $req = is_numeric($r['required_pct'] ?? null) ? (float)$r['required_pct'] : 80.0;
             if ($req < 0) $req = 0.0;
             if ($req > 100) $req = 100.0;
             $gap = $req - $kpiPct;
             if ($gap < 0) $gap = 0.0;

             $computed[] = [
                 'kpi_name' => (string)($r['kpi_name'] ?? ''),
                 'avg' => round($avg, 2),
                 'kpi_pct' => round($kpiPct, 1),
                 'required_pct' => round($req, 1),
                 'gap_pct' => round($gap, 1),
             ];
             $sumPct += $kpiPct;
             $cnt++;
         }

         $overallPct = $cnt > 0 ? ($sumPct / $cnt) : 0.0;
         echo json_encode([
             'success' => true,
             'evaluation_period' => $period,
             'employee' => $emp,
             'computed' => $computed,
             'overall' => [
                 'pct' => round($overallPct, 1),
                 'status' => mapCompetencyToStatus((float)$overallPct),
             ],
         ]);
         exit;
     } catch (Throwable $e) {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed')]);
         exit;
     }
 }

 if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_GET['ajax'] ?? '') === '1' && (string)($_GET['action'] ?? '') === 'save') {
     header('Content-Type: application/json; charset=utf-8');
     try {
         $period = trim((string)($_GET['evaluation_period'] ?? ''));
         if ($period === '') {
             $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
         }

         $raw = file_get_contents('php://input');
         $payload = json_decode($raw ?: '[]', true);
         if (!is_array($payload)) {
             throw new RuntimeException('Invalid payload');
         }

         $employeeId = trim((string)($payload['employee_id'] ?? ''));
         if ($employeeId === '') {
             throw new RuntimeException('Missing employee_id');
         }

         $overall = is_numeric($payload['overall_competency'] ?? null) ? (float)$payload['overall_competency'] : 0.0;
         if ($overall < 0) $overall = 0.0;
         if ($overall > 100) $overall = 100.0;

         $status = trim((string)($payload['status'] ?? ''));
         if ($status === '') {
             $status = mapCompetencyToStatus($overall);
         }

         $detailsJson = json_encode($payload['details'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

         global $pdo;
         $stmt = $pdo->prepare(
             'INSERT INTO kpi_gap_formulations (employee_id, evaluation_period, overall_competency, status, details_json)
              VALUES (?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE overall_competency = VALUES(overall_competency), status = VALUES(status), details_json = VALUES(details_json)'
         );
         $stmt->execute([$employeeId, $period, $overall, $status, $detailsJson]);

         echo json_encode(['success' => true]);
         exit;
     } catch (Throwable $e) {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed')]);
         exit;
     }
 }

 require('../../partials/header.php');
 ?>
 <body class="bg-base-200 min-h-screen">
     <div class="flex h-screen">
         <?php include '../../USM/sidebarr.php'; ?>
 
         <div class="flex flex-col flex-1 overflow-auto">
             <?php include '../../USM/navbar.php'; ?>
 
             <div class="max-w-7xl mx-auto p-6">
                 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                     <div>
                         <h1 class="text-2xl font-bold">Skill Gap Analysis</h1>
                         <div class="text-sm opacity-70">This module is being replaced with the KPI-based gap analysis.</div>
                     </div>
                     <div class="flex gap-2">
                         <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
                         <a href="pushed_critical_roles.php" class="btn btn-outline btn-sm">Pushed History</a>
                     </div>
                 </div>
 
                 <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                            <div class="w-full md:w-72">
                                <label class="label"><span class="label-text">Evaluation Period</span></label>
                                <input id="periodInput" type="text" class="input input-bordered w-full" placeholder="YYYY-Qn" />
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="refreshBtn" class="btn btn-primary">Refresh</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                            <div class="card bg-base-100 border border-base-300">
                                <div class="card-body">
                                    <h2 class="card-title text-base">Employees Missing KPIs</h2>
                                    <div class="text-sm opacity-70">All employees are listed here. Use View to open their KPI evaluations, then Analyze Gap Analysis and Save Formulation.</div>
                                    <div class="overflow-x-auto">
                                        <table class="table table-zebra table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Dept</th>
                                                    <th class="text-right">KPI</th>
                                                    <th class="text-right">Formulated</th>
                                                    <th class="text-right">Competency</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="missingRows"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-base-100 border border-base-300 mt-4">
                            <div class="card-body">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="card-title text-base">Selected Employee KPI Gap Computation</h2>
                                        <div class="text-sm opacity-70" id="selectedMeta">Select an employee to compute KPI % and gaps.</div>
                                    </div>
                                    <button type="button" id="saveBtn" class="btn btn-outline" disabled>Save Formulation</button>
                                </div>

                                <div class="overflow-x-auto mt-3">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>KPI</th>
                                                <th class="text-right">Rating (1-5)</th>
                                                <th class="text-right">KPI %</th>
                                                <th class="text-right">Required %</th>
                                                <th class="text-right">Gap %</th>
                                            </tr>
                                        </thead>
                                        <tbody id="resultRows"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
             </div>
         </div>
     </div>

     <dialog id="kpiViewModal" class="modal">
         <div class="modal-box max-w-5xl">
             <div class="flex items-start justify-between gap-3">
                 <div>
                     <h3 class="font-bold text-lg" id="kpiModalTitle">Employee KPI Evaluations</h3>
                     <div class="text-sm opacity-70" id="kpiModalMeta"></div>
                 </div>
                 <form method="dialog">
                     <button class="btn btn-sm btn-ghost" type="submit">✕</button>
                 </form>
             </div>

             <div class="mt-4">
                 <h4 class="font-semibold">Evaluated KPI (Competency Criteria)</h4>
                 <div class="text-sm opacity-70">Ratings are 1–5 per criteria.</div>
                 <div class="overflow-x-auto mt-2">
                     <table class="table table-zebra">
                         <thead>
                             <tr>
                                 <th>KPI</th>
                                 <th>Criteria</th>
                                 <th class="text-right">Rating (1-5)</th>
                             </tr>
                         </thead>
                         <tbody id="kpiModalRows"></tbody>
                     </table>
                 </div>
             </div>

             <div class="mt-6 flex justify-end">
                 <button type="button" class="btn btn-primary" id="openGapBtn">Gap Analysis</button>
             </div>
         </div>
     </dialog>

     <dialog id="gapAnalysisModal" class="modal">
         <div class="modal-box max-w-5xl">
             <div class="flex items-start justify-between gap-3">
                 <div>
                     <h3 class="font-bold text-lg">Gap Analysis</h3>
                     <div class="text-sm opacity-70" id="gapModalMeta"></div>
                 </div>
                 <form method="dialog">
                     <button class="btn btn-sm btn-ghost" type="submit">✕</button>
                 </form>
             </div>

             <div class="mt-4">
                 <div class="text-sm opacity-70" id="gapModalOverall">Computing...</div>
                 <div class="overflow-x-auto mt-2">
                     <table class="table table-zebra">
                         <thead>
                             <tr>
                                 <th>KPI</th>
                                 <th class="text-right">Rating (1-5)</th>
                                 <th class="text-right">KPI %</th>
                                 <th class="text-right">Required %</th>
                                 <th class="text-right">Gap %</th>
                             </tr>
                         </thead>
                         <tbody id="gapModalRows"></tbody>
                     </table>
                 </div>
             </div>

             <div class="modal-action">
                 <button type="button" class="btn btn-primary" id="gapConfirmBtn" disabled>Confirm</button>
             </div>
         </div>
     </dialog>

     <?php
     $__employees = [];
     try {
         global $pdo;
         $st = $pdo->query('SELECT employee_id, full_name, department, position AS role FROM employees ORDER BY full_name ASC');
         $__employees = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
     } catch (Throwable $e) {
         $__employees = [];
     }
     ?>

     <script>
         window.__employees = <?php echo json_encode($__employees ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
     </script>

     <script>
        lucide.createIcons();
    </script>
    <script>
        (function () {
            const els = {
                periodInput: document.getElementById('periodInput'),
                refreshBtn: document.getElementById('refreshBtn'),
                missingRows: document.getElementById('missingRows'),
                resultRows: document.getElementById('resultRows'),
                selectedMeta: document.getElementById('selectedMeta'),
                saveBtn: document.getElementById('saveBtn'),
            };

            const modalEls = {
                dialog: document.getElementById('kpiViewModal'),
                title: document.getElementById('kpiModalTitle'),
                meta: document.getElementById('kpiModalMeta'),
                rows: document.getElementById('kpiModalRows'),
                openGapBtn: document.getElementById('openGapBtn'),
            };

            const gapEls = {
                dialog: document.getElementById('gapAnalysisModal'),
                meta: document.getElementById('gapModalMeta'),
                overall: document.getElementById('gapModalOverall'),
                rows: document.getElementById('gapModalRows'),
                confirmBtn: document.getElementById('gapConfirmBtn'),
            };

            let selectedEmployeeId = '';
            let selectedComputed = null;
            let selectedOverall = null;
            let currentPeriod = '';

            let modalEmployeeId = '';
            let modalComputed = null;
            let modalOverall = null;

            function mapStatus(pct) {
                const p = Number(pct || 0);
                if (p <= 20) return 'Retrain';
                if (p <= 40) return 'Reskilling';
                if (p <= 60) return 'Refresher Training';
                if (p <= 80) return 'Upskilling';
                return 'Succession Ready';
            }

            const dummyCriteria = [
                { kpi_id: 1, kpi_name: 'Work Quality', required_pct: 80, departments: ['All'], roles: [] },
                { kpi_id: 2, kpi_name: 'Productivity', required_pct: 75, departments: ['All'], roles: [] },
                { kpi_id: 3, kpi_name: 'Customer Service', required_pct: 75, departments: ['All'], roles: [] },
                { kpi_id: 4, kpi_name: 'Teamwork', required_pct: 75, departments: ['All'], roles: [] },
                { kpi_id: 5, kpi_name: 'Compliance', required_pct: 80, departments: ['All'], roles: [] },
            ];

            function esc(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getDefaultPeriod() {
                const y = new Date().getFullYear();
                const q = Math.ceil((new Date().getMonth() + 1) / 3);
                return `${y}-Q${q}`;
            }

            function getEmployee(empId) {
                return (window.__employees || []).find(e => String(e.employee_id) === String(empId)) || null;
            }

            function criteriaForEmployee(emp) {
                if (!emp) return [];
                return dummyCriteria.filter(c => {
                    const depts = Array.isArray(c.departments) ? c.departments : ['All'];
                    const roles = Array.isArray(c.roles) ? c.roles : [];
                    const deptOk = depts.includes('All') || depts.includes(String(emp.department || ''));
                    const empRole = String(emp.role || '');
                    const roleOk = roles.length === 0 || roles.some(r => empRole.toLowerCase().includes(String(r).toLowerCase()));
                    return deptOk && roleOk;
                });
            }

            async function fetchLists(period) {
                const res = await fetch(`gap_analysis.php?ajax=1&action=list&evaluation_period=${encodeURIComponent(period)}`);
                const data = await res.json();
                if (!res.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Request failed');
                }
                return data;
            }

            async function fetchEmployee(empId, period) {
                const res = await fetch(`gap_analysis.php?ajax=1&action=employee&employee_id=${encodeURIComponent(empId)}&evaluation_period=${encodeURIComponent(period)}`);
                const data = await res.json();
                if (!res.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Request failed');
                }
                return data;
            }

            async function fetchKpis(empId, period) {
                const res = await fetch(`gap_analysis.php?ajax=1&action=kpis&employee_id=${encodeURIComponent(empId)}&evaluation_period=${encodeURIComponent(period)}`);
                const data = await res.json();
                if (!res.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Request failed');
                }
                return data;
            }

            async function loadLists() {
                currentPeriod = String(els.periodInput.value || '').trim() || getDefaultPeriod();
                els.periodInput.value = currentPeriod;

                try {
                    await fetch(`gap_analysis.php?ajax=1&action=seed&evaluation_period=${encodeURIComponent(currentPeriod)}`);
                } catch (e) {
                }

                const lists = await fetchLists(currentPeriod);
                const empRows = Array.isArray(lists.employees) ? lists.employees : [];

                els.missingRows.innerHTML = empRows.map(r => {
                    const mk = Number(r.missing_kpi) === 1 ? 'Yes' : 'No';
                    const mf = Number(r.missing_formulation) === 1 ? 'No' : 'Yes';
                    const comp = Number(r.overall_competency || 0);
                    const compText = Number(r.missing_formulation) === 1 ? '-' : `${comp.toFixed(1)}%`;
                    const statusText = Number(r.missing_formulation) === 1 ? '-' : String(r.status || '');
                    return `
                        <tr>
                            <td>
                                <div class="font-semibold">${esc(r.full_name)}</div>
                                <div class="text-xs opacity-70">${esc(r.employee_id)}</div>
                            </td>
                            <td>${esc(r.department)}</td>
                            <td class="text-right">${esc(mk)}</td>
                            <td class="text-right">${esc(mf)}</td>
                            <td class="text-right font-semibold">${esc(compText)}</td>
                            <td>${esc(statusText)}</td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline" data-view-emp="${esc(r.employee_id)}">View</button>
                            </td>
                        </tr>
                    `;
                }).join('') || `
                    <tr>
                        <td colspan="7" class="text-center py-10 opacity-70">No employees found.</td>
                    </tr>
                `;

                els.missingRows.querySelectorAll('button[data-view-emp]').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        await openKpiModal(btn.getAttribute('data-view-emp') || '');
                    });
                });

                lucide.createIcons();
            }

            function resetModal() {
                modalEmployeeId = '';
                modalComputed = null;
                modalOverall = null;
                if (modalEls.rows) modalEls.rows.innerHTML = '';
                if (gapEls.rows) gapEls.rows.innerHTML = '';
                if (gapEls.overall) gapEls.overall.textContent = 'Computing...';
                if (gapEls.confirmBtn) gapEls.confirmBtn.disabled = true;
            }

            async function openKpiModal(employeeId) {
                resetModal();
                modalEmployeeId = String(employeeId || '').trim();
                if (!modalEmployeeId) return;

                const emp = getEmployee(modalEmployeeId);
                if (modalEls.title) modalEls.title.textContent = `Employee KPI Evaluations`;
                if (modalEls.meta) {
                    modalEls.meta.textContent = `${(emp && emp.full_name) ? emp.full_name : ''} (${modalEmployeeId}) • ${(emp && emp.department) ? emp.department : ''} • ${currentPeriod}`;
                }

                if (modalEls.dialog && typeof modalEls.dialog.showModal === 'function') {
                    modalEls.dialog.showModal();
                }

                const data = await fetchKpis(modalEmployeeId, currentPeriod);
                const allItems = Array.isArray(data.items) ? data.items : [];

                const allowed = new Set((criteriaForEmployee(emp) || []).map(c => String(c.kpi_name || '').trim()).filter(Boolean));
                const items = allowed.size > 0 ? allItems.filter(it => allowed.has(String(it.kpi_name || '').trim())) : allItems;

                if (items.length < 5) {
                    if (modalEls.rows) {
                        modalEls.rows.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-10 opacity-70">Not enough KPI criteria found (need at least 5).</td>
                            </tr>
                        `;
                    }
                    return;
                }

                if (modalEls.rows) {
                    modalEls.rows.innerHTML = items.map(it => {
                        return `
                            <tr>
                                <td class="font-medium">${esc(it.kpi_name)}</td>
                                <td>${esc(it.criteria)}</td>
                                <td class="text-right">${Number(it.score || 0).toFixed(2)}</td>
                            </tr>
                        `;
                    }).join('');
                }
            }

            async function openGapAnalysisModal() {
                if (!modalEmployeeId) return;

                const emp = getEmployee(modalEmployeeId);
                if (gapEls.meta) {
                    gapEls.meta.textContent = `${(emp && emp.full_name) ? emp.full_name : ''} (${modalEmployeeId}) • ${(emp && emp.department) ? emp.department : ''} • ${currentPeriod}`;
                }

                if (gapEls.dialog && typeof gapEls.dialog.showModal === 'function') {
                    gapEls.dialog.showModal();
                }

                const data = await fetchEmployee(modalEmployeeId, currentPeriod);
                const emp2 = data.employee || emp;

                const allowed = new Set((criteriaForEmployee(emp2) || []).map(c => String(c.kpi_name || '').trim()).filter(Boolean));
                const allRows = Array.isArray(data.computed) ? data.computed : [];
                const filtered = allowed.size > 0 ? allRows.filter(r => allowed.has(String(r.kpi_name || '').trim())) : allRows;

                modalComputed = filtered;

                const sum = (filtered || []).reduce((acc, r) => acc + Number(r.kpi_pct || 0), 0);
                const cnt = (filtered || []).length;
                const overallPct = cnt > 0 ? (sum / cnt) : 0;
                modalOverall = { pct: Number(overallPct.toFixed(1)), status: mapStatus(overallPct) };

                if (gapEls.overall) {
                    gapEls.overall.textContent = `Competency Level: ${Number(modalOverall.pct || 0).toFixed(1)}% • ${String(modalOverall.status || '')}`;
                }

                if (gapEls.rows) {
                    gapEls.rows.innerHTML = (modalComputed || []).map(r => {
                        const gap = Number(r.gap_pct || 0);
                        const gapClass = gap > 0 ? 'text-error font-semibold' : 'text-success font-semibold';
                        return `
                            <tr>
                                <td class="font-medium">${esc(r.kpi_name)}</td>
                                <td class="text-right">${Number(r.avg || 0).toFixed(2)}</td>
                                <td class="text-right">${Number(r.kpi_pct || 0).toFixed(1)}%</td>
                                <td class="text-right">${Number(r.required_pct || 0).toFixed(1)}%</td>
                                <td class="text-right ${gapClass}">${Number(r.gap_pct || 0).toFixed(1)}%</td>
                            </tr>
                        `;
                    }).join('') || `
                        <tr>
                            <td colspan="5" class="text-center py-10 opacity-70">No KPI data found.</td>
                        </tr>
                    `;
                }

                if (gapEls.confirmBtn) {
                    gapEls.confirmBtn.disabled = !(modalOverall && Number.isFinite(Number(modalOverall.pct)));
                }
            }

            async function confirmGapAnalysis() {
                if (!modalEmployeeId || !modalOverall) return;

                selectedEmployeeId = modalEmployeeId;
                selectedComputed = modalComputed;
                selectedOverall = modalOverall;

                await saveFormulation();

                if (gapEls.dialog && typeof gapEls.dialog.close === 'function') {
                    gapEls.dialog.close();
                }
                if (modalEls.dialog && typeof modalEls.dialog.close === 'function') {
                    modalEls.dialog.close();
                }
            }

            async function selectEmployee(employeeId) {
                selectedEmployeeId = String(employeeId || '').trim();
                if (!selectedEmployeeId) return;

                const data = await fetchEmployee(selectedEmployeeId, currentPeriod);
                const emp = data.employee || getEmployee(selectedEmployeeId);

                const allowed = new Set((criteriaForEmployee(emp) || []).map(c => String(c.kpi_name || '').trim()).filter(Boolean));
                const allRows = Array.isArray(data.computed) ? data.computed : [];
                const filtered = allowed.size > 0 ? allRows.filter(r => allowed.has(String(r.kpi_name || '').trim())) : allRows;

                selectedComputed = filtered;
                selectedOverall = data.overall || { pct: 0, status: 'Retrain' };

                els.selectedMeta.textContent = `${(emp && emp.full_name) ? emp.full_name : ''} (${selectedEmployeeId}) • ${(emp && emp.department) ? emp.department : ''} • ${currentPeriod}`;

                els.resultRows.innerHTML = (selectedComputed || []).map(r => {
                    const gap = Number(r.gap_pct || 0);
                    const gapClass = gap > 0 ? 'text-error font-semibold' : 'text-success font-semibold';
                    return `
                        <tr>
                            <td class="font-medium">${esc(r.kpi_name)}</td>
                            <td class="text-right">${Number(r.avg || 0).toFixed(2)}</td>
                            <td class="text-right">${Number(r.kpi_pct || 0).toFixed(1)}%</td>
                            <td class="text-right">${Number(r.required_pct || 0).toFixed(1)}%</td>
                            <td class="text-right ${gapClass}">${Number(r.gap_pct || 0).toFixed(1)}%</td>
                        </tr>
                    `;
                }).join('') || `
                    <tr>
                        <td colspan="5" class="text-center py-10 opacity-70">No KPI data found.</td>
                    </tr>
                `;

                els.saveBtn.disabled = !(selectedOverall && Number.isFinite(Number(selectedOverall.pct)));
                lucide.createIcons();
            }

            async function saveFormulation() {
                if (!selectedEmployeeId || !selectedOverall) return;

                const payload = {
                    employee_id: selectedEmployeeId,
                    overall_competency: Number(selectedOverall.pct || 0),
                    status: String(selectedOverall.status || ''),
                    details: {
                        computed: selectedComputed,
                        overall: selectedOverall,
                    }
                };

                try {
                    await fetch(`gap_analysis.php?ajax=1&action=save&evaluation_period=${encodeURIComponent(currentPeriod)}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                } catch (e) {
                }

                await loadLists();
            }

            els.periodInput.value = getDefaultPeriod();
            els.refreshBtn.addEventListener('click', () => loadLists());
            els.saveBtn.addEventListener('click', () => saveFormulation());

            if (modalEls.openGapBtn) {
                modalEls.openGapBtn.addEventListener('click', () => openGapAnalysisModal());
            }
            if (gapEls.confirmBtn) {
                gapEls.confirmBtn.addEventListener('click', () => confirmGapAnalysis());
            }

            loadLists().catch(() => {});
        })();
    </script>
     <script src="../../soliera.js"></script>
     <script src="../../sidebar.js"></script>
 </body>
 </html>
