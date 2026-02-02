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

// Handle POST request to update module status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id']) && isset($_POST['new_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $module_id = $_POST['module_id'];
    $new_status = $_POST['new_status'];
    $remarks = $_POST['remarks'] ?? '';
    
    $update_sql = "UPDATE learning_modules SET status = ?, remarks = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $new_status, $remarks, $module_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Module status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating module status']);
    }
    exit;
}

// Handle GET request to fetch module data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['module_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $module_id = $_GET['module_id'];
    
    $sql = "SELECT * FROM learning_modules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $module = $result->fetch_assoc();
        echo json_encode($module);
    } else {
        echo json_encode(['error' => 'Module not found']);
    }
    exit;
}

// Fetch only posted modules from database
$posted_modules = [];
$sql = "SELECT * FROM learning_modules WHERE status = 'posted' ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $posted_modules[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Modules</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
   <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="../../CSS/learning_module_repository.css">
  <style>
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
    
    .btn-border:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
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
    
    /* Status badge styles */
    .status-posted {
      background-color: #dbeafe;
      color: #1e40af;
      border: 1px solid #93c5fd;
    }
    
    /* Modal styles */
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
    
    .document-preview {
      height: 300px;
      overflow-y: auto;
      background-color: white;
      border: 1px solid #e2e8f0;
      border-radius: 0.375rem;
      padding: 1rem;
    }
    
    /* Styles for displaying the actual content properly */
    .document-preview * {
      max-width: 100%;
    }
    
    .document-preview img {
      max-width: 100%;
      height: auto;
    }
    
    .document-preview table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .document-preview table, 
    .document-preview th, 
    .document-preview td {
      border: 1px solid #ddd;
    }
    
    .document-preview th, 
    .document-preview td {
      padding: 8px;
      text-align: left;
    }
    
    .document-preview ul, 
    .document-preview ol {
      padding-left: 1.5rem;
    }
    
    .document-preview li {
      margin-bottom: 0.25rem;
    }
    
    .reason-section {
      background-color: #fef3c7;
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid #fde68a;
    }
    
    .reason-title {
      font-weight: 600;
      color: #92400e;
      margin-bottom: 0.5rem;
    }
    
    .reason-text {
      color: #92400e;
    }
    
    .module-card {
      transition: all 0.2s ease;
      border: 1px solid #e5e7eb;
      background: white;
    }
    
    .module-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
    
    .filter-section {
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .stat-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
      text-align: left;
    }

    .stat-card .stat-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .stat-card .stat-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      color: #4b5563;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #1f2937;
      margin-bottom: 0.25rem;
    }
    
    .stat-label {
      font-size: 0.875rem;
      color: #6b7280;
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

    /* Action buttons styling - Colors removed but design preserved */
    .action-buttons {
      display: flex;
      gap: 12px;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #e5e7eb;
    }

    .action-btn {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid #d1d5db;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background-color: transparent;
      color: #374151;
    }

    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      background-color: #f9fafb;
      border-color: #9ca3af;
    }

    /* SweetAlert buttons - Gray for cancel, Blue for confirm */
    .swal2-cancel {
      background-color: #6b7280 !important;
      border: 1px solid #6b7280 !important;
      color: white !important;
      transition: all 0.2s ease-in-out !important;
    }

    .swal2-cancel:hover {
      background-color: #4b5563 !important;
      border-color: #4b5563 !important;
    }

    .swal2-confirm {
      background-color: #3b82f6 !important;
      border: 1px solid #3b82f6 !important;
      color: white !important;
      transition: all 0.2s ease-in-out !important;
    }

    .swal2-confirm:hover {
      background-color: #2563eb !important;
      border-color: #2563eb !important;
    }

    .swal2-confirm:focus, .swal2-cancel:focus {
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
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
          <!-- Posted Modules Section -->
          <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-2xl font-bold mb-2">Posted Learning Modules</h1>
                <p class="text-gray-600">All currently active and posted learning materials available to employees</p>
              </div>
              <div class="flex gap-2">
  
                
              </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-grid mb-6">
              <div class="stat-card">
                <div class="stat-inner">
                  <div>
                    <div class="stat-label">Total Posted Modules</div>
                    <div class="stat-number"><?php echo count($posted_modules); ?></div>
                  </div>
                  <div class="stat-icon">
                    <i data-lucide="layers" class="w-7 h-7"></i>
                  </div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-inner">
                  <div>
                    <div class="stat-label">Posted This Month</div>
                    <div class="stat-number">
                      <?php 
                        $current_month = date('Y-m');
                        $monthly_count = 0;
                        foreach ($posted_modules as $module) {
                          if (date('Y-m', strtotime($module['created_at'])) === $current_month) {
                            $monthly_count++;
                          }
                        }
                        echo $monthly_count;
                      ?>
                    </div>
                  </div>
                  <div class="stat-icon">
                    <i data-lucide="calendar" class="w-7 h-7"></i>
                  </div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-inner">
                  <div>
                    <div class="stat-label">Active Departments</div>
                    <div class="stat-number">
                      <?php
                        $departments = [];
                        foreach ($posted_modules as $module) {
                          if (!in_array($module['department'], $departments)) {
                            $departments[] = $module['department'];
                          }
                        }
                        echo count($departments);
                      ?>
                    </div>
                  </div>
                  <div class="stat-icon">
                    <i data-lucide="building-2" class="w-7 h-7"></i>
                  </div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-inner">
                  <div>
                    <div class="stat-label">Latest Post</div>
                    <div class="stat-number">
                      <?php 
                        if (!empty($posted_modules)) {
                          echo date('M j', strtotime($posted_modules[0]['created_at']));
                        } else {
                          echo 'N/A';
                        }
                      ?>
                    </div>
                  </div>
                  <div class="stat-icon">
                    <i data-lucide="clock" class="w-7 h-7"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
              <div class="flex flex-wrap gap-4">
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Department</span>
                  </label>
                  <select class="select select-bordered w-48" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <?php
                    $departments = array_unique(array_column($posted_modules, 'department'));
                    foreach ($departments as $dept) {
                        $display_name = ucwords(str_replace('-', ' ', $dept));
                        echo "<option value='$dept'>$display_name</option>";
                    }
                    ?>
                  </select>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-border" onclick="applyFilters()">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                  </button>
                </div>
                
                <div class="form-control self-end">
                  <button class="btn btn-border" onclick="clearFilters()">
                    <i class="fas fa-times mr-2"></i>Clear
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Posted Modules Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="moduleCards">
              <?php if (empty($posted_modules)): ?>
                <div class="col-span-full text-center py-8">
                  <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                  <p class="text-gray-500">No posted learning modules found.</p>
                </div>
              <?php else: ?>
                <?php foreach ($posted_modules as $module): ?>
                  <div class="card bg-base-100 shadow-md module-card" 
                       data-department="<?php echo $module['department']; ?>" 
                       data-id="<?php echo $module['id']; ?>"
                       data-content="<?php echo htmlspecialchars($module['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="card-body">
                      <div class="flex justify-between items-start">
                        <h3 class="card-title"><?php echo htmlspecialchars($module['title']); ?></h3>
                        <div class="badge status-posted">
                          Posted
                        </div>
                      </div>
                      <div class="flex flex-wrap gap-2 my-2">
                        <div class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $module['department'])); ?></div>
                        <div class="badge badge-outline"><?php echo htmlspecialchars($module['roles']); ?></div>
                      </div>
                      <p class="text-sm text-gray-500">Date Posted: <?php echo date('Y-m-d', strtotime($module['created_at'])); ?></p>
                      <p class="text-sm text-gray-500">Topic: <?php echo htmlspecialchars($module['topic']); ?></p>
                      <?php if (!empty($module['remarks'])): ?>
                        <p class="text-sm text-gray-500">Notes: <?php echo htmlspecialchars($module['remarks']); ?></p>
                      <?php endif; ?>

                      <div class="card-actions justify-end mt-4">
                        <button class="btn btn-sm btn-border" type="button" onclick="viewPostedModule(<?php echo (int)$module['id']; ?>)">
                          <i class="fas fa-eye mr-1"></i> View
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

  <!-- POSTED MODULE MODAL -->
  <dialog id="posted_module_modal" class="modal">
    <div class="modal-box max-w-5xl">
      <h3 class="font-bold text-lg mb-4" id="posted-title">Module Title</h3>
      
      <div class="modal-grid">
        <!-- Info Section -->
        <div class="info-section">
          <h4 class="font-semibold text-lg mb-4">Info</h4>
          <div class="info-item">
            <span class="info-label">Title:</span>
            <span class="info-value" id="posted-exam-title">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Topic:</span>
            <span class="info-value" id="posted-topic">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Department:</span>
            <span class="info-value" id="posted-department">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role:</span>
            <span class="info-value" id="posted-role">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value" id="posted-status">Posted</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date Created:</span>
            <span class="info-value" id="posted-date">-</span>
          </div>
        </div>
        
        <!-- Document Section -->
        <div>
          <div class="document-section">
            <h4 class="font-semibold text-lg mb-4">DOCUMENT PREVIEW</h4>
            <div class="document-preview" id="posted-document-preview">
              <!-- ACTUAL document content will be displayed here -->
            </div>
            <div class="mt-4 flex gap-2">
              <button class="btn btn-border flex-1" id="posted-view-full-content">View Full Content</button>
              <button class="btn btn-border flex-1" id="posted-download-file">
                <i class="fas fa-download mr-2"></i>Download File
              </button>
            </div>
          </div>
          
          <!-- CRUD Actions - Colors removed but design preserved -->
          <div class="action-buttons"></div>
        </div>
      </div>
      
      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
        <button class="btn btn-border" id="posted-hold-btn" type="button">Hold</button>
      </div>
    </div>
  </dialog>

  <!-- FULL CONTENT MODAL -->
  <dialog id="full_content_modal" class="modal modal-middle">
    <div class="modal-box max-w-6xl max-h-[80vh]">
      <h3 class="font-bold text-lg mb-4" id="full-content-title">Full Module Content</h3>
      
      <div class="bg-white border rounded-lg p-6 max-h-[60vh] overflow-y-auto">
        <div id="full-content-display">
          <!-- Full content will be displayed here -->
        </div>
      </div>
      
      <div class="modal-action mt-6">
        <form method="dialog">
          <button class="btn btn-border">Close</button>
        </form>
      </div>
    </div>
  </dialog>

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

    // Module data storage
    let currentModuleId = null;
    let currentModuleData = null;

    // Filter Functions
    function applyFilters() {
      const departmentValue = document.getElementById('departmentFilter').value;
      const cards = document.querySelectorAll('.module-card');
      
      cards.forEach(card => {
        const cardDepartment = card.getAttribute('data-department');
        
        if (departmentValue === 'all' || cardDepartment === departmentValue) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    function clearFilters() {
      document.getElementById('departmentFilter').value = 'all';
      applyFilters();
    }

    // View Posted Module Function - Now fetches from server
    function viewPostedModule(moduleId) {
      console.log('View posted module clicked:', moduleId);
      
      // Set current module ID
      currentModuleId = moduleId;
      
      // Show loading state
      const modal = document.getElementById('posted_module_modal');
      const previewElement = document.getElementById('posted-document-preview');
      previewElement.innerHTML = '<div class="flex justify-center items-center h-full"><div class="loading-spinner"></div><span class="ml-2">Loading content...</span></div>';
      
      // Fetch module data from server
      fetch(`posted_modules.php?module_id=${moduleId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(moduleData => {
          console.log('Module data fetched:', moduleData);
          currentModuleData = moduleData;
          showPostedModal(moduleData);
        })
        .catch(error => {
          console.error('Error fetching module data:', error);
          previewElement.innerHTML = '<p class="text-red-500 italic">Error loading module content. Please try again.</p>';
        });
      
      // Show the modal immediately while content loads
      if (modal) {
        modal.showModal();
      }
    }

    function showPostedModal(moduleData) {
      console.log('Showing posted modal for:', moduleData.title);
      
      // Set modal title
      document.getElementById('posted-title').textContent = moduleData.title;
      
      // Set info section
      document.getElementById('posted-exam-title').textContent = moduleData.title;
      document.getElementById('posted-topic').textContent = moduleData.topic;
      document.getElementById('posted-department').textContent = formatDepartment(moduleData.department);
      document.getElementById('posted-role').textContent = moduleData.roles;
      document.getElementById('posted-status').textContent = 'Posted';
      document.getElementById('posted-date').textContent = moduleData.created_at ? moduleData.created_at.split(' ')[0] : 'N/A';
      
      // Set document preview with ACTUAL content from database
      const previewElement = document.getElementById('posted-document-preview');
      if (moduleData.content && moduleData.content.trim() !== '') {
        previewElement.innerHTML = moduleData.content;
      } else {
        previewElement.innerHTML = '<p class="text-gray-500 italic">No content available for this module.</p>';
      }
      
      // Set up action buttons
      const holdBtn = document.getElementById('posted-hold-btn');
      if (holdBtn) {
        holdBtn.onclick = function() {
          // Close the modal first
          const modal = document.getElementById('posted_module_modal');
          if (modal) {
            modal.close();
          }
          // Then show the hold confirmation with reason input
          showHoldConfirmation();
        };
      }
      
      document.getElementById('posted-download-file').onclick = function() {
        downloadModuleFile(moduleData);
      };
      
      document.getElementById('posted-view-full-content').onclick = function() {
        showFullContentModal(moduleData);
      };
    }

    // SweetAlert for Hold Confirmation - Gray cancel, Blue confirm
    function showHoldConfirmation() {
      Swal.fire({
        title: 'Put Module on Hold?',
        html: `
          <div class="text-left">
            <p class="text-gray-600 mb-3">Hold reason is required. Please provide a reason for putting this module on hold:</p>
            <textarea id="hold-reason" class="w-full border border-gray-300 rounded-md p-3 h-32 text-sm" 
                      placeholder="Enter your reason here... (e.g., Content needs updating, Pending approval, etc.)"></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'PUT ON HOLD',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        showLoaderOnConfirm: true,
        preConfirm: () => {
          const reason = document.getElementById('hold-reason').value;
          if (!reason.trim()) {
            Swal.showValidationMessage('Hold reason is required');
            return false;
          }
          return updateModuleStatus(currentModuleId, 'hold', reason)
            .then(result => {
              if (!result.success) {
                throw new Error(result.message);
              }
              return result;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Success!',
            text: 'Module has been placed on hold successfully!',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          }).then(() => {
            removePostedModuleCard(currentModuleId);
            currentModuleId = null;
            currentModuleData = null;
          });
        }
      }).catch(error => {
        Swal.fire({
          title: 'Error!',
          text: error.message || 'Failed to update module status',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
      });
    }



    // Edit Module Function
    function editModule(moduleId) {
      console.log('Fetching module data for editing:', moduleId);
      
      // Show loading state on the edit button
      const editBtn = event.target;
      const originalText = editBtn.innerHTML;
      editBtn.innerHTML = '<div class="loading-spinner mr-2"></div>Loading...';
      editBtn.disabled = true;
      
      // Fetch module data from server
      fetch(`fetch_module_data.php?module_id=${moduleId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(moduleData => {
          console.log('Module data fetched:', moduleData);
          
          // Store module data in sessionStorage
          sessionStorage.setItem('editModuleData', JSON.stringify(moduleData));
          
          // Redirect to edit page
          window.location.href = `create_learning_modules.php?edit=${moduleId}`;
        })
        .catch(error => {
          console.error('Error fetching module data:', error);
          Swal.fire({
            title: 'Error!',
            text: 'Error fetching module data. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
          
          // Restore button state
          editBtn.innerHTML = originalText;
          editBtn.disabled = false;
        });
    }

    // Download Function
    function downloadModuleFile(moduleData) {
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
      
      // Show success message
      Swal.fire({
        title: 'Download Started!',
        text: 'Your module file is being downloaded.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    }

    // New function to show full content in a modal
    function showFullContentModal(moduleData) {
      console.log('Showing full content modal for:', moduleData.title);
      
      // Set modal title
      document.getElementById('full-content-title').textContent = moduleData.title + ' - Full Content';
      
      // Set full content
      const fullContentDisplay = document.getElementById('full-content-display');
      if (moduleData.content && moduleData.content.trim() !== '') {
        fullContentDisplay.innerHTML = moduleData.content;
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

    // Helper Functions
    function formatDepartment(department) {
      return department.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    function removePostedModuleCard(moduleId) {
      const card = document.querySelector(`.module-card[data-id="${moduleId}"]`);
      if (card) {
        card.remove();
      }

      const container = document.getElementById('moduleCards');
      if (container && container.querySelectorAll('.module-card').length === 0) {
        container.innerHTML = `
          <div class="col-span-full text-center py-8">
            <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No posted learning modules found.</p>
          </div>
        `;
      }
    }

    // AJAX function to update module status
    function updateModuleStatus(moduleId, newStatus, remarks = '') {
      // Create form data
      const formData = new FormData();
      formData.append('module_id', moduleId);
      formData.append('new_status', newStatus);
      formData.append('remarks', remarks);
      
      // Send AJAX request
      return fetch('posted_modules.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          return { success: true, message: data.message };
        } else {
          return { success: false, message: data.message };
        }
      })
      .catch(error => {
        console.error('Error:', error);
        return { success: false, message: 'Error updating module status. Please try again.' };
      });
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Posted Modules page initialized');
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
