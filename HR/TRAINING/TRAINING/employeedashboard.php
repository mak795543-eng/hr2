<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Dashboard - Training Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function () {
      if (!window.Swal || window.__SWAL_DAISY_PATCHED__) return;
      window.__SWAL_DAISY_PATCHED__ = true;
      const orig = window.Swal.fire.bind(window.Swal);
      window.Swal.fire = function (opts) {
        const inOpts = opts || {};
        const inCustom = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
        const customClass = {
          popup: 'bg-base-100 text-base-content rounded-box',
          title: 'text-base-content',
          htmlContainer: 'text-base-content',
          actions: 'flex gap-2',
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-ghost',
          denyButton: 'btn btn-ghost',
          ...(inCustom || {})
        };
        return orig({
          returnFocus: false,
          buttonsStyling: false,
          ...inOpts,
          customClass
        });
      };
    })();
  </script>
  <style>
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .swal2-container { z-index: 2147483647 !important; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../USM/navbar.php'; ?>
    
  <main class="container mx-auto px-4 py-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">My Training Dashboard</h1>
        <p class="text-gray-600">Employee Name (EMP-0001)</p>
      </div>

    </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 fade-in">
        <div class="bg-white rounded-xl shadow-md p-5">
          <div class="text-sm text-gray-500">Total Trainings Assigned</div>
          <div class="text-2xl font-bold text-gray-900" id="card-assigned">12</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5">
          <div class="text-sm text-gray-500">Completion Percentage</div>
          <div class="text-2xl font-bold text-gray-900" id="card-completion">58%</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5">
          <div class="text-sm text-gray-500">Average Assessment Score</div>
          <div class="text-2xl font-bold text-gray-900" id="card-avgscore">87.50</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5">
          <div class="text-sm text-gray-500">Passed / Failed Trainings</div>
          <div class="text-2xl font-bold text-gray-900" id="card-passfail">9 / 3</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 fade-in">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="text-lg font-semibold text-gray-800">My Training Progress</div>
              <div class="text-sm text-gray-500">Trainings Completed vs Assigned</div>
            </div>
          </div>

          <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div class="bg-green-500 h-4" style="width: 58%"></div>
          </div>
          <div class="mt-2 text-sm text-gray-600">
            Completed: <span class="font-semibold">7</span> / Assigned: <span class="font-semibold">12</span>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
          <div class="text-lg font-semibold text-gray-800 mb-2">Progress Overview</div>
          <div class="h-56">
            <canvas id="progressDonut"></canvas>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6 mb-8 fade-in">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-lg font-semibold text-gray-800">Training Schedule</div>
            <div class="text-sm text-gray-500">Your upcoming and ongoing trainings</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div class="border rounded-xl p-4 bg-base-100 training-card">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-gray-900">Fire Safety & Evacuation Drill</div>
                <div class="text-xs text-gray-500 mt-1">Assigned: 2026-01-12 09:00</div>
              </div>
              <div class="badge badge-info">Upcoming</div>
            </div>

            <div class="mt-3 space-y-1 text-sm text-gray-700">
              <div><span class="font-semibold">Date & Time:</span> 2026-01-25 13:00 - 2026-01-25 15:00</div>
              <div><span class="font-semibold">Mode:</span> Onsite</div>
              <div><span class="font-semibold">Location / Platform:</span> Conference Room A</div>
              <div><span class="font-semibold">Trainer / Mentor:</span> Juan Dela Cruz</div>
              <div><span class="font-semibold">Acknowledged:</span> <span data-ack-text>No</span></div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn btn-outline btn-sm"
                data-action="view"
                data-title="Fire Safety & Evacuation Drill"
                data-start="2026-01-25 13:00"
                data-end="2026-01-25 15:00"
                data-mode="Onsite"
                data-location="Conference Room A"
                data-mentor="Juan Dela Cruz"
                data-description="Mandatory safety training covering evacuation routes, assembly points, and emergency roles."
                data-status="Upcoming"
              >
                View Details
              </button>

              <a class="btn btn-primary btn-sm" href="#" onclick="return false;">Join Training</a>

              <button type="button" class="btn btn-success btn-sm" data-action="ack">Acknowledge Attendance</button>
            </div>
          </div>

          <div class="border rounded-xl p-4 bg-base-100 training-card">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-gray-900">Customer Service Excellence</div>
                <div class="text-xs text-gray-500 mt-1">Assigned: 2026-01-05 10:00</div>
              </div>
              <div class="badge badge-success">Ongoing</div>
            </div>

            <div class="mt-3 space-y-1 text-sm text-gray-700">
              <div><span class="font-semibold">Date & Time:</span> 2026-01-19 09:00 - 2026-01-19 17:00</div>
              <div><span class="font-semibold">Mode:</span> Online</div>
              <div><span class="font-semibold">Location / Platform:</span> https://meet.example.com/training</div>
              <div><span class="font-semibold">Trainer / Mentor:</span> Maria Santos</div>
              <div><span class="font-semibold">Acknowledged:</span> <span data-ack-text>No</span></div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn btn-outline btn-sm"
                data-action="view"
                data-title="Customer Service Excellence"
                data-start="2026-01-19 09:00"
                data-end="2026-01-19 17:00"
                data-mode="Online"
                data-location="https://meet.example.com/training"
                data-mentor="Maria Santos"
                data-description="Interactive training on communication, handling complaints, and service recovery."
                data-status="Ongoing"
              >
                View Details
              </button>

              <a class="btn btn-primary btn-sm" href="https://meet.example.com/training" target="_blank" rel="noreferrer">Join Training</a>

              <button type="button" class="btn btn-success btn-sm" data-action="ack">Acknowledge Attendance</button>
            </div>
          </div>

          <div class="border rounded-xl p-4 bg-base-100 training-card">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-gray-900">Data Privacy & Compliance</div>
                <div class="text-xs text-gray-500 mt-1">Assigned: 2025-12-20 08:00</div>
              </div>
              <div class="badge">Upcoming</div>
            </div>

            <div class="mt-3 space-y-1 text-sm text-gray-700">
              <div><span class="font-semibold">Date & Time:</span> 2026-02-02 09:00 - 2026-02-02 12:00</div>
              <div><span class="font-semibold">Mode:</span> Onsite</div>
              <div><span class="font-semibold">Location / Platform:</span> Training Hall</div>
              <div><span class="font-semibold">Trainer / Mentor:</span> Jose Reyes</div>
              <div><span class="font-semibold">Acknowledged:</span> <span data-ack-text>No</span></div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn btn-outline btn-sm"
                data-action="view"
                data-title="Data Privacy & Compliance"
                data-start="2026-02-02 09:00"
                data-end="2026-02-02 12:00"
                data-mode="Onsite"
                data-location="Training Hall"
                data-mentor="Jose Reyes"
                data-description="Compliance refresher training; covers policy updates and mandatory reminders."
                data-status="Upcoming"
              >
                View Details
              </button>

              <a class="btn btn-primary btn-sm" href="#" onclick="return false;">Join Training</a>

              <button type="button" class="btn btn-success btn-sm" data-action="ack">Acknowledge Attendance</button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6 fade-in">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-lg font-semibold text-gray-800">Mandatory & Compliance Trainings</div>
            <div class="text-sm text-gray-500">Due Soon / Overdue tracking (basic)</div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>Training</th>
                <th>Status</th>
                <th>Completion date</th>
                <th>Expiry date</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="font-semibold text-gray-900">Data Privacy & Compliance</div>
                  <div class="text-xs text-gray-500">2026-02-02 09:00</div>
                </td>
                <td>
                  <div class="badge badge-success">Completed</div>
                </td>
                <td>2026-02-02</td>
                <td>2027-02-02</td>
              </tr>
              <tr>
                <td>
                  <div class="font-semibold text-gray-900">Workplace Safety Orientation</div>
                  <div class="text-xs text-gray-500">2026-01-23 10:00</div>
                </td>
                <td>
                  <div class="badge badge-warning">Due Soon</div>
                </td>
                <td>-</td>
                <td>-</td>
              </tr>
              <tr>
                <td>
                  <div class="font-semibold text-gray-900">Anti-Harassment Policy Refresher</div>
                  <div class="text-xs text-gray-500">2026-01-10 14:00</div>
                </td>
                <td>
                  <div class="badge badge-error">Overdue</div>
                </td>
                <td>-</td>
                <td>-</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <dialog id="details-modal" class="modal">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-xl" id="dm-title">Training Details</h3>
          <div class="mt-4 space-y-2 text-sm">
            <div><span class="font-semibold">Status:</span> <span id="dm-status"></span></div>
            <div><span class="font-semibold">Date & Time:</span> <span id="dm-datetime"></span></div>
            <div><span class="font-semibold">Mode:</span> <span id="dm-mode"></span></div>
            <div><span class="font-semibold">Location / Platform:</span> <span id="dm-location"></span></div>
            <div><span class="font-semibold">Trainer / Mentor:</span> <span id="dm-mentor"></span></div>
            <div class="pt-2"><span class="font-semibold">Description:</span></div>
            <div class="whitespace-pre-line text-gray-700" id="dm-description"></div>
          </div>
          <div class="modal-action">
            <form method="dialog">
              <button class="btn">Close</button>
            </form>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button>close</button>
        </form>
      </dialog>

  </main>

  <script>
    const completedCount = 7;
    const totalAssigned = 12;

    const initCharts = () => {
      const ctx = document.getElementById('progressDonut');
      if (!ctx) return;

      const remaining = Math.max(0, totalAssigned - completedCount);
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Completed', 'Remaining'],
          datasets: [{
            data: [completedCount, remaining],
            backgroundColor: ['#22c55e', '#e5e7eb'],
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
    };

    const showDetailsModal = (btn) => {
      const modal = document.getElementById('details-modal');
      if (!modal) return;

      const title = btn.getAttribute('data-title') || '';
      const start = btn.getAttribute('data-start') || '';
      const end = btn.getAttribute('data-end') || '';
      const mode = btn.getAttribute('data-mode') || '';
      const location = btn.getAttribute('data-location') || '';
      const mentor = btn.getAttribute('data-mentor') || '';
      const description = btn.getAttribute('data-description') || '';
      const status = btn.getAttribute('data-status') || '';

      const set = (id, v) => {
        const el = document.getElementById(id);
        if (el) el.textContent = v;
      };

      set('dm-title', title || 'Training Details');
      set('dm-datetime', `${start} - ${end}`);
      set('dm-mode', mode);
      set('dm-location', location);
      set('dm-mentor', mentor);
      set('dm-description', description);
      set('dm-status', status);

      modal.showModal();
    };

    const ackAttendance = async (btn) => {
      const res = await Swal.fire({
        icon: 'question',
        title: 'Acknowledge attendance?',
        text: 'This will mark your attendance as acknowledged for this training.',
        showCancelButton: true,
        confirmButtonText: 'Acknowledge',
        cancelButtonText: 'Cancel'
      });

      if (!res.isConfirmed) return;

      btn.disabled = true;
      btn.classList.add('btn-disabled');

      const card = btn.closest('.training-card');
      if (card) {
        const ackEl = card.querySelector('[data-ack-text]');
        if (ackEl) ackEl.textContent = 'Yes';
      }

      await Swal.fire({ icon: 'success', title: 'Acknowledged', timer: 1200, showConfirmButton: false });
    };

    document.addEventListener('click', (e) => {
      const btn = e.target && e.target.closest ? e.target.closest('[data-action]') : null;
      if (!btn) return;

      const action = btn.getAttribute('data-action');
      if (action === 'view') {
        showDetailsModal(btn);
      }
      if (action === 'ack') {
        ackAttendance(btn);
      }
    });

    if (window.lucide) window.lucide.createIcons();
    initCharts();
  </script>
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>
