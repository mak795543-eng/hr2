<?php
require_once 'config.php';

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? 'all';

// Fetch employees from database
$employees = getEmployees($filter, $search, $department);
$departments = getDepartments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Management System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-reskilling {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #dc2626;
        }
        
        .status-retain {
            background-color: #fef3c7;
            color: #d97706;
            border: 1px solid #d97706;
        }
        
        .status-upskilling {
            background-color: #dbeafe;
            color: #2563eb;
            border: 1px solid #2563eb;
        }
        
        .status-succession {
            background-color: #d1fae5;
            color: #059669;
            border: 1px solid #059669;
        }
        
        .table-header {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .dropdown-option {
            background-color: white !important;
            color: #1f2937 !important;
        }
        
        .dropdown-option:hover {
            background-color: #f3f4f6 !important;
        }
        
        .progress-bar {
            background-color: #e5e7eb;
            border: 1px solid #d1d5db;
        }
        
        .progress-fill {
            transition: width 0.3s ease;
            border-right: 1px solid rgba(0,0,0,0.1);
        }
        
        .skill-low {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
        }
        
        .skill-medium {
            background-color: #fef3c7;
            border-left: 4px solid #d97706;
        }
        
        .skill-high {
            background-color: #d1fae5;
            border-left: 4px solid #059669;
        }
        
        .skill-card {
            transition: all 0.2s ease;
        }
        
        .skill-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .modal-box {
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-box::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-box::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .modal-box::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .modal-box::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .range.range-sm {
            height: 0.5rem;
        }
        
        .range.range-primary::-webkit-slider-thumb {
            background-color: #1f2937;
        }
        
        .range.range-primary::-moz-range-thumb {
            background-color: #1f2937;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast .alert {
            animation: slideIn 0.3s ease-out;
        }
        
        .tab-active {
            border-bottom: 2px solid #1f2937;
            color: #1f2937;
            font-weight: 600;
        }
        
        /* Card styles */
        .employee-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
        }
        
        .employee-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .employee-avatar {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .competency-chart {
            position: relative;
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }
        
        .chart-background {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                transparent 0deg,
                transparent calc(var(--percentage) * 3.6deg),
                #e5e7eb calc(var(--percentage) * 3.6deg),
                #e5e7eb 360deg
            );
        }
        
        .chart-fill {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                var(--chart-color) 0deg,
                var(--chart-color) calc(var(--percentage) * 3.6deg),
                transparent calc(var(--percentage) * 3.6deg),
                transparent 360deg
            );
            transition: all 0.6s ease;
        }
        
        .chart-inner {
            position: absolute;
            width: 70%;
            height: 70%;
            background: white;
            border-radius: 50%;
            top: 15%;
            left: 15%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .chart-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
        }
        
        .chart-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .card-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .card-detail-item:last-child {
            border-bottom: none;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
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
      
      <main class="container mx-auto px-4 py-8">
        <!-- Header and Tabs -->
        <header class="mb-6 border-b border-gray-300 pb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Competency Management</h1>
                    <p class="text-gray-600 mt-2">Track and manage employee competency levels and development</p>
                </div>
            </div>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white border border-gray-300 rounded-lg p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wide">Reskilling</h3>
                        <?php
                        $reskilling_count = array_filter($employees, function($emp) {
                            return $emp['status'] === 'Reskilling';
                        });
                        ?>
                        <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo count($reskilling_count); ?></p>
                        <p class="text-xs text-gray-500 mt-1">0% – 30%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-red-50 border border-red-200">
                        <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-300 rounded-lg p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wide">Retain</h3>
                        <?php
                        $retain_count = array_filter($employees, function($emp) {
                            return $emp['status'] === 'Retain';
                        });
                        ?>
                        <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo count($retain_count); ?></p>
                        <p class="text-xs text-gray-500 mt-1">31% – 60%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-200">
                        <i data-lucide="users" class="w-6 h-6 text-amber-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-300 rounded-lg p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wide">Upskilling</h3>
                        <?php
                        $upskilling_count = array_filter($employees, function($emp) {
                            return $emp['status'] === 'Upskilling';
                        });
                        ?>
                        <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo count($upskilling_count); ?></p>
                        <p class="text-xs text-gray-500 mt-1">61% – 85%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-blue-50 border border-blue-200">
                        <i data-lucide="trending-up" class="w-6 h-6 text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-300 rounded-lg p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wide">Succession Ready</h3>
                        <?php
                        $succession_count = array_filter($employees, function($emp) {
                            return $emp['status'] === 'Succession Ready';
                        });
                        ?>
                        <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo count($succession_count); ?></p>
                        <p class="text-xs text-gray-500 mt-1">86% – 100%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                        <i data-lucide="award" class="w-6 h-6 text-emerald-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Department Filter -->
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">
                        <i data-lucide="building" class="w-4 h-4 mr-2"></i>
                        <?php echo $department === 'all' ? 'All Departments' : htmlspecialchars($department); ?>
                        <i data-lucide="chevron-down" class="w-4 h-4 ml-2"></i>
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-white border border-gray-300 rounded-lg z-[1] w-52 p-1 shadow-lg">
                        <li><a href="?department=all&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $department === 'all' ? 'active' : ''; ?>">
                            All Departments
                        </a></li>
                        <?php foreach ($departments as $dept): ?>
                        <li><a href="?department=<?php echo urlencode($dept); ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $department === $dept ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($dept); ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Status Filter -->
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                        <?php echo $filter === 'all' ? 'All Status' : htmlspecialchars($filter); ?>
                        <i data-lucide="chevron-down" class="w-4 h-4 ml-2"></i>
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-white border border-gray-300 rounded-lg z-[1] w-52 p-1 shadow-lg">
                        <li><a href="?department=<?php echo urlencode($department); ?>&filter=all&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All Status
                        </a></li>
                        <li><a href="?department=<?php echo urlencode($department); ?>&filter=Reskilling&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $filter === 'Reskilling' ? 'active' : ''; ?>">
                            Reskilling
                        </a></li>
                        <li><a href="?department=<?php echo urlencode($department); ?>&filter=Retain&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $filter === 'Retain' ? 'active' : ''; ?>">
                            Retain
                        </a></li>
                        <li><a href="?department=<?php echo urlencode($department); ?>&filter=Upskilling&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $filter === 'Upskilling' ? 'active' : ''; ?>">
                            Upskilling
                        </a></li>
                        <li><a href="?department=<?php echo urlencode($department); ?>&filter=Succession Ready&search=<?php echo urlencode($search); ?>" 
                               class="dropdown-option <?php echo $filter === 'Succession Ready' ? 'active' : ''; ?>">
                            Succession Ready
                        </a></li>
                    </ul>
                </div>
                
                <!-- Legend Button -->
                <button class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800" onclick="document.getElementById('legend-modal').showModal()">
                    <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                    Proficiency Legend
                </button>
            </div>
            
            <!-- Search Form -->
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search employees..." 
                           class="input input-bordered bg-white border border-gray-300 text-gray-800 w-full md:w-64 focus:border-gray-400 focus:outline-none" />
                    <i data-lucide="search" class="absolute right-3 top-3 w-4 h-4 text-gray-400"></i>
                </div>
                <button type="submit" class="btn bg-gray-900 text-white hover:bg-gray-800 border-0">
                    Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="?department=<?php echo urlencode($department); ?>&filter=<?php echo urlencode($filter); ?>" 
                   class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">
                    Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Competency Cards -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (count($employees) > 0): ?>
                    <?php foreach ($employees as $emp): ?>
                    <?php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $emp['status']));
                    $progressColor = '';
                    $chartColor = '';
                    if ($emp['competency'] <= 30) {
                        $progressColor = 'bg-red-500';
                        $chartColor = '#dc2626';
                    } elseif ($emp['competency'] <= 60) {
                        $progressColor = 'bg-amber-500';
                        $chartColor = '#d97706';
                    } elseif ($emp['competency'] <= 85) {
                        $progressColor = 'bg-blue-500';
                        $chartColor = '#2563eb';
                    } else {
                        $progressColor = 'bg-emerald-500';
                        $chartColor = '#059669';
                    }
                    ?>
                    <div class="employee-card bg-white border border-gray-300 rounded-xl shadow-sm">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex items-start gap-4">
                                    <div class="employee-avatar bg-gray-100">
                                        <i data-lucide="user" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($emp['full_name']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($emp['employee_id']); ?></p>
                                    </div>
                                </div>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($emp['status']); ?>
                                </span>
                            </div>
                            
                            <!-- Competency Chart -->
                            <div class="flex items-center justify-center mb-6">
                                <div class="competency-chart">
                                    <div class="chart-background" style="--percentage: 100"></div>
                                    <div class="chart-fill" 
                                         style="--percentage: <?php echo min(100, $emp['competency']); ?>; --chart-color: <?php echo $chartColor; ?>"></div>
                                    <div class="chart-inner">
                                        <div class="chart-value" style="color: <?php echo $chartColor; ?>">
                                            <?php echo number_format($emp['competency'], 1); ?>%
                                        </div>
                                        <div class="chart-label">Competency</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Details -->
                            <div class="space-y-1 mb-6">
                                <div class="card-detail-item">
                                    <i data-lucide="briefcase" class="w-4 h-4 text-gray-400"></i>
                                    <span class="text-sm text-gray-600 flex-1">Position</span>
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['position']); ?></span>
                                </div>
                                <div class="card-detail-item">
    <i data-lucide="building" class="w-4 h-4 text-gray-400"></i>
    <span class="text-sm text-gray-600 flex-1">Department</span>
    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['department'] ?? ''); ?></span>
