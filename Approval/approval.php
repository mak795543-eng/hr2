<?php
session_start();

// testing user session variable
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Dashboard</title>
    <!-- Tailwind CSS & DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
        }
        
        .table-container {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table-row {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }
        
        .table-row:hover {
            background-color: #f9fafb;
        }
        
        .tab-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        
        /* Modal Styling */
        .modal-box {
            padding: 0;
            overflow: hidden;
            max-width: 700px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 0.75rem;
            background: white;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-content {
            padding: 1.5rem;
            max-height: calc(90vh - 180px);
            overflow-y: auto;
            background: white;
        }
        
        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        /* Section Styling */
        .section-divider {
            margin: 1.5rem 0;
            border-top: 2px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 0.875rem;
            color: #1f2937;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .info-value-large {
            font-size: 1rem;
            color: #111827;
            font-weight: 600;
        }
        
        /* Status Badges */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Approval Level Badges */
        .level-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            display: inline-block;
        }
        
        .level-low {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .level-medium {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .level-high {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Category Badges */
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Level Cards */
        .level-card {
            border-left: 4px solid #3b82f6;
            margin-bottom: 0.75rem;
            background: #f8fafc;
            border-radius: 0.375rem;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            transition: all 0.2s;
        }
        
        .level-card:hover {
            transform: translateX(2px);
            background: #f1f5f9;
        }
        
        /* Tabs Container */
        .tabs-container {
            display: flex;
            background: #f1f5f9;
            padding: 0.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            background: transparent;
            color: #64748b;
            flex: 1;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .tab-btn.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            padding: 1.25rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        /* Description Box */
        .description-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
            line-height: 1.6;
        }
        
        /* Modal visibility */
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-open {
            display: flex;
        }
        
        /* Button Styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
            border-color: #059669;
        }
        
        .btn-error {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .btn-error:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        
        .btn-outline {
            background: transparent;
            color: #475569;
            border-color: #e2e8f0;
        }
        
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .btn-ghost {
            background: transparent;
            color: #475569;
            border: none;
        }
        
        .btn-ghost:hover {
            background: #f1f5f9;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: #f1f5f9;
        }
        
        /* Custom scrollbar */
        .modal-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
        
        .empty-state-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            background: #f3f4f6;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Responsive Grid */
        .grid-responsive {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .grid-responsive {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .grid-responsive {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Priority Indicator */
        .priority-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .priority-low {
            background-color: #10b981;
        }
        
        .priority-medium {
            background-color: #f59e0b;
        }
        
        .priority-high {
            background-color: #ef4444;
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
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Approval Dashboard</h1>
                        <p class="text-gray-600 mt-1">Review and approve training programs, competency frameworks, and IDPs</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid-responsive mb-8">
                        <div class="stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Pending</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-1" id="pending-count">0</h3>
                                </div>
                                <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">High Priority</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-1" id="high-priority-count">0</h3>
                                </div>
                                <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Medium Priority</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-1" id="medium-priority-count">0</h3>
                                </div>
                                <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flag text-amber-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Low Priority</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-1" id="low-priority-count">0</h3>
                                </div>
                                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('training')">
                            <i class="fas fa-graduation-cap"></i>
                            Training Programs
                            <span class="bg-blue-100 text-blue-600 text-xs px-2 py-0.5 rounded-full" id="training-badge">0</span>
                        </button>
                        <button class="tab-btn" onclick="switchTab('competency')">
                            <i class="fas fa-award"></i>
                            Competency Frameworks
                            <span class="bg-purple-100 text-purple-600 text-xs px-2 py-0.5 rounded-full" id="competency-badge">0</span>
                        </button>
                        <button class="tab-btn" onclick="switchTab('idp')">
                            <i class="fas fa-user-graduate"></i>
                            IDP Approvals
                            <span class="bg-green-100 text-green-600 text-xs px-2 py-0.5 rounded-full" id="idp-badge">0</span>
                        </button>
                    </div>

                    <!-- Training Programs Table -->
                    <div id="training-table" class="table-container">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="table-header">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Training Program</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Requested By</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Approval Level</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted At</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="training-tbody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Competency Table -->
                    <div id="competency-table" class="table-container hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="table-header">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Competency</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Requested By</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Approval Level</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted At</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="competency-tbody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- IDP Table -->
                    <div id="idp-table" class="table-container hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="table-header">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Employee</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Department</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Position</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Approval Level</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted At</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="idp-tbody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Training Program Modal -->
    <div id="training-view-modal" class="modal">
        <div class="modal-box">
            <!-- Header -->
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Training Program Approval</h3>
                        <p class="text-white/80 text-sm mt-0.5">Request ID: <span id="modal-request-id">TR-00000</span></p>
                    </div>
                    <button class="btn-ghost btn-sm text-white hover:bg-white/20 rounded-full p-1" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="modal-content" id="training-modal-content">
                <!-- Content will be dynamically populated -->
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <div class="flex justify-end gap-2">
                    <button class="btn btn-sm btn-success" id="training-approve-btn">
                        <i class="fas fa-check mr-1"></i>
                        Approve
                    </button>
                    <button class="btn btn-sm btn-outline btn-error" id="training-decline-btn">
                        <i class="fas fa-times mr-1"></i>
                        Reject
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Competency Modal -->
    <div id="competency-view-modal" class="modal">
        <div class="modal-box">
            <!-- Header -->
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Competency Framework Approval</h3>
                        <p class="text-white/80 text-sm mt-0.5">Request ID: <span id="modal-competency-request-id">CF-00000</span></p>
                    </div>
                    <button class="btn-ghost btn-sm text-white hover:bg-white/20 rounded-full p-1" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="modal-content" id="competency-modal-content">
                <!-- Content will be dynamically populated -->
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <div class="flex justify-end gap-2">
                    <button class="btn btn-sm btn-success" id="competency-approve-btn">
                        <i class="fas fa-check mr-1"></i>
                        Approve
                    </button>
                    <button class="btn btn-sm btn-outline btn-error" id="competency-decline-btn">
                        <i class="fas fa-times mr-1"></i>
                        Reject
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- IDP Modal -->
    <div id="idp-view-modal" class="modal">
        <div class="modal-box">
            <!-- Header -->
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Individual Development Plan (IDP) Approval</h3>
                        <p class="text-white/80 text-sm mt-0.5">Request ID: <span id="modal-idp-request-id">IDP-00000</span></p>
                    </div>
                    <button class="btn-ghost btn-sm text-white hover:bg-white/20 rounded-full p-1" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="modal-content" id="idp-modal-content">
                <!-- Content will be dynamically populated -->
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <div class="flex justify-end gap-2">
                    <button class="btn btn-sm btn-success" id="idp-approve-btn">
                        <i class="fas fa-check mr-1"></i>
                        Approve
                    </button>
                    <button class="btn btn-sm btn-outline btn-error" id="idp-decline-btn">
                        <i class="fas fa-times mr-1"></i>
                        Reject
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="common-approval.js"></script>
    <script src="training-approval.js"></script>
    <script src="competency-approval.js"></script>
    <script src="idp-approval.js"></script>
    <script>
        // ====================
        // GLOBAL STATE
        // ====================
        let state = {
            activeTab: 'training',
            modalRequestId: null,
            modalReferenceId: null,
            modalType: null,
            currentUserId: 1,
            currentUserRole: 'HR',
            trainingData: [],
            competencyData: [],
            idpData: []
        };

        // ====================
        // INITIALIZATION
        // ====================
        function init() {
            // Load data from separate modules
            if (typeof loadTrainingData === 'function') {
                state.trainingData = loadTrainingData();
            }
            
            if (typeof loadCompetencyData === 'function') {
                state.competencyData = loadCompetencyData();
            }
            
            if (typeof loadIdpData === 'function') {
                state.idpData = loadIdpData();
            }
            
            // Setup event listeners
            setupEventListeners();
            
            // Render initial data
            if (typeof renderTrainingTable === 'function') {
                renderTrainingTable();
            }
            
            if (typeof renderCompetencyTable === 'function') {
                renderCompetencyTable();
            }
            
            if (typeof renderIdpTable === 'function') {
                renderIdpTable();
            }
            
            updateStats();
            
            // Set active tab
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
            
            // Show training table by default
            document.getElementById('training-table').classList.remove('hidden');
            document.getElementById('competency-table').classList.add('hidden');
            document.getElementById('idp-table').classList.add('hidden');
        }

        function setupEventListeners() {
            // Training approve button
            document.getElementById('training-approve-btn').addEventListener('click', function() {
                if (state.modalRequestId) {
                    approveRequest(state.modalRequestId, 'training');
                }
            });

            // Training decline button
            document.getElementById('training-decline-btn').addEventListener('click', function() {
                if (state.modalRequestId) {
                    declineRequest(state.modalRequestId, 'training');
                }
            });

            // Competency approve button
            document.getElementById('competency-approve-btn').addEventListener('click', function() {
                if (state.modalRequestId) {
                    approveRequest(state.modalRequestId, 'competency');
                }
            });

            // Competency decline button
            document.getElementById('competency-decline-btn').addEventListener('click', function() {
                if (state.modalRequestId) {
                    declineRequest(state.modalRequestId, 'competency');
                }
            });

            // IDP approve button
            document.getElementById('idp-approve-btn').addEventListener('click', function() {
                if (state.modalRequestId && typeof approveIdpRequest === 'function') {
                    approveIdpRequest(state.modalRequestId);
                }
            });

            // IDP decline button
            document.getElementById('idp-decline-btn').addEventListener('click', function() {
                if (state.modalRequestId && typeof declineIdpRequest === 'function') {
                    declineIdpRequest(state.modalRequestId);
                }
            });

            // Close modal when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            });
        }

        // ====================
        // TAB FUNCTION
        // ====================
        function switchTab(tab) {
            state.activeTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if ((tab === 'training' && btn.textContent.includes('Training')) ||
                    (tab === 'competency' && btn.textContent.includes('Competency')) ||
                    (tab === 'idp' && btn.textContent.includes('IDP'))) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide tables
            const trainingTable = document.getElementById('training-table');
            const competencyTable = document.getElementById('competency-table');
            const idpTable = document.getElementById('idp-table');
            
            trainingTable.classList.add('hidden');
            competencyTable.classList.add('hidden');
            idpTable.classList.add('hidden');
            
            if (tab === 'training') {
                trainingTable.classList.remove('hidden');
            } else if (tab === 'competency') {
                competencyTable.classList.remove('hidden');
            } else if (tab === 'idp') {
                idpTable.classList.remove('hidden');
            }
        }

        // ====================
        // ACTION FUNCTIONS
        // ====================
        function approveRequest(id, type) {
            if (type === 'training') {
                const item = state.trainingData.find(t => t.id === id);
                if (item && item.current_status === 'PENDING') {
                    item.current_status = 'APPROVED';
                    showAlert('Training program approved successfully!', 'success');
                    if (typeof renderTrainingTable === 'function') {
                        renderTrainingTable();
                    }
                    updateStats();
                    closeModal();
                }
            } else if (type === 'competency') {
                const item = state.competencyData.find(c => c.id === id);
                if (item && item.current_status === 'PENDING') {
                    item.current_status = 'APPROVED';
                    showAlert('Competency framework approved successfully!', 'success');
                    if (typeof renderCompetencyTable === 'function') {
                        renderCompetencyTable();
                    }
                    updateStats();
                    closeModal();
                }
            }
        }

        function declineRequest(id, type) {
            Swal.fire({
                title: 'Reject Request',
                html: '<textarea id="rejection-reason" class="w-full p-2 border rounded mt-2" placeholder="Please provide a reason for rejection..." rows="3"></textarea>',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const reason = document.getElementById('rejection-reason').value;
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for rejection');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'training') {
                        const item = state.trainingData.find(t => t.id === id);
                        if (item && item.current_status === 'PENDING') {
                            item.current_status = 'REJECTED';
                            item.rejection_reason = result.value;
                            showAlert('Training program rejected.', 'error');
                            if (typeof renderTrainingTable === 'function') {
                                renderTrainingTable();
                            }
                            updateStats();
                            closeModal();
                        }
                    } else if (type === 'competency') {
                        const item = state.competencyData.find(c => c.id === id);
                        if (item && item.current_status === 'PENDING') {
                            item.current_status = 'REJECTED';
                            item.rejection_reason = result.value;
                            showAlert('Competency framework rejected.', 'error');
                            if (typeof renderCompetencyTable === 'function') {
                                renderCompetencyTable();
                            }
                            updateStats();
                            closeModal();
                        }
                    }
                }
            });
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('modal-open');
            });
            state.modalRequestId = null;
            state.modalReferenceId = null;
            state.modalType = null;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>