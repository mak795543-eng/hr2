<?php
// // approval.php
// require_once 'config/session.php';
// require_once 'config/database.php';

// SessionManager::startSecureSession();
// SessionManager::requireRole(['admin', 'reviewer']);

// $database = Database::getInstance();
// $db = $database->getConnection();

// // Get filter parameters
// $status_filter = $_GET['status'] ?? 'all';
// $search = $_GET['search'] ?? '';
// $page = $_GET['page'] ?? 1;
// $limit = 20;
// $offset = ($page - 1) * $limit;

// // Build query with filters
// $query = "SELECT e.*, u.username as creator_name, u.full_name as creator_full_name,
//           COUNT(q.id) as question_count
//           FROM examinations e
//           LEFT JOIN users u ON e.created_by = u.id
//           LEFT JOIN questions q ON e.id = q.exam_id
//           WHERE 1=1";

// $params = [];
// $types = [];

// if ($status_filter !== 'all') {
//     $query .= " AND e.status = ?";
//     $params[] = $status_filter;
//     $types[] = 's';
// }

// if (!empty($search)) {
//     $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.module_name LIKE ? OR u.username LIKE ?)";
//     $searchTerm = "%$search%";
//     $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
//     $types = array_merge($types, ['s', 's', 's', 's']);
// }

// $query .= " GROUP BY e.id ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
// $params[] = $limit;
// $params[] = $offset;
// $types[] = 'i';
// $types[] = 'i';

// // Prepare and execute
// $stmt = $db->prepare($query);
// if ($params) {
//     $stmt->bind_param(implode('', $types), ...$params);
// }
// $stmt->execute();
// $result = $stmt->get_result();
// $examinations = $result->fetch_all(MYSQLI_ASSOC);

// // Get total count for pagination
// $countQuery = "SELECT COUNT(*) as total FROM examinations e WHERE 1=1";
// $countParams = [];
// $countTypes = [];

// if ($status_filter !== 'all') {
//     $countQuery .= " AND e.status = ?";
//     $countParams[] = $status_filter;
//     $countTypes[] = 's';
// }

// if (!empty($search)) {
//     $countQuery .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.module_name LIKE ?)";
//     $searchTerm = "%$search%";
//     $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm]);
//     $countTypes = array_merge($countTypes, ['s', 's', 's']);
// }

// $countStmt = $db->prepare($countQuery);
// if ($countParams) {
//     $countStmt->bind_param(implode('', $countTypes), ...$countParams);
// }
// $countStmt->execute();
// $countResult = $countStmt->get_result();
// $totalCount = $countResult->fetch_assoc()['total'];
// $totalPages = ceil($totalCount / $limit);

// Get statistics
// $statsQuery = "SELECT 
//     SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
//     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
//     SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
//     SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
//     SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
//     COUNT(*) as total_count
//     FROM examinations";
    
