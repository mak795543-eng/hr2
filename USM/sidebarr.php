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

// Define base path for consistent URL structure
// $base_url = getenv('APP_BASE_PATH') ?: '/hr2/'; // Set this to your base path if needed, e.g., '/restaurant-system'

// Check if function already exists before declaring
if (!function_exists('hasAccess')) {
    // Function to check if user has access to a module
    function hasAccess($module, $allowed_modules, $is_supervisor)
    {
        return $is_supervisor || in_array($module, $allowed_modules);
    }
}
?>

<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full w-64 transition-all duration-300 ease-in-out shadow-xl transform -translate-x-full md:transform-none md:translate-x-0" id="sidebar">
    <!-- Sidebar Header -->
    <div class="flex flex-col sm:flex-row items-center justify-between px-4 mb-6 text-center">
        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
            <img id="sidebar-logo" src="logo/logofinal.png"
                alt="Logo"
                class="h-12 sm:h-16 md:h-20 w-auto max-w-full">
            <img id="sonly" src="logo/logofinal.png"
                alt="Logo"
                class="hidden h-10 w-auto max-w-full">
        </h1>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 flex flex-col overflow-hidden hover:overflow-y-auto">
        <nav class="flex-1 px-2 space-y-1">
            <!-- DASHBOARD SECTION -->
            <?php if (hasAccess('dashboard', $allowed_modules, $is_supervisor)): ?>
                <a href="dashboard.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
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

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="book" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Learning Management</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="learning_module_repository.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="library" class="w-4 h-4 text-[#F7B32B]"></i>
                                Learning Module Repository
                            </span>
                        </a>
                        <a href="posted_modules.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4 text-[#F7B32B]"></i>
                                Posted Learning Modules
                            </span>
                        </a>
                        <a href="examination_repository.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-[#F7B32B]"></i>
                                Examination Repository
                            </span>
                        </a>
                        <a href="posted_examinations.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="clipboard-check" class="w-4 h-4 text-[#F7B32B]"></i>
                                Posted Examinations
                            </span>
                        </a>
                        <a href="exam_results.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="bar-chart" class="w-4 h-4 text-[#F7B32B]"></i>
                                Examination Results
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TRAINING MANAGEMENT SECTION -->
            <?php if (hasAccess('training_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Training Management</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Training Management</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="/../TRAINING/TRAINING/trainingprogram.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="file-edit" class="w-4 h-4 text-[#F7B32B]"></i>
                                Training Program
                            </span>
                        </a>
                        <a href="/../TRAINING/TRAINING/posted_trainings.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="send" class="w-4 h-4 text-[#F7B32B]"></i>
                                Posted Trainings
                            </span>
                        </a>
                        <a href="/../TRAINING/TRAINING/trainingrequest.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="clipboard-list" class="w-4 h-4 text-[#F7B32B]"></i>
                                Training Requests
                            </span>
                        </a>
                        <a href="/../TRAINING/TRAINING/request_logs.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="send" class="w-4 h-4 text-[#F7B32B]"></i>
                                Department Request Logs
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>


            <!-- COMPETENCY MANAGEMENT SECTION -->
            <?php if (hasAccess('competency_management', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Competency Management</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="chart-line" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Competency</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="/../COMPETENCY/criticalgaps/vacancies_request.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-[#F7B32B]"></i> VACANCY REQUEST </span>
                        </a>

                        <a href="/../COMPETENCY/criticalgaps/criticalgaps.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-[#F7B32B]"></i>
                                Critical Gaps
                            </span>
                        </a>

                        <a href="/../COMPETENCY/criticalgaps/gap_analysis.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="target" class="w-4 h-4 text-[#F7B32B]"></i>
                                Gap Analysis
                            </span>
                        </a>

                        <a href="/../COMPETENCY/competecy_criteria.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="list-checks" class="w-4 h-4 text-[#F7B32B]"></i>
                                Competency Criteria
                            </span>
                        </a>

                    </div>
                </div>
            <?php endif; ?>

            <!-- SUCCESSION PLANNING SECTION -->
            <?php if (hasAccess('succession_planning', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Succession Planning</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="notebook-tabs" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Succession Planning</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="/../SUCCESSION/HR_director/succession_dashboard.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#F7B32B]"></i>
                                Succession Plans
                            </span>
                        </a>
                        <a href="/../SUCCESSION/HR_director/requested_idps_repository.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#F7B32B]"></i>
                                IDP Requests
                            </span>
                        </a>
                        <a href="/../SUCCESSION/HR_director/individual_development_plans.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#F7B32B]"></i>
                                IDP Repository
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- APPROVALS SECTION (HR Managers) -->
            <?php if (hasAccess('approvals', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">HR Managers</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="shield-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Approvals</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="TRAINING/TRAINING/review.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-[#F7B32B]"></i>
                                Training Approvals
                            </span>
                        </a>
                        <a href="ESS/approval.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-[#F7B32B]"></i>
                                ESS Document Approvals
                            </span>
                        </a>
                        <a href="TRAINING/TRAINING/request_logs.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="list-checks" class="w-4 h-4 text-[#F7B32B]"></i>
                                Department Request Logs
                            </span>
                        </a>
                        <a href="LEARNING/hr_manager/review_dashboard.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="book-open-check" class="w-4 h-4 text-[#F7B32B]"></i>
                                Learning & Examination Review
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- APPLICANT ASSESSMENT SECTION -->
            <?php if (hasAccess('applicant_assessment', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Applicants</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="clipboard-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Assessments</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="LEARNING/applicant/applicant_assessment.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-[#F7B32B]"></i>
                                Applicant Assessment
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ESS SECTION -->
            <?php if (hasAccess('employee_self_service', $allowed_modules, $is_supervisor)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider section-title">Employee Self Service</p>
                </div>

                <div class="collapse group">
                    <input type="checkbox" class="peer" />
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="user" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">Employee Self Service</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
                        <a href="ESS/dashboard.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#F7B32B]"></i>
                                Dashboard
                            </span>
                        </a>
                        <a href="ESS/profile_management.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="flag" class="w-4 h-4 text-[#F7B32B]"></i>
                                Profie
                            </span>
                        </a>
                        <a href="ESS/social_recognition.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="flag" class="w-4 h-4 text-[#F7B32B]"></i>
                                My Achievements
                            </span>
                        </a>
                        <a href="ESS/paymenthistory.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="receipt" class="w-4 h-4 text-[#F7B32B]"></i>
                                Payment History
                            </span>
                        </a>
                        <a href=".php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="receipt" class="w-4 h-4 text-[#F7B32B]"></i>
                                My Training
                            </span>
                        </a>
                        <a href=".php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="receipt" class="w-4 h-4 text-[#F7B32B]"></i>
                                My Modules
                            </span>
                        </a>
                        <a href="ESS/mydocuments.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="folder" class="w-4 h-4 text-[#F7B32B]"></i>
                                My Documents
                            </span>
                        </a>
                        <a href="ESS/submitdocument.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="history" class="w-4 h-4 text-[#F7B32B]"></i>
                                Submit document
                            </span>
                        </a>
                        <a href="ESS/leaverequest.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#F7B32B]"></i>
                                Leave Request
                            </span>
                        </a>
                        <a href="ESS/complaint.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#F7B32B]"></i>
                                Complaint
                            </span>
                        </a>
                    </div>
                </div>
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
            <form action="USM/logout.php" method="POST" class="px-4 py-3">
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