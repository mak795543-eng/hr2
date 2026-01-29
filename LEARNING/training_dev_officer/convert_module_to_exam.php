<?php
session_start();

require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('hr2_learning_db');
if ($conn->connect_error) {
    $_SESSION['ai_generated_exam_error'] = 'Database connection failed.';
    header('Location: create_examination.php');
    exit;
}

$moduleId = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;
$questionCount = isset($_GET['question_count']) ? (int) $_GET['question_count'] : 10;
if ($questionCount <= 0) {
    $questionCount = 10;
}
if ($questionCount > 25) {
    $questionCount = 25;
}

if ($moduleId <= 0) {
    $_SESSION['ai_generated_exam_error'] = 'Module ID is required.';
    header('Location: examination_repository.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM learning_modules WHERE id = ?');
if (!$stmt) {
    $_SESSION['ai_generated_exam_error'] = 'Failed to prepare module query.';
    $conn->close();
    header('Location: examination_repository.php');
    exit;
}

$stmt->bind_param('i', $moduleId);

try {
    $stmt->execute();
} catch (Throwable $e) {
    $_SESSION['ai_generated_exam_error'] = 'Failed to load module data.';
    $stmt->close();
    $conn->close();
    header('Location: examination_repository.php');
    exit;
}

$result = $stmt->get_result();
$module = $result ? $result->fetch_assoc() : null;
$stmt->close();
$conn->close();

if (!$module) {
    $_SESSION['ai_generated_exam_error'] = 'Module not found.';
    header('Location: examination_repository.php');
    exit;
}

$title = (string) ($module['title'] ?? '');
$topic = (string) ($module['topic'] ?? '');
$department = (string) ($module['department'] ?? '');
$roles = (string) ($module['roles'] ?? '');

$parts = [];
foreach (['content', 'learning_objectives', 'key_points'] as $field) {
    if (!array_key_exists($field, $module)) {
        continue;
    }
    $val = (string) ($module[$field] ?? '');
    if ($val !== '') {
        $plain = html_entity_decode(strip_tags($val), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/', ' ', $plain);
        $plain = trim($plain);
        if ($plain !== '') {
            $parts[] = $plain;
        }
    }
}

$material = trim(implode("\n\n", $parts));
if ($material === '') {
    $_SESSION['ai_generated_exam_error'] = 'This module has no readable text content to convert. (reason=empty_material)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'empty_material',
        'module_id' => $moduleId
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$maxChars = 14000;
if (mb_strlen($material, 'UTF-8') > $maxChars) {
    $material = mb_substr($material, 0, $maxChars, 'UTF-8');
}

$ollamaUrl = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/chat';
$ollamaModel = getenv('OLLAMA_MODEL') ?: 'llama3.1:8b';

$system = "You are an exam generator. Return ONLY valid JSON. No markdown, no explanations.";
$user = "Create {$questionCount} exam questions based strictly on the provided training module content. Make the questions realistic and job-relevant. Mix question types across: multiple (multiple choice, 4 options, single correct), truefalse, shortanswer, identification. Avoid trivia not in the material. If the material contains procedures or safety/compliance rules, include scenario-based questions.\n\nReturn JSON with this exact schema:\n{\n  \"questions\": [\n    {\n      \"type\": \"multiple\"|\"truefalse\"|\"shortanswer\"|\"identification\",\n      \"question_text\": string,\n      \"options\": [string,string,string,string] (only for type=multiple or type=truefalse; for truefalse must be [\"True\",\"False\"]),\n      \"correct_answers\": [string] (exactly 1 item; for multiple/truefalse must match one of the options),\n      \"points\": number\n    }\n  ]\n}\n\nModule Title: {$title}\nTopic: {$topic}\nDepartment: {$department}\nRoles: {$roles}\n\nTraining Material:\n{$material}";

$payload = [
    'model' => $ollamaModel,
    'stream' => false,
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user]
    ],
    'options' => [
        'temperature' => 0.7
    ]
];

$ch = curl_init($ollamaUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 90);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlErr) {
    $_SESSION['ai_generated_exam_error'] = 'AI service is not reachable. Start Ollama (ollama serve) and try again. (reason=curl_error)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'curl_error',
        'http_code' => $httpCode,
        'curl_error' => $curlErr
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    $_SESSION['ai_generated_exam_error'] = 'AI service returned an error (HTTP ' . $httpCode . '). Make sure the model exists (ollama pull ' . $ollamaModel . ') and try again. (reason=http_error)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'http_error',
        'http_code' => $httpCode,
        'response_snippet' => mb_substr((string) $response, 0, 1500, 'UTF-8')
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$data = json_decode($response, true);
$content = $data['message']['content'] ?? '';
if (!is_string($content) || trim($content) === '') {
    $_SESSION['ai_generated_exam_error'] = 'AI service returned an empty response. Try again or use a different model. (reason=empty_content)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'empty_content',
        'http_code' => $httpCode,
        'response_snippet' => mb_substr((string) $response, 0, 1500, 'UTF-8')
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$startObj = strpos($content, '{');
$startArr = strpos($content, '[');
if ($startObj === false && $startArr === false) {
    $_SESSION['ai_generated_exam_error'] = 'AI response was not valid JSON. Try again or use a different model. (reason=no_json_start)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'no_json_start',
        'content_snippet' => mb_substr((string) $content, 0, 1500, 'UTF-8')
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$useArray = false;
if ($startObj === false || ($startArr !== false && $startArr < $startObj)) {
    $start = $startArr;
    $end = strrpos($content, ']');
    $useArray = true;
} else {
    $start = $startObj;
    $end = strrpos($content, '}');
}

if ($start === false || $end === false || $end <= $start) {
    $_SESSION['ai_generated_exam_error'] = 'AI response was not valid JSON. Try again or use a different model. (reason=no_json_object_found)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'no_json_object_found',
        'content_snippet' => mb_substr((string) $content, 0, 1500, 'UTF-8')
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$jsonText = substr($content, $start, $end - $start + 1);
$exam = json_decode($jsonText, true);

if (!is_array($exam)) {
    $snippet = htmlspecialchars(mb_substr((string) $jsonText, 0, 250, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $_SESSION['ai_generated_exam_error'] = 'AI response was not valid JSON. Try again or use a different model. (reason=json_decode_failed) Snippet: ' . $snippet;
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'json_decode_failed',
        'json_snippet' => mb_substr((string) $jsonText, 0, 1500, 'UTF-8'),
        'use_array' => $useArray
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$isList = static function (array $arr): bool {
    $keys = array_keys($arr);
    return $keys === range(0, count($arr) - 1);
};

$questions = null;
if (isset($exam['questions']) && is_array($exam['questions'])) {
    $questions = $exam['questions'];
} elseif (isset($exam['items']) && is_array($exam['items'])) {
    $questions = $exam['items'];
} elseif (isset($exam['data']) && is_array($exam['data']) && isset($exam['data']['questions']) && is_array($exam['data']['questions'])) {
    $questions = $exam['data']['questions'];
} elseif ($isList($exam)) {
    $questions = $exam;
}

if (!is_array($questions)) {
    $snippet = htmlspecialchars(mb_substr((string) $jsonText, 0, 250, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $_SESSION['ai_generated_exam_error'] = 'AI response JSON schema is invalid. Try again or use a different model. (reason=invalid_schema) Snippet: ' . $snippet;
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'invalid_schema',
        'json_snippet' => mb_substr((string) $jsonText, 0, 1500, 'UTF-8'),
        'use_array' => $useArray
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$normalized = ['questions' => []];
foreach ($questions as $q) {
    if (!is_array($q)) {
        continue;
    }

    $typeRaw = '';
    foreach (['type', 'question_type', 'questionType', 'questiontype'] as $k) {
        if (isset($q[$k])) {
            $typeRaw = (string) $q[$k];
            break;
        }
    }
    $type = strtolower(trim((string) $typeRaw));

    $questionText = '';
    foreach (['question_text', 'question', 'text', 'prompt'] as $k) {
        if (isset($q[$k])) {
            $questionText = trim((string) $q[$k]);
            if ($questionText !== '') {
                break;
            }
        }
    }
    if ($questionText === '' && isset($q['question'])) {
        $questionText = trim((string) $q['question']);
    }
    $points = isset($q['points']) ? (int) $q['points'] : (isset($q['score']) ? (int) $q['score'] : 1);

    if ($points <= 0) {
        $points = 1;
    }

    $correctAnswers = [];
    foreach (['correct_answers', 'correct_answer', 'answer', 'answer_key', 'answerKey', 'expected_answer', 'expectedAnswer'] as $k) {
        if (!isset($q[$k])) {
            continue;
        }

        if (is_array($q[$k])) {
            foreach ($q[$k] as $ans) {
                $ans = trim((string) $ans);
                if ($ans !== '') {
                    $correctAnswers[] = $ans;
                }
            }
        } else {
            $ans = trim((string) $q[$k]);
            if ($ans !== '') {
                $correctAnswers[] = $ans;
            }
        }

        if (count($correctAnswers) > 0) {
            break;
        }
    }

    $options = [];
    $optionsRaw = null;
    foreach (['options', 'choices', 'choice', 'answers_options', 'answer_options'] as $k) {
        if (isset($q[$k])) {
            $optionsRaw = $q[$k];
            break;
        }
    }

    if (is_array($optionsRaw)) {
        foreach ($optionsRaw as $opt) {
            if (is_array($opt)) {
                $opt = (string) ($opt['text'] ?? $opt['value'] ?? $opt['option'] ?? '');
            }
            $opt = trim((string) $opt);
            $opt = preg_replace('/^[A-Da-d][\)|\.|\:|\-]\s*/', '', $opt);
            if ($opt !== '') {
                $options[] = $opt;
            }
        }
    } elseif (is_string($optionsRaw) && trim($optionsRaw) !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $optionsRaw);
        foreach ($lines as $line) {
            $line = trim((string) $line);
            $line = preg_replace('/^[A-Da-d][\)|\.|\:|\-]\s*/', '', $line);
            if ($line !== '') {
                $options[] = $line;
            }
        }
    }

    if ($questionText === '' || count($correctAnswers) === 0) {
        continue;
    }

    if ($type !== '') {
        $type = str_replace([' ', '-', '_'], '', $type);
    }

    if (in_array($type, ['mcq', 'multiplechoice', 'multiple'], true)) {
        $type = 'multiple';
    } elseif (in_array($type, ['tf', 'truefalse', 'trueorfalse', 'truefalsequestion', 'truefalsequiz'], true)) {
        $type = 'truefalse';
    } elseif (in_array($type, ['shortanswer', 'short', 'shortresponse'], true)) {
        $type = 'shortanswer';
    } elseif (in_array($type, ['identification', 'identify', 'identificationquestion'], true)) {
        $type = 'identification';
    }

    if ($type === '') {
        if (count($options) >= 2) {
            $opt0 = strtolower(trim((string) ($options[0] ?? '')));
            $opt1 = strtolower(trim((string) ($options[1] ?? '')));
            if (($opt0 === 'true' && $opt1 === 'false') || ($opt0 === 'false' && $opt1 === 'true')) {
                $type = 'truefalse';
            } else {
                $type = 'multiple';
            }
        } else {
            $type = 'shortanswer';
        }
    }

    $correct = $correctAnswers[0];
    $correctNormalized = trim($correct);
    $letterOnly = preg_replace('/[^A-Za-z]/', '', $correctNormalized);
    $letterOnly = strtoupper((string) $letterOnly);
    $letter = $letterOnly !== '' ? $letterOnly[0] : '';

    if ($type === 'truefalse') {
        $options = ['True', 'False'];
        $tf = strtolower($correctNormalized);
        if (in_array($tf, ['true', 't', 'yes'], true)) {
            $correctNormalized = 'True';
        } elseif (in_array($tf, ['false', 'f', 'no'], true)) {
            $correctNormalized = 'False';
        } elseif ($letter === 'A') {
            $correctNormalized = 'True';
        } elseif ($letter === 'B') {
            $correctNormalized = 'False';
        }

        if ($correctNormalized !== 'True' && $correctNormalized !== 'False') {
            continue;
        }
    }

    if ($type === 'truefalse') {
        // already normalized above
    }

    if ($type === 'multiple') {
        if (count($options) < 2) {
            continue;
        }

        if (in_array($letter, ['A','B','C','D'], true)) {
            $idx = ord($letter) - ord('A');
            if (isset($options[$idx])) {
                $correctNormalized = $options[$idx];
            }
        }

        if (ctype_digit($correctNormalized)) {
            $idx = (int) $correctNormalized;
            if ($idx > 0) {
                $idx = $idx - 1;
            }
            if (isset($options[$idx])) {
                $correctNormalized = $options[$idx];
            }
        }

        foreach ($options as $opt) {
            if (strcasecmp($opt, $correctNormalized) === 0) {
                $correctNormalized = $opt;
                break;
            }
        }

        if (!in_array($correctNormalized, $options, true)) {
            continue;
        }
    }

    if ($type === 'shortanswer' || $type === 'identification') {
        $options = [];
    }

    $normalized['questions'][] = [
        'type' => $type,
        'question_text' => $questionText,
        'options' => $options,
        'correct_answers' => [$correctNormalized],
        'points' => $points
    ];
}

if (count($normalized['questions']) === 0) {
    $_SESSION['ai_generated_exam_error'] = 'AI did not generate usable questions. Please try again (tip: ensure the module has clear text content and try a different model if needed). (reason=normalized_empty)';
    $_SESSION['ai_generated_exam_debug'] = [
        'reason' => 'normalized_empty',
        'json_snippet' => mb_substr((string) $jsonText, 0, 1500, 'UTF-8')
    ];
    header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId));
    exit;
}

$_SESSION['ai_generated_exam'] = $normalized;
unset($_SESSION['ai_generated_exam_debug']);
header('Location: create_examination.php?module_id=' . urlencode((string) $moduleId) . '&ai_generated=1');
exit;
