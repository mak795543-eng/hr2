<?php
session_start();
require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;

// 🔹 1. Ensure uploads folder exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 🔹 2. Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        die("❌ File upload failed. Please try again.");
    }

    $fileTmp  = $_FILES['pdf_file']['tmp_name'];
    $fileName = basename($_FILES['pdf_file']['name']);
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($fileTmp, $targetFile)) {
        die("❌ Failed to move uploaded file.");
    }

    // 🔹 3. Parse PDF
    $parser = new Parser();
    $pdf = $parser->parseFile($targetFile);
    $text = trim($pdf->getText());

    if ($text === "") {
        die("❌ No readable text found in PDF. Make sure it's not a scanned image.");
    }

    // 🔹 4. Send to OpenAI (example)
    $apiKey = "YOUR_OPENAI_API_KEY";
    $endpoint = "https://api.openai.com/v1/chat/completions";

    $prompt = "
You are an exam generator AI. Based ONLY on the following study material, create exactly 10 multiple-choice questions. 
Each question must have:
- The question itself
- 4 answer choices (A, B, C, D)
- Indicate the correct answer clearly.

Study Material:
$text
";

    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [[
            "role" => "user",
            "content" => $prompt
        ]],
        "temperature" => 0.7
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $output = $result['choices'][0]['message']['content'] ?? "❌ No output.";

    // 🔹 5. Display results
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>AI Generated Exam</title>
      <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 p-6">
      <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl p-6">
        <h1 class="text-2xl font-bold mb-4">📝 AI-Generated Exam Questions</h1>
        <div class="space-y-4 text-lg">
          <pre class="whitespace-pre-wrap"><?= htmlspecialchars($output) ?></pre>
        </div>
        <div class="mt-6">
          <a href="index.php" class="btn btn-primary">⬅ Back to Upload</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!-- 🔹 6. Simple upload form -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload PDF</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-xl mx-auto bg-white shadow-xl rounded-xl p-6">
        <h1 class="text-2xl font-bold mb-4">📄 Upload PDF for Exam Generation</h1>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="file" name="pdf_file" accept="application/pdf" required class="border p-2 rounded w-full">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Upload & Generate</button>
        </form>
    </div>
</body>
</html>
