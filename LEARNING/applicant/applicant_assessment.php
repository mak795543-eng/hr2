<?php
session_start();

require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('learning_db');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$conn->query("CREATE TABLE IF NOT EXISTS exam_violation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    taker_id VARCHAR(50) DEFAULT NULL,
    taker_type ENUM('applicant','employee') NOT NULL DEFAULT 'applicant',
    exam_id INT NOT NULL,
    violation_type VARCHAR(50) NOT NULL,
    details TEXT,
    user_agent TEXT,
    ip_address VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_exam (exam_id),
    INDEX idx_taker (taker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && isset($_POST['log_violation'])) {
    $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
    $type = trim((string)($_POST['violation_type'] ?? 'unknown'));
    $details = trim((string)($_POST['details'] ?? ''));

    $taker_id = $_SESSION['employee_id'] ?? ($_SESSION['user_id'] ?? null);
    $taker_type = 'applicant';

    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    if ($exam_id > 0) {
        $stmt = $conn->prepare('INSERT INTO exam_violation_logs (taker_id, taker_type, exam_id, violation_type, details, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssissss', $taker_id, $taker_type, $exam_id, $type, $details, $ua, $ip);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing exam_id']);
    }
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS exam_repository_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    audience ENUM('applicant', 'employee') NOT NULL,
    department VARCHAR(100) NOT NULL,
    role VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    assigned_by VARCHAR(50),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_exam_audience_dept_role (exam_id, audience, department, role),
    INDEX idx_audience_dept_role (audience, department, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM {$tableName} LIKE '" . $conn->real_escape_string($columnName) . "'");
    return $result && $result->num_rows > 0;
}

if (!tableExists($conn, 'exam_results')) {
    $conn->query("CREATE TABLE exam_results (
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
        taker_department VARCHAR(100) DEFAULT NULL,
        taker_role VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_employee_exam (employee_id, exam_id),
        INDEX idx_exam_id (exam_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if (!columnExists($conn, 'exam_results', 'attempt_number')) {
    $conn->query('ALTER TABLE exam_results ADD COLUMN attempt_number INT DEFAULT 1');
}
if (!columnExists($conn, 'exam_results', 'time_taken')) {
    $conn->query("ALTER TABLE exam_results ADD COLUMN time_taken INT NOT NULL DEFAULT 0 COMMENT 'Time taken in seconds'");
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

if (!tableExists($conn, 'exam_result_answers')) {
    $conn->query("CREATE TABLE exam_result_answers (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function normalize_options($rawOptions): array {
    if ($rawOptions === null || $rawOptions === '') {
        return [];
    }

    $decoded = json_decode($rawOptions, true);
    if (!is_array($decoded)) {
        return [];
    }

    $options = [];
    foreach ($decoded as $opt) {
        if (is_string($opt)) {
            $v = trim($opt);
            if ($v !== '') {
                $options[] = $v;
            }
            continue;
        }

        if (is_array($opt)) {
            $v = trim((string)($opt['value'] ?? ''));
            if ($v !== '') {
                $options[] = $v;
            }
        }
    }

    return $options;
}

function normalize_correct_answers(?string $answerKeyJson, ?string $fallbackExpectedAnswer): array {
    $answers = [];

    if ($answerKeyJson) {
        $decoded = json_decode($answerKeyJson, true);
        if (is_array($decoded) && isset($decoded['correctAnswers']) && is_array($decoded['correctAnswers'])) {
            foreach ($decoded['correctAnswers'] as $a) {
                if (is_string($a)) {
                    $v = trim($a);
                    if ($v !== '') {
                        $answers[] = $v;
                    }
                }
            }
        }
    }

    if (empty($answers) && $fallbackExpectedAnswer) {
        $v = trim((string)$fallbackExpectedAnswer);
        if ($v !== '') {
            $answers[] = $v;
        }
    }

    return $answers;
}

$department_roles = [
    'front-office' => [
        'Front Desk Manager',
        'Receptionist / Front Desk Officer',
        'Guest Service Agent / Concierge',
        'Reservation Agent',
        'Bellhop / Porter',
        'Front Office Supervisor'
    ],
    'housekeeping' => [
        'Executive Housekeeper / Housekeeping Manager',
        'Floor Supervisor',
        'Room Attendant / Housekeeper',
        'Laundry Attendant',
        'Public Area Attendant',
        'Housekeeping Inspector'
    ],
    'food-beverage' => [
        'F&B Manager / Director',
        'Restaurant Manager / Captain',
        'Waiter / Waitress / Server',
        'Bartender',
        'Banquet / Catering Coordinator',
        'F&B Supervisor'
    ],
    'kitchen' => [
        'Executive Chef / Head Chef',
        'Sous Chef',
        'Line Cook / Station Chef',
        'Pastry Chef / Baker',
        'Kitchen Steward / Dishwasher',
        'Commis Chef'
    ],
    'sales-marketing' => [
        'Sales & Marketing Manager',
        'Revenue Manager',
        'Event / Banquet Sales Coordinator',
        'Social Media / Marketing Executive',
        'Sales Executive',
        'Marketing Coordinator'
    ],
    'human-resources' => [
        'HR Manager / Director',
        'Recruitment Officer',
        'Training & Development Specialist',
        'Payroll / HR Assistant',
        'HR Coordinator',
        'Employee Relations Specialist'
    ],
    'finance' => [
        'Finance Manager / Controller',
        'Accountant',
        'Payroll Officer',
        'Cost Controller',
        'Accounts Payable/Receivable Clerk',
        'Financial Analyst'
    ],
    'engineering' => [
        'Chief Engineer / Engineering Manager',
        'Maintenance Technician',
        'Electrician / Plumber',
        'HVAC Technician',
        'Carpenter',
        'Painter'
    ],
    'security' => [
        'Security Manager / Supervisor',
        'Security Guard',
        'CCTV / Surveillance Officer',
        'Security Officer',
        'Surveillance Operator',
        'Access Control Officer'
    ]
];

$selectedDepartment = isset($_GET['department']) ? trim((string)$_GET['department']) : '';
$selectedRole = isset($_GET['role']) ? trim((string)$_GET['role']) : '';

$secureMode = isset($_GET['secure']) && $_GET['secure'] === '1';
$maxViolations = 5;

$applicantName = trim((string)($_SESSION['applicant_name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['context_submit'])) {
    $name = trim((string)($_POST['applicant_name'] ?? ''));
    $dept = trim((string)($_POST['department'] ?? ''));
    $role = trim((string)($_POST['role'] ?? ''));
    if ($name !== '' && $dept !== '' && $role !== '') {
        $_SESSION['applicant_name'] = $name;
        $applicantName = $name;
        header('Location: applicant_assessment.php?department=' . urlencode($dept) . '&role=' . urlencode($role));
        exit;
    }
}

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

$exam = null;
$questions = [];

if ($examId > 0) {
    if ($selectedDepartment !== '' && $selectedRole !== '') {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'applicant'
             AND era.status = 'active'
             AND era.department = ?
             AND era.role = ?
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('ssi', $selectedDepartment, $selectedRole, $examId);
    } else {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'applicant'
             AND era.status = 'active'
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('i', $examId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $exam = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($exam) {
        $qStmt = $conn->prepare('SELECT * FROM exam_repository_questions WHERE exam_id = ? ORDER BY question_number');
        $qStmt->bind_param('i', $examId);
        $qStmt->execute();
        $qRes = $qStmt->get_result();
        while ($qRes && ($row = $qRes->fetch_assoc())) {
            $questions[] = $row;
        }
        $qStmt->close();
    }
}

$resultSummary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedExamId = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

    if ($selectedDepartment !== '' && $selectedRole !== '') {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'applicant'
             AND era.status = 'active'
             AND era.department = ?
             AND era.role = ?
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('ssi', $selectedDepartment, $selectedRole, $postedExamId);
    } else {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'applicant'
             AND era.status = 'active'
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('i', $postedExamId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $exam = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($exam) {
        $examId = (int)$exam['id'];

        $qStmt = $conn->prepare('SELECT * FROM exam_repository_questions WHERE exam_id = ? ORDER BY question_number');
        $qStmt->bind_param('i', $examId);
        $qStmt->execute();
        $qRes = $qStmt->get_result();
        $questions = [];
        while ($qRes && ($row = $qRes->fetch_assoc())) {
            $questions[] = $row;
        }
        $qStmt->close();

        $answers = $_POST['answers'] ?? [];
        $timeTaken = isset($_POST['time_taken']) ? (int)$_POST['time_taken'] : 0;
        if ($timeTaken < 0) {
            $timeTaken = 0;
        }

        $earned = 0;
        $total = 0;

        $answerRows = [];

        foreach ($questions as $q) {
            $qid = (int)($q['id'] ?? 0);
            $points = (int)($q['points'] ?? 1);
            if ($points <= 0) {
                $points = 1;
            }

            $total += $points;

            $type = (string)($q['question_type'] ?? '');
            $correct = normalize_correct_answers($q['answer_key'] ?? null, $q['expected_answer'] ?? null);

            $isCorrect = false;
            $userAnswerStore = '';
            $correctAnswerStore = '';

            if (count($correct) > 1) {
                $correctAnswerStore = json_encode($correct, JSON_UNESCAPED_UNICODE);
            } else {
                $correctAnswerStore = (string)($correct[0] ?? '');
            }

            if ($type === 'multiple') {
                $userAns = $answers[$qid] ?? '';
                $user = mb_strtolower(trim((string)$userAns));

                $userAnswerStore = (string)$userAns;

                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);
                $isCorrect = in_array($user, $normCorrect, true) && $user !== '';
            } elseif ($type === 'truefalse') {
                $userAns = $answers[$qid] ?? '';
                $user = mb_strtolower(trim((string)$userAns));

                $userAnswerStore = (string)$userAns;

                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);
                $isCorrect = in_array($user, $normCorrect, true) && $user !== '';
            } elseif ($type === 'shortanswer' || $type === 'identification') {
                $userAns = $answers[$qid] ?? '';
                $user = mb_strtolower(trim((string)$userAns));

                $userAnswerStore = (string)$userAns;

                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);
                $isCorrect = in_array($user, $normCorrect, true) && $user !== '';
            } else {
                $userAns = $answers[$qid] ?? '';
                $userAnswerStore = is_array($userAns) ? json_encode($userAns, JSON_UNESCAPED_UNICODE) : (string)$userAns;
            }

            if ($isCorrect) {
                $earned += $points;
            }

            $answerRows[] = [
                'question_id' => $qid,
                'question_number' => (int)($q['question_number'] ?? 0),
                'question_type' => $type,
                'user_answer' => $userAnswerStore,
                'correct_answer' => $correctAnswerStore,
                'is_correct' => $isCorrect ? 1 : 0,
                'points_possible' => $points,
                'points_earned' => $isCorrect ? $points : 0
            ];
        }

        $percent = $total > 0 ? round(($earned / $total) * 100, 2) : 0.0;
        $passingScore = isset($exam['passing_score']) ? (float)$exam['passing_score'] : 0.0;
        $passed = $percent >= $passingScore;

        // Insert into exam_results
        $applicantId = $_SESSION['employee_id'] ?? ($_SESSION['user_id'] ?? 'unknown');
        $passedInt = $passed ? 1 : 0;

        $attemptNumber = 1;
        $takerType = 'applicant';
        $attemptStmt = $conn->prepare('SELECT COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt FROM exam_results WHERE employee_id = ? AND exam_id = ? AND taker_type = ?');
        $attemptStmt->bind_param('sis', $applicantId, $examId, $takerType);
        $attemptStmt->execute();
        $attemptRes = $attemptStmt->get_result();
        $attemptNumber = (int)(($attemptRes ? $attemptRes->fetch_assoc()['next_attempt'] ?? 1 : 1));
        if ($attemptNumber <= 0) {
            $attemptNumber = 1;
        }
        $attemptStmt->close();

        $takerName = $applicantName !== '' ? $applicantName : 'Applicant';
        $insertStmt = $conn->prepare('INSERT INTO exam_results (employee_id, exam_id, score, total_questions, passed, time_taken, completed_at, attempt_number, taker_type, taker_name, taker_department, taker_role) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)');
        $insertStmt->bind_param('sidiiiissss', $applicantId, $examId, $percent, $total, $passedInt, $timeTaken, $attemptNumber, $takerType, $takerName, $selectedDepartment, $selectedRole);
        $insertStmt->execute();
        $resultId = (int)$conn->insert_id;
        $insertStmt->close();

        if ($resultId > 0 && !empty($answerRows)) {
            $ansStmt = $conn->prepare('INSERT INTO exam_result_answers (result_id, question_id, question_number, question_type, user_answer, correct_answer, is_correct, points_possible, points_earned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_answer = VALUES(user_answer), correct_answer = VALUES(correct_answer), is_correct = VALUES(is_correct), points_possible = VALUES(points_possible), points_earned = VALUES(points_earned)');
            foreach ($answerRows as $r) {
                $qid = (int)$r['question_id'];
                $qnum = (int)$r['question_number'];
                $qtype = (string)$r['question_type'];
                $ua = (string)$r['user_answer'];
                $ca = (string)$r['correct_answer'];
                $ic = (int)$r['is_correct'];
                $pp = (int)$r['points_possible'];
                $pe = (int)$r['points_earned'];
                $ansStmt->bind_param('iiisssiii', $resultId, $qid, $qnum, $qtype, $ua, $ca, $ic, $pp, $pe);
                $ansStmt->execute();
            }
            $ansStmt->close();
        }

        $resultSummary = [
            'earned' => $earned,
            'total' => $total,
            'percent' => $percent,
            'passing_score' => $passingScore,
            'passed' => $passed
        ];
    }
}

$postedExams = [];
if ($selectedDepartment !== '' && $selectedRole !== '') {
    $postedStmt = $conn->prepare("SELECT er.*, COUNT(erq.id) AS question_count
        FROM exam_repository er
        INNER JOIN exam_repository_assignments era
          ON era.exam_id = er.id
         AND era.audience = 'applicant'
         AND era.status = 'active'
         AND era.department = ?
         AND era.role = ?
        LEFT JOIN exam_repository_questions erq ON er.id = erq.exam_id
        WHERE er.status = 'posted'
        GROUP BY er.id
        ORDER BY er.created_at DESC");
    $postedStmt->bind_param('ss', $selectedDepartment, $selectedRole);
    $postedStmt->execute();
    $postRes = $postedStmt->get_result();
    while ($postRes && ($row = $postRes->fetch_assoc())) {
        $postedExams[] = $row;
    }
    $postedStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Assessment</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .swal2-container {
      position: fixed !important;
      inset: 0 !important;
      z-index: 2147483647 !important;
      pointer-events: auto !important;
    }

    .swal2-popup {
      z-index: 2147483647 !important;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
 <?php $hideShell = ($secureMode && $exam && !empty($questions)); ?>
 <?php if (!$hideShell): ?>
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
 <?php endif; ?>

 <?php if ($exam && !empty($questions)): ?>
    <div class="min-h-screen">
      <?php if ($secureMode): ?>
        <div id="secureBar" class="fixed top-0 left-0 right-0 z-50 bg-gray-900 text-white px-4 py-2 flex items-center justify-between" style="user-select: none;">
          <div class="font-semibold">Secure Examination Mode</div>
          <div class="flex items-center gap-4">
            <div>Time Left: <span id="timeLeft">--:--</span></div>
            <div>Warnings: <span id="violationCount">0</span>/<span id="violationMax"><?php echo (int)$maxViolations; ?></span></div>
          </div>
        </div>
        <div id="secureOverlay" class="fixed inset-0 z-50 bg-black bg-opacity-80 text-white flex items-center justify-center" style="user-select: none;">
          <div class="max-w-lg w-full px-6">
            <div class="text-2xl font-bold mb-3">Start Examination</div>
            <div class="text-sm text-gray-200 mb-5">To continue, you must enter fullscreen. Leaving fullscreen or switching apps/tabs will be recorded as a violation.</div>
            <button type="button" id="startSecureExamBtn" class="btn btn-primary w-full">Enter Fullscreen & Start</button>
          </div>
        </div>
      <?php endif; ?>
      <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-3xl font-bold">Applicant Assessment</h1>
            <p class="text-gray-600">Select a posted examination and take the assessment.</p>
          </div>
          <div>
            <?php if ($exam): ?>
              <div></div>
            <?php endif; ?>
          </div>
        </div>

          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="mb-6">
              <h2 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($exam['title'] ?? 'Assessment'); ?></h2>
              <div class="mt-2 text-sm text-gray-600">
                <div>Department: <?php echo htmlspecialchars($exam['department'] ?? '-'); ?></div>
                <div>Role: <?php echo htmlspecialchars($selectedRole !== '' ? $selectedRole : '-'); ?></div>
                <div>Duration: <?php echo htmlspecialchars((string)($exam['duration'] ?? '')); ?> minutes</div>
                <div>Passing Score: <?php echo htmlspecialchars((string)($exam['passing_score'] ?? '')); ?>%</div>
              </div>
            </div>

            <form method="POST" class="space-y-8">
              <input type="hidden" name="exam_id" value="<?php echo (int)$exam['id']; ?>">
              <input type="hidden" name="time_taken" id="timeTakenInput" value="0">
              <input type="hidden" name="violation_count" id="violationCountInput" value="0">

              <?php foreach ($questions as $idx => $q): ?>
                <?php
                  $qid = (int)($q['id'] ?? 0);
                  $type = (string)($q['question_type'] ?? '');
                  $points = (int)($q['points'] ?? 1);
                  if ($points <= 0) { $points = 1; }
                  $options = normalize_options($q['options'] ?? '');
                ?>

                <div class="border border-gray-200 rounded-lg p-5">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <div class="text-sm text-gray-500">Question <?php echo (int)($q['question_number'] ?? ($idx + 1)); ?> â€¢ <?php echo htmlspecialchars($type); ?></div>
                      <div class="text-lg font-medium text-gray-800 mt-1"><?php echo htmlspecialchars($q['question_text'] ?? ''); ?></div>
                    </div>
                    <div class="text-sm font-medium text-gray-600 whitespace-nowrap"><?php echo $points; ?> pt</div>
                  </div>

                  <div class="mt-4">
                    <?php if ($type === 'multiple'): ?>
                      <?php foreach ($options as $optIdx => $opt): ?>
                        <label class="flex items-center gap-3 py-1">
                          <input class="radio" type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($opt); ?>">
                          <span><?php echo htmlspecialchars($opt); ?></span>
                        </label>
                      <?php endforeach; ?>
                    <?php elseif ($type === 'truefalse'): ?>
                      <?php $tf = !empty($options) ? $options : ['True', 'False']; ?>
                      <?php foreach ($tf as $opt): ?>
                        <label class="flex items-center gap-3 py-1">
                          <input class="radio" type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($opt); ?>">
                          <span><?php echo htmlspecialchars($opt); ?></span>
                        </label>
                      <?php endforeach; ?>
                    <?php elseif ($type === 'shortanswer' || $type === 'identification'): ?>
                      <input class="input input-bordered w-full" type="text" name="answers[<?php echo $qid; ?>]" placeholder="Your answer" autocomplete="off">
                    <?php else: ?>
                      <textarea class="textarea textarea-bordered w-full" name="answers[<?php echo $qid; ?>]" rows="3" placeholder="Your answer"></textarea>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="flex justify-end">
                <button class="btn btn-primary" type="submit">
                  <i class="fas fa-paper-plane mr-2"></i>Submit Assessment
                </button>
              </div>
            </form>
          </div>
      </div>
    </div>
  <?php else: ?>
      <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-3xl font-bold">Applicant Assessment</h1>
            <p class="text-gray-600">Provide your target department and applied role to view available assessments.</p>
          </div>
        </div>

        <?php if ($selectedDepartment === '' || $selectedRole === '' || $applicantName === ''): ?>
          <div class="flex items-center justify-center" style="min-height: 60vh;">
            <div class="bg-white border border-gray-200 rounded-lg p-8 w-full" style="max-width: 520px;">
            <form method="POST" class="space-y-4">
              <input type="hidden" name="context_submit" value="1">
              <div class="form-control">
                <label class="label"><span class="label-text font-medium">Name</span></label>
                <input class="input input-bordered" type="text" name="applicant_name" required value="<?php echo htmlspecialchars($applicantName); ?>" placeholder="Enter your full name">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text font-medium">Target Department</span></label>
                <select class="select select-bordered" name="department" id="departmentSelect" required>
                  <option value="" disabled selected>Select Department</option>
                  <?php foreach (array_keys($department_roles) as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $dept))); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text font-medium">Applied Role / Position</span></label>
                <select class="select select-bordered" name="role" id="roleSelect" required disabled>
                  <option value="" disabled selected>Select Department First</option>
                </select>
              </div>
              <button class="btn btn-primary w-full" type="submit">Continue</button>
            </form>
            </div>
          </div>
        <?php else: ?>
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="overflow-x-auto">
              <table class="table table-zebra">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Questions</th>
                    <th>Duration</th>
                    <th>Passing</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($postedExams)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-gray-500 py-8">No examinations assigned for the selected role.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($postedExams as $e): ?>
                      <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($e['title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($e['department'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($selectedRole); ?></td>
                        <td><?php echo (int)($e['question_count'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars((string)($e['duration'] ?? '')); ?> min</td>
                        <td><?php echo htmlspecialchars((string)($e['passing_score'] ?? '')); ?>%</td>
                        <td class="text-right">
                          <a class="btn btn-sm btn-primary" href="applicant_assessment.php?department=<?php echo urlencode($selectedDepartment); ?>&role=<?php echo urlencode($selectedRole); ?>&exam_id=<?php echo (int)$e['id']; ?>&secure=1">
                            Take Examination
                          </a>
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

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function () {
      if (!window.Swal || Swal.__hrPatched) return;

      const origFire = Swal.fire.bind(Swal);
      Swal.fire = function () {
        let opts = null;
        if (arguments.length === 1 && arguments[0] && typeof arguments[0] === 'object') {
          opts = Object.assign({}, arguments[0]);
        } else {
          opts = {
            title: arguments[0],
            html: arguments[1],
            icon: arguments[2]
          };
        }

        try {
          const openDialogs = Array.from(document.querySelectorAll('dialog[open]'));
          const topDialog = openDialogs.length ? openDialogs[openDialogs.length - 1] : null;
          if (topDialog && !opts.target) {
            opts.target = topDialog;
          }
        } catch (e) {
        }

        if (typeof opts.heightAuto === 'undefined') {
          opts.heightAuto = false;
        }

        return origFire(opts);
      };

      Swal.__hrPatched = true;
    })();

    const departmentRoles = <?php echo json_encode($department_roles, JSON_UNESCAPED_UNICODE); ?>;
    const deptSelect = document.getElementById('departmentSelect');
    const roleSelect = document.getElementById('roleSelect');

    function populateRoles(dept) {
      if (!roleSelect) return;
      roleSelect.innerHTML = '';

      const roles = departmentRoles[dept] || [];
      if (!roles.length) {
        roleSelect.disabled = true;
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No roles available';
        roleSelect.appendChild(opt);
        return;
      }

      roleSelect.disabled = false;
      const first = document.createElement('option');
      first.value = '';
      first.disabled = true;
      first.selected = true;
      first.textContent = 'Select Role / Position';
      roleSelect.appendChild(first);

      roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = r;
        roleSelect.appendChild(opt);
      });
    }

    if (deptSelect && roleSelect) {
      deptSelect.addEventListener('change', (e) => populateRoles(e.target.value));
    }
  </script>
  <script>
    (function () {
      const form = document.querySelector('form[method="POST"].space-y-8');
      const timeInput = document.getElementById('timeTakenInput');
      if (!form || !timeInput) return;
      const startedAt = Date.now();
      let allowNavigation = false;

      const secureMode = <?php echo $secureMode ? 'true' : 'false'; ?>;
      const examId = <?php echo (int)($exam['id'] ?? 0); ?>;
      const maxViolations = <?php echo (int)$maxViolations; ?>;
      const examDurationMinutes = <?php echo (int)($exam['duration'] ?? 0); ?>;
      let violations = 0;
      let isSubmitting = false;
      let forceSubmit = false;
      let countdownInterval = null;
      let remainingSeconds = Math.max(0, (Number(examDurationMinutes) || 0) * 60);

      const violationCountEl = document.getElementById('violationCount');
      const violationCountInput = document.getElementById('violationCountInput');
      const timeLeftEl = document.getElementById('timeLeft');

      function formatTime(totalSeconds) {
        const s = Math.max(0, Math.floor(Number(totalSeconds) || 0));
        const m = Math.floor(s / 60);
        const r = s % 60;
        return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
      }

      function renderTime() {
        if (timeLeftEl) timeLeftEl.textContent = formatTime(remainingSeconds);
      }

      function stopTimer() {
        if (countdownInterval) {
          clearInterval(countdownInterval);
          countdownInterval = null;
        }
      }

      function autoSubmit(reason) {
        if (isSubmitting) return;
        isSubmitting = true;
        forceSubmit = true;
        allowNavigation = true;
        stopTimer();

        const isTime = reason === 'time';
        const title = isTime ? 'Time is up' : 'Auto-submitting';
        const text = isTime
          ? 'Time limit reached. Your assessment will be submitted now.'
          : ('You violated ' + maxViolations + ' warnings. Your assessment will be submitted now.');

        Swal.fire({
          title,
          text,
          icon: 'error',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false
        });

        setTimeout(() => {
          try { form.requestSubmit(); } catch (e) { form.submit(); }
        }, 500);
      }

      function setViolations(v) {
        violations = v;
        if (violationCountEl) violationCountEl.textContent = String(violations);
        if (violationCountInput) violationCountInput.value = String(violations);
      }

      function logViolation(type, details) {
        if (!secureMode || !examId) return;
        const payload = new URLSearchParams();
        payload.append('ajax', '1');
        payload.append('log_violation', '1');
        payload.append('exam_id', String(examId));
        payload.append('violation_type', String(type || 'unknown'));
        payload.append('details', String(details || ''));

        try {
          if (navigator.sendBeacon) {
            const blob = new Blob([payload.toString()], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(window.location.href, blob);
            return;
          }
        } catch (e) {
        }

        try {
          fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString(),
            keepalive: true
          });
        } catch (e) {
        }
      }

      function warn(type, details) {
        if (!secureMode) return;
        setViolations(violations + 1);
        logViolation(type, details);

        if (violations >= maxViolations) {
          autoSubmit('violations');
          return;
        }

        Swal.fire({
          title: 'Warning',
          text: 'Violation detected. Warnings: ' + violations + '/' + maxViolations,
          icon: 'warning',
          confirmButtonText: 'Continue',
          allowOutsideClick: false
        });
      }

      function tryEnterFullscreen() {
        const el = document.documentElement;
        if (!el) return Promise.reject(new Error('no element'));
        const req = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (!req) return Promise.reject(new Error('fullscreen not supported'));
        return req.call(el);
      }

      function isFullscreenActive() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
      }

      function lockDownUI() {
        const overlay = document.getElementById('secureOverlay');
        if (overlay) overlay.style.display = 'none';
        const bar = document.getElementById('secureBar');
        if (bar) bar.style.display = '';

        renderTime();
        if (remainingSeconds > 0 && !countdownInterval) {
          countdownInterval = setInterval(() => {
            if (allowNavigation || isSubmitting) {
              stopTimer();
              return;
            }
            remainingSeconds -= 1;
            renderTime();
            if (remainingSeconds <= 0) {
              remainingSeconds = 0;
              renderTime();
              autoSubmit('time');
            }
          }, 1000);
        }

        let lastAppSwitchAt = 0;
        function appSwitchWarn(details) {
          const now = Date.now();
          if (now - lastAppSwitchAt < 800) return;
          lastAppSwitchAt = now;
          warn('app_switch', details || 'App/tab switched');
        }

        window.addEventListener('blur', () => {
          setTimeout(() => {
            if (document.hidden) appSwitchWarn('Attempted to switch apps/tabs');
          }, 0);
        });

        document.addEventListener('visibilitychange', () => {
          if (document.hidden) appSwitchWarn('Attempted to switch apps/tabs');
        });

        document.addEventListener('copy', (e) => {
          try {
            const sel = (window.getSelection && window.getSelection()) ? String(window.getSelection().toString() || '') : '';
            if (sel.trim().length > 0) {
              e.preventDefault();
              warn('copy', 'Attempted to copy selected text');
            }
          } catch (err) {
            e.preventDefault();
            warn('copy', 'Attempted to copy');
          }
        }, true);

        document.addEventListener('fullscreenchange', () => {
          if (!isFullscreenActive()) {
            const overlay2 = document.getElementById('secureOverlay');
            if (overlay2) overlay2.style.display = '';
          }
        });
      }

      if (secureMode) {
        setViolations(0);
        const startBtn = document.getElementById('startSecureExamBtn');
        if (startBtn) {
          startBtn.addEventListener('click', () => {
            tryEnterFullscreen()
              .then(() => {
                lockDownUI();
              })
              .catch(() => {
                Swal.fire({
                  title: 'Fullscreen required',
                  text: 'Please allow fullscreen to continue the examination.',
                  icon: 'warning',
                  allowOutsideClick: false,
                  allowEscapeKey: false
                });
              });
          });
        }
      }

      try {
        history.pushState(null, '', window.location.href);
      } catch (e) {}

      window.addEventListener('popstate', () => {
        if (allowNavigation) return;
        Swal.fire({
          title: 'Assessment in progress',
          text: 'You must finish the assessment. Please continue and submit your answers.',
          icon: 'warning',
          confirmButtonText: 'Continue',
          allowOutsideClick: false,
          allowEscapeKey: false
        }).then(() => {
          try {
            history.pushState(null, '', window.location.href);
          } catch (e) {}
        });
      });

      form.addEventListener('submit', () => {
        const seconds = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
        timeInput.value = String(seconds);
        stopTimer();
      });

      form.addEventListener('submit', (e) => {
        if (forceSubmit) {
          allowNavigation = true;
          return;
        }
        const fields = form.querySelectorAll('input[name^="answers["], textarea[name^="answers["]');
        const groups = new Map();
        fields.forEach((el) => {
          const m = String(el.name || '').match(/^answers\[(\d+)\]/);
          if (!m) return;
          const qid = m[1];
          if (!groups.has(qid)) groups.set(qid, []);
          groups.get(qid).push(el);
        });

        const missing = [];
        groups.forEach((els, qid) => {
          const hasCheckbox = els.some((el) => el.tagName === 'INPUT' && el.type === 'checkbox');
          const hasRadio = els.some((el) => el.tagName === 'INPUT' && el.type === 'radio');

          if (hasCheckbox) {
            if (!els.some((el) => el.checked)) missing.push(qid);
            return;
          }
          if (hasRadio) {
            if (!els.some((el) => el.checked)) missing.push(qid);
            return;
          }

          const hasValue = els.some((el) => String(el.value || '').trim() !== '');
          if (!hasValue) missing.push(qid);
        });

        if (missing.length > 0) {
          e.preventDefault();
          const first = missing[0];
          const target = form.querySelector(`[name^="answers[${first}]"], [name^="answers[${first}][]"]`);
          if (target && typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          Swal.fire({
            title: 'Incomplete Assessment',
            text: 'Please answer all questions before submitting.',
            icon: 'warning',
            confirmButtonText: 'OK',
            allowOutsideClick: false
          });
          return;
        }

        allowNavigation = true;
      });
    })();
  </script>
  <?php if ($resultSummary): ?>
    <script>
      Swal.fire({
        title: 'Assessment Submitted',
        text: 'Your assessment has been submitted successfully.',
        icon: 'success',
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then(() => {
        window.location.href = 'assessment_uploaded.php';
      });
    </script>
  <?php endif; ?>
 <?php if (!$hideShell): ?>
     </div>
   </div>
 <?php endif; ?>
<script>
    lucide.createIcons();
  </script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