// $statsResult = $db->query($statsQuery);
// $stats = $statsResult->fetch_assoc();
// ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Portal - Exam Approval System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-draft { background-color: #dbeafe; color: #1e40af; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .status-posted { background-color: #e0e7ff; color: #3730a3; }
        .exam-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .exam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .exam-card.draft { border-left-color: #3b82f6; }
        .exam-card.pending { border-left-color: #f59e0b; }
        .exam-card.approved { border-left-color: #10b981; }
        .exam-card.rejected { border-left-color: #ef4444; }
        .exam-card.posted { border-left-color: #8b5cf6; }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        .filter-active {
            background-color: #3b82f6 !important;
            color: white !important;
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
            
            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="loading-overlay">
                <div class="flex flex-col items-center">
                    <div class="loading loading-spinner loading-lg text-primary mb-4"></div>
                    <p class="text-gray-700 font-medium">Processing...</p>
                </div>
            </div>
            
            <main class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Exam Approval Dashboard</h1>
                    <p class="text-gray-600 mt-2">Review, approve, or reject submitted examinations</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-8">
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3 mr-4">
                                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Total Exams</div>
                                    <div class="stat-value text-2xl font-bold"><?php echo $stats['total_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-yellow-100 p-3 mr-4">
                                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Pending</div>
                                    <div class="stat-value text-2xl font-bold text-yellow-600"><?php echo $stats['pending_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3 mr-4">
                                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Approved</div>
                                    <div class="stat-value text-2xl font-bold text-green-600"><?php echo $stats['approved_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-red-100 p-3 mr-4">
                                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Rejected</div>
                                    <div class="stat-value text-2xl font-bold text-red-600"><?php echo $stats['rejected_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3 mr-4">
                                    <i class="fas fa-paper-plane text-purple-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Posted</div>
                                    <div class="stat-value text-2xl font-bold text-purple-600"><?php echo $stats['posted_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow">
                        <div class="card-body p-4">
                            <div class="flex items-center">
                                <div class="rounded-full bg-gray-100 p-3 mr-4">
                                    <i class="fas fa-save text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="stat-title text-gray-600">Draft</div>
                                    <div class="stat-value text-2xl font-bold text-gray-600"><?php echo $stats['draft_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <div class="flex flex-col md:flex-row gap-6 justify-between items-start md:items-center">
                        <!-- Status Filters -->
                        <div class="flex flex-wrap gap-2">
                            <a href="?status=all" class="btn btn-sm <?php echo $status_filter == 'all' ? 'filter-active' : 'btn-ghost'; ?>">
                                All (<?php echo $stats['total_count'] ?? 0; ?>)
                            </a>
                            <a href="?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'filter-active' : 'btn-ghost'; ?>">
                                <i class="fas fa-clock mr-1"></i>
                                Pending (<?php echo $stats['pending_count'] ?? 0; ?>)
                            </a>
                            <a href="?status=approved" class="btn btn-sm <?php echo $status_filter == 'approved' ? 'filter-active' : 'btn-ghost'; ?>">
                                <i class="fas fa-check mr-1"></i>
                                Approved (<?php echo $stats['approved_count'] ?? 0; ?>)
                            </a>
                            <a href="?status=rejected" class="btn btn-sm <?php echo $status_filter == 'rejected' ? 'filter-active' : 'btn-ghost'; ?>">
                                <i class="fas fa-times mr-1"></i>
                                Rejected (<?php echo $stats['rejected_count'] ?? 0; ?>)
                            </a>
                            <a href="?status=posted" class="btn btn-sm <?php echo $status_filter == 'posted' ? 'filter-active' : 'btn-ghost'; ?>">
                                <i class="fas fa-paper-plane mr-1"></i>
                                Posted (<?php echo $stats['posted_count'] ?? 0; ?>)
                            </a>
                            <a href="?status=draft" class="btn btn-sm <?php echo $status_filter == 'draft' ? 'filter-active' : 'btn-ghost'; ?>">
                                <i class="fas fa-save mr-1"></i>
                                Draft (<?php echo $stats['draft_count'] ?? 0; ?>)
                            </a>
                        </div>
                        
                        <!-- Search and Actions -->
                        <div class="flex gap-3">
                            <form method="GET" class="flex gap-2">
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                <div class="join">
                                    <input type="text" name="search" placeholder="Search exams..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           class="input input-bordered input-sm join-item">
                                    <button type="submit" class="btn btn-sm btn-primary join-item">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="?status=<?php echo $status_filter; ?>" class="btn btn-sm btn-ghost join-item">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <button class="btn btn-sm btn-outline" onclick="refreshPage()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Examinations Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php if (empty($examinations)): ?>
                        <div class="col-span-2">
                            <div class="bg-white rounded-lg shadow p-12 text-center">
                                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No examinations found</h3>
                                <p class="text-gray-500 mb-4">There are no examinations matching your criteria.</p>
                                <a href="?" class="btn btn-primary">
                                    <i class="fas fa-redo mr-2"></i>
                                    Reset Filters
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($examinations as $exam): ?>
                            <div class="exam-card <?php echo $exam['status']; ?> bg-white rounded-lg shadow overflow-hidden">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="font-bold text-lg text-gray-800 truncate">
                                                <?php echo htmlspecialchars($exam['title']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-user mr-1"></i>
                                                <?php echo htmlspecialchars($exam['creator_full_name'] ?: $exam['creator_name']); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge status-<?php echo $exam['status']; ?>">
                                            <?php echo ucfirst($exam['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <p class="text-gray-600 text-sm line-clamp-2">
                                            <?php echo htmlspecialchars($exam['description'] ?: 'No description'); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-6">
                                        <div class="text-sm">
                                            <span class="text-gray-500">Module:</span>
                                            <span class="font-medium ml-1"><?php echo htmlspecialchars($exam['module_name']); ?></span>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-gray-500">Type:</span>
                                            <span class="font-medium ml-1"><?php echo ucfirst($exam['exam_type']); ?></span>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-gray-500">Questions:</span>
                                            <span class="font-medium ml-1"><?php echo $exam['question_count']; ?></span>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-gray-500">Points:</span>
                                            <span class="font-medium ml-1"><?php echo $exam['total_points']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('M d, Y', strtotime($exam['created_at'])); ?>
                                        </div>
                                        
                                        <div class="flex gap-2">
                                            <!-- View Details -->
                                            <button class="btn btn-xs btn-outline" onclick="viewExamDetails(<?php echo $exam['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- History -->
                                            <button class="btn btn-xs btn-outline" onclick="viewApprovalHistory(<?php echo $exam['id']; ?>)">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            
                                            <!-- Action Buttons based on status -->
                                            <?php if ($exam['status'] == 'pending'): ?>
                                                <button class="btn btn-xs btn-success" onclick="approveExam(<?php echo $exam['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-xs btn-error" onclick="rejectExam(<?php echo $exam['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($exam['status'] == 'approved'): ?>
                                                <button class="btn btn-xs btn-primary" onclick="postExam(<?php echo $exam['id']; ?>)">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <div class="join">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                               class="join-item btn <?php echo $i == $page ? 'btn-active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- View Exam Modal -->
    <div id="viewExamModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-4xl max-h-[80vh] overflow-y-auto">
            <h3 class="font-bold text-lg mb-4">Exam Details</h3>
            <div id="examDetailsContent"></div>
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeModal('viewExamModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Approval History Modal -->
    <div id="historyModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4">Approval History</h3>
            <div id="historyContent"></div>
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeModal('historyModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4" id="reviewModalTitle">Review Examination</h3>
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text font-semibold">Comments *</span>
                </label>
                <textarea id="reviewComments" class="textarea textarea-bordered h-24" 
                          placeholder="Enter your comments or feedback..." required></textarea>
            </div>
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeModal('reviewModal')">Cancel</button>
                <button class="btn btn-primary" id="submitReview">Submit</button>
            </div>
            <input type="hidden" id="currentExamId">
            <input type="hidden" id="reviewAction">
        </div>
    </div>
    
    <script>
        // Global variables
        let currentExamId = null;
        let currentAction = null;
        
        // Show loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        // Hide loading
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('modal-open');
        }
        
        // Refresh page
        function refreshPage() {
            window.location.reload();
        }
        
        // View exam details
        async function viewExamDetails(examId) {
            try {
                showLoading();
                const response = await fetch(`get_exam_details.php?id=${examId}`);
                const data = await response.json();
                
                if (data.success) {
                    const exam = data.exam;
                    let html = `
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-bold text-xl text-gray-800 mb-2">${exam.title}</h4>
                                <p class="text-gray-600">${exam.description || 'No description'}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="font-semibold">Type:</span>
                                    <span class="ml-2 badge ${exam.exam_type === 'applicant' ? 'badge-info' : 'badge-secondary'}">
                                        ${exam.exam_type.toUpperCase()}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-semibold">Module:</span>
                                    <span class="ml-2">${exam.module_name}</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Department:</span>
                                    <span class="ml-2">${exam.department}</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Roles:</span>
                                    <span class="ml-2">${exam.roles}</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Duration:</span>
                                    <span class="ml-2">${exam.duration_minutes} minutes</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Passing Score:</span>
                                    <span class="ml-2">${exam.passing_score}%</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Total Points:</span>
                                    <span class="ml-2">${exam.total_points}</span>
                                </div>
                                <div>
                                    <span class="font-semibold">Status:</span>
                                    <span class="ml-2 status-badge status-${exam.status}">
                                        ${exam.status.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="border-t pt-4">
                                <h5 class="font-bold text-lg mb-3">Questions (${data.questions.length})</h5>
                                <div class="space-y-4">
                    `;
                    
                    data.questions.forEach((q, index) => {
                        html += `
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-bold text-gray-800">Q${index + 1}. ${q.question_text}</span>
                                        <span class="badge badge-info ml-2">${q.points} pts</span>
                                    </div>
                                    <span class="badge badge-outline">${q.question_type}</span>
                                </div>
                        `;
                        
                        if (q.options && q.options.length > 0) {
                            html += `<div class="mt-3"><span class="font-semibold text-sm">Options:</span><div class="space-y-2 mt-1">`;
                            q.options.forEach(opt => {
                                const correct = opt.is_correct ? '✓' : '';
                                const other = opt.is_other_option ? ' (Other)' : '';
                                html += `
                                    <div class="flex items-center p-2 rounded ${opt.is_correct ? 'bg-green-50 border border-green-200' : 'bg-gray-50'}">
                                        <span class="${opt.is_correct ? 'text-green-600 font-semibold' : 'text-gray-700'}">
                                            ${opt.option_text}${other}
                                        </span>
                                        ${opt.is_correct ? '<span class="ml-2 text-green-600 font-bold">✓ Correct</span>' : ''}
                                    </div>
                                `;
                            });
                            html += `</div></div>`;
                        }
                        
                        if (q.correct_answers && q.correct_answers.length > 0) {
                            html += `<div class="mt-3 p-3 bg-blue-50 rounded border border-blue-200">
                                <span class="font-semibold text-sm">Correct Answer:</span>
                                <span class="ml-2 text-blue-700 font-medium">${q.correct_answers.join(', ')}</span>
                            </div>`;
                        }
                        
                        html += `</div>`;
                    });
                    
                    html += `</div></div></div>`;
                    document.getElementById('examDetailsContent').innerHTML = html;
                    document.getElementById('viewExamModal').classList.add('modal-open');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // View approval history
        async function viewApprovalHistory(examId) {
            try {
                showLoading();
                const response = await fetch(`get_approval_history.php?id=${examId}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="space-y-4">';
                    
                    if (data.history.length === 0) {
                        html += '<p class="text-gray-500 text-center py-4">No approval history found</p>';
                    } else {
                        data.history.forEach(record => {
                            const date = new Date(record.reviewed_at).toLocaleString();
                            const statusColors = {
                                'pending': 'border-yellow-500 bg-yellow-50',
                                'approved': 'border-green-500 bg-green-50',
                                'rejected': 'border-red-500 bg-red-50',
                                'posted': 'border-blue-500 bg-blue-50'
                            };
                            
                            html += `
                                <div class="border-l-4 pl-4 py-3 ${statusColors[record.new_status] || 'border-gray-300'}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-gray-800">${record.new_status.toUpperCase()}</div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <i class="fas fa-user mr-1"></i>${record.reviewer_name}
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-500">${date}</div>
                                    </div>
                                    ${record.comments ? `
                                        <div class="mt-2 p-2 bg-white rounded border text-sm">
                                            <strong>Comments:</strong> ${record.comments}
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                    }
                    
                    html += '</div>';
                    document.getElementById('historyContent').innerHTML = html;
                    document.getElementById('historyModal').classList.add('modal-open');
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Open review modal
        function openReviewModal(examId, action, title) {
            currentExamId = examId;
            currentAction = action;
            
            document.getElementById('currentExamId').value = examId;
            document.getElementById('reviewAction').value = action;
            document.getElementById('reviewModalTitle').textContent = title;
            document.getElementById('reviewComments').value = '';
            document.getElementById('reviewModal').classList.add('modal-open');
        }
        
        // Approve exam
        function approveExam(examId) {
            openReviewModal(examId, 'approve', 'Approve Examination');
        }
        
        // Reject exam
        function rejectExam(examId) {
            openReviewModal(examId, 'reject', 'Reject Examination');
        }
        
        // Post exam
        function postExam(examId) {
            openReviewModal(examId, 'post', 'Post Examination');
        }
        
        // Submit review
        document.getElementById('submitReview').addEventListener('click', async function() {
            const examId = document.getElementById('currentExamId').value;
            const action = document.getElementById('reviewAction').value;
            const comments = document.getElementById('reviewComments').value;
            
            if (!comments.trim()) {
                Swal.fire('Warning', 'Please enter comments', 'warning');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('update_exam_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        exam_id: examId,
                        action: action,
                        comments: comments
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal('reviewModal');
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    refreshPage();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                hideLoading();
            }
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('viewExamModal');
                closeModal('historyModal');
                closeModal('reviewModal');
            }
        });
    </script>
</body>
</html>