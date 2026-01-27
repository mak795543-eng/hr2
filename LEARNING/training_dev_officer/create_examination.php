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

// Get module/exam ID from URL parameter
$edit_exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;
$module_data = null;
$edit_exam_data = null;
$exam_title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : '';
$exam_description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';

$ai_generated_exam = $_SESSION['ai_generated_exam'] ?? null;
$ai_generated_exam_error = $_SESSION['ai_generated_exam_error'] ?? null;
unset($_SESSION['ai_generated_exam'], $_SESSION['ai_generated_exam_error']);

if ($edit_exam_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM examinations WHERE id = ? AND status IN ('pending', 'cancelled')");
    $stmt->bind_param('i', $edit_exam_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $edit_exam_data = $res->fetch_assoc();
        $exam_title = htmlspecialchars($edit_exam_data['title'] ?? '');
        $exam_description = htmlspecialchars($edit_exam_data['description'] ?? '');

        $qStmt = $conn->prepare('SELECT * FROM examination_questions WHERE examination_id = ? ORDER BY question_number');
        $qStmt->bind_param('i', $edit_exam_id);
        $qStmt->execute();
        $qRes = $qStmt->get_result();
        $questions = [];
        if ($qRes) {
            while ($row = $qRes->fetch_assoc()) {
                $questions[] = $row;
            }
        }
        $qStmt->close();
        $edit_exam_data['questions'] = $questions;

        $module_id = $edit_exam_data['module_id'] ?? null;
    }
    $stmt->close();
}

