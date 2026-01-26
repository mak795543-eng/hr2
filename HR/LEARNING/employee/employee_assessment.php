<?php
session_start();

require_once __DIR__ . '../../../../db.php';

$conn = usm_db_connect('learning_db');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

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
        taker_name VARCHAR(255) DEFAULT NULL,
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

function normalize_department_slug(string $v): string {
    $s = mb_strtolower(trim($v));
    if ($s === '') {
        return '';
    }

    if (str_contains($s, 'human') && str_contains($s, 'resource')) {
        return 'human-resources';
    }
    if (str_contains($s, 'front') && str_contains($s, 'office')) {
        return 'front-office';
    }
    if (str_contains($s, 'house')) {
        return 'housekeeping';
    }
    if (str_contains($s, 'food') || str_contains($s, 'beverage') || str_contains($s, 'f&b')) {
        return 'food-beverage';
    }
    if (str_contains($s, 'kitchen') || str_contains($s, 'culinary')) {
        return 'kitchen';
    }
    if (str_contains($s, 'sales') || str_contains($s, 'marketing')) {
        return 'sales-marketing';
    }
    if (str_contains($s, 'finance') || str_contains($s, 'accounting')) {
        return 'finance';
    }
    if (str_contains($s, 'engineering') || str_contains($s, 'maintenance')) {
        return 'engineering';
    }
    if (str_contains($s, 'security')) {
        return 'security';
    }

    $s = preg_replace('/\([^\)]*\)/', '', $s);
    $s = str_replace(['&', '/', '_'], ['and', '-', '-'], $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-');
}

$employeeId = (string)($_SESSION['employee_id'] ?? '');
$employeeRole = (string)($_SESSION['role'] ?? '');
$employeeDepartmentSlug = '';

if ($employeeId !== '') {
    $hrConn = usm_db_connect('hr2_soliera_usm');
    if (!$hrConn->connect_error) {
        $hrConn->set_charset('utf8mb4');
        $stmt = $hrConn->prepare('SELECT dept_name, role FROM department_accounts WHERE employee_id = ? LIMIT 1');
        $stmt->bind_param('s', $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            if (!empty($row['role'])) {
                $employeeRole = (string)$row['role'];
            }
            $employeeDepartmentSlug = normalize_department_slug((string)($row['dept_name'] ?? ''));
        }
    }
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

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

$exam = null;
$questions = [];

if ($examId > 0) {
    if ($employeeDepartmentSlug !== '' && $employeeRole !== '') {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'employee'
             AND era.status = 'active'
             AND era.department = ?
             AND era.role = ?
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('ssi', $employeeDepartmentSlug, $employeeRole, $examId);
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
}

$resultSummary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedExamId = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

    if ($employeeDepartmentSlug !== '' && $employeeRole !== '') {
        $stmt = $conn->prepare("SELECT er.*
            FROM exam_repository er
            INNER JOIN exam_repository_assignments era
              ON era.exam_id = er.id
             AND era.audience = 'employee'
             AND era.status = 'active'
             AND era.department = ?
             AND era.role = ?
            WHERE er.id = ? AND er.status = 'posted'
            LIMIT 1");
        $stmt->bind_param('ssi', $employeeDepartmentSlug, $employeeRole, $postedExamId);
        $stmt->execute();
        $res = $stmt->get_result();
        $exam = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }

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
        $employeeId = $_SESSION['employee_id'] ?? ($_SESSION['user_id'] ?? 'unknown');
        $passedInt = $passed ? 1 : 0;

        $attemptNumber = 1;
        $takerType = 'employee';
        $attemptStmt = $conn->prepare('SELECT COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt FROM exam_results WHERE employee_id = ? AND exam_id = ? AND taker_type = ?');
        $attemptStmt->bind_param('sis', $employeeId, $examId, $takerType);
        $attemptStmt->execute();
        $attemptRes = $attemptStmt->get_result();
        $attemptNumber = (int)(($attemptRes ? $attemptRes->fetch_assoc()['next_attempt'] ?? 1 : 1));
        if ($attemptNumber <= 0) {
            $attemptNumber = 1;
        }
        $attemptStmt->close();

        $insertStmt = $conn->prepare('INSERT INTO exam_results (employee_id, exam_id, score, total_questions, passed, time_taken, completed_at, attempt_number, taker_type, taker_department, taker_role) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)');
        $insertStmt->bind_param('sidiiiisss', $employeeId, $examId, $percent, $total, $passedInt, $timeTaken, $attemptNumber, $takerType, $employeeDepartmentSlug, $employeeRole);
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
if ($employeeDepartmentSlug !== '' && $employeeRole !== '') {
    $postedStmt = $conn->prepare("SELECT er.*, COUNT(erq.id) AS question_count
        FROM exam_repository er
        INNER JOIN exam_repository_assignments era
          ON era.exam_id = er.id
         AND era.audience = 'employee'
         AND era.status = 'active'
         AND era.department = ?
         AND era.role = ?
        LEFT JOIN exam_repository_questions erq ON er.id = erq.exam_id
        WHERE er.status = 'posted'
        GROUP BY er.id
        ORDER BY er.created_at DESC");
    $postedStmt->bind_param('ss', $employeeDepartmentSlug, $employeeRole);
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
<html lang="en" data-theme="corporate">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Assessment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../../CSS/sidebar.css">
  <link rel="stylesheet" href="../../CSS/learning_theme.css">
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
   <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../../USM/navbar.php'; ?>

      <?php if ($exam && !empty($questions)): ?>
      <div class="min-h-screen">
        <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-3xl font-bold">Employee Assessment</h1>
            <p class="text-gray-600">Take your assigned examination.</p>
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
                <div>Role: <?php echo htmlspecialchars($employeeRole !== '' ? $employeeRole : '-'); ?></div>
                <div>Duration: <?php echo htmlspecialchars((string)($exam['duration'] ?? '')); ?> minutes</div>
                <div>Passing Score: <?php echo htmlspecialchars((string)($exam['passing_score'] ?? '')); ?>%</div>
              </div>
            </div>

            <form method="POST" class="space-y-8">
              <input type="hidden" name="exam_id" value="<?php echo (int)$exam['id']; ?>">
              <input type="hidden" name="time_taken" id="timeTakenInput" value="0">

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
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="overflow-x-auto">
              <div class="mb-4">
                <div class="text-sm text-gray-600">Detected Department: <span class="font-medium"><?php echo htmlspecialchars($employeeDepartmentSlug !== '' ? $employeeDepartmentSlug : '-'); ?></span></div>
                <div class="text-sm text-gray-600">Detected Role: <span class="font-medium"><?php echo htmlspecialchars($employeeRole !== '' ? $employeeRole : '-'); ?></span></div>
              </div>
              <?php if ($employeeDepartmentSlug === '' || $employeeRole === ''): ?>
                <div class="text-center text-gray-600 py-8">Your account role/department could not be detected. Please contact HR/Administrator.</div>
              <?php else: ?>
              <table class="table table-zebra">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Questions</th>
                    <th>Duration</th>
                    <th>Passing</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($postedExams)): ?>
                    <tr>
                      <td colspan="6" class="text-center text-gray-500 py-8">No examinations assigned for your role.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($postedExams as $e): ?>
                      <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($e['title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($e['department'] ?? ''); ?></td>
                        <td><?php echo (int)($e['question_count'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars((string)($e['duration'] ?? '')); ?> min</td>
                        <td><?php echo htmlspecialchars((string)($e['passing_score'] ?? '')); ?>%</td>
                        <td class="text-right">
                          <a class="btn btn-sm btn-primary" href="employee_assessment.php?exam_id=<?php echo (int)$e['id']; ?>">
                            Take Examination
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
              <?php endif; ?>
            </div>
          </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

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

    (function () {
      const form = document.querySelector('form[method="POST"].space-y-8');
      const timeInput = document.getElementById('timeTakenInput');
      if (!form || !timeInput) return;
      const startedAt = Date.now();
      let allowNavigation = false;

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
      });

      form.addEventListener('submit', (e) => {
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
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>

