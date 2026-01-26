<?php
session_start();

require_once __DIR__ . '/USM/db.php';

$conn = usm_db_connect('learning_db');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

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
    $stmt = $conn->prepare("SELECT * FROM exam_repository WHERE id = ? AND status = 'posted' LIMIT 1");
    $stmt->bind_param('i', $examId);
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

    $stmt = $conn->prepare("SELECT * FROM exam_repository WHERE id = ? AND status = 'posted' LIMIT 1");
    $stmt->bind_param('i', $postedExamId);
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

        $earned = 0;
        $total = 0;

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

            if ($type === 'multiple') {
                $userAns = $answers[$qid] ?? [];
                if (!is_array($userAns)) {
                    $userAns = [$userAns];
                }

                $normUser = array_map(fn($v) => mb_strtolower(trim((string)$v)), $userAns);
                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);

                sort($normUser);
                sort($normCorrect);

                $isCorrect = ($normUser === $normCorrect) && !empty($normCorrect);
            } elseif ($type === 'truefalse') {
                $userAns = $answers[$qid] ?? '';
                $user = mb_strtolower(trim((string)$userAns));

                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);
                $isCorrect = in_array($user, $normCorrect, true) && $user !== '';
            } elseif ($type === 'shortanswer' || $type === 'identification') {
                $userAns = $answers[$qid] ?? '';
                $user = mb_strtolower(trim((string)$userAns));

                $normCorrect = array_map(fn($v) => mb_strtolower(trim((string)$v)), $correct);
                $isCorrect = in_array($user, $normCorrect, true) && $user !== '';
            }

            if ($isCorrect) {
                $earned += $points;
            }
        }

        $percent = $total > 0 ? round(($earned / $total) * 100, 2) : 0.0;
        $passingScore = isset($exam['passing_score']) ? (float)$exam['passing_score'] : 0.0;
        $passed = $percent >= $passingScore;

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
$postedSql = "SELECT er.*, COUNT(erq.id) AS question_count
              FROM exam_repository er
              LEFT JOIN exam_repository_questions erq ON er.id = erq.exam_id
              WHERE er.status = 'posted'
              GROUP BY er.id
              ORDER BY er.created_at DESC";
$postRes = $conn->query($postedSql);
if ($postRes) {
    while ($row = $postRes->fetch_assoc()) {
        $postedExams[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Assessment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="CSS/sidebar.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include 'USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include 'USM/navbar.php'; ?>

      <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-3xl font-bold">Employee Assessment</h1>
            <p class="text-gray-600">Select a posted examination and take the assessment.</p>
          </div>
          <div>
            <?php if ($exam): ?>
              <a class="btn btn-ghost" href="employee_assessment.php"><i class="fas fa-arrow-left mr-2"></i>Back</a>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($exam && !empty($questions)): ?>
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="mb-6">
              <h2 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($exam['title'] ?? 'Assessment'); ?></h2>
              <div class="mt-2 text-sm text-gray-600">
                <div>Department: <?php echo htmlspecialchars($exam['department'] ?? '-'); ?></div>
                <div>Duration: <?php echo htmlspecialchars((string)($exam['duration'] ?? '')); ?> minutes</div>
                <div>Passing Score: <?php echo htmlspecialchars((string)($exam['passing_score'] ?? '')); ?>%</div>
              </div>
            </div>

            <form method="POST" class="space-y-8">
              <input type="hidden" name="exam_id" value="<?php echo (int)$exam['id']; ?>">

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
                      <div class="text-sm text-gray-500">Question <?php echo (int)($q['question_number'] ?? ($idx + 1)); ?> • <?php echo htmlspecialchars($type); ?></div>
                      <div class="text-lg font-medium text-gray-800 mt-1"><?php echo htmlspecialchars($q['question_text'] ?? ''); ?></div>
                    </div>
                    <div class="text-sm font-medium text-gray-600 whitespace-nowrap"><?php echo $points; ?> pt</div>
                  </div>

                  <div class="mt-4">
                    <?php if ($type === 'multiple'): ?>
                      <?php foreach ($options as $optIdx => $opt): ?>
                        <label class="flex items-center gap-3 py-1">
                          <input class="checkbox" type="checkbox" name="answers[<?php echo $qid; ?>][]" value="<?php echo htmlspecialchars($opt); ?>">
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
        <?php else: ?>
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="overflow-x-auto">
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
                      <td colspan="6" class="text-center text-gray-500 py-8">No posted examinations found.</td>
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
                            Take Assessment
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
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if ($resultSummary): ?>
    <script>
      Swal.fire({
        title: <?php echo json_encode(($resultSummary['passed'] ? 'Passed' : 'Failed')); ?>,
        html: <?php echo json_encode(
          'Score: <b>' . $resultSummary['earned'] . ' / ' . $resultSummary['total'] . '</b><br>' .
          'Percent: <b>' . $resultSummary['percent'] . '%</b><br>' .
          'Passing Score: <b>' . $resultSummary['passing_score'] . '%</b>'
        ); ?>,
        icon: <?php echo json_encode(($resultSummary['passed'] ? 'success' : 'error')); ?>,
        confirmButtonText: 'OK'
      });
    </script>
  <?php endif; ?>
</body>
</html>
