<?php
session_start();

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected department from filter
$selected_department = $_GET['department'] ?? 'all';
$selected_exam_department = $_GET['exam_department'] ?? 'all';

// Build SQL query for learning modules based on filter
if ($selected_department === 'all') {
    $sql = "SELECT * FROM learning_modules WHERE status = 'pending' ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM learning_modules WHERE status = 'pending' AND department = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_department);
}

// Execute query for learning modules
$pending_modules = [];
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $pending_modules[] = $row;
        }
    }
}

// **FIXED: Fetch pending examinations from EXAMINATIONS table, not exam_repository**
if ($selected_exam_department === 'all') {
    $exam_sql = "SELECT e.*, 
                        COUNT(eq.id) as question_count 
                 FROM examinations e
                 LEFT JOIN examination_questions eq ON e.id = eq.examination_id
                 WHERE e.status = 'pending' 
                 GROUP BY e.id
                 ORDER BY e.created_at DESC";
    $exam_stmt = $conn->prepare($exam_sql);
} else {
    $exam_sql = "SELECT e.*, 
                        COUNT(eq.id) as question_count 
                 FROM examinations e
                 LEFT JOIN examination_questions eq ON e.id = eq.examination_id
                 WHERE e.status = 'pending' AND e.department = ? 
                 GROUP BY e.id
                 ORDER BY e.created_at DESC";
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("s", $selected_exam_department);
}

// Execute query for examinations
$pending_examinations = [];
if ($exam_stmt->execute()) {
    $exam_result = $exam_stmt->get_result();
    if ($exam_result && $exam_result->num_rows > 0) {
        while($row = $exam_result->fetch_assoc()) {
            // Get questions for each examination
            $questions_sql = "SELECT * FROM examination_questions WHERE examination_id = ? ORDER BY question_number";
            $questions_stmt = $conn->prepare($questions_sql);
            $questions_stmt->bind_param("i", $row['id']);
            $questions_stmt->execute();
            $questions_result = $questions_stmt->get_result();
            $questions = [];
            
            while($question = $questions_result->fetch_assoc()) {
                $questions[] = $question;
            }
            $questions_stmt->close();
            
            $row['questions'] = $questions;
            $pending_examinations[] = $row;
        }
    }
}