// Fetch module data if ID is provided
if ($module_id) {
    $sql = "SELECT * FROM learning_modules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $module_data = $result->fetch_assoc();
        if ($edit_exam_id <= 0) {
            $exam_title = htmlspecialchars($module_data['title'] ?? '');
            $exam_description = htmlspecialchars($module_data['topic'] ?? '');
        }
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Create Examination</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function () {
      if (!window.Swal || Swal.__hrPatched) return;

      const origFire = Swal.fire.bind(Swal);
      Swal.fire = function () {
        let opts = null;
        if (arguments.length === 1 && arguments[0] && typeof arguments[0] === 'object') {
          opts = Object.assign({}, arguments[0]);
        } else {
          opts = {
            title: arguments[0],
            html: arguments[1],
            icon: arguments[2]
          };
        }

        try {
          const openDialogs = Array.from(document.querySelectorAll('dialog[open]'));
          const topDialog = openDialogs.length ? openDialogs[openDialogs.length - 1] : null;
          if (topDialog && !opts.target) {
            opts.target = topDialog;
          }
        } catch (e) {
        }

        if (typeof opts.heightAuto === 'undefined') {
          opts.heightAuto = false;
        }

        return origFire(opts);
      };

      Swal.__hrPatched = true;
    })();
  </script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* (Keep all your existing CSS styles) */
    .question-card {
      transition: all 0.3s ease;
    }
    .question-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .option-image-preview {
      max-width: 150px;
      max-height: 100px;
      object-fit: contain;
    }
    .other-option-input {
      border: none !important;
      background: transparent !important;
      box-shadow: none !important;
      padding-left: 0 !important;
      margin-left: -8px;
      color: #6b7280 !important;
    }
    .other-option-input:focus {
      outline: none !important;
      border: none !important;
      box-shadow: none !important;
    }
    .other-option-input::placeholder {
      color: #6b7280 !important;
      font-style: italic;
    }
    .other-label {
      color: #6b7280;
      font-weight: 500;
    }
    .answer-input {
      background-color: #f9fafb;
      border-left: 4px solid #3b82f6;
    }
    .correct-answer {
      background-color: #d1fae5 !important;
      border-color: #10b981 !important;
    }
    .answer-key-indicator {
      background-color: #10b981;
      color: white;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 0.75rem;
      margin-left: 8px;
    }
    
    /* Split layout with side-by-side sections */
    .split-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      height: calc(100vh - 200px);
      transition: all 0.3s ease;
    }
    
    .exam-form-section, .module-section {
      height: 100%;
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
    }
    
    /* Independent scrolling containers */
    .exam-scroll-container {
        flex: 1;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: white;
        max-height: calc(100vh - 350px);
        min-height: 500px;
    }
    
    .module-content-container {
        flex: 1;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        max-height: calc(100vh - 350px);
        min-height: 500px;
    }
    
    /* Force scrollbar visibility */
    .exam-scroll-container {
        overflow-y: scroll !important;
    }
    
    .module-content-container {
        overflow-y: scroll !important;
    }
    
    .module-content {
      background: white;
      padding: 1.5rem;
      border-radius: 0.5rem;
      min-height: 100%;
    }
    
    .module-header {
      border-bottom: 2px solid #3b82f6;
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .module-section-content {
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .module-section-content:last-child {
      border-bottom: none;
    }
    
    .section-title {
      color: #1f2937;
      font-weight: 600;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
    }
    
    .section-title i {
      margin-right: 0.5rem;
      color: #3b82f6;
    }
    
    .section-content {
      color: #4b5563;
      line-height: 1.6;
    }
    
    .section-content ul, .section-content ol {
      padding-left: 1.5rem;
      margin-top: 0.5rem;
    }
    
    .section-content li {
      margin-bottom: 0.5rem;
    }
    
    .highlight-box {
      background-color: #eff6ff;
      border-left: 4px solid #3b82f6;
      padding: 1rem;
      margin: 1rem 0;
      border-radius: 0 0.375rem 0.375rem 0;
    }
    
    /* Form Styling */
    .main-form-container {
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    
    /* Custom scrollbar styling - ENHANCED */
    .exam-scroll-container::-webkit-scrollbar,
    .module-content-container::-webkit-scrollbar {
        width: 10px;
    }
    
    .exam-scroll-container::-webkit-scrollbar-track,
    .module-content-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
        margin: 4px;
    }
    
    .exam-scroll-container::-webkit-scrollbar-thumb,
    .module-content-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
        border: 2px solid #f1f5f9;
    }
    
    .exam-scroll-container::-webkit-scrollbar-thumb:hover,
    .module-content-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* Firefox scrollbar */
    .exam-scroll-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    
    .module-content-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    
    /* Module info display */
    .module-info-display {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .module-info-header {
      display: flex;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    
    .module-info-header i {
      color: #3b82f6;
      margin-right: 0.5rem;
    }
    
    /* Button adjustments */
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
    }
    
    .action-buttons .btn {
      min-height: 2.5rem;
      padding-left: 1rem;
      padding-right: 1rem;
      font-size: 0.875rem;
      white-space: nowrap;
    }
    
    .btn-compact {
      padding-left: 0.75rem !important;
      padding-right: 0.75rem !important;
      font-size: 0.8125rem !important;
    }
    
    .btn-icon-text {
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }
    
    /* Toggle button for module */
    .module-toggle-btn {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      z-index: 10;
    }
    
    /* Independent scrolling sections */
    .scroll-container {
      height: 100%;
      overflow-y: auto;
    }
    
    /* Show Module Button in header */
    .header-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    /* Preview Modal Styles */
    .preview-question {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .preview-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      margin-bottom: 0.5rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      background: #f9fafb;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .preview-option:hover {
      background: #f1f5f9;
      border-color: #cbd5e1;
    }
    
    .preview-option input[type="radio"],
    .preview-option input[type="checkbox"] {
      margin-right: 0.5rem;
    }
    
    .preview-points {
      background: #3b82f6;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    /* Read-only fields */
    .readonly-field {
      background-color: #f8fafc !important;
      border-color: #e2e8f0 !important;
      color: #64748b !important;
      cursor: not-allowed !important;
    }
    
    /* Required field styling */
    .required-field::after {
      content: " *";
      color: #ef4444;
    }
    
    .error-field {
      border-color: #ef4444 !important;
      background-color: #fef2f2 !important;
    }
    
    .error-message {
      color: #ef4444;
      font-size: 0.875rem;
      margin-top: 0.25rem;
    }
    
    /* Responsive layout */
    @media (max-width: 1024px) {
      .split-layout {
        grid-template-columns: 1fr;
        height: auto;
        gap: 1.5rem;
      }
      
      .exam-form-section, .module-section {
        height: auto;
        min-height: 500px;
      }
      
      .exam-scroll-container, .module-content-container {
        max-height: none;
        min-height: 400px;
      }
    }
    
    @media (max-width: 768px) {
      .action-buttons {
        flex-direction: column;
        width: 100%;
      }
      
      .action-buttons .btn {
        width: 100%;
        justify-content: center;
      }
      
      .header-actions {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
      }
      
      .exam-scroll-container, .module-content-container {
        min-height: 300px;
      }
    }

    /* Ensure content is long enough to require scrolling */
    .questions-content {
        min-height: 800px;
    }
    
    /* Auto-save status */
    .auto-save-status {
      font-size: 0.75rem;
      color: #6b7280;
    }
    
    /* Loading state for buttons */
    .btn-loading {
      position: relative;
      color: transparent !important;
    }
    
    .btn-loading::after {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
      top: 50%;
      left: 50%;
      margin-left: -8px;
      margin-top: -8px;
      border: 2px solid #ffffff;
      border-radius: 50%;
      border-right-color: transparent;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
    
    /* Add these new styles for the success message */
    .success-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .swal2-container {
      position: fixed !important;
      inset: 0 !important;
      z-index: 2147483647 !important;
      pointer-events: auto !important;
    }

    .swal2-popup {
      z-index: 2147483647 !important;
    }

    .swal2-actions {
      gap: 0.5rem !important;
      width: 100% !important;
      margin-top: 1rem !important;
    }

    .swal2-styled {
      padding: 0.75rem 1rem !important;
      border-radius: 0.375rem !important;
      font-weight: 600 !important;
      border: none !important;
    }

    .swal2-confirm,
    .swal2-cancel {
      flex: 1 !important;
      min-height: 44px !important;
    }

    .swal2-confirm {
      background-color: #3b82f6 !important;
      color: #ffffff !important;
    }

    .swal2-cancel {
      background-color: #6b7280 !important;
      color: #ffffff !important;
    }

    .swal2-actions-visible {
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 0.5rem !important;
      width: 100% !important;
      justify-content: center !important;
      margin-top: 1rem !important;
    }

    .swal2-actions-hidden {
      display: none !important;
    }

    .swal2-confirm-button,
    .swal2-cancel-button {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      visibility: visible !important;
      opacity: 1 !important;
      min-height: 44px !important;
      padding: 0.75rem 1rem !important;
      border-radius: 0.375rem !important;
      font-weight: 600 !important;
      border: none !important;
      width: 140px !important;
    }

    .swal2-confirm-button {
      background-color: #3b82f6 !important;
      color: #ffffff !important;
    }

    .swal2-cancel-button {
      background-color: #6b7280 !important;
      color: #ffffff !important;
    }

  </style>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#3b82f6',
            secondary: '#10b981',
            accent: '#8b5cf6',
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-50 min-h-screen">
 <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>

  <div class="flex h-screen" id="main-container">
    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto content-area">
      <!-- Navbar -->
      
      <main class="container mx-auto px-4 py-8">
        <!-- Header with Back Button -->
        <div class="mb-6 header-actions">
          <button onclick="window.location.href = 'examination_repository.php';" class="btn btn-ghost btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Exam Repository
          </button>
        </div>

        <!-- Split Layout Container -->
        <div class="split-layout" id="splitLayout">
          <!-- Left: Examination Form -->
          <div class="exam-form-section">
            <!-- Main Form Container -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden main-form-container">
              <form id="examForm">
                <!-- Form Sections Container -->
                <div class="exam-scroll-container" id="examScrollContainer">
                  <!-- Section 1: Exam Details -->
                  <div class="p-6 md:p-8 border-b border-gray-200">
                    <div class="flex items-center mb-6">
                      <div class="bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                        <span>1</span>
                      </div>
                      <h2 class="text-xl font-bold text-gray-800">Examination Details</h2>
                    </div>

                    <div class="space-y-6">
                      <!-- Exam Title (Read-only) -->
                      <div class="form-control">
                        <label class="label">
                          <span class="label-text font-semibold text-gray-700">Examination Title</span>
                        </label>
                        <input type="text" id="examTitle" name="examTitle" 
                          class="input input-bordered w-full readonly-field" 
                          value="<?php echo $exam_title; ?>" readonly>
                        <div class="text-sm text-gray-500 mt-1">
                          <i class="fas fa-info-circle mr-1"></i>
                          Title is fetched from the module and cannot be edited
                        </div>
                      </div>

                      <!-- Description (Read-only) -->
                      <div class="form-control">
                        <label class="label">
                          <span class="label-text font-semibold text-gray-700">Description</span>
                        </label>
                        <textarea id="examDescription" name="examDescription" 
                          class="textarea textarea-bordered h-24 readonly-field" readonly><?php echo $exam_description; ?></textarea>
                        <div class="text-sm text-gray-500 mt-1">
                          <i class="fas fa-info-circle mr-1"></i>
                          Description is fetched from the module and cannot be edited
                        </div>
                      </div>

                      <!-- Module Information Display -->
                      <?php if ($module_data): ?>
                      <div class="module-info-display">
                        <div class="module-info-header">
                          <i class="fas fa-book"></i>
                          <h3 class="font-semibold text-gray-800">Based on Module</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                            <span class="font-medium text-gray-700">Module:</span>
                            <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($module_data['title']); ?></span>
                          </div>
                          <div>
                            <span class="font-medium text-gray-700">Department:</span>
                            <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($module_data['department']); ?></span>
                          </div>
                          <div>
                            <span class="font-medium text-gray-700">Roles:</span>
                            <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($module_data['roles']); ?></span>
                          </div>
                          <div>
                            <span class="font-medium text-gray-700">Created:</span>
                            <span class="ml-2 text-gray-600"><?php echo date('M j, Y', strtotime($module_data['created_at'])); ?></span>
                          </div>
                        </div>
                        <input type="hidden" id="moduleSelect" name="moduleSelect" value="<?php echo $module_data['id']; ?>">
                        <input type="hidden" id="moduleTitle" value="<?php echo htmlspecialchars($module_data['title']); ?>">
                        <input type="hidden" id="moduleDepartment" value="<?php echo htmlspecialchars($module_data['department']); ?>">
                        <input type="hidden" id="moduleRoles" value="<?php echo htmlspecialchars($module_data['roles']); ?>">
                      </div>
                      <?php else: ?>
                      <div class="alert alert-warning">
                        <div>
                          <i class="fas fa-exclamation-triangle"></i>
                          <span>No module selected. Please select a module from the examination repository.</span>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Section 2: Questions -->
                  <div class="p-6 md:p-8 questions-content">
                    <div class="flex items-center mb-6">
                      <div class="bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                        <span>2</span>
                      </div>
                      <h2 class="text-xl font-bold text-gray-800">Examination Questions</h2>
                    </div>

                    <p class="text-gray-600 mb-6">Add questions to your examination. You can add multiple choice, true/false, short answer, or identification questions.</p>

                    <!-- Questions Container -->
                    <div id="questionsContainer" class="space-y-6 mb-6">
                      <!-- Questions will be added here dynamically -->
                    </div>

                    <!-- Add Question Button -->
                    <div class="flex justify-center">
                      <button type="button" id="addQuestion" class="btn btn-outline btn-compact">
                        <i class="fas fa-plus mr-2"></i>
                        Add Question
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-gray-50 p-6 md:p-8 border-t border-gray-200">
                  <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="action-buttons">
                      <button type="button" onclick="window.location.href='examination_repository.php'" class="btn btn-ghost btn-compact">
                        <span class="btn-icon-text">
                          <i class="fas fa-arrow-left"></i>
                          <span>Back</span>
                        </span>
                      </button>
                      <button type="button" id="saveDraft" class="btn btn-outline btn-compact">
                        <span class="btn-icon-text">
                          <i class="fas fa-save"></i>
                          <span>Save Draft</span>
                        </span>
                      </button>
                      <button type="button" id="clearDraft" class="btn btn-outline btn-compact">
                        <span class="btn-icon-text">
                          <i class="fas fa-trash"></i>
                          <span>Clear Draft</span>
                        </span>
                      </button>
                    </div>
                    <div class="action-buttons">
                      <button type="button" id="previewExam" class="btn btn-outline btn-compact">
                        <span class="btn-icon-text">
                          <i class="fas fa-eye"></i>
                          <span>Preview</span>
                        </span>
                      </button>
                      <button type="button" id="createExamBtn" class="btn btn-primary btn-compact">
                        <span class="btn-icon-text">
                          <i class="fas fa-check-circle"></i>
                          <span>Create Exam</span>
                        </span>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Auto-save status -->
                  <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center text-sm text-gray-600">
                      <span id="autoSaveStatus">
                        <i class="fas fa-save mr-1"></i>
                        Changes are auto-saved every 30 seconds
                      </span>
                      <span id="lastSaved" class="text-xs"></span>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Right: Learning Module Content -->
          <div class="module-section">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden h-full flex flex-col">
              <div class="bg-primary text-white p-4 relative">
                <h2 class="text-xl font-bold">
                  <i class="fas fa-book mr-2"></i>
                  Learning Module
                </h2>
              </div>
              
              <div class="p-4 flex-1 flex flex-col">
                <?php if ($module_data): ?>
                  <div class="module-content-container" id="moduleScrollContainer">
                    <div class="module-content">
                      <div class="module-header">
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($module_data['title']); ?></h3>
                        <div class="flex flex-wrap gap-2">
                          <span class="badge badge-primary"><?php echo htmlspecialchars($module_data['department']); ?></span>
                          <span class="badge badge-secondary"><?php echo htmlspecialchars($module_data['roles']); ?></span>
                        </div>
                      </div>
                      
                      <?php if (!empty($module_data['content'])): ?>
                        <div class="module-section-content">
                          <h4 class="section-title">
                            <i class="fas fa-file-alt"></i>
                            Module Content
                          </h4>
                          <div class="section-content">
                            <?php 
                            if (strip_tags($module_data['content']) === $module_data['content']) {
                                echo nl2br(htmlspecialchars($module_data['content']));
                            } else {
                                echo $module_data['content'];
                            }
                            ?>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($module_data['learning_objectives'])): ?>
                        <div class="module-section-content">
                          <h4 class="section-title">
                            <i class="fas fa-bullseye"></i>
                            Learning Objectives
                          </h4>
                          <div class="section-content">
                            <?php 
                            if (strip_tags($module_data['learning_objectives']) === $module_data['learning_objectives']) {
                                echo nl2br(htmlspecialchars($module_data['learning_objectives']));
                            } else {
                                echo $module_data['learning_objectives'];
                            }
                            ?>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($module_data['key_points'])): ?>
                        <div class="module-section-content">
                          <h4 class="section-title">
                            <i class="fas fa-key"></i>
                            Key Points
                          </h4>
                          <div class="section-content">
                            <?php 
                            if (strip_tags($module_data['key_points']) === $module_data['key_points']) {
                                echo nl2br(htmlspecialchars($module_data['key_points']));
                            } else {
                                echo $module_data['key_points'];
                            }
                            ?>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <!-- Add more content to ensure scrolling -->
                      <div class="module-section-content">
                        <h4 class="section-title">
                          <i class="fas fa-info-circle"></i>
                          Additional Information
                        </h4>
                        <div class="section-content">
                          <p>This section contains additional information about the module that extends the content to ensure scrolling is available.</p>
                          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                          <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                          <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                          <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        </div>
                      </div>
                      
                      <div class="highlight-box">
                        <p class="font-medium text-primary">Use this module as a reference while creating your examination questions.</p>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="text-center py-8 flex-1 flex items-center justify-center">
                    <div>
                      <i class="fas fa-book-open text-4xl text-gray-400 mb-4"></i>
                      <p class="text-gray-500">No module selected</p>
                      <p class="text-sm text-gray-400 mt-2">Select a module from the examination repository to view its content here.</p>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Answer Key Modal -->
  <div id="answerKeyModal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">Set Answer Key</h3>
      
      <div id="modalQuestionContent" class="mb-6">
        <!-- Question content will be inserted here -->
      </div>
      
      <div class="form-control mb-4">
        <label class="label">
          <span class="label-text font-semibold required-field">Points</span>
        </label>
        <input type="number" id="questionPoints" min="1" max="100" value="1" 
          class="input input-bordered w-24" required>
      </div>
      
      <div id="answerKeyOptions" class="mb-6">
        <!-- Answer options will be inserted here based on question type -->
      </div>

      <div id="answerKeyError" class="text-error text-sm mb-4 hidden">
        <i class="fas fa-exclamation-circle mr-1"></i>
        <span>Please select at least one correct answer</span>
      </div>
      
      <div class="modal-action">
        <button class="btn btn-ghost btn-compact" id="closeAnswerKeyModal">Cancel</button>
        <button class="btn btn-primary btn-compact" id="saveAnswerKey">Save Answer Key</button>
      </div>
    </div>
  </div>

  <!-- Preview Exam Modal -->
  <div id="previewExamModal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-2xl">Examination Preview</h3>
        <button class="btn btn-circle btn-ghost btn-sm" id="closePreviewModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto pr-4">
        <div id="previewExamContent">
          <!-- Preview content will be inserted here -->
        </div>
      </div>
      
      <div class="modal-action mt-6 pt-4 border-t border-gray-200">
        <button class="btn btn-ghost btn-compact" id="closePreviewModalBtn">Close</button>
        <button class="btn btn-primary btn-compact" id="printPreview">
          <i class="fas fa-print mr-2"></i>
          Print
        </button>
      </div>
    </div>
  </div>

  <!-- Question Template (Hidden) -->
  <template id="questionTemplate">
    <div class="question-item bg-base-100 border border-gray-200 rounded-xl p-5 shadow-sm transition-all duration-300 hover:shadow-md" data-question-id="">
      <div class="flex justify-between items-start mb-4">
        <div class="flex items-center">
          <span class="question-number font-bold text-lg text-primary mr-3">Q1</span>
          <select class="question-type select select-bordered select-sm focus:select-primary">
            <option value="multiple">Multiple Choice</option>
            <option value="truefalse">True/False</option>
            <option value="shortanswer">Short Answer</option>
            <option value="identification">Identification</option>
          </select>
        </div>
        <button type="button" class="remove-question btn btn-circle btn-ghost btn-sm text-error">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="form-control">
        <label class="label">
          <span class="label-text font-semibold text-gray-700 required-field">Question Text</span>
        </label>
        <input type="text" class="question-text input input-bordered w-full mb-4 focus:input-primary" placeholder="Enter your question" required>
      </div>
      
      <!-- Options Container (for Multiple Choice and True/False) -->
      <div class="options-container mt-4 space-y-3">
        <!-- Options will be dynamically added based on question type -->
      </div>
      
      <!-- Answer Input (for Short Answer and Identification) -->
      <div class="answer-container mt-4 hidden">
        <div class="form-control">
          <label class="label">
            <span class="label-text font-semibold text-gray-700 required-field">Expected Answer</span>
          </label>
          <input type="text" class="answer-input input input-bordered w-full focus:input-primary" 
            placeholder="Enter the expected answer for this question" required>
        </div>
      </div>
      
      <!-- Add Options (for Multiple Choice only) -->
      <div class="add-options-container mt-4 hidden">
        <div class="flex items-center text-sm">
          <span class="text-gray-600 mr-2">Add</span>
          <button type="button" class="add-regular-option text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
            option
          </button>
          <span class="text-gray-600 mx-2">or</span>
          <button type="button" class="add-other-option text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
            "other"
          </button>
        </div>
      </div>

      <!-- Options Required Message -->
      <div id="optionsRequiredMessage" class="mt-3 text-error text-sm hidden">
        <i class="fas fa-exclamation-circle mr-1"></i>
        <span>Please add at least 2 options for multiple choice questions</span>
      </div>
      
      <!-- Answer Key Section -->
      <div class="answer-key-section mt-6 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <span class="text-sm text-gray-600">Answer Key:</span>
            <span id="answerKeyStatus" class="text-sm font-medium text-gray-800 ml-2">Not set</span>
            <span id="pointsDisplay" class="text-sm text-gray-600 ml-2"></span>
          </div>
          <button type="button" class="set-answer-key-btn btn btn-sm btn-outline btn-compact">
            <span class="btn-icon-text">
              <i class="fas fa-key"></i>
              <span>Set Key</span>
            </span>
          </button>
        </div>
        <div id="answerKeyRequired" class="mt-2 text-error text-sm hidden">
          <i class="fas fa-exclamation-circle mr-1"></i>
          <span>Answer key is required for this question</span>
        </div>
        <div id="correctAnswersPreview" class="mt-2 text-sm text-gray-700 hidden">
          <!-- Correct answers will be displayed here -->
        </div>
      </div>
    </div>
  </template>

  <script>
    window.__AI_GENERATED_EXAM = <?php echo json_encode($ai_generated_exam, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.__AI_GENERATED_EXAM_ERROR = <?php echo json_encode($ai_generated_exam_error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.__EDIT_EXAM = <?php echo json_encode($edit_exam_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Module information data
      const moduleData = {
        <?php if ($module_data): ?>
          '<?php echo $module_data['id']; ?>': {
            department: "<?php echo $module_data['department']; ?>",
            roles: "<?php echo $module_data['roles']; ?>"
          }
        <?php endif; ?>
      };
      
      // Answer key data storage
      const answerKeys = {};
      let currentQuestionId = null;
      
      // Question management
      const questionsContainer = document.getElementById('questionsContainer');
      const addQuestionBtn = document.getElementById('addQuestion');
      const questionTemplate = document.getElementById('questionTemplate');
      const createExamBtn = document.getElementById('createExamBtn');
      let questionCount = 0;
      
      // Answer Key Modal Elements
      const answerKeyModal = document.getElementById('answerKeyModal');
      const modalQuestionContent = document.getElementById('modalQuestionContent');
      const answerKeyOptions = document.getElementById('answerKeyOptions');
      const questionPoints = document.getElementById('questionPoints');
      const closeAnswerKeyModal = document.getElementById('closeAnswerKeyModal');
      const saveAnswerKey = document.getElementById('saveAnswerKey');
      const answerKeyError = document.getElementById('answerKeyError');
      
      // Preview Modal Elements
      const previewExamModal = document.getElementById('previewExamModal');
      const previewExamContent = document.getElementById('previewExamContent');
      const closePreviewModal = document.getElementById('closePreviewModal');
      const closePreviewModalBtn = document.getElementById('closePreviewModalBtn');
      const printPreview = document.getElementById('printPreview');
      
      // Auto-save functionality
      let autoSaveInterval;
      
      // Initialize independent scrolling
      function initializeIndependentScrolling() {
        const examScrollContainer = document.getElementById('examScrollContainer');
        const moduleScrollContainer = document.getElementById('moduleScrollContainer');
        
        // Force scrollbars to be always visible
        examScrollContainer.style.overflowY = 'scroll';
        if (moduleScrollContainer) {
          moduleScrollContainer.style.overflowY = 'scroll';
        }
        
        // Add mouse enter events for exam section
        examScrollContainer.addEventListener('mouseenter', function() {
          if (moduleScrollContainer) {
            moduleScrollContainer.style.overflowY = 'hidden';
          }
        });
        
        // Add mouse enter events for module section
        if (moduleScrollContainer) {
          moduleScrollContainer.addEventListener('mouseenter', function() {
            examScrollContainer.style.overflowY = 'hidden';
          });
        }
        
        // Add mouse leave events to restore scrolling
        examScrollContainer.addEventListener('mouseleave', function() {
          if (moduleScrollContainer) {
            moduleScrollContainer.style.overflowY = 'scroll';
          }
        });
        
        if (moduleScrollContainer) {
          moduleScrollContainer.addEventListener('mouseleave', function() {
            examScrollContainer.style.overflowY = 'scroll';
          });
        }
      }
      
      // Auto-save functionality
      function startAutoSave() {
        // Save immediately
        saveFormState();
        
        // Then save every 30 seconds
        autoSaveInterval = setInterval(saveFormState, 30000);
      }
      
      function stopAutoSave() {
        if (autoSaveInterval) {
          clearInterval(autoSaveInterval);
        }
      }
      
      function saveFormState() {
        const formState = {
          examTitle: document.getElementById('examTitle').value,
          examDescription: document.getElementById('examDescription').value,
          moduleSelect: document.getElementById('moduleSelect')?.value || '',
          questions: [],
          answerKeys: {...answerKeys},
          timestamp: new Date().toISOString()
        };

        // Save all questions
        const questionElements = document.querySelectorAll('.question-item');
        questionElements.forEach((question, index) => {
          const questionId = question.getAttribute('data-question-id');
          const questionText = question.querySelector('.question-text').value || `Question ${index + 1}`;
          const questionType = question.querySelector('.question-type').value;
          const answerKeyData = answerKeys[questionId] || { points: 1, correctAnswers: [] };
          
          const questionData = {
            id: questionId,
            number: index + 1,
            type: questionType,
            text: questionText,
            options: []
          };

          // Save options for multiple choice questions
          if (questionData.type === 'multiple' || questionData.type === 'truefalse') {
            const options = question.querySelectorAll('.option-input');
            options.forEach(option => {
              questionData.options.push({
                value: option.value,
                isOther: option.hasAttribute('data-other')
              });
            });
          }

          // Save answer for short answer/identification
          if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
            const answerInput = question.querySelector('.answer-input');
            questionData.answer = answerInput ? answerInput.value : '';
          }

          formState.questions.push(questionData);
        });

        // Save to localStorage with a unique key based on module
        const moduleId = formState.moduleSelect || 'new_exam';
        localStorage.setItem(`exam_draft_${moduleId}`, JSON.stringify(formState));
        
        // Update last saved time
        const lastSavedElement = document.getElementById('lastSaved');
        if (lastSavedElement) {
          lastSavedElement.textContent = `Last saved: ${new Date().toLocaleTimeString()}`;
        }
        
        console.log('Form state saved');
      }
      
      function loadFormState() {
        const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
        const savedState = localStorage.getItem(`exam_draft_${moduleId}`);
        
        if (!savedState) return false;

        try {
          const formState = JSON.parse(savedState);
          
          // Restore basic form data
          document.getElementById('examTitle').value = formState.examTitle || '';
          document.getElementById('examDescription').value = formState.examDescription || '';
          
          // Restore answer keys
          Object.keys(formState.answerKeys || {}).forEach(key => {
            answerKeys[key] = formState.answerKeys[key];
          });

          // Clear existing questions
          questionsContainer.innerHTML = '';
          questionCount = 0;

          // Restore questions
          if (formState.questions && formState.questions.length > 0) {
            formState.questions.forEach(questionData => {
              restoreQuestion(questionData);
            });
          } else {
            // Add a default question if none were saved
            addQuestion();
          }

          console.log('Form state restored');
          return true;
        } catch (error) {
          console.error('Error loading saved state:', error);
          return false;
        }
      }
      
      function restoreQuestion(questionData) {
        questionCount++;
        const questionClone = document.importNode(questionTemplate.content, true);
        const questionDiv = questionClone.querySelector('.question-item');
        const questionId = questionData.id || `question_${Date.now()}_${questionCount}`;
        questionDiv.setAttribute('data-question-id', questionId);

        // Update question number
        questionDiv.querySelector('.question-number').textContent = `Q${questionCount}`;

        // Set question text
        const questionText = questionDiv.querySelector('.question-text');
        questionText.value = questionData.text || '';

        // Set question type
        const questionType = questionDiv.querySelector('.question-type');
        questionType.value = questionData.type || 'multiple';

        // Get containers
        const optionsContainer = questionDiv.querySelector('.options-container');
        const answerContainer = questionDiv.querySelector('.answer-container');
        const addOptionsContainer = questionDiv.querySelector('.add-options-container');
        const addRegularOptionBtn = questionDiv.querySelector('.add-regular-option');
        const addOtherOptionBtn = questionDiv.querySelector('.add-other-option');

        // Initialize based on question type
        if (questionData.type === 'multiple') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.remove('hidden');
          
          // Restore options
          if (questionData.options && questionData.options.length > 0) {
            questionData.options.forEach(optionData => {
              const optionDiv = createOptionElement(optionData.value, false, optionData.isOther);
              optionsContainer.appendChild(optionDiv);
            });
          } else {
            // Add default options
            for (let i = 0; i < 2; i++) {
              const optionDiv = createOptionElement('', false, false);
              optionsContainer.appendChild(optionDiv);
            }
          }
        } else if (questionData.type === 'truefalse') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.add('hidden');
          
          // Add True/False options
          const trueOption = createOptionElement('True', true);
          const falseOption = createOptionElement('False', true);
          optionsContainer.appendChild(trueOption);
          optionsContainer.appendChild(falseOption);
        } else if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
          optionsContainer.style.display = 'none';
          answerContainer.style.display = 'block';
          addOptionsContainer.classList.add('hidden');
          
          // Restore answer
          const answerInput = answerContainer.querySelector('.answer-input');
          if (answerInput && questionData.answer) {
            answerInput.value = questionData.answer;
          }
        }

        // Add to container
        questionsContainer.appendChild(questionDiv);

        // Add event listeners
        const removeQuestionBtn = questionDiv.querySelector('.remove-question');
        const setAnswerKeyBtn = questionDiv.querySelector('.set-answer-key-btn');

        questionType.addEventListener('change', function() {
          updateQuestionType(this, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
          answerKeys[questionId].questionType = this.value;
          answerKeys[questionId].correctAnswers = [];
          updateAnswerKeyDisplay(questionDiv);
          saveFormState(); // Auto-save on change
        });

        addRegularOptionBtn?.addEventListener('click', function() {
          addOption(optionsContainer, false);
          saveFormState(); // Auto-save on change
        });

        addOtherOptionBtn?.addEventListener('click', function() {
          addOption(optionsContainer, true);
          saveFormState(); // Auto-save on change
        });

        removeQuestionBtn.addEventListener('click', function() {
          questionDiv.classList.add('opacity-0', 'translate-y-4');
          setTimeout(() => {
            delete answerKeys[questionId];
            questionDiv.remove();
            updateQuestionNumbers();
            saveFormState(); // Auto-save on change
          }, 300);
        });

        setAnswerKeyBtn.addEventListener('click', function() {
          openAnswerKeyModal(questionDiv, questionId);
        });

        // Update answer key display if it exists
        if (answerKeys[questionId]) {
          updateAnswerKeyDisplay(questionDiv);
        }

        // Add input listeners for auto-save
        questionText.addEventListener('input', saveFormState);
        
        const optionInputs = questionDiv.querySelectorAll('.option-input');
        optionInputs.forEach(input => {
          input.addEventListener('input', saveFormState);
        });

        const answerInput = questionDiv.querySelector('.answer-input');
        if (answerInput) {
          answerInput.addEventListener('input', saveFormState);
        }
      }
      
      function clearSavedDraft() {
        const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
        localStorage.removeItem(`exam_draft_${moduleId}`);
        showToast('Draft cleared', 'success');
        
        // Reload the page to start fresh
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }
      
      // Function to generate preview content (Student View)
      function generatePreviewContent() {
        const examTitle = document.getElementById('examTitle').value || 'Untitled Examination';
        const examDescription = document.getElementById('examDescription').value || 'No description provided';
        const questions = questionsContainer.querySelectorAll('.question-item');
        
        let previewHTML = `
          <div class="preview-header bg-primary text-white p-6 rounded-t-2xl mb-6">
            <h1 class="text-3xl font-bold mb-2">${examTitle}</h1>
            <p class="text-primary-content opacity-90">${examDescription}</p>
          </div>
          
          <div class="preview-instructions bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2">Instructions:</h3>
            <ul class="text-blue-700 text-sm space-y-1">
              <li>â€¢ Read each question carefully before answering</li>
              <li>â€¢ Select the best answer for each question</li>
              <li>â€¢ You cannot go back to previous questions once answered</li>
              <li>â€¢ Ensure all answers are final before submitting</li>
            </ul>
          </div>
          
          <form id="studentExamForm" class="preview-questions space-y-6">
        `;
        
        if (questions.length === 0) {
          previewHTML += `
            <div class="text-center py-8">
              <i class="fas fa-question-circle text-4xl text-gray-400 mb-4"></i>
              <p class="text-gray-500 text-lg">No questions added yet</p>
              <p class="text-gray-400">Add questions to see the preview</p>
            </div>
          `;
        } else {
          questions.forEach((question, index) => {
            const questionId = question.getAttribute('data-question-id');
            const questionText = question.querySelector('.question-text').value || `Question ${index + 1}`;
            const questionType = question.querySelector('.question-type').value;
            const answerKeyData = answerKeys[questionId] || { points: 1, correctAnswers: [] };
            
            previewHTML += `
              <div class="preview-question">
                <div class="flex justify-between items-start mb-4">
                  <div class="flex items-center">
                    <span class="question-number font-bold text-lg text-primary mr-3">Q${index + 1}</span>
                    <span class="question-type-badge badge badge-outline badge-sm">${getQuestionTypeLabel(questionType)}</span>
                  </div>
                  <span class="preview-points">${answerKeyData.points} point${answerKeyData.points > 1 ? 's' : ''}</span>
                </div>
                
                <h3 class="text-lg font-semibold text-gray-800 mb-4">${questionText}</h3>
            `;
            
            if (questionType === 'multiple' || questionType === 'truefalse') {
              const options = question.querySelectorAll('.option-input');
              previewHTML += `<div class="preview-options space-y-2">`;
              
              options.forEach((option, optIndex) => {
                const optionText = option.value || (questionType === 'truefalse' ? (optIndex === 0 ? 'True' : 'False') : `Option ${optIndex + 1}`);
                const inputName = `question_${questionId}`;
                const inputType = questionType === 'multiple' ? 'checkbox' : 'radio';
                const inputId = `option_${questionId}_${optIndex}`;
                
                previewHTML += `
                  <label class="preview-option" for="${inputId}">
                    <input type="${inputType}" id="${inputId}" name="${inputName}" value="${optionText}">
                    <span class="flex-1">${optionText}</span>
                  </label>
                `;
              });
              
              previewHTML += `</div>`;
            } else if (questionType === 'shortanswer' || questionType === 'identification') {
              previewHTML += `
                <div class="preview-answer">
                  <div class="form-control">
                    <label class="label">
                      <span class="label-text font-semibold">Your Answer:</span>
                    </label>
                    <input type="text" name="question_${questionId}" 
                      class="input input-bordered w-full" 
                      placeholder="Type your answer here...">
                  </div>
                </div>
              `;
            }
            
            previewHTML += `</div>`;
          });
        }
        
        previewHTML += `
          </form>
          <div class="text-center mt-8 pt-6 border-t border-gray-200">
            <button type="button" class="btn btn-primary btn-wide" onclick="Swal.fire({ title: 'Info', text: 'This would submit the exam in a real application', icon: 'info', confirmButtonText: 'OK', confirmButtonColor: '#3b82f6' })">
              <i class="fas fa-paper-plane mr-2"></i>
              Submit Examination
            </button>
          </div>
        `;
        
        return previewHTML;
      }
      
      // Function to get question type label
      function getQuestionTypeLabel(type) {
        const labels = {
          'multiple': 'Multiple Choice',
          'truefalse': 'True/False',
          'shortanswer': 'Short Answer',
          'identification': 'Identification'
        };
        return labels[type] || type;
      }
      
      // Function to validate question
      function validateQuestion(questionDiv) {
        const questionId = questionDiv.getAttribute('data-question-id');
        const questionText = questionDiv.querySelector('.question-text');
        const questionType = questionDiv.querySelector('.question-type').value;
        const answerKeyRequired = questionDiv.querySelector('#answerKeyRequired');
        const optionsRequiredMessage = questionDiv.querySelector('#optionsRequiredMessage');
        
        let isValid = true;
        
        // Validate question text
        if (!questionText.value.trim()) {
          questionText.classList.add('error-field');
          isValid = false;
        } else {
          questionText.classList.remove('error-field');
        }
        
        // Validate options for multiple choice
        if (questionType === 'multiple') {
          const options = questionDiv.querySelectorAll('.option-input');
          const hasEmptyOptions = Array.from(options).some(option => !option.value.trim());
          const hasEnoughOptions = options.length >= 2;
          
          if (hasEmptyOptions || !hasEnoughOptions) {
            optionsRequiredMessage.classList.remove('hidden');
            isValid = false;
          } else {
            optionsRequiredMessage.classList.add('hidden');
          }
        }
        
        // Validate answer key
        const answerKeyData = answerKeys[questionId];
        if (!answerKeyData || answerKeyData.correctAnswers.length === 0) {
          answerKeyRequired.classList.remove('hidden');
          isValid = false;
        } else {
          answerKeyRequired.classList.add('hidden');
        }
        
        return isValid;
      }
      
      // Function to validate all questions
      function validateAllQuestions() {
        const questions = questionsContainer.querySelectorAll('.question-item');
        let allValid = true;
        
        questions.forEach(question => {
          if (!validateQuestion(question)) {
            allValid = false;
          }
        });
        
        return allValid;
      }
      
      // Function to open preview modal
      function openPreviewModal() {
        // Basic validation
        const examTitle = document.getElementById('examTitle').value;
        const questions = questionsContainer.querySelectorAll('.question-item');
        
        if (!examTitle) {
          showToast('Please enter an examination title before previewing', 'warning');
          return;
        }
        
        if (questions.length === 0) {
          showToast('Please add at least one question before previewing', 'warning');
          return;
        }
        
        // Validate all questions
        if (!validateAllQuestions()) {
          showToast('Please complete all questions with required fields before previewing', 'error');
          return;
        }
        
        // Generate and set preview content
        previewExamContent.innerHTML = generatePreviewContent();
        
        // Show modal
        previewExamModal.classList.add('modal-open');
      }
      
      // Function to close preview modal
      function closePreviewModalFunc() {
        previewExamModal.classList.remove('modal-open');
      }
      
      // Function to print preview
      function printPreviewFunc() {
        const printContent = previewExamContent.innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
          <div class="p-8">
            <div class="print-content">
              ${printContent}
            </div>
          </div>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        window.location.reload();
      }
      
      // Preview button handler
      document.getElementById('previewExam').addEventListener('click', openPreviewModal);
      
      // Close preview modal handlers
      closePreviewModal.addEventListener('click', closePreviewModalFunc);
      closePreviewModalBtn.addEventListener('click', closePreviewModalFunc);
      
      // Print preview handler
      printPreview.addEventListener('click', printPreviewFunc);
      
      // Add question button handler
      addQuestionBtn.addEventListener('click', function() {
        addQuestion();
      });
      
      // Clear draft button handler
      document.getElementById('clearDraft').addEventListener('click', clearSavedDraft);
      
      // Warn user before leaving if they have unsaved changes
      let suppressBeforeUnload = false;
      window.addEventListener('beforeunload', function(e) {
        if (suppressBeforeUnload) return;
        // Check if there are any questions or changes
        const questions = questionsContainer.querySelectorAll('.question-item');
        if (questions.length > 0) {
          // Don't show warning if form is being submitted
          if (document.activeElement.type === 'submit') return;
          
          e.preventDefault();
          e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
          return 'You have unsaved changes. Are you sure you want to leave?';
        }
      });
      
      // Function to add a new question
      function addQuestion() {
        questionCount++;
        const questionClone = document.importNode(questionTemplate.content, true);
        const questionDiv = questionClone.querySelector('.question-item');
        const questionId = `question_${Date.now()}_${questionCount}`;
        questionDiv.setAttribute('data-question-id', questionId);
        
        // Initialize answer key data for this question
        answerKeys[questionId] = {
          points: 1,
          correctAnswers: [],
          questionType: 'multiple'
        };
        
        // Update question number
        questionDiv.querySelector('.question-number').textContent = `Q${questionCount}`;
        
        // Set unique IDs for inputs
        const questionInput = questionDiv.querySelector('.question-text');
        questionInput.name = `questionText${questionCount}`;
        
        const questionType = questionDiv.querySelector('.question-type');
        questionType.name = `questionType${questionCount}`;
        
        // Add to container with animation
        questionDiv.classList.add('opacity-0', 'transform', '-translate-y-4');
        questionsContainer.appendChild(questionDiv);
        
        // Animate in
        setTimeout(() => {
          questionDiv.classList.remove('opacity-0', '-translate-y-4');
          questionDiv.classList.add('opacity-100', 'translate-y-0');
        }, 10);
        
        // Add event listeners for the new question
        const optionsContainer = questionDiv.querySelector('.options-container');
        const answerContainer = questionDiv.querySelector('.answer-container');
        const addOptionsContainer = questionDiv.querySelector('.add-options-container');
        const addRegularOptionBtn = questionDiv.querySelector('.add-regular-option');
        const addOtherOptionBtn = questionDiv.querySelector('.add-other-option');
        const removeQuestionBtn = questionDiv.querySelector('.remove-question');
        const setAnswerKeyBtn = questionDiv.querySelector('.set-answer-key-btn');
        const optionsRequiredMessage = questionDiv.querySelector('#optionsRequiredMessage');
        
        // Initialize question with Multiple Choice type (default)
        initializeMultipleChoice(optionsContainer, addOptionsContainer);
        
        // Question type change handler
        questionType.addEventListener('change', function() {
          updateQuestionType(this, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
          // Update answer key data
          answerKeys[questionId].questionType = this.value;
          answerKeys[questionId].correctAnswers = [];
          updateAnswerKeyDisplay(questionDiv);
          
          // Hide options required message when switching away from multiple choice
          if (this.value !== 'multiple') {
            optionsRequiredMessage.classList.add('hidden');
          }
          
          // Auto-save on change
          saveFormState();
        });
        
        // Add option button handlers
        addRegularOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, false);
          saveFormState(); // Auto-save on change
        });
        
        addOtherOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, true);
          saveFormState(); // Auto-save on change
        });
        
        // Remove question button handler
        removeQuestionBtn.addEventListener('click', function() {
          // Animate out
          questionDiv.classList.add('opacity-0', 'translate-y-4');
          setTimeout(() => {
            // Remove from answer keys
            delete answerKeys[questionId];
            questionDiv.remove();
            updateQuestionNumbers();
            saveFormState(); // Auto-save on change
          }, 300);
        });
        
        // Set Answer Key button handler
        setAnswerKeyBtn.addEventListener('click', function() {
          openAnswerKeyModal(questionDiv, questionId);
        });
        
        // Validate question text on input
        questionInput.addEventListener('input', function() {
          if (this.value.trim()) {
            this.classList.remove('error-field');
          }
          saveFormState(); // Auto-save on change
        });
        
        // Auto-save after adding question
        setTimeout(saveFormState, 100);
      }
      
      // Function to initialize Multiple Choice question with blank options
      function initializeMultipleChoice(optionsContainer, addOptionsContainer) {
        // Clear any existing options
        optionsContainer.innerHTML = '';
        
        // Add two blank options for Multiple Choice
        for (let i = 0; i < 2; i++) {
          const optionDiv = createOptionElement('', false, false);
          optionsContainer.appendChild(optionDiv);
        }
        
        // Show add options container
        addOptionsContainer.classList.remove('hidden');
      }
      
      // Function to open Answer Key Modal
      function openAnswerKeyModal(questionDiv, questionId) {
        currentQuestionId = questionId;
        const questionText = questionDiv.querySelector('.question-text').value;
        const questionType = questionDiv.querySelector('.question-type').value;
        const answerKeyData = answerKeys[questionId];
        
        // Set modal title and points
        modalQuestionContent.innerHTML = `
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold text-gray-800 mb-2">Question:</h4>
            <p class="text-gray-700">${questionText || 'No question text entered'}</p>
          </div>
        `;
        
        questionPoints.value = answerKeyData.points;
        answerKeyError.classList.add('hidden');
        
        // Generate answer options based on question type
        let optionsHtml = '';
        
        if (questionType === 'multiple' || questionType === 'truefalse') {
          const options = questionDiv.querySelectorAll('.option-input');
          optionsHtml = `
            <div class="form-control">
              <label class="label">
                <span class="label-text font-semibold required-field">Select Correct Answer(s)</span>
                ${questionType === 'multiple' ? '<span class="label-text-alt text-gray-500">(Multiple answers allowed)</span>' : ''}
              </label>
              <div class="space-y-2">
          `;
          
          options.forEach((option, index) => {
            const optionText = option.value || (questionType === 'truefalse' ? (index === 0 ? 'True' : 'False') : `Option ${index + 1}`);
            const isChecked = answerKeyData.correctAnswers.includes(optionText);
            optionsHtml += `
              <label class="flex items-center cursor-pointer p-2 rounded hover:bg-gray-100">
                <input type="${questionType === 'multiple' ? 'checkbox' : 'radio'}" 
                       name="correctAnswer" 
                       value="${optionText}" 
                       class="mr-3 ${questionType === 'multiple' ? 'checkbox' : 'radio'} checkbox-primary" 
                       ${isChecked ? 'checked' : ''}>
                <span>${optionText}</span>
              </label>
            `;
          });
          
          optionsHtml += `</div></div>`;
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          const answerInput = questionDiv.querySelector('.answer-input');
          const currentAnswer = answerKeyData.correctAnswers[0] || '';
          
          optionsHtml = `
            <div class="form-control">
              <label class="label">
                <span class="label-text font-semibold required-field">Correct Answer</span>
              </label>
              <input type="text" id="textAnswerInput" class="input input-bordered w-full" 
                     value="${currentAnswer}" placeholder="Enter the correct answer" required>
            </div>
          `;
        }
        
        answerKeyOptions.innerHTML = optionsHtml;
        
        // Show modal
        answerKeyModal.classList.add('modal-open');
      }
      
      // Function to save answer key
      saveAnswerKey.addEventListener('click', function() {
        if (!currentQuestionId) return;
        
        const questionDiv = document.querySelector(`[data-question-id="${currentQuestionId}"]`);
        const questionType = questionDiv.querySelector('.question-type').value;
        const points = parseInt(questionPoints.value) || 1;
        
        // Validation
        let hasError = false;
        
        if (questionType === 'multiple' || questionType === 'truefalse') {
          const selectedOptions = answerKeyOptions.querySelectorAll('input:checked');
          if (selectedOptions.length === 0) {
            answerKeyError.classList.remove('hidden');
            hasError = true;
          } else {
            answerKeyError.classList.add('hidden');
          }
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          const textAnswer = document.getElementById('textAnswerInput');
          if (!textAnswer.value.trim()) {
            textAnswer.classList.add('error-field');
            hasError = true;
          } else {
            textAnswer.classList.remove('error-field');
          }
        }
        
        if (hasError) return;
        
        // Update answer key data
        answerKeys[currentQuestionId].points = points;
        answerKeys[currentQuestionId].correctAnswers = [];
        
        if (questionType === 'multiple' || questionType === 'truefalse') {
          const selectedOptions = answerKeyOptions.querySelectorAll('input:checked');
          selectedOptions.forEach(option => {
            answerKeys[currentQuestionId].correctAnswers.push(option.value);
          });
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          const textAnswer = document.getElementById('textAnswerInput').value;
          if (textAnswer) {
            answerKeys[currentQuestionId].correctAnswers.push(textAnswer);
          }
        }
        
        // Update display
        updateAnswerKeyDisplay(questionDiv);
        
        // Close modal
        answerKeyModal.classList.remove('modal-open');
        showToast('Answer key saved successfully!', 'success');
        
        // Auto-save after saving answer key
        saveFormState();
      });
      
      // Close modal handler
      closeAnswerKeyModal.addEventListener('click', function() {
        answerKeyModal.classList.remove('modal-open');
      });
      
      // Function to update answer key display on question card
      function updateAnswerKeyDisplay(questionDiv) {
        const questionId = questionDiv.getAttribute('data-question-id');
        const answerKeyData = answerKeys[questionId];
        const answerKeyStatus = questionDiv.querySelector('#answerKeyStatus');
        const pointsDisplay = questionDiv.querySelector('#pointsDisplay');
        const correctAnswersPreview = questionDiv.querySelector('#correctAnswersPreview');
        const answerKeyRequired = questionDiv.querySelector('#answerKeyRequired');
        
        if (answerKeyData.correctAnswers.length > 0) {
          answerKeyStatus.textContent = 'Set';
          answerKeyStatus.classList.add('answer-key-indicator');
          pointsDisplay.textContent = `(${answerKeyData.points} point${answerKeyData.points > 1 ? 's' : ''})`;
          answerKeyRequired.classList.add('hidden');
          
          // Show correct answers preview
          correctAnswersPreview.classList.remove('hidden');
          correctAnswersPreview.innerHTML = `
            <strong>Correct Answer(s):</strong> ${answerKeyData.correctAnswers.join(', ')}
          `;
        } else {
          answerKeyStatus.textContent = 'Not set';
          answerKeyStatus.classList.remove('answer-key-indicator');
          pointsDisplay.textContent = '';
          correctAnswersPreview.classList.add('hidden');
        }
      }
      
      // Function to update question type
      function updateQuestionType(selectElement, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn) {
        const questionType = selectElement.value;
        
        if (questionType === 'multiple') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.remove('hidden');
          
          // Clear existing options and add blank options for Multiple Choice
          optionsContainer.innerHTML = '';
          for (let i = 0; i < 2; i++) {
            const optionDiv = createOptionElement('', false, false);
            optionsContainer.appendChild(optionDiv);
          }
          
        } else if (questionType === 'truefalse') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.add('hidden');
          
          // Clear existing options and add True/False
          optionsContainer.innerHTML = '';
          
          const trueOption = createOptionElement('True', true);
          const falseOption = createOptionElement('False', true);
          
          optionsContainer.appendChild(trueOption);
          optionsContainer.appendChild(falseOption);
          
          // Hide image upload buttons for True/False options
          const optionItems = optionsContainer.querySelectorAll('.option-item');
          optionItems.forEach(item => {
            const imageBtn = item.querySelector('.btn.btn-circle.btn-ghost.btn-sm.cursor-pointer');
            if (imageBtn) {
              imageBtn.style.display = 'none';
            }
          });
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          optionsContainer.style.display = 'none';
          answerContainer.style.display = 'block';
          addOptionsContainer.classList.add('hidden');
          
          // Update placeholder based on question type
          const answerInput = answerContainer.querySelector('.answer-input');
          if (questionType === 'identification') {
            answerInput.placeholder = "Enter the correct identification term or phrase";
          } else {
            answerInput.placeholder = "Enter the expected answer for this question";
          }
        }
      }
      
      // Function to create an option element
      function createOptionElement(value, readOnly = false, isOther = false) {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-item flex items-center gap-3';
        
        if (isOther) {
          // Special styling for "Other" option - empty field for respondents
          optionDiv.innerHTML = `
            <div class="drag-handle text-gray-400 cursor-move">
              <i class="fas fa-grip-vertical"></i>
            </div>
            <div class="flex items-center flex-grow">
              <span class="other-label">Other:</span>
              <input type="text" 
                     class="other-option-input flex-grow ml-2" 
                     placeholder="______" 
                     readonly 
                     disabled
                     data-other="true">
            </div>
            <div class="flex items-center gap-2">
              <!-- No image upload for Other option since it's for respondents -->
              <button type="button" class="remove-option btn btn-circle btn-ghost btn-sm text-error">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          `;
          
          // Add tooltip to explain this is for respondents
          optionDiv.title = "This field will be empty for respondents to fill in their own answers";
        } else {
          // Regular option
          optionDiv.innerHTML = `
            <div class="drag-handle text-gray-400 cursor-move">
              <i class="fas fa-grip-vertical"></i>
            </div>
            <input type="text" class="option-input input input-bordered flex-grow focus:input-primary" 
              value="${value}" ${readOnly ? 'readonly' : ''} placeholder="Enter option" required>
            <div class="flex items-center gap-2">
              <label class="btn btn-circle btn-ghost btn-sm cursor-pointer">
                <i class="fas fa-image"></i>
                <input type="file" class="option-image-input hidden" accept="image/*">
              </label>
              <button type="button" class="remove-option btn btn-circle btn-ghost btn-sm text-error">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          `;
        }
        
        // Add event listener to remove button
        const removeBtn = optionDiv.querySelector('.remove-option');
        removeBtn.addEventListener('click', function() {
          const optionsContainer = optionDiv.closest('.options-container');
          if (optionsContainer.children.length > 1) {
            // Animate out
            optionDiv.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => {
              optionDiv.remove();
              saveFormState(); // Auto-save on change
            }, 300);
          } else {
            showToast('A question must have at least one option', 'warning');
          }
        });
        
        // Add validation for option input
        if (!isOther) {
          const optionInput = optionDiv.querySelector('.option-input');
          optionInput.addEventListener('input', function() {
            if (this.value.trim()) {
              this.classList.remove('error-field');
            }
            saveFormState(); // Auto-save on change
          });
        }
        
        // Add image upload handler only for regular options
        if (!isOther) {
          const imageInput = optionDiv.querySelector('.option-image-input');
          if (imageInput) {
            imageInput.addEventListener('change', handleImageUpload);
          }
        }
        
        return optionDiv;
      }
      
      // Function to handle image upload for options
      function handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Check if file is an image
        if (!file.type.match('image.*')) {
          showToast('Please select an image file', 'error');
          return;
        }
        
        // Create a preview
        const reader = new FileReader();
        reader.onload = function(e) {
          const optionItem = event.target.closest('.option-item');
          
          // Remove existing preview if any
          const existingPreview = optionItem.querySelector('.option-image-preview');
          if (existingPreview) {
            existingPreview.remove();
          }
          
          // Create and add preview
          const preview = document.createElement('img');
          preview.src = e.target.result;
          preview.className = 'option-image-preview rounded border border-gray-300';
          preview.alt = 'Option image preview';
          
          // Insert after the input field
          const inputContainer = optionItem.querySelector('.flex-grow');
          inputContainer.parentNode.insertBefore(preview, inputContainer.nextSibling);
          
          // Add remove image button
          const removeImageBtn = document.createElement('button');
          removeImageBtn.type = 'button';
          removeImageBtn.className = 'btn btn-circle btn-ghost btn-sm text-error remove-image';
          removeImageBtn.innerHTML = '<i class="fas fa-times"></i>';
          removeImageBtn.addEventListener('click', function() {
            preview.remove();
            removeImageBtn.remove();
            event.target.value = ''; // Clear the file input
            saveFormState(); // Auto-save on change
          });
          
          // Replace the image upload button with remove button
          const imageBtnContainer = optionItem.querySelector('.btn.btn-circle.btn-ghost.btn-sm.cursor-pointer');
          imageBtnContainer.replaceWith(removeImageBtn);
          
          // Auto-save after image upload
          saveFormState();
        };
        
        reader.readAsDataURL(file);
      }
      
      // Function to add an option to a question
      function addOption(optionsContainer, isOther = false) {
        const optionCount = optionsContainer.children.length + 1;
        const value = isOther ? 'Other' : '';
        const optionDiv = createOptionElement(value, false, isOther);
        
        // Animate in
        optionDiv.classList.add('opacity-0', 'translate-x-4');
        optionsContainer.appendChild(optionDiv);
        
        setTimeout(() => {
          optionDiv.classList.remove('opacity-0', 'translate-x-4');
          optionDiv.classList.add('opacity-100', 'translate-x-0');
        }, 10);
      }
      
      // Function to update question numbers after removal
      function updateQuestionNumbers() {
        const questions = questionsContainer.querySelectorAll('.question-item');
        questionCount = questions.length;
        
        questions.forEach((question, index) => {
          const questionNumber = question.querySelector('.question-number');
          questionNumber.textContent = `Q${index + 1}`;
        });
      }
      
      // Create Exam button handler - UPDATED FIXED VERSION
      const editExam = window.__EDIT_EXAM;
      const isEditMode = !!(editExam && editExam.id);

      createExamBtn.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Show loading state
          createExamBtn.classList.add('btn-loading');
          createExamBtn.disabled = true;
          
          // Basic validation
          const examTitle = document.getElementById('examTitle').value;
          const moduleSelect = document.getElementById('moduleSelect').value;
          const moduleTitle = document.getElementById('moduleTitle')?.value || 'Untitled Module';
          const moduleDepartment = document.getElementById('moduleDepartment')?.value || 'General';
          const moduleRoles = document.getElementById('moduleRoles')?.value || 'All';
          const questions = questionsContainer.querySelectorAll('.question-item');
          
          if (!examTitle) {
              showToast('Please enter an examination title', 'error');
              document.getElementById('examTitle').focus();
              createExamBtn.classList.remove('btn-loading');
              createExamBtn.disabled = false;
              return;
          }
          
          if (!moduleSelect) {
              showToast('Please select a module', 'error');
              createExamBtn.classList.remove('btn-loading');
              createExamBtn.disabled = false;
              return;
          }
          
          if (questions.length === 0) {
              showToast('Please add at least one question', 'error');
              createExamBtn.classList.remove('btn-loading');
              createExamBtn.disabled = false;
              return;
          }
          
          // Validate all questions
          if (!validateAllQuestions()) {
              showToast('Please complete all questions with required fields before creating the exam', 'error');
              createExamBtn.classList.remove('btn-loading');
              createExamBtn.disabled = false;
              return;
          }
          
          // Prepare data for submission
          const examData = {
              title: examTitle,
              description: document.getElementById('examDescription').value,
              module_id: moduleSelect,
              module_title: moduleTitle,
              department: moduleDepartment,
              roles: moduleRoles,
              status: 'pending',
              questions: [],
              total_points: 0,
              passing_score: 70, // Default passing score
              duration: 60, // Default duration in minutes
              created_at: new Date().toISOString(),
              created_by: 'User' // You can replace this with actual user data from session
          };
          
          // Collect question data
          questions.forEach((question, index) => {
              const questionId = question.getAttribute('data-question-id');
              const questionType = question.querySelector('.question-type').value;
              const answerKeyData = answerKeys[questionId];
              
              const questionData = {
                  question_number: index + 1,
                  question_type: questionType,
                  question_text: question.querySelector('.question-text').value,
                  points: answerKeyData.points || 1,
                  answer_key: JSON.stringify(answerKeyData),
                  options: ''
              };
              
              // Add total points
              examData.total_points += questionData.points;
              
              // For multiple choice and true/false, collect options
              if (questionData.question_type === 'multiple' || questionData.question_type === 'truefalse') {
                  questionData.options = [];
                  const options = question.querySelectorAll('.option-input');
                  options.forEach(option => {
                      questionData.options.push(option.value);
                  });
              }
              
              // For short answer and identification, collect expected answer
              if (questionData.question_type === 'shortanswer' || questionData.question_type === 'identification') {
                  const answerInput = question.querySelector('.answer-input');
                  questionData.expected_answer = answerInput.value;
              }
              
              // Convert options array to JSON string
              if (Array.isArray(questionData.options) && questionData.options.length > 0) {
                  questionData.options = JSON.stringify(questionData.options);
              } else if (Array.isArray(questionData.options)) {
                  questionData.options = '';
              }
              
              examData.questions.push(questionData);
          });
          
          console.log('Exam data to submit:', examData);
          
          let createExamConfirmed = false;

          // Show confirmation dialog
          Swal.fire({
              title: isEditMode ? 'Update Examination?' : 'Create Examination?',
              html: `
                <div class="text-left">
                  <p>${isEditMode ? 'Are you sure you want to update this examination?' : 'Are you sure you want to create this examination?'}</p>
                  <div class="mt-4 p-3 bg-gray-50 rounded">
                    <p><strong>Title:</strong> ${examData.title}</p>
                    <p><strong>Module:</strong> ${examData.module_title}</p>
                    <p><strong>Questions:</strong> ${examData.questions.length}</p>
                    <p><strong>Total Points:</strong> ${examData.total_points}</p>
                  </div>
                  <p class="mt-3 text-sm text-gray-600">It will be submitted for review.</p>
                  <div class="mt-6 flex gap-3 justify-center">
                    <button type="button" id="swalCreateExamConfirm" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0.75rem 1rem;border-radius:0.375rem;font-weight:600;background:#3b82f6;color:#fff;border:none;min-width:160px;">${isEditMode ? 'Yes, Update Exam!' : 'Yes, Create Exam!'}</button>
                    <button type="button" id="swalCreateExamCancel" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0.75rem 1rem;border-radius:0.375rem;font-weight:600;background:#6b7280;color:#fff;border:none;min-width:160px;">Cancel</button>
                  </div>
                </div>
              `,
              icon: 'question',
              showConfirmButton: false,
              showCancelButton: false,
              customClass: {
                actions: 'swal2-actions-hidden'
              },
              allowOutsideClick: false,
              allowEscapeKey: true,
              didOpen: () => {
                const popup = Swal.getPopup();
                const confirmBtn = popup ? popup.querySelector('#swalCreateExamConfirm') : null;
                const cancelBtn = popup ? popup.querySelector('#swalCreateExamCancel') : null;

                if (confirmBtn) {
                  confirmBtn.addEventListener('click', () => {
                    createExamConfirmed = true;
                    Swal.close();
                    saveExaminationToDatabase(examData);
                  });
                }

                if (cancelBtn) {
                  cancelBtn.addEventListener('click', () => {
                    Swal.close();
                    createExamBtn.classList.remove('btn-loading');
                    createExamBtn.disabled = false;
                  });
                }
              },
              didClose: () => {
                if (!createExamConfirmed) {
                  createExamBtn.classList.remove('btn-loading');
                  createExamBtn.disabled = false;
                }
              }
          });
      });
      
      // Function to save examination to database via AJAX - FIXED VERSION
      function saveExaminationToDatabase(examData) {
          console.log('Sending exam data:', examData);
          
          // Show loading state
          createExamBtn.classList.add('btn-loading');
          createExamBtn.disabled = true;
          
          // Create FormData for better compatibility
          const formData = new FormData();
          formData.append('action', isEditMode ? 'update_exam' : 'create_exam');
          if (isEditMode) {
            formData.append('exam_id', String(editExam.id));
          }
          formData.append('exam_data', JSON.stringify(examData));
          
          // Send AJAX request with proper error handling
          fetch('save_examination.php', {
              method: 'POST',
              body: formData
          })
          .then(response => {
              console.log('Response status:', response.status, response.statusText);
              
              // First get the response as text to see what we're getting
              return response.text().then(text => {
                  console.log('Raw response text:', text);
                  
                  if (!text.trim()) {
                      throw new Error('Empty response from server');
                  }
                  
                  try {
                      return JSON.parse(text);
                  } catch (e) {
                      console.error('JSON parse error:', e);
                      console.error('Response that failed to parse:', text.substring(0, 500));
                      
                      // Try to extract error from HTML if that's what we got
                      if (text.includes('<') && text.includes('>')) {
                          // It's HTML, try to find error messages
                          const errorMatch = text.match(/<b>([^<]+)<\/b>/i) || 
                                            text.match(/<pre>([^<]+)<\/pre>/i) ||
                                            text.match(/error:(.*?)</i);
                          const errorMsg = errorMatch ? errorMatch[1] : 'Server returned HTML instead of JSON';
                          throw new Error('Server Error: ' + errorMsg);
                      }
                      
                      throw new Error('Invalid response from server: ' + text.substring(0, 100));
                  }
              });
          })
          .then(data => {
              console.log('Parsed response data:', data);
              
              if (data.success) {
                  // Clear the draft after successful submission
                  const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
                  localStorage.removeItem(`exam_draft_${moduleId}`);
                  
                  // Redirect directly to the Examination Repository
                  Swal.fire({
                      icon: 'success',
                      title: isEditMode ? 'Examination Updated!' : 'Examination Created!',
                      text: 'Redirecting to examination repository...',
                      showConfirmButton: false,
                      timer: 1200,
                      timerProgressBar: true
                  }).then(() => {
                      suppressBeforeUnload = true;
                      window.location.href = 'examination_repository.php';
                  });
              } else {
                  throw new Error(data.message || 'Failed to create examination');
              }
          })
          .catch(error => {
              console.error('Error:', error);
              
              // Show user-friendly error message
              let errorMessage = error.message;
              if (error.message.includes('Network')) {
                  errorMessage = 'Network error. Please check your internet connection and try again.';
              } else if (error.message.includes('Empty response')) {
                  errorMessage = 'Server returned empty response. Please check PHP error logs.';
              } else if (error.message.includes('Server Error')) {
                  errorMessage = error.message;
              }
              
              Swal.fire({
                  icon: 'error',
                  title: 'Failed to Create Examination',
                  html: `
                      <div class="text-left">
                          <p>${errorMessage}</p>
                          <div class="mt-3 p-2 bg-red-50 rounded text-sm">
                              <p class="font-semibold">Troubleshooting tips:</p>
                              <ul class="list-disc ml-4 mt-1">
                                  <li>Check if PHP is running properly</li>
                                  <li>Verify database connection settings</li>
                                  <li>Check PHP error logs for details</li>
                              </ul>
                          </div>
                      </div>
                  `,
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3085d6'
              });
              
              createExamBtn.classList.remove('btn-loading');
              createExamBtn.disabled = false;
          });
      }
      
      // Save draft button handler
      document.getElementById('saveDraft').addEventListener('click', function() {
          saveAsDraft();
      });
      
      // Function to save as draft
      function saveAsDraft() {
          // Basic validation
          const examTitle = document.getElementById('examTitle').value;
          const moduleSelect = document.getElementById('moduleSelect').value;
          
          if (!examTitle) {
              showToast('Please enter an examination title', 'error');
              document.getElementById('examTitle').focus();
              return;
          }
          
          if (!moduleSelect) {
              showToast('Please select a module', 'error');
              return;
          }
          
          // Prepare draft data
          const draftData = {
              title: examTitle,
              description: document.getElementById('examDescription').value,
              module_id: moduleSelect,
              module_title: document.getElementById('moduleTitle')?.value || 'Untitled Module',
              department: document.getElementById('moduleDepartment')?.value || 'General',
              roles: document.getElementById('moduleRoles')?.value || 'All',
              status: 'draft',
              questions: [],
              answerKeys: {...answerKeys},
              created_at: new Date().toISOString()
          };
          
          // Collect question data
          const questions = questionsContainer.querySelectorAll('.question-item');
          questions.forEach((question, index) => {
              const questionId = question.getAttribute('data-question-id');
              const questionType = question.querySelector('.question-type').value;
              
              const questionData = {
                  id: questionId,
                  number: index + 1,
                  type: questionType,
                  text: question.querySelector('.question-text').value,
                  options: []
              };
              
              // For multiple choice and true/false, collect options
              if (questionData.type === 'multiple' || questionData.type === 'truefalse') {
                  const options = question.querySelectorAll('.option-input');
                  options.forEach(option => {
                      questionData.options.push(option.value);
                  });
              }
              
              // For short answer and identification, collect expected answer
              if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
                  const answerInput = question.querySelector('.answer-input');
                  questionData.expected_answer = answerInput ? answerInput.value : '';
              }
              
              draftData.questions.push(questionData);
          });
          
          // Save to localStorage as draft
          let drafts = JSON.parse(localStorage.getItem('exam_drafts')) || [];
          drafts.push(draftData);
          localStorage.setItem('exam_drafts', JSON.stringify(drafts));
          
          // Clear the auto-save draft
          const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
          localStorage.removeItem(`exam_draft_${moduleId}`);
          
          // Show success message and redirect
          Swal.fire({
              icon: 'success',
              title: 'Draft Saved!',
              text: 'Your examination has been saved as draft.',
              showConfirmButton: false,
              timer: 1500
          }).then(() => {
              suppressBeforeUnload = true;
              // Redirect to examination drafts page
              window.location.href = 'examination_repository.php?tab=drafts';
          });
      }

      // Toast notification function
      function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `success-toast`;
        
        let alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        if (type === 'error') alertClass = 'alert-error';
        if (type === 'warning') alertClass = 'alert-warning';
        
        toast.innerHTML = `
          <div class="alert ${alertClass} shadow-lg text-white flex items-center">
            <div class="flex items-center">
              <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
              <span>${message}</span>
            </div>
          </div>
        `;
        
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
          toast.remove();
        }, 3000);
      }
      
      if (window.__AI_GENERATED_EXAM_ERROR) {
        showToast(window.__AI_GENERATED_EXAM_ERROR, 'error');
      }
      
      // Initialize independent scrolling
      initializeIndependentScrolling();
      
      // Try to load saved state
      let stateLoaded = loadFormState();

      if (isEditMode && editExam && Array.isArray(editExam.questions)) {
        const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
        localStorage.removeItem(`exam_draft_${moduleId}`);

        questionsContainer.innerHTML = '';
        questionCount = 0;
        Object.keys(answerKeys).forEach((k) => {
          delete answerKeys[k];
        });

        const safeParse = (val) => {
          try {
            return JSON.parse(val);
          } catch (e) {
            return null;
          }
        };

        editExam.questions.forEach((q, idx) => {
          const questionId = `db_question_${q.id || idx + 1}`;
          const type = q && q.question_type ? String(q.question_type) : 'multiple';
          const questionText = q && q.question_text ? String(q.question_text) : '';
          const pointsRaw = q && q.points !== undefined ? parseInt(q.points, 10) : 1;
          const points = Number.isFinite(pointsRaw) && pointsRaw > 0 ? pointsRaw : 1;

          const answerKeyParsed = q && q.answer_key ? safeParse(String(q.answer_key)) : null;
          answerKeys[questionId] = answerKeyParsed && typeof answerKeyParsed === 'object'
            ? answerKeyParsed
            : { points: points, correctAnswers: [], questionType: type };

          const questionData = {
            id: questionId,
            number: idx + 1,
            type: type,
            text: questionText,
            options: []
          };

          if (type === 'multiple' || type === 'truefalse') {
            const optsParsed = q && q.options ? safeParse(String(q.options)) : null;
            const options = Array.isArray(optsParsed) ? optsParsed : [];
            questionData.options = options.map((opt) => ({
              value: String(opt),
              isOther: false
            }));
          }

          if (type === 'shortanswer' || type === 'identification') {
            questionData.answer = q && q.expected_answer ? String(q.expected_answer) : '';
          }

          restoreQuestion(questionData);
        });

        saveFormState();
        stateLoaded = true;
      }

      const aiExam = window.__AI_GENERATED_EXAM;
      if (!isEditMode && aiExam && Array.isArray(aiExam.questions) && aiExam.questions.length > 0) {
        const moduleId = document.getElementById('moduleSelect')?.value || 'new_exam';
        localStorage.removeItem(`exam_draft_${moduleId}`);

        questionsContainer.innerHTML = '';
        questionCount = 0;
        Object.keys(answerKeys).forEach((k) => {
          delete answerKeys[k];
        });

        aiExam.questions.forEach((q, idx) => {
          const questionId = `ai_question_${Date.now()}_${idx + 1}`;
          const type = q && q.type ? String(q.type) : 'multiple';
          const questionText = q && q.question_text ? String(q.question_text) : '';
          const pointsRaw = q && q.points !== undefined ? parseInt(q.points, 10) : 1;
          const points = Number.isFinite(pointsRaw) && pointsRaw > 0 ? pointsRaw : 1;
          const correct = q && Array.isArray(q.correct_answers) && q.correct_answers[0] ? String(q.correct_answers[0]) : '';

          answerKeys[questionId] = {
            points: points,
            correctAnswers: correct ? [correct] : [],
            questionType: type
          };

          const questionData = {
            id: questionId,
            number: idx + 1,
            type: type,
            text: questionText,
            options: []
          };

          if ((type === 'multiple' || type === 'truefalse') && q && Array.isArray(q.options)) {
            questionData.options = q.options.map((opt) => ({
              value: String(opt),
              isOther: false
            }));
          }

          if (type === 'shortanswer' || type === 'identification') {
            questionData.answer = correct;
          }

          restoreQuestion(questionData);
        });

        saveFormState();
        stateLoaded = true;
      }
      
      // If no saved state, add initial question
      if (!stateLoaded) {
        addQuestion();
      }
      
      // Start auto-save
      startAutoSave();
    });
  </script>
    </div>
  </div>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>

</html>
