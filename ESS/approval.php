<?php
session_start();

$isBinaryView = isset($_GET['view'])
    || isset($_GET['download'])
    || isset($_GET['profile_proof_view'])
    || isset($_GET['profile_proof_download']);

if ($isBinaryView && !defined('SUPPRESS_DB_ERRORS')) {
    define('SUPPRESS_DB_ERRORS', true);
}

require __DIR__ . '/db.php';

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

$profileProofDir = $uploadDir . DIRECTORY_SEPARATOR . 'profile_request_proofs';
if (!is_dir($profileProofDir)) {
    @mkdir($profileProofDir, 0775, true);
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_request'])) {
    $rid = (int)($_POST['request_id'] ?? 0);
    $newStatus = trim((string)($_POST['status'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    $allowed = ['Approved', 'Rejected', 'Pending'];
    if ($rid <= 0 || !in_array($newStatus, $allowed, true)) {
        $error_message = 'Invalid request.';
    } elseif (!$conn) {
        $error_message = 'Database connection unavailable.';
    } else {
        $approverId = ess_employee_id($conn);
        $reviewedBy = is_int($approverId) ? $approverId : 0;
        $reviewedAt = date('Y-m-d H:i:s');

        $stmtMeta = mysqli_prepare($conn, 'SELECT employee_id, reason_choice, proof_file_path FROM profile_update_requests WHERE id = ? LIMIT 1');
        $meta = null;
        if ($stmtMeta) {
            mysqli_stmt_bind_param($stmtMeta, 'i', $rid);
            mysqli_stmt_execute($stmtMeta);
            $resMeta = mysqli_stmt_get_result($stmtMeta);
            $meta = $resMeta ? mysqli_fetch_assoc($resMeta) : null;
            mysqli_stmt_close($stmtMeta);
        }

        $stmt = mysqli_prepare($conn, 'UPDATE profile_update_requests SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = ?, seen_by_employee = 0 WHERE id = ?');
        if (!$stmt) {
            $error_message = 'Failed to update request.';
        } else {
            mysqli_stmt_bind_param($stmt, 'ssisi', $newStatus, $remarks, $reviewedBy, $reviewedAt, $rid);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                $error_message = 'Failed to update request.';
            } else {
                if (
                    $newStatus === 'Approved'
                    && is_array($meta)
                    && (int)($meta['employee_id'] ?? 0) > 0
                    && strtolower(trim((string)($meta['reason_choice'] ?? ''))) === 'change of surname'
                ) {
                    $eid = (int)$meta['employee_id'];
                    $stmt2 = mysqli_prepare(
                        $conn,
                        'INSERT INTO employee_profiles (employee_id, civil_status) VALUES (?, ?) '
                        . 'ON DUPLICATE KEY UPDATE civil_status = VALUES(civil_status)'
                    );
                    if ($stmt2) {
                        $civil = 'Married';
                        mysqli_stmt_bind_param($stmt2, 'is', $eid, $civil);
                        mysqli_stmt_execute($stmt2);
                        mysqli_stmt_close($stmt2);
                    }
                }

                if ($newStatus === 'Approved' && is_array($meta) && (int)($meta['employee_id'] ?? 0) > 0) {
                    $eid = (int)$meta['employee_id'];
                    $proofPath = trim((string)($meta['proof_file_path'] ?? ''));
                    if ($proofPath !== '') {
                        $docName = 'Profile Request Proof';
                        $reasonChoice = trim((string)($meta['reason_choice'] ?? ''));
                        if ($reasonChoice !== '') {
                            $docName .= ' - ' . $reasonChoice;
                        }

                        $existsStmt = mysqli_prepare($conn, 'SELECT id FROM employee_documents WHERE employee_id = ? AND file_path = ? LIMIT 1');
                        $already = false;
                        if ($existsStmt) {
                            mysqli_stmt_bind_param($existsStmt, 'is', $eid, $proofPath);
                            mysqli_stmt_execute($existsStmt);
                            $res = mysqli_stmt_get_result($existsStmt);
                            $already = $res ? (mysqli_num_rows($res) > 0) : false;
                            mysqli_stmt_close($existsStmt);
                        }

                        if (!$already) {
                            $docType = 'Profile Request';
                            $stmtDoc = mysqli_prepare($conn, 'INSERT INTO employee_documents (employee_id, document_name, document_type, file_path) VALUES (?, ?, ?, ?)');
                            if ($stmtDoc) {
                                mysqli_stmt_bind_param($stmtDoc, 'isss', $eid, $docName, $docType, $proofPath);
                                mysqli_stmt_execute($stmtDoc);
                                mysqli_stmt_close($stmtDoc);
                            }
                        }
                    }
                }

                $success_message = 'Profile request updated successfully.';

                $redirect = (string)($_SERVER['PHP_SELF']);
                $qs = http_build_query(['section' => 'profiles', 'pstatus' => 'pending']);
                header('Location: ' . $redirect . '?' . $qs);
                exit;
            }
        }
    }
}

$section = (string)($_GET['section'] ?? 'documents');
$section = in_array($section, ['documents', 'profiles'], true) ? $section : 'documents';

function ess_is_compliance(string $remarks): bool {
    return str_starts_with(trim($remarks), '[COMPLIANCE]');
}

function ess_status_label(string $status, string $remarks): string {
    $s = strtolower(trim($status));
    if ($s === 'pending' && ess_is_compliance($remarks)) return 'For Compliance';
    if ($s === 'pending') return 'For Approval';
    if ($s === 'approved') return 'Approved';
    if ($s === 'rejected') return 'Rejected';
    return $status;
}

function ess_status_to_db(string $uiStatus, string $remarks): array {
    $ui = strtolower(trim($uiStatus));
    $r = trim($remarks);

    if ($ui === 'for compliance') {
        $r = preg_replace('/^\[COMPLIANCE\]\s*/', '', $r);
        $r = '[COMPLIANCE] ' . $r;
        return ['Pending', trim($r)];
    }

    if ($ui === 'for approval') {
        $r = preg_replace('/^\[COMPLIANCE\]\s*/', '', $r);
        return ['Pending', trim($r)];
    }

    if ($ui === 'approved') {
        $r = preg_replace('/^\[COMPLIANCE\]\s*/', '', $r);
        return ['Approved', trim($r)];
    }

    if ($ui === 'rejected') {
        $r = preg_replace('/^\[COMPLIANCE\]\s*/', '', $r);
        return ['Rejected', trim($r)];
    }

    return ['Pending', trim($r)];
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

function ess_guess_mime_type(string $path): string {
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));

    $mimeMap = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
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

    if (isset($mimeMap[$ext])) {
        return $mimeMap[$ext];
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $path);
            finfo_close($finfo);
            if ($mime !== '') {
                return $mime;
            }
        }
    }

    return 'application/octet-stream';
}

