<?php

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
require('../../partials/header.php');
?>

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
       
      </div>
      <div class="flex gap-2">
       
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in">
      <div class="hr2-summary-card rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">My Trainings</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-my-trainings">0</div>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <i data-lucide="graduation-cap" class="h-6 w-6 text-blue-600"></i>
          </div>
        </div>
      </div>

      <div class="hr2-summary-card rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Completed</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-completed">0</div>
          </div>
          <div class="p-3 bg-purple-100 rounded-full">
            <i data-lucide="check-circle" class="h-6 w-6 text-purple-600"></i>
          </div>
        </div>
      </div>

      <div class="hr2-summary-card rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Due Soon</div>
            <div class="text-2xl font-bold text-gray-900" id="dash-due-soon">0</div>
          </div>
          <div class="p-3 bg-yellow-100 rounded-full">
            <i data-lucide="calendar" class="h-6 w-6 text-yellow-600"></i>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    const parseDate = (s) => {
      try {
        const d = new Date(String(s || ''));
        return isNaN(d.getTime()) ? null : d;
      } catch (_) {
        return null;
      }
    };

    const isCancelledOrPostponed = (status) => {
      const st = String(status || '').trim();
      return st === 'Cancelled' || st === 'Postponed';
    };

    const fetchPrograms = async () => {
      const url = new URL('trainingprogram.php', window.location.href);
      url.searchParams.set('action', 'list_all_programs');
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

      const total = programs.length;
      const completed = programs.filter((p) => String(p.status || '') === 'Completed').length;

      const dueSoon = programs.filter((p) => {
        const sd = parseDate(p.start_datetime);
        if (!sd) return false;
        const st = String(p.status || '');
        if (st === 'Completed' || st === 'Cancelled' || isCancelledOrPostponed(st)) return false;
        const diffDays = (sd.getTime() - now.getTime()) / (1000 * 60 * 60 * 24);
        return diffDays >= 0 && diffDays <= 7;
      }).length;

      const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val);
      };

      setText('dash-my-trainings', total);
      setText('dash-completed', completed);
      setText('dash-due-soon', dueSoon);

      if (window.lucide) window.lucide.createIcons();
    };

    initDashboard();
  </script>
 <?php require('../../partials/footer.php') ?>
