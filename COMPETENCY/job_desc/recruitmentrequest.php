<?php
session_start();

require_once __DIR__ . '/db_job_desc.php';

function json_response($success, $message = '', $data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function generate_request_id(): string {
    $date = date('YmdHis');
    $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    return "REQ-{$date}-{$rand}";
}

$jobCatalog = [
    ['job_title' => 'Front Desk Agent', 'department_id' => 'DPT-FO'],
    ['job_title' => 'Housekeeper', 'department_id' => 'DPT-HK'],
    ['job_title' => 'Waiter / Server', 'department_id' => 'DPT-FBS'],
    ['job_title' => 'Bartender', 'department_id' => 'DPT-FBS'],
    ['job_title' => 'Cook', 'department_id' => 'DPT-KC'],
    ['job_title' => 'Sales Executive', 'department_id' => 'DPT-SM'],
    ['job_title' => 'HR Assistant', 'department_id' => 'DPT-HR'],
    ['job_title' => 'Accounting Clerk', 'department_id' => 'DPT-FIN'],
    ['job_title' => 'Maintenance Technician', 'department_id' => 'DPT-ENG'],
    ['job_title' => 'Security Guard', 'department_id' => 'DPT-SEC'],
];

$departments = [
    'DPT-FO' => 'Front Office / Reception',
    'DPT-HK' => 'Housekeeping',
    'DPT-FBS' => 'Food & Beverage (F&B)',
    'DPT-KC' => 'Kitchen / Culinary',
    'DPT-SM' => 'Sales & Marketing',
    'DPT-HR' => 'Human Resources (HR)',
    'DPT-FIN' => 'Finance / Accounting',
    'DPT-ENG' => 'Engineering / Maintenance',
    'DPT-SEC' => 'Security'
];

$departmentDisplayNames = $departments;
$deptApiError = '';
$deptById = [];

try {
    $deptApiUrl = 'https://hr4.soliera-hotel-restaurant.com/CHM/API/get_departments.php';
    $chDept = curl_init($deptApiUrl);
    curl_setopt($chDept, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chDept, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $deptResponse = curl_exec($chDept);
    if (curl_errno($chDept)) {
        $deptApiError = curl_error($chDept);
    }

    $deptHttpCode = curl_getinfo($chDept, CURLINFO_HTTP_CODE);
    curl_close($chDept);

    if ($deptApiError === '' && $deptHttpCode !== 200) {
        $deptApiError = "HTTP Error: {$deptHttpCode}";
    }

    if ($deptApiError === '' && is_string($deptResponse) && $deptResponse !== '') {
        $deptList = json_decode($deptResponse, true);
        if (is_array($deptList)) {
            foreach ($deptList as $d) {
                if (!is_array($d)) continue;
                if (isset($d['active']) && (string)$d['active'] !== '1') continue;
                $idNum = (int)($d['id'] ?? 0);
                $code = trim((string)($d['dept_code'] ?? ''));
                $name = trim((string)($d['name'] ?? ''));
                if ($idNum <= 0 || $code === '' || $name === '') continue;

                $deptById[$idNum] = [
                    'dept_code' => $code,
                    'name' => $name
                ];
                $departmentDisplayNames[$code] = $name;
            }
        }
    }
} catch (Throwable $e) {
    $deptApiError = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sendRecruitment') {
    $job_title = trim((string)($_POST['job_title'] ?? ''));
    $department_id = trim((string)($_POST['department_id'] ?? ''));
    $vacancies = (int)($_POST['vacancies'] ?? 1);

    $isApi = (($_GET['api'] ?? '') === '1');

    if ($job_title === '' || $department_id === '') {
        if ($isApi) {
            json_response(false, 'Missing job title or department', [], 422);
        }
        header('Location: recruitmentrequest.php?error=1');
        exit;
    }
    if ($vacancies <= 0) {
        $vacancies = 1;
    }

    try {
        $conn = job_desc_mysqli();
        $conn->begin_transaction();

        $deptName = $departmentDisplayNames[$department_id] ?? $department_id;
        $stmtDept = $conn->prepare("INSERT INTO departments (request_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        $stmtDept->bind_param('ss', $department_id, $deptName);
        $stmtDept->execute();
        $stmtDept->close();

        $request_id = generate_request_id();

        $stmtReq = $conn->prepare(
            "INSERT INTO recruitment_requests (request_id, job_title, department_id, vacancies, status)
             VALUES (?, ?, ?, ?, 'SENT')
             ON DUPLICATE KEY UPDATE job_title = VALUES(job_title), department_id = VALUES(department_id), vacancies = VALUES(vacancies), status = 'SENT'"
        );
        $stmtReq->bind_param('sssi', $request_id, $job_title, $department_id, $vacancies);
        $stmtReq->execute();
        $stmtReq->close();

        $emptyDesc = '';
        $stmtRole = $conn->prepare(
            "INSERT INTO job_roles (request_id, name, department_id, vacancies, description, source_recruitment_request_id)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), department_id = VALUES(department_id), vacancies = VALUES(vacancies), source_recruitment_request_id = VALUES(source_recruitment_request_id)"
        );
        $stmtRole->bind_param('sssiss', $request_id, $job_title, $department_id, $vacancies, $emptyDesc, $request_id);
        $stmtRole->execute();
        $stmtRole->close();

        $conn->commit();
        $conn->close();

        if ($isApi) {
            json_response(true, 'SQL Success: Recruitment request sent', [
                'request_id' => $request_id,
                'job_title' => $job_title,
                'department_id' => $department_id,
                'vacancies' => $vacancies
            ]);
        }

        header('Location: recruitmentrequest.php?sent=1&request_id=' . urlencode($request_id));
        exit;
    } catch (Throwable $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            try { $conn->rollback(); } catch (Throwable $t) {}
            try { $conn->close(); } catch (Throwable $t) {}
        }
        if ($isApi) {
            json_response(false, 'SQL Error: ' . $e->getMessage(), [], 500);
        }
        header('Location: recruitmentrequest.php?error=1');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Recruitment Requests</h1>
                <p class="text-gray-600">Select a job title and department to send to Job Description module.</p>
            </div>
            <a href="index.php" class="btn btn-outline">Go to Job Descriptions</a>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error mb-4">
                <span>Failed to send recruitment request. Please try again.</span>
            </div>
        <?php endif; ?>

        <?php if ($deptApiError !== ''): ?>
            <div class="alert alert-warning mb-4">
                <span>Department API Warning: <?php echo htmlspecialchars($deptApiError); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow p-4">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th class="w-40">Vacancies</th>
                            <th class="w-40">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobCatalog as $idx => $row): ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($row['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['department_name'] ?? ($departments[$row['department_id']] ?? $row['department_id'])); ?></td>
                                <td>
                                    <input name="vacancies" form="sendForm-<?php echo (int)$idx; ?>" type="text" readonly value="<?php echo (int)($row['vacancies'] ?? 1); ?>" class="input input-bordered input-sm w-24 bg-gray-50" />
                                </td>
                                <td>
                                    <form id="sendForm-<?php echo (int)$idx; ?>" method="POST" class="sendRecruitmentForm">
                                        <input type="hidden" name="action" value="sendRecruitment" />
                                        <input type="hidden" name="job_title" value="<?php echo htmlspecialchars($row['job_title'], ENT_QUOTES); ?>" />
                                        <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($row['department_id'], ENT_QUOTES); ?>" />
                                        <button type="submit" class="btn btn-primary btn-sm">Send</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.sendRecruitmentForm').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                try {
                    const fd = new FormData(form);
                    const res = await fetch('recruitmentrequest.php?api=1', {
                        method: 'POST',
                        body: fd,
                        headers: { 'Accept': 'application/json' }
                    });

                    const json = await res.json();
                    if (!json || json.success !== true || !json.data || !json.data.request_id) {
                        throw new Error(json && json.message ? json.message : 'Failed to send');
                    }

                    const requestId = json && json.data && json.data.request_id ? String(json.data.request_id) : '';
                    const res2 = await Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: json.message || 'SQL Success',
                        showCancelButton: true,
                        confirmButtonText: 'Stay Here',
                        cancelButtonText: 'Go to Job Descriptions',
                        reverseButtons: true
                    });

                    if (res2.dismiss === Swal.DismissReason.cancel) {
                        const qs = requestId ? ('?sent=1&request_id=' + encodeURIComponent(requestId)) : '';
                        window.location.href = 'index.php' + qs;
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err && err.message ? err.message : 'SQL Error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('sent') === '1') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'SQL Success: Recruitment request sent',
                    confirmButtonText: 'OK'
                }).then(() => {
                    params.delete('sent');
                    params.delete('request_id');
                    const qs = params.toString();
                    const cleanUrl = window.location.pathname + (qs ? ('?' + qs) : '');
                    window.history.replaceState({}, '', cleanUrl);
                });
            }
        })();
    </script>
</body>
</html>
