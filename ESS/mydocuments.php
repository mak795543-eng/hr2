<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$sort = (string)($_GET['sort'] ?? 'desc');
$sort = ($sort === 'asc') ? 'asc' : 'desc';

$docs = [];
if ($conn && $employeeId) {
    $order = $sort === 'asc' ? 'ASC' : 'DESC';
    $sql = "SELECT id, document_name, document_type, file_path, uploaded_at FROM employee_documents WHERE employee_id = ? ORDER BY uploaded_at {$order}";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $docs[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

function humanFileSize($bytes) {
    $b = (int)$bytes;
    if ($b < 1024) return $b . ' B';
    if ($b < 1024 * 1024) return round($b / 1024, 1) . ' KB';
    return round($b / (1024 * 1024), 1) . ' MB';
}

function safeDate($iso) {
    $t = strtotime((string)$iso);
    if ($t === false) return (string)$iso;
    return date('Y-m-d', $t);
}

$viewParam = (string)($_GET['view'] ?? '');
$downloadParam = (string)($_GET['download'] ?? '');

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

function ess_resolve_file_path(string $filePath, string $baseDir): ?string {
    $p = trim($filePath);
    if ($p === '') return null;
    $p = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $p);

    if (preg_match('#^[A-Za-z]:\\#', $p) || str_starts_with($p, DIRECTORY_SEPARATOR)) {
        $candidate = $p;
    } else {
        $candidate = __DIR__ . DIRECTORY_SEPARATOR . ltrim($p, DIRECTORY_SEPARATOR);
        if (!is_file($candidate)) {
            $candidate = $baseDir . DIRECTORY_SEPARATOR . basename($p);
        }
    }

    $real = realpath($candidate);
    $baseReal = realpath($baseDir);
    if ($real === false || $baseReal === false) return null;
    if (strpos($real, $baseReal) !== 0) return null;
    return $real;
}

if ($downloadParam !== '') {
    if ($conn && $employeeId) {
        $id = (int)$downloadParam;
        $stmt = mysqli_prepare($conn, 'SELECT document_name, file_path FROM employee_documents WHERE id = ? AND employee_id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $employeeId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $target = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($target)) {
                $orig = (string)($target['document_name'] ?? 'document');
                $filePath = (string)($target['file_path'] ?? '');
                $path = ess_resolve_file_path($filePath, $uploadDir);
                if ($path && is_file($path)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($orig) . '"');
                    header('Content-Length: ' . filesize($path));
                    readfile($path);
                    exit;
                }
            }
        }
    }

    http_response_code(404);
    echo 'File not found.';
    exit;
}

