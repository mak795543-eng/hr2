<?php
session_start();

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX module status updates
if (isset($_POST['update_status']) && isset($_POST['ajax'])) {
  $module_id = $_POST['module_id'];
  $new_status = $_POST['new_status'];
  $remarks = $_POST['remarks'] ?? '';

  $stmt = $conn->prepare("UPDATE learning_modules SET status = ?, remarks = ? WHERE id = ?");
  $stmt->bind_param("ssi", $new_status, $remarks, $module_id);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Module status updated successfully!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error updating module status: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

// Handle AJAX module deletion
if (isset($_POST['delete_module']) && isset($_POST['ajax'])) {
  $module_id = $_POST['module_id'];

  $stmt = $conn->prepare("DELETE FROM learning_modules WHERE id = ?");
  $stmt->bind_param("i", $module_id);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Module deleted successfully!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error deleting module: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

// Handle AJAX module posting
if (isset($_POST['post_module']) && isset($_POST['ajax'])) {
  $module_id = $_POST['module_id'];

  $stmt = $conn->prepare("UPDATE learning_modules SET status = 'posted' WHERE id = ?");
  $stmt->bind_param("i", $module_id);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Module posted successfully!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error posting module: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

// NEW: Handle AJAX module edit status update to pending
if (isset($_POST['edit_module']) && isset($_POST['ajax'])) {
  $module_id = $_POST['module_id'];

  $stmt = $conn->prepare("UPDATE learning_modules SET status = 'pending', remarks = '' WHERE id = ?");
  $stmt->bind_param("i", $module_id);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Module status set to pending for editing!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error updating module status: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

if (isset($_POST['store_extracted']) && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');

  $content = (string)($_POST['content'] ?? '');
  $fileName = (string)($_POST['file_name'] ?? 'uploaded_file');

  if ($content === '') {
    echo json_encode(['success' => false, 'message' => 'No extracted content received.']);
    exit();
  }

  $_SESSION['learning_uploaded_file_content'] = $content;
  $_SESSION['learning_uploaded_file_name'] = $fileName;

  echo json_encode(['success' => true, 'file_name' => $fileName]);
  exit();
}

if (isset($_POST['extract_file']) && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');

  try {
    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
      echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
      exit();
    }

    $uploadError = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
      $uploadErrorMessages = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit (MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk on the server.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension on the server.',
      ];
      $msg = $uploadErrorMessages[$uploadError] ?? ('File upload error (code ' . $uploadError . ').');
      echo json_encode(['success' => false, 'message' => $msg]);
      exit();
    }

    if (empty($_FILES['file']['tmp_name'])) {
      echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
      exit();
    }

    $tmpPath = (string)$_FILES['file']['tmp_name'];
    $origName = (string)($_FILES['file']['name'] ?? 'uploaded_file');
    $size = (int)($_FILES['file']['size'] ?? 0);
    $mime = (string)($_FILES['file']['type'] ?? '');

    if (!is_uploaded_file($tmpPath)) {
      echo json_encode(['success' => false, 'message' => 'Upload validation failed. Please try again.']);
      exit();
    }

    if ($size > 10 * 1024 * 1024) {
      echo json_encode(['success' => false, 'message' => 'File too large. Maximum is 10MB.']);
      exit();
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $content = '';
    if (in_array($ext, ['txt', 'csv', 'html', 'htm', 'rtf'], true)) {
      $content = (string)@file_get_contents($tmpPath);
    } elseif ($ext === 'pdf') {
      $autoload = __DIR__ . '/../../tanu-ai/vendor/autoload.php';
      if (file_exists($autoload)) {
        require_once $autoload;
      }
      if (class_exists('Smalot\\PdfParser\\Parser')) {
        try {
          $parser = new \Smalot\PdfParser\Parser();
          $pdf = $parser->parseFile($tmpPath);
          $content = (string)$pdf->getText();
        } catch (Throwable $e) {
          $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract PDF content. Please edit the content manually in the editor.]";
        }
      } else {
        $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[PDF parsing library not available. Please edit the content manually in the editor.]";
      }
    } elseif ($ext === 'docx') {
      if (!class_exists('ZipArchive')) {
        $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract DOCX content because PHP Zip extension is not enabled on the server. Enable the 'zip' extension in php.ini (extension=zip) then restart Apache.]";
      } else {
        $zip = new ZipArchive();
        $openRes = $zip->open($tmpPath);
        if ($openRes === true) {
          $xml = (string)$zip->getFromName('word/document.xml');
          if ($xml === '') {
            $stream = $zip->getStream('word/document.xml');
            if (is_resource($stream)) {
              $xml = (string)stream_get_contents($stream);
              fclose($stream);
            }
          }
          $zip->close();
          if ($xml !== '') {
            $xml = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml);
            $xml = str_replace(['<w:tab/>', '<w:br/>', '<w:cr/>'], ["\t", "\n", "\n"], $xml);
            $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/\n{3,}/", "\n\n", (string)$text);
            $content = trim((string)$text);
          }
        } else {
          $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract DOCX content. ZipArchive could not open the file (code {$openRes}).]";
        }

        if ($content === '') {
          $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract DOCX content. Please edit the content manually in the editor.]";
        }
      }
    } elseif ($ext === 'pptx') {
      if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) === true) {
          $slideTexts = [];
          for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
              $xml = (string)$zip->getFromName($name);
              if ($xml !== '') {
                $xml = str_replace(['</a:p>', '</p:sp>', '</p:txBody>'], ["\n", "\n", "\n"], $xml);
                $slideTexts[] = trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
              }
            }
          }
          $zip->close();
          $content = trim(implode("\n\n", array_filter($slideTexts, static fn($t) => $t !== '')));
        }
      }
      if ($content === '') {
        $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract PPTX content. Please edit the content manually in the editor.]";
      }
    } elseif ($ext === 'xlsx') {
      if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) === true) {
          $xml = (string)$zip->getFromName('xl/sharedStrings.xml');
          $zip->close();
          if ($xml !== '') {
            $xml = str_replace(['</si>'], ["\n"], $xml);
            $content = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
          }
        }
      }
      if ($content === '') {
        $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[Unable to extract XLSX content. Please edit the content manually in the editor.]";
      }
    } else {
      $content = "File: {$origName}\nType: {$mime}\nSize: {$size}\n\n[This file type is not supported for automatic extraction. Please edit the content manually in the editor.]";
    }

    $_SESSION['learning_uploaded_file_content'] = $content;
    $_SESSION['learning_uploaded_file_name'] = $origName;

    echo json_encode(['success' => true, 'file_name' => $origName]);
    exit();
  } catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to process uploaded file: ' . $e->getMessage()]);
    exit();
  }
}

