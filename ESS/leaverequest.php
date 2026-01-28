<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$success_message = '';
$error_message = '';

$balances = [
    ['label' => 'Vacation', 'remaining' => 80, 'used' => 40, 'total' => 120, 'color' => 'text-blue-600', 'bar' => 'progress-primary'],
    ['label' => 'Sick Leave', 'remaining' => 64, 'used' => 16, 'total' => 80, 'color' => 'text-emerald-600', 'bar' => 'progress-success'],
    ['label' => 'Personal', 'remaining' => 16, 'used' => 8, 'total' => 24, 'color' => 'text-amber-600', 'bar' => 'progress-warning'],
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

    if ($leaveType === '' || $startDate === '' || $endDate === '') {
        $error_message = 'Please fill in the required fields.';
    } elseif (!$employeeId) {
        $error_message = 'Unable to identify employee. Please login again.';
    } elseif (!$conn) {
        $error_message = 'Database connection unavailable.';
    } else {
        $status = 'Pending';
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
                $success_message = 'Leave request submitted successfully.';
            }
        }
    }
}

if ($conn && $employeeId) {
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

function leaveStatusBadge($status) {
    $s = strtolower(trim($status));
    return match ($s) {
        'approved' => 'badge-success',
        'pending' => 'badge-warning',
        'rejected' => 'badge-error',
        default => 'badge-ghost',
    };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leave Request</title>
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
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Leave Request</h1>
            <p class="text-gray-600">Submit a new leave request and track approvals.</p>
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
            <?php foreach ($balances as $b): ?>
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-start justify-between">
                    <div>
                      <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($b['label']); ?></div>
                      <div class="mt-2 text-3xl font-bold <?php echo $b['color']; ?>"><?php echo (int)$b['remaining']; ?></div>
                      <div class="text-xs text-gray-500">Hours Remaining</div>
                    </div>
                    <div class="p-2 rounded-xl bg-base-200">
                      <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                  </div>
                  <div class="mt-4">
                    <progress class="progress <?php echo $b['bar']; ?> w-full" value="<?php echo (int)$b['used']; ?>" max="<?php echo (int)$b['total']; ?>"></progress>
                    <div class="mt-2 text-[11px] text-gray-500"><?php echo (int)$b['used']; ?> hrs used of <?php echo (int)$b['total']; ?> hrs</div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <h2 class="card-title">New Leave Request</h2>

                  <form method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="submit_leave" value="1" />
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LEAVE TYPE</span></label>
                      <select name="leave_type" class="select select-bordered" required>
                        <option>Vacation</option>
                        <option>Sick Leave</option>
                        <option>Personal</option>
                      </select>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">NUMBER OF DAYS</span></label>
                      <input name="days" class="input input-bordered" type="number" min="1" value="1" />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">START DATE</span></label>
                      <input name="start_date" class="input input-bordered" type="date" required />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">END DATE</span></label>
                      <input name="end_date" class="input input-bordered" type="date" required />
                    </div>

                    <div class="form-control md:col-span-2">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">REASON / NOTE (OPTIONAL)</span></label>
                      <textarea name="reason" class="textarea textarea-bordered" rows="4" placeholder="Briefly explain your request..."></textarea>
                    </div>

                    <div class="md:col-span-2 mt-2">
                      <button class="btn btn-primary w-full" type="submit">
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

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
