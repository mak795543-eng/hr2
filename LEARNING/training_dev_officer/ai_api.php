<?php
ob_start();
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('html_errors', '0');

function ai_api_respond(array $payload, int $status = 200): void
{
    if (ob_get_length() !== false) {
        @ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e) return;
    $type = (int)($e['type'] ?? 0);
    if (!in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
    $msg = trim((string)($e['message'] ?? ''));
    if (strlen($msg) > 200) $msg = substr($msg, 0, 200) . '...';
    ai_api_respond(['error' => 'AI API crashed on the server.', 'details' => $msg], 500);
});

header("Content-Type: application/json");
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

try {
// Load HF_API_KEY from environment (.env) reliably
$projectRoot = realpath(__DIR__ . '/../../');
if ($projectRoot && file_exists($projectRoot . '/vendor/autoload.php')) {
    require_once $projectRoot . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
        $dotenv->safeLoad();
    }
}

$HF_API_KEY = getenv('HF_API_KEY') ?: ($_ENV['HF_API_KEY'] ?? ($_SERVER['HF_API_KEY'] ?? ''));

$envPath = $projectRoot ? ($projectRoot . DIRECTORY_SEPARATOR . '.env') : '';

if ($HF_API_KEY === '' && $envPath !== '') {
    if (is_file($envPath) && is_readable($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                $k = trim(substr($line, 0, $pos));
                if ($k !== 'HF_API_KEY') continue;
                $v = trim(substr($line, $pos + 1));
                $v = trim($v, "\"'");
                $HF_API_KEY = $v;
                break;
            }
        }
    }
}

if ($HF_API_KEY === '') {
    ai_api_respond([
        'error' => 'Missing HF_API_KEY. Set HF_API_KEY in the project root .env (e.g. HF_API_KEY=hf_...)',
        'diagnostics' => [
            'project_root' => $projectRoot ?: null,
            'env_path' => $envPath !== '' ? $envPath : null,
            'env_file_exists' => $envPath !== '' ? is_file($envPath) : false,
            'env_file_readable' => $envPath !== '' ? is_readable($envPath) : false,
            'source_getenv_present' => (getenv('HF_API_KEY') !== false && getenv('HF_API_KEY') !== ''),
            'source__ENV_present' => (isset($_ENV['HF_API_KEY']) && (string)$_ENV['HF_API_KEY'] !== ''),
            'source__SERVER_present' => (isset($_SERVER['HF_API_KEY']) && (string)$_SERVER['HF_API_KEY'] !== '')
        ]
    ], 500);
}

if (!function_exists('curl_init')) {
    ai_api_respond([
        'error' => 'Server is missing PHP cURL extension (curl_init not available).'
    ], 500);
}

// Try multiple HuggingFace endpoints
$MODEL_URLS = [
    "https://router.huggingface.co/v1/chat/completions",
    "https://api-inference.huggingface.co/models/HuggingFaceTB/SmolLM3-3B/v1/chat/completions",
    "https://huggingface.co/api/inference/v1/chat/completions"
];
$MODEL_NAME = "HuggingFaceTB/SmolLM3-3B";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['lesson']) || trim($input['lesson']) === "") {
    echo json_encode(["error" => "No lesson provided"]);
    exit;
}

$questionType = isset($input['questionType']) ? trim((string)$input['questionType']) : 'mixed';
$questionType = $questionType !== '' ? $questionType : 'mixed';

$questionCount = isset($input['questionCount']) ? (int)$input['questionCount'] : 5;
if ($questionCount < 1) {
    $questionCount = 1;
}
if ($questionCount > 50) {
    $questionCount = 50;
}

