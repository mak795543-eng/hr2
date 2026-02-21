<?php

require_once __DIR__ . '/../LEARNING/db.php';

$conn = usm_db_connect('hr2_learning_db');
if ($conn->connect_error) {
  http_response_code(500);
  die('Database connection failed');
}
$conn->set_charset('utf8mb4');

$role = trim((string)($_SESSION['role'] ?? ''));
$roleLower = strtolower($role);
$viewerId = trim((string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? ''));
$viewerKey = $viewerId !== '' ? $viewerId : ($roleLower !== '' ? $roleLower : 'guest');

function role_can_delete_modules(string $role): bool
{
  $roleKey = strtolower(trim($role));
  if ($roleKey === '') {
    return false;
  }

  $permissions = require __DIR__ . '/../USM/role_permissions.php';
  if (!is_array($permissions)) {
    return false;
  }

  $normalized = [];
  foreach ($permissions as $k => $v) {
    $normalized[strtolower(trim((string)$k))] = is_array($v) ? $v : [];
  }

  $perms = $normalized[$roleKey] ?? [];
  return in_array('learning_management', $perms, true) || in_array('training_management', $perms, true);
}

$canDeleteModules = role_can_delete_modules($role);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_module'])) {
  $moduleId = (int)($_POST['module_id'] ?? 0);

  if (!$canDeleteModules) {
    http_response_code(403);
    $_SESSION['module_delete_error'] = 'Not allowed.';
    header('Location: mymodule.php');
    exit;
  }

  if ($moduleId <= 0) {
    http_response_code(400);
    $_SESSION['module_delete_error'] = 'Invalid module.';
    header('Location: mymodule.php');
    exit;
  }

  $stmt = $conn->prepare("DELETE FROM learning_modules WHERE id = ? AND status = 'posted' LIMIT 1");
  if (!$stmt) {
    http_response_code(500);
    $_SESSION['module_delete_error'] = 'Delete failed.';
    header('Location: mymodule.php');
    exit;
  }

  $stmt->bind_param('i', $moduleId);
  $ok = $stmt->execute();
  $affected = $stmt->affected_rows;
  $stmt->close();

  if ($ok && $affected > 0) {
    $_SESSION['module_delete_success'] = 'Module deleted.';
  } else {
    $_SESSION['module_delete_error'] = 'Module not found or could not be deleted.';
  }

  header('Location: mymodule.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download']) && isset($_GET['module_id'])) {
  $moduleId = (int)($_GET['module_id'] ?? 0);
  if ($moduleId <= 0 || $roleLower === '') {
    http_response_code(400);
    echo 'Invalid request';
    exit;
  }

  $stmt = $conn->prepare(
    "SELECT id, title, content, topic, department, roles, created_at\n         FROM learning_modules\n         WHERE id = ? AND status = 'posted'\n           AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)\n         LIMIT 1"
  );
  if (!$stmt) {
    http_response_code(500);
    echo 'Query prepare failed';
    exit;
  }

  $stmt->bind_param('iss', $moduleId, $roleLower, $roleLower);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!is_array($row)) {
    http_response_code(404);
    echo 'Module not found';
    exit;
  }

  $title = (string)($row['title'] ?? 'module');
  $safeName = preg_replace('/[^A-Za-z0-9 _\-]/', '', $title);
  $safeName = trim((string)$safeName);
  if ($safeName === '') {
    $safeName = 'module';
  }
  $safeName = str_replace(' ', '_', $safeName);

  $html = "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</title></head><body>";
  $html .= (string)($row['content'] ?? '');
  $html .= "</body></html>";

  header('Content-Type: text/html; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $safeName . '.html"');
  header('X-Content-Type-Options: nosniff');
  echo $html;
  exit;
}

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

