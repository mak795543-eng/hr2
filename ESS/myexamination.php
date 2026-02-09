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

$employeeId = (string)($_SESSION['employee_id'] ?? ($_SESSION['user_id'] ?? ''));

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

$completedExamIds = [];
if ($employeeId !== '' && $roleLower !== '' && $conn) {
    $stmt = $conn->prepare(
        "SELECT DISTINCT exam_id
         FROM exam_results
         WHERE employee_id = ?
           AND taker_type = 'employee'"
    );
    if ($stmt) {
        $stmt->bind_param('s', $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $completedExamIds[(int)($row['exam_id'] ?? 0)] = true;
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <?php $isCompleted = isset($completedExamIds[(int)($e['id'] ?? 0)]); ?>
                <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
                  <div class="card-body">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h2 class="font-semibold text-gray-900 leading-tight"><?php echo htmlspecialchars((string)($e['title'] ?? 'Untitled')); ?></h2>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars((string)($e['module_title'] ?? '')); ?></p>
                      </div>
                      <?php if ($isCompleted): ?>
                        <span class="badge badge-success badge-outline">Completed</span>
                      <?php else: ?>
                        <span class="badge badge-info badge-outline">Posted</span>
                      <?php endif; ?>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                      <span class="badge badge-outline"><?php echo htmlspecialchars((string)($e['department'] ?? '')); ?></span>
                      <span class="badge badge-outline"><?php echo (int)($e['question_count'] ?? 0); ?> Questions</span>
                    </div>

                    <p class="text-xs text-gray-500 mt-3">Duration: <?php echo htmlspecialchars((string)($e['duration'] ?? '')); ?> minutes</p>

                    <div class="mt-4 flex justify-end">
                      <button class="btn btn-sm btn-primary" <?php echo $isCompleted ? 'disabled' : ''; ?> onclick="<?php echo $isCompleted ? '' : ('takeExam(' . (int)($e['id'] ?? 0) . ')'); ?>">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        <span class="ml-2"><?php echo $isCompleted ? 'Completed' : 'Take Exam'; ?></span>
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

  <dialog id="terms_modal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Close">✕</button>
      </form>

      <h3 class="font-bold text-lg text-gray-900">Examination Terms and Conditions</h3>
      <p class="text-sm text-gray-600 mt-2">
        Please read the following Terms and Conditions carefully before proceeding. These terms govern your access to and participation in this examination.
      </p>

      <div class="mt-5 space-y-4 text-sm text-gray-700 leading-relaxed">
        <div>
          <div class="font-semibold text-gray-900">1. Purpose of the Examination</div>
          <div class="mt-1">This examination is administered for assessment, evaluation, training, and/or employment-related purposes as determined by the organization.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">2. Eligibility and Authorization</div>
          <div class="mt-1">Only authorized users may take the examination. Account sharing is strictly prohibited.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">3. Examination Rules and Conduct</div>
          <div class="mt-1">You must take the exam individually. Cheating, collaboration, plagiarism, or any form of unauthorized assistance is prohibited.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">4. Time Limit and Attempts</div>
          <div class="mt-1">The examination is time-bound. Attempts are limited unless explicitly authorized by the organization.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">5. Data Privacy and Confidentiality</div>
          <div class="mt-1">Personal data may be collected and processed in accordance with company policy and applicable data privacy laws. Data is used solely for evaluation, compliance, and related administrative purposes.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">6. System Integrity and Violations</div>
          <div class="mt-1">Any attempt to manipulate, bypass, or disrupt the examination system may result in disqualification and may lead to disciplinary action in accordance with organizational policy.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">7. Technical Responsibility Disclaimer</div>
          <div class="mt-1">The organization is not liable for issues arising from your internet connection, power interruptions, or device-related problems.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">8. Results and Evaluation Policy</div>
          <div class="mt-1">Results are considered final unless a formal review process is made available. The organization reserves the right to invalidate results if violations are detected.</div>
        </div>

        <div>
          <div class="font-semibold text-gray-900">9. User Agreement Clause</div>
          <div class="mt-1">Proceeding with the examination signifies your full acceptance of these Terms and Conditions.</div>
        </div>
      </div>

      <div class="mt-6 border-t pt-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <label class="flex items-start gap-3 cursor-pointer">
            <input id="terms_accept_checkbox" type="checkbox" class="checkbox checkbox-sm mt-1" />
            <span class="text-sm text-gray-700">I have read and agree to the Terms and Conditions of the Examination.</span>
          </label>

          <button id="accept_all_start_btn" class="btn btn-primary" disabled>
              Start Examination
          </button>
        </div>
        <div class="text-xs text-gray-500 mt-2">You must accept the Terms and Conditions before you can proceed.</div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>

  <dialog id="exam_modal" class="modal">
    <div class="modal-box max-w-6xl p-0 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <h3 class="font-bold text-2xl mb-2" id="modal_title">Examination</h3>
            <div class="flex flex-wrap gap-3 text-sm" id="modal_meta_badges">
              <span class="bg-white/20 px-3 py-1 rounded-full flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4"></i>
                <span id="meta_department">Department</span>
              </span>
              <span class="bg-white/20 px-3 py-1 rounded-full flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span id="meta_roles">Roles</span>
              </span>
              <span class="bg-white/20 px-3 py-1 rounded-full flex items-center gap-2">
                <i data-lucide="timer" class="w-4 h-4"></i>
                <span id="meta_duration">0</span> min
              </span>
              <span class="bg-white/20 px-3 py-1 rounded-full flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                Passing <span id="meta_passing">0</span>%
              </span>
            </div>
          </div>
          <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" aria-label="Close">✕</button>
          </form>
        </div>
      </div>

      <div class="p-6 max-h-[60vh] overflow-y-auto">
        <div class="mb-6">
          <div class="flex items-center mb-4">
            <i data-lucide="file-text" class="w-5 h-5 text-blue-600 mr-2"></i>
            <h4 class="font-semibold text-lg text-gray-800">Examination Preview</h4>
            <span class="ml-auto text-sm text-gray-500" id="question_count">0 Questions</span>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h5 class="font-semibold text-blue-800 mb-2 flex items-center">
              <i data-lucide="info" class="w-4 h-4 mr-2"></i>
              Instructions:
            </h5>
            <ul class="text-blue-700 text-sm space-y-1">
              <li>• Read each question carefully before answering</li>
              <li>• Select the best answer for each question</li>
              <li>• Ensure all answers are final before submitting</li>
            </ul>
          </div>

          <div id="modal_questions" class="space-y-4">
            <div class="text-center py-8">
              <span class="loading loading-spinner loading-sm"></span>
              <p class="mt-2 text-gray-500">Loading examination questions...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>

  <script>
    let pendingExamId = null;

    const completedFromRedirect = <?php echo (isset($_GET['completed']) && (string)$_GET['completed'] === '1') ? 'true' : 'false'; ?>;

    function escapeHtml(s) {
      return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function takeExam(examId) {
      pendingExamId = examId;
      const termsModal = document.getElementById('terms_modal');
      const cb = document.getElementById('terms_accept_checkbox');
      const btn = document.getElementById('accept_all_start_btn');

      if (cb) cb.checked = false;
      if (btn) btn.disabled = true;

      termsModal.showModal();
    }

    function viewExam(examId) {
      const modal = document.getElementById('exam_modal');
      const titleEl = document.getElementById('modal_title');
      const metaEl = document.getElementById('modal_meta');
      const qEl = document.getElementById('modal_questions');

      titleEl.textContent = 'Loading...';
      metaEl.textContent = '';
      qEl.innerHTML = '<div class="flex items-center gap-2 text-gray-500"><span class="loading loading-spinner loading-sm"></span><span>Loading examination...</span></div>';

      try { window.hrCloseDialogs && window.hrCloseDialogs(); } catch(e) {}
      if (typeof modal.showModal === 'function') {
        modal.showModal();
      } else {
        modal.setAttribute('open','');
      }

      fetch(`myexamination.php?exam_id=${examId}`)
        .then(r => r.json())
        .then(data => {
          if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Failed to load');
          }

          const exam = data.exam || {};
          titleEl.textContent = exam.title || 'Untitled';
          document.getElementById('meta_department').textContent = String(exam.department || '');
          document.getElementById('meta_roles').textContent = String(exam.roles || '');
          document.getElementById('meta_duration').textContent = String(exam.duration || '0');
          document.getElementById('meta_passing').textContent = String(exam.passing_score || '0');

          const questions = Array.isArray(exam.questions) ? exam.questions : [];
          document.getElementById('question_count').textContent = `${questions.length} Questions`;
          if (!questions.length) {
            qEl.innerHTML = `
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <i data-lucide="help-circle" class="w-8 h-8 text-yellow-500 mx-auto mb-3"></i>
                <p class="text-yellow-700 font-medium">No questions found in this examination.</p>
              </div>
            `;
            return;
          }

          function getBadge(type) {
            switch(String(type||'').toLowerCase()) {
              case 'multiple': return { cls: 'bg-blue-100 text-blue-800', icon: 'list' , label: 'Multiple Choice' };
              case 'truefalse': return { cls: 'bg-purple-100 text-purple-800', icon: 'toggle-left' , label: 'True/False' };
              case 'shortanswer': return { cls: 'bg-green-100 text-green-800', icon: 'align-left' , label: 'Short Answer' };
              case 'identification': return { cls: 'bg-orange-100 text-orange-800', icon: 'type' , label: 'Identification' };
              default: return { cls: 'bg-gray-100 text-gray-800', icon: 'help-circle' , label: String(type||'') };
            }
          }

          const html = questions.map((q, idx) => {
            const n = Number(q.question_number || (idx+1));
            const text = escapeHtml(q.question_text);
            const badge = getBadge(q.question_type);
            let answerKey = { correctAnswers: [], points: Number(q.points||1) };
            try { if (q.answer_key) answerKey = JSON.parse(q.answer_key); } catch(_){}
            let options = [];
            try { if (q.options) options = JSON.parse(q.options); } catch(_){}
            const points = Number(answerKey.points||q.points||1);

            let body = '';
            if ((String(q.question_type).toLowerCase() === 'multiple' || String(q.question_type).toLowerCase() === 'truefalse') && options.length) {
              body += `<div class="space-y-2 mb-3">`;
              options.forEach((opt, oi) => {
                const isCorrect = Array.isArray(answerKey.correctAnswers) && answerKey.correctAnswers.includes(opt);
                const optionClass = isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200';
                const letter = String.fromCharCode(65 + oi);
                body += `
                  <div class="flex items-start p-2 rounded border ${optionClass}">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full ${isCorrect ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'} text-xs font-semibold mr-2 mt-0.5">
                      ${letter}
                    </span>
                    <span class="text-sm ${isCorrect ? 'text-green-700 font-medium' : 'text-gray-700'}">${escapeHtml(String(opt||''))}</span>
                    ${isCorrect ? '<span class="ml-auto text-green-600 text-xs font-semibold"><i data-lucide="check" class="w-3 h-3 mr-1 inline-block"></i>Correct</span>' : ''}
                  </div>
                `;
              });
              body += `</div>`;
            } else if (String(q.question_type).toLowerCase() === 'shortanswer' || String(q.question_type).toLowerCase() === 'identification') {
              const correctAnswer = (Array.isArray(answerKey.correctAnswers) && answerKey.correctAnswers.length > 0) ? answerKey.correctAnswers[0] : (q.expected_answer || 'No answer provided');
              body += `
                <div class="mb-3">
                  <div class="bg-green-50 border border-green-200 rounded p-3">
                    <div class="flex items-center mb-1">
                      <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600 mr-2"></i>
                      <span class="font-medium text-green-800 text-sm">Expected Answer:</span>
                    </div>
                    <p class="text-green-700 text-sm pl-6">${escapeHtml(String(correctAnswer||''))}</p>
                  </div>
                </div>
              `;
            }

            return `
              <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex justify-between items-start mb-3">
                  <div class="flex items-center">
                    <span class="font-bold text-lg text-gray-800 mr-3">Q${n}</span>
                    <span class="px-2 py-1 rounded-full text-xs ${badge.cls} flex items-center gap-1">
                      <i data-lucide="${badge.icon}" class="w-3.5 h-3.5"></i>
                      ${badge.label}
                    </span>
                  </div>
                  <div class="flex items-center">
                    <span class="text-xs text-gray-500 mr-2">Points:</span>
                    <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-full">${points}</span>
                  </div>
                </div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">${text || 'No question text'}</h3>
                ${body}
              </div>
            `;
          }).join('');

          qEl.innerHTML = html;
          setTimeout(() => lucide.createIcons(), 50);
        })
        .catch(err => {
          titleEl.textContent = 'Error';
          metaEl.textContent = '';
          qEl.textContent = err.message || 'Failed to load examination.';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      if (completedFromRedirect && window.Swal) {
        Swal.fire({
          title: 'Examination Completed',
          text: 'You have finished the examination.',
          icon: 'success',
          confirmButtonText: 'OK',
          allowOutsideClick: false
        });
      }

      const params = new URLSearchParams(window.location.search);
      const viewId = parseInt(params.get('view') || '0', 10);
      if (Number.isFinite(viewId) && viewId > 0) {
        viewExam(viewId);
      }

      const cb = document.getElementById('terms_accept_checkbox');
      const btn = document.getElementById('accept_all_start_btn');
      const termsModal = document.getElementById('terms_modal');

      if (cb && btn) {
        cb.addEventListener('change', () => {
          btn.disabled = !cb.checked;
        });
      }

      if (btn && termsModal) {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          if (!cb || !cb.checked) return;
          if (!pendingExamId) return;
          try {
            termsModal.close();
          } catch (e2) {}

          // Use the same assessment UI as applicants (employee mode)
          const url = `../LEARNING/applicant/applicant_assessment.php?exam_id=${encodeURIComponent(String(pendingExamId))}&secure=1&taker_type=employee`;
          window.location.href = url;
        });
      }
    });

    lucide.createIcons();
  </script>
</body>
</html>
