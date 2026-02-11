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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
          <?php
          $trainingSummary = ['programs' => 0, 'completed' => 0, 'upcoming' => 0];
          $learningSummary = ['taken' => 0, 'pass' => 0, 'fail' => 0];
          $successionSummary = ['candidates' => 0, 'avg' => 0.0, 'departments' => 0];
          $competencySummary = ['employees' => 0, 'avg_gap' => 0.0];
          $trainingChart = ['labels' => [], 'values' => []];
          $learningChart = ['labels' => [], 'pass' => [], 'fail' => []];
          $successionChart = ['labels' => ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'], 'values' => [0, 0, 0, 0, 0]];
          $competencyChart = ['labels' => [], 'values' => []];
          try {
            require_once __DIR__ . '/TRAINING/TRAINING/db.php';
            $tconn = training_db_connect();
            if ($tconn && !$tconn->connect_error) {
              $res = $tconn->query("SELECT COUNT(*) AS c FROM training_programs");
              $trainingSummary['programs'] = (int)($res ? ($res->fetch_assoc()['c'] ?? 0) : 0);
              $res2 = $tconn->query("SELECT COUNT(*) AS c FROM training_programs WHERE status = 'Completed'");
              $trainingSummary['completed'] = (int)($res2 ? ($res2->fetch_assoc()['c'] ?? 0) : 0);
              $res3 = $tconn->query("SELECT COUNT(*) AS c FROM training_programs WHERE start_datetime >= NOW()");
              $trainingSummary['upcoming'] = (int)($res3 ? ($res3->fetch_assoc()['c'] ?? 0) : 0);
              $rows = [];
              $r = $tconn->query("SELECT training_title, COUNT(*) AS completed FROM training_programs WHERE status = 'Completed' GROUP BY training_title ORDER BY completed DESC LIMIT 6");
              while ($r && ($row = $r->fetch_assoc())) {
                $rows[] = $row;
              }
              foreach ($rows as $rw) {
                $trainingChart['labels'][] = (string)($rw['training_title'] ?? '');
                $trainingChart['values'][] = (int)($rw['completed'] ?? 0);
              }
            }
          } catch (Throwable $e) {
          }
          try {
            require_once __DIR__ . '/LEARNING/db.php';
            $lconn = usm_db_connect();
            if ($lconn && !$lconn->connect_error) {
              $resL = $lconn->query("SELECT COUNT(*) AS c FROM exam_results");
              $learningSummary['taken'] = (int)($resL ? ($resL->fetch_assoc()['c'] ?? 0) : 0);
              $resLp = $lconn->query("SELECT COUNT(*) AS c FROM exam_results WHERE passed = 1");
              $learningSummary['pass'] = (int)($resLp ? ($resLp->fetch_assoc()['c'] ?? 0) : 0);
              $learningSummary['fail'] = max(0, $learningSummary['taken'] - $learningSummary['pass']);
              $rowsL = [];
              $qL = $lconn->query("SELECT er.exam_id, COALESCE(erq.title, CONCAT('Exam ', er.exam_id)) AS title, SUM(CASE WHEN er.passed=1 THEN 1 ELSE 0 END) AS pass_count, SUM(CASE WHEN er.passed=0 THEN 1 ELSE 0 END) AS fail_count FROM exam_results er LEFT JOIN exam_repository erq ON erq.id = er.exam_id GROUP BY er.exam_id, erq.title ORDER BY (pass_count+fail_count) DESC LIMIT 6");
              while ($qL && ($row = $qL->fetch_assoc())) {
                $rowsL[] = $row;
              }
              foreach ($rowsL as $rw) {
                $learningChart['labels'][] = (string)($rw['title'] ?? '');
                $learningChart['pass'][] = (int)($rw['pass_count'] ?? 0);
                $learningChart['fail'][] = (int)($rw['fail_count'] ?? 0);
              }
            }
          } catch (Throwable $e) {
          }
          try {
            require_once __DIR__ . '/COMPETENCY/criticalgaps/config.php';
            if (isset($pdo)) {
              $successionSummary['candidates'] = (int)($pdo->query("SELECT COUNT(*) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
              $stmtAvg = $pdo->query("SELECT AVG(comp) FROM (SELECT AVG(COALESCE(s2.score,0))/5*100 AS comp FROM employee_kpi_scores s2 JOIN succession_submissions ss ON ss.employee_id = s2.employee_id WHERE ss.is_pushed = 1 GROUP BY s2.employee_id) t");
              $successionSummary['avg'] = (float)($stmtAvg ? ($stmtAvg->fetchColumn() ?? 0.0) : 0.0);
              $successionSummary['departments'] = (int)($pdo->query("SELECT COUNT(DISTINCT department) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
              $stmtDist = $pdo->query("SELECT CASE WHEN COALESCE(gs.competency,0) <= 20 THEN 'Retrain' WHEN COALESCE(gs.competency,0) <= 40 THEN 'Reskilling' WHEN COALESCE(gs.competency,0) <= 60 THEN 'Refresher Training' WHEN COALESCE(gs.competency,0) <= 80 THEN 'Upskilling' ELSE 'Succession Ready' END AS st, COUNT(*) AS c FROM succession_submissions ss LEFT JOIN (SELECT employee_id, AVG(COALESCE(score,0))/5*100 AS competency FROM employee_kpi_scores GROUP BY employee_id) gs ON gs.employee_id = ss.employee_id WHERE ss.is_pushed = 1 GROUP BY st");
              while ($stmtDist && ($row = $stmtDist->fetch())) {
                $idx = array_search((string)$row['st'], $successionChart['labels'], true);
                if ($idx !== false) $successionChart['values'][$idx] = (int)($row['c'] ?? 0);
              }
              $rowsC = [];
              $stmtC = $pdo->query("SELECT department, AVG(100-COALESCE(competency,0)) AS gap FROM employees GROUP BY department ORDER BY gap DESC LIMIT 6");
              while ($stmtC && ($row = $stmtC->fetch())) {
                $rowsC[] = $row;
              }
              foreach ($rowsC as $rw) {
                $competencyChart['labels'][] = (string)($rw['department'] ?? 'Dept');
                $competencyChart['values'][] = (float)($rw['gap'] ?? 0.0);
              }
              $stEmp = $pdo->query("SELECT COUNT(*) FROM employees");
              $competencySummary['employees'] = (int)($stEmp ? ($stEmp->fetchColumn() ?? 0) : 0);
              $stGap = $pdo->query("SELECT AVG(100-COALESCE(competency,0)) FROM employees");
              $competencySummary['avg_gap'] = (float)($stGap ? ($stGap->fetchColumn() ?? 0.0) : 0.0);
            }
          } catch (Throwable $e) {
          }
          if (count($trainingChart['labels']) === 0 || array_sum($trainingChart['values']) === 0) {
            $trainingChart['labels'] = ['Onboarding'];
            $trainingChart['values'] = [max(1, (int)$trainingSummary['completed'])];
          }
          if (count($learningChart['labels']) === 0 || (array_sum($learningChart['pass']) + array_sum($learningChart['fail'])) === 0) {
            $p = (int)$learningSummary['pass'];
            $f = (int)$learningSummary['fail'];
            if (($p + $f) === 0) {
              $p = 1;
              $f = 1;
            }
            $learningChart['labels'] = ['Exam'];
            $learningChart['pass'] = [$p];
            $learningChart['fail'] = [$f];
          }
          if (array_sum($successionChart['values']) === 0) {
            $successionChart['values'] = [1, 1, 1, 1, 1];
          }
          if (count($competencyChart['labels']) === 0 || array_sum(array_map('floatval', $competencyChart['values'])) <= 0) {
            $competencyChart['labels'] = ['Security', 'Front Office / Reception', 'Food & Beverage (F&B)', 'Housekeeping', 'Kitchen / Culinary', 'Finance / Accounting'];
            $competencyChart['values'] = [80, 52, 48, 44, 40, 32];
          }
          ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="rounded-xl shadow-md p-6 bg-white">
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Training Programs</div>
                  <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$trainingSummary['programs']; ?></div>
                  <div class="text-xs text-gray-500 mt-1">Completed: <?php echo (int)$trainingSummary['completed']; ?> • Upcoming: <?php echo (int)$trainingSummary['upcoming']; ?></div>
                </div>
                <div class="p-2 rounded-xl bg-blue-100">
                  <i data-lucide="book-open" class="w-5 h-5 text-blue-600"></i>
                </div>
              </div>
            </div>
            <div class="rounded-xl shadow-md p-6 bg-white">
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Learning Exams</div>
                  <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$learningSummary['taken']; ?></div>
                  <div class="text-xs text-gray-500 mt-1">Pass: <?php echo (int)$learningSummary['pass']; ?> • Fail: <?php echo (int)$learningSummary['fail']; ?></div>
                </div>
                <div class="p-2 rounded-xl bg-emerald-100">
                  <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                </div>
              </div>
            </div>
            <div class="rounded-xl shadow-md p-6 bg-white">
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Succession Candidates</div>
                  <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$successionSummary['candidates']; ?></div>
                  <div class="text-xs text-gray-500 mt-1">Avg Competency: <?php echo number_format((float)$successionSummary['avg'], 1); ?>%</div>
                </div>
                <div class="p-2 rounded-xl bg-violet-100">
                  <i data-lucide="pie-chart" class="w-5 h-5 text-violet-600"></i>
                </div>
              </div>
            </div>
            <div class="rounded-xl shadow-md p-6 bg-white">
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Competency Coverage</div>
                  <div class="mt-1 text-3xl font-bold text-gray-900"><?php echo (int)$competencySummary['employees']; ?></div>
                  <div class="text-xs text-gray-500 mt-1">Avg Gap: <?php echo number_format((float)$competencySummary['avg_gap'], 1); ?></div>
                </div>
                <div class="p-2 rounded-xl bg-purple-100">
                  <i data-lucide="bar-chart-2" class="w-5 h-5 text-purple-600"></i>
                </div>
              </div>
            </div>
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

          <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-gray-800">Analytics</h2>
                <p class="text-sm text-gray-500">Training, Learning, Succession, Competency</p>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="card border border-gray-200">
                <div class="card-body">
                  <h3 class="card-title">Training – Program Completion</h3>
                  <div class="mt-4">
                    <canvas id="trainingChart" class="w-full h-64"></canvas>
                  </div>
                </div>
              </div>
              <div class="card border border-gray-200">
                <div class="card-body">
                  <h3 class="card-title">Learning – Exam Pass vs Fail</h3>
                  <div class="mt-4">
                    <canvas id="learningChart" class="w-full h-64"></canvas>
                  </div>
                </div>
              </div>
              <div class="card border border-gray-200">
                <div class="card-body">
                  <h3 class="card-title">Succession – Readiness Distribution</h3>
                  <div class="mt-4">
                    <canvas id="successionChart" class="w-full h-64"></canvas>
                  </div>
                </div>
              </div>
              <div class="card border border-gray-200">
                <div class="card-body">
                  <h3 class="card-title">Competency – Gap Analysis</h3>
                  <div class="mt-4">
                    <canvas id="competencyChart" class="w-full h-64"></canvas>
                  </div>
                </div>
              </div>
            </div>
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const trainingCtx = document.getElementById('trainingChart');
      const learningCtx = document.getElementById('learningChart');
      const successionCtx = document.getElementById('successionChart');
      const competencyCtx = document.getElementById('competencyChart');

      if (trainingCtx && window.Chart) {
        new Chart(trainingCtx, {
          type: 'bar',
          data: {
            labels: <?php echo json_encode($trainingChart['labels']); ?>,
            datasets: [{
              label: 'Completed',
              data: <?php echo json_encode($trainingChart['values']); ?>,
              backgroundColor: 'rgba(59, 130, 246, 0.6)',
              borderColor: 'rgb(59, 130, 246)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: true
              },
              tooltip: {
                enabled: true
              }
            },
            scales: {
              x: {
                title: {
                  display: true,
                  text: 'Program'
                }
              },
              y: {
                title: {
                  display: true,
                  text: 'Completions'
                },
                beginAtZero: true
              }
            }
          }
        });
      }

      if (learningCtx && window.Chart) {
        new Chart(learningCtx, {
          type: 'bar',
          data: {
            labels: <?php echo json_encode($learningChart['labels']); ?>,
            datasets: [{
                label: 'Pass',
                data: <?php echo json_encode($learningChart['pass']); ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1
              },
              {
                label: 'Fail',
                data: <?php echo json_encode($learningChart['fail']); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: true
              },
              tooltip: {
                enabled: true
              }
            },
            scales: {
              x: {
                title: {
                  display: true,
                  text: 'Exam Module'
                }
              },
              y: {
                title: {
                  display: true,
                  text: 'Count'
                },
                beginAtZero: true
              }
            }
          }
        });
      }

      if (successionCtx && window.Chart) {
        new Chart(successionCtx, {
          type: 'pie',
          data: {
            labels: <?php echo json_encode($successionChart['labels']); ?>,
            datasets: [{
              data: <?php echo json_encode($successionChart['values']); ?>,
              backgroundColor: [
                'rgba(34, 197, 94, 0.7)',
                'rgba(59, 130, 246, 0.7)',
                'rgba(234, 179, 8, 0.7)',
                'rgba(239, 68, 68, 0.7)'
              ],
              borderColor: [
                'rgb(34, 197, 94)',
                'rgb(59, 130, 246)',
                'rgb(234, 179, 8)',
                'rgb(239, 68, 68)'
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'bottom'
              },
              tooltip: {
                enabled: true
              }
            }
          }
        });
      }

      if (competencyCtx && window.Chart) {
        new Chart(competencyCtx, {
          type: 'bar',
          data: {
            labels: <?php echo json_encode($competencyChart['labels']); ?>,
            datasets: [{
              label: 'Gap',
              data: <?php echo json_encode($competencyChart['values']); ?>,
              backgroundColor: 'rgba(147, 51, 234, 0.6)',
              borderColor: 'rgb(147, 51, 234)',
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
              legend: {
                display: true
              },
              tooltip: {
                enabled: true
              }
            },
            scales: {
              x: {
                title: {
                  display: true,
                  text: 'Gap (score)'
                },
                beginAtZero: true
              },
              y: {
                title: {
                  display: true,
                  text: 'Competency'
                }
              }
            }
          }
        });
      }
    });
  </script>
  <script src="soliera.js"></script>
  <script src="sidebar.js"></script>
</body>

</html>