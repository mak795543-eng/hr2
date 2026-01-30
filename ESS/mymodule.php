<?php
session_start();

require_once __DIR__ . '/../LEARNING/db.php';

$conn = usm_db_connect('hr2_learning_db');
if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed');
}
$conn->set_charset('utf8mb4');

$role = trim((string)($_SESSION['role'] ?? ''));
$roleLower = strtolower($role);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['module_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $moduleId = (int)($_GET['module_id'] ?? 0);
    if ($moduleId <= 0 || $roleLower === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT * FROM learning_modules\n         WHERE id = ? AND status = 'posted'\n           AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)\n         LIMIT 1"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
        exit;
    }
    $stmt->bind_param('iss', $moduleId, $roleLower, $roleLower);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($row)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit;
    }

    echo json_encode(['success' => true, 'module' => $row]);
    exit;
}

$modules = [];
if ($roleLower !== '') {
    $stmt = $conn->prepare(
        "SELECT id, title, topic, department, roles, status, created_at\n         FROM learning_modules\n         WHERE status = 'posted'\n           AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)\n         ORDER BY created_at DESC"
    );
    if ($stmt) {
        $stmt->bind_param('ss', $roleLower, $roleLower);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $modules[] = $r;
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Modules</title>
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
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">My Modules</h1>
              <p class="text-sm text-gray-500">Modules posted for your role: <span class="font-semibold"><?php echo htmlspecialchars($role !== '' ? $role : 'Unknown'); ?></span></p>
            </div>
          </div>

          <?php if ($roleLower === ''): ?>
            <div class="mt-6 alert alert-warning">
              <span>Your session role is missing. Please log in again.</span>
            </div>
          <?php elseif (count($modules) === 0): ?>
            <div class="mt-6 card bg-base-100 border border-base-200 shadow-sm">
              <div class="card-body">
                <div class="flex items-center gap-2 text-gray-700">
                  <i data-lucide="book-open" class="w-5 h-5"></i>
                  <h2 class="font-semibold">No modules yet</h2>
                </div>
                <p class="text-sm text-gray-500 mt-2">There are no posted learning modules for your role right now.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <?php foreach ($modules as $m): ?>
                <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
                  <div class="card-body">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h2 class="font-semibold text-gray-900 leading-tight"><?php echo htmlspecialchars((string)($m['title'] ?? 'Untitled')); ?></h2>
                        <p class="text-sm text-gray-500 mt-1">Topic: <?php echo htmlspecialchars((string)($m['topic'] ?? '')); ?></p>
                      </div>
                      <span class="badge badge-info badge-outline">Posted</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                      <span class="badge badge-outline"><?php echo htmlspecialchars((string)($m['department'] ?? '')); ?></span>
                      <span class="badge badge-outline"><?php echo htmlspecialchars((string)($m['roles'] ?? '')); ?></span>
                    </div>

                    <p class="text-xs text-gray-500 mt-3">Date Posted: <?php echo htmlspecialchars(date('Y-m-d', strtotime((string)($m['created_at'] ?? 'now')))); ?></p>

                    <div class="mt-4 flex justify-end">
                      <button class="btn btn-sm btn-outline" onclick="viewModule(<?php echo (int)($m['id'] ?? 0); ?>)">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span class="ml-2">View</span>
                      </button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <dialog id="module_modal" class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Close">✕</button>
      </form>

      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="font-bold text-lg" id="modal_title">Module</h3>
          <p class="text-sm text-gray-500" id="modal_meta"></p>
        </div>
        <span class="badge badge-info badge-outline">Posted</span>
      </div>

      <div class="mt-4 border rounded-lg bg-white p-4 overflow-auto" style="max-height: 60vh;" id="modal_content"></div>

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

  <script>
    function viewModule(moduleId) {
      const modal = document.getElementById('module_modal');
      const titleEl = document.getElementById('modal_title');
      const metaEl = document.getElementById('modal_meta');
      const contentEl = document.getElementById('modal_content');

      titleEl.textContent = 'Loading...';
      metaEl.textContent = '';
      contentEl.innerHTML = '<div class="flex items-center gap-2 text-gray-500"><span class="loading loading-spinner loading-sm"></span><span>Loading module...</span></div>';

      modal.showModal();

      fetch(`mymodule.php?module_id=${moduleId}`)
        .then(r => r.json())
        .then(data => {
          if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Failed to load');
          }
          const m = data.module;
          titleEl.textContent = m.title || 'Untitled';
          metaEl.textContent = `Topic: ${m.topic || ''} | Department: ${m.department || ''} | Role: ${m.roles || ''}`;
          contentEl.innerHTML = m.content || '';
        })
        .catch(err => {
          titleEl.textContent = 'Error';
          metaEl.textContent = '';
          contentEl.textContent = err.message || 'Failed to load module.';
        });
    }

    lucide.createIcons();
  </script>
</body>
</html>