// NEW: Handle AJAX draft module save
if (isset($_POST['save_draft']) && isset($_POST['ajax'])) {
  $title = $_POST['title'] ?? '';
  $topic = $_POST['topic'] ?? '';
  $department = $_POST['department'] ?? '';
  $role = $_POST['role'] ?? '';
  $content = $_POST['content'] ?? '';

  // Check if this is an existing draft (has ID)
  if (isset($_POST['draft_id']) && !empty($_POST['draft_id'])) {
    $draft_id = $_POST['draft_id'];
    $stmt = $conn->prepare("UPDATE learning_modules SET title = ?, topic = ?, department = ?, roles = ?, content = ?, status = 'draft', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $topic, $department, $role, $content, $draft_id);
  } else {
    // Create new draft
    $stmt = $conn->prepare("INSERT INTO learning_modules (title, topic, department, roles, content, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'draft', NOW(), NOW())");
    $stmt->bind_param("sssss", $title, $topic, $department, $role, $content);
  }

  if ($stmt->execute()) {
    $draft_id = isset($draft_id) ? $draft_id : $stmt->insert_id;
    echo json_encode(['success' => true, 'message' => 'Module saved to drafts!', 'draft_id' => $draft_id]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error saving draft: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

// NEW: Fetch draft modules
if (isset($_POST['get_drafts']) && isset($_POST['ajax'])) {
  $sql = "SELECT * FROM learning_modules WHERE status = 'draft' ORDER BY updated_at DESC";
  $result = $conn->query($sql);

  $drafts = [];
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $drafts[] = $row;
    }
  }

  echo json_encode(['success' => true, 'drafts' => $drafts]);
  exit();
}

// NEW: Delete draft
if (isset($_POST['delete_draft']) && isset($_POST['ajax'])) {
  $draft_id = $_POST['draft_id'];

  $stmt = $conn->prepare("DELETE FROM learning_modules WHERE id = ? AND status = 'draft'");
  $stmt->bind_param("i", $draft_id);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Draft deleted successfully!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error deleting draft: ' . $stmt->error]);
  }

  $stmt->close();
  exit();
}

// Fetch learning modules from database (include approved, rejected, compliance, hold - exclude posted, pending, and drafts)
$modules = [];
$sql = "SELECT * FROM learning_modules WHERE status IN ('approved', 'rejected', 'compliance', 'for-compliance', 'hold', 'pending') ORDER BY 
    CASE 
        WHEN status = 'approved' THEN 1
        WHEN status = 'compliance' THEN 2
        WHEN status = 'for-compliance' THEN 2
        WHEN status = 'rejected' THEN 3
        WHEN status = 'hold' THEN 4
        WHEN status = 'pending' THEN 5
        ELSE 6
    END, created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Modules</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../../CSS/learning_module_repository.css">
  <style>
    /* Custom styles for border-only buttons */
    .btn-border {
      background-color: transparent;
      border: 1px solid #d1d5db;
      color: #374151;
      transition: all 0.2s ease-in-out;
    }

    .btn-border:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }

    .btn-border:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
    }

    .btn-sm-border {
      background-color: transparent;
      border: 1px solid #d1d5db;
      color: #374151;
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 0.375rem;
      transition: all 0.2s ease-in-out;
    }

    .btn-sm-border:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }

    /* Status badge styles */
    .status-approved {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .status-rejected {
      background-color: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    .status-compliance {
      background-color: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
    }

    .status-pending {
      background-color: #e0e7ff;
      color: #3730a3;
      border: 1px solid #c7d2fe;
    }

    .status-posted {
      background-color: #dbeafe;
      color: #1e40af;
      border: 1px solid #93c5fd;
    }

    .status-hold {
      background-color: #f3f4f6;
      color: #374151;
      border: 1px solid #d1d5db;
    }

    .status-draft {
      background-color: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
    }

    /* Modal styles */
    .modal-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .info-section {
      background-color: #f8fafc;
      padding: 1.5rem;
      border-radius: 0.5rem;
      border: 1px solid #e2e8f0;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .info-label {
      font-weight: 600;
      color: #4b5563;
    }

    .info-value {
      color: #1f2937;
    }

    .document-section {
      background-color: #f8fafc;
      padding: 1.5rem;
      border-radius: 0.5rem;
      border: 1px solid #e2e8f0;
      margin-top: 1.5rem;
    }

    .document-preview {
      height: 300px;
      overflow-y: auto;
      background-color: white;
      border: 1px solid #e2e8f0;
      border-radius: 0.375rem;
      padding: 1rem;
    }

    /* FIXED: Styles for displaying the actual content properly */
    .document-preview * {
      max-width: 100%;
      word-wrap: break-word;
    }

    .document-preview img {
      max-width: 100%;
      height: auto;
    }

    .document-preview table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .document-preview table,
    .document-preview th,
    .document-preview td {
      border: 1px solid #ddd;
    }

    .document-preview th,
    .document-preview td {
      padding: 8px;
      text-align: left;
      word-wrap: break-word;
    }

    .document-preview ul,
    .document-preview ol {
      padding-left: 1.5rem;
    }

    .document-preview li {
      margin-bottom: 0.25rem;
    }

    .document-preview h1,
    .document-preview h2,
    .document-preview h3,
    .document-preview h4,
    .document-preview h5,
    .document-preview h6 {
      margin-top: 1rem;
      margin-bottom: 0.5rem;
    }

    .document-preview p {
      margin-bottom: 0.5rem;
    }

    .reason-section {
      background-color: #fef3c7;
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid #fde68a;
    }

    .reason-title {
      font-weight: 600;
      color: #92400e;
      margin-bottom: 0.5rem;
    }

    .reason-text {
      color: #92400e;
    }

    /* NEW: Reason display on module cards */
    .module-reason {
      background-color: #fef3c7;
      padding: 0.75rem;
      border-radius: 0.375rem;
      margin: 0.75rem 0;
      border: 1px solid #fde68a;
      font-size: 0.875rem;
    }

    .module-reason.rejected {
      background-color: #fee2e2;
      border-color: #fecaca;
    }

    .module-reason.compliance {
      background-color: #fef3c7;
      border-color: #fde68a;
    }

    .module-reason.hold {
      background-color: #f3f4f6;
      border-color: #d1d5db;
      color: #374151;
    }

    .reason-label {
      font-weight: 600;
      margin-bottom: 0.25rem;
      display: block;
    }

    .drop-zone {
      border: 2px dashed #d1d5db;
      border-radius: 0.5rem;
      transition: all 0.3s ease;
      position: relative;
    }

    .drop-zone.active {
      border-color: #3b82f6;
      background-color: #eff6ff;
    }

    .file-input {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      opacity: 0;
      cursor: pointer;
    }

    /* File list styles */
    .file-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem;
      background-color: #f8fafc;
      border-radius: 0.375rem;
      margin-bottom: 0.5rem;
    }

    .file-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .file-icon {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .file-name {
      font-weight: 500;
    }

    .file-size {
      color: #6b7280;
      font-size: 0.875rem;
    }

    .file-remove {
      background: none;
      border: none;
      color: #ef4444;
      cursor: pointer;
      padding: 0.25rem;
      border-radius: 0.25rem;
    }

    .file-remove:hover {
      background-color: #fef2f2;
    }

    /* Loading spinner */
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Notification styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      z-index: 1000;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      max-width: 400px;
      opacity: 0;
      transform: translateY(-20px);
      transition: all 0.3s ease;
    }

    .notification.show {
      opacity: 1;
      transform: translateY(0);
    }

    .notification-success {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .notification-error {
      background-color: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    /* FIXED: Full content modal styles */
    .full-content-display {
      max-height: 60vh;
      overflow-y: auto;
      padding: 1rem;
      background: white;
      border-radius: 0.5rem;
      border: 1px solid #e2e8f0;
    }

    .full-content-display * {
      max-width: 100%;
      word-wrap: break-word;
    }

    .full-content-display img {
      max-width: 100%;
      height: auto;
    }

    .full-content-display table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .full-content-display table,
    .full-content-display th,
    .full-content-display td {
      border: 1px solid #ddd;
    }

    .full-content-display th,
    .full-content-display td {
      padding: 8px;
      text-align: left;
      word-wrap: break-word;
    }

    .full-content-display ul,
    .full-content-display ol {
      padding-left: 1.5rem;
    }

    .full-content-display li {
      margin-bottom: 0.25rem;
    }

    .full-content-display h1,
    .full-content-display h2,
    .full-content-display h3,
    .full-content-display h4,
    .full-content-display h5,
    .full-content-display h6 {
      margin-top: 1rem;
      margin-bottom: 0.5rem;
    }

    .full-content-display p {
      margin-bottom: 0.5rem;
    }

    /* Optional file upload indicator */
    .optional-indicator {
      color: #6b7280;
      font-size: 0.875rem;
      font-style: italic;
    }

    /* Drafts button style */
    .btn-draft {
      background-color: #f59e0b;
      border: 1px solid #f59e0b;
      color: white;
      transition: all 0.2s ease-in-out;
    }

    .btn-draft:hover {
      background-color: #d97706;
      border-color: #d97706;
    }

    /* Drafts modal styles */
    .draft-item {
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 0.75rem;
      background-color: white;
      transition: all 0.2s ease-in-out;
    }

    .draft-item:hover {
      border-color: #d1d5db;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .draft-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.5rem;
    }

    .draft-title {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.25rem;
    }

    .draft-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.875rem;
      color: #6b7280;
    }

    .draft-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.75rem;
    }

    .draft-content-preview {
      color: #6b7280;
      font-size: 0.875rem;
      max-height: 3rem;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .no-drafts {
      text-align: center;
      padding: 2rem;
      color: #6b7280;
    }

    .no-drafts i {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .module-card {
      font-size: inherit;
    }

    .module-card .card-title {
      font-size: 1.125rem;
      font-weight: 600;
    }

    .module-card p {
      font-size: 0.875rem;
    }

    .module-reason {
      font-size: 0.875rem;
    }

    .card-actions .btn-sm-border {
      font-size: 0.875rem;
      padding: 0.375rem 0.75rem;
    }

    .top-nav-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php';
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>

      <!-- Notification Container -->
      <div id="notificationContainer"></div>

      <!-- Main Content -->
      <div class="container mx-auto px-4 py-8">
        <!-- Learning Modules Section -->
        <div class="mb-12">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h1 class="text-2xl font-bold mb-2">Learning Modules Repository</h1>
              <p class="text-gray-600">Manage and organize all learning materials for your organization</p>
            </div>
            <div class="top-nav-buttons">

              <button class="btn btn-border" onclick="openUploadModal()">
                <i class="fas fa-plus mr-2"></i>Upload Module
              </button>

            </div>
          </div>

          <!-- Filter Section -->
          <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
            <div class="flex flex-wrap gap-4">
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Status</span>
                </label>
                <select class="select select-bordered w-40" id="statusFilter">
                  <option value="all">All Status</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="compliance">For Compliance</option>
                  <option value="pending">Under Review</option>
                  <option value="hold">On Hold</option>
                </select>
              </div>

              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Department</span>
                </label>
                <select class="select select-bordered w-48" id="departmentFilter">
                  <option value="all">All Departments</option>
                  <option value="front-office">Front Office</option>
                  <option value="housekeeping">Housekeeping</option>
                  <option value="food-beverage">Food & Beverage</option>
                  <option value="kitchen">Kitchen</option>
                  <option value="sales-marketing">Sales & Marketing</option>
                  <option value="hr">Human Resources</option>
                  <option value="human-resources">Human Resources</option>
                  <option value="finance">Finance</option>
                  <option value="engineering">Engineering</option>
                  <option value="security">Security</option>
                </select>
              </div>

              <div class="form-control self-end">
                <button class="btn btn-border" onclick="applyFilters()">
                  <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
              </div>

              <div class="form-control self-end">
                <button class="btn btn-border" onclick="clearFilters()">
                  <i class="fas fa-times mr-2"></i>Clear
                </button>
              </div>
            </div>
          </div>

          <!-- Module Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="moduleCards">
            <?php if (empty($modules)): ?>
              <div class="col-span-full text-center py-8">
                <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">No learning modules found. Create your first module!</p>
              </div>
            <?php else: ?>
              <?php foreach ($modules as $module): ?>
                <div class="card bg-base-100 shadow-md module-card"
                  data-status="<?php echo $module['status']; ?>"
                  data-department="<?php echo $module['department']; ?>"
                  data-id="<?php echo $module['id']; ?>"
                  data-content="<?php echo htmlspecialchars($module['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  data-rejection-reason="<?php echo htmlspecialchars($module['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  data-compliance-reason="<?php echo htmlspecialchars($module['compliance_reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  data-remarks="<?php echo htmlspecialchars($module['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="card-body">
                    <div class="flex justify-between items-start">
                      <h3 class="card-title"><?php echo htmlspecialchars($module['title']); ?></h3>
                      <div class="badge status-<?php echo $module['status']; ?>" id="status-badge-<?php echo $module['id']; ?>">
                        <?php
                        $statusDisplay = [
                          'approved' => 'Approved',
                          'rejected' => 'Rejected',
                          'compliance' => 'For Compliance',
                          'for-compliance' => 'For Compliance',
                          'pending' => 'Under Review',
                          'posted' => 'Posted',
                          'hold' => 'ON HOLD',
                          'draft' => 'Draft'
                        ];
                        echo $statusDisplay[$module['status']] ?? ucfirst($module['status']);
                        ?>
                      </div>
                    </div>

                    <!-- Display reason on module card if available -->
                    <?php if ($module['status'] === 'rejected' && !empty($module['rejection_reason'])): ?>
                      <div class="module-reason rejected">
                        <span class="reason-label">Reason for Rejection:</span>
                        <?php echo htmlspecialchars($module['rejection_reason']); ?>
                      </div>
                    <?php elseif (($module['status'] === 'compliance' || $module['status'] === 'for-compliance') && !empty($module['compliance_reason'])): ?>
                      <div class="module-reason compliance">
                        <span class="reason-label">Compliance Requirements:</span>
                        <?php echo htmlspecialchars($module['compliance_reason']); ?>
                      </div>
                    <?php elseif ($module['status'] === 'hold' && !empty($module['remarks'])): ?>
                      <div class="module-reason hold">
                        <span class="reason-label">Hold Reason:</span>
                        <?php echo htmlspecialchars($module['remarks']); ?>
                      </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap gap-2 my-2">
                      <div class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $module['department'])); ?></div>
                      <div class="badge badge-outline"><?php echo htmlspecialchars($module['roles']); ?></div>
                    </div>
                    <p class="text-sm text-gray-500">Date Added: <?php echo date('Y-m-d', strtotime($module['created_at'])); ?></p>
                    <p class="text-sm text-gray-500">Topic: <?php echo htmlspecialchars($module['topic']); ?></p>
                    <div class="card-actions justify-end mt-4">
                      <button class="btn-sm-border" onclick="viewModule(<?php echo (int)$module['id']; ?>, this)">View</button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODALS -->

  <!-- Upload Module Modal -->
  <dialog id="upload_modal" class="modal modal-middle">
    <div class="modal-box max-w-4xl">
      <h3 class="font-bold text-lg mb-6">Upload Learning Module</h3>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left: Drag and Drop & File Info -->
        <div>
          <h4 class="text-lg font-medium mb-4">Upload File <span class="optional-indicator">(Optional)</span></h4>
          <div id="dropZone" class="drop-zone p-8 text-center cursor-pointer mb-4">
            <div class="flex flex-col items-center justify-center gap-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="text-lg">Drag and drop files here</p>
              <p class="text-sm text-gray-500">Supports PDF, DOCX, PPT, TXT, HTML, CSV, and more</p>
              <p class="text-sm optional-indicator">Optional - you can also create content manually</p>
              <button class="btn btn-border mt-2">Browse Files</button>
            </div>
            <input type="file" id="fileInput" class="file-input" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.rtf,.html,.htm,.xls,.xlsx,.csv" multiple>
          </div>

          <div class="mt-4">
            <div class="text-sm text-gray-500 mb-2">Uploaded files:</div>
            <div class="space-y-2" id="fileList">
              <!-- Files will be listed here -->
            </div>
          </div>
        </div>

        <!-- Right: Module Details Form -->
        <div>
          <h4 class="text-lg font-medium mb-4">Module Details</h4>
          <form class="space-y-4" id="uploadForm">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Title</span>
              </label>
              <input type="text" name="title" class="input input-bordered" placeholder="Enter module title" required>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Department</span>
              </label>
              <select class="select select-bordered" id="departmentSelect" name="department" required>
                <option disabled selected>Select Department</option>
                <option value="front-office">Front Office / Reception</option>
                <option value="housekeeping">Housekeeping</option>
                <option value="food-beverage">Food & Beverage (F&B)</option>
                <option value="kitchen">Kitchen / Culinary</option>
                <option value="sales-marketing">Sales & Marketing</option>
                <option value="hr">Human Resources (HR)</option>
                <option value="human-resources">Human Resources (HR)</option>
                <option value="finance">Finance / Accounting</option>
                <option value="engineering">Engineering / Maintenance</option>
                <option value="security">Security</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Role</span>
              </label>
              <select class="select select-bordered" id="roleSelect" name="role" disabled required>
                <option disabled selected>Select Department First</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Topic</span>
              </label>
              <textarea name="topic" class="textarea textarea-bordered" placeholder="Enter topic" rows="2" required></textarea>
            </div>
          </form>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Cancel</button>
        </form>
        <!-- NEW: Drafts Button -->
        <button class="btn btn-draft" onclick="showDraftsModal()">
          <i class="fas fa-file-alt mr-2"></i>Drafts
        </button>
        <button class="btn btn-primary" onclick="startModuleCreation()">
          <i class="fas fa-play mr-2"></i>Upload Module
        </button>
      </div>
    </div>
  </dialog>

  <!-- NEW: Drafts Modal -->
  <dialog id="drafts_modal" class="modal modal-middle">
    <div class="modal-box max-w-4xl">
      <h3 class="font-bold text-lg mb-6">Module Drafts</h3>

      <div class="mb-4">
        <p class="text-gray-600">Your unfinished learning modules are saved here. You can continue editing them anytime.</p>
      </div>

      <div id="draftsList" class="max-h-96 overflow-y-auto">
        <!-- Drafts will be loaded here -->
        <div class="no-drafts">
          <i class="fas fa-file-alt"></i>
          <p>No drafts found</p>
          <p class="text-sm">Start creating a module to see your drafts here</p>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- APPROVED MODULE MODAL -->
  <dialog id="approved_module_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="approved-title">Module Title</h3>

      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="approved-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="approved-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="approved-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="approved-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="approved-status">Approved</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="approved-date">-</span>
          </div>
        </div>

        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="approved-document-preview">
              <!-- ACTUAL document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="approved-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="approved-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>

          <!-- CRUD Actions -->
          <div class="mt-4 flex gap-2">
            <button class="btn btn-border flex-1" id="approved-post-btn">Post</button>
            <button class="btn btn-primary flex-1" id="approved-edit-btn">
              <i class="fas fa-edit mr-2"></i>Edit
            </button>
          </div>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- REJECTED MODULE MODAL -->
  <dialog id="rejected_module_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="rejected-title">Module Title</h3>

      <!-- Reason Section -->
      <div class="reason-section">
        <div class="reason-title">Reason for Rejection</div>
        <div class="reason-text" id="rejected-reason">This module was rejected due to incomplete content and outdated information.</div>
      </div>

      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="rejected-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="rejected-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="rejected-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="rejected-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="rejected-status">Rejected</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="rejected-date">-</span>
          </div>
        </div>

        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="rejected-document-preview">
              <!-- ACTUAL document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="rejected-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="rejected-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>

          <!-- CRUD Actions - ONLY DELETE BUTTON FOR REJECTED STATUS -->
          <div class="mt-4 flex gap-2">
            <button class="btn btn-error flex-1" id="rejected-delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- FOR COMPLIANCE MODULE MODAL -->
  <dialog id="compliance_module_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="compliance-title">Module Title</h3>

      <!-- Reason Section -->
      <div class="reason-section">
        <div class="reason-title">Compliance Requirements</div>
        <div class="reason-text" id="compliance-reason">This module requires updates to meet compliance standards. Please review the following requirements.</div>
      </div>

      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="compliance-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="compliance-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="compliance-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="compliance-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="compliance-status">For Compliance</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="compliance-date">-</span>
          </div>
        </div>

        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="compliance-document-preview">
              <!-- ACTUAL document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="compliance-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="compliance-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>

          <!-- CRUD Actions -->
          <div class="mt-4 flex gap-2">
            <button class="btn btn-border flex-1" id="compliance-resubmit-btn">Resubmit Request</button>
            <button class="btn btn-primary flex-1" id="compliance-edit-btn">
              <i class="fas fa-edit mr-2"></i>Edit
            </button>
            <button class="btn btn-error flex-1" id="compliance-delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- HOLD MODULE MODAL -->
  <dialog id="hold_module_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="hold-title">Module Title</h3>

      <!-- Reason Section -->
      <div class="reason-section">
        <div class="reason-title">Hold Reason</div>
        <div class="reason-text" id="hold-reason">This module is currently on hold.</div>
      </div>

      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="hold-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="hold-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="hold-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="hold-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="hold-status">ON HOLD</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="hold-date">-</span>
          </div>
        </div>

        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="hold-document-preview">
              <!-- ACTUAL document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="hold-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="hold-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>

          <!-- CRUD Actions -->
          <div class="mt-4 flex gap-2">
            <button class="btn btn-primary flex-1" id="hold-edit-btn">
              <i class="fas fa-edit mr-2"></i>Edit
            </button>
            <button class="btn btn-error flex-1" id="hold-delete-btn">Delete</button>
            <button class="btn btn-border flex-1" id="hold-resume-btn">Resume</button>
          </div>
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- FULL CONTENT MODAL -->
  <dialog id="full_content_modal" class="modal modal-middle">
    <div class="modal-box max-w-6xl max-h-[80vh]">
      <h3 class="font-bold text-lg mb-4" id="full-content-title">Full Module Content</h3>

      <div class="full-content-display" id="full-content-display">
        <!-- Full content will be displayed here -->
      </div>

      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- CONVERT MODULE MODAL -->
  <dialog id="convert_module_modal" class="modal modal-middle">
    <div class="modal-box max-w-md">
      <h3 class="font-bold text-lg mb-4">Convert Module</h3>
      <p class="mb-4">This module will be converted using AI to create a quiz/exam.</p>
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-border">Cancel</button>
        </form>
        <button class="btn btn-primary" id="confirm-convert-btn">Convert</button>
      </div>
    </div>
  </dialog>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/mammoth/mammoth.browser.min.js"></script>

  <script>
    // File handling variables
    let uploadedFiles = [];
    let selectedFileContent = '';
    let currentModal = null;

    // Function to close current modal
    function closeCurrentModal() {
      if (currentModal) {
        currentModal.close();
        currentModal = null;
      }
    }

    function openUploadModal() {
      const modal = document.getElementById('upload_modal');
      if (modal && typeof modal.showModal === 'function') {
        currentModal = modal;
        modal.showModal();
      }
    }

    // NEW: Function to show drafts modal
    function showDraftsModal() {
      // Close upload modal first
      closeCurrentModal();

      // Show drafts modal
      const draftsModal = document.getElementById('drafts_modal');
      if (draftsModal && typeof draftsModal.showModal === 'function') {
        currentModal = draftsModal;
        draftsModal.showModal();
      }

      // Load drafts
      loadDrafts();
    }

    // NEW: Function to load drafts
    function loadDrafts() {
      const draftsList = document.getElementById('draftsList');
      draftsList.innerHTML = '<div class="text-center py-4"><div class="loading-spinner"></div><p class="mt-2">Loading drafts...</p></div>';

      const formData = new FormData();
      formData.append('get_drafts', '1');
      formData.append('ajax', '1');

      fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayDrafts(data.drafts);
          } else {
            draftsList.innerHTML = '<div class="no-drafts"><i class="fas fa-exclamation-circle"></i><p>Error loading drafts</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading drafts:', error);
          draftsList.innerHTML = '<div class="no-drafts"><i class="fas fa-exclamation-circle"></i><p>Error loading drafts</p></div>';
        });
    }

    // NEW: Function to display drafts
    function displayDrafts(drafts) {
      const draftsList = document.getElementById('draftsList');

      if (drafts.length === 0) {
        draftsList.innerHTML = `
          <div class="no-drafts">
            <i class="fas fa-file-alt"></i>
            <p>No drafts found</p>
            <p class="text-sm">Start creating a module to see your drafts here</p>
          </div>
        `;
        return;
      }

      let draftsHTML = '';

      drafts.forEach(draft => {
        const createdDate = new Date(draft.created_at).toLocaleDateString();
        const updatedDate = new Date(draft.updated_at).toLocaleDateString();
        const contentPreview = draft.content ?
          draft.content.replace(/<[^>]*>/g, '').substring(0, 100) + '...' :
          'No content yet';

        draftsHTML += `
          <div class="draft-item">
            <div class="draft-header">
              <div class="flex-1">
                <div class="draft-title">${draft.title || 'Untitled Module'}</div>
                <div class="draft-meta">
                  <span>Topic: ${draft.topic || 'Not specified'}</span>
                  <span>Department: ${formatDepartment(draft.department) || 'Not specified'}</span>
                  <span>Last updated: ${updatedDate}</span>
                </div>
              </div>
              <div class="badge status-draft">Draft</div>
            </div>
            <div class="draft-content-preview">${contentPreview}</div>
            <div class="draft-actions">
              <button class="btn btn-primary btn-sm" onclick="continueDraft(${draft.id})">
                <i class="fas fa-edit mr-1"></i>Continue Editing
              </button>
              <button class="btn btn-border btn-sm" onclick="deleteDraft(${draft.id})">
                <i class="fas fa-trash mr-1"></i>Delete
              </button>
            </div>
          </div>
        `;
      });

      draftsList.innerHTML = draftsHTML;
    }

    // NEW: Function to continue editing a draft
    function continueDraft(draftId) {
      // Close drafts modal
      const draftsModal = document.getElementById('drafts_modal');
      draftsModal.close();

      // Redirect to create_learning_modules.php with edit parameter
      window.location.href = `create_learning_modules.php?edit=${draftId}`;
    }

    // NEW: Function to delete a draft
    function deleteDraft(draftId) {
      // Close modal first, then show SweetAlert
      closeCurrentModal();

      Swal.fire({
        title: 'Delete Draft?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('delete_draft', '1');
          formData.append('draft_id', draftId);
          formData.append('ajax', '1');

          fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showNotification(data.message, 'success');
                loadDrafts(); // Reload the drafts list
              } else {
                showNotification(data.message, 'error');
              }
            })
            .catch(error => {
              console.error('Error deleting draft:', error);
              showNotification('An error occurred while deleting the draft.', 'error');
            });
        }
      });
    }

    // NEW: Function to save module as draft
    function saveModuleAsDraft(title, topic, department, role, content) {
      const formData = new FormData();
      formData.append('save_draft', '1');
      formData.append('title', title);
      formData.append('topic', topic);
      formData.append('department', department);
      formData.append('role', role);
      formData.append('content', content);
      formData.append('ajax', '1');

      return fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            return data;
          } else {
            throw new Error(data.message);
          }
        });
    }

    // Initialize drag and drop functionality
    function initDragAndDrop() {
      const dropZone = document.getElementById('dropZone');
      const fileInput = document.getElementById('fileInput');
      const fileList = document.getElementById('fileList');


      // Prevent default drag behaviors
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
      });

      // Highlight drop zone when item is dragged over it
      ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
      });

      // Handle dropped files
      dropZone.addEventListener('drop', handleDrop, false);

      // Handle file input change
      fileInput.addEventListener('change', handleFileSelect, false);

      fileInput.addEventListener('click', (e) => {
        e.stopPropagation();
      }, false);

      // Click on drop zone to trigger file input
      dropZone.addEventListener('click', (e) => {
        if (e.target === fileInput) return;
        try {
          fileInput.value = '';
        } catch (err) {}
        fileInput.click();
      });

      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }

      function highlight() {
        dropZone.classList.add('active');
      }

      function unhighlight() {
        dropZone.classList.remove('active');
      }

      function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
      }

      function handleFileSelect(e) {
        const files = e.target.files;
        handleFiles(files);
      }

      function handleFiles(files) {
        if (files.length > 0) {
          // Clear previous files
          uploadedFiles = [];
          fileList.innerHTML = '';
          selectedFileContent = '';

          for (let i = 0; i < files.length; i++) {
            const file = files[i];
            processFile(file);
          }
        }
      }

      function processFile(file) {
        // Check file type
        const allowedTypes = [
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-powerpoint',
          'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'text/plain',
          'application/rtf',
          'text/rtf',
          'text/html',
          'text/csv',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        const fileExtension = file.name.split('.').pop().toLowerCase();
        const allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'rtf', 'html', 'htm', 'xls', 'xlsx', 'csv'];

        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
          // Close modal first, then show SweetAlert
          closeCurrentModal();
          Swal.fire({
            title: 'File Type Not Supported',
            text: 'File type not supported: ' + file.type,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
          return;
        }

        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
          // Close modal first, then show SweetAlert
          closeCurrentModal();
          Swal.fire({
            title: 'File Too Large',
            text: 'File size too large: ' + file.name,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
          return;
        }

        // Add file to uploaded files list
        uploadedFiles.push(file);
        displayFile(file);

        // Read file content - only read the first file for content
        if (uploadedFiles.length === 1) {
          readFileContent(file);
        }
      }

      function displayFile(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.dataset.fileName = file.name;

        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';

        const fileIcon = document.createElement('div');
        fileIcon.className = 'file-icon';
        fileIcon.innerHTML = getFileIcon(file);

        const fileName = document.createElement('div');
        fileName.className = 'file-name';
        fileName.textContent = file.name;

        const fileSize = document.createElement('div');
        fileSize.className = 'file-size';
        fileSize.textContent = formatFileSize(file.size);

        const fileRemove = document.createElement('button');
        fileRemove.className = 'file-remove';
        fileRemove.innerHTML = '<i class="fas fa-times"></i>';
        fileRemove.onclick = () => removeFile(file.name);

        fileInfo.appendChild(fileIcon);
        fileInfo.appendChild(fileName);
        fileItem.appendChild(fileInfo);
        fileItem.appendChild(fileSize);
        fileItem.appendChild(fileRemove);

        fileList.appendChild(fileItem);
      }

      function getFileIcon(file) {
        const type = file.type;
        const name = file.name.toLowerCase();

        if (type.includes('pdf') || name.endsWith('.pdf')) return '<i class="fas fa-file-pdf text-red-500"></i>';
        if (type.includes('word') || type.includes('document') || name.endsWith('.doc') || name.endsWith('.docx')) return '<i class="fas fa-file-word text-blue-500"></i>';
        if (type.includes('powerpoint') || type.includes('presentation') || name.endsWith('.ppt') || name.endsWith('.pptx')) return '<i class="fas fa-file-powerpoint text-orange-500"></i>';
        if (type.includes('excel') || type.includes('spreadsheet') || name.endsWith('.xls') || name.endsWith('.xlsx')) return '<i class="fas fa-file-excel text-green-500"></i>';
        if (type.includes('text') || name.endsWith('.txt')) return '<i class="fas fa-file-alt text-gray-500"></i>';
        if (type.includes('html') || name.endsWith('.html') || name.endsWith('.htm')) return '<i class="fas fa-file-code text-purple-500"></i>';
        if (name.endsWith('.csv')) return '<i class="fas fa-file-csv text-teal-500"></i>';
        return '<i class="fas fa-file text-gray-400"></i>';
      }

      function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }

      function removeFile(fileName) {
        uploadedFiles = uploadedFiles.filter(file => file.name !== fileName);
        const fileItem = document.querySelector(`.file-item[data-file-name="${fileName}"]`);
        if (fileItem) {
          fileItem.remove();
        }

        // Clear selected content if this was the selected file
        if (selectedFileContent) {
          selectedFileContent = '';
        }

        // If there are other files, read the next one
        if (uploadedFiles.length > 0) {
          readFileContent(uploadedFiles[0]);
        } else {
          try {
            fileInput.value = '';
          } catch (err) {}
        }
      }

      function readFileContent(file) {
        const reader = new FileReader();

        reader.onload = function(e) {
          const content = e.target.result;
          selectedFileContent = extractTextContent(file, content);
          console.log('File content loaded:', selectedFileContent);
          showNotification('File content loaded successfully! Ready to create module.', 'success');
        };

        reader.onerror = function() {
          showNotification('Error reading file: ' + file.name, 'error');
          selectedFileContent = `Error reading file: ${file.name}`;
        };

        // Read based on file type
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (file.type.includes('text') ||
          fileExtension === 'txt' ||
          fileExtension === 'rtf' ||
          fileExtension === 'html' ||
          fileExtension === 'htm' ||
          fileExtension === 'csv') {
          reader.readAsText(file);
        } else {
          // For binary files, show a message that content will be processed
          selectedFileContent = `File: ${file.name}\nType: ${file.type}\nSize: ${formatFileSize(file.size)}\n\n[This file contains binary data. Please edit the content manually in the editor.]`;
          showNotification('File uploaded. Please edit content manually in the editor.', 'info');
        }
      }

      function extractTextContent(file, content) {
        const fileExtension = file.name.split('.').pop().toLowerCase();

        switch (fileExtension) {
          case 'txt':
            // Plain text - return as is
            return content;

          case 'rtf':
            // Basic RTF text extraction (remove RTF control words)
            return content.replace(/\\[a-z]+\d*|{[^}]*}|[{}]|\\\n?/g, ' ')
              .replace(/\s+/g, ' ')
              .trim();

          case 'html':
          case 'htm':
            // Extract text from HTML while preserving basic structure
            return extractHtmlContent(content);

          case 'csv':
            // Convert CSV to readable table format
            return convertCsvToText(content);

          default:
            // For other file types, return the raw content
            return content;
        }
      }

      function extractHtmlContent(html) {
        // Create a temporary DOM element to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // Remove script and style elements
        const scripts = tempDiv.getElementsByTagName('script');
        const styles = tempDiv.getElementsByTagName('style');

        Array.from(scripts).forEach(script => script.remove());
        Array.from(styles).forEach(style => style.remove());

        // Extract text content while preserving some structure
        let textContent = '';

        // Process headings
        const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');
        headings.forEach(heading => {
          textContent += '\n' + heading.textContent.trim() + '\n';
        });

        // Process paragraphs
        const paragraphs = tempDiv.querySelectorAll('p');
        paragraphs.forEach(p => {
          textContent += p.textContent.trim() + '\n\n';
        });

        // Process lists
        const lists = tempDiv.querySelectorAll('ul, ol');
        lists.forEach(list => {
          const items = list.querySelectorAll('li');
          items.forEach(item => {
            textContent += 'â€¢ ' + item.textContent.trim() + '\n';
          });
          textContent += '\n';
        });

        // Process tables
        const tables = tempDiv.querySelectorAll('table');
        tables.forEach(table => {
          const rows = table.querySelectorAll('tr');
          rows.forEach(row => {
            const cells = row.querySelectorAll('td, th');
            const rowText = Array.from(cells).map(cell => cell.textContent.trim()).join(' | ');
            textContent += rowText + '\n';
          });
          textContent += '\n';
        });

        // If no structured content found, return plain text
        if (!textContent.trim()) {
          textContent = tempDiv.textContent || tempDiv.innerText || '';
        }

        return textContent.trim() || 'No readable content found in the HTML file.';
      }

      function convertCsvToText(csvContent) {
        try {
          const lines = csvContent.split('\n');
          let result = '';

          lines.forEach(line => {
            const cells = line.split(',').map(cell => cell.trim().replace(/^"|"$/g, ''));
            result += cells.join(' | ') + '\n';
          });

          return result || 'No data found in CSV file.';
        } catch (error) {
          console.error('Error parsing CSV:', error);
          return 'Error parsing CSV file. Content: ' + csvContent;
        }
      }
    }

    function startModuleCreation() {
      const title = document.querySelector('input[name="title"]').value;
      const department = document.getElementById('departmentSelect').value;
      const role = document.getElementById('roleSelect').value;
      const topic = document.querySelector('textarea[name="topic"]').value;

      // Fallback: if file input has a file but uploadedFiles wasn't populated (e.g. selecting same file twice)
      try {
        const fileInputEl = document.getElementById('fileInput');
        if (uploadedFiles.length === 0 && fileInputEl && fileInputEl.files && fileInputEl.files.length > 0) {
          uploadedFiles = [fileInputEl.files[0]];
        }
      } catch (e) {}

      if (!title || !department || !role || !topic) {
        // Close modal first, then show SweetAlert
        closeCurrentModal();
        Swal.fire({
          title: 'Missing Information',
          text: 'Please fill in all required fields.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
        return;
      }

      const selectedFile = uploadedFiles.length > 0 ? uploadedFiles[0] : null;

      // File upload is optional - no error if no file is uploaded
      if (!selectedFile) {
        // Close modal first, then show SweetAlert with success message
        closeCurrentModal();
        Swal.fire({
          title: 'Ready to Create Module',
          text: 'You can create your module content manually in the editor.',
          icon: 'info',
          confirmButtonText: 'Continue',
          confirmButtonColor: '#3b82f6'
        }).then((result) => {
          if (result.isConfirmed) {
            // Create URL parameters without file content
            const params = new URLSearchParams({
              title: title,
              department: department,
              role: role,
              topic: topic
            });

            // Redirect to create learning modules page
            window.location.href = 'create_learning_modules.php?' + params.toString();
          }
        });
        return;
      }

      closeCurrentModal();

      if (window.Swal) {
        Swal.fire({
          title: 'Processing file...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
      }

      const fileExt = (selectedFile && selectedFile.name) ? selectedFile.name.split('.').pop().toLowerCase() : '';

      if (fileExt === 'docx' && window.mammoth && typeof window.mammoth.convertToHtml === 'function') {
        const reader = new FileReader();

        reader.onload = function(e) {
          const arrayBuffer = e.target.result;
          window.mammoth.convertToHtml({
              arrayBuffer: arrayBuffer
            })
            .then(function(result) {
              const html = (result && result.value) ? String(result.value) : '';
              const payload = new FormData();
              payload.append('ajax', '1');
              payload.append('store_extracted', '1');
              payload.append('file_name', selectedFile.name);
              payload.append('content', html);

              return fetch('learning_module_repository.php', {
                method: 'POST',
                body: payload,
                credentials: 'same-origin'
              });
            })
            .then(function(res) {
              return res.json();
            })
            .then(function(data) {
              if (!data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Failed to process file.');
              }

              if (window.Swal) Swal.close();

              const params = new URLSearchParams({
                title: title,
                department: department,
                role: role,
                topic: topic,
                uploaded: '1'
              });
              if (data.file_name) params.append('file_name', data.file_name);
              window.location.href = 'create_learning_modules.php?' + params.toString();
            })
            .catch(function(err) {
              if (window.Swal) {
                Swal.fire({
                  title: 'Upload Failed',
                  text: err && err.message ? err.message : 'Upload failed.',
                  icon: 'error',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
              }
            });
        };

        reader.onerror = function() {
          if (window.Swal) {
            Swal.fire({
              title: 'Upload Failed',
              text: 'Failed to read the DOCX file in the browser.',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#3b82f6'
            });
          }
        };

        reader.readAsArrayBuffer(selectedFile);
        return;
      }

      const fd = new FormData();
      fd.append('ajax', '1');
      fd.append('extract_file', '1');
      fd.append('file', selectedFile);

      fetch('learning_module_repository.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Failed to process file.');
          }

          if (window.Swal) Swal.close();

          const params = new URLSearchParams({
            title: title,
            department: department,
            role: role,
            topic: topic,
            uploaded: '1'
          });

          if (data.file_name) {
            params.append('file_name', data.file_name);
          }

          window.location.href = 'create_learning_modules.php?' + params.toString();
        })
        .catch(err => {
          if (window.Swal) {
            Swal.fire({
              title: 'Upload Failed',
              text: err && err.message ? err.message : 'Upload failed.',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#3b82f6'
            });
          }
        });
    }

    // AJAX Functions
    function showNotification(message, type = 'success') {
      const notificationContainer = document.getElementById('notificationContainer');
      const notification = document.createElement('div');
      notification.className = `notification notification-${type}`;
      notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
      `;
      notificationContainer.appendChild(notification);

      // Trigger animation
      setTimeout(() => notification.classList.add('show'), 100);

      // Auto remove after 5 seconds
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
      }, 5000);
    }

    function updateModuleStatus(moduleId, newStatus, remarks = '') {
      const formData = new FormData();
      formData.append('update_status', '1');
      formData.append('module_id', moduleId);
      formData.append('new_status', newStatus);
      formData.append('remarks', remarks);
      formData.append('ajax', '1');

      return fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification(data.message, 'success');
            updateModuleUI(moduleId, newStatus);
            return true;
          } else {
            showNotification(data.message, 'error');
            return false;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('An error occurred while updating the module status.', 'error');
          return false;
        });
    }

    function deleteModule(moduleId) {
      // Close modal first, then show SweetAlert
      closeCurrentModal();

      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('delete_module', '1');
          formData.append('module_id', moduleId);
          formData.append('ajax', '1');

          fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: data.message,
                  icon: 'success',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
                removeModuleFromUI(moduleId);
              } else {
                Swal.fire({
                  title: 'Error!',
                  text: data.message,
                  icon: 'error',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: 'An error occurred while deleting the module.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
              });
            });
        }
      });
    }

    function postModule(moduleId) {
      // Close modal first, then show SweetAlert
      closeCurrentModal();

      Swal.fire({
        title: 'Post Module?',
        text: "Are you sure you want to post this module?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, post it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('post_module', '1');
          formData.append('module_id', moduleId);
          formData.append('ajax', '1');

          fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Posted!',
                  text: data.message,
                  icon: 'success',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
                updateModuleUI(moduleId, 'posted');
                removeModuleFromUI(moduleId);
              } else {
                Swal.fire({
                  title: 'Error!',
                  text: data.message,
                  icon: 'error',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: 'An error occurred while posting the module.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
              });
            });
        }
      });
    }

    function updateModuleUI(moduleId, newStatus) {
      const moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
      const statusBadge = document.getElementById(`status-badge-${moduleId}`);

      if (moduleCard && statusBadge) {
        moduleCard.setAttribute('data-status', newStatus);
        const statusTextMap = {
          approved: 'Approved',
          rejected: 'Rejected',
          compliance: 'For Compliance',
          'for-compliance': 'For Compliance',
          pending: 'Under Review',
          posted: 'Posted',
          hold: 'ON HOLD',
          draft: 'Draft'
        };
        statusBadge.textContent = statusTextMap[newStatus] || (newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
        statusBadge.className = `badge status-${newStatus}`;
      }
    }

    function removeModuleFromUI(moduleId) {
      const moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
      if (moduleCard) {
        moduleCard.style.opacity = '0';
        moduleCard.style.transform = 'scale(0.8)';
        setTimeout(() => {
          moduleCard.remove();
        }, 300);
      }
    }

    // Module Action Functions
    function viewModule(moduleId, buttonEl) {
      console.log('View module clicked:', moduleId);

      // Set current module ID
      currentModuleId = moduleId;

      // Get the module card to check its status
      let moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
      if (!moduleCard && buttonEl) {
        const maybeCard = buttonEl.closest ? buttonEl.closest('.module-card') : null;
        if (maybeCard) {
          moduleCard = maybeCard;
          const dsId = moduleCard.getAttribute('data-id');
          if (dsId !== null && dsId !== '') {
            currentModuleId = dsId;
            moduleId = dsId;
          }
        }
      }

      if (!moduleCard) {
        Swal.fire({
          title: 'Module Not Found',
          text: 'The requested module could not be found.',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
        return;
      }

      const status = moduleCard.getAttribute('data-status');
      console.log('Module status:', status);

      // Fetch module data
      const moduleData = getModuleData(moduleId);
      if (!moduleData) {
        Swal.fire({
          title: 'Data Not Found',
          text: 'Module data could not be retrieved.',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
        return;
      }

      currentModuleData = moduleData;
      moduleData.status = status; // Ensure status is set correctly

      // Determine which modal to show based on status
      switch (status) {
        case 'approved':
          showApprovedModal(moduleData);
          break;
        case 'pending':
          showApprovedModal(moduleData);
          break;
        case 'rejected':
          showRejectedModal(moduleData);
          break;
        case 'compliance':
          showComplianceModal(moduleData);
          break;
        case 'hold':
          showHoldModal(moduleData);
          break;
        default:
          Swal.fire({
            title: 'Viewing Module',
            text: 'Module ID: ' + moduleId,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
      }
    }

    // Helper function to get module data from the card
    function getModuleData(moduleId) {
      console.log('Getting module data for ID:', moduleId);

      const moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
      if (!moduleCard) {
        console.error('Module card not found for ID:', moduleId);
        return null;
      }

      // Extract data from the module card
      const title = moduleCard.querySelector('.card-title').textContent;

      // Get topic - find the paragraph that contains "Topic:"
      const topicElement = Array.from(moduleCard.querySelectorAll('p')).find(p => p.textContent.includes('Topic:'));
      const topic = topicElement ? topicElement.textContent.replace('Topic: ', '') : 'N/A';

      const department = moduleCard.getAttribute('data-department');

      // Get roles - find the second badge (role badge)
      const roleBadge = moduleCard.querySelectorAll('.badge-outline')[1];
      const roles = roleBadge ? roleBadge.textContent : 'N/A';

      // Get date - find the paragraph that contains "Date Added:"
      const dateElement = Array.from(moduleCard.querySelectorAll('p')).find(p => p.textContent.includes('Date Added:'));
      const created_at = dateElement ? dateElement.textContent.replace('Date Added: ', '') : 'N/A';

      // Get content and reasons from data attributes
      const content = moduleCard.getAttribute('data-content');
      const rejection_reason = moduleCard.getAttribute('data-rejection-reason');
      const compliance_reason = moduleCard.getAttribute('data-compliance-reason');
      const remarks = moduleCard.getAttribute('data-remarks');

      return {
        id: moduleId,
        title: title,
        topic: topic,
        department: department,
        roles: roles,
        content: content,
        status: moduleCard.getAttribute('data-status'),
        created_at: created_at,
        rejection_reason: rejection_reason,
        compliance_reason: compliance_reason,
        remarks: remarks
      };
    }

    // Modal Display Functions
    function showApprovedModal(moduleData) {
      console.log('Showing approved modal for:', moduleData.title);

      // Set modal title
      document.getElementById('approved-title').textContent = moduleData.title;

      // Set info section
      document.getElementById('approved-exam-title').textContent = moduleData.title;
      document.getElementById('approved-topic').textContent = moduleData.topic;
      document.getElementById('approved-department').textContent = formatDepartment(moduleData.department);
      document.getElementById('approved-role').textContent = moduleData.roles;
      document.getElementById('approved-status').textContent = (moduleData.status === 'pending') ? 'Under Review' : 'Approved';
      document.getElementById('approved-date').textContent = moduleData.created_at || 'N/A';

      const postBtn = document.getElementById('approved-post-btn');
      const editBtn = document.getElementById('approved-edit-btn');
      const convertBtn = document.getElementById('approved-convert-btn');

      if (moduleData.status === 'pending') {
        if (postBtn) postBtn.style.display = 'none';
        if (editBtn) editBtn.style.display = 'none';
      } else {
        if (postBtn) postBtn.style.display = '';
        if (editBtn) editBtn.style.display = '';
      }

      // Set document preview with ACTUAL content from database
      const previewElement = document.getElementById('approved-document-preview');
      if (moduleData.content && moduleData.content.trim() !== '') {
        previewElement.innerHTML = moduleData.content;
      } else {
        previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }

      // Set up action buttons - CLOSE MODAL FIRST, then show SweetAlert
      document.getElementById('approved-post-btn').onclick = function() {
        closeCurrentModal();
        postModule(moduleData.id);
      };

      document.getElementById('approved-edit-btn').onclick = function() {
        closeCurrentModal();
        editModule(moduleData.id);
      };

      if (convertBtn) {
        convertBtn.style.display = 'none';
      }

      document.getElementById('approved-download-file').onclick = function() {
        downloadModuleFile(moduleData);
      };

      document.getElementById('approved-view-full-content').onclick = function() {
        showFullContentModal(moduleData);
      };

      // Show the modal
      const modal = document.getElementById('approved_module_modal');
      if (modal) {
        currentModal = modal;
        modal.showModal();
      } else {
        console.error('Approved modal not found');
      }
    }

    function showRejectedModal(moduleData) {
      console.log('Showing rejected modal for:', moduleData.title);

      // Set modal title
      document.getElementById('rejected-title').textContent = moduleData.title;

      // Set reason section with actual data from database
      document.getElementById('rejected-reason').textContent = moduleData.rejection_reason || 'This module was rejected due to incomplete content and outdated information.';

      // Set info section
      document.getElementById('rejected-exam-title').textContent = moduleData.title;
      document.getElementById('rejected-topic').textContent = moduleData.topic;
      document.getElementById('rejected-department').textContent = formatDepartment(moduleData.department);
      document.getElementById('rejected-role').textContent = moduleData.roles;
      document.getElementById('rejected-status').textContent = 'Rejected';
      document.getElementById('rejected-date').textContent = moduleData.created_at || 'N/A';

      // Set document preview with ACTUAL content from database
      const previewElement = document.getElementById('rejected-document-preview');
      if (moduleData.content && moduleData.content.trim() !== '') {
        previewElement.innerHTML = moduleData.content;
      } else {
        previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }

      // Set up action buttons - ONLY DELETE BUTTON FOR REJECTED STATUS
      document.getElementById('rejected-delete-btn').onclick = function() {
        closeCurrentModal();
        deleteModule(moduleData.id);
      };

      document.getElementById('rejected-download-file').onclick = function() {
        downloadModuleFile(moduleData);
      };

      document.getElementById('rejected-view-full-content').onclick = function() {
        showFullContentModal(moduleData);
      };

      // Show the modal
      const modal = document.getElementById('rejected_module_modal');
      if (modal) {
        currentModal = modal;
        modal.showModal();
      } else {
        console.error('Rejected modal not found');
      }
    }

    function showComplianceModal(moduleData) {
      console.log('Showing compliance modal for:', moduleData.title);

      // Set modal title
      document.getElementById('compliance-title').textContent = moduleData.title;

      // Set reason section
      document.getElementById('compliance-reason').textContent = moduleData.compliance_reason || 'This module requires updates to meet compliance standards. Please review the following requirements.';

      // Set info section
      document.getElementById('compliance-exam-title').textContent = moduleData.title;
      document.getElementById('compliance-topic').textContent = moduleData.topic;
      document.getElementById('compliance-department').textContent = formatDepartment(moduleData.department);
      document.getElementById('compliance-role').textContent = moduleData.roles;
      document.getElementById('compliance-status').textContent = 'For Compliance';
      document.getElementById('compliance-date').textContent = moduleData.created_at || 'N/A';

      // Set document preview with ACTUAL content from database
      const previewElement = document.getElementById('compliance-document-preview');
      if (moduleData.content && moduleData.content.trim() !== '') {
        previewElement.innerHTML = moduleData.content;
      } else {
        previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }

      // Set up action buttons - CLOSE MODAL FIRST, then show SweetAlert
      document.getElementById('compliance-resubmit-btn').onclick = function() {
        closeCurrentModal();
        Swal.fire({
          title: 'Resubmit Module?',
          text: "Are you sure you want to resubmit this module for review?",
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, resubmit it!',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#10b981',
          cancelButtonColor: '#6b7280',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            // Update status back to pending (under review)
            updateModuleStatus(moduleData.id, 'pending');
          }
        });
      };

      document.getElementById('compliance-edit-btn').onclick = function() {
        closeCurrentModal();
        editModule(moduleData.id);
      };

      document.getElementById('compliance-download-file').onclick = function() {
        downloadModuleFile(moduleData);
      };

      document.getElementById('compliance-delete-btn').onclick = function() {
        closeCurrentModal();
        deleteModule(moduleData.id);
      };

      document.getElementById('compliance-view-full-content').onclick = function() {
        showFullContentModal(moduleData);
      };

      // Show the modal
      const modal = document.getElementById('compliance_module_modal');
      if (modal) {
        currentModal = modal;
        modal.showModal();
      } else {
        console.error('Compliance modal not found');
      }
    }

    // NEW: Hold Modal Function
    function showHoldModal(moduleData) {
      console.log('Showing hold modal for:', moduleData.title);

      // Set modal title
      document.getElementById('hold-title').textContent = moduleData.title;

      // Set reason section with actual data from database
      document.getElementById('hold-reason').textContent = moduleData.remarks || 'This module is currently on hold.';

      // Set info section
      document.getElementById('hold-exam-title').textContent = moduleData.title;
      document.getElementById('hold-topic').textContent = moduleData.topic;
      document.getElementById('hold-department').textContent = formatDepartment(moduleData.department);
      document.getElementById('hold-role').textContent = moduleData.roles;
      document.getElementById('hold-status').textContent = 'ON HOLD';
      document.getElementById('hold-date').textContent = moduleData.created_at || 'N/A';

      // Set document preview with ACTUAL content from database
      const previewElement = document.getElementById('hold-document-preview');
      if (moduleData.content && moduleData.content.trim() !== '') {
        previewElement.innerHTML = moduleData.content;
      } else {
        previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }

      // Set up action buttons - CLOSE MODAL FIRST, then show SweetAlert
      document.getElementById('hold-edit-btn').onclick = function() {
        closeCurrentModal();
        editModule(moduleData.id);
      };

      document.getElementById('hold-delete-btn').onclick = function() {
        closeCurrentModal();
        deleteModule(moduleData.id);
      };

      document.getElementById('hold-resume-btn').onclick = function() {
        closeCurrentModal();
        Swal.fire({
          title: 'Resume Module?',
          text: "Are you sure you want to resume this module?",
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, resume it!',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#10b981',
          cancelButtonColor: '#6b7280',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            updateModuleStatus(moduleData.id, 'posted', '').then((success) => {
              if (success) {
                removeModuleFromUI(moduleData.id);
              }
            });
          }
        });
      };

      document.getElementById('hold-download-file').onclick = function() {
        downloadModuleFile(moduleData);
      };

      document.getElementById('hold-view-full-content').onclick = function() {
        showFullContentModal(moduleData);
      };

      // Show the modal
      const modal = document.getElementById('hold_module_modal');
      if (modal) {
        currentModal = modal;
        modal.showModal();
      } else {
        console.error('Hold modal not found');
      }
    }

    // New function to show full content in a modal
    function showFullContentModal(moduleData) {
      console.log('Showing full content modal for:', moduleData.title);

      // Set modal title
      document.getElementById('full-content-title').textContent = moduleData.title + ' - Full Content';

      // Set full content
      const fullContentDisplay = document.getElementById('full-content-display');
      if (moduleData.content && moduleData.content.trim() !== '') {
        fullContentDisplay.innerHTML = moduleData.content;
      } else {
        fullContentDisplay.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }

      // Show the modal
      const modal = document.getElementById('full_content_modal');
      if (modal) {
        currentModal = modal;
        modal.showModal();
      } else {
        console.error('Full content modal not found');
      }
    }

    // Download Function
    function downloadModuleFile(moduleData) {
      // Create a blob with the content
      const content = moduleData.content || '<p>No content available</p>';
      const blob = new Blob([`
        <!DOCTYPE html>
        <html>
        <head>
          <title>${moduleData.title}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
            .title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
            .meta { color: #666; font-size: 14px; }
            .content { margin-top: 20px; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="title">${moduleData.title}</div>
            <div class="meta">
              Topic: ${moduleData.topic} | Department: ${formatDepartment(moduleData.department)} | 
              Role: ${moduleData.roles} | Status: ${moduleData.status}
            </div>
          </div>
          <div class="content">
            ${content}
          </div>
        </body>
        </html>
      `], {
        type: 'text/html'
      });

      // Create download link
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${moduleData.title.replace(/\s+/g, '_')}.html`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      // Also offer PDF download option with SweetAlert
      closeCurrentModal();
      Swal.fire({
        title: 'Download Complete',
        text: 'HTML file has been downloaded. Would you like to download as PDF instead?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Download PDF',
        cancelButtonText: 'No, thanks',
        confirmButtonColor: '#3b7280',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // In a real application, you would generate a PDF here
          showNotification('PDF download functionality would be implemented here with a PDF generation library.', 'success');
        }
      });
    }

    // Helper Functions
    function formatDepartment(department) {
      return department.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    const departmentRoles = {
      'front-office': [
        'Front Desk Manager',
        'Receptionist / Front Desk Officer',
        'Guest Service Agent / Concierge',
        'Reservation Agent',
        'Bellhop / Porter',
        'Front Office Supervisor'
      ],
      'housekeeping': [
        'Executive Housekeeper / Housekeeping Manager',
        'Floor Supervisor',
        'Room Attendant / Housekeeper',
        'Laundry Attendant',
        'Public Area Attendant',
        'Housekeeping Inspector'
      ],
      'food-beverage': [
        'F&B Manager / Director',
        'Restaurant Manager / Captain',
        'Waiter / Waitress / Server',
        'Bartender',
        'Banquet / Catering Coordinator',
        'F&B Supervisor'
      ],
      'kitchen': [
        'Executive Chef / Head Chef',
        'Sous Chef',
        'Line Cook / Station Chef',
        'Pastry Chef / Baker',
        'Kitchen Steward / Dishwasher',
        'Commis Chef'
      ],
      'sales-marketing': [
        'Sales & Marketing Manager',
        'Revenue Manager',
        'Event / Banquet Sales Coordinator',
        'Social Media / Marketing Executive',
        'Sales Executive',
        'Marketing Coordinator'
      ],
      'hr': [
        'HR Manager / Director',
        'Recruitment Officer',
        'Training & Development Specialist',
        'Payroll / HR Assistant',
        'HR Coordinator',
        'Employee Relations Specialist'
      ],
      'finance': [
        'Finance Manager / Controller',
        'Accountant',
        'Payroll Officer',
        'Cost Controller',
        'Accounts Payable/Receivable Clerk',
        'Financial Analyst'
      ],
      'engineering': [
        'Chief Engineer / Engineering Manager',
        'Maintenance Technician',
        'Electrician / Plumber',
        'HVAC Technician',
        'Carpenter',
        'Painter'
      ],
      'security': [
        'Security Manager / Supervisor',
        'Security Guard',
        'CCTV / Surveillance Officer',
        'Security Officer',
        'Surveillance Operator',
        'Access Control Officer'
      ]
    };

    departmentRoles['human-resources'] = departmentRoles['hr'];

    // Update roles based on department selection
    const departmentSelect = document.getElementById('departmentSelect');
    const roleSelect = document.getElementById('roleSelect');

    if (departmentSelect && roleSelect) {
      departmentSelect.addEventListener('change', function() {
        const department = this.value;

        // Clear existing options
        roleSelect.innerHTML = '';

        if (department && departmentRoles[department]) {
          // Enable the role select
          roleSelect.disabled = false;

          // Add default option
          const defaultOption = document.createElement('option');
          defaultOption.disabled = true;
          defaultOption.selected = true;
          defaultOption.textContent = 'Select Role';
          roleSelect.appendChild(defaultOption);

          // Add department-specific roles
          departmentRoles[department].forEach(role => {
            const option = document.createElement('option');
            option.value = role;
            option.textContent = role;
            roleSelect.appendChild(option);
          });
        } else {
          // Disable the role select if no department is selected
          roleSelect.disabled = true;
          const defaultOption = document.createElement('option');
          defaultOption.disabled = true;
          defaultOption.selected = true;
          defaultOption.textContent = 'Select Department First';
          roleSelect.appendChild(defaultOption);
        }
      });
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Learning Module Repository initialized');
      console.log('Total module cards found:', document.querySelectorAll('.module-card').length);

      // Initialize drag and drop functionality
      initDragAndDrop();

      // IDP-driven prefill / open upload modal
      try {
        const params = new URLSearchParams(window.location.search || '');
        const openUpload = params.get('open_upload');
        const preDept = params.get('department');
        const preRole = params.get('role');

        const deptEl = document.getElementById('departmentSelect');
        const roleEl = document.getElementById('roleSelect');
        const uploadModal = document.getElementById('upload_modal');

        if (deptEl && preDept) {
          deptEl.value = preDept;
          deptEl.dispatchEvent(new Event('change'));
        }

        if (roleEl && preRole) {
          const roleStr = String(preRole || '').trim();
          if (roleStr !== '') {
            roleEl.disabled = false;

            let found = false;
            Array.from(roleEl.options || []).forEach(function(opt) {
              if (String(opt.value || '') === roleStr) {
                found = true;
              }
            });
            if (!found) {
              const opt = document.createElement('option');
              opt.value = roleStr;
              opt.textContent = roleStr;
              roleEl.appendChild(opt);
            }

            roleEl.value = roleStr;
          }
        }

        if (openUpload === '1' && uploadModal && typeof uploadModal.showModal === 'function') {
          currentModal = uploadModal;
          uploadModal.showModal();
        }
      } catch (e) {}

      // Make functions globally accessible
      window.viewModule = viewModule;
      window.editModule = editModule;
      window.downloadModuleFile = downloadModuleFile;
      window.showFullContentModal = showFullContentModal;
      window.showDraftsModal = showDraftsModal;
      window.continueDraft = continueDraft;
      window.deleteDraft = deleteDraft;
      console.log('All functions are now accessible globally');
    });
  </script>

  <!-- Include JavaScript file -->
  <script src="../../JS/learning_modules_repository.js"></script>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>

</html>