<?php
session_start();

if (!isset($_SESSION['role'])) {
  header("Location: index.php");
  exit();
}

$employeeId = $_SESSION['employee_id'] ?? null;
$displayName = trim((string)($_SESSION['employee_name'] ?? ($_SESSION['username'] ?? '')));
$roleDisplay = trim((string)($_SESSION['role'] ?? ''));

define('SUPPRESS_DB_ERRORS', true);
require_once __DIR__ . '/db.php';
define('HR_SKIP_CRITICALGAPS_BOOTSTRAP', true);
require_once __DIR__ . '/COMPETENCY/criticalgaps/config.php';

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

function hr_dashboard_build_analytics(string $departmentFilter, string $rangeKey): array
{
  $now = new DateTimeImmutable('now');
  $rangeDays = 3650;
  $rangeKey = strtolower(trim($rangeKey));
  if ($rangeKey === '7d') $rangeDays = 7;
  if ($rangeKey === '30d') $rangeDays = 30;
  if ($rangeKey === '90d') $rangeDays = 90;
  if ($rangeKey === '180d') $rangeDays = 180;
  if ($rangeKey === '365d') $rangeDays = 365;
  if ($rangeKey === '1y') $rangeDays = 365;
  if ($rangeKey === 'all') $rangeDays = 3650;
  $rangeStart = $now->sub(new DateInterval('P' . $rangeDays . 'D'))->format('Y-m-d 00:00:00');

  $departmentFilter = trim($departmentFilter);
  $deptForLearning = $departmentFilter === '' || $departmentFilter === 'all' ? null : $departmentFilter;

  $data = [
    'generatedAt' => $now->format(DateTimeInterface::ATOM),
    'overview' => [
      'training' => ['labels' => [], 'values' => []],
      'learning' => ['labels' => [], 'pass' => [], 'fail' => []],
      'succession' => ['labels' => [], 'values' => []],
      'competency' => ['labels' => [], 'values' => []],
    ],
    'succession' => [
      'summary' => [
        'pushed' => 0,
        'idpCreated' => 0,
        'idpPending' => 0,
        'avgCompetency' => null,
      ],
      'byStatus' => ['labels' => [], 'values' => []],
      'byDepartment' => ['labels' => [], 'values' => []],
    ],
    'competencyManagement' => [
      'summary' => [
        'totalStandards' => 0,
        'activeStandards' => 0,
        'inactiveStandards' => 0,
        'pendingStandards' => 0,
        'postedStandards' => 0,
        'rejectedStandards' => 0,
        'complianceStandards' => 0,
        'criteriaRows' => 0,
        'activeMappings' => 0,
      ],
      'standardsByApproval' => ['labels' => [], 'values' => []],
      'standardsByCategory' => ['labels' => [], 'values' => []],
      'standardsByRole' => ['labels' => [], 'values' => []],
    ],
    'trainingRequests' => [
      'total' => 0,
      'onboarding' => ['count' => 0, 'approvalRate' => null],
      'idp' => ['count' => 0, 'approvalRate' => null],
      'avgProcessingDays' => null,
      'trend' => ['labels' => [], 'onboarding' => [], 'idp' => []],
      'approval' => ['labels' => [], 'values' => []],
    ],
    'learningModules' => [
      'statusCounts' => [],
      'statusOrder' => ['approved', 'rejected', 'for_compliance', 'posted', 'hold'],
      'statusLabels' => [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'for_compliance' => 'For Compliance',
        'posted' => 'Posted',
        'hold' => 'On Hold',
      ],
      'statusChart' => ['labels' => [], 'values' => []],
      'trend' => ['labels' => [], 'series' => []],
    ],
    'examinations' => [
      'statusCounts' => [],
      'statusOrder' => ['pending', 'posted', 'cancelled'],
      'statusLabels' => [
        'pending' => 'Pending',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
      ],
      'statusChart' => ['labels' => [], 'values' => []],
    ],
    'idp' => [
      'statusCounts' => [],
      'statusOrder' => ['approved', 'rejected', 'for_compliance', 'requested'],
      'statusLabels' => [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'for_compliance' => 'For Compliance',
        'requested' => 'Requested',
      ],
      'statusChart' => ['labels' => [], 'values' => []],
      'byDepartment' => ['labels' => [], 'values' => []],
    ],
    'idpList' => [],
  ];

  try {
    require_once __DIR__ . '/TRAINING/TRAINING/db.php';
    $tconn = training_db_connect();
    if ($tconn && !$tconn->connect_error) {
      $sql = "SELECT department, COUNT(*) AS completed_count
              FROM training_programs
              WHERE status = 'Completed'";
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $sql .= " AND department = ?";
        $stmt = $tconn->prepare($sql . " GROUP BY department ORDER BY department ASC");
        if ($stmt) {
          $stmt->bind_param('s', $departmentFilter);
          $stmt->execute();
          $res = $stmt->get_result();
        } else {
          $res = $tconn->query($sql . " GROUP BY department ORDER BY department ASC");
        }
      } else {
        $res = $tconn->query($sql . " GROUP BY department ORDER BY department ASC");
      }
      $labels = [];
      $values = [];
      if ($res) {
        while ($row = $res->fetch_assoc()) {
          $labels[] = (string)$row['department'];
          $values[] = (int)$row['completed_count'];
        }
      }
      $data['overview']['training'] = [
        'labels' => $labels,
        'values' => $values,
      ];

      $trendSql = "SELECT DATE(training_requested_at) AS d,
                          SUM(CASE WHEN requested_training_type IS NOT NULL AND LOWER(requested_training_type) LIKE '%onboard%' THEN 1 ELSE 0 END) AS onboarding_count,
                          SUM(CASE WHEN requested_training_type IS NOT NULL AND LOWER(requested_training_type) LIKE '%idp%' THEN 1 ELSE 0 END) AS idp_count
                   FROM requested_idps_repository
                   WHERE training_requested_at IS NOT NULL
                     AND training_requested_at >= ?
                     AND delivery_mode IN ('Onsite','Hybrid')";
      $trendParams = [$rangeStart];
      $trendTypes = 's';
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $trendSql .= " AND department = ?";
        $trendParams[] = $departmentFilter;
        $trendTypes .= 's';
      }
      $trendSql .= " GROUP BY DATE(training_requested_at) ORDER BY d ASC";
      $trendLabels = [];
      $trendOnboarding = [];
      $trendIdp = [];
      $trendStmt = $tconn->prepare($trendSql);
      if ($trendStmt) {
        $trendStmt->bind_param($trendTypes, ...$trendParams);
        $trendStmt->execute();
        $trendRes = $trendStmt->get_result();
      } else {
        $trendRes = null;
      }
      if ($trendRes) {
        while ($row = $trendRes->fetch_assoc()) {
          $trendLabels[] = (string)$row['d'];
          $trendOnboarding[] = (int)$row['onboarding_count'];
          $trendIdp[] = (int)$row['idp_count'];
        }
      }

      $totalSql = "SELECT COUNT(*) AS c FROM requested_idps_repository WHERE training_requested_at IS NOT NULL";
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $totalSql .= " AND department = ?";
        $totalStmt = $tconn->prepare($totalSql);
        $totalCount = 0;
        if ($totalStmt) {
          $totalStmt->bind_param('s', $departmentFilter);
          $totalStmt->execute();
          $totalRes = $totalStmt->get_result();
          if ($totalRes) {
            $row = $totalRes->fetch_assoc();
            if ($row && isset($row['c'])) {
              $totalCount = (int)$row['c'];
            }
          }
        }
      } else {
        $totalRes = $tconn->query($totalSql);
        $totalCount = 0;
        if ($totalRes) {
          $row = $totalRes->fetch_assoc();
          if ($row && isset($row['c'])) {
            $totalCount = (int)$row['c'];
          }
        }
      }

      $sumSql = "SELECT
                   SUM(CASE WHEN requested_training_type IS NOT NULL AND LOWER(requested_training_type) LIKE '%onboard%' THEN 1 ELSE 0 END) AS onboarding_total,
                   SUM(CASE WHEN requested_training_type IS NOT NULL AND LOWER(requested_training_type) LIKE '%idp%' THEN 1 ELSE 0 END) AS idp_total,
                   SUM(CASE WHEN idp_status = 'approved' THEN 1 ELSE 0 END) AS approved_total,
                   SUM(CASE WHEN idp_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_total,
                   AVG(CASE WHEN idp_status IN ('approved','rejected') AND training_requested_at IS NOT NULL AND updated_at IS NOT NULL
                            THEN TIMESTAMPDIFF(DAY, training_requested_at, updated_at) END) AS avg_days
                 FROM requested_idps_repository
                 WHERE training_requested_at IS NOT NULL";
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $sumSql .= " AND department = ?";
        $sumStmt = $tconn->prepare($sumSql);
        if ($sumStmt) {
          $sumStmt->bind_param('s', $departmentFilter);
          $sumStmt->execute();
          $sumRes = $sumStmt->get_result();
        } else {
          $sumRes = null;
        }
      } else {
        $sumRes = $tconn->query($sumSql);
      }
      $onboardingTotal = 0;
      $idpTotal = 0;
      $approvedTotal = 0;
      $rejectedTotal = 0;
      $avgDays = null;
      if ($sumRes) {
        $row = $sumRes->fetch_assoc();
        if ($row) {
          $onboardingTotal = (int)($row['onboarding_total'] ?? 0);
          $idpTotal = (int)($row['idp_total'] ?? 0);
          $approvedTotal = (int)($row['approved_total'] ?? 0);
          $rejectedTotal = (int)($row['rejected_total'] ?? 0);
          $avgDays = $row['avg_days'] !== null ? (float)$row['avg_days'] : null;
        }
      }

      $data['trainingRequests']['total'] = $totalCount;
      $data['trainingRequests']['onboarding']['count'] = $onboardingTotal;
      $data['trainingRequests']['idp']['count'] = $idpTotal;
      $denApproval = $approvedTotal + $rejectedTotal;
      $data['trainingRequests']['onboarding']['approvalRate'] = $denApproval > 0 ? ($approvedTotal / $denApproval) * 100.0 : null;
      $data['trainingRequests']['idp']['approvalRate'] = $denApproval > 0 ? ($approvedTotal / $denApproval) * 100.0 : null;
      $data['trainingRequests']['avgProcessingDays'] = $avgDays;
      $data['trainingRequests']['trend'] = [
        'labels' => $trendLabels,
        'onboarding' => $trendOnboarding,
        'idp' => $trendIdp,
      ];

      $approvalSql = "SELECT idp_status, COUNT(*) AS c
                      FROM requested_idps_repository
                      WHERE training_requested_at IS NOT NULL";
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $approvalSql .= " AND department = ?";
        $approvalSql .= " GROUP BY idp_status";
        $approvalStmt = $tconn->prepare($approvalSql);
        if ($approvalStmt) {
          $approvalStmt->bind_param('s', $departmentFilter);
          $approvalStmt->execute();
          $approvalRes = $approvalStmt->get_result();
        } else {
          $approvalRes = null;
        }
      } else {
        $approvalSql .= " GROUP BY idp_status";
        $approvalRes = $tconn->query($approvalSql);
      }
      $apLabels = [];
      $apValues = [];
      if ($approvalRes) {
        while ($row = $approvalRes->fetch_assoc()) {
          $apLabels[] = ucwords(str_replace('_', ' ', (string)$row['idp_status']));
          $apValues[] = (int)$row['c'];
        }
      }
      $data['trainingRequests']['approval'] = [
        'labels' => $apLabels,
        'values' => $apValues,
      ];
    }
  } catch (Throwable $e) {
    error_log('dashboard_training_analytics_error: ' . $e->getMessage());
  }

  try {
    require_once __DIR__ . '/LEARNING/db.php';
    $lconn = usm_db_connect();
    if ($lconn && !$lconn->connect_error) {
      $examSql = "SELECT examinations.title AS exam_title,
                         SUM(CASE WHEN exam_results.passed = 1 THEN 1 ELSE 0 END) AS pass_count,
                         SUM(CASE WHEN exam_results.passed = 0 THEN 1 ELSE 0 END) AS fail_count
                  FROM exam_results
                  JOIN examinations ON examinations.id = exam_results.exam_id
                  WHERE exam_results.completed_at >= ?";
      $examParams = [$rangeStart];
      $examTypes = 's';
      if ($deptForLearning !== null) {
        $examSql .= " AND exam_results.taker_department = ?";
        $examParams[] = $deptForLearning;
        $examTypes .= 's';
      }
      $examSql .= " GROUP BY exam_results.exam_id
                    ORDER BY exam_title ASC";
      $examStmt = $lconn->prepare($examSql);
      if ($examStmt) {
        $examStmt->bind_param($examTypes, ...$examParams);
        $examStmt->execute();
        $examRes = $examStmt->get_result();
      } else {
        $examRes = null;
      }
      $examLabels = [];
      $examPass = [];
      $examFail = [];
      if ($examRes) {
        while ($row = $examRes->fetch_assoc()) {
          $examLabels[] = (string)$row['exam_title'];
          $examPass[] = (int)$row['pass_count'];
          $examFail[] = (int)$row['fail_count'];
        }
      }
      $data['overview']['learning'] = [
        'labels' => $examLabels,
        'pass' => $examPass,
        'fail' => $examFail,
      ];

      $lmSql = "SELECT status, COUNT(*) AS c
                FROM learning_modules";
      $lmWhere = [];
      if ($deptForLearning !== null) {
        $lmWhere[] = "department = ?";
      }
      if (!empty($lmWhere)) {
        $lmSql .= " WHERE " . implode(' AND ', $lmWhere);
      }
      $lmSql .= " GROUP BY status";
      $lmStmt = $lconn->prepare($lmSql);
      if ($lmStmt) {
        if ($deptForLearning !== null) {
          $lmStmt->bind_param('s', $deptForLearning);
        }
        $lmStmt->execute();
        $lmRes = $lmStmt->get_result();
      } else {
        $lmRes = null;
      }
      $statusCounts = [
        'approved' => 0,
        'rejected' => 0,
        'for_compliance' => 0,
        'posted' => 0,
        'hold' => 0,
      ];
      if ($lmRes) {
        while ($row = $lmRes->fetch_assoc()) {
          $rawStatus = (string)$row['status'];
          $key = $rawStatus;
          if ($rawStatus === 'compliance') {
            $key = 'for_compliance';
          }
          if (!isset($statusCounts[$key])) {
            $statusCounts[$key] = 0;
          }
          $statusCounts[$key] += (int)$row['c'];
        }
      }
      $data['learningModules']['statusCounts'] = $statusCounts;
      $lmLabels = [];
      $lmValues = [];
      foreach ($data['learningModules']['statusOrder'] as $st) {
        if (isset($statusCounts[$st])) {
          $lmLabels[] = $data['learningModules']['statusLabels'][$st] ?? $st;
          $lmValues[] = (int)$statusCounts[$st];
        }
      }
      $data['learningModules']['statusChart'] = [
        'labels' => $lmLabels,
        'values' => $lmValues,
      ];

      $trendSql = "SELECT DATE(created_at) AS d,
                          status,
                          COUNT(*) AS c
                   FROM learning_modules
                   WHERE created_at >= ?";
      $trendParams = [$rangeStart];
      $trendTypes = 's';
      if ($deptForLearning !== null) {
        $trendSql .= " AND department = ?";
        $trendParams[] = $deptForLearning;
        $trendTypes .= 's';
      }
      $trendSql .= " GROUP BY DATE(created_at), status
                     ORDER BY DATE(created_at) ASC";
      $trendStmt = $lconn->prepare($trendSql);
      if ($trendStmt) {
        $trendStmt->bind_param($trendTypes, ...$trendParams);
        $trendStmt->execute();
        $trendRes = $trendStmt->get_result();
      } else {
        $trendRes = null;
      }
      $trendLabels = [];
      $series = [
        'approved' => [],
        'rejected' => [],
        'for_compliance' => [],
        'posted' => [],
        'hold' => [],
      ];
      $labelIndex = [];
      if ($trendRes) {
        while ($row = $trendRes->fetch_assoc()) {
          $d = (string)$row['d'];
          if (!isset($labelIndex[$d])) {
            $labelIndex[$d] = count($trendLabels);
            $trendLabels[] = $d;
            foreach ($series as $k => $unused) {
              $series[$k][] = 0;
            }
          }
          $rawStatus = (string)$row['status'];
          $key = $rawStatus;
          if ($rawStatus === 'compliance') {
            $key = 'for_compliance';
          }
          if (!isset($series[$key])) {
            continue;
          }
          $idx = $labelIndex[$d];
          $series[$key][$idx] += (int)$row['c'];
        }
      }
      $data['learningModules']['trend'] = [
        'labels' => $trendLabels,
        'series' => $series,
      ];

      $examStatusSql = "SELECT status, COUNT(*) AS c
                        FROM examinations
                        WHERE created_at >= ?";
      $examStatusParams = [$rangeStart];
      $examStatusTypes = 's';
      if ($deptForLearning !== null) {
        $examStatusSql .= " AND department = ?";
        $examStatusParams[] = $deptForLearning;
        $examStatusTypes .= 's';
      }
      $examStatusSql .= " GROUP BY status";
      $examStatusStmt = $lconn->prepare($examStatusSql);
      if ($examStatusStmt) {
        $examStatusStmt->bind_param($examStatusTypes, ...$examStatusParams);
        $examStatusStmt->execute();
        $examStatusRes = $examStatusStmt->get_result();
      } else {
        $examStatusRes = null;
      }
      $examStatusCounts = [
        'pending' => 0,
        'posted' => 0,
        'cancelled' => 0,
      ];
      if ($examStatusRes) {
        while ($row = $examStatusRes->fetch_assoc()) {
          $statusKey = strtolower((string)$row['status']);
          if (!isset($examStatusCounts[$statusKey])) {
            $examStatusCounts[$statusKey] = 0;
          }
          $examStatusCounts[$statusKey] += (int)$row['c'];
        }
      }
      $data['examinations']['statusCounts'] = $examStatusCounts;
      $examLabels = [];
      $examValues = [];
      foreach ($data['examinations']['statusOrder'] as $st) {
        if (isset($examStatusCounts[$st])) {
          $examLabels[] = $data['examinations']['statusLabels'][$st] ?? ucfirst($st);
          $examValues[] = (int)$examStatusCounts[$st];
        }
      }
      $data['examinations']['statusChart'] = [
        'labels' => $examLabels,
        'values' => $examValues,
      ];
    }
  } catch (Throwable $e) {
    error_log('dashboard_learning_analytics_error: ' . $e->getMessage());
  }

  try {
    require_once __DIR__ . '/COMPETENCY/criticalgaps/config.php';
    if (isset($pdo)) {
      $succSql = "SELECT succession_status, COUNT(*) AS c
                  FROM individual_development_plans";
      $succWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succWhere[] = "department = :dept";
      }
      if (!empty($succWhere)) {
        $succSql .= " WHERE " . implode(' AND ', $succWhere);
      }
      $succSql .= " GROUP BY succession_status";
      $succStmt = $pdo->prepare($succSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succStmt->bindValue(':dept', $departmentFilter, PDO::PARAM_STR);
      }
      $succStmt->execute();
      $succLabels = [];
      $succValues = [];
      while ($row = $succStmt->fetch(PDO::FETCH_ASSOC)) {
        $succLabels[] = (string)$row['succession_status'];
        $succValues[] = (int)$row['c'];
      }
      $data['overview']['succession'] = [
        'labels' => $succLabels,
        'values' => $succValues,
      ];

      $gapSql = "SELECT department, AVG(100-COALESCE(competency,0)) AS avg_gap
                 FROM employees";
      $gapWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $gapWhere[] = "department = :dept";
      }
      if (!empty($gapWhere)) {
        $gapSql .= " WHERE " . implode(' AND ', $gapWhere);
      }
      $gapSql .= " GROUP BY department ORDER BY department ASC";
      $gapStmt = $pdo->prepare($gapSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $gapStmt->bindValue(':dept', $departmentFilter, PDO::PARAM_STR);
      }
      $gapStmt->execute();
      $gapLabels = [];
      $gapValues = [];
      while ($row = $gapStmt->fetch(PDO::FETCH_ASSOC)) {
        $gapLabels[] = (string)$row['department'];
        $gapValues[] = (float)$row['avg_gap'];
      }
      $data['overview']['competency'] = [
        'labels' => $gapLabels,
        'values' => $gapValues,
      ];

      $idpSql = "SELECT idp_status, COUNT(*) AS c
                 FROM individual_development_plans";
      $idpWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $idpWhere[] = "department = :dept2";
      }
      if (!empty($idpWhere)) {
        $idpSql .= " WHERE " . implode(' AND ', $idpWhere);
      }
      $idpSql .= " GROUP BY idp_status";
      $idpStmt = $pdo->prepare($idpSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $idpStmt->bindValue(':dept2', $departmentFilter, PDO::PARAM_STR);
      }
      $idpStmt->execute();
      $statusCounts = [];
      while ($row = $idpStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = (string)$row['idp_status'];
        if ($status === '') {
          $status = 'under_review';
        }
        if (!isset($statusCounts[$status])) {
          $statusCounts[$status] = 0;
        }
        $statusCounts[$status] += (int)$row['c'];
      }
      $data['idp']['statusCounts'] = $statusCounts;
      $statusLabels = [];
      $statusValues = [];
      foreach ($data['idp']['statusOrder'] as $st) {
        if (isset($statusCounts[$st])) {
          $statusLabels[] = $data['idp']['statusLabels'][$st] ?? $st;
          $statusValues[] = (int)$statusCounts[$st];
        }
      }
      $data['idp']['statusChart'] = [
        'labels' => $statusLabels,
        'values' => $statusValues,
      ];

      $deptSql = "SELECT department, COUNT(*) AS c
                  FROM individual_development_plans";
      $deptWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $deptWhere[] = "department = :dept3";
      }
      if (!empty($deptWhere)) {
        $deptSql .= " WHERE " . implode(' AND ', $deptWhere);
      }
      $deptSql .= " GROUP BY department ORDER BY department ASC";
      $deptStmt = $pdo->prepare($deptSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $deptStmt->bindValue(':dept3', $departmentFilter, PDO::PARAM_STR);
      }
      $deptStmt->execute();
      $deptLabels = [];
      $deptValues = [];
      while ($row = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
        $deptLabels[] = (string)$row['department'];
        $deptValues[] = (int)$row['c'];
      }
      $data['idp']['byDepartment'] = [
        'labels' => $deptLabels,
        'values' => $deptValues,
      ];

      $succSumSql = "SELECT
                       COUNT(*) AS total,
                       SUM(CASE WHEN idp_status = 'Created' THEN 1 ELSE 0 END) AS idp_created,
                       SUM(CASE WHEN idp_status = 'Pending' THEN 1 ELSE 0 END) AS idp_pending,
                       AVG(competency) AS avg_comp
                     FROM succession_submissions
                     WHERE is_pushed = 1";
      $succSumWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succSumWhere[] = "department = :sdept";
      }
      if (!empty($succSumWhere)) {
        $succSumSql .= " AND " . implode(' AND ', $succSumWhere);
      }
      $succSumStmt = $pdo->prepare($succSumSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succSumStmt->bindValue(':sdept', $departmentFilter, PDO::PARAM_STR);
      }
      $succSumStmt->execute();
      $succSum = $succSumStmt->fetch(PDO::FETCH_ASSOC) ?: [];
      $data['succession']['summary']['pushed'] = (int)($succSum['total'] ?? 0);
      $data['succession']['summary']['idpCreated'] = (int)($succSum['idp_created'] ?? 0);
      $data['succession']['summary']['idpPending'] = (int)($succSum['idp_pending'] ?? 0);
      $data['succession']['summary']['avgCompetency'] = ($succSum['avg_comp'] !== null) ? (float)$succSum['avg_comp'] : null;

      $succStatusSql = "SELECT status, COUNT(*) AS c
                        FROM succession_submissions
                        WHERE is_pushed = 1";
      $succStatusWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succStatusWhere[] = "department = :sdept2";
      }
      if (!empty($succStatusWhere)) {
        $succStatusSql .= " AND " . implode(' AND ', $succStatusWhere);
      }
      $succStatusSql .= " GROUP BY status
                          ORDER BY FIELD(status, 'Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready')";
      $succStatusStmt = $pdo->prepare($succStatusSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succStatusStmt->bindValue(':sdept2', $departmentFilter, PDO::PARAM_STR);
      }
      $succStatusStmt->execute();
      $succStatusLabels = [];
      $succStatusValues = [];
      while ($row = $succStatusStmt->fetch(PDO::FETCH_ASSOC)) {
        $succStatusLabels[] = (string)$row['status'];
        $succStatusValues[] = (int)$row['c'];
      }
      $data['succession']['byStatus'] = [
        'labels' => $succStatusLabels,
        'values' => $succStatusValues,
      ];

      $succDeptSql = "SELECT department, COUNT(*) AS c
                      FROM succession_submissions
                      WHERE is_pushed = 1";
      $succDeptWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succDeptWhere[] = "department = :sdept3";
      }
      if (!empty($succDeptWhere)) {
        $succDeptSql .= " AND " . implode(' AND ', $succDeptWhere);
      }
      $succDeptSql .= " GROUP BY department ORDER BY department ASC";
      $succDeptStmt = $pdo->prepare($succDeptSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $succDeptStmt->bindValue(':sdept3', $departmentFilter, PDO::PARAM_STR);
      }
      $succDeptStmt->execute();
      $succDeptLabels = [];
      $succDeptValues = [];
      while ($row = $succDeptStmt->fetch(PDO::FETCH_ASSOC)) {
        $succDeptLabels[] = (string)$row['department'];
        $succDeptValues[] = (int)$row['c'];
      }
      $data['succession']['byDepartment'] = [
        'labels' => $succDeptLabels,
        'values' => $succDeptValues,
      ];

    }
  } catch (Throwable $e) {
    error_log('dashboard_competency_analytics_error: ' . $e->getMessage());
  }

  try {
    require_once __DIR__ . '/COMPETENCY/job_desc/db_job_desc.php';
    $jconn = job_desc_mysqli();

    $tableRes = $jconn->query("SHOW TABLES LIKE 'competency_standards'");
    $hasStandards = $tableRes && $tableRes->num_rows > 0;
    if ($tableRes) $tableRes->free();

    if ($hasStandards) {
      $cols = [];
      $colRes = $jconn->query("SHOW COLUMNS FROM competency_standards");
      if ($colRes) {
        while ($row = $colRes->fetch_assoc()) {
          $field = (string)($row['Field'] ?? '');
          if ($field !== '') $cols[$field] = true;
        }
        $colRes->free();
      }
      $hasApproval = isset($cols['approval_status']);

      $sumSql = "SELECT
                   COUNT(*) AS total,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
                   SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count" .
                   ($hasApproval ? ",
                   SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                   SUM(CASE WHEN approval_status = 'posted' THEN 1 ELSE 0 END) AS posted_count,
                   SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                   SUM(CASE WHEN approval_status = 'compliance' THEN 1 ELSE 0 END) AS compliance_count" : "") . "
                 FROM competency_standards";
      $sumRes = $jconn->query($sumSql);
      if ($sumRes) {
        $row = $sumRes->fetch_assoc() ?: [];
        $sumRes->free();
        $data['competencyManagement']['summary']['totalStandards'] = (int)($row['total'] ?? 0);
        $data['competencyManagement']['summary']['activeStandards'] = (int)($row['active_count'] ?? 0);
        $data['competencyManagement']['summary']['inactiveStandards'] = (int)($row['inactive_count'] ?? 0);
        if ($hasApproval) {
          $data['competencyManagement']['summary']['pendingStandards'] = (int)($row['pending_count'] ?? 0);
          $data['competencyManagement']['summary']['postedStandards'] = (int)($row['posted_count'] ?? 0);
          $data['competencyManagement']['summary']['rejectedStandards'] = (int)($row['rejected_count'] ?? 0);
          $data['competencyManagement']['summary']['complianceStandards'] = (int)($row['compliance_count'] ?? 0);
        } else {
          $data['competencyManagement']['summary']['postedStandards'] = (int)($row['active_count'] ?? 0);
        }
      }

      $byApprovalLabels = [];
      $byApprovalValues = [];
      if ($hasApproval) {
        $apRes = $jconn->query("SELECT approval_status, COUNT(*) AS c FROM competency_standards GROUP BY approval_status ORDER BY approval_status ASC");
        if ($apRes) {
          while ($row = $apRes->fetch_assoc()) {
            $byApprovalLabels[] = ucwords(str_replace('_', ' ', (string)($row['approval_status'] ?? '')));
            $byApprovalValues[] = (int)($row['c'] ?? 0);
          }
          $apRes->free();
        }
      } else {
        $byApprovalLabels = ['Posted'];
        $byApprovalValues = [(int)($data['competencyManagement']['summary']['totalStandards'] ?? 0)];
      }
      $data['competencyManagement']['standardsByApproval'] = [
        'labels' => $byApprovalLabels,
        'values' => $byApprovalValues,
      ];

      $catRes = $jconn->query("SELECT category, COUNT(*) AS c FROM competency_standards GROUP BY category ORDER BY category ASC");
      $catLabels = [];
      $catValues = [];
      if ($catRes) {
        while ($row = $catRes->fetch_assoc()) {
          $catLabels[] = ucwords((string)($row['category'] ?? ''));
          $catValues[] = (int)($row['c'] ?? 0);
        }
        $catRes->free();
      }
      $data['competencyManagement']['standardsByCategory'] = [
        'labels' => $catLabels,
        'values' => $catValues,
      ];

      $roleRes = $jconn->query("SELECT role, COUNT(*) AS c FROM competency_standards GROUP BY role ORDER BY role ASC");
      $roleLabels = [];
      $roleValues = [];
      if ($roleRes) {
        while ($row = $roleRes->fetch_assoc()) {
          $roleLabels[] = ucwords((string)($row['role'] ?? ''));
          $roleValues[] = (int)($row['c'] ?? 0);
        }
        $roleRes->free();
      }
      $data['competencyManagement']['standardsByRole'] = [
        'labels' => $roleLabels,
        'values' => $roleValues,
      ];
    }

    $criteriaTableRes = $jconn->query("SHOW TABLES LIKE 'competency_level_criteria'");
    $hasCriteria = $criteriaTableRes && $criteriaTableRes->num_rows > 0;
    if ($criteriaTableRes) $criteriaTableRes->free();
    if ($hasCriteria) {
      $crRes = $jconn->query("SELECT COUNT(*) AS c FROM competency_level_criteria");
      if ($crRes) {
        $data['competencyManagement']['summary']['criteriaRows'] = (int)(($crRes->fetch_assoc()['c'] ?? 0));
        $crRes->free();
      }
    }

    $mapTableRes = $jconn->query("SHOW TABLES LIKE 'job_criteria_mappings'");
    $hasMappings = $mapTableRes && $mapTableRes->num_rows > 0;
    if ($mapTableRes) $mapTableRes->free();
    if ($hasMappings) {
      $mapRes = $jconn->query("SELECT COUNT(*) AS c FROM job_criteria_mappings WHERE is_active = 1");
      if ($mapRes) {
        $data['competencyManagement']['summary']['activeMappings'] = (int)(($mapRes->fetch_assoc()['c'] ?? 0));
        $mapRes->free();
      }
    }

    $jconn->close();
  } catch (Throwable $e) {
    error_log('dashboard_job_desc_analytics_error: ' . $e->getMessage());
  }

  return $data;
}

