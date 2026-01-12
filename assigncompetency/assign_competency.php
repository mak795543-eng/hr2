<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #ffffff;
            color: #000000;
        }
        
        .stat-card {
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            border-color: #000000;
        }
        
        .modal-box {
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
        }
        
        input, select, textarea {
            background-color: #ffffff !important;
            border-color: #e5e5e5 !important;
            color: #000000 !important;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #000000 !important;
            box-shadow: 0 0 0 1px #000000 !important;
        }
        
        .table th {
            background-color: #f5f5f5;
            border-color: #e5e5e5;
        }
        
        .table td {
            border-color: #e5e5e5;
        }
        
        .btn {
            background-color: white !important;
            color: black !important;
            border: 1px solid black !important;
        }
        
        .btn:hover {
            background-color: #f5f5f5 !important;
            border-color: #333333 !important;
        }
        
        .btn-outline {
            background-color: transparent !important;
            color: black !important;
            border-color: black !important;
        }
        
        .btn-outline:hover {
            background-color: black !important;
            color: white !important;
        }
        
        .badge {
            background-color: #f5f5f5;
            color: #000000;
            border: 1px solid #e5e5e5;
        }
        
        .badge-hotel {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        
        .badge-restaurant {
            background-color: #f0fdf4;
            color: #15803d;
            border-color: #dcfce7;
        }
        
        .badge-general {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
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
            
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="container mx-auto p-4">
                    <!-- Header -->
                    <header class="mb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <div>
                                <h1 class="text-3xl md:text-4xl font-bold mb-2">Competency Management System</h1>
                                <p class="text-gray-600">Track and manage employee skills and proficiency levels</p>
                            </div>
                            <button onclick="refreshData()" class="btn">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                            </button>
                        </div>
                        
                        <!-- System Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" id="statsContainer">
                            <div class="stat-card rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-gray-100 mr-4">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Employees</p>
                                        <h3 class="text-2xl font-bold" id="totalEmployees">0</h3>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-gray-100 mr-4">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Competencies</p>
                                        <h3 class="text-2xl font-bold" id="totalCompetencies">0</h3>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-gray-100 mr-4">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Avg. Proficiency</p>
                                        <h3 class="text-2xl font-bold" id="avgProficiency">0.0</h3>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-gray-100 mr-4">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">High Performers</p>
                                        <h3 class="text-2xl font-bold" id="highPerformers">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Quick Actions -->
                    <div class="rounded-lg p-6 mb-6 border border-gray-200">
                        <h2 class="text-2xl font-bold mb-6">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button onclick="showAddCompetencyModal()" class="btn">
                                <i class="fas fa-tools mr-2"></i>Add Competency
                            </button>
                            <button onclick="exportData()" class="btn-outline btn">
                                <i class="fas fa-file-export mr-2"></i>Export Data
                            </button>
                            <button onclick="showProficiencyLegend()" class="btn-outline btn">
                                <i class="fas fa-info-circle mr-2"></i>Proficiency Legend
                            </button>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="grid grid-cols-1">
                        <!-- Employee and Competency Tables -->
                        <div>
                            <div class="rounded-lg p-6 mb-6 border border-gray-200">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                    <h2 class="text-2xl font-bold">Employee Competency Data</h2>
                                    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                        <input type="text" id="searchEmployee" placeholder="Search employees..." class="input input-bordered w-full" />
                                        <select id="filterDepartment" class="select select-bordered w-full sm:w-auto">
                                            <option value="">All Departments</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Position</th>
                                                <th>Competencies</th>
                                                <th>Avg. Proficiency</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="employeeTableBody">
                                            <!-- Data loaded by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Competency Table -->
                            <div class="rounded-lg p-6 border border-gray-200">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                    <h2 class="text-2xl font-bold">Competency Management</h2>
                                    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                        <input type="text" id="searchCompetency" placeholder="Search competencies..." class="input input-bordered w-full" />
                                        <select id="filterCompetencyType" class="select select-bordered w-full sm:w-auto">
                                            <option value="">All Types</option>
                                            <option value="hotel">Hotel Competency</option>
                                            <option value="restaurant">Restaurant Competency</option>
                                            <option value="general">General Skill</option>
                                            <option value="core">Core Skill</option>
                                            <option value="technical">Technical Skill</option>
                                            <option value="soft">Soft Skill</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Competency</th>
                                                <th>Type</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="competencyTableBody">
                                            <!-- Data loaded by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Modals remain the same as before -->
                <!-- Proficiency Legend Modal -->
                <div id="legendModal" class="modal">
                    <div class="modal-box w-11/12 max-w-md">
                        <h3 class="font-bold text-2xl mb-6">Proficiency Legend</h3>
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <div class="flex items-center">
                                    <div class="badge badge-lg mr-3">Level 5</div>
                                    <span>Expert</span>
                                </div>
                                <span class="text-gray-600">90-100%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <div class="flex items-center">
                                    <div class="badge badge-lg mr-3">Level 4</div>
                                    <span>Advanced</span>
                                </div>
                                <span class="text-gray-600">70-89%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <div class="flex items-center">
                                    <div class="badge badge-lg mr-3">Level 3</div>
                                    <span>Competent</span>
                                </div>
                                <span class="text-gray-600">50-69%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <div class="flex items-center">
                                    <div class="badge badge-lg mr-3">Level 2</div>
                                    <span>Basic</span>
                                </div>
                                <span class="text-gray-600">30-49%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <div class="flex items-center">
                                    <div class="badge badge-lg mr-3">Level 1</div>
                                    <span>Novice</span>
                                </div>
                                <span class="text-gray-600">0-29%</span>
                            </div>
                        </div>
                        <div class="modal-action">
                            <button onclick="closeModal('legendModal')" class="btn btn-ghost">Close</button>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Employee Modal -->
                <div id="employeeModal" class="modal">
                    <div class="modal-box w-11/12 max-w-lg">
                        <h3 class="font-bold text-2xl mb-6" id="employeeModalTitle">Add New Employee</h3>
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Full Name *</span>
                                </label>
                                <input type="text" id="modalEmployeeName" class="input input-bordered" placeholder="John Smith" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold">Position *</span>
                                    </label>
                                    <select id="modalEmployeePosition" class="select select-bordered">
                                        <option value="Waiter">Waiter</option>
                                        <option value="Chef">Chef</option>
                                        <option value="Front Desk">Front Desk</option>
                                        <option value="Housekeeping">Housekeeping</option>
                                        <option value="Manager">Manager</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold">Department *</span>
                                    </label>
                                    <input type="text" id="modalEmployeeDepartment" class="input input-bordered" placeholder="Restaurant" />
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Email *</span>
                                </label>
                                <input type="email" id="modalEmployeeEmail" class="input input-bordered" placeholder="john@example.com" />
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Hire Date</span>
                                </label>
                                <input type="date" id="modalEmployeeHireDate" class="input input-bordered" />
                            </div>
                        </div>
                        <div class="modal-action mt-6">
                            <button onclick="closeModal('employeeModal')" class="btn btn-ghost">Cancel</button>
                            <button onclick="saveEmployee()" class="btn">Save Employee</button>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Competency Modal -->
                <div id="competencyModal" class="modal">
                    <div class="modal-box w-11/12 max-w-lg">
                        <h3 class="font-bold text-2xl mb-6" id="competencyModalTitle">Add New Competency</h3>
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Competency Name *</span>
                                </label>
                                <input type="text" id="modalCompetencyName" class="input input-bordered" placeholder="Communication Skills" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold">Type *</span>
                                    </label>
                                    <select id="modalCompetencyType" class="select select-bordered">
                                        <option value="general">General Skill</option>
                                        <option value="hotel">Hotel Competency</option>
                                        <option value="restaurant">Restaurant Competency</option>
                                        <option value="core">Core Skill</option>
                                        <option value="technical">Technical Skill</option>
                                        <option value="soft">Soft Skill</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold">Category *</span>
                                    </label>
                                    <select id="modalCompetencyCategory" class="select select-bordered">
                                        <option value="Teamwork">Teamwork</option>
                                        <option value="Customer Service">Customer Service / Guest Relations</option>
                                        <option value="Communication">Communication Skills</option>
                                        <option value="Time Management">Time Management / Efficiency</option>
                                        <option value="Problem Solving">Problem-Solving / Initiative</option>
                                        <option value="Technical">Technical</option>
                                        <option value="Safety">Safety & Security</option>
                                        <option value="Leadership">Leadership</option>
                                        <option value="Food Service">Food & Beverage Service</option>
                                        <option value="Housekeeping">Housekeeping & Cleaning</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Description</span>
                                </label>
                                <textarea id="modalCompetencyDescription" class="textarea textarea-bordered h-32" placeholder="Describe this competency..."></textarea>
                            </div>
                        </div>
                        <div class="modal-action mt-6">
                            <button onclick="closeModal('competencyModal')" class="btn btn-ghost">Cancel</button>
                            <button onclick="saveCompetency()" class="btn">Save Competency</button>
                        </div>
                    </div>
                </div>

                <!-- Assign Competency Modal -->
                <div id="assignCompetencyModal" class="modal">
                    <div class="modal-box w-11/12 max-w-md">
                        <h3 class="font-bold text-2xl mb-6" id="assignModalTitle">Assign Competency</h3>
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Select Competency *</span>
                                </label>
                                <select id="modalAssignCompetency" class="select select-bordered">
                                    <option value="">Choose a competency</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Proficiency Level *</span>
                                </label>
                                <input id="modalAssignProficiency" type="range" min="1" max="5" value="3" class="range" />
                                <div class="flex justify-between text-xs px-2 mt-1">
                                    <span>1</span>
                                    <span>2</span>
                                    <span>3</span>
                                    <span>4</span>
                                    <span>5</span>
                                </div>
                                <div class="text-center mt-2">
                                    <span id="proficiencyValueDisplay" class="badge badge-lg">Level 3 - Competent</span>
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold">Assessment Notes</span>
                                </label>
                                <textarea id="modalAssignNotes" class="textarea textarea-bordered h-24" placeholder="Add assessment notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-action mt-6">
                            <button onclick="closeModal('assignCompetencyModal')" class="btn btn-ghost">Cancel</button>
                            <button onclick="saveAssignedCompetency()" class="btn">Assign Competency</button>
                        </div>
                    </div>
                </div>

                <!-- Include the updated main.js file -->
                <script src="main.js"></script>
            </main>
        </div>
    </div>
</body>
</html>