$typeInstruction = '';
if ($questionType === 'true_false') {
    $typeInstruction = 'Create True/False questions only.';
} elseif ($questionType === 'multiple_choice') {
    $typeInstruction = 'Create Multiple Choice questions only. Each question must have options A, B, C, D.';
} elseif ($questionType === 'identification') {
    $typeInstruction = 'Create Identification questions only (the student must provide a term/name/concept).';
} elseif ($questionType === 'fill_in_the_blank') {
    $typeInstruction = 'Create Fill-in-the-Blank questions only. Use exactly one blank like ____ per question.';
} elseif ($questionType === 'short_answer') {
    $typeInstruction = 'Create Short Answer questions only.';
} else {
    $typeInstruction = 'Create a mixed set of question types.';
}

function callHFTextGeneration(string $prompt, int $maxNewTokens): array
{
    global $HF_API_KEY;

    $url = "https://api-inference.huggingface.co/models/HuggingFaceTB/SmolLM3-3B";
    $payload = [
        "inputs" => $prompt,
        "parameters" => [
            "max_new_tokens" => $maxNewTokens,
            "temperature" => 0.7,
            "return_full_text" => false
        ],
        "options" => [
            "wait_for_model" => true
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $HF_API_KEY",
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $raw = is_string($response) ? $response : '';
    $decoded = null;
    if ($response !== false && $curlErrno === 0) {
        $decoded = json_decode($raw, true);
    }

    if (is_array($decoded)) {
        if (isset($decoded['error']) && is_string($decoded['error'])) {
            return [
                "error" => $decoded['error'],
                "http_status" => (int)$httpStatus,
                "raw_text" => $raw
            ];
        }

        if (isset($decoded[0]['generated_text']) && is_string($decoded[0]['generated_text'])) {
            return [
                "choices" => [
                    ["message" => ["content" => $decoded[0]['generated_text']]]
                ],
                "http_status" => (int)$httpStatus,
                "raw_text" => $raw
            ];
        }
    }

    return [
        "error" => "HF text-generation failed. " . ($curlError ? ("cURL: " . $curlError) : ("HTTP " . (int)$httpStatus)),
        "http_status" => (int)$httpStatus,
        "raw_text" => $raw
    ];
}

function callHF($payload, $retry = 3) {
    global $HF_API_KEY, $MODEL_URLS;
    $lastCurlError = '';
    $lastHttpStatus = 0;
    $lastRawResponse = '';

    foreach ($MODEL_URLS as $MODEL_URL) {
        $ch = curl_init($MODEL_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $HF_API_KEY",
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $lastCurlError = $curlError ?: $lastCurlError;
        $lastHttpStatus = (int)$httpStatus ?: $lastHttpStatus;
        $lastRawResponse = is_string($response) ? $response : $lastRawResponse;

        // If successful, return the response
        if ($response !== false && $curlErrno === 0) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                // Model loading → retry
                if (isset($decoded['error']) && str_contains(strtolower((string)$decoded['error']), 'loading') && $retry > 0) {
                    sleep(5);
                    return callHF($payload, $retry - 1);
                }
                
                if ($httpStatus >= 400 && !isset($decoded['error'])) {
                    $decoded['error'] = 'HF HTTP error';
                }
                $decoded['http_status'] = $httpStatus;
                $decoded['raw_text'] = $response;
                return $decoded;
            }
        }
        
        // If this endpoint failed, try the next one
        error_log("Failed endpoint $MODEL_URL: $curlError");
    }
    
    // All endpoints failed
    $fallback = callHFTextGeneration((string)($payload['messages'][1]['content'] ?? ''), (int)($payload['max_tokens'] ?? 300));
    if (isset($fallback['choices'][0]['message']['content'])) {
        return $fallback;
    }
    return [
        "error" => "All HuggingFace endpoints failed. Last error: " . $lastCurlError,
        "http_status" => $lastHttpStatus,
        "raw_text" => $lastRawResponse
    ];
}

function looksLikeQuestionText($text) {
    $t = strtolower($text);
    if (str_contains($t, 'true or false')) {
        return true;
    }
    if (str_contains($text, '?')) {
        return true;
    }
    if (str_contains($text, '____')) {
        return true;
    }
    if (preg_match('/\b(a\.|b\.|c\.|d\.)\b/i', $text)) {
        return true;
    }
    return false;
}

function fallbackGenerateQaOutput(string $lesson, string $questionType, int $questionCount): string
{
    $lesson = trim($lesson);
    if ($lesson === '') {
        return "Questions:\n1. Please provide lesson content.\n\nAnswer Key:\n1. N/A";
    }

    $stop = [
        'the' => true, 'and' => true, 'for' => true, 'with' => true, 'that' => true, 'this' => true, 'from' => true,
        'are' => true, 'was' => true, 'were' => true, 'have' => true, 'has' => true, 'had' => true, 'will' => true,
        'your' => true, 'you' => true, 'their' => true, 'they' => true, 'them' => true, 'into' => true, 'over' => true,
        'when' => true, 'what' => true, 'where' => true, 'which' => true, 'while' => true, 'can' => true, 'cannot' => true,
        'not' => true, 'only' => true, 'also' => true, 'should' => true, 'must' => true, 'may' => true, 'might' => true,
        'about' => true, 'after' => true, 'before' => true, 'during' => true, 'then' => true, 'than' => true, 'such' => true,
        'use' => true, 'using' => true, 'used' => true
    ];

    $sentences = preg_split('/(?<=[\.\?\!])\s+/', preg_replace("/\r\n|\n|\r/", " ", $lesson)) ?: [];
    $sentences = array_values(array_filter(array_map('trim', $sentences), static function ($s) {
        return $s !== '' && strlen($s) >= 25;
    }));
    if (!$sentences) {
        $sentences = [preg_replace("/\r\n|\n|\r/", " ", $lesson)];
    }

    $allWords = [];
    foreach ($sentences as $s) {
        if (preg_match_all('/\b[a-zA-Z][a-zA-Z0-9\-]{3,}\b/', $s, $m)) {
            foreach ($m[0] as $w) {
                $lw = strtolower($w);
                if (isset($stop[$lw])) continue;
                $allWords[] = $w;
            }
        }
    }
    $pool = array_values(array_unique($allWords));
    if (count($pool) < 4) {
        $pool = array_values(array_unique(array_merge($pool, ['procedure', 'safety', 'policy', 'standard', 'quality', 'process', 'training', 'compliance'])));
    }

    $typeMap = [
        'multiple_choice' => 'multiple_choice',
        'true_false' => 'true_false',
        'identification' => 'identification',
        'fill_in_the_blank' => 'fill_in_the_blank',
        'short_answer' => 'short_answer'
    ];

    $types = [];
    if (isset($typeMap[$questionType])) {
        $types = [$typeMap[$questionType]];
    } else {
        $types = ['multiple_choice', 'true_false', 'identification', 'fill_in_the_blank', 'short_answer'];
    }

    $questionsOut = [];
    $answersOut = [];

    for ($i = 1; $i <= $questionCount; $i++) {
        $type = $types[($i - 1) % count($types)];
        $s = $sentences[($i - 1) % count($sentences)];
        $s = trim(preg_replace('/\s+/', ' ', $s));

        $key = '';
        if (preg_match_all('/\b[a-zA-Z][a-zA-Z0-9\-]{3,}\b/', $s, $m)) {
            $cands = [];
            foreach ($m[0] as $w) {
                $lw = strtolower($w);
                if (isset($stop[$lw])) continue;
                $cands[] = $w;
            }
            usort($cands, static function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });
            $key = $cands[0] ?? '';
        }
        if ($key === '' && isset($pool[0])) {
            $key = (string)$pool[0];
        }

        if ($type === 'true_false') {
            $q = "True or False: " . rtrim($s, ".?!") . ".";
            $questionsOut[] = $i . ". " . $q;
            $answersOut[] = $i . ". True";
            continue;
        }

        if ($type === 'short_answer') {
            $q = "In 1-2 sentences, explain this idea from the lesson: " . rtrim($s, ".?!") . ".";
            $questionsOut[] = $i . ". " . $q;
            $answersOut[] = $i . ". " . ($key !== '' ? $key : 'Answers may vary.');
            continue;
        }

        if ($type === 'identification') {
            $q = "Identification: Identify the key term related to this statement: " . rtrim($s, ".?!") . ".";
            $questionsOut[] = $i . ". " . $q;
            $answersOut[] = $i . ". " . ($key !== '' ? $key : 'N/A');
            continue;
        }

        $blanked = $s;
        if ($key !== '') {
            $blanked = preg_replace('/\b' . preg_quote($key, '/') . '\b/u', '____', $s, 1) ?: $s;
        }

        if ($type === 'fill_in_the_blank') {
            $q = "Fill in the blank: " . rtrim($blanked, ".?!") . ".";
            $questionsOut[] = $i . ". " . $q;
            $answersOut[] = $i . ". " . ($key !== '' ? $key : 'N/A');
            continue;
        }

        $q = "Multiple Choice: Which term best completes this statement? " . rtrim($blanked, ".?!") . ".";
        $opts = [$key !== '' ? $key : 'N/A'];
        foreach ($pool as $w) {
            if (count($opts) >= 4) break;
            if ($key !== '' && strcasecmp($w, $key) === 0) continue;
            $opts[] = $w;
        }
        while (count($opts) < 4) {
            $opts[] = 'N/A';
        }
        shuffle($opts);
        $correctIdx = 0;
        foreach ($opts as $idx => $opt) {
            if ($key !== '' && strcasecmp($opt, $key) === 0) {
                $correctIdx = $idx;
                break;
            }
        }
        $letters = ['A', 'B', 'C', 'D'];
        $qLines = [$i . ". " . $q];
        for ($j = 0; $j < 4; $j++) {
            $qLines[] = "   " . $letters[$j] . ". " . $opts[$j];
        }
        $questionsOut[] = implode("\n", $qLines);
        $answersOut[] = $i . ". " . $letters[$correctIdx] . " - " . $opts[$correctIdx];
    }

    return "Questions:\n" . implode("\n", $questionsOut) . "\n\nAnswer Key:\n" . implode("\n", $answersOut);
}

