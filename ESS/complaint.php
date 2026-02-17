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
$terms_accepted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $subject = trim((string)($_POST['subject'] ?? ''));
  $category = trim((string)($_POST['category'] ?? 'Workplace Grievance'));
  $category_other = trim((string)($_POST['category_other'] ?? ''));
  $incident_date = trim((string)($_POST['incident_date'] ?? ''));
  $details = trim((string)($_POST['details'] ?? ''));
  $terms_accepted = ((string)($_POST['terms_accepted'] ?? '')) === '1';

  if ($subject === '' || $details === '' || $incident_date === '') {
    $error_message = 'Please fill in the required fields.';
  } elseif (!$terms_accepted) {
    $error_message = 'Please accept the Terms & Conditions.';
  } elseif (in_array(strtolower($category), ['other', 'others'], true) && $category_other === '') {
    $error_message = 'Please specify the incident category.';
  } else {
    $incDt = DateTime::createFromFormat('Y-m-d', $incident_date);
    $today = new DateTime('today');
    $minDt = (clone $today)->modify('-1 month');
    if (!$incDt) {
      $error_message = 'Invalid incident date.';
    } elseif ($incDt < $minDt || $incDt > $today) {
      $error_message = 'Incident date must be within the last 1 month.';
    }
  }

  if ($error_message === '') {
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
          $catOther = in_array(strtolower($category), ['other', 'others'], true) ? $category_other : '';
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
            $terms_accepted = false;
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
  'Others',
];

