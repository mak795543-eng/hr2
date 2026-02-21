<?php


require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);
$employeeGender = '';
if ($conn && $employeeId) {
  $stmtG = mysqli_prepare($conn, 'SELECT gender FROM employee_profiles WHERE employee_id = ? LIMIT 1');
  if ($stmtG) {
    mysqli_stmt_bind_param($stmtG, 'i', $employeeId);
    mysqli_stmt_execute($stmtG);
    $resG = mysqli_stmt_get_result($stmtG);
    $rowG = $resG ? mysqli_fetch_assoc($resG) : null;
    mysqli_stmt_close($stmtG);
    if (is_array($rowG)) {
      $employeeGender = strtolower(trim((string)($rowG['gender'] ?? '')));
    }
  }
}

$success_message = '';
$error_message = '';

$myReqStats = [
  'total' => 0,
  'pending' => 0,
  'for_compliance' => 0,
];

$balances = [
  ['label' => 'Vacation', 'remaining' => 80, 'used' => 40, 'total' => 120, 'color' => 'text-gray-900', 'bar' => 'progress-primary'],
  ['label' => 'Sick Leave', 'remaining' => 64, 'used' => 16, 'total' => 80, 'color' => 'text-gray-900', 'bar' => 'progress-success'],
  ['label' => 'Personal', 'remaining' => 16, 'used' => 8, 'total' => 24, 'color' => 'text-gray-900', 'bar' => 'progress-warning'],
];

