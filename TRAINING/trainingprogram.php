<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Portal - Training Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .training-card { transition: all 0.2s ease; }
        .training-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #10b981;
            transition: width 0.3s ease;
        }
        
        .stats-card {
            transition: all 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        
        .status-planned { background-color: #fef3c7; color: #92400e; }
        .status-scheduled { background-color: #dbeafe; color: #1e40af; }
        .status-ongoing { background-color: #dcfce7; color: #166534; }
        .status-completed { background-color: #f3f4f6; color: #374151; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .datetime-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        @media (max-width: 640px) {
            .datetime-container {
                grid-template-columns: 1fr;
            }
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
        
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stats-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Trainings</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="total-trainings">0</h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i data-lucide="book-open" class="h-6 w-6 text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Active:</span>
                        <span class="font-medium" id="active-trainings">0</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill" id="active-progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Participants</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="total-participants">0</h3>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i data-lucide="users" class="h-6 w-6 text-green-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Registered:</span>
                        <span class="font-medium" id="registered-participants">0</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill" id="registration-progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Completion Rate</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="completion-rate">0%</h3>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i data-lucide="check-circle" class="h-6 w-6 text-purple-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Open for Reg:</span>
                        <span class="font-medium" id="open-registrations">0</span>
                    </div>
                </div>
            </div>
            
            <div class="stats-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Upcoming Trainings</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="upcoming-trainings">0</h3>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i data-lucide="calendar" class="h-6 w-6 text-yellow-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Next 7 days:</span>
                        <span class="font-medium" id="week-trainings">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Training Programs Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <!-- Action Bar with Filters -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-700">Training Programs</h2>
                    <p class="text-gray-500 text-sm">Manage all training programs across the organization</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter buttons -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <i data-lucide="filter" class="h-4 w-4 mr-2"></i>
                            Filter
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByType('all')">All Types</a></li>
                            <li><a onclick="filterByType('Orientation')">Orientation</a></li>
                            <li><a onclick="filterByType('Training')">Training</a></li>
                            <li><a onclick="filterByType('Seminar')">Seminar</a></li>
                            <li><a onclick="filterByType('Workshop')">Workshop</a></li>
                        </ul>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <i data-lucide="list-filter" class="h-4 w-4 mr-2"></i>
                            Status
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByStatus('all')">All Status</a></li>
                            <li><a onclick="filterByStatus('Planned')">Planned</a></li>
                            <li><a onclick="filterByStatus('Scheduled')">Scheduled</a></li>
                            <li><a onclick="filterByStatus('Ongoing')">Ongoing</a></li>
                            <li><a onclick="filterByStatus('Completed')">Completed</a></li>
                        </ul>
                    </div>
                    
                    <button id="add-training-btn" class="btn btn-primary btn-sm">
                        <i data-lucide="plus" class="h-5 w-5 mr-2"></i>
                        Add Training
                    </button>
                </div>
            </div>

            <!-- Training Cards Container -->
            <div id="training-cards-container" class="fade-in">
                <div id="cards-view">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="training-cards">
                        <!-- Training cards will be dynamically added here -->
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="text-center py-12">
                    <i data-lucide="book-open" class="h-16 w-16 mx-auto text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No training programs yet</h3>
                    <p class="text-gray-500 mb-4">Get started by creating your first training program.</p>
                    <button id="empty-add-btn" class="btn btn-primary">Add Training</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Training Modal -->
    <dialog id="training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <h3 class="font-bold text-2xl mb-2" id="modal-title">Create New Training Program</h3>
            <p class="text-gray-600 mb-6" id="modal-subtitle">Fill in all required information to create a new training program</p>
            
            <form id="training-form" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Training Program Title -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Program Title <span class="text-red-500">*</span></span>
                            </label>
                            <input id="training-title" type="text" placeholder="Enter training title" class="input input-bordered w-full" required>
                        </div>
                        
                        <!-- Training Type -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Type <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-type" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select training type</option>
                                <option value="Orientation">Orientation</option>
                                <option value="Training">Training</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Workshop">Workshop</option>
                            </select>
                        </div>
                        
                        <!-- Target Audience -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Target Audience <span class="text-red-500">*</span></span>
                            </label>
                            <select id="target-audience" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select target audience</option>
                                <option value="New Hires">New Hires</option>
                                <option value="Specific Department">Specific Department</option>
                                <option value="Specific Role">Specific Role</option>
                                <option value="All Employees">All Employees</option>
                                <option value="Management">Management</option>
                                <option value="Technical Staff">Technical Staff</option>
                                <option value="Customer Service">Customer Service</option>
                            </select>
                        </div>
                        
                        <!-- Department Selection (shown when Specific Department is selected) -->
                        <div id="department-container" class="form-control fade-in hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Department <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-department" class="select select-bordered w-full">
                                <option value="" selected>Select a department</option>
                                <!-- Departments will be populated from database -->
                            </select>
                        </div>
                        
                        <!-- Role Selection (shown when Specific Role is selected) -->
                        <div id="role-container" class="form-control fade-in hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Role <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-role" class="select select-bordered w-full">
                                <option value="" selected>Select a role</option>
                                <option value="Manager">Manager</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Team Lead">Team Lead</option>
                                <option value="Executive">Executive</option>
                                <option value="Associate">Associate</option>
                                <option value="Intern">Intern</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Training Category -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Category <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-category" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="Technical Skills">Technical Skills</option>
                                <option value="Soft Skills">Soft Skills</option>
                                <option value="Compliance">Compliance</option>
                                <option value="Leadership">Leadership</option>
                                <option value="Customer Service">Customer Service</option>
                                <option value="Sales & Marketing">Sales & Marketing</option>
                                <option value="Safety & Security">Safety & Security</option>
                                <option value="IT & Digital Literacy">IT & Digital Literacy</option>
                                <option value="Product Knowledge">Product Knowledge</option>
                                <option value="Quality Management">Quality Management</option>
                            </select>
                        </div>
                        
                        <!-- Expected Participants -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Expected Participants <span class="text-red-500">*</span></span>
                            </label>
                            <input id="participants-needed" type="number" min="1" value="10" 
                                   class="input input-bordered w-full" required>
                            <div class="mt-2 text-sm text-gray-500">
                                This will be used for budget planning and logistical arrangements
                            </div>
                        </div>
                        
                        <!-- Schedule Section -->
                        <div class="space-y-4">
                            <div>
                                <label class="label">
                                    <span class="label-text font-semibold">Schedule <span class="text-red-500">*</span></span>
                                </label>
                                <div class="datetime-container">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Start Date</span>
                                        </label>
                                        <input id="start-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Start Time</span>
                                        </label>
                                        <input id="start-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">End Date</span>
                                        </label>
                                        <input id="end-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">End Time</span>
                                        </label>
                                        <input id="end-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-500" id="schedule-validation">
                                    <!-- Validation messages will appear here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description / Overview -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description / Overview <span class="text-red-500">*</span></span>
                    </label>
                    <textarea id="description" class="textarea textarea-bordered h-32" placeholder="Provide a brief explanation of the training program" required></textarea>
                </div>
            </form>
            
            <div class="modal-action">
                <button type="button" id="cancel-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="save-training-btn" class="btn btn-primary">Save Training Program</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- View Training Details Modal -->
    <dialog id="view-training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <h3 class="font-bold text-2xl mb-2" id="view-training-title">Training Details</h3>
            <div class="flex items-center gap-2 mb-4">
                <span id="view-training-type" class="badge badge-outline"></span>
            </div>
            
            <div class="space-y-4" id="view-training-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Category</h4>
                        <p id="view-category" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Target Audience</h4>
                        <p id="view-target-audience" class="badge badge-outline"></p>
                    </div>
                </div>
                
                <div id="view-department-container" class="hidden">
                    <h4 class="font-semibold text-gray-700">Department</h4>
                    <p id="view-department" class="text-gray-900"></p>
                </div>
                
                <div id="view-role-container" class="hidden">
                    <h4 class="font-semibold text-gray-700">Role</h4>
                    <p id="view-role" class="text-gray-900"></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Expected Participants</h4>
                        <p id="view-participants" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Status</h4>
                        <p id="view-status" class="badge badge-outline"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Start Date & Time</h4>
                        <p id="view-start-date" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">End Date & Time</h4>
                        <p id="view-end-date" class="text-gray-900"></p>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700">Duration</h4>
                    <p id="view-duration" class="text-gray-900"></p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700">Description</h4>
                    <p id="view-description" class="text-gray-900 whitespace-pre-line"></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Created Date</h4>
                        <p id="view-created-date" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Last Updated</h4>
                        <p id="view-updated-date" class="text-gray-900"></p>
                    </div>
                </div>
            </div>
            
            <div class="modal-action">
                <button type="button" id="close-view-modal" class="btn btn-ghost">Close</button>
                <button type="button" id="edit-training-btn" class="btn btn-primary">Edit</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        // Training data - Will be loaded from database via PHP
        let trainings = [];
        let departments = [];
        let editingTrainingId = null;
        let viewingTrainingId = null;
        let currentFilter = { type: 'all', status: 'all' };

        // DOM Elements
        const addTrainingBtn = document.getElementById('add-training-btn');
        const emptyAddBtn = document.getElementById('empty-add-btn');
        const trainingModal = document.getElementById('training-modal');
        const viewTrainingModal = document.getElementById('view-training-modal');
        const cancelBtn = document.getElementById('cancel-btn');
        const saveTrainingBtn = document.getElementById('save-training-btn');
        const trainingForm = document.getElementById('training-form');
        const trainingCards = document.getElementById('training-cards');
        const emptyState = document.getElementById('empty-state');
        const closeViewModal = document.getElementById('close-view-modal');
        const editTrainingBtn = document.getElementById('edit-training-btn');
        const targetAudienceSelect = document.getElementById('target-audience');
        const departmentContainer = document.getElementById('department-container');
        const roleContainer = document.getElementById('role-container');
        const trainingDepartmentSelect = document.getElementById('training-department');
        const modalTitle = document.getElementById('modal-title');
        const modalSubtitle = document.getElementById('modal-subtitle');
        
        // Statistics elements
        const totalTrainingsEl = document.getElementById('total-trainings');
        const activeTrainingsEl = document.getElementById('active-trainings');
        const activeProgressEl = document.getElementById('active-progress');
        const totalParticipantsEl = document.getElementById('total-participants');
        const registeredParticipantsEl = document.getElementById('registered-participants');
        const registrationProgressEl = document.getElementById('registration-progress');
        const completionRateEl = document.getElementById('completion-rate');
        const openRegistrationsEl = document.getElementById('open-registrations');
        const upcomingTrainingsEl = document.getElementById('upcoming-trainings');
        const weekTrainingsEl = document.getElementById('week-trainings');

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            // Initialize Lucide icons
            lucide.createIcons();
            
            // Load data from PHP backend
            await loadDepartments();
            await loadTrainings();
            
            updateUI();
            setupEventListeners();
            setDefaultDates();
        });

        // Load departments from database
        async function loadDepartments() {
            try {
                const response = await fetch('api/get_departments.php');
                const data = await response.json();
                departments = data;
                populateDepartmentOptions();
            } catch (error) {
                console.error('Error loading departments:', error);
                // Fallback to empty array
                departments = [];
            }
        }

        // Load trainings from database
        async function loadTrainings() {
            try {
                const response = await fetch('api/get_trainings.php');
                const data = await response.json();
                trainings = data;
            } catch (error) {
                console.error('Error loading trainings:', error);
                // Fallback to empty array
                trainings = [];
            }
        }

        function setDefaultDates() {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            // Format dates for date inputs (YYYY-MM-DD)
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };
            
            // Default times (9:00 AM - 5:00 PM)
            const defaultStartTime = '09:00';
            const defaultEndTime = '17:00';
            
            document.getElementById('start-date').value = formatDate(tomorrow);
            document.getElementById('start-time').value = defaultStartTime;
            document.getElementById('end-date').value = formatDate(tomorrow);
            document.getElementById('end-time').value = defaultEndTime;
        }

        function setupEventListeners() {
            // Add Training button
            addTrainingBtn.addEventListener('click', () => {
                resetForm();
                modalTitle.textContent = 'Create New Training Program';
                modalSubtitle.textContent = 'Fill in all required information to create a new training program';
                trainingModal.showModal();
            });

            // Empty State Add button
            emptyAddBtn.addEventListener('click', () => {
                resetForm();
                modalTitle.textContent = 'Create New Training Program';
                modalSubtitle.textContent = 'Fill in all required information to create a new training program';
                trainingModal.showModal();
            });

            // Cancel button
            cancelBtn.addEventListener('click', () => {
                trainingModal.close();
                resetForm();
            });

            // Save Training button
            saveTrainingBtn.addEventListener('click', saveTraining);

            // Close View Modal button
            closeViewModal.addEventListener('click', () => {
                viewTrainingModal.close();
            });

            // Edit Training button in View Modal
            editTrainingBtn.addEventListener('click', () => {
                if (viewingTrainingId) {
                    editTraining(viewingTrainingId);
                    viewTrainingModal.close();
                }
            });

            // Target Audience change handler
            targetAudienceSelect.addEventListener('change', handleTargetAudienceChange);

            // Date and time validation
            document.getElementById('start-date').addEventListener('change', validateSchedule);
            document.getElementById('start-time').addEventListener('change', validateSchedule);
            document.getElementById('end-date').addEventListener('change', validateSchedule);
            document.getElementById('end-time').addEventListener('change', validateSchedule);

            // Event delegation for dynamic action buttons
            document.addEventListener('click', function(event) {
                const button = event.target.closest('button');
                if (!button) return;
                
                const action = button.getAttribute('data-action');
                const id = button.getAttribute('data-id');
                
                if (action && id) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    switch(action) {
                        case 'view':
                            viewTraining(id);
                            break;
                        case 'edit':
                            editTraining(id);
                            break;
                        case 'delete':
                            deleteTraining(id);
                            break;
                    }
                }
            });
        }

        // Validate schedule dates and times
        function validateSchedule() {
            const startDate = document.getElementById('start-date').value;
            const startTime = document.getElementById('start-time').value;
            const endDate = document.getElementById('end-date').value;
            const endTime = document.getElementById('end-time').value;
            
            const validationElement = document.getElementById('schedule-validation');
            
            if (!startDate || !startTime || !endDate || !endTime) {
                validationElement.innerHTML = '';
                return;
            }
            
            // Create date objects
            const startDateTime = new Date(`${startDate}T${startTime}`);
            const endDateTime = new Date(`${endDate}T${endTime}`);
            const now = new Date();
            
            let messages = [];
            
            // Check if end is after start
            if (endDateTime <= startDateTime) {
                messages.push('<span class="text-red-500">❌ End date/time must be after start date/time</span>');
            }
            
            // Check if start is in the past
            if (startDateTime < now) {
                messages.push('<span class="text-yellow-500">⚠️ Start date/time is in the past</span>');
            }
            
            // Calculate duration
            const durationMs = endDateTime - startDateTime;
            const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
            const durationMinutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (durationHours > 0 || durationMinutes > 0) {
                messages.push(`<span class="text-green-500">✓ Duration: ${durationHours}h ${durationMinutes}m</span>`);
            }
            
            validationElement.innerHTML = messages.join('<br>');
        }

        // Handle target audience change
        function handleTargetAudienceChange() {
            const targetAudience = targetAudienceSelect.value;
            
            // Show/hide department and role containers
            departmentContainer.classList.add('hidden');
            roleContainer.classList.add('hidden');
            
            if (targetAudience === 'Specific Department') {
                departmentContainer.classList.remove('hidden');
            } else if (targetAudience === 'Specific Role') {
                roleContainer.classList.remove('hidden');
            }
        }

        // Populate department dropdown
        function populateDepartmentOptions() {
            trainingDepartmentSelect.innerHTML = '<option value="" selected>Select a department</option>';
            
            departments.forEach(department => {
                const option = document.createElement('option');
                option.value = department.id;
                option.textContent = department.department_name;
                trainingDepartmentSelect.appendChild(option);
            });
        }

        // Filter by type
        function filterByType(type) {
            currentFilter.type = type;
            applyFilters();
        }

        // Filter by status
        function filterByStatus(status) {
            currentFilter.status = status;
            applyFilters();
        }

        // Apply both filters
        function applyFilters() {
            let filtered = trainings;
            
            if (currentFilter.type !== 'all') {
                filtered = filtered.filter(t => t.training_type === currentFilter.type);
            }
            
            if (currentFilter.status !== 'all') {
                filtered = filtered.filter(t => t.status === currentFilter.status);
            }
            
            updateTrainingCards(filtered);
        }

        // Update UI based on trainings
        function updateUI() {
            updateTrainingCards();
            
            if (trainings.length === 0) {
                emptyState.classList.remove('hidden');
                trainingCards.innerHTML = '';
            } else {
                emptyState.classList.add('hidden');
            }
            
            updateStatistics();
        }

        // Update training cards
        function updateTrainingCards(trainingsToShow = trainings) {
            if (trainingsToShow.length === 0) {
                trainingCards.innerHTML = `
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <i data-lucide="search" class="h-16 w-16 mx-auto mb-4 text-gray-300"></i>
                        <p class="text-lg">No trainings found matching your filters</p>
                        <button onclick="filterByType('all'); filterByStatus('all')" class="btn btn-link mt-2">Clear filters</button>
                    </div>
                `;
                return;
            }
            
            trainingCards.innerHTML = trainingsToShow.map(training => {
                const startDateTime = new Date(training.start_date);
                const endDateTime = new Date(training.end_date);
                const now = new Date();
                
                // Determine if training is upcoming
                const isUpcoming = startDateTime > now;
                const isToday = startDateTime.toDateString() === now.toDateString();
                
                // Get status class
                const statusClass = `status-${training.status.toLowerCase()}`;
                
                // Format date and time
                const formattedDate = isToday ? 'Today' : startDateTime.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric'
                });
                
                const formattedTime = startDateTime.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Calculate duration
                const duration = calculateDuration(training.start_date, training.end_date);
                
                return `
                <div class="training-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-5">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="badge badge-outline">${training.training_type}</span>
                                    <span class="${statusClass} status-badge">${training.status}</span>
                                </div>
                                <h3 class="font-bold text-lg text-gray-900 line-clamp-1">${training.training_title}</h3>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="space-y-3 mb-4">
                            <!-- Schedule -->
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                                <span>${formattedDate} at ${formattedTime}</span>
                            </div>
                            
                            <!-- Duration -->
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                                <span>${duration}</span>
                            </div>
                            
                            <!-- Category -->
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="tag" class="h-4 w-4"></i>
                                <span>${training.category}</span>
                            </div>
                            
                            <!-- Participants -->
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="users" class="h-4 w-4"></i>
                                <span>${training.participants_needed} participants</span>
                            </div>
                            
                            <!-- Target Audience -->
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="target" class="h-4 w-4"></i>
                                <span>${training.target_audience}</span>
                            </div>
                        </div>
                        
                        <!-- Description Preview -->
                        <p class="text-sm text-gray-500 mb-5 line-clamp-2">${training.description}</p>
                        
                        <!-- Actions -->
                        <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                            <span class="text-xs text-gray-400">
                                Starts ${formatRelativeTime(training.start_date)}
                            </span>
                            <div class="flex items-center gap-2">
                                <button data-action="view" data-id="${training.id}" 
                                        class="btn btn-xs btn-ghost" title="View Details">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button data-action="edit" data-id="${training.id}" 
                                        class="btn btn-xs btn-ghost" title="Edit">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <button data-action="delete" data-id="${training.id}" 
                                        class="btn btn-xs btn-ghost text-red-600" title="Delete">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
            
            // Refresh icons
            lucide.createIcons();
        }

        // Format relative time
        function formatRelativeTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = date - now;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'today';
            if (diffDays === 1) return 'tomorrow';
            if (diffDays < 0) return `${Math.abs(diffDays)} days ago`;
            if (diffDays < 7) return `in ${diffDays} days`;
            if (diffDays < 30) return `in ${Math.floor(diffDays / 7)} weeks`;
            return `in ${Math.floor(diffDays / 30)} months`;
        }

        // Format date and time
        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Calculate duration
        function calculateDuration(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffMs = end - start;
            
            const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (days > 0) {
                return `${days}d ${hours}h`;
            } else if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else {
                return `${minutes}m`;
            }
        }

        // Update statistics
        function updateStatistics() {
            // Total trainings
            totalTrainingsEl.textContent = trainings.length;
            
            // Active trainings (Planned + Scheduled + Ongoing)
            const activeTrainings = trainings.filter(t => t.status === 'Planned' || t.status === 'Scheduled' || t.status === 'Ongoing');
            activeTrainingsEl.textContent = activeTrainings.length;
            const activePercentage = trainings.length > 0 ? (activeTrainings.length / trainings.length) * 100 : 0;
            activeProgressEl.style.width = `${activePercentage}%`;
            
            // Total participants needed
            const totalParticipantsNeeded = trainings.reduce((sum, t) => sum + (t.participants_needed || 0), 0);
            totalParticipantsEl.textContent = totalParticipantsNeeded;
            
            // For demo purposes, set registered participants as 70% of needed
            const totalRegistered = Math.floor(totalParticipantsNeeded * 0.7);
            registeredParticipantsEl.textContent = totalRegistered;
            
            // Registration progress
            const registrationPercentage = totalParticipantsNeeded > 0 ? (totalRegistered / totalParticipantsNeeded) * 100 : 0;
            registrationProgressEl.style.width = `${registrationPercentage}%`;
            
            // Completion rate (Completed trainings)
            const completedTrainings = trainings.filter(t => t.status === 'Completed');
            const completionRate = trainings.length > 0 ? (completedTrainings.length / trainings.length) * 100 : 0;
            completionRateEl.textContent = `${completionRate.toFixed(1)}%`;
            
            // Open registrations (Planned)
            const openRegistrations = trainings.filter(t => t.status === 'Planned');
            openRegistrationsEl.textContent = openRegistrations.length;
            
            // Upcoming trainings (Scheduled in future)
            const now = new Date();
            const upcomingTrainings = trainings.filter(t => {
                const startDate = new Date(t.start_date);
                return (t.status === 'Scheduled' || t.status === 'Planned') && startDate > now;
            });
            upcomingTrainingsEl.textContent = upcomingTrainings.length;
            
            // Trainings in next 7 days
            const nextWeek = new Date(now);
            nextWeek.setDate(nextWeek.getDate() + 7);
            const weekTrainings = trainings.filter(t => {
                const startDate = new Date(t.start_date);
                return startDate >= now && startDate <= nextWeek;
            }).length;
            weekTrainingsEl.textContent = weekTrainings;
        }

        // Save training
        async function saveTraining() {
            if (!trainingForm.checkValidity()) {
                trainingForm.reportValidity();
                return;
            }

            const targetAudience = document.getElementById('target-audience').value;
            const department = document.getElementById('training-department').value;
            const role = document.getElementById('training-role').value;
            const startDate = document.getElementById('start-date').value;
            const startTime = document.getElementById('start-time').value;
            const endDate = document.getElementById('end-date').value;
            const endTime = document.getElementById('end-time').value;
            
            // Validation for specific department/role
            if (targetAudience === 'Specific Department' && !department) {
                Swal.fire({
                    icon: 'error',
                    title: 'Department Required',
                    text: 'Please select a department for Specific Department target audience',
                });
                return;
            }
            
            if (targetAudience === 'Specific Role' && !role) {
                Swal.fire({
                    icon: 'error',
                    title: 'Role Required',
                    text: 'Please select a role for Specific Role target audience',
                });
                return;
            }

            // Validate dates and times
            if (!startDate || !startTime || !endDate || !endTime) {
                Swal.fire({
                    icon: 'error',
                    title: 'Schedule Required',
                    text: 'Please fill in all schedule fields',
                });
                return;
            }

            // Create combined datetime strings
            const startDateTime = new Date(`${startDate}T${startTime}`);
            const endDateTime = new Date(`${endDate}T${endTime}`);
            
            if (endDateTime <= startDateTime) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Schedule',
                    text: 'End date/time must be after start date/time',
                });
                return;
            }

            const trainingData = {
                training_title: document.getElementById('training-title').value,
                training_type: document.getElementById('training-type').value,
                description: document.getElementById('description').value,
                target_audience: targetAudience,
                department_id: targetAudience === 'Specific Department' ? department : null,
                target_role: targetAudience === 'Specific Role' ? role : null,
                category: document.getElementById('training-category').value,
                participants_needed: parseInt(document.getElementById('participants-needed').value),
                start_date: startDateTime.toISOString(),
                end_date: endDateTime.toISOString(),
                status: determineStatus(startDateTime, endDateTime)
            };

            try {
                // Send data to PHP backend
                const response = await saveTrainingToDatabase(trainingData);
                
                trainingModal.close();
                resetForm();

                Swal.fire({
                    icon: 'success',
                    title: editingTrainingId ? 'Training Updated!' : 'Training Created!',
                    text: `Training "${trainingData.training_title}" has been saved successfully.`,
                    timer: 2000
                });
                
                // Reload data from database
                await loadTrainings();
                updateUI();
            } catch (error) {
                console.error('Error saving training:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save training. Please try again.',
                });
            }
        }

        // Save training to database via PHP
        async function saveTrainingToDatabase(trainingData) {
            const endpoint = editingTrainingId ? 'api/update_training.php' : 'api/create_training.php';
            
            const dataToSend = {
                ...trainingData,
                id: editingTrainingId
            };
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dataToSend)
            });
            
            if (!response.ok) {
                throw new Error('Failed to save training');
            }
            
            return response.json();
        }

        // Determine status based on dates
        function determineStatus(startDateTime, endDateTime) {
            const now = new Date();
            
            if (now > endDateTime) return 'Completed';
            if (now >= startDateTime && now <= endDateTime) return 'Ongoing';
            return startDateTime <= new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000) ? 'Scheduled' : 'Planned';
        }

        // View training details
        function viewTraining(id) {
            const training = trainings.find(t => t.id == id);
            if (training) {
                viewingTrainingId = id;
                
                document.getElementById('view-training-title').textContent = training.training_title;
                document.getElementById('view-training-type').textContent = training.training_type;
                document.getElementById('view-category').textContent = training.category;
                document.getElementById('view-target-audience').textContent = training.target_audience;
                document.getElementById('view-participants').textContent = training.participants_needed;
                document.getElementById('view-description').textContent = training.description || 'No description provided';
                document.getElementById('view-created-date').textContent = formatDateTime(training.created_at);
                document.getElementById('view-updated-date').textContent = formatDateTime(training.updated_at);
                document.getElementById('view-status').textContent = training.status;
                document.getElementById('view-start-date').textContent = formatDateTime(training.start_date);
                document.getElementById('view-end-date').textContent = formatDateTime(training.end_date);
                document.getElementById('view-duration').textContent = calculateDuration(training.start_date, training.end_date);
                
                // Show/hide department and role info
                const viewDeptContainer = document.getElementById('view-department-container');
                const viewRoleContainer = document.getElementById('view-role-container');
                
                viewDeptContainer.classList.add('hidden');
                viewRoleContainer.classList.add('hidden');
                
                if (training.target_audience === 'Specific Department' && training.department_name) {
                    document.getElementById('view-department').textContent = training.department_name;
                    viewDeptContainer.classList.remove('hidden');
                } else if (training.target_audience === 'Specific Role' && training.target_role) {
                    document.getElementById('view-role').textContent = training.target_role;
                    viewRoleContainer.classList.remove('hidden');
                }
                
                viewTrainingModal.showModal();
            }
        }

        // Edit training
        function editTraining(id) {
            const training = trainings.find(t => t.id == id);
            if (training) {
                editingTrainingId = id;
                modalTitle.textContent = 'Edit Training Program';
                modalSubtitle.textContent = 'Update the training program information';
                
                // Parse start and end dates/times
                const startDateTime = new Date(training.start_date);
                const endDateTime = new Date(training.end_date);
                
                // Format date for input (YYYY-MM-DD)
                const formatDateForInput = (date) => {
                    return date.toISOString().split('T')[0];
                };
                
                // Format time for input (HH:MM)
                const formatTimeForInput = (date) => {
                    return date.toTimeString().slice(0, 5);
                };
                
                // Populate form fields
                document.getElementById('training-title').value = training.training_title;
                document.getElementById('training-type').value = training.training_type;
                document.getElementById('description').value = training.description || '';
                document.getElementById('target-audience').value = training.target_audience;
                document.getElementById('training-category').value = training.category;
                document.getElementById('participants-needed').value = training.participants_needed;
                document.getElementById('start-date').value = formatDateForInput(startDateTime);
                document.getElementById('start-time').value = formatTimeForInput(startDateTime);
                document.getElementById('end-date').value = formatDateForInput(endDateTime);
                document.getElementById('end-time').value = formatTimeForInput(endDateTime);
                
                // Handle target audience specific fields
                handleTargetAudienceChange();
                
                if (training.target_audience === 'Specific Department' && training.department_id) {
                    setTimeout(() => {
                        document.getElementById('training-department').value = training.department_id;
                    }, 100);
                } else if (training.target_audience === 'Specific Role' && training.target_role) {
                    setTimeout(() => {
                        document.getElementById('training-role').value = training.target_role;
                    }, 100);
                }
                
                trainingModal.showModal();
            }
        }

        // Delete training
        async function deleteTraining(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        // Send delete request to PHP backend
                        const response = await fetch('api/delete_training.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });
                        
                        if (response.ok) {
                            // Remove from local array
                            trainings = trainings.filter(t => t.id != id);
                            updateUI();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Training has been deleted.',
                                timer: 1500
                            });
                        } else {
                            throw new Error('Delete failed');
                        }
                    } catch (error) {
                        console.error('Error deleting training:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete training. Please try again.',
                        });
                    }
                }
            });
        }

        // Reset form
        function resetForm() {
            editingTrainingId = null;
            viewingTrainingId = null;
            trainingForm.reset();
            departmentContainer.classList.add('hidden');
            roleContainer.classList.add('hidden');
            setDefaultDates();
            
            // Reset target audience to default
            document.getElementById('target-audience').value = '';
            document.getElementById('schedule-validation').innerHTML = '';
        }

        // Export functions to window for onclick handlers
        window.handleTargetAudienceChange = handleTargetAudienceChange;
        window.filterByType = filterByType;
        window.filterByStatus = filterByStatus;
        window.viewTraining = viewTraining;
        window.editTraining = editTraining;
        window.deleteTraining = deleteTraining;
    </script>
</body>
</html>