<?php
$role = $_SESSION['role'] ?? 'guest';
$permissions = include 'role_permissions.php';
$allowed_modules = $permissions[$role] ?? [];
$is_supervisor = ($role === 'admin');

// Define base path for consistent URL structure
$base_url = '/HR2/'; // Set this to your base path if needed, e.g., '/restaurant-system'

// Function to check if user has access to a module
function hasAccess($module, $allowed_modules, $is_supervisor) {
    return $is_supervisor || in_array($module, $allowed_modules);
}
?>

<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full transition-all duration-300 ease-in-out shadow-xl transform -translate-x-full md:transform-none md:translate-x-0" id="sidebar">
    <!-- Sidebar Header -->
   <div class="flex flex-col sm:flex-row items-center justify-between px-4 mb-6 text-center">
  <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
    <img src="<?php echo $base_url; ?>logo/logofinal.png" 
         alt="Logo" 
         class="h-12 sm:h-16 md:h-20 w-auto max-w-full">
  </h1>
</div>


    <!-- Navigation Menu -->
    <div class="flex-1 flex flex-col overflow-hidden hover:overflow-y-auto">
        <nav class="flex-1 px-2 space-y-1">
            <!-- DASHBOARD SECTION -->
            <?php if (hasAccess('dashboard', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>dashboard2.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Dashboard</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- MODULES SECTION -->
            <?php if (hasAccess('learning_training', $allowed_modules, $is_supervisor) || 
                      hasAccess('competency_management', $allowed_modules, $is_supervisor) || 
                      hasAccess('succession_planning', $allowed_modules, $is_supervisor)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Modules</p>
            </div>
            
            <!-- Learning & Training -->
            <?php if (hasAccess('learning_training', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>TRAINING/addtraining.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Add Training</span>
                </div>
            </a>
            
            <?php endif; ?>

              <!-- Learning & Training -->
            <?php if (hasAccess('learning_training', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>TRAINING/employee.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Assign Employees
                    </span>
                </div>
            </a>
<?php endif; ?>
             <!-- Learning & Training -->
            <?php if (hasAccess('learning_training', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>LEARNING/NEWLEARNING.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Creation of Exam</span>
                </div>
            </a>
            <?php endif; ?>
            <!-- Learning & Training -->
            <?php if (hasAccess('learning_training', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>LEARNING/examinationdraft.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Exam Drafts</span>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- Competency Management -->
            <?php if (hasAccess('competency_management', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>COMPETENCY/gapanalysis.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="bar-chart" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Gap Analysis</span>
                </div>
            </a>
             <a href="<?php echo $base_url; ?>COMPETENCY/neednirain.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="award" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">job descriptions</span>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- Succession Planning -->
            <?php if (hasAccess('succession_planning', $allowed_modules, $is_supervisor)): ?>
            <a href="<?php echo $base_url; ?>SUCCESSION/candidates.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Succession Planning</span>
                </div>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ESS SECTION -->
            <?php if (hasAccess('employee_self_service', $allowed_modules, $is_supervisor)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">ESS</p>
            </div>
            
            <div class="collapse group">
                <input type="checkbox" class="peer" /> 
                <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Employee Self Service</span>
                    </div>
                    <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                </div>
                <div class="collapse-content pl-14 pr-4 py-1 space-y-1"> 
                    <a href="<?php echo $base_url; ?>dashboard.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="user-cog" class="w-4 h-4 text-[#F7B32B]"></i>
                            Dashboard
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>DOCUMENT/profile.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="user-cog" class="w-4 h-4 text-[#F7B32B]"></i>
                            Profile
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>CLAIMS/newclaim.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="w-4 h-4 text-[#F7B32B]"></i>
                            Request Claims
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>DOCUMENT/document.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-[#F7B32B]"></i>
                            Document 
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>PERFORMANCE/myperformance.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-[#F7B32B]"></i>
                            Performance
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>PAYROLL/payslip.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="credit-card" class="w-4 h-4 text-[#F7B32B]"></i>
                            PaySlip
                        </span>
                    </a>
                     <a href="<?php echo $base_url; ?>LEAVE/leavereq.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-[#F7B32B]"></i>
                            Leave Request
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>TIMESHEET/Shifting.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-[#F7B32B]"></i>
                            Shifting
                        </span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- USER MANAGEMENT SECTION -->
            <?php if (hasAccess('user_management', $allowed_modules, $is_supervisor)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Administration</p>
            </div>
            
            <div class="collapse group">
                <input type="checkbox" class="peer" /> 
                <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="settings" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">User Management</span>
                    </div>
                    <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                </div>
                <div class="collapse-content pl-14 pr-4 py-1 space-y-1"> 
                    <a href="<?php echo $base_url; ?>USM/department_accounts.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="user-cog" class="w-4 h-4 text-[#F7B32B]"></i>
                            Department Accounts
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>USM/department_logs.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="w-4 h-4 text-[#F7B32B]"></i>
                            Department Logs
                        </span>
                    </a>
                    <a href="<?php echo $base_url; ?>USM/audit_trail&transaction.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                        <span class="flex items-center gap-2">
                            <i data-lucide="history" class="w-4 h-4 text-[#F7B32B]"></i>
                            Audit Trail & Transaction
                        </span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ACCOUNT SECTION -->
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Account</p>
            </div>
            <form action="<?php echo $base_url; ?>USM/logout.php" method="POST" class="px-4 py-3">
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