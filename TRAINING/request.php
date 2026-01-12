<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Request Management System</title>
    <!-- Tailwind CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JavaScript Files -->
    <script src="JS/main.js" defer></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#2563eb',
                        'secondary': '#475569',
                        'accent': '#0ea5e9',
                    }
                }
            }
        }
        
        // API Configuration
        window.API_CONFIG = {
            baseURL: 'api.php',
            endpoints: {
                getRequests: '?action=get_requests',
                createLocationRequest: '?action=create_location_request',
                createBudgetRequest: '?action=create_budget_request',
                createLogisticsRequest: '?action=create_logistics_request',
                deleteRequest: '?action=delete_request'
            }
        };
    </script>
    <style>
        /* White background for all form elements */
        .form-input-white {
            background-color: white !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            width: 100% !important;
            color: #374151 !important;
        }
        
        .form-input-white:focus {
            outline: none !important;
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
        }
        
        .select-white {
            background-color: white !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            color: #374151 !important;
        }
        
        .textarea-white {
            background-color: white !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            color: #374151 !important;
        }
        
        /* Status badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Type badges */
        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Card styling */
        .request-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        
        /* Read-only field styling */
        .readonly-field {
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
            color: #6b7280 !important;
            cursor: not-allowed;
        }
        
        /* Responsive table cell */
        .table-cell {
            padding: 12px 8px;
            vertical-align: middle;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .mobile-stack {
                flex-direction: column;
            }
            
            .mobile-full-width {
                width: 100% !important;
            }
            
            .mobile-text-center {
                text-align: center;
            }
            
            .mobile-p-2 {
                padding: 8px !important;
            }
            
            .mobile-hidden {
                display: none !important;
            }
            
            .mobile-show {
                display: block !important;
            }
            
            .table-header-mobile {
                font-size: 11px;
                padding: 8px 4px;
            }
            
            .table-cell-mobile {
                font-size: 12px;
                padding: 8px 4px;
            }
            
            .mobile-truncate {
                max-width: 120px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
        
        /* Modal responsiveness */
        @media (max-width: 640px) {
            .modal-box {
                margin: 0.5rem;
                padding: 1rem;
                width: calc(100% - 1rem);
            }
            
            .modal-action {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .modal-action button {
                width: 100%;
            }
        }
        
        /* Better spacing for small screens */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            .filter-grid {
                grid-template-columns: 1fr !important;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .action-buttons button {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Main Layout with Sidebar and Navbar -->
    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Sidebar (Hidden on mobile, shown on desktop) -->
        <div class="hidden lg:block">
            <?php include '../USM/sidebarr.php'; ?>
        </div>
        
        <!-- Main Content Area -->
        <div class="flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../USM/navbar.php'; ?>
            
            <!-- Main Content -->
            <main class="container mx-auto px-3 lg:px-6 py-4 lg:py-6">
                <!-- Header -->
                <header class="mb-6 lg:mb-8">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div class="mobile-text-center lg:text-left">
                            <h1 class="text-xl lg:text-2xl font-bold text-gray-800">Training Request System</h1>
                            <p class="text-gray-600 mt-1 text-sm lg:text-base">Submit and track training requests</p>
                        </div>
                        
                        <!-- New Request Button with Dropdown -->
                        <div class="relative w-full lg:w-auto">
                            <button id="newRequestBtn" class="btn btn-primary w-full lg:w-auto">
                                <i class="fas fa-plus mr-2"></i> New Request
                            </button>
                            <div id="requestTypeDropdown" class="absolute hidden mt-2 w-full lg:w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                <a href="#" class="block px-4 py-3 hover:bg-blue-50 border-b border-gray-100" onclick="openRequestModal('location')">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-md bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-map-marker-alt text-blue-600 text-sm"></i>
                                        </div>
                                        <div class="font-medium text-gray-800">Location Request</div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Request venue for training</div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-green-50 border-b border-gray-100" onclick="openRequestModal('budget')">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-md bg-green-100 flex items-center justify-center">
                                            <i class="fas fa-money-bill text-green-600 text-sm"></i>
                                        </div>
                                        <div class="font-medium text-gray-800">Budget Request</div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Request training budget</div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-purple-50" onclick="openRequestModal('logistics')">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-md bg-purple-100 flex items-center justify-center">
                                            <i class="fas fa-box text-purple-600 text-sm"></i>
                                        </div>
                                        <div class="font-medium text-gray-800">Logistics Request</div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Request items for training</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Stats Cards - Responsive Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6 stats-grid">
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Total Requests</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="totalRequests">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-blue-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Draft</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="draftRequests">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-file-draft text-gray-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Submitted</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="submittedRequests">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-yellow-50 flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">This Month</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="monthRequests">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                <i class="fas fa-calendar text-green-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="request-card overflow-hidden">
                    <!-- Header with Search and Filters -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                            <div class="mobile-text-center lg:text-left">
                                <h2 class="text-lg font-semibold text-gray-800">Request Tracking</h2>
                                <p class="text-sm text-gray-600">Track all training requests</p>
                            </div>
                            
                            <!-- White background search and filters - Responsive layout -->
                            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-3 w-full lg:w-auto filter-grid">
                                <div class="relative w-full">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search requests..." 
                                           class="form-input-white pl-10 w-full" 
                                           onkeyup="searchRequests()">
                                </div>
                                
                                <div class="grid grid-cols-2 lg:flex gap-2 w-full lg:w-auto">
                                    <select id="typeFilter" class="select-white py-2 px-3 w-full" onchange="filterRequests()">
                                        <option value="">All Types</option>
                                        <option value="LOCATION">Location</option>
                                        <option value="BUDGET">Budget</option>
                                        <option value="LOGISTICS">Logistics</option>
                                    </select>
                                    
                                    <select id="statusFilter" class="select-white py-2 px-3 w-full" onchange="filterRequests()">
                                        <option value="">All Status</option>
                                        <option value="DRAFT">Draft</option>
                                        <option value="SUBMITTED">Submitted</option>
                                    </select>
                                    
                                    <select id="departmentFilter" class="select-white py-2 px-3 w-full" onchange="filterRequests()">
                                        <option value="">All Departments</option>
                                        <option value="HR Department">HR Department</option>
                                        <option value="IT Department">IT Department</option>
                                        <option value="Operations">Operations</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Admin Department">Admin Department</option>
                                        <option value="Finance Department">Finance Department</option>
                                        <option value="Logistics Department">Logistics Department</option>
                                        <option value="Facilities Management">Facilities Management</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Requests Table - Mobile Optimized -->
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="font-semibold text-gray-700 table-header-mobile">Req ID</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile hidden lg:table-cell">Type</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Training</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile hidden md:table-cell">Department</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile hidden lg:table-cell">Date</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Status</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTableBody">
                                <!-- Data will be loaded from main.js -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile View Cards (Alternative to table on small screens) -->
                    <div id="mobileRequestsView" class="hidden p-4 space-y-4">
                        <!-- Mobile cards will be populated here -->
                    </div>
                    
                    <!-- Loading State -->
                    <div id="loadingState" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-4"></div>
                        <p class="text-gray-600">Loading requests...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="emptyState" class="hidden p-8 text-center">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-clipboard-list text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No requests found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first request</p>
                        <button onclick="openRequestModal('location')" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Create Request
                        </button>
                    </div>
                    
                    <!-- Error State -->
                    <div id="errorState" class="hidden p-8 text-center">
                        <div class="text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Failed to load requests</h3>
                        <p class="text-gray-500 mb-4" id="errorMessage">Unable to connect to the server</p>
                        <button onclick="loadRequests()" class="btn btn-primary">
                            <i class="fas fa-redo mr-2"></i> Retry
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    <!-- Location Request Modal -->
    <dialog id="locationModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl bg-white mx-auto">
            <div class="flex justify-between items-center mb-4 lg:mb-6 pb-4 border-b">
                <div>
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                        Location Request
                    </h3>
                    <p class="text-gray-600 text-xs lg:text-sm mt-1">Request venue for training, seminar, or orientation</p>
                </div>
                <button onclick="closeModal('location')" class="btn btn-sm btn-circle btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="locationForm" class="space-y-4 lg:space-y-6">
                <!-- Basic Information -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Training/Seminar Title *
                            </label>
                            <input type="text" class="form-input-white" name="training_title" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Purpose *
                            </label>
                            <input type="text" class="form-input-white" name="purpose" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Department *
                            </label>
                            <select class="form-input-white" name="department" required>
                                <option value="">Select Department</option>
                                <option value="HR Department">HR Department</option>
                                <option value="IT Department">IT Department</option>
                                <option value="Operations">Operations</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Sales">Sales</option>
                                <option value="Admin Department">Admin Department</option>
                                <option value="Finance Department">Finance Department</option>
                                <option value="Logistics Department">Logistics Department</option>
                                <option value="Facilities Management">Facilities Management</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Requested To Department *
                            </label>
                            <select class="form-input-white" name="requested_to_department" required>
                                <option value="">Select Department</option>
                                <option value="Admin Department">Admin Department</option>
                                <option value="Facilities Management">Facilities Management</option>
                                <option value="Operations">Operations</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Expected Participants *
                            </label>
                            <input type="number" class="form-input-white" name="participants" min="1" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Event Date *
                            </label>
                            <input type="date" class="form-input-white" name="event_date" required>
                        </div>
                    </div>
                </div>
                
                <!-- Location Details -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Location Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Preferred Location *
                            </label>
                            <select class="form-input-white" name="preferred_location" required>
                                <option value="">Select Location</option>
                                <option value="Main Conference Hall">Main Conference Hall</option>
                                <option value="Executive Boardroom">Executive Boardroom</option>
                                <option value="Training Room A">Training Room A</option>
                                <option value="Training Room B">Training Room B</option>
                                <option value="Company Auditorium">Company Auditorium</option>
                                <option value="External Venue">External Venue</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Start Time *
                            </label>
                            <input type="time" class="form-input-white" name="start_time" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                End Time *
                            </label>
                            <input type="time" class="form-input-white" name="end_time" required>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Requirements -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Special Requirements
                    </label>
                    <textarea class="textarea-white w-full h-20 lg:h-24" name="special_requirements" 
                              placeholder="Audio-visual equipment, seating arrangement, internet access, etc."></textarea>
                </div>
                
                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Remarks
                    </label>
                    <textarea class="textarea-white w-full h-16 lg:h-20" name="remarks" 
                              placeholder="Additional notes or comments..."></textarea>
                </div>
                
                <div class="modal-action flex flex-col lg:flex-row gap-2">
                    <button type="button" onclick="closeModal('location')" class="btn btn-ghost w-full lg:w-auto">Cancel</button>
                    <button type="button" onclick="saveAsDraft('location')" class="btn btn-outline w-full lg:w-auto">Save as Draft</button>
                    <button type="submit" class="btn btn-primary w-full lg:w-auto">
                        <span id="locationSubmitText">Submit Request</span>
                        <span id="locationLoading" class="hidden">
                            <i class="fas fa-spinner fa-spin ml-2"></i>
                        </span>
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Budget Request Modal -->
    <dialog id="budgetModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl bg-white mx-auto">
            <div class="flex justify-between items-center mb-4 lg:mb-6 pb-4 border-b">
                <div>
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800">
                        <i class="fas fa-money-bill text-green-500 mr-2"></i>
                        Budget Request
                    </h3>
                    <p class="text-gray-600 text-xs lg:text-sm mt-1">Request budget for training, seminar, or orientation</p>
                </div>
                <button onclick="closeModal('budget')" class="btn btn-sm btn-circle btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="budgetForm" class="space-y-4 lg:space-y-6">
                <!-- Basic Information -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Training/Seminar Title *
                            </label>
                            <input type="text" class="form-input-white" name="training_title" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Purpose *
                            </label>
                            <input type="text" class="form-input-white" name="purpose" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Department *
                            </label>
                            <select class="form-input-white" name="department" required>
                                <option value="">Select Department</option>
                                <option value="HR Department">HR Department</option>
                                <option value="IT Department">IT Department</option>
                                <option value="Operations">Operations</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Sales">Sales</option>
                                <option value="Admin Department">Admin Department</option>
                                <option value="Finance Department">Finance Department</option>
                                <option value="Logistics Department">Logistics Department</option>
                                <option value="Facilities Management">Facilities Management</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Requested To Department *
                            </label>
                            <select class="form-input-white" name="requested_to_department" required>
                                <option value="">Select Department</option>
                                <option value="Finance Department">Finance Department</option>
                                <option value="Admin Department">Admin Department</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Event Date *
                            </label>
                            <input type="date" class="form-input-white" name="event_date" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Expected Participants *
                            </label>
                            <input type="number" class="form-input-white" name="participants" min="1" required>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Items -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Budget Items</h4>
                    <div id="budgetItemsContainer">
                        <!-- Budget items will be added here -->
                    </div>
                    
                    <button type="button" onclick="addBudgetItem()" class="btn btn-outline btn-sm w-full">
                        <i class="fas fa-plus mr-2"></i> Add Another Budget Item
                    </button>
                </div>
                
                <!-- Total Budget -->
                <div class="bg-blue-50 p-3 lg:p-4 rounded-lg">
                    <div class="flex flex-col lg:flex-row justify-between items-center">
                        <div class="mb-2 lg:mb-0">
                            <h4 class="font-medium text-gray-700">Total Estimated Cost</h4>
                            <p class="text-sm text-gray-600">Sum of all budget items</p>
                        </div>
                        <div class="text-xl lg:text-2xl font-bold text-blue-600" id="totalBudgetAmount">₱0.00</div>
                    </div>
                </div>
                
                <!-- Justification -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Justification *
                    </label>
                    <textarea class="textarea-white w-full h-20 lg:h-24" name="justification" required
                              placeholder="Explain why this budget is needed and how it will benefit the training..."></textarea>
                </div>
                
                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Remarks
                    </label>
                    <textarea class="textarea-white w-full h-16 lg:h-20" name="remarks" 
                              placeholder="Additional notes or comments..."></textarea>
                </div>
                
                <!-- Action Buttons -->
                <div class="modal-action flex flex-col lg:flex-row gap-2">
                    <div class="w-full lg:w-auto">
                        <button type="button" onclick="closeModal('budget')" class="btn btn-ghost w-full lg:w-auto">
                            <i class="fas fa-times mr-2"></i> Cancel/Close
                        </button>
                    </div>
                    <div class="flex flex-col lg:flex-row gap-2 w-full lg:w-auto">
                        <button type="button" onclick="saveAsDraft('budget')" class="btn btn-outline w-full lg:w-auto">
                            <i class="fas fa-save mr-2"></i> Save Draft
                        </button>
                        <button type="submit" class="btn btn-primary w-full lg:w-auto" id="budgetSubmitBtn">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span id="budgetSubmitText">Submit</span>
                            <span id="budgetLoading" class="hidden">
                                <i class="fas fa-spinner fa-spin ml-2"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Logistics Request Modal -->
    <dialog id="logisticsModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl bg-white mx-auto">
            <div class="flex justify-between items-center mb-4 lg:mb-6 pb-4 border-b">
                <div>
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800">
                        <i class="fas fa-box text-purple-500 mr-2"></i>
                        Logistics Request
                    </h3>
                    <p class="text-gray-600 text-xs lg:text-sm mt-1">Request items for training, seminar, or orientation</p>
                </div>
                <button onclick="closeModal('logistics')" class="btn btn-sm btn-circle btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="logisticsForm" class="space-y-4 lg:space-y-6">
                <!-- Basic Information -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Training/Seminar Title *
                            </label>
                            <input type="text" class="form-input-white" name="training_title" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Purpose *
                            </label>
                            <input type="text" class="form-input-white" name="purpose" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Department *
                            </label>
                            <select class="form-input-white" name="department" required>
                                <option value="">Select Department</option>
                                <option value="HR Department">HR Department</option>
                                <option value="IT Department">IT Department</option>
                                <option value="Operations">Operations</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Sales">Sales</option>
                                <option value="Admin Department">Admin Department</option>
                                <option value="Finance Department">Finance Department</option>
                                <option value="Logistics Department">Logistics Department</option>
                                <option value="Facilities Management">Facilities Management</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Requested To Department *
                            </label>
                            <select class="form-input-white" name="requested_to_department" required>
                                <option value="">Select Department</option>
                                <option value="Logistics Department">Logistics Department</option>
                                <option value="Admin Department">Admin Department</option>
                                <option value="IT Department">IT Department</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Event Date *
                            </label>
                            <input type="date" class="form-input-white" name="event_date" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Needed By Date *
                            </label>
                            <input type="date" class="form-input-white" name="needed_by_date" required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Expected Participants *
                            </label>
                            <input type="number" class="form-input-white" name="participants" min="1" required>
                        </div>
                    </div>
                </div>
                
                <!-- Logistics Items -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3 lg:mb-4 pb-2 border-b">Requested Items</h4>
                    <div id="logisticsItemsContainer">
                        <!-- Logistics items will be added here -->
                    </div>
                    
                    <button type="button" onclick="addLogisticsItem()" class="btn btn-outline btn-sm w-full">
                        <i class="fas fa-plus mr-2"></i> Add Another Item
                    </button>
                </div>
                
                <!-- Delivery Information -->
                <div class="bg-blue-50 p-3 lg:p-4 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2 lg:mb-3">Delivery Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Delivery Location *
                            </label>
                            <input type="text" class="form-input-white" name="delivery_location" 
                                   placeholder="e.g., Training Room A, 3rd Floor" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                                Contact Person *
                            </label>
                            <input type="text" class="form-input-white" name="contact_person" 
                                   placeholder="Name of person to receive items" required>
                        </div>
                    </div>
                </div>
                
                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Remarks
                    </label>
                    <textarea class="textarea-white w-full h-16 lg:h-20" name="remarks" 
                              placeholder="Additional notes, special handling instructions, or comments..."></textarea>
                </div>
                
                <!-- Action Buttons -->
                <div class="modal-action flex flex-col lg:flex-row gap-2">
                    <div class="w-full lg:w-auto">
                        <button type="button" onclick="closeModal('logistics')" class="btn btn-ghost w-full lg:w-auto">
                            <i class="fas fa-times mr-2"></i> Cancel/Close
                        </button>
                    </div>
                    <div class="flex flex-col lg:flex-row gap-2 w-full lg:w-auto">
                        <button type="button" onclick="saveAsDraft('logistics')" class="btn btn-outline w-full lg:w-auto">
                            <i class="fas fa-save mr-2"></i> Save Draft
                        </button>
                        <button type="submit" class="btn btn-primary w-full lg:w-auto">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span id="logisticsSubmitText">Submit</span>
                            <span id="logisticsLoading" class="hidden">
                                <i class="fas fa-spinner fa-spin ml-2"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- View Request Details Modal -->
    <dialog id="viewModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl bg-white mx-auto">
            <div class="flex justify-between items-center mb-4 lg:mb-6 pb-4 border-b">
                <div>
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800">
                        <i class="fas fa-eye text-primary mr-2"></i>
                        Request Details
                    </h3>
                    <p class="text-gray-600 text-xs lg:text-sm mt-1">View complete request information</p>
                </div>
                <button onclick="closeModal('view')" class="btn btn-sm btn-circle btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="viewModalContent" class="space-y-4 lg:space-y-6">
                <!-- Content will be populated by JavaScript -->
            </div>
            
            <div class="modal-action">
                <button onclick="closeModal('view')" class="btn btn-ghost">Close</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</body>
</html>