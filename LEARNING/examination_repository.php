<?php
session_start();
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_db";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch posted modules for the examination modal
$posted_modules = [];
$sql_modules = "SELECT * FROM learning_modules WHERE status = 'posted' ORDER BY created_at DESC";
$result_modules = $conn->query($sql_modules);

if ($result_modules && $result_modules->num_rows > 0) {
    while($row = $result_modules->fetch_assoc()) {
        $posted_modules[] = $row;
    }
}

// Fetch unique departments and roles for filtering
$departments = [];
$roles = [];
$sql_dept = "SELECT DISTINCT department FROM learning_modules WHERE status = 'posted' AND department IS NOT NULL";
$result_dept = $conn->query($sql_dept);
if ($result_dept && $result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$sql_roles = "SELECT DISTINCT roles FROM learning_modules WHERE status = 'posted' AND roles IS NOT NULL";
$result_roles = $conn->query($sql_roles);
if ($result_roles && $result_roles->num_rows > 0) {
    while($row = $result_roles->fetch_assoc()) {
        $roles[] = $row['roles'];
    }
}

// Fetch examinations from database - ONLY approved, rejected, hold, and compliance
$examinations = [];
$sql = "SELECT * FROM examinations WHERE status IN ('approved', 'rejected', 'hold', 'compliance') ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $examinations[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Examination Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
  <style>
    .examination-card {
      transition: all 0.3s ease;
    }
    .examination-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .status-pending {
      @apply bg-yellow-100 text-yellow-800;
    }
    .status-approved {
      @apply bg-green-100 text-green-800;
    }
    .status-hold {
      @apply bg-orange-100 text-orange-800;
    }
    .status-rejected {
      @apply bg-red-100 text-red-800;
    }
    .status-compliance {
      @apply bg-blue-100 text-blue-800;
    }
    .status-posted {
      @apply bg-purple-100 text-purple-800;
    }
    .btn-custom {
      @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-success {
      @apply bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-warning {
      @apply bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-danger {
      @apply bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-info {
      @apply bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    
    /* First Modal - View Document Modal - Make bigger and centered */
    #view_document_modal .modal-box {
      max-width: 85vw;
      width: 90%;
      height: 85vh;
      display: flex;
      flex-direction: column;
      margin: 0 auto;
    }

    /* Center the modal content */
    #view_document_modal .modal-box > * {
      margin-left: auto;
      margin-right: auto;
    }

    /* Second Modal - Create Examination Modal - Make larger */
    #create_examination_modal .modal-box {
      max-width: 95vw;
      width: 98%;
      height: 90vh;
      max-height: 90vh;
    }

    /* Examination Modal Styles */
    .examination-modal {
      max-width: 90vw;
      width: 95%;
      height: 85vh;
    }

    .examination-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      height: calc(100% - 80px); /* Account for header and actions */
      overflow: hidden;
    }

    .form-section {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      overflow-y: auto;
      max-height: 100%;
      padding-right: 0.5rem;
    }

    .modules-section {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      border-left: 1px solid #e5e7eb;
      padding-left: 2rem;
      overflow: hidden;
    }

    .filter-section {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .filter-select {
      flex: 1;
      padding: 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.875rem;
    }

    .filter-select:disabled {
      background-color: #f9fafb;
      color: #9ca3af;
      cursor: not-allowed;
    }

    .modules-list {
      flex: 1;
      overflow-y: auto;
      max-height: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
      background-color: #f9fafb;
    }

    .module-item {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 0.75rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .module-item:hover {
      border-color: #3b82f6;
      box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
    }

    .module-item.selected {
      border-color: #3b82f6;
      background-color: #eff6ff;
    }

    .module-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #1f2937;
    }

    .module-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.875rem;
      color: #6b7280;
    }

    .module-badge {
      background-color: #e5e7eb;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
    }

    .no-modules {
      text-align: center;
      padding: 2rem;
      color: #6b7280;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: auto;
      padding-top: 1.5rem;
      border-top: 1px solid #e5e7eb;
    }

    /* Document Preview Modal - Bond Paper Size */
    .document-preview-modal {
      max-width: 85vw;
      width: 90%;
      height: 90vh;
      max-height: 90vh;
      z-index: 10050;
    }

    .document-content {
      height: 70vh;
      max-height: 70vh;
      overflow-y: auto;
      padding: 2rem;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      /* Bond paper styling */
      width: 8.5in;
      min-height: 11in;
      margin: 0 auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      background: white;
      position: relative;
    }

    /* Ensure SweetAlert buttons are always visible and on top */
    .swal2-container {
      z-index: 10060 !important;
    }
    
    .swal2-popup {
      z-index: 10061 !important;
      font-size: 1rem !important;
    }
    
    .swal2-actions {
      display: flex !important;
      flex-direction: row !important;
      gap: 0.5rem !important;
      justify-content: center !important;
    }
    
    .swal2-confirm, .swal2-cancel, .swal2-deny {
      display: inline-block !important;
      visibility: visible !important;
      opacity: 1 !important;
      padding: 0.625rem 1.5rem !important;
      font-size: 0.875rem !important;
      border-radius: 0.375rem !important;
      margin: 0 0.25rem !important;
      min-width: 100px !important;
    }

    .swal2-confirm {
      background-color: #3085d6 !important;
      border: 1px solid #3085d6 !important;
    }

    .swal2-confirm.swal2-danger {
      background-color: #d33 !important;
      border: 1px solid #d33 !important;
    }

    .swal2-confirm.swal2-success {
      background-color: #28a745 !important;
      border: 1px solid #28a745 !important;
    }

    .swal2-cancel {
      background-color: #6c757d !important;
      border: 1px solid #6c757d !important;
      color: white !important;
    }

    /* Make sure modals have proper z-index but lower than SweetAlert */
    .modal {
      z-index: 10040;
    }
    
    .modal-box {
      z-index: 10041;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
      .examination-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
      
      .modules-section {
        border-left: none;
        border-top: 1px solid #e5e7eb;
        padding-left: 0;
        padding-top: 1.5rem;
      }
      
      .document-content {
        width: 100%;
        min-height: auto;
      }
      
      #view_document_modal .modal-box,
      #create_examination_modal .modal-box {
        max-width: 95vw;
        width: 98%;
        height: 95vh;
      }
    }

    @media (max-width: 768px) {
      .document-preview-modal {
        max-width: 95vw;
        width: 98%;
        height: 95vh;
      }
      
      .document-content {
        padding: 1rem;
        height: 65vh;
      }
      
      #view_document_modal .modal-box,
      #create_examination_modal .modal-box {
        max-width: 98vw;
        width: 99%;
        height: 98vh;
      }
      
      .examination-grid {
        gap: 1rem;
      }
      
      .filter-section {
        flex-direction: column;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .form-actions button {
        width: 100%;
      }
    }

    @media (max-width: 480px) {
      #view_document_modal .modal-box,
      #create_examination_modal .modal-box {
        max-width: 99vw;
        width: 100%;
        height: 99vh;
        margin: 0.5rem;
      }
      
      .examination-grid {
        height: calc(100% - 100px);
      }
      
      .modules-list {
        padding: 0.5rem;
      }
      
      .module-item {
        padding: 0.75rem;
      }
      
      .module-meta {
        flex-direction: column;
        gap: 0.5rem;
      }
    }

    /* Additional utility classes */
    .btn-border {
      @apply border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }

    .text-ellipsis-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .text-ellipsis-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Scrollbar styling */
    .modules-list::-webkit-scrollbar,
    .form-section::-webkit-scrollbar {
      width: 6px;
    }

    .modules-list::-webkit-scrollbar-track,
    .form-section::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    .modules-list::-webkit-scrollbar-thumb,
    .form-section::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }

    .modules-list::-webkit-scrollbar-thumb:hover,
    .form-section::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Loading states */
    .loading {
      opacity: 0.7;
      pointer-events: none;
    }

    .loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Focus states for accessibility */
    .module-item:focus {
      outline: 2px solid #3b82f6;
      outline-offset: 2px;
    }

    button:focus {
      outline: 2px solid #3b82f6;
      outline-offset: 2px;
    }

    /* Print styles for document preview */
    @media print {
      .document-content {
        width: 100%;
        height: auto;
        box-shadow: none;
        border: none;
        padding: 0;
      }
      
      .no-print {
        display: none !important;
      }
    }
