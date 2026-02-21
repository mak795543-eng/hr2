<?php


$isBinaryView = isset($_GET['view'])
  || isset($_GET['download'])
  || isset($_GET['profile_proof_view'])
  || isset($_GET['profile_proof_download'])
  || isset($_GET['complaint_attachment_view'])
  || isset($_GET['complaint_attachment_download']);

if ($isBinaryView && !defined('SUPPRESS_DB_ERRORS')) {
  define('SUPPRESS_DB_ERRORS', true);
}
require __DIR__ . '/db.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint_workflow'])) {
  $cid = (int)($_POST['complaint_id'] ?? 0);
  $action = trim((string)($_POST['action'] ?? ''));
  $reason = trim((string)($_POST['reason'] ?? ''));
  $assignRole = trim((string)($_POST['assign_role'] ?? ''));
  $assignTo = trim((string)($_POST['assign_to_employee_no'] ?? ''));

  if ($cid <= 0 || !in_array($action, ['accept', 'return', 'assign'], true)) {
    $error_message = 'Invalid complaint request.';
  } elseif (!$conn) {
    $error_message = 'Database connection unavailable.';
  } elseif ($action === 'return' && $reason === '') {
    $error_message = 'Clarification/return remarks are required.';
  } elseif ($action === 'assign' && ($assignRole === '' || $assignTo === '')) {
    $error_message = 'Please choose an assignee.';
  } else {
    $actorId = ess_employee_id($conn);
    $actor = is_int($actorId) ? $actorId : 0;
    $now = date('Y-m-d H:i:s');

    if ($action === 'accept') {
      $stmt = mysqli_prepare($conn, "UPDATE complaints SET workflow_status = 'Under Review', accepted_by = ?, accepted_at = ?, returned_reason = NULL, seen_by_employee = 0 WHERE id = ?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isi', $actor, $now, $cid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) $success_message = 'Complaint accepted.';
        else $error_message = 'Failed to update complaint.';
      } else {
        $error_message = 'Failed to update complaint.';
      }
    }

    if ($action === 'return' && $error_message === '') {
      $stmt = mysqli_prepare($conn, "UPDATE complaints SET workflow_status = 'Returned', returned_reason = ?, accepted_by = ?, accepted_at = ?, assigned_role = NULL, assigned_to_employee_no = NULL, assigned_at = NULL, seen_by_employee = 0 WHERE id = ?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sisi', $reason, $actor, $now, $cid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) $success_message = 'Complaint returned.';
        else $error_message = 'Failed to update complaint.';
      } else {
        $error_message = 'Failed to update complaint.';
      }
    }

    if ($action === 'assign' && $error_message === '') {
      $stmt = mysqli_prepare($conn, "UPDATE complaints SET workflow_status = 'Assigned', assigned_role = ?, assigned_to_employee_no = ?, assigned_at = ?, seen_by_assignee = 0, seen_by_employee = 0 WHERE id = ?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssi', $assignRole, $assignTo, $now, $cid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
          $assigneeId = 0;
          $stmtA = mysqli_prepare($conn, 'SELECT id FROM employees WHERE employee_no = ? LIMIT 1');
          if ($stmtA) {
            mysqli_stmt_bind_param($stmtA, 's', $assignTo);
            mysqli_stmt_execute($stmtA);
            $resA = mysqli_stmt_get_result($stmtA);
            $rowA = $resA ? mysqli_fetch_assoc($resA) : null;
            mysqli_stmt_close($stmtA);
            if (is_array($rowA)) {
              $assigneeId = (int)($rowA['id'] ?? 0);
            }
          }

          if ($assigneeId > 0) {
            $notifKey = sha1('complaint_assigned|' . $cid . '|' . $assignTo . '|' . $now);
            $notifType = 'Complaint';
            $notifTitle = 'Complaint Assigned';
            $notifMeta = 'A complaint has been assigned to you. Complaint ID: ' . $cid . '.';
            $notifLink = 'dashboard.php';
            $notifDate = $now;

            $stmtNotif = mysqli_prepare(
              $conn,
              "INSERT INTO notification_states (employee_id, notif_key, status, deleted, notif_type, notif_title, notif_meta, notif_link, notif_date)
                             VALUES (?, ?, 'unread', 0, ?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                               status = 'unread',
                               deleted = 0,
                               notif_type = VALUES(notif_type),
                               notif_title = VALUES(notif_title),
                               notif_meta = VALUES(notif_meta),
                               notif_link = VALUES(notif_link),
                               notif_date = VALUES(notif_date),
                               updated_at = CURRENT_TIMESTAMP"
            );
            if ($stmtNotif) {
              mysqli_stmt_bind_param($stmtNotif, 'issssss', $assigneeId, $notifKey, $notifType, $notifTitle, $notifMeta, $notifLink, $notifDate);
              @mysqli_stmt_execute($stmtNotif);
              mysqli_stmt_close($stmtNotif);
            }
          }

          $success_message = 'Complaint assigned.';
        } else {
          $error_message = 'Failed to assign complaint.';
        }
      } else {
        $error_message = 'Failed to assign complaint.';
      }
    }
  }

  if ($error_message === '' && $success_message !== '') {
    $redirect = (string)($_SERVER['PHP_SELF']);
    $curCstatus = (string)($_POST['redirect_cstatus'] ?? 'all');
    $qs = http_build_query(['section' => 'complaints', 'cstatus' => $curCstatus]);
    header('Location: ' . $redirect . '?' . $qs);
    exit;
  }
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

$profileProofDir = $uploadDir . DIRECTORY_SEPARATOR . 'profile_request_proofs';
if (!is_dir($profileProofDir)) {
  @mkdir($profileProofDir, 0775, true);
}

$complaintUploadDir = $uploadDir . DIRECTORY_SEPARATOR . 'complaints';
if (!is_dir($complaintUploadDir)) {
  @mkdir($complaintUploadDir, 0775, true);
}



function ess_profile_status_to_db(string $uiStatus, string $remarks): array
{
  $ui = strtolower(trim($uiStatus));
  $r = trim($remarks);

  if ($ui === 'for compliance') {
    $r = preg_replace('/^\[COMPLIANCE\]\s*/', '', $r);
    $r = '[COMPLIANCE] ' . $r;
    return ['Pending', trim($r)];
  }

  if ($ui === 'for approval' || $ui === 'pending') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_request'])) {
  $rid = (int)($_POST['request_id'] ?? 0);
  $uiStatus = trim((string)($_POST['status'] ?? ''));
  $remarks = trim((string)($_POST['remarks'] ?? ''));

  $isAjax = (string)($_POST['ajax'] ?? '') === '1';

  $allowedUi = ['Approved', 'Rejected', 'Pending', 'For Compliance', 'For Approval'];
  if ($rid <= 0 || !in_array($uiStatus, $allowedUi, true)) {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Invalid request.']);
      exit;
    }
    $error_message = 'Invalid request.';
  } elseif (strtolower(trim($uiStatus)) === 'rejected' && $remarks === '') {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);
      exit;
    }
    $error_message = 'Rejection reason is required.';
  } elseif (strtolower(trim($uiStatus)) === 'for compliance' && $remarks === '') {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Compliance remarks are required.']);
      exit;
    }
    $error_message = 'Compliance remarks are required.';
  } elseif (!$conn) {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
      exit;
    }
    $error_message = 'Database connection unavailable.';
  } else {
    [$newStatus, $remarks] = ess_profile_status_to_db($uiStatus, $remarks);
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

        if ($isAjax) {
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['success' => true, 'message' => 'Profile request updated successfully.']);
          exit;
        }

        $success_message = 'Profile request updated successfully.';

        $redirect = (string)($_SERVER['PHP_SELF']);
        $curSection = (string)($_POST['redirect_section'] ?? 'profiles');
        $curSection = in_array($curSection, ['documents', 'profiles'], true) ? $curSection : 'profiles';
        $curPstatus = (string)($_POST['redirect_pstatus'] ?? 'pending');
        $qs = http_build_query(['section' => $curSection, 'pstatus' => $curPstatus]);
        header('Location: ' . $redirect . '?' . $qs);
        exit;
      }
    }
  }
}

