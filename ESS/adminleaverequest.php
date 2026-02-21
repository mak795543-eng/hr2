<?php


require_once __DIR__ . '/db.php';

$rawRole = strtolower(trim((string)($_SESSION['role'] ?? 'guest')));
$allowedRoles = ['admin', 'supervisor', 'hr_manager', 'manager'];
if (!in_array($rawRole, $allowedRoles, true)) {
  header('Location: dashboard.php');
  exit;
}

$success_message = '';
$error_message = '';

$filterStatus = strtolower(trim((string)($_GET['status'] ?? 'all')));
$allowedFilters = ['all', 'pending', 'approved', 'rejected', 'for compliance'];
if (!in_array($filterStatus, $allowedFilters, true)) {
  $filterStatus = 'all';
}

$q = trim((string)($_GET['q'] ?? ''));

function leaveStatusBadgeAdmin($status): string
{
  $s = strtolower(trim((string)$status));
  return match ($s) {
    'approved' => 'badge-success',
    'pending' => 'badge-warning',
    'rejected' => 'badge-error',
    'for compliance' => 'badge-info',
    default => 'badge-ghost',
  };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave'])) {
  $leaveId = (int)($_POST['leave_id'] ?? 0);
  $uiStatus = trim((string)($_POST['status'] ?? ''));
  $remarks = trim((string)($_POST['remarks'] ?? ''));

  $allowedUi = ['Approved', 'Rejected', 'For Compliance', 'Pending'];

  if ($leaveId <= 0 || !in_array($uiStatus, $allowedUi, true)) {
    $error_message = 'Invalid leave request.';
  } elseif (!$conn) {
    $error_message = 'Database connection unavailable.';
  } elseif (strtolower($uiStatus) === 'rejected' && $remarks === '') {
    $error_message = 'Rejection remarks are required.';
  } elseif (strtolower($uiStatus) === 'for compliance' && $remarks === '') {
    $error_message = 'Compliance remarks are required.';
  } else {
    $dbStatus = $uiStatus;
    $actorId = ess_employee_id($conn);
    $actor = is_int($actorId) ? $actorId : 0;
    $now = date('Y-m-d H:i:s');

    $setApprover = ($dbStatus === 'Approved' || $dbStatus === 'Rejected');
    $approvedBy = $setApprover ? $actor : null;
    $approvedAt = $setApprover ? $now : null;

    $stmt = mysqli_prepare(
      $conn,
      'UPDATE leave_requests SET status = ?, remarks = ?, approved_by = ?, approved_at = ? WHERE id = ?'
    );
    if (!$stmt) {
      $error_message = 'Failed to update leave request.';
    } else {
      mysqli_stmt_bind_param($stmt, 'ssisi', $dbStatus, $remarks, $approvedBy, $approvedAt, $leaveId);
      $ok = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);

      if (!$ok) {
        $error_message = 'Failed to update leave request.';
      } else {
        $employeeTargetId = 0;
        $leaveType = '';
        $startDate = '';
        $endDate = '';
        $stmtMeta = mysqli_prepare($conn, 'SELECT employee_id, leave_type, start_date, end_date FROM leave_requests WHERE id = ? LIMIT 1');
        if ($stmtMeta) {
          mysqli_stmt_bind_param($stmtMeta, 'i', $leaveId);
          mysqli_stmt_execute($stmtMeta);
          $resMeta = mysqli_stmt_get_result($stmtMeta);
          $rowMeta = $resMeta ? mysqli_fetch_assoc($resMeta) : null;
          mysqli_stmt_close($stmtMeta);
          if (is_array($rowMeta)) {
            $employeeTargetId = (int)($rowMeta['employee_id'] ?? 0);
            $leaveType = (string)($rowMeta['leave_type'] ?? '');
            $startDate = (string)($rowMeta['start_date'] ?? '');
            $endDate = (string)($rowMeta['end_date'] ?? '');
          }
        }

        if ($employeeTargetId > 0) {
          $notifType = 'Leave Update';
          $title = '';
          $message = '';
          $stLower = strtolower(trim($dbStatus));
          if ($stLower === 'approved') {
            $title = 'Leave Request Approved';
            $message = 'The leave requested is approved, you may now leave.';
          } elseif ($stLower === 'rejected') {
            $title = 'Leave Request Rejected';
            $message = $remarks !== '' ? ('Reason: ' . $remarks) : 'Rejected.';
          } elseif ($stLower === 'for compliance') {
            $title = 'Leave Request For Compliance';
            $message = $remarks !== '' ? ('Reason: ' . $remarks) : 'For compliance.';
          } else {
            $title = 'Leave Request Updated';
            $message = $remarks;
          }

          $range = trim($startDate) !== '' && trim($endDate) !== '' ? ($startDate . ' - ' . $endDate) : '';
          $meta = trim($leaveType) !== '' ? ('Leave: ' . $leaveType . ($range !== '' ? (' (' . $range . ')') : '') . '. ' . $message) : $message;

          $notifKey = sha1('leave_update|' . $leaveId . '|' . $dbStatus . '|' . $now);
          $notifLink = 'leaverequest.php';
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
            mysqli_stmt_bind_param($stmtNotif, 'issssss', $employeeTargetId, $notifKey, $notifType, $title, $meta, $notifLink, $notifDate);
            @mysqli_stmt_execute($stmtNotif);
            mysqli_stmt_close($stmtNotif);
          }
        }

        $success_message = 'Leave request updated.';
      }
    }

    if ($error_message === '' && $success_message !== '') {
      $qs = [];
      if ($filterStatus !== 'all') $qs['status'] = $filterStatus;
      if ($q !== '') $qs['q'] = $q;
      $redirect = basename((string)$_SERVER['PHP_SELF']);
      $url = $redirect . (count($qs) ? ('?' . http_build_query($qs)) : '');
      header('Location: ' . $url);
      exit;
    }
  }
}