$viewDoc = null;
if ($viewParam !== '') {
    if ($conn && $employeeId) {
        $id = (int)$viewParam;
        $stmt = mysqli_prepare($conn, 'SELECT document_name, file_path FROM employee_documents WHERE id = ? AND employee_id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $employeeId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $viewDoc = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($viewDoc)) {
                $orig = (string)($viewDoc['document_name'] ?? 'document');
                $filePath = (string)($viewDoc['file_path'] ?? '');
                $path = ess_resolve_file_path($filePath, $uploadDir);
                if ($path && is_file($path)) {
                    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
                    $mimeMap = [
                        'pdf' => 'application/pdf',
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                    ];
                    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

                    header('Content-Type: ' . $mime);
                    header('Content-Disposition: inline; filename="' . basename($orig) . '"');
                    readfile($path);
                    exit;
                }
            }
        }
    }

    http_response_code(404);
    echo 'File not found.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Documents</title>
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
        <div class="max-w-5xl mx-auto">
          <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h1 class="text-lg md:text-xl font-bold text-gray-800">Document Repository</h1>
                  <p class="text-sm text-gray-500">Store and access your uploaded documents.</p>
                </div>

                <a class="btn btn-ghost btn-sm" href="?sort=<?php echo $sort === 'desc' ? 'asc' : 'desc'; ?>">
                  <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                  <span class="ml-2">Sort by Date</span>
                </a>
              </div>

              <div class="mt-4 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>File Name</th>
                      <th>Upload Date</th>
                      <th>Size</th>
                      <th class="text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($docs) === 0): ?>
                      <tr>
                        <td colspan="4" class="text-center text-gray-500 py-10">No documents yet. Upload one in <a class="link link-primary" href="submitdocument.php">Submit Document</a>.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($docs as $d): ?>
                      <?php
                        $id = (string)($d['id'] ?? '');
                        $orig = (string)($d['document_name'] ?? '');
                        $uploaded = (string)($d['uploaded_at'] ?? '');
                        $path = ess_resolve_file_path((string)($d['file_path'] ?? ''), $uploadDir);
                        $size = ($path && is_file($path)) ? (int)@filesize($path) : 0;
                      ?>
                      <tr>
                        <td>
                          <div class="flex items-center gap-2">
                            <i data-lucide="file" class="w-4 h-4 text-blue-600"></i>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($orig); ?></span>
                          </div>
                        </td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(safeDate($uploaded)); ?></td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(humanFileSize($size)); ?></td>
                        <td class="text-right">
                          <div class="flex justify-end gap-2">
                            <button class="btn btn-ghost btn-xs" type="button" data-view-id="<?php echo htmlspecialchars($id); ?>" data-view-name="<?php echo htmlspecialchars($orig); ?>">
                              <i data-lucide="eye" class="w-4 h-4"></i>
                              <span class="hidden sm:inline ml-1">View</span>
                            </button>
                            <a class="btn btn-link btn-xs" href="?download=<?php echo urlencode($id); ?>">Download</a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="mt-4 flex justify-end">
                <a class="btn btn-primary btn-sm" href="submitdocument.php">
                  <i data-lucide="upload" class="w-4 h-4"></i>
                  <span class="ml-2">Upload New Document</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <dialog id="docViewModal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 id="docViewTitle" class="font-bold text-lg">View Document</h3>
          <p class="text-sm text-gray-500">Preview (PDF/Image only). Other files will open/download.</p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <div class="mt-4">
        <div id="docViewFallback" class="hidden alert alert-info">
          <i data-lucide="info" class="w-5 h-5"></i>
          <span>This file type can’t be previewed here. Use Download instead.</span>
        </div>

        <iframe id="docViewFrame" class="w-full h-[65vh] rounded-lg border border-base-200 bg-white" src="about:blank"></iframe>
      </div>

      <div class="modal-action">
        <a id="docViewDownload" class="btn btn-primary" href="#">Download</a>
        <form method="dialog">
          <button class="btn">Close</button>
        </form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <script>
    function canPreview(name) {
      const ext = (name || '').split('.').pop().toLowerCase();
      return ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext);
    }

    document.addEventListener('DOMContentLoaded', function () {
      lucide.createIcons();

      const modal = document.getElementById('docViewModal');
      const title = document.getElementById('docViewTitle');
      const frame = document.getElementById('docViewFrame');
      const fallback = document.getElementById('docViewFallback');
      const dl = document.getElementById('docViewDownload');

      document.querySelectorAll('[data-view-id]').forEach((btn) => {
        btn.addEventListener('click', function () {
          const id = this.getAttribute('data-view-id') || '';
          const name = this.getAttribute('data-view-name') || 'Document';

          title.textContent = name;
          dl.setAttribute('href', '?download=' + encodeURIComponent(id));

          if (canPreview(name)) {
            fallback.classList.add('hidden');
            frame.classList.remove('hidden');
            frame.setAttribute('src', '?view=' + encodeURIComponent(id));
          } else {
            frame.classList.add('hidden');
            frame.setAttribute('src', 'about:blank');
            fallback.classList.remove('hidden');
          }

          if (modal && typeof modal.showModal === 'function') {
            modal.showModal();
          }
        });
      });

      modal.addEventListener('close', function () {
        frame.setAttribute('src', 'about:blank');
      });
    });
  </script>
</body>
</html>
