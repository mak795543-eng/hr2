<?php require('../../partials/header.php') ?>

<body class="bg-base-100 min-h-screen bg-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../../USM/sidebarr.php'; ?>

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../../USM/navbar.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-auto p-4 md:p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Job Vacancies</h1>
                    <p class="text-gray-600 mt-2">View and manage all available job vacancies</p>
                </div>

                <!-- Filters and Actions -->
                <div class="bg-base-100 rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <!-- Search Bar -->
                        <div class="flex-1 w-full md:w-auto">
                            <div class="relative">
                                <input
                                    type="text"
                                    placeholder="Search vacancies..."
                                    class="input input-bordered w-full pl-10"
                                    id="searchInput">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <select class="select select-bordered select-sm">
                                <option value="">All Departments</option>
                                <option value="1">Human Resources</option>
                                <option value="2">IT</option>
                                <option value="3">Finance</option>
                            </select>

                            <select class="select select-bordered select-sm">
                                <option value="">All Types</option>
                                <option value="contract">Contract</option>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                            </select>

                            <select class="select select-bordered select-sm">
                                <option value="">All Status</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Vacancies Table -->
                <div class="bg-base-100 rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span>Job Title</span>
                                            <button class="btn btn-ghost btn-xs">
                                                <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Department</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Type</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Vacancies</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Salary Range</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Days Remaining</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="vacanciesTableBody">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="p-8 text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                        <p class="mt-4 text-gray-600">Loading vacancies...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden p-8 text-center">
                        <i data-lucide="briefcase" class="w-16 h-16 text-gray-300 mx-auto"></i>
                        <p class="mt-4 text-gray-600">No vacancies found</p>
                    </div>

                    <!-- Pagination -->
                    <div class="border-t border-gray-200 p-4">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-600">
                                Showing <span id="startCount">1</span> to <span id="endCount">10</span> of
                                <span id="totalCount">0</span> entries
                            </div>
                            <div class="join">
                                <button class="join-item btn btn-sm" id="prevPage">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </button>
                                <button class="join-item btn btn-sm btn-active">1</button>
                                <button class="join-item btn btn-sm">2</button>
                                <button class="join-item btn btn-sm">3</button>
                                <button class="join-item btn btn-sm" id="nextPage">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Modal -->
                <dialog id="detailModal" class="modal">
                    <div class="modal-box max-w-4xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Vacancy Details</h3>
                        <div id="modalContent">
                            <!-- Details will be populated here -->
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            </main>

            <!-- JavaScript for handling vacancies data -->
            <script>
                // Sample data - in real application, this would come from an API
                const vacanciesData = {
                    "14": {
                        "id": "14",
                        "department_id": "1",
                        "sub_department_id": null,
                        "title": "Senior HR Specialist",
                        "type": "contract",
                        "status": "open",
                        "vacancies": "64",
                        "exam_required": "0",
                        "created_at": "2026-01-13 06:14:58",
                        "updated_at": "2026-01-13 06:14:58",
                        "salary_min": "33",
                        "salary_max": "52",
                        "job_period_days": "1",
                        "department_name": "Human Resources",
                        "created_at_formatted": "2026-01-13 06:14:58",
                        "updated_at_formatted": "2026-01-13 06:14:58",
                        "job_end_date": "2026-01-14",
                        "salary_range": "₱33.00 - ₱52.00",
                        "days_remaining": 12,
                        "is_expired": true,
                        "department": "Human Resources",
                        "sub_department": null
                    },
                    // Add more sample data
                    "15": {
                        "id": "15",
                        "department_id": "2",
                        "sub_department_id": "5",
                        "title": "Frontend Developer",
                        "type": "full-time",
                        "status": "open",
                        "vacancies": "3",
                        "exam_required": "1",
                        "created_at": "2026-01-14 10:30:00",
                        "updated_at": "2026-01-14 10:30:00",
                        "salary_min": "45",
                        "salary_max": "65",
                        "job_period_days": "30",
                        "department_name": "Information Technology",
                        "created_at_formatted": "2026-01-14 10:30:00",
                        "updated_at_formatted": "2026-01-14 10:30:00",
                        "job_end_date": "2026-02-13",
                        "salary_range": "₱45.00 - ₱65.00",
                        "days_remaining": 25,
                        "is_expired": false,
                        "department": "IT Department",
                        "sub_department": "Web Development"
                    },
                    "16": {
                        "id": "16",
                        "department_id": "3",
                        "sub_department_id": null,
                        "title": "Financial Analyst",
                        "type": "contract",
                        "status": "closed",
                        "vacancies": "2",
                        "exam_required": "1",
                        "created_at": "2026-01-10 14:20:00",
                        "updated_at": "2026-01-10 14:20:00",
                        "salary_min": "38",
                        "salary_max": "55",
                        "job_period_days": "15",
                        "department_name": "Finance",
                        "created_at_formatted": "2026-01-10 14:20:00",
                        "updated_at_formatted": "2026-01-10 14:20:00",
                        "job_end_date": "2026-01-25",
                        "salary_range": "₱38.00 - ₱55.00",
                        "days_remaining": 5,
                        "is_expired": false,
                        "department": "Finance",
                        "sub_department": null
                    }
                };

                document.addEventListener('DOMContentLoaded', function() {
                    const vacanciesTableBody = document.getElementById('vacanciesTableBody');
                    const loadingState = document.getElementById('loadingState');
                    const emptyState = document.getElementById('emptyState');
                    const searchInput = document.getElementById('searchInput');
                    const detailModal = document.getElementById('detailModal');
                    const modalContent = document.getElementById('modalContent');

                    // Initialize Lucide icons
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    // Render vacancies table
                    function renderVacancies(data) {
                        const vacanciesArray = Object.values(data);

                        if (vacanciesArray.length === 0) {
                            loadingState.classList.add('hidden');
                            emptyState.classList.remove('hidden');
                            vacanciesTableBody.innerHTML = '';
                            return;
                        }

                        loadingState.classList.add('hidden');
                        emptyState.classList.add('hidden');

                        vacanciesTableBody.innerHTML = vacanciesArray.map(vacancy => `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-4 px-6">
                                <div>
                                    <div class="font-medium text-gray-900">${vacancy.title}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                        Created: ${new Date(vacancy.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-700">${vacancy.department}</div>
                                ${vacancy.sub_department ? 
                                    `<div class="text-sm text-gray-500">${vacancy.sub_department}</div>` : ''}
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${vacancy.type === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                      vacancy.type === 'full-time' ? 'bg-green-50 text-green-700' : 
                                      'bg-purple-50 text-purple-700'}">
                                    <i data-lucide="${vacancy.type === 'contract' ? 'file-text' : 
                                                     vacancy.type === 'full-time' ? 'briefcase' : 
                                                     'clock'}" 
                                       class="w-4 h-4"></i>
                                    ${vacancy.type.charAt(0).toUpperCase() + vacancy.type.slice(1)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-5 h-5 text-gray-400"></i>
                                    <span class="font-semibold">${vacancy.vacancies}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">${vacancy.salary_range}</div>
                                <div class="text-sm text-gray-500">per hour</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${vacancy.status === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                    <i data-lucide="${vacancy.status === 'open' ? 'check-circle' : 'x-circle'}" 
                                       class="w-4 h-4"></i>
                                    ${vacancy.status.charAt(0).toUpperCase() + vacancy.status.slice(1)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar-clock" class="w-5 h-5 
                                        ${vacancy.is_expired ? 'text-red-500' : 
                                         vacancy.days_remaining <= 5 ? 'text-orange-500' : 
                                         'text-green-500'}"></i>
                                    <span class="font-medium ${vacancy.is_expired ? 'text-red-600' : 
                                                              vacancy.days_remaining <= 5 ? 'text-orange-600' : 
                                                              'text-green-600'}">
                                        ${vacancy.is_expired ? 'Expired' : `${vacancy.days_remaining} days`}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <button class="btn btn-sm btn-ghost btn-square" 
                                            onclick="viewVacancyDetails('${vacancy.id}')"
                                            title="View Details">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn btn-sm btn-ghost btn-square ${vacancy.status === 'closed' ? 'text-gray-400' : ''}"
                                            ${vacancy.status === 'closed' ? 'disabled' : ''}
                                            title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn btn-sm btn-ghost btn-square text-red-600 hover:bg-red-50"
                                            title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');

                        // Update counts
                        document.getElementById('totalCount').textContent = vacanciesArray.length;
                        document.getElementById('endCount').textContent = vacanciesArray.length;

                        // Re-initialize icons for newly added elements
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }

                    // Search functionality
                    searchInput.addEventListener('input', function(e) {
                        const searchTerm = e.target.value.toLowerCase();
                        const filteredData = Object.values(vacanciesData).filter(vacancy =>
                            vacancy.title.toLowerCase().includes(searchTerm) ||
                            vacancy.department.toLowerCase().includes(searchTerm) ||
                            vacancy.type.toLowerCase().includes(searchTerm) ||
                            vacancy.status.toLowerCase().includes(searchTerm)
                        );

                        renderVacancies(filteredData.reduce((acc, vacancy) => {
                            acc[vacancy.id] = vacancy;
                            return acc;
                        }, {}));
                    });

                    // View vacancy details
                    window.viewVacancyDetails = function(vacancyId) {
                        const vacancy = vacanciesData[vacancyId];
                        if (!vacancy) return;

                        modalContent.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Title</label>
                                    <p class="mt-1 text-lg font-semibold">${vacancy.title}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1">${vacancy.department}</p>
                                    ${vacancy.sub_department ? 
                                        `<p class="text-sm text-gray-600">${vacancy.sub_department}</p>` : ''}
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Employment Type</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                            ${vacancy.type === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                              vacancy.type === 'full-time' ? 'bg-green-50 text-green-700' : 
                                              'bg-purple-50 text-purple-700'}">
                                            ${vacancy.type.charAt(0).toUpperCase() + vacancy.type.slice(1)}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                            ${vacancy.status === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                            <i data-lucide="${vacancy.status === 'open' ? 'check-circle' : 'x-circle'}" 
                                               class="w-4 h-4"></i>
                                            ${vacancy.status.charAt(0).toUpperCase() + vacancy.status.slice(1)}
                                        </span>
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Available Vacancies</label>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">${vacancy.vacancies}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Days Remaining</label>
                                    <p class="mt-1 text-lg font-semibold ${vacancy.is_expired ? 'text-red-600' : 
                                                                        vacancy.days_remaining <= 5 ? 'text-orange-600' : 
                                                                        'text-green-600'}">
                                        ${vacancy.is_expired ? 'Expired' : `${vacancy.days_remaining} days`}
                                    </p>
                                </div>
                            </div>

                            <!-- Salary Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Salary Range</label>
                                    <p class="mt-1 text-xl font-bold text-gray-900">${vacancy.salary_range}</p>
                                    <p class="text-sm text-gray-500">per hour</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Period</label>
                                    <p class="mt-1">${vacancy.job_period_days} days</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Exam Required</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="${vacancy.exam_required === '1' ? 'check-circle' : 'x-circle'}" 
                                               class="w-5 h-5 ${vacancy.exam_required === '1' ? 'text-green-500' : 'text-red-500'}"></i>
                                            ${vacancy.exam_required === '1' ? 'Yes' : 'No'}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Created At</label>
                                    <p class="mt-1">${vacancy.created_at_formatted}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Last Updated</label>
                                    <p class="mt-1">${vacancy.updated_at_formatted}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job End Date</label>
                                    <p class="mt-1">${vacancy.job_end_date}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-end gap-3">
                                <button class="btn btn-ghost" onclick="detailModal.close()">Close</button>
                                <button class="btn btn-primary">Apply Now</button>
                            </div>
                        </div>
                    `;

                        // Re-initialize icons in modal
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        detailModal.showModal();
                    };

                    // Initial render
                    setTimeout(() => {
                        renderVacancies(vacanciesData);
                    }, 500); // Simulate loading delay

                    // Pagination handlers
                    document.getElementById('prevPage').addEventListener('click', () => {
                        console.log('Previous page clicked');
                    });

                    document.getElementById('nextPage').addEventListener('click', () => {
                        console.log('Next page clicked');
                    });
                });
            </script>

            <script src="../JAVASCRIPT/sidebar.js"></script>
            <?php require('../../partials/footer.php') ?>