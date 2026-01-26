<?php
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json; charset=utf-8');

    $remoteUrl = 'https://hr4.soliera-hotel-restaurant.com/CHM/API/save_employee.php';
    $responseBody = null;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (($responseBody === false || $httpCode === 0) && function_exists('curl_setopt')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $responseBody = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
            ],
        ]);
        $responseBody = @file_get_contents($remoteUrl, false, $context);
        $httpCode = 200;
    }

    if ($responseBody === false || $responseBody === null) {
        http_response_code(502);
        echo json_encode(['error' => 'Failed to fetch remote data']);
        exit;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        http_response_code(502);
        echo json_encode(['error' => 'Remote API returned an error', 'status' => $httpCode]);
        exit;
    }

    json_decode($responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(502);
        echo json_encode(['error' => 'Remote API returned invalid JSON']);
        exit;
    }

    echo $responseBody;
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'save_job_details') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $requestId = isset($_POST['request_id']) ? trim((string) $_POST['request_id']) : '';
    $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';

    $qNames = isset($_POST['qualifications_name']) && is_array($_POST['qualifications_name']) ? $_POST['qualifications_name'] : [];
    $qDescriptions = isset($_POST['qualifications_description']) && is_array($_POST['qualifications_description']) ? $_POST['qualifications_description'] : [];

    $rNames = isset($_POST['requirements_name']) && is_array($_POST['requirements_name']) ? $_POST['requirements_name'] : [];
    $rDescriptions = isset($_POST['requirements_description']) && is_array($_POST['requirements_description']) ? $_POST['requirements_description'] : [];

    $qualifications = [];
    $requirements = [];

    $qCount = max(count($qNames), count($qDescriptions));
    for ($i = 0; $i < $qCount; $i++) {
        $name = isset($qNames[$i]) ? trim((string) $qNames[$i]) : '';
        $desc = isset($qDescriptions[$i]) ? trim((string) $qDescriptions[$i]) : '';
        if ($name !== '' || $desc !== '') {
            $qualifications[] = ['name' => $name, 'description' => $desc];
        }
    }

    $rCount = max(count($rNames), count($rDescriptions));
    for ($i = 0; $i < $rCount; $i++) {
        $name = isset($rNames[$i]) ? trim((string) $rNames[$i]) : '';
        $desc = isset($rDescriptions[$i]) ? trim((string) $rDescriptions[$i]) : '';
        if ($name !== '' || $desc !== '') {
            $requirements[] = ['name' => $name, 'description' => $desc];
        }
    }

    $_SESSION['job_details_last_payload'] = [
        'request_id' => $requestId,
        'description' => $description,
        'qualifications' => $qualifications,
        'requirements' => $requirements,
    ];

    if ($requestId !== '') {
        require_once __DIR__ . '/../job_desc/db_job_desc.php';

        $toText = function ($name, $desc) {
            $name = trim((string) $name);
            $desc = trim((string) $desc);
            if ($name !== '' && $desc !== '') {
                return $name . ': ' . $desc;
            }
            if ($name !== '') {
                return $name;
            }
            return $desc;
        };

        $jobTitle = isset($_POST['job_title']) ? trim((string) $_POST['job_title']) : '';
        $departmentId = isset($_POST['department_id']) ? trim((string) $_POST['department_id']) : '';
        if ($departmentId === '' || stripos($departmentId, 'DPT-') !== 0) {
            $departmentId = null;
        }
        $vacanciesCount = isset($_POST['vacancies']) && is_numeric($_POST['vacancies']) ? (int) $_POST['vacancies'] : 1;

        $conn = null;
        try {
            $conn = job_desc_mysqli();
            $conn->begin_transaction();

            $stmtRole = $conn->prepare(
                "INSERT INTO job_roles (request_id, name, department_id, vacancies, description, source_recruitment_request_id)\n" .
                "VALUES (?, ?, ?, ?, ?, ?)\n" .
                "ON DUPLICATE KEY UPDATE\n" .
                "name = VALUES(name),\n" .
                "department_id = VALUES(department_id),\n" .
                "vacancies = VALUES(vacancies),\n" .
                "description = VALUES(description),\n" .
                "source_recruitment_request_id = VALUES(source_recruitment_request_id)"
            );
            $sourceRecruitId = $requestId;
            $stmtRole->bind_param('sssiss', $requestId, $jobTitle, $departmentId, $vacanciesCount, $description, $sourceRecruitId);
            $stmtRole->execute();
            $stmtRole->close();

            $stmtDelQ = $conn->prepare('DELETE FROM qualifications WHERE request_id = ?');
            $stmtDelQ->bind_param('s', $requestId);
            $stmtDelQ->execute();
            $stmtDelQ->close();

            $stmtDelR = $conn->prepare('DELETE FROM job_requirements WHERE request_id = ?');
            $stmtDelR->bind_param('s', $requestId);
            $stmtDelR->execute();
            $stmtDelR->close();

            $stmtInsQ = $conn->prepare('INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)');
            $qType = 'Education';
            $priority = 1;
            foreach ($qualifications as $q) {
                $text = $toText($q['name'] ?? '', $q['description'] ?? '');
                $text = trim((string) $text);
                if ($text === '') {
                    continue;
                }
                $stmtInsQ->bind_param('sssi', $requestId, $text, $qType, $priority);
                $stmtInsQ->execute();
                $priority++;
            }
            $stmtInsQ->close();

            $stmtInsR = $conn->prepare('INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)');
            $category = 'Skill';
            $isEssential = 1;
            foreach ($requirements as $r) {
                $text = $toText($r['name'] ?? '', $r['description'] ?? '');
                $text = trim((string) $text);
                if ($text === '') {
                    continue;
                }
                $stmtInsR->bind_param('sssi', $requestId, $text, $category, $isEssential);
                $stmtInsR->execute();
            }
            $stmtInsR->close();

            $conn->commit();
            $conn->close();
        } catch (Throwable $e) {
            if ($conn instanceof mysqli) {
                try {
                    $conn->rollback();
                } catch (Throwable $t) {
                }
                try {
                    $conn->close();
                } catch (Throwable $t) {
                }
            }
            $_SESSION['job_details_db_error'] = $e->getMessage();
        }
    }

    $redirectPath = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectPath . '?saved=1');
    exit;
}

