<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$summary = [
    'documents' => ['count' => 0, 'label' => 'Documents', 'link' => 'mydocuments.php'],
    'leave' => ['count' => 0, 'label' => 'Leave Requests', 'link' => 'leaverequest.php'],
    'payments' => ['count' => 0, 'label' => 'Payment History', 'link' => 'paymenthistory.php'],
    'claims' => ['count' => 0, 'label' => 'Claims', 'link' => 'submitclaim.php'],
];

$recentActivities = [];

if ($conn && $employeeId) {
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM employee_documents WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['documents']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM leave_requests WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['leave']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM payment_history WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['payments']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT document_title, status, submitted_at FROM submitted_documents WHERE employee_id = ? ORDER BY submitted_at DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $recentActivities[] = [
                'type' => 'Document',
                'title' => (string)($row['document_title'] ?? 'Document Submission'),
                'status' => (string)($row['status'] ?? 'Pending'),
                'date' => date('Y-m-d', strtotime((string)($row['submitted_at'] ?? 'now'))),
                'link' => 'submitdocument.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT leave_type, start_date, end_date, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $title = (string)($row['leave_type'] ?? 'Leave') . ' (' . (string)($row['start_date'] ?? '') . ' - ' . (string)($row['end_date'] ?? '') . ')';
            $recentActivities[] = [
                'type' => 'Leave',
                'title' => $title,
                'status' => (string)($row['status'] ?? 'Pending'),
                'date' => date('Y-m-d', strtotime((string)($row['created_at'] ?? 'now'))),
                'link' => 'leaverequest.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT pay_period_start, pay_period_end, payment_date, status, net_pay FROM payment_history WHERE employee_id = ? ORDER BY payment_date DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $title = 'Pay Period ' . (string)($row['pay_period_start'] ?? '') . ' - ' . (string)($row['pay_period_end'] ?? '');
            $recentActivities[] = [
                'type' => 'Payment',
                'title' => $title,
                'status' => (string)($row['status'] ?? 'Paid'),
                'date' => date('Y-m-d', strtotime((string)($row['payment_date'] ?? 'now'))),
                'link' => 'paymenthistory.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

function badgeClassForType($type) {
    $t = strtolower(trim((string)$type));
    return match ($t) {
        'document' => 'badge-info',
        'leave' => 'badge-warning',
        'payment' => 'badge-success',
        'claim' => 'badge-ghost',
        default => 'badge-ghost',
    };
}

function badgeClassForStatus($status) {
    $s = strtolower(trim((string)$status));
    return match ($s) {
        'uploaded' => 'badge-info',
        'for approval' => 'badge-warning',
        'approved' => 'badge-success',
        'paid' => 'badge-success',
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
  <title>ESS Dashboard</title>
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
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">Employee Self Service</h1>
              <p class="text-sm text-gray-500">Quick summary and recent activities for your requests and records.</p>
            </div>
            <a href="mydocuments.php" class="btn btn-outline btn-sm hidden sm:inline-flex">
              <i data-lucide="external-link" class="w-4 h-4"></i>
              <span class="ml-2">Open ESS</span>
            </a>
          </div>

          <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <a href="<?php echo htmlspecialchars($summary['documents']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-info badge-outline">Documents</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['documents']['count']; ?></div>
                  <div class="text-sm text-gray-500">Items on file</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['leave']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-warning badge-outline">Leave</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['leave']['count']; ?></div>
                  <div class="text-sm text-gray-500">Recent requests</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['payments']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-success badge-outline">Payments</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['payments']['count']; ?></div>
                  <div class="text-sm text-gray-500">Records</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['claims']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="hand-coins" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-ghost badge-outline">Claims</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['claims']['count']; ?></div>
                  <div class="text-sm text-gray-500">Active submissions</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>
          </div>

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
              <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body">
                  <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <div class="p-2 rounded-xl bg-base-200">
                        <i data-lucide="activity" class="w-5 h-5"></i>
                      </div>
                      <div>
                        <h2 class="font-semibold text-gray-800">Recent Activities</h2>
                        <p class="text-sm text-gray-500">Latest updates across your ESS modules.</p>
                      </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2">
                      <a href="mydocuments.php" class="btn btn-ghost btn-sm">Documents</a>
                      <a href="leaverequest.php" class="btn btn-ghost btn-sm">Leave</a>
                      <a href="paymenthistory.php" class="btn btn-ghost btn-sm">Payments</a>
                      <a href="submitclaim.php" class="btn btn-ghost btn-sm">Claims</a>
                    </div>
                  </div>

                  <div class="mt-4 overflow-x-auto">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Type</th>
                          <th>Title</th>
                          <th>Status</th>
                          <th>Date</th>
                          <th class="text-right">Open</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($recentActivities as $a): ?>
                          <tr>
                            <td>
                              <span class="badge badge-sm <?php echo badgeClassForType($a['type']); ?>"><?php echo htmlspecialchars($a['type']); ?></span>
                            </td>
                            <td class="font-medium text-gray-900"><?php echo htmlspecialchars($a['title']); ?></td>
                            <td>
                              <span class="badge badge-sm <?php echo badgeClassForStatus($a['status']); ?>"><?php echo htmlspecialchars($a['status']); ?></span>
                            </td>
                            <td class="text-gray-700"><?php echo htmlspecialchars(date('M d, Y', strtotime($a['date']))); ?></td>
                            <td class="text-right">
                              <a class="btn btn-ghost btn-xs" href="<?php echo htmlspecialchars($a['link']); ?>" aria-label="Open">
                                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body">
                  <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-base-200">
                      <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                    <div>
                      <h2 class="font-semibold text-gray-800">Quick Actions</h2>
                      <p class="text-sm text-gray-500">Jump to common tasks.</p>
                    </div>
                  </div>

                  <div class="mt-4 grid grid-cols-1 gap-2">
                    <a href="submitdocument.php" class="btn btn-outline justify-start">
                      <i data-lucide="upload" class="w-4 h-4"></i>
                      <span class="ml-2">Upload Document</span>
                    </a>
                    <a href="leaverequest.php" class="btn btn-outline justify-start">
                      <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                      <span class="ml-2">Request Leave</span>
                    </a>
                    <a href="paymenthistory.php" class="btn btn-outline justify-start">
                      <i data-lucide="receipt" class="w-4 h-4"></i>
                      <span class="ml-2">View Payment History</span>
                    </a>
                    <a href="submitclaim.php" class="btn btn-outline justify-start">
                      <i data-lucide="file-plus" class="w-4 h-4"></i>
                      <span class="ml-2">Submit Claim</span>
                    </a>
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
