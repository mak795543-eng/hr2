<?php
session_start();
require_once __DIR__ . '/db.php';

$getOwnerKey = function(): string {
    $candidates = [
        'user_id' => 'user:',
        'employee_id' => 'emp:',
        'employee_no' => 'empno:',
        'username' => 'user:',
        'email' => 'user:',
    ];
    foreach ($candidates as $k => $prefix) {
        if (isset($_SESSION[$k]) && trim((string)$_SESSION[$k]) !== '') {
            return $prefix . trim((string)$_SESSION[$k]);
        }
    }
    return 'sess:' . session_id();
};

$ownerKey = $getOwnerKey();

$employeesCount = 0;
try {
    $res = $conn->query("SELECT COUNT(*) AS c FROM employees");
    $row = $res ? $res->fetch_assoc() : null;
    $employeesCount = $row ? (int)($row['c'] ?? 0) : 0;
} catch (Throwable $e) {
    $employeesCount = 0;
}

$enrolledCount = 0;
try {
    $res = $conn->query("SELECT COUNT(*) AS c FROM training_post_assignments");
    $row = $res ? $res->fetch_assoc() : null;
    $enrolledCount = $row ? (int)($row['c'] ?? 0) : 0;
} catch (Throwable $e) {
    $enrolledCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Training Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body class="bg-gray-50 min-h-screen" data-owner-key="<?= htmlspecialchars($ownerKey) ?>">
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
     
<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Training management overview</p>
      </div>
      <div class="flex gap-2">
        <a href="trainingprogram.php" class="btn btn-outline btn-sm">Training Programs</a>
        <a href="drafts.php" class="btn btn-outline btn-sm">Drafts</a>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in">
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Total Trainings</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-total-trainings">0</div>
            <div class="text-xs text-gray-500 mt-1">This Month: <span id="dash-total-month">0</span> / Year: <span id="dash-total-year">0</span></div>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <i data-lucide="book-open" class="h-6 w-6 text-blue-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Upcoming Trainings</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-upcoming">0</div>
          </div>
          <div class="p-3 bg-yellow-100 rounded-full">
            <i data-lucide="calendar" class="h-6 w-6 text-yellow-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Ongoing Trainings</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-ongoing">0</div>
          </div>
          <div class="p-3 bg-green-100 rounded-full">
            <i data-lucide="activity" class="h-6 w-6 text-green-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Completed Trainings</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-completed">0</div>
          </div>
          <div class="p-3 bg-purple-100 rounded-full">
            <i data-lucide="check-circle" class="h-6 w-6 text-purple-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Cancelled / Postponed</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-cancelled">0</div>
          </div>
          <div class="p-3 bg-red-100 rounded-full">
            <i data-lucide="x-circle" class="h-6 w-6 text-red-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Employees</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-employees"><?= (int)$employeesCount ?></div>
          </div>
          <div class="p-3 bg-slate-100 rounded-full">
            <i data-lucide="users" class="h-6 w-6 text-slate-600"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 fade-in">
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-lg font-semibold text-gray-800">Status Distribution</div>
            <div class="text-sm text-gray-500">Planned / Pending Approval / Approved / Ongoing / Completed</div>
          </div>
        </div>
        <div class="h-72">
          <canvas id="statusDonut"></canvas>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-lg font-semibold text-gray-800">Monthly Trends</div>
            <div class="text-sm text-gray-500">Trainings and participants per month</div>
          </div>
        </div>
        <div class="h-72">
          <canvas id="monthlyBar"></canvas>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 fade-in">
      <div class="flex items-center justify-between mb-4">
        <div>
          <div class="text-lg font-semibold text-gray-800">Participation Metrics</div>
          <div class="text-sm text-gray-500">Rollup based on posted assignments</div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-base-200 rounded-lg p-4">
          <div class="text-sm text-gray-500">Total Employees Enrolled</div>
          <div class="text-2xl font-bold text-gray-900" id="pm-enrolled"><?= (int)$enrolledCount ?></div>
        </div>
        <div class="bg-base-200 rounded-lg p-4">
          <div class="text-sm text-gray-500">Employees Attended</div>
          <div class="text-2xl font-bold text-gray-900" id="pm-attended">0</div>
        </div>
        <div class="bg-base-200 rounded-lg p-4">
          <div class="text-sm text-gray-500">Employees Completed</div>
          <div class="text-2xl font-bold text-gray-900" id="pm-completed">0</div>
        </div>
        <div class="bg-base-200 rounded-lg p-4">
          <div class="text-sm text-gray-500">No-Shows / Absentees</div>
          <div class="text-2xl font-bold text-gray-900" id="pm-noshow">0</div>
        </div>
      </div>
    </div>
  </main>

  <script>
    const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    const parseDate = (s) => {
      try {
        const d = new Date(String(s || ''));
        return isNaN(d.getTime()) ? null : d;
      } catch (_) {
        return null;
      }
    };

    const normalizeStatusBucket = (status) => {
      const st = String(status || '').trim();
      if (st === 'Planned') return 'Planned';
      if (st === 'Approved') return 'Approved';
      if (st === 'Ongoing') return 'Ongoing';
      if (st === 'Completed') return 'Completed';
      if (st === 'Pending' || st === 'Under Review' || st === 'For Compliance') return 'Pending Approval';
      return 'Pending Approval';
    };

    const isCancelledOrPostponed = (status) => {
      const st = String(status || '').trim();
      return st === 'Cancelled' || st === 'Postponed';
    };

    const fetchPrograms = async () => {
      const url = new URL('trainingprogram.php', window.location.href);
      url.searchParams.set('action', 'list_programs');
      const res = await fetch(url.toString(), { credentials: 'same-origin' });
      return await res.json();
    };

    const initDashboard = async () => {
      let programs = [];
      try {
        const r = await fetchPrograms();
        if (r && r.success && Array.isArray(r.programs)) programs = r.programs;
      } catch (_) {
        programs = [];
      }

      const now = new Date();
      const curYear = now.getFullYear();
      const curMonth = now.getMonth();

      const total = programs.length;
      const totalThisYear = programs.filter((p) => {
        const d = parseDate(p.created_at || p.start_datetime);
        return d && d.getFullYear() === curYear;
      }).length;
      const totalThisMonth = programs.filter((p) => {
        const d = parseDate(p.created_at || p.start_datetime);
        return d && d.getFullYear() === curYear && d.getMonth() === curMonth;
      }).length;

      const upcoming = programs.filter((p) => {
        const sd = parseDate(p.start_datetime);
        if (!sd) return false;
        const st = String(p.status || '');
        if (st === 'Completed' || st === 'Cancelled') return false;
        return sd.getTime() > now.getTime();
      }).length;

      const ongoing = programs.filter((p) => String(p.status || '') === 'Ongoing').length;
      const completed = programs.filter((p) => String(p.status || '') === 'Completed').length;
      const cancelled = programs.filter((p) => isCancelledOrPostponed(p.status)).length;

      const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val);
      };

      setText('dash-total-trainings', total);
      setText('dash-total-year', totalThisYear);
      setText('dash-total-month', totalThisMonth);
      setText('dash-upcoming', upcoming);
      setText('dash-ongoing', ongoing);
      setText('dash-completed', completed);
      setText('dash-cancelled', cancelled);

      const buckets = {
        'Planned': 0,
        'Pending Approval': 0,
        'Approved': 0,
        'Ongoing': 0,
        'Completed': 0
      };

      programs.forEach((p) => {
        const b = normalizeStatusBucket(p.status);
        buckets[b] = (buckets[b] || 0) + 1;
      });

      const donutCtx = document.getElementById('statusDonut');
      if (donutCtx) {
        new Chart(donutCtx, {
          type: 'doughnut',
          data: {
            labels: Object.keys(buckets),
            datasets: [{
              data: Object.values(buckets),
              backgroundColor: ['#f59e0b','#60a5fa','#10b981','#22c55e','#a78bfa'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }

      const trainingsPerMonth = new Array(12).fill(0);
      const participantsPerMonth = new Array(12).fill(0);

      programs.forEach((p) => {
        const d = parseDate(p.start_datetime || p.created_at);
        if (!d) return;
        if (d.getFullYear() !== curYear) return;
        const m = d.getMonth();
        trainingsPerMonth[m] += 1;
        const pn = parseInt(String(p.participants_needed || '0'), 10);
        if (!isNaN(pn)) participantsPerMonth[m] += pn;
      });

      const barCtx = document.getElementById('monthlyBar');
      if (barCtx) {
        new Chart(barCtx, {
          type: 'bar',
          data: {
            labels: monthLabels,
            datasets: [
              { label: 'Trainings', data: trainingsPerMonth, backgroundColor: '#60a5fa' },
              { label: 'Participants', data: participantsPerMonth, backgroundColor: '#34d399' }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true }
            },
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }

      if (window.lucide) window.lucide.createIcons();
    };

    initDashboard();
  </script>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