$recentRequests = [
  ['type' => 'Vacation', 'range' => 'Feb 12 — Feb 15', 'days' => 4, 'status' => 'Pending'],
  ['type' => 'Sick Leave', 'range' => 'Jan 05 — Jan 05', 'days' => 1, 'status' => 'Approved'],
  ['type' => 'Personal', 'range' => 'Dec 22 — Dec 23', 'days' => 2, 'status' => 'Approved'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
  $leaveType = trim((string)($_POST['leave_type'] ?? ''));
  $startDate = trim((string)($_POST['start_date'] ?? ''));
  $endDate = trim((string)($_POST['end_date'] ?? ''));
  $reason = trim((string)($_POST['reason'] ?? ''));
  $termsAccepted = (string)($_POST['terms_accepted'] ?? '') === '1';
  $declarationConfirmed = (string)($_POST['declaration_confirmed'] ?? '') === '1';

  $allowedLeaveTypes = [
    'Vacation',
    'Sick Leave',
    'Emergency Leave',
    'Service Incentive Leave',
    'Bereavement Leave',
    'Solo Parent Leave',
    'Maternity Leave',
    'Paternity Leave',
  ];

  $maxDays = [
    'Vacation' => 15,
    'Sick Leave' => 15,
    'Emergency Leave' => 5,
    'Service Incentive Leave' => 5,
    'Bereavement Leave' => 5,
    'Solo Parent Leave' => 7,
    'Maternity Leave' => 105,
    'Paternity Leave' => 7,
  ];

  $genderIsFemale = ($employeeGender === 'female' || $employeeGender === 'f');
  $genderIsMale = ($employeeGender === 'male' || $employeeGender === 'm');

  if (!$termsAccepted) {
    $error_message = 'You must accept the Terms and Conditions before submitting.';
  } elseif (!$declarationConfirmed) {
    $error_message = 'You must confirm that the information provided is true and correct.';
  } elseif ($leaveType === '' || $startDate === '') {
    $error_message = 'Please fill in the required fields.';
  } elseif ($startDate < date('Y-m-d')) {
    $error_message = 'Start date cannot be in the past.';
  } elseif (!in_array($leaveType, $allowedLeaveTypes, true)) {
    $error_message = 'Invalid leave type selected.';
  } elseif (!$employeeId) {
    $error_message = 'Unable to identify employee. Please login again.';
  } elseif (!$conn) {
    $error_message = 'Database connection unavailable.';
  } elseif ($genderIsFemale && $leaveType === 'Paternity Leave') {
    $error_message = 'Invalid leave type selected.';
  } elseif ($genderIsMale && $leaveType === 'Maternity Leave') {
    $error_message = 'Invalid leave type selected.';
  } else {
    $status = 'Pending';

    $days = (int)($_POST['days'] ?? 1);
    if ($days <= 0) $days = 1;
    $max = (int)($maxDays[$leaveType] ?? 15);
    if ($days > $max) $days = $max;

    $sd = strtotime($startDate);
    if ($sd !== false) {
      $endDate = date('Y-m-d', $sd + (max(1, $days) - 1) * 86400);
    }

    $dupFound = false;
    $stmtDup = mysqli_prepare($conn, 'SELECT id FROM leave_requests WHERE employee_id = ? AND leave_type = ? AND start_date = ? AND end_date = ? AND status = ? LIMIT 1');
    if ($stmtDup) {
      $pending = 'Pending';
      mysqli_stmt_bind_param($stmtDup, 'issss', $employeeId, $leaveType, $startDate, $endDate, $pending);
      mysqli_stmt_execute($stmtDup);
      $resDup = mysqli_stmt_get_result($stmtDup);
      $dupFound = (bool)($resDup && mysqli_fetch_assoc($resDup));
      mysqli_stmt_close($stmtDup);
    }

    if ($dupFound) {
      $error_message = 'Duplicate request detected for the same dates and type.';
    } else {
      $stmt = mysqli_prepare($conn, 'INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
      if (!$stmt) {
        $error_message = 'Failed to submit leave request.';
      } else {
        mysqli_stmt_bind_param($stmt, 'isssss', $employeeId, $leaveType, $startDate, $endDate, $reason, $status);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
          $error_message = 'Failed to submit leave request.';
        } else {
          header('Location: leaverequest.php?submitted=1');
          exit;
        }
      }
    }
  }
}

if (((string)($_GET['submitted'] ?? '')) === '1') {
  $success_message = 'Leave request submitted successfully.';
}

if ($conn && $employeeId) {
  $stmtStats = mysqli_prepare($conn, 'SELECT status, COUNT(*) AS c FROM leave_requests WHERE employee_id = ? GROUP BY status');
  if ($stmtStats) {
    mysqli_stmt_bind_param($stmtStats, 'i', $employeeId);
    mysqli_stmt_execute($stmtStats);
    $resStats = mysqli_stmt_get_result($stmtStats);
    if ($resStats) {
      while ($r = mysqli_fetch_assoc($resStats)) {
        $st = strtolower(trim((string)($r['status'] ?? '')));
        $c = (int)($r['c'] ?? 0);
        $myReqStats['total'] += $c;
        if ($st === 'pending') $myReqStats['pending'] = $c;
        if ($st === 'for compliance') $myReqStats['for_compliance'] = $c;
      }
    }
    mysqli_stmt_close($stmtStats);
  }

  $recentRequests = [];
  $stmt = mysqli_prepare($conn, 'SELECT leave_type, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5');
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) {
        $s = (string)($row['start_date'] ?? '');
        $e = (string)($row['end_date'] ?? '');
        $days = 0;
        if ($s !== '' && $e !== '') {
          $sd = strtotime($s);
          $ed = strtotime($e);
          if ($sd !== false && $ed !== false && $ed >= $sd) {
            $days = (int)floor(($ed - $sd) / 86400) + 1;
          }
        }
        $recentRequests[] = [
          'type' => (string)($row['leave_type'] ?? ''),
          'range' => $s . ' — ' . $e,
          'days' => $days,
          'status' => (string)($row['status'] ?? 'Pending'),
        ];
      }
    }
    mysqli_stmt_close($stmt);
  }
}

