<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System - RBAC</title>
     <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
    <style>
        .btn {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn:hover {
            background-color: #e5e7eb;
            border-color: #9ca3af;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border-color: #2563eb;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
            border-color: #4b5563;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .card {
            border: 1px solid #e5e7eb;
            background: white;
        }
        .badge {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .stats {
            background: white;
            border: 1px solid #e5e7eb;
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
        
        <!-- Notification Container -->
        <div id="notificationContainer"></div>
    <!-- Navigation -->
    <div class="navbar bg-white shadow-sm sticky top-0 z-50 border-b">
        <div class="navbar-start">
            <a class="btn btn-ghost text-xl" href="#">
                <i class="fas fa-hotel mr-2"></i>
                HotelPro
            </a>
        </div>
        <div class="navbar-end">
            <div id="userNavSection" class="hidden">
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost flex items-center gap-2">
                        <div class="avatar">
                            <div class="w-8 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center">
                                <span id="userInitial">U</span>
                            </div>
                        </div>
                        <span id="userName">User</span>
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-white rounded-box z-[1] w-52 p-2 shadow border">
                        <li><a href="#home" onclick="showHome()"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="#exams" onclick="showExams()"><i class="fas fa-file-alt"></i> Examinations</a></li>
                        <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container mx-auto px-4 py-8">
        <!-- Registration Section -->
        <div id="registrationSection" class="max-w-4xl mx-auto">
            <!-- Department Overview - Moved to Top -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Hotel Departments Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Front Office</h4>
                            <p class="text-sm text-gray-600">6 roles including Manager, Receptionist, Concierge</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Housekeeping</h4>
                            <p class="text-sm text-gray-600">5 roles including Manager, Supervisor, Attendant</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Food & Beverage</h4>
                            <p class="text-sm text-gray-600">5 roles including Manager, Server, Bartender</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Kitchen</h4>
                            <p class="text-sm text-gray-600">6 roles including Chef, Cook, Baker</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Sales & Marketing</h4>
                            <p class="text-sm text-gray-600">6 roles including Manager, Coordinator, Executive</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Human Resources</h4>
                            <p class="text-sm text-gray-600">6 roles including Manager, Officer, Specialist</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Finance & Accounting</h4>
                            <p class="text-sm text-gray-600">6 roles including Manager, Accountant, Controller</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Engineering</h4>
                            <p class="text-sm text-gray-600">6 roles including Engineer, Technician, Electrician</p>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title text-lg font-medium">Security</h4>
                            <p class="text-sm text-gray-600">5 roles including Manager, Guard, Officer</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Hotel Management System</h1>
                <p class="text-gray-600">Role-Based Access Control for Hotel Departments</p>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-xl mb-6">Register / Login</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Registration Form -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">New User Registration</h3>
                            <form id="registrationForm" class="space-y-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Full Name</span>
                                    </label>
                                    <input type="text" id="regName" placeholder="Enter your full name" class="input input-bordered w-full" required>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Email</span>
                                    </label>
                                    <input type="email" id="regEmail" placeholder="you@example.com" class="input input-bordered w-full" required>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Password</span>
                                    </label>
                                    <input type="password" id="regPassword" placeholder="Create a password" class="input input-bordered w-full" required>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Department</span>
                                    </label>
                                    <select id="regDepartment" class="select select-bordered w-full" required onchange="updateRoleOptions()">
                                        <option value="" disabled selected>Select your department</option>
                                        <option value="Front Office">Front Office</option>
                                        <option value="Housekeeping">Housekeeping</option>
                                        <option value="Food & Beverage">Food & Beverage</option>
                                        <option value="Kitchen">Kitchen</option>
                                        <option value="Sales & Marketing">Sales & Marketing</option>
                                        <option value="Human Resources">Human Resources</option>
                                        <option value="Finance & Accounting">Finance & Accounting</option>
                                        <option value="Engineering & Maintenance">Engineering & Maintenance</option>
                                        <option value="Security">Security</option>
                                    </select>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Role</span>
                                    </label>
                                    <select id="regRole" class="select select-bordered w-full" required disabled>
                                        <option value="" disabled selected>Please select a department first</option>
                                    </select>
                                    <label class="label">
                                        <span class="label-text-alt text-gray-500" id="roleHelpText">Role selection is disabled until a department is selected</span>
                                    </label>
                                </div>
                                
                                <div class="form-control mt-6">
                                    <button type="submit" class="btn btn-primary">Register</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Login Form -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Existing User Login</h3>
                            <form id="loginForm" class="space-y-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Email</span>
                                    </label>
                                    <input type="email" id="loginEmail" placeholder="you@example.com" class="input input-bordered w-full" required>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Password</span>
                                    </label>
                                    <input type="password" id="loginPassword" placeholder="Enter your password" class="input input-bordered w-full" required>
                                </div>
                                
                                <div class="form-control mt-6">
                                    <button type="submit" class="btn btn-secondary">Login</button>
                                </div>
                            </form>
                            
                            <div class="divider">OR</div>
                            
                            <div class="text-center">
                                <p class="text-gray-600 mb-4">Use demo credentials to test:</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="card bg-gray-50">
                                        <div class="card-body p-4">
                                            <h4 class="font-semibold">Front Office Manager</h4>
                                            <p class="text-sm">Email: manager@hotel.com</p>
                                            <p class="text-sm">Password: demo123</p>
                                            <p class="text-xs mt-1 text-gray-500">Front Office Department</p>
                                        </div>
                                    </div>
                                    <div class="card bg-gray-50">
                                        <div class="card-body p-4">
                                            <h4 class="font-semibold">Housekeeping Staff</h4>
                                            <p class="text-sm">Email: staff@hotel.com</p>
                                            <p class="text-sm">Password: demo123</p>
                                            <p class="text-xs mt-1 text-gray-500">Housekeeping Department</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Home Page Section (Hidden by default) -->
        <div id="homeSection" class="hidden max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, <span id="homeUserName">User</span></h1>
                <div class="badge badge-lg" id="homeUserDeptRole">Department / Role</div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- User Info Card -->
                <div class="card shadow-sm lg:col-span-2">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Your Profile</h2>
                        
                        <div class="overflow-x-auto">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th class="font-semibold">Name</th>
                                        <td id="profileName">-</td>
                                    </tr>
                                    <tr>
                                        <th class="font-semibold">Email</th>
                                        <td id="profileEmail">-</td>
                                    </tr>
                                    <tr>
                                        <th class="font-semibold">Department</th>
                                        <td id="profileDepartment">-</td>
                                    </tr>
                                    <tr>
                                        <th class="font-semibold">Role</th>
                                        <td id="profileRole">-</td>
                                    </tr>
                                    <tr>
                                        <th class="font-semibold">Access Level</th>
                                        <td id="profileAccessLevel">-</td>
                                    </tr>
                                    <tr>
                                        <th class="font-semibold">Examination Access</th>
                                        <td id="profileExamAccess" class="font-semibold">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Quick Actions</h2>
                        <div class="space-y-4">
                            <button onclick="showExams()" class="btn btn-block justify-start">
                                <i class="fas fa-file-alt mr-2"></i>
                                View Examinations
                            </button>
                            <button onclick="checkExamAccess()" class="btn btn-block justify-start">
                                <i class="fas fa-clipboard-check mr-2"></i>
                                Check Exam Eligibility
                            </button>
                            <button onclick="showDepartmentInfo()" class="btn btn-block justify-start">
                                <i class="fas fa-building mr-2"></i>
                                Department Information
                            </button>
                            <button onclick="viewAllDepartments()" class="btn btn-block justify-start">
                                <i class="fas fa-list mr-2"></i>
                                View All Departments
                            </button>
                        </div>
                        
                        <!-- Access Level Indicator -->
                        <div class="mt-8 p-4 rounded-lg bg-gray-50 border">
                            <h3 class="font-semibold mb-2">Your Access Level</h3>
                            <div class="flex items-center">
                                <div class="radial-progress text-gray-600" id="accessLevelProgress" style="--value:70; --size:3.5rem; --thickness: 4px;">70%</div>
                                <div class="ml-4">
                                    <p id="accessLevelText" class="font-medium">Medium Access</p>
                                    <p class="text-sm text-gray-600">Based on your role and department</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Department Stats -->
                <div class="card shadow-sm lg:col-span-3">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">System Overview</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div class="stats shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">Total Departments</div>
                                    <div class="stat-value text-gray-800">9</div>
                                </div>
                            </div>
                            <div class="stats shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">Total Roles</div>
                                    <div class="stat-value text-gray-800">38</div>
                                </div>
                            </div>
                            <div class="stats shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">With Exam Access</div>
                                    <div class="stat-value text-gray-800">24</div>
                                </div>
                            </div>
                            <div class="stats shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">Managers</div>
                                    <div class="stat-value text-gray-800">12</div>
                                </div>
                            </div>
                            <div class="stats shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">Staff Members</div>
                                    <div class="stat-value text-gray-800">26</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Examinations Section (Hidden by default) -->
        <div id="examsSection" class="hidden max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Examinations</h1>
                <p class="text-gray-600">Available exams for your department and role</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Exam List -->
                <div class="card shadow-sm lg:col-span-2">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Available Examinations</h2>
                        <div id="examList" class="space-y-4">
                            <!-- Exams will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Exam Eligibility Info -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Exam Eligibility</h2>
                        <div class="space-y-4">
                            <div class="p-4 rounded-lg bg-gray-50 border" id="examEligibilityInfo">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                    <div>
                                        <h3 class="font-bold">You have exam access!</h3>
                                        <div class="text-xs text-gray-600">Based on your role and department</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 rounded-lg bg-gray-50 border">
                                <h3 class="font-semibold mb-2">Eligibility Criteria</h3>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                        <span>Your department must have examinations enabled</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                        <span>Your role must have examination permissions</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                        <span>You must be registered in the system</span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="p-4 rounded-lg bg-gray-100 border">
                                <h3 class="font-semibold mb-2">Exams by Department</h3>
                                <div class="text-sm">
                                    <p class="text-gray-700">Exams are available for:</p>
                                    <ul class="mt-2 space-y-1">
                                        <li class="text-gray-600">• Front Office</li>
                                        <li class="text-gray-600">• Housekeeping</li>
                                        <li class="text-gray-600">• Food & Beverage</li>
                                        <li class="text-gray-600">• Kitchen</li>
                                        <li class="text-gray-600">• Sales & Marketing</li>
                                        <li class="text-gray-600">• Human Resources</li>
                                        <li class="text-gray-600">• Finance & Accounting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Department to Role Mapping
        const departmentRoles = {
            "Front Office": [
                "Front Desk Manager",
                "Receptionist / Front Desk Officer", 
                "Guest Service Agent / Concierge",
                "Reservation Agent",
                "Bellhop / Porter",
                "Front Office Supervisor"
            ],
            "Housekeeping": [
                "Executive Housekeeper / Housekeeping Manager",
                "Floor Supervisor",
                "Room Attendant / Housekeeper",
                "Laundry Attendant",
                "Public Area Attendant",
                "Housekeeping Inspector"
            ],
            "Food & Beverage": [
                "F&B Manager / Director",
                "Restaurant Manager / Captain",
                "Waiter / Waitress / Server",
                "Bartender",
                "Banquet / Catering Coordinator",
                "F&B Supervisor"
            ],
            "Kitchen": [
                "Executive Chef / Head Chef",
                "Sous Chef",
                "Line Cook / Station Chef",
                "Pastry Chef / Baker",
                "Kitchen Steward / Dishwasher",
                "Commis Chef"
            ],
            "Sales & Marketing": [
                "Sales & Marketing Manager",
                "Revenue Manager",
                "Event / Banquet Sales Coordinator",
                "Social Media / Marketing Executive",
                "Sales Executive",
                "Marketing Coordinator"
            ],
            "Human Resources": [
                "HR Manager / Director",
                "Recruitment Officer",
                "Training & Development Specialist",
                "Payroll / HR Assistant",
                "HR Coordinator",
                "Employee Relations Specialist"
            ],
            "Finance & Accounting": [
                "Finance Manager / Controller",
                "Accountant",
                "Payroll Officer",
                "Cost Controller",
                "Accounts Payable/Receivable Clerk",
                "Financial Analyst"
            ],
            "Engineering & Maintenance": [
                "Chief Engineer / Engineering Manager",
                "Maintenance Technician",
                "Electrician / Plumber",
                "HVAC Technician",
                "Carpenter",
                "Painter"
            ],
            "Security": [
                "Security Manager / Supervisor",
                "Security Guard",
                "Security Officer",
                "Surveillance Operator",
                "Access Control Officer"
            ]
        };

        // Departments with exam access
        const departmentsWithExams = [
            "Front Office",
            "Housekeeping",
            "Food & Beverage",
            "Kitchen",
            "Sales & Marketing",
            "Human Resources",
            "Finance & Accounting"
        ];

        // Sample user data for demo
        const sampleUsers = [
            {
                name: "John Smith",
                email: "manager@hotel.com",
                password: "demo123",
                department: "Front Office",
                role: "Front Desk Manager",
                accessLevel: "high"
            },
            {
                name: "Maria Garcia",
                email: "staff@hotel.com",
                password: "demo123",
                department: "Housekeeping",
                role: "Room Attendant / Housekeeper",
                accessLevel: "medium"
            },
            {
                name: "Robert Chen",
                email: "chef@hotel.com",
                password: "demo123",
                department: "Kitchen",
                role: "Executive Chef / Head Chef",
                accessLevel: "high"
            }
        ];

        // Available exams
        const exams = [
            { id: 1, title: "Front Office Operations", department: "Front Office", duration: "60 mins", questions: 50 },
            { id: 2, title: "Housekeeping Standards", department: "Housekeeping", duration: "45 mins", questions: 40 },
            { id: 3, title: "Food Safety & Hygiene", department: "Food & Beverage", duration: "90 mins", questions: 75 },
            { id: 4, title: "Culinary Skills Test", department: "Kitchen", duration: "120 mins", questions: 100 },
            { id: 5, title: "Sales Techniques", department: "Sales & Marketing", duration: "60 mins", questions: 50 },
            { id: 6, title: "HR Policies & Procedures", department: "Human Resources", duration: "75 mins", questions: 60 },
            { id: 7, title: "Financial Management", department: "Finance & Accounting", duration: "90 mins", questions: 70 }
        ];

        // Current user
        let currentUser = null;
        let users = JSON.parse(localStorage.getItem('hotelUsers')) || [...sampleUsers];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is already logged in
            const savedUser = localStorage.getItem('currentUser');
            if (savedUser) {
                currentUser = JSON.parse(savedUser);
                showHome();
            }
            
            // Setup form submissions
            document.getElementById('registrationForm').addEventListener('submit', handleRegistration);
            document.getElementById('loginForm').addEventListener('submit', handleLogin);
            
            // Initialize role dropdown as disabled
            document.getElementById('regRole').disabled = true;
        });

        // Update role options based on selected department
        function updateRoleOptions() {
            const departmentSelect = document.getElementById('regDepartment');
            const roleSelect = document.getElementById('regRole');
            const roleHelpText = document.getElementById('roleHelpText');
            
            const selectedDepartment = departmentSelect.value;
            
            if (selectedDepartment && departmentRoles[selectedDepartment]) {
                // Enable role dropdown
                roleSelect.disabled = false;
                roleSelect.innerHTML = '<option value="" disabled selected>Select your role</option>';
                
                // Add role options for the selected department
                departmentRoles[selectedDepartment].forEach(role => {
                    const option = document.createElement('option');
                    option.value = role;
                    option.textContent = role;
                    roleSelect.appendChild(option);
                });
                
                roleHelpText.textContent = `Select a role for ${selectedDepartment} Department`;
                roleHelpText.className = "label-text-alt text-gray-600";
            } else {
                // Disable role dropdown
                roleSelect.disabled = true;
                roleSelect.innerHTML = '<option value="" disabled selected>Please select a department first</option>';
                roleHelpText.textContent = "Role selection is disabled until a department is selected";
                roleHelpText.className = "label-text-alt text-gray-500";
            }
        }

        // Handle registration
        function handleRegistration(e) {
            e.preventDefault();
            
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const department = document.getElementById('regDepartment').value;
            const role = document.getElementById('regRole').value;
            
            // Validate department and role
            if (!department || !role || role === "Please select a department first") {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select both department and role!',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Check if user already exists
            if (users.find(u => u.email === email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: 'User with this email already exists!',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Determine access level based on role
            let accessLevel = 'low';
            const roleLower = role.toLowerCase();
            if (roleLower.includes('manager') || roleLower.includes('director') || 
                roleLower.includes('chief') || roleLower.includes('executive') || 
                roleLower.includes('head')) {
                accessLevel = 'high';
            } else if (roleLower.includes('supervisor') || roleLower.includes('senior') || 
                      roleLower.includes('specialist') || roleLower.includes('coordinator') || 
                      roleLower.includes('captain') || roleLower.includes('officer')) {
                accessLevel = 'medium';
            }
            
            // Create new user
            const newUser = {
                name,
                email,
                password,
                department,
                role,
                accessLevel
            };
            
            users.push(newUser);
            localStorage.setItem('hotelUsers', JSON.stringify(users));
            
            // Auto login
            currentUser = newUser;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                text: 'Welcome to HotelPro Management System',
                confirmButtonColor: '#3b82f6'
            }).then(() => {
                showHome();
            });
        }

        // Handle login
        function handleLogin(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            // Find user
            const user = users.find(u => u.email === email && u.password === password);
            
            if (user) {
                currentUser = user;
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful!',
                    text: `Welcome back, ${user.name}`,
                    confirmButtonColor: '#3b82f6'
                }).then(() => {
                    showHome();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid email or password!',
                    confirmButtonColor: '#3b82f6'
                });
            }
        }

        // Show home section
        function showHome() {
            document.getElementById('registrationSection').classList.add('hidden');
            document.getElementById('homeSection').classList.remove('hidden');
            document.getElementById('examsSection').classList.add('hidden');
            document.getElementById('userNavSection').classList.remove('hidden');
            
            // Update user info
            if (currentUser) {
                document.getElementById('homeUserName').textContent = currentUser.name;
                document.getElementById('userName').textContent = currentUser.name;
                document.getElementById('userInitial').textContent = currentUser.name.charAt(0).toUpperCase();
                document.getElementById('homeUserDeptRole').textContent = `${currentUser.department} / ${currentUser.role}`;
                
                document.getElementById('profileName').textContent = currentUser.name;
                document.getElementById('profileEmail').textContent = currentUser.email;
                document.getElementById('profileDepartment').textContent = currentUser.department;
                document.getElementById('profileRole').textContent = currentUser.role;
                document.getElementById('profileAccessLevel').textContent = currentUser.accessLevel.charAt(0).toUpperCase() + currentUser.accessLevel.slice(1);
                
                // Check exam access
                const hasExamAccess = departmentsWithExams.includes(currentUser.department);
                document.getElementById('profileExamAccess').textContent = hasExamAccess ? 'Available' : 'Not Available';
                
                // Update access level progress
                let progressValue = 30;
                let accessText = "Low Access";
                if (currentUser.accessLevel === 'medium') {
                    progressValue = 70;
                    accessText = "Medium Access";
                } else if (currentUser.accessLevel === 'high') {
                    progressValue = 100;
                    accessText = "High Access";
                }
                
                document.getElementById('accessLevelProgress').style.setProperty('--value', progressValue);
                document.getElementById('accessLevelProgress').textContent = `${progressValue}%`;
                document.getElementById('accessLevelText').textContent = accessText;
            }
        }

        // Show exams section
        function showExams() {
            document.getElementById('registrationSection').classList.add('hidden');
            document.getElementById('homeSection').classList.add('hidden');
            document.getElementById('examsSection').classList.remove('hidden');
            
            // Check exam eligibility
            const hasExamAccess = departmentsWithExams.includes(currentUser.department);
            const examEligibilityInfo = document.getElementById('examEligibilityInfo');
            
            if (hasExamAccess) {
                examEligibilityInfo.innerHTML = `
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <div>
                            <h3 class="font-bold">You have exam access!</h3>
                            <div class="text-xs text-gray-600">Based on your role and department</div>
                        </div>
                    </div>
                `;
            } else {
                examEligibilityInfo.innerHTML = `
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-2"></i>
                        <div>
                            <h3 class="font-bold">No exam access</h3>
                            <div class="text-xs text-gray-600">Your department doesn't have examination permissions</div>
                        </div>
                    </div>
                `;
            }
            
            // Load exams
            const examList = document.getElementById('examList');
            examList.innerHTML = '';
            
            // Filter exams based on department
            const userDept = currentUser.department;
            let filteredExams = exams.filter(e => e.department === userDept);
            
            if (filteredExams.length > 0 && hasExamAccess) {
                filteredExams.forEach(exam => {
                    const examCard = document.createElement('div');
                    examCard.className = 'card shadow-sm';
                    examCard.innerHTML = `
                        <div class="card-body p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="card-title text-lg">${exam.title}</h3>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="badge badge-outline">${exam.duration}</span>
                                        <span class="badge badge-outline">${exam.questions} questions</span>
                                        <span class="badge">${exam.department}</span>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm" onclick="startExam(${exam.id})">
                                        Start Exam <i class="fas fa-arrow-right ml-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    examList.appendChild(examCard);
                });
            } else {
                examList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700">No exams available</h3>
                        <p class="text-gray-500 mt-2">There are no examinations available for ${userDept} department.</p>
                    </div>
                `;
            }
        }

        // Check exam access
        function checkExamAccess() {
            const hasExamAccess = departmentsWithExams.includes(currentUser.department);
            
            if (hasExamAccess) {
                Swal.fire({
                    icon: 'success',
                    title: 'Exam Access Available',
                    html: `You have examination access for <b>${currentUser.department}</b> department.<br><br>
                           Your role: <b>${currentUser.role}</b>`,
                    confirmButtonColor: '#3b82f6'
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Exam Access',
                    html: `Your department (<b>${currentUser.department}</b>) does not have examination access.<br><br>
                           Only the following departments have exam access:<br>
                           • Front Office<br>
                           • Housekeeping<br>
                           • Food & Beverage<br>
                           • Kitchen<br>
                           • Sales & Marketing<br>
                           • Human Resources<br>
                           • Finance & Accounting`,
                    confirmButtonColor: '#3b82f6'
                });
            }
        }

        // Start exam
        function startExam(examId) {
            const exam = exams.find(e => e.id === examId);
            
            Swal.fire({
                title: 'Start Examination',
                html: `Are you ready to start the <b>${exam.title}</b> exam?<br><br>
                       <div class="text-left">
                         <p><b>Duration:</b> ${exam.duration}</p>
                         <p><b>Questions:</b> ${exam.questions}</p>
                         <p><b>Department:</b> ${exam.department}</p>
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Start Exam',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Exam Started!',
                        html: `Good luck with your <b>${exam.title}</b> exam!<br><br>
                               <p>The exam will begin now. You have ${exam.duration} to complete ${exam.questions} questions.</p>`,
                        icon: 'info',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            });
        }

        // Show department info
        function showDepartmentInfo() {
            const roles = departmentRoles[currentUser.department];
            
            Swal.fire({
                title: `${currentUser.department} Department`,
                html: `
                    <div class="text-left">
                        <p><b>Your Role:</b> ${currentUser.role}</p>
                        <p><b>Access Level:</b> ${currentUser.accessLevel.charAt(0).toUpperCase() + currentUser.accessLevel.slice(1)}</p>
                        <p><b>Total Roles in Department:</b> ${roles.length}</p>
                        <hr class="my-3">
                        <p><b>Available Roles:</b></p>
                        <ul class="list-disc pl-5">
                            ${roles.map(role => `<li>${role}</li>`).join('')}
                        </ul>
                    </div>
                `,
                icon: 'info',
                confirmButtonColor: '#3b82f6',
                width: '600px'
            });
        }

        // View all departments
        function viewAllDepartments() {
            let departmentsHTML = '';
            
            Object.keys(departmentRoles).forEach(dept => {
                const roleCount = departmentRoles[dept].length;
                const hasExamAccess = departmentsWithExams.includes(dept);
                
                departmentsHTML += `
                    <div class="border rounded p-3 mb-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-semibold">${dept}</h4>
                            <span class="badge">${roleCount} roles</span>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            ${hasExamAccess ? 
                                '<span class="text-green-500"><i class="fas fa-check-circle"></i> Has exam access</span>' : 
                                '<span class="text-gray-500"><i class="fas fa-times-circle"></i> No exam access</span>'
                            }
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Sample roles: ${departmentRoles[dept].slice(0, 2).join(', ')}${departmentRoles[dept].length > 2 ? '...' : ''}
                        </div>
                    </div>
                `;
            });
            
            Swal.fire({
                title: 'All Departments',
                html: `
                    <div class="text-left max-h-96 overflow-y-auto">
                        <p class="mb-4">The hotel has 9 departments with a total of 38 different roles.</p>
                        ${departmentsHTML}
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Close',
                confirmButtonColor: '#3b82f6',
                width: '700px'
            });
        }

        // Logout
        function logout() {
            Swal.fire({
                title: 'Confirm Logout',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    currentUser = null;
                    localStorage.removeItem('currentUser');
                    
                    document.getElementById('registrationSection').classList.remove('hidden');
                    document.getElementById('homeSection').classList.add('hidden');
                    document.getElementById('examsSection').classList.add('hidden');
                    document.getElementById('userNavSection').classList.add('hidden');
                    
                    // Reset registration form
                    document.getElementById('registrationForm').reset();
                    document.getElementById('regRole').disabled = true;
                    document.getElementById('regRole').innerHTML = '<option value="" disabled selected>Please select a department first</option>';
                    document.getElementById('roleHelpText').textContent = "Role selection is disabled until a department is selected";
                    document.getElementById('roleHelpText').className = "label-text-alt text-gray-500";
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have been successfully logged out.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            });
        }
    </script>
      <script src="../JS/soliera.js"></script>
            <script src="../JS/sidebar.js"></script>
</body>
</html>