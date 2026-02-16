<?php

require_once __DIR__ . '/db.php';

$tableHasColumn = function (mysqli $conn, string $table, string $column): bool {
  $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
  $stmt->bind_param('ss', $table, $column);
  $stmt->execute();
  return (bool)$stmt->get_result()->fetch_row();
};

$ensurePostSchema = function (mysqli $conn) use ($tableHasColumn): void {
  try {
    if (!$tableHasColumn($conn, 'employees', 'role')) {
      $conn->query("ALTER TABLE employees ADD COLUMN role VARCHAR(150) NULL");
    }
  } catch (Throwable $e) {
  }

  try {
    $conn->query("CREATE TABLE IF NOT EXISTS training_posts (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_training_post (program_id, submission_no), INDEX idx_tp_program (program_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
  }

  try {
    $conn->query("CREATE TABLE IF NOT EXISTS training_post_assignments (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, employee_id INT NOT NULL, assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_tpa (program_id, submission_no, employee_id), INDEX idx_tpa_program (program_id), INDEX idx_tpa_employee (employee_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
  }
};

$ensurePostSchema($conn);

$deptMap = [
  1 => 'Hotel',
  2 => 'Restaurant',
  3 => 'HR',
  4 => 'Administrative',
  5 => 'Financial',
  6 => 'Logistics 1',
  7 => 'Logistics 2'
];

if (isset($_GET['action']) && $_GET['action'] === 'get_program') {
  header('Content-Type: application/json; charset=utf-8');
  $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
  if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing program_id']);
    exit;
  }

  try {
    $stmt = $conn->prepare("SELECT id, training_title, training_type, training_mode, target_audience, department_id, target_role, participants_needed, start_datetime, end_datetime, status, category, description, submission_no FROM training_programs WHERE id = ?");
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    if (!$program) {
      echo json_encode(['success' => false, 'message' => 'Program not found']);
      exit;
    }
    echo json_encode(['success' => true, 'program' => $program]);
    exit;
  } catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load program']);
    exit;
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_assignments') {
  header('Content-Type: application/json; charset=utf-8');
  $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
  $submissionNo = isset($_GET['submission_no']) ? (int)$_GET['submission_no'] : 1;
  if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing program_id']);
    exit;
  }
  if ($submissionNo <= 0) $submissionNo = 1;

  $ids = [];
  try {
    $stmt = $conn->prepare("SELECT employee_id FROM training_post_assignments WHERE program_id = ? AND submission_no = ?");
    $stmt->bind_param('ii', $programId, $submissionNo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $ids[] = (int)($row['employee_id'] ?? 0);
    }
  } catch (Throwable $e) {
    $ids = [];
  }

  echo json_encode(['success' => true, 'employee_ids' => $ids]);
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_employees') {
  header('Content-Type: application/json; charset=utf-8');
  $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
  if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing program_id']);
    exit;
  }

  $program = null;
  try {
    $stmt = $conn->prepare("SELECT target_audience, department_id, target_role FROM training_programs WHERE id = ?");
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
  } catch (Throwable $e) {
    $program = null;
  }

  if (!$program) {
    echo json_encode(['success' => false, 'message' => 'Program not found']);
    exit;
  }

  $targetAudience = (string)($program['target_audience'] ?? '');
  $deptId = isset($program['department_id']) ? (int)$program['department_id'] : 0;
  $targetRole = trim((string)($program['target_role'] ?? ''));

  $employees = [];
  try {
    $sql = "SELECT id, employee_no, first_name, last_name, department, role FROM employees WHERE 1=1";
    $params = [];
    $types = '';

    $aud = trim($targetAudience);

    if ($deptId > 0) {
      $deptMapLocal = [
        1 => 'Front Office / Reception',
        2 => 'Housekeeping',
        3 => 'Food & Beverage (F&B)',
        4 => 'Kitchen / Culinary',
        5 => 'Sales & Marketing',
        6 => 'Human Resources (HR)',
        7 => 'Finance / Accounting',
        8 => 'Engineering / Maintenance',
        9 => 'Security'
      ];
      $deptName = $deptMapLocal[$deptId] ?? '';

      $legacyDept = '';
      if (in_array($deptId, [1, 2], true)) $legacyDept = 'Hotel';
      elseif (in_array($deptId, [3, 4], true)) $legacyDept = 'Restaurant';
      elseif ($deptId === 6) $legacyDept = 'HR';
      elseif ($deptId === 7) $legacyDept = 'Financial';
      elseif ($deptId === 5) $legacyDept = 'Administrative';

      $deptCandidates = [];
      foreach ([$deptName, $legacyDept] as $v) {
        $v = trim((string)$v);
        if ($v !== '' && !in_array($v, $deptCandidates, true)) $deptCandidates[] = $v;
      }

      $hasDeptIdCol = false;
      try {
        $hasDeptIdCol = $tableHasColumn($conn, 'employees', 'department_id');
      } catch (Throwable $e) {
        $hasDeptIdCol = false;
      }

      if ($hasDeptIdCol && !empty($deptCandidates)) {
        $placeholders = implode(',', array_fill(0, count($deptCandidates), '?'));
        $sql .= " AND (department_id = ? OR department IN (" . $placeholders . "))";
        $types .= 'i' . str_repeat('s', count($deptCandidates));
        $params[] = $deptId;
        foreach ($deptCandidates as $d) $params[] = $d;
      } elseif ($hasDeptIdCol) {
        $sql .= " AND department_id = ?";
        $types .= 'i';
        $params[] = $deptId;
      } elseif (!empty($deptCandidates)) {
        $placeholders = implode(',', array_fill(0, count($deptCandidates), '?'));
        $sql .= " AND department IN (" . $placeholders . ")";
        $types .= str_repeat('s', count($deptCandidates));
        foreach ($deptCandidates as $d) $params[] = $d;
      }
    }

    if ($aud === 'By Department' && $targetRole !== '') {
      $sql .= " AND role = ?";
      $types .= 's';
      $params[] = $targetRole;
    } elseif ($aud === 'Managers') {
      $sql .= " AND (role LIKE ? OR role LIKE ?)";
      $types .= 'ss';
      $params[] = '%Manager%';
      $params[] = '%Supervisor%';
    } elseif ($aud === 'Trainee') {
      if ($tableHasColumn($conn, 'employees', 'employment_status')) {
        $sql .= " AND employment_status = ?";
        $types .= 's';
        $params[] = 'New Hire';
      }
    } elseif ($aud === 'New Hires') {
      if ($tableHasColumn($conn, 'employees', 'employment_status')) {
        $sql .= " AND employment_status = ?";
        $types .= 's';
        $params[] = 'New Hire';
      }
    } elseif ($aud === 'Specific Employee') {
      $eid = (int)$targetRole;
      if ($eid > 0) {
        $sql .= " AND id = ?";
        $types .= 'i';
        $params[] = $eid;
      }
    }

    $sql .= " ORDER BY last_name, first_name";
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
      $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $employees[] = $row;
    }
  } catch (Throwable $e) {
    $employees = [];
  }

  echo json_encode(['success' => true, 'employees' => $employees]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_assignments') {
  header('Content-Type: application/json; charset=utf-8');
  $programId = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
  $submissionNo = isset($_POST['submission_no']) ? (int)$_POST['submission_no'] : 1;
  if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing program_id']);
    exit;
  }
  if ($submissionNo <= 0) $submissionNo = 1;

  $employeeIds = [];
  if (isset($_POST['employee_ids_json'])) {
    $decoded = json_decode((string)$_POST['employee_ids_json'], true);
    if (is_array($decoded)) {
      foreach ($decoded as $id) {
        $eid = (int)$id;
        if ($eid > 0) $employeeIds[] = $eid;
      }
    }
  }

  try {
    $conn->begin_transaction();

    $stmtPost = $conn->prepare("INSERT IGNORE INTO training_posts (program_id, submission_no) VALUES (?, ?)");
    $stmtPost->bind_param('ii', $programId, $submissionNo);
    $stmtPost->execute();

    $stmtDel = $conn->prepare("DELETE FROM training_post_assignments WHERE program_id = ? AND submission_no = ?");
    $stmtDel->bind_param('ii', $programId, $submissionNo);
    $stmtDel->execute();

    if (!empty($employeeIds)) {
      $stmtIns = $conn->prepare("INSERT IGNORE INTO training_post_assignments (program_id, submission_no, employee_id) VALUES (?, ?, ?)");
      foreach ($employeeIds as $eid) {
        $stmtIns->bind_param('iii', $programId, $submissionNo, $eid);
        $stmtIns->execute();
      }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
    exit;
  } catch (Throwable $e) {
    try {
      $conn->rollback();
    } catch (Throwable $e2) {
    }
    echo json_encode(['success' => false, 'message' => 'Failed to save assignments']);
    exit;
  }
}

