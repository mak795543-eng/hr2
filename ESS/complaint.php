<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$success_message = '';
$error_message = '';

$subject = '';
$category = 'Workplace Grievance';
$category_other = '';
$incident_date = '';
$details = '';
$attachment_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim((string)($_POST['subject'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'Workplace Grievance'));
    $category_other = trim((string)($_POST['category_other'] ?? ''));
    $incident_date = trim((string)($_POST['incident_date'] ?? ''));
    $details = trim((string)($_POST['details'] ?? ''));

    if ($subject === '' || $details === '' || $incident_date === '') {
        $error_message = 'Please fill in the required fields.';
    } elseif ($category === 'Other' && $category_other === '') {
        $error_message = 'Please specify the incident category.';
    } else {
        if (!$employeeId) {
            $error_message = 'Unable to identify employee. Please login again.';
        } elseif (!$conn) {
            $error_message = 'Database connection unavailable.';
        } else {
            $desc = $details;

            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'complaints';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $attachment_path = '';
            if (isset($_FILES['attachment']) && is_array($_FILES['attachment'])) {
                $f = $_FILES['attachment'];
                $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
                $tmp = (string)($f['tmp_name'] ?? '');
                $name = (string)($f['name'] ?? '');
                $size = (int)($f['size'] ?? 0);

                if ($err !== UPLOAD_ERR_NO_FILE) {
                    if ($err !== UPLOAD_ERR_OK) {
                        $error_message = 'Attachment upload failed. Please try again.';
                    } elseif ($size > 10 * 1024 * 1024) {
                        $error_message = 'Attachment must be 10MB or less.';
                    } elseif ($tmp === '' || !is_uploaded_file($tmp)) {
                        $error_message = 'Attachment upload failed. Please try again.';
                    } else {
                        $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
                        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                        if (!in_array($ext, $allowed, true)) {
                            $error_message = 'Unsupported attachment type.';
                        } else {
                            $safe = bin2hex(random_bytes(8)) . ($ext !== '' ? ('.' . $ext) : '');
                            $dest = $uploadDir . DIRECTORY_SEPARATOR . $safe;
                            if (!@move_uploaded_file($tmp, $dest)) {
                                $error_message = 'Attachment upload failed. Please try again.';
                            } else {
                                $attachment_path = 'uploads/complaints/' . $safe;
                            }
                        }
                    }
                }
            }

            if ($error_message !== '') {
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO complaints (employee_id, subject, description, status, category, category_other, incident_date, attachment_path, workflow_status, seen_by_employee, seen_by_assignee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)'
                );
            if (!$stmt) {
                $error_message = 'Failed to submit complaint. Please try again.';
            } else {
                $status = 'Open';
                $wf = 'For Approval';
                $catOther = ($category === 'Other') ? $category_other : '';
                $inc = $incident_date !== '' ? $incident_date : null;
                mysqli_stmt_bind_param($stmt, 'issssssss', $employeeId, $subject, $desc, $status, $category, $catOther, $inc, $attachment_path, $wf);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if (!$ok) {
                    $error_message = 'Failed to submit complaint. Please try again.';
                } else {
                    $success_message = 'Your incident report has been submitted.';
                    $subject = '';
                    $details = '';
                    $category = 'Workplace Grievance';
                    $category_other = '';
                    $incident_date = '';
                    $attachment_path = '';
                }
            }
            }
        }
    }
}

$categories = [
    'Workplace Grievance',
    'Harassment',
    'Safety Concern',
    'Policy Violation',
    'Facilities / Maintenance',
    'Payroll / Benefits',
    'Other',
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complaint / Feedback</title>
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
        <div class="max-w-3xl mx-auto">
          <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-base-200">
                  <i data-lucide="message-square-warning" class="w-5 h-5"></i>
                </div>
                <div>
                  <h1 class="text-xl md:text-2xl font-bold text-gray-800">Lodge a Complaint / Feedback</h1>
                  <p class="text-sm text-gray-500">Submit an incident report and we’ll route it to the appropriate team.</p>
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

              <form method="POST" class="mt-6 space-y-4" enctype="multipart/form-data">
                <div class="form-control">
                  <label class="label"><span class="label-text">Subject</span></label>
                  <textarea name="subject" id="subject" class="textarea textarea-bordered w-full" rows="2" placeholder="Brief summary of the issue..." required><?php echo htmlspecialchars($subject); ?></textarea>
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Date of Incident</span></label>
                  <input type="date" name="incident_date" class="input input-bordered" value="<?php echo htmlspecialchars($incident_date); ?>" required />
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Incident Category</span></label>
                  <select name="category" id="category" class="select select-bordered">
                    <?php foreach ($categories as $c): ?>
                      <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($category === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-control hidden" id="category-other-wrap">
                  <label class="label"><span class="label-text">Specify Category</span></label>
                  <input name="category_other" id="category-other" class="input input-bordered" placeholder="Type the category..." value="<?php echo htmlspecialchars($category_other); ?>" />
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Details</span></label>
                  <textarea name="details" class="textarea textarea-bordered min-h-[140px]" placeholder="Provide as much detail as possible..." required><?php echo htmlspecialchars($details); ?></textarea>
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Optional Attachment (screenshots, documents)</span></label>
                  <input type="file" name="attachment" class="file-input file-input-bordered w-full" accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" />
                </div>

                <div class="pt-2">
                  <button class="btn btn-error w-full" type="submit">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span class="ml-2">Submit Incident Report</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();

    (function () {
      const sel = document.getElementById('category');
      const wrap = document.getElementById('category-other-wrap');
      const other = document.getElementById('category-other');

      const syncOther = function () {
        const v = sel ? String(sel.value || '') : '';
        const isOther = v === 'Other';
        if (wrap) {
          if (isOther) wrap.classList.remove('hidden');
          else wrap.classList.add('hidden');
        }
        if (other) {
          other.required = isOther;
          if (!isOther) other.value = '';
        }
      };

      const enhanceTextarea = (ta) => {
        if (!ta) return;
        if (ta.dataset && ta.dataset.autogrowApplied === '1') return;
        if (ta.dataset) ta.dataset.autogrowApplied = '1';
        ta.style.resize = 'vertical';
        ta.style.overflowY = 'hidden';
        const autoGrow = () => {
          ta.style.height = 'auto';
          ta.style.height = String(ta.scrollHeight) + 'px';
        };
        ta.addEventListener('input', autoGrow);
        autoGrow();
      };

      document.addEventListener('DOMContentLoaded', function () {
        if (sel) sel.addEventListener('change', syncOther);
        syncOther();
        enhanceTextarea(document.getElementById('subject'));
      });
    })();
  </script>
</body>
</html>
