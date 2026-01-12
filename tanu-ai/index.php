<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>TANu AI Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="card w-96 bg-white shadow-xl p-6 rounded-2xl">
    <h1 class="text-2xl font-bold text-center mb-4">📘 TANu AI</h1>
    <p class="text-center mb-4">Upload a PDF to generate exam questions</p>
    
    <form action="upload.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-3">
      <input type="file" name="pdf_file" class="file-input file-input-bordered w-full" required>
      <button type="submit" class="btn btn-primary w-full">Upload & Generate</button>
    </form>
  </div>
</body>
</html>