require('../../partials/header.php');

function format_type_label($type)
{
    $t = trim((string) $type);
    if ($t === '') {
        return '';
    }
    $t = str_replace('_', ' ', $t);
    $t = preg_replace('/\s+/', ' ', $t);
    $t = strtolower($t);
    $t = ucwords($t);
    return $t;
}

function normalize_filter_value($value)
{
    $v = trim((string) $value);
    $v = strtolower($v);
    return $v;
}

function get_request_id_from_vacancy($v)
{
    if (is_array($v)) {
        if (isset($v['request_id']) && trim((string) $v['request_id']) !== '') {
            return trim((string) $v['request_id']);
        }
        if (isset($v['id']) && trim((string) $v['id']) !== '') {
            return trim((string) $v['id']);
        }
    }
    return '';
}

function parse_text_to_namedesc($text)
{
    $t = trim((string) $text);
    if ($t === '') {
        return ['name' => '', 'description' => ''];
    }
    $parts = explode(':', $t, 2);
    if (count($parts) === 2) {
        $name = trim((string) $parts[0]);
        $desc = trim((string) $parts[1]);
        if ($name !== '' && $desc !== '') {
            return ['name' => $name, 'description' => $desc];
        }
    }
    return ['name' => $t, 'description' => ''];
}

function fetch_remote_vacancies()
{
    $remoteUrl = 'https://hr4.soliera-hotel-restaurant.com/CHM/API/save_employee.php';
    $responseBody = null;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (($responseBody === false || $httpCode === 0) && function_exists('curl_setopt')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $responseBody = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
            ],
        ]);
        $responseBody = @file_get_contents($remoteUrl, false, $context);
        $httpCode = 200;
    }

    if ($responseBody === false || $responseBody === null) {
        return [[], 'Failed to fetch remote data'];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [[], 'Remote API returned an error'];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return [[], 'Remote API returned invalid JSON'];
    }

    return [$decoded, null];
}