</div>
                                <div class="card-detail-item">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-gray-400"></i>
                                    <span class="text-sm text-gray-600 flex-1">Progress</span>
                                    <div class="w-24">
                                        <div class="progress-bar h-2 rounded-full overflow-hidden">
                                            <div class="progress-fill h-full <?php echo $progressColor; ?>" 
                                                 style="width: <?php echo min(100, $emp['competency']); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex gap-2">
                                <button class="btn flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800" 
                                        onclick="openViewModal('<?php echo $emp['employee_id']; ?>')"
                                        title="View Competency Details">
                                    <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                    View
                                </button>
                                <button class="btn flex-1 bg-gray-900 text-white hover:bg-gray-800 border-0 idp-btn" 
                                        data-employee-id="<?php echo $emp['employee_id']; ?>"
                                        data-employee-name="<?php echo htmlspecialchars($emp['full_name']); ?>"
                                        title="Create Individual Development Plan">
                                    <i data-lucide="clipboard-list" class="w-4 h-4 mr-2"></i>
                                    IDP
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4 border border-gray-300 mx-auto">
                            <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No employees found</h3>
                        <p class="text-gray-500">Try adjusting your filters or search term</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Count -->
        <div class="text-right mt-4 text-sm text-gray-600">
            Total: <span class="font-medium"><?php echo count($employees); ?></span> employees
        </div>
    </div>

    <!-- ============================================
         MODALS SECTION
    ============================================ -->

    <!-- View Details Modal -->
    <dialog id="view-modal" class="modal modal-lg">
        <div class="modal-box bg-white border border-gray-300 p-0 max-w-5xl">
            <div class="p-6 border-b border-gray-300 bg-gray-50">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                            <i data-lucide="bar-chart" class="w-5 h-5 text-gray-600"></i>
                            Competency Breakdown & Skill Assessment
                        </h3>
                        <p class="text-sm text-gray-600 mt-1" id="employee-subtitle"></p>
                    </div>
                    <button onclick="document.getElementById('view-modal').close()" 
                            class="btn btn-sm btn-circle bg-transparent border-0 hover:bg-gray-200 text-gray-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div id="employee-details-content">
                    <div class="text-center py-12">
                        <div class="loading loading-spinner loading-lg text-gray-600"></div>
                        <p class="mt-4 text-gray-600">Loading competency assessment...</p>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Legend Modal -->
    <dialog id="legend-modal" class="modal">
        <div class="modal-box bg-white border border-gray-300 p-0 max-w-md">
            <div class="p-6 border-b border-gray-300">
                <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                    <i data-lucide="info" class="w-5 h-5 text-gray-600"></i>
                    Proficiency Level Legend
                </h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                    <div class="w-3 h-3 rounded-full bg-red-500 mt-1 mr-3 flex-shrink-0"></div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Reskilling (0% - 30%)</h4>
                        <p class="text-sm text-gray-600 mt-1">Employees requiring fundamental skill development and retraining.</p>
                    </div>
                </div>
                
                <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                    <div class="w-3 h-3 rounded-full bg-amber-500 mt-1 mr-3 flex-shrink-0"></div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Retain (31% - 60%)</h4>
                        <p class="text-sm text-gray-600 mt-1">Employees with basic competency, needs development in key areas.</p>
                    </div>
                </div>
                
                <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                    <div class="w-3 h-3 rounded-full bg-blue-500 mt-1 mr-3 flex-shrink-0"></div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Upskilling (61% - 85%)</h4>
                        <p class="text-sm text-gray-600 mt-1">Competent employees ready for advanced skill development.</p>
                    </div>
                </div>
                
                <div class="flex items-start p-4 bg-white border border-gray-300 rounded-lg">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 mt-1 mr-3 flex-shrink-0"></div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Succession Ready (86% - 100%)</h4>
                        <p class="text-sm text-gray-600 mt-1">High performers ready for leadership roles and succession planning.</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-action p-6 border-t border-gray-300 bg-gray-50">
                <form method="dialog">
                    <button class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">Close</button>
                </form>
            </div>
        </div>
    </dialog>

    <!-- Success Toast -->
    <div id="success-toast" class="toast toast-top toast-end hidden">
        <div class="alert alert-success bg-emerald-50 border border-emerald-200 text-emerald-800">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span id="toast-message">Operation completed successfully!</span>
        </div>
    </div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Function to get status color class
    function getStatusClass(status) {
        return `status-${status.toLowerCase().replace(' ', '-')}`;
    }
    
    // Function to get progress bar color
    function getProgressColor(percentage) {
        percentage = parseFloat(percentage) || 0;
        if (percentage <= 30) return "bg-red-500";
        if (percentage <= 60) return "bg-amber-500";
        if (percentage <= 85) return "bg-blue-500";
        return "bg-emerald-500";
    }
    
    // Function to get status based on percentage
    function getStatus(percentage) {
        percentage = parseFloat(percentage) || 0;
        if (percentage <= 30) return "Reskilling";
        if (percentage <= 60) return "Retain";
        if (percentage <= 85) return "Upskilling";
        return "Succession Ready";
    }
    
    // Function to get skill category icon
    function getSkillCategoryIcon(category) {
        const icons = {
            'Technical': 'wrench',
            'Soft Skills': 'users',
            'Leadership': 'award',
            'Industry Knowledge': 'book-open',
            'Safety': 'shield'
        };
        return icons[category] || 'circle';
    }
    
    // Function to get skill score color
    function getSkillScoreColor(score) {
        score = parseFloat(score) || 0;
        if (score < 40) return 'text-red-600';
        if (score < 70) return 'text-amber-600';
        return 'text-emerald-600';
    }
    
    // ============================================
    // VIEW MODAL FUNCTIONS - FIXED
    // ============================================
    
    async function openViewModal(employeeId) {
        const modal = document.getElementById('view-modal');
        const content = document.getElementById('employee-details-content');
        const subtitle = document.getElementById('employee-subtitle');
        
        // Show loading state
        content.innerHTML = `
            <div class="text-center py-12">
                <div class="loading loading-spinner loading-lg text-gray-600"></div>
                <p class="mt-4 text-gray-600">Loading competency assessment...</p>
            </div>
        `;
        
        modal.showModal();
        
        try {
            // Fetch employee data
            const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
            const employee = await response.json();
            
            if (employee.error) {
                throw new Error(employee.error);
            }
            
            // Set subtitle
            subtitle.textContent = `${employee.full_name} | ${employee.position} | ${employee.department}`;
            
            // Calculate competency breakdown
            const competencyBreakdown = calculateCompetencyBreakdown(employee.skills || []);
            const lowSkills = (employee.skills || []).filter(skill => skill.skill_score < 40);
            const mediumSkills = (employee.skills || []).filter(skill => skill.skill_score >= 40 && skill.skill_score < 70);
            const highSkills = (employee.skills || []).filter(skill => skill.skill_score >= 70);
            
            // Get status and colors
            const statusClass = getStatusClass(employee.status);
            const progressColor = getProgressColor(employee.competency);
            
            // Populate modal content
            content.innerHTML = `
                <div class="space-y-6">
                    <!-- Employee Header -->
                    <div class="bg-gray-50 p-5 rounded-lg border border-gray-300">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">${employee.full_name}</h3>
                                        <div class="flex flex-wrap gap-3 mt-1">
                                            <span class="text-gray-700">${employee.employee_id}</span>
                                            <span class="text-gray-600">•</span>
                                            <span class="text-gray-700">${employee.position}</span>
                                            <span class="text-gray-600">•</span>
                                            <span class="text-gray-700">${employee.department}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Overall Competency Score</div>
                                <div class="text-3xl font-bold text-gray-900 mt-1">${parseFloat(employee.competency).toFixed(1)}%</div>
                                <div class="mt-2">
                                    <span class="status-badge ${statusClass}">${employee.status}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overall Score Breakdown -->
                    <div class="bg-white border border-gray-300 rounded-lg p-5">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-lucide="calculator" class="w-4 h-4"></i>
                            How the Competency Score is Calculated
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <!-- Technical Skills -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-900">Technical Skills</span>
                                    <span class="text-lg font-bold ${getSkillScoreColor(competencyBreakdown.technical)}">
                                        ${competencyBreakdown.technical}%
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Weight: 40% of total score
                                </div>
                                <div class="progress-bar h-2 rounded-full overflow-hidden mt-2">
                                    <div class="progress-fill h-full ${getProgressColor(competencyBreakdown.technical)}" 
                                         style="width: ${competencyBreakdown.technical}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-2">
                                    ${competencyBreakdown.technicalDetails.count} skills assessed
                                </div>
                            </div>
                            
                            <!-- Soft Skills -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-900">Soft Skills</span>
                                    <span class="text-lg font-bold ${getSkillScoreColor(competencyBreakdown.soft)}">
                                        ${competencyBreakdown.soft}%
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Weight: 30% of total score
                                </div>
                                <div class="progress-bar h-2 rounded-full overflow-hidden mt-2">
                                    <div class="progress-fill h-full ${getProgressColor(competencyBreakdown.soft)}" 
                                         style="width: ${competencyBreakdown.soft}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-2">
                                    ${competencyBreakdown.softDetails.count} skills assessed
                                </div>
                            </div>
                            
                            <!-- Leadership & Knowledge -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-900">Leadership & Knowledge</span>
                                    <span class="text-lg font-bold ${getSkillScoreColor(competencyBreakdown.other)}">
                                        ${competencyBreakdown.other}%
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Weight: 30% of total score
                                </div>
                                <div class="progress-bar h-2 rounded-full overflow-hidden mt-2">
                                    <div class="progress-fill h-full ${getProgressColor(competencyBreakdown.other)}" 
                                         style="width: ${competencyBreakdown.other}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-2">
                                    ${competencyBreakdown.otherDetails.count} skills assessed
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calculation Formula -->
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-300">
                            <div class="flex items-center gap-2 text-blue-800 mb-2">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span class="font-medium">Score Calculation Formula</span>
                            </div>
                            <div class="text-sm text-blue-700">
                                Total = (Technical × 0.40) + (Soft Skills × 0.30) + (Leadership & Knowledge × 0.30)
                            </div>
                            <div class="text-sm text-blue-700 mt-1">
                                = (${competencyBreakdown.technical} × 0.40) + (${competencyBreakdown.soft} × 0.30) + (${competencyBreakdown.other} × 0.30) = ${parseFloat(employee.competency).toFixed(1)}%
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Skill Assessment -->
                    <div class="bg-white border border-gray-300 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                <i data-lucide="list-checks" class="w-4 h-4"></i>
                                Detailed Skill Assessment
                            </h4>
                            <div class="flex gap-2">
                                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">
                                    ${lowSkills.length} Low Skills
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                                    ${mediumSkills.length} Medium Skills
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-800">
                                    ${highSkills.length} High Skills
                                </span>
                            </div>
                        </div>
                        
                        ${employee.skills && employee.skills.length > 0 ? `
                            <!-- Skill Breakdown Table -->
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-gray-700 py-3 px-4 border-b border-gray-300">Skill Category</th>
                                            <th class="text-gray-700 py-3 px-4 border-b border-gray-300">Skill Name</th>
                                            <th class="text-gray-700 py-3 px-4 border-b border-gray-300">Score</th>
                                            <th class="text-gray-700 py-3 px-4 border-b border-gray-300">Level</th>
                                            <th class="text-gray-700 py-3 px-4 border-b border-gray-300">Last Assessed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        ${employee.skills.map(skill => `
                                            <tr class="hover:bg-gray-50 ${skill.skill_score < 40 ? 'bg-red-50' : ''}">
                                                <td class="py-3 px-4 border-b border-gray-300">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="${getSkillCategoryIcon(skill.category)}" class="w-4 h-4 text-gray-500"></i>
                                                        <span class="text-gray-700">${skill.category}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 border-b border-gray-300">
                                                    <div class="font-medium text-gray-900">${skill.skill_name}</div>
                                                    <div class="text-xs text-gray-500 mt-1">${skill.description || 'No description'}</div>
                                                </td>
                                                <td class="py-3 px-4 border-b border-gray-300">
                                                    <div class="flex items-center gap-2">
                                                        <div class="text-lg font-bold ${getSkillScoreColor(skill.skill_score)}">
                                                            ${skill.skill_score}%
                                                        </div>
                                                        <div class="progress-bar w-20 h-2 rounded-full overflow-hidden">
                                                            <div class="progress-fill h-full ${getProgressColor(skill.skill_score)}" 
                                                                 style="width: ${skill.skill_score}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 border-b border-gray-300">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${skill.skill_score < 40 ? 'bg-red-100 text-red-800' : skill.skill_score < 70 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}">
                                                        ${skill.skill_score < 40 ? 'Low' : skill.skill_score < 70 ? 'Medium' : 'High'}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 border-b border-gray-300 text-sm text-gray-600">
                                                    ${skill.assessment_date ? new Date(skill.assessment_date).toLocaleDateString() : 'N/A'}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : `
                            <div class="text-center py-12 text-gray-500">
                                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i>
                                <p class="text-lg font-medium">No skill assessments available</p>
                                <p class="text-sm mt-2">This employee has not been assessed for specific skills yet.</p>
                            </div>
                        `}
                    </div>
                    
                    <!-- Skill Gaps Analysis -->
                    ${lowSkills.length > 0 ? `
                        <div class="bg-red-50 border border-red-300 rounded-lg p-5">
                            <h4 class="font-semibold text-red-900 mb-4 flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                Critical Skill Gaps Identified
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                ${lowSkills.map(skill => `
                                    <div class="bg-white p-4 rounded-lg border border-red-200">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h5 class="font-medium text-gray-900">${skill.skill_name}</h5>
                                                <p class="text-sm text-gray-600">${skill.category}</p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-red-600">${skill.skill_score}%</div>
                                                <div class="text-xs text-red-500">Critical Gap</div>
                                            </div>
                                        </div>
                                        <div class="progress-bar h-2 rounded-full overflow-hidden">
                                            <div class="progress-fill h-full bg-red-500" 
                                                 style="width: ${skill.skill_score}%"></div>
                                        </div>
                                        <button onclick="showIDPConfirmationForSkill('${employee.employee_id}', '${employee.full_name}', '${skill.skill_name}')" 
                                                class="btn btn-sm bg-red-100 text-red-800 border-red-300 hover:bg-red-200 w-full mt-3">
                                            <i data-lucide="clipboard-list" class="w-4 h-4 mr-2"></i>
                                            Create IDP for This Skill
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : employee.skills && employee.skills.length > 0 ? `
                        <div class="bg-emerald-50 border border-emerald-300 rounded-lg p-5">
                            <div class="flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
                                <div>
                                    <h4 class="font-semibold text-emerald-900">No Critical Skill Gaps</h4>
                                    <p class="text-emerald-700 text-sm mt-1">
                                        All skills are above 40% competency. Focus on maintaining skills and developing advanced capabilities.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-300">
                        <div class="text-sm text-gray-600">
                            Last assessment: ${employee.last_assessment ? new Date(employee.last_assessment).toLocaleDateString() : 'N/A'}
                            ${employee.next_review_date ? ` | Next review: ${new Date(employee.next_review_date).toLocaleDateString()}` : ''}
                        </div>
                        <div class="flex gap-2">
                            <button onclick="showIDPConfirmation('${employee.employee_id}', '${employee.full_name}')" 
                                    class="btn bg-gray-900 text-white hover:bg-gray-800 border-0">
                                <i data-lucide="clipboard-list" class="w-4 h-4 mr-2"></i>
                                Create IDP
                            </button>
                            <button onclick="document.getElementById('view-modal').close()" 
                                    class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
        } catch (error) {
            content.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4 border border-red-200">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Unable to Load Assessment</h3>
                    <p class="text-gray-500">${error.message}</p>
                    <button onclick="document.getElementById('view-modal').close()" 
                            class="btn bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 mt-4">
                        Close
                    </button>
                </div>
            `;
        }
        
        setTimeout(() => lucide.createIcons(), 100);
    }
    
    function calculateCompetencyBreakdown(skills) {
        let technical = { total: 0, count: 0 };
        let soft = { total: 0, count: 0 };
        let other = { total: 0, count: 0 };
        
        skills.forEach(skill => {
            if (skill.category === 'Technical' || skill.category === 'Safety') {
                technical.total += parseFloat(skill.skill_score) || 0;
                technical.count++;
            } else if (skill.category === 'Soft Skills') {
                soft.total += parseFloat(skill.skill_score) || 0;
                soft.count++;
            } else {
                other.total += parseFloat(skill.skill_score) || 0;
                other.count++;
            }
        });
        
        return {
            technical: technical.count > 0 ? Math.round(technical.total / technical.count) : 0,
            soft: soft.count > 0 ? Math.round(soft.total / soft.count) : 0,
            other: other.count > 0 ? Math.round(other.total / other.count) : 0,
            technicalDetails: technical,
            softDetails: soft,
            otherDetails: other
        };
    }
    
    // ============================================
    // IDP FUNCTIONS - FIXED
    // ============================================
    
    // Add event listeners to IDP buttons
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.idp-btn').forEach(button => {
            button.addEventListener('click', function() {
                const employeeId = this.getAttribute('data-employee-id');
                const employeeName = this.getAttribute('data-employee-name');
                showIDPConfirmation(employeeId, employeeName);
            });
        });
    });
    
    // Function to show IDP confirmation dialog
    function showIDPConfirmation(employeeId, employeeName) {
        Swal.fire({
            title: 'Create Individual Development Plan',
            html: `Do you want to create an Individual Development Plan for <strong>${employeeName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create IDP',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#1f2937',
            cancelButtonColor: '#6b7280',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                return await createIDP(employeeId, employeeName);
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Success!',
                    text: `IDP has been created for ${employeeName}`,
                    icon: 'success',
                    confirmButtonColor: '#1f2937'
                });
            }
        });
    }
    
    // Function to create IDP
    async function createIDP(employeeId, employeeName) {
        try {
            // Fetch employee data
            const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
            const employee = await response.json();
            
            if (employee.error) {
                throw new Error(employee.error);
            }
            
            // Prepare IDP data
            const idpData = {
                employee_id: employeeId,
                employee_name: employeeName,
                position: employee.position || '',
                department: employee.department || '',
                current_competency: parseFloat(employee.competency) || 0,
                status: employee.status || 'Retain',
                skills: employee.skills || [],
                created_date: new Date().toISOString().split('T')[0]
            };
            
            // Here you would normally save to database
            // For now, we'll just log it
            console.log('IDP Created:', idpData);
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            return true;
            
        } catch (error) {
            console.error("Error creating IDP:", error);
            Swal.fire({
                title: 'Error',
                text: `Error creating IDP: ${error.message}`,
                icon: 'error',
                confirmButtonColor: '#1f2937'
            });
            return false;
        }
    }
    
    // Function to show IDP confirmation for specific skill
    function showIDPConfirmationForSkill(employeeId, employeeName, skillName) {
        Swal.fire({
            title: 'Create Individual Development Plan',
            html: `Do you want to create an Individual Development Plan for <strong>${employeeName}</strong> focusing on <strong>${skillName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create IDP',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#1f2937',
            cancelButtonColor: '#6b7280',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                return await createIDPForSkill(employeeId, employeeName, skillName);
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Success!',
                    text: `IDP has been created for ${employeeName} focusing on ${skillName}`,
                    icon: 'success',
                    confirmButtonColor: '#1f2937'
                });
            }
        });
    }
    
    // Function to create IDP for specific skill
    async function createIDPForSkill(employeeId, employeeName, skillName) {
        try {
            // Fetch employee data
            const response = await fetch(`get_employee_details.php?id=${encodeURIComponent(employeeId)}`);
            const employee = await response.json();
            
            if (employee.error) {
                throw new Error(employee.error);
            }
            
            // Find the specific skill
            const skill = (employee.skills || []).find(s => s.skill_name === skillName);
            
            // Prepare IDP data with focus on specific skill
            const idpData = {
                employee_id: employeeId,
                employee_name: employeeName,
                position: employee.position || '',
                department: employee.department || '',
                current_competency: parseFloat(employee.competency) || 0,
                status: employee.status || 'Retain',
                focus_skill: skillName,
                focus_skill_score: skill ? skill.skill_score : 0,
                all_skills: employee.skills || [],
                created_date: new Date().toISOString().split('T')[0]
            };
            
            // Here you would normally save to database
            console.log('IDP with Focus Skill Created:', idpData);
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            return true;
            
        } catch (error) {
            console.error("Error creating IDP for skill:", error);
            Swal.fire({
                title: 'Error',
                text: `Error creating IDP: ${error.message}`,
                icon: 'error',
                confirmButtonColor: '#1f2937'
            });
            return false;
        }
    }
    
    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    
    // Close modals when clicking outside
    document.getElementById('view-modal').addEventListener('click', function(event) {
        if (event.target === this) this.close();
    });
    
    document.getElementById('legend-modal').addEventListener('click', function(event) {
        if (event.target === this) this.close();
    });
</script>
</body>
</html>