function normalizeQaOutput($text, $questionCount) {
    $lines = preg_split("/\r\n|\n|\r/", (string)$text);
    $questions = [];
    $answers = [];
    $mode = '';
    $currenttanu = null;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }

        if (preg_match('/^questions\s*:\s*$/i', $trimmed)) {
            $mode = 'questions';
            $currenttanu = null;
            continue;
        }
        if (preg_match('/^answer\s*key\s*:\s*$/i', $trimmed)) {
            $mode = 'answers';
            $currenttanu = null;
            continue;
        }

        if (preg_match('/^(\d{1,3})\s*[\.|\)|\-|:]\s*(.+)$/', $trimmed, $m)) {
            $idx = (int)$m[1];
            $body = trim($m[2]);

            if ($idx < 1 || $idx > $questionCount) {
                $currenttanu = null;
                continue;
            }

            if ($mode === 'answers' && looksLikeQuestionText($body) && !isset($questions[$idx])) {
                $mode = 'questions';
            }

            if ($mode === 'questions') {
                if (!isset($questions[$idx])) {
                    $questions[$idx] = $body;
                }
                $currenttanu = $idx;
                continue;
            }

            if ($mode === 'answers') {
                if (!isset($answers[$idx])) {
                    $answers[$idx] = $body;
                }
                $currenttanu = $idx;
                continue;
            }

            $currenttanu = null;
            continue;
        }

        if ($mode === 'questions' && $currenttanu !== null && isset($questions[$currenttanu])) {
            $questions[$currenttanu] .= "\n" . $trimmed;
            continue;
        }
        if ($mode === 'answers' && $currenttanu !== null && isset($answers[$currenttanu])) {
            $answers[$currenttanu] .= "\n" . $trimmed;
            continue;
        }
    }

    if (count($questions) === 0) {
        return $text;
    }

    $out = "Questions:\n";
    for ($i = 1; $i <= $questionCount; $i++) {
        if (isset($questions[$i])) {
            $out .= $i . ". " . trim($questions[$i]) . "\n";
        }
    }
    $out .= "\nAnswer Key:\n";
    for ($i = 1; $i <= $questionCount; $i++) {
        if (isset($answers[$i])) {
            $out .= $i . ". " . trim($answers[$i]) . "\n";
        }
    }

    return trim($out);
}