$seedSousChef = function () use ($conn, $roleLower) {
  if ($roleLower !== 'sous chef') return;

  $sampleTitleMap = [
    'capstone' => [
      'title' => 'Kitchen Food Safety & HACCP Basics',
      'topic' => 'Food safety, HACCP principles, cross-contamination prevention, temperature control, and sanitation.',
      'content' => '<div class="prose max-w-none"><h2>Kitchen Food Safety &amp; HACCP Basics</h2><p>This module covers the daily safety standards a Sous Chef must enforce to protect guests, team members, and the brand.</p><h3>Learning outcomes</h3><ul><li>Apply time and temperature controls for receiving, storage, prep, cooking, and holding.</li><li>Prevent cross-contamination through zoning, labeling, and proper handling.</li><li>Use HACCP thinking: identify hazards, control points, and corrective actions.</li></ul><h3>Key checkpoints</h3><ul><li>Receiving: check supplier condition, packaging integrity, and delivery temperatures.</li><li>Storage: FIFO/FEFO, label/date, separate raw and ready-to-eat foods.</li><li>Prep: sanitize stations, change gloves, separate boards/knives per product.</li><li>Cooking/holding: verify with thermometer and record critical temps.</li></ul><h3>Daily checklist</h3><ul><li>Sanitation buckets labeled and at correct concentration.</li><li>Walk-in organized with clear raw-to-ready separation.</li><li>Cooling procedures followed (shallow pans, uncovered, rapid chill).</li></ul></div>',
    ],
    'para sa mga shep' => [
      'title' => 'Knife Skills & Safe Prep Standards',
      'topic' => 'Knife safety, grip, sharpening basics, standard cuts, and prep station discipline.',
      'content' => '<div class="prose max-w-none"><h2>Knife Skills &amp; Safe Prep Standards</h2><p>Build speed and consistency without sacrificing safety. Focus on the standards a Sous Chef should coach every day.</p><h3>Learning outcomes</h3><ul><li>Demonstrate safe handling, carrying, and storage of knives.</li><li>Maintain knives: honing vs. sharpening, cleaning and storage.</li><li>Apply standard cuts to improve consistency and portion control.</li></ul><h3>Core techniques</h3><ul><li>Grip: pinch grip for control; stable claw hand for guiding.</li><li>Station setup: dry towel under board; clear landing zone for product.</li><li>Hygiene: sanitize between allergens/raw proteins and ready-to-eat foods.</li></ul><h3>Standards to enforce</h3><ul><li>Use the right knife for the task; no dull blades in service.</li><li>Keep blades off sinks; wash immediately and store safely.</li><li>Label prepped items with name, date, time, and initials.</li></ul></div>',
    ],
    'fefef' => [
      'title' => 'Kitchen Inventory, FIFO & Waste Control',
      'topic' => 'Par levels, FIFO, spoilage prevention, stock rotation, and waste tracking.',
      'content' => '<div class="prose max-w-none"><h2>Kitchen Inventory, FIFO &amp; Waste Control</h2><p>Inventory discipline protects food cost and prevents service issues. This module focuses on the day-to-day controls a Sous Chef can own.</p><h3>Learning outcomes</h3><ul><li>Implement FIFO/FEFO rotation and labeling standards.</li><li>Track waste and identify recurring root causes.</li><li>Maintain par levels for key items to avoid over/under-ordering.</li></ul><h3>Practical workflow</h3><ul><li>Daily: check high-risk items, sauces, proteins, and dairy; rotate and date.</li><li>Weekly: spot counts for top 20 items; reconcile variances.</li><li>Service: communicate low stock early and adjust prep priorities.</li></ul><h3>Waste log categories</h3><ul><li>Trim/overproduction</li><li>Spillage/handling error</li><li>Expired/spoilage</li><li>Returned/quality issue</li></ul></div>',
    ],
  ];

  $stmt = $conn->prepare(
    "SELECT id, title
     FROM learning_modules
     WHERE status = 'posted'
       AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)"
  );
  if (!$stmt) return;
  $stmt->bind_param('ss', $roleLower, $roleLower);
  $stmt->execute();
  $res = $stmt->get_result();
  $existing = [];
  if ($res) {
    while ($r = $res->fetch_assoc()) {
      $existing[] = $r;
    }
  }
  $stmt->close();

  $normalize = static fn($v) => strtolower(trim(preg_replace('/\s+/', ' ', (string)$v)));
  $existingByTitle = [];
  foreach ($existing as $r) {
    $existingByTitle[$normalize($r['title'] ?? '')] = (int)($r['id'] ?? 0);
  }

  $didUpdate = false;
  $stmtUp = $conn->prepare("UPDATE learning_modules SET title = ?, topic = ?, department = 'kitchen', roles = 'Sous Chef', content = ?, status = 'posted' WHERE id = ? LIMIT 1");
  if ($stmtUp) {
    foreach ($sampleTitleMap as $oldTitle => $new) {
      $id = $existingByTitle[$normalize($oldTitle)] ?? 0;
      if ($id <= 0) continue;
      $t = (string)$new['title'];
      $tp = (string)$new['topic'];
      $c = (string)$new['content'];
      $stmtUp->bind_param('sssi', $t, $tp, $c, $id);
      $stmtUp->execute();
      if ($stmtUp->affected_rows > 0) $didUpdate = true;
    }
    $stmtUp->close();
  }

  if (count($existing) > 0) {
    if ($didUpdate) return;
    $titles = array_map(static fn($r) => strtolower(trim((string)($r['title'] ?? ''))), $existing);
    $looksLikeSample = false;
    foreach ($titles as $t) {
      if ($t === '' || preg_match('/^[a-z]{3,10}$/', $t)) {
        $looksLikeSample = true;
        break;
      }
    }
    if (!$looksLikeSample) return;
  }

  if (count($existing) >= 3) return;

  $defaults = array_values($sampleTitleMap);
  $stmtIns = $conn->prepare("INSERT INTO learning_modules (title, topic, department, roles, content, status) VALUES (?, ?, 'kitchen', 'Sous Chef', ?, 'posted')");
  if (!$stmtIns) return;
  $need = max(0, 3 - count($existing));
  for ($i = 0; $i < $need; $i++) {
    $d = $defaults[$i] ?? null;
    if (!$d) break;
    $t = (string)$d['title'];
    $tp = (string)$d['topic'];
    $c = (string)$d['content'];
    $stmtIns->bind_param('sss', $t, $tp, $c);
    $stmtIns->execute();
  }
  $stmtIns->close();
};

