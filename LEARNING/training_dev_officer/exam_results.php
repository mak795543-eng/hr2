<?php

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

// Function to check if column exists in table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return $result && $result->num_rows > 0;
}

function bindParams($stmt, $types, &$params) {
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = &$params[$k];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Create exam_results table if it doesn't exist
if (!tableExists($conn, 'exam_results')) {
    $createTableSQL = "
        CREATE TABLE exam_results (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id VARCHAR(50) NOT NULL,
            exam_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            total_questions INT NOT NULL,
            passed BOOLEAN NOT NULL DEFAULT FALSE,
            time_taken INT NOT NULL COMMENT 'Time taken in seconds',
            completed_at DATETIME NOT NULL,
            attempt_number INT DEFAULT 1,
            taker_type ENUM('applicant','employee') DEFAULT 'employee',
            taker_name VARCHAR(255) DEFAULT NULL,
            taker_department VARCHAR(100) DEFAULT NULL,
            taker_role VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_employee_exam (employee_id, exam_id),
            INDEX idx_exam_id (exam_id)
        )
    ";
    
    if (!$conn->query($createTableSQL)) {
        error_log("Error creating exam_results table: " . $conn->error);
    }
}

if (!columnExists($conn, 'exam_results', 'taker_type')) {
    $conn->query("ALTER TABLE exam_results ADD COLUMN taker_type ENUM('applicant','employee') DEFAULT 'employee'");
}

if (!columnExists($conn, 'exam_results', 'taker_name')) {
    $conn->query("ALTER TABLE exam_results ADD COLUMN taker_name VARCHAR(255) DEFAULT NULL");
}

if (!columnExists($conn, 'exam_results', 'taker_department')) {
    $conn->query("ALTER TABLE exam_results ADD COLUMN taker_department VARCHAR(100) DEFAULT NULL");
}

if (!columnExists($conn, 'exam_results', 'taker_role')) {
    $conn->query("ALTER TABLE exam_results ADD COLUMN taker_role VARCHAR(255) DEFAULT NULL");
}

// Create exam_result_answers table if it doesn't exist
if (!tableExists($conn, 'exam_result_answers')) {
    $createAnswersSQL = "
        CREATE TABLE exam_result_answers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            result_id INT NOT NULL,
            question_id INT NOT NULL,
            question_number INT,
            question_type VARCHAR(50),
            user_answer TEXT,
            correct_answer TEXT,
            is_correct TINYINT(1) DEFAULT 0,
            points_possible INT DEFAULT 0,
            points_earned INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_result (result_id),
            INDEX idx_question (question_id),
            UNIQUE KEY uniq_result_question (result_id, question_id)
        )
    ";

    $conn->query($createAnswersSQL);
}

// Get selected department from filter
$selected_department = $_GET['department'] ?? 'all';
$exam_id = $_GET['exam_id'] ?? null;
$detail_employee_id = isset($_GET['detail_employee_id']) ? (string)$_GET['detail_employee_id'] : '';
$detail_attempt = isset($_GET['detail_attempt']) ? (int)$_GET['detail_attempt'] : 0;

$filter_department = $_GET['filter_department'] ?? 'all';
$filter_role = $_GET['filter_role'] ?? 'all';
$filter_audience = 'all';

// Get all posted examinations from exam_repository
if ($selected_department === 'all') {
    $sql = "SELECT er.*, COUNT(erq.id) AS question_count
            FROM exam_repository er
            LEFT JOIN examinations e ON e.id = er.original_exam_id
            LEFT JOIN exam_repository_questions erq ON er.id = erq.exam_id
            WHERE er.status = 'posted'
              AND (er.original_exam_id IS NULL OR e.id IS NOT NULL)
            GROUP BY er.id
            ORDER BY er.created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT er.*, COUNT(erq.id) AS question_count
            FROM exam_repository er
            LEFT JOIN examinations e ON e.id = er.original_exam_id
            LEFT JOIN exam_repository_questions erq ON er.id = erq.exam_id
            WHERE er.status = 'posted' AND er.department = ?
              AND (er.original_exam_id IS NULL OR e.id IS NOT NULL)
            GROUP BY er.id
            ORDER BY er.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_department);
}

