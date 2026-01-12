<?php
session_start();

if (!isset($_FILES['pdf_file'])) {
    die("❌ No file uploaded.");
}

// Save uploaded file
$targetDir = __DIR__ . "/uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$filename = time() . "_" . basename($_FILES['pdf_file']['name']);
$targetFile = $targetDir . $filename;

if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetFile)) {
    // Redirect to generate.php with filename
    header("Location: generate.php?file=" . urlencode($filename));
    exit;
} else {
    die("❌ Failed to upload file.");
}
