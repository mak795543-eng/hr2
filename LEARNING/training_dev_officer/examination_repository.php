<?php
session_start();
// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

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
$departments = [
    'front-office',
    'housekeeping',
    'food-beverage',
    'kitchen',
    'sales-marketing',
    'hr',
    'finance',
    'engineering',
    'security'
];
$roles = [];

// Fetch examinations from database
$examinations = [];
$sql = "SELECT e.*, COUNT(eq.id) AS question_count
        FROM examinations e
        LEFT JOIN examination_questions eq ON e.id = eq.examination_id
        WHERE e.status IN ('pending', 'approved', 'rejected', 'hold', 'compliance', 'for-compliance', 'cancelled')
        GROUP BY e.id
        ORDER BY e.created_at DESC";
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
      <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../../CSS/learning_theme.css">
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
    .status-cancelled {
      @apply bg-gray-200 text-gray-700;
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

    #view_document_modal .modal-box {
      padding: 1.5rem;
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
      align-items: center;
      flex-wrap: wrap;
      margin-top: auto;
      padding-top: 1.5rem;
      border-top: 1px solid #e5e7eb;
    }

    .form-actions button {
      min-width: 170px;
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
      height: 60vh;
      max-height: 60vh;
      overflow-y: auto;
      padding: 1.5rem;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      /* Bond paper styling */
      width: min(8.5in, 100%);
      max-width: 100%;
      min-height: 0;
      margin: 0 auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      background: white;
      position: relative;
    }

    #crudOperations {
      padding-top: 0.75rem;
      margin-top: 0.25rem;
      border-top: 1px solid #e5e7eb;
    }

    .view-doc-actions .btn {
      min-width: 140px;
    }

    .view-doc-actions form {
      margin: 0;
    }

    /* Ensure SweetAlert buttons are always visible and on top */
    .swal2-container {
      position: fixed !important;
      inset: 0 !important;
      z-index: 2147483647 !important;
      pointer-events: auto !important;
    }
    
    .swal2-popup {
      z-index: 2147483647 !important;
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

    /* Examination Preview Modal Styles */
    #previewExamModal .modal-box {
      max-width: 90vw;
      width: 95%;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
    }

    .preview-exam-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1.5rem;
      border-radius: 0.5rem 0.5rem 0 0;
      margin-bottom: 1.5rem;
    }

    .preview-question {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .preview-question h4 {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.75rem;
      font-size: 1.1rem;
    }

    .preview-option {
      padding: 0.75rem;
      margin-bottom: 0.5rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      background: #f9fafb;
      transition: all 0.2s ease;
    }

    .preview-option.correct {
      background: #d1fae5;
      border-color: #10b981;
      color: #065f46;
    }

    .preview-option.selected {
      background: #dbeafe;
      border-color: #3b82f6;
    }

    .preview-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem;
      background: #f8fafc;
      border-radius: 0.5rem;
      margin-top: 1rem;
      font-size: 0.875rem;
      color: #64748b;
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
      
      #previewExamModal .modal-box {
        max-width: 95vw;
        width: 98%;
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
      
      .preview-exam-header {
        padding: 1rem;
      }
      
      .preview-question {
        padding: 1rem;
      }
      
      .preview-meta {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
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
      
      #previewExamModal .modal-box {
        max-width: 99vw;
        width: 100%;
        margin: 0.5rem;
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
    .form-section::-webkit-scrollbar,
    #previewExamModal .modal-box::-webkit-scrollbar {
      width: 6px;
    }

    .modules-list::-webkit-scrollbar-track,
    .form-section::-webkit-scrollbar-track,
    #previewExamModal .modal-box::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    .modules-list::-webkit-scrollbar-thumb,
    .form-section::-webkit-scrollbar-thumb,
    #previewExamModal .modal-box::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }

    .modules-list::-webkit-scrollbar-thumb:hover,
    .form-section::-webkit-scrollbar-thumb:hover,
    #previewExamModal .modal-box::-webkit-scrollbar-thumb:hover {
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
      
      #previewExamModal .modal-box {
        box-shadow: none;
        border: none;
      }
      
      .preview-exam-header {
        background: white !important;
        color: black !important;
        border: 1px solid #000;
      }
    }