$complaintHistory = [];
if ($conn && $employeeId) {
  $stmt = mysqli_prepare(
    $conn,
    'SELECT id, subject, category, category_other, incident_date, workflow_status, status, created_at FROM complaints WHERE employee_id = ? ORDER BY created_at DESC, id DESC LIMIT 50'
  );
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) {
        $complaintHistory[] = $row;
      }
    }
    mysqli_stmt_close($stmt);
  }
}
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

        <div class="card bg-base-100 shadow-sm border border-base-200">
          <div class="card-body">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-base-200">
                  <i data-lucide="message-square-warning" class="w-5 h-5"></i>
                </div>
                <div>
                  <h1 class="text-xl md:text-2xl font-bold text-gray-800">File a Complaint</h1>
                  <p class="text-sm text-gray-500">Submit an incident report and we’ll route it to the appropriate team.</p>
                </div>
              </div>
              <button type="button" id="complaintHistoryBtn" class="btn btn-sm hr2-primary-btn">
                <span>View History</span>
              </button>
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
                <input type="date" name="incident_date" id="incident_date" class="input input-bordered" value="<?php echo htmlspecialchars($incident_date); ?>" required />
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
                <textarea name="category_other" id="category-other" class="textarea textarea-bordered w-full" rows="2" placeholder="Type the category..."><?php echo htmlspecialchars($category_other); ?></textarea>
              </div>

              <div class="form-control">
                <label class="label"><span class="label-text">Details</span></label>
                <textarea name="details" class="textarea textarea-bordered min-h-[140px]" placeholder="Provide as much detail as possible..." required><?php echo htmlspecialchars($details); ?></textarea>
              </div>

              <div class="form-control">
                <label class="label"><span class="label-text">Optional Attachment (screenshots, documents)</span></label>
                <input type="file" name="attachment" class="file-input file-input-bordered w-full" accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" />
              </div>

              <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                  <input type="checkbox" name="terms_accepted" value="1" class="checkbox" <?php echo $terms_accepted ? 'checked' : ''; ?> required />
                  <span class="label-text">
                    I agree to the <button type="button" class="link link-primary" id="complaintTermsLink">Terms &amp; Conditions</button>
                  </span>
                </label>
              </div>

              <div class="pt-2">
                <button class="btn hr2-primary-btn w-full" type="submit" id="complaintSubmitBtn" disabled>
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

  <dialog id="complaintHistoryModal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Complaint History</h3>
          <p class="text-sm text-gray-500">Previous incident reports you have submitted.</p>
        </div>
        <button type="button" class="btn btn-sm hr2-primary-btn" id="complaintHistoryClose" aria-label="Close">✕</button>
      </div>

      <div class="divider my-4"></div>

      <?php if (empty($complaintHistory)): ?>
        <p class="text-sm text-gray-500">No complaint history found.</p>
      <?php else: ?>
        <div class="overflow-x-auto max-h-[400px]">
          <table class="table table-zebra table-sm w-full">
            <thead class="text-xs text-gray-500 uppercase">
              <tr>
                <th class="whitespace-nowrap">ID</th>
                <th>Subject</th>
                <th class="whitespace-nowrap">Category</th>
                <th class="whitespace-nowrap">Incident Date</th>
                <th class="whitespace-nowrap">Status</th>
                <th class="whitespace-nowrap">Workflow</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($complaintHistory as $row): ?>
                <?php
                $cat = (string)($row['category'] ?? '');
                $catOther = (string)($row['category_other'] ?? '');
                $catLower = strtolower(trim($cat));
                $catDisplay = $cat;
                if (in_array($catLower, ['other', 'others'], true) && $catOther !== '') {
                  $catDisplay = 'Other - ' . $catOther;
                }
                $status = (string)($row['status'] ?? '');
                $wf = (string)($row['workflow_status'] ?? '');
                $incDate = (string)($row['incident_date'] ?? '');
                ?>
                <tr>
                  <td class="whitespace-nowrap text-xs text-gray-500">#<?php echo (int)($row['id'] ?? 0); ?></td>
                  <td class="max-w-[220px]">
                    <div class="truncate" title="<?php echo htmlspecialchars((string)($row['subject'] ?? ''), ENT_QUOTES); ?>">
                      <?php echo htmlspecialchars((string)($row['subject'] ?? ''), ENT_QUOTES); ?>
                    </div>
                  </td>
                  <td class="whitespace-nowrap"><?php echo htmlspecialchars($catDisplay, ENT_QUOTES); ?></td>
                  <td class="whitespace-nowrap"><?php echo htmlspecialchars($incDate, ENT_QUOTES); ?></td>
                  <td class="whitespace-nowrap">
                    <?php if ($status !== ''): ?>
                      <span class="badge badge-outline"><?php echo htmlspecialchars($status, ENT_QUOTES); ?></span>
                    <?php else: ?>
                      <span class="text-gray-400 text-xs">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td class="whitespace-nowrap">
                    <?php if ($wf !== ''): ?>
                      <span class="badge badge-outline badge-primary"><?php echo htmlspecialchars($wf, ENT_QUOTES); ?></span>
                    <?php else: ?>
                      <span class="text-gray-400 text-xs">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="modal-action">
        <button type="button" class="btn hr2-primary-btn" id="complaintHistoryClose2">Close</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="complaintTermsModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg">Terms &amp; Conditions</h3>
          <p class="text-sm text-gray-500">Please review before submitting your complaint.</p>
        </div>
        <button type="button" class="btn btn-sm hr2-outline-btn" id="complaintTermsClose" aria-label="Close">✕</button>
      </div>

      <div class="divider my-4"></div>

      <div class="space-y-4 text-sm text-gray-700">
        <div>
          <div class="font-semibold text-gray-900"><strong>1. Purpose of the Complaint System</strong></div>
          <div class="mt-1">
            The ESS Complaint System is intended to provide employees with a formal, secure, and confidential platform to report workplace-related concerns, grievances, or incidents in accordance with company policies and applicable laws of the Republic of the Philippines.
          </div>
        </div>

        <div>
          <div class="font-semibold text-gray-900"><strong>2. Confidentiality and Data Privacy</strong></div>
          <div class="mt-1">
            All information submitted shall be treated with strict confidentiality and processed in compliance with Republic Act No. 10173 (Data Privacy Act of 2012). Access to complaint records shall be limited to authorized personnel only and used solely for investigation, resolution, and legal compliance purposes.
          </div>
        </div>

        <div>
          <div class="font-semibold text-gray-900"><strong>3. Accuracy and Truthfulness of Information</strong></div>
          <div class="mt-1">
            The complainant certifies that all information, statements, and supporting documents submitted are true, correct, and complete to the best of their knowledge. The submission of false, misleading, or malicious complaints may result in administrative or disciplinary action in accordance with company policies and applicable laws
          </div>
        </div>

        <div>
          <div class="font-semibold text-gray-900"><strong>4. Legal Basis and Governing Law</strong></div>
          <div class="mt-1">
            All complaints shall be governed by and construed in accordance with the laws of the Republic of the Philippines, including but not limited to:
          </div>
          <div class="mt-2 space-y-1">
            <div>Presidential Decree No. 442 (Labor Code of the Philippines)</div>
            <div>Republic Act No. 7877 (Anti-Sexual Harassment Act)</div>
            <div>Republic Act No. 11313 (Safe Spaces Act)</div>
            <div>Republic Act No. 9710 (Magna Carta of Women)</div>
            <div>Republic Act No. 10173 (Data Privacy Act of 2012)</div>
            <div>Republic Act No. 10175 (Cybercrime Prevention Act)</div>
            <div>Other applicable laws, rules, and company policies</div>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <button type="button" class="btn hr2-outline-btn" id="complaintTermsCancelBtn">Close</button>
        <button type="button" class="btn hr2-primary-btn" id="complaintTermsAgreeBtn">I Agree</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <script>
    lucide.createIcons();

    (function() {
      const sel = document.getElementById('category');
      const wrap = document.getElementById('category-other-wrap');
      const other = document.getElementById('category-other');
      const termsLink = document.getElementById('complaintTermsLink');
      const termsModal = document.getElementById('complaintTermsModal');
      const termsClose = document.getElementById('complaintTermsClose');
      const termsCancel = document.getElementById('complaintTermsCancelBtn');
      const termsAgree = document.getElementById('complaintTermsAgreeBtn');
      const termsCb = document.querySelector('input[name="terms_accepted"]');
      const submitBtn = document.getElementById('complaintSubmitBtn');
      const historyBtn = document.getElementById('complaintHistoryBtn');
      const historyModal = document.getElementById('complaintHistoryModal');
      const historyClose = document.getElementById('complaintHistoryClose');
      const historyClose2 = document.getElementById('complaintHistoryClose2');

      const syncOther = function() {
        const v = sel ? String(sel.value || '') : '';
        const isOther = v.trim().toLowerCase() === 'other' || v.trim().toLowerCase() === 'others';
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

      document.addEventListener('DOMContentLoaded', function() {
        if (sel) sel.addEventListener('change', syncOther);
        syncOther();
        enhanceTextarea(document.getElementById('subject'));
        enhanceTextarea(document.getElementById('category-other'));

        const syncSubmit = () => {
          if (!submitBtn) return;
          submitBtn.disabled = !(termsCb && termsCb.checked);
        };
        if (termsCb) termsCb.addEventListener('change', syncSubmit);
        syncSubmit();

        const closeHistory = () => {
          if (historyModal) historyModal.close();
        };
        if (historyBtn) {
          historyBtn.addEventListener('click', () => {
            if (historyModal) historyModal.showModal();
          });
        }
        if (historyClose) historyClose.addEventListener('click', closeHistory);
        if (historyClose2) historyClose2.addEventListener('click', closeHistory);

        const closeTerms = () => {
          if (termsModal) termsModal.close();
        };
        if (termsLink) {
          termsLink.addEventListener('click', () => {
            if (termsModal) termsModal.showModal();
          });
        }
        if (termsClose) termsClose.addEventListener('click', closeTerms);
        if (termsCancel) termsCancel.addEventListener('click', closeTerms);
        if (termsAgree) {
          termsAgree.addEventListener('click', () => {
            const cb = document.querySelector('input[name="terms_accepted"]');
            if (cb) cb.checked = true;
            syncSubmit();
            closeTerms();
          });
        }

        const inc = document.getElementById('incident_date');
        if (inc) {
          const d = new Date();
          const yyyy = d.getFullYear();
          const mm = String(d.getMonth() + 1).padStart(2, '0');
          const dd = String(d.getDate()).padStart(2, '0');
          const max = `${yyyy}-${mm}-${dd}`;
          const oneMonthAgo = new Date(d);
          oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
          const y2 = oneMonthAgo.getFullYear();
          const m2 = String(oneMonthAgo.getMonth() + 1).padStart(2, '0');
          const d2 = String(oneMonthAgo.getDate()).padStart(2, '0');
          const min = `${y2}-${m2}-${d2}`;
          inc.min = min;
          inc.max = max;
        }
      });
    })();
  </script>
</body>

</html>