function badgeClassForStatus($status) {
    $s = strtolower(trim((string)$status));
    return match ($s) {
        'approved' => 'badge-success',
        'rejected' => 'badge-error',
        'for compliance' => 'badge-info',
        'for approval' => 'badge-warning',
        default => 'badge-ghost',
    };
}

function safeDateTime($iso) {
    $t = strtotime((string)$iso);
    if ($t === false) return (string)$iso;
    return date('M d, Y g:i A', $t);
}

function humanFileSize($bytes) {
    $b = (int)$bytes;
    if ($b < 1024) return $b . ' B';
    if ($b < 1024 * 1024) return round($b / 1024, 1) . ' KB';
    return round($b / (1024 * 1024), 1) . ' MB';
}

$filter = (string)($_GET['status'] ?? 'all');
$allowedFilters = ['all', 'for approval', 'approved', 'rejected', 'for compliance'];
$filter = in_array(strtolower($filter), $allowedFilters, true) ? strtolower($filter) : 'all';

$profileFilter = (string)($_GET['pstatus'] ?? '');
$hasExplicitProfileFilter = array_key_exists('pstatus', $_GET);
$profileFilter = $hasExplicitProfileFilter ? $profileFilter : (($section === 'profiles') ? 'pending' : 'all');
$profileAllowed = ['all', 'pending', 'approved', 'rejected'];
$profileFilter = in_array(strtolower($profileFilter), $profileAllowed, true) ? strtolower($profileFilter) : 'all';

$viewParam = (string)($_GET['view'] ?? '');
$downloadParam = (string)($_GET['download'] ?? '');

