<?php
session_start();

if (!isset($_SESSION['role'])) {
  header("Location: index.php");
  exit();
}

$employeeId = $_GET['employee_id'] ?? ($_SESSION['employee_id'] ?? '');
$employeeId = is_scalar($employeeId) ? trim((string)$employeeId) : '';

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($basePath === '.') {
  $basePath = '';
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee KPI Evaluation</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <meta name="api-base" content="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="stylesheet" href="soliera.css">
  <link rel="stylesheet" href="sidebar.css">
</head>

<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include 'USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include 'USM/navbar.php'; ?>

      <main class="flex-1 p-6 overflow-auto">
        <div class="max-w-7xl mx-auto space-y-6">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 class="text-2xl font-bold text-gray-800">Employee KPI Evaluation</h1>
              <p class="text-gray-600">Read-only viewer</p>
            </div>
            <div class="badge badge-outline gap-2">
              <i data-lucide="id-card" class="w-4 h-4"></i>
              <span id="employeeIdBadge"><?php echo htmlspecialchars($employeeId !== '' ? $employeeId : 'No employee_id', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>

          <div class="card bg-white shadow-sm">
            <div class="card-body">
              <div class="flex items-center gap-2 mb-2">
                <i data-lucide="user" class="w-5 h-5 text-gray-700"></i>
                <h2 class="card-title text-lg">Employee Information</h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-xs uppercase tracking-wide text-gray-500">Employee Name</div>
                  <div id="employeeName" class="font-semibold text-gray-800">-</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-xs uppercase tracking-wide text-gray-500">Employee ID</div>
                  <div id="employeeId" class="font-semibold text-gray-800">-</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-xs uppercase tracking-wide text-gray-500">Department</div>
                  <div id="department" class="font-semibold text-gray-800">-</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-xs uppercase tracking-wide text-gray-500">Role</div>
                  <div id="role" class="font-semibold text-gray-800">-</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 md:col-span-2 lg:col-span-2">
                  <div class="text-xs uppercase tracking-wide text-gray-500">Evaluation Period</div>
                  <div id="evaluationPeriod" class="font-semibold text-gray-800">-</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card bg-white shadow-sm">
            <div class="card-body">
              <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                  <i data-lucide="clipboard-list" class="w-5 h-5 text-gray-700"></i>
                  <h2 class="card-title text-lg">KPIs</h2>
                </div>

                <div role="tablist" class="tabs tabs-boxed">
                  <a role="tab" class="tab tab-active" id="tabCards">Cards</a>
                  <a role="tab" class="tab" id="tabTable">Table</a>
                </div>
              </div>

              <div id="stateLoading" class="mt-4">
                <div class="flex items-center gap-2 text-gray-600">
                  <span class="loading loading-spinner loading-sm"></span>
                  <span>Loading evaluation...</span>
                </div>
              </div>

              <div id="stateError" class="alert alert-error mt-4 hidden">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <span id="errorText">Failed to load.</span>
              </div>

              <div id="stateEmpty" class="alert mt-4 hidden">
                <i data-lucide="info" class="w-5 h-5"></i>
                <span>No KPIs found.</span>
              </div>

              <div id="viewCards" class="mt-4 space-y-3"></div>

              <div id="viewTable" class="mt-4 hidden">
                <div class="overflow-x-auto">
                  <table class="table table-zebra w-full">
                    <thead>
                      <tr>
                        <th>KPI</th>
                        <th>Criteria</th>
                        <th class="text-right">Score</th>
                      </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script src="soliera.js"></script>
  <script src="sidebar.js"></script>
  <script>
    lucide.createIcons();

    const employeeId = <?php echo json_encode($employeeId, JSON_UNESCAPED_SLASHES); ?>;
    const apiBase = document.querySelector('meta[name="api-base"]')?.content || '';

    const els = {
      employeeName: document.getElementById('employeeName'),
      employeeId: document.getElementById('employeeId'),
      department: document.getElementById('department'),
      role: document.getElementById('role'),
      evaluationPeriod: document.getElementById('evaluationPeriod'),
      employeeIdBadge: document.getElementById('employeeIdBadge'),
      stateLoading: document.getElementById('stateLoading'),
      stateError: document.getElementById('stateError'),
      errorText: document.getElementById('errorText'),
      stateEmpty: document.getElementById('stateEmpty'),
      viewCards: document.getElementById('viewCards'),
      viewTable: document.getElementById('viewTable'),
      tableBody: document.getElementById('tableBody'),
      tabCards: document.getElementById('tabCards'),
      tabTable: document.getElementById('tabTable'),
    };

    function setText(el, value) {
      el.textContent = (value === null || value === undefined || value === '') ? '-' : String(value);
    }

    function show(el) {
      el.classList.remove('hidden');
    }

    function hide(el) {
      el.classList.add('hidden');
    }

    function clearChildren(el) {
      while (el.firstChild) el.removeChild(el.firstChild);
    }

    function renderEmployeeHeader(data) {
      setText(els.employeeName, data.employee_name);
      setText(els.employeeId, data.employee_id);
      setText(els.department, data.department);
      setText(els.role, data.role);
      setText(els.evaluationPeriod, data.evaluation_period);

      if (els.employeeIdBadge) {
        els.employeeIdBadge.textContent = (data.employee_id === null || data.employee_id === undefined || data.employee_id === '')
          ? (employeeId || 'No employee_id')
          : String(data.employee_id);
      }
    }

    function createKpiCard(kpi, index) {
      const collapse = document.createElement('div');
      collapse.className = 'collapse collapse-arrow bg-gray-50 border border-gray-200';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = index === 0;

      const title = document.createElement('div');
      title.className = 'collapse-title font-semibold text-gray-800 flex items-center gap-2';

      const icon = document.createElement('i');
      icon.setAttribute('data-lucide', 'target');
      icon.className = 'w-4 h-4 text-gray-700';

      const titleText = document.createElement('span');
      titleText.textContent = kpi && kpi.kpi_name ? String(kpi.kpi_name) : 'KPI';

      title.appendChild(icon);
      title.appendChild(titleText);

      const content = document.createElement('div');
      content.className = 'collapse-content';

      const list = document.createElement('div');
      list.className = 'mt-2 space-y-2';

      const evaluations = Array.isArray(kpi?.evaluations) ? kpi.evaluations : [];
      if (evaluations.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'text-gray-600 text-sm';
        empty.textContent = 'No evaluation criteria.';
        list.appendChild(empty);
      } else {
        evaluations.forEach((ev) => {
          const row = document.createElement('div');
          row.className = 'flex items-start justify-between gap-3 bg-white border border-gray-200 rounded-lg p-3';

          const left = document.createElement('div');
          left.className = 'text-gray-800';
          left.textContent = ev && ev.criteria ? String(ev.criteria) : '-';

          const right = document.createElement('div');
          right.className = 'badge badge-neutral badge-outline font-mono';
          right.textContent = (ev && ev.score !== undefined && ev.score !== null) ? String(ev.score) : '-';

          row.appendChild(left);
          row.appendChild(right);
          list.appendChild(row);
        });
      }

      content.appendChild(list);
      collapse.appendChild(checkbox);
      collapse.appendChild(title);
      collapse.appendChild(content);
      return collapse;
    }

    function renderKpiCards(kpis) {
      clearChildren(els.viewCards);
      kpis.forEach((kpi, idx) => {
        els.viewCards.appendChild(createKpiCard(kpi, idx));
      });
      lucide.createIcons();
    }

    function renderKpiTable(kpis) {
      clearChildren(els.tableBody);

      kpis.forEach((kpi) => {
        const kpiName = kpi && kpi.kpi_name ? String(kpi.kpi_name) : 'KPI';
        const evaluations = Array.isArray(kpi?.evaluations) ? kpi.evaluations : [];

        if (evaluations.length === 0) {
          const tr = document.createElement('tr');

          const tdKpi = document.createElement('td');
          tdKpi.textContent = kpiName;

          const tdCriteria = document.createElement('td');
          tdCriteria.textContent = '-';

          const tdScore = document.createElement('td');
          tdScore.className = 'text-right font-mono';
          tdScore.textContent = '-';

          tr.appendChild(tdKpi);
          tr.appendChild(tdCriteria);
          tr.appendChild(tdScore);
          els.tableBody.appendChild(tr);
          return;
        }

        evaluations.forEach((ev) => {
          const tr = document.createElement('tr');

          const tdKpi = document.createElement('td');
          tdKpi.textContent = kpiName;

          const tdCriteria = document.createElement('td');
          tdCriteria.textContent = ev && ev.criteria ? String(ev.criteria) : '-';

          const tdScore = document.createElement('td');
          tdScore.className = 'text-right font-mono';
          tdScore.textContent = (ev && ev.score !== undefined && ev.score !== null) ? String(ev.score) : '-';

          tr.appendChild(tdKpi);
          tr.appendChild(tdCriteria);
          tr.appendChild(tdScore);
          els.tableBody.appendChild(tr);
        });
      });
    }

    function setTab(active) {
      if (active === 'cards') {
        els.tabCards.classList.add('tab-active');
        els.tabTable.classList.remove('tab-active');
        hide(els.viewTable);
        show(els.viewCards);
      } else {
        els.tabTable.classList.add('tab-active');
        els.tabCards.classList.remove('tab-active');
        hide(els.viewCards);
        show(els.viewTable);
      }
    }

    els.tabCards.addEventListener('click', () => setTab('cards'));
    els.tabTable.addEventListener('click', () => setTab('table'));

    async function loadEvaluation() {
      hide(els.stateError);
      hide(els.stateEmpty);
      show(els.stateLoading);
      clearChildren(els.viewCards);
      clearChildren(els.tableBody);

      if (!employeeId) {
        hide(els.stateLoading);
        show(els.stateError);
        els.errorText.textContent = 'Missing employee_id. Provide ?employee_id=... in the URL.';
        lucide.createIcons();
        return;
      }

      const url = `${apiBase}/api/employee_kpi_evaluation.php?employee_id=${encodeURIComponent(employeeId)}`;

      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
        });

        if (!res.ok) {
          const msg = `Request failed (${res.status})`;
          throw new Error(msg);
        }

        const data = await res.json();
        renderEmployeeHeader(data || {});

        const kpis = Array.isArray(data?.kpis) ? data.kpis : [];

        hide(els.stateLoading);

        if (kpis.length === 0) {
          show(els.stateEmpty);
          lucide.createIcons();
          return;
        }

        renderKpiCards(kpis);
        renderKpiTable(kpis);
        lucide.createIcons();
      } catch (e) {
        hide(els.stateLoading);
        show(els.stateError);
        els.errorText.textContent = e && e.message ? String(e.message) : 'Failed to load evaluation.';
        lucide.createIcons();
      }
    }

    setTab('cards');
    loadEvaluation();
  </script>
</body>

</html>