if (isset($_GET['analytics'])) {
  header('Content-Type: application/json; charset=utf-8');
  $dept = isset($_GET['department']) ? (string)$_GET['department'] : 'all';
  $range = isset($_GET['range']) ? (string)$_GET['range'] : '30d';
  $analytics = hr_dashboard_build_analytics($dept, $range);
  echo json_encode(['success' => true, 'data' => $analytics]);
  exit;
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
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
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
              Welcome &quot;<?php echo htmlspecialchars($roleDisplay !== '' ? $roleDisplay : ''); ?>&quot; to the Human Resource Management System
            </p>
          </div>
          <?php
          $trainingSummary = ['programs' => 0, 'completed' => 0, 'upcoming' => 0];
          $learningSummary = ['taken' => 0, 'pass' => 0, 'fail' => 0];
          $successionSummary = ['candidates' => 0, 'avg' => 0.0, 'departments' => 0];
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
            }
          } catch (Throwable $e) {
            error_log('dashboard_training_summary_error: ' . $e->getMessage());
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
            }
          } catch (Throwable $e) {
            error_log('dashboard_learning_summary_error: ' . $e->getMessage());
          }
          try {
            $successionSummary['candidates'] = (int)($pdo->query("SELECT COUNT(*) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
            $stmtAvg = $pdo->query("SELECT AVG(comp) FROM (SELECT AVG(COALESCE(s2.score,0))/5*100 AS comp FROM employee_kpi_scores s2 JOIN succession_submissions ss ON ss.employee_id = s2.employee_id WHERE ss.is_pushed = 1 GROUP BY s2.employee_id) t");
            $successionSummary['avg'] = (float)($stmtAvg ? ($stmtAvg->fetchColumn() ?? 0.0) : 0.0);
            $successionSummary['departments'] = (int)($pdo->query("SELECT COUNT(DISTINCT department) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
          } catch (Throwable $e) {
            error_log('dashboard_succession_summary_error: ' . $e->getMessage());
          }
          ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
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
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-gray-800">Analytics</h2>
                <p class="text-sm text-gray-500">Real-time training, learning, examinations, and competency insights</p>
              </div>
            </div>

            <div id="analyticsRoot" class="mt-4 space-y-8">
              <section aria-label="Learning modules analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Learning Modules</h3>
                  <div class="text-xs text-gray-500" id="learningModulesSummary"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4" id="learningModulesStatusCards"></div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Modules by Status</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesStatus" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesStatus" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesStatus" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="learningModulesStatus"></div>
                        <canvas id="learningModulesStatusChart" class="w-full h-56 hidden" aria-label="Learning modules status chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="learningModulesStatusEmptyMessage">No learning module status data available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Module Activity Trend</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesTrend" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesTrend" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learningModulesTrend" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="learningModulesTrend"></div>
                        <canvas id="learningModulesTrendChart" class="w-full h-56 hidden" aria-label="Learning modules activity trend chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="learningModulesTrendEmptyMessage">No learning module activity within the selected period.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section aria-label="Created examinations analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Created Examinations</h3>
                  <div class="text-xs text-gray-500" id="examCreatedSummary"></div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Exams by Status</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="examCreatedStatus" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="examCreatedStatus" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="examCreatedStatus" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="examCreatedStatus"></div>
                        <canvas id="examCreatedStatusChart" class="w-full h-56 hidden" aria-label="Examinations by status chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="examCreatedStatusEmptyMessage">No examinations created in this period.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section aria-label="Succession analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Succession</h3>
                  <div class="text-xs text-gray-500" id="successionAnalyticsSummary"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="successionAnalyticsCards"></div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Pushed Candidates by Status</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionStatus" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionStatus" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionStatus" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="successionStatus"></div>
                        <canvas id="successionStatusChart" class="w-full h-56 hidden" aria-label="Succession by status chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="successionStatusEmptyMessage">No succession candidates data available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Pushed Candidates by Department</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionDepartment" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionDepartment" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="successionDepartment" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="successionDepartment"></div>
                        <canvas id="successionDepartmentChart" class="w-full h-56 hidden" aria-label="Succession by department chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="successionDepartmentEmptyMessage">No succession department data available.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section aria-label="Competency management analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Competency Management</h3>
                  <div class="text-xs text-gray-500" id="competencyManagementSummary"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4" id="competencyManagementCards"></div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Standards by Approval Status</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyApproval" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyApproval" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyApproval" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="competencyApproval"></div>
                        <canvas id="competencyApprovalChart" class="w-full h-56 hidden" aria-label="Competency standards approval status chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="competencyApprovalEmptyMessage">No competency standards data available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Standards by Category</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyCategory" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyCategory" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competencyCategory" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="competencyCategory"></div>
                        <canvas id="competencyCategoryChart" class="w-full h-56 hidden" aria-label="Competency standards category chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="competencyCategoryEmptyMessage">No competency category data available.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

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

      const primaryColor = '#2563eb';
      const successColor = '#16a34a';
      const dangerColor = '#dc2626';
      const warningColor = '#f97316';
      const infoColor = '#0ea5e9';
      const slateColor = '#64748b';

      const analyticsEndpoint = '<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES); ?>?analytics=1';

      const charts = {};

      function hideSkeleton(key) {
        const skeleton = document.querySelector(`[data-skeleton="${key}"]`);
        if (skeleton) skeleton.classList.add('hidden');
      }

      function showCanvas(id) {
        const canvas = document.getElementById(id);
        if (canvas) canvas.classList.remove('hidden');
      }

      function showEmptyMessage(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('hidden');
      }

      function formatDateLabel(dateStr) {
        const d = new Date(dateStr);
        if (Number.isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString(undefined, {
          month: 'short',
          day: 'numeric'
        });
      }

      function buildQuery(params) {
        const url = new URL(analyticsEndpoint, window.location.origin);
        Object.keys(params).forEach(key => {
          if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
            url.searchParams.set(key, params[key]);
          }
        });
        return url.toString();
      }

      function exportChart(id, format, filename) {
        const chart = charts[id];
        if (!chart) return;

        if (format === 'png') {
          const link = document.createElement('a');
          link.href = chart.toBase64Image('image/png', 1.0);
          link.download = filename + '.png';
          link.click();
          return;
        }

        if (format === 'csv') {
          const labels = chart.data.labels || [];
          const datasets = chart.data.datasets || [];
          const header = ['Label'].concat(datasets.map(d => d.label || 'Series')).join(',');
          const rows = labels.map((label, idx) => {
            const cols = datasets.map(ds => {
              const v = Array.isArray(ds.data) ? ds.data[idx] : null;
              return typeof v === 'number' ? v : (v ?? '');
            });
            return [label].concat(cols).join(',');
          });
          const csv = [header].concat(rows).join('\r\n');
          const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
          });
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = filename + '.csv';
          link.click();
          URL.revokeObjectURL(url);
          return;
        }

        if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
          const doc = new window.jspdf.jsPDF({
            orientation: 'landscape',
            unit: 'pt',
            format: 'a4'
          });
          const imgData = chart.toBase64Image('image/png', 1.0);
          const pageWidth = doc.internal.pageSize.getWidth();
          const pageHeight = doc.internal.pageSize.getHeight();
          const margin = 32;
          const imgWidth = pageWidth - margin * 2;
          const imgHeight = chart.height ? (imgWidth * chart.height / chart.width) : (pageHeight - margin * 2);
          doc.text(filename, margin, margin - 8);
          doc.addImage(imgData, 'PNG', margin, margin, imgWidth, Math.min(imgHeight, pageHeight - margin * 2));
          doc.save(filename + '.pdf');
        }
      }

      document.querySelectorAll('[data-export]').forEach(btn => {
        btn.addEventListener('click', function() {
          const key = this.getAttribute('data-export');
          const format = this.getAttribute('data-format') || 'png';
          exportChart(key, format, 'hr_dashboard_' + key + '_' + new Date().toISOString().slice(0, 10));
        });
      });

      function attachDrilldown(chart, type) {
        chart.options.onClick = function(evt, elements) {
          if (!elements || elements.length === 0) return;
          const element = elements[0];
          const label = chart.data.labels[element.index];
          if (!label) return;
          let targetUrl = null;
          if (type === 'training') {
            const search = encodeURIComponent(label);
            targetUrl = 'TRAINING/TRAINING/trainingrequest.php?source=dashboard&search=' + search;
          } else if (type === 'learning') {
            const search = encodeURIComponent(label);
            targetUrl = 'LEARNING/training_dev_officer/exam_results.php?source=dashboard&exam_title=' + search;
          } else if (type === 'succession') {
            targetUrl = 'COMPETENCY/criticalgaps/index.php?source=dashboard&readiness=' + encodeURIComponent(label);
          } else if (type === 'competency') {
            targetUrl = 'COMPETENCY/criticalgaps/index.php?source=dashboard&department=' + encodeURIComponent(label);
          } else if (type === 'successionStatus') {
            targetUrl = 'SUCCESSION/HR_director/succession_dashboard.php?source=dashboard&status=' + encodeURIComponent(label);
          } else if (type === 'successionDepartment') {
            targetUrl = 'SUCCESSION/HR_director/succession_dashboard.php?source=dashboard&department=' + encodeURIComponent(label);
          } else if (type === 'learningModulesStatus') {
            targetUrl = 'LEARNING/training_dev_officer/learning_module_repository.php?source=dashboard&status=' + encodeURIComponent(label);
          } else if (type === 'idpStatus') {
            targetUrl = 'COMPETENCY/criticalgaps/idp_repository.php?source=dashboard&status=' + encodeURIComponent(label);
          } else if (type === 'idpDepartment') {
            targetUrl = 'COMPETENCY/criticalgaps/idp_repository.php?source=dashboard&department=' + encodeURIComponent(label);
          } else if (type === 'trainingRequests') {
            targetUrl = 'TRAINING/TRAINING/trainingrequest.php?source=dashboard&date=' + encodeURIComponent(label);
          } else if (type === 'trainingApproval') {
            targetUrl = 'TRAINING/TRAINING/trainingrequest.php?source=dashboard&approval_segment=' + encodeURIComponent(label);
          } else if (type === 'competencyStatusTrend') {
            targetUrl = 'COMPETENCY/criticalgaps/index.php?source=dashboard&status_date=' + encodeURIComponent(label);
          } else if (type === 'competencyApproval') {
            targetUrl = 'COMPETENCY/job_desc/index.php?source=dashboard';
          } else if (type === 'competencyCategory') {
            targetUrl = 'COMPETENCY/job_desc/index.php?source=dashboard';
          }
          if (targetUrl) {
            window.location.href = targetUrl;
          }
        };
      }

      function renderOverviewCharts() {}

      function renderTrainingRequests() {}

      function renderLearningModules(data) {
        const analytics = data.learningModules || {};
        const summaryEl = document.getElementById('learningModulesSummary');
        const cardsRoot = document.getElementById('learningModulesStatusCards');
        hideSkeleton('learningModulesStatus');
        hideSkeleton('learningModulesTrend');

        const statusCounts = analytics.statusCounts || {};
        const statusOrder = analytics.statusOrder || ['approved', 'rejected', 'for_compliance', 'posted', 'hold'];
        const statusLabels = analytics.statusLabels || {
          approved: 'Approved',
          rejected: 'Rejected',
          for_compliance: 'For Compliance',
          posted: 'Posted',
          hold: 'On Hold'
        };

        const total = Object.values(statusCounts).reduce((sum, v) => sum + (v || 0), 0);
        if (summaryEl) summaryEl.textContent = total > 0 ? `${total} learning modules in repository` : 'No learning modules found';

        if (cardsRoot) {
          cardsRoot.innerHTML = '';
          statusOrder.forEach(key => {
            const count = statusCounts[key] ?? null;
            if (count === null) return;
            const label = statusLabels[key] || key;
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'text-left rounded-xl border border-gray-200 p-3 bg-white hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50';
            card.dataset.status = key;
            card.innerHTML = '<div class="text-[0.65rem] font-semibold text-gray-500 uppercase tracking-wider mb-1">' +
              label +
              '</div><div class="text-xl font-bold text-gray-900">' +
              count +
              '</div>';
            card.addEventListener('click', () => {
              window.location.href = 'LEARNING/training_dev_officer/learning_module_repository.php?source=dashboard&status=' + encodeURIComponent(label);
            });
            cardsRoot.appendChild(card);
          });
        }

        const statusChart = analytics.statusChart || {};
        if (Array.isArray(statusChart.labels) && statusChart.labels.length > 0 && Array.isArray(statusChart.values) && statusChart.values.some(v => v > 0)) {
          const ctx = document.getElementById('learningModulesStatusChart');
          if (ctx && window.Chart) {
            showCanvas('learningModulesStatusChart');
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: statusChart.labels,
                datasets: [{
                  label: 'Modules',
                  data: statusChart.values,
                  backgroundColor: ['#22c55e', '#ef4444', '#f97316', '#0ea5e9', '#eab308']
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Modules'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'learningModulesStatus');
            charts.learningModulesStatus = chart;
          }
        } else {
          showEmptyMessage('learningModulesStatusEmptyMessage');
        }

        const trend = analytics.trend || {};
        if (Array.isArray(trend.labels) && trend.labels.length > 0 && Object.values(trend.series || {}).some(arr => Array.isArray(arr) && arr.some(v => v > 0))) {
          const ctx = document.getElementById('learningModulesTrendChart');
          if (ctx && window.Chart) {
            showCanvas('learningModulesTrendChart');
            const datasets = [];
            const colors = {
              approved: '#22c55e',
              rejected: '#ef4444',
              for_compliance: '#f97316',
              posted: '#0ea5e9',
              hold: '#eab308'
            };
            Object.keys(trend.series || {}).forEach(key => {
              const arr = trend.series[key] || [];
              if (!arr.some(v => v > 0)) return;
              datasets.push({
                label: statusLabels[key] || key,
                data: arr,
                borderColor: colors[key] || primaryColor,
                backgroundColor: colors[key] || primaryColor,
                tension: 0.3,
                fill: false
              });
            });
            const chart = new Chart(ctx, {
              type: 'line',
              data: {
                labels: trend.labels.map(formatDateLabel),
                datasets
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: 'bottom'
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Modules'
                    }
                  }
                }
              }
            });
            charts.learningModulesTrend = chart;
          }
        } else {
          showEmptyMessage('learningModulesTrendEmptyMessage');
        }
      }

      function renderExaminations(data) {
        const analytics = data.examinations || {};
        const chartData = analytics.statusChart || {};
        const summaryEl = document.getElementById('examCreatedSummary');
        hideSkeleton('examCreatedStatus');
        const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
        const values = Array.isArray(chartData.values) ? chartData.values : [];
        if (labels.length > 0 && values.some(v => v > 0)) {
          const ctx = document.getElementById('examCreatedStatusChart');
          if (ctx && window.Chart) {
            showCanvas('examCreatedStatusChart');
            const chart = new Chart(ctx, {
              type: 'pie',
              data: {
                labels,
                datasets: [{
                  data: values,
                  backgroundColor: ['#0ea5e9', '#22c55e', '#f97316']
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: 'bottom'
                  },
                  tooltip: {
                    callbacks: {
                      label: ctx => {
                        const total = ctx.dataset.data.reduce((sum, v) => sum + v, 0);
                        const val = ctx.parsed;
                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                        return ctx.label + ': ' + val + ' (' + pct + '%)';
                      }
                    }
                  }
                }
              }
            });
            charts.examCreatedStatus = chart;
            if (summaryEl) {
              const total = values.reduce((sum, v) => sum + v, 0);
              summaryEl.textContent = total + ' examinations created';
            }
          }
        } else {
          showEmptyMessage('examCreatedStatusEmptyMessage');
          if (summaryEl) {
            summaryEl.textContent = 'No examinations created';
          }
        }
      }

      function renderStatCards(rootId, items) {
        const root = document.getElementById(rootId);
        if (!root) return;
        root.innerHTML = '';
        items.forEach(item => {
          const el = document.createElement('div');
          el.className = 'card border border-gray-200';
          const sub = item.sub ? `<div class="text-xs text-gray-500 mt-1">${item.sub}</div>` : '';
          el.innerHTML = `<div class="card-body p-4">
              <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">${item.label}</div>
              <div class="mt-1 text-2xl font-bold text-gray-900">${item.value}</div>
              ${sub}
            </div>`;
          root.appendChild(el);
        });
      }

      function renderSuccessionAnalytics(data) {
        const analytics = data.succession || {};
        const summary = analytics.summary || {};
        const summaryEl = document.getElementById('successionAnalyticsSummary');
        hideSkeleton('successionStatus');
        hideSkeleton('successionDepartment');

        const pushed = Number(summary.pushed || 0);
        const avg = summary.avgCompetency !== null && summary.avgCompetency !== undefined ? Number(summary.avgCompetency) : null;
        if (summaryEl) {
          summaryEl.textContent = 'Pushed: ' + pushed + (avg !== null ? (' • Avg Competency: ' + avg.toFixed(1) + '%') : '');
        }

        renderStatCards('successionAnalyticsCards', [{
          label: 'Pushed',
          value: pushed,
          sub: 'Forwarded candidates'
        }, {
          label: 'IDP Created',
          value: Number(summary.idpCreated || 0),
          sub: 'Pipeline progress'
        }, {
          label: 'IDP Pending',
          value: Number(summary.idpPending || 0),
          sub: 'Needs IDP'
        }, {
          label: 'Avg Competency',
          value: avg !== null ? avg.toFixed(1) + '%' : 'N/A',
          sub: 'From succession submissions'
        }]);

        const byStatus = analytics.byStatus || {};
        const stLabels = Array.isArray(byStatus.labels) ? byStatus.labels : [];
        const stValues = Array.isArray(byStatus.values) ? byStatus.values : [];
        if (stLabels.length > 0 && stValues.some(v => v > 0)) {
          const ctx = document.getElementById('successionStatusChart');
          if (ctx && window.Chart) {
            showCanvas('successionStatusChart');
            if (charts.successionStatus) {
              charts.successionStatus.destroy();
            }
            const colors = stLabels.map(l => {
              if (l === 'Succession Ready') return '#22c55e';
              if (l === 'Upskilling') return '#0ea5e9';
              if (l === 'Refresher Training') return '#f97316';
              if (l === 'Reskilling') return '#ef4444';
              return '#94a3b8';
            });
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: stLabels,
                datasets: [{
                  label: 'Candidates',
                  data: stValues,
                  backgroundColor: colors
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Candidates'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'successionStatus');
            charts.successionStatus = chart;
          }
        } else {
          showEmptyMessage('successionStatusEmptyMessage');
        }

        const byDept = analytics.byDepartment || {};
        const dLabels = Array.isArray(byDept.labels) ? byDept.labels : [];
        const dValues = Array.isArray(byDept.values) ? byDept.values : [];
        if (dLabels.length > 0 && dValues.some(v => v > 0)) {
          const ctx = document.getElementById('successionDepartmentChart');
          if (ctx && window.Chart) {
            showCanvas('successionDepartmentChart');
            if (charts.successionDepartment) {
              charts.successionDepartment.destroy();
            }
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: dLabels,
                datasets: [{
                  label: 'Candidates',
                  data: dValues,
                  backgroundColor: '#7c3aed'
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Candidates'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'successionDepartment');
            charts.successionDepartment = chart;
          }
        } else {
          showEmptyMessage('successionDepartmentEmptyMessage');
        }
      }

      function renderCompetencyManagementAnalytics(data) {
        const analytics = data.competencyManagement || {};
        const summary = analytics.summary || {};
        const summaryEl = document.getElementById('competencyManagementSummary');
        hideSkeleton('competencyApproval');
        hideSkeleton('competencyCategory');

        const total = Number(summary.totalStandards || 0);
        const pending = Number(summary.pendingStandards || 0);
        if (summaryEl) {
          summaryEl.textContent = total + ' standards • Pending: ' + pending;
        }

        renderStatCards('competencyManagementCards', [{
          label: 'Standards',
          value: total,
          sub: 'Total defined'
        }, {
          label: 'Active',
          value: Number(summary.activeStandards || 0),
          sub: 'Currently active'
        }, {
          label: 'Pending',
          value: pending,
          sub: 'Needs approval'
        }, {
          label: 'Posted',
          value: Number(summary.postedStandards || 0),
          sub: 'Approved/posted'
        }, {
          label: 'Mappings',
          value: Number(summary.activeMappings || 0),
          sub: 'Active mappings'
        }]);

        const byApproval = analytics.standardsByApproval || {};
        const apLabels = Array.isArray(byApproval.labels) ? byApproval.labels : [];
        const apValues = Array.isArray(byApproval.values) ? byApproval.values : [];
        if (apLabels.length > 0 && apValues.some(v => v > 0)) {
          const ctx = document.getElementById('competencyApprovalChart');
          if (ctx && window.Chart) {
            showCanvas('competencyApprovalChart');
            if (charts.competencyApproval) {
              charts.competencyApproval.destroy();
            }
            const chart = new Chart(ctx, {
              type: 'doughnut',
              data: {
                labels: apLabels,
                datasets: [{
                  data: apValues,
                  backgroundColor: ['#f97316', '#22c55e', '#ef4444', '#0ea5e9', '#94a3b8']
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: 'bottom'
                  }
                }
              }
            });
            attachDrilldown(chart, 'competencyApproval');
            charts.competencyApproval = chart;
          }
        } else {
          showEmptyMessage('competencyApprovalEmptyMessage');
        }

        const byCat = analytics.standardsByCategory || {};
        const cLabels = Array.isArray(byCat.labels) ? byCat.labels : [];
        const cValues = Array.isArray(byCat.values) ? byCat.values : [];
        if (cLabels.length > 0 && cValues.some(v => v > 0)) {
          const ctx = document.getElementById('competencyCategoryChart');
          if (ctx && window.Chart) {
            showCanvas('competencyCategoryChart');
            if (charts.competencyCategory) {
              charts.competencyCategory.destroy();
            }
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: cLabels,
                datasets: [{
                  label: 'Standards',
                  data: cValues,
                  backgroundColor: '#2563eb'
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Standards'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'competencyCategory');
            charts.competencyCategory = chart;
          }
        } else {
          showEmptyMessage('competencyCategoryEmptyMessage');
        }
      }

      function loadAnalytics() {
        fetch(analyticsEndpoint, {
            headers: {
              'Accept': 'application/json'
            }
          })
          .then(resp => resp.json())
          .then(payload => {
            if (!payload || !payload.success) {
              return;
            }
            const data = payload.data || {};
            renderLearningModules(data);
            renderExaminations(data);
            renderSuccessionAnalytics(data);
            renderCompetencyManagementAnalytics(data);
          })
          .catch(err => {
            console.error('Analytics load error', err);
          });
      }

      loadAnalytics();
      setInterval(loadAnalytics, 60000);
    });
  </script>
  <script src="soliera.js"></script>
  <script src="sidebar.js"></script>
</body>

</html>