$summary = [
  'total' => 0,
  'pending' => 0,
  'approved' => 0,
  'rejected' => 0,
  'for_compliance' => 0,
];

$rows = [];

if ($conn) {
  try {
    $res = mysqli_query($conn, 'SELECT status, COUNT(*) AS c FROM leave_requests GROUP BY status');
    if ($res) {
      while ($r = mysqli_fetch_assoc($res)) {
        $st = strtolower(trim((string)($r['status'] ?? '')));
        $c = (int)($r['c'] ?? 0);
        $summary['total'] += $c;
        if ($st === 'pending') $summary['pending'] = $c;
        if ($st === 'approved') $summary['approved'] = $c;
        if ($st === 'rejected') $summary['rejected'] = $c;
        if ($st === 'for compliance') $summary['for_compliance'] = $c;
      }
    }
  } catch (Throwable $e) {
  }

  $where = [];
  $params = [];
  $types = '';

  if ($filterStatus !== 'all') {
    $where[] = 'lr.status = ?';
    $types .= 's';
    $params[] = ucwords($filterStatus);
    if ($filterStatus === 'for compliance') {
      $params[count($params) - 1] = 'For Compliance';
    }
  }

  if ($q !== '') {
    $where[] = '(e.employee_no LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR CONCAT(e.first_name, " ", e.last_name) LIKE ?)';
    $types .= 'ssss';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
  }

  $sql = 'SELECT lr.id, lr.employee_id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.remarks, lr.created_at, lr.approved_at, e.employee_no, e.first_name, e.last_name, e.department '
    . 'FROM leave_requests lr '
    . 'LEFT JOIN employees e ON e.id = lr.employee_id '
    . (count($where) ? ('WHERE ' . implode(' AND ', $where) . ' ') : '')
    . 'ORDER BY lr.created_at DESC';

  $stmt = mysqli_prepare($conn, $sql);
  if ($stmt) {
    if ($types !== '') {
      mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
      $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
  }
}
require('../partials/header.php');
?>

<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-800">Leave Request Approval</h1>
              <p class="text-gray-600">Review and manage employee leave requests.</p>
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

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="card hr2-summary-card shadow-sm">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Requests</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$summary['total']; ?></div>
                    <div class="text-xs text-gray-500">All time</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="layers" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card shadow-sm">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$summary['pending']; ?></div>
                    <div class="text-xs text-gray-500">For review</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="clock" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card shadow-sm">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">For Compliance</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$summary['for_compliance']; ?></div>
                    <div class="text-xs text-gray-500">Waiting requirements</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card shadow-sm">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$summary['approved']; ?></div>
                    <div class="text-xs text-gray-500">Completed</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="check-circle" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="card hr2-summary-card shadow-sm">
              <div class="card-body">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rejected</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)$summary['rejected']; ?></div>
                    <div class="text-xs text-gray-500">Closed</div>
                  </div>
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="x-circle" class="w-5 h-5 text-blue-600"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
            <div class="card-body">
              <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-xs font-semibold text-gray-500">SEARCH</span></label>
                  <input name="q" value="<?php echo htmlspecialchars($q); ?>" class="input input-bordered" placeholder="Employee no. / name" />
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text text-xs font-semibold text-gray-500">STATUS</span></label>
                  <select name="status" class="select select-bordered">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="for compliance" <?php echo $filterStatus === 'for compliance' ? 'selected' : ''; ?>>For Compliance</option>
                    <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                  </select>
                </div>

                <div class="form-control md:self-end">
                  <button class="btn hr2-primary-btn" type="submit">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span class="ml-2">Apply Filters</span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
              <div class="flex items-center justify-between gap-3">
                <h2 class="card-title">Requests</h2>
                <div class="text-xs text-gray-500"><?php echo (int)count($rows); ?> result(s)</div>
              </div>

              <div class="overflow-x-auto mt-4">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th>Leave</th>
                      <th>Dates</th>
                      <th>Days</th>
                      <th>Submitted</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$conn): ?>
                      <tr>
                        <td colspan="7" class="text-center text-gray-500">Database connection unavailable.</td>
                      </tr>
                    <?php elseif (count($rows) === 0): ?>
                      <tr>
                        <td colspan="7" class="text-center text-gray-500">No leave requests found.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($rows as $r): ?>
                        <?php
                        $s = (string)($r['start_date'] ?? '');
                        $e = (string)($r['end_date'] ?? '');
                        $days = 0;
                        if ($s !== '' && $e !== '') {
                          $sd = strtotime($s);
                          $ed = strtotime($e);
                          if ($sd !== false && $ed !== false && $ed >= $sd) {
                            $days = (int)floor(($ed - $sd) / 86400) + 1;
                          }
                        }
                        $employeeName = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
                        $employeeNo = (string)($r['employee_no'] ?? '');
                        $dept = (string)($r['department'] ?? '');
                        $status = (string)($r['status'] ?? 'Pending');
                        $reason = (string)($r['reason'] ?? '');
                        $remarks = (string)($r['remarks'] ?? '');
                        $submittedAt = (string)($r['created_at'] ?? '');
                        ?>
                        <tr>
                          <td>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($employeeName !== '' ? $employeeName : 'Employee'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($employeeNo !== '' ? $employeeNo : ('ID #' . (int)($r['employee_id'] ?? 0))); ?><?php echo $dept !== '' ? (' • ' . htmlspecialchars($dept)) : ''; ?></div>
                          </td>
                          <td>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars((string)($r['leave_type'] ?? '')); ?></div>
                            <?php if (trim($reason) !== ''): ?>
                              <div class="text-xs text-gray-500 line-clamp-2"><?php echo htmlspecialchars($reason); ?></div>
                            <?php endif; ?>
                          </td>
                          <td class="text-gray-700"><?php echo htmlspecialchars($s . ' — ' . $e); ?></td>
                          <td class="text-gray-700"><?php echo (int)$days; ?></td>
                          <td class="text-gray-700"><?php echo htmlspecialchars($submittedAt !== '' ? date('M d, Y g:i A', strtotime($submittedAt)) : ''); ?></td>
                          <td>
                            <span class="badge <?php echo leaveStatusBadgeAdmin($status); ?> badge-sm"><?php echo htmlspecialchars(strtoupper($status)); ?></span>
                            <?php if (trim($remarks) !== ''): ?>
                              <div class="mt-1 text-[11px] text-gray-500 line-clamp-2"><?php echo htmlspecialchars($remarks); ?></div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="flex flex-wrap gap-2">
                              <button
                                type="button"
                                class="btn btn-sm hr2-outline-btn"
                                data-action="view"
                                data-id="<?php echo (int)($r['id'] ?? 0); ?>"
                                data-employee="<?php echo htmlspecialchars($employeeName); ?>"
                                data-employeeno="<?php echo htmlspecialchars($employeeNo); ?>"
                                data-dept="<?php echo htmlspecialchars($dept); ?>"
                                data-leavetype="<?php echo htmlspecialchars((string)($r['leave_type'] ?? '')); ?>"
                                data-start="<?php echo htmlspecialchars($s); ?>"
                                data-end="<?php echo htmlspecialchars($e); ?>"
                                data-reason="<?php echo htmlspecialchars($reason); ?>"
                                data-status="<?php echo htmlspecialchars($status); ?>"
                                data-remarks="<?php echo htmlspecialchars($remarks); ?>">View</button>

                              <button type="button" class="btn btn-sm hr2-primary-btn" data-action="approve" data-id="<?php echo (int)($r['id'] ?? 0); ?>">Approve</button>
                              <button type="button" class="btn btn-sm hr2-outline-btn" data-action="compliance" data-id="<?php echo (int)($r['id'] ?? 0); ?>">For Compliance</button>
                              <button type="button" class="btn btn-sm btn-error" data-action="reject" data-id="<?php echo (int)($r['id'] ?? 0); ?>">Reject</button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <dialog id="leave-view-modal" class="modal">
            <div class="modal-box w-11/12 max-w-3xl">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="font-bold text-lg" id="lv-title">Leave Request</h3>
                  <div class="text-sm text-gray-500" id="lv-sub"></div>
                </div>
                <button type="button" class="btn btn-sm btn-ghost" id="lv-close">✕</button>
              </div>

              <div class="divider my-4"></div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <div class="text-xs font-semibold text-gray-500">EMPLOYEE</div>
                  <div class="font-semibold text-gray-900" id="lv-emp"></div>
                  <div class="text-xs text-gray-500" id="lv-emp-meta"></div>
                </div>
                <div>
                  <div class="text-xs font-semibold text-gray-500">STATUS</div>
                  <div class="mt-1" id="lv-status"></div>
                </div>
                <div>
                  <div class="text-xs font-semibold text-gray-500">LEAVE TYPE</div>
                  <div class="font-semibold text-gray-900" id="lv-type"></div>
                </div>
                <div>
                  <div class="text-xs font-semibold text-gray-500">DATE RANGE</div>
                  <div class="text-gray-700" id="lv-range"></div>
                </div>
              </div>

              <div class="mt-4">
                <div class="text-xs font-semibold text-gray-500">REASON</div>
                <div class="mt-1 text-gray-700 whitespace-pre-line" id="lv-reason"></div>
              </div>

              <div class="mt-4">
                <div class="text-xs font-semibold text-gray-500">REMARKS</div>
                <div class="mt-1 text-gray-700 whitespace-pre-line" id="lv-remarks"></div>
              </div>

              <div class="modal-action">
                <button type="button" class="btn" id="lv-ok">Close</button>
              </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
          </dialog>

          <dialog id="leave-action-modal" class="modal">
            <div class="modal-box w-11/12 max-w-lg">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="font-bold text-lg" id="la-title">Update Leave Request</h3>
                  <div class="text-sm text-gray-500" id="la-sub"></div>
                </div>
                <button type="button" class="btn btn-sm btn-ghost" id="la-close">✕</button>
              </div>

              <form method="POST" class="mt-4 space-y-3" id="la-form">
                <input type="hidden" name="update_leave" value="1" />
                <input type="hidden" name="leave_id" id="la-leave-id" value="0" />
                <input type="hidden" name="status" id="la-status" value="" />

                <div class="form-control" id="la-remarks-wrap">
                  <label class="label"><span class="label-text text-xs font-semibold text-gray-500">REMARKS</span></label>
                  <textarea name="remarks" id="la-remarks" class="textarea textarea-bordered" rows="4" placeholder="Write remarks..."></textarea>
                  <div class="mt-1 text-[11px] text-gray-400" id="la-remarks-hint"></div>
                </div>

                <div class="modal-action">
                  <button type="submit" class="btn hr2-primary-btn" id="la-submit">Save</button>
                  <button type="button" class="btn" id="la-cancel">Cancel</button>
                </div>
              </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
          </dialog>

        </div>
      </main>
    </div>
  </div>
  <?php require('../partials/footer.php') ?>
  <script>
    (function() {
      if (window.lucide) window.lucide.createIcons();

      const viewModal = document.getElementById('leave-view-modal');
      const viewClose = document.getElementById('lv-close');
      const viewOk = document.getElementById('lv-ok');

      const actionModal = document.getElementById('leave-action-modal');
      const actionClose = document.getElementById('la-close');
      const actionCancel = document.getElementById('la-cancel');
      const laLeaveId = document.getElementById('la-leave-id');
      const laStatus = document.getElementById('la-status');
      const laTitle = document.getElementById('la-title');
      const laSub = document.getElementById('la-sub');
      const laRemarksWrap = document.getElementById('la-remarks-wrap');
      const laRemarks = document.getElementById('la-remarks');
      const laHint = document.getElementById('la-remarks-hint');

      const setText = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.textContent = txt || '';
      };

      const setHtml = (id, html) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html || '';
      };

      const closeDialog = (dlg) => {
        if (dlg) dlg.close();
      };
      if (viewClose) viewClose.addEventListener('click', () => closeDialog(viewModal));
      if (viewOk) viewOk.addEventListener('click', () => closeDialog(viewModal));
      if (actionClose) actionClose.addEventListener('click', () => closeDialog(actionModal));
      if (actionCancel) actionCancel.addEventListener('click', () => closeDialog(actionModal));

      function openAction(id, status) {
        if (!actionModal) return;
        const st = String(status || '');
        laLeaveId.value = String(id || '0');
        laStatus.value = st;
        laRemarks.value = '';

        if (st === 'Approved') {
          laTitle.textContent = 'Approve Leave Request';
          laSub.textContent = 'This will mark the request as approved.';
          laRemarksWrap.classList.add('hidden');
        } else if (st === 'Rejected') {
          laTitle.textContent = 'Reject Leave Request';
          laSub.textContent = 'Rejection remarks are required.';
          laRemarksWrap.classList.remove('hidden');
          laHint.textContent = 'Please provide a clear rejection reason.';
        } else if (st === 'For Compliance') {
          laTitle.textContent = 'Mark For Compliance';
          laSub.textContent = 'Compliance remarks are required.';
          laRemarksWrap.classList.remove('hidden');
          laHint.textContent = 'Specify what the employee must comply with.';
        } else {
          laTitle.textContent = 'Update Leave Request';
          laSub.textContent = '';
          laRemarksWrap.classList.remove('hidden');
        }

        actionModal.showModal();
      }

      function openView(btn) {
        if (!viewModal) return;
        const emp = btn.getAttribute('data-employee') || '';
        const empNo = btn.getAttribute('data-employeeno') || '';
        const dept = btn.getAttribute('data-dept') || '';
        const type = btn.getAttribute('data-leavetype') || '';
        const start = btn.getAttribute('data-start') || '';
        const end = btn.getAttribute('data-end') || '';
        const reason = btn.getAttribute('data-reason') || '';
        const status = btn.getAttribute('data-status') || '';
        const remarks = btn.getAttribute('data-remarks') || '';

        setText('lv-emp', emp || 'Employee');
        setText('lv-emp-meta', [empNo, dept].filter(Boolean).join(' • '));
        setText('lv-type', type);
        setText('lv-range', `${start} — ${end}`);
        setText('lv-reason', reason);
        setText('lv-remarks', remarks);

        const badgeClass = (() => {
          const s = String(status || '').toLowerCase();
          if (s === 'approved') return 'badge-success';
          if (s === 'pending') return 'badge-warning';
          if (s === 'rejected') return 'badge-error';
          if (s === 'for compliance') return 'badge-info';
          return 'badge-ghost';
        })();

        setHtml('lv-status', `<span class="badge ${badgeClass}">${String(status || '').toUpperCase()}</span>`);

        viewModal.showModal();
      }

      document.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('button') : null;
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        if (!action) return;

        if (action === 'view') {
          openView(btn);
          return;
        }

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (action === 'approve') openAction(id, 'Approved');
        if (action === 'reject') openAction(id, 'Rejected');
        if (action === 'compliance') openAction(id, 'For Compliance');
      });
    })();
  </script>
  <?php require('../partials/footer.php') ?>
</body>

</html>