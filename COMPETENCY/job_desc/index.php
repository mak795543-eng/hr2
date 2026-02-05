<?php

session_start();

require_once __DIR__ . '../../../../db.php';

$database = "job_desc";
try {
    $jobDescConn = job_desc_mysqli();
} catch (Throwable $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'updateJobRole') {
    $request_id = $_POST['request_id'] ?? null;
   
    $description = $_POST['description'] ?? '';
    $qualifications = json_decode($_POST['qualifications'] ?? '[]', true);
    $requirements = json_decode($_POST['requirements'] ?? '[]', true);
    if ($request_id) {
        if (!is_array($qualifications)) {
            $qualifications = [];
        }
        if (!is_array($requirements)) {
            $requirements = [];
        }

        $jobRoleRequestId = trim((string)$request_id);
        $description = (string)$description;

        try {
            $conn = job_desc_mysqli();
            $conn->begin_transaction();

            $jobTitle = '';
            $departmentId = '';
            $stmtJob = $conn->prepare("SELECT name, department_id FROM job_roles WHERE request_id = ?");
            $stmtJob->bind_param('s', $jobRoleRequestId);
            $stmtJob->execute();
            $resJob = $stmtJob->get_result();
            $jobRow = $resJob ? $resJob->fetch_assoc() : null;
            $stmtJob->close();
            if ($jobRow) {
                $jobTitle = (string)($jobRow['name'] ?? '');
                $departmentId = (string)($jobRow['department_id'] ?? '');
            }

            $stmtUpRole = $conn->prepare("UPDATE job_roles SET description = ? WHERE request_id = ?");
            $stmtUpRole->bind_param('ss', $description, $jobRoleRequestId);
            $stmtUpRole->execute();
            $stmtUpRole->close();

            $stmtDelQ = $conn->prepare("DELETE FROM qualifications WHERE request_id = ?");
            $stmtDelQ->bind_param('s', $jobRoleRequestId);
            $stmtDelQ->execute();
            $stmtDelQ->close();

            $stmtDelR = $conn->prepare("DELETE FROM job_requirements WHERE request_id = ?");
            $stmtDelR->bind_param('s', $jobRoleRequestId);
            $stmtDelR->execute();
            $stmtDelR->close();

            $stmtInsQ = $conn->prepare("INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)");
            $qPriority = 1;
            foreach ($qualifications as $q) {
                if (!is_array($q)) continue;
                $text = trim((string)($q['text'] ?? ''));
                if ($text === '') continue;
                $type = trim((string)($q['type'] ?? 'Education'));
                if ($type === '') $type = 'Education';
                $stmtInsQ->bind_param('sssi', $jobRoleRequestId, $text, $type, $qPriority);
                $stmtInsQ->execute();
                $qPriority++;
            }
            $stmtInsQ->close();

            $stmtInsR = $conn->prepare("INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)");
            foreach ($requirements as $r) {
                if (!is_array($r)) continue;
                $text = trim((string)($r['text'] ?? ''));
                if ($text === '') continue;
                $cat = trim((string)($r['category'] ?? 'Skill'));
                if ($cat === '') $cat = 'Skill';
                $essential = !empty($r['essential']) ? 1 : 0;
                $stmtInsR->bind_param('sssi', $jobRoleRequestId, $text, $cat, $essential);
                $stmtInsR->execute();
            }
            $stmtInsR->close();

            $stmtMarkAssigned = $conn->prepare("UPDATE recruitment_requests SET status = 'ASSIGNED' WHERE request_id = ?");
            if ($stmtMarkAssigned) {
                $stmtMarkAssigned->bind_param('s', $jobRoleRequestId);
                $stmtMarkAssigned->execute();
                $stmtMarkAssigned->close();
            }

            $conn->commit();
            $conn->close();

            header('Location: index.php?saved=1');
            exit;
        } catch (Throwable $e) {
            if (isset($conn) && $conn instanceof mysqli) {
                try { $conn->rollback(); } catch (Throwable $t) {}
                try { $conn->close(); } catch (Throwable $t) {}
            }
            error_log('Update job role failed: ' . $e->getMessage());
            header('Location: index.php?error=1');
            exit;
        }
    }

    header("Location: index.php?error=1");
    exit;
}

