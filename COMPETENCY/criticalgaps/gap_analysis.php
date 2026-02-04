<?php
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
                                    <h2 class="card-title text-base">Employees Missing KPI / Gap Formulation</h2>
                                    <div class="text-sm opacity-70">These employees either have no KPI evaluations for the period or have not been formulated.</div>
                                    <div class="overflow-x-auto">
                                        <table class="table table-zebra table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Dept</th>
                                                    <th class="text-right">KPI</th>
                                                    <th class="text-right">Formulation</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="missingRows"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-base-100 border border-base-300">
                                <div class="card-body">
                                    <h2 class="card-title text-base">Processed Employees (Formulated)</h2>
                                    <div class="text-sm opacity-70">Employees with saved KPI gap formulation for the period.</div>
                                    <div class="overflow-x-auto">
                                        <table class="table table-zebra table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Dept</th>
                                                    <th class="text-right">Competency</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="processedRows"></tbody>
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
                                                <th class="text-right">Avg</th>
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
 
     <script>
        lucide.createIcons();
    </script>
    <script>
        (function () {
            const apiBase = window.location.origin;
            const apiUrl = `${apiBase}/api/gap_analysis.php`;

            const els = {
                periodInput: document.getElementById('periodInput'),
                refreshBtn: document.getElementById('refreshBtn'),
                missingRows: document.getElementById('missingRows'),
                processedRows: document.getElementById('processedRows'),
                resultRows: document.getElementById('resultRows'),
                selectedMeta: document.getElementById('selectedMeta'),
                saveBtn: document.getElementById('saveBtn'),
            };

            let selectedEmployeeId = '';
            let selectedComputed = null;
            let selectedOverall = null;
            let currentPeriod = '';

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

            async function fetchJson(url, opts) {
                const res = await fetch(url, opts || { method: 'GET' });
                const data = await res.json();
                if (!res.ok || (data && data.success === false)) {
                    throw new Error((data && data.message) ? data.message : `Request failed (${res.status})`);
                }
                return data;
            }

            async function loadLists() {
                currentPeriod = String(els.periodInput.value || '').trim() || getDefaultPeriod();
                els.periodInput.value = currentPeriod;

                const missing = await fetchJson(`${apiUrl}?action=missing&evaluation_period=${encodeURIComponent(currentPeriod)}`);
                const processed = await fetchJson(`${apiUrl}?action=processed&evaluation_period=${encodeURIComponent(currentPeriod)}`);

                const missRows = Array.isArray(missing.data) ? missing.data : [];
                const procRows = Array.isArray(processed.data) ? processed.data : [];

                els.missingRows.innerHTML = missRows.map(r => {
                    const mk = Number(r.missing_kpi) === 1 ? 'Yes' : 'No';
                    const mf = Number(r.missing_formulation) === 1 ? 'Yes' : 'No';
                    return `
                        <tr>
                            <td>
                                <div class="font-semibold">${esc(r.full_name)}</div>
                                <div class="text-xs opacity-70">${esc(r.employee_id)}</div>
                            </td>
                            <td>${esc(r.department)}</td>
                            <td class="text-right">${esc(mk)}</td>
                            <td class="text-right">${esc(mf)}</td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline" data-emp="${esc(r.employee_id)}">Select</button>
                            </td>
                        </tr>
                    `;
                }).join('') || `
                    <tr>
                        <td colspan="5" class="text-center py-10 opacity-70">No missing employees.</td>
                    </tr>
                `;

                els.processedRows.innerHTML = procRows.map(r => {
                    return `
                        <tr>
                            <td>
                                <div class="font-semibold">${esc(r.full_name)}</div>
                                <div class="text-xs opacity-70">${esc(r.employee_id)}</div>
                            </td>
                            <td>${esc(r.department)}</td>
                            <td class="text-right font-semibold">${Number(r.overall_competency || 0).toFixed(1)}%</td>
                            <td>${esc(r.status || '')}</td>
                        </tr>
                    `;
                }).join('') || `
                    <tr>
                        <td colspan="4" class="text-center py-10 opacity-70">No processed employees yet.</td>
                    </tr>
                `;

                els.missingRows.querySelectorAll('button[data-emp]').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        await selectEmployee(btn.getAttribute('data-emp') || '');
                    });
                });

                lucide.createIcons();
            }

            async function selectEmployee(employeeId) {
                selectedEmployeeId = String(employeeId || '').trim();
                if (!selectedEmployeeId) return;

                const data = await fetchJson(`${apiUrl}?action=employee&employee_id=${encodeURIComponent(selectedEmployeeId)}&evaluation_period=${encodeURIComponent(currentPeriod)}`);
                const emp = data.employee || {};
                selectedComputed = Array.isArray(data.computed) ? data.computed : [];
                selectedOverall = data.overall || null;

                els.selectedMeta.textContent = `${emp.full_name || ''} (${emp.employee_id || ''}) • ${emp.department || ''} • ${data.evaluation_period || ''}`;

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

                await fetchJson(`${apiUrl}?action=save&evaluation_period=${encodeURIComponent(currentPeriod)}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                await loadLists();
            }

            els.periodInput.value = getDefaultPeriod();
            els.refreshBtn.addEventListener('click', () => loadLists());
            els.saveBtn.addEventListener('click', () => saveFormulation());

            loadLists().catch(() => {});
        })();
    </script>
     <script src="../../soliera.js"></script>
     <script src="../../sidebar.js"></script>
 </body>
 </html>
