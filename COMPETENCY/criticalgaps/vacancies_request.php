<?php
 
 if (($_GET['action'] ?? '') === 'fetch_vacancies') {
     header('Content-Type: application/json; charset=utf-8');
 
     $url = 'https://hr4.soliera-hotel-restaurant.com/CHM/API/save_employee.php';
     $raw = null;
 
     if (function_exists('curl_init')) {
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
         curl_setopt($ch, CURLOPT_TIMEOUT, 15);
         $raw = curl_exec($ch);
         $err = curl_error($ch);
         $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);
 
         if ($raw === false || $status >= 400) {
             echo json_encode([
                 'success' => false,
                 'message' => 'Failed to fetch vacancies from remote API',
                 'status' => $status,
                 'error' => $err,
             ]);
             exit;
         }
     } else {
         $context = stream_context_create([
             'http' => [
                 'method' => 'GET',
                 'timeout' => 15,
                 'header' => "Accept: application/json\r\n",
             ],
         ]);
         $raw = @file_get_contents($url, false, $context);
         if ($raw === false) {
             echo json_encode([
                 'success' => false,
                 'message' => 'Failed to fetch vacancies from remote API',
             ]);
             exit;
         }
     }
 
     $decoded = json_decode($raw, true);
     if (!is_array($decoded)) {
         echo json_encode([
             'success' => false,
             'message' => 'Remote API returned invalid JSON',
             'raw' => substr((string)$raw, 0, 500),
         ]);
         exit;
     }
 
     $mapped = [];
     foreach ($decoded as $key => $row) {
         if (!is_array($row)) {
             continue;
         }
         $id = (string)($row['id'] ?? $key);
         if ($id === '') {
             continue;
         }
         $row['request_id'] = $id;
         $mapped[$id] = $row;
     }
 
     echo json_encode([
         'success' => true,
         'data' => $mapped,
     ]);
     exit;
 }
 
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save_job_details') {
     header('Content-Type: application/json; charset=utf-8');
 
     $requestId = trim((string)($_POST['request_id'] ?? ''));
     $jobTitle = trim((string)($_POST['job_title'] ?? ''));
     $jobDescription = trim((string)($_POST['job_description'] ?? ''));
 
     $parseList = function ($value) use (&$parseList): array {
         if (is_array($value)) {
             $out = [];
             foreach ($value as $v) {
                 $t = trim((string)$v);
                 if ($t !== '') {
                     $out[] = $t;
                 }
             }
             return $out;
         }
 
         $raw = trim((string)$value);
         if ($raw === '') {
             return [];
         }
 
         if ($raw[0] === '[') {
             $decoded = json_decode($raw, true);
             if (is_array($decoded)) {
                 return $parseList($decoded);
             }
         }
 
         $lines = preg_split('/\r\n|\r|\n/', $raw);
         $out = [];
         foreach ($lines as $line) {
             $t = trim((string)$line);
             if ($t !== '') {
                 $out[] = $t;
             }
         }
         return $out;
     };
 
     $qualifications = $parseList($_POST['qualifications'] ?? '');
     $requirements = $parseList($_POST['requirements'] ?? '');
 
     if ($requestId === '') {
         echo json_encode(['success' => false, 'message' => 'Missing request_id']);
         exit;
     }
     if ($jobDescription === '') {
         echo json_encode(['success' => false, 'message' => 'Job description is required']);
         exit;
     }
 
     try {
         require_once __DIR__ . '/../job_desc/db_job_desc.php';
         $conn = job_desc_mysqli();
         $conn->begin_transaction();
 
         $tableExists = function (mysqli $c, string $table): bool {
             $t = $c->real_escape_string($table);
             $res = $c->query("SHOW TABLES LIKE '{$t}'");
             if (!$res) {
                 return false;
             }
             $exists = $res->num_rows > 0;
             $res->free();
             return $exists;
         };
 
         if ($tableExists($conn, 'job_description')) {
             $stmtDel = $conn->prepare('DELETE FROM job_description WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();
 
             $stmtIns = $conn->prepare('INSERT INTO job_description (request_id, job_description) VALUES (?, ?)');
             $stmtIns->bind_param('ss', $requestId, $jobDescription);
             $stmtIns->execute();
             $stmtIns->close();
         } elseif ($tableExists($conn, 'job_roles')) {
             $stmtUp = $conn->prepare('UPDATE job_roles SET description = ? WHERE request_id = ?');
             $stmtUp->bind_param('ss', $jobDescription, $requestId);
             $stmtUp->execute();
             $affected = $stmtUp->affected_rows;
             $stmtUp->close();
 
             if ($affected === 0 && $jobTitle !== '') {
                 $vacancies = 1;
                 $stmtInsRole = $conn->prepare('INSERT INTO job_roles (request_id, name, vacancies, description) VALUES (?, ?, ?, ?)');
                 $stmtInsRole->bind_param('ssis', $requestId, $jobTitle, $vacancies, $jobDescription);
                 $stmtInsRole->execute();
                 $stmtInsRole->close();
             }
         } else {
             throw new RuntimeException('Missing job_description/job_roles table in job_desc database');
         }
 
         if ($tableExists($conn, 'qualificcaion')) {
             $stmtDel = $conn->prepare('DELETE FROM qualificcaion WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();
 
             $stmtIns = $conn->prepare('INSERT INTO qualificcaion (request_id, qualification) VALUES (?, ?)');
             foreach ($qualifications as $q) {
                 $stmtIns->bind_param('ss', $requestId, $q);
                 $stmtIns->execute();
             }
             $stmtIns->close();
         } elseif ($tableExists($conn, 'qualifications')) {
             $stmtDel = $conn->prepare('DELETE FROM qualifications WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();
 
             $stmtIns = $conn->prepare('INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)');
             $type = 'General';
             $priority = 1;
             foreach ($qualifications as $q) {
                 $stmtIns->bind_param('sssi', $requestId, $q, $type, $priority);
                 $stmtIns->execute();
                 $priority++;
             }
             $stmtIns->close();
         }
 
         if ($tableExists($conn, 'requirements')) {
             $stmtDel = $conn->prepare('DELETE FROM requirements WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();
 
             $stmtIns = $conn->prepare('INSERT INTO requirements (request_id, requirement) VALUES (?, ?)');
             foreach ($requirements as $r) {
                 $stmtIns->bind_param('ss', $requestId, $r);
                 $stmtIns->execute();
             }
             $stmtIns->close();
         } elseif ($tableExists($conn, 'job_requirements')) {
             $stmtDel = $conn->prepare('DELETE FROM job_requirements WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();
 
             $stmtIns = $conn->prepare('INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)');
             $category = 'General';
             $essential = 1;
             foreach ($requirements as $r) {
                 $stmtIns->bind_param('sssi', $requestId, $r, $category, $essential);
                 $stmtIns->execute();
             }
             $stmtIns->close();
         }
 
         $conn->commit();
         $conn->close();
 
         echo json_encode(['success' => true, 'message' => 'Saved']);
         exit;
     } catch (Throwable $e) {
         if (isset($conn) && $conn instanceof mysqli) {
             try { $conn->rollback(); } catch (Throwable $t) {}
             try { $conn->close(); } catch (Throwable $t) {}
         }
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
         exit;
     }
 }
 
 require('../../partials/header.php');
 ?>
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

                <dialog id="jobDetailsModal" class="modal">
                    <div class="modal-box max-w-3xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Add Job Details</h3>

                        <form id="jobDetailsForm" class="space-y-4">
                            <input type="hidden" name="request_id" id="jobDetailsRequestId">
                            <input type="hidden" name="job_title" id="jobDetailsJobTitle">

                            <div>
                                <label class="label">
                                    <span class="label-text">Request ID</span>
                                </label>
                                <input type="text" class="input input-bordered w-full" id="jobDetailsRequestIdDisplay" disabled>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Job Title</span>
                                </label>
                                <input type="text" class="input input-bordered w-full" id="jobDetailsJobTitleDisplay" disabled>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Job Description</span>
                                </label>
                                <textarea name="job_description" id="jobDescriptionInput" class="textarea textarea-bordered w-full" rows="5" required></textarea>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Qualifications (one per line)</span>
                                </label>
                                <textarea name="qualifications" id="qualificationsInput" class="textarea textarea-bordered w-full" rows="4"></textarea>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Requirements (one per line)</span>
                                </label>
                                <textarea name="requirements" id="requirementsInput" class="textarea textarea-bordered w-full" rows="4"></textarea>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" class="btn btn-ghost" onclick="document.getElementById('jobDetailsModal').close()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="saveJobDetailsBtn">Save</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            </main>

            <!-- JavaScript for handling vacancies data -->
            <script>
                let vacanciesData = {};

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

                    function safeText(value) {
                        if (value === null || value === undefined) return '';
                        return String(value);
                    }

                    function formatType(type) {
                        const t = safeText(type).toLowerCase().replace(/_/g, '-');
                        if (!t) return '';
                        return t;
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

                        vacanciesTableBody.innerHTML = vacanciesArray.map(vacancy => {
                            const requestId = safeText(vacancy.request_id || vacancy.id);
                            const typeNormalized = formatType(vacancy.type);
                            const statusNormalized = safeText(vacancy.status).toLowerCase();
                            const isExpired = Boolean(vacancy.is_expired);
                            const daysRemaining = vacancy.days_remaining !== undefined && vacancy.days_remaining !== null
                                ? Number(vacancy.days_remaining)
                                : null;
                            const createdAt = vacancy.created_at ? new Date(vacancy.created_at) : null;
                            const createdAtLabel = createdAt && !isNaN(createdAt.getTime()) ? createdAt.toLocaleDateString() : '';

                            return `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-4 px-6">
                                <div>
                                    <div class="font-medium text-gray-900">${safeText(vacancy.title)}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                        Created: ${createdAtLabel}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-700">${safeText(vacancy.department || vacancy.department_name)}</div>
                                ${vacancy.sub_department ? `<div class="text-sm text-gray-500">${safeText(vacancy.sub_department)}</div>` : ''}
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${typeNormalized === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                      typeNormalized === 'full-time' || typeNormalized === 'full time' ? 'bg-green-50 text-green-700' : 
                                      'bg-purple-50 text-purple-700'}">
                                    <i data-lucide="${typeNormalized === 'contract' ? 'file-text' : 
                                                     typeNormalized === 'full-time' || typeNormalized === 'full time' ? 'briefcase' : 
                                                     'clock'}" 
                                       class="w-4 h-4"></i>
                                    ${safeText(vacancy.type)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-5 h-5 text-gray-400"></i>
                                    <span class="font-semibold">${safeText(vacancy.vacancies)}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">${safeText(vacancy.salary_range)}</div>
                                <div class="text-sm text-gray-500">per hour</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${statusNormalized === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                    <i data-lucide="${statusNormalized === 'open' ? 'check-circle' : 'x-circle'}" 
                                       class="w-4 h-4"></i>
                                    ${safeText(vacancy.status)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar-clock" class="w-5 h-5 
                                        ${isExpired ? 'text-red-500' : 
                                         (daysRemaining !== null && daysRemaining <= 5) ? 'text-orange-500' : 
                                         'text-green-500'}"></i>
                                    <span class="font-medium ${isExpired ? 'text-red-600' : 
                                                              (daysRemaining !== null && daysRemaining <= 5) ? 'text-orange-600' : 
                                                              'text-green-600'}">
                                        ${isExpired ? 'Expired' : (daysRemaining === null ? '' : `${daysRemaining} days`)}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <button class="btn btn-sm btn-ghost btn-square" 
                                            onclick="viewVacancyDetails('${requestId}')"
                                            title="View Details">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn btn-sm btn-ghost btn-square"
                                            onclick="openJobDetailsModal('${requestId}')"
                                            title="Add Job Details">
                                        <i data-lucide="file-plus" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                        }).join('');

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
                            safeText(vacancy.title).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.department || vacancy.department_name).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.type).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.status).toLowerCase().includes(searchTerm)
                        );

                        renderVacancies(filteredData.reduce((acc, vacancy) => {
                            const k = safeText(vacancy.request_id || vacancy.id);
                            acc[k] = vacancy;
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
                                    <label class="text-sm font-medium text-gray-500">Request ID</label>
                                    <p class="mt-1 font-semibold">${safeText(vacancy.request_id || vacancy.id)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Title</label>
                                    <p class="mt-1 text-lg font-semibold">${safeText(vacancy.title)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1">${safeText(vacancy.department || vacancy.department_name)}</p>
                                    ${vacancy.sub_department ? `<p class="text-sm text-gray-600">${safeText(vacancy.sub_department)}</p>` : ''}
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Employment Type</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                            ${formatType(vacancy.type) === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                              formatType(vacancy.type) === 'full-time' || formatType(vacancy.type) === 'full time' ? 'bg-green-50 text-green-700' : 
                                              'bg-purple-50 text-purple-700'}">
                                            ${safeText(vacancy.type)}
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
                                            ${safeText(vacancy.status).toLowerCase() === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                            <i data-lucide="${safeText(vacancy.status).toLowerCase() === 'open' ? 'check-circle' : 'x-circle'}" 
                                               class="w-4 h-4"></i>
                                            ${safeText(vacancy.status)}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Available Vacancies</label>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">${safeText(vacancy.vacancies)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Days Remaining</label>
                                    <p class="mt-1 text-lg font-semibold ${vacancy.is_expired ? 'text-red-600' : 
                                                                        (vacancy.days_remaining !== undefined && vacancy.days_remaining !== null && Number(vacancy.days_remaining) <= 5) ? 'text-orange-600' : 
                                                                        'text-green-600'}">
                                        ${vacancy.is_expired ? 'Expired' : (vacancy.days_remaining === undefined || vacancy.days_remaining === null ? '' : `${vacancy.days_remaining} days`)}
                                    </p>
                                </div>
                            </div>

                            <!-- Salary Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Salary Range</label>
                                    <p class="mt-1 text-xl font-bold text-gray-900">${safeText(vacancy.salary_range)}</p>
                                    <p class="text-sm text-gray-500">per hour</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Period</label>
                                    <p class="mt-1">${safeText(vacancy.job_period_days)}${vacancy.job_period_days ? ' days' : ''}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Exam Required</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="${safeText(vacancy.exam_required) === '1' ? 'check-circle' : 'x-circle'}" 
                                               class="w-5 h-5 ${safeText(vacancy.exam_required) === '1' ? 'text-green-500' : 'text-red-500'}"></i>
                                            ${safeText(vacancy.exam_required) === '1' ? 'Yes' : 'No'}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Created At</label>
                                    <p class="mt-1">${safeText(vacancy.created_at_formatted || vacancy.created_at)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Last Updated</label>
                                    <p class="mt-1">${safeText(vacancy.updated_at_formatted || vacancy.updated_at)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job End Date</label>
                                    <p class="mt-1">${safeText(vacancy.job_end_date)}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-end gap-3">
                                <button class="btn btn-ghost" onclick="document.getElementById('detailModal').close()">Close</button>
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

                    window.openJobDetailsModal = function(vacancyId) {
                        const vacancy = vacanciesData[vacancyId];
                        if (!vacancy) return;

                        const requestId = safeText(vacancy.request_id || vacancy.id);
                        document.getElementById('jobDetailsRequestId').value = requestId;
                        document.getElementById('jobDetailsJobTitle').value = safeText(vacancy.title);
                        document.getElementById('jobDetailsRequestIdDisplay').value = requestId;
                        document.getElementById('jobDetailsJobTitleDisplay').value = safeText(vacancy.title);
                        document.getElementById('jobDescriptionInput').value = '';
                        document.getElementById('qualificationsInput').value = '';
                        document.getElementById('requirementsInput').value = '';

                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        document.getElementById('jobDetailsModal').showModal();
                    };

                    async function fetchVacancies() {
                        loadingState.classList.remove('hidden');
                        emptyState.classList.add('hidden');
                        try {
                            const res = await fetch(window.location.pathname + '?action=fetch_vacancies', {
                                headers: { 'Accept': 'application/json' }
                            });
                            const json = await res.json();
                            if (!json || !json.success) {
                                throw new Error(json && json.message ? json.message : 'Failed to load vacancies');
                            }
                            vacanciesData = json.data || {};
                            renderVacancies(vacanciesData);
                        } catch (e) {
                            loadingState.classList.add('hidden');
                            emptyState.classList.remove('hidden');
                            vacanciesTableBody.innerHTML = '';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: e.message || 'Failed to load vacancies'
                                });
                            }
                        }
                    }

                    document.getElementById('jobDetailsForm').addEventListener('submit', async function(e) {
                        e.preventDefault();
                        const form = e.currentTarget;
                        const btn = document.getElementById('saveJobDetailsBtn');
                        btn.disabled = true;
                        try {
                            const fd = new FormData(form);
                            const res = await fetch(window.location.pathname + '?action=save_job_details', {
                                method: 'POST',
                                body: fd
                            });
                            const json = await res.json();
                            if (!json || !json.success) {
                                throw new Error(json && json.message ? json.message : 'Failed to save');
                            }

                            document.getElementById('jobDetailsModal').close();
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: 'Job details saved successfully'
                                });
                            }
                        } catch (err) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: err.message || 'Failed to save'
                                });
                            }
                        } finally {
                            btn.disabled = false;
                        }
                    });

                    fetchVacancies();

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