$jobRoles = [];
$roleRows = [];
$roleRes = $jobDescConn->query("SELECT jr.request_id, jr.name, jr.department_id, d.name AS department_name, jr.vacancies, jr.description
                               FROM job_roles jr
                               LEFT JOIN departments d ON d.request_id = jr.department_id
                               ORDER BY jr.updated_at DESC, jr.created_at DESC");
if ($roleRes) {
    while ($row = $roleRes->fetch_assoc()) {
        $roleRows[] = $row;
    }
    $roleRes->free();
}

$qualByReq = [];
$qualRes = $jobDescConn->query("SELECT request_id, qualification, type FROM qualifications ORDER BY request_id ASC, priority ASC, id ASC");
if ($qualRes) {
    while ($row = $qualRes->fetch_assoc()) {
        $rid = (string)($row['request_id'] ?? '');
        if ($rid === '') continue;
        if (!isset($qualByReq[$rid])) $qualByReq[$rid] = [];
        $qualByReq[$rid][] = [
            'text' => (string)($row['qualification'] ?? ''),
            'type' => (string)($row['type'] ?? 'Education')
        ];
    }
    $qualRes->free();
}

$reqByReq = [];
$reqRes = $jobDescConn->query("SELECT request_id, requirement, category, is_essential FROM job_requirements ORDER BY request_id ASC, id ASC");
if ($reqRes) {
    while ($row = $reqRes->fetch_assoc()) {
        $rid = (string)($row['request_id'] ?? '');
        if ($rid === '') continue;
        if (!isset($reqByReq[$rid])) $reqByReq[$rid] = [];
        $reqByReq[$rid][] = [
            'text' => (string)($row['requirement'] ?? ''),
            'category' => (string)($row['category'] ?? 'Skill'),
            'essential' => !empty($row['is_essential'])
        ];
    }
    $reqRes->free();
}

foreach ($roleRows as $row) {
    $rid = trim((string)($row['request_id'] ?? ''));
    if ($rid === '') continue;

    $desc = (string)($row['description'] ?? '');
    $quals = $qualByReq[$rid] ?? [];
    $reqs = $reqByReq[$rid] ?? [];

    $workflowStatus = (trim($desc) !== '' && !empty($quals) && !empty($reqs)) ? 'done' : 'pending';

    $jobRoles[] = [
        'request_id' => $rid,
        'name' => (string)($row['name'] ?? ''),
        'department_id' => (string)($row['department_id'] ?? ''),
        'department_name' => (string)($row['department_name'] ?? ''),
        'vacancies' => (int)($row['vacancies'] ?? 0),
        'description' => $desc,
        'qualifications' => $quals,
        'requirements' => $reqs,
        'workflow_status' => $workflowStatus
    ];
}

$departmentsData = [];
$departmentsResult = $jobDescConn->query("SELECT request_id, name FROM departments ORDER BY name");
if ($departmentsResult) {
    while ($dept = $departmentsResult->fetch_assoc()) {
        $departmentsData[] = $dept;
    }
}

$jobCriteriaMappings = [];
$jobCriteriaTable = $jobDescConn->query("SHOW TABLES LIKE 'job_criteria_mappings'");
if ($jobCriteriaTable && $jobCriteriaTable->num_rows > 0) {
    $hasApprovalStatusForMappings = false;
    $compTableForMappings = $jobDescConn->query("SHOW TABLES LIKE 'competency_standards'");
    if ($compTableForMappings && $compTableForMappings->num_rows > 0) {
        $colRes = $jobDescConn->query("SHOW COLUMNS FROM competency_standards LIKE 'approval_status'");
        if ($colRes && $colRes->num_rows > 0) {
            $hasApprovalStatusForMappings = true;
        }
    }

    $mapSql = "SELECT id, department_id, job_title_pattern, competency_id, priority
               FROM job_criteria_mappings
               WHERE is_active = 1
               ORDER BY priority DESC, id ASC";

    if ($hasApprovalStatusForMappings) {
        $mapSql = "SELECT m.id, m.department_id, m.job_title_pattern, m.competency_id, m.priority
                   FROM job_criteria_mappings m
                   INNER JOIN competency_standards cs ON cs.id = m.competency_id
                   WHERE m.is_active = 1
                     AND cs.status = 'active'
                     AND cs.approval_status = 'posted'
                   ORDER BY m.priority DESC, m.id ASC";
    }
    $mapResult = $jobDescConn->query($mapSql);
    if ($mapResult) {
        while ($row = $mapResult->fetch_assoc()) {
            $jobCriteriaMappings[] = [
                'id' => (int)$row['id'],
                'department_id' => $row['department_id'],
                'job_title_pattern' => $row['job_title_pattern'],
                'competency_id' => (int)$row['competency_id'],
                'priority' => (int)$row['priority']
            ];
        }
        $mapResult->free();
    }
}

$competencyStandardsData = [];
$compTable = $jobDescConn->query("SHOW TABLES LIKE 'competency_standards'");
if ($compTable && $compTable->num_rows > 0) {
    $hasQualificationsJson = false;
    $hasRequirementsJson = false;
    $hasApprovalStatus = false;
    $colResult = $jobDescConn->query("SHOW COLUMNS FROM competency_standards LIKE 'qualifications_json'");
    if ($colResult && $colResult->num_rows > 0) {
        $hasQualificationsJson = true;
    }
    $colResult = $jobDescConn->query("SHOW COLUMNS FROM competency_standards LIKE 'requirements_json'");
    if ($colResult && $colResult->num_rows > 0) {
        $hasRequirementsJson = true;
    }

    $colResult = $jobDescConn->query("SHOW COLUMNS FROM competency_standards LIKE 'approval_status'");
    if ($colResult && $colResult->num_rows > 0) {
        $hasApprovalStatus = true;
    }

    $extraSelect = '';
    if ($hasQualificationsJson) {
        $extraSelect .= ", cs.qualifications_json";
    }
    if ($hasRequirementsJson) {
        $extraSelect .= ", cs.requirements_json";
    }

    $whereApproval = $hasApprovalStatus ? " AND cs.approval_status = 'posted'" : "";

    $compSql = "SELECT cs.id,
                    cs.name,
                    cs.description,
                    cs.category,
                    cs.priority,
                    cs.role,
                    cs.hotel_context,
                    cs.restaurant_context,
                    cs.education,
                    cs.certifications,
                    cs.tech_skills,
                    cs.soft_skills,
                    cs.experience,
                    cs.physical,
                    cs.last_updated,
                    cl.level,
                    cl.criteria_text
                FROM competency_standards cs
                LEFT JOIN competency_level_criteria cl ON cl.competency_id = cs.id
                WHERE cs.status = 'active'" . $whereApproval . "
                ORDER BY cs.priority DESC, cs.id ASC, cl.level ASC";

    if ($extraSelect !== '') {
        $compSql = "SELECT cs.id,
                    cs.name,
                    cs.description,
                    cs.category,
                    cs.priority,
                    cs.role,
                    cs.hotel_context,
                    cs.restaurant_context,
                    cs.education,
                    cs.certifications,
                    cs.tech_skills,
                    cs.soft_skills,
                    cs.experience,
                    cs.physical" .
                    $extraSelect .
                    ", cs.last_updated,
                    cl.level,
                    cl.criteria_text
                FROM competency_standards cs
                LEFT JOIN competency_level_criteria cl ON cl.competency_id = cs.id
                WHERE cs.status = 'active'" . $whereApproval . "
                ORDER BY cs.priority DESC, cs.id ASC, cl.level ASC";
    }

    $compResult = $jobDescConn->query($compSql);
    if ($compResult) {
        $map = [];
        while ($row = $compResult->fetch_assoc()) {
            $id = (int)$row['id'];
            if (!isset($map[$id])) {
                $map[$id] = [
                    'id' => $id,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                    'category' => $row['category'],
                    'priority' => (int)$row['priority'],
                    'role' => $row['role'],
                    'hotel_context' => $row['hotel_context'],
                    'restaurant_context' => $row['restaurant_context'],
                    'education' => $row['education'],
                    'certifications' => $row['certifications'],
                    'tech_skills' => $row['tech_skills'],
                    'soft_skills' => $row['soft_skills'],
                    'experience' => $row['experience'],
                    'physical' => $row['physical'],
                    'qualifications' => [],
                    'requirements' => [],
                    'criteria' => [],
                    'last_updated' => $row['last_updated']
                ];

                if ($hasQualificationsJson && isset($row['qualifications_json']) && $row['qualifications_json'] !== null && $row['qualifications_json'] !== '') {
                    $decoded = json_decode($row['qualifications_json'], true);
                    if (is_array($decoded)) {
                        $map[$id]['qualifications'] = $decoded;
                    }
                }

                if ($hasRequirementsJson && isset($row['requirements_json']) && $row['requirements_json'] !== null && $row['requirements_json'] !== '') {
                    $decoded = json_decode($row['requirements_json'], true);
                    if (is_array($decoded)) {
                        $map[$id]['requirements'] = $decoded;
                    }
                }
            }
            if ($row['level'] !== null) {
                $lvl = (int)$row['level'];
                $map[$id]['criteria'][(string)$lvl] = $row['criteria_text'];
            }
        }
        $competencyStandardsData = array_values($map);
        $compResult->free();
    }
}

$jobDescConn->close();
?>

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --brand-primary: #1f3a8a;
            --brand-primary-hover: #1b3280;
            --brand-accent: #f5c542;

            --brand-surface: #ffffff;
            --brand-bg: #f3f4f6;
        }

        .btn-primary,
        .btn-success,
        .btn-secondary {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }
        .btn-primary:hover,
        .btn-success:hover,
        .btn-secondary:hover {
            background-color: var(--brand-primary-hover) !important;
            border-color: var(--brand-primary-hover) !important;
        }

        .btn-outline.btn-primary,
        .btn-outline.btn-success,
        .btn-outline.btn-secondary {
            background-color: transparent !important;
            color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }
        .btn-outline.btn-primary:hover,
        .btn-outline.btn-success:hover,
        .btn-outline.btn-secondary:hover {
            background-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }

        .brand-icon-tile {
            background-color: var(--brand-primary);
            padding: 0.75rem;
            border-radius: 0.75rem;
        }
        .brand-icon {
            color: var(--brand-accent) !important;
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
            background-color: var(--brand-primary) !important;
            color: white !important;
            border-color: var(--brand-primary) !important;
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
            color: var(--brand-primary);
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

        .brand-summary-card {
            background: linear-gradient(135deg, #071a3a 0%, #0b2d66 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .brand-summary-label {
            color: var(--brand-accent) !important;
            font-weight: 600;
        }
        .brand-summary-value {
            color: #ffffff !important;
        }
        .brand-summary-card .brand-icon-tile {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .brand-modal-header {
            background: linear-gradient(135deg, #071a3a 0%, #0b2d66 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-modal-header h3 {
            color: #ffffff;
        }

        .brand-modal-header p,
        .brand-modal-header span {
            color: rgba(255, 255, 255, 0.85);
        }

        .brand-modal-header .btn-ghost {
            color: #ffffff !important;
        }

        .brand-panel {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid var(--brand-accent);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .brand-title-icon {
            color: var(--brand-primary) !important;
        }

        .brand-badge-essential {
            background-color: rgba(245, 197, 66, 0.22);
            color: var(--brand-primary);
            border: 1px solid rgba(245, 197, 66, 0.55);
        }

        .brand-badge-preferred {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen">
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
                    <div class="brand-summary-card rounded-xl shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="brand-summary-label">Total Job Roles</p>
                                <h3 id="totalRoles" class="brand-summary-value text-2xl font-bold">0</h3>
                            </div>
                            <div class="brand-icon-tile">
                                <i data-lucide="briefcase" class="w-8 h-8 brand-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="brand-summary-card rounded-xl shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="brand-summary-label">Total Vacancies</p>
                                <h3 id="totalVacancies" class="brand-summary-value text-2xl font-bold">0</h3>
                            </div>
                            <div class="brand-icon-tile">
                                <i data-lucide="users" class="w-8 h-8 brand-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="brand-summary-card rounded-xl shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="brand-summary-label">Requirements</p>
                                <h3 id="totalRequirements" class="brand-summary-value text-2xl font-bold">0</h3>
                            </div>
                            <div class="brand-icon-tile">
                                <i data-lucide="list-checks" class="w-8 h-8 brand-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="brand-summary-card rounded-xl shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="brand-summary-label">Departments</p>
                                <h3 id="totalDepartments" class="brand-summary-value text-2xl font-bold">0</h3>
                            </div>
                            <div class="brand-icon-tile">
                                <i data-lucide="building" class="w-8 h-8 brand-icon"></i>
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
                                    <li id="departmentFilterList" class="p-0">
                                        <!-- Department filters will be loaded here dynamically -->
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Status Filter Dropdown - Right side -->
                        <div class="w-full lg:w-auto">
                            <div class="dropdown dropdown-bottom w-full lg:w-64">
                                <div tabindex="0" role="button" class="btn btn-outline w-full h-12 justify-between">
                                    <div class="flex items-center">
                                        <i data-lucide="filter" class="w-4 h-4 mr-2 text-gray-500"></i>
                                        <span id="statusFilterLabel" class="font-medium">All Status</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                                </div>
                                <ul tabindex="0" class="dropdown-content z-[1] menu p-0 shadow bg-base-100 rounded-box w-full mt-1 max-h-64 overflow-y-auto border border-gray-200">
                                    <li class="border-b border-gray-100">
                                        <a href="javascript:void(0)" 
                                           class="py-3 px-4 hover:bg-blue-50 active flex justify-between items-center"
                                           onclick="setStatusFilter('all', 'All Status')">
                                            <div class="flex items-center">
                                                <i data-lucide="grid" class="w-4 h-4 mr-2 text-gray-500"></i>
                                                <span>All Status</span>
                                            </div>
                                            <i data-lucide="check" class="w-4 h-4 status-check-icon text-blue-600" data-status="all"></i>
                                        </a>
                                    </li>
                                    <li class="border-b border-gray-100">
                                        <a href="javascript:void(0)" 
                                           class="py-3 px-4 hover:bg-blue-50 active flex justify-between items-center"
                                           onclick="setStatusFilter('pending', 'Pending')">

                                            <div class="flex items-center">
                                                <i data-lucide="clock" class="w-4 h-4 mr-2 text-gray-500"></i>
                                                <span>Pending</span>
                                            </div>
                                            <i data-lucide="check" class="w-4 h-4 status-check-icon text-blue-600 hidden" data-status="pending"></i>
                                        </a>
                                    </li>
                                    <li class="border-b border-gray-100">
                                        <a href="javascript:void(0)" 
                                           class="py-3 px-4 hover:bg-blue-50 active flex justify-between items-center"
                                           onclick="setStatusFilter('done', 'Done')">

                                            <div class="flex items-center">
                                                <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-gray-500"></i>
                                                <span>Done</span>
                                            </div>
                                            <i data-lucide="check" class="w-4 h-4 status-check-icon text-blue-600 hidden" data-status="done"></i>
                                        </a>
                                    </li>
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
    </div>

    <!-- View Details Modal -->
    <dialog id="viewModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl bg-white p-0 max-h-[90vh] overflow-hidden">
            <div class="brand-modal-header flex justify-between items-center p-6">
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
                        <i data-lucide="file-text" class="w-5 h-5 mr-2 brand-title-icon"></i>
                        Job Description
                    </h4>
                    <div class="brand-panel">
                        <p id="viewJobDescription" class="text-gray-700 whitespace-pre-line"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Qualifications Section -->
                    <div>
                        <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                            <i data-lucide="graduation-cap" class="w-5 h-5 mr-2 brand-title-icon"></i>
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
                            <i data-lucide="list-checks" class="w-5 h-5 mr-2 brand-title-icon"></i>
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
        <div class="modal-box w-11/12 max-w-5xl bg-white p-0 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="brand-modal-header flex justify-between items-center p-6">
                <div>
                    <h3 class="font-bold text-xl text-gray-800">Edit Job Role Details</h3>
                    <p class="text-gray-500 text-sm mt-1">Job Title, Department and Vacancies are read-only</p>
                </div>
                <button type="button" onclick="document.getElementById('editModal').close()" class="btn btn-sm btn-circle btn-ghost">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <input type="hidden" id="editRequestId" name="request_id" value="">
                <input type="hidden" id="editActualRequestId" name="actual_request_id" value="">
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
                    <div class="brand-panel">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                <i data-lucide="graduation-cap" class="w-5 h-5 mr-2 brand-title-icon"></i>
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
                    <div class="brand-panel">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                <i data-lucide="list-checks" class="w-5 h-5 mr-2 brand-title-icon"></i>
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
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-100 bg-white">
                <button type="button" id="cancelEditBtn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="saveEditBtn" class="btn btn-primary text-white">
                    <span id="saveBtnText">Save Changes</span>
                    <span id="saveBtnLoading" class="hidden">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </span>
                </button>
            </div>
        </div>
    </dialog>

    <template id="qualificationTemplate">
        <div class="draggable-item bg-white rounded-lg border border-gray-200 p-3">
            <div class="flex items-center gap-3">
                <div class="cursor-move text-gray-400">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </div>
                <input type="text" class="input input-bordered w-full qualification-input" placeholder="Enter qualification">
                <button type="button" class="btn btn-ghost btn-sm remove-btn text-gray-400 hover:text-red-500">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex items-center gap-3 mt-2">
                <select class="select select-bordered select-sm qualification-type">
                    <option value="Education">Education</option>
                    <option value="Certification">Certification</option>
                    <option value="Skill">Skill</option>
                    <option value="Experience">Experience</option>
                </select>
            </div>
        </div>
    </template>

    <template id="requirementTemplate">
        <div class="draggable-item bg-white rounded-lg border border-gray-200 p-3">
            <div class="flex items-center gap-3">
                <div class="cursor-move text-gray-400">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </div>
                <input type="text" class="input input-bordered w-full requirement-input" placeholder="Enter requirement">
                <button type="button" class="btn btn-ghost btn-sm remove-btn text-gray-400 hover:text-red-500">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex items-center gap-3 mt-2">
                <select class="select select-bordered select-sm requirement-category">
                    <option value="Skill">Skill</option>
                    <option value="Physical">Physical</option>
                    <option value="Other">Other</option>
                </select>
                <label class="label cursor-pointer gap-2 ml-auto">
                    <span class="label-text text-xs text-gray-600">Essential</span>
                    <input type="checkbox" class="checkbox checkbox-sm requirement-essential" checked>
                </label>
            </div>
        </div>
    </template>

    <script>
        window.__JOB_ROLES__ = <?php echo json_encode($jobRoles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__DEPARTMENTS__ = <?php echo json_encode($departmentsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__COMPETENCY_STANDARDS__ = <?php echo json_encode($competencyStandardsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__JOB_CRITERIA_MAPPINGS__ = <?php echo json_encode($jobCriteriaMappings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        let currentEditId = null;
        let currentViewId = null;
        let qualificationsSortable = null;
        let requirementsSortable = null;

        let allJobRoles = Array.isArray(window.__JOB_ROLES__) ? window.__JOB_ROLES__ : [];
        let filteredJobRoles = [...allJobRoles];
        let departments = Array.isArray(window.__DEPARTMENTS__) ? window.__DEPARTMENTS__ : [];
        const competencyStandards = Array.isArray(window.__COMPETENCY_STANDARDS__) ? window.__COMPETENCY_STANDARDS__ : [];
        const jobCriteriaMappings = Array.isArray(window.__JOB_CRITERIA_MAPPINGS__) ? window.__JOB_CRITERIA_MAPPINGS__ : [];

        let currentDepartmentFilter = 'all';
        let currentSearchTerm = '';
        let currentStatusFilter = 'all';

        const cardsContainer = document.getElementById('cardsContainer');
        const noDataPlaceholder = document.getElementById('noDataPlaceholder');
        const tableLoading = document.getElementById('tableLoading');
        const loadingState = document.getElementById('loadingState');
        const mainContent = document.getElementById('mainContent');
        const refreshBtn = document.getElementById('refreshBtn');

        const totalRolesElement = document.getElementById('totalRoles');
        const totalVacanciesElement = document.getElementById('totalVacancies');
        const totalDepartmentsElement = document.getElementById('totalDepartments');
        const totalRequirementsElement = document.getElementById('totalRequirements');

        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const departmentFilterLabel = document.getElementById('departmentFilterLabel');
        const departmentFilterList = document.getElementById('departmentFilterList');
        const statusFilterLabel = document.getElementById('statusFilterLabel');
        const activeFilterBadges = document.getElementById('activeFilterBadges');
        const showingCountElement = document.getElementById('showingCount');
        const totalCountElement = document.getElementById('totalCount');
        const filterStatusElement = document.getElementById('filterStatus');

        const viewModal = document.getElementById('viewModal');
        const editModal = document.getElementById('editModal');

        const viewJobTitle = document.getElementById('viewJobTitle');
        const viewDepartment = document.getElementById('viewDepartment');
        const viewJobId = document.getElementById('viewJobId');
        const viewVacancies = document.getElementById('viewVacancies');
        const viewJobDescription = document.getElementById('viewJobDescription');
        const viewQualificationsList = document.getElementById('viewQualificationsList');
        const viewRequirementsList = document.getElementById('viewRequirementsList');
        const qualificationsCount = document.getElementById('qualificationsCount');
        const requirementsCount = document.getElementById('requirementsCount');

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

        const qualificationTemplate = document.getElementById('qualificationTemplate');
        const requirementTemplate = document.getElementById('requirementTemplate');

        const editRequestId = document.getElementById('editRequestId');
        const editActualRequestId = document.getElementById('editActualRequestId');

        async function loadAllData() {
            allJobRoles = Array.isArray(window.__JOB_ROLES__) ? window.__JOB_ROLES__ : [];
            filteredJobRoles = [...allJobRoles];
            departments = Array.isArray(window.__DEPARTMENTS__) ? window.__DEPARTMENTS__ : [];
            updateDepartmentFilters();
        }

        function splitCsv(s) {
            return String(s || '')
                .split(',')
                .map(x => x.trim())
                .filter(Boolean);
        }

        function matchSqlLike(pattern, value) {
            const p = String(pattern || '');
            const v = String(value || '');
            if (!p) return false;
            const escaped = p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const reStr = '^' + escaped.replace(/%/g, '.*').replace(/_/g, '.') + '$';
            try {
                return new RegExp(reStr, 'i').test(v);
            } catch (e) {
                return v.toLowerCase().includes(p.toLowerCase().replace(/%/g, ''));
            }
        }

        function matchJobTitlePattern(pattern, jobTitle) {
            const p = String(pattern || '').trim();
            const t = String(jobTitle || '').trim();
            if (!p || !t) return false;
            if (p.includes('%') || p.includes('_')) return matchSqlLike(p, t);
            return p.toLowerCase() === t.toLowerCase();
        }

        function autoFillFromCompetency(competencyId) {
            const comp = competencyStandards.find(c => String(c.id) === String(competencyId));
            if (!comp) return;

            if (editJobDescription) {
                editJobDescription.value = String(comp.description || '').trim();
            }

            if (qualificationsContainer) {
                qualificationsContainer.innerHTML = '';
                const q = Array.isArray(comp.qualifications) ? comp.qualifications : [];

                if (q.length > 0) {
                    q.forEach(item => addQualificationItem(item.text || '', item.type || 'Education'));
                } else {
                    if (comp.education) addQualificationItem(comp.education, 'Education');
                    splitCsv(comp.certifications).forEach(t => addQualificationItem(t, 'Certification'));
                    splitCsv(comp.tech_skills).forEach(t => addQualificationItem(t, 'Skill'));
                    splitCsv(comp.soft_skills).forEach(t => addQualificationItem(t, 'Skill'));
                    if (comp.experience) addQualificationItem(comp.experience, 'Experience');
                }
            }

            if (requirementsContainer) {
                requirementsContainer.innerHTML = '';
                const r = Array.isArray(comp.requirements) ? comp.requirements : [];
                if (r.length > 0) {
                    r.forEach(item => addRequirementItem(item.text || '', item.category || 'Skill', Boolean(item.essential)));
                } else {
                    if (comp.physical) addRequirementItem(comp.physical, 'Physical', true);
                    if (comp.criteria) {
                        for (let lvl = 1; lvl <= 5; lvl++) {
                            const txt = comp.criteria[String(lvl)] || comp.criteria[lvl];
                            if (txt) addRequirementItem(txt, 'Skill', lvl >= 3);
                        }
                    }
                }
            }

            initializeSortable();
        }

        // Initialize application
        async function initApp() {
            try {
                await loadAllData();
                applyFilters();
                updateStatistics();
                updateActiveFilterBadges();
                if (loadingState) loadingState.classList.add('hidden');
                if (mainContent) mainContent.classList.remove('hidden');
                lucide.createIcons();
            } catch (error) {
                console.error('Error initializing app:', error);
                showError('Error loading data. Please refresh the page.');
            }
        }

        window.autoGenerate = async function(request_id) {
            try {
                const job = allJobRoles.find(j => String(j.request_id) === String(request_id));

                if (!job) {
                    showError('Job role not found');
                    return;
                }

                const matches = jobCriteriaMappings
                    .filter(m => !m.department_id || String(m.department_id) === String(job.department_id))
                    .filter(m => matchJobTitlePattern(m.job_title_pattern, job.name))
                    .sort((a, b) => (b.priority || 0) - (a.priority || 0));

                if (!matches || matches.length === 0) {
                    const brand = getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#1f3a8a';
                    const res = await Swal.fire({
                        icon: 'info',
                        title: 'No Criteria for this Role',
                        text: 'No competency criteria is applicable for this job role yet.',
                        showCancelButton: true,
                        confirmButtonText: 'Manual Assign',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: brand,
                        cancelButtonColor: '#6b7280',
                        reverseButtons: true
                    });

                    if (res.isConfirmed) {
                        await window.openEditModal(request_id);
                    }
                    return;
                }

                const chosen = matches[0];
                await window.openEditModal(request_id);
                autoFillFromCompetency(chosen.competency_id);
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Auto-Generate failed.',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#1f3a8a'
                });
            }
        };

        // Update department filter UI
        function updateDepartmentFilters() {
            departmentFilterList.innerHTML = '';

            // Add "All Departments" option
            {
                const li = document.createElement('li');
                li.className = 'border-b border-gray-100 last:border-b-0';
                li.innerHTML = `
                    <a href="javascript:void(0)" 
                       class="py-3 px-4 hover:bg-blue-50 flex justify-between items-center"
                       onclick="clearDepartmentFilter()">
                        <div class="flex items-center">
                            <i data-lucide="layers" class="w-4 h-4 mr-2 text-gray-500"></i>
                            <span>All Departments</span>
                        </div>
                        <i data-lucide="check" class="w-4 h-4 department-check-icon text-blue-600 hidden" data-department="all"></i>
                    </a>
                `;
                departmentFilterList.appendChild(li);
            }

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
            lucide.createIcons();
        }

        // Set department filter
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

        // Set status filter
        function setStatusFilter(statusKey, label) {
            currentStatusFilter = statusKey;
            if (statusFilterLabel) {
                statusFilterLabel.textContent = label || 'All Status';
            }

            document.querySelectorAll('.status-check-icon').forEach(icon => {
                icon.classList.add('hidden');
            });
            const active = document.querySelector(`.status-check-icon[data-status="${currentStatusFilter}"]`);
            if (active) {
                active.classList.remove('hidden');
            }

            updateActiveFilterBadges();
            applyFilters();
        }

        // Update filter button states
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

        // Update active filter badges
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

            // Add status filter badge
            if (currentStatusFilter !== 'all') {
                const statusBadge = document.createElement('div');
                statusBadge.className = 'badge badge-outline gap-2 px-3 py-2';

                const statusText = currentStatusFilter === 'pending' ? 'Pending' : 'Done';
                statusBadge.innerHTML = `
                    <i data-lucide="circle-check" class="w-3 h-3"></i>
                    <span>${statusText}</span>
                    <button onclick="clearStatusFilter()" class="btn btn-xs btn-circle btn-ghost p-0">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                activeFilterBadges.appendChild(statusBadge);
            }

            // Add clear all button if any filters are active
            if ((currentSearchTerm.trim() !== '' || currentDepartmentFilter !== 'all' || currentStatusFilter !== 'all')) {
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
                        String(job.department_id || '') === String(currentDepartmentFilter || '')
                    );
                }

                // Apply status filter
                if (currentStatusFilter !== 'all') {
                    results = results.filter(job => {
                        const s = String(job.workflow_status || '').toLowerCase();
                        return s === currentStatusFilter;
                    });
                }

                // Apply search filter
                if (currentSearchTerm.trim() !== '') {
                    const searchTerm = currentSearchTerm.toLowerCase().trim();
                    results = results.filter(job => {
                        // Search in job title
                        if (String(job.name || '').toLowerCase().includes(searchTerm)) return true;

                        // Search in job description
                        if (String(job.description || '').toLowerCase().includes(searchTerm)) return true;

                        // Search in qualifications
                        if (job.qualifications && job.qualifications.some(qual =>
                            String(qual.text || '').toLowerCase().includes(searchTerm)
                        )) return true;

                        // Search in requirements
                        if (job.requirements && job.requirements.some(req =>
                            String(req.text || '').toLowerCase().includes(searchTerm)
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
                const isDone = String(job.workflow_status || '').toLowerCase() === 'done';
                const rawDescription = String(job.description || '').trim();

                const qualificationsArr = Array.isArray(job.qualifications) ? job.qualifications : [];
                const requirementsArr = Array.isArray(job.requirements) ? job.requirements : [];

                const qualificationsText = qualificationsArr.map(q => q.text).filter(Boolean).slice(0, 2);
                const requirementsText = requirementsArr.map(r => r.text).filter(Boolean).slice(0, 2);

                const qualificationsHtml = (qualificationsText.map(text =>
                    `<div class="text-sm text-gray-700 mb-1 flex items-start">
                        <i data-lucide="check" class="w-3 h-3 text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span class="line-clamp-1">${text}</span>
                    </div>`
                ).join('')) || '<div class="text-xs text-gray-400 italic">No qualifications added</div>';

                const requirementsHtml = (requirementsText.map(text =>
                    `<div class="text-sm text-gray-700 mb-1 flex items-start">
                        <i data-lucide="circle" class="w-3 h-3 text-blue-500 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span class="line-clamp-1">${text}</span>
                    </div>`
                ).join('')) || '<div class="text-xs text-gray-400 italic">No requirements added</div>';

                const vacancyBadge = getVacancyBadge(job.vacancies);

                const shortDescription = rawDescription
                    ? (rawDescription.length > 120 ? rawDescription.substring(0, 120) + '...' : rawDescription)
                    : 'No description provided.';
                const descriptionClass = rawDescription ? 'text-gray-600' : 'text-gray-400 italic';

                return `
                    <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow duration-300 border border-gray-100 flex flex-col h-full">
                        <input type="hidden" class="jobActualRequestId" value="${job.request_id}">
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
                                        ${isDone ? '' : `
                                        <li>
                                            <button onclick="openEditModal('${job.request_id}')" class="text-green-600 hover:text-green-800">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                                Manual Assign
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="autoGenerate('${job.request_id}')" class="text-purple-600 hover:text-purple-800">
                                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                                                Auto-Generate
                                            </button>
                                        </li>
                                        `}
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

                        <div class="p-5 flex-1">
                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i data-lucide="file-text" class="w-4 h-4 text-gray-400 mr-2"></i>
                                    <h4 class="font-medium text-gray-700">Description</h4>
                                </div>
                                <p class="${descriptionClass} text-sm line-clamp-3">${shortDescription}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <i data-lucide="graduation-cap" class="w-4 h-4 text-gray-400 mr-2"></i>
                                            <h4 class="font-medium text-gray-700">Qualifications</h4>
                                        </div>
                                        <span class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                            ${qualificationsArr.length}
                                        </span>
                                    </div>
                                    <div class="space-y-1 min-h-[1.25rem]">
                                        ${qualificationsHtml}
                                        ${qualificationsArr.length > 2 ? `<div class="text-xs text-gray-500 mt-1">+${qualificationsArr.length - 2} more</div>` : ''}
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <i data-lucide="list-checks" class="w-4 h-4 text-gray-400 mr-2"></i>
                                            <h4 class="font-medium text-gray-700">Requirements</h4>
                                        </div>
                                        <span class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                            ${requirementsArr.length}
                                        </span>
                                    </div>
                                    <div class="space-y-1 min-h-[1.25rem]">
                                        ${requirementsHtml}
                                        ${requirementsArr.length > 2 ? `<div class="text-xs text-gray-500 mt-1">+${requirementsArr.length - 2} more</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                            ${isDone ? `
                                <div class="flex">
                                    <button onclick="openViewModal('${job.request_id}')" class="btn btn-sm btn-outline btn-primary w-full flex items-center justify-center gap-2 whitespace-nowrap">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        <span>View Only</span>
                                    </button>
                                </div>
                            ` : `
                                <div class="flex space-x-2">
                                    <button onclick="openEditModal('${job.request_id}')" class="btn btn-sm btn-outline btn-success flex-1 flex items-center justify-center gap-2 whitespace-nowrap">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                        <span>Manual Assign</span>
                                    </button>
                                    <button onclick="autoGenerate('${job.request_id}')" class="btn btn-sm btn-outline btn-secondary flex-1 flex items-center justify-center gap-2 whitespace-nowrap">
                                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                                        <span>Auto-Generate</span>
                                    </button>
                                </div>
                            `}
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

        // Update filter status message
        function updateFilterStatus() {
            let status = '';
            
            const statusLabel = currentStatusFilter === 'pending' ? 'Pending' : (currentStatusFilter === 'done' ? 'Done' : '');

            if (currentSearchTerm.trim() !== '' && currentDepartmentFilter !== 'all') {
                const dept = departments.find(d => d.request_id === currentDepartmentFilter)?.name || 'Selected Department';
                status = `Found ${filteredJobRoles.length} job roles for "${currentSearchTerm}" in ${dept}${statusLabel ? (' • ' + statusLabel) : ''}`;
            } else if (currentSearchTerm.trim() !== '') {
                status = `Found ${filteredJobRoles.length} job roles for "${currentSearchTerm}"${statusLabel ? (' • ' + statusLabel) : ''}`;
            } else if (currentDepartmentFilter !== 'all') {
                const dept = departments.find(d => d.request_id === currentDepartmentFilter)?.name || 'Selected Department';
                status = `Showing ${filteredJobRoles.length} job roles in ${dept}${statusLabel ? (' • ' + statusLabel) : ''}`;
            } else {
                status = `Showing ${filteredJobRoles.length} job roles${statusLabel ? (' • ' + statusLabel) : ''}`;
            }
            
            filterStatusElement.textContent = status;
        }

        // Clear search
        function clearSearch() {
            searchInput.value = '';
            currentSearchTerm = '';
            clearSearchBtn.classList.add('hidden');
            updateActiveFilterBadges();
            applyFilters();
        }

        // Clear department filter
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

        // Clear status filter
        function clearStatusFilter() {
            currentStatusFilter = 'all';
            if (statusFilterLabel) {
                statusFilterLabel.textContent = 'All Status';
            }
            document.querySelectorAll('.status-check-icon').forEach(icon => {
                icon.classList.add('hidden');
            });
            const allStatusIcon = document.querySelector('.status-check-icon[data-status="all"]');
            if (allStatusIcon) {
                allStatusIcon.classList.remove('hidden');
            }
            updateActiveFilterBadges();
            applyFilters();
        }

        // Clear all filters
        function clearAllFilters() {
            clearSearch();
            clearDepartmentFilter();
            clearStatusFilter();
        }

        // Open view modal
        window.openViewModal = async function(request_id) {
            try {
                const job = allJobRoles.find(j => j.request_id == request_id);
                
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
                            '<span class="px-2 py-1 text-xs rounded-full brand-badge-essential ml-2">Essential</span>' : 
                            '<span class="px-2 py-1 text-xs rounded-full brand-badge-preferred ml-2">Preferred</span>';
                        
                        return `
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <div class="flex items-start">
                                    <div class="mr-3 mt-1">
                                        ${req.essential ? 
                                            '<i data-lucide="check-circle" class="w-5 h-5 brand-title-icon"></i>' : 
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
                const job = allJobRoles.find(j => j.request_id == request_id);
                
                if (job && !job.error) {
                    currentEditId = job.request_id;

                    if (editRequestId) {
                        editRequestId.value = String(job.request_id || '');
                    }

                    if (editActualRequestId) {
                        editActualRequestId.value = String(job.request_id || '');
                    }
                    
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
            if (essentialCheckbox) essentialCheckbox.checked = essential;
            
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

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'updateJobRole';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'request_id';
                idInput.value = (editActualRequestId && editActualRequestId.value)
                    ? editActualRequestId.value
                    : ((editRequestId && editRequestId.value) ? editRequestId.value : currentEditId);
                form.appendChild(idInput);

                const descInput = document.createElement('input');
                descInput.type = 'hidden';
                descInput.name = 'description';
                descInput.value = updateData.description;
                form.appendChild(descInput);

                const qualInput = document.createElement('input');
                qualInput.type = 'hidden';
                qualInput.name = 'qualifications';
                qualInput.value = JSON.stringify(updateData.qualifications);
                form.appendChild(qualInput);

                const reqInput = document.createElement('input');
                reqInput.type = 'hidden';
                reqInput.name = 'requirements';
                reqInput.value = JSON.stringify(updateData.requirements);
                form.appendChild(reqInput);

                document.body.appendChild(form);
                form.submit();
                
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
        function updateStatistics() {
            try {
                totalRolesElement.textContent = allJobRoles.length || 0;
                totalVacanciesElement.textContent = allJobRoles.reduce((sum, j) => sum + (parseInt(j.vacancies, 10) || 0), 0);
                totalDepartmentsElement.textContent = departments.length || 0;
                totalRequirementsElement.textContent = allJobRoles.reduce((sum, j) => sum + (Array.isArray(j.requirements) ? j.requirements.length : 0), 0);
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }

        // Helper functions
        function getDepartmentColor(dept) {
            const colors = {
                'Kitchen / Culinary': 'bg-orange-100 text-orange-800',
                'Food & Beverage (F&B)': 'bg-green-100 text-green-800',
                'Housekeeping': 'bg-purple-100 text-purple-800',
                'Front Office / Reception': 'bg-blue-100 text-blue-800',
                'Sales & Marketing': 'bg-pink-100 text-pink-800',
                'Human Resources (HR)': 'bg-indigo-100 text-indigo-800',
                'Finance / Accounting': 'bg-yellow-100 text-yellow-800',
                'Engineering / Maintenance': 'bg-gray-100 text-gray-800',
                'Security': 'bg-red-100 text-red-800'
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
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', () => {
                editModal.close();
                resetEditForm();
            });
        }

        if (addQualificationBtn) {
            addQualificationBtn.addEventListener('click', () => {
                addQualificationItem();
            });
        }

        if (addRequirementBtn) {
            addRequirementBtn.addEventListener('click', () => {
                addRequirementItem();
            });
        }

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
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', clearSearch);
        }

        // Refresh button
        if (refreshBtn) refreshBtn.addEventListener('click', async () => {
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