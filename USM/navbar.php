<header class="bg-base-100 shadow-sm z-10 border-b border-base-300 dark:border-gray-700" data-theme="light">
    <div class="px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm hover:bg-base-300  transition-all hover:scale-105">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
        </div>
       <div class="flex items-center gap-4">
         <!-- Time Display -->
         <div class="animate-fadeIn">
           <span id="philippineTime" class="font-medium max-md:text-sm"></span>
         </div>
         
          <!-- Notification Dropdown -->
<div class="dropdown dropdown-end">

  
  <!-- Button -->
  <button id="notification-button" tabindex="0" class="btn btn-ghost btn-circle btn-sm relative text-gray-700 hover:text-gray-900">
    <i data-lucide="bell" class="w-5 h-5"></i>
    <span id="notif-dot" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full hidden"></span>
  </button>
  
  <!-- Dropdown Content - Responsive -->
  <ul tabindex="0" class="dropdown-content menu mt-3 z-[9999] bg-[#001f54] rounded-lg shadow-xl overflow-hidden transform md:translate-x-0 sm:translate-x-1/2 sm:-translate-x-1/2">
    <!-- Header -->
    <li class="px-4 py-3 border-b  flex justify-between items-center sticky top-0 bg-[#001f54] backdrop-blur-sm z-10">
      <div class="flex items-center gap-2">
        <i data-lucide="bell" class="w-5 h-5 text-blue-300"></i>
        <span class="font-semibold text-white">Notifications</span>
      </div>
      <button class="text-blue-300 hover:text-white text-sm flex items-center gap-1">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
        <span>Clear All</span>
      </button>
    </li>
    
    <!-- Notification Items Container - Scrollable -->
    <div id="notif-items" class="max-h-96 overflow-y-auto">
      <li class="px-4 py-3 text-blue-200">Loading...</li>
    </div>
    
    <!-- Footer -->
    <li class="px-4 py-2 border-t  sticky bottom-0 bg-[#001f54] backdrop-blur-sm">
      <a href="/hr2/ESS/dashboard.php" class="text-center text-blue-300 hover:text-white text-sm flex items-center justify-center gap-1">
        <i data-lucide="list" class="w-4 h-4"></i>
        <span>View All Notifications</span>
      </a>
    </li>
  </ul>
</div>


          <!-- User Dropdown -->
          <div class="dropdown dropdown-end">
  <label tabindex="0" class="btn btn-ghost btn-circle avatar">
    <div class="w-8 rounded-full">
      <img src="" alt="User Avatar" />
    </div>
  </label>
  <ul tabindex="0" class="dropdown-content menu mt-1 z-[100] w-52 bg-[#001f54] rounded-box shadow-xl">
    <!-- User Profile Section -->
    <li class="p-3 border-b ">
      <div class="bg-blue-700/50 rounded-md shadow-md flex items-center gap-3">
        <div class="avatar">
          <div class="w-10 rounded-full">
            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="User Avatar" class="dark:brightness-90" />
          </div>
        </div>
        <div>
          <p class="font-medium text-white"></p>
          <p class="text-xs text-white"></p>
        </div>
      </div>
    </li>
    
    <!-- Menu Items -->
    <li>
      <a class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="user" class="w-4 h-4"></i>
        <span>Profile</span>
      </a>
    </li>
    <li>
      <a class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="settings" class="w-4 h-4"></i>
        <span>Settings</span>
      </a>
    </li>
    <li class="">
      <a href="/hr2/USM/logout.php" class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        <span>Sign out</span>
      </a>
    </li>
  </ul>
</div>
        </div>
      </div>
    </div>
  </header>


