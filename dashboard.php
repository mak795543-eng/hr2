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

function hr_dashboard_build_analytics(string $departmentFilter, string $rangeKey): array
{
  $now = new DateTimeImmutable('now');
  $rangeDays = 30;
  if ($rangeKey === '90d') {
    $rangeDays = 90;
  } elseif ($rangeKey === '365d') {
    $rangeDays = 365;
  } elseif ($rangeKey === 'all') {
    $rangeDays = 3650;
  }
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
      'statusOrder' => ['approved', 'rejected', 'for_compliance', 'posted', 'hold', 'pending'],
      'statusLabels' => [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'for_compliance' => 'For Compliance',
        'posted' => 'Posted',
        'hold' => 'On Hold',
        'pending' => 'Pending',
      ],
      'statusChart' => ['labels' => [], 'values' => []],
      'trend' => ['labels' => [], 'series' => []],
    ],
    'competencyProfiles' => [
      'counts' => [
        'ongoing_training' => 0,
        'forwarded_idp' => 0,
        'training_requested' => 0,
        'idp_created' => 0,
      ],
      'trend' => ['labels' => [], 'series' => []],
    ],
    'idp' => [
      'statusCounts' => [],
      'statusOrder' => ['approved', 'under_review', 'for_compliance', 'on_hold', 'rejected', 'cancelled', 'requested'],
      'statusLabels' => [
        'approved' => 'Approved',
        'under_review' => 'Under Review',
        'for_compliance' => 'For Compliance',
        'on_hold' => 'On Hold',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'requested' => 'Requested',
      ],
      'statusChart' => ['labels' => [], 'values' => []],
      'byDepartment' => ['labels' => [], 'values' => []],
    ],
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
        'pending' => 0,
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
        'pending' => [],
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

      $counts = [
        'ongoing_training' => 0,
        'forwarded_idp' => 0,
        'training_requested' => 0,
        'idp_created' => 0,
      ];
      $statusMap = [
        'approved' => 'idp_created',
        'for_compliance' => 'training_requested',
        'on_hold' => 'training_requested',
        'under_review' => 'forwarded_idp',
        'requested' => 'training_requested',
      ];
      $compSql = "SELECT idp_status, COUNT(*) AS c
                  FROM individual_development_plans";
      $compWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $compWhere[] = "department = :dept4";
      }
      if (!empty($compWhere)) {
        $compSql .= " WHERE " . implode(' AND ', $compWhere);
      }
      $compSql .= " GROUP BY idp_status";
      $compStmt = $pdo->prepare($compSql);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $compStmt->bindValue(':dept4', $departmentFilter, PDO::PARAM_STR);
      }
      $compStmt->execute();
      while ($row = $compStmt->fetch(PDO::FETCH_ASSOC)) {
        $st = (string)$row['idp_status'];
        $key = $statusMap[$st] ?? null;
        if ($key !== null) {
          $counts[$key] += (int)$row['c'];
        }
      }
      $data['competencyProfiles']['counts'] = $counts;

      $trendSql = "SELECT DATE(updated_at) AS d, idp_status, COUNT(*) AS c
                   FROM individual_development_plans
                   WHERE updated_at >= :rs";
      $trendWhere = [];
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $trendWhere[] = "department = :dept5";
      }
      if (!empty($trendWhere)) {
        $trendSql .= " AND " . implode(' AND ', $trendWhere);
      }
      $trendSql .= " GROUP BY DATE(updated_at), idp_status
                     ORDER BY DATE(updated_at) ASC";
      $trendStmt = $pdo->prepare($trendSql);
      $trendStmt->bindValue(':rs', $rangeStart, PDO::PARAM_STR);
      if ($departmentFilter !== '' && $departmentFilter !== 'all') {
        $trendStmt->bindValue(':dept5', $departmentFilter, PDO::PARAM_STR);
      }
      $trendStmt->execute();
      $trendLabels = [];
      $trendSeries = [
        'ongoing_training' => [],
        'forwarded_idp' => [],
        'training_requested' => [],
        'idp_created' => [],
      ];
      $labelIndex = [];
      while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {
        $d = (string)$row['d'];
        if (!isset($labelIndex[$d])) {
          $labelIndex[$d] = count($trendLabels);
          $trendLabels[] = $d;
          foreach ($trendSeries as $k => $unused) {
            $trendSeries[$k][] = 0;
          }
        }
        $st = (string)$row['idp_status'];
        $mapped = $statusMap[$st] ?? null;
        if ($mapped === null || !isset($trendSeries[$mapped])) {
          continue;
        }
        $idx = $labelIndex[$d];
        $trendSeries[$mapped][$idx] += (int)$row['c'];
      }
      $data['competencyProfiles']['trend'] = [
        'labels' => $trendLabels,
        'series' => $trendSeries,
      ];
    }
  } catch (Throwable $e) {
    error_log('dashboard_competency_analytics_error: ' . $e->getMessage());
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
              Welcome &quot;<?php echo htmlspecialchars($roleDisplay !== '' ? $roleDisplay : ''); ?>&quot; to the HR Training and Development System
            </p>
          </div>
          <?php
          $trainingSummary = ['programs' => 0, 'completed' => 0, 'upcoming' => 0];
          $learningSummary = ['taken' => 0, 'pass' => 0, 'fail' => 0];
          $successionSummary = ['candidates' => 0, 'avg' => 0.0, 'departments' => 0];
          $competencySummary = ['employees' => 0, 'avg_gap' => 0.0];
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
            require_once __DIR__ . '/COMPETENCY/criticalgaps/config.php';
            if (isset($pdo)) {
              $successionSummary['candidates'] = (int)($pdo->query("SELECT COUNT(*) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
              $stmtAvg = $pdo->query("SELECT AVG(comp) FROM (SELECT AVG(COALESCE(s2.score,0))/5*100 AS comp FROM employee_kpi_scores s2 JOIN succession_submissions ss ON ss.employee_id = s2.employee_id WHERE ss.is_pushed = 1 GROUP BY s2.employee_id) t");
              $successionSummary['avg'] = (float)($stmtAvg ? ($stmtAvg->fetchColumn() ?? 0.0) : 0.0);
              $successionSummary['departments'] = (int)($pdo->query("SELECT COUNT(DISTINCT department) FROM succession_submissions WHERE is_pushed = 1")->fetchColumn() ?? 0);
              $stEmp = $pdo->query("SELECT COUNT(*) FROM employees");
              $competencySummary['employees'] = (int)($stEmp ? ($stEmp->fetchColumn() ?? 0) : 0);
              $stGap = $pdo->query("SELECT AVG(100-COALESCE(competency,0)) FROM employees");
              $competencySummary['avg_gap'] = (float)($stGap ? ($stGap->fetchColumn() ?? 0.0) : 0.0);
            }
          } catch (Throwable $e) {
            error_log('dashboard_competency_summary_error: ' . $e->getMessage());
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
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-gray-800">Analytics</h2>
                <p class="text-sm text-gray-500">Real-time training, learning, succession, competency, and IDP insights</p>
              </div>
              <div class="flex flex-wrap gap-3 items-center">
                <div class="form-control">
                  <label class="label">
                    <span class="label-text text-xs font-semibold uppercase tracking-wider">Department</span>
                  </label>
                  <select id="analyticsDepartmentFilter" class="select select-bordered select-sm min-w-[10rem]">
                    <option value="all">All Departments</option>
                    <option value="Front Office / Reception">Front Office / Reception</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="Food & Beverage (F&amp;B)">Food &amp; Beverage (F&amp;B)</option>
                    <option value="Kitchen / Culinary">Kitchen / Culinary</option>
                    <option value="Sales & Marketing">Sales &amp; Marketing</option>
                    <option value="Human Resources (HR)">Human Resources (HR)</option>
                    <option value="Finance / Accounting">Finance / Accounting</option>
                    <option value="Engineering / Maintenance">Engineering / Maintenance</option>
                    <option value="Security">Security</option>
                  </select>
                </div>
                <div class="form-control">
                  <label class="label">
                    <span class="label-text text-xs font-semibold uppercase tracking-wider">Time Range</span>
                  </label>
                  <select id="analyticsRangeFilter" class="select select-bordered select-sm min-w-[8rem]">
                    <option value="30d">Last 30 days</option>
                    <option value="90d">Last 90 days</option>
                    <option value="365d">Last 12 months</option>
                    <option value="all">All time</option>
                  </select>
                </div>
              </div>
            </div>

            <div id="analyticsRoot" class="mt-4 space-y-8">
              <section aria-label="Overview charts" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Charts</h3>
                  <div class="text-xs text-gray-500" id="analyticsLastUpdated"></div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Training – Program Completion</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="training" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="training" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="training" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-64 rounded-lg bg-gray-100 animate-pulse" data-skeleton="training"></div>
                        <canvas id="trainingChart" class="w-full h-64 hidden" aria-label="Training completion chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="trainingEmptyMessage">No training completion data available for the selected filters.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Learning – Exam Pass vs Fail</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learning" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learning" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="learning" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-64 rounded-lg bg-gray-100 animate-pulse" data-skeleton="learning"></div>
                        <canvas id="learningChart" class="w-full h-64 hidden" aria-label="Learning exam pass fail chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="learningEmptyMessage">No exam results available for the selected filters.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Succession – Readiness Distribution</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="succession" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="succession" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="succession" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-64 rounded-lg bg-gray-100 animate-pulse" data-skeleton="succession"></div>
                        <canvas id="successionChart" class="w-full h-64 hidden" aria-label="Succession readiness chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="successionEmptyMessage">No succession readiness data available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Competency – Gap Analysis</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competency" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competency" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="competency" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-64 rounded-lg bg-gray-100 animate-pulse" data-skeleton="competency"></div>
                        <canvas id="competencyChart" class="w-full h-64 hidden" aria-label="Competency gap chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="competencyEmptyMessage">No competency gap data available.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section aria-label="Training requests analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Training Requests</h3>
                  <div class="text-xs text-gray-500" id="trainingRequestsSummary"></div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                  <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Onboarding Requests</div>
                    <div class="text-2xl font-bold text-gray-900" id="onboardingRequestsCount">0</div>
                    <div class="text-xs text-gray-500 mt-1" id="onboardingRequestsMeta"></div>
                  </div>
                  <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">IDP Training Requests</div>
                    <div class="text-2xl font-bold text-gray-900" id="idpTrainingRequestsCount">0</div>
                    <div class="text-xs text-gray-500 mt-1" id="idpTrainingRequestsMeta"></div>
                  </div>
                  <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Average Processing Time</div>
                    <div class="text-2xl font-bold text-gray-900" id="trainingProcessingTime">0d</div>
                    <div class="text-xs text-gray-500 mt-1">Across approved/rejected requests in range.</div>
                  </div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Request Volume Trend</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingRequests" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingRequests" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingRequests" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="trainingRequests"></div>
                        <canvas id="trainingRequestsChart" class="w-full h-56 hidden" aria-label="Training requests volume trend" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="trainingRequestsEmptyMessage">No training request history available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Approval Rate</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingApproval" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingApproval" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="trainingApproval" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="trainingApproval"></div>
                        <canvas id="trainingApprovalChart" class="w-full h-56 hidden" aria-label="Training approval rate chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="trainingApprovalEmptyMessage">No approval data available.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

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

              <section aria-label="Competency profiles status" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Competency Profiles Status</h3>
                  <div class="text-xs text-gray-500" id="competencyProfilesSummary"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div class="rounded-xl border border-gray-200 p-4 bg-red-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">On-going Training</div>
                    <div class="text-2xl font-bold text-red-700" id="statusOngoingTraining">0</div>
                  </div>
                  <div class="rounded-xl border border-gray-200 p-4 bg-amber-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Forwarded for IDP</div>
                    <div class="text-2xl font-bold text-amber-700" id="statusForwardedIdp">0</div>
                  </div>
                  <div class="rounded-xl border border-gray-200 p-4 bg-sky-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Training Requested</div>
                    <div class="text-2xl font-bold text-sky-700" id="statusTrainingRequested">0</div>
                  </div>
                  <div class="rounded-xl border border-gray-200 p-4 bg-emerald-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">IDP Created</div>
                    <div class="text-2xl font-bold text-emerald-700" id="statusIdpCreated">0</div>
                  </div>
                </div>
                <div class="card border border-gray-200">
                  <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="card-title text-sm font-semibold">Status Trend</h4>
                      <div class="flex gap-2 text-xs">
                        <button type="button" class="btn btn-ghost btn-xs" data-export="competencyStatusTrend" data-format="png">PNG</button>
                        <button type="button" class="btn btn-ghost btn-xs" data-export="competencyStatusTrend" data-format="csv">CSV</button>
                        <button type="button" class="btn btn-ghost btn-xs" data-export="competencyStatusTrend" data-format="pdf">PDF</button>
                      </div>
                    </div>
                    <div class="mt-2">
                      <div class="analytics-skeleton h-64 rounded-lg bg-gray-100 animate-pulse" data-skeleton="competencyStatusTrend"></div>
                      <canvas id="competencyStatusTrendChart" class="w-full h-64 hidden" aria-label="Competency profile status trend chart" role="img"></canvas>
                      <div class="text-sm text-gray-500 mt-2 hidden" id="competencyStatusTrendEmptyMessage">No competency status changes recorded in the selected range.</div>
                    </div>
                  </div>
                </div>
              </section>

              <section aria-label="Individual Development Plans analytics" class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">IDP</h3>
                  <div class="text-xs text-gray-500" id="idpSummary"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="idpStatusCards"></div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">IDPs by Status</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpStatus" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpStatus" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpStatus" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="idpStatus"></div>
                        <canvas id="idpStatusChart" class="w-full h-56 hidden" aria-label="IDP status chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="idpStatusEmptyMessage">No IDP records available.</div>
                      </div>
                    </div>
                  </div>
                  <div class="card border border-gray-200">
                    <div class="card-body">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="card-title text-sm font-semibold">Department Comparison</h4>
                        <div class="flex gap-2 text-xs">
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpDepartment" data-format="png">PNG</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpDepartment" data-format="csv">CSV</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-export="idpDepartment" data-format="pdf">PDF</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="analytics-skeleton h-56 rounded-lg bg-gray-100 animate-pulse" data-skeleton="idpDepartment"></div>
                        <canvas id="idpDepartmentChart" class="w-full h-56 hidden" aria-label="IDP department comparison chart" role="img"></canvas>
                        <div class="text-sm text-gray-500 mt-2 hidden" id="idpDepartmentEmptyMessage">No IDP department comparison data available.</div>
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
          }
          if (targetUrl) {
            window.location.href = targetUrl;
          }
        };
      }

      function renderOverviewCharts(data) {
        const overview = data.overview || {};
        const training = overview.training || {};
        const learning = overview.learning || {};
        const succession = overview.succession || {};
        const competency = overview.competency || {};

        const lastUpdatedEl = document.getElementById('analyticsLastUpdated');
        if (lastUpdatedEl && data.generatedAt) {
          const d = new Date(data.generatedAt);
          lastUpdatedEl.textContent = 'Updated ' + d.toLocaleString();
        }

        hideSkeleton('training');
        hideSkeleton('learning');
        hideSkeleton('succession');
        hideSkeleton('competency');

        if (Array.isArray(training.labels) && training.labels.length > 0 && Array.isArray(training.values) && training.values.some(v => v > 0)) {
          if (trainingCtx && window.Chart) {
            showCanvas('trainingChart');
            const chart = new Chart(trainingCtx, {
              type: 'bar',
              data: {
                labels: training.labels,
                datasets: [{
                  label: 'Completed Programs',
                  data: training.values,
                  backgroundColor: primaryColor,
                  borderRadius: 4,
                  maxBarThickness: 32
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  },
                  tooltip: {
                    callbacks: {
                      label: ctx => ctx.parsed.y + ' completed'
                    }
                  }
                },
                scales: {
                  x: {
                    ticks: {
                      maxRotation: 45,
                      minRotation: 0
                    }
                  },
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Completed Programs'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'training');
            charts.training = chart;
          }
        } else {
          showEmptyMessage('trainingEmptyMessage');
        }

        if (Array.isArray(learning.labels) && learning.labels.length > 0 && (learning.pass || learning.fail)) {
          const pass = Array.isArray(learning.pass) ? learning.pass : [];
          const fail = Array.isArray(learning.fail) ? learning.fail : [];
          if (pass.some(v => v > 0) || fail.some(v => v > 0)) {
            if (learningCtx && window.Chart) {
              showCanvas('learningChart');
              const chart = new Chart(learningCtx, {
                type: 'bar',
                data: {
                  labels: learning.labels,
                  datasets: [{
                      label: 'Pass',
                      data: pass,
                      backgroundColor: successColor,
                      borderRadius: 4,
                      maxBarThickness: 28
                    },
                    {
                      label: 'Fail',
                      data: fail,
                      backgroundColor: dangerColor,
                      borderRadius: 4,
                      maxBarThickness: 28
                    }
                  ]
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
                        label: ctx => ctx.parsed.y + ' ' + ctx.dataset.label
                      }
                    }
                  },
                  scales: {
                    x: {
                      stacked: true,
                      ticks: {
                        maxRotation: 45,
                        minRotation: 0
                      }
                    },
                    y: {
                      stacked: true,
                      beginAtZero: true,
                      title: {
                        display: true,
                        text: 'Exam Attempts'
                      }
                    }
                  }
                }
              });
              attachDrilldown(chart, 'learning');
              charts.learning = chart;
            }
          } else {
            showEmptyMessage('learningEmptyMessage');
          }
        } else {
          showEmptyMessage('learningEmptyMessage');
        }

        if (Array.isArray(succession.labels) && succession.labels.length > 0 && Array.isArray(succession.values) && succession.values.some(v => v > 0)) {
          if (successionCtx && window.Chart) {
            showCanvas('successionChart');
            const chart = new Chart(successionCtx, {
              type: 'doughnut',
              data: {
                labels: succession.labels,
                datasets: [{
                  data: succession.values,
                  backgroundColor: ['#f97316', '#facc15', '#22c55e', '#0ea5e9', '#6366f1']
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
                        return `${ctx.label}: ${val} (${pct}%)`;
                      }
                    }
                  }
                },
                cutout: '60%'
              }
            });
            attachDrilldown(chart, 'succession');
            charts.succession = chart;
          }
        } else {
          showEmptyMessage('successionEmptyMessage');
        }

        if (Array.isArray(competency.labels) && competency.labels.length > 0 && Array.isArray(competency.values) && competency.values.some(v => v > 0)) {
          if (competencyCtx && window.Chart) {
            showCanvas('competencyChart');
            const chart = new Chart(competencyCtx, {
              type: 'bar',
              data: {
                labels: competency.labels,
                datasets: [{
                  label: 'Average Gap (%)',
                  data: competency.values,
                  backgroundColor: slateColor,
                  borderRadius: 4,
                  maxBarThickness: 28
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  },
                  tooltip: {
                    callbacks: {
                      label: ctx => ctx.parsed.y.toFixed(1) + '% gap'
                    }
                  }
                },
                scales: {
                  x: {
                    ticks: {
                      maxRotation: 45,
                      minRotation: 0
                    }
                  },
                  y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                      display: true,
                      text: 'Average Gap (%)'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'competency');
            charts.competency = chart;
          }
        } else {
          showEmptyMessage('competencyEmptyMessage');
        }
      }

      function renderTrainingRequests(data) {
        const analytics = data.trainingRequests || {};
        const total = analytics.total || 0;
        const onboarding = analytics.onboarding || {};
        const idp = analytics.idp || {};

        const summaryEl = document.getElementById('trainingRequestsSummary');
        if (summaryEl) {
          summaryEl.textContent = total > 0 ? `${total} requests in selected period` : 'No training requests in selected period';
        }

        const onboardingCountEl = document.getElementById('onboardingRequestsCount');
        const onboardingMetaEl = document.getElementById('onboardingRequestsMeta');
        const idpCountEl = document.getElementById('idpTrainingRequestsCount');
        const idpMetaEl = document.getElementById('idpTrainingRequestsMeta');
        const processingEl = document.getElementById('trainingProcessingTime');

        if (onboardingCountEl) onboardingCountEl.textContent = onboarding.count ?? 0;
        if (onboardingMetaEl) onboardingMetaEl.textContent = onboarding.approvalRate != null ? `${onboarding.approvalRate.toFixed(1)}% approval` : 'No approval data';
        if (idpCountEl) idpCountEl.textContent = idp.count ?? 0;
        if (idpMetaEl) idpMetaEl.textContent = idp.approvalRate != null ? `${idp.approvalRate.toFixed(1)}% approval` : 'No approval data';
        if (processingEl) processingEl.textContent = analytics.avgProcessingDays != null ? `${analytics.avgProcessingDays.toFixed(1)}d` : '0d';

        hideSkeleton('trainingRequests');
        hideSkeleton('trainingApproval');

        const trend = analytics.trend || {};
        if (Array.isArray(trend.labels) && trend.labels.length > 0 && ((trend.onboarding && trend.onboarding.some(v => v > 0)) || (trend.idp && trend.idp.some(v => v > 0)))) {
          const ctx = document.getElementById('trainingRequestsChart');
          if (ctx && window.Chart) {
            showCanvas('trainingRequestsChart');
            const chart = new Chart(ctx, {
              type: 'line',
              data: {
                labels: trend.labels.map(formatDateLabel),
                datasets: [{
                    label: 'Onboarding',
                    data: trend.onboarding || [],
                    borderColor: primaryColor,
                    backgroundColor: primaryColor,
                    tension: 0.3,
                    fill: false
                  },
                  {
                    label: 'IDP',
                    data: trend.idp || [],
                    borderColor: infoColor,
                    backgroundColor: infoColor,
                    tension: 0.3,
                    fill: false
                  }
                ]
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
                      text: 'Requests'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'trainingRequests');
            charts.trainingRequests = chart;
          }
        } else {
          showEmptyMessage('trainingRequestsEmptyMessage');
        }

        const approval = analytics.approval || {};
        if (Array.isArray(approval.labels) && approval.labels.length > 0 && Array.isArray(approval.values) && approval.values.some(v => v > 0)) {
          const ctx = document.getElementById('trainingApprovalChart');
          if (ctx && window.Chart) {
            showCanvas('trainingApprovalChart');
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: approval.labels,
                datasets: [{
                  label: 'Requests',
                  data: approval.values,
                  backgroundColor: [successColor, dangerColor, warningColor, slateColor]
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
                      text: 'Requests'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'trainingApproval');
            charts.trainingApproval = chart;
          }
        } else {
          showEmptyMessage('trainingApprovalEmptyMessage');
        }
      }

      function renderLearningModules(data) {
        const analytics = data.learningModules || {};
        const summaryEl = document.getElementById('learningModulesSummary');
        const cardsRoot = document.getElementById('learningModulesStatusCards');
        hideSkeleton('learningModulesStatus');
        hideSkeleton('learningModulesTrend');

        const statusCounts = analytics.statusCounts || {};
        const statusOrder = analytics.statusOrder || ['approved', 'rejected', 'for_compliance', 'posted', 'hold', 'pending'];
        const statusLabels = analytics.statusLabels || {
          approved: 'Approved',
          rejected: 'Rejected',
          for_compliance: 'For Compliance',
          posted: 'Posted',
          hold: 'On Hold',
          pending: 'Pending'
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
                  backgroundColor: ['#22c55e', '#ef4444', '#f97316', '#0ea5e9', '#eab308', '#6b7280']
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
              hold: '#eab308',
              pending: '#6b7280'
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

      function renderCompetencyProfiles(data) {
        const analytics = data.competencyProfiles || {};
        const counts = analytics.counts || {};
        const trend = analytics.trend || {};

        const summaryEl = document.getElementById('competencyProfilesSummary');
        if (summaryEl) {
          const total = Object.values(counts).reduce((sum, v) => sum + (v || 0), 0);
          summaryEl.textContent = total > 0 ? `${total} employees with active development statuses` : 'No employees with tracked development statuses';
        }

        const idMap = {
          ongoing_training: 'statusOngoingTraining',
          forwarded_idp: 'statusForwardedIdp',
          training_requested: 'statusTrainingRequested',
          idp_created: 'statusIdpCreated'
        };
        Object.keys(idMap).forEach(key => {
          const el = document.getElementById(idMap[key]);
          if (el) el.textContent = counts[key] ?? 0;
        });

        hideSkeleton('competencyStatusTrend');
        if (Array.isArray(trend.labels) && trend.labels.length > 0 && Object.values(trend.series || {}).some(arr => Array.isArray(arr) && arr.some(v => v > 0))) {
          const ctx = document.getElementById('competencyStatusTrendChart');
          if (ctx && window.Chart) {
            showCanvas('competencyStatusTrendChart');
            const series = trend.series || {};
            const colors = {
              ongoing_training: '#ef4444',
              forwarded_idp: '#f97316',
              training_requested: '#0ea5e9',
              idp_created: '#22c55e'
            };
            const labelsMap = {
              ongoing_training: 'On-going Training',
              forwarded_idp: 'Forwarded for IDP',
              training_requested: 'Training Requested',
              idp_created: 'IDP Created'
            };
            const datasets = Object.keys(series).map(key => ({
              label: labelsMap[key] || key,
              data: series[key] || [],
              borderColor: colors[key] || primaryColor,
              backgroundColor: colors[key] || primaryColor,
              tension: 0.3,
              fill: false
            }));
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
                      text: 'Employees'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'competencyStatusTrend');
            charts.competencyStatusTrend = chart;
          }
        } else {
          showEmptyMessage('competencyStatusTrendEmptyMessage');
        }
      }

      function renderIdp(data) {
        const analytics = data.idp || {};
        const summaryEl = document.getElementById('idpSummary');
        const statusCardsRoot = document.getElementById('idpStatusCards');
        hideSkeleton('idpStatus');
        hideSkeleton('idpDepartment');

        const statusCounts = analytics.statusCounts || {};
        const statusOrder = analytics.statusOrder || [];
        const total = Object.values(statusCounts).reduce((sum, v) => sum + (v || 0), 0);
        if (summaryEl) summaryEl.textContent = total > 0 ? `${total} IDPs in selected period` : 'No IDPs in selected period';

        if (statusCardsRoot) {
          statusCardsRoot.innerHTML = '';
          statusOrder.forEach(key => {
            const count = statusCounts[key] ?? null;
            if (count === null) return;
            const label = analytics.statusLabels && analytics.statusLabels[key] ? analytics.statusLabels[key] : key;
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
              window.location.href = 'COMPETENCY/criticalgaps/idp_repository.php?source=dashboard&status=' + encodeURIComponent(key);
            });
            statusCardsRoot.appendChild(card);
          });
        }

        const statusChart = analytics.statusChart || {};
        if (Array.isArray(statusChart.labels) && statusChart.labels.length > 0 && Array.isArray(statusChart.values) && statusChart.values.some(v => v > 0)) {
          const ctx = document.getElementById('idpStatusChart');
          if (ctx && window.Chart) {
            showCanvas('idpStatusChart');
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: statusChart.labels,
                datasets: [{
                  label: 'IDPs',
                  data: statusChart.values,
                  backgroundColor: [primaryColor, successColor, warningColor, dangerColor, slateColor]
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
                      text: 'IDPs'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'idpStatus');
            charts.idpStatus = chart;
          }
        } else {
          showEmptyMessage('idpStatusEmptyMessage');
        }

        const byDepartment = analytics.byDepartment || {};
        if (Array.isArray(byDepartment.labels) && byDepartment.labels.length > 0 && Array.isArray(byDepartment.values) && byDepartment.values.some(v => v > 0)) {
          const ctx = document.getElementById('idpDepartmentChart');
          if (ctx && window.Chart) {
            showCanvas('idpDepartmentChart');
            const chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: byDepartment.labels,
                datasets: [{
                  label: 'IDPs',
                  data: byDepartment.values,
                  backgroundColor: primaryColor
                }]
              },
              options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  x: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'IDPs'
                    }
                  }
                }
              }
            });
            attachDrilldown(chart, 'idpDepartment');
            charts.idpDepartment = chart;
          }
        } else {
          showEmptyMessage('idpDepartmentEmptyMessage');
        }
      }

      let currentFilters = {
        department: 'all',
        range: '30d'
      };

      function loadAnalytics() {
        const url = buildQuery({
          department: currentFilters.department,
          range: currentFilters.range
        });
        fetch(url, {
            headers: {
              'Accept': 'application/json'
            }
          })
          .then(resp => resp.json())
          .then(payload => {
            if (!payload || !payload.success) return;
            const data = payload.data || {};
            renderOverviewCharts(data);
            renderTrainingRequests(data);
            renderLearningModules(data);
            renderCompetencyProfiles(data);
            renderIdp(data);
          })
          .catch(err => {
            console.error('Analytics load error', err);
          });
      }

      const deptSelect = document.getElementById('analyticsDepartmentFilter');
      const rangeSelect = document.getElementById('analyticsRangeFilter');
      if (deptSelect) {
        deptSelect.addEventListener('change', () => {
          currentFilters.department = deptSelect.value || 'all';
          loadAnalytics();
        });
      }
      if (rangeSelect) {
        rangeSelect.addEventListener('change', () => {
          currentFilters.range = rangeSelect.value || '30d';
          loadAnalytics();
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