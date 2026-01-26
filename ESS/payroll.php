<?php
session_start();

if (!defined('SUPPRESS_DB_ERRORS')) {
    define('SUPPRESS_DB_ERRORS', true);
}

$appBasePath = getenv('APP_BASE_PATH') ?: '/hr2/';

require_once __DIR__ . '/../../db.php';

$preferredDbNames = ['hr2_usmhr2', 'hr2usm', 'rest_core_2_usm', 'hr2_soliera_usm'];
$conn = null;
foreach ($preferredDbNames as $dbName) {
    if (isset($connections[$dbName]) && $connections[$dbName] instanceof mysqli) {
        $conn = $connections[$dbName];
        break;
    }
}

$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) {
    header('Location: ' . $appBasePath . 'USM/index.php');
    exit();
}

$employeeName = trim((string)($_SESSION['employee_name'] ?? ''));
$employeeRole = trim((string)($_SESSION['role'] ?? ''));
$employeeDept = trim((string)($_SESSION['Dept_id'] ?? ''));
$employeeTin = '';

if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_accounts WHERE employee_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $acct = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (is_array($acct)) {
            if ($employeeName === '' && !empty($acct['employee_name'])) {
                $employeeName = (string)$acct['employee_name'];
                $_SESSION['employee_name'] = $employeeName;
            }
            if ($employeeRole === '' && !empty($acct['role'])) {
                $employeeRole = (string)$acct['role'];
                $_SESSION['role'] = $employeeRole;
            }
            if ($employeeDept === '' && !empty($acct['dept_name'])) {
                $employeeDept = (string)$acct['dept_name'];
            }
            if ($employeeDept === '' && !empty($acct['Dept_id'])) {
                $employeeDept = (string)$acct['Dept_id'];
            }
            if (!empty($acct['tin'])) {
                $employeeTin = (string)$acct['tin'];
            }
        }
    }
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int)date('Y');
}

$fmtMoney = function ($n): string {
    $v = is_numeric($n) ? (float)$n : 0.0;
    return '₱' . number_format($v, 2);
};

$jsonAction = $_GET['action'] ?? null;
if ($jsonAction) {
    header('Content-Type: application/json');

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        exit;
    }

    if ($jsonAction === 'list_years') {
        $years = [];
        $stmt = mysqli_prepare($conn, "SELECT DISTINCT YEAR(pay_date) AS y FROM payroll_payslips WHERE employee_id = ? ORDER BY y DESC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $employeeId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = $res ? mysqli_fetch_assoc($res) : null) {
                if (!$row) {
                    break;
                }
                $years[] = (int)($row['y'] ?? 0);
            }
            mysqli_stmt_close($stmt);
        }
        if (!$years) {
            $years = [(int)date('Y')];
        }
        echo json_encode(['success' => true, 'years' => $years]);
        exit;
    }

    if ($jsonAction === 'list_payslips') {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        $sql = "SELECT id, period_start, period_end, pay_date, basic_pay, gross_pay, total_deductions, net_pay, status FROM payroll_payslips WHERE employee_id = ? AND YEAR(pay_date) = ?";
        $types = 'si';
        $params = [$employeeId, $year];
        if ($search !== '') {
            $sql .= " AND (DATE_FORMAT(period_start, '%b %e') LIKE ? OR DATE_FORMAT(period_end, '%b %e') LIKE ? OR DATE_FORMAT(pay_date, '%b %e') LIKE ?)";
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY pay_date DESC, period_start DESC";

        $rows = [];
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($r = $res ? mysqli_fetch_assoc($res) : null) {
                if (!$r) {
                    break;
                }
                $rows[] = $r;
            }
            mysqli_stmt_close($stmt);
        }

        echo json_encode(['success' => true, 'payslips' => $rows]);
        exit;
    }

    if ($jsonAction === 'get_payslip') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing payslip id']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "SELECT * FROM payroll_payslips WHERE id = ? AND employee_id = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query failed']);
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'is', $id, $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $payslip = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!is_array($payslip)) {
            echo json_encode(['success' => false, 'message' => 'Payslip not found']);
            exit;
        }

        $earnings = [];
        $stmt = mysqli_prepare($conn, "SELECT label, amount FROM payroll_payslip_earnings WHERE payslip_id = ? ORDER BY sort_order ASC, id ASC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($r = $res ? mysqli_fetch_assoc($res) : null) {
                if (!$r) {
                    break;
                }
                $earnings[] = $r;
            }
            mysqli_stmt_close($stmt);
        }

        $deductions = [];
        $stmt = mysqli_prepare($conn, "SELECT label, amount FROM payroll_payslip_deductions WHERE payslip_id = ? ORDER BY sort_order ASC, id ASC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($r = $res ? mysqli_fetch_assoc($res) : null) {
                if (!$r) {
                    break;
                }
                $deductions[] = $r;
            }
            mysqli_stmt_close($stmt);
        }

        echo json_encode([
            'success' => true,
            'payslip' => $payslip,
            'employee' => [
                'employee_id' => $employeeId,
                'name' => $employeeName,
                'role' => $employeeRole,
                'department' => $employeeDept,
                'tin' => $employeeTin,
            ],
            'earnings' => $earnings,
            'deductions' => $deductions,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

$payslips = [];
$stmt = $conn ? mysqli_prepare($conn, "SELECT id, period_start, period_end, pay_date, basic_pay, gross_pay, total_deductions, net_pay, status FROM payroll_payslips WHERE employee_id = ? AND YEAR(pay_date) = ? ORDER BY pay_date DESC, period_start DESC") : null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $employeeId, $selectedYear);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = $res ? mysqli_fetch_assoc($res) : null) {
        if (!$r) {
            break;
        }
        $payslips[] = $r;
    }
    mysqli_stmt_close($stmt);
}