$prompt =
    "You are an expert educator. Based on the provided lesson content, generate $questionCount accurate and relevant exam questions. " .
    $typeInstruction . "\n\n" .
    "CRITICAL REQUIREMENTS:\n" .
    "- Questions MUST be directly based on facts, concepts, and information from the lesson content\n" .
    "- Do NOT invent information or make assumptions beyond what's in the lesson\n" .
    "- Each question must test understanding of the actual lesson material\n" .
    "- Questions should be clear, specific, and answerable from the lesson alone\n\n" .
    "Return two sections ONLY, using this exact format:\n" .
    "Questions:\n" .
    "1. ...\n" .
    "2. ...\n" .
    "...\n\n" .
    "Answer Key:\n" .
    "1. ...\n" .
    "2. ...\n" .
    "...\n\n" .
    "Rules:\n" .
    "- The answer key numbering must match the questions exactly.\n" .
    "- For Multiple Choice, the answer key must be the letter (A/B/C/D) and the correct option text.\n" .
    "- For True/False, the answer key must be True or False.\n" .
    "- Keep answers concise.\n" .
    "- Ensure all questions are factually correct based on the lesson.\n\n" .
    "Lesson Content:\n" . $input['lesson'];

$maxTokens = 250 + ($questionCount * 80);
if ($maxTokens < 300) {
    $maxTokens = 300;
}
if ($maxTokens > 1200) {
    $maxTokens = 1200;
}