</style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../USM/navbar.php'; ?>
        
        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
          <!-- Examinations Section -->
          <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-3xl font-bold mb-2">Examination <br>Management</h1>
                <p class="text-gray-600">Manage approved, rejected, <br>on hold, and compliance examinations</p>
              </div>
             <!-- In examination_repository.php, find this section and update: -->
<div class="flex gap-2">
  <button class="btn btn-custom" onclick="window.location.href='exam_results.php'">
    <i class="fas fa-chart-bar mr-2"></i>Exam Results 
  </button>
  <!-- ADD THIS NEW BUTTON -->
 
  <button class="btn btn-custom" id="createExaminationBtn">
    <i class="fas fa-plus mr-2"></i>Create Examination
  </button>
  <button class="btn btn-border" onclick="window.location.href='review_dashboard.php'">
    <i class="fas fa-eye mr-2"></i>Review Page
  </button>
  <button class="btn btn-border" onclick="window.location.href='posted_examinations.php'">
    <i class="fas fa-list-check mr-2"></i>Posted Examinations
  </button>
</div>
            </div>
            
            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
              <div class="flex flex-wrap gap-4">
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Status</span>
                  </label>
                  <select class="select select-bordered w-40" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="hold">Hold</option>
                    <option value="rejected">Rejected</option>
                    <option value="compliance">For Compliance</option>
                  </select>
                </div>
                
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Department</span>
                  </label>
                  <select class="select select-bordered w-48" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <option value="human-resources">Human Resources</option>
                    <option value="operations">Operations</option>
                    <option value="information-technology">Information Technology</option>
                    <option value="front-office">Front Office</option>
                    <option value="kitchen">Kitchen</option>
                    <option value="sales-marketing">Sales & Marketing</option>
                  </select>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-custom" onclick="applyFilters()">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                  </button>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-custom" onclick="clearFilters()">
                    <i class="fas fa-times mr-2"></i>Clear
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Examination Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="examinationCards">
                <?php if (empty($examinations)): ?>
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500">No examinations found. Create your first examination!</p>
                        <p class="text-sm text-gray-400 mt-2">Pending examinations are displayed in the Review Page</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($examinations as $exam): ?>
                        <div class="card bg-base-100 shadow-md examination-card" data-status="<?php echo $exam['status']; ?>" data-department="<?php echo $exam['department']; ?>">
                            <div class="card-body">
                                <div class="flex justify-between items-start">
                                    <h3 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                    <div class="badge status-<?php echo $exam['status']; ?>">
                                        <?php echo ucfirst($exam['status']); ?>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 my-2">
                                    <div class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></div>
                                    <div class="badge badge-outline"><?php echo htmlspecialchars($exam['question_count']); ?> Questions</div>
                                </div>
                                <p class="text-sm text-gray-500">Created: <?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></p>
                                <p class="text-sm text-gray-500">Duration: <?php echo htmlspecialchars($exam['duration']); ?> minutes</p>
                                <p class="text-sm text-gray-500">Passing Score: <?php echo htmlspecialchars($exam['passing_score']); ?>%</p>
                                
                                <div class="card-actions justify-end mt-4">
                                    <button class="btn btn-sm btn-custom" onclick="viewDocument(<?php echo $exam['id']; ?>, '<?php echo $exam['status']; ?>')">
                                        <i class="fas fa-eye mr-1"></i> View Document
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
          </div>
        </div>
    </div>
  </div>

  <!-- MODALS -->

  <!-- View Document Modal -->
  <dialog id="view_document_modal" class="modal modal-middle">
    <div class="modal-box max-w-4xl">
      <h3 class="font-bold text-lg mb-4" id="documentTitle">Examination Document</h3>
      
      <!-- Document Preview Section -->
      <div class="bg-base-200 p-6 rounded-lg mb-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h4 class="text-lg font-semibold" id="previewExamTitle">Employee Policy Examination</h4>
            <div class="flex flex-wrap gap-2 mt-2">
              <div class="badge badge-primary" id="previewDepartment">Human Resources</div>
              <div class="badge badge-outline" id="previewQuestionCount">10 Questions</div>
              <div class="badge" id="previewStatusBadge">Pending</div>
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <p class="text-sm text-gray-500">Duration</p>
            <p class="font-medium" id="previewDuration">30 minutes</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Passing Score</p>
            <p class="font-medium" id="previewPassingScore">70%</p>
          </div>
        </div>
        
        <div class="mb-4">
          <p class="text-sm text-gray-500">Description</p>
          <p id="previewDescription">This examination tests knowledge of company policies and procedures.</p>
        </div>
        
        <div class="card bg-white">
          <div class="card-body">
            <h4 class="card-title">Document Preview</h4>
            <div class="flex items-center justify-center h-64 bg-base-100 rounded-lg border-2 border-dashed border-gray-300">
              <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 font-medium">examination_document.pdf</p>
                <p class="text-sm text-gray-500">2.4 MB</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- CRUD Operations Section - Dynamic based on status -->
      <div id="crudOperations">
        <!-- This section will be dynamically populated based on status -->
      </div>
      
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-custom">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- Compliance Reason Modal -->
  <dialog id="compliance_reason_modal" class="modal">
    <div class="modal-box">
      <h3 class="font-bold text-lg">Compliance Remarks</h3>
      <div class="py-4">
        <p class="mb-4">This examination requires compliance updates. Please review the following remarks:</p>
        <div class="bg-yellow-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-yellow-800" id="complianceRemarksText">The examination needs updated questions according to the latest industry standards. Please ensure all references to company policies are from the 2023 revision.</p>
        </div>
        <div class="form-control">
          <label class="label">
            <span class="label-text">Add Additional Remarks</span>
          </label>
          <textarea class="textarea textarea-bordered w-full h-32" placeholder="Enter additional remarks..." id="additionalRemarks"></textarea>
        </div>
      </div>
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-custom">Close</button>
        </form>
        <button class="btn btn-success" onclick="saveComplianceRemarks()">Save Remarks</button>
      </div>
    </div>
  </dialog>

  <!-- NEW: CREATE EXAMINATION MODAL -->
  <dialog id="create_examination_modal" class="modal">
    <div class="modal-box examination-modal">
      <h3 class="font-bold text-lg mb-6">Create New Examination</h3>
      
      <div class="examination-grid">
        <!-- Left: Examination Details Form -->
        <div class="form-section">
          <h4 class="font-semibold text-lg mb-4">Examination Details</h4>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Examination Title</span>
              <span class="label-text-alt text-red-500">*</span>
            </label>
            <input type="text" id="examTitle" class="input input-bordered w-full" placeholder="Enter examination title" required>
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea id="examDescription" class="textarea textarea-bordered h-32" placeholder="Enter examination description..."></textarea>
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Duration (minutes)</span>
            </label>
            <input type="number" id="examDuration" class="input input-bordered w-full" placeholder="60" min="1" value="60">
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Passing Score (%)</span>
            </label>
            <input type="number" id="passingScore" class="input input-bordered w-full" placeholder="70" min="1" max="100" value="70">
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Number of Questions</span>
            </label>
            <input type="number" id="questionCount" class="input input-bordered w-full" placeholder="10" min="1" value="10">
          </div>
        </div>
        
        <!-- Right: Posted Modules List -->
        <div class="modules-section">
          <h4 class="font-semibold text-lg mb-4">Select Learning Module</h4>
          
          <!-- Filter Section -->
          <div class="filter-section">
            <select id="modalDepartmentFilter" class="filter-select">
              <option value="">All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>">
                  <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $dept))); ?>
                </option>
              <?php endforeach; ?>
            </select>
            
            <select id="modalRoleFilter" class="filter-select" disabled>
              <option value="">All Roles</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?php echo htmlspecialchars($role); ?>">
                  <?php echo htmlspecialchars($role); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Modules List -->
          <div class="modules-list" id="modulesList">
            <?php if (empty($posted_modules)): ?>
              <div class="no-modules">
                <i class="fas fa-file-alt text-4xl mb-4 text-gray-400"></i>
                <p>No posted modules available</p>
                <p class="text-sm">Modules need to be posted first before creating examinations</p>
              </div>
            <?php else: ?>
              <?php foreach ($posted_modules as $module): ?>
                <div class="module-item" 
                     data-id="<?php echo $module['id']; ?>"
                     data-department="<?php echo htmlspecialchars($module['department']); ?>"
                     data-role="<?php echo htmlspecialchars($module['roles']); ?>"
                     data-content="<?php echo htmlspecialchars($module['content'] ?? ''); ?>">
                  <div class="flex justify-between items-start">
                    <div class="module-title"><?php echo htmlspecialchars($module['title']); ?></div>
                    <button class="btn btn-xs btn-info view-module-btn" data-id="<?php echo $module['id']; ?>">
                      <i class="fas fa-eye mr-1"></i> View
                    </button>
                  </div>
                  <div class="module-meta">
                    <span class="module-badge"><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $module['department']))); ?></span>
                    <span class="module-badge"><?php echo htmlspecialchars($module['roles']); ?></span>
                    <span class="text-xs"><?php echo date('M j, Y', strtotime($module['created_at'])); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <div class="selected-module-info bg-blue-50 p-3 rounded-lg mt-2" id="selectedModuleInfo" style="display: none;">
            <div class="flex justify-between items-center">
              <div>
                <strong>Selected Module:</strong>
                <span id="selectedModuleTitle" class="ml-2"></span>
              </div>
              <button type="button" id="clearSelection" class="btn btn-xs btn-ghost">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Form Actions -->
      <div class="form-actions">
        <form method="dialog">
          <button class="btn btn-custom">Cancel</button>
        </form>
        <button class="btn btn-info" id="convertModuleBtn" disabled>
          <i class="fas fa-sync-alt mr-2"></i>Convert
        </button>
        <button class="btn btn-success" id="startExaminationBtn" disabled>
          <i class="fas fa-play mr-2"></i>Start Examination
        </button>
      </div>
    </div>
  </dialog>

  <!-- NEW: MODULE CONTENT PREVIEW MODAL - BOND PAPER SIZE -->
  <dialog id="module_content_modal" class="modal document-preview-modal">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4" id="moduleContentTitle">Module Content</h3>
      
      <div class="document-content" id="moduleContentDisplay">
        <!-- Module content will be displayed here -->
      </div>
      
      <div class="modal-action mt-4">
        <form method="dialog">
          <button class="btn btn-custom">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Current exam ID for operations
    let currentExamId = null;
    let currentExamStatus = null;

    // NEW: Create Examination Modal Variables
    let selectedModuleId = null;
    let selectedModuleData = null;

    // Store modal state in sessionStorage
    function saveModalState() {
      const state = {
        examTitle: document.getElementById('examTitle').value,
        examDescription: document.getElementById('examDescription').value,
        examDuration: document.getElementById('examDuration').value,
        passingScore: document.getElementById('passingScore').value,
        questionCount: document.getElementById('questionCount').value,
        selectedModuleId: selectedModuleId,
        selectedModuleData: selectedModuleData,
        departmentFilter: document.getElementById('modalDepartmentFilter').value,
        roleFilter: document.getElementById('modalRoleFilter').value
      };
      sessionStorage.setItem('examinationModalState', JSON.stringify(state));
    }

    function loadModalState() {
      const savedState = sessionStorage.getItem('examinationModalState');
      if (savedState) {
        const state = JSON.parse(savedState);
        document.getElementById('examTitle').value = state.examTitle || '';
        document.getElementById('examDescription').value = state.examDescription || '';
        document.getElementById('examDuration').value = state.examDuration || '60';
        document.getElementById('passingScore').value = state.passingScore || '70';
        document.getElementById('questionCount').value = state.questionCount || '10';
        document.getElementById('modalDepartmentFilter').value = state.departmentFilter || '';
        document.getElementById('modalRoleFilter').value = state.roleFilter || '';
        
        if (state.selectedModuleId) {
          selectedModuleId = state.selectedModuleId;
          selectedModuleData = state.selectedModuleData;
          
          // Restore selected module UI
          document.querySelectorAll('.module-item').forEach(item => {
            if (item.dataset.id === selectedModuleId) {
              item.classList.add('selected');
              document.getElementById('selectedModuleTitle').textContent = selectedModuleData.title;
              document.getElementById('selectedModuleInfo').style.display = 'block';
              document.getElementById('startExaminationBtn').disabled = false;
              document.getElementById('convertModuleBtn').disabled = false;
            }
          });
        }
        
        // Enable role filter if department is selected
        if (state.departmentFilter) {
          document.getElementById('modalRoleFilter').disabled = false;
        }
      }
    }

    function clearModalState() {
      sessionStorage.removeItem('examinationModalState');
    }

    // Function to load examinations from localStorage
    function loadLocalStorageExaminations() {
      const examinations = JSON.parse(localStorage.getItem('examinations')) || [];
      const examinationCards = document.getElementById('examinationCards');
      
      // Clear existing cards except database ones
      const dbCards = examinationCards.querySelectorAll('[data-source="database"]');
      const localStorageCards = examinationCards.querySelectorAll('[data-source="localstorage"]');
      localStorageCards.forEach(card => card.remove());
      
      // Add localStorage examinations (only approved, rejected, hold, compliance)
      const filteredExams = examinations.filter(exam => 
        exam.status === 'approved' || 
        exam.status === 'rejected' || 
        exam.status === 'hold' || 
        exam.status === 'compliance'
      );
      
      filteredExams.forEach(exam => {
        const examCard = createExamCard(exam, 'localstorage');
        examinationCards.appendChild(examCard);
      });
    }

    // Function to create examination card
    function createExamCard(exam, source = 'database') {
      const card = document.createElement('div');
      card.className = 'card bg-base-100 shadow-md examination-card';
      card.setAttribute('data-status', exam.status);
      card.setAttribute('data-department', exam.department || 'general');
      card.setAttribute('data-source', source);
      
      const formattedDate = new Date(exam.created_at).toLocaleDateString();
      
      card.innerHTML = `
        <div class="card-body">
          <div class="flex justify-between items-start">
            <h3 class="card-title">${exam.examTitle || 'Untitled Examination'}</h3>
            <div class="badge status-${exam.status}">
              ${exam.status.charAt(0).toUpperCase() + exam.status.slice(1)}
            </div>
          </div>
          <div class="flex flex-wrap gap-2 my-2">
            <div class="badge badge-outline">${exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General'}</div>
            <div class="badge badge-outline">${exam.questions ? exam.questions.length : 0} Questions</div>
          </div>
          <p class="text-sm text-gray-500">Created: ${formattedDate}</p>
          <p class="text-sm text-gray-500">Duration: ${exam.duration || 60} minutes</p>
          <p class="text-sm text-gray-500">Passing Score: ${exam.passing_score || 70}%</p>
          
          <div class="card-actions justify-end mt-4">
            <button class="btn btn-sm btn-custom" onclick="viewLocalStorageDocument('${exam.id}', '${exam.status}')">
              <i class="fas fa-eye mr-1"></i> View Document
            </button>
          </div>
        </div>
      `;
      
      return card;
    }

    // Function to view localStorage examination document
    function viewLocalStorageDocument(id, status) {
      const examinations = JSON.parse(localStorage.getItem('examinations')) || [];
      const exam = examinations.find(e => e.id === id);
      
      if (!exam) {
        Swal.fire({
          title: 'Error',
          text: 'Examination not found!',
          icon: 'error',
          confirmButtonText: 'OK'
        });
        return;
      }
      
      currentExamId = id;
      currentExamStatus = status;
      
      // Update modal content
      document.getElementById('previewExamTitle').textContent = exam.examTitle || 'Untitled Examination';
      document.getElementById('previewDepartment').textContent = exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General';
      document.getElementById('previewQuestionCount').textContent = `${exam.questions ? exam.questions.length : 0} Questions`;
      document.getElementById('previewDuration').textContent = `${exam.duration || 60} minutes`;
      document.getElementById('previewPassingScore').textContent = `${exam.passing_score || 70}%`;
      document.getElementById('previewDescription').textContent = exam.examDescription || 'No description provided.';
      
      // Update status badge
      const statusBadge = document.getElementById('previewStatusBadge');
      statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
      statusBadge.className = 'badge status-' + status;
      
      // Set CRUD operations based on status
      setupCrudOperations(status);
      
      view_document_modal.showModal();
    }

    // Filter functionality
    function applyFilters() {
      const statusFilter = document.getElementById('statusFilter').value;
      const departmentFilter = document.getElementById('departmentFilter').value;
      
      const cards = document.querySelectorAll('.examination-card');
      
      cards.forEach(card => {
        let show = true;
        
        // Status filter
        if (statusFilter !== 'all' && card.dataset.status !== statusFilter) {
          show = false;
        }
        
        // Department filter
        if (departmentFilter !== 'all' && card.dataset.department !== departmentFilter) {
          show = false;
        }
        
        card.style.display = show ? 'block' : 'none';
      });
    }
    
    function clearFilters() {
      document.getElementById('statusFilter').value = 'all';
      document.getElementById('departmentFilter').value = 'all';
      
      const cards = document.querySelectorAll('.examination-card');
      cards.forEach(card => {
        card.style.display = 'block';
      });
    }
    
    // View Document with CRUD operations based on status
    function viewDocument(id, status) {
      currentExamId = id;
      currentExamStatus = status;
      
      // In a real implementation, this would fetch exam details from the server
      document.getElementById('previewExamTitle').textContent = 'Employee Policy Examination ' + id;
      document.getElementById('previewDepartment').textContent = 'Human Resources';
      document.getElementById('previewQuestionCount').textContent = '10 Questions';
      document.getElementById('previewDuration').textContent = '30 minutes';
      document.getElementById('previewPassingScore').textContent = '70%';
      document.getElementById('previewDescription').textContent = 'This examination tests knowledge of company policies and procedures.';
      
      // Update status badge
      const statusBadge = document.getElementById('previewStatusBadge');
      statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
      statusBadge.className = 'badge status-' + status;
      
      // Set CRUD operations based on status
      setupCrudOperations(status);
      
      view_document_modal.showModal();
    }
    
    // Setup CRUD operations based on status
    function setupCrudOperations(status) {
      const crudContainer = document.getElementById('crudOperations');
      let html = '<div class="flex flex-wrap gap-2 justify-end mb-4">';
      
      switch(status) {
        case 'approved':
          html += `
            <button class="btn btn-success" onclick="postExam('${currentExamId}')">
              <i class="fas fa-share-square mr-1"></i> Post
            </button>
            <button class="btn btn-warning" onclick="holdExam('${currentExamId}')">
              <i class="fas fa-pause-circle mr-1"></i> Hold
            </button>
          `;
          break;
          
        case 'hold':
          html += `
            <button class="btn btn-success" onclick="postExam('${currentExamId}')">
              <i class="fas fa-share-square mr-1"></i> Post
            </button>
          `;
          break;
          
        case 'rejected':
          html += `
            <button class="btn btn-danger" onclick="deleteLocalStorageExam('${currentExamId}')">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
          `;
          break;
          
        case 'compliance':
          html += `
            <button class="btn btn-custom" onclick="showComplianceReason('${currentExamId}')">
              <i class="fas fa-comment-alt mr-1"></i> Reason Why
            </button>
            <button class="btn btn-danger" onclick="deleteLocalStorageExam('${currentExamId}')">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
            <button class="btn btn-success" onclick="editExam('${currentExamId}')">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
          `;
          break;
          
        default:
          html += '<p>No actions available for this status.</p>';
      }
      
      html += '</div>';
      crudContainer.innerHTML = html;
    }
    
    // CRUD Operations for localStorage exams
    function deleteLocalStorageExam(id) {
      Swal.fire({
        title: 'Delete Examination?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          let examinations = JSON.parse(localStorage.getItem('examinations')) || [];
          examinations = examinations.filter(exam => exam.id !== id);
          localStorage.setItem('examinations', JSON.stringify(examinations));
          
          Swal.fire(
            'Deleted!',
            'Examination has been deleted.',
            'success'
          ).then(() => {
            view_document_modal.close();
            loadLocalStorageExaminations(); // Refresh the display
          });
        }
      });
    }
    
    function cancelRequest(id) {
      Swal.fire({
        title: 'Cancel Examination Request?',
        text: "Are you sure you want to cancel this examination request?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          let examinations = JSON.parse(localStorage.getItem('examinations')) || [];
          const examIndex = examinations.findIndex(exam => exam.id === id);
          if (examIndex !== -1) {
            examinations[examIndex].status = 'cancelled';
            localStorage.setItem('examinations', JSON.stringify(examinations));
          }
          
          Swal.fire(
            'Cancelled!',
            'Examination request has been cancelled.',
            'success'
          ).then(() => {
            view_document_modal.close();
            loadLocalStorageExaminations();
          });
        }
      });
    }
    
    function reviewDocument(id) {
      // Redirect to review dashboard
      window.location.href = 'review_dashboard.php?id=' + id;
    }
    
    function postExam(id) {
      Swal.fire({
        title: 'Post Examination?',
        text: "Are you sure you want to post this examination? It will be visible to employees.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, post it!',
        cancelButtonText: 'No, cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          let examinations = JSON.parse(localStorage.getItem('examinations')) || [];
          const examIndex = examinations.findIndex(exam => exam.id === id);
          if (examIndex !== -1) {
            examinations[examIndex].status = 'posted';
            localStorage.setItem('examinations', JSON.stringify(examinations));
          }
          
          Swal.fire(
            'Posted!',
            'Examination has been posted successfully.',
            'success'
          ).then(() => {
            view_document_modal.close();
            loadLocalStorageExaminations();
          });
        }
      });
    }
    
    function holdExam(id) {
      Swal.fire({
        title: 'Hold Examination?',
        text: "Are you sure you want to put this examination on hold? It will not be visible to employees.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, put on hold',
        cancelButtonText: 'No, cancel',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          let examinations = JSON.parse(localStorage.getItem('examinations')) || [];
          const examIndex = examinations.findIndex(exam => exam.id === id);
          if (examIndex !== -1) {
            examinations[examIndex].status = 'hold';
            localStorage.setItem('examinations', JSON.stringify(examinations));
          }
          
          Swal.fire(
            'On Hold!',
            'Examination has been put on hold.',
            'success'
          ).then(() => {
            view_document_modal.close();
            loadLocalStorageExaminations();
          });
        }
      });
    }
    
    function editExam(id) {
      // For now, just show a message
      Swal.fire({
        title: 'Edit Examination',
        text: 'Edit functionality will be implemented in the next phase.',
        icon: 'info',
        confirmButtonText: 'OK'
      });
    }
    
    function showComplianceReason(id) {
      document.getElementById('complianceRemarksText').textContent = 
        'The examination needs updated questions according to the latest industry standards. Please ensure all references to company policies are from the 2023 revision.';
      
      compliance_reason_modal.showModal();
    }
    
    function saveComplianceRemarks() {
      const additionalRemarks = document.getElementById('additionalRemarks').value;
      alert('Compliance remarks saved for examination ID: ' + currentExamId + '\nAdditional Remarks: ' + additionalRemarks);
      compliance_reason_modal.close();
    }

    // NEW: Create Examination Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
      const createExaminationBtn = document.getElementById('createExaminationBtn');
      const createExaminationModal = document.getElementById('create_examination_modal');
      const departmentFilter = document.getElementById('modalDepartmentFilter');
      const roleFilter = document.getElementById('modalRoleFilter');
      const modulesList = document.getElementById('modulesList');
      const startExaminationBtn = document.getElementById('startExaminationBtn');
      const convertModuleBtn = document.getElementById('convertModuleBtn');
      const selectedModuleInfo = document.getElementById('selectedModuleInfo');
      const selectedModuleTitle = document.getElementById('selectedModuleTitle');
      const clearSelection = document.getElementById('clearSelection');
      const moduleContentModal = document.getElementById('module_content_modal');
      const moduleContentTitle = document.getElementById('moduleContentTitle');
      const moduleContentDisplay = document.getElementById('moduleContentDisplay');

      // Load saved modal state
      loadModalState();
      
      // Load localStorage examinations
      loadLocalStorageExaminations();

      // Open examination modal
      createExaminationBtn.addEventListener('click', function() {
        createExaminationModal.showModal();
        // Don't reset form if we have saved state
        if (!sessionStorage.getItem('examinationModalState')) {
          resetExaminationForm();
        }
      });

      // Save state when inputs change
      document.getElementById('examTitle').addEventListener('input', saveModalState);
      document.getElementById('examDescription').addEventListener('input', saveModalState);
      document.getElementById('examDuration').addEventListener('input', saveModalState);
      document.getElementById('passingScore').addEventListener('input', saveModalState);
      document.getElementById('questionCount').addEventListener('input', saveModalState);
      departmentFilter.addEventListener('change', saveModalState);
      roleFilter.addEventListener('change', saveModalState);

      // Department filter change
      departmentFilter.addEventListener('change', function() {
        filterModules();
        saveModalState();
        
        // Enable/disable role filter based on department selection
        if (this.value) {
          roleFilter.disabled = false;
        } else {
          roleFilter.disabled = true;
          roleFilter.value = '';
        }
      });

      // Role filter change
      roleFilter.addEventListener('change', function() {
        filterModules();
        saveModalState();
      });

      // Module selection
      modulesList.addEventListener('click', function(e) {
        // Handle view button click
        if (e.target.closest('.view-module-btn')) {
          const viewBtn = e.target.closest('.view-module-btn');
          const moduleId = viewBtn.dataset.id;
          const moduleItem = viewBtn.closest('.module-item');
          const moduleTitle = moduleItem.querySelector('.module-title').textContent;
          const moduleContent = moduleItem.dataset.content;
          
          // Display module content in modal
          moduleContentTitle.textContent = moduleTitle;
          moduleContentDisplay.innerHTML = moduleContent || '<p class="text-gray-500">No content available for this module.</p>';
          moduleContentModal.showModal();
          return;
        }
        
        // Handle module selection
        const moduleItem = e.target.closest('.module-item');
        if (moduleItem && !e.target.closest('.view-module-btn')) {
          // Remove previous selection
          document.querySelectorAll('.module-item').forEach(item => {
            item.classList.remove('selected');
          });
          
          // Add selection to clicked item
          moduleItem.classList.add('selected');
          
          // Store selected module data
          selectedModuleId = moduleItem.dataset.id;
          selectedModuleData = {
            id: selectedModuleId,
            title: moduleItem.querySelector('.module-title').textContent,
            department: moduleItem.dataset.department,
            role: moduleItem.dataset.role,
            content: moduleItem.dataset.content
          };
          
          // Update selected module info
          selectedModuleTitle.textContent = selectedModuleData.title;
          selectedModuleInfo.style.display = 'block';
          
          // Enable buttons
          startExaminationBtn.disabled = false;
          convertModuleBtn.disabled = false;

          saveModalState();
        }
      });

      // Clear selection
      clearSelection.addEventListener('click', function() {
        selectedModuleId = null;
        selectedModuleData = null;
        document.querySelectorAll('.module-item').forEach(item => {
          item.classList.remove('selected');
        });
        selectedModuleInfo.style.display = 'none';
        startExaminationBtn.disabled = true;
        convertModuleBtn.disabled = true;

        saveModalState();
      });

      // Start Examination - redirect to create_examination.php
      startExaminationBtn.addEventListener('click', function() {
        const examTitle = document.getElementById('examTitle').value;
        const examDescription = document.getElementById('examDescription').value;
        const examDuration = document.getElementById('examDuration').value;
        const passingScore = document.getElementById('passingScore').value;
        const questionCount = document.getElementById('questionCount').value;

        if (!examTitle.trim()) {
          Swal.fire({
            title: 'Missing Information',
            text: 'Please enter an examination title.',
            icon: 'warning',
            confirmButtonText: 'OK'
          });
          return;
        }

        if (!selectedModuleId) {
          Swal.fire({
            title: 'No Module Selected',
            text: 'Please select a learning module for the examination.',
            icon: 'warning',
            confirmButtonText: 'OK'
          });
          return;
        }

        // Close modal first so SweetAlert is visible
        createExaminationModal.close();

        // Use SweetAlert for confirmation
        Swal.fire({
          title: 'Start Examination Creation?',
          text: "You will be redirected to the examination creation page with the selected module.",
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, start examination!',
          cancelButtonText: 'No, cancel',
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#6c757d',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            // Clear modal state before redirecting
            clearModalState();
            // Redirect to create_examination.php with module data
            const params = new URLSearchParams({
              module_id: selectedModuleId,
              title: examTitle,
              description: examDescription,
              duration: examDuration,
              passing_score: passingScore,
              question_count: questionCount
            });
            window.location.href = 'create_examination.php?' + params.toString();
          } else {
            // If cancelled, reopen the modal
            createExaminationModal.showModal();
          }
        });
      });

      // Convert Module functionality
      convertModuleBtn.addEventListener('click', function() {
        if (!selectedModuleId) {
          Swal.fire({
            title: 'No Module Selected',
            text: 'Please select a learning module to convert.',
            icon: 'warning',
            confirmButtonText: 'OK'
          });
          return;
        }

        // Close modal first so SweetAlert is visible
        createExaminationModal.close();

        // Use SweetAlert for conversion
        Swal.fire({
          title: 'Convert Module to Examination?',
          html: `This will automatically convert the selected module "<strong>${selectedModuleData.title}</strong>" into an examination using AI.`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, convert it!',
          cancelButtonText: 'No, cancel',
          confirmButtonColor: '#17a2b8',
          cancelButtonColor: '#6c757d',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
              title: 'Converting Module...',
              text: 'Please wait while we convert your module to an examination.',
              icon: 'info',
              showConfirmButton: false,
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            // Simulate conversion process
            setTimeout(() => {
              Swal.fire({
                title: 'Conversion Complete!',
                html: `The module "<strong>${selectedModuleData.title}</strong>" has been successfully converted to an examination.`,
                icon: 'success',
                confirmButtonText: 'View Examination',
                confirmButtonColor: '#28a745'
              }).then(() => {
                // Clear modal state and refresh
                clearModalState();
                location.reload();
              });
            }, 2000);
          } else {
            // If cancelled, reopen the modal
            createExaminationModal.showModal();
          }
        });
      });

      // Filter modules function
      function filterModules() {
        const departmentValue = departmentFilter.value;
        const roleValue = roleFilter.value;
        
        document.querySelectorAll('.module-item').forEach(item => {
          const itemDept = item.dataset.department;
          const itemRole = item.dataset.role;
          
          let deptMatch = !departmentValue || itemDept === departmentValue;
          let roleMatch = !roleValue || itemRole === roleValue;
          
          if (deptMatch && roleMatch) {
            item.style.display = 'block';
          } else {
            item.style.display = 'none';
          }
        });
      }

      // Reset examination form
      function resetExaminationForm() {
        document.getElementById('examTitle').value = '';
        document.getElementById('examDescription').value = '';
        document.getElementById('examDuration').value = '60';
        document.getElementById('passingScore').value = '70';
        document.getElementById('questionCount').value = '10';
        departmentFilter.value = '';
        roleFilter.value = '';
        roleFilter.disabled = true;
        
        selectedModuleId = null;
        selectedModuleData = null;
        document.querySelectorAll('.module-item').forEach(item => {
          item.classList.remove('selected');
          item.style.display = 'block';
        });
        selectedModuleInfo.style.display = 'none';
        startExaminationBtn.disabled = true;
        convertModuleBtn.disabled = true;
      }

      // Clear modal state when modal is closed via cancel button
      document.querySelector('#create_examination_modal form[method="dialog"] button').addEventListener('click', function() {
        clearModalState();
      });
    });
  </script>
  <script src="../JS/soliera.js"></script>
  <script src="../JS/sidebar.js"></script>
</body>
</html>