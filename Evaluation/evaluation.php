<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Evaluation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<style>
    /* Clean Custom Styles */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .modal {
        background-color: rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(8px);
        animation: fadeIn 0.2s ease-out;
        z-index: 1000;
    }
    
    .modal-box {
        animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        max-height: 85vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    .modal-content {
        flex: 1;
        overflow-y: auto;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    @keyframes slide-in {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slide-out {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(20px);
        }
    }
    
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
    
    .animate-slide-out {
        animation: slide-out 0.3s ease-out;
    }
    
    /* Custom scrollbar for modal */
    .modal-box::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-box::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-box::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .modal-box::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    
    /* Enhanced rating stars */
    .rating input {
        width: 1.75rem;
        height: 1.75rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .rating input:hover {
        transform: scale(1.15);
    }
    
    /* Smooth transitions */
    button, select, input, textarea, .border-gray-200 {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Custom focus styles */
    .select:focus, .input:focus, .textarea:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        border-color: #3b82f6;
    }
    
    /* Subtle hover effects for rating cards */
    .border-gray-200:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-color: #d1d5db;
    }
    
    /* Rectangular form container */
    .form-container {
        width: 900px;
        max-width: 95%;
        min-height: 600px;
    }
    
    /* Fixed Modal Width */
    #legendModal .modal-box {
        width: 800px;
        max-width: 90vw;
        display: flex;
        flex-direction: column;
    }
    
    /* Department selection step */
    .step-indicator {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }
    
    .step-line {
        width: 40px;
        height: 2px;
        background-color: #e5e7eb;
    }
    
    .step-line.active {
        background-color: #3b82f6;
    }
    
    /* Modal footer styling */
    .modal-footer {
        flex-shrink: 0;
        border-top: 1px solid #e5e7eb;
        background: linear-gradient(to right, #f9fafb, #f3f4f6);
        padding: 1.5rem;
    }
</style>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../USM/sidebarr.php'; ?>

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../USM/navbar.php'; ?>
            
            <main class="flex-1 overflow-auto p-4 md:p-6">
            
                    <!-- Main Form Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
                        <form id="evaluationForm" class="p-6 md:p-8">
                            <!-- Department & Employee Selection -->
                            <div class="space-y-6 mb-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Department Selection (REQUIRED FIRST) -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Select Department <span class="text-red-500">*</span>
                                        </label>
                                        <select id="departmentSelect" name="department_id" class="select select-bordered w-full bg-white border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                            <option value="" disabled selected>Choose a department first</option>
                                            <option value="1">Front Office</option>
                                            <option value="2">Housekeeping</option>
                                            <option value="3">Food & Beverage</option>
                                            <option value="4">Kitchen</option>
                                            <option value="5">Sales & Marketing</option>
                                            <option value="6">Human Resources</option>
                                            <option value="7">Maintenance</option>
                                            <option value="8">Security</option>
                                            <option value="9">Spa & Wellness</option>
                                            <option value="10">Banquets & Events</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-2">Please select a department to view available employees</p>
                                    </div>

                                    <!-- Employee Selection (Disabled until department is selected) -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Select Employee <span class="text-red-500">*</span>
                                        </label>
                                        <select id="employeeSelect" name="employee_id" class="select select-bordered w-full bg-gray-100 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" disabled required>
                                            <option disabled selected>Select department first</option>
                                        </select>
                                        <div id="employeeCount" class="text-xs text-gray-500 mt-2 hidden">
                                            <span id="countNumber">0</span> employees available in this department
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Reviewer Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Reviewer Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="reviewer_name" placeholder="Enter reviewer's full name" class="input input-bordered w-full bg-white border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>

                                    <!-- Evaluation Date -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Evaluation Date <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="evaluation_date" class="input input-bordered w-full bg-white border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                </div>

                                <!-- Review Period Textbox -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Review Period <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="review_period" placeholder="e.g., Q3 2024, Jan-Mar 2024, Annual Review" class="input input-bordered w-full bg-white border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                            </div>

                            <!-- Performance Ratings Section -->
                            <div class="mb-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900">Performance Ratings</h2>
                                        <p class="text-sm text-gray-500 mt-1">Rate each area from 1 to 5</p>
                                    </div>
                                    <button type="button" id="legendButton" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50 transition-colors border border-blue-200">
                                        <i data-lucide="star" class="w-4 h-4"></i>
                                        Rating Guide
                                    </button>
                                </div>

                                <!-- Rating Categories -->
                                <div class="space-y-4">
                                    <!-- Team Productivity -->
                                    <div class="border border-gray-200 rounded-xl p-5 hover:border-gray-300 transition-colors bg-gradient-to-r from-white to-gray-50/50">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                                                        <i data-lucide="trending-up" class="w-4 h-4 text-blue-600"></i>
                                                    </div>
                                                    <h3 class="font-medium text-gray-800">Team Productivity</h3>
                                                </div>
                                                <p class="text-sm text-gray-500">Ensure team meets productivity targets and deadlines</p>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="rating rating-md">
                                                    <input type="radio" name="productivity_rating" class="mask mask-star-2 bg-amber-400" value="1" />
                                                    <input type="radio" name="productivity_rating" class="mask mask-star-2 bg-amber-400" value="2" />
                                                    <input type="radio" name="productivity_rating" class="mask mask-star-2 bg-amber-400" value="3" checked />
                                                    <input type="radio" name="productivity_rating" class="mask mask-star-2 bg-amber-400" value="4" />
                                                    <input type="radio" name="productivity_rating" class="mask mask-star-2 bg-amber-400" value="5" />
                                                </div>
                                                <span id="productivityDisplay" class="text-sm font-medium text-gray-700 min-w-12 text-center bg-gray-100 px-2 py-1 rounded-lg">3/5</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Staff Development -->
                                    <div class="border border-gray-200 rounded-xl p-5 hover:border-gray-300 transition-colors bg-gradient-to-r from-white to-gray-50/50">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                                                        <i data-lucide="users" class="w-4 h-4 text-green-600"></i>
                                                    </div>
                                                    <h3 class="font-medium text-gray-800">Staff Development</h3>
                                                </div>
                                                <p class="text-sm text-gray-500">Coach and develop direct reports with regular feedback</p>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="rating rating-md">
                                                    <input type="radio" name="development_rating" class="mask mask-star-2 bg-amber-400" value="1" />
                                                    <input type="radio" name="development_rating" class="mask mask-star-2 bg-amber-400" value="2" />
                                                    <input type="radio" name="development_rating" class="mask mask-star-2 bg-amber-400" value="3" />
                                                    <input type="radio" name="development_rating" class="mask mask-star-2 bg-amber-400" value="4" checked />
                                                    <input type="radio" name="development_rating" class="mask mask-star-2 bg-amber-400" value="5" />
                                                </div>
                                                <span id="developmentDisplay" class="text-sm font-medium text-gray-700 min-w-12 text-center bg-gray-100 px-2 py-1 rounded-lg">4/5</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Operational Compliance -->
                                    <div class="border border-gray-200 rounded-xl p-5 hover:border-gray-300 transition-colors bg-gradient-to-r from-white to-gray-50/50">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                                                        <i data-lucide="shield-check" class="w-4 h-4 text-purple-600"></i>
                                                    </div>
                                                    <h3 class="font-medium text-gray-800">Operational Compliance</h3>
                                                </div>
                                                <p class="text-sm text-gray-500">Maintain departmental compliance with policies and procedures</p>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="rating rating-md">
                                                    <input type="radio" name="compliance_rating" class="mask mask-star-2 bg-amber-400" value="1" />
                                                    <input type="radio" name="compliance_rating" class="mask mask-star-2 bg-amber-400" value="2" />
                                                    <input type="radio" name="compliance_rating" class="mask mask-star-2 bg-amber-400" value="3" checked />
                                                    <input type="radio" name="compliance_rating" class="mask mask-star-2 bg-amber-400" value="4" />
                                                    <input type="radio" name="compliance_rating" class="mask mask-star-2 bg-amber-400" value="5" />
                                                </div>
                                                <span id="complianceDisplay" class="text-sm font-medium text-gray-700 min-w-12 text-center bg-gray-100 px-2 py-1 rounded-lg">3/5</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Feedback -->
                            <div class="mb-10">
                                <div class="flex items-center gap-2 mb-3">
                                    <label class="text-sm font-medium text-gray-700">Additional Feedback</label>
                                    <div class="group relative">
                                        <i data-lucide="help-circle" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                        <div class="absolute bottom-full left-0 mb-2 px-3 py-1.5 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 shadow-lg">
                                            Optional but recommended
                                            <div class="absolute top-full left-3 border-4 border-transparent border-t-gray-800"></div>
                                        </div>
                                    </div>
                                </div>
                                <textarea name="additional_feedback" class="textarea textarea-bordered w-full h-36 bg-white border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Provide detailed feedback on performance, achievements, and areas for improvement..."></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3 pt-8 border-t border-gray-100">
                                <button type="button" id="cancelButton" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors shadow-sm">
                                    <i data-lucide="x" class="w-4 h-4 inline-block mr-2"></i>
                                    Cancel
                                </button>
                                <button type="button" id="saveDraftButton" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors shadow-sm border border-gray-200">
                                    <i data-lucide="save" class="w-4 h-4 inline-block mr-2"></i>
                                    Save Draft
                                </button>
                                <button type="submit" id="submitButton" class="px-8 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors shadow-lg ml-auto">
                                    <i data-lucide="check-circle" class="w-4 h-4 inline-block mr-2"></i>
                                    Submit Evaluation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Fixed Width Rating Guide Modal - Now Scrollable -->
    <div id="legendModal" class="modal">
        <div class="modal-box p-0 overflow-hidden bg-white shadow-2xl border border-gray-100 rounded-xl">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-blue-100/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center shadow-sm">
                            <i data-lucide="award" class="w-5 h-5 text-blue-700"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Performance Rating Guide</h3>
                            <p class="text-sm text-gray-600 mt-0.5">Understanding the 1-5 performance rating system</p>
                        </div>
                    </div>
                    <button type="button" id="closeModalHeader" class="btn btn-ghost btn-sm btn-circle hover:bg-white/50">
                        <i data-lucide="x" class="w-4 h-4 text-gray-600"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Content - Now Scrollable -->
            <div class="modal-content p-6 overflow-y-auto max-h-[60vh]">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Rating 1 -->
                        <div class="bg-gradient-to-br from-red-50 to-red-25 border border-red-200 rounded-xl p-5 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-white border border-red-300 flex items-center justify-center shadow-sm">
                                    <span class="text-xl font-bold text-red-700">1</span>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900">Needs Improvement</h4>
                                    <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded-full">Below Expectations</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Performance consistently falls short of required standards and expectations. Requires immediate improvement and additional supervision.</p>
                        </div>

                        <!-- Rating 2 -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-25 border border-amber-200 rounded-xl p-5 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-white border border-amber-300 flex items-center justify-center shadow-sm">
                                    <span class="text-xl font-bold text-amber-700">2</span>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900">Developing</h4>
                                    <span class="text-xs font-medium text-amber-600 bg-amber-100 px-2 py-1 rounded-full">Progressing</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Shows progress but requires additional training and development to meet expectations consistently. Needs guidance and regular feedback.</p>
                        </div>

                        <!-- Rating 3 -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-25 border border-blue-200 rounded-xl p-5 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-white border border-blue-300 flex items-center justify-center shadow-sm">
                                    <span class="text-xl font-bold text-blue-700">3</span>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900">Competent</h4>
                                    <span class="text-xs font-medium text-blue-600 bg-blue-100 px-2 py-1 rounded-full">Meets Expectations</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Consistently meets all job requirements and performance expectations. Reliable performer who completes tasks accurately and on time.</p>
                        </div>

                        <!-- Rating 4 -->
                        <div class="bg-gradient-to-br from-teal-50 to-teal-25 border border-teal-200 rounded-xl p-5 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-white border border-teal-300 flex items-center justify-center shadow-sm">
                                    <span class="text-xl font-bold text-teal-700">4</span>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900">Exceeds</h4>
                                    <span class="text-xs font-medium text-teal-600 bg-teal-100 px-2 py-1 rounded-full">Above Expectations</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Regularly exceeds expectations and demonstrates above-average performance. Strong contributor who often goes beyond basic requirements.</p>
                        </div>
                    </div>
                    
                    <!-- Rating 5 - Full Width -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5 hover:shadow-sm transition-all">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-14 h-14 rounded-xl bg-white border border-green-300 flex items-center justify-center shadow-sm">
                                <span class="text-2xl font-bold text-green-700">5</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Exceptional</h4>
                                <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">Outstanding Performance</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">Consistently exceeds expectations significantly and demonstrates exceptional performance. Role model for others, demonstrates innovation and leadership.</p>
                    </div>
                    
                    <!-- Rating Guidelines -->
                    <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i data-lucide="info" class="w-4 h-4 text-blue-600"></i>
                            Rating Guidelines
                        </h4>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                    <i data-lucide="check" class="w-3 h-3 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-700">Select ratings based on consistent performance during the entire review period</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Consider performance trends over time, not isolated incidents</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                    <i data-lucide="check" class="w-3 h-3 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-700">Consider both quantitative metrics and qualitative observations</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Balance measurable results with behavioral observations and peer feedback</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                    <i data-lucide="check" class="w-3 h-3 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-700">Provide specific examples in the additional feedback section</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Include concrete examples to support each rating given</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                    <i data-lucide="check" class="w-3 h-3 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-700">Be fair and objective in your assessment</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Avoid personal biases and focus on job-related performance</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="p-4 bg-gradient-to-r from-blue-50 to-blue-100/30 rounded-lg border border-blue-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            <i data-lucide="lightbulb" class="w-4 h-4 text-amber-600"></i>
                            Tips for Effective Evaluation
                        </h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li class="flex items-start gap-2">
                                <div class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                                <span>Review the employee's job description and performance goals before rating</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <div class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                                <span>Document performance incidents throughout the review period</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <div class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                                <span>Balance strengths with areas for development in your feedback</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <div class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                                <span>Consider the employee's growth and development over time</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Modal Footer with Close Button -->
            <div class="modal-footer">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 text-center sm:text-left">
                        <p>Use these guidelines to provide fair and consistent performance ratings</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="closeModalButton" class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-colors shadow-sm flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Got It, Close Guide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="evaluation_date"]').value = today;
        
        // Sample employee data by department
        const employeesByDepartment = {
            '1': [ // Front Office
                { id: 1, name: 'Alex Johnson', position: 'Front Desk Manager' },
                { id: 2, name: 'Sarah Williams', position: 'Guest Service Agent' },
                { id: 3, name: 'Michael Chen', position: 'Night Auditor' }
            ],
            '2': [ // Housekeeping
                { id: 4, name: 'Maria Garcia', position: 'Housekeeping Supervisor' },
                { id: 5, name: 'James Brown', position: 'Room Attendant' },
                { id: 6, name: 'Lisa Taylor', position: 'Laundry Supervisor' }
            ],
            '3': [ // Food & Beverage
                { id: 7, name: 'David Chen', position: 'F&B Manager' },
                { id: 8, name: 'Sophia Martinez', position: 'Restaurant Supervisor' },
                { id: 9, name: 'Robert Davis', position: 'Bartender' }
            ],
            '4': [ // Kitchen
                { id: 10, name: 'Michael Lee', position: 'Head Chef' },
                { id: 11, name: 'Emma Wang', position: 'Sous Chef' },
                { id: 12, name: 'William Chen', position: 'Line Cook' }
            ],
            '5': [ // Sales & Marketing
                { id: 13, name: 'Robert Davis', position: 'Sales Director' },
                { id: 14, name: 'Sophia Martinez', position: 'Marketing Coordinator' }
            ],
            '6': [ // Human Resources
                { id: 15, name: 'Sarah Williams', position: 'HR Manager' },
                { id: 16, name: 'Thomas Wilson', position: 'Recruitment Specialist' }
            ],
            '7': [ // Maintenance
                { id: 17, name: 'John Anderson', position: 'Maintenance Supervisor' },
                { id: 18, name: 'Brian Miller', position: 'Technician' }
            ],
            '8': [ // Security
                { id: 19, name: 'Kevin Scott', position: 'Security Supervisor' },
                { id: 20, name: 'Jason Moore', position: 'Security Officer' }
            ],
            '9': [ // Spa & Wellness
                { id: 21, name: 'Jessica Taylor', position: 'Spa Manager' },
                { id: 22, name: 'Amanda White', position: 'Therapist' }
            ],
            '10': [ // Banquets & Events
                { id: 23, name: 'Daniel Clark', position: 'Events Manager' },
                { id: 24, name: 'Olivia Lewis', position: 'Banquet Supervisor' }
            ]
        };
        
        // Department Selection Functionality
        const departmentSelect = document.getElementById('departmentSelect');
        const employeeSelect = document.getElementById('employeeSelect');
        const employeeCount = document.getElementById('employeeCount');
        const countNumber = document.getElementById('countNumber');
        
        departmentSelect.addEventListener('change', function() {
            const selectedDept = this.value;
            
            if (selectedDept) {
                // Enable employee dropdown
                employeeSelect.disabled = false;
                employeeSelect.className = employeeSelect.className.replace('bg-gray-100', 'bg-white');
                
                // Clear current options
                employeeSelect.innerHTML = '<option disabled selected>Select an employee</option>';
                
                // Add employees from selected department
                if (employeesByDepartment[selectedDept]) {
                    const employees = employeesByDepartment[selectedDept];
                    
                    employees.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.id;
                        option.textContent = `${employee.name} - ${employee.position}`;
                        employeeSelect.appendChild(option);
                    });
                    
                    // Update employee count display
                    countNumber.textContent = employees.length;
                    employeeCount.classList.remove('hidden');
                }
            } else {
                // Disable and reset employee dropdown
                employeeSelect.disabled = true;
                employeeSelect.className = employeeSelect.className.replace('bg-white', 'bg-gray-100');
                employeeSelect.innerHTML = '<option disabled selected>Select department first</option>';
                employeeCount.classList.add('hidden');
            }
        });
        
        // Modal Controls
        const legendButton = document.getElementById('legendButton');
        const legendModal = document.getElementById('legendModal');
        const closeModalHeader = document.getElementById('closeModalHeader');
        const closeModalButton = document.getElementById('closeModalButton');
        
        legendButton.addEventListener('click', () => {
            legendModal.classList.add('modal-open');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            lucide.createIcons(); // Reinitialize icons in modal
        });
        
        // Function to close modal
        function closeModal() {
            legendModal.classList.remove('modal-open');
            document.body.style.overflow = 'auto'; // Restore background scrolling
        }
        
        // Close modal from header button
        closeModalHeader.addEventListener('click', closeModal);
        
        // Close modal from footer button
        closeModalButton.addEventListener('click', closeModal);
        
        // Close modal when clicking outside or pressing Escape
        legendModal.addEventListener('click', (e) => {
            if (e.target === legendModal) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && legendModal.classList.contains('modal-open')) {
                closeModal();
            }
        });
        
        // Rating Display Update
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const ratingValue = this.value;
                const name = this.name;
                let displayId;
                
                if (name === 'productivity_rating') {
                    displayId = 'productivityDisplay';
                } else if (name === 'development_rating') {
                    displayId = 'developmentDisplay';
                } else if (name === 'compliance_rating') {
                    displayId = 'complianceDisplay';
                }
                
                const ratingDisplay = document.getElementById(displayId);
                if (ratingDisplay) {
                    ratingDisplay.textContent = `${ratingValue}/5`;
                }
            });
        });
        
        // Form Submission
        document.getElementById('submitButton').addEventListener('click', async (e) => {
            e.preventDefault();
            
            // Validation
            const department = document.getElementById('departmentSelect');
            const employee = document.getElementById('employeeSelect');
            const reviewer = document.querySelector('input[name="reviewer_name"]');
            const reviewPeriod = document.querySelector('input[name="review_period"]');
            
            if (!department.value) {
                showMessage('Please select a department first', 'error');
                department.focus();
                return;
            }
            
            if (!employee.value || employee.value === 'Select an employee') {
                showMessage('Please select an employee', 'error');
                employee.focus();
                return;
            }
            
            if (!reviewer.value.trim()) {
                showMessage('Please enter reviewer name', 'error');
                reviewer.focus();
                return;
            }
            
            if (!reviewPeriod.value.trim()) {
                showMessage('Please enter review period', 'error');
                reviewPeriod.focus();
                return;
            }
            
            // Get form data
            const formData = {
                department_id: department.value,
                employee_id: employee.value,
                reviewer_name: reviewer.value,
                evaluation_date: document.querySelector('input[name="evaluation_date"]').value,
                review_period: reviewPeriod.value,
                productivity_rating: document.querySelector('input[name="productivity_rating"]:checked')?.value || '3',
                development_rating: document.querySelector('input[name="development_rating"]:checked')?.value || '4',
                compliance_rating: document.querySelector('input[name="compliance_rating"]:checked')?.value || '3',
                additional_feedback: document.querySelector('textarea[name="additional_feedback"]').value
            };
            
            // Show loading
            const btn = document.getElementById('submitButton');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-circle" class="w-4 h-4 animate-spin inline-block mr-2"></i>Submitting...';
            btn.disabled = true;
            
            try {
                // Submit to PHP backend
                const response = await fetch('save_evaluation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Evaluation submitted successfully!', 'success');
                    
                    // Reset form
                    document.getElementById('evaluationForm').reset();
                    departmentSelect.value = '';
                    departmentSelect.dispatchEvent(new Event('change'));
                    document.querySelector('input[name="evaluation_date"]').value = today;
                    
                    // Reset rating displays
                    document.getElementById('productivityDisplay').textContent = '3/5';
                    document.getElementById('developmentDisplay').textContent = '4/5';
                    document.getElementById('complianceDisplay').textContent = '3/5';
                    
                    // Reset radio buttons to default
                    document.querySelector('input[name="productivity_rating"][value="3"]').checked = true;
                    document.querySelector('input[name="development_rating"][value="4"]').checked = true;
                    document.querySelector('input[name="compliance_rating"][value="3"]').checked = true;
                } else {
                    showMessage(result.message || 'Error submitting evaluation', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error submitting evaluation. Please try again.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
                lucide.createIcons();
            }
        });
        
        // Save Draft Button
        document.getElementById('saveDraftButton').addEventListener('click', async () => {
            const btn = document.getElementById('saveDraftButton');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-circle" class="w-4 h-4 animate-spin inline-block mr-2"></i>Saving...';
            btn.disabled = true;
            
            // Simulate save
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                lucide.createIcons();
                showMessage('Draft saved successfully', 'success');
            }, 800);
        });
        
        // Cancel Button
        document.getElementById('cancelButton').addEventListener('click', () => {
            if (confirm('Are you sure you want to cancel? All unsaved changes will be lost.')) {
                document.getElementById('evaluationForm').reset();
                departmentSelect.value = '';
                departmentSelect.dispatchEvent(new Event('change'));
                document.querySelector('input[name="evaluation_date"]').value = today;
                
                // Reset rating displays
                document.getElementById('productivityDisplay').textContent = '3/5';
                document.getElementById('developmentDisplay').textContent = '4/5';
                document.getElementById('complianceDisplay').textContent = '3/5';
            }
        });
        
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            const colors = {
                success: { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-200', icon: 'check-circle' },
                error: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', icon: 'alert-circle' }
            };
            
            messageDiv.className = `fixed top-4 right-4 px-4 py-3 ${colors[type].bg} ${colors[type].text} rounded-lg border ${colors[type].border} shadow-lg z-50 animate-slide-in`;
            messageDiv.innerHTML = `
                <div class="flex items-center gap-2">
                    <i data-lucide="${colors[type].icon}" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">${message}</span>
                </div>
            `;
            document.body.appendChild(messageDiv);
            lucide.createIcons();
            
            setTimeout(() => {
                messageDiv.classList.add('animate-slide-out');
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>