$response = callHF([
    "model" => $MODEL_NAME,
    "messages" => [
        ["role" => "system", "content" => "You are a helpful teacher."],
        ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.7,
    "max_tokens" => $maxTokens
]);

// ✅ HANDLE ALL KNOWN RESPONSE TYPES
if (isset($response['choices'][0]['message']['content'])) {
    $content = $response['choices'][0]['message']['content'];
    ai_api_respond(["output" => normalizeQaOutput($content, $questionCount)], 200);
}
elseif (isset($response['choices'][0]['text'])) {
    $content = $response['choices'][0]['text'];
    ai_api_respond(["output" => normalizeQaOutput($content, $questionCount)], 200);
}
elseif (isset($response['error'])) {
    $fallback = fallbackGenerateQaOutput((string)($input['lesson'] ?? ''), (string)$questionType, (int)$questionCount);
    ai_api_respond([
        "output" => $fallback,
        "warning" => "AI service unavailable; used fallback generator.",
        "http_status" => $response['http_status'] ?? null,
        "details" => $response['error']
    ], 200);
}
else {
    // 🔍 Debug-safe fallback
    $fallback = fallbackGenerateQaOutput((string)($input['lesson'] ?? ''), (string)$questionType, (int)$questionCount);
    ai_api_respond([
        "output" => $fallback,
        "warning" => "AI service unavailable; used fallback generator.",
        "http_status" => $response['http_status'] ?? null,
        "details" => "Unknown response format"
    ], 200);
}
} catch (Throwable $e) {
    $m = trim((string)$e->getMessage());
    if (strlen($m) > 200) $m = substr($m, 0, 200) . '...';
    ai_api_respond(['error' => 'AI API failed on the server.', 'details' => $m], 500);
}
