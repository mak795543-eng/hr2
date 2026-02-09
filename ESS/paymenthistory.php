<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$rows = [];

if ($conn && $employeeId) {
  $has = false;
  $stmtC = mysqli_prepare($conn, 'SELECT 1 FROM payment_history WHERE employee_id = ? LIMIT 1');
  if ($stmtC) {
    mysqli_stmt_bind_param($stmtC, 'i', $employeeId);
    mysqli_stmt_execute($stmtC);
    $resC = mysqli_stmt_get_result($stmtC);
    $has = (bool)($resC && mysqli_fetch_assoc($resC));
    mysqli_stmt_close($stmtC);
  }

  if (!$has) {
    $now = new DateTime('now');
    $periods = [];
    $curY = (int)$now->format('Y');
    $curM = (int)$now->format('n');
    $prev = (clone $now)->modify('-1 month');
    $prevY = (int)$prev->format('Y');
    $prevM = (int)$prev->format('n');
    $endCur = (new DateTime("$curY-$curM-01"))->modify('last day of this month');
    $endPrev = (new DateTime("$prevY-$prevM-01"))->modify('last day of this month');
    $periods[] = ['start' => "$curY-" . str_pad((string)$curM, 2, '0', STR_PAD_LEFT) . "-01", 'end' => "$curY-" . str_pad((string)$curM, 2, '0', STR_PAD_LEFT) . "-15"];
    $periods[] = ['start' => "$curY-" . str_pad((string)$curM, 2, '0', STR_PAD_LEFT) . "-16", 'end' => $endCur->format('Y-m-d')];
    $periods[] = ['start' => "$prevY-" . str_pad((string)$prevM, 2, '0', STR_PAD_LEFT) . "-01", 'end' => "$prevY-" . str_pad((string)$prevM, 2, '0', STR_PAD_LEFT) . "-15"];
    $periods[] = ['start' => "$prevY-" . str_pad((string)$prevM, 2, '0', STR_PAD_LEFT) . "-16", 'end' => $endPrev->format('Y-m-d')];

    foreach ($periods as $p) {
      $ps = $p['start'];
      $pe = $p['end'];
      $stmtDup = mysqli_prepare($conn, 'SELECT 1 FROM payment_history WHERE employee_id = ? AND pay_period_start = ? AND pay_period_end = ? LIMIT 1');
      $exists = false;
      if ($stmtDup) {
        mysqli_stmt_bind_param($stmtDup, 'iss', $employeeId, $ps, $pe);
        mysqli_stmt_execute($stmtDup);
        $resDup = mysqli_stmt_get_result($stmtDup);
        $exists = (bool)($resDup && mysqli_fetch_assoc($resDup));
        mysqli_stmt_close($stmtDup);
      }
      if ($exists) continue;

      $basic = 15000.00;
      $allow = 2000.00;
      $ded = 1500.00;
      $net = $basic + $allow - $ded;
      $payDate = $pe;

      $stmtIns = mysqli_prepare($conn, 'INSERT INTO payment_history (employee_id, pay_period_start, pay_period_end, basic_pay, allowances, deductions, net_pay, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
      if ($stmtIns) {
        $status = 'Paid';
        mysqli_stmt_bind_param($stmtIns, 'issddddss', $employeeId, $ps, $pe, $basic, $allow, $ded, $net, $payDate, $status);
        mysqli_stmt_execute($stmtIns);
        mysqli_stmt_close($stmtIns);
      }
    }
  }

  $stmt = mysqli_prepare($conn, 'SELECT pay_period_start, pay_period_end, basic_pay, allowances, deductions, net_pay, payment_date, status FROM payment_history WHERE employee_id = ? ORDER BY payment_date DESC');
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
      while ($r = mysqli_fetch_assoc($res)) {
        $start = (string)($r['pay_period_start'] ?? '');
        $end = (string)($r['pay_period_end'] ?? '');
        $gross = (float)($r['basic_pay'] ?? 0) + (float)($r['allowances'] ?? 0);
        $rows[] = [
          'check_date' => (string)($r['payment_date'] ?? ''),
          'period' => $start . ' - ' . $end,
          'gross' => $gross,
          'net' => (float)($r['net_pay'] ?? 0),
          'status' => (string)($r['status'] ?? 'Paid'),
        ];
      }
    }
    mysqli_stmt_close($stmt);
  }
}

function peso($amount)
{
  return '₱' . number_format((float)$amount, 2);
}

function statusBadgeClass($status)
{
  $s = strtolower(trim($status));
  return match ($s) {
    'pending' => 'badge-warning',
    'for approval' => 'badge-info',
    'approved' => 'badge-success',
    'processing' => 'badge-primary',
    'paid' => 'badge-success',
    'on hold' => 'badge-ghost',
    'failed' => 'badge-error',
    'cancelled' => 'badge-neutral',
    default => 'badge-ghost'
  };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment History</title>
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
          <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
              <div class="flex items-start justify-between">
                <div>
                  <h1 class="text-2xl font-bold text-gray-800">Payroll</h1>
                  <p class="text-gray-600">View and download your past pay stubs and statements.</p>
                </div>
                <div class="flex items-center gap-2">
                  <button class="btn btn-ghost btn-sm" type="button">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                  </button>
                  <button class="btn btn-ghost btn-sm" type="button">
                    <i data-lucide="search" class="w-4 h-4"></i>
                  </button>
                </div>
              </div>

              <div class="mt-6 overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Check Date</th>
                      <th>Pay Period</th>
                      <th class="text-right">Gross Pay</th>
                      <th class="text-right">Net Pay</th>
                      <th>Status</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rows as $r): ?>
                      <tr>
                        <td class="font-medium"><?php echo htmlspecialchars(date('M d, Y', strtotime($r['check_date']))); ?></td>
                        <td class="text-gray-600"><?php echo htmlspecialchars($r['period']); ?></td>
                        <td class="text-right"><?php echo htmlspecialchars(peso($r['gross'])); ?></td>
                        <td class="text-right font-semibold"><?php echo htmlspecialchars(peso($r['net'])); ?></td>
                        <td>
                          <span class="badge badge-sm <?php echo statusBadgeClass($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                        </td>
                        <td class="text-center">
                          <div class="flex items-center justify-center gap-2">
                            <button class="btn btn-ghost btn-xs" type="button" aria-label="View">
                              <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="btn btn-ghost btn-xs" type="button" aria-label="Download">
                              <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="mt-4 text-xs text-gray-500">
                Status values: Pending, For Approval, Approved, Processing, Paid, On Hold, Failed, Cancelled.
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