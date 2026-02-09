<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$requiredDocs = [
  'Identification (ID/Passport)',
  'Signed Policy Acknowledgment',
  'Updated CV/Resume',
  'Degree Certificate',
];

function allowedExt($name)
{
  $ext = strtolower((string)pathinfo((string)$name, PATHINFO_EXTENSION));
  $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
  return in_array($ext, $allowed, true);
}

$sort = (string)($_GET['sort'] ?? 'desc');
$sort = ($sort === 'asc') ? 'asc' : 'desc';

$docs = [];
if ($conn && $employeeId) {
  $order = $sort === 'asc' ? 'ASC' : 'DESC';
  $sql = "SELECT id, document_title, document_type, file_path, status, remarks, submitted_at FROM submitted_documents WHERE employee_id = ? ORDER BY submitted_at {$order}";
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

function humanFileSize($bytes)
{
  $b = (int)$bytes;
  if ($b < 1024) return $b . ' B';
  if ($b < 1024 * 1024) return round($b / 1024, 1) . ' KB';
  return round($b / (1024 * 1024), 1) . ' MB';
}

function safeDate($iso)
{
  $t = strtotime((string)$iso);
  if ($t === false) return (string)$iso;
  return date('Y-m-d', $t);
}

function docStatusLabel(string $status, string $remarks): string
{
  $s = strtolower(trim($status));
  $r = trim($remarks);
  if ($s === 'pending' && str_starts_with($r, '[COMPLIANCE]')) return 'For Compliance';
  if ($s === 'pending') return 'Under Review';
  if ($s === 'approved') return 'Approved';
  if ($s === 'rejected') return 'Rejected';
  return $status;
}

function docStatusBadge(string $label): string
{
  $s = strtolower(trim($label));
  return match ($s) {
    'approved' => 'badge-success',
    'rejected' => 'badge-error',
    'for compliance' => 'badge-info',
    'under review' => 'badge-warning',
    default => 'badge-ghost',
  };
}

$viewParam = (string)($_GET['view'] ?? '');
$downloadParam = (string)($_GET['download'] ?? '');

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_document'])) {
  $requiredType = trim((string)($_POST['required_type'] ?? ''));
  $otherType = trim((string)($_POST['other_type'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $finalType = '';

  if ($requiredType === '') {
    $error_message = 'Please select a required document type.';
  } elseif ($requiredType === 'Others') {
    if ($otherType === '') {
      $error_message = 'Please specify the document type.';
    } else {
      $finalType = $otherType;
    }
  } elseif (!in_array($requiredType, $requiredDocs, true)) {
    $error_message = 'Please select a valid document type.';
  } else {
    $finalType = $requiredType;
  }

  if ($error_message === '' && (!isset($_FILES['document']) || !is_array($_FILES['document']))) {
    $error_message = 'Please select a file to upload.';
  } else {
    $err = (int)($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
      $uploadErrorMessages = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit (MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk on the server.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension on the server.',
      ];
      $error_message = $uploadErrorMessages[$err] ?? ('File upload error (code ' . $err . ').');
    } else {
      $tmp = (string)($_FILES['document']['tmp_name'] ?? '');
      $orig = (string)($_FILES['document']['name'] ?? 'document');
      $size = (int)($_FILES['document']['size'] ?? 0);

      if (!$employeeId) {
        $error_message = 'Unable to identify employee. Please login again.';
      } elseif ($tmp === '' || !is_uploaded_file($tmp)) {
        $error_message = 'Upload validation failed. Please try again.';
      } elseif ($size > 10 * 1024 * 1024) {
        $error_message = 'File too large. Maximum is 10MB.';
      } elseif (!allowedExt($orig)) {
        $error_message = 'Unsupported file type. Please upload PDF, image, or office files.';
      } elseif (!$conn) {
        $error_message = 'Database connection unavailable.';
      } else {
        $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(16)) . ($ext !== '' ? ('.' . $ext) : '');
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $stored;

        if (!@move_uploaded_file($tmp, $dest)) {
          $error_message = 'Failed to save uploaded file. Please try again.';
        } else {
          $fileRel = 'uploads/' . $stored;
          $status = 'Pending';
          $remarks = $description;

          $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO submitted_documents (employee_id, document_title, document_type, file_path, status, remarks) VALUES (?, ?, ?, ?, ?, ?)'
          );
          if (!$stmt) {
            $error_message = 'Failed to save record. Please try again.';
          } else {
            mysqli_stmt_bind_param($stmt, 'isssss', $employeeId, $orig, $finalType, $fileRel, $status, $remarks);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
              $error_message = 'Failed to save submission. Please try again.';
            } else {
              header('Location: mydocuments.php?uploaded=1');
              exit;
            }
          }
        }
      }
    }
  }
}

