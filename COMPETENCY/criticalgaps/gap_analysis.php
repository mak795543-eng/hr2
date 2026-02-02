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
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <div class="font-semibold">Employees Pending KPI Gap Computation</div>
                                <div class="text-sm opacity-70">Employees with KPI evaluation scores but not yet calculated/formulated.</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="refreshBtn" class="btn btn-primary">Refresh</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Dept</th>
                                        <th>Position</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingRows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <dialog id="analyzeModal" class="modal">
                    <div class="modal-box max-w-5xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">KPI Computation</h3>
                        <div class="text-sm opacity-70" id="analyzeMeta">Loading...</div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>KPI</th>
                                        <th>Scores</th>
                                        <th class="text-right">Avg</th>
                                        <th class="text-right">KPI %</th>
                                        <th class="text-right">Required %</th>
                                        <th class="text-right">Gap %</th>
                                    </tr>
                                </thead>
                                <tbody id="analyzeRows"></tbody>
                            </table>
                        </div>

                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" id="saveBtn" class="btn btn-primary" disabled>Continue</button>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>

                <dialog id="forwardModal" class="modal">
                    <div class="modal-box max-w-xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-2">Forward to Critical Roles</h3>
                        <div class="text-sm opacity-70" id="forwardMeta">Loading...</div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="card bg-base-200">
                                <div class="card-body p-4">
                                    <div class="text-xs opacity-70">Overall Average</div>
                                    <div class="text-2xl font-bold" id="forwardOverallAvg">—</div>
                                </div>
                            </div>
                            <div class="card bg-base-200">
                                <div class="card-body p-4">
                                    <div class="text-xs opacity-70">Overall KPI %</div>
                                    <div class="text-2xl font-bold" id="forwardOverallPct">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="text-xs opacity-70">Status</div>
                            <div class="font-semibold" id="forwardStatus">—</div>
                        </div>

                        <div class="flex justify-end gap-2 mt-5">
                            <button type="button" id="applyBtn" class="btn btn-primary" disabled>Forward Employee</button>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            </div>
        </div>
    </div>
 
     <script>
        lucide.createIcons();
    </script>
    <script>
        (function () {
            const apiUrl = '../../api/gap_analysis.php';

            const els = {
                refreshBtn: document.getElementById('refreshBtn'),
                pendingRows: document.getElementById('pendingRows'),
                analyzeModal: document.getElementById('analyzeModal'),
                analyzeMeta: document.getElementById('analyzeMeta'),
                analyzeRows: document.getElementById('analyzeRows'),
                saveBtn: document.getElementById('saveBtn'),
                forwardModal: document.getElementById('forwardModal'),
                forwardMeta: document.getElementById('forwardMeta'),
                forwardOverallAvg: document.getElementById('forwardOverallAvg'),
                forwardOverallPct: document.getElementById('forwardOverallPct'),
                forwardStatus: document.getElementById('forwardStatus'),
                applyBtn: document.getElementById('applyBtn'),
            };

            let selectedEmployeeId = '';
            let selectedComputed = null;
            let selectedOverall = null;

            function esc(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            async function fetchJson(url, opts) {
                const res = await fetch(url, opts || { method: 'GET' });
                const raw = await res.text();
                let data = null;
                try {
                    data = raw ? JSON.parse(raw) : null;
                } catch (e) {
                    data = null;
                }
                if (!res.ok || (data && data.success === false)) {
                    const msg = (data && data.message)
                        ? data.message
                        : (raw ? raw.slice(0, 180) : `Request failed (${res.status})`);
                    throw new Error(msg);
                }
                return data;
            }

            async function loadLists() {
                try {
                    const pending = await fetchJson(`${apiUrl}?action=pending`);

                    const rows = Array.isArray(pending && pending.data) ? pending.data : [];

                    els.pendingRows.innerHTML = rows.map(r => {
                        return `
                            <tr>
                                <td>
                                    <div class="font-semibold">${esc(r.full_name)}</div>
                                    <div class="text-xs opacity-70">${esc(r.employee_id)}</div>
                                </td>
                                <td>${esc(r.department)}</td>
                                <td>${esc(r.position)}</td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-outline" data-analyze="${esc(r.employee_id)}">Analyze</button>
                                </td>
                            </tr>
                        `;
                    }).join('') || `
                        <tr>
                            <td colspan="4" class="text-center py-10 opacity-70">No pending employees.</td>
                        </tr>
                    `;

                    els.pendingRows.querySelectorAll('button[data-analyze]').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            await analyzeEmployee(btn.getAttribute('data-analyze') || '');
                        });
                    });

                    lucide.createIcons();
                } catch (err) {
                    console.error(err);
                    els.pendingRows.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-10 text-error">
                                Failed to load employees. Please refresh and check the server/console.
                            </td>
                        </tr>
                    `;
                }
            }

            async function analyzeEmployee(employeeId) {
                selectedEmployeeId = String(employeeId || '').trim();
                if (!selectedEmployeeId) return;

                const data = await fetchJson(`${apiUrl}?action=employee&employee_id=${encodeURIComponent(selectedEmployeeId)}`);
                const emp = data.employee || {};
                selectedComputed = Array.isArray(data.computed) ? data.computed : [];
                selectedOverall = data.overall || null;

                els.analyzeMeta.textContent = `${emp.full_name || ''} (${emp.employee_id || ''}) • ${emp.department || ''} • ${data.evaluation_period || ''}`;

                els.analyzeRows.innerHTML = (selectedComputed || []).map(r => {
                    const evals = Array.isArray(r.evaluations) ? r.evaluations : [];
                    const scores = evals.map(e => Number(e.score || 0));
                    const scoreText = scores.length ? scores.join(', ') : '';
                    const avg = Number(r.avg || 0);
                    const kpiPct = Number(r.kpi_pct || 0);
                    const reqPct = Number(r.required_pct || 0);
                    const gap = Number(r.gap_pct || 0);
                    const gapClass = gap > 0 ? 'text-error font-semibold' : 'text-success font-semibold';

                    return `
                        <tr>
                            <td class="font-medium">${esc(r.kpi_name)}</td>
                            <td class="text-sm">${esc(scoreText)}</td>
                            <td class="text-right">${avg.toFixed(2)}</td>
                            <td class="text-right">${kpiPct.toFixed(1)}%</td>
                            <td class="text-right">${reqPct.toFixed(1)}%</td>
                            <td class="text-right ${gapClass}">${gap.toFixed(1)}%</td>
                        </tr>
                    `;
                }).join('') || `
                    <tr>
                        <td colspan="6" class="text-center py-10 opacity-70">No KPI data found.</td>
                    </tr>
                `;

                els.saveBtn.disabled = !(selectedOverall && Number.isFinite(Number(selectedOverall.pct)));
                lucide.createIcons();

                els.analyzeModal.showModal();
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

                await fetchJson(`${apiUrl}?action=save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                els.forwardMeta.textContent = els.analyzeMeta.textContent || '—';
                els.forwardOverallAvg.textContent = Number(selectedOverall.avg || 0).toFixed(2);
                els.forwardOverallPct.textContent = `${Number(selectedOverall.pct || 0).toFixed(1)}%`;
                els.forwardStatus.textContent = String(selectedOverall.status || '');
                els.applyBtn.disabled = false;

                try {
                    if (els.analyzeModal && typeof els.analyzeModal.close === 'function') {
                        els.analyzeModal.close();
                    }
                } catch (e) {
                }

                els.forwardModal.showModal();
            }

            async function applyEmployeeForward() {
                if (!selectedEmployeeId) return;
                els.applyBtn.disabled = true;
                try {
                    await fetchJson(`${apiUrl}?action=apply_employee&employee_id=${encodeURIComponent(selectedEmployeeId)}`, { method: 'POST' });

                    try {
                        if (els.forwardModal && typeof els.forwardModal.close === 'function') {
                            els.forwardModal.close();
                        }
                    } catch (e) {
                    }

                    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Forwarded',
                            text: 'Employee has been forwarded to Critical Roles.',
                            confirmButtonColor: '#1f2937'
                        });
                    } else {
                        alert('Employee has been forwarded to Critical Roles.');
                    }

                    selectedEmployeeId = '';
                    selectedComputed = null;
                    selectedOverall = null;

                    await loadLists();
                } catch (err) {
                    els.applyBtn.disabled = false;
                    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Forward failed',
                            text: (err && err.message) ? err.message : 'Failed to forward employee.',
                            confirmButtonColor: '#1f2937'
                        });
                    } else {
                        alert((err && err.message) ? err.message : 'Failed to forward employee.');
                    }
                }
            }

            els.refreshBtn.addEventListener('click', () => loadLists());
            els.saveBtn.addEventListener('click', () => saveFormulation());
            els.applyBtn.addEventListener('click', () => applyEmployeeForward());

            loadLists();
        })();
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
 </body>
 </html>