$posted_examinations = [];
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $posted_examinations[] = $row;
        }
    }
}

// Fetch attempt counts for each posted exam
foreach ($posted_examinations as &$exam) {
    $examId = (int)$exam['id'];
    $countSql = "SELECT COUNT(*) AS attempt_count FROM exam_results WHERE exam_id = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('i', $examId);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $exam['attempt_count'] = $countRes ? (int)($countRes->fetch_assoc()['attempt_count'] ?? 0) : 0;
    $countStmt->close();
}
unset($exam); // break reference

// If exam_id is provided, get exam details and employees who took it
$exam_details = null;
$exam_results = [];
$detail_result = null;
$detail_answers = [];
$taker_departments = [];
$taker_roles = [];
if ($exam_id) {
    // Get exam details from exam_repository
    $exam_sql = "SELECT * FROM exam_repository WHERE id = ?";
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("i", $exam_id);
    
    if ($exam_stmt->execute()) {
        $exam_result = $exam_stmt->get_result();
        $exam_details = $exam_result->fetch_assoc();
    }
    
    // Get employees who took the exam
    if ($exam_details) {
        $employeeJoinOn = columnExists($conn, 'employees', 'employee_id')
            ? 'e.employee_id = er.employee_id'
            : 'e.id = er.employee_id';

        if ($detail_employee_id !== '' && $detail_attempt > 0) {
            $detail_stmt = $conn->prepare("SELECT er.*
                FROM exam_results er
                WHERE er.exam_id = ? AND er.employee_id = ? AND er.attempt_number = ?
                LIMIT 1");
            $detail_stmt->bind_param('isi', $exam_id, $detail_employee_id, $detail_attempt);
            $detail_stmt->execute();
            $detail_res = $detail_stmt->get_result();
            $detail_result = $detail_res ? $detail_res->fetch_assoc() : null;
            $detail_stmt->close();

            if ($detail_result) {
                $answers_stmt = $conn->prepare("SELECT 
                        q.question_number,
                        q.question_text,
                        q.question_type,
                        q.options,
                        q.answer_key,
                        q.expected_answer,
                        q.points,
                        a.user_answer,
                        a.correct_answer,
                        a.is_correct
                    FROM exam_result_answers a
                    INNER JOIN exam_repository_questions q ON q.id = a.question_id
                    WHERE a.result_id = ?
                    ORDER BY q.question_number");
                $rid = (int)$detail_result['id'];
                $answers_stmt->bind_param('i', $rid);
                $answers_stmt->execute();
                $answers_res = $answers_stmt->get_result();
                while ($answers_res && ($row = $answers_res->fetch_assoc())) {
                    $detail_answers[] = $row;
                }
                $answers_stmt->close();
            }
        } else {
            $dept_stmt = $conn->prepare("SELECT DISTINCT taker_department FROM exam_results WHERE exam_id = ? AND taker_department IS NOT NULL AND taker_department <> '' ORDER BY taker_department");
            $dept_stmt->bind_param('i', $exam_id);
            $dept_stmt->execute();
            $dept_res = $dept_stmt->get_result();
            while ($dept_res && ($r = $dept_res->fetch_assoc())) {
                $taker_departments[] = (string)$r['taker_department'];
            }
            $dept_stmt->close();

            $role_stmt = $conn->prepare("SELECT DISTINCT taker_role FROM exam_results WHERE exam_id = ? AND taker_role IS NOT NULL AND taker_role <> '' ORDER BY taker_role");
            $role_stmt->bind_param('i', $exam_id);
            $role_stmt->execute();
            $role_res = $role_stmt->get_result();
            while ($role_res && ($r = $role_res->fetch_assoc())) {
                $taker_roles[] = (string)$r['taker_role'];
            }
            $role_stmt->close();

            $filterWheres = [];
            $filterTypes = '';
            $filterParams = [];
            if ($filter_department !== 'all' && $filter_department !== '') {
                $filterWheres[] = 'er.taker_department = ?';
                $filterTypes .= 's';
                $filterParams[] = $filter_department;
            }
            if ($filter_role !== 'all' && $filter_role !== '') {
                $filterWheres[] = 'er.taker_role = ?';
                $filterTypes .= 's';
                $filterParams[] = $filter_role;
            }
            $filterSql = '';
            if (!empty($filterWheres)) {
                $filterSql = ' AND ' . implode(' AND ', $filterWheres);
            }

            $subWheres = [];
            $subTypes = '';
            $subParams = [];
            if ($filter_department !== 'all' && $filter_department !== '') {
                $subWheres[] = 'taker_department = ?';
                $subTypes .= 's';
                $subParams[] = $filter_department;
            }
            if ($filter_role !== 'all' && $filter_role !== '') {
                $subWheres[] = 'taker_role = ?';
                $subTypes .= 's';
                $subParams[] = $filter_role;
            }
            $subSql = '';
            if (!empty($subWheres)) {
                $subSql = ' AND ' . implode(' AND ', $subWheres);
            }

            $results_sql = "
                SELECT 
                    er.employee_id AS employee_id,
                    e.first_name,
                    e.last_name,
                    e.department,
                    e.position,
                    er.taker_department,
                    er.taker_role,
                    er.taker_name,
                    er.score,
                    er.total_questions,
                    er.passed,
                    er.time_taken,
                    er.completed_at,
                    er.attempt_number,
                    er.taker_type
                FROM exam_results er
                LEFT JOIN employees e ON {$employeeJoinOn}
                WHERE er.exam_id = ?{$filterSql}
                ORDER BY er.employee_id, er.attempt_number DESC, er.completed_at DESC
            ";

            $results_stmt = $conn->prepare($results_sql);
            if ($results_stmt) {
                $params = [$exam_id];
                $types = 'i' . $filterTypes;
                $params = array_merge($params, $filterParams);
                bindParams($results_stmt, $types, $params);
            }

            if (!empty($results_stmt) && $results_stmt) {
                if ($results_stmt->execute()) {
                    $results_result = $results_stmt->get_result();
                    while ($row = $results_result->fetch_assoc()) {
                        $exam_results[] = $row;
                    }
                }
                $results_stmt->close();
            }
        }
    }
}

// Get unique departments for filter dropdown from exam_repository
$departments_sql = "SELECT DISTINCT er.department
    FROM exam_repository er
    LEFT JOIN examinations e ON e.id = er.original_exam_id
    WHERE er.status = 'posted'
      AND (er.original_exam_id IS NULL OR e.id IS NOT NULL)
    ORDER BY er.department";
$departments_result = $conn->query($departments_sql);
$departments = [];
if ($departments_result && $departments_result->num_rows > 0) {
    while($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$conn->close();
require('../../partials/header.php');
?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .exam-card, .employee-card {
      transition: all 0.2s ease;
      border: 1px solid #e5e7eb;
      background: white;
    }
    
    .exam-card:hover, .employee-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .btn-plain {
      background-color: white;
      border: 1px solid #d1d5db;
      color: #374151;
      transition: all 0.2s ease;
    }
    
    .btn-plain:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }
    
    .status-posted {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }
    
    .status-passed {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .status-failed {
      background-color: #fee2e2;
      color: #991b1b;
    }
    
    .badge-outline {
      background-color: white;
      border: 1px solid #d1d5db;
      color: #6b7280;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #6b7280;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      background: white;
    }
    
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .filter-section {
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
    }
    
    .stats-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
    }
    
    .table-row:hover {
      background-color: #f9fafb;
    }
  </style>
  <link rel="stylesheet" href="../../CSS/learning_theme.css">
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
        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">

          <?php if (!$exam_id): ?>
            <!-- Examination List View -->
            <div class="mb-12">
              <div class="flex justify-between items-center mb-6">
                <div>
                  <h1 class="text-3xl font-bold mb-2 text-gray-800">Examination Results Dashboard</h1>
                  <p class="text-gray-600">View results for all posted examinations</p>
                </div>
                <div class="flex gap-4 items-center">
                  <div class="stats-card hr2-summary-card">
                    <div class="text-sm text-gray-500">Posted Exams</div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo count($posted_examinations); ?></div>
                    <div class="text-xs text-gray-400">Available for review</div>
                  </div>
                 
                </div>
              </div>

              <!-- Department Filter -->
              <div class="filter-section mb-6">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Filter by Department
                    </label>
                    <div class="flex flex-col sm:flex-row gap-3">
                      <select class="select select-bordered w-full sm:w-64" id="departmentFilter">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): 
                          $display_name = ucwords(str_replace('-', ' ', $dept));
                        ?>
                          <option value="<?php echo $dept; ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                            <?php echo $display_name; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      
                      <div class="flex gap-2">
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyFilter()">
                          Apply Filter
                        </button>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearFilter()">
                          Clear
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Active Filter Badge -->
                  <?php if ($selected_department !== 'all'): ?>
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-600">Active filter:</span>
                      <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                        <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Examination Cards -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php if (empty($posted_examinations)): ?>
                  <div class="col-span-full empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3 class="text-xl font-semibold mb-2 text-gray-700">
                      <?php if ($selected_department !== 'all'): ?>
                        No Posted Examinations in <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                      <?php else: ?>
                        No Posted Examinations
                      <?php endif; ?>
                    </h3>
                    <p class="text-gray-500 mb-4">
                      <?php if ($selected_department !== 'all'): ?>
                        There are no approved examinations in this department.
                      <?php else: ?>
                        There are no approved examinations at this time.
                      <?php endif; ?>
                    </p>
                    <div class="flex gap-2 justify-center">
                      <?php if ($selected_department !== 'all'): ?>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearFilter()">
                          View All Departments
                        </button>
                      <?php endif; ?>
                      <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='examinations.php'">
                        <i class="fas fa-plus mr-2"></i>Create New Examination
                      </button>
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($posted_examinations as $exam): ?>
                    <div class="exam-card rounded-lg p-6">
                      <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($exam['title']); ?></h3>
                        <span class="status-posted text-xs px-2 py-1 rounded-full">Posted</span>
                      </div>
                      
                      <div class="flex flex-wrap gap-2 my-3">
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></span>
                        <?php if (isset($exam['roles']) && !empty($exam['roles'])): ?>
                          <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($exam['roles']); ?></span>
                        <?php else: ?>
                          <span class="badge-outline text-xs px-2 py-1 rounded">All Roles</span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="space-y-2 mb-4">
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Questions:</span> 
                          <?php echo isset($exam['question_count']) ? $exam['question_count'] : 'N/A'; ?>
                        </p>
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Duration:</span> 
                          <?php echo isset($exam['duration']) ? $exam['duration'] : 'N/A'; ?> minutes
                        </p>
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Passing Score:</span> 
                          <?php echo isset($exam['passing_score']) ? $exam['passing_score'] . '%' : '70%'; ?>
                        </p>
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Attempts:</span> 
                          <?php echo (int)($exam['attempt_count'] ?? 0); ?>
                        </p>
                      </div>
                      
                      <div class="mt-4 flex gap-2">
                        <button class="btn-plain flex-1 py-2 rounded-lg text-sm" 
                                onclick="viewResults(<?php echo $exam['id']; ?>)">
                          <i class="fas fa-chart-bar mr-2"></i>View Results
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

          <?php else: ?>
            <!-- Exam Results Detail View -->
            <div class="mb-12">
              <div class="flex justify-between items-center mb-6">
                <div>
                  <div class="flex items-center gap-2 mb-2">
                    <button class="btn-plain px-3 py-1 rounded-lg text-sm" onclick="goBack()">
                      <i class="fas fa-arrow-left mr-1"></i>Back
                    </button>
                    <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($exam_details['title']); ?></h2>
                  </div>
                  <p class="text-gray-600">Examination results and performance</p>
                </div>
                <div class="flex gap-4"></div>
              </div>

              <?php if ($detail_employee_id !== '' && $detail_attempt > 0 && $detail_result): ?>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
                  <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                      <div>
                        <h3 class="text-lg font-semibold text-gray-800">Attempt Details</h3>
                        <div class="text-sm text-gray-600">Taker ID: <span class="font-medium"><?php echo htmlspecialchars($detail_employee_id); ?></span></div>
                        <div class="text-sm text-gray-600">Type: <span class="font-medium"><?php echo htmlspecialchars($detail_result['taker_type'] ?? 'employee'); ?></span></div>
                      </div>
                      <div class="text-sm text-gray-600">
                        Attempt #<?php echo (int)($detail_result['attempt_number'] ?? 1); ?> â€¢ Time Taken: <?php echo gmdate('H:i:s', (int)($detail_result['time_taken'] ?? 0)); ?>
                      </div>
                    </div>
                  </div>

                  <div class="p-6 space-y-4">
                    <?php if (empty($detail_answers)): ?>
                      <div class="text-sm text-gray-600">No detailed answers recorded for this attempt.</div>
                    <?php else: ?>
                      <?php foreach ($detail_answers as $a): ?>
                        <?php
                          $ua = (string)($a['user_answer'] ?? '');
                          $ca = (string)($a['correct_answer'] ?? '');
                          $uaJson = json_decode($ua, true);
                          $caJson = json_decode($ca, true);
                          $uaDisplay = is_array($uaJson) ? implode(', ', array_map('strval', $uaJson)) : $ua;
                          $caDisplay = is_array($caJson) ? implode(', ', array_map('strval', $caJson)) : $ca;
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                          <div class="flex items-start justify-between gap-4">
                            <div>
                              <div class="text-sm text-gray-500">Question <?php echo (int)($a['question_number'] ?? 0); ?> â€¢ <?php echo htmlspecialchars((string)($a['question_type'] ?? '')); ?></div>
                              <div class="text-base font-medium text-gray-800 mt-1"><?php echo htmlspecialchars((string)($a['question_text'] ?? '')); ?></div>
                            </div>
                            <div>
                              <?php if (!empty($a['is_correct'])): ?>
                                <span class="status-passed px-2 py-1 text-xs rounded-full">Correct</span>
                              <?php else: ?>
                                <span class="status-failed px-2 py-1 text-xs rounded-full">Incorrect</span>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                              <div class="text-xs font-medium text-gray-500 uppercase">Answer</div>
                              <div class="text-sm text-gray-800 mt-1"><?php echo htmlspecialchars($uaDisplay !== '' ? $uaDisplay : '-'); ?></div>
                            </div>
                            <div>
                              <div class="text-xs font-medium text-gray-500 uppercase">Correct Answer</div>
                              <div class="text-sm text-gray-800 mt-1"><?php echo htmlspecialchars($caDisplay !== '' ? $caDisplay : '-'); ?></div>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="filter-section mb-6">
                  <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700 mb-2">Filters</label>
                      <div class="flex flex-col sm:flex-row gap-3">
                        <select class="select select-bordered w-full sm:w-56" id="takerDepartmentFilter">
                          <option value="all" <?php echo $filter_department === 'all' ? 'selected' : ''; ?>>All Departments</option>
                          <?php foreach ($taker_departments as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $filter_department === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $d))); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <select class="select select-bordered w-full sm:w-64" id="takerRoleFilter">
                          <option value="all" <?php echo $filter_role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                          <?php foreach ($taker_roles as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $filter_role === $r ? 'selected' : ''; ?>><?php echo htmlspecialchars($r); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <div class="flex gap-2">
                          <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyResultFilters(<?php echo (int)$exam_id; ?>)">Apply</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              <!-- Exam Overview Stats -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card hr2-summary-card">
                  <div class="text-sm text-gray-500">Total Participants</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo count($exam_results); ?></div>
                </div>
                <div class="stats-card hr2-summary-card">
                  <div class="text-sm text-gray-500">Average Score</div>
                  <div class="text-2xl font-bold text-gray-800">
                    <?php 
                      if (count($exam_results) > 0) {
                        $total_score = 0;
                        foreach ($exam_results as $result) {
                          $total_score += $result['score'];
                        }
                        echo round($total_score / count($exam_results), 1) . '%';
                      } else {
                        echo '0%';
                      }
                    ?>
                  </div>
                </div>
                <div class="stats-card hr2-summary-card">
                  <div class="text-sm text-gray-500">Pass Rate</div>
                  <div class="text-2xl font-bold text-gray-800">
                    <?php 
                      if (count($exam_results) > 0) {
                        $passed_count = 0;
                        foreach ($exam_results as $result) {
                          if ($result['passed']) $passed_count++;
                        }
                        echo round(($passed_count / count($exam_results)) * 100, 1) . '%';
                      } else {
                        echo '0%';
                      }
                    ?>
                  </div>
                </div>
                <div class="stats-card hr2-summary-card">
                  <div class="text-sm text-gray-500">Department</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo isset($exam_details['department']) ? ucwords(str_replace('-', ' ', $exam_details['department'])) : 'All'; ?></div>
                </div>
              </div>

              <!-- Results Table -->
              <?php
                $applicant_results = [];
                $employee_results = [];
                foreach ($exam_results as $r) {
                  if (($r['taker_type'] ?? 'employee') === 'applicant') {
                    $applicant_results[] = $r;
                  } else {
                    $employee_results[] = $r;
                  }
                }
              ?>

              <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800">Applicant Results</h3>
                  <p class="text-sm text-gray-600">Applicants who took this exam</p>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Taken</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php if (empty($applicant_results)): ?>
                        <tr>
                          <td colspan="8" class="px-6 py-6 text-center text-sm text-gray-500">No applicant results found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($applicant_results as $result): ?>
                          <?php $displayName = trim((string)($result['taker_name'] ?? 'Applicant')); ?>
                          <?php $initials = $displayName !== '' ? strtoupper(substr($displayName, 0, 1)) : 'A'; ?>
                          <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                  <span class="text-blue-600 font-medium"><?php echo htmlspecialchars($initials); ?></span>
                                </div>
                                <div class="ml-4">
                                  <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Applicant'); ?></div>
                                </div>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-900"><?php echo htmlspecialchars(!empty($result['taker_role']) ? $result['taker_role'] : '-'); ?></div>
                              <div class="text-sm text-gray-500"><?php echo htmlspecialchars(!empty($result['taker_department']) ? $result['taker_department'] : '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2.5 mr-2">
                                  <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $result['score']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $result['score']; ?>%</span>
                              </div>
                              <div class="text-xs text-gray-500"><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?> correct</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <?php if ($result['passed']): ?>
                                <span class="status-passed px-2 py-1 text-xs rounded-full">Passed</span>
                              <?php else: ?>
                                <span class="status-failed px-2 py-1 text-xs rounded-full">Failed</span>
                              <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo gmdate("H:i:s", (int)($result['time_taken'] ?? 0)); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo !empty($result['completed_at']) ? date('M j, Y g:i A', strtotime($result['completed_at'])) : '-'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Attempt #<?php echo (int)($result['attempt_number'] ?? 1); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                              <a class="text-blue-600 hover:text-blue-900 mr-3" href="exam_results.php?exam_id=<?php echo (int)$exam_id; ?>&filter_department=<?php echo urlencode((string)$filter_department); ?>&filter_role=<?php echo urlencode((string)$filter_role); ?>&detail_employee_id=<?php echo urlencode((string)($result['employee_id'] ?? '')); ?>&detail_attempt=<?php echo (int)($result['attempt_number'] ?? 1); ?>">Details</a>
                              <?php if (!empty($result['passed'])): ?>
                                <button class="text-green-600 hover:text-green-900" onclick='forwardApplicantResult(<?php echo json_encode($result['employee_id'] ?? ''); ?>, <?php echo (int)$exam_id; ?>)'>Forward Result</button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800">Employee Results</h3>
                  <p class="text-sm text-gray-600">Employees who took this exam</p>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Taken</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php if (empty($employee_results)): ?>
                        <tr>
                          <td colspan="8" class="px-6 py-6 text-center text-sm text-gray-500">No employee results found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($employee_results as $result): ?>
                          <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                  <span class="text-blue-600 font-medium">
                                    <?php echo strtoupper(substr($result['first_name'] ?? 'E', 0, 1) . substr($result['last_name'] ?? 'M', 0, 1)); ?>
                                  </span>
                                </div>
                                <div class="ml-4">
                                  <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(($result['first_name'] ?? 'Employee') . ' ' . ($result['last_name'] ?? 'Name')); ?></div>
                                  <div class="text-sm text-gray-500"><?php echo htmlspecialchars($result['employee_id'] ?? 'EMP001'); ?></div>
                                </div>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-900"><?php echo htmlspecialchars(!empty($result['taker_role']) ? $result['taker_role'] : ($result['position'] ?? '-')); ?></div>
                              <div class="text-sm text-gray-500"><?php echo htmlspecialchars(!empty($result['taker_department']) ? $result['taker_department'] : ($result['department'] ?? '-')); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2.5 mr-2">
                                  <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $result['score']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $result['score']; ?>%</span>
                              </div>
                              <div class="text-xs text-gray-500"><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?> correct</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <?php if ($result['passed']): ?>
                                <span class="status-passed px-2 py-1 text-xs rounded-full">Passed</span>
                              <?php else: ?>
                                <span class="status-failed px-2 py-1 text-xs rounded-full">Failed</span>
                              <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo gmdate("H:i:s", (int)($result['time_taken'] ?? 0)); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo !empty($result['completed_at']) ? date('M j, Y g:i A', strtotime($result['completed_at'])) : '-'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Attempt #<?php echo (int)($result['attempt_number'] ?? 1); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                              <a class="text-blue-600 hover:text-blue-900 mr-3" href="exam_results.php?exam_id=<?php echo (int)$exam_id; ?>&filter_department=<?php echo urlencode((string)$filter_department); ?>&filter_role=<?php echo urlencode((string)$filter_role); ?>&detail_employee_id=<?php echo urlencode((string)($result['employee_id'] ?? '')); ?>&detail_attempt=<?php echo (int)($result['attempt_number'] ?? 1); ?>">Details</a>
                              <?php if (!empty($result['passed'])): ?>
                                <button class="text-green-600 hover:text-green-900" onclick='viewCertificate(<?php echo json_encode($result['employee_id'] ?? ''); ?>, <?php echo (int)$exam_id; ?>)'>Certificate</button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
    </div>
  </div>

  <script>
    // Filter Functions
    function applyFilter() {
      const departmentFilter = document.getElementById('departmentFilter');
      const selectedDepartment = departmentFilter.value;
      window.location.href = `?department=${selectedDepartment}`;
    }
    
    function clearFilter() {
      window.location.href = '?department=all';
    }

    function viewResults(examId) {
      window.location.href = `?exam_id=${examId}`;
    }

    function goBack() {
      window.location.href = 'exam_results.php';
    }

    function applyResultFilters(examId) {
      const deptEl = document.getElementById('takerDepartmentFilter');
      const roleEl = document.getElementById('takerRoleFilter');

      const dept = deptEl ? deptEl.value : 'all';
      const role = roleEl ? roleEl.value : 'all';

      window.location.href = `?exam_id=${examId}`
        + `&filter_department=${encodeURIComponent(dept)}`
        + `&filter_role=${encodeURIComponent(role)}`;
    }

    function viewDetailedResult(employeeId, examId) {
      window.location.href = `?exam_id=${examId}&detail_employee_id=${encodeURIComponent(employeeId)}&detail_attempt=1`;
    }

    function viewCertificate(employeeId, examId) {
      if (typeof Swal === 'undefined' || !Swal.fire) {
        alert(`Generate certificate for employee ${employeeId} and exam ${examId}`);
        return;
      }
      Swal.fire({
        title: 'Info',
        text: `Generate certificate for employee ${employeeId} and exam ${examId}`,
        icon: 'info',
        confirmButtonText: 'OK',
        confirmButtonColor: '#3b82f6'
      });
    }

    function forwardApplicantResult(applicantId, examId) {
      const targetUrl = `../HR_2/applicant_management.php?applicant_id=${encodeURIComponent(applicantId)}&exam_id=${encodeURIComponent(examId)}`;

      if (typeof Swal === 'undefined' || !Swal.fire) {
        if (confirm('Forward to applicant management?')) {
          window.location.href = targetUrl;
        }
        return;
      }

      Swal.fire({
        title: 'Forward Result?',
        text: 'Forward to applicant management?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: '#3b82f6'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = targetUrl;
        }
      });
    }
  </script>
      <script>
    lucide.createIcons();
  </script>
  <?php require('../../partials/footer.php') ?>
