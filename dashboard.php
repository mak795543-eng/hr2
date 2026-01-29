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