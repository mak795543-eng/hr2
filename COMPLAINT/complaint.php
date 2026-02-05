<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Stays - Employee Complaint Management</title>
   <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            color: #2D2D2D;
            background-color: #FFFFFF;
        }
        
        .text-primary {
            color: #2B6CB0;
        }
        
        .bg-primary {
            background-color: #2B6CB0;
        }
        
        .border-primary {
            border-color: #2B6CB0;
        }
        
        .text-secondary {
            color: #ACACAC;
        }
        
        .bg-secondary {
            background-color: #ACACAC;
        }
        
        .bg-soft {
            background-color: #DBEAF6;
        }
        
        .complaint-card {
            transition: all 0.3s ease;
            border-left: 4px solid #2B6CB0;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: #2B6CB0;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2B6CB0;
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            background-color: #2B6CB0;
            opacity: 0.8;
            transform: translateY(0);
        }
        
        /* Custom checkbox styling - black when checked */
        .custom-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            position: relative;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .custom-checkbox:checked {
            background-color: #000000;
            border-color: #000000;
        }
        
        .custom-checkbox:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 14px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .custom-checkbox:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .file-input::file-selector-button {
            background-color: #2B6CB0;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-right: 1rem;
            cursor: pointer;
        }

        .section-divider {
            border-top: 2px solid #DBEAF6;
            margin: 2rem 0;
        }

        .terms-link {
            color: #2B6CB0;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 500;
        }

        .terms-link:hover {
            color: #2B6CB0;
            opacity: 0.8;
        }

        .terms-modal-content {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 1rem;
            background-color: white;
        }

        .terms-modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .terms-modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .terms-modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .terms-modal-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .modal-box {
            background-color: white;
        }

        .status-badge.submitted {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.under-review {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-badge.under-investigation {
            background-color: #000000;
            color: #ffffff;
        }
        
        .status-badge.resolved {
            background-color: #dcfce7;
            color: #ffffffff;
        }
        
        .status-badge.closed {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        
        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .custom-select:focus {
            outline: none;
            border-color: #2B6CB0;
            box-shadow: 0 0 0 2px rgba(43, 108, 176, 0.2);
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .checkbox-label {
            cursor: pointer;
            font-weight: 500;
        }
        
        .tracking-timeline {
            position: relative;
            padding-left: 20px;
        }
        
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e5e7eb;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #e5e7eb;
        }
        
        .timeline-item.active::before {
            background-color: #2B6CB0;
        }
        
        .timeline-item.completed::before {
            background-color: #16a34a;
        }
        
        /* Custom styling for other category textbox */
        .other-category-container {
            margin-top: 8px;
            display: none;
        }
        
        .other-category-input {
            transition: all 0.3s ease;
        }
        
        .other-category-input:focus {
            border-color: #2B6CB0;
            box-shadow: 0 0 0 2px rgba(43, 108, 176, 0.2);
        }
        
        /* Modal specific styles */
        .modal-scrollable {
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-scrollable::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-scrollable::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .modal-scrollable::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .modal-scrollable::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* View My Complaints button */
        .view-complaints-btn {
            margin-top: 2rem;
            width: 100%;
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
        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column - Complaint Submission -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-primary">
                            Submit a New Complaint
                        </h2>
                        <button type="button" class="btn btn-primary text-white" onclick="openMyComplaintsModal()">
                            <i class='bx bx-list-check mr-2'></i>
                            View My Complaints
                        </button>
                    </div>
                    <form id="complaintForm">
                        <!-- Complaint Category -->
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Complaint Category</span>
                            </label>
                            <select class="custom-select select select-bordered w-full border-gray-300 focus:border-primary bg-white" id="complaintCategory" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="Workplace Issue">Workplace Issue</option>
                                <option value="Pay Concern">Pay Concern</option>
                                <option value="Safety Incident">Safety Incident</option>
                                <option value="Harassment">Harassment</option>
                                <option value="Scheduling Issue">Scheduling Issue</option>
                                <option value="Discrimination">Discrimination</option>
                                <option value="Work Environment">Work Environment</option>
                                <option value="Equipment/Facility">Equipment/Facility</option>
                                <option value="Food Quality">Food Quality (Restaurant)</option>
                                <option value="Guest Interaction">Guest Interaction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Other Category Textbox (Hidden by default) -->
                        <div class="form-control w-full mb-4 other-category-container" id="otherCategoryContainer">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Please specify your complaint</span>
                            </label>
                            <input type="text" 
                                   class="input input-bordered w-full border-gray-300 focus:border-primary bg-white other-category-input" 
                                   id="otherCategoryInput" 
                                   placeholder="Enter your complaint category here...">
                        </div>

                        <!-- Location/Branch/Department -->
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Location/Branch/Department</span>
                            </label>
                            <select class="custom-select select select-bordered w-full border-gray-300 focus:border-primary bg-white" id="complaintLocation" required>
                                <option value="" disabled selected>Select department</option>
                                <optgroup label="Departments">
                                    <option value="HR DEPARTMENT">HR DEPARTMENT</option>
                                    <option value="LOGISTICS DEPARTMENT">LOGISTICS DEPARTMENT</option>
                                    <option value="FINANCE">FINANCE</option>
                                    <option value="HOTEL">HOTEL</option>
                                    <option value="RESTAURANT">RESTAURANT</option>
                                </optgroup>
                                <optgroup label="Hotel Departments">
                                    <option value="Front Desk">Front Desk</option>
                                    <option value="Housekeeping">Housekeeping</option>
                                    <option value="Kitchen">Kitchen</option>
                                    <option value="Banquet Services">Banquet Services</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Spa & Wellness">Spa & Wellness</option>
                                </optgroup>
                                <optgroup label="Restaurant Departments">
                                    <option value="Restaurant - Front of House">Restaurant - Front of House</option>
                                    <option value="Restaurant - Kitchen">Restaurant - Kitchen</option>
                                    <option value="Restaurant - Bar">Restaurant - Bar</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Date of Incident -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Date of Incident</span>
                            </label>
                            <input type="date" class="input input-bordered w-full border-gray-300 focus:border-primary bg-white" id="incidentDate" required>
                        </div>

                        <div class="section-divider"></div>

                        <!-- Detailed Description -->
                        <div class="form-control mb-6">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700 text-lg">Detailed Description</span>
                            </label>
                            <textarea class="textarea textarea-bordered h-32 border-gray-300 focus:border-primary bg-white" placeholder="Please provide a detailed description of the issue, including relevant details, people involved, and impact..." id="complaintDescription" required></textarea>
                        </div>

                        <!-- File Attachment -->
                        <div class="form-control mb-6">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Attachment (Optional)</span>
                            </label>
                            <input type="file" class="file-input file-input-bordered w-full border-gray-300 bg-white" id="complaintAttachment" />
                            <label class="label">
                                <span class="label-text-alt text-secondary">You can attach images or documents (max 5MB)</span>
                            </label>
                        </div>

                        <!-- Submission Options -->
                        <div class="form-control mb-6">
                            <div class="flex flex-col space-y-4">
                                <div class="checkbox-container">
                                    <input type="checkbox" id="confidentialCheckbox" class="custom-checkbox">
                                    <label for="confidentialCheckbox" class="checkbox-label">Submit as confidential complaint (Only HR and management will view details)</label>
                                </div>
                                <div class="checkbox-container">
                                    <input type="checkbox" id="anonymousCheckbox" class="custom-checkbox">
                                    <label for="anonymousCheckbox" class="checkbox-label">Submit anonymously (Your identity will not be recorded)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-control mb-6">
                            <div class="checkbox-container">
                                <input type="checkbox" id="termsCheckbox" class="custom-checkbox" required>
                                <label for="termsCheckbox" class="checkbox-label">
                                    I have read and agree to the 
                                    <span class="terms-link" onclick="openTermsModal()">Terms & Conditions</span> 
                                    for complaint submission
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary text-white w-full" id="submitButton" disabled>
                                SUBMIT COMPLAINT
                            </button>
                        </div>
                    </form>
                    
                    <!-- View My Complaints Button (Alternative Position) -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <button type="button" class="btn btn-outline border-primary text-primary hover:bg-primary hover:text-white w-full" onclick="openMyComplaintsModal()">
                            <i class='bx bx-history mr-2'></i>
                            View My Complaint History
                        </button>
                    </div>
                </div>
            </div>
        </div>
      </main>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-box bg-white">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                    <i class='bx bx-check text-3xl text-green-600'></i>
                </div>
                <h3 class="font-bold text-lg text-primary">Complaint Submitted Successfully!</h3>
                <p class="py-4 text-secondary">Your complaint has been submitted and assigned tracking number: <span id="complaintNumber" class="font-semibold text-primary"></span></p>
                <p class="text-sm text-secondary mb-4">You will receive updates on your complaint via the ESS portal and email.</p>
                <div class="modal-action">
                    <button class="btn btn-primary text-white" onclick="closeSuccessModal()">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-box max-w-4xl bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-primary">Terms & Conditions</h3>
                <button class="btn btn-sm btn-circle" onclick="closeTermsModal()">✕</button>
            </div>
            <div class="terms-modal-content">
                <h4 class="font-semibold text-primary mb-4 text-lg">Complaint Submission Terms & Conditions</h4>
                <ol class="list-decimal list-inside space-y-4 text-sm text-gray-700">
                    <li>
                        <strong class="text-primary">Truthful Information</strong>: By submitting this complaint, you confirm that all information provided is true and accurate to the best of your knowledge.
                    </li>
                    <li>
                        <strong class="text-primary">Confidentiality</strong>: Luxury Stays will handle your complaint with appropriate confidentiality. However, complete anonymity cannot be guaranteed if investigation requires disclosure.
                    </li>
                    <li>
                        <strong class="text-primary">Good Faith Submission</strong>: Complaints must be submitted in good faith and not for malicious, frivolous, or vexatious purposes.
                    </li>
                    <li>
                        <strong class="text-primary">Investigation Process</strong>: You acknowledge that the company will conduct a fair investigation, which may involve speaking with other parties mentioned in your complaint.
                    </li>
                    <li>
                        <strong class="text-primary">Non-Retaliation</strong>: Luxury Stays prohibits retaliation against any employee who submits a complaint in good faith. Report any retaliation immediately to HR.
                    </li>
                    <li>
                        <strong class="text-primary">Timely Response</strong>: The company aims to acknowledge complaints within 48 hours and provide resolution updates within 10 business days.
                    </li>
                    <li>
                        <strong class="text-primary">Documentation</strong>: You agree to maintain confidentiality regarding the investigation process and not discuss ongoing investigations with unauthorized parties.
                    </li>
                    <li>
                        <strong class="text-primary">False Complaints</strong>: Submission of knowingly false or misleading information may result in disciplinary action, up to and including termination.
                    </li>
                    <li>
                        <strong class="text-primary">Data Protection</strong>: Your personal information will be processed in accordance with company privacy policies and applicable data protection laws.
                    </li>
                    <li>
                        <strong class="text-primary">Acceptance</strong>: By checking the box below, you acknowledge that you have read, understood, and agree to these terms and conditions.
                    </li>
                </ol>
                <div class="border-t border-gray-200 mt-6 pt-4">
                    <div class="checkbox-container">
                        <input type="checkbox" id="modalTermsCheckbox" class="custom-checkbox">
                        <label for="modalTermsCheckbox" class="checkbox-label">
                            I have read and agree to the Terms & Conditions for complaint submission
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-action mt-6">
                <button class="btn btn-primary text-white w-full" onclick="acceptTerms()">
                    I UNDERSTAND
                </button>
            </div>
        </div>
    </div>

    <!-- My Complaints Modal -->
    <div id="myComplaintsModal" class="modal">
        <div class="modal-box max-w-5xl bg-white modal-scrollable">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-primary">My Complaints</h3>
                <button class="btn btn-sm btn-circle" onclick="closeMyComplaintsModal()">✕</button>
            </div>
            <p class="text-secondary mb-6">Track the status of your submitted complaints</p>
            
            <!-- Search and Filter -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-control">
                    <div class="relative">
                        <input type="text" placeholder="Search complaints..." class="input input-bordered w-full border-gray-300 focus:border-primary bg-white pl-10" id="complaintSearch">
                        <i class='bx bx-search absolute left-3 top-3 text-secondary'></i>
                    </div>
                </div>
                <div class="form-control">
                    <select class="custom-select select select-bordered w-full border-gray-300 focus:border-primary bg-white" id="statusFilter">
                        <option value="all">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="under-review">Under Review</option>
                        <option value="under-investigation">Under Investigation</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>
            
            <!-- Complaint List -->
            <div class="space-y-4" id="complaintList">
                <!-- Content will be dynamically populated -->
            </div>
            
            <!-- No Complaints Message -->
            <div id="noComplaintsMessage" class="text-center py-8 hidden">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4 mx-auto">
                    <i class='bx bx-file text-2xl text-gray-400'></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">No complaints found</h4>
                <p class="text-secondary text-sm">You haven't submitted any complaints yet.</p>
            </div>
            
            <!-- Stats Summary -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary" id="totalComplaints">0</div>
                        <div class="text-xs text-secondary">Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600" id="submittedCount">0</div>
                        <div class="text-xs text-secondary">Submitted</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600" id="reviewCount">0</div>
                        <div class="text-xs text-secondary">In Review</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-black" id="investigationCount">0</div>
                        <div class="text-xs text-secondary">Investigation</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600" id="resolvedCount">0</div>
                        <div class="text-xs text-secondary">Resolved</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complaint Details Modal -->
    <div id="complaintDetailsModal" class="modal">
        <div class="modal-box max-w-4xl bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-primary">Complaint Details</h3>
                <button class="btn btn-sm btn-circle" onclick="closeComplaintDetails()">✕</button>
            </div>
            <div id="complaintDetailsContent">
                <!-- Content will be dynamically populated -->
            </div>
        </div>
    </div>

    <script>
        // Sample complaint data
        let complaints = [
            {
                id: "LS-COMP-2023-001",
                category: "Workplace Issue",
                location: "Front Desk",
                date: "2023-10-15",
                description: "Issue with workstation setup causing back pain after long shifts",
                status: "submitted",
                submittedDate: "2023-10-16",
                confidential: false,
                anonymous: false,
                priority: "medium",
                statusHistory: [
                    { status: "submitted", date: "2023-10-16", note: "Complaint submitted successfully" }
                ]
            },
            {
                id: "LS-COMP-2023-002",
                category: "Pay Concern",
                location: "Restaurant",
                date: "2023-10-10",
                description: "Missing overtime pay for last weekend's banquet event",
                status: "under-review",
                submittedDate: "2023-10-11",
                confidential: true,
                anonymous: false,
                priority: "high",
                statusHistory: [
                    { status: "submitted", date: "2023-10-11", note: "Complaint submitted successfully" },
                    { status: "under-review", date: "2023-10-12", note: "Complaint assigned to HR representative" }
                ]
            },
            {
                id: "LS-COMP-2023-003",
                category: "Safety Incident",
                location: "Kitchen",
                date: "2023-10-05",
                description: "Slippery floor near dishwasher area, nearly caused a fall",
                status: "resolved",
                submittedDate: "2023-10-06",
                confidential: false,
                anonymous: false,
                priority: "high",
                statusHistory: [
                    { status: "submitted", date: "2023-10-06", note: "Complaint submitted successfully" },
                    { status: "under-review", date: "2023-10-07", note: "Complaint assigned to Safety Officer" },
                    { status: "under-investigation", date: "2023-10-09", note: "Investigation initiated" },
                    { status: "resolved", date: "2023-10-12", note: "Non-slip mats installed, issue resolved" }
                ]
            },
            {
                id: "LS-COMP-2023-004",
                category: "Scheduling Issue",
                location: "Housekeeping",
                date: "2023-10-02",
                description: "Last-minute schedule changes without proper notification",
                status: "under-investigation",
                submittedDate: "2023-10-03",
                confidential: false,
                anonymous: true,
                priority: "medium",
                statusHistory: [
                    { status: "submitted", date: "2023-10-03", note: "Complaint submitted successfully" },
                    { status: "under-review", date: "2023-10-04", note: "Complaint assigned to Department Manager" },
                    { status: "under-investigation", date: "2023-10-05", note: "Investigation of scheduling practices initiated" }
                ]
            }
        ];

        // Function to handle complaint category change
        function handleCategoryChange() {
            const categorySelect = document.getElementById('complaintCategory');
            const otherCategoryContainer = document.getElementById('otherCategoryContainer');
            const otherCategoryInput = document.getElementById('otherCategoryInput');
            
            if (categorySelect.value === 'Other') {
                otherCategoryContainer.style.display = 'block';
                otherCategoryInput.required = true;
                // Add a small animation for smooth transition
                setTimeout(() => {
                    otherCategoryContainer.style.opacity = '1';
                }, 10);
            } else {
                otherCategoryContainer.style.display = 'none';
                otherCategoryInput.required = false;
                otherCategoryInput.value = ''; // Clear the input when hiding
            }
        }

        // Open My Complaints Modal
        function openMyComplaintsModal() {
            // Initialize the complaint list first
            initializeComplaintList();
            // Update the stats
            updateComplaintStats();
            // Show the modal
            document.getElementById('myComplaintsModal').classList.add('modal-open');
        }

        // Close My Complaints Modal
        function closeMyComplaintsModal() {
            document.getElementById('myComplaintsModal').classList.remove('modal-open');
        }

        // Open Terms Modal
        function openTermsModal() {
            document.getElementById('termsModal').classList.add('modal-open');
        }

        // Close Terms Modal
        function closeTermsModal() {
            document.getElementById('termsModal').classList.remove('modal-open');
        }

        // Accept Terms from modal
        function acceptTerms() {
            const modalCheckbox = document.getElementById('modalTermsCheckbox');
            const mainCheckbox = document.getElementById('termsCheckbox');
            
            if (modalCheckbox.checked) {
                mainCheckbox.checked = true;
                document.getElementById('submitButton').disabled = false;
                closeTermsModal();
            } else {
                alert('Please check the box to accept the Terms & Conditions.');
            }
        }

        // Initialize the complaint list
        function initializeComplaintList() {
            const complaintList = document.getElementById('complaintList');
            const noComplaintsMessage = document.getElementById('noComplaintsMessage');
            
            // Clear current list
            complaintList.innerHTML = '';
            
            if (complaints.length === 0) {
                noComplaintsMessage.classList.remove('hidden');
                return;
            }
            
            noComplaintsMessage.classList.add('hidden');
            
            complaints.forEach(complaint => {
                const statusClass = getStatusClass(complaint.status);
                
                const complaintItem = document.createElement('div');
                complaintItem.className = 'complaint-card bg-white p-4 rounded border border-gray-200 cursor-pointer hover:bg-gray-50';
                complaintItem.setAttribute('onclick', `openComplaintDetails('${complaint.id}')`);
                complaintItem.innerHTML = `
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-primary text-sm mb-1">${complaint.id}</h3>
                            <div class="flex items-center mb-2">
                                <span class="text-sm font-medium mr-2">${complaint.category}</span>
                                <span class="status-badge ${statusClass}">${complaint.status}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2 line-clamp-2">${complaint.description}</p>
                            <div class="text-xs text-secondary">
                                ${complaint.location} • ${formatDate(complaint.date)}
                            </div>
                        </div>
                        <div class="mt-2 md:mt-0 md:ml-4 text-right">
                            <div class="text-xs text-gray-500 mb-1">Submitted: ${formatDate(complaint.submittedDate)}</div>
                            <div class="text-xs">
                                <span class="px-2 py-1 rounded ${complaint.confidential ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'}">
                                    ${complaint.confidential ? 'Confidential' : 'Standard'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                complaintList.appendChild(complaintItem);
            });
        }

        // Update complaint statistics
        function updateComplaintStats() {
            const total = complaints.length;
            const submittedCount = complaints.filter(c => c.status === 'submitted').length;
            const reviewCount = complaints.filter(c => c.status === 'under-review').length;
            const investigationCount = complaints.filter(c => c.status === 'under-investigation').length;
            const resolvedCount = complaints.filter(c => c.status === 'resolved').length;
            
            document.getElementById('totalComplaints').textContent = total;
            document.getElementById('submittedCount').textContent = submittedCount;
            document.getElementById('reviewCount').textContent = reviewCount;
            document.getElementById('investigationCount').textContent = investigationCount;
            document.getElementById('resolvedCount').textContent = resolvedCount;
        }

        // Get status class for badges
        function getStatusClass(status) {
            switch(status) {
                case 'submitted': return 'submitted';
                case 'under-review': return 'under-review';
                case 'under-investigation': return 'under-investigation';
                case 'resolved': return 'resolved';
                case 'closed': return 'closed';
                default: return 'closed';
            }
        }

        // Format date for display
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        // Generate a unique complaint ID
        function generateComplaintId() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            return `LS-COMP-${year}-${month}${day}-${random}`;
        }

        // Handle form submission
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const categorySelect = document.getElementById('complaintCategory');
            const otherCategoryInput = document.getElementById('otherCategoryInput');
            
            // Get category value - if "Other" is selected, use the textbox value
            let category = categorySelect.value;
            if (category === 'Other') {
                category = otherCategoryInput.value.trim();
                if (!category) {
                    alert('Please specify your complaint category in the "Other" field.');
                    otherCategoryInput.focus();
                    return;
                }
            }
            
            const location = document.getElementById('complaintLocation').value;
            const date = document.getElementById('incidentDate').value;
            const description = document.getElementById('complaintDescription').value;
            const confidential = document.getElementById('confidentialCheckbox').checked;
            const anonymous = document.getElementById('anonymousCheckbox').checked;
            const termsAccepted = document.getElementById('termsCheckbox').checked;
            
            if (!termsAccepted) {
                alert('Please accept the Terms & Conditions to submit your complaint.');
                return;
            }
            
            // Create new complaint object
            const newComplaint = {
                id: generateComplaintId(),
                category: category,
                location: location,
                date: date,
                description: description,
                status: 'submitted',
                submittedDate: new Date().toISOString().split('T')[0],
                confidential: confidential,
                anonymous: anonymous,
                priority: 'medium',
                statusHistory: [
                    { status: "submitted", date: new Date().toISOString().split('T')[0], note: "Complaint submitted successfully" }
                ]
            };
            
            // Add to complaints array
            complaints.unshift(newComplaint);
            
            // Reset form
            document.getElementById('complaintForm').reset();
            
            // Hide the other category textbox if it's visible
            document.getElementById('otherCategoryContainer').style.display = 'none';
            document.getElementById('otherCategoryInput').required = false;
            
            // Show success modal with complaint number
            document.getElementById('complaintNumber').textContent = newComplaint.id;
            document.getElementById('successModal').classList.add('modal-open');
            
            // Update stats (in case user opens the complaints modal after submission)
            updateComplaintStats();
        });

        // Close success modal
        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('modal-open');
        }

        // Open complaint details modal
        function openComplaintDetails(complaintId) {
            const complaint = complaints.find(c => c.id === complaintId);
            if (!complaint) return;
            
            const statusClass = getStatusClass(complaint.status);
            const statusHistory = complaint.statusHistory || [];
            
            let statusTimeline = '';
            statusHistory.forEach((entry, index) => {
                const isLast = index === statusHistory.length - 1;
                const isActive = complaint.status === entry.status;
                const isCompleted = index < statusHistory.length - 1;
                
                let timelineClass = 'timeline-item';
                if (isActive) timelineClass += ' active';
                if (isCompleted) timelineClass += ' completed';
                
                statusTimeline += `
                    <div class="${timelineClass}">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium capitalize">${entry.status.replace('-', ' ')}</h4>
                                <p class="text-sm text-gray-600">${entry.note}</p>
                            </div>
                            <span class="text-xs text-gray-500">${formatDate(entry.date)}</span>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('complaintDetailsContent').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Complaint Information</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Complaint ID:</span>
                                <span class="font-medium">${complaint.id}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span class="font-medium">${complaint.category}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Location:</span>
                                <span class="font-medium">${complaint.location}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date of Incident:</span>
                                <span class="font-medium">${formatDate(complaint.date)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Status:</span>
                                <span class="status-badge ${statusClass}">${complaint.status}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Submission Details</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Submitted:</span>
                                <span class="font-medium">${formatDate(complaint.submittedDate)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Confidential:</span>
                                <span class="font-medium">${complaint.confidential ? 'Yes' : 'No'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Anonymous:</span>
                                <span class="font-medium">${complaint.anonymous ? 'Yes' : 'No'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Priority:</span>
                                <span class="font-medium capitalize">${complaint.priority}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Description</h4>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded">${complaint.description}</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-4">Status Tracking</h4>
                    <div class="tracking-timeline">
                        ${statusTimeline}
                    </div>
                </div>
            `;
            
            document.getElementById('complaintDetailsModal').classList.add('modal-open');
        }

        // Close complaint details modal
        function closeComplaintDetails() {
            document.getElementById('complaintDetailsModal').classList.remove('modal-open');
        }

        // Enable/disable submit button based on terms acceptance
        document.getElementById('termsCheckbox').addEventListener('change', function() {
            document.getElementById('submitButton').disabled = !this.checked;
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date as default for incident date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('incidentDate').value = today;
            
            // Add event listener for category dropdown change
            document.getElementById('complaintCategory').addEventListener('change', handleCategoryChange);
            
            // Initialize the other category container
            const otherCategoryContainer = document.getElementById('otherCategoryContainer');
            otherCategoryContainer.style.opacity = '0';
            otherCategoryContainer.style.transition = 'opacity 0.3s ease';
            
            // Initialize complaint stats
            updateComplaintStats();
            
            // Add event listeners for search and filter in my complaints modal
            document.getElementById('complaintSearch').addEventListener('input', filterComplaintsInModal);
            document.getElementById('statusFilter').addEventListener('change', filterComplaintsInModal);
        });

        // Filter complaints in the modal based on search and status
        function filterComplaintsInModal() {
            const searchTerm = document.getElementById('complaintSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const complaintList = document.getElementById('complaintList');
            const noComplaintsMessage = document.getElementById('noComplaintsMessage');
            
            const filteredComplaints = complaints.filter(complaint => {
                const matchesSearch = complaint.id.toLowerCase().includes(searchTerm) || 
                                     complaint.category.toLowerCase().includes(searchTerm) ||
                                     complaint.description.toLowerCase().includes(searchTerm) ||
                                     complaint.location.toLowerCase().includes(searchTerm);
                
                const matchesStatus = statusFilter === 'all' || complaint.status === statusFilter;
                
                return matchesSearch && matchesStatus;
            });
            
            // Clear current list
            complaintList.innerHTML = '';
            
            if (filteredComplaints.length === 0) {
                noComplaintsMessage.classList.remove('hidden');
                return;
            }
            
            noComplaintsMessage.classList.add('hidden');
            
            // Update the complaint list with filtered results
            filteredComplaints.forEach(complaint => {
                const statusClass = getStatusClass(complaint.status);
                
                const complaintItem = document.createElement('div');
                complaintItem.className = 'complaint-card bg-white p-4 rounded border border-gray-200 cursor-pointer hover:bg-gray-50';
                complaintItem.setAttribute('onclick', `openComplaintDetails('${complaint.id}')`);
                complaintItem.innerHTML = `
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-primary text-sm mb-1">${complaint.id}</h3>
                            <div class="flex items-center mb-2">
                                <span class="text-sm font-medium mr-2">${complaint.category}</span>
                                <span class="status-badge ${statusClass}">${complaint.status}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2 line-clamp-2">${complaint.description}</p>
                            <div class="text-xs text-secondary">
                                ${complaint.location} • ${formatDate(complaint.date)}
                            </div>
                        </div>
                        <div class="mt-2 md:mt-0 md:ml-4 text-right">
                            <div class="text-xs text-gray-500 mb-1">Submitted: ${formatDate(complaint.submittedDate)}</div>
                            <div class="text-xs">
                                <span class="px-2 py-1 rounded ${complaint.confidential ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'}">
                                    ${complaint.confidential ? 'Confidential' : 'Standard'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                complaintList.appendChild(complaintItem);
            });
        }
    </script>
     <script src="../soliera.js"></script>
  <script src="../.sidebar.js"></script>
</body>
</html>