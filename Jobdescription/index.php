<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Management System</title>
    <!-- Tailwind CSS + DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #ffffff;
            --accent: #000000;
            --light: #ecf0f1;
        }
        .btn-primary {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .modal-box {
            background-color: white;
        }
        input, textarea, select {
            background-color: white !important;
            border-color: #e5e7eb !important;
        }
        .loading {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .readonly-field {
            background-color: #f8f9fa !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
        }
        .draggable-item {
            transition: all 0.2s ease;
        }
        .draggable-item:hover {
            background-color: #f8fafc !important;
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        .sortable-drag {
            opacity: 0.8;
        }
        .action-btn {
            padding: 6px !important;
            min-height: 32px !important;
            height: 32px !important;
        }
        .vacancy-badge {
            min-width: 70px;
        }
        .filter-active {
            background-color: var(--secondary) !important;
            color: white !important;
            border-color: var(--secondary) !important;
        }
        /* New styles for dropdown and badges */
        .dropdown-content {
            background-color: white;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .menu-title {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        .department-check-icon {
            color: #3b82f6;
        }
        .filter-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .filter-badge:hover {
            opacity: 0.9;
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
                <div class="container mx-auto">
                    <!-- Header -->
                    <header class="mb-10">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800">Competency Management System</h1>
                                <p class="text-gray-600 mt-2">Manage job roles, qualifications, and requirements</p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <button id="refreshBtn" class="btn btn-outline border-gray-300">
                                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                            <div class="bg-white rounded-xl shadow p-5">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <i data-lucide="briefcase" class="w-8 h-8 text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-500">Total Job Roles</p>
                                        <h3 id="totalRoles" class="text-2xl font-bold">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl shadow p-5">
                                <div class="flex items-center">
                                    <div class="bg-green-100 p-3 rounded-lg">
                                        <i data-lucide="users" class="w-8 h-8 text-green-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-500">Total Vacancies</p>
                                        <h3 id="totalVacancies" class="text-2xl font-bold">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl shadow p-5">
                                <div class="flex items-center">
                                    <div class="bg-purple-100 p-3 rounded-lg">
                                        <i data-lucide="list-checks" class="w-8 h-8 text-purple-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-500">Requirements</p>
                                        <h3 id="totalRequirements" class="text-2xl font-bold">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl shadow p-5">
                                <div class="flex items-center">
                                    <div class="bg-orange-100 p-3 rounded-lg">
                                        <i data-lucide="building" class="w-8 h-8 text-orange-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-500">Departments</p>
                                        <h3 id="totalDepartments" class="text-2xl font-bold">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Loading State -->
                    <div id="loadingState" class="flex flex-col items-center justify-center py-12">
                        <div class="loading">
                            <i data-lucide="loader-2" class="w-12 h-12 text-blue-600 animate-spin"></i>
                        </div>
                        <p class="mt-4 text-gray-600">Loading job roles...</p>
                    </div>

                    <!-- Main Content -->
                    <div id="mainContent" class="hidden">
                        <!-- Search and Filter Section - UPDATED -->
                        <div class="mb-6 bg-white rounded-xl shadow p-4">
                            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center mb-4">
                                <!-- Search Bar - Left side -->
                                <div class="flex-grow w-full">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i data-lucide="search" class="w-5 h-5 text-gray-400"></i>
                                        </div>
                                        <input type="text" id="searchInput" 
                                               class="input input-bordered w-full pl-10 pr-10 h-12" 
                                               placeholder="Search job titles, descriptions, or qualifications...">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button id="clearSearchBtn" class="btn btn-ghost btn-sm hidden">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Department Filter Dropdown - Right side -->
                                <div class="w-full lg:w-auto">
                                    <div class="dropdown dropdown-bottom w-full lg:w-64">
                                        <div tabindex="0" role="button" class="btn btn-outline w-full h-12 justify-between">
                                            <div class="flex items-center">
                                                <i data-lucide="filter" class="w-4 h-4 mr-2 text-gray-500"></i>
                                                <span id="departmentFilterLabel" class="font-medium">All Departments</span>
                                            </div>
                                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                                        </div>
                                        <ul tabindex="0" class="dropdown-content z-[1] menu p-0 shadow bg-base-100 rounded-box w-full mt-1 max-h-64 overflow-y-auto border border-gray-200">
                                            <li class="border-b border-gray-100">
                                                <a href="javascript:void(0)" 
                                                   class="py-3 px-4 hover:bg-blue-50 active flex justify-between items-center"
                                                   onclick="setDepartmentFilter('all', 'All Departments')">
                                                    <div class="flex items-center">
                                                        <i data-lucide="grid" class="w-4 h-4 mr-2 text-gray-500"></i>
                                                        <span>All Departments</span>
                                                    </div>
                                                    <i data-lucide="check" class="w-4 h-4 department-check-icon text-blue-600" data-department="all"></i>
                                                </a>
                                            </li>
                                            <div id="departmentFilterList">
                                                <!-- Department filters will be loaded here dynamically -->
                                            </div>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Active Filters Badges -->
                            <div class="flex flex-wrap gap-2 mb-3" id="activeFilterBadges">
                                <!-- Active filter badges will appear here -->
                            </div>
                            
                            <!-- Results Count -->
                            <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    Showing <span id="showingCount" class="font-semibold">0</span> of 
                                    <span id="totalCount" class="font-semibold">0</span> job roles
                                </div>
                                <div class="text-sm text-gray-500" id="filterStatus">
                                    <!-- Filter status will be shown here -->
                                </div>
                            </div>
                        </div>

                        <!-- Cards Section -->
                        <div class="bg-transparent">
                            <div id="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Cards will be loaded here dynamically -->
                            </div>
                            
                            <!-- No Data Placeholder -->
                            <div id="noDataPlaceholder" class="hidden py-12 text-center">
                                <i data-lucide="folder-x" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-500 mb-2">No job roles found</h3>
                                <p class="text-gray-400 mb-4">No job roles match your search criteria</p>
                                <button onclick="clearAllFilters()" class="btn btn-primary">
                                    <i data-lucide="filter-x" class="w-4 h-4 mr-2"></i>
                                    Clear all filters
                                </button>
                            </div>
                            
                            <!-- Loading for cards -->
                            <div id="tableLoading" class="hidden py-8 text-center">
                                <div class="loading">
                                    <i data-lucide="loader-2" class="w-8 h-8 text-blue-600 animate-spin mx-auto"></i>
                                </div>
                                <p class="mt-2 text-gray-600">Filtering results...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Details Modal -->
    <dialog id="viewModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl bg-white p-0 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <div>
                    <h3 class="font-bold text-xl text-gray-800" id="viewJobTitle"></h3>
                    <div class="flex items-center mt-1">
                        <span id="viewDepartment" class="px-3 py-1 text-sm rounded-full mr-3"></span>
                        <span class="text-gray-500 text-sm">ID: <span id="viewJobId" class="font-mono font-medium"></span></span>
                        <span id="viewVacancies" class="ml-3 px-3 py-1 text-sm rounded-full"></span>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('viewModal').close()" class="btn btn-sm btn-circle btn-ghost">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-7rem)]">
                <!-- Job Description -->
                <div class="mb-8">
                    <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                        <i data-lucide="file-text" class="w-5 h-5 mr-2 text-blue-600"></i>
                        Job Description
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p id="viewJobDescription" class="text-gray-700 whitespace-pre-line"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Qualifications Section -->
                    <div>
                        <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                            <i data-lucide="graduation-cap" class="w-5 h-5 mr-2 text-green-600"></i>
                            Qualifications
                            <span class="ml-2 text-sm font-normal text-gray-500" id="qualificationsCount"></span>
                        </h4>
                        <div class="space-y-3" id="viewQualificationsList">
                            <!-- Qualifications will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Requirements Section -->
                    <div>
                        <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                            <i data-lucide="list-checks" class="w-5 h-5 mr-2 text-purple-600"></i>
                            Job Requirements
                            <span class="ml-2 text-sm font-normal text-gray-500" id="requirementsCount"></span>
                        </h4>
                        <div class="space-y-3" id="viewRequirementsList">
                            <!-- Requirements will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Edit Modal -->
    <dialog id="editModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl bg-white p-0 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <div>
                    <h3 class="font-bold text-xl text-gray-800">Edit Job Role Details</h3>
                    <p class="text-gray-500 text-sm mt-1">Job Title, Department and Vacancies are read-only</p>
                </div>
                <button type="button" onclick="document.getElementById('editModal').close()" class="btn btn-sm btn-circle btn-ghost">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-7rem)]">
                <!-- Read-only Job Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Job Title</span>
                        </label>
                        <input type="text" id="editJobTitle" class="input input-bordered w-full readonly-field" readonly>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Department</span>
                        </label>
                        <input type="text" id="editDepartment" class="input input-bordered w-full readonly-field" readonly>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Vacancies</span>
                        </label>
                        <input type="text" id="editVacancies" class="input input-bordered w-full readonly-field" readonly>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="mb-8">
                    <label class="label">
                        <span class="label-text text-gray-700 font-medium">Job Description *</span>
                    </label>
                    <textarea id="editJobDescription" rows="4" 
                              class="textarea textarea-bordered w-full bg-white" 
                              placeholder="Enter job responsibilities and duties..."
                              required></textarea>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Qualifications Section -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                <i data-lucide="graduation-cap" class="w-5 h-5 mr-2 text-green-600"></i>
                                Qualifications
                            </h4>
                            <button type="button" id="addQualificationBtn" class="btn btn-sm btn-success text-white">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        
                        <div id="qualificationsContainer" class="space-y-3 mb-4">
                            <!-- Qualifications will be added here -->
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                            Drag to reorder or click × to remove
                        </div>
                    </div>
                    
                    <!-- Requirements Section -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                <i data-lucide="list-checks" class="w-5 h-5 mr-2 text-purple-600"></i>
                                Job Requirements
                            </h4>
                            <button type="button" id="addRequirementBtn" class="btn btn-sm btn-primary text-white">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        
                        <div id="requirementsContainer" class="space-y-3 mb-4">
                            <!-- Requirements will be added here -->
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                            Drag to reorder or click × to remove
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" id="cancelEditBtn" class="btn btn-ghost">Cancel</button>
                    <button type="button" id="saveEditBtn" class="btn btn-primary text-white">
                        <span id="saveBtnText">Save Changes</span>
                        <span id="saveBtnLoading" class="hidden">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Template for qualification item -->
    <template id="qualificationTemplate">
        <div class="draggable-item bg-white border border-gray-200 rounded-lg p-3 flex items-start group">
            <div class="mr-3 cursor-move text-gray-400 hover:text-gray-600 mt-1">
                <i data-lucide="grip-vertical" class="w-4 h-4"></i>
            </div>
            <div class="flex-grow">
                <input type="text" class="input input-bordered input-sm w-full qualification-input" placeholder="Enter qualification" required>
                <div class="flex items-center mt-2">
                    <select class="select select-bordered select-xs qualification-type">
                        <option value="Education">Education</option>
                        <option value="Certification">Certification</option>
                        <option value="Experience">Experience</option>
                        <option value="Skill">Skill</option>
                        <option value="Other">Other</option>
                    </select>
                    <span class="text-xs text-gray-500 ml-2">Drag to reorder</span>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-ghost text-gray-400 hover:text-red-600 ml-2 remove-btn">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </template>

    <!-- Template for requirement item -->
    <template id="requirementTemplate">
        <div class="draggable-item bg-white border border-gray-200 rounded-lg p-3 flex items-start group">
            <div class="mr-3 cursor-move text-gray-400 hover:text-gray-600 mt-1">
                <i data-lucide="grip-vertical" class="w-4 h-4"></i>
            </div>
            <div class="flex-grow">
                <input type="text" class="input input-bordered input-sm w-full requirement-input" placeholder="Enter requirement" required>
                <div class="flex items-center mt-2">
                    <select class="select select-bordered select-xs requirement-category">
                        <option value="Skill">Skill</option>
                        <option value="Physical">Physical</option>
                        <option value="Mental">Mental</option>
                        <option value="Technical">Technical</option>
                        <option value="Personal">Personal</option>
                        <option value="Other">Other</option>
                    </select>
                    <label class="label cursor-pointer ml-2">
                        <input type="checkbox" class="checkbox checkbox-xs requirement-essential" checked>
                        <span class="label-text text-xs ml-1">Essential</span>
                    </label>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-ghost text-gray-400 hover:text-red-600 ml-2 remove-btn">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // API Configuration
        const API_BASE_URL = 'api.php';

        // Real API Service (connected to PHP backend)
        class CompetencyAPI {
            constructor() {
                this.baseUrl = API_BASE_URL;
            }

            // Helper method for API calls
            async fetchAPI(endpoint, method = 'GET', data = null) {
                const url = `${this.baseUrl}?endpoint=${endpoint}`;
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                };

                if (data && (method === 'POST' || method === 'PUT')) {
                    options.body = JSON.stringify(data);
                }

                try {
                    const response = await fetch(url, options);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return await response.json();
                } catch (error) {
                    console.error('API Error:', error);
                    throw error;
                }
            }

            // Get all job roles
            async getJobRoles() {
                return await this.fetchAPI('job-roles', 'GET');
            }

            // Get job role by ID
            async getJobRole(request_id) {
                return await this.fetchAPI(`job-roles&id=${request_id}`, 'GET');
            }

            // Update job role
            async updateJobRole(request_id, updateData) {
                const data = {
                    request_id: request_id,
                    description: updateData.description,
                    qualifications: updateData.qualifications,
                    requirements: updateData.requirements
                };
                return await this.fetchAPI('job-roles', 'PUT', data);
            }

            // Get departments
            async getDepartments() {
                return await this.fetchAPI('departments', 'GET');
            }

            // Get statistics
            async getStatistics() {
                return await this.fetchAPI('statistics', 'GET');
            }
        }

        // Application State
        const api = new CompetencyAPI();
        let currentEditId = null;
        let currentViewId = null;
        let qualificationsSortable = null;
        let requirementsSortable = null;
        
        // Filter State
        let allJobRoles = [];
        let filteredJobRoles = [];
        let departments = [];
        let currentDepartmentFilter = 'all';
        let currentSearchTerm = '';

        // DOM Elements
        const cardsContainer = document.getElementById('cardsContainer');
        const noDataPlaceholder = document.getElementById('noDataPlaceholder');
        const loadingState = document.getElementById('loadingState');
        const mainContent = document.getElementById('mainContent');
        const totalRolesElement = document.getElementById('totalRoles');
        const totalVacanciesElement = document.getElementById('totalVacancies');
        const totalDepartmentsElement = document.getElementById('totalDepartments');
        const totalRequirementsElement = document.getElementById('totalRequirements');
        const refreshBtn = document.getElementById('refreshBtn');
        const editModal = document.getElementById('editModal');
        const viewModal = document.getElementById('viewModal');
        
        // Filter elements - UPDATED
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const departmentFilterLabel = document.getElementById('departmentFilterLabel');
        const departmentFilterList = document.getElementById('departmentFilterList');
        const activeFilterBadges = document.getElementById('activeFilterBadges');
        const showingCountElement = document.getElementById('showingCount');
        const totalCountElement = document.getElementById('totalCount');
        const filterStatusElement = document.getElementById('filterStatus');
        const tableLoading = document.getElementById('tableLoading');

        // Edit modal elements
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const saveEditBtn = document.getElementById('saveEditBtn');
        const saveBtnText = document.getElementById('saveBtnText');
        const saveBtnLoading = document.getElementById('saveBtnLoading');
        const editJobTitle = document.getElementById('editJobTitle');
        const editDepartment = document.getElementById('editDepartment');
        const editVacancies = document.getElementById('editVacancies');
        const editJobDescription = document.getElementById('editJobDescription');
        const qualificationsContainer = document.getElementById('qualificationsContainer');
        const requirementsContainer = document.getElementById('requirementsContainer');
        const addQualificationBtn = document.getElementById('addQualificationBtn');
        const addRequirementBtn = document.getElementById('addRequirementBtn');

        // View modal elements
        const viewJobTitle = document.getElementById('viewJobTitle');
        const viewDepartment = document.getElementById('viewDepartment');
        const viewJobId = document.getElementById('viewJobId');
        const viewVacancies = document.getElementById('viewVacancies');
        const viewJobDescription = document.getElementById('viewJobDescription');
        const viewQualificationsList = document.getElementById('viewQualificationsList');
        const viewRequirementsList = document.getElementById('viewRequirementsList');
        const qualificationsCount = document.getElementById('qualificationsCount');
        const requirementsCount = document.getElementById('requirementsCount');

        // Templates
        const qualificationTemplate = document.getElementById('qualificationTemplate');
        const requirementTemplate = document.getElementById('requirementTemplate');

        // Initialize application
        async function initApp() {
            try {
                await loadAllData();
                
                // Set the check icon for "All Departments" as active initially
                const allDeptCheckIcon = document.querySelector('.department-check-icon[data-department="all"]');
                if (allDeptCheckIcon) {
                    allDeptCheckIcon.classList.remove('hidden');
                }
                
                // Initialize filter badges
                updateActiveFilterBadges();
                
                loadingState.classList.add('hidden');
                mainContent.classList.remove('hidden');
            } catch (error) {
                console.error('Error initializing app:', error);
                showError('Error loading data. Please refresh the page.');
            }
        }

        // Load all data (job roles and departments)
        async function loadAllData() {
            try {
                // Load job roles
                allJobRoles = await api.getJobRoles();
                
                // Load departments
                departments = await api.getDepartments();
                
                // Initialize filtered job roles
                filteredJobRoles = [...allJobRoles];
                
                // Update UI
                updateDepartmentFilters();
                applyFilters();
                await updateStatistics();
            } catch (error) {
                console.error('Error loading data:', error);
                throw error;
            }
        }

        // Update department filter UI - UPDATED
        function updateDepartmentFilters() {
            departmentFilterList.innerHTML = '';
            
            // Add department filters as dropdown items
            departments.forEach(dept => {
                const li = document.createElement('li');
                li.className = 'border-b border-gray-100 last:border-b-0';
                li.innerHTML = `
                    <a href="javascript:void(0)" 
                       class="py-3 px-4 hover:bg-blue-50 flex justify-between items-center"
                       onclick="setDepartmentFilter('${dept.request_id}', '${dept.name}')">
                        <div class="flex items-center">
                            <i data-lucide="building" class="w-4 h-4 mr-2 text-gray-500"></i>
                            <span>${dept.name}</span>
                        </div>
                        <i data-lucide="check" class="w-4 h-4 department-check-icon text-blue-600 hidden" data-department="${dept.request_id}"></i>
                    </a>
                `;
                departmentFilterList.appendChild(li);
            });
            
            // Update active state
            updateFilterButtonStates();
        }

        // Set department filter - UPDATED
        function setDepartmentFilter(departmentId, departmentName = '') {
            currentDepartmentFilter = departmentId;
            
            // Update dropdown label
            departmentFilterLabel.textContent = departmentName || 'All Departments';
            
            // Update UI
            updateFilterButtonStates();
            updateActiveFilterBadges();
            
            // Apply filters
            applyFilters();
        }

        // Update filter button states - UPDATED for dropdown
        function updateFilterButtonStates() {
            // Hide all check icons
            document.querySelectorAll('.department-check-icon').forEach(icon => {
                icon.classList.add('hidden');
            });
            
            // Show check icon for active filter
            const activeCheckIcon = document.querySelector(`.department-check-icon[data-department="${currentDepartmentFilter}"]`);
            if (activeCheckIcon) {
                activeCheckIcon.classList.remove('hidden');
            }
        }

        // Update active filter badges - UPDATED
        function updateActiveFilterBadges() {
            activeFilterBadges.innerHTML = '';
            
            // Add search filter badge
            if (currentSearchTerm.trim() !== '') {
                const searchBadge = document.createElement('div');
                searchBadge.className = 'badge badge-info gap-2 px-3 py-2';
                searchBadge.innerHTML = `
                    <i data-lucide="search" class="w-3 h-3"></i>
                    <span>"${currentSearchTerm}"</span>
                    <button onclick="clearSearch()" class="btn btn-xs btn-circle btn-ghost p-0">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                activeFilterBadges.appendChild(searchBadge);
            }
            
            // Add department filter badge
            if (currentDepartmentFilter !== 'all') {
                const dept = departments.find(d => d.request_id === currentDepartmentFilter);
                if (dept) {
                    const deptBadge = document.createElement('div');
                    deptBadge.className = 'badge badge-primary gap-2 px-3 py-2';
                    deptBadge.innerHTML = `
                        <i data-lucide="building" class="w-3 h-3"></i>
                        <span>${dept.name}</span>
                        <button onclick="clearDepartmentFilter()" class="btn btn-xs btn-circle btn-ghost p-0">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    `;
                    activeFilterBadges.appendChild(deptBadge);
                }
            }
            
            // Add clear all button if any filters are active
            if ((currentSearchTerm.trim() !== '' || currentDepartmentFilter !== 'all')) {
                const clearAllBadge = document.createElement('button');
                clearAllBadge.className = 'btn btn-xs btn-ghost text-gray-600 h-8';
                clearAllBadge.innerHTML = `
                    <i data-lucide="filter-x" class="w-3 h-3 mr-1"></i>
                    Clear all
                `;
                clearAllBadge.onclick = clearAllFilters;
                activeFilterBadges.appendChild(clearAllBadge);
            }
        }

        // Apply filters (search + department)
        function applyFilters() {
            // Show loading
            tableLoading.classList.remove('hidden');
            if (cardsContainer) cardsContainer.innerHTML = '';
            
            // Use setTimeout to allow UI to update and show loading state
            setTimeout(() => {
                let results = [...allJobRoles];
                
                // Apply department filter
                if (currentDepartmentFilter !== 'all') {
                    results = results.filter(job => 
                        job.department_id === currentDepartmentFilter
                    );
                }
                
                // Apply search filter
                if (currentSearchTerm.trim() !== '') {
                    const searchTerm = currentSearchTerm.toLowerCase().trim();
                    results = results.filter(job => {
                        // Search in job title
                        if (job.name.toLowerCase().includes(searchTerm)) return true;
                        
                        // Search in job description
                        if (job.description.toLowerCase().includes(searchTerm)) return true;
                        
                        // Search in qualifications
                        if (job.qualifications && job.qualifications.some(qual => 
                            qual.text.toLowerCase().includes(searchTerm)
                        )) return true;
                        
                        // Search in requirements
                        if (job.requirements && job.requirements.some(req => 
                            req.text.toLowerCase().includes(searchTerm)
                        )) return true;
                        
                        return false;
                    });
                }
                
                // Update filtered results
                filteredJobRoles = results;
                
                // Update UI
                renderFilteredResults();
                updateResultsCount();
                updateFilterStatus();
                
                // Hide loading
                tableLoading.classList.add('hidden');
            }, 100); // Small delay for better UX
        }

        // Render filtered results as cards
        function renderFilteredResults() {
            if (filteredJobRoles.length === 0) {
                cardsContainer.innerHTML = '';
                noDataPlaceholder.classList.remove('hidden');
                return;
            }

            noDataPlaceholder.classList.add('hidden');
            
            cardsContainer.innerHTML = filteredJobRoles.map(job => {
                // Format qualifications for display
                const qualificationsText = job.qualifications?.map(q => q.text).slice(0, 2) || [];
                const qualificationsHtml = qualificationsText.map(text => 
                    `<div class="text-sm text-gray-700 mb-1 flex items-start">
                        <i data-lucide="check" class="w-3 h-3 text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span class="line-clamp-1">${text}</span>
                    </div>`
                ).join('');
                
                // Format requirements for display
                const requirementsText = job.requirements?.map(r => r.text).slice(0, 2) || [];
                const requirementsHtml = requirementsText.map(text => 
                    `<div class="text-sm text-gray-700 mb-1 flex items-start">
                        <i data-lucide="circle" class="w-3 h-3 text-blue-500 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span class="line-clamp-1">${text}</span>
                    </div>`
                ).join('');
                
                // Get vacancy badge
                const vacancyBadge = getVacancyBadge(job.vacancies);
                
                // Truncate description
                const shortDescription = job.description.length > 120 
                    ? job.description.substring(0, 120) + '...' 
                    : job.description;
                
                return `
                    <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                        <!-- Card Header -->
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <span class="font-mono text-xs text-gray-500 font-medium bg-gray-50 px-2 py-1 rounded">${job.request_id}</span>
                                        ${vacancyBadge}
                                    </div>
                                    <h3 class="font-bold text-lg text-gray-800 line-clamp-1">${job.name}</h3>
                                </div>
                                <div class="dropdown dropdown-end">
                                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-square">
                                        <i data-lucide="more-vertical" class="w-5 h-5 text-gray-500"></i>
                                    </div>
                                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-40 border border-gray-200">
                                        <li>
                                            <button onclick="openViewModal('${job.request_id}')" class="text-blue-600 hover:text-blue-800">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                                View Details
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="openEditModal('${job.request_id}')" class="text-green-600 hover:text-green-800">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                                Edit Details
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <i data-lucide="building" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <span class="px-3 py-1 text-xs rounded-full ${getDepartmentColor(job.department_name)}">
                                    ${job.department_name}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="p-5">
                            <!-- Job Description -->
                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i data-lucide="file-text" class="w-4 h-4 text-gray-400 mr-2"></i>
                                    <h4 class="font-medium text-gray-700">Description</h4>
                                </div>
                                <p class="text-gray-600 text-sm line-clamp-3">${shortDescription}</p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Qualifications -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <i data-lucide="graduation-cap" class="w-4 h-4 text-gray-400 mr-2"></i>
                                            <h4 class="font-medium text-gray-700">Qualifications</h4>
                                        </div>
                                        <span class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                            ${job.qualifications?.length || 0}
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        ${qualificationsHtml}
                                        ${job.qualifications && job.qualifications.length > 2 ? 
                                            `<div class="text-xs text-gray-500 mt-1">
                                                +${job.qualifications.length - 2} more
                                            </div>` : ''}
                                    </div>
                                </div>
                                
                                <!-- Requirements -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <i data-lucide="list-checks" class="w-4 h-4 text-gray-400 mr-2"></i>
                                            <h4 class="font-medium text-gray-700">Requirements</h4>
                                        </div>
                                        <span class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                            ${job.requirements?.length || 0}
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        ${requirementsHtml}
                                        ${job.requirements && job.requirements.length > 2 ? 
                                            `<div class="text-xs text-gray-500 mt-1">
                                                +${job.requirements.length - 2} more
                                            </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="p-5 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                            <div class="flex space-x-2">
                                <button onclick="openViewModal('${job.request_id}')" 
                                        class="btn btn-sm btn-outline btn-primary flex-1">
                                    <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                    View
                                </button>
                                <button onclick="openEditModal('${job.request_id}')" 
                                        class="btn btn-sm btn-outline btn-success flex-1">
                                    <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            lucide.createIcons();
        }

        // Update results count display
        function updateResultsCount() {
            showingCountElement.textContent = filteredJobRoles.length;
            totalCountElement.textContent = allJobRoles.length;
        }

        // Update filter status message - UPDATED for better messages
        function updateFilterStatus() {
            let status = '';
            
            if (currentSearchTerm.trim() !== '' && currentDepartmentFilter !== 'all') {
                const dept = departments.find(d => d.request_id === currentDepartmentFilter)?.name || 'Selected Department';
                status = `Found ${filteredJobRoles.length} job roles for "${currentSearchTerm}" in ${dept}`;
            } else if (currentSearchTerm.trim() !== '') {
                status = `Found ${filteredJobRoles.length} job roles for "${currentSearchTerm}"`;
            } else if (currentDepartmentFilter !== 'all') {
                const dept = departments.find(d => d.request_id === currentDepartmentFilter)?.name || 'Selected Department';
                status = `Showing ${filteredJobRoles.length} job roles in ${dept}`;
            } else {
                status = `Showing all ${filteredJobRoles.length} job roles`;
            }
            
            filterStatusElement.textContent = status;
        }

        // Clear search - UPDATED
        function clearSearch() {
            searchInput.value = '';
            currentSearchTerm = '';
            clearSearchBtn.classList.add('hidden');
            updateActiveFilterBadges();
            applyFilters();
        }

        // Clear department filter - UPDATED
        function clearDepartmentFilter() {
            currentDepartmentFilter = 'all';
            departmentFilterLabel.textContent = 'All Departments';
            
            // Update check icons
            document.querySelectorAll('.department-check-icon').forEach(icon => {
                icon.classList.add('hidden');
            });
            
            const allDeptCheckIcon = document.querySelector('.department-check-icon[data-department="all"]');
            if (allDeptCheckIcon) {
                allDeptCheckIcon.classList.remove('hidden');
            }
            
            updateActiveFilterBadges();
            applyFilters();
        }

        // Clear all filters - UPDATED
        function clearAllFilters() {
            clearSearch();
            clearDepartmentFilter();
        }

        // Open view modal
        window.openViewModal = async function(request_id) {
            try {
                const job = await api.getJobRole(request_id);
                
                if (job && !job.error) {
                    currentViewId = request_id;
                    
                    // Set basic info
                    viewJobTitle.textContent = job.name;
                    viewJobId.textContent = job.request_id;
                    viewDepartment.textContent = job.department_name;
                    viewDepartment.className = `px-3 py-1 text-sm rounded-full ${getDepartmentColor(job.department_name)}`;
                    viewVacancies.innerHTML = getVacancyBadge(job.vacancies);
                    viewJobDescription.textContent = job.description;
                    
                    // Set qualifications
                    const qualifications = job.qualifications || [];
                    qualificationsCount.textContent = `(${qualifications.length})`;
                    viewQualificationsList.innerHTML = qualifications.map((qual, index) => {
                        const typeIcon = getQualificationTypeIcon(qual.type);
                        return `
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <div class="flex items-start">
                                    <div class="mr-3 mt-1">
                                        ${typeIcon}
                                    </div>
                                    <div class="flex-grow">
                                        <p class="text-gray-700">${qual.text}</p>
                                        <div class="flex items-center mt-2">
                                            <span class="px-2 py-1 text-xs rounded-full ${getQualificationTypeColor(qual.type)}">
                                                ${qual.type}
                                            </span>
                                            <span class="text-xs text-gray-500 ml-2">${index + 1}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    // Set requirements
                    const requirements = job.requirements || [];
                    requirementsCount.textContent = `(${requirements.length})`;
                    viewRequirementsList.innerHTML = requirements.map((req, index) => {
                        const essentialBadge = req.essential ? 
                            '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 ml-2">Essential</span>' : 
                            '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 ml-2">Preferred</span>';
                        
                        return `
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <div class="flex items-start">
                                    <div class="mr-3 mt-1">
                                        ${req.essential ? 
                                            '<i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>' : 
                                            '<i data-lucide="circle" class="w-5 h-5 text-gray-400"></i>'}
                                    </div>
                                    <div class="flex-grow">
                                        <p class="text-gray-700">${req.text}</p>
                                        <div class="flex items-center mt-2">
                                            <span class="px-2 py-1 text-xs rounded-full ${getRequirementCategoryColor(req.category)}">
                                                ${req.category}
                                            </span>
                                            ${essentialBadge}
                                            <span class="text-xs text-gray-500 ml-2">${index + 1}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    // Update icons in modal
                    lucide.createIcons();
                    viewModal.showModal();
                } else {
                    showError('Job role not found');
                }
            } catch (error) {
                console.error('Error opening view modal:', error);
                showError('Error loading job details. Please try again.');
            }
        };

        // Open edit modal
        window.openEditModal = async function(request_id) {
            try {
                const job = await api.getJobRole(request_id);
                
                if (job && !job.error) {
                    currentEditId = request_id;
                    
                    // Set read-only fields
                    editJobTitle.value = job.name;
                    editDepartment.value = job.department_name;
                    editVacancies.value = job.vacancies;
                    
                    // Set editable fields
                    editJobDescription.value = job.description;
                    
                    // Load qualifications
                    qualificationsContainer.innerHTML = '';
                    const qualifications = job.qualifications || [];
                    qualifications.forEach((qual, index) => {
                        addQualificationItem(qual.text, qual.type);
                    });
                    
                    // Load requirements
                    requirementsContainer.innerHTML = '';
                    const requirements = job.requirements || [];
                    requirements.forEach((req, index) => {
                        addRequirementItem(req.text, req.category, req.essential);
                    });
                    
                    // Initialize sortable
                    initializeSortable();
                    
                    editModal.showModal();
                } else {
                    showError('Job role not found');
                }
            } catch (error) {
                console.error('Error opening edit modal:', error);
                showError('Error loading job details. Please try again.');
            }
        };

        // Add qualification item
        function addQualificationItem(text = '', type = 'Education') {
            const template = qualificationTemplate.content.cloneNode(true);
            const container = template.querySelector('.qualification-input');
            const typeSelect = template.querySelector('.qualification-type');
            const removeBtn = template.querySelector('.remove-btn');
            
            if (text) container.value = text;
            typeSelect.value = type;
            
            removeBtn.addEventListener('click', function() {
                this.closest('.draggable-item').remove();
            });
            
            qualificationsContainer.appendChild(template);
            lucide.createIcons();
        }

        // Add requirement item
        function addRequirementItem(text = '', category = 'Skill', essential = true) {
            const template = requirementTemplate.content.cloneNode(true);
            const container = template.querySelector('.requirement-input');
            const categorySelect = template.querySelector('.requirement-category');
            const essentialCheckbox = template.querySelector('.requirement-essential');
            const removeBtn = template.querySelector('.remove-btn');
            
            if (text) container.value = text;
            categorySelect.value = category;
            essentialCheckbox.checked = essential;
            
            removeBtn.addEventListener('click', function() {
                this.closest('.draggable-item').remove();
            });
            
            requirementsContainer.appendChild(template);
            lucide.createIcons();
        }

        // Initialize sortable drag and drop
        function initializeSortable() {
            // Destroy existing instances
            if (qualificationsSortable) qualificationsSortable.destroy();
            if (requirementsSortable) requirementsSortable.destroy();
            
            // Initialize qualifications sortable
            qualificationsSortable = new Sortable(qualificationsContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.cursor-move',
                onEnd: function() {
                    lucide.createIcons();
                }
            });
            
            // Initialize requirements sortable
            requirementsSortable = new Sortable(requirementsContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.cursor-move',
                onEnd: function() {
                    lucide.createIcons();
                }
            });
        }

        // Save edited job
        saveEditBtn.addEventListener('click', async () => {
            if (!validateEditForm()) return;

            try {
                // Show loading state
                saveBtnText.classList.add('hidden');
                saveBtnLoading.classList.remove('hidden');
                saveEditBtn.disabled = true;

                // Collect qualifications
                const qualifications = Array.from(qualificationsContainer.querySelectorAll('.draggable-item')).map(item => {
                    const input = item.querySelector('.qualification-input');
                    const typeSelect = item.querySelector('.qualification-type');
                    return {
                        text: input.value.trim(),
                        type: typeSelect.value
                    };
                }).filter(q => q.text); // Remove empty entries

                // Collect requirements
                const requirements = Array.from(requirementsContainer.querySelectorAll('.draggable-item')).map(item => {
                    const input = item.querySelector('.requirement-input');
                    const categorySelect = item.querySelector('.requirement-category');
                    const essentialCheckbox = item.querySelector('.requirement-essential');
                    return {
                        text: input.value.trim(),
                        category: categorySelect.value,
                        essential: essentialCheckbox.checked
                    };
                }).filter(r => r.text); // Remove empty entries

                const updateData = {
                    description: editJobDescription.value.trim(),
                    qualifications,
                    requirements
                };

                await api.updateJobRole(currentEditId, updateData);
                
                // Reload data
                await loadAllData();
                
                // Close modal
                editModal.close();
                resetEditForm();
                
                showSuccess('Job details updated successfully!');
                
            } catch (error) {
                console.error('Error saving job:', error);
                showError('Error saving changes. Please try again.');
            } finally {
                // Reset button state
                saveBtnText.classList.remove('hidden');
                saveBtnLoading.classList.add('hidden');
                saveEditBtn.disabled = false;
            }
        });

        // Update statistics
        async function updateStatistics() {
            try {
                const stats = await api.getStatistics();
                totalRolesElement.textContent = stats.totalRoles || 0;
                totalVacanciesElement.textContent = stats.totalVacancies || 0;
                totalDepartmentsElement.textContent = stats.totalDepartments || 0;
                totalRequirementsElement.textContent = stats.totalRequirements || 0;
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }

        // Helper functions
        function getDepartmentColor(dept) {
            const colors = {
                'Kitchen & Culinary': 'bg-orange-100 text-orange-800',
                'Food & Beverage Service': 'bg-green-100 text-green-800',
                'Front Office': 'bg-blue-100 text-blue-800',
                'Housekeeping': 'bg-purple-100 text-purple-800',
                'Sales & Marketing': 'bg-indigo-100 text-indigo-800'
            };
            return colors[dept] || 'bg-gray-100 text-gray-800';
        }

        function getVacancyBadge(vacancies) {
            if (vacancies === 0) {
                return `<span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800 vacancy-badge inline-block text-center">
                    <i data-lucide="x-circle" class="w-3 h-3 inline mr-1"></i>No Vacancy
                </span>`;
            } else if (vacancies <= 2) {
                return `<span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 vacancy-badge inline-block text-center">
                    <i data-lucide="alert-circle" class="w-3 h-3 inline mr-1"></i>${vacancies}
                </span>`;
            } else {
                return `<span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 vacancy-badge inline-block text-center">
                    <i data-lucide="check-circle" class="w-3 h-3 inline mr-1"></i>${vacancies}
                </span>`;
            }
        }

        function getQualificationTypeColor(type) {
            const colors = {
                'Education': 'bg-blue-100 text-blue-800',
                'Certification': 'bg-green-100 text-green-800',
                'Experience': 'bg-purple-100 text-purple-800',
                'Skill': 'bg-yellow-100 text-yellow-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        function getQualificationTypeIcon(type) {
            const icons = {
                'Education': '<i data-lucide="book-open" class="w-5 h-5 text-blue-600"></i>',
                'Certification': '<i data-lucide="award" class="w-5 h-5 text-green-600"></i>',
                'Experience': '<i data-lucide="briefcase" class="w-5 h-5 text-purple-600"></i>',
                'Skill': '<i data-lucide="star" class="w-5 h-5 text-yellow-600"></i>',
                'Other': '<i data-lucide="file" class="w-5 h-5 text-gray-600"></i>'
            };
            return icons[type] || '<i data-lucide="file" class="w-5 h-5 text-gray-600"></i>';
        }

        function getRequirementCategoryColor(category) {
            const colors = {
                'Skill': 'bg-blue-100 text-blue-800',
                'Physical': 'bg-red-100 text-red-800',
                'Mental': 'bg-purple-100 text-purple-800',
                'Technical': 'bg-indigo-100 text-indigo-800',
                'Personal': 'bg-green-100 text-green-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[category] || 'bg-gray-100 text-gray-800';
        }

        // Form validation
        function validateEditForm() {
            const errors = [];
            
            if (!editJobDescription.value.trim()) {
                errors.push('Job Description is required');
                editJobDescription.focus();
            }
            
            // Check if there are any qualifications
            const qualificationInputs = Array.from(qualificationsContainer.querySelectorAll('.qualification-input'));
            const validQualifications = qualificationInputs.filter(input => input.value.trim()).length;
            
            if (validQualifications === 0) {
                errors.push('At least one qualification is required');
                if (qualificationInputs[0]) qualificationInputs[0].focus();
            }
            
            // Check if there are any requirements
            const requirementInputs = Array.from(requirementsContainer.querySelectorAll('.requirement-input'));
            const validRequirements = requirementInputs.filter(input => input.value.trim()).length;
            
            if (validRequirements === 0) {
                errors.push('At least one requirement is required');
                if (requirementInputs[0]) requirementInputs[0].focus();
            }

            if (errors.length > 0) {
                alert(errors.join('\n'));
                return false;
            }
            return true;
        }

        // Reset edit form
        function resetEditForm() {
            currentEditId = null;
            editJobDescription.value = '';
            qualificationsContainer.innerHTML = '';
            requirementsContainer.innerHTML = '';
        }

        // Show success message
        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'toast toast-top toast-center';
            successDiv.innerHTML = `
                <div class="alert alert-success">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(successDiv);
            lucide.createIcons();
            
            setTimeout(() => {
                document.body.removeChild(successDiv);
            }, 3000);
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'toast toast-top toast-center';
            errorDiv.innerHTML = `
                <div class="alert alert-error">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(errorDiv);
            lucide.createIcons();
            
            setTimeout(() => {
                document.body.removeChild(errorDiv);
            }, 3000);
        }

        // Event Listeners
        cancelEditBtn.addEventListener('click', () => {
            editModal.close();
            resetEditForm();
        });

        addQualificationBtn.addEventListener('click', () => {
            addQualificationItem();
        });

        addRequirementBtn.addEventListener('click', () => {
            addRequirementItem();
        });

        // Search input event listener
        searchInput.addEventListener('input', () => {
            currentSearchTerm = searchInput.value;
            
            // Show/hide clear button
            if (currentSearchTerm.trim() !== '') {
                clearSearchBtn.classList.remove('hidden');
            } else {
                clearSearchBtn.classList.add('hidden');
            }
            
            // Update active filter badges
            updateActiveFilterBadges();
            
            // Apply filters with debounce
            clearTimeout(searchInput.timeout);
            searchInput.timeout = setTimeout(() => {
                applyFilters();
            }, 300); // 300ms debounce
        });

        // Clear search button
        clearSearchBtn.addEventListener('click', clearSearch);

        // Refresh button
        refreshBtn.addEventListener('click', async () => {
            try {
                loadingState.classList.remove('hidden');
                mainContent.classList.add('hidden');
                await loadAllData();
                loadingState.classList.add('hidden');
                mainContent.classList.remove('hidden');
                updateActiveFilterBadges();
                showSuccess('Data refreshed successfully!');
            } catch (error) {
                console.error('Error refreshing data:', error);
                showError('Error refreshing data. Please try again.');
                loadingState.classList.add('hidden');
                mainContent.classList.remove('hidden');
            }
        });

        // Initialize the application
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>