// Get unique departments for filter dropdowns
$departments_sql = "SELECT DISTINCT department FROM learning_modules WHERE status = 'pending' ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = [];
if ($departments_result && $departments_result->num_rows > 0) {
    while($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// **FIXED: Get unique departments for examinations from EXAMINATIONS table**
$exam_departments_sql = "SELECT DISTINCT department FROM examinations WHERE status = 'pending' ORDER BY department";
$exam_departments_result = $conn->query($exam_departments_sql);
$exam_departments = [];
if ($exam_departments_result && $exam_departments_result->num_rows > 0) {
    while($row = $exam_departments_result->fetch_assoc()) {
        $exam_departments[] = $row['department'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Learning Module & Examination Review</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    /* Simplified SweetAlert2 Styling */
    .swal2-container {
      z-index: 2147483000 !important;
      position: fixed !important;
      inset: 0 !important;
    }

    .swal2-popup {
      z-index: 2147483005 !important;
      padding: 1.5rem !important;
      border-radius: 0.5rem !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
      max-width: 400px !important;
    }

    .swal2-title {
      font-size: 1.25rem !important;
      font-weight: 600 !important;
      margin-bottom: 1rem !important;
      color: #1f2937 !important;
    }

    .swal2-html-container {
      font-size: 0.95rem !important;
      color: #6b7280 !important;
      margin-bottom: 1.5rem !important;
    }

    .swal2-actions {
      display: flex !important;
      gap: 0.5rem !important;
      width: 100% !important;
      margin: 0 !important;
    }

    .swal2-confirm, 
    .swal2-cancel {
      flex: 1 !important;
      padding: 0.75rem 1rem !important;
      border-radius: 0.375rem !important;
      font-weight: 500 !important;
      font-size: 0.9rem !important;
      cursor: pointer !important;
      transition: all 0.2s !important;
      border: none !important;
      min-height: 44px !important;
    }

    .swal2-confirm {
      background-color: #3b82f6 !important;
      color: white !important;
    }

    .swal2-confirm:hover {
      background-color: #2563eb !important;
    }

    .swal2-cancel {
      background-color: #6b7280 !important;
      color: white !important;
    }

    .swal2-cancel:hover {
      background-color: #4b5563 !important;
    }

    /* Original styles */
    .module-card, .exam-card {
      transition: all 0.2s ease;
      border: 1px solid #e5e7eb;
      background: white;
    }
    
    .module-card:hover, .exam-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .btn-plain {
      background-color: white;
      border: 1px solid #d1d5db;
      color: #374151;
      transition: all 0.2s ease;
    }
    
    .btn-plain:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }
    
    .status-pending {
      background-color: #f3f4f6;
      color: #6b7280;
      border: 1px solid #d1d5db;
    }
    
    .badge-outline {
      background-color: white;
      border: 1px solid #d1d5db;
      color: #6b7280;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #6b7280;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      background: white;
    }
    
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .modal-box {
      border: 1px solid #e5e7eb;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .action-section {
      border-top: 1px solid #e5e7eb;
      padding-top: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .action-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .action-btn {
      flex: 1;
      min-width: 120px;
      border: 1px solid #d1d5db;
      background: white;
      padding: 0.75rem 1rem;
      border-radius: 0.375rem;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }
    
    .action-btn:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }
    
    .action-btn.approve:hover {
      background-color: #f0f9f0;
      border-color: #10b981;
    }
    
    .action-btn.reject:hover {
      background-color: #fef2f2;
      border-color: #ef4444;
    }
    
    .action-btn.compliance:hover {
      background-color: #faf5ff;
      border-color: #8b5cf6;
    }
    
    .content-preview {
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      padding: 1rem;
      background: #f9fafb;
      max-height: 300px;
      overflow-y: auto;
    }
    
    .filter-section {
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
    }
    
    .tab-active {
      background-color: #3b82f6;
      color: white;
      border-color: #3b82f6;
    }
    
    .tab-inactive {
      background-color: white;
      color: #6b7280;
      border-color: #d1d5db;
    }
    
    .question-item {
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1rem;
      background: white;
    }
    
    .correct-answer {
      background-color: #f0f9f0;
      border-color: #10b981;
    }
    
    /* Status badge styles */
    .status-pending {
        background-color: #e0e7ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
    }

    /* Updated Modal styles */
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .info-section {
        background-color: #f8fafc;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .info-label {
        font-weight: 600;
        color: #4b5563;
    }

    .info-value {
        color: #1f2937;
    }

    .document-section {
        background-color: #f8fafc;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        margin-top: 1.5rem;
    }

    /* FIXED: Document preview styles with image handling */
    .document-preview {
        max-height: 300px;
        overflow-y: auto;
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 1rem;
        word-wrap: break-word;
        overflow-x: hidden;
    }

    .document-preview * {
        max-width: 100% !important;
        box-sizing: border-box;
    }

    .document-preview img {
        max-width: 100% !important;
        height: auto !important;
        display: block !important;
        margin: 10px 0 !important;
        border-radius: 4px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    .document-preview table {
        width: 100% !important;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .document-preview table, 
    .document-preview th, 
    .document-preview td {
        border: 1px solid #ddd;
        word-wrap: break-word;
    }

    .document-preview th, 
    .document-preview td {
        padding: 8px;
        text-align: left;
        max-width: 100%;
    }

    .document-preview ul, 
    .document-preview ol {
        padding-left: 1.5rem;
    }

    .document-preview li {
        margin-bottom: 0.25rem;
        word-wrap: break-word;
    }

    .document-preview p {
        word-wrap: break-word;
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }

    .document-preview h1,
    .document-preview h2,
    .document-preview h3,
    .document-preview h4,
    .document-preview h5,
    .document-preview h6 {
        word-wrap: break-word;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    /* Image specific styles */
    .image-placeholder {
        background-color: #f3f4f6;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        color: #6b7280;
        margin: 10px 0;
    }

    .image-placeholder i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }

    /* Custom styles for border-only buttons */
    .btn-border {
        background-color: transparent;
        border: 1px solid #d1d5db;
        color: #374151;
        transition: all 0.2s ease-in-out;
    }

    .btn-border:hover {
        background-color: #f9fafb;
        border-color: #9ca3af;
    }

    .btn-sm-border {
        background-color: transparent;
        border: 1px solid #d1d5db;
        color: #374151;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
    }

    .btn-sm-border:hover {
        background-color: #f9fafb;
        border-color: #9ca3af;
    }

    /* Loading spinner */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Examination Preview Styles */
    .exam-preview-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 2rem;
      border-radius: 0.5rem 0.5rem 0 0;
      margin-bottom: 1.5rem;
    }

    .exam-preview-instructions {
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .exam-preview-question {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .exam-preview-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      margin-bottom: 0.5rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      background: #f9fafb;
      transition: all 0.2s ease;
    }

    .exam-preview-points {
      background: #3b82f6;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../USM/navbar.php'; ?>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
          <!-- Learning Modules Review Section -->
          <div class="mb-12" id="modules-section">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
              <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2 text-gray-800">Learning Module Review</h1>
                <p class="text-gray-600">Review and approve pending learning modules</p>
              </div>
              
              <!-- Tabs for Switching Between Modules and Examinations - Moved to middle -->
              <div class="tabs tabs-boxed bg-white p-1 rounded-lg inline-flex">
                <a class="tab tab-lg font-medium transition-all duration-200 tab-active" id="modules-tab">Learning Modules</a>
                <a class="tab tab-lg font-medium transition-all duration-200 tab-inactive" id="examinations-tab">Examinations</a>
              </div>
              
              <div class="flex gap-4 items-center">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                  <div class="text-sm text-gray-500">Pending Modules</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo count($pending_modules); ?></div>
                  <div class="text-xs text-gray-400">Awaiting review</div>
                </div>
               
              </div>
            </div>

            <!-- Department Filter for Modules -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
              <div class="flex flex-wrap gap-4">
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Department</span>
                  </label>
                  <select class="select select-bordered w-48" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <?php foreach ($departments as $dept): 
                      $display_name = ucwords(str_replace('-', ' ', $dept));
                    ?>
                      <option value="<?php echo $dept; ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                        <?php echo $display_name; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-border" onclick="applyModuleFilter()">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                  </button>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-border" onclick="clearModuleFilter()">
                    <i class="fas fa-times mr-2"></i>Clear
                  </button>
                </div>
                
                <!-- Active Filter Badge -->
                <?php if ($selected_department !== 'all'): ?>
                  <div class="form-control self-end">
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-600">Active filter:</span>
                      <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                        <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                      </span>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Module Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              <?php if (empty($pending_modules)): ?>
                <div class="col-span-full text-center py-8">
                  <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                  <p class="text-gray-500">
                    <?php if ($selected_department !== 'all'): ?>
                      No pending modules in <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                    <?php else: ?>
                      No pending learning modules found.
                    <?php endif; ?>
                  </p>
                  <div class="flex gap-2 justify-center mt-4">
                    <?php if ($selected_department !== 'all'): ?>
                      <button class="btn btn-border" onclick="clearModuleFilter()">
                        View All Departments
                      </button>
                    <?php endif; ?>
                    
                  </div>
                </div>
              <?php else: ?>
                <?php foreach ($pending_modules as $module): ?>
                  <div class="card bg-base-100 shadow-md module-card" 
                       data-department="<?php echo $module['department']; ?>" 
                       data-id="<?php echo $module['id']; ?>"
                       data-content="<?php echo htmlspecialchars($module['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="card-body">
                      <div class="flex justify-between items-start">
                        <h3 class="card-title"><?php echo htmlspecialchars($module['title']); ?></h3>
                        <div class="badge status-pending">
                          Pending
                        </div>
                      </div>
                      <div class="flex flex-wrap gap-2 my-2">
                        <div class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $module['department'])); ?></div>
                        <div class="badge badge-outline"><?php echo htmlspecialchars($module['roles']); ?></div>
                      </div>
                      <p class="text-sm text-gray-500">Date Added: <?php echo date('Y-m-d', strtotime($module['created_at'])); ?></p>
                      <p class="text-sm text-gray-500">Topic: <?php echo htmlspecialchars($module['topic']); ?></p>
                      <div class="card-actions justify-end mt-4">
                        <button class="btn-sm-border" onclick="viewPendingModule(<?php echo $module['id']; ?>)">Review</button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Examinations Review Section -->
          <div class="mb-12 hidden" id="examinations-section">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
              <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2 text-gray-800">Examination Review</h1>
                <p class="text-gray-600">Review and approve pending examinations</p>
              </div>
              
              <!-- Tabs for Switching Between Modules and Examinations - Moved to middle -->
              <div class="tabs tabs-boxed bg-white p-1 rounded-lg inline-flex">
                <a class="tab tab-lg font-medium transition-all duration-200 tab-inactive" id="modules-tab-2">Learning Modules</a>
                <a class="tab tab-lg font-medium transition-all duration-200 tab-active" id="examinations-tab-2">Examinations</a>
              </div>
              
              <div class="flex gap-4 items-center">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                  <div class="text-sm text-gray-500">Pending Exams</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo count($pending_examinations); ?></div>
                  <div class="text-xs text-gray-400">Awaiting review</div>
                </div>
                
              </div>
            </div>

            <!-- Department Filter for Examinations -->
            <div class="filter-section mb-6">
              <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Filter by Department
                  </label>
                  <div class="flex flex-col sm:flex-row gap-3">
                    <select class="select select-bordered w-full sm:w-64" id="examDepartmentFilter">
                      <option value="all">All Departments</option>
                      <?php foreach ($exam_departments as $dept): 
                        $display_name = ucwords(str_replace('-', ' ', $dept));
                      ?>
                        <option value="<?php echo $dept; ?>" <?php echo $selected_exam_department === $dept ? 'selected' : ''; ?>>
                          <?php echo $display_name; ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    
                    <div class="flex gap-2">
                      <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyExamFilter()">
                        Apply Filter
                      </button>
                      <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearExamFilter()">
                        Clear
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Active Filter Badge -->
                <?php if ($selected_exam_department !== 'all'): ?>
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Active filter:</span>
                    <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                      <?php echo ucwords(str_replace('-', ' ', $selected_exam_department)); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Examination Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              <?php if (empty($pending_examinations)): ?>
                <div class="col-span-full empty-state">
                  <i class="fas fa-file-alt"></i>
                  <h3 class="text-xl font-semibold mb-2 text-gray-700">
                    <?php if ($selected_exam_department !== 'all'): ?>
                      No Pending Examinations in <?php echo ucwords(str_replace('-', ' ', $selected_exam_department)); ?>
                    <?php else: ?>
                      No Pending Examinations
                    <?php endif; ?>
                  </h3>
                  <p class="text-gray-500 mb-4">
                    <?php if ($selected_exam_department !== 'all'): ?>
                      There are no examinations awaiting review in this department.
                    <?php else: ?>
                      There are no examinations awaiting review at this time.
                    <?php endif; ?>
                  </p>
                  <div class="flex gap-2 justify-center">
                    <?php if ($selected_exam_department !== 'all'): ?>
                      <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearExamFilter()">
                        View All Departments
                      </button>
                    <?php endif; ?>
                    
                  </div>
                </div>
              <?php else: ?>
                <?php foreach ($pending_examinations as $exam): ?>
                  <div class="exam-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                      <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($exam['title']); ?></h3>
                      <span class="status-pending text-xs px-2 py-1 rounded-full">Pending</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 my-3">
                      <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></span>
                      <!-- FIXED: Check if roles key exists before using it -->
                      <?php if (isset($exam['roles']) && !empty($exam['roles'])): ?>
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($exam['roles']); ?></span>
                      <?php else: ?>
                        <span class="badge-outline text-xs px-2 py-1 rounded">All Roles</span>
                      <?php endif; ?>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                      <p class="text-sm text-gray-600">
                        <span class="font-medium">Date Added:</span> 
                        <?php echo date('Y-m-d', strtotime($exam['created_at'])); ?>
                      </p>
                      <p class="text-sm text-gray-600">
                        <span class="font-medium">Questions:</span> 
                        <?php echo isset($exam['question_count']) ? $exam['question_count'] : 'N/A'; ?>
                      </p>
                      <p class="text-sm text-gray-600">
                        <span class="font-medium">Duration:</span> 
                        <?php echo isset($exam['duration']) ? $exam['duration'] : 'N/A'; ?> minutes
                      </p>
                    </div>
                    
                    <div class="mt-4">
                      <button class="btn-plain w-full py-2 rounded-lg text-sm" 
                              onclick="viewExam(<?php echo $exam['id']; ?>, '<?php echo htmlspecialchars($exam['title']); ?>', '<?php echo htmlspecialchars($exam['department']); ?>', '<?php echo isset($exam['roles']) ? htmlspecialchars($exam['roles']) : 'All Roles'; ?>', '<?php echo isset($exam['duration']) ? $exam['duration'] : 'N/A'; ?>', '<?php echo isset($exam['question_count']) ? $exam['question_count'] : 'N/A'; ?>')">
                        <i class="fas fa-eye mr-2"></i>View Examination
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
    </div>
  </div>

  <!-- PENDING MODULE REVIEW MODAL -->
  <dialog id="pending_review_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="pending-review-title">Module Title</h3>
      
      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="pending-review-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="pending-review-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="pending-review-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="pending-review-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="pending-review-status">Pending</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="pending-review-date">-</span>
          </div>
        </div>
        
        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="pending-review-document-preview">
              <!-- Document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="pending-review-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="pending-review-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>
          
          <!-- Review Actions -->
          <div class="mt-4 flex gap-2">
            <button class="btn btn-success flex-1" id="pending-review-approve-btn">
              <i class="fas fa-check mr-2"></i>Approve
            </button>
            <button class="btn btn-error flex-1" id="pending-review-reject-btn">Reject</button>
            <button class="btn btn-warning flex-1" id="pending-review-compliance-btn">For Compliance</button>
          </div>
        </div>
      </div>
      
      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- IMPROVED REJECT CONFIRMATION MODAL -->
  <dialog id="reject_review_modal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg">Reject Module</h3>
      <div class="mb-4">
        <p class="text-gray-600 mb-3">Are you sure you want to reject <span id="reject_module_name" class="font-semibold">this module</span>?</p>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Reason for Rejection (Optional)</span>
          </label>
          <textarea class="textarea textarea-bordered w-full h-32" id="reject_review_reason" placeholder="Enter reason for rejection..."></textarea>
        </div>
        <div class="mt-3 text-sm text-gray-500">
          <p><i class="fas fa-info-circle mr-2"></i>Providing a reason helps the creator understand what needs to be improved.</p>
        </div>
      </div>
      <div class="modal-action">
        <button class="btn btn-border" onclick="reject_review_modal.close()">Cancel</button>
        <button class="btn btn-error" onclick="confirmRejectReview()">Reject Module</button>
      </div>
    </div>
  </dialog>

  <!-- FIXED COMPLIANCE CONFIRMATION MODAL -->
  <dialog id="compliance_review_modal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg">Mark for Compliance</h3>
      <div class="mb-4 compliance-modal-content">
        <p class="text-gray-600 mb-3">Please specify the compliance requirements for <span id="compliance_module_name" class="font-semibold">this module</span>:</p>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Compliance Requirements</span>
          </label>
          <textarea class="textarea textarea-bordered w-full h-40" id="compliance_review_requirements" placeholder="Specify compliance requirements, areas that need improvement, or specific changes required..."></textarea>
        </div>
        <div class="mt-3 text-sm text-gray-500">
          <p><i class="fas fa-info-circle mr-2"></i>This module will be returned to the creator with your feedback for compliance updates.</p>
        </div>
      </div>
      <div class="modal-action">
        <button class="btn btn-border" onclick="compliance_review_modal.close()">Cancel</button>
        <button class="btn btn-warning" onclick="confirmComplianceReview()">Mark for Compliance</button>
      </div>
    </div>
  </dialog>

  <!-- FULL CONTENT MODAL -->
  <dialog id="full_content_modal" class="modal modal-lg">
    <div class="modal-box max-w-6xl">
      <h3 class="font-bold text-lg mb-4" id="full-content-title">Full Content</h3>
      <div class="document-preview max-h-96" id="full-content-display">
        <!-- Full content will be displayed here -->
      </div>
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- View Examination Modal -->
<dialog id="view_exam_modal" class="modal">
    <div class="modal-box max-w-6xl p-0 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-bold text-2xl mb-2" id="exam_title">Examination Title</h3>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-building mr-1"></i>
                            <span id="exam_department">Department</span>
                        </span>
                        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-users mr-1"></i>
                            <span id="exam_roles">Roles</span>
                        </span>
                        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-clock mr-1"></i>
                            <span id="exam_duration">Duration</span> min
                        </span>
                    </div>
                </div>
                <button class="btn btn-circle btn-ghost btn-sm text-white hover:bg-white hover:bg-opacity-20" onclick="view_exam_modal.close()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>
        
        <!-- Learning Module Reference Section (Collapsible) -->
        <div class="border-b border-gray-200">
            <div class="flex items-center justify-between p-4 cursor-pointer bg-gray-50" onclick="toggleModulePreview()">
                <div class="flex items-center">
                    <i class="fas fa-book text-blue-600 mr-3 text-lg"></i>
                    <h4 class="font-semibold text-gray-800">Learning Module Reference</h4>
                </div>
                <i class="fas fa-chevron-down text-gray-500 transition-transform" id="moduleToggleIcon"></i>
            </div>
            <div class="hidden" id="modulePreviewContent">
                <div class="p-4 bg-gray-50 border-t border-gray-200 max-h-60 overflow-y-auto">
                    <div id="moduleContentPreview">
                        <!-- Module content will be loaded here -->
                        <div class="text-center py-4">
                            <div class="loading-spinner inline-block"></div>
                            <p class="text-gray-500 mt-2">Loading module content...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Examination Preview Content -->
        <div class="p-6 max-h-[60vh] overflow-y-auto">
            <div class="mb-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                    <h4 class="font-semibold text-lg text-gray-800">Examination Preview</h4>
                    <span class="ml-auto text-sm text-gray-500" id="examQuestionCount">0 Questions</span>
                </div>
                
                <!-- Instructions Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h5 class="font-semibold text-blue-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Instructions:
                    </h5>
                    <ul class="text-blue-700 text-sm space-y-1">
                        <li>â€¢ Read each question carefully before answering</li>
                        <li>â€¢ Select the best answer for each question</li>
                        <li>â€¢ Ensure all answers are final before submitting</li>
                        <li>â€¢ Time limit: <span id="examDurationPreview">60</span> minutes</li>
                    </ul>
                </div>
                
                <!-- Questions Container -->
                <div id="exam_questions" class="space-y-4">
                    <!-- Questions will be inserted here -->
                    <div class="text-center py-8">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-gray-500">Loading examination questions...</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <p class="text-sm text-gray-500 mb-3">Examination Actions</p>
                <div class="action-buttons">
                    <button class="action-btn approve" onclick="approveExam()">
                        <i class="fas fa-check mr-2"></i>Approve
                    </button>
                    <button class="action-btn reject" onclick="rejectExam()">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button class="action-btn compliance" onclick="forExamCompliance()">
                        <i class="fas fa-exclamation-triangle mr-2"></i>For Compliance
                    </button>
                    <button class="action-btn" id="examEditBtn" onclick="editPendingExam()" style="display:none;">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                    <button class="action-btn" id="examCancelBtn" onclick="cancelPendingExam()" style="display:none;">
                        <i class="fas fa-ban mr-2"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</dialog>

  <!-- IMPROVED Reject Exam Modal -->
  <dialog id="reject_exam_modal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg text-gray-800 mb-4">Reject Examination</h3>
      <div class="mb-4">
        <p class="text-gray-600 mb-3">Are you sure you want to reject <span id="reject_exam_name" class="font-semibold">this examination</span>?</p>
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Reason for Rejection (Optional)</span>
          </label>
          <textarea class="textarea textarea-bordered w-full h-32 border-gray-300" id="reject_exam_reason" placeholder="Enter reason for rejection..."></textarea>
        </div>
        <div class="mt-3 text-sm text-gray-500">
          <p><i class="fas fa-info-circle mr-2"></i>Providing a reason helps the creator understand what needs to be improved.</p>
        </div>
      </div>
      <div class="modal-action">
        <button class="btn-plain px-4 py-2 rounded-lg" onclick="reject_exam_modal.close()">Cancel</button>
        <button class="btn-plain px-4 py-2 rounded-lg border-red-200 text-red-700 hover:bg-red-50" onclick="confirmExamReject()">Reject Examination</button>
      </div>
    </div>
  </dialog>

  <!-- IMPROVED For Exam Compliance Modal -->
  <dialog id="compliance_exam_modal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg text-gray-800 mb-4">Mark for Compliance</h3>
      <div class="mb-4 compliance-modal-content">
        <p class="text-gray-600 mb-3">Please specify the compliance requirements for <span id="compliance_exam_name" class="font-semibold">this examination</span>:</p>
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Compliance Requirements</span>
          </label>
          <textarea class="textarea textarea-bordered w-full h-40 border-gray-300" id="compliance_exam_requirements" placeholder="Specify compliance requirements, areas that need improvement, or specific changes required..."></textarea>
        </div>
        <div class="mt-3 text-sm text-gray-500">
          <p><i class="fas fa-info-circle mr-2"></i>This examination will be returned to the creator with your feedback for compliance updates.</p>
        </div>
      </div>
      <div class="modal-action">
        <button class="btn-plain px-4 py-2 rounded-lg" onclick="compliance_exam_modal.close()">Cancel</button>
        <button class="btn-plain px-4 py-2 rounded-lg border-purple-200 text-purple-700 hover:bg-purple-50" onclick="confirmExamCompliance()">Mark for Compliance</button>
      </div>
    </div>
  </dialog>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    // Module data storage for review
    let currentReviewModuleId = null;
    let currentReviewModuleData = null;
    
    // Examination data storage
    let currentExamId = null;
    let currentExamData = null;

    // Simplified SweetAlert2 function
    function showSweetAlert(options) {
        const openDialog = document.querySelector('dialog[open]');
        const resolvedOptions = {
            customClass: {
                popup: 'custom-swal-popup',
                confirmButton: 'custom-swal-confirm',
                cancelButton: 'custom-swal-cancel'
            },
            ...options
        };

        if (!resolvedOptions.target && openDialog) {
            resolvedOptions.target = openDialog;
        }

        return Swal.fire(resolvedOptions);
    }

    // Simple error alert function
    function showErrorAlert(message) {
        return showSweetAlert({
            title: 'Error!',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444'
        });
    }

    // Fixed approve confirmation
    function showApproveConfirmation(moduleId, moduleTitle) {
        pending_review_modal.close();
        
        return showSweetAlert({
            title: 'Approve Module?',
            html: `Are you sure you want to approve <strong>"${moduleTitle}"</strong>?<br><br>This module will be ready for posting.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true
        }).then((result) => {
            if (result.isConfirmed) {
                updateReviewModuleStatus(moduleId, 'approved', `Module "${moduleTitle}" has been approved.`);
            } else {
                setTimeout(() => {
                    pending_review_modal.showModal();
                }, 100);
            }
        });
    }

    // View Pending Module Function
    function viewPendingModule(moduleId) {
        console.log('View pending module for review:', moduleId);
        
        // Set current module ID
        currentReviewModuleId = moduleId;
        
        // Show loading state
        const modal = document.getElementById('pending_review_modal');
        const previewElement = document.getElementById('pending-review-document-preview');
        previewElement.innerHTML = '<div class="flex justify-center items-center h-full"><div class="loading-spinner"></div><span class="ml-2">Loading content...</span></div>';
        
        // Show the modal immediately while content loads
        if (modal) {
            modal.showModal();
        }
        
        // Try to get module data from the card first
        const moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
        if (moduleCard) {
            const moduleData = getModuleDataFromCard(moduleCard);
            if (moduleData) {
                currentReviewModuleData = moduleData;
                showPendingReviewModal(moduleData);
                return;
            }
        }
        
        // If card data not available, fetch from server
        fetch(`fetch_module_data.php?module_id=${moduleId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(moduleData => {
                console.log('Module data fetched:', moduleData);
                currentReviewModuleData = moduleData;
                showPendingReviewModal(moduleData);
            })
            .catch(error => {
                console.error('Error fetching module data:', error);
                previewElement.innerHTML = '<p class="text-red-500 italic">Error loading module content. Please try again.</p>';
                // Show basic modal with limited info
                showBasicModal(moduleId);
            });
    }

    function getModuleDataFromCard(moduleCard) {
        try {
            const title = moduleCard.querySelector('.card-title').textContent;
            const topic = moduleCard.querySelector('p:nth-child(4)')?.textContent.replace('Topic: ', '') || 'N/A';
            const department = moduleCard.getAttribute('data-department');
            const roles = moduleCard.querySelector('.badge-outline:nth-child(2)')?.textContent || 'N/A';
            const created_at = moduleCard.querySelector('p:nth-child(3)')?.textContent.replace('Date Added: ', '') || 'N/A';
            const content = moduleCard.getAttribute('data-content');
            
            return {
                id: moduleCard.getAttribute('data-id'),
                title: title,
                topic: topic,
                department: department,
                roles: roles,
                content: content,
                status: 'pending',
                created_at: created_at
            };
        } catch (error) {
            console.error('Error getting data from card:', error);
            return null;
        }
    }

    function showBasicModal(moduleId) {
        document.getElementById('pending-review-title').textContent = 'Module #' + moduleId;
        document.getElementById('pending-review-exam-title').textContent = 'Module #' + moduleId;
        document.getElementById('pending-review-topic').textContent = 'Information not available';
        document.getElementById('pending-review-department').textContent = 'Information not available';
        document.getElementById('pending-review-role').textContent = 'Information not available';
        document.getElementById('pending-review-status').textContent = 'Pending';
        document.getElementById('pending-review-date').textContent = 'N/A';
        
        const previewElement = document.getElementById('pending-review-document-preview');
        previewElement.innerHTML = '<p class="text-gray-500 italic">Content not available. You can still perform actions on this module.</p>';
        
        setupActionButtons(moduleId, 'Module #' + moduleId);
    }

    // **FIXED: Function to properly handle and display content with images**
    function processContentWithImages(content) {
        if (!content) return '<p class="text-gray-500 italic">No content available.</p>';
        
        // Create a temporary div to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        
        // Process images
        const images = tempDiv.querySelectorAll('img');
        images.forEach(img => {
            // Ensure images have proper styling
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.style.display = 'block';
            img.style.margin = '10px 0';
            img.style.borderRadius = '4px';
            img.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            
            // Add onerror handler for broken images
            img.onerror = function() {
                this.onerror = null; // Prevent infinite loop
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNNjUgMzVINTVWMjVINDBWMzVIMzBWNTBINDBWNjVINTVWNTBINjVWMzVaIiBmaWxsPSIjOEY5MUEwIi8+PHBhdGggZD0iTTM1IDY1VjUwSDMwVjY1SDM1WiIgZmlsbD0iIzhGOTFBOCIvPjxwYXRoIGQ9Ik02NSA1MFY2NUg3MFY1MEg2NVoiIGZpbGw9IiM4RjkxQTgiLz48L3N2Zz4=';
                this.alt = 'Image not available';
                this.title = 'Image could not be loaded';
                this.style.width = '100px';
                this.style.height = '100px';
                this.style.margin = '10px auto';
            };
            
            // Add loading attribute for better performance
            img.loading = 'lazy';
        });
        
        // Process other elements for consistent styling
        const paragraphs = tempDiv.querySelectorAll('p');
        paragraphs.forEach(p => {
            p.style.lineHeight = '1.6';
            p.style.marginBottom = '0.5rem';
        });
        
        const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');
        headings.forEach(h => {
            h.style.color = '#1f2937';
            h.style.marginTop = '1rem';
            h.style.marginBottom = '0.5rem';
        });
        
        return tempDiv.innerHTML;
    }

    // **UPDATED: Function to properly display content with images**
    function showPendingReviewModal(moduleData) {
        console.log('Showing pending review modal for:', moduleData.title);
        
        // Set modal title
        document.getElementById('pending-review-title').textContent = moduleData.title;
        
        // Set info section
        document.getElementById('pending-review-exam-title').textContent = moduleData.title;
        document.getElementById('pending-review-topic').textContent = moduleData.topic || 'N/A';
        document.getElementById('pending-review-department').textContent = formatDepartment(moduleData.department) || 'N/A';
        document.getElementById('pending-review-role').textContent = moduleData.roles || 'N/A';
        document.getElementById('pending-review-status').textContent = 'Pending';
        document.getElementById('pending-review-date').textContent = moduleData.created_at ? moduleData.created_at.split(' ')[0] : 'N/A';
        
        // Set document preview with PROCESSED content
        const previewElement = document.getElementById('pending-review-document-preview');
        if (moduleData.content && moduleData.content.trim() !== '') {
            // Process the content to handle images properly
            const processedContent = processContentWithImages(moduleData.content);
            
            // Clear and set the processed content
            previewElement.innerHTML = processedContent;
            
            // Force a reflow to ensure proper rendering
            setTimeout(() => {
                previewElement.style.overflowY = 'auto';
            }, 100);
        } else {
            previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
        }
        
        setupActionButtons(moduleData.id, moduleData.title);
    }

    function setupActionButtons(moduleId, moduleTitle) {
        // Set module names in reject and compliance modals
        document.getElementById('reject_module_name').textContent = `"${moduleTitle}"`;
        document.getElementById('compliance_module_name').textContent = `"${moduleTitle}"`;
        
        // Set up action buttons
        document.getElementById('pending-review-approve-btn').onclick = function() {
            showApproveConfirmation(moduleId, moduleTitle);
        };
        
        document.getElementById('pending-review-reject-btn').onclick = function() {
            document.getElementById('reject_review_reason').value = '';
            pending_review_modal.close();
            reject_review_modal.showModal();
        };
        
        document.getElementById('pending-review-compliance-btn').onclick = function() {
            document.getElementById('compliance_review_requirements').value = '';
            pending_review_modal.close();
            compliance_review_modal.showModal();
        };
        
        document.getElementById('pending-review-download-file').onclick = function() {
            if (currentReviewModuleData) {
                downloadReviewModuleFile(currentReviewModuleData);
            } else {
                showErrorAlert('Module data not available for download.');
            }
        };
        
        document.getElementById('pending-review-view-full-content').onclick = function() {
            if (currentReviewModuleData) {
                showFullContentModal(currentReviewModuleData);
            } else {
                showErrorAlert('Module data not available for full content view.');
            }
        };
    }

    function confirmRejectReview() {
        const reason = document.getElementById('reject_review_reason').value;
        const moduleTitle = currentReviewModuleData ? currentReviewModuleData.title : 'Module #' + currentReviewModuleId;
        
        // Close reject modal first
        reject_review_modal.close();
        
        showSweetAlert({
            title: 'Confirm Rejection',
            html: `Are you sure you want to reject <strong>"${moduleTitle}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true,
            focusConfirm: false
        }).then((result) => {
            if (result.isConfirmed) {
                updateReviewModuleStatus(currentReviewModuleId, 'rejected', `Module "${moduleTitle}" has been rejected.`, reason);
            } else {
                // Reopen reject modal if user cancels
                setTimeout(() => {
                    reject_review_modal.showModal();
                }, 100);
            }
        });
    }

    function confirmComplianceReview() {
        const requirements = document.getElementById('compliance_review_requirements').value;
        const moduleTitle = currentReviewModuleData ? currentReviewModuleData.title : 'Module #' + currentReviewModuleId;
        
        if (!requirements.trim()) {
            showSweetAlert({
                title: 'Requirements Needed',
                text: 'Please specify compliance requirements before marking for compliance.',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
            });
            return;
        }
        
        // Close compliance modal first
        compliance_review_modal.close();
        
        showSweetAlert({
            title: 'Confirm Compliance',
            html: `Mark <strong>"${moduleTitle}"</strong> for compliance?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Mark for Compliance!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true,
            focusConfirm: false
        }).then((result) => {
            if (result.isConfirmed) {
                updateReviewModuleStatus(currentReviewModuleId, 'compliance', `Module "${moduleTitle}" has been marked for compliance.`, requirements);
            } else {
                // Reopen compliance modal if user cancels
                setTimeout(() => {
                    compliance_review_modal.showModal();
                }, 100);
            }
        });
    }

    // AJAX function to update module status from review
    function updateReviewModuleStatus(moduleId, newStatus, successMessage, remarks = '') {
        console.log('Updating module status:', {moduleId, newStatus, remarks});
        
        // Create form data
        const formData = new FormData();
        formData.append('module_id', moduleId);
        formData.append('new_status', newStatus);
        formData.append('remarks', remarks);
        
        // Show loading state in SweetAlert
        showSweetAlert({
            title: 'Processing...',
            text: 'Please wait while we update the module status.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send AJAX request
        fetch('update_module_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            Swal.close();
            if (data.success) {
                showSweetAlert({
                    title: 'Success!',
                    text: successMessage,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10b981',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Remove the module card from UI
                removeModuleCard(moduleId);
                
                // Check if there are any modules left
                setTimeout(() => {
                    const remainingModules = document.querySelectorAll('.module-card');
                    if (remainingModules.length === 0) {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                showSweetAlert({
                    title: 'Error!',
                    text: 'Error: ' + data.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showSweetAlert({
                title: 'Network Error!',
                text: 'Please check console for details.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444'
            });
        });
    }

    function removeModuleCard(moduleId) {
        const moduleCard = document.querySelector(`.module-card[data-id="${moduleId}"]`);
        if (moduleCard) {
            moduleCard.style.opacity = '0.5';
            moduleCard.style.transform = 'scale(0.95)';
            setTimeout(() => {
                moduleCard.remove();
            }, 500);
        }
    }

    // Download Function for Review
    function downloadReviewModuleFile(moduleData) {
        // Create a blob with the content
        const content = moduleData.content || '<p>No content available</p>';
        const blob = new Blob([`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${moduleData.title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                    .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                    .title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                    .meta { color: #666; font-size: 14px; }
                    .content { margin-top: 20px; }
                    img { max-width: 100%; height: auto; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title">${moduleData.title}</div>
                    <div class="meta">
                        Topic: ${moduleData.topic} | Department: ${formatDepartment(moduleData.department)} | 
                        Role: ${moduleData.roles} | Status: ${moduleData.status}
                    </div>
                </div>
                <div class="content">
                    ${content}
                </div>
            </body>
            </html>
        `], { type: 'text/html' });
        
        // Create download link
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${moduleData.title.replace(/\s+/g, '_')}.html`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Helper function to format department names
    function formatDepartment(department) {
        if (!department) return 'N/A';
        return department.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    // **UPDATED: Function for full content modal with image handling**
    function showFullContentModal(moduleData) {
        console.log('Showing full content modal for:', moduleData.title);
        
        // Set modal title
        document.getElementById('full-content-title').textContent = moduleData.title + ' - Full Content';
        
        // Set full content with processed images
        const fullContentDisplay = document.getElementById('full-content-display');
        if (moduleData.content && moduleData.content.trim() !== '') {
            // Process the content to handle images properly
            const processedContent = processContentWithImages(moduleData.content);
            
            // Clear and set the processed content
            fullContentDisplay.innerHTML = processedContent;
            
            // Ensure proper scrolling
            setTimeout(() => {
                fullContentDisplay.style.overflowY = 'auto';
            }, 100);
        } else {
            fullContentDisplay.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
        }
        
        // Show the modal
        const modal = document.getElementById('full_content_modal');
        if (modal) {
            modal.showModal();
        } else {
            console.error('Full content modal not found');
        }
    }

    // Tab switching functionality
    document.getElementById('modules-tab').addEventListener('click', function() {
        document.getElementById('modules-section').classList.remove('hidden');
        document.getElementById('examinations-section').classList.add('hidden');
        this.classList.add('tab-active');
        this.classList.remove('tab-inactive');
        document.getElementById('examinations-tab').classList.add('tab-inactive');
        document.getElementById('examinations-tab').classList.remove('tab-active');
    });

    document.getElementById('examinations-tab').addEventListener('click', function() {
        document.getElementById('examinations-section').classList.remove('hidden');
        document.getElementById('modules-section').classList.add('hidden');
        this.classList.add('tab-active');
        this.classList.remove('tab-inactive');
        document.getElementById('modules-tab').classList.add('tab-inactive');
        document.getElementById('modules-tab').classList.remove('tab-active');
    });

    document.getElementById('modules-tab-2').addEventListener('click', function() {
        document.getElementById('modules-section').classList.remove('hidden');
        document.getElementById('examinations-section').classList.add('hidden');
        document.getElementById('modules-tab').classList.add('tab-active');
        document.getElementById('modules-tab').classList.remove('tab-inactive');
        document.getElementById('examinations-tab').classList.add('tab-inactive');
        document.getElementById('examinations-tab').classList.remove('tab-active');
    });

    document.getElementById('examinations-tab-2').addEventListener('click', function() {
        document.getElementById('examinations-section').classList.remove('hidden');
        document.getElementById('modules-section').classList.add('hidden');
        document.getElementById('examinations-tab').classList.add('tab-active');
        document.getElementById('examinations-tab').classList.remove('tab-inactive');
        document.getElementById('modules-tab').classList.add('tab-inactive');
        document.getElementById('modules-tab').classList.remove('tab-active');
    });

    (function syncTabFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = (urlParams.get('tab') || '').toLowerCase();
        if (tab === 'examinations') {
            document.getElementById('examinations-tab')?.click();
        }
    })();

    // Filter functions
    function applyModuleFilter() {
        const department = document.getElementById('departmentFilter').value;
        window.location.href = `review_dashboard.php?department=${department}`;
    }

    function clearModuleFilter() {
        window.location.href = `review_dashboard.php?department=all`;
    }

    function applyExamFilter() {
        const department = document.getElementById('examDepartmentFilter').value;
        window.location.href = `review_dashboard.php?exam_department=${department}`;
    }

    function clearExamFilter() {
        window.location.href = `review_dashboard.php?exam_department=all`;
    }

    // **FIXED EXAMINATION REVIEW FUNCTIONS**
    function viewExam(examId, title, department, roles, duration, questionCount) {
        currentExamId = examId;
        
        // Set basic exam info
        document.getElementById('exam_title').textContent = title;
        document.getElementById('exam_department').textContent = formatDepartment(department);
        document.getElementById('exam_roles').textContent = roles;
        document.getElementById('exam_duration').textContent = duration;
        document.getElementById('examDurationPreview').textContent = duration;
        document.getElementById('examQuestionCount').textContent = `${questionCount} Questions`;
        
        // Reset module preview
        document.getElementById('modulePreviewContent').classList.add('hidden');
        document.getElementById('moduleContentPreview').innerHTML = `
            <div class="text-center py-4">
                <div class="loading-spinner inline-block"></div>
                <p class="text-gray-500 mt-2">Loading module content...</p>
            </div>
        `;
        
        // Reset toggle icon
        document.getElementById('moduleToggleIcon').classList.remove('fa-chevron-up');
        document.getElementById('moduleToggleIcon').classList.add('fa-chevron-down');
        
        // Show loading state for questions
        const questionsContainer = document.getElementById('exam_questions');
        questionsContainer.innerHTML = '<div class="text-center py-8"><div class="loading-spinner"></div><p class="mt-2 text-gray-500">Loading examination questions...</p></div>';
        
        // Show modal
        const modal = document.getElementById('view_exam_modal');
        modal.showModal();
        
        // Fetch exam data from server
        fetch(`../training_dev_officer/fetch_exam_data.php?exam_id=${examId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(examData => {
                currentExamData = examData;
                displayExamQuestions(examData);

                const editBtn = document.getElementById('examEditBtn');
                const cancelBtn = document.getElementById('examCancelBtn');
                const isPending = (examData && String(examData.status || '').toLowerCase() === 'pending');
                if (editBtn) editBtn.style.display = isPending ? 'inline-flex' : 'none';
                if (cancelBtn) cancelBtn.style.display = isPending ? 'inline-flex' : 'none';
                
                // Load module content if module_id exists
                if (examData.module_id) {
                    loadModuleContent(examData.module_id);
                }
            })
            .catch(error => {
                console.error('Error fetching exam data:', error);
                questionsContainer.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mb-3"></i>
                        <p class="text-red-700 font-medium">Error loading examination</p>
                        <p class="text-red-600 text-sm">Please try again or contact support.</p>
                    </div>
                `;
            });
    }

    // Function to toggle module preview
    function toggleModulePreview() {
        const moduleContent = document.getElementById('modulePreviewContent');
        const toggleIcon = document.getElementById('moduleToggleIcon');
        
        moduleContent.classList.toggle('hidden');
        
        // Toggle icon
        if (moduleContent.classList.contains('hidden')) {
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
        } else {
            toggleIcon.classList.remove('fa-chevron-down');
            toggleIcon.classList.add('fa-chevron-up');
        }
    }

    // Function to load module content
    function loadModuleContent(moduleId) {
        fetch(`fetch_module_content.php?module_id=${moduleId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(moduleData => {
                displayModuleContent(moduleData);
            })
            .catch(error => {
                console.error('Error loading module content:', error);
                document.getElementById('moduleContentPreview').innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                            <div>
                                <p class="font-medium text-yellow-700">Module content not available</p>
                                <p class="text-yellow-600 text-sm">The learning module content could not be loaded.</p>
                            </div>
                        </div>
                    </div>
                `;
            });
    }

    // **UPDATED: Function to display module content with image handling**
    function displayModuleContent(moduleData) {
        const moduleContentDiv = document.getElementById('moduleContentPreview');
        
        let html = `
            <div class="space-y-3">
                <div>
                    <h5 class="font-semibold text-lg mb-1">${moduleData.title || 'Untitled Module'}</h5>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">${formatDepartment(moduleData.department || 'General')}</span>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">${moduleData.roles || 'All Roles'}</span>
                    </div>
                </div>
        `;
        
        // Add content if available
        if (moduleData.content && moduleData.content.trim() !== '') {
            // Process content to handle images
            const processedContent = processContentWithImages(moduleData.content);
            
            // Truncate content if too long
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = processedContent;
            const textContent = tempDiv.textContent || tempDiv.innerText || '';
            const isLong = textContent.length > 500;
            
            if (isLong) {
                // Show first 500 characters with "Read More" option
                const truncatedDiv = document.createElement('div');
                truncatedDiv.innerHTML = processedContent.substring(0, 500) + '...';
                html += `
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <h6 class="font-medium text-gray-700 mb-1 text-sm">Content Preview:</h6>
                        <div class="text-gray-600 text-sm max-h-32 overflow-y-auto">
                            ${truncatedDiv.innerHTML}
                        </div>
                        <button class="text-blue-600 text-xs mt-2 hover:underline" onclick="showFullModuleContent(${moduleData.id})">
                            <i class="fas fa-external-link-alt mr-1"></i>View Full Content
                        </button>
                    </div>
                `;
            } else {
                html += `
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <h6 class="font-medium text-gray-700 mb-1 text-sm">Content Preview:</h6>
                        <div class="text-gray-600 text-sm max-h-32 overflow-y-auto">
                            ${processedContent}
                        </div>
                    </div>
                `;
            }
        }
        
        // Add key points if available
        if (moduleData.key_points && moduleData.key_points.trim() !== '') {
            const truncatedPoints = moduleData.key_points.length > 200 ? 
                moduleData.key_points.substring(0, 200) + '...' : 
                moduleData.key_points;
            
            html += `
                <div class="bg-white p-3 rounded border border-gray-200">
                    <h6 class="font-medium text-gray-700 mb-1 text-sm">Key Points:</h6>
                    <div class="text-gray-600 text-sm">
                        ${truncatedPoints}
                    </div>
                </div>
            `;
        }
        
        html += `</div>`;
        
        moduleContentDiv.innerHTML = html;
    }

    // **NEW: Function to show full module content in a modal**
    function showFullModuleContent(moduleId) {
        fetch(`fetch_module_content.php?module_id=${moduleId}`)
            .then(response => response.json())
            .then(moduleData => {
                // Create a simple modal to show full content
                const fullContent = moduleData.content || 'No content available';
                const processedContent = processContentWithImages(fullContent);
                
                showSweetAlert({
                    title: moduleData.title || 'Module Content',
                    html: `
                        <div class="text-left max-h-96 overflow-y-auto p-2">
                            <div class="mb-3">
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">${formatDepartment(moduleData.department || 'General')}</span>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">${moduleData.roles || 'All Roles'}</span>
                            </div>
                            <div class="module-full-content">
                                ${processedContent}
                            </div>
                        </div>
                    `,
                    width: '800px',
                    showConfirmButton: false,
                    showCloseButton: true
                });
            })
            .catch(error => {
                console.error('Error loading full module content:', error);
                showErrorAlert('Could not load full module content');
            });
    }

    // Function to display exam questions in compact preview
    function displayExamQuestions(examData) {
        const questionsContainer = document.getElementById('exam_questions');
        questionsContainer.innerHTML = '';
        
        if (!examData.questions || examData.questions.length === 0) {
            questionsContainer.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                    <i class="fas fa-question-circle text-3xl text-yellow-500 mb-3"></i>
                    <p class="text-yellow-700 font-medium">No questions found in this examination.</p>
                </div>
            `;
            return;
        }
        
        examData.questions.forEach((question, index) => {
            const questionDiv = document.createElement('div');
            questionDiv.className = 'exam-preview-question bg-white rounded-lg border border-gray-200 p-4';
            
            // Determine question type badge color
            let badgeClass = 'bg-blue-100 text-blue-800';
            let badgeIcon = 'fas fa-list';
            
            switch(question.question_type) {
                case 'multiple':
                    badgeClass = 'bg-blue-100 text-blue-800';
                    badgeIcon = 'fas fa-list';
                    break;
                case 'truefalse':
                    badgeClass = 'bg-purple-100 text-purple-800';
                    badgeIcon = 'fas fa-toggle-on';
                    break;
                case 'shortanswer':
                    badgeClass = 'bg-green-100 text-green-800';
                    badgeIcon = 'fas fa-align-left';
                    break;
                case 'identification':
                    badgeClass = 'bg-orange-100 text-orange-800';
                    badgeIcon = 'fas fa-font';
                    break;
            }
            
            // Parse answer key
            let answerKey = { correctAnswers: [], points: 1 };
            try {
                if (question.answer_key) {
                    answerKey = JSON.parse(question.answer_key);
                }
            } catch (e) {
                console.error('Error parsing answer key:', e);
            }
            
            // Parse options if available
            let options = [];
            try {
                if (question.options) {
                    options = JSON.parse(question.options);
                }
            } catch (e) {
                console.error('Error parsing options:', e);
            }
            
            // Build question HTML
            let questionHtml = `
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center">
                        <span class="font-bold text-lg text-gray-800 mr-3">Q${index + 1}</span>
                        <span class="badge ${badgeClass} text-xs px-2 py-1 rounded-full">
                            <i class="${badgeIcon} mr-1"></i>
                            ${getQuestionTypeLabel(question.question_type)}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xs text-gray-500 mr-2">Points:</span>
                        <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-full">${answerKey.points || 1}</span>
                    </div>
                </div>
                
                <h3 class="text-base font-semibold text-gray-800 mb-3">${question.question_text || 'No question text'}</h3>
            `;
            
            // Display options for multiple choice and true/false
            if (question.question_type === 'multiple' || question.question_type === 'truefalse') {
                if (options && options.length > 0) {
                    questionHtml += `<div class="space-y-2 mb-3">`;
                    
                    options.forEach((option, optIndex) => {
                        const isCorrect = answerKey.correctAnswers && answerKey.correctAnswers.includes(option);
                        const optionClass = isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200';
                        const letter = String.fromCharCode(65 + optIndex);
                        
                        questionHtml += `
                            <div class="flex items-start p-2 rounded border ${optionClass}">
                                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full ${isCorrect ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'} text-xs font-semibold mr-2 mt-0.5">
                                    ${letter}
                                </span>
                                <span class="text-sm ${isCorrect ? 'text-green-700 font-medium' : 'text-gray-700'}">${option || `Option ${optIndex + 1}`}</span>
                                ${isCorrect ? '<span class="ml-auto text-green-600 text-xs font-semibold"><i class="fas fa-check mr-1"></i>Correct</span>' : ''}
                            </div>
                        `;
                    });
                    
                    questionHtml += `</div>`;
                }
            }
            
            // Display answer for short answer and identification
            if (question.question_type === 'shortanswer' || question.question_type === 'identification') {
                const correctAnswer = answerKey.correctAnswers && answerKey.correctAnswers.length > 0 ? 
                    answerKey.correctAnswers[0] : 'No answer provided';
                
                questionHtml += `
                    <div class="mb-3">
                        <div class="bg-green-50 border border-green-200 rounded p-3">
                            <div class="flex items-center mb-1">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="font-medium text-green-800 text-sm">Expected Answer:</span>
                            </div>
                            <p class="text-green-700 text-sm pl-6">${correctAnswer}</p>
                        </div>
                    </div>
                `;
            }
            
            // Add a separator if not the last question
            if (index < examData.questions.length - 1) {
                questionHtml += `<div class="border-t border-gray-100 mt-4 pt-4"></div>`;
            }
            
            questionDiv.innerHTML = questionHtml;
            questionsContainer.appendChild(questionDiv);
        });
    }
    
    function getQuestionTypeLabel(type) {
        const labels = {
            'multiple': 'Multiple Choice',
            'truefalse': 'True/False',
            'shortanswer': 'Short Answer',
            'identification': 'Identification'
        };
        return labels[type] || type;
    }
    
    function approveExam() {
        const examTitle = currentExamData?.title || 'Examination';
        
        showSweetAlert({
            title: 'Approve Examination?',
            html: `Are you sure you want to approve <strong>"${examTitle}"</strong>?<br><br>This examination will be moved to the Examination Repository.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Call the updateExamStatus function for APPROVAL
                updateExamStatus(currentExamId, 'approved', `Examination "${examTitle}" has been approved.`);
            }
        });
    }
    
    function rejectExam() {
        const examTitle = currentExamData?.title || 'Examination';
        document.getElementById('reject_exam_name').textContent = `"${examTitle}"`;
        document.getElementById('reject_exam_reason').value = '';
        
        view_exam_modal.close();
        reject_exam_modal.showModal();
    }
    
    function confirmExamReject() {
        const reason = document.getElementById('reject_exam_reason').value;
        const examTitle = currentExamData?.title || 'Examination';
        
        // Close reject modal first
        reject_exam_modal.close();
        
        showSweetAlert({
            title: 'Confirm Rejection',
            html: `Are you sure you want to reject <strong>"${examTitle}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true,
            focusConfirm: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Call the updateExamStatus function for REJECTION
                updateExamStatus(currentExamId, 'rejected', `Examination "${examTitle}" has been rejected.`, reason);
            } else {
                // Reopen reject modal if user cancels
                setTimeout(() => {
                    reject_exam_modal.showModal();
                }, 100);
            }
        });
    }
    
    function forExamCompliance() {
        const examTitle = currentExamData?.title || 'Examination';
        document.getElementById('compliance_exam_name').textContent = `"${examTitle}"`;
        document.getElementById('compliance_exam_requirements').value = '';
        
        view_exam_modal.close();
        compliance_exam_modal.showModal();
    }
    
    function confirmExamCompliance() {
        const requirements = document.getElementById('compliance_exam_requirements').value;
        const examTitle = currentExamData?.title || 'Examination';
        
        if (!requirements.trim()) {
            showSweetAlert({
                title: 'Requirements Needed',
                text: 'Please specify compliance requirements before marking for compliance.',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
            });
            return;
        }
        
        // Close compliance modal first
        compliance_exam_modal.close();

        showSweetAlert({
            title: 'Confirm Compliance',
            html: `Mark <strong>"${examTitle}"</strong> for compliance?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Mark for Compliance!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Call the updateExamStatus function for COMPLIANCE
                updateExamStatus(currentExamId, 'compliance', `Examination "${examTitle}" has been marked for compliance.`, requirements);
            } else {
                // Reopen compliance modal if user cancels
                setTimeout(() => {
                    compliance_exam_modal.showModal();
                }, 100);
            }
        });
    }
    
    function updateExamStatus(examId, newStatus, successMessage, remarks = '') {
        // Create form data
        const formData = new FormData();
        formData.append('exam_id', examId);
        formData.append('new_status', newStatus);
        formData.append('remarks', remarks);
        
        // Send AJAX request
        fetch('update_exam_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSweetAlert({
                    title: 'Success!',
                    text: successMessage,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10b981',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Close modals
                view_exam_modal.close();
                
                // Remove the exam card from UI
                removeExamCard(examId);
                
                // Check if there are any exams left
                setTimeout(() => {
                    const remainingExams = document.querySelectorAll('.exam-card');
                    if (remainingExams.length === 0) {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                showSweetAlert({
                    title: 'Error!',
                    text: 'Error: ' + data.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showSweetAlert({
                title: 'Network Error!',
                text: 'Please check console for details.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444'
            });
        });
    }
    
    function removeExamCard(examId) {
        // Find and remove the exam card
        const examCards = document.querySelectorAll('.exam-card');
        examCards.forEach(card => {
            const button = card.querySelector('button[onclick*="viewExam"]');
            if (button && button.getAttribute('onclick').includes(examId.toString())) {
                card.style.opacity = '0.5';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                }, 500);
            }
        });
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Review Dashboard initialized');
    });

</script>
  
 <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>