function leaveStatusBadge($status)
{
  $s = strtolower(trim($status));
  return match ($s) {
    'approved' => 'badge-success',
    'pending' => 'badge-warning',
    'rejected' => 'badge-error',
    default => 'badge-ghost',
  };
}
require('../partials/header.php');
?>
<style>
  .swal2-container {
    z-index: 5000 !important;
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
          <div class="mb-6 flex items-start justify-between gap-3">
            <div>
              <h1 class="text-2xl font-bold text-gray-800">Leave Request</h1>
              <p class="text-gray-600">Submit a new leave request and track approvals.</p>
            </div>
            <div class="pt-1">
              <button class="btn btn-sm hr2-primary-btn" type="button" id="openTermsBtn">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span class="ml-2">Terms and Conditions</span>
              </button>
            </div>
          </div>

          <?php if ($success_message !== ''): ?>
            <div class="alert alert-success mb-6">
              <i data-lucide="check-circle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($error_message !== ''): ?>
            <div class="alert alert-error mb-6">
              <i data-lucide="alert-triangle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
          <?php endif; ?>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card hr2-summary-card bg-base-100 shadow-sm border border-base-200">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">My Total Leave Request</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$myReqStats['total']; ?></div>
                    <div class="text-xs text-gray-500">All time</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="layers" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card bg-base-100 shadow-sm border border-base-200">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending Request</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$myReqStats['pending']; ?></div>
                    <div class="text-xs text-gray-500">Awaiting approval</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="hourglass" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card bg-base-100 shadow-sm border border-base-200">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Remaining Leave Request</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$myReqStats['for_compliance']; ?></div>
                    <div class="text-xs text-gray-500">For compliance</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="clipboard-check" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <h2 class="card-title">New Leave Request</h2>

                  <form method="POST" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4" id="leaveRequestForm">
                    <input type="hidden" name="submit_leave" value="1" />
                    <input type="hidden" name="terms_accepted" value="0" id="termsAcceptedInput" />

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LEAVE TYPE</span></label>
                      <select name="leave_type" class="select select-bordered" required id="leaveTypeSelect">
                        <option value="Vacation">Vacation</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                        <option value="Service Incentive Leave">Service Incentive Leave</option>
                        <option value="Solo Parent Leave">Solo Parent Leave</option>
                        <option value="Bereavement Leave">Bereavement Leave</option>
                        <?php if ($employeeGender === 'female' || $employeeGender === 'f'): ?>
                          <option value="Maternity Leave">Maternity Leave</option>
                        <?php elseif ($employeeGender === 'male' || $employeeGender === 'm'): ?>
                          <option value="Paternity Leave">Paternity Leave</option>
                        <?php else: ?>
                          <option value="Maternity Leave">Maternity Leave</option>
                          <option value="Paternity Leave">Paternity Leave</option>
                        <?php endif; ?>
                      </select>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">NUMBER OF DAYS</span></label>
                      <input name="days" id="leaveDays" class="input input-bordered" type="number" min="1" value="1" />
                      <label class="label"><span id="leaveDaysWarning" class="label-text-alt text-error hidden"></span></label>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">START DATE</span></label>
                      <input name="start_date" id="leaveStartDate" class="input input-bordered" type="date" required min="<?php echo htmlspecialchars(date('Y-m-d')); ?>" />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">END DATE</span></label>
                      <input name="end_date" id="leaveEndDate" class="input input-bordered" type="date" required readonly min="<?php echo htmlspecialchars(date('Y-m-d')); ?>" />
                    </div>

                    <div class="form-control md:col-span-2">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">REASON / NOTE (OPTIONAL)</span></label>
                      <textarea name="reason" class="textarea textarea-bordered" rows="4" placeholder="Briefly explain your request..."></textarea>
                    </div>

                    <div class="form-control md:col-span-2">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">SUPPORTING FILE / PHOTO (OPTIONAL)</span></label>
                      <input type="file" name="supporting_file" class="file-input file-input-bordered w-full" accept="image/*,.pdf,.doc,.docx" />
                      <label class="label">
                        <span class="label-text-alt text-[11px] text-gray-400">You may upload a medical certificate, document, or photo if needed.</span>
                      </label>
                    </div>

                    <div class="form-control md:col-span-2">
                      <label class="cursor-pointer flex items-start gap-3">
                        <input type="checkbox" name="declaration_confirmed" id="declarationConfirmed" value="1" class="checkbox checkbox-primary mt-1" />
                        <span class="text-xs text-gray-600 leading-snug">I confirm that the information provided is true and correct.</span>
                      </label>
                    </div>

                    <div class="md:col-span-2 mt-2">
                      <button class="btn hr2-primary-btn w-full" type="button" id="openTermsSubmitBtn">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span class="ml-2">Submit Request</span>
                      </button>
                      <div class="mt-3 text-[11px] text-gray-400 italic">
                        Requests must be submitted at least 2 weeks in advance for vacations longer than 3 days.
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div>
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <h2 class="card-title">Recent Requests</h2>
                    <button class="btn btn-ghost btn-sm" type="button">
                      <i data-lucide="history" class="w-4 h-4"></i>
                    </button>
                  </div>

                  <div class="mt-4 space-y-4">
                    <?php foreach ($recentRequests as $r): ?>
                      <div class="flex items-start justify-between">
                        <div>
                          <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($r['type']); ?></div>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars($r['range']); ?></div>
                          <div class="mt-1 text-[11px] text-gray-500 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span><?php echo (int)$r['days']; ?> working day<?php echo ((int)$r['days'] === 1) ? '' : 's'; ?></span>
                          </div>
                        </div>
                        <span class="badge <?php echo leaveStatusBadge($r['status']); ?> badge-sm"><?php echo htmlspecialchars(strtoupper($r['status'])); ?></span>
                      </div>
                      <div class="divider my-0"></div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <dialog id="termsModal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg" id="termsTitle">Terms and Conditions</h3>
          <p class="text-sm text-gray-500" id="termsSubtitle">Please review the leave request policy.</p>
        </div>
        <button class="btn btn-sm btn-ghost" type="button" id="termsCloseBtn" aria-label="Close">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <div class="divider my-4"></div>

      <div class="prose max-w-none text-sm text-gray-700" style="max-height: 55vh; overflow: auto;">
        <h4><strong>1. General Provisions</strong></h4>
        <p>Leave requests must be submitted through the ESS portal and approved by the appropriate supervisor or HR representative.</p>
        <p>Employees are responsible for ensuring that their leave request is accurate, complete, and filed within the required timeframe.</p>
        <p>Leave will be credited and deducted based on the company’s approved leave balances and policies.</p>

        <h4><strong>2. Vacation Leave (VL)</strong></h4>
        <p>Employees are entitled to 10–15 days of vacation leave per year, depending on tenure and company policy.</p>
        <p>Leave requests cannot exceed the employee’s available vacation leave balance.</p>
        <p>Maximum per request is generally 5 consecutive working days.</p>
        <p>Requests beyond this may require additional approval from HR or management.</p>

        <h4><strong>3. Sick Leave (SL)</strong></h4>
        <p>Employees are entitled to 10–15 days of sick leave per year.</p>
        <p>Medical certificates may be required for absences of 2 or more consecutive days.</p>
        <p>Sick leave cannot exceed the employee’s available sick leave balance.</p>

        <h4><strong>4. Emergency Leave (EL)</strong></h4>
        <p>Emergency leave is intended for unforeseen personal or family emergencies.</p>
        <p>Employees may request 1–2 days per occurrence, with a maximum of 3–5 days per year.</p>
        <p>Requests exceeding this limit require supervisor approval.</p>

        <h4><strong>5. Service Incentive Leave (SIL)</strong></h4>
        <p>Employees are entitled to 5 days of service incentive leave per year in accordance with Philippine labor law.</p>
        <p>Unused SIL may be converted to cash or carried over according to company policy.</p>

        <h4><strong>6. Bereavement Leave</strong></h4>
        <p>Bereavement leave of 3–5 days per incident is granted for the death of an immediate family member.</p>
        <p>Proper documentation may be required.</p>

        <h4><strong>7. Maternity / Paternity Leave</strong></h4>
        <p>Maternity Leave: Eligible female employees are entitled to 105 days of leave per childbirth as mandated by law, with additional benefits per company policy.</p>
        <p>Paternity Leave: Eligible married male employees are entitled to 7 days of leave per childbirth as mandated by law.</p>

        <h4><strong>8. Solo Parent Leave</strong></h4>
        <p>Eligible solo parent employees are entitled to 7 days of leave per year in accordance with RA 8972.</p>
        <p>This leave may be used for parental responsibilities, including child care and related activities.</p>
        <p>Supporting documentation may be required to confirm eligibility.</p>

        <h4><strong>9. Leave Approval &amp; Responsibility</strong></h4>
        <p>Submission of leave does not guarantee approval; all requests are subject to verification and supervisor/HR approval.</p>
        <p>Employees must ensure that leave does not interfere with operational requirements unless otherwise approved.</p>
        <p>Employees must update their leave request in the ESS if circumstances change.</p>

        <h4><strong>10. Governing Law</strong></h4>
        <p>All leave requests submitted through the ESS shall be governed by and construed in accordance with the laws of the Republic of the Philippines, including but not limited to:</p>
        <p>Presidential Decree No. 442 – Labor Code of the Philippines</p>
        <p>Republic Act No. 11210 – 105-Day Expanded Maternity Leave Law</p>
        <p>Republic Act No. 8187 – Paternity Leave Act of 1996</p>
        <p>Republic Act No. 9710 – Magna Carta of Women</p>
        <p>Republic Act No. 8972, as amended by RA 11861 – Solo Parents’ Welfare Act</p>
        <p>Republic Act No. 10173 – Data Privacy Act of 2012</p>
        <p>Applicable DOLE issuances and company policies</p>

        <h4><strong>11. Data Privacy and Confidentiality</strong></h4>
        <p>All personal information and documents submitted shall be processed in compliance with the Data Privacy Act of 2012 and used solely for leave administration, payroll processing, and legal compliance.</p>

        <h4><strong>12. Documentation and Verification</strong></h4>
        <p>Management reserves the right to require supporting documents (e.g., medical certificates, proof of emergency, government-issued documents) to validate the leave request. Failure to provide required documents may result in denial or adjustment of the leave.</p>

        <h4><strong>13. Acknowledgment</strong></h4>
        <p>By clicking “I Accept” below, I acknowledge that I have read, understood, and agree to comply with these Terms and Conditions regarding leave requests through the Employee Self-Service system.</p>
      </div>

      <div class="mt-4" id="termsAcceptWrap">
        <label class="flex items-start gap-3">
          <input type="checkbox" class="checkbox" id="termsAcceptCheckbox" />
          <span class="text-sm text-gray-700">I Accept All Terms and Conditions</span>
        </label>
      </div>

      <div class="modal-action">
        <button type="button" class="btn hr2-outline-btn" id="termsCancelBtn">Close</button>
        <button type="button" class="btn hr2-primary-btn" id="termsSubmitBtn">Submit</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <script>
    (function() {
      if (window.lucide) window.lucide.createIcons();

      const leaveTypeEl = document.getElementById('leaveTypeSelect');
      const daysEl = document.getElementById('leaveDays');
      const startEl = document.getElementById('leaveStartDate');
      const endEl = document.getElementById('leaveEndDate');

      const openTermsBtn = document.getElementById('openTermsBtn');
      const openTermsSubmitBtn = document.getElementById('openTermsSubmitBtn');

      const termsModal = document.getElementById('termsModal');
      const termsCloseBtn = document.getElementById('termsCloseBtn');
      const termsCancelBtn = document.getElementById('termsCancelBtn');
      const termsSubmitBtn = document.getElementById('termsSubmitBtn');
      const termsAcceptWrap = document.getElementById('termsAcceptWrap');
      const termsAcceptCheckbox = document.getElementById('termsAcceptCheckbox');
      const termsAcceptedInput = document.getElementById('termsAcceptedInput');
      const formEl = document.getElementById('leaveRequestForm');
      const declarationCheckbox = document.getElementById('declarationConfirmed');
      let lastLeaveType = null;

      const policy = {
        'Vacation': {
          max: 15
        },
        'Sick Leave': {
          max: 15
        },
        'Emergency Leave': {
          max: 5
        },
        'Service Incentive Leave': {
          max: 5
        },
        'Bereavement Leave': {
          max: 5
        },
        'Maternity Leave': {
          max: 105
        },
        'Paternity Leave': {
          max: 7
        },
        'Solo Parent Leave': {
          max: 7
        },
      };

      const warningEl = document.getElementById('leaveDaysWarning');

      function updateDaysWarning() {
        if (!leaveTypeEl || !daysEl || !warningEl) return;
        const t = String(leaveTypeEl.value || 'Vacation');
        const p = policy[t];
        if (!p) {
          warningEl.classList.add('hidden');
          warningEl.textContent = '';
          return;
        }
        const v = getDayInt();
        const exceeded = v > (p.max || 1);
        if (exceeded) {
          warningEl.classList.remove('hidden');
          warningEl.textContent = 'Maximum ' + String(p.max) + ' days for ' + t;
        } else {
          warningEl.classList.add('hidden');
          warningEl.textContent = '';
        }
      }

      const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

      function addDays(dateStr, deltaDays) {
        const d = new Date(dateStr);
        if (Number.isNaN(d.getTime())) return '';
        d.setDate(d.getDate() + deltaDays);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
      }

      function getDayInt() {
        const v = parseInt(String(daysEl && daysEl.value || '1'), 10);
        return Number.isFinite(v) && v > 0 ? v : 1;
      }

      function syncEndFromStartAndDays() {
        if (!startEl || !endEl) return;
        const s = String(startEl.value || '').trim();
        const d = getDayInt();
        if (!s) return;
        endEl.value = addDays(s, Math.max(1, d) - 1);
      }

      function syncDaysFromStartEndMax5() {
        if (!startEl || !endEl || !daysEl) return;
        const s = String(startEl.value || '').trim();
        const e = String(endEl.value || '').trim();
        if (!s || !e) return;
        const sd = new Date(s);
        const ed = new Date(e);
        if (Number.isNaN(sd.getTime()) || Number.isNaN(ed.getTime())) return;
        const diff = Math.floor((ed.getTime() - sd.getTime()) / 86400000) + 1;
        daysEl.value = String(clamp(diff, 1, 5));
      }

      function applyPolicy() {
        if (!leaveTypeEl || !daysEl || !endEl) return;
        const t = String(leaveTypeEl.value || 'Vacation');
        const p = policy[t] || policy['Vacation'];
        const wasSick = lastLeaveType === 'Sick Leave';
        const isSick = t === 'Sick Leave';
        if (startEl) {
          if (isSick) {
            const today = todayStr();
            startEl.value = today;
            startEl.readOnly = true;
          } else {
            startEl.readOnly = false;
            if (wasSick) {
              startEl.value = '';
            }
          }
        }
        daysEl.readOnly = false;
        daysEl.max = String(p.max);
        if (getDayInt() < 1) daysEl.value = '1';
        endEl.readOnly = true;
        syncEndFromStartAndDays();
        updateDaysWarning();
        if (endEl && wasSick && !isSick) {
          endEl.value = '';
        }
        lastLeaveType = t;
      }

      function openTerms(mode) {
        if (!termsModal) return;
        const isSubmit = mode === 'submit';
        if (termsAcceptWrap) termsAcceptWrap.classList.toggle('hidden', !isSubmit);
        if (termsSubmitBtn) termsSubmitBtn.classList.toggle('hidden', !isSubmit);
        if (termsAcceptCheckbox) termsAcceptCheckbox.checked = false;
        if (termsAcceptedInput) termsAcceptedInput.value = '0';
        termsModal.showModal();
      }

      function closeTerms() {
        if (termsModal) termsModal.close();
      }

      if (leaveTypeEl) leaveTypeEl.addEventListener('change', applyPolicy);
      if (daysEl) daysEl.addEventListener('input', () => {
        syncEndFromStartAndDays();
        updateDaysWarning();
      });

      function todayStr() {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
      }

      function applyMinDates() {
        const min = todayStr();
        if (startEl) {
          startEl.min = min;
          if (String(startEl.value || '') !== '' && String(startEl.value) < min) startEl.value = min;
        }
        if (endEl) endEl.min = min;
      }

      if (startEl) startEl.addEventListener('change', () => {
        applyMinDates();
        syncEndFromStartAndDays();
        updateDaysWarning();
      });

      if (openTermsBtn) openTermsBtn.addEventListener('click', () => openTerms('view'));

      function validateAndOpenTerms() {
        if (!leaveTypeEl || !daysEl || !startEl || !endEl) {
          if (window.Swal) {
            Swal.fire({
              icon: 'error',
              title: 'Incomplete Form',
              text: 'Please complete all required fields.',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn hr2-primary-btn'
              }
            });
          }
          return;
        }
        const t = String(leaveTypeEl.value || '').trim();
        const s = String(startEl.value || '').trim();
        const p = policy[t];
        const d = getDayInt();
        const max = p ? (p.max || 1) : 1;
        if (!t || !s || d < 1) {
          if (window.Swal) {
            Swal.fire({
              icon: 'error',
              title: 'Incomplete Form',
              text: 'Leave type, start date, and days are required.',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn hr2-primary-btn'
              }
            });
          }
          return;
        }
        if (d > max) {
          if (window.Swal) {
            Swal.fire({
              icon: 'warning',
              title: 'Days Exceeded',
              text: 'Maximum ' + String(max) + ' days allowed for ' + t + '.',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn hr2-primary-btn'
              }
            });
          }
          return;
        }
        const reasonEl = document.querySelector('textarea[name="reason"]');
        const reasonEmpty = !reasonEl || String(reasonEl.value || '').trim() === '';
        if (reasonEmpty && window.Swal) {
          Swal.fire({
            icon: 'info',
            title: 'Reason Recommended',
            text: 'It is recommended to provide a reason for your request.',
            showCancelButton: true,
            confirmButtonText: 'Fill In',
            cancelButtonText: 'Continue',
            buttonsStyling: false,
            customClass: {
              confirmButton: 'btn hr2-primary-btn',
              cancelButton: 'btn hr2-outline-btn'
            }
          }).then((res) => {
            if (res.isConfirmed && reasonEl) {
              reasonEl.focus();
            } else {
              openTerms('submit');
            }
          });
          return;
        }
        if (!declarationCheckbox || !declarationCheckbox.checked) {
          if (window.Swal) {
            Swal.fire({
              icon: 'warning',
              title: 'Declaration Required',
              text: 'Please confirm that the information provided is true and correct.',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn hr2-primary-btn'
              }
            });
          }
          return;
        }
        openTerms('submit');
      }
      if (openTermsSubmitBtn) openTermsSubmitBtn.addEventListener('click', validateAndOpenTerms);
      if (termsCloseBtn) termsCloseBtn.addEventListener('click', closeTerms);
      if (termsCancelBtn) termsCancelBtn.addEventListener('click', closeTerms);

      if (termsSubmitBtn) {
        let isSubmitting = false;
        termsSubmitBtn.addEventListener('click', () => {
          if (!formEl) return;
          if (!declarationCheckbox || !declarationCheckbox.checked) {
            if (window.Swal) {
              Swal.fire({
                icon: 'warning',
                title: 'Declaration Required',
                text: 'Please confirm that the information provided is true and correct.',
                buttonsStyling: false,
                customClass: {
                  confirmButton: 'btn hr2-primary-btn'
                }
              });
            }
            return;
          }
          if (!termsAcceptCheckbox || !termsAcceptCheckbox.checked) {
            if (window.Swal) {
              Swal.fire({
                icon: 'warning',
                title: 'Terms Required',
                text: 'Please accept the Terms and Conditions to continue.',
                buttonsStyling: false,
                customClass: {
                  confirmButton: 'btn hr2-primary-btn'
                }
              });
            }
            return;
          }
          if (isSubmitting) return;
          isSubmitting = true;
          if (termsSubmitBtn) termsSubmitBtn.setAttribute('disabled', 'disabled');
          if (termsAcceptedInput) termsAcceptedInput.value = '1';
          formEl.submit();
        });
      }

      applyMinDates();
      applyPolicy();

      const submitted = <?php echo json_encode($success_message !== ''); ?>;
      if (submitted && window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'Submitted',
          text: 'Leave request submitted successfully.',
          buttonsStyling: false,
          customClass: {
            confirmButton: 'btn hr2-primary-btn'
          }
        });
      }
    })();
  </script>
</body>

</html>
<?php require('../partials/footer.php') ?>