if ($conn && $roleLower !== '') {
  $seedSousChef();
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
require('../partials/header.php');
$conn->close();
?>

<style>
  .swal2-container {
    position: fixed !important;
    inset: 0 !important;
    z-index: 2147483647 !important;
    pointer-events: auto !important;
  }

  .swal2-popup {
    z-index: 2147483647 !important;
  }

  .swal2-actions {
    display: flex !important;
    flex-direction: row !important;
    gap: 0.5rem !important;
    justify-content: center !important;
    visibility: visible !important;
    opacity: 1 !important;
  }

  .swal2-confirm,
  .swal2-cancel,
  .swal2-deny {
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
</style>
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

          <?php if (isset($_SESSION['module_delete_success'])): ?>
            <div class="mt-6 alert alert-success">
              <span><?php echo htmlspecialchars((string)$_SESSION['module_delete_success']); ?></span>
            </div>
            <?php unset($_SESSION['module_delete_success']); ?>
          <?php elseif (isset($_SESSION['module_delete_error'])): ?>
            <div class="mt-6 alert alert-error">
              <span><?php echo htmlspecialchars((string)$_SESSION['module_delete_error']); ?></span>
            </div>
            <?php unset($_SESSION['module_delete_error']); ?>
          <?php endif; ?>

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
                <div
                  class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow"
                  data-module-id="<?php echo (int)($m['id'] ?? 0); ?>"
                  data-module-title="<?php echo htmlspecialchars((string)($m['title'] ?? 'Untitled'), ENT_QUOTES); ?>">
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

                    <div class="mt-4 grid grid-cols-3 gap-2">
                      <button class="btn btn-xs btn-outline w-full whitespace-nowrap flex-nowrap justify-center gap-1 px-2" onclick="viewModule(<?php echo (int)($m['id'] ?? 0); ?>)">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        View
                      </button>
                      <a class="btn btn-xs btn-outline w-full whitespace-nowrap flex-nowrap justify-center gap-1 px-2" href="mymodule.php?download=1&module_id=<?php echo (int)($m['id'] ?? 0); ?>">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        Download
                      </a>
                      <?php if ($canDeleteModules): ?>
                        <form method="POST" action="mymodule.php" class="contents">
                          <input type="hidden" name="module_id" value="<?php echo (int)($m['id'] ?? 0); ?>">
                          <button
                            type="submit"
                            name="delete_module"
                            class="btn btn-xs btn-outline btn-error w-full whitespace-nowrap flex-nowrap justify-center gap-1 px-2"
                            data-action="delete-module"
                            data-mode="server"
                            data-id="<?php echo (int)($m['id'] ?? 0); ?>">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            Delete
                          </button>
                        </form>
                      <?php else: ?>
                        <button
                          type="button"
                          class="btn btn-xs btn-outline btn-error w-full whitespace-nowrap flex-nowrap justify-center gap-1 px-2"
                          data-action="delete-module"
                          data-mode="local"
                          data-id="<?php echo (int)($m['id'] ?? 0); ?>">
                          <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                          Delete
                        </button>
                      <?php endif; ?>
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
    const hiddenStorageKey = 'ess_mymodule_hidden_' + <?php echo json_encode($viewerKey); ?>;

    function readHiddenIds() {
      try {
        const raw = localStorage.getItem(hiddenStorageKey);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }

    function writeHiddenIds(ids) {
      const uniq = Array.from(new Set((ids || []).map(v => parseInt(v, 10)).filter(v => Number.isFinite(v) && v > 0)));
      localStorage.setItem(hiddenStorageKey, JSON.stringify(uniq));
    }

    function removeCard(moduleId) {
      const card = document.querySelector(`[data-module-id="${moduleId}"]`);
      if (card) card.remove();
    }

    function deleteModuleLocal(moduleId) {
      const id = parseInt(moduleId, 10);
      if (!Number.isFinite(id) || id <= 0) return;
      const ids = readHiddenIds();
      ids.push(id);
      writeHiddenIds(ids);
      removeCard(id);
    }

    function getModuleTitle(moduleId) {
      const card = document.querySelector(`[data-module-id="${moduleId}"]`);
      const title = card ? card.getAttribute('data-module-title') : '';
      return title && title.trim() !== '' ? title.trim() : 'this module';
    }

    function confirmDelete(title, mode) {
      const baseText = mode === 'server' ?
        'This will permanently delete it.' :
        'This will remove it from your list on this device.';

      if (typeof Swal === 'undefined') {
        return Promise.resolve(confirm(`Delete "${title}"?\n\n${baseText}`));
      }

      return Swal.fire({
        title: `Delete "${title}"?`,
        text: baseText,
        icon: 'warning',
        showDenyButton: false,
        denyButtonText: '',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: false,
        buttonsStyling: false,
        customClass: {
          actions: 'swal2-actions',
          confirmButton: 'btn btn-error',
          cancelButton: 'btn btn-outline',
          denyButton: 'hidden'
        },
        didOpen: () => {
          const denyBtn = Swal.getDenyButton && Swal.getDenyButton();
          if (denyBtn) denyBtn.style.display = 'none';
        }
      }).then((result) => !!result.isConfirmed);
    }

    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-action="delete-module"]');
      if (!btn) return;

      e.preventDefault();

      const mode = (btn.getAttribute('data-mode') || '').toLowerCase();
      const moduleId = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (!Number.isFinite(moduleId) || moduleId <= 0) return;

      const title = getModuleTitle(moduleId);
      const ok = await confirmDelete(title, mode);
      if (!ok) return;

      if (mode === 'server') {
        const form = btn.closest('form');
        if (form) form.submit();
        return;
      }

      deleteModuleLocal(moduleId);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Deleted',
          text: 'Module removed from your list.',
          icon: 'success',
          timer: 1200,
          showConfirmButton: false
        });
      }
    });

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

    document.addEventListener('DOMContentLoaded', () => {
      const hiddenIds = readHiddenIds();
      hiddenIds.forEach(id => removeCard(id));
      const params = new URLSearchParams(window.location.search);
      const viewId = parseInt(params.get('view') || '0', 10);
      if (Number.isFinite(viewId) && viewId > 0) {
        viewModule(viewId);
      }
    });

    lucide.createIcons();
  </script>
</body>

</html>