</style>
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

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
          <!-- Examinations Section -->
          <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-2xl font-bold mb-2">Examination Management</h1>
                <p class="text-gray-600">Manage examinations in the repository</p>
              </div>
              <div class="flex gap-2">
                <button class="btn btn-custom" id="createExaminationBtn">
                  <i class="fas fa-plus mr-2"></i>Create Examination</button>
  
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
                    <option value="pending">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="hold">Hold</option>
                    <option value="rejected">Rejected</option>
                    <option value="compliance">For Compliance</option>
                    <option value="cancelled">Canceled</option>
                  </select>
                </div>
                
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Department</span>
                  </label>
                  <select class="select select-bordered w-48" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <option value="front-office">Front Office / Reception</option>
                    <option value="housekeeping">Housekeeping</option>
                    <option value="food-beverage">Food &amp; Beverage (F&amp;B)</option>
                    <option value="kitchen">Kitchen / Culinary</option>
                    <option value="sales-marketing">Sales &amp; Marketing</option>
                    <option value="hr">Human Resources (HR)</option>
                    <option value="human-resources">Human Resources (HR)</option>
                    <option value="finance">Finance / Accounting</option>
                    <option value="engineering">Engineering / Maintenance</option>
                    <option value="security">Security</option>
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
<?php echo $exam['status'] === 'pending' ? 'Under Review' : ($exam['status'] === 'cancelled' ? 'Canceled' : ucfirst($exam['status'])); ?>
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
      <div class="flex justify-between items-center mb-4 w-full">
        <h3 class="font-bold text-lg" id="documentTitle">Examination Document</h3>
        <form method="dialog">
          <button class="btn btn-sm btn-circle btn-ghost" type="submit">✕</button>
        </form>
      </div>
      
      <!-- Document Preview Section -->
      <div class="bg-base-200 p-5 rounded-lg mb-4">
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
          <div class="card-body p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-semibold">Document Preview</h4>
            </div>
            <div id="documentPreviewContent" class="document-content"></div>
          </div>
        </div>
      </div>
      
      <!-- CRUD Operations Section - Dynamic based on status -->
      <div class="view-doc-actions mt-2 pt-4 border-t border-gray-200 flex flex-wrap gap-2 justify-end items-center">
        <div id="crudOperations" class="flex flex-wrap gap-2 justify-end items-center">
          <!-- This section will be dynamically populated based on status -->
        </div>
        <form method="dialog">
          <button class="btn btn-custom">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <dialog id="create_examination_modal" class="modal modal-middle">
    <div class="modal-box examination-modal">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-lg">Create Examination</h3>
        <form method="dialog">
          <button class="btn btn-sm btn-circle btn-ghost" type="submit">✕</button>
        </form>
      </div>

      <div class="examination-grid">
        <div class="form-section">
          <div class="form-control">
            <label class="label"><span class="label-text font-medium">Examination Title</span></label>
            <input id="examTitle" type="text" class="input input-bordered" placeholder="Enter examination title" />
          </div>

          <div class="form-control">
            <label class="label"><span class="label-text font-medium">Description</span></label>
            <textarea id="examDescription" class="textarea textarea-bordered" rows="3" placeholder="Enter description"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="form-control">
              <label class="label"><span class="label-text font-medium">Duration (min)</span></label>
              <input id="examDuration" type="number" min="1" class="input input-bordered" value="60" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text font-medium">Passing Score (%)</span></label>
              <input id="passingScore" type="number" min="0" max="100" class="input input-bordered" value="70" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text font-medium">Target Questions</span></label>
              <input id="questionCount" type="number" min="1" class="input input-bordered" value="10" />
            </div>
          </div>

          <div id="selectedModuleInfo" style="display:none" class="bg-base-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-500">Selected module</div>
                <div id="selectedModuleTitle" class="font-semibold"></div>
              </div>
              <button id="clearSelection" type="button" class="btn btn-ghost btn-sm">Clear</button>
            </div>
          </div>

          <div class="form-actions">
            <button id="convertModuleBtn" type="button" class="btn btn-info" style="min-width: 170px" disabled>
              <i class="fas fa-wand-magic-sparkles mr-2"></i>Convert Module
            </button>
            <button id="startExaminationBtn" type="button" class="btn btn-success" style="min-width: 170px" disabled>
              <i class="fas fa-arrow-right mr-2"></i>Start Examination
            </button>
          </div>
        </div>

        <div class="modules-section">
          <div class="filter-section">
            <select id="modalDepartmentFilter" class="filter-select">
              <option value="">All Departments</option>
              <option value="front-office">Front Office / Reception</option>
              <option value="housekeeping">Housekeeping</option>
              <option value="food-beverage">Food &amp; Beverage (F&amp;B)</option>
              <option value="kitchen">Kitchen / Culinary</option>
              <option value="sales-marketing">Sales &amp; Marketing</option>
              <option value="hr">Human Resources (HR)</option>
              <option value="human-resources">Human Resources (HR)</option>
              <option value="finance">Finance / Accounting</option>
              <option value="engineering">Engineering / Maintenance</option>
              <option value="security">Security</option>
            </select>
            <select id="modalRoleFilter" class="filter-select" disabled>
              <option value="" disabled selected>Select Department First</option>
            </select>
          </div>

          <div id="modulesList" class="modules-list">
<?php if (empty($posted_modules)): ?>
              <div class="no-modules">No posted modules available.</div>
<?php else: ?>
<?php foreach ($posted_modules as $m): ?>
                <div
                  class="module-item"
                  data-id="<?php echo htmlspecialchars((string)$m['id']); ?>"
                  data-department="<?php echo htmlspecialchars((string)($m['department'] ?? '')); ?>"
                  data-role="<?php echo htmlspecialchars((string)($m['roles'] ?? '')); ?>"
                  data-content="<?php echo htmlspecialchars((string)($m['content'] ?? '')); ?>"
                >
                  <div class="flex justify-between items-start gap-2">
                    <div>
                      <div class="module-title"><?php echo htmlspecialchars((string)($m['title'] ?? 'Untitled Module')); ?></div>
                      <div class="module-meta">
                        <span class="module-badge"><?php echo htmlspecialchars((string)($m['department'] ?? '')); ?></span>
                        <span class="module-badge"><?php echo htmlspecialchars((string)($m['roles'] ?? '')); ?></span>
                      </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm view-module-btn" data-id="<?php echo htmlspecialchars((string)$m['id']); ?>">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
