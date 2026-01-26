<?php
session_start();

require_once __DIR__ . '/db.php';

$tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
};

$ensureDraftSchema = function(mysqli $conn) use ($tableHasColumn): void {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS training_program_drafts (id VARCHAR(64) PRIMARY KEY, session_key VARCHAR(128) NOT NULL, title VARCHAR(255) NOT NULL, data_json LONGTEXT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_tpd_session (session_key), INDEX idx_tpd_updated (updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
    try {
        if (!$tableHasColumn($conn, 'training_program_drafts', 'session_key')) {
            return;
        }
    } catch (Throwable $e) {
    }
};

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

$ensureDraftSchema($conn);
$ownerKey = $getOwnerKey();

if (isset($_GET['action']) && in_array((string)$_GET['action'], ['list', 'get'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    $action = (string)$_GET['action'];

    if ($action === 'list') {
        $items = [];
        try {
            $stmt = $conn->prepare("SELECT id, title, data_json, updated_at FROM training_program_drafts WHERE session_key = ? ORDER BY updated_at DESC");
            $stmt->bind_param('s', $ownerKey);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $items[] = [
                    'id' => (string)($row['id'] ?? ''),
                    'title' => (string)($row['title'] ?? ''),
                    'saved_at' => (string)($row['updated_at'] ?? ''),
                    'data' => json_decode((string)($row['data_json'] ?? ''), true)
                ];
            }
        } catch (Throwable $e) {
        }
        echo json_encode(['success' => true, 'drafts' => $items]);
        exit;
    }

    if ($action === 'get') {
        $id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
        if ($id === '') {
            echo json_encode(['success' => false, 'message' => 'Missing id']);
            exit;
        }
        try {
            $stmt = $conn->prepare("SELECT id, title, data_json, updated_at FROM training_program_drafts WHERE id = ? AND session_key = ? LIMIT 1");
            $stmt->bind_param('ss', $id, $ownerKey);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Draft not found']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'draft' => [
                    'id' => (string)($row['id'] ?? ''),
                    'title' => (string)($row['title'] ?? ''),
                    'saved_at' => (string)($row['updated_at'] ?? ''),
                    'data' => json_decode((string)($row['data_json'] ?? ''), true)
                ]
            ]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array((string)$_POST['action'], ['upsert', 'delete', 'migrate'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    $action = (string)$_POST['action'];

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
        if ($id === '') {
            echo json_encode(['success' => false, 'message' => 'Missing id']);
            exit;
        }
        try {
            $stmt = $conn->prepare("DELETE FROM training_program_drafts WHERE id = ? AND session_key = ?");
            $stmt->bind_param('ss', $id, $ownerKey);
            $stmt->execute();
        } catch (Throwable $e) {
        }
        echo json_encode(['success' => true]);
        exit;
    }

    $upsertOne = function(array $draft) use ($conn, $ownerKey): void {
        $id = trim((string)($draft['id'] ?? ''));
        $title = trim((string)($draft['title'] ?? ''));
        $data = $draft['data'] ?? null;
        if ($id === '' || $title === '' || $data === null) return;
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (!is_string($dataJson)) return;

        $stmt = $conn->prepare("INSERT INTO training_program_drafts (id, session_key, title, data_json) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), data_json = VALUES(data_json), session_key = VALUES(session_key)");
        $stmt->bind_param('ssss', $id, $ownerKey, $title, $dataJson);
        $stmt->execute();
    };

    if ($action === 'upsert') {
        $id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
        $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
        $dataJsonRaw = isset($_POST['data_json']) ? (string)$_POST['data_json'] : '';
        $data = json_decode($dataJsonRaw, true);
        if ($id === '' || $title === '' || $data === null) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }
        try {
            $upsertOne(['id' => $id, 'title' => $title, 'data' => $data]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'migrate') {
        $draftsJsonRaw = isset($_POST['drafts_json']) ? (string)$_POST['drafts_json'] : '';
        $drafts = json_decode($draftsJsonRaw, true);
        if (!is_array($drafts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid drafts']);
            exit;
        }
        $count = 0;
        foreach ($drafts as $d) {
            if (!is_array($d)) continue;
            try {
                $upsertOne($d);
                $count++;
            } catch (Throwable $e) {
            }
        }
        echo json_encode(['success' => true, 'migrated' => $count]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Drafts</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
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
        .swal2-container { z-index: 2147483647 !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" data-owner-key="<?= htmlspecialchars($ownerKey) ?>">
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
      

<main class="max-w-5xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Drafts</h1>
                    <p class="text-gray-600">Continue a saved draft or delete drafts you no longer need.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="add_training.php" class="btn btn-primary">Create New</a>
                    <a href="trainingprogram.php" class="btn btn-ghost">Back</a>
                </div>
            </div>

            <div id="drafts-empty" class="hidden">
                <div class="bg-base-200 rounded-lg p-6 text-center">
                    <div class="text-lg font-semibold text-gray-800">No drafts yet</div>
                    <div class="text-sm text-gray-600 mt-2">Create a training program draft from the Add Training page.</div>
                    <div class="mt-4">
                        <a href="add_training.php" class="btn btn-primary">Create New Training</a>
                    </div>
                </div>
            </div>

            <div id="drafts-list" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </div>
    </main>

    <script>
        (function () {
            const STORAGE_KEY = 'training_program_drafts_v1';
            const OWNER_KEY = document.body ? (document.body.getAttribute('data-owner-key') || '') : '';

            const apiPost = async (action, body) => {
                const fd = new FormData();
                fd.append('action', action);
                Object.keys(body || {}).forEach((k) => fd.append(k, body[k]));
                const res = await fetch('drafts.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                return await res.json();
            };

            const apiGet = async (params) => {
                const url = new URL('drafts.php', window.location.href);
                Object.keys(params || {}).forEach((k) => url.searchParams.set(k, params[k]));
                const res = await fetch(url.toString(), { credentials: 'same-origin' });
                return await res.json();
            };

            const esc = (v) => String(v ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const loadDrafts = () => {
                try {
                    const raw = window.localStorage ? window.localStorage.getItem(STORAGE_KEY) : null;
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (_) {
                    return [];
                }
            };

            const persistDrafts = (drafts) => {
                try {
                    if (!window.localStorage) return;
                    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.isArray(drafts) ? drafts : []));
                } catch (_) {
                }
            };

            const migrateLocalDraftsOnce = async () => {
                if (!window.localStorage) return;
                const localDrafts = loadDrafts();
                if (!localDrafts.length) return;
                const guardKey = `drafts_migrated_to_db_${encodeURIComponent(String(OWNER_KEY || ''))}`;
                const already = window.localStorage.getItem(guardKey);
                if (already === '1') return;

                try {
                    const payload = localDrafts.map((d) => ({
                        id: String(d && d.id || ''),
                        title: String(d && d.title || 'Untitled Training'),
                        data: d && d.data ? d.data : null
                    })).filter((d) => d.id && d.data);

                    if (!payload.length) {
                        window.localStorage.setItem(guardKey, '1');
                        return;
                    }

                    const r = await apiPost('migrate', { drafts_json: JSON.stringify(payload) });
                    if (r && r.success) {
                        persistDrafts([]);
                        window.localStorage.setItem(guardKey, '1');
                    }
                } catch (_) {
                }
            };

            const deleteDraft = async (id) => {
                if (window.Swal) {
                    const res = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Delete this draft?',
                        text: 'This action cannot be undone.',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        cancelButtonText: 'Cancel'
                    });
                    if (!res.isConfirmed) return;
                } else {
                    if (!window.confirm('Delete this draft?')) return;
                }

                try {
                    await apiPost('delete', { id: String(id || '') });
                } catch (_) {
                }
                await render();
            };

            const formatSavedAt = (iso) => {
                if (!iso) return '';
                const d = new Date(String(iso));
                if (isNaN(d.getTime())) return String(iso);
                return d.toLocaleString();
            };

            const render = async () => {
                const list = document.getElementById('drafts-list');
                const empty = document.getElementById('drafts-empty');
                if (!list || !empty) return;

                let drafts = [];
                try {
                    const r = await apiGet({ action: 'list' });
                    drafts = (r && r.success && Array.isArray(r.drafts)) ? r.drafts : [];
                } catch (_) {
                    drafts = [];
                }
                list.innerHTML = '';

                if (!drafts.length) {
                    empty.classList.remove('hidden');
                    return;
                }

                empty.classList.add('hidden');

                drafts.forEach((d) => {
                    const id = String(d && d.id || '');
                    const title = (d && d.title) ? String(d.title) : 'Untitled Training';
                    const savedAt = formatSavedAt(d && d.saved_at);
                    const targetAudience = d && d.data && d.data.form ? d.data.form.target_audience : '';
                    const trainingType = d && d.data && d.data.form ? d.data.form.training_type : '';

                    const card = document.createElement('div');
                    card.className = 'card bg-white border border-gray-200 shadow-sm';
                    card.innerHTML = `
                        <div class="card-body p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-gray-500">Draft</div>
                                    <div class="text-lg font-bold text-gray-900">${esc(title)}</div>
                                    <div class="text-xs text-gray-500 mt-1">Saved: ${esc(savedAt || '-') }</div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    ${trainingType ? `<span class="badge badge-outline">${esc(trainingType)}</span>` : ''}
                                    ${targetAudience ? `<span class="badge badge-ghost">${esc(targetAudience)}</span>` : ''}
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-primary" href="add_training.php?draft_id=${encodeURIComponent(id)}">Continue</a>
                                <button type="button" class="btn btn-sm btn-outline" data-action="delete" data-id="${esc(id)}">Delete</button>
                            </div>
                        </div>
                    `;
                    list.appendChild(card);
                });

                list.querySelectorAll('button[data-action="delete"]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const id = btn.getAttribute('data-id');
                        await deleteDraft(id);
                    });
                });
            };

            document.addEventListener('DOMContentLoaded', async () => {
                await migrateLocalDraftsOnce();
                await render();
            });
        })();
    </script>
     <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>