$latestPayslip = $payslips[0] ?? null;
$latestNetPay = $latestPayslip['net_pay'] ?? 0;
$latestPayDate = $latestPayslip['pay_date'] ?? null;

$ytdEarnings = 0.0;
$ytdDeductions = 0.0;
if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(gross_pay),0) AS gross, COALESCE(SUM(total_deductions),0) AS ded FROM payroll_payslips WHERE employee_id = ? AND YEAR(pay_date) = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $employeeId, $selectedYear);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        $ytdEarnings = (float)($row['gross'] ?? 0);
        $ytdDeductions = (float)($row['ded'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payroll Dashboard | Hotel & Restaurant Organization</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'hotel-primary': '#0864a6',
                        'hotel-secondary': '#d4af37',
                        'hotel-accent': '#0d9488'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .payslip-modal {
            max-height: 90vh;
        }
        .ph-flag-badge {
            background: linear-gradient(90deg, #0038a8 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%);
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>
    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>

    <!-- Simplified Header Section -->
    <div class="bg-gradient-to-r from-hotel-primary to-hotel-accent text-white p-6 shadow-md">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold">Manila Grand Horizon Hotels</h1>
                    <p class="opacity-90 mt-1">Philippine Payroll Portal - Employee Dashboard</p>
                </div>
                
                <!-- Employee Info & Actions on the Right -->
                <div class="flex flex-col items-end">
                    <!-- Employee Information -->
                    <div class="bg-white/10 p-3 rounded-lg mb-3 w-full md:w-auto">
                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                            <div class="text-right md:text-left">
                                <span class="font-semibold block"><?php echo htmlspecialchars($employeeName !== '' ? $employeeName : $employeeId); ?></span>
                                <span class="text-sm opacity-90"><?php echo htmlspecialchars(($employeeRole !== '' ? $employeeRole : 'Employee') . ' • ' . ($employeeDept !== '' ? $employeeDept : 'Department')); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="badge ph-flag-badge gap-1 px-2 py-1 text-xs">
                                    <i class="fas fa-flag"></i> PH Payroll
                                </div>
                                <div class="dropdown dropdown-end">
                                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm bg-white/20">
                                        <div class="indicator">
                                            <i class="fas fa-bell"></i>
                                            <span class="badge badge-xs badge-error indicator-item">2</span>
                                        </div>
                                    </div>
                                    <div tabindex="0" class="dropdown-content z-[1] card card-compact w-72 p-2 shadow bg-base-100 text-gray-800">
                                        <div class="card-body">
                                            <span class="font-bold">Payroll Notifications</span>
                                            <div class="space-y-2 mt-2">
                                                <div class="alert alert-success p-2 text-sm">
                                                    <i class="fas fa-file-invoice-dollar"></i>
                                                    <span>January 2024 payslip available</span>
                                                </div>
                                                <div class="alert alert-info p-2 text-sm">
                                                    <i class="fas fa-calendar-check"></i>
                                                    <span>BIR Form 2316 ready for download</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-ghost hover:bg-white/20" onclick="logout()" title="Logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-outline btn-sm border-white/50 hover:bg-white/20">
                            <i class="fas fa-user-circle mr-1"></i> Profile
                        </button>
                        <button class="btn btn-sm btn-outline btn-sm border-white/50 hover:bg-white/20">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <main class="px-4 md:px-8 pb-10 max-w-7xl mx-auto">
        <!-- Page Title -->
        <div class="my-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Payroll Dashboard</h1>
                    <p class="text-gray-600 mt-2">View your payroll information in Philippine Peso (₱)</p>
                </div>
                <div class="mt-4 md:mt-0 flex items-center space-x-3">
                    <div class="bg-blue-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-info-circle text-hotel-primary mr-2"></i>
                        <span class="text-sm">All amounts in ₱ (Philippine Peso)</span>
                    </div>
                    <button class="btn btn-outline btn-hotel-primary btn-sm">
                        <i class="fas fa-question-circle mr-2"></i> Help
                    </button>
                </div>
            </div>
        </div>

        <!-- Payroll Summary Cards -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-5 text-gray-700">Payroll Overview</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Latest Net Pay Card -->
                <div class="stat-card card bg-gradient-to-r from-hotel-primary/10 to-hotel-accent/10 border border-hotel-primary/20">
                    <div class="card-body">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center">
                                    <i class="fas fa-wallet text-hotel-primary mr-2"></i>
                                    <h3 class="card-title text-gray-700">Latest Net Pay</h3>
                                </div>
                                <p class="text-3xl font-bold mt-2 text-gray-800"><?php echo htmlspecialchars($fmtMoney($latestNetPay)); ?></p>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($latestPayDate ? date('F Y', strtotime((string)$latestPayDate)) : ''); ?></p>
                            </div>
                            <div class="badge badge-success badge-lg gap-1 p-3">
                                <i class="fas fa-check"></i> Released
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Pay Date Card -->
                <div class="stat-card card bg-gradient-to-r from-amber-50 to-amber-100/50 border border-amber-200">
                    <div class="card-body">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-check text-amber-600 mr-2"></i>
                            <h3 class="card-title text-gray-700">Last Pay Date</h3>
                        </div>
                        <p class="text-3xl font-bold mt-2 text-gray-800"><?php echo htmlspecialchars($latestPayDate ? date('M d, Y', strtotime((string)$latestPayDate)) : ''); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Semi-monthly payroll</p>
                    </div>
                </div>

                <!-- Total Deductions Card -->
                <div class="stat-card card bg-gradient-to-r from-rose-50 to-rose-100/50 border border-rose-200">
                    <div class="card-body">
                        <div class="flex items-center">
                            <i class="fas fa-file-invoice text-rose-600 mr-2"></i>
                            <h3 class="card-title text-gray-700">Total Deductions</h3>
                        </div>
                        <p class="text-3xl font-bold mt-2 text-gray-800"><?php echo htmlspecialchars($fmtMoney($ytdDeductions)); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Current Month</p>
                    </div>
                </div>

                <!-- YTD Earnings Card -->
                <div class="stat-card card bg-gradient-to-r from-emerald-50 to-emerald-100/50 border border-emerald-200">
                    <div class="card-body">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-emerald-600 mr-2"></i>
                            <h3 class="card-title text-gray-700">YTD Earnings</h3>
                        </div>
                        <p class="text-3xl font-bold mt-2 text-gray-800"><?php echo htmlspecialchars($fmtMoney($ytdEarnings)); ?></p>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars((string)$selectedYear . ' Year to Date'); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Year Selector and Payroll Table Section -->
        <section class="mb-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 md:mb-0">Payroll History</h2>
                
                <!-- Year Selector -->
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 font-medium">Select Year:</span>
                    <div class="tabs tabs-boxed bg-gray-100" id="year-tabs">
                        <?php
                        $years = [(int)date('Y'), (int)date('Y') - 1, (int)date('Y') - 2];
                        foreach ($years as $y) {
                            $isActive = $y === $selectedYear;
                            echo '<a class="tab ' . ($isActive ? 'tab-active' : '') . '" data-year="' . (int)$y . '">' . (int)$y . '</a>';
                        }
                        ?>
                    </div>
                    
                    <!-- Search Input -->
                    <div class="form-control">
                        <div class="input input-bordered input-sm flex items-center gap-2">
                            <i class="fas fa-search text-gray-400"></i>
                            <input type="text" class="grow w-40" placeholder="Search pay period..." />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Records Table -->
            <div class="card bg-base-100 shadow">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr class="bg-hotel-primary/10">
                                    <th class="text-gray-700 font-semibold">Pay Period</th>
                                    <th class="text-gray-700 font-semibold">Pay Date</th>
                                    <th class="text-gray-700 font-semibold">Basic Pay</th>
                                    <th class="text-gray-700 font-semibold">Gross Pay</th>
                                    <th class="text-gray-700 font-semibold">Deductions</th>
                                    <th class="text-gray-700 font-semibold">Net Pay</th>
                                    <th class="text-gray-700 font-semibold">Status</th>
                                    <th class="text-gray-700 font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payroll-tbody">
                                <?php if (count($payslips) === 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-gray-500 py-6">No payslips found for <?php echo (int)$selectedYear; ?>.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payslips as $ps): ?>
                                        <?php
                                        $status = (string)($ps['status'] ?? 'Released');
                                        $statusNorm = strtolower(trim($status));
                                        $badgeClass = 'badge-success';
                                        if ($statusNorm === 'pending') {
                                            $badgeClass = 'badge-warning';
                                        } elseif ($statusNorm === 'failed' || $statusNorm === 'void' || $statusNorm === 'cancelled') {
                                            $badgeClass = 'badge-error';
                                        }
                                        $periodStart = !empty($ps['period_start']) ? date('M d', strtotime((string)$ps['period_start'])) : '';
                                        $periodEnd = !empty($ps['period_end']) ? date('M d, Y', strtotime((string)$ps['period_end'])) : '';
                                        $payDate = !empty($ps['pay_date']) ? date('M d, Y', strtotime((string)$ps['pay_date'])) : '';
                                        $periodText = trim($periodStart . ' - ' . $periodEnd);
                                        ?>
                                        <tr data-payslip-id="<?php echo (int)$ps['id']; ?>" data-period="<?php echo htmlspecialchars($periodText); ?>">
                                            <td><?php echo htmlspecialchars($periodText); ?></td>
                                            <td><?php echo htmlspecialchars($payDate); ?></td>
                                            <td><?php echo htmlspecialchars($fmtMoney($ps['basic_pay'] ?? 0)); ?></td>
                                            <td><?php echo htmlspecialchars($fmtMoney($ps['gross_pay'] ?? 0)); ?></td>
                                            <td><?php echo htmlspecialchars($fmtMoney($ps['total_deductions'] ?? 0)); ?></td>
                                            <td class="font-semibold"><?php echo htmlspecialchars($fmtMoney($ps['net_pay'] ?? 0)); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?> gap-1">
                                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex space-x-2">
                                                    <button class="btn btn-xs btn-outline btn-hotel-primary view-payslip-btn" data-id="<?php echo (int)$ps['id']; ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-xs btn-hotel-primary download-pdf-btn" data-id="<?php echo (int)$ps['id']; ?>">
                                                        <i class="fas fa-download"></i> PDF
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex justify-between items-center p-4 border-t">
                        <div class="text-sm text-gray-500">
                            Showing 1 to <?php echo count($payslips); ?> of <?php echo count($payslips); ?> records (Semi-monthly)
                        </div>
                        <div class="join">
                            <button class="join-item btn btn-sm">«</button>
                            <button class="join-item btn btn-sm btn-active">1</button>
                            <button class="join-item btn btn-sm">2</button>
                            <button class="join-item btn btn-sm">3</button>
                            <button class="join-item btn btn-sm">4</button>
                            <button class="join-item btn btn-sm">»</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Philippine Payroll Features Section -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-5 text-gray-700">Philippine Payroll Features</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- BIR Forms Access -->
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center mr-3">
                                <i class="fas fa-file-contract text-red-600 text-xl"></i>
                            </div>
                            <h3 class="card-title text-gray-800">BIR Forms</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Access your BIR forms for tax filing purposes.</p>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center p-2 bg-blue-50 rounded">
                                <span class="text-sm font-medium">BIR Form 2316</span>
                                <button class="btn btn-xs btn-outline btn-error">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-blue-50 rounded">
                                <span class="text-sm font-medium">Certificate of Compensation</span>
                                <button class="btn btn-xs btn-outline btn-error">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SSS, PhilHealth, Pag-IBIG -->
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center mr-3">
                                <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                            </div>
                            <h3 class="card-title text-gray-800">Government Contributions</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Your monthly government contributions.</p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                        <span class="text-xs font-bold text-blue-600">SSS</span>
                                    </div>
                                    <span class="text-sm">SSS Contribution</span>
                                </div>
                                <span class="font-semibold">₱1,125.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-2">
                                        <span class="text-xs font-bold text-red-600">PH</span>
                                    </div>
                                    <span class="text-sm">PhilHealth</span>
                                </div>
                                <span class="font-semibold">₱450.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center mr-2">
                                        <span class="text-xs font-bold text-yellow-600">PI</span>
                                    </div>
                                    <span class="text-sm">Pag-IBIG</span>
                                </div>
                                <span class="font-semibold">₱200.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Annual Payroll Summary -->
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="card-title text-gray-800">2023 Annual Summary</h3>
                            <div class="badge badge-ghost badge-lg p-3">BIR Filing Ready</div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Gross Income</span>
                                <span class="font-bold text-lg">₱614,760.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Withholding Tax</span>
                                <span class="font-bold text-lg text-rose-600">₱68,292.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Net Income</span>
                                <span class="font-bold text-lg text-emerald-600">₱546,468.00</span>
                            </div>
                            <div class="divider my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Pay Periods</span>
                                <span class="font-bold">24</span>
                            </div>
                        </div>
                        
                        <div class="card-actions justify-end mt-6">
                            <button class="btn btn-outline btn-hotel-primary btn-sm">
                                <i class="fas fa-download mr-2"></i> Annual Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Download Center -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-5 text-gray-700">Download Center</h2>
            
            <div class="card bg-gradient-to-br from-hotel-primary/5 to-hotel-accent/5 border border-hotel-primary/20 shadow">
                <div class="card-body">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                        <div class="flex items-center mb-4 md:mb-0">
                            <div class="w-12 h-12 rounded-lg bg-hotel-primary/10 flex items-center justify-center mr-3">
                                <i class="fas fa-archive text-hotel-primary text-xl"></i>
                            </div>
                            <div>
                                <h3 class="card-title text-gray-800">Download Documents</h3>
                                <p class="text-gray-600">Secure download of payroll documents</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <button class="btn btn-outline btn-hotel-primary btn-sm">
                                <i class="fas fa-download mr-2"></i> All 2023 Payslips
                            </button>
                            <button class="btn btn-outline btn-hotel-primary btn-sm">
                                <i class="fas fa-download mr-2"></i> All 2024 Payslips
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-base-100 rounded-lg border">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium">Q4 2023 (Oct-Dec)</h4>
                                <span class="badge badge-sm">3 PDFs</span>
                            </div>
                            <p class="text-sm text-gray-500 mb-3">Includes December bonus payslip</p>
                            <button class="btn btn-hotel-primary btn-sm w-full">
                                <i class="fas fa-download mr-2"></i> Download Quarter
                            </button>
                        </div>
                        
                        <div class="p-4 bg-base-100 rounded-lg border">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium">January 2024</h4>
                                <span class="badge badge-sm badge-success">2 PDFs</span>
                            </div>
                            <p class="text-sm text-gray-500 mb-3">Semi-monthly payslips for January</p>
                            <button class="btn btn-hotel-primary btn-sm w-full">
                                <i class="fas fa-download mr-2"></i> Download Month
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security Notice with Philippine Context -->
        <div class="alert alert-info shadow-lg">
            <div class="flex items-start">
                <i class="fas fa-shield-alt text-xl mt-1 mr-3"></i>
                <div>
                    <h3 class="font-bold">Secure Philippine Payroll Portal</h3>
                    <div class="text-sm mt-1">Your payroll data is protected under Philippine Data Privacy Act (RA 10173). All amounts are in Philippine Peso (₱). For discrepancies, contact HR/Finance at hr@manilagrandhorizon.com or call (02) 8123-4567.</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Payslip Details Modal (Philippine Format) -->
    <dialog id="payslip-modal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box payslip-modal w-11/12 max-w-4xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-2xl text-gray-800">Philippine Payslip</h3>
                    <p class="text-gray-500">Pay Period: <span id="ps-pay-period">-</span></p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="badge badge-error gap-1">
                        <i class="fas fa-flag"></i> PH
                    </div>
                    <button class="btn btn-hotel-primary">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <button class="btn btn-outline btn-hotel-primary">
                        <i class="fas fa-download mr-2"></i> PDF
                    </button>
                    <form method="dialog">
                        <button class="btn btn-ghost">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Payslip Header -->
            <div class="bg-gradient-to-r from-hotel-primary to-hotel-accent text-white p-6 rounded-t-2xl mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-4 md:mb-0">
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <h2 class="text-2xl font-bold">Manila Grand Horizon Hotels</h2>
                        </div>
                        <p class="opacity-90">Bonifacio Global City, Taguig, Metro Manila</p>
                        <p class="opacity-90">BIR TIN: 123-456-789-000 | SEC Reg: CS2020123456</p>
                    </div>
                    <div class="text-left md:text-right">
                        <h3 class="text-2xl font-bold">PAYSLIP</h3>
                        <p class="opacity-90">Issue Date: <span id="ps-issue-date">-</span></p>
                        <p class="opacity-90">Payment Method: Bank Deposit</p>
                    </div>
                </div>
            </div>
            
            <!-- Employee Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-base-100 border">
                    <div class="card-body p-5">
                        <h4 class="card-title text-gray-700 mb-4">Employee Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Employee ID</p>
                                <p class="font-semibold" id="ps-employee-id">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Name</p>
                                <p class="font-semibold" id="ps-employee-name">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Position</p>
                                <p class="font-semibold" id="ps-employee-position">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">TIN</p>
                                <p class="font-semibold" id="ps-employee-tin">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-base-100 border">
                    <div class="card-body p-5">
                        <h4 class="card-title text-gray-700 mb-4">Payroll Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Pay Period</p>
                                <p class="font-semibold" id="ps-pay-period-2">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Pay Date</p>
                                <p class="font-semibold" id="ps-pay-date">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Payment Status</p>
                                <p class="font-semibold" id="ps-payment-status">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Pay Frequency</p>
                                <p class="font-semibold" id="ps-pay-frequency">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Earnings & Deductions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Earnings Breakdown -->
                <div class="card bg-base-100 border">
                    <div class="card-body p-5">
                        <h4 class="card-title text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-money-bill-wave text-emerald-500 mr-2"></i>
                            Earnings (Philippine Peso)
                        </h4>
                        <div class="space-y-3" id="ps-earnings-list"></div>
                    </div>
                </div>
                
                <!-- Deductions Breakdown (Philippine Standard) -->
                <div class="card bg-base-100 border">
                    <div class="card-body p-5">
                        <h4 class="card-title text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-file-invoice-dollar text-rose-500 mr-2"></i>
                            Deductions (Philippine Peso)
                        </h4>
                        <div class="space-y-3" id="ps-deductions-list"></div>
                    </div>
                </div>
            </div>
            
            <!-- Net Pay Summary -->
            <div class="card bg-gradient-to-r from-hotel-primary/10 to-hotel-accent/10 border border-hotel-primary/30">
                <div class="card-body">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Net Pay (Take Home)</h3>
                            <p class="text-gray-600">Amount deposited to your bank account</p>
                        </div>
                        <div class="text-center md:text-right mt-4 md:mt-0">
                            <p class="text-4xl font-bold text-hotel-primary" id="ps-net-pay">-</p>
                            <p class="text-gray-600" id="ps-bank">-</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Notes -->
            <div class="mt-6 text-center text-gray-500 text-sm">
                <p>This is a computer-generated document. No signature is required.</p>
                <p>For questions, contact HR at hr@manilagrandhorizon.com or call (02) 8123-4567.</p>
                <p class="mt-2"><i class="fas fa-lock mr-1"></i> Secured under Philippine Data Privacy Act</p>
            </div>
            
            <div class="modal-action mt-8">
                <form method="dialog">
                    <button class="btn btn-hotel-primary">
                        <i class="fas fa-download mr-2"></i> Download PDF
                    </button>
                </form>
                <form method="dialog">
                    <button class="btn">Close</button>
                </form>
            </div>
        </div>
        
        <!-- Backdrop -->
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- JavaScript -->
    <script>
        const peso = (n) => {
            const v = Number(n || 0);
            return '₱' + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const loadPayslip = async (id) => {
            const url = new URL(window.location.href);
            url.searchParams.set('action', 'get_payslip');
            url.searchParams.set('id', String(id));
            const res = await fetch(url.toString(), { credentials: 'same-origin' });
            const data = await res.json();
            if (!data || !data.success) {
                alert((data && data.message) ? data.message : 'Failed to load payslip');
                return;
            }

            const ps = data.payslip || {};
            const emp = data.employee || {};
            const earnings = Array.isArray(data.earnings) ? data.earnings : [];
            const deductions = Array.isArray(data.deductions) ? data.deductions : [];

            const periodStart = ps.period_start ? new Date(ps.period_start) : null;
            const periodEnd = ps.period_end ? new Date(ps.period_end) : null;
            const payDate = ps.pay_date ? new Date(ps.pay_date) : null;
            const periodText = (periodStart && periodEnd)
                ? `${periodStart.toLocaleString('en-US', { month: 'short', day: '2-digit' })} - ${periodEnd.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}`
                : '-';

            const setText = (id2, v) => {
                const el = document.getElementById(id2);
                if (el) el.textContent = v == null || v === '' ? '-' : String(v);
            };

            setText('ps-pay-period', periodText);
            setText('ps-pay-period-2', periodText);
            setText('ps-issue-date', payDate ? payDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-');
            setText('ps-pay-date', payDate ? payDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-');
            setText('ps-employee-id', emp.employee_id || ps.employee_id || '');
            setText('ps-employee-name', emp.name || '');
            setText('ps-employee-position', emp.role || ps.position || '');
            setText('ps-employee-tin', emp.tin || ps.tin || '');
            setText('ps-payment-status', ps.status || '');
            setText('ps-pay-frequency', ps.pay_frequency || '');
            setText('ps-net-pay', peso(ps.net_pay));
            setText('ps-bank', ps.bank_account_masked ? `Deposited to ${ps.bank_name || 'Bank'} Account ${ps.bank_account_masked}` : '-');

            const earningsEl = document.getElementById('ps-earnings-list');
            if (earningsEl) {
                const items = earnings.map((e) => {
                    const label = String(e.label || 'Earning');
                    return `<div class="flex justify-between items-center"><span>${label}</span><span class="font-semibold">${peso(e.amount)}</span></div>`;
                });
                items.push('<div class="divider my-2"></div>');
                items.push(`<div class="flex justify-between items-center text-lg font-bold"><span>Total Gross Pay</span><span class="text-emerald-600">${peso(ps.gross_pay)}</span></div>`);
                earningsEl.innerHTML = items.join('');
            }

            const deductionsEl = document.getElementById('ps-deductions-list');
            if (deductionsEl) {
                const items = deductions.map((d) => {
                    const label = String(d.label || 'Deduction');
                    return `<div class="flex justify-between items-center"><span>${label}</span><span class="font-semibold">${peso(d.amount)}</span></div>`;
                });
                items.push('<div class="divider my-2"></div>');
                items.push(`<div class="flex justify-between items-center text-lg font-bold"><span>Total Deductions</span><span class="text-rose-600">${peso(ps.total_deductions)}</span></div>`);
                deductionsEl.innerHTML = items.join('');
            }

            document.getElementById('payslip-modal').showModal();
        };

        document.querySelectorAll('.view-payslip-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                if (id) loadPayslip(id);
            });
        });

        document.querySelectorAll('.download-pdf-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                if (!id) return;
                alert('PDF generation is not configured yet. Use View then Print/Save as PDF for now.');
            });
        });

        document.querySelectorAll('#year-tabs .tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const y = this.getAttribute('data-year') || this.textContent;
                const url = new URL(window.location.href);
                url.searchParams.set('year', String(y));
                url.searchParams.delete('action');
                window.location.href = url.toString();
            });
        });

        const searchInput = document.querySelector('input[placeholder="Search pay period..."]');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = String(e.target.value || '').toLowerCase();
                document.querySelectorAll('#payroll-tbody tr[data-payslip-id]').forEach((tr) => {
                    const period = String(tr.getAttribute('data-period') || '').toLowerCase();
                    tr.style.display = period.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        function logout() {
            window.location.href = "<?php echo htmlspecialchars($appBasePath . 'USM/logout.php'); ?>";
        }
    </script>
    </div>
  </div>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