<?php endforeach; ?>
<?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </dialog>

  <dialog id="module_content_modal" class="modal modal-middle">
    <div class="modal-box max-w-4xl">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-lg" id="moduleContentTitle">Module</h3>
        <form method="dialog">
          <button class="btn btn-sm btn-circle btn-ghost" type="submit">✕</button>
        </form>
      </div>
      <div id="moduleContentDisplay" class="prose max-w-none"></div>
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-custom" type="submit">Close</button>
        </form>
      </div>
    </div>
  </dialog>

  <!-- SweetAlert2 JS -->
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

    // Current exam ID for operations
    let currentExamId = null;
    let currentExamStatus = null;
    let currentExamSource = null;

    // Create Examination Modal Variables
    let selectedModuleId = null;
    let selectedModuleData = null;

    function formatExamStatusLabel(status) {
      if (!status) return '';
      if (status === 'pending') return 'Under Review';
      if (status === 'cancelled') return 'Canceled';
      return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function cancelDbExam(id) {
      Swal.fire({
        title: 'Cancel Examination? ',
        text: 'Are you sure you want to cancel this examination?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true
      }).then((result) => {
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('exam_id', id);
        fd.append('new_status', 'cancelled');
        fd.append('remarks', '');

        fetch('../hr_manager/update_exam_status.php', {
          method: 'POST',
          body: fd
        })
          .then(r => r.json())
          .then(data => {
            if (!data?.success) {
              throw new Error(data?.message || 'Failed to cancel examination');
            }

            Swal.fire('Cancelled!', 'Examination has been cancelled.', 'success').then(() => {
              view_document_modal.close();
              window.location.reload();
            });
          })
          .catch(err => {
            Swal.fire({
              title: 'Error',
              text: err?.message || 'Failed to cancel examination',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#3b82f6'
            });
          });
      });
    }

    function deleteDbExam(id) {
      Swal.fire({
        title: 'Delete Examination?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true
      }).then((result) => {
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('original_exam_id', id);

        fetch('delete_examination.php', {
          method: 'POST',
          body: fd
        })
          .then(r => r.json())
          .then(data => {
            if (!data?.success) {
              throw new Error(data?.message || 'Failed to delete examination');
            }

            Swal.fire('Deleted!', 'Examination has been deleted.', 'success').then(() => {
              view_document_modal.close();
              window.location.reload();
            });
          })
          .catch(err => {
            Swal.fire({
              title: 'Error',
              text: err?.message || 'Failed to delete examination',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#3b82f6'
            });
          });
      });
    }

    const departmentRoles = {
      'front-office': [
        'Front Desk Manager',
        'Receptionist / Front Desk Officer',
        'Guest Service Agent / Concierge',
        'Reservation Agent',
        'Bellhop / Porter',
        'Front Office Supervisor'
      ],
      'housekeeping': [
        'Executive Housekeeper / Housekeeping Manager',
        'Floor Supervisor',
        'Room Attendant / Housekeeper',
        'Laundry Attendant',
        'Public Area Attendant',
        'Housekeeping Inspector'
      ],
      'food-beverage': [
        'F&B Manager / Director',
        'Restaurant Manager / Captain',
        'Waiter / Waitress / Server',
        'Bartender',
        'Banquet / Catering Coordinator',
        'F&B Supervisor'
      ],
      'kitchen': [
        'Executive Chef / Head Chef',
        'Sous Chef',
        'Line Cook / Station Chef',
        'Pastry Chef / Baker',
        'Kitchen Steward / Dishwasher',
        'Commis Chef'
      ],
      'sales-marketing': [
        'Sales & Marketing Manager',
        'Revenue Manager',
        'Event / Banquet Sales Coordinator',
        'Social Media / Marketing Executive',
        'Sales Executive',
        'Marketing Coordinator'
      ],
      'hr': [
        'HR Manager / Director',
        'Recruitment Officer',
        'Training & Development Specialist',
        'Payroll / HR Assistant',
        'HR Coordinator',
        'Employee Relations Specialist'
      ],
      'finance': [
        'Finance Manager / Controller',
        'Accountant',
        'Payroll Officer',
        'Cost Controller',
        'Accounts Payable/Receivable Clerk',
        'Financial Analyst'
      ],
      'engineering': [
        'Chief Engineer / Engineering Manager',
        'Maintenance Technician',
        'Electrician / Plumber',
        'HVAC Technician',
        'Carpenter',
        'Painter'
      ],
      'security': [
        'Security Manager / Supervisor',
        'Security Guard',
        'CCTV / Surveillance Officer',
        'Security Officer',
        'Surveillance Operator',
        'Access Control Officer'
      ]
    };

    departmentRoles['human-resources'] = departmentRoles['hr'];

    function populateModalRoleOptions(department, selectedRole = '') {
      const roleSelect = document.getElementById('modalRoleFilter');
      if (!roleSelect) return;

      roleSelect.innerHTML = '';

      if (department && departmentRoles[department]) {
        roleSelect.disabled = false;

        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = 'All Roles';
        roleSelect.appendChild(allOption);

        departmentRoles[department].forEach(role => {
          const option = document.createElement('option');
          option.value = role;
          option.textContent = role;
          roleSelect.appendChild(option);
        });

        if (selectedRole) {
          roleSelect.value = selectedRole;
        }
      } else {
        roleSelect.disabled = true;
        const defaultOption = document.createElement('option');
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = 'Select Department First';
        roleSelect.appendChild(defaultOption);
      }
    }

    // Load saved modal state
    function loadModalState() {
      const state = sessionStorage.getItem('examinationModalState');
      if (state) {
        const parsedState = JSON.parse(state);
        document.getElementById('examTitle').value = parsedState.examTitle || '';
        document.getElementById('examDescription').value = parsedState.examDescription || '';
        document.getElementById('examDuration').value = parsedState.examDuration || '60';
        document.getElementById('passingScore').value = parsedState.passingScore || '70';
        document.getElementById('questionCount').value = parsedState.questionCount || '10';
        document.getElementById('modalDepartmentFilter').value = parsedState.departmentFilter || '';
        populateModalRoleOptions(parsedState.departmentFilter || '', parsedState.roleFilter || '');
        
        if (parsedState.selectedModuleId) {
          selectedModuleId = parsedState.selectedModuleId;
          selectedModuleData = parsedState.selectedModuleData;
          
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
      }
    }

    function clearModalState() {
      sessionStorage.removeItem('examinationModalState');
    }

    function saveModalState() {
      const state = {
        examTitle: document.getElementById('examTitle')?.value || '',
        examDescription: document.getElementById('examDescription')?.value || '',
        examDuration: document.getElementById('examDuration')?.value || '60',
        passingScore: document.getElementById('passingScore')?.value || '70',
        questionCount: document.getElementById('questionCount')?.value || '10',
        departmentFilter: document.getElementById('modalDepartmentFilter')?.value || '',
        roleFilter: document.getElementById('modalRoleFilter')?.value || '',
        selectedModuleId,
        selectedModuleData
      };
      sessionStorage.setItem('examinationModalState', JSON.stringify(state));
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
      currentExamSource = 'local';
      
      // Update modal content
      document.getElementById('previewExamTitle').textContent = exam.examTitle || 'Untitled Examination';
      document.getElementById('previewDepartment').textContent = exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General';
      document.getElementById('previewQuestionCount').textContent = `${exam.questions ? exam.questions.length : 0} Questions`;
      document.getElementById('previewDuration').textContent = `${exam.duration || 60} minutes`;
      document.getElementById('previewPassingScore').textContent = `${exam.passing_score || 70}%`;
      document.getElementById('previewDescription').textContent = exam.examDescription || 'No description provided.';
      
      renderStudentExamPreviewToDocumentModal({
        title: exam.examTitle || 'Untitled Examination',
        description: exam.examDescription || '',
        department: exam.department || '',
        duration: exam.duration || 60,
        passing_score: exam.passing_score || 70,
        questions: exam.questions || []
      }, 'local');
      
      // Update status badge
      const statusBadge = document.getElementById('previewStatusBadge');
      statusBadge.textContent = formatExamStatusLabel(status);
      statusBadge.className = 'badge status-' + status;
      
      // Set CRUD operations based on status
      setupCrudOperations(status);
      
      view_document_modal.showModal();
    }

    function safeJsonParse(str) {
      try {
        return JSON.parse(str);
      } catch (e) {
        return null;
      }
    }

    function normalizeQuestionForPreview(q, source) {
      const questionType = (q.question_type || q.type || 'multiple').toLowerCase();
      let options = [];

      const rawOptions = q.options ?? q.option ?? '';
      if (typeof rawOptions === 'string' && rawOptions.trim()) {
        const parsed = safeJsonParse(rawOptions);
        if (Array.isArray(parsed)) {
          options = parsed.map(o => (typeof o === 'string' ? o : (o.value ?? o.text ?? ''))).filter(Boolean);
        }
      } else if (Array.isArray(rawOptions)) {
        options = rawOptions.map(o => (typeof o === 'string' ? o : (o.value ?? o.text ?? ''))).filter(Boolean);
      }

      return {
        question_type: questionType,
        question_text: q.question_text || q.question || '',
        points: Number(q.points || 1),
        options
      };
    }

    function renderStudentExamPreviewToDocumentModal(examData, source) {
      const container = document.getElementById('documentPreviewContent');
      if (!container) return;

      const title = examData.title || 'Untitled Examination';
      const description = examData.description || '';
      const questions = Array.isArray(examData.questions) ? examData.questions : [];

      let html = `
        <div class="preview-header bg-primary text-white p-6 rounded-t-2xl mb-6">
          <h1 class="text-3xl font-bold mb-2">${title}</h1>
          <p class="text-primary-content opacity-90">${description || 'No description provided'}</p>
        </div>

        <div class="preview-instructions bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h3 class="font-semibold text-blue-800 mb-2">Instructions:</h3>
          <ul class="text-blue-700 text-sm space-y-1">
            <li>Read each question carefully before answering</li>
            <li>Select the best answer for each question</li>
            <li>You cannot go back to previous questions once answered</li>
            <li>Ensure all answers are final before submitting</li>
          </ul>
        </div>
      `;

      if (!questions.length) {
        html += `
          <div class="text-center py-8">
            <i class="fas fa-question-circle text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500 text-lg">No questions added yet</p>
            <p class="text-gray-400">Add questions to see the preview</p>
          </div>
        `;
        container.innerHTML = html;
        return;
      }

      questions.forEach((rawQ, idx) => {
        const q = normalizeQuestionForPreview(rawQ, source);

        html += `
          <div class="preview-question">
            <div class="flex justify-between items-start mb-4">
              <div class="flex items-center">
                <span class="question-number font-bold text-lg text-primary mr-3">Q${idx + 1}</span>
              </div>
              <span class="preview-points">${q.points} point${q.points > 1 ? 's' : ''}</span>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-4">${q.question_text || `Question ${idx + 1}`}</h3>
        `;

        if (q.question_type === 'multiple' || q.question_type === 'truefalse') {
          const inputType = q.question_type === 'multiple' ? 'checkbox' : 'radio';
          const options = q.question_type === 'truefalse' ? ['True', 'False'] : (q.options.length ? q.options : []);

          html += `<div class="preview-options space-y-2">`;

          if (!options.length) {
            html += `
              <div class="preview-option">
                <span class="text-gray-500">No options available for this question.</span>
              </div>
            `;
          } else {
            options.forEach((opt, optIndex) => {
              const inputId = `preview_${idx}_${optIndex}`;
              html += `
                <label class="preview-option" for="${inputId}">
                  <input type="${inputType}" id="${inputId}" name="preview_q_${idx}" value="${opt}">
                  <span class="flex-1">${opt}</span>
                </label>
              `;
            });
          }

          html += `</div>`;
        } else {
          html += `
            <div class="preview-answer">
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-semibold">Your Answer:</span>
                </label>
                <input type="text" class="input input-bordered w-full" placeholder="Type your answer here...">
              </div>
            </div>
          `;
        }

        html += `</div>`;
      });

      container.innerHTML = html;
    }

    // NEW: Function to render examination preview
    function renderExaminationPreview(examId, examTitle) {
      // Sample examination data - in real app, fetch from server
      const sampleExam = {
        title: examTitle || "Employee Policy Examination",
        department: "Human Resources",
        duration: 30,
        passingScore: 70,
        questionCount: 10,
        description: "This examination tests knowledge of company policies and procedures.",
        questions: [
          {
            id: 1,
            question: "What is the company's policy regarding remote work?",
            options: [
              { id: 'A', text: "Remote work is not allowed", correct: false },
              { id: 'B', text: "Remote work is allowed only on Fridays", correct: false },
              { id: 'C', text: "Remote work can be arranged with manager approval", correct: true },
              { id: 'D', text: "Remote work is mandatory twice a week", correct: false }
            ]
          },
          {
            id: 2,
            question: "How should employees report safety concerns?",
            options: [
              { id: 'A', text: "Directly to their supervisor", correct: false },
              { id: 'B', text: "Through the online safety portal", correct: true },
              { id: 'C', text: "Via email to HR", correct: false },
              { id: 'D', text: "All of the above", correct: true }
            ]
          },
          {
            id: 3,
            question: "What is the deadline for submitting expense reports?",
            options: [
              { id: 'A', text: "Within 7 days of incurring the expense", correct: false },
              { id: 'B', text: "By the 15th of each month", correct: false },
              { id: 'C', text: "Within 30 days of the expense date", correct: true },
              { id: 'D', text: "There is no deadline", correct: false }
            ]
          }
        ]
      };

      renderStudentExamPreviewToDocumentModal(sampleExam, 'db');
    }

    // NEW: Function to view examination preview (for database exams)
    function viewExaminationPreview(examId, examTitle) {
      // Show loading state
      const container = document.getElementById('documentPreviewContent');
      if (container) {
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-600">Loading examination preview...</p>
          </div>
        `;
      }
      
      fetch(`fetch_exam_data.php?exam_id=${encodeURIComponent(examId)}`)
        .then(r => r.json())
        .then(exam => {
          if (!exam || exam.error) {
            throw new Error(exam?.error || 'Failed to load examination');
          }

          document.getElementById('previewExamTitle').textContent = exam.title || 'Untitled Examination';
          document.getElementById('previewDepartment').textContent = exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General';
          document.getElementById('previewQuestionCount').textContent = `${(exam.questions || []).length} Questions`;
          document.getElementById('previewDuration').textContent = `${exam.duration || 60} minutes`;
          document.getElementById('previewPassingScore').textContent = `${exam.passing_score || 70}%`;
          document.getElementById('previewDescription').textContent = exam.description || 'No description provided.';

          renderStudentExamPreviewToDocumentModal(exam, 'db');
        })
        .catch(err => {
          Swal.fire({
            title: 'Error',
            text: err?.message || 'Failed to load examination preview',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
        });
    }

    // NEW: Function to view localStorage examination preview
    function viewLocalStoragePreview(id, title) {
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
      
      // Show loading state
      const container = document.getElementById('documentPreviewContent');
      if (container) {
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-600">Loading examination preview...</p>
          </div>
        `;
      }
      
      // Render the preview
      setTimeout(() => {
        renderStudentExamPreviewToDocumentModal(exam, 'local');
      }, 500);
    }

    function safeJsonParse(str) {
      try {
        return JSON.parse(str);
      } catch (e) {
        return null;
      }
    }

    function normalizeQuestionForPreview(q, source) {
      const questionType = (q.question_type || q.type || 'multiple').toLowerCase();
      let options = [];

      const rawOptions = q.options ?? q.option ?? '';
      if (typeof rawOptions === 'string' && rawOptions.trim()) {
        const parsed = safeJsonParse(rawOptions);
        if (Array.isArray(parsed)) {
          options = parsed.map(o => (typeof o === 'string' ? o : (o.value ?? o.text ?? ''))).filter(Boolean);
        }
      } else if (Array.isArray(rawOptions)) {
        options = rawOptions.map(o => (typeof o === 'string' ? o : (o.value ?? o.text ?? ''))).filter(Boolean);
      }

      return {
        question_type: questionType,
        question_text: q.question_text || q.question || '',
        points: Number(q.points || 1),
        options
      };
    }

    function renderStudentExamPreviewToDocumentModal(examData, source) {
      const container = document.getElementById('documentPreviewContent');
      if (!container) return;

      const title = examData.title || 'Untitled Examination';
      const description = examData.description || '';
      const questions = Array.isArray(examData.questions) ? examData.questions : [];

      let html = `
        <div class="preview-header bg-primary text-white p-6 rounded-t-2xl mb-6">
          <h1 class="text-3xl font-bold mb-2">${title}</h1>
          <p class="text-primary-content opacity-90">${description || 'No description provided'}</p>
        </div>

        <div class="preview-instructions bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h3 class="font-semibold text-blue-800 mb-2">Instructions:</h3>
          <ul class="text-blue-700 text-sm space-y-1">
            <li>Read each question carefully before answering</li>
            <li>Select the best answer for each question</li>
            <li>You cannot go back to previous questions once answered</li>
            <li>Ensure all answers are final before submitting</li>
          </ul>
        </div>
      `;

      if (!questions.length) {
        html += `
          <div class="text-center py-8">
            <i class="fas fa-question-circle text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500 text-lg">No questions added yet</p>
            <p class="text-gray-400">Add questions to see the preview</p>
          </div>
        `;
        container.innerHTML = html;
        return;
      }

      questions.forEach((rawQ, idx) => {
        const q = normalizeQuestionForPreview(rawQ, source);

        html += `
          <div class="preview-question">
            <div class="flex justify-between items-start mb-4">
              <div class="flex items-center">
                <span class="question-number font-bold text-lg text-primary mr-3">Q${idx + 1}</span>
              </div>
              <span class="preview-points">${q.points} point${q.points > 1 ? 's' : ''}</span>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-4">${q.question_text || `Question ${idx + 1}`}</h3>
        `;

        if (q.question_type === 'multiple' || q.question_type === 'truefalse') {
          const inputType = q.question_type === 'multiple' ? 'checkbox' : 'radio';
          const options = q.question_type === 'truefalse' ? ['True', 'False'] : (q.options.length ? q.options : []);

          html += `<div class="preview-options space-y-2">`;

          if (!options.length) {
            html += `
              <div class="preview-option">
                <span class="text-gray-500">No options available for this question.</span>
              </div>
            `;
          } else {
            options.forEach((opt, optIndex) => {
              const inputId = `preview_${idx}_${optIndex}`;
              html += `
                <label class="preview-option" for="${inputId}">
                  <input type="${inputType}" id="${inputId}" name="preview_q_${idx}" value="${opt}">
                  <span class="flex-1">${opt}</span>
                </label>
              `;
            });
          }

          html += `</div>`;
        } else {
          html += `
            <div class="preview-answer">
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-semibold">Your Answer:</span>
                </label>
                <input type="text" class="input input-bordered w-full" placeholder="Type your answer here...">
              </div>
            </div>
          `;
        }

        html += `</div>`;
      });

      container.innerHTML = html;
    }

    // NEW: Function to render examination preview
    function renderExaminationPreview(examId, examTitle) {
      // Sample examination data - in real app, fetch from server
      const sampleExam = {
        title: examTitle || "Employee Policy Examination",
        department: "Human Resources",
        duration: 30,
        passingScore: 70,
        questionCount: 10,
        description: "This examination tests knowledge of company policies and procedures.",
        questions: [
          {
            id: 1,
            question: "What is the company's policy regarding remote work?",
            options: [
              { id: 'A', text: "Remote work is not allowed", correct: false },
              { id: 'B', text: "Remote work is allowed only on Fridays", correct: false },
              { id: 'C', text: "Remote work can be arranged with manager approval", correct: true },
              { id: 'D', text: "Remote work is mandatory twice a week", correct: false }
            ]
          },
          {
            id: 2,
            question: "How should employees report safety concerns?",
            options: [
              { id: 'A', text: "Directly to their supervisor", correct: false },
              { id: 'B', text: "Through the online safety portal", correct: true },
              { id: 'C', text: "Via email to HR", correct: false },
              { id: 'D', text: "All of the above", correct: true }
            ]
          },
          {
            id: 3,
            question: "What is the deadline for submitting expense reports?",
            options: [
              { id: 'A', text: "Within 7 days of incurring the expense", correct: false },
              { id: 'B', text: "By the 15th of each month", correct: false },
              { id: 'C', text: "Within 30 days of the expense date", correct: true },
              { id: 'D', text: "There is no deadline", correct: false }
            ]
          }
        ]
      };

      renderStudentExamPreviewToDocumentModal(sampleExam, 'db');
    }

    // NEW: Function to view examination preview (for database exams)
    function viewExaminationPreview(examId, examTitle) {
      // Show loading state
      const container = document.getElementById('documentPreviewContent');
      if (container) {
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-600">Loading examination preview...</p>
          </div>
        `;
      }
      
      fetch(`fetch_exam_data.php?exam_id=${encodeURIComponent(examId)}`)
        .then(r => r.json())
        .then(exam => {
          if (!exam || exam.error) {
            throw new Error(exam?.error || 'Failed to load examination');
          }

          document.getElementById('previewExamTitle').textContent = exam.title || 'Untitled Examination';
          document.getElementById('previewDepartment').textContent = exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General';
          document.getElementById('previewQuestionCount').textContent = `${(exam.questions || []).length} Questions`;
          document.getElementById('previewDuration').textContent = `${exam.duration || 60} minutes`;
          document.getElementById('previewPassingScore').textContent = `${exam.passing_score || 70}%`;
          document.getElementById('previewDescription').textContent = exam.description || 'No description provided.';

          renderStudentExamPreviewToDocumentModal(exam, 'db');
        })
        .catch(err => {
          Swal.fire({
            title: 'Error',
            text: err?.message || 'Failed to load examination preview',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
        });
    }

    // NEW: Function to view localStorage examination preview
    function viewLocalStoragePreview(id, title) {
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
      
      // Show loading state
      const container = document.getElementById('documentPreviewContent');
      if (container) {
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-600">Loading examination preview...</p>
          </div>
        `;
      }
      
      // Render the preview
      setTimeout(() => {
        renderStudentExamPreviewToDocumentModal(exam, 'local');
      }, 500);
    }

    function filterModules() {
      const departmentFilter = document.getElementById('modalDepartmentFilter').value;
      const roleFilter = document.getElementById('modalRoleFilter').value;
      
      const modules = document.querySelectorAll('.module-item');
      
      modules.forEach(module => {
        let show = true;
        
        // Department filter
        if (departmentFilter && module.dataset.department !== departmentFilter) {
          show = false;
        }
        
        // Role filter
        if (roleFilter && module.dataset.role !== roleFilter) {
          show = false;
        }
        
        module.style.display = show ? 'block' : 'none';
      });
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
      currentExamSource = 'db';

      // Loading state
      const container = document.getElementById('documentPreviewContent');
      if (container) {
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-600">Loading examination preview...</p>
          </div>
        `;
      }
      
      fetch(`fetch_exam_data.php?exam_id=${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(exam => {
          if (!exam || exam.error) {
            throw new Error(exam?.error || 'Failed to load examination');
          }

          document.getElementById('previewExamTitle').textContent = exam.title || 'Untitled Examination';
          document.getElementById('previewDepartment').textContent = exam.department ? exam.department.charAt(0).toUpperCase() + exam.department.slice(1) : 'General';
          document.getElementById('previewQuestionCount').textContent = `${(exam.questions || []).length} Questions`;
          document.getElementById('previewDuration').textContent = `${exam.duration || 60} minutes`;
          document.getElementById('previewPassingScore').textContent = `${exam.passing_score || 70}%`;
          document.getElementById('previewDescription').textContent = exam.description || 'No description provided.';

          renderStudentExamPreviewToDocumentModal(exam, 'db');
        })
        .catch(err => {
          Swal.fire({
            title: 'Error',
            text: err?.message || 'Failed to load examination preview',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
        });
      
      // Update status badge
      const statusBadge = document.getElementById('previewStatusBadge');
      statusBadge.textContent = formatExamStatusLabel(status);
      statusBadge.className = 'badge status-' + status;
      
      // Set CRUD operations based on status
      setupCrudOperations(status);
      
      view_document_modal.showModal();
    }
    
    // Setup CRUD operations based on status
    function setupCrudOperations(status) {
      const crudContainer = document.getElementById('crudOperations');
      let html = '';
      
      switch(status) {
        case 'pending':
          html += `
            <button class="btn btn-success" style="min-width: 140px" onclick="editExam('${currentExamId}')">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
            <button class="btn btn-danger" style="min-width: 140px" onclick="cancelDbExam('${currentExamId}')">
              <i class="fas fa-ban mr-1"></i> Cancel
            </button>
          `;
          break;

        case 'cancelled':
          html += `
            <button class="btn btn-success" style="min-width: 140px" onclick="editExam('${currentExamId}')">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
            <button class="btn btn-danger" style="min-width: 140px" onclick="deleteDbExam('${currentExamId}')">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
          `;
          break;

        case 'approved':
          html += `
            <button class="btn btn-success" style="min-width: 140px" onclick="postExam('${currentExamId}')">
              <i class="fas fa-share-square mr-1"></i> Post
            </button>
            <button class="btn btn-warning" style="min-width: 140px" onclick="holdExam('${currentExamId}')">
              <i class="fas fa-pause-circle mr-1"></i> Hold
            </button>
          `;
          break;
          
        case 'hold':
          html += `
            <button class="btn btn-success" style="min-width: 140px" onclick="postExam('${currentExamId}')">
              <i class="fas fa-share-square mr-1"></i> Post
            </button>
          `;
          break;
          
        case 'rejected':
          html += `
            <button class="btn btn-danger" style="min-width: 140px" onclick="deleteLocalStorageExam('${currentExamId}')">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
          `;
          break;
          
        case 'compliance':
          html += `
            <button class="btn btn-custom" style="min-width: 140px" onclick="showComplianceReason('${currentExamId}')">
              <i class="fas fa-comment-alt mr-1"></i> Reason Why
            </button>
            <button class="btn btn-danger" style="min-width: 140px" onclick="deleteLocalStorageExam('${currentExamId}')">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
            <button class="btn btn-success" style="min-width: 140px" onclick="editExam('${currentExamId}')">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
          `;
          break;
          
        default:
          html += '<p>No actions available for this status.</p>';
      }
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
      window.location.href = '../hr_manager/review_dashboard.php?id=' + id;
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
          if (currentExamSource === 'db') {
            const fd = new FormData();
            fd.append('original_exam_id', id);
            fd.append('action', 'post');

            fetch('post_examination.php', { method: 'POST', body: fd })
              .then(r => r.json())
              .then(data => {
                if (!data?.success) {
                  throw new Error(data?.message || 'Failed to post examination');
                }

                Swal.fire({
                  title: 'Posted!',
                  text: 'Examination has been posted successfully.',
                  icon: 'success',
                  confirmButtonText: 'Go to Posted Examinations',
                  confirmButtonColor: '#28a745'
                }).then(() => {
                  window.location.href = 'posted_examinations.php';
                });
              })
              .catch(err => {
                Swal.fire({
                  title: 'Error',
                  text: err?.message || 'Failed to post examination',
                  icon: 'error',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#3b82f6'
                });
              });
            return;
          }

          let examinations = JSON.parse(localStorage.getItem('examinations')) || [];
          const examIndex = examinations.findIndex(exam => exam.id === id);
          if (examIndex !== -1) {
            examinations[examIndex].status = 'posted';
            localStorage.setItem('examinations', JSON.stringify(examinations));
          }

          Swal.fire('Posted!', 'Examination has been posted successfully.', 'success').then(() => {
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
      window.location.href = 'create_examination.php?exam_id=' + encodeURIComponent(id);
    }
    
    function showComplianceReason(id) {
      document.getElementById('complianceRemarksText').textContent = 
        'The examination needs updated questions according to the latest industry standards. Please ensure all references to company policies are from the 2023 revision.';
      
      compliance_reason_modal.showModal();
    }
    
    function saveComplianceRemarks() {
      const additionalRemarks = document.getElementById('additionalRemarks').value;
      Swal.fire({
        title: 'Saved',
        text: 'Compliance remarks saved for examination ID: ' + currentExamId,
        icon: 'success',
        confirmButtonText: 'OK',
        confirmButtonColor: '#3b82f6'
      });
      compliance_reason_modal.close();
    }

    // Create Examination Modal Functionality
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
      filterModules();
      
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
        populateModalRoleOptions(this.value, '');
        filterModules();
        saveModalState();
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
            const countValue = document.getElementById('questionCount')?.value || '10';

            Swal.fire({
              title: 'Generating Questions...',
              text: 'Please wait while we generate an examination from your module.',
              icon: 'info',
              showConfirmButton: false,
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
                window.location.href = `convert_module_to_exam.php?module_id=${encodeURIComponent(selectedModuleId)}&question_count=${encodeURIComponent(countValue)}`;
              }
            });
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
        populateModalRoleOptions('', '');
        
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
    <script>
    lucide.createIcons();
  </script>
   <!-- Include JavaScript file -->
  <script src="../../JS/learning_modules_repository.js"></script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>