<style>
  @media (max-width: 767px) {
    .dropdown-content {
      left: 50% !important;
      transform: translateX(-50%) !important;
    }
  }

  .hr2-summary-card {
    background: linear-gradient(135deg, #001a44 0%, #002a66 60%, #001a44 100%) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 12px 28px rgba(0, 26, 68, 0.35) !important;
    color: rgba(255, 255, 255, 0.92) !important;
    position: relative;
    overflow: hidden;
    transition: transform 160ms ease, box-shadow 160ms ease;
  }

  .hr2-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 40px rgba(0, 26, 68, 0.45) !important;
  }

  .hr2-summary-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(1200px 500px at 10% 10%, rgba(247, 179, 43, 0.16), transparent 60%);
    pointer-events: none;
  }

  .hr2-summary-card .text-gray-900,
  .hr2-summary-card .text-gray-800,
  .hr2-summary-card .text-gray-700 {
    color: rgba(255, 255, 255, 0.96) !important;
  }

  .hr2-summary-card .text-gray-600,
  .hr2-summary-card .text-gray-500,
  .hr2-summary-card .text-gray-400 {
    color: rgba(234, 242, 255, 0.72) !important;
  }

  .hr2-summary-card .text-blue-600,
  .hr2-summary-card .text-yellow-600,
  .hr2-summary-card .text-green-600,
  .hr2-summary-card .text-purple-600,
  .hr2-summary-card .text-slate-600 {
    color: #f7b32b !important;
  }

  .hr2-summary-card .bg-base-200,
  .hr2-summary-card .bg-blue-100,
  .hr2-summary-card .bg-yellow-100,
  .hr2-summary-card .bg-green-100,
  .hr2-summary-card .bg-purple-100,
  .hr2-summary-card .bg-red-100,
  .hr2-summary-card .bg-slate-100 {
    background: rgba(255, 255, 255, 0.10) !important;
  }

  .hr2-summary-card .badge {
    background: rgba(247, 179, 43, 0.12) !important;
    border: 1px solid rgba(247, 179, 43, 0.55) !important;
    color: #f7b32b !important;
  }

  .hr2-summary-card .badge-outline,
  .hr2-summary-card .badge-ghost {
    background: rgba(247, 179, 43, 0.12) !important;
    border: 1px solid rgba(247, 179, 43, 0.55) !important;
    color: #f7b32b !important;
  }

  .hr2-summary-card.stat-card {
    border-left: none !important;
  }

  .hr2-primary-btn {
    background: linear-gradient(135deg, #001a44 0%, #002a66 60%, #001a44 100%) !important;
    border: 1px solid rgba(247, 179, 43, 0.65) !important;
    color: rgba(255, 255, 255, 0.95) !important;
  }

  .hr2-primary-btn:hover {
    background: linear-gradient(135deg, #001433 0%, #002357 60%, #001433 100%) !important;
    border-color: rgba(247, 179, 43, 0.85) !important;
  }

  .hr2-outline-btn {
    background: transparent !important;
    border: 1px solid rgba(247, 179, 43, 0.65) !important;
    color: #f7b32b !important;
  }

  .hr2-outline-btn:hover {
    background: rgba(247, 179, 43, 0.12) !important;
    border-color: rgba(247, 179, 43, 0.85) !important;
    color: #f7b32b !important;
  }
</style>

<div id="notif-toast" class="fixed bottom-6 right-6 z-[99999] hidden">
  <div class="bg-[#0b1220] border border-white/10 text-white rounded-xl shadow-xl px-4 py-3 w-80">
    <div class="flex items-start gap-3">
      <div class="mt-0.5 p-2 rounded-lg bg-blue-600/30">
        <i data-lucide="bell" class="w-5 h-5 text-blue-200"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-semibold">New Notification Received</div>
        <div id="notif-toast-text" class="text-sm text-blue-100 truncate">A new security alert has been triggered.</div>
      </div>
      <button type="button" id="notif-toast-close" class="text-blue-200 hover:text-white">×</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const itemsEl = document.getElementById('notif-items');
    const dotEl = document.getElementById('notif-dot');
    const bellBtnEl = document.getElementById('notification-button');
    const toastEl = document.getElementById('notif-toast');
    const toastTextEl = document.getElementById('notif-toast-text');
    const toastCloseEl = document.getElementById('notif-toast-close');
    if (!itemsEl || !dotEl) return;

    const employeeKey = <?php echo json_encode((string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? '')); ?>;
    const storageKey = 'hr2_notif_latest_key_' + (employeeKey || 'anon');
    let lastLatestKey = '';

    function relTime(dateStr) {
      const s = String(dateStr || '').trim();
      if (!s) return '';
      const d = new Date(s.replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return '';
      const diffMs = Date.now() - d.getTime();
      const diffMin = Math.floor(diffMs / 60000);
      if (diffMin < 1) return 'just now';
      if (diffMin < 60) return diffMin + ' min ago';
      const diffHr = Math.floor(diffMin / 60);
      if (diffHr < 24) return diffHr + ' hours ago';
      const diffDay = Math.floor(diffHr / 24);
      return diffDay + ' days ago';
    }

    function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function showToast(text) {
      if (!toastEl || !toastTextEl) return;
      toastTextEl.textContent = text || 'You have a new notification.';
      toastEl.classList.remove('hidden');
      window.setTimeout(() => {
        toastEl.classList.add('hidden');
      }, 5000);
    }

    if (toastCloseEl && toastEl) {
      toastCloseEl.addEventListener('click', () => toastEl.classList.add('hidden'));
    }

    async function loadNotifs() {
      try {
        const res = await fetch('/hr2/USM/notifications_feed.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data || !data.success) throw new Error('Failed');

        const latestKey = String(data.latest_key || '');
        lastLatestKey = latestKey;
        const prevKey = String(localStorage.getItem(storageKey) || '');
        const hasNew = latestKey && latestKey !== prevKey;

        const count = Number(data.count || 0);
        if (!prevKey && latestKey) {
          localStorage.setItem(storageKey, latestKey);
          dotEl.classList.add('hidden');
        } else {
          dotEl.classList.toggle('hidden', !(count > 0 && hasNew));
        }

        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
          itemsEl.innerHTML = '<li class="px-4 py-3 text-blue-200">No new notifications (last 24 hours).</li>';
        } else {
          itemsEl.innerHTML = items.map((n) => {
            const t = escapeHtml(n.type);
            const title = escapeHtml(n.title);
            const meta = escapeHtml(n.meta);
            const when = escapeHtml(relTime(n.date));
            const link = escapeHtml(n.link);
            return `
              <li class="px-4 py-3 border-b border-white/10 hover:bg-blue-700/30">
                <a href="${link}" class="block">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-blue-200">${t}</span>
                    <span class="text-xs text-blue-300">${when}</span>
                  </div>
                  <div class="text-sm font-semibold text-white mt-1">${title}</div>
                  ${meta ? `<div class="text-xs text-blue-200 mt-1">${meta}</div>` : ''}
                </a>
              </li>
            `;
          }).join('');
        }

        if (hasNew) {
          localStorage.setItem(storageKey, latestKey);
          if (items[0] && items[0].title) {
            showToast(String(items[0].title));
          } else {
            showToast('You have a new notification.');
          }
        }
      } catch (e) {
        itemsEl.innerHTML = '<li class="px-4 py-3 text-blue-200">Unable to load notifications.</li>';
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadNotifs();
      window.setInterval(loadNotifs, 60000);
    });

    if (bellBtnEl) {
      bellBtnEl.addEventListener('click', () => {
        if (lastLatestKey) {
          localStorage.setItem(storageKey, lastLatestKey);
        }
        dotEl.classList.add('hidden');
      });
    }
  })();
</script>