[$vacanciesById, $fetchError] = fetch_remote_vacancies();
$allVacancies = array_values($vacanciesById);

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$departmentFilter = isset($_GET['department']) ? trim((string) $_GET['department']) : '';
$typeFilter = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$departmentOptions = [];
$typeOptions = [];
$statusOptions = [];

foreach ($allVacancies as $v) {
    $dept = isset($v['department']) && $v['department'] !== null ? (string) $v['department'] : (isset($v['department_name']) ? (string) $v['department_name'] : '');
    $dept = trim($dept);
    if ($dept !== '') {
        $departmentOptions[$dept] = $dept;
    }

    $typeLabel = isset($v['type']) ? format_type_label($v['type']) : '';
    if ($typeLabel !== '') {
        $typeOptions[$typeLabel] = $typeLabel;
    }

    $status = isset($v['status']) && $v['status'] !== null ? trim((string) $v['status']) : '';
    if ($status !== '') {
        $statusOptions[$status] = $status;
    }
}

ksort($departmentOptions);
ksort($typeOptions);
ksort($statusOptions);

$filtered = [];
$searchNorm = normalize_filter_value($search);
$deptNorm = normalize_filter_value($departmentFilter);
$typeNorm = normalize_filter_value($typeFilter);
$statusNorm = normalize_filter_value($statusFilter);

foreach ($allVacancies as $v) {
    $title = isset($v['title']) ? (string) $v['title'] : '';
    $dept = isset($v['department']) && $v['department'] !== null ? (string) $v['department'] : (isset($v['department_name']) ? (string) $v['department_name'] : '');
    $subDept = isset($v['sub_department']) && $v['sub_department'] !== null ? (string) $v['sub_department'] : '';
    $typeLabel = isset($v['type']) ? format_type_label($v['type']) : '';
    $status = isset($v['status']) ? (string) $v['status'] : '';

    $titleN = normalize_filter_value($title);
    $deptN = normalize_filter_value($dept);
    $subDeptN = normalize_filter_value($subDept);
    $typeN = normalize_filter_value($typeLabel);
    $statusN = normalize_filter_value($status);

    $matchesSearch = $searchNorm === '' ||
        strpos($titleN, $searchNorm) !== false ||
        strpos($deptN, $searchNorm) !== false ||
        strpos($subDeptN, $searchNorm) !== false ||
        strpos($typeN, $searchNorm) !== false ||
        strpos($statusN, $searchNorm) !== false;

    $matchesDept = $deptNorm === '' || $deptN === $deptNorm;
    $matchesType = $typeNorm === '' || $typeN === $typeNorm;
    $matchesStatus = $statusNorm === '' || $statusN === $statusNorm;

    if ($matchesSearch && $matchesDept && $matchesType && $matchesStatus) {
        $filtered[] = $v;
    }
}

$pageSize = 10;
$total = count($filtered);
$pageCount = max(1, (int) ceil($total / $pageSize));
if ($page > $pageCount) {
    $page = $pageCount;
}
$startIndex = ($page - 1) * $pageSize;
$pageItems = array_slice($filtered, $startIndex, $pageSize);

$jobDetailsByRequestId = [];
$pageRequestIds = [];
foreach ($pageItems as $v) {
    $rid = get_request_id_from_vacancy($v);
    if ($rid !== '') {
        $pageRequestIds[$rid] = $rid;
    }
}

