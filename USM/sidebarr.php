<?php
$rawRole = strtolower(trim($_SESSION['role'] ?? 'guest'));
$roleAliases = [
    'training development officer' => 'trainer',
    'training dev officer' => 'trainer',
    'training_development_officer' => 'trainer',
    'training_dev_officer' => 'trainer',
    'learning officer' => 'trainer',
];
$role = $roleAliases[$rawRole] ?? $rawRole;
$permissions = include __DIR__ . '/role_permissions.php';
$allowed_modules = $permissions[$role] ?? [];
$is_supervisor = ($role === 'admin' || $role === 'supervisor');

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Define base path for consistent URL structure
// $base_url = getenv('APP_BASE_PATH') ?: '/hr2/'; // Set this to your base path if needed, e.g., '/restaurant-system'

// Check if function already exists before declaring
if (!function_exists('hasAccess')) {
    function hasAccess($module, $allowed_modules, $is_supervisor)
    {
        return $is_supervisor || in_array($module, $allowed_modules);
    }
}

if (!function_exists('sidebarActiveClass')) {
    function sidebarActiveClass($file, $currentPage)
    {
        return $currentPage === $file ? ' bg-blue-700' : '';
    }
}
// $base = '/hr2';
?>

<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full w-64 transition-all duration-300 ease-in-out shadow-xl transform -translate-x-full md:transform-none md:translate-x-0" id="sidebar">
    <!-- Sidebar Header -->
    <div class="flex flex-col sm:flex-row items-center justify-between px-4 mb-6 text-center">
        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
            <img id="sidebar-logo" src="/hr2/logofinal.png"
                alt="Logo"
                class="h-12 sm:h-16 md:h-20 w-auto max-w-full">
            <img id="sonly" src="/hr2/sonly.png"
                alt="Logo"
                class="hidden h-10 w-auto max-w-full">
        </h1>
    </div>

    <!-- Navigsdation Menu -->
    <div id="sidebar-scroll" class="flex-1 flex flex-col overflow-hidden hover:overflow-y-auto">
        <nav class="flex-1 px-2 space-y-1">
            <!-- DASHBOARD SECTION -->
            <?php if (hasAccess('dashboard', $allowed_modules, $is_supervisor)): ?>
                <a href="/../hr2/dashboard.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('dashboard.php', $currentPage); ?>">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="layout-dashboard" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text"> Dashboard</span>
                    </div>
                </a>
            <?php endif; ?>




            <!-- LEARNING MANAGEMENT SECTION (Training Development Officer) -->
            <?php if (hasAccess('learning_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Training Development Officer</p>
                </div>

                <a href="/../hr2/LEARNING/training_dev_officer/learning_module_repository.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="library" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Learning Module Repository</span>
                    </div>
                </a>

                <a href="/../hr2/LEARNING/training_dev_officer/posted_modules.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="upload" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Posted Learning Modules</span>
                    </div>
                </a>

                <a href="/../hr2/LEARNING/training_dev_officer/examination_repository.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="file-text" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Examination Repository</span>
                    </div>
                </a>

                <a href="/../hr2/LEARNING/training_dev_officer/posted_examinations.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="clipboard-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Posted Examinations</span>
                    </div>
                </a>

                <a href="/../hr2/LEARNING/training_dev_officer/exam_results.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="bar-chart" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Examination Results</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- TRAINING MANAGEMENT SECTION -->
            <?php if (hasAccess('training_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Training Management</p>
                </div>

                <a href="/../hr2/TRAINING/TRAINING/trainingprogram.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="file-edit" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Training Program</span>
                    </div>
                </a>

                <a href="/../hr2/TRAINING/TRAINING/posted_trainings.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="send" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Posted Trainings</span>
                    </div>
                </a>

                <a href="/../hr2/TRAINING/TRAINING/trainingrequest.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Training Requests</span>
                    </div>
                </a>

                <a href="/../hr2/TRAINING/TRAINING/learningrequest.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="monitor-play" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Learning Requests</span>
                    </div>
                </a>

                <a href="/../hr2/TRAINING/TRAINING/request_logs.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="send" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Department Request Logs</span>
                    </div>
                </a>
            <?php endif; ?>


            <!-- COMPETENCY MANAGEMENT SECTION -->
            <?php if (hasAccess('competency_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Competency Management</p>
                </div>

                <a href="/../hr2/COMPETENCY/criticalgaps/vacancies_request.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">VACANCY REQUEST</span>
                    </div>
                </a>

                <a href="/../hr2/COMPETENCY/criticalgaps/criticalgaps.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('criticalgaps.php', $currentPage); ?>">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Competency Profiles</span>
                    </div>
                </a>

                <a href="/../hr2/COMPETENCY/criticalgaps/gap_analysis.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="target" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Gap Analysis</span>
                    </div>
                </a>

                <a href="/../hr2/COMPETENCY/competecy_criteria.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="list-checks" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Competency Criteria</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- SUCCESSION PLANNING SECTION -->
            <?php if (hasAccess('succession_planning', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Succession Planning</p>
                </div>

                <a href="/../hr2/SUCCESSION/HR_director/succession_dashboard.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('succession_dashboard.php', $currentPage); ?>">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Succession Plans</span>
                    </div>
                </a>

                <a href="/../hr2/SUCCESSION/HR_director/requested_idps_repository.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('requested_idps_repository.php', $currentPage); ?>">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">IDP Requests</span>
                    </div>
                </a>

                <a href="/../hr2/SUCCESSION/HR_director/individual_development_plans.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('individual_development_plans.php', $currentPage); ?>">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">IDP Repository</span>
                    </div>
                </a>

                <a href="/../hr2/SUCCESSION/HR_director/pre-promotion-table.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group<?php echo sidebarActiveClass('pre-promotion-table.php', $currentPage); ?>">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="arrow-up-circle" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Pre-Promotion</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- APPROVALS SECTION (HR Managers) -->
            <?php if (hasAccess('approvals', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">HR Managers</p>
                </div>

                <a href="/../hr2/TRAINING/TRAINING/review.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="check-circle" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Training Approvals</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/approval.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="check-circle" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">ESS Approvals</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/adminleaverequest.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="check-circle" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Leave Request Approval</span>
                    </div>
                </a>

                <a href="/../hr2/TRAINING/TRAINING/request_logs.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="list-checks" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Department Request Logs</span>
                    </div>
                </a>

                <a href="/../hr2/LEARNING/hr_manager/review_dashboard.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="book-open-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Learning &amp; Examination Review</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- APPLICANT ASSESSMENT SECTION -->
            <?php if (hasAccess('applicant_assessment', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Applicants</p>
                </div>
                <a href="/../hr2/LEARNING/applicant/applicant_assessment.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="file-text" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text"> Applicant Assessment</span>
                    </div>
                </a>

            <?php endif; ?>

            <!-- ESS SECTION -->
            <?php if (hasAccess('employee_self_service', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Employee Self Service</p>
                </div>
                <a href="/../hr2/ESS/dashboard.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="layout-dashboard" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">ESS dashboard</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/profile_management.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="user" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Profile</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/social_recognition.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="award" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">My Achievements</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/paymenthistory.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="banknote" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Payroll</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/mytraining.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">My Training</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/mymodule.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="layers" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">My Modules</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/myexamination.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="clipboard-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">My Examination</span>
                    </div>
                </a>



                <a href="/../hr2/ESS/mydocuments.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="file-text" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">My Document</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/leaverequest.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="calendar" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Leave Request</span>
                    </div>
                </a>

                <a href="/../hr2/ESS/complaint.php" class="block">
                    <div class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="message-square-warning" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Complaint</span>
                    </div>
                </a>
            <?php endif; ?>


            <!-- USER MANAGEMENT SECTION (Admin Only) -->
            <?php if (hasAccess('user_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Administration</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="settings" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">System Management</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="USM/department_accounts.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#F7B32B]"></i>
                                Department Accounts
                            </span>
                        </a>
                        <a href="USM/department_logs.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="list" class="w-4 h-4 text-[#F7B32B]"></i>
                                Department Logs
                            </span>
                        </a>
                        <a href="USM/audit_trail&transaction.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="database" class="w-4 h-4 text-[#F7B32B]"></i>
                                Audit Trail & Transaction
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT SECTION -->
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Account</p>
            </div>
            <form action="/../hr2/USM/logout.php" method="POST" class="px-4 py-3">
                <button type="submit" class="flex items-center w-full text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Logout</span>
                </button>
            </form>
        </nav>
    </div>
</div>