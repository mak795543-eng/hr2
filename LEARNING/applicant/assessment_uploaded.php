<?php
session_start();
require_once __DIR__ . '/../db.php';
$conn = usm_db_connect('learning_db');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assessment Uploaded</title>
  <script src="https://cdn.tailwindcss.com"></script>
         <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../CSS/sidebar.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>
<!-- xczx -->

    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>


      <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-center min-h-[60vh]">
          <div class="bg-white border border-gray-200 rounded-lg p-8 max-w-md w-full text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-check text-green-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Assessment Uploaded</h2>
            <p class="text-gray-600 mb-6">Your assessment has been submitted successfully. You will be notified of the results.</p>
            <div class="flex flex-col gap-3">
              <button class="btn btn-primary" onclick="window.location.href='applicant_assessment.php'">
                <i class="fas fa-arrow-left mr-2"></i>Back to Assessments
              </button>
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<script>
    lucide.createIcons();
  </script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>

