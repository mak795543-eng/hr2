<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

$requiredDocs = [
    'Identification (ID/Passport)',
    'Signed Policy Acknowledgment',
    'Updated CV/Resume',
    'Degree Certificate',
];

function ess_is_compliance(string $remarks): bool {
    return str_starts_with(trim($remarks), '[COMPLIANCE]');
}

function computeRequiredStatus($conn, $employeeId, $requiredType) {
    if (!$conn || !$employeeId) {
        return ['state' => 'pending', 'label' => 'Pending'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT status, remarks FROM submitted_documents WHERE employee_id = ? AND document_type = ? ORDER BY submitted_at DESC LIMIT 1'
    );

    if (!$stmt) {
        return ['state' => 'pending', 'label' => 'Pending'];
    }

    mysqli_stmt_bind_param($stmt, 'is', $employeeId, $requiredType);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!is_array($row)) {
        return ['state' => 'pending', 'label' => 'Pending'];
    }

    $status = strtolower(trim((string)($row['status'] ?? 'Pending')));
    $remarks = (string)($row['remarks'] ?? '');

    if ($status === 'approved') return ['state' => 'done', 'label' => 'Completed'];
    if ($status === 'rejected') return ['state' => 'rejected', 'label' => 'Rejected'];
    if ($status === 'pending' && ess_is_compliance($remarks)) return ['state' => 'compliance', 'label' => 'For Compliance'];
    return ['state' => 'pending', 'label' => 'Pending'];
}

function allowedExt($name) {
    $ext = strtolower((string)pathinfo((string)$name, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    return in_array($ext, $allowed, true);
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_document'])) {
    $requiredType = trim((string)($_POST['required_type'] ?? ''));

    if ($requiredType === '' || !in_array($requiredType, $requiredDocs, true)) {
        $error_message = 'Please select a required document type.';
    } elseif (!isset($_FILES['document']) || !is_array($_FILES['document'])) {
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
                    $remarks = '';

                    $stmt = mysqli_prepare(
                        $conn,
                        'INSERT INTO submitted_documents (employee_id, document_title, document_type, file_path, status, remarks) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    if (!$stmt) {
                        $error_message = 'Failed to save record. Please try again.';
                    } else {
                        mysqli_stmt_bind_param($stmt, 'isssss', $employeeId, $orig, $requiredType, $fileRel, $status, $remarks);
                        $ok = mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        if (!$ok) {
                            $error_message = 'Failed to save submission. Please try again.';
                        } else {
                            $success_message = 'Document submitted successfully. Awaiting approval.';
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Document</title>
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
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">Submit Document</h1>
              <p class="text-sm text-gray-500">Upload documents requested by HR and track their status.</p>
            </div>
            <div class="flex items-center gap-2">
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

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
              <div class="card-body">
                <h2 class="font-semibold text-gray-800">Required Documents</h2>
                <div class="mt-3 space-y-2">
                  <?php foreach ($requiredDocs as $rd): ?>
                    <?php $st = computeRequiredStatus($conn, $employeeId, $rd); ?>
                    <div class="flex items-center justify-between rounded-xl border border-base-200 px-4 py-3">
                      <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($rd); ?></div>
                      <div class="flex items-center gap-2">
                        <?php if ($st['state'] === 'done'): ?>
                          <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                        <?php elseif ($st['state'] === 'rejected'): ?>
                          <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                        <?php elseif ($st['state'] === 'compliance'): ?>
                          <i data-lucide="clipboard-check" class="w-5 h-5 text-blue-600"></i>
                        <?php else: ?>
                          <i data-lucide="clock" class="w-5 h-5 text-yellow-500"></i>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($st['label']); ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="card bg-base-100 border border-base-200 shadow-sm">
              <div class="card-body">
                <div class="flex items-center justify-center">
                  <div class="w-full max-w-md">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
                        <i data-lucide="cloud-upload" class="w-7 h-7 text-blue-600"></i>
                      </div>
                      <h2 class="mt-4 font-semibold text-gray-900">Upload New Document</h2>
                      <p class="text-sm text-gray-500 mt-1">Upload any certificates, identification or policy documents requested by HR.</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="mt-6 space-y-3">
                      <input type="hidden" name="submit_document" value="1" />

                      <div class="form-control">
                        <label class="label"><span class="label-text">Document Type</span></label>
                        <select name="required_type" class="select select-bordered" required>
                          <option value="" selected disabled>Select required document</option>
                          <?php foreach ($requiredDocs as $rd): ?>
                            <option value="<?php echo htmlspecialchars($rd); ?>"><?php echo htmlspecialchars($rd); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-control">
                        <label class="label"><span class="label-text">File</span></label>
                        <input name="document" type="file" class="file-input file-input-bordered w-full" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.txt,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required />
                        <label class="label"><span class="label-text-alt text-gray-500">Max 10MB. PDF or image files can be previewed.</span></label>
                      </div>

                      <button class="btn btn-primary w-full" type="submit">
                        <i data-lucide="file-up" class="w-4 h-4"></i>
                        <span class="ml-2">Select File</span>
                      </button>

                      <div class="text-center">
                        <a href="mydocuments.php" class="link link-primary text-sm">Go to Document Repository</a>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
