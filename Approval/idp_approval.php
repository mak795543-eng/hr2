<?php
require_once 'config.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$department_filter = $_GET['department'] ?? 'all';
$position_filter = $_GET['position'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query for pending requests
$query = "SELECT * FROM idp_approval WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($department_filter !== 'all') {
    $query .= " AND department = ?";
    $params[] = $department_filter;
    $types .= "s";
}

if ($position_filter !== 'all') {
    $query .= " AND position = ?";
    $params[] = $position_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (employee_name LIKE ? OR employee_id LIKE ? OR position LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

$query .= " ORDER BY submitted_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$summary_sql = "
    SELECT 
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'approved' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as approved_this_week,
        SUM(CASE WHEN status = 'declined' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as declined_this_week,
        SUM(CASE WHEN status = 'pending' AND WEEK(submitted_date) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as pending_this_week
    FROM idp_approval
";

$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();

// Get unique departments and positions for filters
$departments_sql = "SELECT DISTINCT department FROM idp_approval WHERE department IS NOT NULL ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = $departments_result->fetch_all(MYSQLI_ASSOC);

$positions_sql = "SELECT DISTINCT position FROM idp_approval WHERE position IS NOT NULL ORDER BY position";
$positions_result = $conn->query($positions_sql);
$positions = $positions_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDP Approval Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #ffffff;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .idp-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .idp-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid;
        }
        .dropdown-content {
            max-height: 300px;
            overflow-y: auto;
        }
        .progress {
            height: 0.375rem;
            border-radius: 0.25rem;
            background-color: #f3f4f6;
        }
        .progress::-webkit-progress-bar {
            background-color: #f3f4f6;
            border-radius: 0.25rem;
        }
        .filter-btn {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .filter-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        .modal-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 1.25rem;
            transition: all 0.2s ease;
        }
        .summary-card:hover {
            border-color: #d1d5db;
        }
        .readiness-legend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .legend-red { background-color: #ef4444; }
        .legend-yellow { background-color: #f59e0b; }
        .legend-blue { background-color: #3b82f6; }
        .legend-green { background-color: #10b981; }
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
            
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="container mx-auto px-4 py-8">
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">IDP Approval Dashboard</h1>
                            <p class="text-gray-600 mt-2">Review and approve Individual Development Plans</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="readiness-legend">
                                <div class="legend-dot legend-red"></div>
                                <span class="text-xs text-gray-600">Not Ready</span>
                                <div class="legend-dot legend-yellow ml-2"></div>
                                <span class="text-xs text-gray-600">Developing</span>
                                <div class="legend-dot legend-blue ml-2"></div>
                                <span class="text-xs text-gray-600">Stable</span>
                                <div class="legend-dot legend-green ml-2"></div>
                                <span class="text-xs text-gray-600">Ready</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <!-- Approved Card -->
                        <div class="summary-card">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $summary['approved_count'] ?? 0; ?></p>
                                    <p class="text-sm text-gray-600">Approved</p>
                                </div>
                            </div>
                            <p class="text-xs text-green-600 mt-2">+<?php echo $summary['approved_this_week'] ?? 0; ?> this week</p>
                        </div>

                        <!-- Declined Card -->
                        <div class="summary-card">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $summary['declined_count'] ?? 0; ?></p>
                                    <p class="text-sm text-gray-600">Declined</p>
                                </div>
                            </div>
                            <p class="text-xs text-red-600 mt-2">+<?php echo $summary['declined_this_week'] ?? 0; ?> this week</p>
                        </div>

                        <!-- Pending Card -->
                        <div class="summary-card">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $summary['pending_count'] ?? 0; ?></p>
                                    <p class="text-sm text-gray-600">Pending</p>
                                </div>
                            </div>
                            <p class="text-xs text-yellow-600 mt-2">+<?php echo $summary['pending_this_week'] ?? 0; ?> this week</p>
                        </div>

                        <!-- Total Requests Card -->
                        <div class="summary-card">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $summary['total_count'] ?? 0; ?></p>
                                    <p class="text-sm text-gray-600">Total Requests</p>
                                </div>
                            </div>
                            <p class="text-xs text-blue-600 mt-2">All time</p>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="mb-8">
                        <h2 class="text-lg font-medium text-gray-700 mb-4">Filter Requests</h2>
                        <div class="flex flex-wrap gap-3 items-center">
                            <!-- Status Filter -->
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="filter-btn">
                                    <i data-lucide="target" class="w-4 h-4"></i>
                                    <span><?php echo $status_filter === 'all' ? 'All Status' : ucfirst($status_filter); ?></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-white border border-gray-200 rounded-lg w-56 shadow-sm mt-2 p-2 z-10">
                                    <li><a href="?status=all&department=<?php echo $department_filter; ?>&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">All Status</a></li>
                                    <li><hr class="my-2 border-gray-100"></li>
                                    <li><a href="?status=pending&department=<?php echo $department_filter; ?>&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">Pending Requests</a></li>
                                    <li><a href="?status=approved&department=<?php echo $department_filter; ?>&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">Approved Requests</a></li>
                                    <li><a href="?status=declined&department=<?php echo $department_filter; ?>&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">Declined Requests</a></li>
                                </ul>
                            </div>

                            <!-- Department Filter -->
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="filter-btn">
                                    <i data-lucide="building" class="w-4 h-4"></i>
                                    <span><?php echo $department_filter === 'all' ? 'All Departments' : $department_filter; ?></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-white border border-gray-200 rounded-lg w-56 shadow-sm mt-2 p-2 z-10">
                                    <li><a href="?status=<?php echo $status_filter; ?>&department=all&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">All Departments</a></li>
                                    <li><hr class="my-2 border-gray-100"></li>
                                    <?php foreach ($departments as $dept): ?>
                                        <li><a href="?status=<?php echo $status_filter; ?>&department=<?php echo urlencode($dept['department']); ?>&position=<?php echo $position_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                               class="py-2 px-3 hover:bg-gray-50 rounded text-sm"><?php echo htmlspecialchars($dept['department']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Position Filter -->
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="filter-btn">
                                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                                    <span><?php echo $position_filter === 'all' ? 'All Positions' : $position_filter; ?></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-white border border-gray-200 rounded-lg w-56 shadow-sm mt-2 p-2 z-10">
                                    <li><a href="?status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&position=all&search=<?php echo urlencode($search); ?>" 
                                           class="py-2 px-3 hover:bg-gray-50 rounded text-sm">All Positions</a></li>
                                    <li><hr class="my-2 border-gray-100"></li>
                                    <?php foreach ($positions as $pos): ?>
                                        <li><a href="?status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&position=<?php echo urlencode($pos['position']); ?>&search=<?php echo urlencode($search); ?>" 
                                               class="py-2 px-3 hover:bg-gray-50 rounded text-sm"><?php echo htmlspecialchars($pos['position']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Search -->
                            <div class="relative flex-1 min-w-[200px]">
                                <input type="text" 
                                       id="searchInput" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, ID, or position..." 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       onkeypress="if(event.key === 'Enter') searchIDPs()">
                                <i data-lucide="search" class="absolute right-3 top-2.5 w-4 h-4 text-gray-400"></i>
                            </div>

                            <!-- Clear Filters -->
                            <button onclick="clearFilters()" class="filter-btn">
                                <i data-lucide="filter-x" class="w-4 h-4"></i>
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- IDP Request Cards -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-medium text-gray-700">
                                <?php echo $status_filter === 'all' ? 'All' : ucfirst($status_filter); ?> Requests 
                                <span class="text-gray-500 font-normal">(<?php echo count($requests); ?>)</span>
                            </h2>
                            <div class="text-sm text-gray-500">
                                Sorted by: <span class="font-medium text-gray-700">Date Submitted</span>
                            </div>
                        </div>

                        <?php if (count($requests) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($requests as $request): 
                                    // Determine badge color based on readiness status
                                    $badge_class = '';
                                    if (strpos($request['readiness_status'], 'Ready / Promotion Ready') !== false) {
                                        $badge_class = 'bg-green-50 text-green-700 border-green-200';
                                    } elseif (strpos($request['readiness_status'], 'Stable / Growth Track') !== false) {
                                        $badge_class = 'bg-blue-50 text-blue-700 border-blue-200';
                                    } elseif (strpos($request['readiness_status'], 'Developing / Partially Ready') !== false) {
                                        $badge_class = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                                    } else {
                                        $badge_class = 'bg-red-50 text-red-700 border-red-200';
                                    }
                                ?>
                                <div class="idp-card p-5" data-approval-id="<?php echo $request['id']; ?>">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="font-semibold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($request['employee_name']); ?></h3>
                                            <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($request['employee_id']); ?> • <?php echo htmlspecialchars($request['position']); ?></p>
                                            <div class="flex gap-2">
                                                <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded"><?php echo htmlspecialchars($request['department']); ?></span>
                                            </div>
                                        </div>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($request['readiness_status']); ?>
                                        </span>
                                    </div>

                                    <div class="space-y-4 mb-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-sm text-gray-600">Competency Level</span>
                                                <span class="text-sm font-medium text-gray-800"><?php echo $request['competency']; ?>%</span>
                                            </div>
                                            <progress class="progress <?php echo getProgressClass($request['competency']); ?>" 
                                                      value="<?php echo $request['competency']; ?>" max="100"></progress>
                                        </div>

                                        <div class="flex items-center text-sm text-gray-600">
                                            <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                            Submitted: <?php echo date('M d, Y', strtotime($request['submitted_date'])); ?>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 mb-1">Development Plan</p>
                                            <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($request['development_plan']); ?></p>
                                        </div>
                                    </div>

                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="flex gap-2">
                                        <button class="approve-btn btn btn-sm flex-1 border border-green-500 text-green-700 bg-white hover:bg-green-50">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            Approve
                                        </button>
                                        <button class="decline-btn btn btn-sm flex-1 border border-red-500 text-red-700 bg-white hover:bg-red-50">
                                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                                            Decline
                                        </button>
                                        <button onclick="openViewModal(<?php echo htmlspecialchars(json_encode($request)); ?>)" 
                                                class="view-btn btn btn-sm border border-gray-300 bg-white hover:bg-gray-50">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                            View
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center">
                                        <span class="status-badge <?php echo $request['status'] === 'approved' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                        <?php if ($request['approved_by']): ?>
                                        <p class="text-xs text-gray-500 mt-2">By: <?php echo htmlspecialchars($request['approved_by']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($request['decline_reason']): ?>
                                        <p class="text-xs text-gray-500 mt-1">Reason: <?php echo htmlspecialchars($request['decline_reason']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 bg-white border border-gray-300 rounded-lg">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4 border border-gray-300 mx-auto">
                                    <i data-lucide="inbox" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">No <?php echo $status_filter; ?> requests found</h3>
                                <p class="text-gray-500">Try adjusting your filters or check back later</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View IDP Details Modal -->
    <dialog id="viewModal" class="modal">
        <div class="modal-box max-w-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800">IDP Details</h3>
                <button onclick="document.getElementById('viewModal').close()" class="btn btn-sm btn-ghost">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </dialog>

    <!-- Decline Reason Modal -->
    <dialog id="declineModal" class="modal">
        <div class="modal-box max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800">Decline IDP Request</h3>
                <button onclick="document.getElementById('declineModal').close()" class="btn btn-sm btn-ghost">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1" id="declineEmployeeName"></p>
                    <p class="font-medium text-gray-800" id="declineEmployeeId"></p>
                </div>
                
                <div>
                    <label for="declineReason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Decline <span class="text-red-500">*</span>
                    </label>
                    <textarea id="declineReason" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                              placeholder="Please provide a reason for declining this IDP request..."></textarea>
                </div>
            </div>
            
            <div class="modal-action mt-8">
                <button onclick="document.getElementById('declineModal').close()" class="btn btn-sm border border-gray-300 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button onclick="submitDecline()" class="btn btn-sm bg-red-600 text-white hover:bg-red-700">
                    Submit Decline
                </button>
            </div>
        </div>
    </dialog>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        let currentApprovalId = null;
        let currentCard = null;

        // Function to get progress bar class based on competency
        function getProgressClass(competency) {
            if (competency >= 86) return 'progress-primary';
            if (competency >= 61) return 'progress-info';
            if (competency >= 31) return 'progress-warning';
            return 'progress-error';
        }

        // Function to open view modal
        function openViewModal(request) {
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('modalContent');
            
            // Determine badge class
            let badgeClass = '';
            if (request.readiness_status.includes('Ready / Promotion Ready')) {
                badgeClass = 'bg-green-50 text-green-700 border-green-200';
            } else if (request.readiness_status.includes('Stable / Growth Track')) {
                badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
            } else if (request.readiness_status.includes('Developing / Partially Ready')) {
                badgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-200';
            } else {
                badgeClass = 'bg-red-50 text-red-700 border-red-200';
            }
            
            content.innerHTML = `
                <div class="space-y-6">
                    <!-- Employee Info -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Employee Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Full Name</p>
                                <p class="font-medium">${request.employee_name}</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Employee ID</p>
                                <p class="font-medium">${request.employee_id}</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Position</p>
                                <p class="font-medium">${request.position}</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Department</p>
                                <p class="font-medium">${request.department}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Development Plan Details -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Development Plan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Plan Name</p>
                                <p class="font-medium">${request.development_plan}</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Submitted Date</p>
                                <p class="font-medium">${new Date(request.submitted_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status and Competency -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Status & Competency</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Readiness Status</p>
                                <span class="status-badge ${badgeClass}">${request.readiness_status}</span>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Competency Level</p>
                                <div class="flex items-center gap-3">
                                    <progress class="progress w-32 ${getProgressClass(request.competency)}" 
                                              value="${request.competency}" max="100"></progress>
                                    <span class="font-medium">${request.competency}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval History -->
                    ${request.status !== 'pending' ? `
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Approval History</h4>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium">Status: ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</p>
                                    ${request.approved_by ? `<p class="text-sm text-gray-600">By: ${request.approved_by}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    ${request.approved_at ? `<p class="text-sm text-gray-600">${new Date(request.approved_at).toLocaleDateString()}</p>` : ''}
                                </div>
                            </div>
                            ${request.decline_reason ? `
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-sm font-medium text-gray-700 mb-1">Decline Reason:</p>
                                    <p class="text-sm text-gray-600">${request.decline_reason}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    ` : ''}
                </div>

                <div class="modal-action mt-8">
                    <button onclick="document.getElementById('viewModal').close()" class="btn btn-sm border border-gray-300 bg-white hover:bg-gray-50">
                        Close
                    </button>
                    ${request.status === 'pending' ? `
                    <button onclick="showDeclineModal(${request.id}, '${request.employee_name}', '${request.employee_id}')" 
                            class="btn btn-sm border border-red-500 text-red-700 bg-white hover:bg-red-50">
                        Decline
                    </button>
                    <button onclick="approveRequest(${request.id})" 
                            class="btn btn-sm bg-green-600 text-white hover:bg-green-700">
                        Approve
                    </button>
                    ` : ''}
                </div>
            `;
            
            modal.showModal();
        }

        // Function to show decline modal
        function showDeclineModal(approvalId, employeeName, employeeId) {
            currentApprovalId = approvalId;
            document.getElementById('declineEmployeeName').textContent = employeeName;
            document.getElementById('declineEmployeeId').textContent = employeeId;
            document.getElementById('declineReason').value = '';
            document.getElementById('declineModal').showModal();
        }

        // Function to submit decline
        async function submitDecline() {
            const reason = document.getElementById('declineReason').value.trim();
            
            if (!reason) {
                Swal.fire({
                    title: 'Reason Required',
                    text: 'Please provide a reason for declining this request.',
                    icon: 'warning',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }
            
            try {
                const response = await fetch('update_approval_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        approval_id: currentApprovalId,
                        status: 'declined',
                        decline_reason: reason,
                        approved_by: 'HR Manager'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        title: 'Request Declined!',
                        text: 'IDP request has been declined successfully.',
                        icon: 'success',
                        confirmButtonColor: '#4f46e5',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Close modals
                    document.getElementById('declineModal').close();
                    document.getElementById('viewModal').close();
                    
                    // Reload the page after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to decline request: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }

        // Function to approve request
        async function approveRequest(approvalId) {
            Swal.fire({
                title: 'Approve IDP Request?',
                text: 'Are you sure you want to approve this IDP request?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('update_approval_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                approval_id: approvalId,
                                status: 'approved',
                                approved_by: 'HR Manager'
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            Swal.fire({
                                title: 'Approved!',
                                text: 'IDP request has been approved successfully.',
                                icon: 'success',
                                confirmButtonColor: '#4f46e5',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Close modal and reload page
                            document.getElementById('viewModal').close();
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to approve request: ' + error.message,
                            icon: 'error',
                            confirmButtonColor: '#4f46e5'
                        });
                    }
                }
            });
        }

        // Add event listeners to approve buttons
        document.querySelectorAll('.approve-btn').forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.idp-card');
                const approvalId = card.dataset.approvalId;
                const name = card.querySelector('h3').textContent;
                const id = card.querySelector('p.text-gray-500').textContent.split('•')[0].trim();
                
                Swal.fire({
                    title: 'Approve IDP Request?',
                    text: `Are you sure you want to approve the IDP request for ${name} (${id})?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('update_approval_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    approval_id: approvalId,
                                    status: 'approved',
                                    approved_by: 'HR Manager'
                                })
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                this.innerHTML = '<i data-lucide="check" class="w-4 h-4 mr-1"></i> Approved';
                                this.classList.remove('border-green-500', 'text-green-700', 'hover:bg-green-50');
                                this.classList.add('border-gray-300', 'text-gray-600', 'bg-gray-100');
                                this.disabled = true;
                                
                                // Also disable decline button
                                const declineBtn = card.querySelector('.decline-btn');
                                declineBtn.disabled = true;
                                
                                Swal.fire({
                                    title: 'Approved!',
                                    text: `IDP request for ${name} has been approved.`,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        } catch (error) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to approve request: ' + error.message,
                                icon: 'error',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    }
                });
            });
        });

        // Add event listeners to decline buttons
        document.querySelectorAll('.decline-btn').forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.idp-card');
                const approvalId = card.dataset.approvalId;
                const name = card.querySelector('h3').textContent;
                const id = card.querySelector('p.text-gray-500').textContent.split('•')[0].trim();
                
                currentApprovalId = approvalId;
                currentCard = card;
                
                document.getElementById('declineEmployeeName').textContent = name;
                document.getElementById('declineEmployeeId').textContent = id;
                document.getElementById('declineModal').showModal();
            });
        });

        // Function to search IDPs
        function searchIDPs() {
            const search = document.getElementById('searchInput').value;
            const url = new URL(window.location.href);
            url.searchParams.set('search', search);
            window.location.href = url.toString();
        }

        // Function to clear filters
        function clearFilters() {
            window.location.href = 'idp_approval.php';
        }

        // Enter key support for search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchIDPs();
            }
        });

    </script>
</body>
</html>

<?php
function getProgressClass($competency) {
    if ($competency >= 86) return 'progress-primary';
    if ($competency >= 61) return 'progress-info';
    if ($competency >= 31) return 'progress-warning';
    return 'progress-error';
}
?>