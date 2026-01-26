<?php
session_start();

require_once __DIR__ . '/db_job_desc.php';

function approval_json_response($success, $message = '', $data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function approval_has_job_role_queue(mysqli $conn): bool {
    $res = $conn->query("SHOW TABLES LIKE 'job_role_approval_requests'");
    return ($res && $res->num_rows > 0);
}

function approval_load_job_role_pending(mysqli $conn): array {
    $res = $conn->query(
        "SELECT jr.id, jr.job_role_request_id, jr.approval_status, jr.payload_json, jr.requested_at, jr.approved_at, jr.rejected_at,
                j.name AS job_title, d.name AS department_name
         FROM job_role_approval_requests jr
         LEFT JOIN job_roles j ON j.request_id = jr.job_role_request_id
         LEFT JOIN departments d ON d.request_id = j.department_id
         WHERE jr.approval_status = 'pending'
         ORDER BY jr.requested_at DESC, jr.id DESC"
    );
    if (!$res) {
        return [];
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
    return $rows;
}

function approval_load_job_role_posted(mysqli $conn): array {
    $res = $conn->query(
        "SELECT jr.id, jr.job_role_request_id, jr.approval_status, jr.payload_json, jr.requested_at, jr.approved_at, jr.rejected_at,
                j.name AS job_title, d.name AS department_name
         FROM job_role_approval_requests jr
         LEFT JOIN job_roles j ON j.request_id = jr.job_role_request_id
         LEFT JOIN departments d ON d.request_id = j.department_id
         WHERE jr.approval_status = 'posted'
         ORDER BY jr.approved_at DESC, jr.id DESC"
    );
    if (!$res) {
        return [];
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
    return $rows;
}

function approval_has_workflow(mysqli $conn): bool {
    $res = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'approval_status'");
    return ($res && $res->num_rows > 0);
}

function approval_load_pending(mysqli $conn): array {
    $res = $conn->query(
        "SELECT id, name, category, status, approval_status, pending_action, delete_reason, review_reason, requested_at, approved_at, rejected_at, description, priority, role, last_updated
         FROM competency_standards
         WHERE approval_status = 'pending'
         ORDER BY requested_at DESC, id DESC"
    );
    if (!$res) {
        return [];
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
    return $rows;
}

function approval_load_posted(mysqli $conn): array {
    $res = $conn->query(
        "SELECT id, name, category, status, approval_status, pending_action, delete_reason, review_reason, requested_at, approved_at, rejected_at, description, priority, role, last_updated
         FROM competency_standards
         WHERE approval_status = 'posted'
         ORDER BY approved_at DESC, id DESC"
    );
    if (!$res) {
        return [];
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['api'] ?? '') === '1') {
    $action = $_GET['action'] ?? '';
    $kind = $_GET['kind'] ?? '';
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    try {
        $conn = job_desc_mysqli();
        $conn->begin_transaction();

        if ($kind === 'job_role') {
            if (!approval_has_job_role_queue($conn)) {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Job Role approval queue not installed yet. Please run the updated job_desc.sql first.', [], 500);
            }

            if ($action === 'approve') {
                $id = isset($payload['id']) ? (int)$payload['id'] : 0;
                if ($id <= 0) {
                    $conn->rollback();
                    $conn->close();
                    approval_json_response(false, 'Missing id', [], 422);
                }

                $stmtSel = $conn->prepare("SELECT approval_status, job_role_request_id, payload_json FROM job_role_approval_requests WHERE id = ?");
                $stmtSel->bind_param('i', $id);
                $stmtSel->execute();
                $res = $stmtSel->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmtSel->close();

                if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
                    $conn->rollback();
                    $conn->close();
                    approval_json_response(false, 'Request is not pending', [], 409);
                }

                $jobRoleRequestId = (string)($row['job_role_request_id'] ?? '');
                $decoded = json_decode((string)($row['payload_json'] ?? ''), true);
                if (!is_array($decoded)) {
                    $decoded = [];
                }

                $desc = (string)($decoded['description'] ?? '');
                $qualifications = is_array($decoded['qualifications'] ?? null) ? $decoded['qualifications'] : [];
                $requirements = is_array($decoded['requirements'] ?? null) ? $decoded['requirements'] : [];

                $stmtUpRole = $conn->prepare("UPDATE job_roles SET description = ?, updated_at = NOW() WHERE request_id = ?");
                $stmtUpRole->bind_param('ss', $desc, $jobRoleRequestId);
                $stmtUpRole->execute();
                $stmtUpRole->close();

                $stmtDelQ = $conn->prepare("DELETE FROM qualifications WHERE request_id = ?");
                $stmtDelQ->bind_param('s', $jobRoleRequestId);
                $stmtDelQ->execute();
                $stmtDelQ->close();

                if (!empty($qualifications)) {
                    $stmtInsQ = $conn->prepare("INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)");
                    $prio = 1;
                    foreach ($qualifications as $q) {
                        if (!is_array($q)) continue;
                        $text = (string)($q['text'] ?? '');
                        if (trim($text) === '') continue;
                        $type = (string)($q['type'] ?? 'Education');
                        $stmtInsQ->bind_param('sssi', $jobRoleRequestId, $text, $type, $prio);
                        $stmtInsQ->execute();
                        $prio++;
                    }
                    $stmtInsQ->close();
                }

                $stmtDelR = $conn->prepare("DELETE FROM job_requirements WHERE request_id = ?");
                $stmtDelR->bind_param('s', $jobRoleRequestId);
                $stmtDelR->execute();
                $stmtDelR->close();

                if (!empty($requirements)) {
                    $stmtInsR = $conn->prepare("INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)");
                    foreach ($requirements as $req) {
                        if (!is_array($req)) continue;
                        $text = (string)($req['text'] ?? '');
                        if (trim($text) === '') continue;
                        $category = (string)($req['category'] ?? 'Skill');
                        $essential = !empty($req['essential']) ? 1 : 0;
                        $stmtInsR->bind_param('sssi', $jobRoleRequestId, $text, $category, $essential);
                        $stmtInsR->execute();
                    }
                    $stmtInsR->close();
                }

                $stmtMarkAssigned = $conn->prepare("UPDATE recruitment_requests SET status = 'ASSIGNED' WHERE request_id = ?");
                if ($stmtMarkAssigned) {
                    $stmtMarkAssigned->bind_param('s', $jobRoleRequestId);
                    $stmtMarkAssigned->execute();
                    $stmtMarkAssigned->close();
                }

                $stmtUpReq = $conn->prepare("UPDATE job_role_approval_requests
                                             SET approval_status = 'posted', approved_at = CURRENT_TIMESTAMP, rejected_at = NULL
                                             WHERE id = ?");
                $stmtUpReq->bind_param('i', $id);
                $stmtUpReq->execute();
                $stmtUpReq->close();

                $conn->commit();
                $pendingJ = approval_load_job_role_pending($conn);
                $postedJ = approval_load_job_role_posted($conn);
                $conn->close();
                approval_json_response(true, 'Approved', ['job_role_pending' => $pendingJ, 'job_role_posted' => $postedJ]);
            }

            if ($action === 'reject') {
                $id = isset($payload['id']) ? (int)$payload['id'] : 0;
                if ($id <= 0) {
                    $conn->rollback();
                    $conn->close();
                    approval_json_response(false, 'Missing id', [], 422);
                }

                $stmtSel = $conn->prepare("SELECT approval_status FROM job_role_approval_requests WHERE id = ?");
                $stmtSel->bind_param('i', $id);
                $stmtSel->execute();
                $res = $stmtSel->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmtSel->close();

                if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
                    $conn->rollback();
                    $conn->close();
                    approval_json_response(false, 'Request is not pending', [], 409);
                }

                $stmtUpReq = $conn->prepare("UPDATE job_role_approval_requests
                                             SET approval_status = 'rejected', rejected_at = CURRENT_TIMESTAMP
                                             WHERE id = ?");
                $stmtUpReq->bind_param('i', $id);
                $stmtUpReq->execute();
                $stmtUpReq->close();

                $conn->commit();
                $pendingJ = approval_load_job_role_pending($conn);
                $postedJ = approval_load_job_role_posted($conn);
                $conn->close();
                approval_json_response(true, 'Rejected', ['job_role_pending' => $pendingJ, 'job_role_posted' => $postedJ]);
            }

            if ($action === 'load') {
                $conn->commit();
                $pendingJ = approval_load_job_role_pending($conn);
                $postedJ = approval_load_job_role_posted($conn);
                $conn->close();
                approval_json_response(true, 'Loaded', ['job_role_pending' => $pendingJ, 'job_role_posted' => $postedJ]);
            }

            $conn->rollback();
            $conn->close();
            approval_json_response(false, 'Unknown action', [], 400);
        }

        if (!approval_has_workflow($conn)) {
            $conn->rollback();
            $conn->close();
            approval_json_response(false, 'Approval workflow not installed yet. Please run the updated job_desc.sql first.', [], 500);
        }

        if ($action === 'approve') {
            $id = isset($payload['id']) ? (int)$payload['id'] : 0;
            if ($id <= 0) {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Missing id', [], 422);
            }

            $stmtSel = $conn->prepare("SELECT approval_status, pending_action FROM competency_standards WHERE id = ?");
            $stmtSel->bind_param('i', $id);
            $stmtSel->execute();
            $res = $stmtSel->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmtSel->close();

            if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Item is not pending', [], 409);
            }

            $pendingAction = (string)($row['pending_action'] ?? 'upsert');

            if ($pendingAction === 'delete') {
                $stmtMap = $conn->prepare("DELETE FROM job_criteria_mappings WHERE competency_id = ?");
                if ($stmtMap) {
                    $stmtMap->bind_param('i', $id);
                    $stmtMap->execute();
                    $stmtMap->close();
                }

                $stmtCrit = $conn->prepare("DELETE FROM competency_level_criteria WHERE competency_id = ?");
                if ($stmtCrit) {
                    $stmtCrit->bind_param('i', $id);
                    $stmtCrit->execute();
                    $stmtCrit->close();
                }

                $stmtDel = $conn->prepare("DELETE FROM competency_standards WHERE id = ?");
                $stmtDel->bind_param('i', $id);
                $stmtDel->execute();
                $stmtDel->close();

                $conn->commit();
                $pending = approval_load_pending($conn);
                $posted = approval_load_posted($conn);
                $conn->close();
                approval_json_response(true, 'Deleted', ['pending' => $pending, 'posted' => $posted]);
            }

            $stmtUp = $conn->prepare("UPDATE competency_standards
                                     SET approval_status = 'posted', pending_action = 'upsert', delete_reason = NULL,
                                         review_reason = NULL,
                                         approved_at = CURRENT_TIMESTAMP, rejected_at = NULL
                                     WHERE id = ?");
            $stmtUp->bind_param('i', $id);
            $stmtUp->execute();
            $stmtUp->close();

            $stmtMap2 = $conn->prepare("UPDATE job_criteria_mappings SET is_active = 1 WHERE competency_id = ?");
            if ($stmtMap2) {
                $stmtMap2->bind_param('i', $id);
                $stmtMap2->execute();
                $stmtMap2->close();
            }

            $conn->commit();
            $pending = approval_load_pending($conn);
            $posted = approval_load_posted($conn);
            $conn->close();
            approval_json_response(true, 'Approved', ['pending' => $pending, 'posted' => $posted]);
        }

        if ($action === 'reject') {
            $id = isset($payload['id']) ? (int)$payload['id'] : 0;
            $reason = trim((string)($payload['reason'] ?? ''));
            if ($id <= 0) {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Missing id', [], 422);
            }
            if ($reason === '') {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Reason is required', [], 422);
            }

            $stmtSel = $conn->prepare("SELECT approval_status FROM competency_standards WHERE id = ?");
            $stmtSel->bind_param('i', $id);
            $stmtSel->execute();
            $res = $stmtSel->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmtSel->close();

            if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Item is not pending', [], 409);
            }

            $stmtUp = $conn->prepare("UPDATE competency_standards
                                     SET approval_status = 'rejected', review_reason = ?, rejected_at = CURRENT_TIMESTAMP
                                     WHERE id = ?");
            $stmtUp->bind_param('si', $reason, $id);
            $stmtUp->execute();
            $stmtUp->close();

            $stmtMap = $conn->prepare("UPDATE job_criteria_mappings SET is_active = 0 WHERE competency_id = ?");
            if ($stmtMap) {
                $stmtMap->bind_param('i', $id);
                $stmtMap->execute();
                $stmtMap->close();
            }

            $conn->commit();
            $pending = approval_load_pending($conn);
            $posted = approval_load_posted($conn);
            $conn->close();
            approval_json_response(true, 'Rejected', ['pending' => $pending, 'posted' => $posted]);
        }

        if ($action === 'compliance') {
            $id = isset($payload['id']) ? (int)$payload['id'] : 0;
            $reason = trim((string)($payload['reason'] ?? ''));
            if ($id <= 0) {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Missing id', [], 422);
            }
            if ($reason === '') {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Reason is required', [], 422);
            }

            $stmtSel = $conn->prepare("SELECT approval_status FROM competency_standards WHERE id = ?");
            $stmtSel->bind_param('i', $id);
            $stmtSel->execute();
            $res = $stmtSel->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmtSel->close();

            if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
                $conn->rollback();
                $conn->close();
                approval_json_response(false, 'Item is not pending', [], 409);
            }

            $stmtUp = $conn->prepare("UPDATE competency_standards
                                     SET approval_status = 'compliance', review_reason = ?, rejected_at = CURRENT_TIMESTAMP
                                     WHERE id = ?");
            $stmtUp->bind_param('si', $reason, $id);
            $stmtUp->execute();
            $stmtUp->close();

            $stmtMap = $conn->prepare("UPDATE job_criteria_mappings SET is_active = 0 WHERE competency_id = ?");
            if ($stmtMap) {
                $stmtMap->bind_param('i', $id);
                $stmtMap->execute();
                $stmtMap->close();
            }

            $conn->commit();
            $pending = approval_load_pending($conn);
            $posted = approval_load_posted($conn);
            $conn->close();
            approval_json_response(true, 'For compliance', ['pending' => $pending, 'posted' => $posted]);
        }

        if ($action === 'load') {
            $conn->commit();
            $pending = approval_load_pending($conn);
            $posted = approval_load_posted($conn);
            $conn->close();
            approval_json_response(true, 'Loaded', ['pending' => $pending, 'posted' => $posted]);
        }

        $conn->rollback();
        $conn->close();
        approval_json_response(false, 'Unknown action', [], 400);
    } catch (Throwable $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            try { $conn->rollback(); } catch (Throwable $t) {}
            try { $conn->close(); } catch (Throwable $t) {}
        }
        approval_json_response(false, 'Server error: ' . $e->getMessage(), [], 500);
    }
}

$pendingRows = [];
$postedRows = [];
$pendingJobRole = [];
$postedJobRole = [];
$workflowInstalled = false;
$jobRoleQueueInstalled = false;
$errorMessage = '';

try {
    $conn = job_desc_mysqli();
    $workflowInstalled = approval_has_workflow($conn);
    $jobRoleQueueInstalled = approval_has_job_role_queue($conn);
    if ($workflowInstalled) {
        $pendingRows = approval_load_pending($conn);
        $postedRows = approval_load_posted($conn);
    }
    if ($jobRoleQueueInstalled) {
        $pendingJobRole = approval_load_job_role_pending($conn);
        $postedJobRole = approval_load_job_role_posted($conn);
    }
    $conn->close();
} catch (Throwable $e) {
    $workflowInstalled = false;
    $jobRoleQueueInstalled = false;
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.__PENDING__ = <?php echo json_encode($pendingRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__POSTED__ = <?php echo json_encode($postedRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__JOB_ROLE_PENDING__ = <?php echo json_encode($pendingJobRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__JOB_ROLE_POSTED__ = <?php echo json_encode($postedJobRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__WORKFLOW_INSTALLED__ = <?php echo $workflowInstalled ? 'true' : 'false'; ?>;
        window.__JOB_ROLE_QUEUE_INSTALLED__ = <?php echo $jobRoleQueueInstalled ? 'true' : 'false'; ?>;
        window.__WORKFLOW_ERROR__ = <?php echo json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1f3a8a',
                        'primary-dark': '#1b3280'
                    }
                }
            }
        }
    </script>

    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .container-box { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; }
        .btn-primary { background: var(--brand-primary, #1f3a8a) !important; color: white !important; border: none !important; border-radius: 8px !important; font-weight: 500 !important; padding: 10px 20px !important; }
        .btn-outline { background: transparent !important; color: #1f3a8a !important; border: 1px solid #1f3a8a !important; border-radius: 8px !important; font-weight: 500 !important; padding: 10px 20px !important; }
        .stat-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; }
        .tab-btn { border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; padding: 10px 14px; font-weight: 600; color: #334155; }
        .tab-btn.active { background: #1f3a8a; border-color: #1f3a8a; color: #ffffff; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Approval</h1>
                <p class="text-gray-600 mt-1">Approve or reject Pending criteria before they can be used by Auto-Generate.</p>
            </div>
            <div class="flex gap-2">
                <a href="addcriteria.php" class="btn-outline"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                <button class="btn-primary" onclick="reloadData()"><i class="fas fa-rotate mr-2"></i>Refresh</button>
            </div>
        </div>

        <div id="workflow-warning" class="container-box p-5 mb-6 hidden">
            <div class="text-red-700 font-medium">Approval workflow not installed.</div>
            <div class="text-sm text-gray-600 mt-2">Run the updated <code>job_desc.sql</code> to add the required columns.</div>
            <div class="text-sm text-gray-500 mt-2" id="workflow-error"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card p-5">
                <div class="text-sm text-gray-600">Pending Criteria</div>
                <div class="text-2xl font-bold text-gray-900" id="stat-pending-criteria">0</div>
            </div>
            <div class="stat-card p-5">
                <div class="text-sm text-gray-600">Posted Criteria</div>
                <div class="text-2xl font-bold text-gray-900" id="stat-posted-criteria">0</div>
            </div>
            <div class="stat-card p-5">
                <div class="text-sm text-gray-600">Pending Job Roles</div>
                <div class="text-2xl font-bold text-gray-900" id="stat-pending-jobroles">0</div>
            </div>
            <div class="stat-card p-5">
                <div class="text-sm text-gray-600">Posted Job Roles</div>
                <div class="text-2xl font-bold text-gray-900" id="stat-posted-jobroles">0</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <button type="button" id="tab-criteria" class="tab-btn active" onclick="setTab('criteria')">
                Criteria Approvals
            </button>
            <button type="button" id="tab-jobroles" class="tab-btn" onclick="setTab('jobroles')">
                Job Role Approvals
            </button>
        </div>

        <div id="panel-criteria">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="container-box p-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Pending Criteria Requests</h2>
                    <div id="pending-cards" class="space-y-3"></div>
                </div>

                <div class="container-box p-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Posted (Approved) Criteria</h2>
                    <div id="posted-cards" class="space-y-3"></div>
                </div>
            </div>
        </div>

        <div id="panel-jobroles" class="hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="container-box p-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Pending Job Role Updates</h2>
                    <div id="jobrole-pending-cards" class="space-y-3"></div>
                </div>

                <div class="container-box p-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Posted Job Role Updates</h2>
                    <div id="jobrole-posted-cards" class="space-y-3"></div>
                </div>
            </div>
        </div>
    </div>

<script>
let pending = Array.isArray(window.__PENDING__) ? window.__PENDING__ : [];
let posted = Array.isArray(window.__POSTED__) ? window.__POSTED__ : [];
let jobRolePending = Array.isArray(window.__JOB_ROLE_PENDING__) ? window.__JOB_ROLE_PENDING__ : [];
let jobRolePosted = Array.isArray(window.__JOB_ROLE_POSTED__) ? window.__JOB_ROLE_POSTED__ : [];

let currentTab = 'criteria';

try {
    const params = new URLSearchParams(window.location.search);
    if (params.get('jobrole_submitted') === '1') {
        Swal.fire({
            title: 'Submitted',
            text: 'Job role changes have been submitted for approval.',
            icon: 'success',
            confirmButtonColor: '#1f3a8a'
        }).then(() => {
            params.delete('jobrole_submitted');
            const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, document.title, clean);
        });
    }
} catch (e) {}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setTab(name) {
    const next = (name === 'jobroles') ? 'jobroles' : 'criteria';
    currentTab = next;

    const tabCriteria = document.getElementById('tab-criteria');
    const tabJobroles = document.getElementById('tab-jobroles');
    const panelCriteria = document.getElementById('panel-criteria');
    const panelJobroles = document.getElementById('panel-jobroles');

    if (tabCriteria) tabCriteria.classList.toggle('active', currentTab === 'criteria');
    if (tabJobroles) tabJobroles.classList.toggle('active', currentTab === 'jobroles');

    if (panelCriteria) panelCriteria.classList.toggle('hidden', currentTab !== 'criteria');
    if (panelJobroles) panelJobroles.classList.toggle('hidden', currentTab !== 'jobroles');
}

function render() {
    const pendingCards = document.getElementById('pending-cards');
    const postedCards = document.getElementById('posted-cards');
    const jobRolePendingCards = document.getElementById('jobrole-pending-cards');
    const jobRolePostedCards = document.getElementById('jobrole-posted-cards');

    const statPendingCriteria = document.getElementById('stat-pending-criteria');
    const statPostedCriteria = document.getElementById('stat-posted-criteria');
    const statPendingJobroles = document.getElementById('stat-pending-jobroles');
    const statPostedJobroles = document.getElementById('stat-posted-jobroles');

    if (statPendingCriteria) statPendingCriteria.textContent = String(pending.length);
    if (statPostedCriteria) statPostedCriteria.textContent = String(posted.length);
    if (statPendingJobroles) statPendingJobroles.textContent = String(jobRolePending.length);
    if (statPostedJobroles) statPostedJobroles.textContent = String(jobRolePosted.length);

    if (pendingCards) {
        pendingCards.innerHTML = pending.length ? pending.map(r => {
            const action = String(r.pending_action || 'upsert');
            const actionText = action === 'delete' ? 'Delete Request' : 'New/Update';
            const badgeClass = action === 'delete' ? 'badge-error' : 'badge-warning';
            const deleteReason = (action === 'delete' && r.delete_reason) ? `<div class="text-xs text-red-600 mt-2">Delete reason: ${escapeHtml(r.delete_reason)}</div>` : '';
            return `
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-gray-500">ID ${escapeHtml(r.id)}</div>
                                <div class="font-semibold text-gray-900 truncate">${escapeHtml(r.name)}</div>
                                <div class="text-sm text-gray-600 line-clamp-2 mt-1">${escapeHtml(r.description || '')}</div>
                                ${deleteReason}
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="badge ${badgeClass}">${escapeHtml(actionText)}</span>
                                <div class="text-xs text-gray-500">${escapeHtml(r.requested_at || '')}</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-end gap-2">
                        <button class="btn btn-sm btn-success text-white" onclick="approve(${Number(r.id)})"><i class="fas fa-check mr-1"></i>Approve</button>
                        <button class="btn btn-sm btn-ghost text-blue-600" onclick="compliance(${Number(r.id)})"><i class="fas fa-circle-exclamation mr-1"></i>For Compliance</button>
                        <button class="btn btn-sm btn-ghost text-red-600" onclick="reject(${Number(r.id)})"><i class="fas fa-xmark mr-1"></i>Reject</button>
                    </div>
                </div>
            `;
        }).join('') : `<div class="text-center text-gray-500 py-10">No pending requests</div>`;
    }

    if (postedCards) {
        postedCards.innerHTML = posted.length ? posted.slice(0, 50).map(r => {
            return `
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-gray-500">ID ${escapeHtml(r.id)}</div>
                                <div class="font-semibold text-gray-900 truncate">${escapeHtml(r.name)}</div>
                                <div class="text-sm text-gray-600 line-clamp-2 mt-1">${escapeHtml(r.description || '')}</div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="badge badge-success">Posted</span>
                                <div class="text-xs text-gray-500">${escapeHtml(r.approved_at || '')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('') : `<div class="text-center text-gray-500 py-10">No posted items</div>`;
    }

    if (jobRolePendingCards) {
        jobRolePendingCards.innerHTML = jobRolePending.length ? jobRolePending.map(r => {
            const title = r.job_title ? String(r.job_title) : String(r.job_role_request_id || '');
            const dept = r.department_name ? String(r.department_name) : '';
            const label = dept ? `${title} • ${dept}` : title;
            return `
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-gray-500">ID ${escapeHtml(r.id)}</div>
                                <div class="font-semibold text-gray-900 truncate">${escapeHtml(label)}</div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="badge badge-warning">Pending</span>
                                <div class="text-xs text-gray-500">${escapeHtml(r.requested_at || '')}</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-end gap-2">
                        <button class="btn btn-sm btn-success text-white" onclick="approveJobRole(${Number(r.id)})"><i class="fas fa-check mr-1"></i>Approve</button>
                        <button class="btn btn-sm btn-ghost text-red-600" onclick="rejectJobRole(${Number(r.id)})"><i class="fas fa-xmark mr-1"></i>Reject</button>
                    </div>
                </div>
            `;
        }).join('') : `<div class="text-center text-gray-500 py-10">No pending job role updates</div>`;
    }

    if (jobRolePostedCards) {
        jobRolePostedCards.innerHTML = jobRolePosted.length ? jobRolePosted.slice(0, 50).map(r => {
            const title = r.job_title ? String(r.job_title) : String(r.job_role_request_id || '');
            const dept = r.department_name ? String(r.department_name) : '';
            const label = dept ? `${title} • ${dept}` : title;
            return `
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-gray-500">ID ${escapeHtml(r.id)}</div>
                                <div class="font-semibold text-gray-900 truncate">${escapeHtml(label)}</div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="badge badge-success">Posted</span>
                                <div class="text-xs text-gray-500">${escapeHtml(r.approved_at || '')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('') : `<div class="text-center text-gray-500 py-10">No posted job role updates</div>`;
    }
}

function reloadData() {
    fetch('approval.php?api=1&action=load', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({})
    }).then(r => r.json())
      .then(json => {
        if (!json || json.success !== true) {
            throw new Error(json && json.message ? json.message : 'Failed to load');
        }
        pending = (json.data && Array.isArray(json.data.pending)) ? json.data.pending : [];
        posted = (json.data && Array.isArray(json.data.posted)) ? json.data.posted : [];
        render();
      })
      .catch(err => {
        Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Failed to load', icon: 'error', confirmButtonColor: '#1f3a8a' });
      });

    if (window.__JOB_ROLE_QUEUE_INSTALLED__) {
        fetch('approval.php?api=1&kind=job_role&action=load', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({})
        }).then(r => r.json())
          .then(json => {
            if (!json || json.success !== true) {
                throw new Error(json && json.message ? json.message : 'Failed to load');
            }
            jobRolePending = (json.data && Array.isArray(json.data.job_role_pending)) ? json.data.job_role_pending : [];
            jobRolePosted = (json.data && Array.isArray(json.data.job_role_posted)) ? json.data.job_role_posted : [];
            render();
          })
          .catch(err => {
            Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Failed to load job role approvals', icon: 'error', confirmButtonColor: '#1f3a8a' });
          });
    }
}

function approveJobRole(id) {
    fetch('approval.php?api=1&kind=job_role&action=approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    }).then(r => r.json())
      .then(json => {
        if (!json || json.success !== true) {
            throw new Error(json && json.message ? json.message : 'Approve failed');
        }
        jobRolePending = (json.data && Array.isArray(json.data.job_role_pending)) ? json.data.job_role_pending : [];
        jobRolePosted = (json.data && Array.isArray(json.data.job_role_posted)) ? json.data.job_role_posted : [];
        render();
        Swal.fire({
            title: 'Approved',
            text: (json && json.message) ? String(json.message) : 'Job role update approved.',
            icon: 'success',
            confirmButtonColor: '#1f3a8a'
        });
      })
      .catch(err => {
        Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Approve failed', icon: 'error', confirmButtonColor: '#1f3a8a' });
      });
}

function rejectJobRole(id) {
    fetch('approval.php?api=1&kind=job_role&action=reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    }).then(r => r.json())
      .then(json => {
        if (!json || json.success !== true) {
            throw new Error(json && json.message ? json.message : 'Reject failed');
        }
        jobRolePending = (json.data && Array.isArray(json.data.job_role_pending)) ? json.data.job_role_pending : [];
        jobRolePosted = (json.data && Array.isArray(json.data.job_role_posted)) ? json.data.job_role_posted : [];
        render();
        Swal.fire({
            title: 'Rejected',
            text: (json && json.message) ? String(json.message) : 'Job role update rejected.',
            icon: 'success',
            confirmButtonColor: '#1f3a8a'
        });
      })
      .catch(err => {
        Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Reject failed', icon: 'error', confirmButtonColor: '#1f3a8a' });
      });
}

function approve(id) {
    fetch('approval.php?api=1&action=approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    }).then(r => r.json())
      .then(json => {
        if (!json || json.success !== true) {
            throw new Error(json && json.message ? json.message : 'Approve failed');
        }
        pending = (json.data && Array.isArray(json.data.pending)) ? json.data.pending : [];
        posted = (json.data && Array.isArray(json.data.posted)) ? json.data.posted : [];
        render();
        Swal.fire({
            title: 'Approved',
            text: (json && json.message) ? String(json.message) : 'Criteria approved.',
            icon: 'success',
            confirmButtonColor: '#1f3a8a'
        });
      })
      .catch(err => {
        Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Approve failed', icon: 'error', confirmButtonColor: '#1f3a8a' });
      });
}

function reject(id) {
    Swal.fire({
        title: 'Reject',
        text: 'Provide a reason for rejection',
        input: 'textarea',
        inputPlaceholder: 'Enter reason...',
        inputValidator: (value) => {
            if (!value || !String(value).trim()) return 'Reason is required';
            return null;
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('approval.php?api=1&action=reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ id: Number(id), reason: String(result.value || '').trim() })
        }).then(r => r.json())
          .then(json => {
            if (!json || json.success !== true) {
                throw new Error(json && json.message ? json.message : 'Reject failed');
            }
            pending = (json.data && Array.isArray(json.data.pending)) ? json.data.pending : [];
            posted = (json.data && Array.isArray(json.data.posted)) ? json.data.posted : [];
            render();
            Swal.fire({
                title: 'Rejected',
                text: (json && json.message) ? String(json.message) : 'Criteria rejected.',
                icon: 'success',
                confirmButtonColor: '#1f3a8a'
            });
          })
          .catch(err => {
            Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Reject failed', icon: 'error', confirmButtonColor: '#1f3a8a' });
          });
    });
}

function compliance(id) {
    Swal.fire({
        title: 'For Compliance',
        text: 'Provide compliance notes / required changes',
        input: 'textarea',
        inputPlaceholder: 'Enter notes...',
        inputValidator: (value) => {
            if (!value || !String(value).trim()) return 'Notes are required';
            return null;
        },
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Send for Compliance',
        confirmButtonColor: '#1f3a8a',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('approval.php?api=1&action=compliance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ id: Number(id), reason: String(result.value || '').trim() })
        }).then(r => r.json())
          .then(json => {
            if (!json || json.success !== true) {
                throw new Error(json && json.message ? json.message : 'Compliance failed');
            }
            pending = (json.data && Array.isArray(json.data.pending)) ? json.data.pending : [];
            posted = (json.data && Array.isArray(json.data.posted)) ? json.data.posted : [];
            render();
            Swal.fire({
                title: 'Sent for Compliance',
                text: (json && json.message) ? String(json.message) : 'Criteria marked for compliance.',
                icon: 'success',
                confirmButtonColor: '#1f3a8a'
            });
          })
          .catch(err => {
            Swal.fire({ title: 'Error', text: err && err.message ? err.message : 'Compliance failed', icon: 'error', confirmButtonColor: '#1f3a8a' });
          });
    });
}

(function init() {
    if (!window.__WORKFLOW_INSTALLED__) {
        document.getElementById('workflow-warning').classList.remove('hidden');
        document.getElementById('workflow-error').textContent = window.__WORKFLOW_ERROR__ ? String(window.__WORKFLOW_ERROR__) : '';
        return;
    }
    setTab('criteria');
    render();
})();
</script>
</body>
</html>
