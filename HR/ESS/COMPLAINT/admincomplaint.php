<?php
// Start session and include database config
session_start();
require_once 'config/db.php';

// // Check if user is logged in as admin
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: login.php');
//     exit();
// }

// Get complaint statistics
$stats = $complaintModel->getComplaintStats();

// Get filter parameters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $filters['category_id'] = $_GET['category_id'];
}
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $filters['department'] = $_GET['department'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filters['limit'] = $limit;
$filters['offset'] = $offset;

// Get complaints
$complaints = $complaintModel->getComplaints($filters);
$categories = $complaintModel->getCategories();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_complaint':
                if (isset($_POST['id']) && isset($_POST['admin_notes']) && isset($_POST['status'])) {
                    $result = $complaintModel->updateComplaint(
                        $_POST['id'],
                        $_POST['admin_notes'],
                        $_POST['status'],
                        $_SESSION['user_id']
                    );
                    
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Complaint updated successfully';
                    } else {
                        $response['message'] = 'Failed to update complaint';
                    }
                }
                break;
                
            case 'delete_complaint':
                if (isset($_POST['id'])) {
                    $result = $complaintModel->deleteComplaint($_POST['id']);
                    
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Complaint deleted successfully';
                    } else {
                        $response['message'] = 'Failed to delete complaint';
                    }
                }
                break;
                
            case 'bulk_delete':
                if (isset($_POST['ids']) && is_array($_POST['ids'])) {
                    $successCount = 0;
                    foreach ($_POST['ids'] as $id) {
                        if ($complaintModel->deleteComplaint($id)) {
                            $successCount++;
                        }
                    }
                    $response['success'] = true;
                    $response['message'] = "Deleted $successCount complaint(s) successfully";
                }
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Stays - Admin Complaint Management</title>
    <!-- DaisyUI & Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #f1f5f9;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .scrollbar-thin {
            scrollbar-width: thin;
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        input, select, textarea {
            background-color: white !important;
        }
        
        .modal-box, .card {
            background-color: white;
        }
        
        .custom-checkbox {
            accent-color: #1e40af;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .action-menu {
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 50;
            min-width: 160px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            background-color: white;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.2s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
      
      <main class="container mx-auto px-4 py-6">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Complaint Management Dashboard</h1>
                <p class="text-gray-600 mt-1">Admin panel for managing employee complaints</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="badge badge-lg badge-primary gap-2">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                    Admin Access
                </div>
                <div class="text-sm text-gray-600">
                    <i data-lucide="user" class="w-4 h-4 inline mr-1"></i>
                    Welcome, <?php echo $_SESSION['user_name'] ?? 'Admin'; ?>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Complaints -->
            <div class="card bg-white shadow-sm border border-gray-200 card-hover">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Complaints</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['total'] ?? 0; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-50">
                            <i data-lucide="clipboard-list" class="w-8 h-8 text-blue-600"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-blue-600 text-sm font-medium flex items-center gap-1">
                            <i data-lucide="database" class="w-4 h-4"></i>
                            All time records
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pending -->
            <div class="card bg-white shadow-sm border border-gray-200 card-hover">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Pending Review</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['pending'] ?? 0; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-yellow-50">
                            <i data-lucide="clock" class="w-8 h-8 text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <?php 
                        $pendingPercent = $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100) : 0;
                        ?>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Percentage</span>
                            <span><?php echo $pendingPercent; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: <?php echo $pendingPercent; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Review -->
            <div class="card bg-white shadow-sm border border-gray-200 card-hover">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">In Review</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2">
                                <?php echo ($stats['under_review'] ?? 0) + ($stats['under_investigation'] ?? 0); ?>
                            </p>
                        </div>
                        <div class="p-3 rounded-full bg-purple-50">
                            <i data-lucide="search" class="w-8 h-8 text-purple-600"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <?php 
                        $reviewPercent = $stats['total'] > 0 ? 
                            round((($stats['under_review'] + $stats['under_investigation']) / $stats['total']) * 100) : 0;
                        ?>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Percentage</span>
                            <span><?php echo $reviewPercent; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $reviewPercent; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resolved -->
            <div class="card bg-white shadow-sm border border-gray-200 card-hover">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Resolved</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2">
                                <?php echo ($stats['resolved'] ?? 0) + ($stats['closed'] ?? 0); ?>
                            </p>
                        </div>
                        <div class="p-3 rounded-full bg-green-50">
                            <i data-lucide="check-circle" class="w-8 h-8 text-green-600"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <?php 
                        $resolvedPercent = $stats['total'] > 0 ? 
                            round((($stats['resolved'] + $stats['closed']) / $stats['total']) * 100) : 0;
                        ?>
                        <span class="text-green-600 text-sm font-medium flex items-center gap-1">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                            <?php echo $resolvedPercent; ?>% resolution rate
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card bg-white shadow-sm border border-gray-200 mb-6">
            <div class="card-body p-6">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Filter & Search Complaints</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        <span>Filter complaints by multiple criteria</span>
                    </div>
                </div>

                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Status Filter -->
                    <div class="form-control">
                        <label class="label py-1">
                            <span class="label-text font-medium text-gray-700">Status</span>
                        </label>
                        <select class="select select-bordered w-full bg-white" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo isset($_GET['status']) && $_GET['status'] == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="under_investigation" <?php echo isset($_GET['status']) && $_GET['status'] == 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                            <option value="resolved" <?php echo isset($_GET['status']) && $_GET['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo isset($_GET['status']) && $_GET['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="form-control">
                        <label class="label py-1">
                            <span class="label-text font-medium text-gray-700">Category</span>
                        </label>
                        <select class="select select-bordered w-full bg-white" name="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="form-control">
                        <label class="label py-1">
                            <span class="label-text font-medium text-gray-700">Department</span>
                        </label>
                        <select class="select select-bordered w-full bg-white" name="department">
                            <option value="">All Departments</option>
                            <option value="Front Desk" <?php echo isset($_GET['department']) && $_GET['department'] == 'Front Desk' ? 'selected' : ''; ?>>Front Desk</option>
                            <option value="Housekeeping" <?php echo isset($_GET['department']) && $_GET['department'] == 'Housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                            <option value="Restaurant" <?php echo isset($_GET['department']) && $_GET['department'] == 'Restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                            <option value="Kitchen" <?php echo isset($_GET['department']) && $_GET['department'] == 'Kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                            <option value="Maintenance" <?php echo isset($_GET['department']) && $_GET['department'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>

                    <!-- Search Bar -->
                    <div class="form-control">
                        <label class="label py-1">
                            <span class="label-text font-medium text-gray-700">Search</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search complaints..." 
                                   class="input input-bordered w-full pl-10 bg-white"
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <div class="absolute left-3 top-3">
                                <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 mt-6 lg:col-span-4">
                        <button type="submit" class="btn btn-primary btn-sm flex items-center gap-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            Apply Filters
                        </button>
                        <a href="?" class="btn btn-outline btn-sm flex items-center gap-2">
                            <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
                            Reset All
                        </a>
                        <button type="button" class="btn btn-outline btn-sm flex items-center gap-2" onclick="exportToCSV()">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            Export to CSV
                        </button>
                        <button type="button" class="btn btn-outline btn-sm flex items-center gap-2" onclick="showBulkActions()">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            Bulk Actions
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Complaint List Table -->
        <div class="card bg-white shadow-sm border border-gray-200">
            <div class="card-body p-0">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Complaint Records</h2>
                    <div class="flex items-center gap-4 mt-2 md:mt-0">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium"><?php echo count($complaints); ?></span> of 
                            <span class="font-medium"><?php echo $stats['total'] ?? 0; ?></span> complaints
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto scrollbar-thin">
                    <table class="table w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-gray-700 font-medium py-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox" id="selectAll">
                                        <span>Select</span>
                                    </label>
                                </th>
                                <th class="text-gray-700 font-medium">Complaint ID</th>
                                <th class="text-gray-700 font-medium">Category</th>
                                <th class="text-gray-700 font-medium">Date</th>
                                <th class="text-gray-700 font-medium">Employee</th>
                                <th class="text-gray-700 font-medium">Department</th>
                                <th class="text-gray-700 font-medium">Status</th>
                                <th class="text-gray-700 font-medium">Priority</th>
                                <th class="text-gray-700 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="complaintTableBody">
                            <?php foreach ($complaints as $complaint): ?>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            $priorityBadge = '';
                            
                            // Status styling
                            switch($complaint['status']) {
                                case 'pending':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusText = 'Pending';
                                    break;
                                case 'under_review':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    $statusText = 'Under Review';
                                    break;
                                case 'under_investigation':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    $statusText = 'Under Investigation';
                                    break;
                                case 'resolved':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Resolved';
                                    break;
                                case 'closed':
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusText = 'Closed';
                                    break;
                                default:
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusText = ucfirst(str_replace('_', ' ', $complaint['status']));
                            }
                            
                            // Priority badge
                            switch($complaint['priority']) {
                                case 'low':
                                    $priorityBadge = '<span class="badge badge-success badge-outline">Low</span>';
                                    break;
                                case 'medium':
                                    $priorityBadge = '<span class="badge badge-warning badge-outline">Medium</span>';
                                    break;
                                case 'high':
                                    $priorityBadge = '<span class="badge badge-error badge-outline">High</span>';
                                    break;
                                case 'critical':
                                    $priorityBadge = '<span class="badge badge-error">Critical</span>';
                                    break;
                                default:
                                    $priorityBadge = '<span class="badge">Unknown</span>';
                            }
                            ?>
                            <tr class="hover:bg-gray-50" data-id="<?php echo $complaint['id']; ?>">
                                <td class="py-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox complaint-checkbox" 
                                               data-id="<?php echo $complaint['id']; ?>">
                                    </label>
                                </td>
                                <td class="font-medium text-blue-600"><?php echo htmlspecialchars($complaint['complaint_code']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['category_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $employeeName = $complaint['employee_first_name'] . ' ' . $complaint['employee_last_name'];
                                    echo htmlspecialchars($employeeName);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($complaint['department']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td><?php echo $priorityBadge; ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button class="btn btn-ghost btn-xs flex items-center gap-1" 
                                                onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                            View
                                        </button>
                                        <button class="btn btn-ghost btn-xs flex items-center gap-1" 
                                                onclick="editComplaint(<?php echo $complaint['id']; ?>)">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                            Edit
                                        </button>
                                        <div class="dropdown dropdown-end">
                                            <button class="btn btn-ghost btn-xs">
                                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                            </button>
                                            <ul class="dropdown-content menu p-2 shadow bg-white rounded-box w-40 z-50">
                                                <li><a onclick="changeStatus(<?php echo $complaint['id']; ?>, 'under_review')">
                                                    <i data-lucide="search" class="w-4 h-4"></i> Mark In Review
                                                </a></li>
                                                <li><a onclick="changeStatus(<?php echo $complaint['id']; ?>, 'resolved')">
                                                    <i data-lucide="check-circle" class="w-4 h-4"></i> Mark Resolved
                                                </a></li>
                                                <li class="divider my-1"></li>
                                                <li><a class="text-red-600" onclick="deleteSingleComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($complaints)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-500">
                                    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300"></i>
                                    <p>No complaints found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($stats['total'] > $limit): ?>
                <div class="flex flex-col md:flex-row justify-between items-center p-6 border-t border-gray-200">
                    <div class="text-sm text-gray-600 mb-4 md:mb-0">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                        <span class="font-medium"><?php echo min($offset + $limit, $stats['total']); ?></span> of 
                        <span class="font-medium"><?php echo $stats['total']; ?></span> entries
                    </div>
                    <div class="join">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . $_GET['search'] : ''; ?>" 
                           class="join-item btn btn-sm btn-outline">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $totalPages = ceil($stats['total'] / $limit);
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . $_GET['search'] : ''; ?>" 
                           class="join-item btn btn-sm <?php echo $i == $page ? 'btn-active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . $_GET['search'] : ''; ?>" 
                           class="join-item btn btn-sm btn-outline">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Complaint Modal -->
    <dialog id="editComplaintModal" class="modal">
        <div class="modal-box max-w-2xl bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Edit Complaint</h3>
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>

            <form id="editComplaintForm" onsubmit="return updateComplaint()">
                <input type="hidden" id="editComplaintId">
                
                <!-- Status - Editable -->
                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text font-medium text-gray-700">Status</span>
                    </label>
                    <select id="editStatus" class="select select-bordered w-full bg-white" required>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="under_investigation">Under Investigation</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <!-- Admin Notes - Editable -->
                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text font-medium text-gray-700">Admin Notes (Internal Only)</span>
                    </label>
                    <textarea id="editAdminNotes" class="textarea textarea-bordered h-32 bg-white" 
                              placeholder="Add internal notes here..."></textarea>
                </div>

                <!-- Read-only fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium text-gray-700">Complaint ID</span>
                        </label>
                        <input type="text" id="editComplaintCode" class="input input-bordered w-full bg-white" readonly>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium text-gray-700">Category</span>
                        </label>
                        <input type="text" id="editCategory" class="input input-bordered w-full bg-white" readonly>
                    </div>
                </div>

                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text font-medium text-gray-700">Description</span>
                    </label>
                    <textarea id="editDescription" class="textarea textarea-bordered h-24 bg-white" readonly></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="modal-action mt-6">
                    <form method="dialog">
                        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                    </form>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteModal" class="modal">
        <div class="modal-box bg-white max-w-md">
            <div class="flex flex-col items-center text-center p-6">
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6" id="deleteMessage"></p>
                <div class="flex gap-3">
                    <form method="dialog">
                        <button class="btn btn-outline">Cancel</button>
                    </form>
                    <button class="btn btn-error" onclick="confirmDelete()">
                        <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- View Details Modal -->
    <dialog id="viewDetailsModal" class="modal">
        <div class="modal-box max-w-4xl bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Complaint Details</h3>
                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost btn-sm" onclick="printDetails()">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                    </button>
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div id="complaintDetails">
                <!-- Content will be dynamically populated -->
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Bulk Actions Modal -->
    <dialog id="bulkActionsModal" class="modal">
        <div class="modal-box bg-white max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Bulk Actions</h3>
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium text-gray-700">Action to Perform</span>
                    </label>
                    <select class="select select-bordered w-full bg-white" id="bulkActionSelect">
                        <option selected disabled>Select action</option>
                        <option value="status">Change status to...</option>
                        <option value="delete">Delete selected</option>
                    </select>
                </div>

                <div id="bulkActionOptions" class="space-y-4 hidden">
                    <!-- Options will be dynamically shown based on selection -->
                </div>

                <div class="text-sm text-gray-600 p-3 bg-gray-50 rounded">
                    <i data-lucide="info" class="w-4 h-4 inline mr-2"></i>
                    <span id="selectedCount">0</span> complaint(s) will be affected
                </div>
            </div>

            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-outline">Cancel</button>
                </form>
                <button class="btn btn-primary" onclick="performBulkAction()">Apply Action</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        let selectedComplaints = new Set();
        
        // Initialize the table
        function initializeTable() {
            // Add event listeners to checkboxes
            document.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleSelection(this.dataset.id, this.checked);
                });
            });
            
            // Select all checkbox
            document.getElementById('selectAll').addEventListener('change', function(e) {
                selectAll(e.target.checked);
            });
        }
        
        // Selection management
        function toggleSelection(id, checked) {
            if (checked) {
                selectedComplaints.add(id);
            } else {
                selectedComplaints.delete(id);
                document.getElementById('selectAll').checked = false;
            }
            updateSelectionCount();
        }
        
        function selectAll(checked) {
            const checkboxes = document.querySelectorAll('.complaint-checkbox');
            selectedComplaints.clear();
            
            if (checked) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    selectedComplaints.add(checkbox.dataset.id);
                });
            } else {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
            
            updateSelectionCount();
        }
        
        function updateSelectionCount() {
            const count = selectedComplaints.size;
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = count;
            }
            
            const deleteMessage = document.getElementById('deleteMessage');
            if (deleteMessage) {
                deleteMessage.textContent = `Are you sure you want to delete ${count} selected complaint(s)? This action cannot be undone.`;
            }
        }
        
        // Action functions
        async function viewComplaint(id) {
            try {
                const response = await fetch(`get_complaint.php?id=${id}`);
                const complaint = await response.json();
                
                if (complaint) {
                    const details = document.getElementById('complaintDetails');
                    details.innerHTML = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Complaint ID</h4>
                                        <p class="font-medium">${complaint.complaint_code}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Category</h4>
                                        <p class="font-medium">${complaint.category_name}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Date Submitted</h4>
                                        <p class="font-medium">${new Date(complaint.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Employee</h4>
                                        <p class="font-medium">${complaint.employee_first_name} ${complaint.employee_last_name}</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Department</h4>
                                        <p class="font-medium">${complaint.department}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Status</h4>
                                        <span class="status-badge ${getStatusClass(complaint.status)}">
                                            ${getStatusText(complaint.status)}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Priority</h4>
                                        ${getPriorityBadge(complaint.priority)}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Assigned To</h4>
                                        <p class="font-medium">${complaint.assigned_to ? complaint.assigned_first_name + ' ' + complaint.assigned_last_name : 'Unassigned'}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t pt-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-gray-700">${complaint.description}</p>
                                </div>
                            </div>
                            
                            ${complaint.admin_notes ? `
                            <div class="border-t pt-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Admin Notes</h4>
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <p class="text-gray-700">${complaint.admin_notes}</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="border-t pt-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-4">Incident Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h5 class="text-xs text-gray-500 mb-1">Incident Date</h5>
                                        <p class="font-medium">${complaint.incident_date ? new Date(complaint.incident_date).toLocaleDateString() : 'Not specified'}</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs text-gray-500 mb-1">Location</h5>
                                        <p class="font-medium">${complaint.location || 'Not specified'}</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs text-gray-500 mb-1">Confidential</h5>
                                        <p class="font-medium">${complaint.is_confidential ? 'Yes' : 'No'}</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs text-gray-500 mb-1">Anonymous</h5>
                                        <p class="font-medium">${complaint.is_anonymous ? 'Yes' : 'No'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('viewDetailsModal').showModal();
                }
            } catch (error) {
                console.error('Error loading complaint:', error);
                showToast('Error loading complaint details', 'error');
            }
        }
        
        async function editComplaint(id) {
            try {
                const response = await fetch(`get_complaint.php?id=${id}`);
                const complaint = await response.json();
                
                if (complaint) {
                    document.getElementById('editComplaintId').value = complaint.id;
                    document.getElementById('editComplaintCode').value = complaint.complaint_code;
                    document.getElementById('editCategory').value = complaint.category_name;
                    document.getElementById('editDescription').value = complaint.description;
                    document.getElementById('editStatus').value = complaint.status;
                    document.getElementById('editAdminNotes').value = complaint.admin_notes || '';
                    
                    document.getElementById('editComplaintModal').showModal();
                }
            } catch (error) {
                console.error('Error loading complaint for edit:', error);
                showToast('Error loading complaint', 'error');
            }
        }
        
        async function updateComplaint() {
            const id = document.getElementById('editComplaintId').value;
            const admin_notes = document.getElementById('editAdminNotes').value;
            const status = document.getElementById('editStatus').value;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_complaint&id=${id}&admin_notes=${encodeURIComponent(admin_notes)}&status=${status}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    document.getElementById('editComplaintModal').close();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating complaint:', error);
                showToast('Error updating complaint', 'error');
            }
            
            return false;
        }
        
        function deleteSingleComplaint(id) {
            selectedComplaints.clear();
            selectedComplaints.add(id);
            updateSelectionCount();
            document.getElementById('deleteModal').showModal();
        }
        
        function showBulkDelete() {
            if (selectedComplaints.size === 0) {
                showToast('Please select at least one complaint to delete', 'error');
                return;
            }
            document.getElementById('deleteModal').showModal();
        }
        
        async function confirmDelete() {
            try {
                const ids = Array.from(selectedComplaints);
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=bulk_delete&ids=${JSON.stringify(ids)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    document.getElementById('deleteModal').close();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting complaints:', error);
                showToast('Error deleting complaints', 'error');
            }
        }
        
        async function changeStatus(id, status) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_complaint&id=${id}&admin_notes=&status=${status}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error changing status:', error);
                showToast('Error changing status', 'error');
            }
        }
        
        // Filter functions
        function applyFilters() {
            // Handled by PHP form submission
        }
        
        function resetFilters() {
            window.location.href = '?';
        }
        
        function exportToCSV() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `export_complaints.php?${params.toString()}`;
        }
        
        // Bulk actions
        function showBulkActions() {
            if (selectedComplaints.size === 0) {
                showToast('Please select complaints to perform bulk actions', 'error');
                return;
            }
            
            const bulkActionSelect = document.getElementById('bulkActionSelect');
            bulkActionSelect.value = '';
            document.getElementById('bulkActionOptions').innerHTML = '';
            document.getElementById('bulkActionOptions').classList.add('hidden');
            
            document.getElementById('bulkActionsModal').showModal();
        }
        
        function onBulkActionChange() {
            const select = document.getElementById('bulkActionSelect');
            const optionsDiv = document.getElementById('bulkActionOptions');
            optionsDiv.classList.remove('hidden');
            
            let optionsHTML = '';
            
            switch(select.value) {
                case 'status':
                    optionsHTML = `
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium text-gray-700">New Status</span>
                            </label>
                            <select class="select select-bordered w-full bg-white" id="bulkStatusSelect">
                                <option value="under_review">Under Review</option>
                                <option value="under_investigation">Under Investigation</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'delete':
                    optionsHTML = `
                        <div class="alert alert-warning">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                            <span>This will permanently delete ${selectedComplaints.size} complaint(s)</span>
                        </div>
                    `;
                    break;
            }
            
            optionsDiv.innerHTML = optionsHTML;
            lucide.createIcons();
        }
        
        async function performBulkAction() {
            const action = document.getElementById('bulkActionSelect').value;
            
            if (action === 'delete') {
                document.getElementById('bulkActionsModal').close();
                document.getElementById('deleteModal').showModal();
                return;
            }
            
            if (action === 'status') {
                const status = document.getElementById('bulkStatusSelect').value;
                const ids = Array.from(selectedComplaints);
                
                try {
                    // Update each complaint
                    let successCount = 0;
                    for (const id of ids) {
                        const response = await fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update_complaint&id=${id}&admin_notes=&status=${status}`
                        });
                        
                        const result = await response.json();
                        if (result.success) successCount++;
                    }
                    
                    showToast(`Updated status for ${successCount} complaint(s)`, 'success');
                    document.getElementById('bulkActionsModal').close();
                    setTimeout(() => location.reload(), 1000);
                } catch (error) {
                    console.error('Error performing bulk action:', error);
                    showToast('Error performing bulk action', 'error');
                }
            }
        }
        
        // Helper functions
        function getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'under_review': 'bg-blue-100 text-blue-800',
                'under_investigation': 'bg-purple-100 text-purple-800',
                'resolved': 'bg-green-100 text-green-800',
                'closed': 'bg-gray-100 text-gray-800',
                'rejected': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
        
        function getStatusText(status) {
            const texts = {
                'pending': 'Pending',
                'under_review': 'Under Review',
                'under_investigation': 'Under Investigation',
                'resolved': 'Resolved',
                'closed': 'Closed',
                'rejected': 'Rejected'
            };
            return texts[status] || status.replace('_', ' ');
        }
        
        function getPriorityBadge(priority) {
            const badges = {
                'low': '<span class="badge badge-success badge-outline">Low</span>',
                'medium': '<span class="badge badge-warning badge-outline">Medium</span>',
                'high': '<span class="badge badge-error badge-outline">High</span>',
                'critical': '<span class="badge badge-error">Critical</span>'
            };
            return badges[priority] || '<span class="badge">Unknown</span>';
        }
        
        function closeEditModal() {
            document.getElementById('editComplaintModal').close();
        }
        
        function printDetails() {
            window.print();
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-top toast-end`;
            toast.innerHTML = `
                <div class="alert ${type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info'} flex items-center gap-2">
                    <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-5 h-5"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            lucide.createIcons();
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            initializeTable();
            
            // Add bulk delete button to the UI
            const actionButtons = document.querySelector('.flex-wrap.gap-3');
            if (actionButtons) {
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'btn btn-outline btn-sm flex items-center gap-2 btn-error';
                deleteButton.innerHTML = `
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete Selected
                `;
                deleteButton.onclick = showBulkDelete;
                actionButtons.appendChild(deleteButton);
            }
            
            // Add bulk action select change listener
            const bulkActionSelect = document.getElementById('bulkActionSelect');
            if (bulkActionSelect) {
                bulkActionSelect.addEventListener('change', onBulkActionChange);
            }
            
            // Initialize icons
            lucide.createIcons();
        });
    </script>
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>
