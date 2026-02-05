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
        .training-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .employee-item { transition: all 0.2s ease; cursor: pointer; }
        .employee-item:hover { background-color: #f3f4f6; }
        .employee-item.selected { background-color: #eff6ff; border-left: 4px solid #3b82f6; }
        
        /* Status colors */
        .status-planned { background-color: #8b5cf6; color: white; }
        .status-scheduled { background-color: #10b981; color: white; }
        .status-ongoing { background-color: #3b82f6; color: white; }
        .status-completed { background-color: #6b7280; color: white; }
        .status-archived { background-color: #374151; color: white; }
        
        /* Mode colors */
        .mode-online { background-color: #06b6d4; color: white; }
        .mode-onsite { background-color: #84cc16; color: white; }
        .mode-hybrid { background-color: #f59e0b; color: white; }
        
        /* Target audience colors */
        .target-employee { background-color: #8b5cf6; color: white; }
        .target-department { background-color: #10b981; color: white; }
        .target-company { background-color: #3b82f6; color: white; }
        
        /* Progress bar */
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
    </style>
</head>

  <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Training Management System</h1>
            <p class="text-gray-600">Manage all training programs, track progress, and analyze training effectiveness</p>
        </div>
        
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stats-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Trainings</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="total-trainings">0</h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13 0A9 9 0 008 3a9 9 0 00-6 16.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Capacity:</span>
                        <span class="font-medium" id="capacity-used">0%</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill" id="capacity-progress" style="width: 0%"></div>
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">This Month:</span>
                        <span class="font-medium" id="monthly-completion">0</span>
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
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

        <!-- Folder Structure Button -->
        <div class="mb-8">
            <button id="folder-structure-btn" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                View Training Program Folders
            </button>
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
                    <!-- Target Audience Filter -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                            Target Audience
                            <span class="badge badge-primary ml-2" id="target-filter-badge">All</span>
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByTarget('all')">All Audiences</a></li>
                            <li><a onclick="filterByTarget('Employee')" class="text-purple-600">Individual Employee</a></li>
                            <li><a onclick="filterByTarget('Department')" class="text-green-600">Department-based</a></li>
                            <li><a onclick="filterByTarget('Company')" class="text-blue-600">Company-wide</a></li>
                        </ul>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                            </svg>
                            Status
                            <span class="badge badge-primary ml-2" id="status-filter-badge">All</span>
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByStatus('all')">All Status</a></li>
                            <li><a onclick="filterByStatus('Planned')" class="text-purple-600">Planned</a></li>
                            <li><a onclick="filterByStatus('Scheduled')" class="text-green-600">Scheduled</a></li>
                            <li><a onclick="filterByStatus('Ongoing')" class="text-blue-600">Ongoing</a></li>
                            <li><a onclick="filterByStatus('Completed')" class="text-gray-600">Completed</a></li>
                            <li><a onclick="filterByStatus('Archived')" class="text-gray-800">Archived</a></li>
                        </ul>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="btn-group">
                        <button id="table-view-btn" class="btn btn-sm active">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 4a1 1 0 00-1 1v2a1 1 0 001 1h2a1 1 0 001-1V5a1 1 0 00-1-1H5zm0 4a1 1 0 00-1 1v2a1 1 0 001 1h2a1 1 0 001-1V9a1 1 0 00-1-1H5zm0 4a1 1 0 00-1 1v2a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 00-1-1H5zm4-8a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V5zm0 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V9zm0 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2zm4-8a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V5zm0 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V9zm0 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2z" clip-rule="evenodd" />
                            </svg>
                            Table
                        </button>
                        <button id="card-view-btn" class="btn btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Cards
                        </button>
                    </div>
                    
                    <button id="add-training-btn" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Training
                    </button>
                </div>
            </div>

            <!-- Training List Container -->
            <div id="training-list-container">
                <!-- Table View -->
                <div id="table-view" class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Training Code</th>
                                <th>Training Title</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Trainer</th>
                                <th>Start Date - Time</th>
                                <th>End Date - Time</th>
                                <th>Status</th>
                                <th>Mode</th>
                                <th>Target</th>
                                <th>Participants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="training-list">
                            <!-- Training items will be dynamically added here -->
                        </tbody>
                    </table>
                </div>

                <!-- Card View -->
                <div id="card-view" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="training-cards">
                        <!-- Training cards will be dynamically added here -->
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No training programs yet</h3>
                    <p class="text-gray-500 mb-4">Get started by creating your first training program.</p>
                    <button id="empty-add-btn" class="btn btn-primary">Add Training</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Folder Structure Modal -->
    <dialog id="folder-modal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl max-h-[80vh] overflow-y-auto">
            <h3 class="font-bold text-2xl mb-2">Training Program Folders</h3>
            <p class="text-gray-600 mb-6">Browse training programs by category or department</p>
            
            <div class="tabs mb-6">
                <a class="tab tab-bordered tab-active" onclick="switchFolderTab('category')">By Category</a>
                <a class="tab tab-bordered" onclick="switchFolderTab('department')">By Department</a>
            </div>
            
            <!-- Category Folders -->
            <div id="category-folders-content" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category folders will be populated here -->
                </div>
            </div>
            
            <!-- Department Folders -->
            <div id="department-folders-content" class="space-y-4 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Department folders will be populated here -->
                </div>
            </div>
            
            <div class="modal-action">
                <button id="close-folder-modal" class="btn btn-ghost">Close</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Add Training Modal -->
    <dialog id="training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold text-2xl mb-2">Create New Training Program</h3>
            <p class="text-gray-600 mb-6">Fill in all required information to create a new training program</p>
            
            <form id="training-form" class="space-y-6">
                <!-- Training Code (Auto-generated but editable) -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Training Code <span class="text-red-500">*</span></span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input id="training-code" type="text" class="input input-bordered w-full" required>
                        <button type="button" onclick="generateTrainingCode()" class="btn btn-outline btn-sm">
                            Generate
                        </button>
                    </div>
                </div>
                
                <!-- Training Title -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Training Title <span class="text-red-500">*</span></span>
                    </label>
                    <input id="training-title" type="text" placeholder="Enter training title" class="input input-bordered w-full" required>
                </div>
                
                <!-- Category Selection -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Category <span class="text-red-500">*</span></span>
                    </label>
                    <select id="training-category" class="select select-bordered w-full" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="Orientation & Onboarding">Orientation & Onboarding</option>
                        <option value="Compliance & Safety">Compliance & Safety</option>
                        <option value="Service Quality (Hotel & Restaurant)">Service Quality (Hotel & Restaurant)</option>
                        <option value="Technical Skills">Technical Skills</option>
                        <option value="Leadership & Supervisory">Leadership & Supervisory</option>
                    </select>
                </div>
                
                <!-- Target Audience (replaces training type) -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Target Audience <span class="text-red-500">*</span></span>
                    </label>
                    <select id="target-audience" class="select select-bordered w-full" required onchange="handleTargetAudienceChange()">
                        <option value="Employee" selected>Individual Employee</option>
                        <option value="Department">Department-based</option>
                        <option value="Company">Company-wide</option>
                    </select>
                </div>
                
                <!-- Department (conditionally shown) -->
                <div id="department-container" class="form-control fade-in">
                    <label class="label">
                        <span class="label-text font-semibold">Department</span>
                    </label>
                    <select id="training-department" class="select select-bordered w-full" onchange="handleDepartmentChange()">
                        <option value="" selected>Select a department</option>
                        <option value="Front Office">Front Office</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Food & Beverage">Food & Beverage</option>
                        <option value="Kitchen">Kitchen</option>
                        <option value="HR / Admin">HR / Admin</option>
                        <option value="All Departments">All Departments</option>
                    </select>
                </div>
                
                <!-- Employee Selection (for Individual Employee target) -->
                <div id="employee-container" class="form-control hidden fade-in">
                    <label class="label">
                        <span class="label-text font-semibold">Select Employee <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <input type="text" id="employee-search" placeholder="Search employees..." class="input input-bordered w-full mb-3" onkeyup="searchEmployees()">
                        <div class="absolute right-3 top-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div id="employee-list" class="border rounded-lg max-h-60 overflow-y-auto">
                        <!-- Employees will be populated here -->
                    </div>
                    <div id="selected-employee" class="mt-3 p-3 bg-blue-50 rounded-lg hidden">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-semibold" id="selected-employee-name"></span>
                                <div class="text-sm text-gray-600">
                                    <span id="selected-employee-dept"></span> • 
                                    <span id="selected-employee-position"></span>
                                </div>
                            </div>
                            <button type="button" onclick="clearSelectedEmployee()" class="btn btn-xs btn-ghost">
                                Remove
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="selected-employee-id">
                </div>
                
                <!-- Trainer -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Trainer</span>
                    </label>
                    <input id="trainer" type="text" placeholder="Enter trainer name" class="input input-bordered w-full">
                </div>
                
                <!-- Date and Time Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Start Date</span>
                        </label>
                        <input id="start-date" type="date" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Start Time</span>
                        </label>
                        <input id="start-time" type="time" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">End Date</span>
                        </label>
                        <input id="end-date" type="date" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">End Time</span>
                        </label>
                        <input id="end-time" type="time" class="input input-bordered w-full">
                    </div>
                </div>
                
                <!-- Participants Information (conditionally shown) -->
                <div id="participants-container" class="form-control fade-in">
                    <label class="label">
                        <span class="label-text font-semibold">Participants Needed <span class="text-red-500">*</span></span>
                        <span class="label-text-alt text-gray-500" id="participants-counter">Max: 20</span>
                    </label>
                    <input id="participants-needed" type="number" min="1" max="20" value="10" 
                           class="input input-bordered w-full" required
                           onchange="validateParticipants()"
                           oninput="updateParticipantsCounter()">
                    <div class="error-message" id="participants-error"></div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span id="min-participants">Minimum: 1</span>
                            <span id="max-participants">Maximum: 20</span>
                        </div>
                    </div>
                </div>
                
                <!-- Mode -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Mode <span class="text-red-500">*</span></span>
                    </label>
                    <select id="mode" class="select select-bordered w-full" required>
                        <option value="Online">Online</option>
                        <option value="Onsite" selected>Onsite</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                
                <!-- Description -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description</span>
                    </label>
                    <textarea id="description" class="textarea textarea-bordered h-32" placeholder="Enter training description"></textarea>
                </div>
            </form>
            
            <div class="modal-action">
                <button type="button" id="cancel-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="save-training-btn" class="btn btn-primary">Save Training</button>
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
                <span id="view-training-code" class="badge badge-outline"></span>
                <span id="view-training-status" class="badge"></span>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Category</h4>
                        <p id="view-category" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Target Audience</h4>
                        <p id="view-target-audience" class="badge"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="view-department-container">
                        <h4 class="font-semibold text-gray-700">Department</h4>
                        <p id="view-department" class="text-gray-900"></p>
                    </div>
                    <div id="view-employee-container" class="hidden">
                        <h4 class="font-semibold text-gray-700">Employee</h4>
                        <p id="view-employee" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Trainer</h4>
                        <p id="view-trainer" class="text-gray-900"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Start Date & Time</h4>
                        <p id="view-start-datetime" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">End Date & Time</h4>
                        <p id="view-end-datetime" class="text-gray-900"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Mode</h4>
                        <p id="view-mode" class="badge"></p>
                    </div>
                    <div id="view-participants-container">
                        <h4 class="font-semibold text-gray-700">Participants</h4>
                        <p id="view-participants" class="text-gray-900"></p>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" id="view-participants-progress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700">Description</h4>
                    <p id="view-description" class="text-gray-900 whitespace-pre-line"></p>
                </div>
            </div>
            
            <div class="modal-action">
                <button id="close-view-modal" class="btn btn-ghost">Close</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Edit Training Status Modal -->
    <dialog id="edit-status-modal" class="modal">
        <div class="modal-box w-11/12 max-w-md">
            <h3 class="font-bold text-2xl mb-2">Update Training Status</h3>
            <p class="text-gray-600 mb-6">Change the status of this training program</p>
            
            <div class="form-control mb-6">
                <label class="label">
                    <span class="label-text font-semibold">Status <span class="text-red-500">*</span></span>
                </label>
                <select id="edit-status-select" class="select select-bordered w-full" required>
                    <option value="Planned">Planned</option>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            
            <div class="modal-action">
                <button type="button" id="cancel-edit-status" class="btn btn-ghost">Cancel</button>
                <button type="button" id="save-status-btn" class="btn btn-primary">Update Status</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Add Training Modal -->
    <dialog id="training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold text-2xl mb-2">Create New Training Program</h3>
            <p class="text-gray-600 mb-6">Fill in all required information to create a new training program</p>
            
            <form id="training-form" class="space-y-6">
                <!-- Training Code (Auto-generated but editable) -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Training Code <span class="text-red-500">*</span></span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input id="training-code" type="text" class="input input-bordered w-full" required>
                        <button type="button" onclick="generateTrainingCode()" class="btn btn-outline btn-sm">
                            Generate
                        </button>
                    </div>
                </div>
                
                <!-- Training Title -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Training Title <span class="text-red-500">*</span></span>
                    </label>
                    <input id="training-title" type="text" placeholder="Enter training title" class="input input-bordered w-full" required>
                </div>
                
                <!-- Category Selection -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Category <span class="text-red-500">*</span></span>
                    </label>
                    <select id="training-category" class="select select-bordered w-full" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="Orientation & Onboarding">Orientation & Onboarding</option>
                        <option value="Compliance & Safety">Compliance & Safety</option>
                        <option value="Service Quality (Hotel & Restaurant)">Service Quality (Hotel & Restaurant)</option>
                        <option value="Technical Skills">Technical Skills</option>
                        <option value="Leadership & Supervisory">Leadership & Supervisory</option>
                    </select>
                </div>
                
                <!-- Target Audience (replaces training type) -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Target Audience <span class="text-red-500">*</span></span>
                    </label>
                    <select id="target-audience" class="select select-bordered w-full" required onchange="handleTargetAudienceChange()">
                        <option value="Employee" selected>Individual Employee</option>
                        <option value="Department">Department-based</option>
                        <option value="Company">Company-wide</option>
                    </select>
                </div>
                
                <!-- Department (conditionally shown) -->
                <div id="department-container" class="form-control fade-in">
                    <label class="label">
                        <span class="label-text font-semibold">Department</span>
                    </label>
                    <select id="training-department" class="select select-bordered w-full" onchange="handleDepartmentChange()">
                        <option value="" selected>Select a department</option>
                        <option value="Front Office">Front Office</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Food & Beverage">Food & Beverage</option>
                        <option value="Kitchen">Kitchen</option>
                        <option value="HR / Admin">HR / Admin</option>
                        <option value="All Departments">All Departments</option>
                    </select>
                </div>
                
                <!-- Employee Selection (for Individual Employee target) -->
                <div id="employee-container" class="form-control hidden fade-in">
                    <label class="label">
                        <span class="label-text font-semibold">Select Employee <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <input type="text" id="employee-search" placeholder="Search employees..." class="input input-bordered w-full mb-3" onkeyup="searchEmployees()">
                        <div class="absolute right-3 top-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div id="employee-list" class="border rounded-lg max-h-60 overflow-y-auto p-2">
                        <div class="p-4 text-center text-gray-500">Select a department first to view employees</div>
                    </div>
                    <div id="selected-employee" class="mt-3 p-3 bg-blue-50 rounded-lg hidden">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-semibold" id="selected-employee-name"></span>
                                <div class="text-sm text-gray-600">
                                    <span id="selected-employee-dept"></span> • 
                                    <span id="selected-employee-position"></span>
                                </div>
                            </div>
                            <button type="button" onclick="clearSelectedEmployee()" class="btn btn-xs btn-ghost">
                                Remove
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="selected-employee-id">
                </div>
                
                <!-- Trainer -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Trainer</span>
                    </label>
                    <input id="trainer" type="text" placeholder="Enter trainer name" class="input input-bordered w-full">
                </div>
                
                <!-- Date and Time Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Start Date</span>
                        </label>
                        <input id="start-date" type="date" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Start Time</span>
                        </label>
                        <input id="start-time" type="time" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">End Date</span>
                        </label>
                        <input id="end-date" type="date" class="input input-bordered w-full">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">End Time</span>
                        </label>
                        <input id="end-time" type="time" class="input input-bordered w-full">
                    </div>
                </div>
                
                <!-- Participants Information (ALWAYS SHOWN) -->
                <div id="participants-container" class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Participants Needed <span class="text-red-500">*</span></span>
                        <span class="label-text-alt text-gray-500" id="participants-counter">Max: 20</span>
                    </label>
                    <input id="participants-needed" type="number" min="1" max="20" value="1" 
                           class="input input-bordered w-full" required
                           onchange="validateParticipants()"
                           oninput="updateParticipantsCounter()">
                    <div class="error-message" id="participants-error"></div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span id="min-participants">Minimum: 1</span>
                            <span id="max-participants">Maximum: 20</span>
                        </div>
                    </div>
                </div>
                
                <!-- Mode -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Mode <span class="text-red-500">*</span></span>
                    </label>
                    <select id="mode" class="select select-bordered w-full" required>
                        <option value="Online">Online</option>
                        <option value="Onsite" selected>Onsite</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                
                <!-- Description -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description</span>
                    </label>
                    <textarea id="description" class="textarea textarea-bordered h-32" placeholder="Enter training description"></textarea>
                </div>
            </form>
            
            <div class="modal-action">
                <button type="button" id="cancel-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="save-training-btn" class="btn btn-primary">Save Training</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- ... (keep other modals) ... -->

    <script>
        // Data structures
        const folderCategories = [
            { 
                name: "Orientation & Onboarding", 
                description: "Training programs for new employee orientation and onboarding processes",
                icon: "users",
                color: "bg-blue-100",
                textColor: "text-blue-600",
                borderColor: "border-blue-200"
            },
            { 
                name: "Compliance & Safety", 
                description: "Mandatory compliance, safety, and regulatory training programs",
                icon: "shield",
                color: "bg-green-100",
                textColor: "text-green-600",
                borderColor: "border-green-200"
            },
            { 
                name: "Service Quality (Hotel & Restaurant)", 
                description: "Customer service and quality standards training for hospitality staff",
                icon: "star",
                color: "bg-yellow-100",
                textColor: "text-yellow-600",
                borderColor: "border-yellow-200"
            },
            { 
                name: "Technical Skills", 
                description: "Technical and operational skills training for specific roles",
                icon: "wrench",
                color: "bg-purple-100",
                textColor: "text-purple-600",
                borderColor: "border-purple-200"
            },
            { 
                name: "Leadership & Supervisory", 
                description: "Leadership development and supervisory skills training",
                icon: "award",
                color: "bg-red-100",
                textColor: "text-red-600",
                borderColor: "border-red-200"
            }
        ];

        const folderDepartments = [
            { 
                name: "Front Office", 
                description: "Training programs for front office, reception, and guest services staff",
                icon: "building",
                color: "bg-indigo-100",
                textColor: "text-indigo-600",
                borderColor: "border-indigo-200"
            },
            { 
                name: "Housekeeping", 
                description: "Training programs for housekeeping and room attendant staff",
                icon: "home",
                color: "bg-emerald-100",
                textColor: "text-emerald-600",
                borderColor: "border-emerald-200"
            },
            { 
                name: "Food & Beverage", 
                description: "Training programs for food and beverage service staff",
                icon: "coffee",
                color: "bg-amber-100",
                textColor: "text-amber-600",
                borderColor: "border-amber-200"
            },
            { 
                name: "Kitchen", 
                description: "Training programs for kitchen and culinary staff",
                icon: "utensils",
                color: "bg-orange-100",
                textColor: "text-orange-600",
                borderColor: "border-orange-200"
            },
            { 
                name: "HR / Admin", 
                description: "Training programs for human resources and administrative staff",
                icon: "briefcase",
                color: "bg-sky-100",
                textColor: "text-sky-600",
                borderColor: "border-sky-200"
            }
        ];

        // Training data - INITIALLY EMPTY (no sample data)
        let trainings = [];
        let currentTargetFilter = 'all';
        let currentStatusFilter = 'all';
        let editingTrainingId = null;
        let editingTrainingForStatus = null;
        let selectedEmployee = null;
        let filteredEmployees = [];

        // DOM Elements
        const addTrainingBtn = document.getElementById('add-training-btn');
        const emptyAddBtn = document.getElementById('empty-add-btn');
        const trainingModal = document.getElementById('training-modal');
        const folderModal = document.getElementById('folder-modal');
        const viewTrainingModal = document.getElementById('view-training-modal');
        const editStatusModal = document.getElementById('edit-status-modal');
        const cancelBtn = document.getElementById('cancel-btn');
        const saveTrainingBtn = document.getElementById('save-training-btn');
        const trainingForm = document.getElementById('training-form');
        const trainingList = document.getElementById('training-list');
        const trainingCards = document.getElementById('training-cards');
        const emptyState = document.getElementById('empty-state');
        const closeViewModal = document.getElementById('close-view-modal');
        const tableViewBtn = document.getElementById('table-view-btn');
        const cardViewBtn = document.getElementById('card-view-btn');
        const tableView = document.getElementById('table-view');
        const cardView = document.getElementById('card-view');
        const folderStructureBtn = document.getElementById('folder-structure-btn');
        const closeFolderModal = document.getElementById('close-folder-modal');
        const targetAudienceSelect = document.getElementById('target-audience');
        const departmentContainer = document.getElementById('department-container');
        const employeeContainer = document.getElementById('employee-container');
        const participantsContainer = document.getElementById('participants-container');
        const participantsNeededInput = document.getElementById('participants-needed');
        const participantsCounter = document.getElementById('participants-counter');
        const participantsError = document.getElementById('participants-error');
        const minParticipants = document.getElementById('min-participants');
        const maxParticipants = document.getElementById('max-participants');
        const cancelEditStatusBtn = document.getElementById('cancel-edit-status');
        const saveStatusBtn = document.getElementById('save-status-btn');
        const editStatusSelect = document.getElementById('edit-status-select');
        const employeeList = document.getElementById('employee-list');
        const employeeSearch = document.getElementById('employee-search');
        const selectedEmployeeDiv = document.getElementById('selected-employee');
        const selectedEmployeeName = document.getElementById('selected-employee-name');
        const selectedEmployeeDept = document.getElementById('selected-employee-dept');
        const selectedEmployeePosition = document.getElementById('selected-employee-position');
        const selectedEmployeeIdInput = document.getElementById('selected-employee-id');
        
        // Statistics elements
        const totalTrainingsEl = document.getElementById('total-trainings');
        const activeTrainingsEl = document.getElementById('active-trainings');
        const activeProgressEl = document.getElementById('active-progress');
        const totalParticipantsEl = document.getElementById('total-participants');
        const capacityUsedEl = document.getElementById('capacity-used');
        const capacityProgressEl = document.getElementById('capacity-progress');
        const completionRateEl = document.getElementById('completion-rate');
        const monthlyCompletionEl = document.getElementById('monthly-completion');
        const upcomingTrainingsEl = document.getElementById('upcoming-trainings');
        const weekTrainingsEl = document.getElementById('week-trainings');
        
        // Filter badges
        const targetFilterBadge = document.getElementById('target-filter-badge');
        const statusFilterBadge = document.getElementById('status-filter-badge');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // No localStorage loading - trainings array is empty
            updateUI();
            updateStatistics();
            setupEventListeners();
            
            // Set min dates for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start-date').min = today;
            document.getElementById('end-date').min = today;
            
            // Initialize participants counter
            updateParticipantsCounter();
        });

        function setupEventListeners() {
            addTrainingBtn.addEventListener('click', () => {
                resetForm();
                generateTrainingCode();
                trainingModal.showModal();
            });

            emptyAddBtn.addEventListener('click', () => {
                resetForm();
                generateTrainingCode();
                trainingModal.showModal();
            });

            cancelBtn.addEventListener('click', () => {
                trainingModal.close();
                resetForm();
            });

            saveTrainingBtn.addEventListener('click', saveTraining);

            closeViewModal.addEventListener('click', () => {
                viewTrainingModal.close();
            });

            // View toggle
            tableViewBtn.addEventListener('click', () => switchView('table'));
            cardViewBtn.addEventListener('click', () => switchView('cards'));

            // Folder modal
            folderStructureBtn.addEventListener('click', () => {
                loadFolderModalContent();
                folderModal.showModal();
            });

            closeFolderModal.addEventListener('click', () => {
                folderModal.close();
            });

            // Edit status modal
            cancelEditStatusBtn.addEventListener('click', () => {
                editStatusModal.close();
                editingTrainingForStatus = null;
            });

            saveStatusBtn.addEventListener('click', updateTrainingStatus);

            // Form validation for dates
            const startDate = document.getElementById('start-date');
            const endDate = document.getElementById('end-date');
            const startTime = document.getElementById('start-time');
            const endTime = document.getElementById('end-time');
            
            startDate.addEventListener('change', () => {
                if (startDate.value && endDate.value && startDate.value > endDate.value) {
                    endDate.value = startDate.value;
                }
                endDate.min = startDate.value;
            });
        }

        // Handle target audience change
        window.handleTargetAudienceChange = function() {
            const targetAudience = targetAudienceSelect.value;
            
            // ALWAYS show participants container
            participantsContainer.classList.remove('hidden');
            participantsNeededInput.disabled = false;
            
            if (targetAudience === 'Company') {
                // Show department, hide employee selection
                departmentContainer.classList.remove('hidden');
                employeeContainer.classList.add('hidden');
                
                // For Company-wide: no limit on participants
                participantsNeededInput.min = 1;
                participantsNeededInput.max = 999; // Large number for company-wide
                participantsNeededInput.value = 50; // Default value for company-wide
                
                // Update labels
                participantsCounter.textContent = 'Company-wide (No strict limit)';
                minParticipants.textContent = 'Minimum: 1';
                maxParticipants.textContent = 'Maximum: No limit';
                
                // Clear employee selection
                clearSelectedEmployee();
                
            } else if (targetAudience === 'Employee') {
                // Show department and employee selection
                departmentContainer.classList.remove('hidden');
                
                // Show employee selection only if department is already selected
                const department = document.getElementById('training-department').value;
                if (department) {
                    employeeContainer.classList.remove('hidden');
                    loadEmployeesByDepartment(department);
                } else {
                    employeeContainer.classList.add('hidden');
                }
                
                // For Individual Employee: maximum is 1
                participantsNeededInput.min = 1;
                participantsNeededInput.max = 1;
                participantsNeededInput.value = 1;
                participantsNeededInput.disabled = true; // Lock at 1 for individual
                
                // Update labels
                participantsCounter.textContent = 'Individual Employee (Maximum: 1)';
                minParticipants.textContent = 'Fixed: 1';
                maxParticipants.textContent = 'Maximum: 1';
                
            } else if (targetAudience === 'Department') {
                // Show department, hide employee selection
                departmentContainer.classList.remove('hidden');
                employeeContainer.classList.add('hidden');
                
                // For Department-based: limit to 20
                participantsNeededInput.min = 1;
                participantsNeededInput.max = 20;
                participantsNeededInput.value = 10;
                
                // Update labels
                participantsCounter.textContent = 'Department-based (Max: 20)';
                minParticipants.textContent = 'Minimum: 1';
                maxParticipants.textContent = 'Maximum: 20';
                
                // Clear employee selection
                clearSelectedEmployee();
            }
            
            updateParticipantsCounter();
        }

        // Handle department change for employee selection
        window.handleDepartmentChange = function() {
            const department = document.getElementById('training-department').value;
            const targetAudience = document.getElementById('target-audience').value;
            
            if (targetAudience === 'Employee') {
                if (department) {
                    employeeContainer.classList.remove('hidden');
                    loadEmployeesByDepartment(department);
                } else {
                    employeeContainer.classList.add('hidden');
                    employeeList.innerHTML = '<div class="p-4 text-center text-gray-500">Select a department first to view employees</div>';
                    clearSelectedEmployee();
                }
            }
        }

        // Load employees by department from MySQL (placeholder function)
        async function loadEmployeesByDepartment(department) {
            try {
                // This would be replaced with actual MySQL fetch
                // Example: const response = await fetch(`/api/employees?department=${department}`);
                // const employees = await response.json();
                
                // For now, show loading and simulate API call
                employeeList.innerHTML = '<div class="p-4 text-center text-gray-500">Loading employees...</div>';
                
                // Simulate API delay
                setTimeout(() => {
                    // This is a placeholder - in real implementation, you would fetch from MySQL
                    // For demo purposes, we'll use a simulated empty result
                    filteredEmployees = []; // Empty array since we're not hardcoding data
                    
                    if (filteredEmployees.length === 0) {
                        employeeList.innerHTML = `
                            <div class="p-4 text-center">
                                <div class="text-gray-500 mb-2">No employees found in ${department}</div>
                                <div class="text-sm text-gray-400">In a real implementation, employees would be fetched from MySQL database</div>
                            </div>`;
                    } else {
                        employeeList.innerHTML = filteredEmployees.map(employee => `
                            <div class="employee-item p-3 border-b hover:bg-gray-50" onclick="selectEmployee(${employee.id})">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">${employee.firstName} ${employee.lastName}</div>
                                        <div class="text-sm text-gray-600">${employee.position} • ${employee.code}</div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                }, 300);
                
            } catch (error) {
                console.error('Error loading employees:', error);
                employeeList.innerHTML = '<div class="p-4 text-center text-red-500">Error loading employees</div>';
            }
        }

        // Search employees
        window.searchEmployees = function() {
            const searchTerm = employeeSearch.value.toLowerCase();
            
            if (filteredEmployees.length === 0) return;
            
            const searchResults = filteredEmployees.filter(employee => 
                employee.firstName.toLowerCase().includes(searchTerm) ||
                employee.lastName.toLowerCase().includes(searchTerm) ||
                employee.position.toLowerCase().includes(searchTerm) ||
                employee.code.toLowerCase().includes(searchTerm)
            );
            
            if (searchResults.length === 0) {
                employeeList.innerHTML = '<div class="p-4 text-center text-gray-500">No employees found matching your search</div>';
            } else {
                employeeList.innerHTML = searchResults.map(employee => `
                    <div class="employee-item p-3 border-b hover:bg-gray-50" onclick="selectEmployee(${employee.id})">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium">${employee.firstName} ${employee.lastName}</div>
                                <div class="text-sm text-gray-600">${employee.position} • ${employee.code}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }

        // Select employee
        window.selectEmployee = function(employeeId) {
            const employee = filteredEmployees.find(emp => emp.id === employeeId);
            if (employee) {
                selectedEmployee = employee;
                
                // Update UI
                selectedEmployeeName.textContent = `${employee.firstName} ${employee.lastName}`;
                selectedEmployeeDept.textContent = employee.department;
                selectedEmployeePosition.textContent = employee.position;
                selectedEmployeeIdInput.value = employee.id;
                selectedEmployeeDiv.classList.remove('hidden');
                
                // Auto-set participants needed to 1 for Individual Employee
                if (targetAudienceSelect.value === 'Employee') {
                    document.getElementById('participants-needed').value = 1;
                    updateParticipantsCounter();
                }
                
                // Scroll to selected employee display
                selectedEmployeeDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Clear selected employee
        window.clearSelectedEmployee = function() {
            selectedEmployee = null;
            selectedEmployeeDiv.classList.add('hidden');
            selectedEmployeeIdInput.value = '';
            
            // Reset participants needed if Individual Employee
            if (targetAudienceSelect.value === 'Employee') {
                document.getElementById('participants-needed').value = 1;
                updateParticipantsCounter();
            }
        }

        // Validate participants input
        window.validateParticipants = function() {
            const input = participantsNeededInput;
            const targetAudience = targetAudienceSelect.value;
            const value = parseInt(input.value);
            
            // Clear previous error
            input.classList.remove('input-error');
            participantsError.textContent = '';
            
            if (targetAudience === 'Employee') {
                // Individual Employee: must be exactly 1
                if (value !== 1) {
                    input.classList.add('input-error');
                    participantsError.textContent = 'Individual Employee training can only have 1 participant';
                    input.value = 1;
                }
            } else if (targetAudience === 'Department') {
                // Department-based: 1-20 limit
                if (value < 1) {
                    input.classList.add('input-error');
                    participantsError.textContent = 'Minimum 1 participant required';
                    input.value = 1;
                } else if (value > 20) {
                    input.classList.add('input-error');
                    participantsError.textContent = 'Maximum 20 participants allowed for department training';
                    input.value = 20;
                }
            } else if (targetAudience === 'Company') {
                // Company-wide: minimum 1
                if (value < 1) {
                    input.classList.add('input-error');
                    participantsError.textContent = 'Minimum 1 participant required';
                    input.value = 1;
                }
            }
            
            updateParticipantsCounter();
        }

        // Update participants counter
        window.updateParticipantsCounter = function() {
            const value = participantsNeededInput.value;
            const targetAudience = targetAudienceSelect.value;
            
            if (targetAudience === 'Company') {
                participantsCounter.textContent = `Company-wide: ${value} participants`;
                participantsCounter.classList.remove('text-red-500', 'text-yellow-500');
                participantsCounter.classList.add('text-green-500');
            } else if (targetAudience === 'Employee') {
                participantsCounter.textContent = `Individual Employee: ${value}/1`;
                if (value == 1) {
                    participantsCounter.classList.remove('text-red-500');
                    participantsCounter.classList.add('text-green-500');
                } else {
                    participantsCounter.classList.remove('text-green-500');
                    participantsCounter.classList.add('text-red-500');
                }
            } else if (targetAudience === 'Department') {
                const max = 20;
                participantsCounter.textContent = `Department: ${value}/${max}`;
                
                if (value >= max) {
                    participantsCounter.classList.remove('text-gray-500', 'text-yellow-500');
                    participantsCounter.classList.add('text-red-500');
                } else if (value >= max * 0.8) {
                    participantsCounter.classList.remove('text-gray-500', 'text-red-500');
                    participantsCounter.classList.add('text-yellow-500');
                } else {
                    participantsCounter.classList.remove('text-yellow-500', 'text-red-500');
                    participantsCounter.classList.add('text-green-500');
                }
            }
        }

        // Generate training code
        window.generateTrainingCode = function() {
            const year = new Date().getFullYear();
            const maxCode = trainings.reduce((max, t) => {
                const match = t.training_code.match(/TRN-\d{4}-(\d+)/);
                return match ? Math.max(max, parseInt(match[1])) : max;
            }, 0);
            
            const nextNumber = String(maxCode + 1).padStart(3, '0');
            document.getElementById('training-code').value = `TRN-${year}-${nextNumber}`;
        }

        // ... (keep all other functions: loadFolderModalContent, filterByCategory, filterByDepartment, 
        // filterByTarget, filterByStatus, switchFolderTab, switchView, updateUI, updateTrainingList,
        // updateTrainingCards, getStatusClass, getModeClass, getTargetClass, updateStatistics,
        // saveTraining, viewTraining, editTraining, openEditStatus, updateTrainingStatus, resetForm) ...

        // These functions remain exactly the same as before, just showing the function headers:
        function loadFolderModalContent() { /* same as before */ }
        window.filterByCategory = function(category) { /* same as before */ }
        window.filterByDepartment = function(department) { /* same as before */ }
        window.filterByTarget = function(target) { /* same as before */ }
        window.filterByStatus = function(status) { /* same as before */ }
        window.switchFolderTab = function(tab) { /* same as before */ }
        function switchView(viewType) { /* same as before */ }
        function updateUI() { /* same as before */ }
        function updateTrainingList(trainingsToShow = trainings) { /* same as before */ }
        function updateTrainingCards(trainingsToShow = trainings) { /* same as before */ }
        function getStatusClass(status) { /* same as before */ }
        function getModeClass(mode) { /* same as before */ }
        function getTargetClass(target) { /* same as before */ }
        function updateStatistics() { /* same as before */ }
        async function saveTraining() { 
            // Modified validation for participants
            if (!trainingForm.checkValidity()) {
                trainingForm.reportValidity();
                return;
            }

            const targetAudience = document.getElementById('target-audience').value;
            const department = document.getElementById('training-department').value;
            const participantsNeeded = parseInt(document.getElementById('participants-needed').value);
            
            // Validate based on target audience
            if (targetAudience === 'Employee') {
                if (!selectedEmployee) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Employee Required',
                        text: 'Please select an employee for Individual Employee training',
                    });
                    return;
                }
                
                if (participantsNeeded !== 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Participants',
                        text: 'Individual Employee training can only have 1 participant',
                    });
                    return;
                }
            } else if (targetAudience === 'Department') {
                if (!department) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Department Required',
                        text: 'Please select a department for Department-based training',
                    });
                    return;
                }
                
                if (participantsNeeded < 1 || participantsNeeded > 20) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Participants',
                        text: 'Department-based training requires 1-20 participants',
                    });
                    return;
                }
            } else if (targetAudience === 'Company') {
                if (participantsNeeded < 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Participants',
                        text: 'Company-wide training requires at least 1 participant',
                    });
                    return;
                }
            }

            // ... rest of saveTraining function remains the same ...
        }
        window.viewTraining = function(id) { /* same as before */ }
        window.editTraining = function(id) { 
            const training = trainings.find(t => t.id === id);
            if (training) {
                editingTrainingId = id;
                
                // Populate form fields
                document.getElementById('training-code').value = training.training_code;
                document.getElementById('training-title').value = training.training_title;
                document.getElementById('training-category').value = training.category;
                document.getElementById('target-audience').value = training.target_audience;
                
                // Trigger target audience change to show/hide appropriate fields
                handleTargetAudienceChange();
                
                // Set department value if applicable
                if (training.target_audience !== 'Company') {
                    document.getElementById('training-department').value = training.department || '';
                    
                    // For Individual Employee, simulate employee selection
                    if (training.target_audience === 'Employee' && training.selected_employee_id) {
                        // In real implementation, you would fetch the employee from MySQL
                        // For now, we'll just set the display values
                        setTimeout(() => {
                            // This simulates having selected an employee
                            selectedEmployeeName.textContent = training.selected_employee_name || 'Selected Employee';
                            selectedEmployeeDept.textContent = training.department || '';
                            selectedEmployeePosition.textContent = training.selected_employee_position || '';
                            selectedEmployeeIdInput.value = training.selected_employee_id || '';
                            selectedEmployeeDiv.classList.remove('hidden');
                        }, 100);
                    }
                }
                
                document.getElementById('trainer').value = training.trainer || '';
                document.getElementById('start-date').value = training.start_date || '';
                document.getElementById('start-time').value = training.start_time || '';
                document.getElementById('end-date').value = training.end_date || '';
                document.getElementById('end-time').value = training.end_time || '';
                document.getElementById('mode').value = training.mode;
                document.getElementById('participants-needed').value = training.participants_needed;
                document.getElementById('description').value = training.description || '';
                
                updateParticipantsCounter();
                
                trainingModal.showModal();
            }
        }
        window.openEditStatus = function(id) { /* same as before */ }
        async function updateTrainingStatus() { /* same as before */ }
        function resetForm() { /* same as before */ }
    </script>
</body>
</html>