$section = (string)($_GET['section'] ?? 'documents');
$section = in_array($section, ['documents', 'profiles', 'complaints'], true) ? $section : 'documents';

function ess_is_compliance(string $remarks): bool
{
  return str_starts_with(trim($remarks), '[COMPLIANCE]');
}

function ess_status_label(string $status, string $remarks): string
{
  $s = strtolower(trim($status));
  if ($s === 'pending' && ess_is_compliance($remarks)) return 'For Compliance';
  if ($s === 'pending') return 'For Approval';
  if ($s === 'approved') return 'Approved';
  if ($s === 'rejected') return 'Rejected';
  return $status;
}

function ess_status_to_db(string $uiStatus, string $remarks): array
{
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

function ess_resolve_file_path(string $filePath, string $baseDir): ?string
{
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

function ess_guess_mime_type(string $path): string
{
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

function badgeClassForStatus($status)
{
  $s = strtolower(trim((string)$status));
  return match ($s) {
    'approved' => 'badge-success',
    'rejected' => 'badge-error',
    'for compliance' => 'badge-info',
    'for approval' => 'badge-warning',
    default => 'badge-ghost',
  };
}

function ess_complaint_badge_class(string $status): string
{
  $s = strtolower(trim($status));
  return match ($s) {
    'for approval' => 'badge-warning',
    'under review' => 'badge-info',
    'returned' => 'badge-error',
    'assigned' => 'badge-success',
    default => 'badge-ghost',
  };
}

function ess_usm_conn(): ?mysqli
{
  if (!defined('SUPPRESS_DB_ERRORS')) {
    define('SUPPRESS_DB_ERRORS', true);
  }

  $rootDb = __DIR__ . '/../db.php';
  if (is_file($rootDb)) {
    require_once $rootDb;
  }

  if (isset($connections['hr2usm']) && $connections['hr2usm'] instanceof mysqli) {
    return $connections['hr2usm'];
  }
  if (isset($connections['hr2_usm']) && $connections['hr2_usm'] instanceof mysqli) {
    return $connections['hr2_usm'];
  }
  return null;
}

function safeDateTime($iso)
{
  $t = strtotime((string)$iso);
  if ($t === false) return (string)$iso;
  return date('M d, Y g:i A', $t);
}

function humanFileSize($bytes)
{
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
$profileAllowed = ['all', 'pending', 'approved', 'rejected', 'for compliance'];
$profileFilter = in_array(strtolower($profileFilter), $profileAllowed, true) ? strtolower($profileFilter) : 'all';

$complaintFilter = (string)($_GET['cstatus'] ?? '');
$hasExplicitComplaintFilter = array_key_exists('cstatus', $_GET);
$complaintFilter = $hasExplicitComplaintFilter ? $complaintFilter : (($section === 'complaints') ? 'for approval' : 'all');
$complaintAllowed = ['all', 'for approval', 'under review', 'returned', 'assigned'];
$complaintFilter = in_array(strtolower($complaintFilter), $complaintAllowed, true) ? strtolower($complaintFilter) : 'all';

$viewParam = (string)($_GET['view'] ?? '');
$downloadParam = (string)($_GET['download'] ?? '');

$profileProofViewParam = (string)($_GET['profile_proof_view'] ?? '');
$profileProofDownloadParam = (string)($_GET['profile_proof_download'] ?? '');

$complaintAttachmentViewParam = (string)($_GET['complaint_attachment_view'] ?? '');
$complaintAttachmentDownloadParam = (string)($_GET['complaint_attachment_download'] ?? '');

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

if ($section === 'complaints' && $complaintAttachmentDownloadParam !== '') {
  if ($conn) {
    $id = (int)$complaintAttachmentDownloadParam;
    $stmt = mysqli_prepare($conn, 'SELECT attachment_path FROM complaints WHERE id = ? LIMIT 1');
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'i', $id);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $target = $res ? mysqli_fetch_assoc($res) : null;
      mysqli_stmt_close($stmt);

      if (is_array($target)) {
        $filePath = (string)($target['attachment_path'] ?? '');
        $path = ess_resolve_file_path($filePath, $complaintUploadDir);
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

if ($section === 'complaints' && $complaintAttachmentViewParam !== '') {
  if ($conn) {
    $id = (int)$complaintAttachmentViewParam;
    $stmt = mysqli_prepare($conn, 'SELECT attachment_path FROM complaints WHERE id = ? LIMIT 1');
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'i', $id);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $target = $res ? mysqli_fetch_assoc($res) : null;
      mysqli_stmt_close($stmt);

      if (is_array($target)) {
        $filePath = (string)($target['attachment_path'] ?? '');
        $path = ess_resolve_file_path($filePath, $complaintUploadDir);
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
    $where = "WHERE r.status = 'Pending' AND (r.remarks IS NULL OR r.remarks NOT LIKE '[COMPLIANCE]%')";
  } elseif ($profileFilter === 'for compliance') {
    $where = "WHERE r.status = 'Pending' AND r.remarks LIKE '[COMPLIANCE]%'";
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

$complaintRows = [];
if ($conn) {
  $where = '';
  if ($complaintFilter === 'for approval') {
    $where = "WHERE c.workflow_status = 'For Approval'";
  } elseif ($complaintFilter === 'under review') {
    $where = "WHERE c.workflow_status = 'Under Review'";
  } elseif ($complaintFilter === 'returned') {
    $where = "WHERE c.workflow_status = 'Returned'";
  } elseif ($complaintFilter === 'assigned') {
    $where = "WHERE c.workflow_status = 'Assigned'";
  }

  $sql = "SELECT c.id, c.employee_id, e.employee_no, e.first_name, e.last_name, c.subject, c.description, c.category, c.category_other, c.incident_date, c.attachment_path, c.workflow_status, c.returned_reason, c.created_at, c.accepted_at, c.assigned_role, c.assigned_to_employee_no, c.assigned_at, c.meeting_date, c.meeting_place FROM complaints c LEFT JOIN employees e ON e.id = c.employee_id {$where} ORDER BY c.created_at DESC";
  $res = mysqli_query($conn, $sql);
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $complaintRows[] = $row;
    }
  }
}

$assignOptions = ['Department Head' => [], 'Supervisor / Manager' => []];
try {
  $uconn = ess_usm_conn();
  if ($uconn) {
    $res = $uconn->query("SELECT employee_id, employee_name, role, dept_name, status FROM department_accounts WHERE status = 'active' ORDER BY dept_name ASC, employee_name ASC");
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $role = strtolower(trim((string)($r['role'] ?? '')));
        $empNo = trim((string)($r['employee_id'] ?? ''));
        $label = trim((string)($r['employee_name'] ?? ''));
        $dept = trim((string)($r['dept_name'] ?? ''));

        if ($empNo === '' || $label === '') {
          continue;
        }
        if ($dept !== '') {
          $label .= ' - ' . $dept;
        }

        if ($role === 'manager') {
          $assignOptions['Department Head'][] = ['employee_no' => $empNo, 'label' => $label];
          $assignOptions['Supervisor / Manager'][] = ['employee_no' => $empNo, 'label' => $label];
        }
        if ($role === 'supervisor' || $role === 'hr_manager') {
          $assignOptions['Supervisor / Manager'][] = ['employee_no' => $empNo, 'label' => $label];
        }
      }
    }
  }
} catch (Throwable $e) {
}
require('../partials/header.php');
?>

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
            </div>
          </div>

          <div class="mt-6">
            <div class="tabs tabs-boxed">
              <a class="tab <?php echo $section === 'documents' ? 'tab-active' : ''; ?>" href="?section=documents&status=<?php echo urlencode($filter); ?>">Document Submissions</a>
              <a class="tab <?php echo $section === 'profiles' ? 'tab-active' : ''; ?>" href="?section=profiles&pstatus=<?php echo urlencode($profileFilter); ?>">Profile Requests</a>
              <a class="tab <?php echo $section === 'complaints' ? 'tab-active' : ''; ?>" href="?section=complaints&cstatus=<?php echo urlencode($complaintFilter); ?>">Complaints</a>
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
          <?php elseif ($section === 'profiles'): ?>
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
                      <option value="pending" <?php echo $profileFilter === 'pending' ? 'selected' : ''; ?>>For Approval</option>
                      <option value="for compliance" <?php echo $profileFilter === 'for compliance' ? 'selected' : ''; ?>>For Compliance</option>
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
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($profileRows) === 0): ?>
                        <tr>
                          <td colspan="4" class="text-center text-gray-500 py-10">No profile requests found.</td>
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
                        $uiStatus = ess_status_label($status, $remarks);
                        $isFinalProfileStatus = in_array(strtolower(trim($uiStatus)), ['approved', 'rejected', 'for compliance'], true);
                        ?>
                        <tr data-profile-row="<?php echo htmlspecialchars($rid); ?>">
                          <td class="text-gray-800 font-medium"><?php echo htmlspecialchars(($empNo !== '' ? $empNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '')); ?></td>
                          <td class="text-gray-700"><?php echo htmlspecialchars(safeDateTime($created)); ?></td>
                          <td>
                            <span class="badge badge-sm <?php echo badgeClassForStatus($uiStatus); ?>"><?php echo htmlspecialchars($uiStatus); ?></span>
                          </td>
                          <td class="text-right">
                            <div class="flex justify-end gap-2">
                              <button
                                class="btn btn-ghost btn-xs"
                                type="button"
                                data-profile-view-id="<?php echo htmlspecialchars($rid); ?>"
                                data-profile-view-emp="<?php echo htmlspecialchars(($empNo !== '' ? $empNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '')); ?>"
                                data-profile-view-reason="<?php echo htmlspecialchars($reasonChoice); ?>"
                                data-profile-view-reason-text="<?php echo htmlspecialchars($reasonText); ?>"
                                data-profile-view-proof="<?php echo htmlspecialchars($proofFilePath); ?>">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="hidden sm:inline ml-1">View</span>
                              </button>
                              <?php if (!$isFinalProfileStatus): ?>
                                <button class="btn btn-success btn-xs" type="button" data-profile-action="approve" data-profile-id="<?php echo htmlspecialchars($rid); ?>">Approve</button>
                                <button class="btn btn-error btn-xs" type="button" data-profile-action="reject" data-profile-id="<?php echo htmlspecialchars($rid); ?>">Reject</button>
                                <button class="btn btn-info btn-xs" type="button" data-profile-action="compliance" data-profile-id="<?php echo htmlspecialchars($rid); ?>">For Compliance</button>
                              <?php endif; ?>
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
                      <i data-lucide="message-square-warning" class="w-5 h-5"></i>
                    </div>
                    <div>
                      <h2 class="font-semibold text-gray-800">Complaints</h2>
                      <p class="text-sm text-gray-500">Total: <?php echo (int)count($complaintRows); ?></p>
                    </div>
                  </div>

                  <div class="flex flex-col sm:flex-row gap-2">
                    <select id="complaintStatusFilter" class="select select-bordered w-full sm:w-56">
                      <option value="all" <?php echo $complaintFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                      <option value="for approval" <?php echo $complaintFilter === 'for approval' ? 'selected' : ''; ?>>For Approval</option>
                      <option value="under review" <?php echo $complaintFilter === 'under review' ? 'selected' : ''; ?>>Under Review</option>
                      <option value="returned" <?php echo $complaintFilter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                      <option value="assigned" <?php echo $complaintFilter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                    </select>
                  </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Category</th>
                        <th>Incident Date</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Attachment</th>
                        <th class="text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($complaintRows) === 0): ?>
                        <tr>
                          <td colspan="7" class="text-center text-gray-500 py-10">No complaints found.</td>
                        </tr>
                      <?php endif; ?>

                      <?php foreach ($complaintRows as $r): ?>
                        <?php
                        $cid = (string)($r['id'] ?? '');
                        $empNo = (string)($r['employee_no'] ?? '');
                        $name = trim(((string)($r['first_name'] ?? '')) . ' ' . ((string)($r['last_name'] ?? '')));
                        $subject = (string)($r['subject'] ?? '');
                        $desc = (string)($r['description'] ?? '');
                        $cat = (string)($r['category'] ?? '');
                        $catOther = (string)($r['category_other'] ?? '');
                        $incidentDate = (string)($r['incident_date'] ?? '');
                        $created = (string)($r['created_at'] ?? '');
                        $wfStatus = (string)($r['workflow_status'] ?? 'For Approval');
                        $wfLower = strtolower(trim($wfStatus));
                        $returnedReason = (string)($r['returned_reason'] ?? '');
                        $meetingDate = (string)($r['meeting_date'] ?? '');
                        $meetingPlace = (string)($r['meeting_place'] ?? '');
                        $attachmentPath = (string)($r['attachment_path'] ?? '');
                        $categoryDisplay = $cat;
                        $catLower = strtolower(trim($cat));
                        if (in_array($catLower, ['other', 'others'], true) && $catOther !== '') {
                          $categoryDisplay = 'Other - ' . $catOther;
                        }
                        $employeeLabel = ($empNo !== '' ? $empNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '');
                        $hasAttachment = trim($attachmentPath) !== '';
                        $canAccept = $wfLower === 'for approval';
                        $canReturn = in_array($wfLower, ['for approval', 'under review'], true);
                        $canAssign = $wfLower === 'under review';
                        ?>
                        <tr>
                          <td class="text-gray-800 font-medium"><?php echo htmlspecialchars($employeeLabel); ?></td>
                          <td class="text-gray-700"><?php echo htmlspecialchars($categoryDisplay); ?></td>
                          <td class="text-gray-700"><?php echo htmlspecialchars($incidentDate); ?></td>
                          <td class="text-gray-700"><?php echo htmlspecialchars(safeDateTime($created)); ?></td>
                          <td>
                            <span class="badge badge-sm <?php echo ess_complaint_badge_class($wfStatus); ?>"><?php echo htmlspecialchars($wfStatus); ?></span>
                          </td>
                          <td>
                            <?php if ($hasAttachment): ?>
                              <div class="flex items-center gap-2">
                                <a class="btn btn-ghost btn-xs" href="?section=complaints&complaint_attachment_view=<?php echo urlencode($cid); ?>&cstatus=<?php echo urlencode($complaintFilter); ?>" target="_blank" rel="noopener">Open</a>
                                <a class="btn btn-ghost btn-xs" href="?section=complaints&complaint_attachment_download=<?php echo urlencode($cid); ?>&cstatus=<?php echo urlencode($complaintFilter); ?>">Download</a>
                              </div>
                            <?php else: ?>
                              <span class="text-gray-500">N/A</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-right">
                            <div class="flex justify-end gap-2">
                              <button
                                class="btn btn-ghost btn-xs"
                                type="button"
                                data-complaint-view-id="<?php echo htmlspecialchars($cid); ?>"
                                data-complaint-view-employee="<?php echo htmlspecialchars($employeeLabel); ?>"
                                data-complaint-view-subject="<?php echo htmlspecialchars($subject); ?>"
                                data-complaint-view-category="<?php echo htmlspecialchars($categoryDisplay); ?>"
                                data-complaint-view-incident="<?php echo htmlspecialchars($incidentDate); ?>"
                                data-complaint-view-status="<?php echo htmlspecialchars($wfStatus); ?>"
                                data-complaint-view-returned="<?php echo htmlspecialchars($returnedReason); ?>"
                                data-complaint-view-desc="<?php echo htmlspecialchars($desc); ?>"
                                data-complaint-view-has-attachment="<?php echo $hasAttachment ? '1' : '0'; ?>"
                                data-complaint-view-attachment-path="<?php echo htmlspecialchars($attachmentPath); ?>"
                                data-complaint-view-meeting-date="<?php echo htmlspecialchars($meetingDate); ?>"
                                data-complaint-view-meeting-place="<?php echo htmlspecialchars($meetingPlace); ?>">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="hidden sm:inline ml-1">View</span>
                              </button>

                              <?php if ($canAccept): ?>
                                <form method="POST" action="<?php echo htmlspecialchars((string)$_SERVER['REQUEST_URI']); ?>" class="inline">
                                  <input type="hidden" name="update_complaint_workflow" value="1" />
                                  <input type="hidden" name="action" value="accept" />
                                  <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($cid); ?>" />
                                  <input type="hidden" name="redirect_cstatus" value="<?php echo htmlspecialchars($complaintFilter); ?>" />
                                  <button class="btn btn-success btn-xs" type="submit">Accept</button>
                                </form>
                              <?php endif; ?>

                              <?php if ($canReturn): ?>
                                <button class="btn btn-error btn-xs" type="button" data-complaint-return-id="<?php echo htmlspecialchars($cid); ?>" data-complaint-return-employee="<?php echo htmlspecialchars($employeeLabel); ?>">Return</button>
                              <?php endif; ?>

                              <?php if ($canAssign): ?>
                                <button class="btn btn-primary btn-xs" type="button" data-complaint-assign-id="<?php echo htmlspecialchars($cid); ?>" data-complaint-assign-employee="<?php echo htmlspecialchars($employeeLabel); ?>">Assign</button>
                              <?php endif; ?>
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

  <dialog id="complaintViewModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Complaint Details</h3>
          <p id="complaintViewEmployee" class="text-sm text-gray-500"></p>
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
            <div class="text-xs font-semibold text-gray-500">SUBJECT</div>
            <div id="complaintViewSubject" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">STATUS</div>
            <div id="complaintViewStatus" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">CATEGORY</div>
            <div id="complaintViewCategory" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">INCIDENT DATE</div>
            <div id="complaintViewIncident" class="mt-1 text-gray-800"></div>
          </div>
        </div>

        <div id="complaintViewReturnedWrap" class="hidden">
          <div class="text-xs font-semibold text-gray-500">RETURNED REASON</div>
          <div id="complaintViewReturned" class="mt-1 whitespace-pre-line text-gray-800"></div>
        </div>

        <div>
          <div class="text-xs font-semibold text-gray-500">DETAILS</div>
          <div id="complaintViewDesc" class="mt-1 whitespace-pre-line text-gray-800"></div>
        </div>

        <div>
          <div class="text-xs font-semibold text-gray-500">ATTACHMENT</div>
          <div class="mt-2">
            <img id="complaintViewAttachmentImg" class="hidden w-full max-h-[320px] object-contain rounded-lg border border-base-200 bg-white" alt="Attachment" />
            <div id="complaintViewAttachmentNone" class="text-sm text-gray-500">No attachment.</div>
            <div class="mt-2 flex items-center gap-2" id="complaintViewAttachmentActions">
              <a id="complaintViewAttachmentDownload" class="btn btn-sm hr2-outline-btn" href="#">Download</a>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <form method="dialog"><button class="btn">Close</button></form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="complaintReturnModal" class="modal">
    <div class="modal-box">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Return Complaint</h3>
          <p id="complaintReturnEmployee" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <form method="POST" class="mt-4 space-y-3">
        <input type="hidden" name="update_complaint_workflow" value="1" />
        <input type="hidden" name="action" value="return" />
        <input type="hidden" name="complaint_id" id="complaintReturnId" value="" />
        <input type="hidden" name="redirect_cstatus" id="complaintReturnRedirect" value="" />

        <div class="form-control">
          <label class="label"><span class="label-text">Remarks</span></label>
          <textarea name="reason" id="complaintReturnReason" class="textarea textarea-bordered" placeholder="Enter clarification/return remarks..." required></textarea>
        </div>

        <div class="modal-action">
          <button class="btn btn-error" type="submit">Return</button>
          <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="complaintAssignModal" class="modal">
    <div class="modal-box">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Assign Complaint</h3>
          <p id="complaintAssignEmployee" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <form method="POST" class="mt-4 space-y-3">
        <input type="hidden" name="update_complaint_workflow" value="1" />
        <input type="hidden" name="action" value="assign" />
        <input type="hidden" name="complaint_id" id="complaintAssignId" value="" />
        <input type="hidden" name="redirect_cstatus" id="complaintAssignRedirect" value="" />

        <div class="form-control">
          <label class="label"><span class="label-text">Role</span></label>
          <select name="assign_role" id="complaintAssignRole" class="select select-bordered" required>
            <option value="">Select role</option>
            <option value="Department Head">Department Head</option>
            <option value="Supervisor / Manager">Supervisor / Manager</option>
          </select>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">Assignee</span></label>
          <select name="assign_to_employee_no" id="complaintAssignTo" class="select select-bordered" required>
            <option value="">Select assignee</option>
          </select>
        </div>

        <div class="modal-action">
          <button class="btn btn-primary" type="submit">Assign</button>
          <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
        </div>
      </form>
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
              <a id="profileViewProofOpen" class="btn btn-outline btn-sm" href="#" target="_blank" rel="noopener">Open</a>
              <a id="profileViewProofDownload" class="btn btn-primary btn-sm" href="#">Download</a>
            </div>
          </div>
        </div>

        <div id="profileProofFallback" class="hidden alert alert-info">
          <i data-lucide="info" class="w-5 h-5"></i>
          <span>Preview is not available for this file type. Use Download instead.</span>
        </div>

        <img id="profileProofImage" class="hidden w-full h-[55vh] object-contain rounded-lg border border-base-200 bg-white" alt="Proof" />
        <iframe id="profileProofFrame" class="hidden w-full h-[55vh] rounded-lg border border-base-200 bg-white" src="about:blank"></iframe>
        <video id="profileProofVideo" class="hidden w-full h-[55vh] rounded-lg border border-base-200 bg-black" controls></video>
        <audio id="profileProofAudio" class="hidden w-full mt-2" controls></audio>
        <pre id="profileProofText" class="hidden w-full h-[55vh] overflow-auto rounded-lg border border-base-200 bg-white p-4 text-sm"></pre>
        <div id="profileProofHtml" class="hidden w-full h-[55vh] overflow-auto rounded-lg border border-base-200 bg-white p-4 prose max-w-none"></div>

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

  <dialog id="profileActionModal" class="modal">
    <div class="modal-box">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 id="profileActionTitle" class="font-bold text-lg">Update Profile Request</h3>
          <p id="profileActionEmployee" class="text-sm text-gray-500"></p>
        </div>
        <form method="dialog">
          <button class="btn btn-sm btn-ghost" aria-label="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </form>
      </div>

      <div class="mt-4 space-y-3">
        <div class="form-control">
          <label class="label"><span id="profileActionLabel" class="label-text">Remarks</span></label>
          <textarea id="profileActionRemarks" class="textarea textarea-bordered" placeholder="Enter remarks..."></textarea>
          <div id="profileActionHint" class="text-xs text-gray-500 mt-2"></div>
        </div>

        <div class="modal-action">
          <button id="profileActionSubmit" class="btn btn-primary" type="button">Submit</button>
          <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
        </div>
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
          <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
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
          <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>
  <?php require('../partials/footer.php') ?>
  <script>
    const complaintAssignOptions = <?php echo json_encode($assignOptions, JSON_UNESCAPED_SLASHES); ?>;

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
      let isImage = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'jfif'].includes(ext);
      let isPdf = ext === 'pdf';
      let isText = ['txt', 'csv'].includes(ext);
      let isVideo = ['mp4', 'webm'].includes(ext);
      let isAudio = ['mp3', 'wav', 'ogg'].includes(ext);
      let isDocx = ext === 'docx';
      let isXlsx = ext === 'xlsx' || ext === 'xls';

      try {
        if (!isImage && !isPdf && !isText && !isVideo && !isAudio && !isDocx && !isXlsx) {
          try {
            const head = await fetch(viewUrl, {
              method: 'HEAD'
            });
            const ct = (head && head.ok) ? String(head.headers.get('content-type') || '').toLowerCase() : '';
            if (ct.startsWith('image/')) isImage = true;
            else if (ct.includes('pdf')) isPdf = true;
            else if (ct.startsWith('video/')) isVideo = true;
            else if (ct.startsWith('audio/')) isAudio = true;
            else if (ct.startsWith('text/')) isText = true;
            else if (ct.includes('spreadsheet') || ct.includes('excel')) isXlsx = true;
            else if (ct.includes('word') || ct.includes('officedocument.wordprocessingml')) isDocx = true;
          } catch (e0) {}
        }

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

      const filter = document.getElementById('statusFilter');
      if (filter) {
        filter.addEventListener('change', function() {
          const v = this.value;
          const url = new URL(window.location.href);
          url.searchParams.set('section', 'documents');
          url.searchParams.set('status', v);
          window.location.href = url.toString();
        });
      }

      const pFilter = document.getElementById('profileStatusFilter');
      if (pFilter) {
        pFilter.addEventListener('change', function() {
          const v = this.value;
          const url = new URL(window.location.href);
          url.searchParams.set('section', 'profiles');
          url.searchParams.set('pstatus', v);
          url.searchParams.delete('status');
          url.searchParams.delete('cstatus');
          window.location.href = url.toString();
        });
      }

      const cFilter = document.getElementById('complaintStatusFilter');
      if (cFilter) {
        cFilter.addEventListener('change', function() {
          const v = this.value;
          const url = new URL(window.location.href);
          url.searchParams.set('section', 'complaints');
          url.searchParams.set('cstatus', v);
          url.searchParams.delete('status');
          url.searchParams.delete('pstatus');
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
        btn.addEventListener('click', async function() {
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
      const profileViewProofOpen = document.getElementById('profileViewProofOpen');
      const profileProofImage = document.getElementById('profileProofImage');
      const profileProofFrame = document.getElementById('profileProofFrame');
      const profileProofFallback = document.getElementById('profileProofFallback');
      const profileProofVideo = document.getElementById('profileProofVideo');
      const profileProofAudio = document.getElementById('profileProofAudio');
      const profileProofText = document.getElementById('profileProofText');
      const profileProofHtml = document.getElementById('profileProofHtml');

      document.querySelectorAll('[data-profile-view-id]').forEach((btn) => {
        btn.addEventListener('click', async function() {
          const id = this.getAttribute('data-profile-view-id') || '';
          if (id === '') return;

          const proofPath = this.getAttribute('data-profile-view-proof') || '';
          const proofName = proofPath ? proofPath.split(/[\\/]/).pop() : 'Proof';

          if (profileViewEmployee) profileViewEmployee.textContent = this.getAttribute('data-profile-view-emp') || 'Employee';
          if (profileViewReason) profileViewReason.textContent = this.getAttribute('data-profile-view-reason') || '-';
          if (profileViewReasonText) profileViewReasonText.textContent = this.getAttribute('data-profile-view-reason-text') || '-';

          const dlUrl = '?section=profiles&profile_proof_download=' + encodeURIComponent(id) + '&pstatus=<?php echo urlencode($profileFilter); ?>';
          const viewUrl = '?section=profiles&profile_proof_view=' + encodeURIComponent(id) + '&pstatus=<?php echo urlencode($profileFilter); ?>';

          let previewUrl = viewUrl;
          try {
            const p = String(proofPath || '').trim();
            const isAbsoluteDisk = /^[A-Za-z]:\\/.test(p);
            const isAbsoluteUrl = /^https?:\/\//i.test(p);
            const isAbsPath = p.startsWith('/') || p.startsWith('\\');
            if (p !== '' && !isAbsoluteDisk && !isAbsoluteUrl && !isAbsPath) {
              previewUrl = p + (p.includes('?') ? '&' : '?') + '_ts=' + Date.now();
            }
          } catch (e2) {}

          if (profileViewProofDownload) profileViewProofDownload.setAttribute('href', dlUrl);
          if (profileViewProofOpen) profileViewProofOpen.setAttribute('href', viewUrl);

          await renderPreview(proofName || 'Proof', previewUrl, {
            fallback: profileProofFallback,
            img: profileProofImage,
            frame: profileProofFrame,
            video: profileProofVideo,
            audio: profileProofAudio,
            text: profileProofText,
            html: profileProofHtml,
          });

          if (profileViewModal && typeof profileViewModal.showModal === 'function') {
            profileViewModal.showModal();
          } else if (profileViewModal) {
            profileViewModal.setAttribute('open', '');
          }
        });
      });

      if (profileViewModal) {
        profileViewModal.addEventListener('close', function() {
          resetPreview({
            fallback: profileProofFallback,
            img: profileProofImage,
            frame: profileProofFrame,
            video: profileProofVideo,
            audio: profileProofAudio,
            text: profileProofText,
            html: profileProofHtml,
          });
        });
      }

      const complaintViewModal = document.getElementById('complaintViewModal');
      const complaintViewEmployee = document.getElementById('complaintViewEmployee');
      const complaintViewSubject = document.getElementById('complaintViewSubject');
      const complaintViewStatus = document.getElementById('complaintViewStatus');
      const complaintViewCategory = document.getElementById('complaintViewCategory');
      const complaintViewIncident = document.getElementById('complaintViewIncident');
      const complaintViewDesc = document.getElementById('complaintViewDesc');
      const complaintViewReturnedWrap = document.getElementById('complaintViewReturnedWrap');
      const complaintViewReturned = document.getElementById('complaintViewReturned');
      const complaintViewAttachmentImg = document.getElementById('complaintViewAttachmentImg');
      const complaintViewAttachmentNone = document.getElementById('complaintViewAttachmentNone');
      const complaintViewAttachmentActions = document.getElementById('complaintViewAttachmentActions');
      const complaintViewAttachmentDownload = document.getElementById('complaintViewAttachmentDownload');

      document.querySelectorAll('[data-complaint-view-id]').forEach((btn) => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-complaint-view-id') || '';
          if (!complaintViewModal || id === '') return;

          const employee = this.getAttribute('data-complaint-view-employee') || '';
          const subject = this.getAttribute('data-complaint-view-subject') || '';
          const category = this.getAttribute('data-complaint-view-category') || '';
          const incident = this.getAttribute('data-complaint-view-incident') || '';
          const status = this.getAttribute('data-complaint-view-status') || '';
          const returned = this.getAttribute('data-complaint-view-returned') || '';
          const desc = this.getAttribute('data-complaint-view-desc') || '';
          const hasAttachment = (this.getAttribute('data-complaint-view-has-attachment') || '0') === '1';
          const meetingDate = this.getAttribute('data-complaint-view-meeting-date') || '';
          const meetingPlace = this.getAttribute('data-complaint-view-meeting-place') || '';

          if (complaintViewEmployee) complaintViewEmployee.textContent = employee;
          if (complaintViewSubject) complaintViewSubject.textContent = subject;
          if (complaintViewStatus) complaintViewStatus.textContent = status;
          if (complaintViewCategory) complaintViewCategory.textContent = category;
          if (complaintViewIncident) complaintViewIncident.textContent = incident;
          if (complaintViewDesc) complaintViewDesc.textContent = desc;

          if (complaintViewReturnedWrap && complaintViewReturned) {
            if (returned && returned.trim() !== '') {
              complaintViewReturned.textContent = returned;
              setHidden(complaintViewReturnedWrap, false);
            } else {
              complaintViewReturned.textContent = '';
              setHidden(complaintViewReturnedWrap, true);
            }
          }

          const cur = document.getElementById('complaintStatusFilter');
          const st = cur && cur.value ? cur.value : 'all';
          const dlUrl = '?section=complaints&complaint_attachment_download=' + encodeURIComponent(id) + '&cstatus=' + encodeURIComponent(st);

          const rawPath = String(this.getAttribute('data-complaint-view-attachment-path') || '').trim();
          const lowerPath = rawPath.toLowerCase();
          const isImg = lowerPath.endsWith('.png') || lowerPath.endsWith('.jpg') || lowerPath.endsWith('.jpeg') || lowerPath.endsWith('.gif') || lowerPath.endsWith('.webp');

          if (complaintViewAttachmentNone) complaintViewAttachmentNone.style.display = hasAttachment ? 'none' : '';
          if (complaintViewAttachmentActions) complaintViewAttachmentActions.style.display = hasAttachment ? '' : 'none';
          if (complaintViewAttachmentImg) {
            complaintViewAttachmentImg.classList.add('hidden');
            complaintViewAttachmentImg.removeAttribute('src');
          }

          if (hasAttachment && isImg && complaintViewAttachmentImg && rawPath !== '') {
            complaintViewAttachmentImg.onerror = function() {
              this.classList.add('hidden');
            };
            complaintViewAttachmentImg.src = rawPath;
            complaintViewAttachmentImg.classList.remove('hidden');
          }

          if (complaintViewAttachmentDownload) {
            complaintViewAttachmentDownload.setAttribute('href', dlUrl);
            complaintViewAttachmentDownload.classList.toggle('btn-disabled', !hasAttachment);
            complaintViewAttachmentDownload.setAttribute('aria-disabled', (!hasAttachment).toString());
          }

          complaintViewModal.showModal();
        });
      });

      const complaintReturnModal = document.getElementById('complaintReturnModal');
      const complaintReturnId = document.getElementById('complaintReturnId');
      const complaintReturnEmployee = document.getElementById('complaintReturnEmployee');
      const complaintReturnRedirect = document.getElementById('complaintReturnRedirect');
      const complaintReturnReason = document.getElementById('complaintReturnReason');

      document.querySelectorAll('[data-complaint-return-id]').forEach((btn) => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-complaint-return-id') || '';
          if (!complaintReturnModal || !complaintReturnId) return;

          const employee = this.getAttribute('data-complaint-return-employee') || '';
          const cur = document.getElementById('complaintStatusFilter');
          const st = cur && cur.value ? cur.value : 'all';

          complaintReturnId.value = id;
          if (complaintReturnEmployee) complaintReturnEmployee.textContent = employee;
          if (complaintReturnRedirect) complaintReturnRedirect.value = st;
          if (complaintReturnReason) complaintReturnReason.value = '';

          complaintReturnModal.showModal();
        });
      });

      const complaintAssignModal = document.getElementById('complaintAssignModal');
      const complaintAssignId = document.getElementById('complaintAssignId');
      const complaintAssignEmployee = document.getElementById('complaintAssignEmployee');
      const complaintAssignRedirect = document.getElementById('complaintAssignRedirect');
      const complaintAssignRole = document.getElementById('complaintAssignRole');
      const complaintAssignTo = document.getElementById('complaintAssignTo');

      function populateComplaintAssignees(role) {
        if (!complaintAssignTo) return;
        const r = String(role || '').trim();
        const opts = (complaintAssignOptions && complaintAssignOptions[r]) ? complaintAssignOptions[r] : [];

        complaintAssignTo.innerHTML = '<option value="">Select assignee</option>';
        opts.forEach((o) => {
          const opt = document.createElement('option');
          opt.value = o.employee_no || '';
          opt.textContent = o.label || o.employee_no || '';
          complaintAssignTo.appendChild(opt);
        });
      }

      if (complaintAssignRole) {
        complaintAssignRole.addEventListener('change', function() {
          populateComplaintAssignees(this.value);
        });
      }

      document.querySelectorAll('[data-complaint-assign-id]').forEach((btn) => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-complaint-assign-id') || '';
          if (!complaintAssignModal || !complaintAssignId) return;

          const employee = this.getAttribute('data-complaint-assign-employee') || '';
          const cur = document.getElementById('complaintStatusFilter');
          const st = cur && cur.value ? cur.value : 'all';

          complaintAssignId.value = id;
          if (complaintAssignEmployee) complaintAssignEmployee.textContent = employee;
          if (complaintAssignRedirect) complaintAssignRedirect.value = st;

          if (complaintAssignRole) complaintAssignRole.value = '';
          populateComplaintAssignees('');
          complaintAssignModal.showModal();
        });
      });

      viewModal.addEventListener('close', function() {
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
        btn.addEventListener('click', function() {
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
        btn.addEventListener('click', function() {
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

      const profileActionModal = document.getElementById('profileActionModal');
      const profileActionTitle = document.getElementById('profileActionTitle');
      const profileActionEmployee = document.getElementById('profileActionEmployee');
      const profileActionLabel = document.getElementById('profileActionLabel');
      const profileActionRemarks = document.getElementById('profileActionRemarks');
      const profileActionSubmit = document.getElementById('profileActionSubmit');
      const profileActionHint = document.getElementById('profileActionHint');

      let pendingProfileAction = {
        id: '',
        action: '',
        row: null,
        emp: ''
      };

      function removeProfileRow(id) {
        if (!id) return;
        const row = document.querySelector(`tr[data-profile-row="${CSS.escape(String(id))}"]`);
        if (row) row.remove();
      }

      async function postProfileUpdate(id, uiStatus, remarks) {
        const url = new URL(window.location.href);
        url.searchParams.set('section', 'profiles');

        const body = new URLSearchParams();
        body.append('ajax', '1');
        body.append('update_profile_request', '1');
        body.append('request_id', String(id));
        body.append('status', String(uiStatus));
        body.append('remarks', String(remarks || ''));

        const res = await fetch(url.toString(), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: body.toString()
        });

        const data = await res.json().catch(() => null);
        if (!data || !data.success) {
          throw new Error((data && data.message) ? data.message : 'Failed to update request');
        }
        return data;
      }

      document.querySelectorAll('[data-profile-action]').forEach((btn) => {
        btn.addEventListener('click', async function() {
          const action = this.getAttribute('data-profile-action') || '';
          const id = this.getAttribute('data-profile-id') || '';
          if (!action || !id) return;

          const row = this.closest('tr');
          const emp = row ? (row.querySelector('td')?.textContent || '') : '';

          if (action === 'approve') {
            try {
              await postProfileUpdate(id, 'Approved', '');
              removeProfileRow(id);
            } catch (e) {
              alert(e.message || 'Failed to approve request');
            }
            return;
          }

          pendingProfileAction = {
            id,
            action,
            row,
            emp
          };

          if (profileActionTitle) {
            profileActionTitle.textContent = action === 'reject' ? 'Reject Profile Request' : 'For Compliance';
          }
          if (profileActionEmployee) {
            profileActionEmployee.textContent = emp || 'Employee';
          }
          if (profileActionLabel) {
            profileActionLabel.textContent = action === 'reject' ? 'Reason for Rejection' : 'Compliance Remarks';
          }
          if (profileActionHint) {
            profileActionHint.textContent = action === 'reject' ?
              'This field is required. Please provide a clear reason for rejection.' :
              'This field is required. State the compliance requirements or missing items.';
          }
          if (profileActionRemarks) {
            profileActionRemarks.value = '';
          }

          if (profileActionSubmit) {
            profileActionSubmit.textContent = action === 'reject' ? 'Reject' : 'Send for Compliance';
          }

          if (profileActionModal && typeof profileActionModal.showModal === 'function') {
            profileActionModal.showModal();
          }
        });
      });

      if (profileActionSubmit) {
        profileActionSubmit.addEventListener('click', async function() {
          const id = pendingProfileAction.id;
          const action = pendingProfileAction.action;
          const remarks = (profileActionRemarks ? profileActionRemarks.value : '').trim();

          if (!id || !action) return;
          if (!remarks) {
            alert(action === 'reject' ? 'Rejection reason is required.' : 'Compliance remarks are required.');
            return;
          }

          const status = action === 'reject' ? 'Rejected' : 'For Compliance';
          try {
            await postProfileUpdate(id, status, remarks);
            if (profileActionModal) profileActionModal.close();
            removeProfileRow(id);
          } catch (e) {
            alert(e.message || 'Failed to update request');
          }
        });
      }
    });
  </script>
</body>

</html>