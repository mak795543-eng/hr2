<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Requests - Training Request System</title>
    <!-- Tailwind CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            transition: all 0.2s ease;
        }
        
        .request-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #d1d5db;
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
            
            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }
            
            .action-buttons button {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
            
            .modal-box {
                margin: 0.5rem;
                padding: 1rem;
                width: calc(100% - 1rem);
            }
        }
        
        /* Loading animation */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Empty state styling */
        .empty-state {
            opacity: 0.6;
        }
        
        /* Draft specific styling */
        .draft-card {
            border-left: 4px solid #6b7280;
        }
        
        .draft-highlight {
            background-color: #f9fafb;
            border-color: #e5e7eb;
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
                <!-- Header -->
                <div class="mb-6 lg:mb-8">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div class="mobile-text-center lg:text-left">
                            <h1 class="text-xl lg:text-2xl font-bold text-gray-800">
                                <i class="fas fa-file-draft text-gray-500 mr-2"></i>
                                Draft Requests
                            </h1>
                            <p class="text-gray-600 mt-1 text-sm lg:text-base">Manage your unsaved draft requests</p>
                        </div>
                        
                        <div class="flex gap-2 w-full lg:w-auto">
                            <a href="request.php" class="btn btn-outline w-full lg:w-auto">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Requests
                            </a>
                            <button onclick="clearAllDrafts()" class="btn btn-error btn-outline w-full lg:w-auto">
                                <i class="fas fa-trash mr-2"></i> Clear All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6 stats-grid">
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Total Drafts</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="totalDrafts">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-file-draft text-gray-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Location Drafts</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="locationDrafts">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-blue-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Budget Drafts</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="budgetDrafts">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                <i class="fas fa-money-bill text-green-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-card p-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs lg:text-sm text-gray-500">Logistics Drafts</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-800" id="logisticsDrafts">0</p>
                            </div>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                                <i class="fas fa-box text-purple-500 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="request-card overflow-hidden">
                    <!-- Header with Actions -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                            <div class="mobile-text-center lg:text-left">
                                <h2 class="text-lg font-semibold text-gray-800">Draft Management</h2>
                                <p class="text-sm text-gray-600">Continue, edit, or delete your draft requests</p>
                            </div>
                            
                            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-3 w-full lg:w-auto">
                                <div class="relative w-full lg:w-64">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" id="searchDrafts" placeholder="Search drafts..." 
                                           class="form-input-white pl-10 w-full" 
                                           onkeyup="searchDrafts()">
                                </div>
                                
                                <select id="draftTypeFilter" class="form-input-white py-2 px-3 w-full lg:w-auto" onchange="filterDrafts()">
                                    <option value="">All Types</option>
                                    <option value="location">Location</option>
                                    <option value="budget">Budget</option>
                                    <option value="logistics">Logistics</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Drafts Table - Desktop View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="font-semibold text-gray-700 table-header-mobile">Draft ID</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Type</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Training Title</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Purpose</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Last Modified</th>
                                    <th class="font-semibold text-gray-700 table-header-mobile">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="draftsTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile View Cards -->
                    <div id="mobileDraftsView" class="lg:hidden p-4 space-y-4">
                        <!-- Mobile cards will be populated here -->
                    </div>
                    
                    <!-- Loading State -->
                    <div id="loadingState" class="p-8 text-center">
                        <div class="inline-block loading-spinner rounded-full h-8 w-8 border-b-2 border-primary mb-4"></div>
                        <p class="text-gray-600">Loading drafts...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="emptyState" class="hidden p-8 text-center empty-state">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-file-draft text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No draft requests found</h3>
                        <p class="text-gray-500 mb-4">Create a new draft from the main requests page</p>
                        <a href="request.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Create New Request
                        </a>
                    </div>
                    
                    <!-- Error State -->
                    <div id="errorState" class="hidden p-8 text-center">
                        <div class="text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Failed to load drafts</h3>
                        <p class="text-gray-500 mb-4" id="errorMessage">Unable to load draft data</p>
                        <button onclick="loadDrafts()" class="btn btn-primary">
                            <i class="fas fa-redo mr-2"></i> Retry
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Continue Draft Modal -->
    <dialog id="continueDraftModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl bg-white mx-auto">
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <div>
                    <h3 class="text-xl font-bold text-gray-800" id="modalTitle">
                        <i class="fas fa-edit text-primary mr-2"></i>
                        Continue Draft
                    </h3>
                    <p class="text-gray-600 text-sm mt-1" id="modalSubtitle">Complete your draft request</p>
                </div>
                <button onclick="closeContinueModal()" class="btn btn-sm btn-circle btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="draftContent">
                <!-- Draft form will be loaded here -->
            </div>
            
            <div class="modal-action">
                <button onclick="closeContinueModal()" class="btn btn-ghost">Cancel</button>
                <button onclick="deleteDraft()" class="btn btn-error">Delete Draft</button>
                <button onclick="submitDraft()" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i>
                    <span id="submitDraftText">Submit Request</span>
                    <span id="submitDraftLoading" class="hidden">
                        <i class="fas fa-spinner fa-spin ml-2"></i>
                    </span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- JavaScript -->
    <script>
        // Configuration
        const API_CONFIG = {
            baseURL: 'http://localhost:3000/api',
            endpoints: {
                drafts: '/drafts',
                submitDraft: '/drafts/submit'
            }
        };
        
        const TYPE_CONFIG = {
            location: { 
                text: 'Location', 
                color: 'bg-blue-100 text-blue-800', 
                icon: 'fa-map-marker-alt',
                modal: 'locationModal'
            },
            budget: { 
                text: 'Budget', 
                color: 'bg-green-100 text-green-800', 
                icon: 'fa-money-bill',
                modal: 'budgetModal'
            },
            logistics: { 
                text: 'Logistics', 
                color: 'bg-purple-100 text-purple-800', 
                icon: 'fa-box',
                modal: 'logisticsModal'
            }
        };
        
        let drafts = [];
        let filteredDrafts = [];
        let currentDraftId = null;
        let currentDraftType = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadDrafts();
            setupResponsiveView();
            window.addEventListener('resize', setupResponsiveView);
        });

        function setupResponsiveView() {
            const isMobile = window.innerWidth < 1024;
            const tableElement = document.querySelector('table');
            const mobileViewElement = document.getElementById('mobileDraftsView');
            
            if (isMobile) {
                tableElement.classList.add('hidden');
                mobileViewElement.classList.remove('hidden');
            } else {
                tableElement.classList.remove('hidden');
                mobileViewElement.classList.add('hidden');
            }
        }

        // Load drafts from localStorage or API
        function loadDrafts() {
            showLoading();
            
            try {
                // Try to get from localStorage first
                const savedDrafts = localStorage.getItem('training_request_drafts');
                
                if (savedDrafts) {
                    drafts = JSON.parse(savedDrafts);
                    filteredDrafts = [...drafts];
                    renderDrafts();
                    updateStats();
                    showContent();
                } else {
                    // If no drafts in localStorage, show empty state
                    drafts = [];
                    filteredDrafts = [];
                    showEmptyState();
                    updateStats();
                }
                
                // You can also fetch from API here
                // fetchDraftsFromAPI();
                
            } catch (error) {
                console.error('Error loading drafts:', error);
                showError('Failed to load drafts from storage');
            }
        }

        function fetchDraftsFromAPI() {
            // Mock API call - replace with real API
            setTimeout(() => {
                const mockDrafts = [
                    {
                        id: 'draft-001',
                        draftId: 'LOC-2023-001-DRAFT',
                        type: 'location',
                        trainingTitle: 'Annual Training Seminar',
                        purpose: 'Quarterly employee training',
                        lastModified: '2023-10-15T10:30:00Z',
                        data: {
                            training_title: 'Annual Training Seminar',
                            purpose: 'Quarterly employee training',
                            department: 'hr',
                            requested_to_department: 'admin',
                            event_date: '2023-11-20',
                            participants: 50,
                            preferred_location: 'main_hall',
                            start_time: '09:00',
                            end_time: '17:00'
                        }
                    },
                    {
                        id: 'draft-002',
                        draftId: 'BUD-2023-001-DRAFT',
                        type: 'budget',
                        trainingTitle: 'Leadership Workshop',
                        purpose: 'Executive leadership training',
                        lastModified: '2023-10-14T15:45:00Z',
                        data: {
                            training_title: 'Leadership Workshop',
                            purpose: 'Executive leadership training',
                            department: 'hr',
                            requested_to_department: 'finance',
                            event_date: '2023-12-01',
                            participants: 20,
                            justification: 'Developing leadership skills',
                            budget_items: [
                                {
                                    category: 'trainer_speaker',
                                    description: 'External trainer fees',
                                    quantity: 1,
                                    unit_cost: 5000,
                                    remarks: 'Certified leadership coach'
                                }
                            ]
                        }
                    }
                ];
                
                drafts = mockDrafts;
                filteredDrafts = [...drafts];
                renderDrafts();
                updateStats();
                showContent();
            }, 500);
        }

        function renderDrafts() {
            if (filteredDrafts.length === 0) {
                showEmptyState();
                return;
            }
            
            // Render desktop table
            renderDesktopTable();
            
            // Render mobile cards
            renderMobileCards();
            
            showContent();
        }

        function renderDesktopTable() {
            const tbody = document.getElementById('draftsTableBody');
            
            tbody.innerHTML = filteredDrafts.map(draft => `
                <tr class="draft-highlight hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <div class="font-mono text-sm font-semibold">${draft.draftId}</div>
                        <div class="text-xs text-gray-500">ID: ${draft.id}</div>
                    </td>
                    <td class="py-3 px-4">
                        <span class="type-badge ${TYPE_CONFIG[draft.type].color}">
                            <i class="fas ${TYPE_CONFIG[draft.type].icon} mr-1"></i>
                            ${TYPE_CONFIG[draft.type].text}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="font-medium">${draft.trainingTitle}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">${draft.purpose}</div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="text-sm text-gray-700">${truncateText(draft.purpose, 50)}</div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="text-sm">${formatDate(draft.lastModified)}</div>
                        <div class="text-xs text-gray-500">${formatTime(draft.lastModified)}</div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex gap-2">
                            <button onclick="continueDraft('${draft.id}')" class="btn btn-xs btn-primary" title="Continue">
                                <i class="fas fa-play mr-1"></i> Continue
                            </button>
                            <button onclick="editDraft('${draft.id}')" class="btn btn-xs btn-outline" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteDraftById('${draft.id}')" class="btn btn-xs btn-error" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderMobileCards() {
            const container = document.getElementById('mobileDraftsView');
            
            container.innerHTML = filteredDrafts.map(draft => `
                <div class="request-card draft-card p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-mono text-sm font-bold text-gray-800">${draft.draftId}</div>
                            <span class="type-badge ${TYPE_CONFIG[draft.type].color} mt-1">
                                <i class="fas ${TYPE_CONFIG[draft.type].icon} mr-1"></i>
                                ${TYPE_CONFIG[draft.type].text}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">
                            ${formatDate(draft.lastModified)}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="font-medium text-gray-800 mb-1">${draft.trainingTitle}</div>
                        <div class="text-sm text-gray-600">${truncateText(draft.purpose, 80)}</div>
                    </div>
                    
                    <div class="flex gap-2 action-buttons">
                        <button onclick="continueDraft('${draft.id}')" class="btn btn-sm btn-primary flex-1">
                            <i class="fas fa-play mr-1"></i> Continue
                        </button>
                        <button onclick="editDraft('${draft.id}')" class="btn btn-sm btn-outline flex-1">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="deleteDraftById('${draft.id}')" class="btn btn-sm btn-error flex-1">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function continueDraft(draftId) {
            const draft = drafts.find(d => d.id === draftId);
            if (!draft) return;
            
            currentDraftId = draftId;
            currentDraftType = draft.type;
            
            // Set modal title
            document.getElementById('modalTitle').innerHTML = `
                <i class="fas ${TYPE_CONFIG[draft.type].icon} text-primary mr-2"></i>
                Continue ${TYPE_CONFIG[draft.type].text} Draft
            `;
            document.getElementById('modalSubtitle').textContent = draft.trainingTitle;
            
            // Load draft content into modal
            loadDraftContent(draft);
            
            // Show modal
            document.getElementById('continueDraftModal').showModal();
        }

        function loadDraftContent(draft) {
            const container = document.getElementById('draftContent');
            
            // Create a preview of the draft data
            let content = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-3">Draft Preview</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm text-gray-500">Training Title</label>
                                <div class="font-medium">${draft.data.training_title}</div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Purpose</label>
                                <div class="font-medium">${draft.data.purpose}</div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Department</label>
                                <div class="font-medium">${formatDepartment(draft.data.department)}</div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Event Date</label>
                                <div class="font-medium">${formatDate(draft.data.event_date)}</div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Participants</label>
                                <div class="font-medium">${draft.data.participants}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    This is a draft. Click "Submit Request" to complete and submit this request, 
                                    or "Delete Draft" to remove it.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = content;
        }

        function submitDraft() {
            const draft = drafts.find(d => d.id === currentDraftId);
            if (!draft) return;
            
            const submitBtn = document.getElementById('submitDraftText');
            const loadingSpinner = document.getElementById('submitDraftLoading');
            
            submitBtn.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            
            // Simulate API call
            setTimeout(() => {
                // Remove draft from storage
                drafts = drafts.filter(d => d.id !== currentDraftId);
                localStorage.setItem('training_request_drafts', JSON.stringify(drafts));
                
                // Update UI
                loadDrafts();
                
                // Close modal and show success message
                closeContinueModal();
                showNotification('Request submitted successfully!', 'success');
                
                // Reset buttons
                submitBtn.classList.remove('hidden');
                loadingSpinner.classList.add('hidden');
                
                // Redirect to main requests page
                setTimeout(() => {
                    window.location.href = 'request.php';
                }, 1000);
                
            }, 1500);
        }

        function deleteDraft() {
            if (!currentDraftId) return;
            
            if (confirm('Are you sure you want to delete this draft? This action cannot be undone.')) {
                deleteDraftById(currentDraftId);
                closeContinueModal();
            }
        }

        function deleteDraftById(draftId) {
            if (confirm('Delete this draft?')) {
                drafts = drafts.filter(d => d.id !== draftId);
                localStorage.setItem('training_request_drafts', JSON.stringify(drafts));
                loadDrafts();
                showNotification('Draft deleted successfully', 'success');
            }
        }

        function editDraft(draftId) {
            const draft = drafts.find(d => d.id === draftId);
            if (!draft) return;
            
            // For now, just continue the draft
            continueDraft(draftId);
            
            // In a real application, you would open the original form
            // with the draft data pre-filled for editing
        }

        function clearAllDrafts() {
            if (drafts.length === 0) {
                showNotification('No drafts to clear', 'info');
                return;
            }
            
            if (confirm('Are you sure you want to delete ALL draft requests? This action cannot be undone.')) {
                drafts = [];
                localStorage.removeItem('training_request_drafts');
                loadDrafts();
                showNotification('All drafts cleared successfully', 'success');
            }
        }

        function searchDrafts() {
            const searchTerm = document.getElementById('searchDrafts').value.toLowerCase();
            filterDrafts();
            
            if (searchTerm) {
                filteredDrafts = filteredDrafts.filter(draft =>
                    draft.trainingTitle.toLowerCase().includes(searchTerm) ||
                    draft.purpose.toLowerCase().includes(searchTerm) ||
                    draft.draftId.toLowerCase().includes(searchTerm)
                );
            }
            
            renderDrafts();
        }

        function filterDrafts() {
            const typeFilter = document.getElementById('draftTypeFilter').value;
            
            filteredDrafts = drafts.filter(draft => {
                return !typeFilter || draft.type === typeFilter;
            });
            
            renderDrafts();
            updateStats();
        }

        function updateStats() {
            const total = drafts.length;
            const location = drafts.filter(d => d.type === 'location').length;
            const budget = drafts.filter(d => d.type === 'budget').length;
            const logistics = drafts.filter(d => d.type === 'logistics').length;
            
            document.getElementById('totalDrafts').textContent = total;
            document.getElementById('locationDrafts').textContent = location;
            document.getElementById('budgetDrafts').textContent = budget;
            document.getElementById('logisticsDrafts').textContent = logistics;
        }

        function closeContinueModal() {
            document.getElementById('continueDraftModal').close();
            currentDraftId = null;
            currentDraftType = null;
        }

        // Utility functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        function formatDepartment(deptCode) {
            const departments = {
                'hr': 'HR Department',
                'it': 'IT Department',
                'operations': 'Operations',
                'marketing': 'Marketing',
                'sales': 'Sales',
                'admin': 'Admin Department',
                'finance': 'Finance Department',
                'logistics': 'Logistics Department'
            };
            return departments[deptCode] || deptCode;
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' :
                type === 'error' ? 'bg-red-50 border border-red-200 text-red-800' :
                'bg-blue-50 border border-blue-200 text-blue-800'
            }`;
            
            const icon = type === 'success' ? 'fa-check-circle' :
                        type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('draftsTableBody').innerHTML = '';
            document.getElementById('mobileDraftsView').innerHTML = '';
        }

        function showContent() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('errorState').classList.add('hidden');
        }

        function showEmptyState() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('draftsTableBody').innerHTML = '';
            document.getElementById('mobileDraftsView').innerHTML = '';
        }

        function showError(message) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
        }
    </script>
</body>
</html>