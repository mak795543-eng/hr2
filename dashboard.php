<?php
session_start();

if (!isset($_SESSION['role'])) {
  header("Location: index.php");
  exit();
}

$employeeId = $_SESSION['employee_id'] ?? null;
$displayName = trim((string)($_SESSION['employee_name'] ?? ($_SESSION['username'] ?? '')));
$roleDisplay = trim((string)($_SESSION['role'] ?? ''));

require_once __DIR__ . '/db.php';

$preferredDbNames = ['hr2usm', 'rest_core_2_usm', 'hr2_usmhr2', 'hr2_soliera_usm'];
$conn = null;
foreach ($preferredDbNames as $dbName) {
  if (isset($connections[$dbName]) && $connections[$dbName] instanceof mysqli) {
    $conn = $connections[$dbName];
    break;
  }
}

if ($conn && $employeeId) {
  $stmt = mysqli_prepare($conn, "SELECT employee_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $employeeId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (is_array($row) && !empty($row['employee_name'])) {
      $displayName = trim((string)$row['employee_name']);
      $_SESSION['employee_name'] = $displayName;
    }
  }
}

$essConn = null;
$assignedComplaints = [];
if ($employeeId) {
  try {
    require_once __DIR__ . '/ESS/db.php';
    if (isset($ess_conn) && $ess_conn instanceof mysqli) {
      $essConn = $ess_conn;
    }

    if ($essConn) {
      ess_ensure_complaint_tables($essConn);

      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complaint_seen'])) {
        $cid = (int)($_POST['complaint_id'] ?? 0);
        if ($cid > 0) {
          $stmt = mysqli_prepare($essConn, "UPDATE complaints SET seen_by_assignee = 1 WHERE id = ? AND assigned_to_employee_no = ?");
          if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $cid, $employeeId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
          }
        }

        header('Location: ' . (string)$_SERVER['PHP_SELF']);
        exit;
      }

      $stmt = mysqli_prepare(
        $essConn,
        "SELECT c.id, c.subject, c.category, c.category_other, c.incident_date, c.workflow_status, c.created_at, c.returned_reason, c.attachment_path, e.employee_no, e.first_name, e.last_name
         FROM complaints c
         LEFT JOIN employees e ON e.id = c.employee_id
         WHERE c.workflow_status = 'Assigned' AND c.assigned_to_employee_no = ?
         ORDER BY c.assigned_at DESC, c.created_at DESC"
      );
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
          $assignedComplaints[] = $row;
        }
        mysqli_stmt_close($stmt);
      }
    }
  } catch (Throwable $e) {
  }
}

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sub System</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="soliera.css">
  <link rel="stylesheet" href="sidebar.css">
  <style>
    /* Custom styles */
    .sidebar-mini {
      width: 70px;
    }

    .sidebar-mini .sidebar-text {
      display: none;
    }

    .sidebar-mini .collapse-content {
      display: none;
    }

    .sidebar-mini .collapse-title {
      justify-content: center;
    }

    .sidebar-mini .dropdown-icon {
      display: none;
    }

    .sidebar-mini .section-title {
      display: none;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php
    // Use relative path or absolute path based on your directory structure
    include 'USM/sidebarr.php';
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include 'USM/navbar.php'; ?>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-auto">
        <!-- Dashboard Content -->
        <div class="max-w-7xl mx-auto">
          <!-- Welcome Section -->
          <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
              Welcome, <?php echo htmlspecialchars($displayName !== '' ? $displayName : $employeeId); ?>!
            </h1>
            <p class="text-gray-600">
              Welcome &quot;<?php echo htmlspecialchars($roleDisplay !== '' ? $roleDisplay : ''); ?>&quot; to the HR Training and Development System
            </p>
          </div>

          <?php if (count($assignedComplaints) > 0): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h2 class="text-lg font-bold text-gray-800">Assigned Complaints</h2>
                  <p class="text-sm text-gray-500">Complaints assigned to you for action.</p>
                </div>
                <a href="ESS/approval.php?section=complaints&cstatus=assigned" class="btn btn-outline btn-sm">Open Approvals</a>
              </div>

              <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($assignedComplaints as $c): ?>
                  <?php
                    $cid = (int)($c['id'] ?? 0);
                    $subject = (string)($c['subject'] ?? '');
                    $cat = (string)($c['category'] ?? '');
                    $catOther = (string)($c['category_other'] ?? '');
                    $incident = (string)($c['incident_date'] ?? '');
                    $created = (string)($c['created_at'] ?? '');
                    $employeeNo = (string)($c['employee_no'] ?? '');
                    $name = trim(((string)($c['first_name'] ?? '')) . ' ' . ((string)($c['last_name'] ?? '')));
                    $employeeLabel = ($employeeNo !== '' ? $employeeNo : 'Employee') . ($name !== '' ? (' - ' . $name) : '');

                    $categoryDisplay = $cat;
                    if (strtolower(trim($cat)) === 'other' && $catOther !== '') {
                      $categoryDisplay = 'Other - ' . $catOther;
                    }
                  ?>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <div class="text-xs font-semibold text-gray-500">SUBJECT</div>
                          <div class="font-semibold text-gray-900 line-clamp-2"><?php echo htmlspecialchars($subject); ?></div>
                        </div>
                        <span class="badge badge-success badge-sm">Assigned</span>
                      </div>

                      <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <div><span class="text-gray-500">Employee:</span> <?php echo htmlspecialchars($employeeLabel); ?></div>
                        <div><span class="text-gray-500">Category:</span> <?php echo htmlspecialchars($categoryDisplay); ?></div>
                        <div><span class="text-gray-500">Incident:</span> <?php echo htmlspecialchars($incident); ?></div>
                        <div><span class="text-gray-500">Submitted:</span> <?php echo htmlspecialchars($created); ?></div>
                      </div>

                      <div class="mt-4 flex items-center justify-between gap-2">
                        <a class="btn btn-primary btn-sm" href="ESS/approval.php?section=complaints&cstatus=assigned">View</a>
                        <form method="POST" class="inline">
                          <input type="hidden" name="mark_complaint_seen" value="1" />
                          <input type="hidden" name="complaint_id" value="<?php echo (int)$cid; ?>" />
                          <button class="btn btn-ghost btn-sm" type="submit">Mark as seen</button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>


        </div>
      </main>

    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('sidebarToggle');

      if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
          sidebar.classList.toggle('sidebar-mini');
        });
      }

      // Collapse functionality
      const collapseInputs = document.querySelectorAll('.collapse input[type="checkbox"]');
      collapseInputs.forEach(input => {
        input.addEventListener('change', function() {
          const icon = this.parentElement.querySelector('.dropdown-icon');
          if (icon) {
            if (this.checked) {
              icon.style.transform = 'rotate(90deg)';
            } else {
              icon.style.transform = 'rotate(0deg)';
            }
          }
        });
      });
    });
  </script>
  <script src="soliera.js"></script>
  <script src="sidebar.js"></script>
</body>

</html>