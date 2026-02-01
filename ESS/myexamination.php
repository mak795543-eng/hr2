<?php
session_start();

require_once __DIR__ . '/../LEARNING/db.php';

$conn = usm_db_connect('hr2_learning_db');
if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed');
}
$conn->set_charset('utf8mb4');

$conn->query(
    "CREATE TABLE IF NOT EXISTS exam_repository_assignments (\n" .
    "  id INT PRIMARY KEY AUTO_INCREMENT,\n" .
    "  exam_id INT NOT NULL,\n" .
    "  audience ENUM('applicant', 'employee') NOT NULL,\n" .
    "  department VARCHAR(100) NOT NULL,\n" .
    "  role VARCHAR(255) NOT NULL,\n" .
    "  status ENUM('active', 'inactive') DEFAULT 'active',\n" .
    "  assigned_by VARCHAR(50),\n" .
    "  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
    "  UNIQUE KEY uniq_exam_audience_dept_role (exam_id, audience, department, role),\n" .
    "  INDEX idx_audience_dept_role (audience, department, role)\n" .
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$role = trim((string)($_SESSION['role'] ?? ''));
$roleLower = strtolower($role);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['exam_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $examId = (int)($_GET['exam_id'] ?? 0);
    if ($examId <= 0 || $roleLower === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT er.*, lm.title AS module_title\n         FROM exam_repository er\n         LEFT JOIN learning_modules lm ON er.module_id = lm.id\n         INNER JOIN exam_repository_assignments a\n           ON a.exam_id = er.id\n          AND a.audience = 'employee'\n          AND a.status = 'active'\n         WHERE er.id = ?\n           AND er.status = 'posted'\n           AND LOWER(a.role) = ?\n         LIMIT 1"
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
        exit;
    }

    $stmt->bind_param('is', $examId, $roleLower);
    $stmt->execute();
    $res = $stmt->get_result();
    $exam = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($exam)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Examination not found']);
        exit;
    }

    $qStmt = $conn->prepare('SELECT * FROM exam_repository_questions WHERE exam_id = ? ORDER BY question_number');
    if ($qStmt) {
        $qStmt->bind_param('i', $examId);
        $qStmt->execute();
        $qRes = $qStmt->get_result();
        $questions = [];
        while ($qRes && ($row = $qRes->fetch_assoc())) {
            $questions[] = $row;
        }
        $qStmt->close();
        $exam['questions'] = $questions;
    } else {
        $exam['questions'] = [];
    }

    echo json_encode(['success' => true, 'exam' => $exam]);
    exit;
}

$exams = [];
if ($roleLower !== '') {
    $stmt = $conn->prepare(
        "SELECT er.id, er.title, er.description, er.department, er.roles, er.duration, er.passing_score, er.created_at,\n                COUNT(eq.id) AS question_count, lm.title AS module_title\n         FROM exam_repository er\n         INNER JOIN exam_repository_assignments a\n           ON a.exam_id = er.id\n          AND a.audience = 'employee'\n          AND a.status = 'active'\n         LEFT JOIN exam_repository_questions eq ON eq.exam_id = er.id\n         LEFT JOIN learning_modules lm ON lm.id = er.module_id\n         WHERE er.status = 'posted'\n           AND LOWER(a.role) = ?\n         GROUP BY er.id\n         ORDER BY er.created_at DESC"
    );
    if ($stmt) {
        $stmt->bind_param('s', $roleLower);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $exams[] = $row;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Examinations</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-6xl mx-auto">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">My Examinations</h1>
              <p class="text-sm text-gray-500">Examinations assigned for your role: <span class="font-semibold"><?php echo htmlspecialchars($role !== '' ? $role : 'Unknown'); ?></span></p>
            </div>
          </div>

          <?php if ($roleLower === ''): ?>
            <div class="mt-6 alert alert-warning">
              <span>Your session role is missing. Please log in again.</span>
            </div>
          <?php elseif (count($exams) === 0): ?>
            <div class="mt-6 card bg-base-100 border border-base-200 shadow-sm">
              <div class="card-body">
                <div class="flex items-center gap-2 text-gray-700">
                  <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                  <h2 class="font-semibold">No examinations yet</h2>
                </div>
                <p class="text-sm text-gray-500 mt-2">There are no posted examinations assigned to your role right now.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <?php foreach ($exams as $e): ?>
                <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
                  <div class="card-body">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h2 class="font-semibold text-gray-900 leading-tight"><?php echo htmlspecialchars((string)($e['title'] ?? 'Untitled')); ?></h2>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars((string)($e['module_title'] ?? '')); ?></p>
                      </div>
                      <span class="badge badge-info badge-outline">Posted</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                      <span class="badge badge-outline"><?php echo htmlspecialchars((string)($e['department'] ?? '')); ?></span>
                      <span class="badge badge-outline"><?php echo (int)($e['question_count'] ?? 0); ?> Questions</span>
                    </div>

                    <p class="text-xs text-gray-500 mt-3">Duration: <?php echo htmlspecialchars((string)($e['duration'] ?? '')); ?> minutes</p>

                    <div class="mt-4 flex justify-end">
                      <button class="btn btn-sm btn-outline" onclick="viewExam(<?php echo (int)($e['id'] ?? 0); ?>)">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span class="ml-2">View</span>
                      </button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <dialog id="exam_modal" class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Close">✕</button>
      </form>

      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="font-bold text-lg" id="modal_title">Examination</h3>
          <p class="text-sm text-gray-500" id="modal_meta"></p>
        </div>
        <span class="badge badge-info badge-outline">Assigned</span>
      </div>

      <div class="mt-4" id="modal_questions"></div>

      <div class="modal-action">
        <form method="dialog">
          <button class="btn">Close</button>
        </form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>

  <script>
    function escapeHtml(s) {
      return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function viewExam(examId) {
      const modal = document.getElementById('exam_modal');
      const titleEl = document.getElementById('modal_title');
      const metaEl = document.getElementById('modal_meta');
      const qEl = document.getElementById('modal_questions');

      titleEl.textContent = 'Loading...';
      metaEl.textContent = '';
      qEl.innerHTML = '<div class="flex items-center gap-2 text-gray-500"><span class="loading loading-spinner loading-sm"></span><span>Loading examination...</span></div>';

      modal.showModal();

      fetch(`myexamination.php?exam_id=${examId}`)
        .then(r => r.json())
        .then(data => {
          if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Failed to load');
          }

          const exam = data.exam;
          titleEl.textContent = exam.title || 'Untitled';
          metaEl.textContent = `Department: ${exam.department || ''} | Duration: ${exam.duration || ''} mins | Passing: ${exam.passing_score || ''}%`;

          const questions = Array.isArray(exam.questions) ? exam.questions : [];
          if (!questions.length) {
            qEl.innerHTML = '<p class="text-sm text-gray-500">No questions found.</p>';
            return;
          }

          const html = questions.map(q => {
            const n = escapeHtml(q.question_number);
            const text = escapeHtml(q.question_text);
            return `<div class="border rounded-lg p-3 mb-3 bg-white"><div class="font-semibold">${n}. ${text}</div></div>`;
          }).join('');

          qEl.innerHTML = html;
        })
        .catch(err => {
          titleEl.textContent = 'Error';
          metaEl.textContent = '';
          qEl.textContent = err.message || 'Failed to load examination.';
        });
    }

    lucide.createIcons();
  </script>
</body>
</html>
