<?php
header("Content-Type: application/json");
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

if ($HF_API_KEY === '' && $projectRoot) {
    $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
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
    echo json_encode([
        'error' => 'Missing HF_API_KEY. Set HF_API_KEY in the project root .env (e.g. HF_API_KEY=hf_...)'
    ]);
    exit;
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
    echo json_encode(["output" => normalizeQaOutput($content, $questionCount)]);
}
elseif (isset($response['choices'][0]['text'])) {
    $content = $response['choices'][0]['text'];
    echo json_encode(["output" => normalizeQaOutput($content, $questionCount)]);
}
elseif (isset($response['raw_text']) && isset($response['http_status']) && (int)$response['http_status'] >= 400) {
    echo json_encode([
        "error" => "HF HTTP error",
        "http_status" => $response['http_status'],
        "details" => $response['raw_text']
    ]);
}
elseif (isset($response['error'])) {
    $out = ["error" => "HF Error: " . $response['error']];
    if (isset($response['http_status'])) {
        $out['http_status'] = $response['http_status'];
    }
    echo json_encode($out);
}
else {
    // 🔍 Debug-safe fallback
    echo json_encode([
        "error" => "Unknown response format",
        "debug" => $response
    ]);
}