$profileProofViewParam = (string)($_GET['profile_proof_view'] ?? '');
$profileProofDownloadParam = (string)($_GET['profile_proof_download'] ?? '');

if ($section === 'profiles' && $profileProofDownloadParam !== '') {
    if ($conn) {
        $id = (int)$profileProofDownloadParam;
        $stmt = mysqli_prepare($conn, 'SELECT proof_file_path FROM profile_update_requests WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $target = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($target)) {
                $filePath = (string)($target['proof_file_path'] ?? '');
                $path = ess_resolve_file_path($filePath, $profileProofDir);
                if ($path && is_file($path)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
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

if ($section === 'profiles' && $profileProofViewParam !== '') {
    if ($conn) {
        $id = (int)$profileProofViewParam;
        $stmt = mysqli_prepare($conn, 'SELECT proof_file_path FROM profile_update_requests WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $target = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($target)) {
                $filePath = (string)($target['proof_file_path'] ?? '');
                $path = ess_resolve_file_path($filePath, $profileProofDir);
                if ($path && is_file($path)) {
                    $mime = ess_guess_mime_type($path);

                    header('Content-Type: ' . $mime);
                    header('Content-Disposition: inline; filename="' . basename($path) . '"');
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

if ($section === 'documents' && $downloadParam !== '') {
    if ($conn) {
        $id = (int)$downloadParam;
        $stmt = mysqli_prepare($conn, 'SELECT document_title, file_path FROM submitted_documents WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
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

if ($section === 'documents' && $viewParam !== '') {
    if ($conn) {
        $id = (int)$viewParam;
        $stmt = mysqli_prepare($conn, 'SELECT document_title, file_path FROM submitted_documents WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $target = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($target)) {
                $orig = (string)($target['document_title'] ?? 'document');
                $filePath = (string)($target['file_path'] ?? '');
                $path = ess_resolve_file_path($filePath, $uploadDir);
                if ($path && is_file($path)) {
                    $mime = ess_guess_mime_type($path);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $section === 'documents') {
    $id = trim((string)($_POST['id'] ?? ''));
    $newStatus = trim((string)($_POST['status'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    $allowed = ['Approved', 'Rejected', 'For Compliance', 'For Approval'];

    if ($id === '' || !in_array($newStatus, $allowed, true)) {
        $error_message = 'Invalid request.';
    } else {
        if (!$conn) {
            $error_message = 'Database connection unavailable.';
        } else {
            [$dbStatus, $dbRemarks] = ess_status_to_db($newStatus, $remarks);
            $rid = (int)$id;
            $approverId = ess_employee_id($conn);
            $reviewedBy = is_int($approverId) ? $approverId : 0;
            $reviewedAt = date('Y-m-d H:i:s');

            $stmt = mysqli_prepare($conn, 'UPDATE submitted_documents SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = ? WHERE id = ?');
            if (!$stmt) {
                $error_message = 'Failed to update record.';
            } else {
                mysqli_stmt_bind_param($stmt, 'ssisi', $dbStatus, $dbRemarks, $reviewedBy, $reviewedAt, $rid);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if (!$ok) {
                    $error_message = 'Failed to update record.';
                } else {
                    if ($dbStatus === 'Approved') {
                        $stmt = mysqli_prepare($conn, 'SELECT employee_id, document_title, document_type, file_path FROM submitted_documents WHERE id = ? LIMIT 1');
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, 'i', $rid);
                            mysqli_stmt_execute($stmt);
                            $res = mysqli_stmt_get_result($stmt);
                            $row = $res ? mysqli_fetch_assoc($res) : null;
                            mysqli_stmt_close($stmt);

                            if (is_array($row)) {
                                $eid = (int)($row['employee_id'] ?? 0);
                                $docName = (string)($row['document_title'] ?? 'Document');
                                $docType = (string)($row['document_type'] ?? '');
                                $filePath = (string)($row['file_path'] ?? '');

                                if ($eid > 0) {
                                    $stmt2 = mysqli_prepare($conn, 'INSERT INTO employee_documents (employee_id, document_name, document_type, file_path) VALUES (?, ?, ?, ?)');
                                    if ($stmt2) {
                                        mysqli_stmt_bind_param($stmt2, 'isss', $eid, $docName, $docType, $filePath);
                                        mysqli_stmt_execute($stmt2);
                                        mysqli_stmt_close($stmt2);
                                    }
                                }
                            }
                        }
                    }

                    $success_message = 'Status updated successfully.';
                }
            }
        }
    }
}

$rows = [];
if ($conn) {
    $where = '';
    if ($filter === 'approved') {
        $where = "WHERE s.status = 'Approved'";
    } elseif ($filter === 'rejected') {
        $where = "WHERE s.status = 'Rejected'";
    } elseif ($filter === 'for compliance') {
        $where = "WHERE s.status = 'Pending' AND s.remarks LIKE '[COMPLIANCE]%'";
    } elseif ($filter === 'for approval') {
        $where = "WHERE s.status = 'Pending' AND (s.remarks IS NULL OR s.remarks NOT LIKE '[COMPLIANCE]%')";
    }

    $sql = "SELECT s.id, s.employee_id, e.employee_no, s.document_type, s.document_title, s.file_path, s.status, s.remarks, s.submitted_at FROM submitted_documents s LEFT JOIN employees e ON e.id = s.employee_id {$where} ORDER BY s.submitted_at DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
}

$profileRows = [];
if ($conn) {
    $where = '';
    if ($profileFilter === 'pending') {
        $where = "WHERE r.status = 'Pending'";
    } elseif ($profileFilter === 'approved') {
        $where = "WHERE r.status = 'Approved'";
    } elseif ($profileFilter === 'rejected') {
        $where = "WHERE r.status = 'Rejected'";
    }

    $sql = "SELECT r.id, r.employee_id, e.employee_no, e.first_name, e.last_name, r.status, r.remarks, r.created_at, r.reviewed_at, r.reason_choice, r.reason_text, r.proof_file_path FROM profile_update_requests r LEFT JOIN employees e ON e.id = r.employee_id {$where} ORDER BY r.created_at DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $profileRows[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document Approval</title>
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
        <div class="max-w-6xl mx-auto">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">Approvals</h1>
              <p class="text-sm text-gray-500">Manage document submissions and profile change requests.</p>
            </div>
            <div class="flex items-center gap-2">
              <a href="submitdocument.php" class="btn btn-outline btn-sm">Submit Document</a>
              <a href="mydocuments.php" class="btn btn-ghost btn-sm">Repository</a>
            </div>
          </div>

          <div class="mt-6">
            <div class="tabs tabs-boxed">
              <a class="tab <?php echo $section === 'documents' ? 'tab-active' : ''; ?>" href="?section=documents&status=<?php echo urlencode($filter); ?>">Document Submissions</a>
              <a class="tab <?php echo $section === 'profiles' ? 'tab-active' : ''; ?>" href="?section=profiles&pstatus=<?php echo urlencode($profileFilter); ?>">Profile Requests</a>
            </div>
          </div>

          <?php if ($success_message !== ''): ?>
            <div class="alert alert-success mt-6">
              <i data-lucide="check-circle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($error_message !== ''): ?>
            <div class="alert alert-error mt-6">
              <i data-lucide="alert-triangle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($section === 'documents'): ?>
          <div class="mt-6 card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-center gap-2">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="inbox" class="w-5 h-5"></i>
                  </div>
                  <div>
                    <h2 class="font-semibold text-gray-800">Submissions</h2>
                    <p class="text-sm text-gray-500">Total: <?php echo (int)count($rows); ?></p>
                  </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                  <select id="statusFilter" class="select select-bordered w-full sm:w-56">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="for approval" <?php echo $filter === 'for approval' ? 'selected' : ''; ?>>For Approval</option>
                    <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="for compliance" <?php echo $filter === 'for compliance' ? 'selected' : ''; ?>>For Compliance</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th>Required Type</th>
                      <th>File</th>
                      <th>Uploaded</th>
                      <th>Status</th>
                      <th class="text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($rows) === 0): ?>
                      <tr>
                        <td colspan="6" class="text-center text-gray-500 py-10">No submissions found.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $r): ?>
                      <?php
                        $id = (string)($r['id'] ?? '');
                        $empNo = (string)($r['employee_no'] ?? '');
                        $empId = (string)($r['employee_id'] ?? '');
                        $type = (string)($r['document_type'] ?? '');
                        $orig = (string)($r['document_title'] ?? '');
                        $filePath = (string)($r['file_path'] ?? '');
                        $uploadedAt = (string)($r['submitted_at'] ?? '');
                        $dbStatus = (string)($r['status'] ?? 'Pending');
                        $remarks = (string)($r['remarks'] ?? '');
                        $status = ess_status_label($dbStatus, $remarks);
                        $path = ess_resolve_file_path($filePath, $uploadDir);
                        $size = ($path && is_file($path)) ? (int)@filesize($path) : 0;
                      ?>
                      <tr>
                        <td class="text-gray-800 font-medium"><?php echo htmlspecialchars($empNo !== '' ? $empNo : ($empId !== '' ? $empId : 'N/A')); ?></td>
                        <td class="text-gray-700"><?php echo htmlspecialchars($type); ?></td>
                        <td>
                          <div class="flex items-center gap-2">
                            <i data-lucide="file" class="w-4 h-4 text-blue-600"></i>
                            <div>
                              <div class="font-medium text-gray-900"><?php echo htmlspecialchars($orig); ?></div>
                              <div class="text-xs text-gray-500"><?php echo htmlspecialchars(humanFileSize($size)); ?></div>
                            </div>
                          </div>
                        </td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(safeDateTime($uploadedAt)); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo badgeClassForStatus($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                        </td>
                        <td class="text-right">
                          <div class="flex justify-end gap-2">
                            <button class="btn btn-ghost btn-xs" type="button" data-view-id="<?php echo htmlspecialchars($id); ?>" data-view-name="<?php echo htmlspecialchars($orig); ?>">
                              <i data-lucide="eye" class="w-4 h-4"></i>
                              <span class="hidden sm:inline ml-1">View</span>
                            </button>
                            <a class="btn btn-ghost btn-xs" href="?download=<?php echo urlencode($id); ?>&status=<?php echo urlencode($filter); ?>">
                              <i data-lucide="download" class="w-4 h-4"></i>
                              <span class="hidden sm:inline ml-1">Download</span>
                            </a>

                            <form method="POST" action="<?php echo htmlspecialchars((string)$_SERVER['REQUEST_URI']); ?>" class="inline">
                              <input type="hidden" name="update_status" value="1" />
                              <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>" />
                              <input type="hidden" name="status" value="Approved" />
                              <input type="hidden" name="remarks" value="" />
                              <button class="btn btn-success btn-xs" type="submit">Approved</button>
                            </form>
                            <form method="POST" action="<?php echo htmlspecialchars((string)$_SERVER['REQUEST_URI']); ?>" class="inline">
                              <input type="hidden" name="update_status" value="1" />
                              <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>" />
                              <input type="hidden" name="status" value="Rejected" />
                              <input type="hidden" name="remarks" value="" />
                              <button class="btn btn-error btn-xs" type="submit">Rejected</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
          <?php else: ?>
          <div class="mt-6 card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-center gap-2">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                  </div>
                  <div>
                    <h2 class="font-semibold text-gray-800">Profile Requests</h2>
                    <p class="text-sm text-gray-500">Total: <?php echo (int)count($profileRows); ?></p>
                  </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                  <select id="profileStatusFilter" class="select select-bordered w-full sm:w-56">
                    <option value="all" <?php echo $profileFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $profileFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $profileFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $profileFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th>Requested</th>
                      <th>Status</th>
                      <th>Remarks</th>
                      <th class="text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($profileRows) === 0): ?>
                      <tr>
                        <td colspan="5" class="text-center text-gray-500 py-10">No profile requests found.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($profileRows as $r): ?>
                      <?php
                        $rid = (string)($r['id'] ?? '');
                        $empNo = (string)($r['employee_no'] ?? '');
                        $name = trim(((string)($r['first_name'] ?? '')) . ' ' . ((string)($r['last_name'] ?? '')));
                        $created = (string)($r['created_at'] ?? '');
                        $status = (string)($r['status'] ?? 'Pending');
                        $remarks = (string)($r['remarks'] ?? '');
                        $reasonChoice = (string)($r['reason_choice'] ?? '');
                        $reasonText = (string)($r['reason_text'] ?? '');
                        $proofFilePath = (string)($r['proof_file_path'] ?? '');
                      ?>
                      <tr>
                        <td class="text-gray-800 font-medium"><?php echo htmlspecialchars(($empNo !== '' ? $empNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '')); ?></td>
                        <td class="text-gray-700"><?php echo htmlspecialchars(safeDateTime($created)); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo badgeClassForStatus(strtolower($status) === 'pending' ? 'for approval' : $status); ?>"><?php echo htmlspecialchars($status); ?></span>
                        </td>
                        <td class="text-gray-700"><?php echo htmlspecialchars($remarks); ?></td>
                        <td class="text-right">
                          <div class="flex justify-end gap-2">
                            <button
                              class="btn btn-ghost btn-xs"
                              type="button"
                              data-profile-view-id="<?php echo htmlspecialchars($rid); ?>"
                              data-profile-view-emp="<?php echo htmlspecialchars(($empNo !== '' ? $empNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '')); ?>"
                              data-profile-view-reason="<?php echo htmlspecialchars($reasonChoice); ?>"
                              data-profile-view-reason-text="<?php echo htmlspecialchars($reasonText); ?>"
                              data-profile-view-proof="<?php echo htmlspecialchars($proofFilePath); ?>"
                            >
                              <i data-lucide="eye" class="w-4 h-4"></i>
                              <span class="hidden sm:inline ml-1">View</span>
                            </button>
                            <form method="POST" action="<?php echo htmlspecialchars((string)$_SERVER['REQUEST_URI']); ?>" class="inline">
                              <input type="hidden" name="update_profile_request" value="1" />
                              <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid); ?>" />
                              <input type="hidden" name="status" value="Approved" />
                              <input type="hidden" name="remarks" value="" />
                              <button class="btn btn-success btn-xs" type="submit">Approved</button>
                            </form>
                            <form method="POST" action="<?php echo htmlspecialchars((string)$_SERVER['REQUEST_URI']); ?>" class="inline">
                              <input type="hidden" name="update_profile_request" value="1" />
                              <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid); ?>" />
                              <input type="hidden" name="status" value="Rejected" />
                              <input type="hidden" name="remarks" value="" />
                              <button class="btn btn-error btn-xs" type="submit">Rejected</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>
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

  <dialog id="profileViewModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Profile Request Details</h3>
          <p id="profileViewEmployee" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-xs font-semibold text-gray-500">REASON</div>
            <div id="profileViewReason" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">PROOF</div>
            <div class="mt-1 flex items-center gap-2">
              <a id="profileViewProofDownload" class="btn btn-primary btn-sm" href="#">Download</a>
            </div>
          </div>
        </div>

        <div id="profileProofFallback" class="hidden alert alert-info">
          <i data-lucide="info" class="w-5 h-5"></i>
          <span>This file type can’t be previewed here. Use Download instead.</span>
        </div>

        <img id="profileProofImage" class="hidden w-full h-[55vh] object-contain rounded-lg border border-base-200 bg-white" alt="Proof" />
        <iframe id="profileProofFrame" class="w-full h-[55vh] rounded-lg border border-base-200 bg-white" src="about:blank"></iframe>

        <div>
          <div class="text-xs font-semibold text-gray-500">REASON DETAILS</div>
          <div id="profileViewReasonText" class="mt-1 whitespace-pre-line text-gray-800"></div>
        </div>
      </div>

      <div class="modal-action">
        <form method="dialog"><button class="btn">Close</button></form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="profileUpdateModal" class="modal">
    <div class="modal-box">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Update Profile Request</h3>
          <p id="profileUpdateEmployee" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <form method="POST" class="mt-4 space-y-3">
        <input type="hidden" name="update_profile_request" value="1" />
        <input type="hidden" name="request_id" id="profileRequestId" value="" />

        <div class="form-control">
          <label class="label"><span class="label-text">Status</span></label>
          <select name="status" id="profileRequestStatus" class="select select-bordered" required>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">Remarks</span></label>
          <textarea name="remarks" id="profileRequestRemarks" class="textarea textarea-bordered" placeholder="Optional remarks..."></textarea>
        </div>

        <div class="modal-action">
          <button class="btn btn-primary" type="submit">Save</button>
          <form method="dialog"><button class="btn" type="button">Cancel</button></form>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="updateModal" class="modal">
    <div class="modal-box">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Update Status</h3>
          <p id="updateFileName" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <form method="POST" class="mt-4 space-y-3">
        <input type="hidden" name="update_status" value="1" />
        <input type="hidden" name="id" id="updateId" value="" />

        <div class="form-control">
          <label class="label"><span class="label-text">Status</span></label>
          <select name="status" id="updateStatus" class="select select-bordered" required>
            <option value="For Approval">For Approval</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="For Compliance">For Compliance</option>
          </select>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">Remarks</span></label>
          <textarea name="remarks" id="updateRemarks" class="textarea textarea-bordered" placeholder="Optional remarks..."></textarea>
        </div>

        <div class="modal-action">
          <button class="btn btn-primary" type="submit">Save</button>
          <form method="dialog"><button class="btn" type="button">Cancel</button></form>
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
          opts.img.onerror = function () {
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
          const res = await fetch(viewUrl, { method: 'GET' });
          if (!res.ok) throw new Error('Failed to load text');
          const t = await res.text();
          opts.text.textContent = t;
          return;
        }

        if (isDocx && opts.html && window.mammoth) {
          setHidden(opts.html, false);
          const res = await fetch(viewUrl, { method: 'GET' });
          if (!res.ok) throw new Error('Failed to load DOCX');
          const buf = await res.arrayBuffer();
          const result = await window.mammoth.convertToHtml({ arrayBuffer: buf });
          opts.html.innerHTML = result && result.value ? result.value : '';
          return;
        }

        if (isXlsx && opts.html && window.XLSX) {
          setHidden(opts.html, false);
          const res = await fetch(viewUrl, { method: 'GET' });
          if (!res.ok) throw new Error('Failed to load spreadsheet');
          const buf = await res.arrayBuffer();
          const wb = window.XLSX.read(buf, { type: 'array' });
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

    document.addEventListener('DOMContentLoaded', function () {
      lucide.createIcons();

      const filter = document.getElementById('statusFilter');
      if (filter) {
        filter.addEventListener('change', function () {
          const v = this.value;
          const url = new URL(window.location.href);
          url.searchParams.set('section', 'documents');
          url.searchParams.set('status', v);
          window.location.href = url.toString();
        });
      }

      const pFilter = document.getElementById('profileStatusFilter');
      if (pFilter) {
        pFilter.addEventListener('change', function () {
          const v = this.value;
          const url = new URL(window.location.href);
          url.searchParams.set('section', 'profiles');
          url.searchParams.set('pstatus', v);
          url.searchParams.delete('status');
          window.location.href = url.toString();
        });
      }

      const viewModal = document.getElementById('docViewModal');
      const viewTitle = document.getElementById('docViewTitle');
      const viewFrame = document.getElementById('docViewFrame');
      const viewFallback = document.getElementById('docViewFallback');
      const viewDl = document.getElementById('docViewDownload');
      const viewOpen = document.getElementById('docViewOpen');
      const viewImg = document.getElementById('docViewImage');
      const viewVideo = document.getElementById('docViewVideo');
      const viewAudio = document.getElementById('docViewAudio');
      const viewText = document.getElementById('docViewText');
      const viewHtml = document.getElementById('docViewHtml');

      const docStatusValue = () => {
        if (filter && filter.value) return filter.value;
        const url = new URL(window.location.href);
        return url.searchParams.get('status') || 'all';
      };

      document.querySelectorAll('[data-view-id]').forEach((btn) => {
        btn.addEventListener('click', async function () {
          const id = this.getAttribute('data-view-id') || '';
          const name = this.getAttribute('data-view-name') || 'Document';

          viewTitle.textContent = name;

          const st = docStatusValue();
          const viewUrl = '?view=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(st);
          const dlUrl = '?download=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(st);
          viewDl.setAttribute('href', dlUrl);
          if (viewOpen) viewOpen.setAttribute('href', viewUrl);

          await renderPreview(name, viewUrl, {
            fallback: viewFallback,
            img: viewImg,
            frame: viewFrame,
            video: viewVideo,
            audio: viewAudio,
            text: viewText,
            html: viewHtml,
          });

          if (viewModal && typeof viewModal.showModal === 'function') {
            viewModal.showModal();
          }
        });
      });

      const profileViewModal = document.getElementById('profileViewModal');
      const profileViewEmployee = document.getElementById('profileViewEmployee');
      const profileViewReason = document.getElementById('profileViewReason');
      const profileViewReasonText = document.getElementById('profileViewReasonText');
      const profileViewProofDownload = document.getElementById('profileViewProofDownload');
      const profileProofImage = document.getElementById('profileProofImage');
      const profileProofFrame = document.getElementById('profileProofFrame');
      const profileProofFallback = document.getElementById('profileProofFallback');

      document.querySelectorAll('[data-profile-view-id]').forEach((btn) => {
        btn.addEventListener('click', async function () {
          const id = this.getAttribute('data-profile-view-id') || '';
          if (id === '') return;

          const proofPath = this.getAttribute('data-profile-view-proof') || '';
          const proofName = proofPath ? proofPath.split(/[\\/]/).pop() : '';

          if (profileViewEmployee) profileViewEmployee.textContent = this.getAttribute('data-profile-view-emp') || 'Employee';
          if (profileViewReason) profileViewReason.textContent = this.getAttribute('data-profile-view-reason') || '-';
          if (profileViewReasonText) profileViewReasonText.textContent = this.getAttribute('data-profile-view-reason-text') || '-';

          if (profileViewProofDownload) profileViewProofDownload.setAttribute('href', '?section=profiles&profile_proof_download=' + encodeURIComponent(id) + '&pstatus=<?php echo urlencode($profileFilter); ?>');

          const viewUrl = '?section=profiles&profile_proof_view=' + encodeURIComponent(id) + '&pstatus=<?php echo urlencode($profileFilter); ?>';

          await renderPreview(proofName || 'Proof', viewUrl, {
            fallback: profileProofFallback,
            img: profileProofImage,
            frame: profileProofFrame,
            video: null,
            audio: null,
            text: null,
            html: null,
          });

          if (profileViewModal && typeof profileViewModal.showModal === 'function') {
            profileViewModal.showModal();
          }
        });
      });

      if (profileViewModal) {
        profileViewModal.addEventListener('close', function () {
          if (profileProofFrame) profileProofFrame.setAttribute('src', 'about:blank');
          if (profileProofImage) profileProofImage.setAttribute('src', '');
        });
      }

      viewModal.addEventListener('close', function () {
        resetPreview({
          fallback: viewFallback,
          img: viewImg,
          frame: viewFrame,
          video: viewVideo,
          audio: viewAudio,
          text: viewText,
          html: viewHtml,
        });
      });

      const updateModal = document.getElementById('updateModal');
      const updateId = document.getElementById('updateId');
      const updateStatus = document.getElementById('updateStatus');
      const updateRemarks = document.getElementById('updateRemarks');
      const updateFileName = document.getElementById('updateFileName');

      document.querySelectorAll('[data-update-id]').forEach((btn) => {
        btn.addEventListener('click', function () {
          updateId.value = this.getAttribute('data-update-id') || '';
          updateStatus.value = this.getAttribute('data-update-status') || 'For Approval';
          updateRemarks.value = this.getAttribute('data-update-remarks') || '';
          updateFileName.textContent = this.getAttribute('data-update-file') || '';

          if (updateModal && typeof updateModal.showModal === 'function') {
            updateModal.showModal();
          }
        });
      });

      const profileModal = document.getElementById('profileUpdateModal');
      const profileRequestId = document.getElementById('profileRequestId');
      const profileRequestStatus = document.getElementById('profileRequestStatus');
      const profileRequestRemarks = document.getElementById('profileRequestRemarks');
      const profileUpdateEmployee = document.getElementById('profileUpdateEmployee');

      document.querySelectorAll('[data-profile-update-id]').forEach((btn) => {
        btn.addEventListener('click', function () {
          if (!profileRequestId || !profileRequestStatus || !profileRequestRemarks) return;

          profileRequestId.value = this.getAttribute('data-profile-update-id') || '';
          profileRequestStatus.value = this.getAttribute('data-profile-update-status') || 'Pending';
          profileRequestRemarks.value = this.getAttribute('data-profile-update-remarks') || '';
          if (profileUpdateEmployee) {
            profileUpdateEmployee.textContent = (this.getAttribute('data-profile-update-emp') || 'Employee') + ' - Profile request';
          }

          if (profileModal && typeof profileModal.showModal === 'function') {
            profileModal.showModal();
          }
        });
      });
    });
  </script>
</body>
</html>
