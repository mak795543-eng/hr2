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
                         <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                             <div class="card bg-base-100 border border-base-300">
                                 <div class="card-body">
                                     <div class="flex items-center justify-between gap-3">
                                         <h2 class="card-title text-base">Sample API Payload</h2>
                                         <button type="button" id="runSample" class="btn btn-primary btn-sm">Run Sample</button>
                                     </div>
                                     <div class="text-sm opacity-70">Mocked employee KPI evaluations (this will be replaced by your real API).</div>
                                     <pre id="samplePayload" class="bg-base-200 rounded p-3 text-xs overflow-auto max-h-80"></pre>
                                 </div>
                             </div>
 
                             <div class="card bg-base-100 border border-base-300">
                                 <div class="card-body">
                                     <h2 class="card-title text-base">Sample Competency Criteria</h2>
                                     <div class="text-sm opacity-70">Mocked required levels (%). In production, this comes from Competency Criteria.</div>
                                     <div class="overflow-x-auto">
                                         <table class="table table-zebra table-sm">
                                             <thead>
                                                 <tr>
                                                     <th>KPI</th>
                                                     <th class="text-right">Required Level (%)</th>
                                                 </tr>
                                             </thead>
                                             <tbody id="criteriaRows"></tbody>
                                         </table>
                                     </div>
                                 </div>
                             </div>
                         </div>
 
                         <div class="card bg-base-100 border border-base-300 mt-4">
                             <div class="card-body">
                                 <h2 class="card-title text-base">Computed KPI Results and Gaps</h2>
                                 <div class="text-sm opacity-70">KPI % = (average evaluation / max_score) * 100. Gap = required_level - KPI % (clamped at 0).</div>
                                 <div class="overflow-x-auto">
                                     <table class="table table-zebra">
                                         <thead>
                                             <tr>
                                                 <th>KPI</th>
                                                 <th class="text-right">Evaluations</th>
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
             const maxScore = 5;
 
             const sampleApiResponse = {
                 employee_id: 'EMP-1001',
                 employee_name: 'Juan Dela Cruz',
                 department: 'Front Office',
                 kpis: [
                     { kpi: 'Productivity', evaluations: [4, 5, 3, 4] },
                     { kpi: 'Development', evaluations: [3, 3, 4] },
                     { kpi: 'Compliance', evaluations: [5, 4, 5, 5] }
                 ]
             };
 
             const sampleCriteria = [
                 { name: 'Productivity', required_level: 85 },
                 { name: 'Development', required_level: 75 },
                 { name: 'Compliance', required_level: 90 }
             ];
 
             const criteriaMap = new Map(sampleCriteria.map(c => [String(c.name), Number(c.required_level) || 0]));
 
             function esc(s) {
                 return String(s ?? '')
                     .replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
             }
 
             function computeAvg(values) {
                 const arr = Array.isArray(values) ? values : [];
                 const nums = arr.map(v => Number(v)).filter(v => Number.isFinite(v));
                 if (nums.length === 0) return 0;
                 const sum = nums.reduce((a, b) => a + b, 0);
                 return sum / nums.length;
             }
 
             function clampPct(v) {
                 let n = Number(v);
                 if (!Number.isFinite(n)) n = 0;
                 if (n < 0) n = 0;
                 if (n > 100) n = 100;
                 return n;
             }
 
             function run() {
                 const payloadEl = document.getElementById('samplePayload');
                 const criteriaRowsEl = document.getElementById('criteriaRows');
                 const resultRowsEl = document.getElementById('resultRows');
 
                 if (payloadEl) {
                     payloadEl.textContent = JSON.stringify(sampleApiResponse, null, 2);
                 }
 
                 if (criteriaRowsEl) {
                     criteriaRowsEl.innerHTML = sampleCriteria.map(c => {
                         return `
                             <tr>
                                 <td>${esc(c.name)}</td>
                                 <td class="text-right font-medium">${clampPct(c.required_level).toFixed(1)}%</td>
                             </tr>
                         `;
                     }).join('');
                 }
 
                 const kpis = Array.isArray(sampleApiResponse.kpis) ? sampleApiResponse.kpis : [];
                 const rows = kpis.map(item => {
                     const name = String(item.kpi ?? '');
                     const evals = Array.isArray(item.evaluations) ? item.evaluations : [];
                     const avg = computeAvg(evals);
                     const kpiPct = clampPct((avg / maxScore) * 100);
                     const required = clampPct(criteriaMap.get(name) ?? 0);
                     const gap = clampPct(Math.max(0, required - kpiPct));
                     return {
                         name,
                         evals,
                         avg,
                         kpiPct,
                         required,
                         gap
                     };
                 });
 
                 if (resultRowsEl) {
                     resultRowsEl.innerHTML = rows.map(r => {
                         const gapClass = r.gap > 0 ? 'text-error font-semibold' : 'text-success font-semibold';
                         return `
                             <tr>
                                 <td class="font-medium">${esc(r.name)}</td>
                                 <td class="text-right">${esc(JSON.stringify(r.evals))}</td>
                                 <td class="text-right">${r.avg.toFixed(2)}</td>
                                 <td class="text-right">${r.kpiPct.toFixed(1)}%</td>
                                 <td class="text-right">${r.required.toFixed(1)}%</td>
                                 <td class="text-right ${gapClass}">${r.gap.toFixed(1)}%</td>
                             </tr>
                         `;
                     }).join('');
                 }
 
                 lucide.createIcons();
             }
 
             const btn = document.getElementById('runSample');
             if (btn) {
                 btn.addEventListener('click', run);
             }
 
             run();
         })();
     </script>
     <script src="../../soliera.js"></script>
     <script src="../../sidebar.js"></script>
 </body>
 </html>