if (((string)($_GET['uploaded'] ?? '')) === '1') {
  $success_message = 'Document submitted successfully. Awaiting approval.';
}

function ess_resolve_file_path(string $filePath, string $baseDir): ?string
{
  $p = trim($filePath);
  if ($p === '') return null;
  $p = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $p);

  if (preg_match('/^[A-Za-z]:\\\\/', $p) || str_starts_with($p, DIRECTORY_SEPARATOR)) {
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
    $stmt = mysqli_prepare($conn, 'SELECT document_title, file_path FROM submitted_documents WHERE id = ? AND employee_id = ? LIMIT 1');
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ii', $id, $employeeId);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $target = $res ? mysqli_fetch_assoc($res) : null;
      mysqli_stmt_close($stmt);

      if (is_array($target)) {
        $orig = (string)($target['document_title'] ?? 'document');
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
    $stmt = mysqli_prepare($conn, 'SELECT document_title, file_path FROM submitted_documents WHERE id = ? AND employee_id = ? LIMIT 1');
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ii', $id, $employeeId);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $viewDoc = $res ? mysqli_fetch_assoc($res) : null;
      mysqli_stmt_close($stmt);

      if (is_array($viewDoc)) {
        $orig = (string)($viewDoc['document_title'] ?? 'document');
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
            'txt' => 'text/plain; charset=utf-8',
            'csv' => 'text/csv; charset=utf-8',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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
  <script src="https://unpkg.com/mammoth@1.6.0/mammoth.browser.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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

                <div class="flex items-center gap-2">
                  <a class="btn btn-ghost btn-sm" href="?sort=<?php echo $sort === 'desc' ? 'asc' : 'desc'; ?>">
                    <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                    <span class="ml-2">Sort by Date</span>
                  </a>
                  <button type="button" class="btn btn-sm hr2-primary-btn" id="openSubmitDocModalBtn">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    <span class="ml-2">Submit Document</span>
                  </button>
                </div>
              </div>

              <?php if ($success_message !== ''): ?>
                <div class="alert alert-success mt-4">
                  <i data-lucide="check-circle" class="w-5 h-5"></i>
                  <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
              <?php endif; ?>

              <?php if ($error_message !== ''): ?>
                <div class="alert alert-error mt-4">
                  <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                  <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
              <?php endif; ?>

              <div class="mt-4 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>File Name</th>
                      <th>Type</th>
                      <th>Status</th>
                      <th>Upload Date</th>
                      <th>Size</th>
                      <th class="text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($docs) === 0): ?>
                      <tr>
                        <td colspan="6" class="text-center text-gray-500 py-10">No documents yet.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($docs as $d): ?>
                      <?php
                      $id = (string)($d['id'] ?? '');
                      $orig = (string)($d['document_title'] ?? '');
                      $docType = (string)($d['document_type'] ?? '');
                      $uploaded = (string)($d['submitted_at'] ?? '');
                      $filePathRaw = (string)($d['file_path'] ?? '');
                      $remarks = (string)($d['remarks'] ?? '');
                      $statusDb = (string)($d['status'] ?? 'Pending');
                      $statusLabel = docStatusLabel($statusDb, $remarks);
                      $fileBase = $filePathRaw !== '' ? basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePathRaw)) : '';
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
                        <td class="text-gray-700"><?php echo htmlspecialchars($docType); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo htmlspecialchars(docStatusBadge($statusLabel)); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(safeDate($uploaded)); ?></td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(humanFileSize($size)); ?></td>
                        <td class="text-right">
                          <div class="flex justify-end gap-2">
                            <button class="btn btn-ghost btn-xs" type="button" data-view-id="<?php echo htmlspecialchars($id); ?>" data-view-name="<?php echo htmlspecialchars($orig); ?>" data-view-file="<?php echo htmlspecialchars($fileBase); ?>">
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
          <p class="text-sm text-gray-500">Preview</p>
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
          <span>Preview is not available for this file type. Use Open or Download.</span>
        </div>

        <img id="docViewImage" class="hidden w-full h-[65vh] object-contain rounded-lg border border-base-200 bg-white" alt="Preview" />
        <iframe id="docViewFrame" class="hidden w-full h-[65vh] rounded-lg border border-base-200 bg-white" src="about:blank"></iframe>
        <video id="docViewVideo" class="hidden w-full h-[65vh] rounded-lg border border-base-200 bg-black" controls></video>
        <audio id="docViewAudio" class="hidden w-full mt-2" controls></audio>
        <pre id="docViewText" class="hidden w-full h-[65vh] overflow-auto rounded-lg border border-base-200 bg-white p-4 text-sm"></pre>
        <div id="docViewHtml" class="hidden w-full h-[65vh] overflow-auto rounded-lg border border-base-200 bg-white p-4 prose max-w-none"></div>
      </div>

      <div class="modal-action">
        <a id="docViewOpen" class="btn btn-outline" href="#" target="_blank" rel="noopener">Open</a>
        <a id="docViewDownload" class="btn btn-primary" href="#">Download</a>
        <form method="dialog">
          <button class="btn">Close</button>
        </form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="submitDocModal" class="modal">
    <div class="modal-box w-11/12 max-w-lg">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Submit Document</h3>
          <p class="text-sm text-gray-500">Upload documents requested by HR and track their status.</p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <div class="divider my-4"></div>

      <form method="POST" enctype="multipart/form-data" class="space-y-3">
        <input type="hidden" name="submit_document" value="1" />

        <div class="form-control">
          <label class="label"><span class="label-text">Document Type</span></label>
          <select name="required_type" class="select select-bordered" required>
            <option value="" selected disabled>Select Document Type</option>
            <?php foreach ($requiredDocs as $rd): ?>
              <option value="<?php echo htmlspecialchars($rd); ?>"><?php echo htmlspecialchars($rd); ?></option>
            <?php endforeach; ?>
            <option value="Others">Others</option>
          </select>
        </div>

        <div id="otherTypeFormControl" class="form-control hidden">
          <label class="label"><span class="label-text">Specify Document</span></label>
          <input name="other_type" type="text" class="input input-bordered w-full" placeholder="Enter document type" />
          <label class="label"><span class="label-text-alt text-gray-500">Provide the exact document name/type.</span></label>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">Description</span></label>
          <textarea name="description" class="textarea textarea-bordered w-full resize-y min-h-24" rows="3" placeholder="Optional description or notes"></textarea>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">File</span></label>
          <input name="document" type="file" class="file-input file-input-bordered w-full" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.txt,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required />
          <label class="label"><span class="label-text-alt text-gray-500">Max 10MB.</span></label>
        </div>

        <div class="modal-action">
          <button type="button" class="btn hr2-outline-btn" id="submitDocCancelBtn">Close</button>
          <button class="btn hr2-primary-btn" type="submit">
            <i data-lucide="file-up" class="w-4 h-4"></i>
            <span class="ml-2">Submit</span>
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <script>
    function getExt(name) {
      return (name || '').split('.').pop().toLowerCase();
    }

    function setHidden(el, hidden) {
      if (!el) return;
      if (hidden) el.classList.add('hidden');
      else el.classList.remove('hidden');
    }

    function resetPreview(opts) {
      if (!opts) return;
      setHidden(opts.fallback, true);
      setHidden(opts.img, true);
      setHidden(opts.frame, true);
      setHidden(opts.video, true);
      setHidden(opts.audio, true);
      setHidden(opts.text, true);
      setHidden(opts.html, true);

      if (opts.frame) opts.frame.setAttribute('src', 'about:blank');
      if (opts.img) opts.img.setAttribute('src', '');
      if (opts.img) opts.img.onerror = null;
      if (opts.video) opts.video.removeAttribute('src');
      if (opts.audio) opts.audio.removeAttribute('src');
      if (opts.text) opts.text.textContent = '';
      if (opts.html) opts.html.innerHTML = '';
    }

    async function renderPreview(fileName, viewUrl, opts) {
      resetPreview(opts);

      const ext = getExt(fileName);
      const isImage = ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext);
      const isPdf = ext === 'pdf';
      const isText = ['txt', 'csv'].includes(ext);
      const isVideo = ['mp4', 'webm'].includes(ext);
      const isAudio = ['mp3', 'wav', 'ogg'].includes(ext);
      const isDocx = ext === 'docx';
      const isXlsx = ext === 'xlsx' || ext === 'xls';

      try {
        if (isImage && opts.img) {
          setHidden(opts.img, false);
          opts.img.onerror = function() {
            setHidden(opts.img, true);
            if (opts.fallback) setHidden(opts.fallback, false);
          };
          opts.img.setAttribute('src', viewUrl);
          return;
        }

        if (isPdf && opts.frame) {
          setHidden(opts.frame, false);
          opts.frame.setAttribute('src', viewUrl);
          return;
        }

        if (isVideo && opts.video) {
          setHidden(opts.video, false);
          opts.video.setAttribute('src', viewUrl);
          opts.video.load();
          return;
        }

        if (isAudio && opts.audio) {
          setHidden(opts.audio, false);
          opts.audio.setAttribute('src', viewUrl);
          opts.audio.load();
          return;
        }

        if (isText && opts.text) {
          setHidden(opts.text, false);
          const res = await fetch(viewUrl, {
            method: 'GET'
          });
          if (!res.ok) throw new Error('Failed to load text');
          const t = await res.text();
          opts.text.textContent = t;
          return;
        }

        if (isDocx && opts.html && window.mammoth) {
          setHidden(opts.html, false);
          const res = await fetch(viewUrl, {
            method: 'GET'
          });
          if (!res.ok) throw new Error('Failed to load DOCX');
          const buf = await res.arrayBuffer();
          const result = await window.mammoth.convertToHtml({
            arrayBuffer: buf
          });
          opts.html.innerHTML = result && result.value ? result.value : '';
          return;
        }

        if (isXlsx && opts.html && window.XLSX) {
          setHidden(opts.html, false);
          const res = await fetch(viewUrl, {
            method: 'GET'
          });
          if (!res.ok) throw new Error('Failed to load spreadsheet');
          const buf = await res.arrayBuffer();
          const wb = window.XLSX.read(buf, {
            type: 'array'
          });
          const first = wb.SheetNames && wb.SheetNames.length ? wb.SheetNames[0] : null;
          if (!first) {
            opts.html.innerHTML = '';
            return;
          }
          const ws = wb.Sheets[first];
          opts.html.innerHTML = window.XLSX.utils.sheet_to_html(ws);
          return;
        }

        if (opts.frame) {
          setHidden(opts.frame, false);
          opts.frame.setAttribute('src', viewUrl);
          return;
        }

        if (opts.fallback) {
          setHidden(opts.fallback, false);
        }
      } catch (e) {
        if (opts.fallback) {
          setHidden(opts.fallback, false);
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      lucide.createIcons();

      const submitModal = document.getElementById('submitDocModal');
      const openSubmitBtn = document.getElementById('openSubmitDocModalBtn');
      const cancelSubmitBtn = document.getElementById('submitDocCancelBtn');
      const closeSubmit = () => {
        if (submitModal) submitModal.close();
      };
      if (openSubmitBtn) openSubmitBtn.addEventListener('click', () => {
        if (submitModal) submitModal.showModal();
      });
      if (cancelSubmitBtn) cancelSubmitBtn.addEventListener('click', closeSubmit);

      try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open_submit') === '1') {
          if (submitModal) submitModal.showModal();
        }
      } catch (_) {}

      const requiredSelect = document.querySelector('#submitDocModal select[name="required_type"]');
      const otherTypeControl = document.getElementById('otherTypeFormControl');
      const otherTypeInput = document.querySelector('#submitDocModal input[name="other_type"]');

      function updateOtherType() {
        const isOthers = requiredSelect && requiredSelect.value === 'Others';
        setHidden(otherTypeControl, !isOthers);
        if (otherTypeInput) otherTypeInput.required = !!isOthers;
      }
      if (requiredSelect) {
        requiredSelect.addEventListener('change', updateOtherType);
        updateOtherType();
      }

      const modal = document.getElementById('docViewModal');
      const title = document.getElementById('docViewTitle');
      const frame = document.getElementById('docViewFrame');
      const fallback = document.getElementById('docViewFallback');
      const dl = document.getElementById('docViewDownload');
      const openBtn = document.getElementById('docViewOpen');
      const img = document.getElementById('docViewImage');
      const video = document.getElementById('docViewVideo');
      const audio = document.getElementById('docViewAudio');
      const text = document.getElementById('docViewText');
      const html = document.getElementById('docViewHtml');

      document.querySelectorAll('[data-view-id]').forEach((btn) => {
        btn.addEventListener('click', async function() {
          const id = this.getAttribute('data-view-id') || '';
          const name = this.getAttribute('data-view-name') || 'Document';
          const fileName = this.getAttribute('data-view-file') || name;

          title.textContent = name;
          dl.setAttribute('href', '?download=' + encodeURIComponent(id));
          if (openBtn) openBtn.setAttribute('href', '?view=' + encodeURIComponent(id));

          const viewUrl = '?view=' + encodeURIComponent(id);

          await renderPreview(fileName, viewUrl, {
            fallback,
            img,
            frame,
            video,
            audio,
            text,
            html,
          });

          if (modal && typeof modal.showModal === 'function') {
            modal.showModal();
          } else if (modal) {
            modal.setAttribute('open', '');
          }
        });
      });

      if (modal) {
        modal.addEventListener('close', function() {
          resetPreview({
            fallback,
            img,
            frame,
            video,
            audio,
            text,
            html,
          });
        });
      }
    });
  </script>
</body>

</html>