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

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

// Function to check if column exists in table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return $result && $result->num_rows > 0;
}

// Create exam_results table if it doesn't exist
if (!tableExists($conn, 'exam_results')) {
    $createTableSQL = "
        CREATE TABLE exam_results (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id VARCHAR(50) NOT NULL,
            exam_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            total_questions INT NOT NULL,
            passed BOOLEAN NOT NULL DEFAULT FALSE,
            time_taken INT NOT NULL COMMENT 'Time taken in seconds',
            completed_at DATETIME NOT NULL,
            attempt_number INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_employee_exam (employee_id, exam_id),
            INDEX idx_exam_id (exam_id)
        )
    ";
    
    if (!$conn->query($createTableSQL)) {
        error_log("Error creating exam_results table: " . $conn->error);
    }
}

// Get selected department from filter
$selected_department = $_GET['department'] ?? 'all';
$exam_id = $_GET['exam_id'] ?? null;

// Get all posted examinations
if ($selected_department === 'all') {
    $sql = "SELECT * FROM examinations WHERE status = 'approved' ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM examinations WHERE status = 'approved' AND department = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_department);
}

$posted_examinations = [];
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $posted_examinations[] = $row;
        }
    }
}

// If exam_id is provided, get exam details and employees who took it
$exam_details = null;
$exam_results = [];
if ($exam_id) {
    // Get exam details
    $exam_sql = "SELECT * FROM examinations WHERE id = ?";
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("i", $exam_id);
    if ($exam_stmt->execute()) {
        $exam_result = $exam_stmt->get_result();
        $exam_details = $exam_result->fetch_assoc();
    }
    
    // Get employees who took the exam
    if ($exam_details) {
        // Check if exam_results table has data, if not use dummy data
        $check_data_sql = "SELECT COUNT(*) as count FROM exam_results WHERE exam_id = ?";
        $check_stmt = $conn->prepare($check_data_sql);
        $check_stmt->bind_param("i", $exam_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $data_count = $check_result->fetch_assoc()['count'];
        $check_stmt->close();
        
        if ($data_count > 0) {
            // Real data exists
            $results_sql = "
                SELECT 
                    e.*,
                    er.score,
                    er.total_questions,
                    er.passed,
                    er.time_taken,
                    er.completed_at,
                    er.attempt_number
                FROM employees e
                INNER JOIN exam_results er ON e.employee_id = er.employee_id
                WHERE er.exam_id = ?
                ORDER BY er.score DESC, er.completed_at DESC
            ";
            
            $results_stmt = $conn->prepare($results_sql);
            if ($results_stmt) {
                $results_stmt->bind_param("i", $exam_id);
                if ($results_stmt->execute()) {
                    $results_result = $results_stmt->get_result();
                    while($row = $results_result->fetch_assoc()) {
                        $exam_results[] = $row;
                    }
                }
                $results_stmt->close();
            }
        } else {
            // Use dummy data for demonstration
            $exam_results = generateDummyResults($exam_id, $exam_details);
        }
    }
}

// Get unique departments for filter dropdown
$departments_sql = "SELECT DISTINCT department FROM examinations WHERE status = 'approved' ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = [];
if ($departments_result && $departments_result->num_rows > 0) {
    while($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$conn->close();

// Function to generate dummy results for testing
function generateDummyResults($exam_id, $exam_details) {
    $dummy_employees = [
        ['employee_id' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'position' => 'Manager', 'job_title' => 'Sales Manager', 'department' => 'sales'],
        ['employee_id' => 'EMP002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'position' => 'Executive', 'job_title' => 'Sales Executive', 'department' => 'sales'],
        ['employee_id' => 'EMP003', 'first_name' => 'Mike', 'last_name' => 'Johnson', 'position' => 'Analyst', 'job_title' => 'Data Analyst', 'department' => 'it'],
        ['employee_id' => 'EMP004', 'first_name' => 'Sarah', 'last_name' => 'Wilson', 'position' => 'Developer', 'job_title' => 'Software Developer', 'department' => 'it'],
        ['employee_id' => 'EMP005', 'first_name' => 'David', 'last_name' => 'Brown', 'position' => 'Specialist', 'job_title' => 'HR Specialist', 'department' => 'hr']
    ];
    
    $results = [];
    foreach ($dummy_employees as $employee) {
        $score = rand(60, 95);
        $total_questions = 20;
        $passed = $score >= 70;
        $time_taken = rand(1200, 3600); // 20-60 minutes in seconds
        
        $results[] = array_merge($employee, [
            'score' => $score,
            'total_questions' => $total_questions,
            'passed' => $passed,
            'time_taken' => $time_taken,
            'completed_at' => date('Y-m-d H:i:s', strtotime('-'.rand(1,30).' days')),
            'attempt_number' => 1
        ]);
    }
    
    return $results;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Examination Results Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .exam-card, .employee-card {
      transition: all 0.2s ease;
      border: 1px solid #e5e7eb;
      background: white;
    }
    
    .exam-card:hover, .employee-card:hover {
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
    
    .status-approved {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }
    
    .status-passed {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .status-failed {
      background-color: #fee2e2;
      color: #991b1b;
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
    
    .stats-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.5rem;
    }
    
    .table-row:hover {
      background-color: #f9fafb;
    }
    
    .demo-badge {
      background-color: #fef3c7;
      color: #92400e;
      border: 1px solid #fbbf24;
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
          <?php if (!$exam_id): ?>
            <!-- Examination List View -->
            <div class="mb-12">
              <div class="flex justify-between items-center mb-6">
                <div>
                  <h1 class="text-3xl font-bold mb-2 text-gray-800">Examination Results Dashboard</h1>
                  <p class="text-gray-600">View results for all posted examinations</p>
                </div>
                <div class="flex gap-4 items-center">
                  <div class="stats-card">
                    <div class="text-sm text-gray-500">Posted Exams</div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo count($posted_examinations); ?></div>
                    <div class="text-xs text-gray-400">Available for review</div>
                  </div>
                  <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='examination_repository.php'">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Exams
                  </button>
                </div>
              </div>

              <!-- Department Filter -->
              <div class="filter-section mb-6">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Filter by Department
                    </label>
                    <div class="flex flex-col sm:flex-row gap-3">
                      <select class="select select-bordered w-full sm:w-64" id="departmentFilter">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): 
                          $display_name = ucwords(str_replace('-', ' ', $dept));
                        ?>
                          <option value="<?php echo $dept; ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                            <?php echo $display_name; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      
                      <div class="flex gap-2">
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyFilter()">
                          Apply Filter
                        </button>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearFilter()">
                          Clear
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Active Filter Badge -->
                  <?php if ($selected_department !== 'all'): ?>
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-600">Active filter:</span>
                      <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                        <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Examination Cards -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php if (empty($posted_examinations)): ?>
                  <div class="col-span-full empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3 class="text-xl font-semibold mb-2 text-gray-700">
                      <?php if ($selected_department !== 'all'): ?>
                        No Posted Examinations in <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                      <?php else: ?>
                        No Posted Examinations
                      <?php endif; ?>
                    </h3>
                    <p class="text-gray-500 mb-4">
                      <?php if ($selected_department !== 'all'): ?>
                        There are no approved examinations in this department.
                      <?php else: ?>
                        There are no approved examinations at this time.
                      <?php endif; ?>
                    </p>
                    <div class="flex gap-2 justify-center">
                      <?php if ($selected_department !== 'all'): ?>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearFilter()">
                          View All Departments
                        </button>
                      <?php endif; ?>
                      <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='examinations.php'">
                        <i class="fas fa-plus mr-2"></i>Create New Examination
                      </button>
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($posted_examinations as $exam): ?>
                    <div class="exam-card rounded-lg p-6">
                      <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($exam['title']); ?></h3>
                        <span class="status-approved text-xs px-2 py-1 rounded-full">Approved</span>
                      </div>
                      
                      <div class="flex flex-wrap gap-2 my-3">
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></span>
                        <?php if (isset($exam['roles']) && !empty($exam['roles'])): ?>
                          <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($exam['roles']); ?></span>
                        <?php else: ?>
                          <span class="badge-outline text-xs px-2 py-1 rounded">All Roles</span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="space-y-2 mb-4">
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Questions:</span> 
                          <?php echo isset($exam['question_count']) ? $exam['question_count'] : 'N/A'; ?>
                        </p>
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Duration:</span> 
                          <?php echo isset($exam['duration']) ? $exam['duration'] : 'N/A'; ?> minutes
                        </p>
                        <p class="text-sm text-gray-600">
                          <span class="font-medium">Passing Score:</span> 
                          <?php echo isset($exam['passing_score']) ? $exam['passing_score'] . '%' : '70%'; ?>
                        </p>
                      </div>
                      
                      <div class="mt-4">
                        <button class="btn-plain w-full py-2 rounded-lg text-sm" 
                                onclick="viewResults(<?php echo $exam['id']; ?>)">
                          <i class="fas fa-chart-bar mr-2"></i>View Results
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

          <?php else: ?>
            <!-- Exam Results Detail View -->
            <div class="mb-12">
              <div class="flex justify-between items-center mb-6">
                <div>
                  <div class="flex items-center gap-2 mb-2">
                    <button class="btn-plain px-3 py-1 rounded-lg text-sm" onclick="goBack()">
                      <i class="fas fa-arrow-left mr-1"></i>Back
                    </button>
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($exam_details['title']); ?></h1>
                    <?php if (empty($exam_results) || !isset($exam_results[0]['score'])): ?>
                      <span class="demo-badge px-2 py-1 text-xs rounded-full ml-2">Demo Data</span>
                    <?php endif; ?>
                  </div>
                  <p class="text-gray-600">Examination results and employee performance</p>
                </div>
                <div class="flex gap-4">
                  <button class="btn-plain px-4 py-2 rounded-lg" onclick="exportToExcel()">
                    <i class="fas fa-download mr-2"></i>Export Excel
                  </button>
                  <button class="btn-plain px-4 py-2 rounded-lg" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                  </button>
                </div>
              </div>

              <!-- Exam Overview Stats -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                  <div class="text-sm text-gray-500">Total Participants</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo count($exam_results); ?></div>
                </div>
                <div class="stats-card">
                  <div class="text-sm text-gray-500">Average Score</div>
                  <div class="text-2xl font-bold text-gray-800">
                    <?php 
                      if (count($exam_results) > 0) {
                        $total_score = 0;
                        foreach ($exam_results as $result) {
                          $total_score += $result['score'];
                        }
                        echo round($total_score / count($exam_results), 1) . '%';
                      } else {
                        echo '0%';
                      }
                    ?>
                  </div>
                </div>
                <div class="stats-card">
                  <div class="text-sm text-gray-500">Pass Rate</div>
                  <div class="text-2xl font-bold text-gray-800">
                    <?php 
                      if (count($exam_results) > 0) {
                        $passed_count = 0;
                        foreach ($exam_results as $result) {
                          if ($result['passed']) $passed_count++;
                        }
                        echo round(($passed_count / count($exam_results)) * 100, 1) . '%';
                      } else {
                        echo '0%';
                      }
                    ?>
                  </div>
                </div>
                <div class="stats-card">
                  <div class="text-sm text-gray-500">Department</div>
                  <div class="text-2xl font-bold text-gray-800"><?php echo isset($exam_details['department']) ? ucwords(str_replace('-', ' ', $exam_details['department'])) : 'All'; ?></div>
                </div>
              </div>

              <!-- Results Table -->
              <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800">Employee Results</h3>
                  <p class="text-sm text-gray-600">
                    Employees who took this exam
                    <?php if (empty($exam_results) || !isset($exam_results[0]['score'])): ?>
                      <span class="demo-badge px-2 py-1 text-xs rounded-full ml-2">Showing Demo Data</span>
                    <?php endif; ?>
                  </p>
                </div>
                
                <div class="overflow-x-auto">
                  <table class="w-full">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Taken</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php if (empty($exam_results)): ?>
                        <tr>
                          <td colspan="8" class="px-6 py-8 text-center">
                            <div class="empty-state py-8">
                              <i class="fas fa-users"></i>
                              <h3 class="text-lg font-semibold mb-2 text-gray-700">No Results Found</h3>
                              <p class="text-gray-500">No employees have taken this examination yet.</p>
                            </div>
                          </td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($exam_results as $result): ?>
                          <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                  <span class="text-blue-600 font-medium">
                                    <?php echo strtoupper(substr($result['first_name'] ?? 'E', 0, 1) . substr($result['last_name'] ?? 'M', 0, 1)); ?>
                                  </span>
                                </div>
                                <div class="ml-4">
                                  <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars(($result['first_name'] ?? 'Employee') . ' ' . ($result['last_name'] ?? 'Name')); ?>
                                  </div>
                                  <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($result['employee_id'] ?? 'EMP001'); ?>
                                  </div>
                                </div>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-900"><?php echo htmlspecialchars($result['position'] ?? 'Position'); ?></div>
                              <div class="text-sm text-gray-500"><?php echo htmlspecialchars($result['job_title'] ?? 'Job Title'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2.5 mr-2">
                                  <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $result['score']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $result['score']; ?>%</span>
                              </div>
                              <div class="text-xs text-gray-500">
                                <?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?> correct
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <?php if ($result['passed']): ?>
                                <span class="status-passed px-2 py-1 text-xs rounded-full">Passed</span>
                              <?php else: ?>
                                <span class="status-failed px-2 py-1 text-xs rounded-full">Failed</span>
                              <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              <?php echo gmdate("H:i:s", $result['time_taken']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              <?php echo date('M j, Y g:i A', strtotime($result['completed_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              Attempt #<?php echo $result['attempt_number']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                              <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="viewDetailedResult(<?php echo $result['employee_id']; ?>, <?php echo $exam_id; ?>)">
                                Details
                              </button>
                              <?php if ($result['passed']): ?>
                                <button class="text-green-600 hover:text-green-900" onclick="viewCertificate(<?php echo $result['employee_id']; ?>, <?php echo $exam_id; ?>)">
                                  Certificate
                                </button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
    </div>
  </div>

  <script>
    // Filter Functions
    function applyFilter() {
      const departmentFilter = document.getElementById('departmentFilter');
      const selectedDepartment = departmentFilter.value;
      window.location.href = `?department=${selectedDepartment}`;
    }
    
    function clearFilter() {
      window.location.href = '?department=all';
    }

    function viewResults(examId) {
      window.location.href = `?exam_id=${examId}`;
    }

    function goBack() {
      window.location.href = 'exam_results.php';
    }

    function exportToExcel() {
      alert('Excel export functionality would be implemented here');
    }

    function exportToPDF() {
      alert('PDF export functionality would be implemented here');
    }

    function viewDetailedResult(employeeId, examId) {
      alert(`View detailed result for employee ${employeeId} and exam ${examId}`);
    }

    function viewCertificate(employeeId, examId) {
      alert(`Generate certificate for employee ${employeeId} and exam ${examId}`);
    }
  </script>
  
  <script src="../JS/soliera.js"></script>
  <script src="../JS/sidebar.js"></script>
</body>
</html>