$tableExists = false;
try {
  $stmtCheck = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'training_posts' LIMIT 1");
  $stmtCheck->execute();
  $tableExists = (bool)$stmtCheck->get_result()->fetch_row();
} catch (Throwable $e) {
  $tableExists = false;
}

$rows = [];
$hasRequestedBy = false;
if ($tableExists) {
  try {
    $hasRequestedBy = $tableHasColumn($conn, 'training_programs', 'requested_by');
    $sql = "SELECT tp.program_id, tp.submission_no, tp.posted_at, p.training_title, p.training_type, p.target_audience, p.start_datetime, p.end_datetime";
    if ($hasRequestedBy) {
      $sql .= ", p.requested_by";
    }
    $sql .= " FROM training_posts tp
                JOIN training_programs p ON p.id = tp.program_id
                ORDER BY tp.posted_at DESC";
    $res = $conn->query($sql);
    if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);
    if (!$hasRequestedBy) {
      foreach ($rows as &$rr) {
        $rr['requested_by'] = '';
      }
      unset($rr);
    }
  } catch (Throwable $e) {
    $rows = [];
  }
}

$ptTotalTrainings = 0;
$ptCompletedTrainings = 0;
$ptIdpTrainings = 0;

if ($tableExists) {
  $ptTotalTrainings = count($rows);

  try {
    $res = $conn->query("SELECT COUNT(*) AS c FROM training_posts tp JOIN training_programs p ON p.id = tp.program_id WHERE p.status = 'Completed'");
    $row = $res ? $res->fetch_assoc() : null;
    $ptCompletedTrainings = $row ? (int)($row['c'] ?? 0) : 0;
  } catch (Throwable $e) {
    $ptCompletedTrainings = 0;
  }

  try {
    if ($tableHasColumn($conn, 'training_programs', 'requested_by')) {
      $res = $conn->query("SELECT COUNT(*) AS c FROM training_posts tp JOIN training_programs p ON p.id = tp.program_id WHERE p.requested_by = 'IDP'");
      $row = $res ? $res->fetch_assoc() : null;
      $ptIdpTrainings = $row ? (int)($row['c'] ?? 0) : 0;
    }
  } catch (Throwable $e) {
    $ptIdpTrainings = 0;
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'list_posts') {
  header('Content-Type: application/json; charset=utf-8');
  if (!$tableExists) {
    echo json_encode(['success' => true, 'posts' => []]);
    exit;
  }
  try {
    $hasRequestedByList = $tableHasColumn($conn, 'training_programs', 'requested_by');
    $sql = "SELECT tp.program_id, tp.submission_no, tp.posted_at, p.training_title, p.training_type, p.target_audience, p.start_datetime, p.end_datetime";
    if ($hasRequestedByList) {
      $sql .= ", p.requested_by";
    }
    $sql .= " FROM training_posts tp
                JOIN training_programs p ON p.id = tp.program_id";

    $filter = isset($_GET['filter']) ? (string)$_GET['filter'] : '';
    if ($hasRequestedByList && $filter === 'idp') {
      $sql .= " WHERE p.requested_by = 'IDP'";
    } elseif ($hasRequestedByList && $filter === 'department') {
      $sql .= " WHERE (p.requested_by IS NULL OR p.requested_by <> 'IDP')";
    }

    $sql .= " ORDER BY tp.posted_at DESC";
    $res = $conn->query($sql);
    $posts = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    if (!$hasRequestedByList) {
      foreach ($posts as &$pr) {
        $pr['requested_by'] = '';
      }
      unset($pr);
    }
    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
  } catch (Throwable $e) {
    echo json_encode(['success' => false, 'posts' => [], 'message' => 'Failed to load posted trainings']);
    exit;
  }
}

require('../../partials/header.php');
?>

<script>
  (function() {
    if (!window.Swal || window.__SWAL_DAISY_PATCHED__) return;
    window.__SWAL_DAISY_PATCHED__ = true;
    const orig = window.Swal.fire.bind(window.Swal);
    window.Swal.fire = function(opts) {
      const inOpts = opts || {};
      const inCustom = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
      const customClass = {
        popup: 'bg-base-100 text-base-content rounded-box',
        title: 'text-base-content',
        htmlContainer: 'text-base-content',
        actions: 'flex gap-2',
        confirmButton: 'btn hr2-primary-btn',
        cancelButton: 'btn btn-ghost',
        denyButton: 'btn btn-ghost',
        ...(inCustom || {})
      };
      return orig({
        returnFocus: false,
        buttonsStyling: false,
        ...inOpts,
        customClass
      });
    };
  })();
</script>
<style>
  .card-table thead {
    display: none;
  }

  .card-table tbody {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 0.75rem;
  }

  @media (min-width: 768px) {
    .card-table tbody {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (min-width: 1280px) {
    .card-table tbody {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  .card-table tbody tr {
    display: block;
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 0.75rem;
    padding: 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .card-table tbody tr.card-empty {
    grid-column: 1 / -1;
    text-align: center;
    color: #6b7280;
    padding: 2.25rem 1rem;
  }

  .card-table tbody tr.card-empty td {
    display: block;
  }

  .card-table td {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.35rem 0;
    border: 0;
    background: transparent;
    white-space: normal;
  }

  .card-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #374151;
    flex: 0 0 auto;
  }

  .card-table td[data-label="Action"] {
    display: block;
    padding-top: 0.75rem;
    margin-top: 0.5rem;
    border-top: 1px solid #eef2f7;
  }

  .card-table td[data-label="Action"]::before {
    display: none;
  }
</style>
</head>

<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php';
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
      <main class="container mx-auto px-4 py-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Posted Trainings</h1>
            <p class="text-sm text-gray-500">List of training programs that have been posted for assignment</p>
          </div>
          <div class="flex gap-2">

          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in">
          <div class="hr2-summary-card rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-500">Total Trainings</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo (int)$ptTotalTrainings; ?></div>
              </div>
              <div class="p-3 bg-blue-100 rounded-full">
                <i data-lucide="layers" class="h-6 w-6 text-blue-600"></i>
              </div>
            </div>
          </div>

          <div class="hr2-summary-card rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-500">Completed Trainings</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo (int)$ptCompletedTrainings; ?></div>
              </div>
              <div class="p-3 bg-purple-100 rounded-full">
                <i data-lucide="check-circle" class="h-6 w-6 text-purple-600"></i>
              </div>
            </div>
          </div>

          <div class="hr2-summary-card rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-500">IDP trainings</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo (int)$ptIdpTrainings; ?></div>
              </div>
              <div class="p-3 bg-yellow-100 rounded-full">
                <i data-lucide="target" class="h-6 w-6 text-yellow-600"></i>
              </div>
            </div>
          </div>
        </div>

        <?php if (!$tableExists): ?>
          <div class="bg-white rounded-xl shadow-md p-6">
            <div class="text-gray-700 font-semibold">No posts table</div>
            <div class="text-gray-500 mt-1">The table <code class="bg-gray-100 px-1 rounded">training_posts</code> does not exist yet. It will be created automatically when you post a training.</div>
          </div>
        <?php else: ?>
          <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-4 border-b border-gray-100">
              <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-gray-700">Posted Trainings</h2>
                <div class="inline-flex gap-2 rounded-lg bg-gray-100 p-1">
                  <button type="button" id="pt-tab-idp" class="btn btn-xs btn-active">IDP Programs</button>
                  <button type="button" id="pt-tab-dept" class="btn btn-xs btn-ghost">Department Program</button>
                </div>
              </div>
            </div>
            <div>
              <table class="table card-table">
                <thead>
                  <tr>
                    <th>Program</th>
                    <th>Type</th>
                    <th>Audience</th>
                    <th>Schedule</th>
                    <th>Posted At</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)) : ?>
                    <tr class="card-empty">
                      <td colspan="6">No posted trainings found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                      <?php
                      $kind = 'department';
                      $reqBy = isset($r['requested_by']) ? (string)$r['requested_by'] : '';
                      if (strcasecmp($reqBy, 'IDP') === 0) $kind = 'idp';
                      ?>
                      <tr data-program-id="<?php echo (int)($r['program_id'] ?? 0); ?>" data-submission-no="<?php echo (int)($r['submission_no'] ?? 1); ?>" data-program-kind="<?php echo htmlspecialchars($kind); ?>">
                        <td data-label="Program"><?php echo htmlspecialchars((string)($r['training_title'] ?? '')); ?></td>
                        <td data-label="Type"><?php echo htmlspecialchars((string)($r['training_type'] ?? '')); ?></td>
                        <td data-label="Audience"><?php echo htmlspecialchars((string)($r['target_audience'] ?? '')); ?></td>
                        <td data-label="Schedule"><?php echo htmlspecialchars((string)($r['start_datetime'] ?? '')); ?> to <?php echo htmlspecialchars((string)($r['end_datetime'] ?? '')); ?></td>
                        <td data-label="Posted At"><?php echo htmlspecialchars((string)($r['posted_at'] ?? '')); ?></td>
                        <td data-label="Action">
                          <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm hr2-outline-btn" data-action="view">View</button>
                            <?php if ($kind !== 'idp'): ?>
                              <button type="button" class="btn btn-sm hr2-primary-btn" data-action="assign">Assign</button>
                            <?php endif; ?>
                            <a class="btn btn-sm hr2-primary-btn" href="evaluatio.php?program_id=<?php echo (int)($r['program_id'] ?? 0); ?>&submission_no=<?php echo (int)($r['submission_no'] ?? 1); ?>">Evaluate</a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <dialog id="posted-view-modal" class="modal">
          <div class="modal-box w-11/12 max-w-3xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 id="posted-view-title" class="text-xl font-semibold text-gray-800">Training</h2>
                <div class="text-sm text-gray-600 mt-1" id="posted-view-subtitle"></div>
              </div>
              <button type="button" class="btn btn-ghost btn-sm" id="posted-view-close">✕</button>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="font-semibold text-gray-700">Status</div>
                <div class="badge badge-outline" id="posted-view-status"></div>
              </div>
              <div>
                <div class="font-semibold text-gray-700">Audience</div>
                <div class="text-gray-700" id="posted-view-audience"></div>
              </div>
              <div>
                <div class="font-semibold text-gray-700">Type</div>
                <div class="text-gray-700" id="posted-view-type"></div>
              </div>
              <div>
                <div class="font-semibold text-gray-700">Schedule</div>
                <div class="text-gray-700" id="posted-view-schedule"></div>
              </div>
            </div>

            <div class="mt-4">
              <div class="font-semibold text-gray-700 mb-1">Description</div>
              <div class="text-gray-700 whitespace-pre-line" id="posted-view-desc"></div>
            </div>
          </div>
          <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <dialog id="posted-assign-modal" class="modal">
          <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 id="posted-assign-title" class="text-xl font-semibold text-gray-800">Assign Employees</h2>
                <div class="text-sm text-gray-600 mt-1" id="posted-assign-subtitle"></div>
              </div>
              <button type="button" class="btn btn-ghost btn-sm" id="posted-assign-close">✕</button>
            </div>

            <div class="flex items-center justify-between gap-3 mt-4">
              <div class="font-semibold text-gray-700">Employees</div>
            </div>

            <div class="overflow-x-auto mt-3">
              <table class="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Employee No</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="posted-assign-body">
                  <tr>
                    <td colspan="5" class="text-center text-gray-500">Loading...</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" id="posted-assign-cancel">Cancel</button>
            </div>
          </div>
          <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

      </main>
    </div>
  </div>

  <script>
    (function() {
      const swalBase = {
        buttonsStyling: false,
        customClass: {
          popup: 'bg-base-100 text-base-content rounded-box',
          title: 'text-base-content',
          htmlContainer: 'text-base-content',
          confirmButton: 'btn hr2-primary-btn',
          cancelButton: 'btn btn-ghost'
        }
      };

      const viewModal = document.getElementById('posted-view-modal');
      const viewClose = document.getElementById('posted-view-close');

      const assignModal = document.getElementById('posted-assign-modal');
      const assignClose = document.getElementById('posted-assign-close');
      const assignCancel = document.getElementById('posted-assign-cancel');
      const assignBody = document.getElementById('posted-assign-body');
      const ptTabIdp = document.getElementById('pt-tab-idp');
      const ptTabDept = document.getElementById('pt-tab-dept');

      let activeProgramId = null;
      let activeSubmissionNo = 1;

      const apiGet = async (params) => {
        const u = new URL(window.location.href);
        Object.keys(params || {}).forEach((k) => u.searchParams.set(k, params[k]));
        const res = await fetch(u.toString(), {
          credentials: 'same-origin'
        });
        return await res.json();
      };

      const apiPost = async (body) => {
        const fd = new FormData();
        Object.keys(body || {}).forEach((k) => fd.append(k, body[k]));
        const res = await fetch(window.location.href, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });
        return await res.json();
      };

      const setText = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.textContent = txt;
      };

      const closeDialog = (dlg) => {
        if (dlg) dlg.close();
      };
      if (viewClose) viewClose.addEventListener('click', () => closeDialog(viewModal));
      if (assignClose) assignClose.addEventListener('click', () => closeDialog(assignModal));
      if (assignCancel) assignCancel.addEventListener('click', () => closeDialog(assignModal));

      const openView = async (programId) => {
        try {
          const r = await apiGet({
            action: 'get_program',
            program_id: String(programId)
          });
          if (!r || !r.success || !r.program) {
            if (window.Swal) await Swal.fire({
              ...swalBase,
              icon: 'error',
              title: 'Failed',
              text: (r && r.message) ? r.message : 'Unable to load training.'
            });
            return;
          }
          const p = r.program;
          setText('posted-view-title', String(p.training_title || 'Training'));
          setText('posted-view-subtitle', String(p.category || ''));
          setText('posted-view-status', String(p.status || ''));
          setText('posted-view-audience', String(p.target_audience || ''));
          setText('posted-view-type', String(p.training_type || ''));
          setText('posted-view-schedule', `${String(p.start_datetime || '')} to ${String(p.end_datetime || '')}`);
          setText('posted-view-desc', String(p.description || ''));
          if (viewModal) viewModal.showModal();
        } catch (_) {
          if (window.Swal) await Swal.fire({
            ...swalBase,
            icon: 'error',
            title: 'Failed',
            text: 'Unexpected error.'
          });
        }
      };

      const renderEmployees = (employees, assignedIds) => {
        if (!assignBody) return;
        const assigned = new Set((assignedIds || []).map((n) => String(n)));
        if (!employees || !employees.length) {
          assignBody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500">No employees found for this filter.</td></tr>';
          return;
        }

        assignBody.innerHTML = employees.map((e) => {
          const id = String(e.id || '');
          const name = `${String(e.last_name || '')}, ${String(e.first_name || '')}`;
          const already = assigned.has(id);
          const btnLabel = already ? 'Scheduled' : 'Assign';
          const btnAttrs = already ? 'disabled data-scheduled="1"' : '';
          return `
          <tr>
            <td>${name.replace(/</g, '&lt;')}</td>
            <td>${String(e.employee_no || '').replace(/</g, '&lt;')}</td>
            <td>${String(e.department || '').replace(/</g, '&lt;')}</td>
            <td>${String(e.role || '').replace(/</g, '&lt;')}</td>
            <td>
              <button type="button" class="btn btn-xs hr2-primary-btn posted-assign-btn" data-emp-id="${id}" ${btnAttrs}>${btnLabel}</button>
            </td>
          </tr>
        `;
        }).join('');
      };

      const openAssign = async (programId, submissionNo) => {
        activeProgramId = String(programId);
        activeSubmissionNo = parseInt(String(submissionNo || '1'), 10);
        if (!Number.isFinite(activeSubmissionNo) || activeSubmissionNo <= 0) activeSubmissionNo = 1;

        if (assignBody) assignBody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500">Loading...</td></tr>';
        try {
          const [progRes, empRes, asgRes] = await Promise.all([
            apiGet({
              action: 'get_program',
              program_id: String(programId)
            }),
            apiGet({
              action: 'get_employees',
              program_id: String(programId)
            }),
            apiGet({
              action: 'get_assignments',
              program_id: String(programId),
              submission_no: String(activeSubmissionNo)
            })
          ]);

          if (!progRes || !progRes.success || !progRes.program) {
            if (window.Swal) await Swal.fire({
              ...swalBase,
              icon: 'error',
              title: 'Failed',
              text: (progRes && progRes.message) ? progRes.message : 'Unable to load training.'
            });
            return;
          }

          setText('posted-assign-title', `Assign Employees: ${String(progRes.program.training_title || '')}`);
          setText('posted-assign-subtitle', `Audience: ${String(progRes.program.target_audience || '')}`);

          const employees = (empRes && empRes.success) ? (empRes.employees || []) : [];
          const assignedIds = (asgRes && asgRes.success) ? (asgRes.employee_ids || []) : [];
          renderEmployees(employees, assignedIds);

          if (assignModal) assignModal.showModal();
        } catch (_) {
          if (window.Swal) await Swal.fire({
            ...swalBase,
            icon: 'error',
            title: 'Failed',
            text: 'Unexpected error.'
          });
        }
      };

      const assignOneEmployee = async (empId, btn) => {
        if (!activeProgramId) return;
        const pid = String(activeProgramId);
        const subNo = String(activeSubmissionNo);

        try {
          const asgRes = await apiGet({
            action: 'get_assignments',
            program_id: pid,
            submission_no: subNo
          });
          let ids = (asgRes && asgRes.success && Array.isArray(asgRes.employee_ids)) ? asgRes.employee_ids.slice() : [];
          const eid = parseInt(String(empId || '0'), 10);
          if (Number.isFinite(eid) && eid > 0 && !ids.some((n) => parseInt(String(n), 10) === eid)) {
            ids.push(eid);
          }

          const r = await apiPost({
            action: 'save_assignments',
            program_id: pid,
            submission_no: subNo,
            employee_ids_json: JSON.stringify(ids)
          });
          if (!r || !r.success) {
            if (window.Swal) await Swal.fire({
              ...swalBase,
              icon: 'error',
              title: 'Failed',
              text: (r && r.message) ? r.message : 'Unable to save.'
            });
            return;
          }

          if (btn) {
            btn.textContent = 'Scheduled';
            btn.disabled = true;
            btn.setAttribute('data-scheduled', '1');
          }

          if (window.Swal) await Swal.fire({
            ...swalBase,
            icon: 'success',
            title: 'Scheduled',
            timer: 800,
            showConfirmButton: false
          });
        } catch (_) {
          if (window.Swal) await Swal.fire({
            ...swalBase,
            icon: 'error',
            title: 'Failed',
            text: 'Unexpected error.'
          });
        }
      };

      const setPtTab = (tab) => {
        const isIdp = tab === 'idp';
        if (ptTabIdp) {
          if (isIdp) {
            ptTabIdp.classList.add('btn-active');
            ptTabIdp.classList.remove('btn-ghost');
          } else {
            ptTabIdp.classList.remove('btn-active');
            ptTabIdp.classList.add('btn-ghost');
          }
        }
        if (ptTabDept) {
          if (!isIdp) {
            ptTabDept.classList.add('btn-active');
            ptTabDept.classList.remove('btn-ghost');
          } else {
            ptTabDept.classList.remove('btn-active');
            ptTabDept.classList.add('btn-ghost');
          }
        }
        document.querySelectorAll('table.card-table tbody tr[data-program-kind]').forEach((tr) => {
          const kind = String(tr.getAttribute('data-program-kind') || 'department').toLowerCase();
          const shouldShow = isIdp ? kind === 'idp' : kind === 'department';
          tr.classList.toggle('hidden', !shouldShow);
        });
      };

      if (ptTabIdp) ptTabIdp.addEventListener('click', (e) => {
        e.preventDefault();
        setPtTab('idp');
      });
      if (ptTabDept) ptTabDept.addEventListener('click', (e) => {
        e.preventDefault();
        setPtTab('department');
      });
      setPtTab('idp');

      document.addEventListener('click', (e) => {
        const t = e && e.target;
        if (!t || typeof t.closest !== 'function') return;

        const rowAssignBtn = t.closest('button.posted-assign-btn');
        if (rowAssignBtn) {
          const empId = rowAssignBtn.getAttribute('data-emp-id') || '';
          if (!rowAssignBtn.disabled) {
            assignOneEmployee(empId, rowAssignBtn);
          }
          return;
        }

        const btn = t.closest('button[data-action]');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;
        const programId = tr.getAttribute('data-program-id');
        const submissionNo = tr.getAttribute('data-submission-no') || '1';
        if (!programId) return;
        const action = btn.getAttribute('data-action');

        if (action === 'view') openView(programId);
        if (action === 'assign') openAssign(programId, submissionNo);
      });
    })();
  </script>
  <?php require('../../partials/footer.php') ?>