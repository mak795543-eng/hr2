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

// Check if employees table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'employees'");
if ($table_check->num_rows == 0) {
    // Create employees table
    $create_table_sql = "CREATE TABLE employees (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql) === TRUE) {
        // Insert sample data
       
        
        $conn->query($sample_data_sql);
    }
}

// **FIXED: Fetch posted examinations from exam_repository table**
$posted_examinations = [];
$sql = "SELECT er.*, 
               COUNT(eq.id) as question_count,
               lm.title as module_title
        FROM exam_repository er
        INNER JOIN examinations e ON e.id = er.original_exam_id
        LEFT JOIN exam_repository_questions eq ON er.id = eq.exam_id
        LEFT JOIN learning_modules lm ON er.module_id = lm.id
        WHERE er.status = 'posted' 
        GROUP BY er.id 
        ORDER BY er.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $posted_examinations[] = $row;
    }
}

// Fetch employee data for assignment
$employees = [];
$employee_sql = "SELECT id, first_name, last_name, department, position FROM employees WHERE status = 'active'";
$employee_result = $conn->query($employee_sql);

if ($employee_result && $employee_result->num_rows > 0) {
    while($row = $employee_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get unique departments for filters from posted exams
$departments = [];
foreach ($posted_examinations as $exam) {
    if (!in_array($exam['department'], $departments) && !empty($exam['department'])) {
        $departments[] = $exam['department'];
    }
}

// **FIXED: Get roles from exam_repository table**
$exam_roles = [];
foreach ($posted_examinations as $exam) {
    if (!empty($exam['roles'])) {
        $exam_roles[$exam['id']] = explode(',', $exam['roles']);
    } else {
        $exam_roles[$exam['id']] = ['All Roles'];
    }
}

$conn->close();

// Define all roles per department
$department_roles = [
    'front-office' => [
        'Front Desk Manager',
        'Receptionist / Front Desk Officer',
        'Guest Service Agent / Concierge',
        'Reservation Agent',
        'Bellhop / Porter',
        'Front Office Supervisor'
    ],
    'housekeeping' => [
        'Executive Housekeeper / Housekeeping Manager',
        'Floor Supervisor',
        'Room Attendant / Housekeeper',
        'Laundry Attendant',
        'Public Area Attendant',
        'Housekeeping Inspector'
    ],
    'food-beverage' => [
        'F&B Manager / Director',
        'Restaurant Manager / Captain',
        'Waiter / Waitress / Server',
        'Bartender',
        'Banquet / Catering Coordinator',
        'F&B Supervisor'
    ],
    'kitchen' => [
        'Executive Chef / Head Chef',
        'Sous Chef',
        'Line Cook / Station Chef',
        'Pastry Chef / Baker',
        'Kitchen Steward / Dishwasher',
        'Commis Chef'
    ],
    'sales-marketing' => [
        'Sales & Marketing Manager',
        'Revenue Manager',
        'Event / Banquet Sales Coordinator',
        'Social Media / Marketing Executive',
        'Sales Executive',
        'Marketing Coordinator'
    ],
    'human-resources' => [
        'HR Manager / Director',
        'Recruitment Officer',
        'Training & Development Specialist',
        'Payroll / HR Assistant',
        'HR Coordinator',
        'Employee Relations Specialist'
    ],
    'finance' => [
        'Finance Manager / Controller',
        'Accountant',
        'Payroll Officer',
        'Cost Controller',
        'Accounts Payable/Receivable Clerk',
        'Financial Analyst'
    ],
    'engineering' => [
        'Chief Engineer / Engineering Manager',
        'Maintenance Technician',
        'Electrician / Plumber',
        'HVAC Technician',
        'Carpenter',
        'Painter'
    ],
    'security' => [
        'Security Manager / Supervisor',
        'Security Guard',
        'CCTV / Surveillance Officer',
        'Security Officer',
        'Surveillance Operator',
        'Access Control Officer'
    ]
];

// Function to get roles for an examination
function getExaminationRoles($exam_id, $exam_roles) {
    return isset($exam_roles[$exam_id]) ? $exam_roles[$exam_id] : ['All Roles'];
}

// Get unique roles for filter dropdown
$all_roles = [];
foreach ($department_roles as $roles_array) {
    $all_roles = array_merge($all_roles, $roles_array);
}
$all_roles = array_unique($all_roles);
sort($all_roles);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Posted Examinations</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../CSS/learning_theme.css">
  <style>
    .examination-card {
      transition: all 0.3s ease;
    }
    .examination-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .btn-custom {
      @apply border border-gray-300 hover:border-gray-400 bg-white text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-success {
      @apply border border-green-600 hover:border-green-700 bg-white text-green-600 font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-warning {
      @apply border border-yellow-600 hover:border-yellow-700 bg-white text-yellow-600 font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .btn-danger {
      @apply border border-red-600 hover:border-red-700 bg-white text-red-600 font-medium py-2 px-4 rounded-lg transition-colors duration-200;
    }
    .table-container {
      max-height: 400px;
      overflow-y: auto;
    }
    .badge-outline {
      @apply border border-gray-300 bg-transparent text-gray-700;
    }
    .badge-role {
      @apply border border-blue-300 bg-blue-50 text-blue-700 text-xs;
    }
    .status-badge {
      @apply px-2 py-1 rounded-full text-xs font-medium border;
    }
    .status-posted { @apply border-purple-300 bg-purple-50 text-purple-700; }
    
    /* Document Preview Modal */
    #document_preview_modal .modal-box {
      max-width: 90vw;
      width: 95%;
      height: 90vh;
      max-height: 90vh;
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
    
    /* SweetAlert2 Styling */
    .swal2-container {
      position: fixed !important;
      inset: 0 !important;
      z-index: 2147483647 !important;
      pointer-events: auto !important;
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
          <!-- Posted Examinations Section -->
          <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-2xl font-bold mb-2">Posted Examinations</h1>
                <p class="text-gray-600">Manage and assign posted examinations to employees</p>
              </div>
              <div class="flex gap-2">
              </div>
            </div>
            
            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
              <div class="flex flex-wrap gap-4 items-end">
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Department</span>
                  </label>
                  <select class="select select-bordered w-48" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <?php foreach($departments as $dept): ?>
                      <option value="<?php echo htmlspecialchars($dept); ?>">
                        <?php echo ucfirst(str_replace('-', ' ', $dept)); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-medium">Role</span>
                  </label>
                  <select class="select select-bordered w-48" id="roleFilter">
                    <option value="all">All Roles</option>
                    <?php foreach($all_roles as $role): ?>
                      <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($role); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="form-control">
                  <button class="btn btn-custom" onclick="applyFilters()">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                  </button>
                </div>
                
                <div class="form-control">
                  <button class="btn btn-custom" onclick="clearFilters()">
                    <i class="fas fa-times mr-2"></i>Clear
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <div class="stat bg-white rounded-lg border border-gray-200 p-6">
                <div class="stat-figure text-gray-600">
                  <i class="fas fa-file-alt text-3xl"></i>
                </div>
                <div class="stat-title text-gray-600">Total Posted</div>
                <div class="stat-value text-gray-800"><?php echo count($posted_examinations); ?></div>
                <div class="stat-desc text-gray-500">Active examinations</div>
              </div>
              
              <div class="stat bg-white rounded-lg border border-gray-200 p-6">
                <div class="stat-figure text-gray-600">
                  <i class="fas fa-users text-3xl"></i>
                </div>
                <div class="stat-title text-gray-600">Available Employees</div>
                <div class="stat-value text-gray-800"><?php echo count($employees); ?></div>
                <div class="stat-desc text-gray-500">For assignment</div>
              </div>
              
              <div class="stat bg-white rounded-lg border border-gray-200 p-6">
                <div class="stat-figure text-gray-600">
                  <i class="fas fa-calendar-check text-3xl"></i>
                </div>
                <div class="stat-title text-gray-600">This Month</div>
                <div class="stat-value text-gray-800">
                  <?php 
                    $current_month = date('Y-m');
                    $month_count = 0;
                    foreach($posted_examinations as $exam) {
                      if (date('Y-m', strtotime($exam['created_at'])) === $current_month) {
                        $month_count++;
                      }
                    }
                    echo $month_count;
                  ?>
                </div>
                <div class="stat-desc text-gray-500">New postings</div>
              </div>
            </div>
            
            <!-- Examinations Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="examinationCards">
                <?php if (empty($posted_examinations)): ?>
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500">No posted examinations found.</p>
                        <p class="text-sm text-gray-400 mt-2">Post examinations from the Examination Repository to make them available here</p>
                        <div class="mt-4">
                            <button class="btn btn-custom" onclick="window.location.href='examination_repository.php'">
                                <i class="fas fa-eye mr-2"></i>Go to Repository
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($posted_examinations as $exam): 
                        $exam_roles_list = getExaminationRoles($exam['id'], $exam_roles);
                        $roles_string = implode(',', $exam_roles_list);
                    ?>
                        <div class="card bg-base-100 border border-gray-200 examination-card" 
                             data-id="<?php echo $exam['id']; ?>"
                             data-department="<?php echo $exam['department']; ?>"
                             data-roles="<?php echo htmlspecialchars($roles_string); ?>">
                            <div class="card-body">
                                <div class="flex justify-between items-start">
                                    <h3 class="card-title text-gray-800"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                    <div class="status-badge status-posted">
                                        Posted
                                    </div>
                                </div>
                                
                                <!-- Department and Role Badges -->
                                <div class="flex flex-wrap gap-2 my-2">
                                    <div class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></div>
                                    <div class="badge badge-outline"><?php echo $exam['question_count'] ?? 0; ?> Questions</div>
                                    <?php if (!empty($exam['module_title'])): ?>
                                        <div class="badge badge-outline">Module: <?php echo htmlspecialchars(substr($exam['module_title'], 0, 15)) . (strlen($exam['module_title']) > 15 ? '...' : ''); ?></div>
                                    <?php endif; ?>
                                    <?php foreach($exam_roles_list as $role): ?>
                                        <div class="badge badge-role" title="Target Role"><?php echo htmlspecialchars($role); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <p class="text-sm text-gray-500">Created: <?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></p>
                                <p class="text-sm text-gray-500">Duration: <?php echo htmlspecialchars($exam['duration']); ?> minutes</p>
                                <p class="text-sm text-gray-500">Passing Score: <?php echo htmlspecialchars($exam['passing_score']); ?>%</p>
                                
                                <div class="card-actions justify-end mt-4">
                                    <button class="btn btn-sm btn-custom" onclick="viewDocument(<?php echo $exam['id']; ?>, '<?php echo htmlspecialchars($exam['title']); ?>')">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="assignExam(<?php echo $exam['id']; ?>, '<?php echo htmlspecialchars($exam['title']); ?>')">
                                        <i class="fas fa-user-plus mr-1"></i> Assign
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

  <!-- Document Preview Modal -->
  <dialog id="document_preview_modal" class="modal">
    <div class="modal-box max-w-6xl max-h-[90vh] overflow-hidden flex flex-col" id="document_preview_modal_box">
      <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-2xl">Examination Preview</h3>
        <button class="btn btn-circle btn-ghost btn-sm" id="closePreviewModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto pr-4">
        <div id="documentPreviewContent">
          <!-- Preview content will be inserted here -->
        </div>
      </div>
      
      <!-- Actions Section -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex flex-wrap gap-2 justify-end mb-4">
          <button class="btn btn-success" onclick="assignExam(currentExamId, currentExamTitle)">
            <i class="fas fa-user-plus mr-1"></i> Assign to Employees
          </button>
          <button class="btn btn-warning" onclick="holdExam(currentExamId)">
            <i class="fas fa-pause-circle mr-1"></i> Put on Hold
          </button>
          <button class="btn btn-custom" onclick="viewResults(currentExamId)">
            <i class="fas fa-chart-bar mr-1"></i> View Results
          </button>
          <button class="btn btn-custom" onclick="printDocument()">
            <i class="fas fa-print mr-1"></i> Print
          </button>
        </div>
      </div>
      
      <div class="modal-action mt-4">
        <button class="btn btn-custom" id="closeDocumentModalBtn">Close</button>
      </div>
    </div>
  </dialog>

  <!-- Assign Exam Modal -->
  <dialog id="assign_exam_modal" class="modal modal-middle">
    <div class="modal-box max-w-4xl">
      <h3 class="font-bold text-lg mb-4">Assign Examination</h3>
      
      <div class="mb-6">
        <h4 class="font-semibold mb-2" id="assignExamTitle">Employee Policy Examination</h4>
        <div class="flex flex-wrap gap-2">
          <div class="badge badge-outline" id="assignDepartment">Human Resources</div>
          <div class="badge badge-outline" id="assignQuestionCount">10 Questions</div>
          <div class="status-badge status-posted">Posted</div>
        </div>
        <div class="mt-2">
          <p class="text-sm text-gray-500">Target Roles:</p>
          <div class="flex flex-wrap gap-1 mt-1" id="assignRoles">
            <div class="badge badge-role">HR Manager</div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="form-control mb-4">
            <label class="label">
              <span class="label-text">Assign For</span>
              <span class="label-text-alt text-red-500">*</span>
            </label>
            <select class="select select-bordered" id="assignAudience">
              <option value="applicant">Applicants</option>
              <option value="employee">Employees</option>
              <option value="specific_employee">Specific Employee</option>
            </select>
          </div>

          <div class="form-control mb-4">
            <label class="label">
              <span class="label-text">Department</span>
              <span class="label-text-alt text-red-500">*</span>
            </label>
            <input type="text" class="input input-bordered" id="assignMapDepartment" readonly>
          </div>

          <div class="form-control mb-4 hidden" id="specificEmployeeContainer">
            <label class="label">
              <span class="label-text">Employee</span>
              <span class="label-text-alt text-red-500">*</span>
            </label>
            <select class="select select-bordered" id="assignSpecificEmployee">
              <option value="" disabled selected>Select department and role first</option>
            </select>
          </div>
        </div>

        <div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Roles / Positions</span>
              <span class="label-text-alt text-red-500">*</span>
            </label>
            <div id="assignRoleOptions" class="border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto space-y-2"></div>
          </div>
        </div>
      </div>
      
      <div class="modal-action">
        <form method="dialog">
          <button class="btn btn-custom">Cancel</button>
        </form>
        <button class="btn btn-success" onclick="confirmAssignment()">
          <i class="fas fa-check mr-1"></i> Assign Examination
        </button>
      </div>
    </div>
  </dialog>

  <!-- Success Modal -->
  <dialog id="success_modal" class="modal">
    <div class="modal-box">
      <div class="flex flex-col items-center text-center">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
          <i class="fas fa-check text-green-600 text-2xl"></i>
        </div>
        <h3 class="font-bold text-lg mb-2">Assignment Successful!</h3>
        <p class="py-4" id="successMessage">The examination has been assigned to selected employees.</p>
      </div>
      <div class="modal-action justify-center">
        <form method="dialog">
          <button class="btn btn-custom">Continue</button>
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

    // Current exam ID and data for operations
    let currentExamId = null;
    let currentExamTitle = null;
    let currentExamData = null;

    const departmentRoles = <?php echo json_encode($department_roles, JSON_UNESCAPED_UNICODE); ?>;

    // Filter functionality
    function applyFilters() {
      const departmentFilter = document.getElementById('departmentFilter').value;
      const roleFilter = document.getElementById('roleFilter').value;
      
      const cards = document.querySelectorAll('.examination-card');
      let visibleCount = 0;
      
      cards.forEach(card => {
        let show = true;
        
        // Department filter
        if (departmentFilter !== 'all' && card.dataset.department !== departmentFilter) {
          show = false;
        }
        
        // Role filter
        if (roleFilter !== 'all') {
          const cardRoles = card.dataset.roles.split(',');
          if (!cardRoles.includes(roleFilter)) {
            show = false;
          }
        }
        
        card.style.display = show ? 'block' : 'none';
        if (show) visibleCount++;
      });
      
      // If no cards visible, show message
      if (visibleCount === 0) {
        const container = document.getElementById('examinationCards');
        const noResults = document.createElement('div');
        noResults.className = 'col-span-full text-center py-8';
        noResults.innerHTML = `
          <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
          <p class="text-gray-500">No examinations match your filters.</p>
          <button class="btn btn-custom mt-4" onclick="clearFilters()">
            <i class="fas fa-times mr-2"></i>Clear Filters
          </button>
        `;
        
        // Check if message already exists
        if (!document.getElementById('noResultsMessage')) {
          noResults.id = 'noResultsMessage';
          container.parentNode.insertBefore(noResults, container.nextSibling);
        }
      } else {
        // Remove no results message if it exists
        const noResultsMsg = document.getElementById('noResultsMessage');
        if (noResultsMsg) {
          noResultsMsg.remove();
        }
      }
    }
    
    function clearFilters() {
      document.getElementById('departmentFilter').value = 'all';
      document.getElementById('roleFilter').value = 'all';
      
      const cards = document.querySelectorAll('.examination-card');
      cards.forEach(card => {
        card.style.display = 'block';
      });
      
      // Remove no results message if it exists
      const noResultsMsg = document.getElementById('noResultsMessage');
      if (noResultsMsg) {
        noResultsMsg.remove();
      }
    }

    // View Document Details
    function viewDocument(id, title) {
      currentExamId = id;
      currentExamTitle = title;
      
      // Show loading state
      const modal = document.getElementById('document_preview_modal');
      const previewElement = document.getElementById('documentPreviewContent');

      if (previewElement) {
        previewElement.innerHTML = `
          <div class="flex items-center justify-center py-12">
            <div class="text-center">
              <div class="loading-spinner mx-auto"></div>
              <p class="text-gray-500 mt-4">Loading examination preview...</p>
            </div>
          </div>
        `;
      }

      if (modal) {
        modal.showModal();
      }

      fetch(`fetch_repository_exam.php?exam_id=${id}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (!data || data.success === false) {
            throw new Error(data?.message || 'Failed to fetch exam');
          }

          const exam = data;
          currentExamData = exam;

          if (previewElement) {
            previewElement.innerHTML = generateExamPreview(exam);
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);

          if (previewElement) {
            previewElement.innerHTML = `
              <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-3xl text-red-500 mb-3"></i>
                <p class="text-red-700 font-medium">Error loading examination</p>
                <p class="text-red-600 text-sm">${error.message || 'Please try again or contact support.'}</p>
              </div>
            `;
          }

          Swal.fire({
            title: 'Error',
            text: error.message || 'Failed to load examination preview.',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        });
    }
    
    // Generate exam preview HTML
    function generateExamPreview(exam) {
      // Fetch questions from original exam
      let questionsHTML = '';
      if (exam.questions && exam.questions.length > 0) {
          exam.questions.forEach((question, index) => {
              questionsHTML += `
                  <div class="preview-question">
                      <div class="flex justify-between items-start mb-4">
                          <div class="flex items-center">
                              <span class="question-number font-bold text-lg text-primary mr-3">Q${index + 1}</span>
                              <span class="question-type-badge badge badge-outline badge-sm">${getQuestionTypeLabel(question.question_type)}</span>
                          </div>
                          <span class="preview-points">${question.points || 1} point${question.points > 1 ? 's' : ''}</span>
                      </div>
                      
                      <h3 class="text-lg font-semibold text-gray-800 mb-4">${question.question_text}</h3>
              `;
              
              if (question.question_type === 'multiple' || question.question_type === 'truefalse') {
                  const options = question.options ? JSON.parse(question.options) : [];
                  const answerKey = question.answer_key ? JSON.parse(question.answer_key) : { correctAnswers: [] };
                  
                  questionsHTML += `<div class="preview-options space-y-2">`;
                  options.forEach((option, optIndex) => {
                      const isCorrect = answerKey.correctAnswers.includes(option);
                      const correctClass = isCorrect ? 'bg-green-50 border-green-200' : '';
                      const letter = String.fromCharCode(65 + optIndex);
                      
                      questionsHTML += `
                          <div class="preview-option ${correctClass}">
                              <div class="flex items-center gap-2">
                                  <span class="w-6 h-6 flex items-center justify-center rounded-full ${isCorrect ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'}">
                                      ${letter}
                                  </span>
                                  <span class="flex-1">${option}</span>
                                  ${isCorrect ? '<span class="text-green-600 font-semibold">âœ“ Correct</span>' : ''}
                              </div>
                          </div>
                      `;
                  });
                  questionsHTML += `</div>`;
              } else if (question.question_type === 'shortanswer' || question.question_type === 'identification') {
                  const answerKey = question.answer_key ? JSON.parse(question.answer_key) : { correctAnswers: [] };
                  const expectedAnswer = answerKey.correctAnswers.length > 0 ? answerKey.correctAnswers[0] : '';
                  
                  questionsHTML += `
                      <div class="preview-answer">
                          <div class="form-control">
                              <label class="label">
                                  <span class="label-text font-semibold">Expected Answer:</span>
                              </label>
                              <div class="bg-green-50 border border-green-200 p-3 rounded-lg">
                                  <p class="text-green-800 font-medium">${expectedAnswer}</p>
                              </div>
                          </div>
                      </div>
                  `;
              }
              
              questionsHTML += `</div>`;
          });
      } else {
          questionsHTML = `
              <div class="text-center py-8">
                  <i class="fas fa-question-circle text-4xl text-gray-400 mb-4"></i>
                  <p class="text-gray-500 text-lg">No questions available</p>
              </div>
          `;
      }
      
      const formattedDate = new Date(exam.created_at).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
      });
      
      return `
          <div class="preview-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 0.5rem 0.5rem 0 0; margin-bottom: 1.5rem;">
              <h1 class="text-3xl font-bold mb-2">${exam.title}</h1>
              <p class="text-primary-content opacity-90">${exam.description || 'No description provided'}</p>
              <div class="flex flex-wrap gap-4 mt-4">
                  <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                      <p class="text-sm opacity-80">Status</p>
                      <p class="text-lg font-semibold">${exam.status.charAt(0).toUpperCase() + exam.status.slice(1)}</p>
                  </div>
                  <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                      <p class="text-sm opacity-80">Duration</p>
                      <p class="text-lg font-semibold">${exam.duration} minutes</p>
                  </div>
                  <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                      <p class="text-sm opacity-80">Passing Score</p>
                      <p class="text-lg font-semibold">${exam.passing_score}%</p>
                  </div>
                  <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                      <p class="text-sm opacity-80">Department</p>
                      <p class="text-lg font-semibold">${exam.department || 'General'}</p>
                  </div>
                  <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                      <p class="text-sm opacity-80">Created</p>
                      <p class="text-lg font-semibold">${formattedDate}</p>
                  </div>
              </div>
          </div>
          
          <div class="preview-instructions" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;">
              <h3 class="font-semibold text-blue-800 mb-2">Instructions:</h3>
              <ul class="text-blue-700 text-sm space-y-1">
                  <li>â€¢ Read each question carefully before answering</li>
                  <li>â€¢ Select the best answer for each question</li>
                  <li>â€¢ You cannot go back to previous questions once answered</li>
                  <li>â€¢ Ensure all answers are final before submitting</li>
                  <li>â€¢ You have ${exam.duration} minutes to complete this examination</li>
                  <li>â€¢ Passing score is ${exam.passing_score}%</li>
              </ul>
          </div>
          
          <div class="preview-questions space-y-6 mt-8">
              ${questionsHTML}
          </div>
      `;
    }
    
    // Get question type label
    function getQuestionTypeLabel(type) {
        const labels = {
            'multiple': 'Multiple Choice',
            'truefalse': 'True/False',
            'shortanswer': 'Short Answer',
            'identification': 'Identification'
        };
        return labels[type] || type;
    }
    
    // Print document
    function printDocument() {
        const printContent = document.getElementById('documentPreviewContent').innerHTML;
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

    function renderAssignRoleOptions(availableRoles, preselectedRoles) {
      const container = document.getElementById('assignRoleOptions');
      if (!container) return;

      const selected = new Set(Array.isArray(preselectedRoles) ? preselectedRoles.map(r => String(r).trim()) : []);
      container.innerHTML = '';

      (Array.isArray(availableRoles) ? availableRoles : []).forEach((role) => {
        const r = String(role || '').trim();
        if (!r) return;

        const label = document.createElement('label');
        label.className = 'flex items-center gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer';

        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'checkbox checkbox-sm assign-role-checkbox';
        cb.value = r;
        cb.checked = selected.has(r);

        const span = document.createElement('span');
        span.className = 'text-sm';
        span.textContent = r;

        label.appendChild(cb);
        label.appendChild(span);
        container.appendChild(label);
      });

      if (container.children.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-500">No roles available for this department.</div>';
      }

      container.querySelectorAll('.assign-role-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
          updateSpecificEmployeeUI();
        });
      });
    }
    
    // Assign Exam to Employees
    function assignExam(id, title) {
      currentExamId = id;
      currentExamTitle = title;
      
      // Close preview modal if open
      document.getElementById('document_preview_modal').close();
      
      // Get the card element to extract exam data
      const card = document.querySelector(`.examination-card[data-id="${id}"]`);
      const roles = card ? card.dataset.roles.split(',') : ['All Roles'];
      const department = card ? card.dataset.department : 'General';
      
      // Set exam details in the modal
      document.getElementById('assignExamTitle').textContent = title;
      document.getElementById('assignDepartment').textContent = ucfirst(department);
      
      // Get question count
      const questionCountElement = card?.querySelector('.badge-outline:nth-child(2)');
      const questionCount = questionCountElement ? questionCountElement.textContent : '10 Questions';
      document.getElementById('assignQuestionCount').textContent = questionCount;
      
      // Update roles in the assignment modal
      const rolesContainer = document.getElementById('assignRoles');
      rolesContainer.innerHTML = '';
      roles.forEach(role => {
        const roleBadge = document.createElement('div');
        roleBadge.className = 'badge badge-role';
        roleBadge.textContent = role;
        rolesContainer.appendChild(roleBadge);
      });

      const departmentInput = document.getElementById('assignMapDepartment');
      if (departmentInput) {
        departmentInput.value = department;
      }

      const availableRoles = Array.isArray(departmentRoles?.[department]) ? departmentRoles[department] : roles;
      renderAssignRoleOptions(availableRoles, roles);
      
      updateSpecificEmployeeUI();
      
      assign_exam_modal.showModal();
    }

    async function fetchEmployeesByDepartmentRole(department, role) {
      const res = await fetch('assign_examination.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          mode: 'list_employees',
          department: department,
          role: role
        })
      });

      const data = await res.json();
      if (!data || data.success === false) {
        throw new Error(data?.message || 'Failed to fetch employees');
      }

      return Array.isArray(data.employees) ? data.employees : [];
    }

    function updateSpecificEmployeeUI() {
      const audience = document.getElementById('assignAudience')?.value || 'employee';
      const container = document.getElementById('specificEmployeeContainer');
      const select = document.getElementById('assignSpecificEmployee');

      if (!container || !select) return;

      if (audience !== 'specific_employee') {
        container.classList.add('hidden');
        select.innerHTML = '<option value="" disabled selected>Select department and role first</option>';
        return;
      }

      container.classList.remove('hidden');

      const department = document.getElementById('assignMapDepartment')?.value || '';
      const roles = Array.from(document.querySelectorAll('.assign-role-checkbox:checked')).map(cb => cb.value);

      if (!department || roles.length !== 1) {
        select.innerHTML = '<option value="" disabled selected>Select department and one role first</option>';
        return;
      }

      const role = roles[0];
      select.innerHTML = '<option value="" disabled selected>Loading employees...</option>';

      fetchEmployeesByDepartmentRole(department, role)
        .then((employees) => {
          if (!employees.length) {
            select.innerHTML = '<option value="" disabled selected>No employees found for this role</option>';
            return;
          }

          select.innerHTML = '<option value="" disabled selected>Select employee</option>';
          employees.forEach((e) => {
            const opt = document.createElement('option');
            opt.value = String(e.id);
            const name = `${e.first_name || ''} ${e.last_name || ''}`.trim();
            opt.textContent = `${name}${e.email ? ` (${e.email})` : ''}`;
            select.appendChild(opt);
          });
        })
        .catch((err) => {
          select.innerHTML = '<option value="" disabled selected>Failed to load employees</option>';
          console.error(err);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      const audienceSelect = document.getElementById('assignAudience');
      if (audienceSelect) {
        audienceSelect.addEventListener('change', () => {
          updateSpecificEmployeeUI();
        });
      }
    });
    
    // Helper function to capitalize first letter
    function ucfirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // Confirm assignment
    function confirmAssignment() {
      const audience = document.getElementById('assignAudience')?.value || 'employee';
      const department = document.getElementById('assignMapDepartment')?.value || '';
      const roles = Array.from(document.querySelectorAll('.assign-role-checkbox:checked')).map(cb => cb.value);
      const selectedEmployeeId = document.getElementById('assignSpecificEmployee')?.value || '';

      if (!department) {
        Swal.fire({
          title: 'Department Required',
          text: 'Department is required to assign the examination.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
        return;
      }

      if (roles.length === 0) {
        Swal.fire({
          title: 'Roles Required',
          text: 'Please select at least one role/position.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6'
        });
        return;
      }

      if (audience === 'specific_employee') {
        if (roles.length !== 1) {
          Swal.fire({
            title: 'Select One Role',
            text: 'Please select exactly one role/position for Specific Employee assignment.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
          return;
        }

        if (!selectedEmployeeId) {
          Swal.fire({
            title: 'Employee Required',
            text: 'Please select an employee.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6'
          });
          return;
        }
      }
      
      // Show confirmation
      Swal.fire({
        title: 'Confirm Assignment',
        html: `Assign <strong>"${currentExamTitle}"</strong> for <strong>${audience === 'applicant' ? 'Applicants' : (audience === 'specific_employee' ? 'Specific Employee' : 'Employees')}</strong> (${roles.length} role(s))?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Assign Now!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Assigning...',
            text: 'Please wait while we assign the examination.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch('assign_examination.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              mode: audience === 'specific_employee' ? 'specific_employee_mapping' : 'role_mapping',
              exam_id: currentExamId,
              audience: audience === 'specific_employee' ? 'employee' : audience,
              department: department,
              roles: roles,
              employee_id: audience === 'specific_employee' ? selectedEmployeeId : null
            })
          })
          .then(r => r.json())
          .then(data => {
            Swal.close();
            if (!data || !data.success) {
              throw new Error(data?.message || 'Failed to assign examination');
            }

            document.getElementById('successMessage').textContent = `"${currentExamTitle}" has been assigned for ${audience === 'applicant' ? 'Applicants' : (audience === 'specific_employee' ? 'Specific Employee' : 'Employees')}.`;
            assign_exam_modal.close();
            success_modal.showModal();
          })
          .catch(err => {
            Swal.fire({
              title: 'Error',
              text: err.message || 'Failed to assign examination',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          });
        }
      });
    }
    
    // Put exam on hold
    function holdExam(id) {
      Swal.fire({
        title: 'Put on Hold?',
        html: `Are you sure you want to put <strong>"${currentExamTitle}"</strong> on hold?<br><br>It will no longer be available for assignment.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Put on Hold',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the examination status.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Send request to update status to hold
          fetch('update_exam_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `exam_id=${id}&new_status=hold`
          })
          .then(response => response.json())
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'On Hold!',
                text: 'Examination has been put on hold.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981'
              }).then(() => {
                document.getElementById('document_preview_modal').close();
                location.reload();
              });
            } else {
              Swal.fire({
                title: 'Error!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'OK'
              });
            }
          })
          .catch(error => {
            Swal.fire({
              title: 'Error!',
              text: 'Failed to update examination status',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          });
        }
      });
    }
    
    // View exam results
    function viewResults(id) {
      // Redirect to results page for this exam
      window.location.href = 'exam_results.php?exam_id=' + id;
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
      // Close document modal handlers
      document.getElementById('closeDocumentModalBtn').addEventListener('click', function() {
        document.getElementById('document_preview_modal').close();
      });
      
      document.getElementById('closePreviewModal').addEventListener('click', function() {
        document.getElementById('document_preview_modal').close();
      });
      
      // Initialize loading spinner style
      const style = document.createElement('style');
      style.textContent = `
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
      `;
      document.head.appendChild(style);
    });
  </script>
   <!-- Include JavaScript file -->
  <script src="../../JS/learning_modules_repository.js"></script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
