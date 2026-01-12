<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Management - IDP Table</title>
    <!-- Tailwind CSS & DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.11.1/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .badge-financial { background-color: #8B5CF6; color: white; }
        .badge-hr { background-color: #10B981; color: white; }
        .badge-hotel { background-color: #F59E0B; color: white; }
        .badge-success { background-color: #10B981; color: white; }
        .badge-warning { background-color: #F59E0B; color: white; }
        .badge-info { background-color: #3B82F6; color: white; }
        .badge-neutral { background-color: #4C4C4C; color: white; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #F3F4F6; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9CA3AF; }
        
        /* Form field styling */
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .form-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #374151;
            transition: all 0.2s;
            background-color: white;
        }
        
        .form-field:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-field:disabled {
            background-color: #F9FAFB;
            color: #9CA3AF;
            cursor: not-allowed;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        /* Stats Cards Horizontal Layout */
        .stats-container {
            display: flex;
            flex-direction: row;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: #D1D5DB #F3F4F6;
        }
        
        .stats-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .stats-container::-webkit-scrollbar-track {
            background: #F3F4F6;
            border-radius: 3px;
        }
        
        .stats-container::-webkit-scrollbar-thumb {
            background: #D1D5DB;
            border-radius: 3px;
        }
        
        .stats-container::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }
        
        .stat-card {
            flex: 1;
            min-width: 220px;
            background: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-container {
                gap: 0.75rem;
            }
            
            .stat-card {
                min-width: 200px;
                padding: 1rem;
            }
            
            .modal-container {
                margin: 1rem;
                max-height: 90vh;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .search-container {
                width: 100% !important;
            }
            
            .dropdown-container {
                width: 100% !important;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 640px) {
            .stat-card {
                min-width: 180px;
            }
            
            .modal-container {
                margin: 0.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        /* Dropdown menu */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 50;
            width: 100%;
            margin-top: 0.25rem;
            background-color: white;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 300px;
            overflow-y: auto;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #F9FAFB;
        }
        
        .dropdown-item.active {
            background-color: #EFF6FF;
            color: #1D4ED8;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../USM/navbar.php'; ?>
      
      <!-- Database Setup Prompt -->
      <?php
      require_once 'config/db.php';
      $db = new Database();
      $conn = $db->connect();
      
      // Check if table exists and has data
      $table_exists = false;
      $has_data = false;
      
      try {
          $check_table = $conn->query("SELECT 1 FROM development_plans LIMIT 1");
          $table_exists = true;
          
          $count_data = $conn->query("SELECT COUNT(*) as count FROM development_plans");
          $result = $count_data->fetch();
          $has_data = $result['count'] > 0;
      } catch (PDOException $e) {
          $table_exists = false;
      }
      ?>
      
      <?php if (!$table_exists || !$has_data): ?>
      <div class="container mx-auto px-4 pt-4">
        <div class="alert alert-warning shadow-lg">
          <div class="flex items-center">
            <i data-lucide="database" class="w-5 h-5 mr-2"></i>
            <span>
              <?php if (!$table_exists): ?>
                Database table not found. 
              <?php else: ?>
                No development plans found in database. 
              <?php endif; ?>
              <button onclick="setupDatabase()" class="underline font-semibold ml-1">Click here to setup sample data</button>
            </span>
          </div>
          <button class="btn btn-sm btn-ghost" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Message Display (will be replaced with SweetAlert) -->
      
      <main class="container mx-auto px-2 md:px-4 py-4 md:py-8">
        <!-- Stats Cards - Horizontal Layout -->
        <div class="mb-6 md:mb-8">
            <div class="stats-container">
                <?php
                // Reuse the same database connection and queries from above
                $department_filter = isset($_GET['department']) ? $_GET['department'] : '';
                $search_filter = isset($_GET['search']) ? $_GET['search'] : '';
                
                $where_clause = '';
                $params = [];
                
                if ($department_filter) {
                    $where_clause = "WHERE department = :department";
                    $params[':department'] = $department_filter;
                }
                
                if ($search_filter) {
                    if ($where_clause) {
                        $where_clause .= " AND (employee LIKE :search OR dev_area LIKE :search OR training LIKE :search)";
                    } else {
                        $where_clause = "WHERE employee LIKE :search OR dev_area LIKE :search OR training LIKE :search";
                    }
                    $params[':search'] = "%$search_filter%";
                }
                
                // Total Plans
                $query_total = "SELECT COUNT(*) as total FROM development_plans" . ($where_clause ? " $where_clause" : "");
                $stmt_total = $conn->prepare($query_total);
                foreach ($params as $key => $value) {
                    $stmt_total->bindValue($key, $value);
                }
                if ($table_exists) {
                    $stmt_total->execute();
                    $total_result = $stmt_total->fetch();
                    $total_plans = $total_result['total'];
                } else {
                    $total_plans = 0;
                }
                
                // Completed Plans
                $query_completed = "SELECT COUNT(*) as completed FROM development_plans WHERE status = 'Completed'" . ($where_clause ? " AND (" . substr($where_clause, 6) . ")" : "");
                $stmt_completed = $conn->prepare($query_completed);
                foreach ($params as $key => $value) {
                    $stmt_completed->bindValue($key, $value);
                }
                if ($table_exists) {
                    $stmt_completed->execute();
                    $completed_result = $stmt_completed->fetch();
                    $completed_plans = $completed_result['completed'];
                } else {
                    $completed_plans = 0;
                }
                
                // In Progress Plans
                $query_inprogress = "SELECT COUNT(*) as inprogress FROM development_plans WHERE status = 'In Progress'" . ($where_clause ? " AND (" . substr($where_clause, 6) . ")" : "");
                $stmt_inprogress = $conn->prepare($query_inprogress);
                foreach ($params as $key => $value) {
                    $stmt_inprogress->bindValue($key, $value);
                }
                if ($table_exists) {
                    $stmt_inprogress->execute();
                    $inprogress_result = $stmt_inprogress->fetch();
                    $inprogress_plans = $inprogress_result['inprogress'];
                } else {
                    $inprogress_plans = 0;
                }
                
                // Not Started Plans
                $query_notstarted = "SELECT COUNT(*) as notstarted FROM development_plans WHERE status = 'Not Started'" . ($where_clause ? " AND (" . substr($where_clause, 6) . ")" : "");
                $stmt_notstarted = $conn->prepare($query_notstarted);
                foreach ($params as $key => $value) {
                    $stmt_notstarted->bindValue($key, $value);
                }
                if ($table_exists) {
                    $stmt_notstarted->execute();
                    $notstarted_result = $stmt_notstarted->fetch();
                    $notstarted_plans = $notstarted_result['notstarted'];
                } else {
                    $notstarted_plans = 0;
                }
                ?>
                
                <!-- Total Plans Card -->
                <div class="stat-card border-blue-600">
                    <div class="flex items-center mb-3">
                        <div class="rounded-lg p-2 mr-3 bg-blue-50">
                            <i data-lucide="table" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600 font-medium">Total Plans</div>
                            <div class="text-2xl font-bold text-blue-600"><?php echo $total_plans; ?></div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Active development plans</div>
                </div>
                
                <!-- Completed Plans Card -->
                <div class="stat-card border-emerald-500">
                    <div class="flex items-center mb-3">
                        <div class="rounded-lg p-2 mr-3 bg-emerald-50">
                            <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600 font-medium">Completed</div>
                            <div class="text-2xl font-bold text-emerald-500"><?php echo $completed_plans; ?></div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Plans completed</div>
                </div>
                
                <!-- In Progress Plans Card -->
                <div class="stat-card border-amber-500">
                    <div class="flex items-center mb-3">
                        <div class="rounded-lg p-2 mr-3 bg-amber-50">
                            <i data-lucide="clock" class="w-6 h-6 text-amber-500"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600 font-medium">In Progress</div>
                            <div class="text-2xl font-bold text-amber-500"><?php echo $inprogress_plans; ?></div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Currently active</div>
                </div>
                
                <!-- Not Started Plans Card -->
                <div class="stat-card border-gray-500">
                    <div class="flex items-center mb-3">
                        <div class="rounded-lg p-2 mr-3 bg-gray-50">
                            <i data-lucide="calendar" class="w-6 h-6 text-gray-500"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600 font-medium">Not Started</div>
                            <div class="text-2xl font-bold text-gray-500"><?php echo $notstarted_plans; ?></div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Awaiting start</div>
                </div>
            </div>
        </div>
        
        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 bg-white p-4 md:p-5 rounded-xl shadow-sm border border-gray-100 action-bar">
            <div class="mb-4 md:mb-0">
                <h2 class="text-lg md:text-xl font-semibold text-gray-800">Development Plans</h2>
                <p class="text-sm text-gray-500 mt-1">Manage individual development plans in table format</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <!-- Search -->
                <div class="relative w-full search-container">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <form method="GET" action="" id="searchForm">
                        <input type="hidden" name="department" id="departmentFilter" value="<?php echo htmlspecialchars($department_filter); ?>">
                        <input type="text" name="search" placeholder="Search plans..." 
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-200"
                               value="<?php echo htmlspecialchars($search_filter); ?>">
                    </form>
                </div>
                
                <!-- Department Filter -->
                <div class="relative w-full dropdown-container">
                    <button class="flex items-center justify-between w-full px-4 py-2 border border-gray-200 rounded-lg bg-white hover:border-blue-500 transition-all duration-200 department-dropdown-btn <?php echo $department_filter ? 'border-blue-500 bg-blue-50' : ''; ?>">
                        <span class="flex items-center gap-2 text-gray-700">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <?php if ($department_filter): ?>
                                <span class="truncate"><?php echo htmlspecialchars($department_filter); ?></span>
                            <?php else: ?>
                                <span>All Departments</span>
                            <?php endif; ?>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 ml-2 transition-transform duration-200"></i>
                    </button>
                    <div class="dropdown-menu hidden">
                        <div class="dropdown-item <?php echo empty($department_filter) ? 'active' : ''; ?>" 
                             onclick="selectDepartment('')">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            <span>All Departments</span>
                        </div>
                        <?php
                        if ($table_exists) {
                            $dept_query = "SELECT DISTINCT department FROM development_plans ORDER BY department";
                            $dept_stmt = $conn->prepare($dept_query);
                            $dept_stmt->execute();
                            $departments = $dept_stmt->fetchAll();
                            
                            foreach ($departments as $dept):
                                $dept_name = $dept['department'];
                                $dept_icon = strpos($dept_name, 'Financial') !== false ? 'banknote' : 
                                            (strpos($dept_name, 'HR') !== false ? 'users' : 
                                            (strpos($dept_name, 'Hotel') !== false || strpos($dept_name, 'Restaurant') !== false ? 'building' : 'briefcase'));
                        ?>
                        <div class="dropdown-item <?php echo $department_filter === $dept_name ? 'active' : ''; ?>" 
                             onclick="selectDepartment('<?php echo htmlspecialchars($dept_name); ?>')">
                            <i data-lucide="<?php echo $dept_icon; ?>" class="w-4 h-4"></i>
                            <span><?php echo htmlspecialchars($dept_name); ?></span>
                        </div>
                        <?php endforeach; 
                        } ?>
                    </div>
                </div>
                
                <!-- Add New Plan Button -->
                <button id="addPlanBtn" class="flex items-center justify-center whitespace-nowrap px-4 py-2 md:px-6 md:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:shadow-lg transition-all duration-300 font-medium text-sm md:text-base">
                    <i data-lucide="plus" class="w-4 h-4 md:w-5 md:h-5 mr-2"></i> Add New Plan
                </button>
            </div>
        </div>
        
        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 table-container">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-blue-50 text-gray-800">
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Employee</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Department</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Development Area</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Required Training</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Timeline</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Responsible</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Status</th>
                            <th class="py-3 px-3 md:py-4 md:px-4 font-semibold text-sm md:text-base">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="idpTableBody">
                        <?php
                        if ($table_exists) {
                            $query = "SELECT * FROM development_plans";
                            if ($where_clause) {
                                $query .= " " . $where_clause;
                            }
                            $query .= " ORDER BY created_at DESC";
                            
                            $stmt = $conn->prepare($query);
                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }
                            $stmt->execute();
                            $plans = $stmt->fetchAll();
                        } else {
                            $plans = [];
                        }
                        
                        if (count($plans) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8">
                                    <div class="py-8 md:py-12 mx-3 md:mx-5 my-4 md:my-8 bg-blue-50 rounded-xl">
                                        <i data-lucide="clipboard" class="w-10 h-10 md:w-12 md:h-12 mb-4 mx-auto text-blue-200"></i>
                                        <h3 class="text-lg md:text-xl font-medium mb-2 text-gray-600">No Development Plans Found</h3>
                                        <p class="text-sm mb-4 md:mb-6 text-gray-500 px-4">
                                            <?php if ($department_filter || $search_filter): ?>
                                                Try changing your filters or
                                            <?php endif; ?>
                                            Get started by creating your first development plan
                                        </p>
                                        <button id="addPlanBtn2" class="flex items-center mx-auto px-4 py-2 md:px-6 md:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:shadow-lg transition-all duration-300 font-medium text-sm md:text-base">
                                            <i data-lucide="plus" class="w-4 h-4 md:w-5 md:h-5 mr-2"></i> 
                                            <?php echo $table_exists ? 'Create First Plan' : 'Setup Database First'; ?>
                                        </button>
                                        <?php if (!$table_exists): ?>
                                        <div class="mt-4">
                                            <button onclick="setupDatabase()" class="flex items-center mx-auto px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-300 font-medium text-sm">
                                                <i data-lucide="database" class="w-4 h-4 mr-2"></i>
                                                Setup Sample Data
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: 
                            foreach ($plans as $plan): 
                                $statusClass = $plan['status'] === 'Completed' ? 'badge-success' : 
                                            ($plan['status'] === 'In Progress' ? 'badge-warning' : 
                                            ($plan['status'] === 'On Hold' ? 'badge-info' : 'badge-neutral'));
                                $statusIcon = $plan['status'] === 'Completed' ? 'check-circle' : 
                                            ($plan['status'] === 'In Progress' ? 'clock' : 
                                            ($plan['status'] === 'On Hold' ? 'pause-circle' : 'calendar'));
                                
                                $dept_name = $plan['department'];
                                $deptClass = strpos($dept_name, 'Financial') !== false ? 'badge-financial' : 
                                           (strpos($dept_name, 'HR') !== false ? 'badge-hr' : 
                                           (strpos($dept_name, 'Hotel') !== false || strpos($dept_name, 'Restaurant') !== false ? 'badge-hotel' : 'badge-neutral'));
                                $deptIcon = strpos($dept_name, 'Financial') !== false ? 'banknote' : 
                                          (strpos($dept_name, 'HR') !== false ? 'users' : 
                                          (strpos($dept_name, 'Hotel') !== false || strpos($dept_name, 'Restaurant') !== false ? 'building' : 'briefcase'));
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-colors duration-200">
                            <td class="py-3 px-3 md:py-4 md:px-4 font-medium text-gray-700 text-sm md:text-base"><?php echo htmlspecialchars($plan['employee']); ?></td>
                            <td class="py-3 px-3 md:py-4 md:px-4">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 md:px-3 md:py-1 rounded-full text-xs md:text-sm font-medium <?php echo $deptClass; ?>">
                                    <i data-lucide="<?php echo $deptIcon; ?>" class="w-3 h-3 md:w-4 md:h-4"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($plan['department']); ?></span>
                                </span>
                            </td>
                            <td class="py-3 px-3 md:py-4 md:px-4 text-gray-700 text-sm md:text-base"><?php echo htmlspecialchars($plan['dev_area']); ?></td>
                            <td class="py-3 px-3 md:py-4 md:px-4 text-gray-700 text-sm md:text-base"><?php echo htmlspecialchars($plan['training']); ?></td>
                            <td class="py-3 px-3 md:py-4 md:px-4">
                                <div class="flex items-center text-gray-700 text-sm md:text-base">
                                    <i data-lucide="calendar" class="w-3 h-3 md:w-4 md:h-4 mr-2 text-blue-600"></i>
                                    <?php echo htmlspecialchars($plan['timeline']); ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 md:py-4 md:px-4 text-gray-700 text-sm md:text-base"><?php echo htmlspecialchars($plan['responsible']); ?></td>
                            <td class="py-3 px-3 md:py-4 md:px-4">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 md:px-3 md:py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <i data-lucide="<?php echo $statusIcon; ?>" class="w-3 h-3 md:w-4 md:h-4"></i>
                                    <?php echo htmlspecialchars($plan['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 md:py-4 md:px-4">
                                <div class="flex items-center gap-1 md:gap-2">
                                    <button class="view-btn p-1.5 md:p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200"
                                            data-id="<?php echo $plan['id']; ?>"
                                            data-employee="<?php echo htmlspecialchars($plan['employee']); ?>"
                                            data-department="<?php echo htmlspecialchars($plan['department']); ?>"
                                            data-dev_area="<?php echo htmlspecialchars($plan['dev_area']); ?>"
                                            data-training="<?php echo htmlspecialchars($plan['training']); ?>"
                                            data-timeline="<?php echo htmlspecialchars($plan['timeline']); ?>"
                                            data-responsible="<?php echo htmlspecialchars($plan['responsible']); ?>"
                                            data-status="<?php echo htmlspecialchars($plan['status']); ?>"
                                            data-start_date="<?php echo $plan['start_date']; ?>"
                                            data-end_date="<?php echo $plan['end_date']; ?>"
                                            data-notes="<?php echo htmlspecialchars($plan['notes']); ?>">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 md:w-4 md:h-4 text-gray-600"></i>
                                    </button>
                                    <button class="edit-btn p-1.5 md:p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                            data-id="<?php echo $plan['id']; ?>"
                                            data-employee="<?php echo htmlspecialchars($plan['employee']); ?>"
                                            data-department="<?php echo htmlspecialchars($plan['department']); ?>"
                                            data-dev_area="<?php echo htmlspecialchars($plan['dev_area']); ?>"
                                            data-training="<?php echo htmlspecialchars($plan['training']); ?>"
                                            data-timeline="<?php echo htmlspecialchars($plan['timeline']); ?>"
                                            data-responsible="<?php echo htmlspecialchars($plan['responsible']); ?>"
                                            data-status="<?php echo htmlspecialchars($plan['status']); ?>"
                                            data-start_date="<?php echo $plan['start_date']; ?>"
                                            data-end_date="<?php echo $plan['end_date']; ?>"
                                            data-notes="<?php echo htmlspecialchars($plan['notes']); ?>">
                                        <i data-lucide="edit" class="w-3.5 h-3.5 md:w-4 md:h-4 text-blue-600"></i>
                                    </button>
                                    <button class="delete-btn p-1.5 md:p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                                            data-id="<?php echo $plan['id']; ?>"
                                            data-employee="<?php echo htmlspecialchars($plan['employee']); ?>"
                                            data-department="<?php echo htmlspecialchars($plan['department']); ?>"
                                            data-dev_area="<?php echo htmlspecialchars($plan['dev_area']); ?>">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 md:w-4 md:h-4 text-red-600"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 md:p-4 bg-black/50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col modal-container" style="animation: fadeIn 0.3s ease-out;">
            <!-- Modal Header -->
            <div class="p-4 md:p-6 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-50">
                            <i data-lucide="file-text" class="w-5 h-5 md:w-6 md:h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg md:text-xl font-semibold text-gray-800">Plan Details</h3>
                            <p class="text-sm text-gray-500">View development plan information</p>
                        </div>
                    </div>
                    <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 close-view-modal">
                        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6">
                <div class="space-y-6">
                    <!-- Employee Information -->
                    <div class="space-y-4">
                        <h4 class="section-title flex items-center gap-2">
                            <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                            Employee Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 form-grid">
                            <div class="space-y-2">
                                <label class="form-label">Employee</label>
                                <div class="form-field bg-gray-50 text-gray-700 view-employee"></div>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Department</label>
                                <div class="form-field bg-gray-50 text-gray-700 view-department"></div>
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="form-label">Development Area</label>
                                <div class="form-field bg-gray-50 text-gray-700 view-dev-area"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Training Information -->
                    <div class="space-y-4">
                        <h4 class="section-title flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-5 h-5 text-blue-600"></i>
                            Training Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 form-grid">
                            <div class="space-y-2">
                                <label class="form-label">Required Training</label>
                                <div class="form-field bg-gray-50 text-gray-700 view-training"></div>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Timeline</label>
                                <div class="form-field bg-gray-50 text-gray-700 view-timeline"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 form-grid">
                        <!-- Timeline Dates -->
                        <div class="space-y-4">
                            <h4 class="section-title flex items-center gap-2">
                                <i data-lucide="calendar-range" class="w-5 h-5 text-blue-600"></i>
                                Timeline Dates
                            </h4>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="form-label">Start Date</label>
                                    <div class="form-field bg-gray-50 text-gray-700 view-start-date"></div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">End Date</label>
                                    <div class="form-field bg-gray-50 text-gray-700 view-end-date"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status & Management -->
                        <div class="space-y-4">
                            <h4 class="section-title flex items-center gap-2">
                                <i data-lucide="bar-chart" class="w-5 h-5 text-blue-600"></i>
                                Status & Management
                            </h4>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="form-label">Status</label>
                                    <div class="view-status"></div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Responsible</label>
                                    <div class="form-field bg-gray-50 text-gray-700 view-responsible"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="space-y-4">
                        <h4 class="section-title flex items-center gap-2">
                            <i data-lucide="message-square" class="w-5 h-5 text-blue-600"></i>
                            Additional Notes
                        </h4>
                        <div class="space-y-2">
                            <div class="form-field bg-gray-50 text-gray-700 min-h-[100px] view-notes"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="p-4 md:p-6 border-t border-gray-200 flex-shrink-0">
                <div class="flex flex-col sm:flex-row justify-end gap-3">
                    <button class="px-4 py-2 md:px-6 md:py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors duration-200 close-view-modal">
                        Close
                    </button>
                    <button class="px-4 py-2 md:px-6 md:py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 edit-from-view">
                        Edit Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 md:p-4 bg-black/50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col modal-container" style="animation: fadeIn 0.3s ease-out;">
            <!-- Modal Header -->
            <div class="p-4 md:p-6 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-50">
                            <i data-lucide="file-plus" class="w-5 h-5 md:w-6 md:h-6 text-blue-600" id="editModalIcon"></i>
                        </div>
                        <div>
                            <h3 class="text-lg md:text-xl font-semibold text-gray-800" id="editModalTitle">Add New Plan</h3>
                            <p class="text-sm text-gray-500" id="editModalSubtitle">Fill in the details to create a new development plan</p>
                        </div>
                    </div>
                    <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 close-edit-modal">
                        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <form method="POST" action="process_idp.php" id="editForm" class="flex-1 overflow-y-auto custom-scrollbar">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editPlanId" value="0">
                
                <div class="p-4 md:p-6">
                    <div class="space-y-6">
                        <!-- Employee Information -->
                        <div class="space-y-4">
                            <h4 class="section-title flex items-center gap-2">
                                <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                                Employee Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 form-grid">
                                <div class="space-y-2">
                                    <label class="form-label">
                                        <span class="flex items-center gap-1">
                                            Employee <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select class="form-field" name="employee" id="editEmployee" required>
                                        <option value="" disabled selected>Select employee</option>
                                        <?php
                                        if ($table_exists) {
                                            $emp_query = "SELECT DISTINCT employee FROM development_plans ORDER BY employee";
                                            $emp_stmt = $conn->prepare($emp_query);
                                            $emp_stmt->execute();
                                            $employees = $emp_stmt->fetchAll();
                                            foreach ($employees as $emp):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($emp['employee']); ?>">
                                            <?php echo htmlspecialchars($emp['employee']); ?>
                                        </option>
                                        <?php 
                                            endforeach; 
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">
                                        <span class="flex items-center gap-1">
                                            Department <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select class="form-field" name="department" id="editDepartment" required>
                                        <option value="" disabled selected>Select department</option>
                                        <?php
                                        if ($table_exists) {
                                            $dept_query = "SELECT DISTINCT department FROM development_plans ORDER BY department";
                                            $dept_stmt = $conn->prepare($dept_query);
                                            $dept_stmt->execute();
                                            $departments = $dept_stmt->fetchAll();
                                            foreach ($departments as $dept):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                            <?php echo htmlspecialchars($dept['department']); ?>
                                        </option>
                                        <?php 
                                            endforeach; 
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2 space-y-2">
                                    <label class="form-label">
                                        <span class="flex items-center gap-1">
                                            Development Area <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select class="form-field" name="devArea" id="editDevArea" required>
                                        <option value="" disabled selected>Select development area</option>
                                        <?php
                                        if ($table_exists) {
                                            $area_query = "SELECT DISTINCT dev_area FROM development_plans ORDER BY dev_area";
                                            $area_stmt = $conn->prepare($area_query);
                                            $area_stmt->execute();
                                            $areas = $area_stmt->fetchAll();
                                            foreach ($areas as $area):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($area['dev_area']); ?>">
                                            <?php echo htmlspecialchars($area['dev_area']); ?>
                                        </option>
                                        <?php 
                                            endforeach; 
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Training Information -->
                        <div class="space-y-4">
                            <h4 class="section-title flex items-center gap-2">
                                <i data-lucide="graduation-cap" class="w-5 h-5 text-blue-600"></i>
                                Training Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 form-grid">
                                <div class="space-y-2">
                                    <label class="form-label">
                                        <span class="flex items-center gap-1">
                                            Required Training <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select class="form-field" name="training" id="editTraining" required>
                                        <option value="" disabled selected>Select required training</option>
                                        <?php
                                        if ($table_exists) {
                                            $train_query = "SELECT DISTINCT training FROM development_plans ORDER BY training";
                                            $train_stmt = $conn->prepare($train_query);
                                            $train_stmt->execute();
                                            $trainings = $train_stmt->fetchAll();
                                            foreach ($trainings as $train):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($train['training']); ?>">
                                            <?php echo htmlspecialchars($train['training']); ?>
                                        </option>
                                        <?php 
                                            endforeach; 
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">
                                        <span class="flex items-center gap-1">
                                            Timeline <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select class="form-field" name="timeline" id="editTimeline" required>
                                        <option value="" disabled selected>Select timeline</option>
                                        <?php
                                        if ($table_exists) {
                                            $time_query = "SELECT DISTINCT timeline FROM development_plans ORDER BY timeline";
                                            $time_stmt = $conn->prepare($time_query);
                                            $time_stmt->execute();
                                            $timelines = $time_stmt->fetchAll();
                                            foreach ($timelines as $time):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($time['timeline']); ?>">
                                            <?php echo htmlspecialchars($time['timeline']); ?>
                                        </option>
                                        <?php 
                                            endforeach; 
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 form-grid">
                            <!-- Timeline Dates -->
                            <div class="space-y-4">
                                <h4 class="section-title flex items-center gap-2">
                                    <i data-lucide="calendar-range" class="w-5 h-5 text-blue-600"></i>
                                    Timeline Dates
                                </h4>
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label class="form-label">
                                            <span class="flex items-center gap-1">
                                                Start Date <span class="text-red-500">*</span>
                                            </span>
                                        </label>
                                        <input type="date" class="form-field" 
                                               name="startDate" id="editStartDate" required>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="form-label">
                                            <span class="flex items-center gap-1">
                                                End Date <span class="text-red-500">*</span>
                                            </span>
                                        </label>
                                        <input type="date" class="form-field" 
                                               name="endDate" id="editEndDate" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status & Management -->
                            <div class="space-y-4">
                                <h4 class="section-title flex items-center gap-2">
                                    <i data-lucide="bar-chart" class="w-5 h-5 text-blue-600"></i>
                                    Status & Management
                                </h4>
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label class="form-label">
                                            <span class="flex items-center gap-1">
                                                Status <span class="text-red-500">*</span>
                                            </span>
                                        </label>
                                        <select class="form-field" name="status" id="editStatus" required>
                                            <?php
                                            if ($table_exists) {
                                                $status_query = "SELECT DISTINCT status FROM development_plans ORDER BY status";
                                                $status_stmt = $conn->prepare($status_query);
                                                $status_stmt->execute();
                                                $statuses = $status_stmt->fetchAll();
                                                foreach ($statuses as $stat):
                                            ?>
                                            <option value="<?php echo htmlspecialchars($stat['status']); ?>">
                                                <?php echo htmlspecialchars($stat['status']); ?>
                                            </option>
                                            <?php 
                                                endforeach; 
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="form-label">
                                            <span class="flex items-center gap-1">
                                                Responsible <span class="text-red-500">*</span>
                                            </span>
                                        </label>
                                        <select class="form-field" name="responsible" id="editResponsible" required>
                                            <option value="" disabled selected>Select responsible</option>
                                            <?php
                                            if ($table_exists) {
                                                $resp_query = "SELECT DISTINCT responsible FROM development_plans ORDER BY responsible";
                                                $resp_stmt = $conn->prepare($resp_query);
                                                $resp_stmt->execute();
                                                $responsibles = $resp_stmt->fetchAll();
                                                foreach ($responsibles as $resp):
                                            ?>
                                            <option value="<?php echo htmlspecialchars($resp['responsible']); ?>">
                                                <?php echo htmlspecialchars($resp['responsible']); ?>
                                            </option>
                                            <?php 
                                                endforeach; 
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="space-y-4">
                            <h4 class="section-title flex items-center gap-2">
                                <i data-lucide="message-square" class="w-5 h-5 text-blue-600"></i>
                                Additional Notes
                            </h4>
                            <div class="space-y-2">
                                <label class="form-label">
                                    <span class="flex items-center gap-1">
                                        Notes <span class="text-gray-400 font-normal">(Optional)</span>
                                    </span>
                                </label>
                                <textarea class="form-field h-32" 
                                          name="notes" id="editNotes" placeholder="Enter any additional notes, comments, or specific requirements..."></textarea>
                                <div class="flex justify-between items-center mt-1">
                                    <span class="text-sm text-gray-500">Maximum 500 characters</span>
                                    <span class="text-sm text-gray-400" id="editCharCount">0/500</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="p-4 md:p-6 border-t border-gray-200 flex-shrink-0">
                    <div class="flex flex-col sm:flex-row justify-end gap-3">
                        <button type="button" class="px-4 py-2 md:px-6 md:py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors duration-200 close-edit-modal">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 md:px-6 md:py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200" id="editSubmitBtn">
                            Save Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 md:p-4 bg-black/50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-container" style="animation: slideUp 0.3s ease-out;">
            <!-- Modal Content -->
            <form method="POST" action="process_idp.php" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">
                
                <div class="p-6">
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mb-4">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Plan</h3>
                        <p class="text-gray-600 mb-6" id="deleteMessage">Are you sure you want to delete this development plan?</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row justify-center gap-3">
                        <button type="button" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors duration-200 close-delete-modal">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200 flex items-center justify-center gap-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Database setup function
        function setupDatabase() {
            Swal.fire({
                title: 'Setup Sample Data',
                text: 'This will create the database table and add sample development plans. Do you want to continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, setup database',
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('setup_database.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error}`
                            );
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Database Setup Complete!',
                        text: 'Sample data has been added successfully.',
                        confirmButtonColor: '#3085d6',
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }
        
        // Check for PHP session messages and show SweetAlert
        <?php if (isset($_SESSION['message'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            const message = "<?php echo addslashes($_SESSION['message']); ?>";
            const messageType = "<?php echo $_SESSION['message_type'] ?? 'success'; ?>";
            
            Swal.fire({
                icon: messageType === 'error' ? 'error' : 'success',
                title: messageType === 'error' ? 'Error!' : 'Success!',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        });
        <?php endif; ?>
        
        // DOM Elements
        const viewModal = document.getElementById('viewModal');
        const editModal = document.getElementById('editModal');
        const deleteModalElement = document.getElementById('deleteModal');
        const editForm = document.getElementById('editForm');
        const deleteForm = document.getElementById('deleteForm');
        const editNotes = document.getElementById('editNotes');
        const editCharCount = document.getElementById('editCharCount');
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            setupDropdowns();
            
            // Set default dates
            const today = new Date();
            const todayFormatted = today.toISOString().split('T')[0];
            const endDate = new Date();
            endDate.setMonth(endDate.getMonth() + 1);
            const endDateFormatted = endDate.toISOString().split('T')[0];
            
            if (document.getElementById('editStartDate')) {
                document.getElementById('editStartDate').value = todayFormatted;
                document.getElementById('editEndDate').value = endDateFormatted;
            }
            
            // Character counter
            if (editNotes) {
                editNotes.addEventListener('input', updateCharCount);
            }
            
            // Form submission with SweetAlert
            setupFormAlerts();
        });
        
        // Update character count
        function updateCharCount() {
            const length = editNotes.value.length;
            editCharCount.textContent = `${length}/500`;
            editCharCount.classList.toggle('text-red-500', length > 500);
            editCharCount.classList.toggle('text-gray-400', length <= 500);
        }
        
        // Setup dropdown functionality
        function setupDropdowns() {
            // Department dropdown
            document.querySelectorAll('.department-dropdown-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menu = this.nextElementSibling;
                    const chevron = this.querySelector('i[data-lucide="chevron-down"]');
                    
                    // Toggle current
                    menu.classList.toggle('hidden');
                    
                    // Rotate chevron
                    if (menu.classList.contains('hidden')) {
                        chevron.style.transform = 'rotate(0deg)';
                    } else {
                        chevron.style.transform = 'rotate(180deg)';
                    }
                    
                    // Close other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.classList.add('hidden');
                            const otherChevron = otherMenu.previousElementSibling.querySelector('i[data-lucide="chevron-down"]');
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    });
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-container')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.add('hidden');
                    });
                    document.querySelectorAll('.department-dropdown-btn i[data-lucide="chevron-down"]').forEach(chevron => {
                        chevron.style.transform = 'rotate(0deg)';
                    });
                }
            });
        }
        
        // Setup form alerts
        function setupFormAlerts() {
            // Edit form submission
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const action = formData.get('action');
                    const isEdit = formData.get('id') !== '0';
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: isEdit ? 'Plan Updated!' : 'Plan Created!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500,
                                willClose: () => {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred',
                                confirmButtonColor: '#3085d6',
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while processing your request',
                            confirmButtonColor: '#3085d6',
                        });
                    });
                });
            }
            
            // Delete form submission
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Plan Deleted!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500,
                                willClose: () => {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred',
                                confirmButtonColor: '#3085d6',
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the plan',
                            confirmButtonColor: '#3085d6',
                        });
                    });
                });
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Add plan buttons
            document.getElementById('addPlanBtn').addEventListener('click', openAddModal);
            if (document.getElementById('addPlanBtn2')) {
                document.getElementById('addPlanBtn2').addEventListener('click', openAddModal);
            }
            
            // View buttons
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', openViewModal);
            });
            
            // Edit buttons
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', openEditModal);
            });
            
            // Delete buttons
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', openDeleteModal);
            });
            
            // Close buttons
            document.querySelectorAll('.close-view-modal').forEach(btn => {
                btn.addEventListener('click', () => viewModal.classList.add('hidden'));
            });
            
            document.querySelectorAll('.close-edit-modal').forEach(btn => {
                btn.addEventListener('click', () => editModal.classList.add('hidden'));
            });
            
            document.querySelectorAll('.close-delete-modal').forEach(btn => {
                btn.addEventListener('click', () => deleteModalElement.classList.add('hidden'));
            });
            
            // Edit from view button
            const editFromViewBtn = document.querySelector('.edit-from-view');
            if (editFromViewBtn) {
                editFromViewBtn.addEventListener('click', function() {
                    const viewId = document.querySelector('.view-employee').dataset.id;
                    viewModal.classList.add('hidden');
                    const editBtn = document.querySelector(`.edit-btn[data-id="${viewId}"]`);
                    if (editBtn) editBtn.click();
                });
            }
            
            // Close modals on background click
            [viewModal, editModal, deleteModalElement].forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.add('hidden');
                    }
                });
            });
            
            // Search form submission
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('searchForm').submit();
                    }
                });
            }
        }
        
        // Open view modal
        function openViewModal() {
            const data = {
                id: this.dataset.id,
                employee: this.dataset.employee,
                department: this.dataset.department,
                devArea: this.dataset.dev_area,
                training: this.dataset.training,
                timeline: this.dataset.timeline,
                responsible: this.dataset.responsible,
                status: this.dataset.status,
                startDate: this.dataset.start_date,
                endDate: this.dataset.end_date,
                notes: this.dataset.notes
            };
            
            // Fill view modal
            document.querySelector('.view-employee').textContent = data.employee;
            document.querySelector('.view-department').textContent = data.department;
            document.querySelector('.view-dev-area').textContent = data.devArea;
            document.querySelector('.view-training').textContent = data.training;
            document.querySelector('.view-timeline').textContent = data.timeline;
            document.querySelector('.view-responsible').textContent = data.responsible;
            document.querySelector('.view-start-date').textContent = formatDate(data.startDate);
            document.querySelector('.view-end-date').textContent = formatDate(data.endDate);
            
            // Status with badge
            const statusDiv = document.querySelector('.view-status');
            statusDiv.innerHTML = `
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(data.status)}">
                    <i data-lucide="${getStatusIcon(data.status)}" class="w-4 h-4"></i>
                    ${data.status}
                </span>
            `;
            
            // Notes
            const notesElement = document.querySelector('.view-notes');
            notesElement.textContent = data.notes || 'No notes provided';
            notesElement.classList.toggle('text-gray-400', !data.notes);
            notesElement.classList.toggle('italic', !data.notes);
            
            // Store ID for edit button
            document.querySelector('.view-employee').dataset.id = data.id;
            
            // Show modal
            viewModal.classList.remove('hidden');
            lucide.createIcons();
        }
        
        // Open add modal
        function openAddModal() {
            document.getElementById('editModalTitle').textContent = 'Add New Plan';
            document.getElementById('editModalSubtitle').textContent = 'Fill in the details to create a new development plan';
            document.getElementById('editModalIcon').setAttribute('data-lucide', 'file-plus');
            document.getElementById('editSubmitBtn').textContent = 'Save Plan';
            document.getElementById('editPlanId').value = '0';
            
            // Reset form
            editForm.reset();
            
            // Set default dates
            const today = new Date();
            const todayFormatted = today.toISOString().split('T')[0];
            const endDate = new Date();
            endDate.setMonth(endDate.getMonth() + 1);
            const endDateFormatted = endDate.toISOString().split('T')[0];
            
            document.getElementById('editStartDate').value = todayFormatted;
            document.getElementById('editEndDate').value = endDateFormatted;
            
            // Reset character count
            editCharCount.textContent = '0/500';
            editCharCount.classList.remove('text-red-500');
            editCharCount.classList.add('text-gray-400');
            
            // Show modal
            editModal.classList.remove('hidden');
            lucide.createIcons();
        }
        
        // Open edit modal
        function openEditModal() {
            document.getElementById('editModalTitle').textContent = 'Edit Plan';
            document.getElementById('editModalSubtitle').textContent = 'Update the development plan details';
            document.getElementById('editModalIcon').setAttribute('data-lucide', 'file-edit');
            document.getElementById('editSubmitBtn').textContent = 'Update Plan';
            document.getElementById('editPlanId').value = this.dataset.id;
            
            // Fill form
            document.getElementById('editEmployee').value = this.dataset.employee;
            document.getElementById('editDepartment').value = this.dataset.department;
            document.getElementById('editDevArea').value = this.dataset.dev_area;
            document.getElementById('editTraining').value = this.dataset.training;
            document.getElementById('editTimeline').value = this.dataset.timeline;
            document.getElementById('editResponsible').value = this.dataset.responsible;
            document.getElementById('editStatus').value = this.dataset.status;
            document.getElementById('editStartDate').value = this.dataset.start_date;
            document.getElementById('editEndDate').value = this.dataset.end_date;
            document.getElementById('editNotes').value = this.dataset.notes || '';
            
            // Update character count
            const length = this.dataset.notes ? this.dataset.notes.length : 0;
            editCharCount.textContent = `${length}/500`;
            if (length > 500) {
                editCharCount.classList.remove('text-gray-400');
                editCharCount.classList.add('text-red-500');
            } else {
                editCharCount.classList.remove('text-red-500');
                editCharCount.classList.add('text-gray-400');
            }
            
            // Show modal
            editModal.classList.remove('hidden');
            lucide.createIcons();
        }
        
        // Open delete modal
        function openDeleteModal() {
            document.getElementById('deleteId').value = this.dataset.id;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete the plan for ${this.dataset.employee} (${this.dataset.department})?`;
            deleteModalElement.classList.remove('hidden');
            lucide.createIcons();
        }
        
        // Helper functions
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }
        
        function getStatusClass(status) {
            if (status === 'Completed') return 'badge-success';
            if (status === 'In Progress') return 'badge-warning';
            if (status === 'On Hold') return 'badge-info';
            return 'badge-neutral';
        }
        
        function getStatusIcon(status) {
            if (status === 'Completed') return 'check-circle';
            if (status === 'In Progress') return 'clock';
            if (status === 'On Hold') return 'pause-circle';
            return 'calendar';
        }
        
        // Department filter functions
        function selectDepartment(department) {
            document.getElementById('departmentFilter').value = department;
            document.getElementById('searchForm').submit();
        }
        
        function clearFilters() {
            document.getElementById('departmentFilter').value = '';
            document.querySelector('input[name="search"]').value = '';
            document.getElementById('searchForm').submit();
        }
    </script>
</body>
</html>