if (!empty($pageRequestIds)) {
    require_once __DIR__ . '/../job_desc/db_job_desc.php';
    $conn = null;
    try {
        $conn = job_desc_mysqli();
        $ids = array_values($pageRequestIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('s', count($ids));

        $bindParams = function ($stmt, $types, $params) {
            $bind = [];
            $bind[] = $types;
            foreach ($params as $k => $v) {
                $bind[] = &$params[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind);
        };

        $stmtRole = $conn->prepare("SELECT request_id, description FROM job_roles WHERE request_id IN ($placeholders)");
        $bindParams($stmtRole, $types, $ids);
        $stmtRole->execute();
        $resRole = $stmtRole->get_result();
        while ($row = $resRole->fetch_assoc()) {
            $rid = (string) ($row['request_id'] ?? '');
            if ($rid === '') {
                continue;
            }
            $jobDetailsByRequestId[$rid] = [
                'description' => (string) ($row['description'] ?? ''),
                'qualifications' => [],
                'requirements' => [],
            ];
        }
        $stmtRole->close();

        $stmtQ = $conn->prepare("SELECT request_id, qualification FROM qualifications WHERE request_id IN ($placeholders) ORDER BY priority ASC, id ASC");
        $bindParams($stmtQ, $types, $ids);
        $stmtQ->execute();
        $resQ = $stmtQ->get_result();
        while ($row = $resQ->fetch_assoc()) {
            $rid = (string) ($row['request_id'] ?? '');
            $text = (string) ($row['qualification'] ?? '');
            if ($rid === '' || $text === '') {
                continue;
            }
            if (!isset($jobDetailsByRequestId[$rid])) {
                $jobDetailsByRequestId[$rid] = ['description' => '', 'qualifications' => [], 'requirements' => []];
            }
            $jobDetailsByRequestId[$rid]['qualifications'][] = parse_text_to_namedesc($text);
        }
        $stmtQ->close();

        $stmtR = $conn->prepare("SELECT request_id, requirement FROM job_requirements WHERE request_id IN ($placeholders) ORDER BY id ASC");
        $bindParams($stmtR, $types, $ids);
        $stmtR->execute();
        $resR = $stmtR->get_result();
        while ($row = $resR->fetch_assoc()) {
            $rid = (string) ($row['request_id'] ?? '');
            $text = (string) ($row['requirement'] ?? '');
            if ($rid === '' || $text === '') {
                continue;
            }
            if (!isset($jobDetailsByRequestId[$rid])) {
                $jobDetailsByRequestId[$rid] = ['description' => '', 'qualifications' => [], 'requirements' => []];
            }
            $jobDetailsByRequestId[$rid]['requirements'][] = parse_text_to_namedesc($text);
        }
        $stmtR->close();

        $conn->close();
    } catch (Throwable $e) {
        if ($conn instanceof mysqli) {
            try {
                $conn->close();
            } catch (Throwable $t) {
            }
        }
    }
}

foreach ($pageItems as $idx => $v) {
    $rid = get_request_id_from_vacancy($v);
    if ($rid !== '' && isset($jobDetailsByRequestId[$rid])) {
        $v['request_id'] = $rid;
        $v['description'] = $jobDetailsByRequestId[$rid]['description'];
        $v['qualifications'] = $jobDetailsByRequestId[$rid]['qualifications'];
        $v['requirements'] = $jobDetailsByRequestId[$rid]['requirements'];
        $pageItems[$idx] = $v;
    } elseif ($rid !== '') {
        $v['request_id'] = $rid;
        $pageItems[$idx] = $v;
    }
}

$startCount = $total === 0 ? 0 : ($startIndex + 1);
$endCount = $total === 0 ? 0 : min($startIndex + $pageSize, $total);

function build_query($overrides)
{
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return http_build_query($params);
}
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
                    <form method="GET" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <!-- Search Bar -->
                        <div class="flex-1 w-full md:w-auto">
                            <div class="relative">
                                <input
                                    type="text"
                                    name="q"
                                    value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>"
                                    placeholder="Search vacancies..."
                                    class="input input-bordered w-full pl-10"
                                    id="searchInput">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <select class="select select-bordered select-sm" id="departmentFilter" name="department" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departmentOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES); ?>" <?php echo $opt === $departmentFilter ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select class="select select-bordered select-sm" id="typeFilter" name="type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <?php foreach ($typeOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES); ?>" <?php echo $opt === $typeFilter ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select class="select select-bordered select-sm" id="statusFilter" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <?php foreach ($statusOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES); ?>" <?php echo $opt === $statusFilter ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
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
                                <?php foreach ($pageItems as $vacancy): ?>
                                    <?php
                                    $id = isset($vacancy['id']) ? (string) $vacancy['id'] : '';
                                    $title = isset($vacancy['title']) ? (string) $vacancy['title'] : '';
                                    $department = isset($vacancy['department']) && $vacancy['department'] !== null ? (string) $vacancy['department'] : (isset($vacancy['department_name']) ? (string) $vacancy['department_name'] : '');
                                    $subDepartment = isset($vacancy['sub_department']) && $vacancy['sub_department'] !== null ? (string) $vacancy['sub_department'] : '';
                                    $typeLabel = isset($vacancy['type']) ? format_type_label($vacancy['type']) : '';
                                    $status = isset($vacancy['status']) ? (string) $vacancy['status'] : '';
                                    $vacanciesCount = isset($vacancy['vacancies']) ? (string) $vacancy['vacancies'] : '';
                                    $salaryRange = isset($vacancy['salary_range']) ? (string) $vacancy['salary_range'] : '';
                                    $daysRemaining = $vacancy['days_remaining'] ?? null;
                                    $isExpired = $vacancy['is_expired'] ?? null;
                                    $createdAt = isset($vacancy['created_at']) ? (string) $vacancy['created_at'] : '';

                                    $daysText = '-';
                                    if ($isExpired === true) {
                                        $daysText = 'Expired';
                                    } elseif (is_numeric($daysRemaining)) {
                                        $daysText = ((int) $daysRemaining) . ' days';
                                    } elseif ($daysRemaining !== null && $daysRemaining !== '') {
                                        $daysText = (string) $daysRemaining;
                                    }

                                    $daysClass = 'text-success';
                                    if ($isExpired === true) {
                                        $daysClass = 'text-error';
                                    } elseif (is_numeric($daysRemaining) && (int) $daysRemaining <= 5) {
                                        $daysClass = 'text-warning';
                                    }

                                    $typeBadge = 'badge-ghost';
                                    $typeNorm = normalize_filter_value($typeLabel);
                                    if (strpos($typeNorm, 'contract') !== false) {
                                        $typeBadge = 'badge-info';
                                    } elseif (strpos($typeNorm, 'full') !== false) {
                                        $typeBadge = 'badge-success';
                                    } elseif (strpos($typeNorm, 'part') !== false) {
                                        $typeBadge = 'badge-secondary';
                                    }

                                    $statusBadge = 'badge-ghost';
                                    $statusNorm = normalize_filter_value($status);
                                    if ($statusNorm === 'open') {
                                        $statusBadge = 'badge-success';
                                    } elseif ($statusNorm === 'closed') {
                                        $statusBadge = 'badge-error';
                                    } elseif ($statusNorm === 'draft') {
                                        $statusBadge = 'badge-warning';
                                    }

                                    $recordJson = htmlspecialchars(json_encode($vacancy, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                    ?>
                                    <tr class="hover:bg-base-200 border-b border-base-200">
                                        <td class="py-4 px-6">
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($title); ?></div>
                                                <?php if (isset($vacancy['description']) && trim((string) $vacancy['description']) !== ''): ?>
                                                    <div class="text-sm text-gray-600 mt-1">
                                                        <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                                                        <?php echo htmlspecialchars((string) $vacancy['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-sm text-gray-500 mt-1">
                                                    <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                                    Created: <?php echo $createdAt !== '' ? htmlspecialchars(date('m/d/Y', strtotime($createdAt))) : '-'; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-4 px-6">
                                            <div class="font-medium text-gray-700"><?php echo htmlspecialchars($department !== '' ? $department : '-'); ?></div>
                                            <?php if ($subDepartment !== ''): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($subDepartment); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-4 px-6">
                                            <span class="badge <?php echo $typeBadge; ?>"><?php echo htmlspecialchars($typeLabel !== '' ? $typeLabel : '-'); ?></span>
                                        </td>

                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="users" class="w-5 h-5 text-gray-400"></i>
                                                <span class="font-semibold"><?php echo htmlspecialchars($vacanciesCount !== '' ? $vacanciesCount : '-'); ?></span>
                                            </div>
                                        </td>

                                        <td class="py-4 px-6">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($salaryRange !== '' ? $salaryRange : '-'); ?></div>
                                        </td>

                                        <td class="py-4 px-6">
                                            <span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($status !== '' ? $status : '-'); ?></span>
                                        </td>

                                        <td class="py-4 px-6">
                                            <span class="font-medium <?php echo $daysClass; ?>"><?php echo htmlspecialchars($daysText); ?></span>
                                        </td>

                                        <td class="py-4 px-6">
                                            <button class="btn btn-sm btn-ghost btn-square" type="button" data-record="<?php echo $recordJson; ?>" onclick="openDetailModal(this)" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-ghost btn-square" type="button" data-record="<?php echo $recordJson; ?>" onclick="openJobDetailsModal(this)" title="Set Job Details">
                                                <i data-lucide="settings" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($fetchError !== null): ?>
                        <div class="p-8 text-center">
                            <p class="mt-4 text-gray-600"><?php echo htmlspecialchars($fetchError); ?></p>
                        </div>
                    <?php elseif ($total === 0): ?>
                        <div class="p-8 text-center">
                            <i data-lucide="briefcase" class="w-16 h-16 text-gray-300 mx-auto"></i>
                            <p class="mt-4 text-gray-600">No vacancies found</p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <div class="border-t border-gray-200 p-4">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-600">
                                Showing <span id="startCount"><?php echo (int) $startCount; ?></span> to <span id="endCount"><?php echo (int) $endCount; ?></span> of
                                <span id="totalCount"><?php echo (int) $total; ?></span> entries
                            </div>
                            <div class="join">
                                <?php if ($page <= 1): ?>
                                    <button class="join-item btn btn-sm" disabled>
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </button>
                                <?php else: ?>
                                    <a class="join-item btn btn-sm" href="?<?php echo htmlspecialchars(build_query(['page' => $page - 1])); ?>">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>

                                <button class="join-item btn btn-sm btn-active" id="currentPageBtn"><?php echo (int) $page; ?></button>

                                <?php if ($page >= $pageCount): ?>
                                    <button class="join-item btn btn-sm" disabled>
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </button>
                                <?php else: ?>
                                    <a class="join-item btn btn-sm" href="?<?php echo htmlspecialchars(build_query(['page' => $page + 1])); ?>">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
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
                    <div class="modal-box max-w-4xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg" id="jobDetailsTitle">Job Details</h3>
                        <form method="POST" action="?action=save_job_details" id="jobDetailsForm">
                            <input type="hidden" name="request_id" id="jobDetailsRequestId" value="">
                            <input type="hidden" name="job_title" id="jobDetailsHiddenJobTitle" value="">
                            <input type="hidden" name="department_id" id="jobDetailsHiddenDepartmentId" value="">
                            <input type="hidden" name="vacancies" id="jobDetailsHiddenVacancies" value="">
                            <div class="mt-4 space-y-5">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-500">Job Title</div>
                                        <div class="font-medium" id="jobDetailsJobTitle">-</div>
                                    </div>

                                    <div>
                                        <div class="text-sm text-gray-500">Department</div>
                                        <div class="font-medium" id="jobDetailsDepartment">-</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-control w-full">
                                        <div class="label">
                                            <span class="label-text">Description</span>
                                        </div>
                                        <input type="text" id="jobDescriptionInput" name="description" class="input input-bordered w-full" placeholder="Enter job description" />
                                    </label>
                                </div>

                                <div class="border border-base-300 rounded-lg p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-semibold">Qualifications</div>
                                        <button type="button" class="btn btn-sm" id="addQualificationBtn">Add</button>
                                    </div>
                                    <div class="mt-4 space-y-3" id="qualificationsContainer"></div>
                                </div>

                                <div class="border border-base-300 rounded-lg p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-semibold">Requirements</div>
                                        <button type="button" class="btn btn-sm" id="addRequirementBtn">Add</button>
                                    </div>
                                    <div class="mt-4 space-y-3" id="requirementsContainer"></div>
                                </div>
                            </div>

                            <div class="modal-action">
                                <button class="btn btn-primary" id="saveJobDetailsBtn" type="submit">Save</button>
                                <button class="btn" type="button" onclick="document.getElementById('jobDetailsModal').close()">Close</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>

                <template id="itemRowTemplate">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start" data-row>
                        <input type="text" class="input input-bordered md:col-span-4" placeholder="Name" data-name>
                        <input type="text" class="input input-bordered md:col-span-7" placeholder="Description" data-description>
                        <button type="button" class="btn btn-ghost btn-square md:col-span-1" data-remove>
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </template>
            </main>

            <!-- JavaScript for handling vacancies data -->
            <script>
                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/\"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function safeText(value) {
                    if (value === null || value === undefined || value === '') return '-';
                    return String(value);
                }

                function getDepartment(v) {
                    return v.department || v.department_name || '';
                }

                function getSubDepartment(v) {
                    return v.sub_department || '';
                }

                function formatTypeLabel(type) {
                    const t = safeText(type);
                    if (t === '-') return '';
                    return t
                        .replace(/_/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .toLowerCase()
                        .replace(/\b\w/g, c => c.toUpperCase());
                }

                window.openDetailModal = function(btn) {
                    const detailModal = document.getElementById('detailModal');
                    const modalContent = document.getElementById('modalContent');
                    if (!detailModal || !modalContent || !btn) return;

                    const raw = btn.getAttribute('data-record');
                    if (!raw) return;
                    const v = JSON.parse(raw);

                    const department = getDepartment(v);
                    const subDepartment = getSubDepartment(v);
                    const typeLabel = formatTypeLabel(v.type);

                    modalContent.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Job Title</div>
                                    <div class="text-lg font-semibold">${escapeHtml(safeText(v.title))}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Department</div>
                                    <div class="font-medium">${escapeHtml(safeText(department))}</div>
                                    ${subDepartment ? `<div class="text-sm text-gray-500">${escapeHtml(safeText(subDepartment))}</div>` : ''}
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Type</div>
                                    <div class="font-medium">${escapeHtml(typeLabel || '-')}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Status</div>
                                    <div class="font-medium">${escapeHtml(safeText(v.status))}</div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Vacancies</div>
                                    <div class="text-2xl font-bold">${escapeHtml(safeText(v.vacancies))}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Salary Range</div>
                                    <div class="text-lg font-semibold">${escapeHtml(safeText(v.salary_range))}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Created At</div>
                                    <div>${escapeHtml(safeText(v.created_at_formatted || v.created_at))}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Job End Date</div>
                                    <div>${escapeHtml(safeText(v.job_end_date))}</div>
                                </div>
                            </div>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    detailModal.showModal();
                }

                function addItemRow(containerId, nameField, descField) {
                    const container = document.getElementById(containerId);
                    const tpl = document.getElementById('itemRowTemplate');
                    if (!container || !tpl) return;
                    const fragment = tpl.content.cloneNode(true);
                    const row = fragment.querySelector('[data-row]');
                    const nameInput = fragment.querySelector('[data-name]');
                    const descInput = fragment.querySelector('[data-description]');
                    const removeBtn = fragment.querySelector('[data-remove]');

                    if (nameInput) nameInput.setAttribute('name', nameField);
                    if (descInput) descInput.setAttribute('name', descField);
                    if (removeBtn) {
                        removeBtn.addEventListener('click', () => {
                            if (row) row.remove();
                        });
                    }
                    container.appendChild(fragment);

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }

                function addItemRowWithValues(containerId, nameField, descField, nameValue, descValue) {
                    const container = document.getElementById(containerId);
                    const tpl = document.getElementById('itemRowTemplate');
                    if (!container || !tpl) return;
                    const fragment = tpl.content.cloneNode(true);
                    const row = fragment.querySelector('[data-row]');
                    const nameInput = fragment.querySelector('[data-name]');
                    const descInput = fragment.querySelector('[data-description]');
                    const removeBtn = fragment.querySelector('[data-remove]');

                    if (nameInput) {
                        nameInput.setAttribute('name', nameField);
                        nameInput.value = typeof nameValue === 'string' ? nameValue : '';
                    }
                    if (descInput) {
                        descInput.setAttribute('name', descField);
                        descInput.value = typeof descValue === 'string' ? descValue : '';
                    }
                    if (removeBtn) {
                        removeBtn.addEventListener('click', () => {
                            if (row) row.remove();
                        });
                    }
                    container.appendChild(fragment);

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }

                function resetJobDetailsRows(qualifications, requirements) {
                    const qualificationsContainer = document.getElementById('qualificationsContainer');
                    const requirementsContainer = document.getElementById('requirementsContainer');
                    if (qualificationsContainer) qualificationsContainer.innerHTML = '';
                    if (requirementsContainer) requirementsContainer.innerHTML = '';

                    const q = Array.isArray(qualifications) ? qualifications : [];
                    const r = Array.isArray(requirements) ? requirements : [];

                    if (q.length === 0) {
                        addItemRow('qualificationsContainer', 'qualifications_name[]', 'qualifications_description[]');
                    } else {
                        q.forEach(item => {
                            addItemRowWithValues(
                                'qualificationsContainer',
                                'qualifications_name[]',
                                'qualifications_description[]',
                                item && typeof item === 'object' ? item.name : '',
                                item && typeof item === 'object' ? item.description : ''
                            );
                        });
                    }

                    if (r.length === 0) {
                        addItemRow('requirementsContainer', 'requirements_name[]', 'requirements_description[]');
                    } else {
                        r.forEach(item => {
                            addItemRowWithValues(
                                'requirementsContainer',
                                'requirements_name[]',
                                'requirements_description[]',
                                item && typeof item === 'object' ? item.name : '',
                                item && typeof item === 'object' ? item.description : ''
                            );
                        });
                    }
                }

                window.openJobDetailsModal = function(btn) {
                    const jobDetailsModal = document.getElementById('jobDetailsModal');
                    const jobDetailsJobTitle = document.getElementById('jobDetailsJobTitle');
                    const jobDetailsDepartment = document.getElementById('jobDetailsDepartment');
                    const jobDetailsRequestId = document.getElementById('jobDetailsRequestId');
                    const jobDescriptionInput = document.getElementById('jobDescriptionInput');
                    const jobDetailsHiddenJobTitle = document.getElementById('jobDetailsHiddenJobTitle');
                    const jobDetailsHiddenDepartmentId = document.getElementById('jobDetailsHiddenDepartmentId');
                    const jobDetailsHiddenVacancies = document.getElementById('jobDetailsHiddenVacancies');

                    if (!jobDetailsModal || !btn) return;
                    const raw = btn.getAttribute('data-record');
                    if (!raw) return;
                    const v = JSON.parse(raw);

                    if (jobDetailsJobTitle) jobDetailsJobTitle.textContent = safeText(v.title);
                    if (jobDetailsDepartment) jobDetailsDepartment.textContent = safeText(getDepartment(v) || '-');
                    if (jobDetailsRequestId) jobDetailsRequestId.value = (v && (v.request_id || v.request_id === 0)) ? String(v.request_id) : ((v && (v.id || v.id === 0)) ? String(v.id) : '');
                    if (jobDescriptionInput) jobDescriptionInput.value = (v && typeof v.description === 'string') ? v.description : '';

                    if (jobDetailsHiddenJobTitle) jobDetailsHiddenJobTitle.value = (v && typeof v.title === 'string') ? v.title : '';
                    if (jobDetailsHiddenDepartmentId) jobDetailsHiddenDepartmentId.value = (v && (typeof v.department_id === 'string' || typeof v.department_id === 'number')) ? String(v.department_id) : '';
                    if (jobDetailsHiddenVacancies) jobDetailsHiddenVacancies.value = (v && (typeof v.vacancies === 'string' || typeof v.vacancies === 'number')) ? String(v.vacancies) : '1';

                    resetJobDetailsRows(v && v.qualifications, v && v.requirements);
                    jobDetailsModal.showModal();
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const addQualificationBtn = document.getElementById('addQualificationBtn');
                    const addRequirementBtn = document.getElementById('addRequirementBtn');

                    if (addQualificationBtn) {
                        addQualificationBtn.addEventListener('click', () => {
                            addItemRow('qualificationsContainer', 'qualifications_name[]', 'qualifications_description[]');
                        });
                    }

                    if (addRequirementBtn) {
                        addRequirementBtn.addEventListener('click', () => {
                            addItemRow('requirementsContainer', 'requirements_name[]', 'requirements_description[]');
                        });
                    }

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            </script>

            <script src="../JAVASCRIPT/sidebar.js"></script>
            <?php require('../../partials/footer.php') ?>