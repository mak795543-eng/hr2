<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$records = [];

if ($conn && $employeeId) {
    $stmt = mysqli_prepare($conn, 'SELECT title, issued_by, achievement_date FROM employee_achievements WHERE employee_id = ? ORDER BY achievement_date DESC');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $records[] = [
                    'title' => (string)($r['title'] ?? ''),
                    'category' => 'Achievement',
                    'issued_by' => (string)($r['issued_by'] ?? ''),
                    'date_awarded' => (string)($r['achievement_date'] ?? ''),
                    'status' => 'Verified',
                ];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

function categoryBadgeClass($category) {
    $c = strtolower(trim((string)$category));
    return match ($c) {
        'award' => 'badge-warning',
        'certificate' => 'badge-info',
        'achievement' => 'badge-success',
        'commendation' => 'badge-ghost',
        default => 'badge-ghost',
    };
}

function statusBadgeClass($status) {
    $s = strtolower(trim((string)$status));
    return match ($s) {
        'verified' => 'badge-success',
        'pending' => 'badge-warning',
        'rejected' => 'badge-error',
        default => 'badge-ghost',
    };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Social Recognition</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-6xl mx-auto">
          <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-base-200">
                  <i data-lucide="badge-check" class="w-5 h-5"></i>
                </div>
                <div>
                  <h1 class="text-xl md:text-2xl font-bold text-gray-800">My Recognition Records</h1>
                  <p class="text-sm text-gray-500">Track recognitions issued to you and download certificates.</p>
                </div>
              </div>

              <div class="mt-6 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
                <label class="input input-bordered flex items-center gap-2 w-full lg:max-w-sm">
                  <i data-lucide="search" class="w-4 h-4 text-gray-500"></i>
                  <input id="srSearch" type="text" class="grow" placeholder="Search recognitions..." />
                </label>

                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                  <select id="srCategory" class="select select-bordered w-full sm:w-56">
                    <option value="all">All Categories</option>
                    <option value="Award">Award</option>
                    <option value="Certificate">Certificate</option>
                    <option value="Achievement">Achievement</option>
                    <option value="Commendation">Commendation</option>
                  </select>

                  <select id="srSort" class="select select-bordered w-full sm:w-56">
                    <option value="newest" selected>Sort by: Newest First</option>
                    <option value="oldest">Sort by: Oldest First</option>
                    <option value="title_az">Sort by: Title (A-Z)</option>
                    <option value="title_za">Sort by: Title (Z-A)</option>
                  </select>
                </div>
              </div>

              <div class="mt-6 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Recognition Title</th>
                      <th>Category</th>
                      <th>Issued By</th>
                      <th>Date Awarded</th>
                      <th>Status</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="srTableBody">
                    <?php foreach ($records as $r): ?>
                      <tr data-title="<?php echo htmlspecialchars(strtolower($r['title'])); ?>" data-category="<?php echo htmlspecialchars($r['category']); ?>" data-date="<?php echo htmlspecialchars($r['date_awarded']); ?>">
                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($r['title']); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo categoryBadgeClass($r['category']); ?>"><?php echo htmlspecialchars($r['category']); ?></span>
                        </td>
                        <td class="text-gray-700"><?php echo htmlspecialchars($r['issued_by']); ?></td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['date_awarded']))); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo statusBadgeClass($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                        </td>
                        <td class="text-center">
                          <div class="flex items-center justify-center gap-2">
                            <button class="btn btn-ghost btn-xs" type="button" aria-label="View">
                              <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="btn btn-ghost btn-xs" type="button" aria-label="Download">
                              <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                            <button class="btn btn-ghost btn-xs" type="button" aria-label="Share">
                              <i data-lucide="share-2" class="w-4 h-4"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="mt-6 flex justify-center">
                <button id="srLoadMore" class="btn btn-outline btn-sm" type="button">Load More Records</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    function srApplyFilters() {
      const q = (document.getElementById('srSearch').value || '').trim().toLowerCase();
      const cat = document.getElementById('srCategory').value;

      const rows = Array.from(document.querySelectorAll('#srTableBody tr'));
      rows.forEach((row) => {
        const title = row.getAttribute('data-title') || '';
        const rowCat = row.getAttribute('data-category') || '';
        const matchQuery = q === '' || title.includes(q);
        const matchCat = cat === 'all' || rowCat === cat;
        row.style.display = (matchQuery && matchCat) ? '' : 'none';
      });
    }

    function srSortRows() {
      const sort = document.getElementById('srSort').value;
      const tbody = document.getElementById('srTableBody');
      const rows = Array.from(tbody.querySelectorAll('tr'));

      rows.sort((a, b) => {
        const da = a.getAttribute('data-date') || '';
        const db = b.getAttribute('data-date') || '';
        const ta = (a.getAttribute('data-title') || '').toLowerCase();
        const tb = (b.getAttribute('data-title') || '').toLowerCase();

        if (sort === 'oldest') return da.localeCompare(db);
        if (sort === 'newest') return db.localeCompare(da);
        if (sort === 'title_az') return ta.localeCompare(tb);
        if (sort === 'title_za') return tb.localeCompare(ta);
        return 0;
      });

      rows.forEach(r => tbody.appendChild(r));
      srApplyFilters();
    }

    document.addEventListener('DOMContentLoaded', function () {
      lucide.createIcons();

      document.getElementById('srSearch').addEventListener('input', srApplyFilters);
      document.getElementById('srCategory').addEventListener('change', srApplyFilters);
      document.getElementById('srSort').addEventListener('change', srSortRows);

      document.getElementById('srLoadMore').addEventListener('click', function () {
        this.classList.add('btn-disabled');
      });

      srSortRows();
    });
  </script>
</body>
</html>
