<?php
// gapanalysis.php - Main Application Interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Gap Analysis System</title>
    <!-- Tailwind CSS with DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gap-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .card-shadow {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .chart-container {
            position: relative;
            height: 280px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Custom styles for white background form elements */
        .input-bordered, 
        .select-bordered, 
        .textarea-bordered {
            background-color: white !important;
            border-color: #d1d5db !important;
        }
        
        .input-bordered:focus, 
        .select-bordered:focus, 
        .textarea-bordered:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            background-color: white !important;
        }
        
        .select-bordered option {
            background-color: white !important;
            color: #1f2937 !important;
        }
        
        .input-bordered::placeholder,
        .textarea-bordered::placeholder {
            color: #9ca3af !important;
        }
        
        /* Ensure all form inputs have white background */
        input[type="text"],
        input[type="date"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            background-color: white !important;
        }
        
        /* Override any DaisyUI styles that might interfere */
        .select:focus {
            background-color: white !important;
        }
        
        /* Modal specific styles */
        .modal-box {
            background-color: white !important;
        }
        
        /* Filter section specific */
        .filter-select,
        .filter-input {
            background-color: white !important;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 220px;
            }
            .summary-card {
                padding: 12px !important;
            }
            .filter-section {
                padding: 16px !important;
            }
        }
        @media (max-width: 640px) {
            .chart-container {
                height: 200px;
            }
            .mobile-stack {
                flex-direction: column;
            }
            .mobile-full {
                width: 100% !important;
            }
        }
        .table-row-hover:hover {
            background-color: #f9fafb;
        }
        .priority-critical {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .priority-high {
            background-color: #fef3c7;
            color: #d97706;
        }
        .priority-medium {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .priority-low {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        /* Ensure white background for all form controls */
        .form-control {
            background-color: white !important;
        }
        
        /* Add a subtle border to make white background more visible */
        .bg-white-border {
            border: 1px solid #e5e7eb;
            background-color: white;
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
      
      <main class="container mx-auto px-4 py-8">
    <div class="container mx-auto px-3 md:px-6 py-4 md:py-8">
        <!-- Header -->
        <header class="mb-6 md:mb-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Competency Gap Analysis</h1>
                    <p class="text-gray-600 text-sm md:text-base mt-1">Track and manage competency gaps across your organization</p>
                </div>
                <div class="mt-3 md:mt-0">
                    <div class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-sm">
                        <div class="w-2 h-2 rounded-full bg-green-500 mr-2"></div>
                        <span>Database: <span id="dbStatus" class="font-semibold">Checking...</span></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Summary Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
                <div class="bg-white card-shadow rounded-lg p-4 summary-card">
                    <div class="flex items-center">
                        <div class="bg-blue-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-users text-blue-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs md:text-sm">Total Employees</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800" id="totalEmployees">0</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white card-shadow rounded-lg p-4 summary-card">
                    <div class="flex items-center">
                        <div class="bg-amber-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-chart-bar text-amber-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs md:text-sm">Average Gap</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800" id="averageGap">0.0</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white card-shadow rounded-lg p-4 summary-card">
                    <div class="flex items-center">
                        <div class="bg-red-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs md:text-sm">Critical Gaps</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800" id="criticalGaps">0</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white card-shadow rounded-lg p-4 summary-card">
                    <div class="flex items-center">
                        <div class="bg-green-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-clipboard-check text-green-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs md:text-sm">Active Plans</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800" id="activePlans">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filter Section -->
        <div class="bg-white card-shadow rounded-xl p-4 md:p-6 mb-6 md:mb-10 filter-section">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 md:mb-6">
                <h3 class="text-lg md:text-xl font-semibold text-gray-800 mb-2 md:mb-0">Filter Data</h3>
                <div class="flex space-x-2">
                    <button class="btn btn-sm btn-ghost text-gray-600" id="resetFilters">
                        <i class="fas fa-redo mr-1"></i> Reset
                    </button>
                    <button class="btn btn-sm btn-primary" id="applyFilters">
                        <i class="fas fa-filter mr-1"></i> Apply
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select class="select select-bordered w-full bg-white text-sm md:text-base filter-select" id="departmentFilter">
                        <option value="all">All Departments</option>
                        <!-- Options will be populated from database -->
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Competency Type</label>
                    <select class="select select-bordered w-full bg-white text-sm md:text-base filter-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <!-- Options will be populated from database -->
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority Level</label>
                    <select class="select select-bordered w-full bg-white text-sm md:text-base filter-select" id="priorityFilter">
                        <option value="all">All Priorities</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>
            
            <div class="pt-4 border-t border-gray-100">
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div class="mb-3 md:mb-0">
                        <p class="text-sm text-gray-600 mb-2">Gap Level Indicators:</p>
                        <div class="flex flex-wrap gap-3">
                            <div class="flex items-center">
                                <div class="gap-indicator bg-red-500"></div>
                                <span class="text-xs md:text-sm">Critical (≥2)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="gap-indicator bg-amber-500"></div>
                                <span class="text-xs md:text-sm">Moderate (1)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="gap-indicator bg-green-500"></div>
                                <span class="text-xs md:text-sm">Minor/No Gap (<1)</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span id="filterCount">0</span> records found
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mb-8 md:mb-12">
            <!-- Bar Chart Card -->
            <div class="bg-white card-shadow rounded-xl p-4 md:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 md:mb-6">
                    <h3 class="text-lg md:text-xl font-semibold text-gray-800 mb-2 sm:mb-0">Required vs Actual Levels</h3>
                    <div class="flex space-x-2">
                        <button onclick="exportChart('barChart', 'competency-gap-bar-chart.png')" 
                                class="btn btn-xs md:btn-sm btn-ghost text-gray-600">
                            <i class="fas fa-download mr-1"></i> Export
                        </button>
                        <button onclick="toggleFullscreen('barChartContainer')" 
                                class="btn btn-xs md:btn-sm btn-ghost text-gray-600">
                            <i class="fas fa-expand mr-1"></i> Fullscreen
                        </button>
                    </div>
                </div>
                <div class="chart-container" id="barChartContainer">
                    <canvas id="barChart"></canvas>
                </div>
                <p class="text-gray-500 text-xs md:text-sm mt-3">Comparison of required and actual competency proficiency levels</p>
            </div>

            <!-- Radar Chart Card -->
            <div class="bg-white card-shadow rounded-xl p-4 md:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 md:mb-6">
                    <h3 class="text-lg md:text-xl font-semibold text-gray-800 mb-2 sm:mb-0">Gap Analysis by Type</h3>
                    <div class="flex space-x-2">
                        <button onclick="exportChart('radarChart', 'competency-type-radar-chart.png')" 
                                class="btn btn-xs md:btn-sm btn-ghost text-gray-600">
                            <i class="fas fa-download mr-1"></i> Export
                        </button>
                        <button onclick="toggleFullscreen('radarChartContainer')" 
                                class="btn btn-xs md:btn-sm btn-ghost text-gray-600">
                            <i class="fas fa-expand mr-1"></i> Fullscreen
                        </button>
                    </div>
                </div>
                <div class="chart-container" id="radarChartContainer">
                    <canvas id="radarChart"></canvas>
                </div>
                <p class="text-gray-500 text-xs md:text-sm mt-3">Distribution of competency gaps across different types</p>
            </div>
        </div>

        <!-- Gap Analysis Table -->
        <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
            <div class="p-4 md:p-6 border-b border-gray-100">
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div>
                        <h3 class="text-lg md:text-xl font-semibold text-gray-800">Detailed Gap Analysis</h3>
                        <p class="text-gray-500 text-sm mt-1" id="tableSummary">No data available</p>
                    </div>
                    <div class="flex items-center mt-2 md:mt-0">
                        <div class="text-sm text-gray-600 mr-4" id="tableCount">0 records</div>
                        <div class="join">
                            <button class="join-item btn btn-xs md:btn-sm" id="prevPage">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="join-item btn btn-xs md:btn-sm btn-active" id="currentPage">1</button>
                            <button class="join-item btn btn-xs md:btn-sm" id="nextPage">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Competency</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Department</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Type</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Required</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Actual</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Gap</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Priority</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Plan Status</th>
                            <th class="text-left py-3 px-4 text-xs md:text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gapTableBody">
                        <!-- Data will be populated from database -->
                    </tbody>
                </table>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden p-8 md:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                    <i class="fas fa-search text-gray-400 text-2xl"></i>
                </div>
                <h4 class="text-lg font-medium text-gray-700 mb-2">No data found</h4>
                <p class="text-gray-500 max-w-md mx-auto">Try adjusting your filters or check back later for updates.</p>
            </div>
        </div>

        <!-- Action Plan Modal -->
        <dialog id="actionPlanModal" class="modal">
            <div class="modal-box max-w-2xl md:max-w-4xl bg-white p-0">
                <!-- Modal Header -->
                <div class="p-4 md:p-6 border-b border-gray-200 bg-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg md:text-xl font-semibold text-gray-800" id="modalTitle">Create Action Plan</h3>
                            <div class="flex items-center mt-1">
                                <span class="text-sm text-gray-600 mr-3" id="modalCompetency">-</span>
                                <span class="text-sm text-gray-600" id="modalEmployee">-</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-circle btn-ghost" id="closeModal">✕</button>
                    </div>
                </div>
                
                <!-- Gap Info -->
                <div class="p-4 md:p-6 bg-blue-50 border-b border-blue-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6">
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Competency Type</p>
                            <p class="font-medium" id="modalType">-</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Gap Score</p>
                            <p class="font-medium text-red-600" id="modalGapScore">0</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Current Status</p>
                            <div class="flex items-center justify-between">
                                <span class="font-medium" id="modalStatus">-</span>
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100" id="modalProgress">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Plan Form -->
                <form id="actionPlanForm" class="p-4 md:p-6 bg-white">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Plan Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="planName" class="input input-bordered w-full bg-white" 
                                       placeholder="Enter plan name" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Action Type <span class="text-red-500">*</span>
                                </label>
                                <select id="actionType" class="select select-bordered w-full bg-white" required>
                                    <option value="">Select type</option>
                                    <option value="Training">Training</option>
                                    <option value="Coaching">Coaching</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Self-Study">Self-Study</option>
                                    <option value="On-the-Job">On-the-Job</option>
                                    <option value="Mentoring">Mentoring</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" class="textarea textarea-bordered w-full h-24 md:h-32 bg-white" 
                                      placeholder="Describe the action plan, objectives, and expected outcomes..." required></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Resources Needed
                            </label>
                            <textarea id="resources" class="textarea textarea-bordered w-full h-20 md:h-24 bg-white" 
                                      placeholder="List any resources, materials, or tools needed..."></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="startDate" class="input input-bordered w-full bg-white" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="endDate" class="input input-bordered w-full bg-white" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Estimated Hours
                                </label>
                                <input type="number" id="estimatedHours" class="input input-bordered w-full bg-white" 
                                       min="1" value="8" placeholder="Hours">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Created By <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="createdBy" class="input input-bordered w-full bg-white" 
                                   value="Administrator" required>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="modal-action mt-6 pt-4 border-t border-gray-100 bg-white">
                        <button type="button" class="btn btn-ghost" id="cancelBtn">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="savePlan">
                            <i class="fas fa-save mr-2"></i> Save Action Plan
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Modal backdrop -->
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Footer -->
        <footer class="text-center py-4 md:py-6 border-t border-gray-200 bg-white card-shadow rounded-lg">
            <p class="text-gray-500 text-sm">Competency Gap Analysis System v1.0 • Data updated in real-time</p>
            <p class="text-gray-400 text-xs mt-1">© 2024 All rights reserved</p>
        </footer>
    </div>

    <!-- Main JavaScript -->
    <script>
        // Global variables
        let filteredData = [];
        let currentPage = 1;
        const itemsPerPage = 10;
        let barChart, radarChart;
        let currentGapId = null;

        // DOM Elements
        let totalEmployeesEl, averageGapEl, criticalGapsEl, activePlansEl;
        let departmentFilter, typeFilter, priorityFilter, applyFiltersBtn, resetFiltersBtn;
        let prevPageBtn, nextPageBtn, currentPageEl;
        let tableBody, tableCount, tableSummary, emptyState, filterCount;
        let actionPlanModal, modalCompetency, modalEmployee, modalGapScore, modalType, modalStatus, modalProgress;
        let planNameInput, actionTypeSelect, descriptionTextarea, resourcesTextarea;
        let startDateInput, endDateInput, estimatedHoursInput, createdByInput;
        let savePlanBtn, closeModalBtn, cancelBtn;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', async function() {
            await initializeApp();
        });

        async function initializeApp() {
            // Cache DOM elements
            cacheDOMElements();
            
            // Initialize modal
            initializeModal();
            
            // Check database connection
            await checkDatabaseConnection();
            
            // Load initial data
            await loadInitialData();
            
            // Initialize charts
            initializeCharts();
            
            // Apply initial filters
            await applyFilters();
            
            // Setup event listeners
            setupEventListeners();
            
            // Force white background on all form elements after page load
            setTimeout(forceWhiteBackgrounds, 100);
        }

        function cacheDOMElements() {
            // Summary cards
            totalEmployeesEl = document.getElementById('totalEmployees');
            averageGapEl = document.getElementById('averageGap');
            criticalGapsEl = document.getElementById('criticalGaps');
            activePlansEl = document.getElementById('activePlans');
            filterCount = document.getElementById('filterCount');
            
            // Filters
            departmentFilter = document.getElementById('departmentFilter');
            typeFilter = document.getElementById('typeFilter');
            priorityFilter = document.getElementById('priorityFilter');
            applyFiltersBtn = document.getElementById('applyFilters');
            resetFiltersBtn = document.getElementById('resetFilters');
            
            // Table elements
            prevPageBtn = document.getElementById('prevPage');
            nextPageBtn = document.getElementById('nextPage');
            currentPageEl = document.getElementById('currentPage');
            tableBody = document.getElementById('gapTableBody');
            tableCount = document.getElementById('tableCount');
            tableSummary = document.getElementById('tableSummary');
            emptyState = document.getElementById('emptyState');
        }

        function forceWhiteBackgrounds() {
            // Force white background on all form elements
            const allInputs = document.querySelectorAll('input, select, textarea');
            allInputs.forEach(element => {
                element.style.backgroundColor = 'white';
                element.style.borderColor = '#d1d5db';
            });
            
            // Force white background on select options
            const allSelects = document.querySelectorAll('select');
            allSelects.forEach(select => {
                select.style.backgroundColor = 'white';
                const options = select.querySelectorAll('option');
                options.forEach(option => {
                    option.style.backgroundColor = 'white';
                    option.style.color = '#1f2937';
                });
            });
            
            // Ensure modal has white background
            const modalBox = document.querySelector('.modal-box');
            if (modalBox) {
                modalBox.style.backgroundColor = 'white';
            }
        }

        function initializeModal() {
            // Get modal elements
            actionPlanModal = document.getElementById('actionPlanModal');
            modalCompetency = document.getElementById('modalCompetency');
            modalEmployee = document.getElementById('modalEmployee');
            modalGapScore = document.getElementById('modalGapScore');
            modalType = document.getElementById('modalType');
            modalStatus = document.getElementById('modalStatus');
            modalProgress = document.getElementById('modalProgress');
            
            // Form elements
            planNameInput = document.getElementById('planName');
            actionTypeSelect = document.getElementById('actionType');
            descriptionTextarea = document.getElementById('description');
            resourcesTextarea = document.getElementById('resources');
            startDateInput = document.getElementById('startDate');
            endDateInput = document.getElementById('endDate');
            estimatedHoursInput = document.getElementById('estimatedHours');
            createdByInput = document.getElementById('createdBy');
            
            // Buttons
            savePlanBtn = document.getElementById('savePlan');
            closeModalBtn = document.getElementById('closeModal');
            cancelBtn = document.getElementById('cancelBtn');
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            const nextMonthStr = nextMonth.toISOString().split('T')[0];
            
            startDateInput.value = today;
            endDateInput.value = nextMonthStr;
            startDateInput.min = today;
            endDateInput.min = today;
            
            // Force white background on modal form elements
            const modalFormElements = [
                planNameInput, actionTypeSelect, descriptionTextarea, 
                resourcesTextarea, startDateInput, endDateInput, 
                estimatedHoursInput, createdByInput
            ];
            
            modalFormElements.forEach(element => {
                if (element) {
                    element.style.backgroundColor = 'white';
                    element.style.borderColor = '#d1d5db';
                    element.addEventListener('focus', function() {
                        this.style.borderColor = '#3b82f6';
                        this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                    });
                    element.addEventListener('blur', function() {
                        this.style.borderColor = '#d1d5db';
                        this.style.boxShadow = 'none';
                    });
                }
            });
            
            // Add event listeners for modal
            savePlanBtn.addEventListener('click', saveActionPlan);
            closeModalBtn.addEventListener('click', () => actionPlanModal.close());
            cancelBtn.addEventListener('click', () => actionPlanModal.close());
            
            // Close modal when clicking outside
            actionPlanModal.addEventListener('click', (e) => {
                if (e.target === actionPlanModal) {
                    actionPlanModal.close();
                }
            });
            
            // When modal opens, ensure white backgrounds
            actionPlanModal.addEventListener('show', () => {
                setTimeout(forceWhiteBackgrounds, 50);
            });
        }

        function setupEventListeners() {
            applyFiltersBtn.addEventListener('click', applyFilters);
            resetFiltersBtn.addEventListener('click', resetFilters);
            prevPageBtn.addEventListener('click', () => changePage(-1));
            nextPageBtn.addEventListener('click', () => changePage(1));
            
            // Add event listeners to filter dropdowns
            [departmentFilter, typeFilter, priorityFilter].forEach(filter => {
                filter.addEventListener('change', applyFilters);
                // Ensure white background on filter changes
                filter.addEventListener('focus', function() {
                    this.style.backgroundColor = 'white';
                });
                filter.addEventListener('blur', function() {
                    this.style.backgroundColor = 'white';
                });
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && actionPlanModal.open) {
                    actionPlanModal.close();
                }
            });
            
            // Ensure all inputs maintain white background
            document.addEventListener('focusin', function(e) {
                if (e.target.matches('input, select, textarea')) {
                    e.target.style.backgroundColor = 'white';
                }
            });
            
            document.addEventListener('focusout', function(e) {
                if (e.target.matches('input, select, textarea')) {
                    e.target.style.backgroundColor = 'white';
                }
            });
        }

        async function checkDatabaseConnection() {
            try {
                const response = await fetch('config.php?action=test');
                const result = await response.json();
                const dbStatus = document.getElementById('dbStatus');
                if (result.status === 'success') {
                    dbStatus.textContent = 'Connected';
                    dbStatus.className = 'font-semibold text-green-600';
                } else {
                    dbStatus.textContent = 'Error';
                    dbStatus.className = 'font-semibold text-red-600';
                }
            } catch (error) {
                console.error('Database connection error:', error);
                document.getElementById('dbStatus').textContent = 'Failed to connect';
                document.getElementById('dbStatus').className = 'font-semibold text-red-600';
                showToast('Cannot connect to database. Please check your connection.', 'error');
            }
        }

        async function loadInitialData() {
            try {
                // Load summary statistics
                const statsResponse = await fetch('config.php?action=getSummaryStats');
                const stats = await statsResponse.json();
                
                // Update summary cards
                totalEmployeesEl.textContent = stats.totalEmployees || 0;
                averageGapEl.textContent = stats.averageGap || '0.0';
                criticalGapsEl.textContent = stats.criticalGaps || 0;
                activePlansEl.textContent = stats.activePlans || 0;
                
                // Populate department filter
                if (stats.departments && stats.departments.length > 0) {
                    stats.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept;
                        option.style.backgroundColor = 'white';
                        option.style.color = '#1f2937';
                        departmentFilter.appendChild(option);
                    });
                }
                
                // Populate type filter
                if (stats.types && stats.types.length > 0) {
                    stats.types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type;
                        option.textContent = type;
                        option.style.backgroundColor = 'white';
                        option.style.color = '#1f2937';
                        typeFilter.appendChild(option);
                    });
                }
                
                // Ensure filter dropdowns have white background
                [departmentFilter, typeFilter, priorityFilter].forEach(filter => {
                    filter.style.backgroundColor = 'white';
                });
                
            } catch (error) {
                console.error('Error loading data:', error);
                showToast('Error loading data from database', 'error');
            }
        }

        async function applyFilters() {
            const department = departmentFilter.value;
            const type = typeFilter.value;
            const priority = priorityFilter.value;
            
            try {
                // Show loading state
                showLoading();
                
                // Build query string for filters
                const params = new URLSearchParams();
                params.append('action', 'getFilteredData');
                if (department !== 'all') params.append('department', department);
                if (type !== 'all') params.append('type', type);
                if (priority !== 'all') params.append('priority', priority);
                
                const response = await fetch(`config.php?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                filteredData = Array.isArray(data) ? data : [];
                filterCount.textContent = filteredData.length;
                
                // Reset to first page
                currentPage = 1;
                
                // Update table
                renderTable();
                
                // Update charts with filtered data
                updateCharts();
                
                if (filteredData.length > 0) {
                    showToast(`${filteredData.length} records found`, 'success');
                }
                
            } catch (error) {
                console.error('Error applying filters:', error);
                showToast('Error applying filters: ' + error.message, 'error');
                filteredData = [];
                renderTable();
            } finally {
                hideLoading();
            }
        }

        function resetFilters() {
            departmentFilter.value = 'all';
            typeFilter.value = 'all';
            priorityFilter.value = 'all';
            
            applyFilters();
            showToast('Filters reset to default', 'info');
        }

        function renderTable() {
            // Clear table
            tableBody.innerHTML = '';
            
            if (!filteredData || filteredData.length === 0) {
                emptyState.classList.remove('hidden');
                tableBody.parentElement.style.display = 'none';
                tableCount.textContent = '0 records';
                tableSummary.textContent = 'No data available with current filters';
                currentPageEl.textContent = '1';
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
                return;
            }
            
            emptyState.classList.add('hidden');
            tableBody.parentElement.style.display = 'table';
            
            // Calculate pagination
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, filteredData.length);
            const pageData = filteredData.slice(startIndex, endIndex);
            
            // Populate table
            pageData.forEach(row => {
                // Determine gap color
                const gapScore = parseInt(row.gap_score) || 0;
                let gapColor = 'green-500';
                if (gapScore >= 2) gapColor = 'red-500';
                else if (gapScore === 1) gapColor = 'amber-500';
                
                // Determine priority class
                const priority = row.priority || 'Medium';
                let priorityClass = '';
                switch(priority) {
                    case 'Critical': priorityClass = 'priority-critical'; break;
                    case 'High': priorityClass = 'priority-high'; break;
                    case 'Medium': priorityClass = 'priority-medium'; break;
                    case 'Low': priorityClass = 'priority-low'; break;
                }
                
                // Determine plan status
                let planStatus = row.plan_status || 'No Plan';
                let planClass = 'text-gray-500';
                if (planStatus === 'In Progress') planClass = 'text-blue-600';
                else if (planStatus === 'Completed') planClass = 'text-green-600';
                else if (planStatus === 'Planned') planClass = 'text-amber-600';
                else if (planStatus === 'Delayed') planClass = 'text-red-600';
                
                const tableRow = document.createElement('tr');
                tableRow.className = 'table-row-hover';
                tableRow.innerHTML = `
                    <td class="py-3 px-4 text-sm">
                        <div class="font-medium text-gray-800">${row.competency_name || '-'}</div>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="text-gray-700">${row.employee_department || '-'}</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="badge badge-outline">${row.competency_type || '-'}</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="font-medium">${row.required_level || 0}/5</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="font-medium">${row.actual_level || 0}/5</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <div class="flex items-center">
                            <div class="gap-indicator bg-${gapColor}"></div>
                            <span class="font-semibold">${gapScore}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="status-badge ${priorityClass}">${priority}</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <span class="${planClass}">${planStatus}</span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="action-btn btn btn-xs btn-outline view-plan-btn" data-gap-id="${row.gap_id}" title="View Plan">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn btn btn-xs btn-primary create-plan-btn" data-gap-id="${row.gap_id}" title="Create Plan">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                // Add event listeners to buttons
                const viewBtn = tableRow.querySelector('.view-plan-btn');
                const createBtn = tableRow.querySelector('.create-plan-btn');
                
                viewBtn.addEventListener('click', () => openActionPlanModal(row.gap_id, 'view'));
                createBtn.addEventListener('click', () => openActionPlanModal(row.gap_id, 'create'));
                
                tableBody.appendChild(tableRow);
            });
            
            // Update pagination info
            tableCount.textContent = `${filteredData.length} records`;
            tableSummary.textContent = `Showing ${startIndex + 1}-${endIndex} of ${filteredData.length} gaps`;
            currentPageEl.textContent = currentPage;
            
            // Update pagination buttons
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;
        }

        async function openActionPlanModal(gapId, mode) {
            currentGapId = gapId;
            
            try {
                // Find the row data
                const rowData = filteredData.find(row => row.gap_id == gapId);
                
                if (!rowData) {
                    showToast('Could not find competency gap data', 'error');
                    return;
                }
                
                // Update modal header with gap details
                modalCompetency.textContent = rowData.competency_name || '-';
                modalEmployee.textContent = rowData.employee_name || '-';
                modalGapScore.textContent = rowData.gap_score || 0;
                modalType.textContent = rowData.competency_type || '-';
                
                // Set modal title based on mode
                const modalTitle = document.getElementById('modalTitle');
                if (mode === 'view') {
                    modalTitle.textContent = 'View Action Plan';
                } else {
                    modalTitle.textContent = 'Create Action Plan';
                }
                
                // Load existing action plan if viewing
                if (mode === 'view') {
                    const response = await fetch(`config.php?action=getActionPlan&gap_id=${gapId}`);
                    const planData = await response.json();
                    
                    if (planData && !planData.error) {
                        // Fill form with existing data
                        planNameInput.value = planData.plan_name || '';
                        actionTypeSelect.value = planData.action_type || '';
                        descriptionTextarea.value = planData.description || '';
                        resourcesTextarea.value = planData.resources_needed || '';
                        startDateInput.value = planData.start_date || '';
                        endDateInput.value = planData.end_date || '';
                        estimatedHoursInput.value = planData.estimated_hours || '';
                        createdByInput.value = planData.created_by || '';
                        
                        // Show status info
                        modalStatus.textContent = planData.status || 'No Status';
                        modalProgress.textContent = planData.progress_percentage ? `${planData.progress_percentage}%` : '-';
                        
                        // Update button text
                        savePlanBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Update Plan';
                    } else {
                        // No existing plan - show create mode
                        resetForm();
                        modalStatus.textContent = 'No Plan Created';
                        modalProgress.textContent = '-';
                        savePlanBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Create Plan';
                    }
                } else {
                    // Create mode - reset form
                    resetForm();
                    modalStatus.textContent = 'No Plan Created';
                    modalProgress.textContent = '-';
                    savePlanBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Create Plan';
                }
                
                // Show the modal
                actionPlanModal.showModal();
                
                // Force white backgrounds after modal opens
                setTimeout(forceWhiteBackgrounds, 50);
                
            } catch (error) {
                console.error('Error opening modal:', error);
                showToast('Error loading action plan details', 'error');
            }
        }

        function resetForm() {
            const today = new Date().toISOString().split('T')[0];
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            const nextMonthStr = nextMonth.toISOString().split('T')[0];
            
            planNameInput.value = '';
            actionTypeSelect.value = '';
            descriptionTextarea.value = '';
            resourcesTextarea.value = '';
            startDateInput.value = today;
            endDateInput.value = nextMonthStr;
            estimatedHoursInput.value = '8';
            
            // Ensure white background after reset
            forceWhiteBackgrounds();
        }

        async function saveActionPlan() {
            if (!currentGapId) {
                showToast('No competency gap selected', 'error');
                return;
            }
            
            // Validate form
            if (!planNameInput.value.trim()) {
                showToast('Please enter a plan name', 'error');
                planNameInput.focus();
                return;
            }
            
            if (!actionTypeSelect.value) {
                showToast('Please select an action type', 'error');
                actionTypeSelect.focus();
                return;
            }
            
            if (!descriptionTextarea.value.trim()) {
                showToast('Please enter a description', 'error');
                descriptionTextarea.focus();
                return;
            }
            
            if (!startDateInput.value || !endDateInput.value) {
                showToast('Please select start and end dates', 'error');
                return;
            }
            
            if (!createdByInput.value.trim()) {
                showToast('Please enter created by name', 'error');
                createdByInput.focus();
                return;
            }
            
            // Prepare data
            const planData = {
                action: 'saveActionPlan',
                gap_id: currentGapId,
                plan_name: planNameInput.value,
                action_type: actionTypeSelect.value,
                description: descriptionTextarea.value,
                resources: resourcesTextarea.value,
                start_date: startDateInput.value,
                end_date: endDateInput.value,
                estimated_hours: parseInt(estimatedHoursInput.value) || 0,
                created_by: createdByInput.value
            };
            
            try {
                // Show loading state
                savePlanBtn.disabled = true;
                savePlanBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
                
                const response = await fetch('config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(planData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Action plan saved successfully!', 'success');
                    actionPlanModal.close();
                    
                    // Refresh the table to show updated plan status
                    applyFilters();
                } else {
                    showToast(result.error || 'Failed to save action plan', 'error');
                }
            } catch (error) {
                console.error('Error saving action plan:', error);
                showToast('Error saving action plan', 'error');
            } finally {
                // Reset button state
                savePlanBtn.disabled = false;
                savePlanBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Action Plan';
            }
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            const newPage = currentPage + direction;
            
            if (newPage < 1 || newPage > totalPages) return;
            
            currentPage = newPage;
            renderTable();
            
            // Smooth scroll to top of table on mobile
            if (window.innerWidth < 768) {
                tableBody.parentElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function initializeCharts() {
            // Initialize Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Required Level',
                            data: [],
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Actual Level',
                            data: [],
                            backgroundColor: 'rgba(249, 115, 22, 0.7)',
                            borderColor: 'rgb(249, 115, 22)',
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 10,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}/5`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            title: {
                                display: true,
                                text: 'Proficiency Level (1-5)'
                            },
                            grid: {
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Initialize Radar Chart
            const radarCtx = document.getElementById('radarChart').getContext('2d');
            radarChart = new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: ['Hotel', 'Restaurant', 'General Skill', 'Core Skill', 'Soft Skill'],
                    datasets: [
                        {
                            label: 'Average Gap',
                            data: [0, 0, 0, 0, 0],
                            backgroundColor: 'rgba(139, 92, 246, 0.2)',
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgb(139, 92, 246)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(139, 92, 246)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 3,
                            ticks: {
                                stepSize: 0.5,
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            angleLines: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.3
                        }
                    }
                }
            });
        }

        async function updateCharts() {
            try {
                // Get chart data with current filters
                const department = departmentFilter.value;
                const params = new URLSearchParams();
                params.append('action', 'getChartData');
                if (department !== 'all') params.append('department', department);
                
                const response = await fetch(`config.php?${params.toString()}`);
                const chartData = await response.json();
                
                // Update bar chart
                if (chartData.barChart && chartData.barChart.length > 0) {
                    const labels = chartData.barChart.map(item => item.competency_name);
                    const avgTarget = chartData.barChart.map(item => parseFloat(item.avg_target) || 0);
                    const avgActual = chartData.barChart.map(item => parseFloat(item.avg_actual) || 0);
                    
                    updateBarChart(labels, avgTarget, avgActual);
                } else {
                    updateBarChart([], [], []);
                }
                
                // Update radar chart
                if (chartData.radarChart && chartData.radarChart.length > 0) {
                    const typeOrder = ['Hotel', 'Restaurant', 'General Skill', 'Core Skill', 'Soft Skill'];
                    const radarData = typeOrder.map(type => {
                        const typeData = chartData.radarChart.find(item => item.type === type);
                        return typeData ? parseFloat(typeData.avg_gap) || 0 : 0;
                    });
                    
                    updateRadarChart(radarData);
                } else {
                    updateRadarChart([0, 0, 0, 0, 0]);
                }
                
            } catch (error) {
                console.error('Error updating charts:', error);
                updateBarChart([], [], []);
                updateRadarChart([0, 0, 0, 0, 0]);
            }
        }

        function updateBarChart(labels, targetData, currentData) {
            barChart.data.labels = labels;
            barChart.data.datasets[0].data = targetData;
            barChart.data.datasets[1].data = currentData;
            barChart.update('none');
        }

        function updateRadarChart(data) {
            radarChart.data.datasets[0].data = data;
            radarChart.update('none');
        }

        function showToast(message, type = 'info') {
            // Remove any existing toast
            const existingToast = document.querySelector('.toast');
            if (existingToast) {
                existingToast.remove();
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast toast-bottom md:toast-top toast-end';
            
            let alertClass = 'alert-info';
            let icon = 'info-circle';
            if (type === 'success') {
                alertClass = 'alert-success';
                icon = 'check-circle';
            } else if (type === 'error') {
                alertClass = 'alert-error';
                icon = 'exclamation-circle';
            } else if (type === 'warning') {
                alertClass = 'alert-warning';
                icon = 'exclamation-triangle';
            }
            
            toast.innerHTML = `
                <div class="alert ${alertClass} shadow-lg max-w-sm">
                    <div class="flex items-center">
                        <i class="fas fa-${icon} mr-2"></i>
                        <span class="text-sm">${message}</span>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
        }

        function showLoading() {
            applyFiltersBtn.disabled = true;
            applyFiltersBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        }

        function hideLoading() {
            applyFiltersBtn.disabled = false;
            applyFiltersBtn.innerHTML = '<i class="fas fa-filter mr-1"></i> Apply';
        }

        // Export functions for chart export
        function exportChart(chartId, filename) {
            const chartCanvas = document.getElementById(chartId);
            const link = document.createElement('a');
            link.download = filename;
            link.href = chartCanvas.toDataURL('image/png');
            link.click();
            showToast(`Chart exported as ${filename}`, 'success');
        }

        function toggleFullscreen(containerId) {
            const container = document.getElementById(containerId);
            if (!document.fullscreenElement) {
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        // Make functions available globally
        window.exportChart = exportChart;
        window.toggleFullscreen = toggleFullscreen;
    </script>
</body>
</html>