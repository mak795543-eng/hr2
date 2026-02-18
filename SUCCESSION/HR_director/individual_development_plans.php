<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');
$flashErrMsg = (string)($_GET['err_msg'] ?? '');

function classifyDbError(Throwable $e): string
{
    $msg = strtolower((string)$e->getMessage());
    if (str_contains($msg, 'base table') && str_contains($msg, 'doesn\'t exist')) return 'db_table_missing';
    if (str_contains($msg, 'table') && str_contains($msg, 'doesn\'t exist')) return 'db_table_missing';
    if (str_contains($msg, 'access denied') || str_contains($msg, 'command denied') || str_contains($msg, 'permission')) return 'db_permission_denied';
    if (str_contains($msg, 'foreign key constraint fails')) return 'db_fk_error';
    if (str_contains($msg, 'unknown column')) return 'db_schema_mismatch';
    if (str_contains($msg, 'duplicate entry')) return 'db_duplicate';
    return 'failed';
}

function friendlyErrMsg(string $code): string
{
    $map = [
        'db_table_missing' => 'Request repository table is missing in the database.',
        'db_permission_denied' => 'Database permission denied while requesting.',
        'db_fk_error' => 'Database constraint error. Employee record may be missing.',
        'db_schema_mismatch' => 'Database schema mismatch. Columns differ from expected.',
        'db_duplicate' => 'This request already exists.',
        'invalid' => 'Invalid request.',
        'failed' => 'Request failed due to a server/database issue.',
    ];
    return $map[$code] ?? 'Something went wrong.';
}

function getTableColumnSet(PDO $pdo, string $tableName): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?"
    );
    $stmt->execute([$tableName]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $set = [];
    foreach ($cols as $c) {
        $c = (string)$c;
        if ($c !== '') $set[$c] = true;
    }
    return $set;
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?
         LIMIT 1"
    );
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

function ensureRequestedIdpsRepositoryTable(PDO $pdo): void
{
    if (tableExists($pdo, 'requested_idps_repository')) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS requested_idps_repository (
            id INT PRIMARY KEY,
            employee_id VARCHAR(50) NOT NULL,
            employee_name VARCHAR(100) NOT NULL,
            position VARCHAR(100) NOT NULL,
            department VARCHAR(100) NOT NULL,
            competency DECIMAL(5,2) DEFAULT 0,
            succession_status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
            development_plan TEXT,
            target_score DECIMAL(5,2) DEFAULT NULL,
            target_date DATE DEFAULT NULL,
            delivery_mode ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite',
            requested_training_type VARCHAR(50) DEFAULT NULL,
            requested_training_mode VARCHAR(20) DEFAULT NULL,
            requested_start_datetime DATETIME DEFAULT NULL,
            requested_end_datetime DATETIME DEFAULT NULL,
            idp_status ENUM('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'requested',
            training_requested_at TIMESTAMP NULL DEFAULT NULL,
            learning_requested_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_employee_requested_idp (employee_id),
            INDEX idx_requested_idp_status (idp_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function buildReturnUrl(string $fallbackPath = 'individual_development_plans.php'): string
{
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref === '') return $fallbackPath;

    $parts = parse_url($ref);
    if (!is_array($parts)) return $fallbackPath;

    $path = (string)($parts['path'] ?? '');
    if ($path === '' || stripos($path, 'individual_development_plans.php') === false) {
        return $fallbackPath;
    }

    $q = [];
    parse_str((string)($parts['query'] ?? ''), $q);
    $dept = isset($q['department']) ? trim((string)$q['department']) : '';
    $role = isset($q['role']) ? trim((string)$q['role']) : '';

    $out = [];
    if ($dept !== '') $out['department'] = $dept;
    if ($role !== '') $out['role'] = $role;

    return $fallbackPath . (count($out) ? ('?' . http_build_query($out)) : '');
}

function addQueryParams(string $url, array $params): string
{
    $parts = parse_url($url);
    if (!is_array($parts)) return $url;

    $path = (string)($parts['path'] ?? $url);
    $q = [];
    parse_str((string)($parts['query'] ?? ''), $q);
    foreach ($params as $k => $v) {
        $q[(string)$k] = (string)$v;
    }
    $query = http_build_query($q);
    return $path . ($query !== '' ? ('?' . $query) : '');
}

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
$selectedDepartment = trim((string)($_GET['department'] ?? 'all'));
$selectedRole = trim((string)($_GET['role'] ?? 'all'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $idpId = (int)($_POST['idp_id'] ?? 0);
    $returnBasePost = buildReturnUrl('individual_development_plans.php');

    if ($action === 'get_general_skills') {
        header('Content-Type: application/json; charset=utf-8');
        $employeeId = trim((string)($_POST['employee_id'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));

        if ($employeeId === '' || $department === '') {
            echo json_encode(['success' => false, 'message' => 'Missing request.']);
            exit;
        }

        try {
            $stmtSkills = $pdo->prepare(
                "SELECT k.kpi_name AS skill_name,
                        AVG(COALESCE(s.score, 0)) / 5 * 100 AS skill_score,
                        NULL AS assessment_date
                 FROM employee_kpi_scores s
                 JOIN kpis k
                   ON k.id = s.kpi_id
                 WHERE s.employee_id = ?
                   AND s.evaluation_period = ?
                 GROUP BY k.kpi_name
                 ORDER BY k.kpi_name ASC"
            );
            seedMissingKpiEvaluations($employeeId, $period);
            $stmtSkills->execute([$employeeId, $period]);
            $skills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'skills' => $skills]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to load skills.']);
            exit;
        }
    }

    try {
        if ($action === 'edit_idp' && $idpId > 0) {
            $developmentPlan = trim((string)($_POST['development_plan'] ?? ''));
            $targetScoreRaw = trim((string)($_POST['target_score'] ?? ''));
            $targetDateRaw = trim((string)($_POST['target_date'] ?? ''));

            $targetScore = null;
            if ($targetScoreRaw !== '' && is_numeric($targetScoreRaw)) {
                $targetScore = (float)$targetScoreRaw;
            }

            $targetDate = null;
            if ($targetDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDateRaw)) {
                $targetDate = $targetDateRaw;
            }

            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET development_plan = ?,
                     target_score = ?,
                     target_date = ?,
                     idp_status = CASE WHEN idp_status = 'requested' THEN 'requested' ELSE 'under_review' END
                 WHERE id = ?"
            );
            $stmt->execute([$developmentPlan, $targetScore, $targetDate, $idpId]);

            header('Location: individual_development_plans.php?ok=updated');
            exit;
        }

        if ($action === 'delete_idp' && $idpId > 0) {
            $stmt = $pdo->prepare("DELETE FROM individual_development_plans WHERE id = ?");
            $stmt->execute([$idpId]);

            header('Location: individual_development_plans.php?ok=deleted');
            exit;
        }

        if ($action === 'cancel_under_review' && $idpId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET idp_status = 'cancelled'
                 WHERE id = ? AND idp_status = 'under_review'"
            );
            $stmt->execute([$idpId]);

            header('Location: individual_development_plans.php?ok=cancelled');
            exit;
        }

        if ($action === 'request_training' && $idpId > 0) {
            $returnBase = $returnBasePost;
            try {
                $pdo->beginTransaction();

                $stmtFetch = $pdo->prepare(
                    "SELECT *
                     FROM individual_development_plans
                     WHERE id = ? AND idp_status = 'approved'
                     LIMIT 1
                     FOR UPDATE"
                );
                $stmtFetch->execute([$idpId]);
                $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $pdo->rollBack();
                    header('Location: ' . addQueryParams($returnBase, ['err' => 'invalid']));
                    exit;
                }

                $now = (new DateTime())->format('Y-m-d H:i:s');
                $deliveryMode = (string)($row['delivery_mode'] ?? 'Onsite');

                $learningRequestedAt = $row['learning_requested_at'];
                $trainingRequestedAt = $row['training_requested_at'];

                if ($deliveryMode === 'Online' || $deliveryMode === 'Hybrid') {
                    $learningRequestedAt = $now;
                }
                if ($deliveryMode === 'Onsite' || $deliveryMode === 'Hybrid') {
                    $trainingRequestedAt = $now;
                }

                ensureRequestedIdpsRepositoryTable($pdo);
                $colSet = getTableColumnSet($pdo, 'requested_idps_repository');
                if (empty($colSet)) {
                    throw new RuntimeException('requested_idps_repository missing.');
                }

                $insertCols = [];
                $insertVals = [];
                $insertParams = [];

                $add = static function (string $col, $val) use (&$insertCols, &$insertVals, &$insertParams, $colSet): void {
                    if (!isset($colSet[$col])) return;
                    $insertCols[] = $col;
                    $insertVals[] = '?';
                    $insertParams[] = $val;
                };

                $add('id', (int)($row['id'] ?? 0));
                $add('employee_id', (string)($row['employee_id'] ?? ''));
                $add('employee_name', (string)($row['employee_name'] ?? ''));
                $add('position', (string)($row['position'] ?? ''));
                $add('department', (string)($row['department'] ?? ''));
                $add('competency', $row['competency'] ?? 0);
                $add('succession_status', (string)($row['succession_status'] ?? ''));
                $add('development_plan', (string)($row['development_plan'] ?? ''));
                $add('target_score', $row['target_score'] ?? null);
                $add('target_date', $row['target_date'] ?? null);
                $add('delivery_mode', $deliveryMode);
                $add('requested_training_type', $row['requested_training_type'] ?? null);
                $add('requested_training_mode', $row['requested_training_mode'] ?? null);
                $add('requested_start_datetime', $row['requested_start_datetime'] ?? null);
                $add('requested_end_datetime', $row['requested_end_datetime'] ?? null);
                $add('idp_status', 'requested');
                $add('training_requested_at', $trainingRequestedAt);
                $add('learning_requested_at', $learningRequestedAt);
                $add('created_at', $row['created_at'] ?? null);
                $add('updated_at', $now);

                if (empty($insertCols)) {
                    throw new RuntimeException('No columns available for requested_idps_repository insert.');
                }
                if (isset($colSet['employee_id']) && trim((string)($row['employee_id'] ?? '')) === '') {
                    throw new RuntimeException('Missing employee_id.');
                }

                $updateClauses = [];
                foreach ($insertCols as $c) {
                    if ($c === 'id' || $c === 'created_at') continue;
                    if ($c === 'idp_status') {
                        $updateClauses[] = "idp_status = 'requested'";
                    } else {
                        $updateClauses[] = "{$c} = VALUES({$c})";
                    }
                }

                $sql = "INSERT INTO requested_idps_repository (" . implode(',', $insertCols) . ")
                        VALUES (" . implode(',', $insertVals) . ")";
                if (!empty($updateClauses)) {
                    $sql .= " ON DUPLICATE KEY UPDATE " . implode(',', $updateClauses);
                }

                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute($insertParams);

                $stmtUpd = $pdo->prepare(
                    "UPDATE individual_development_plans
                     SET idp_status = 'requested',
                         training_requested_at = ?,
                         learning_requested_at = ?
                     WHERE id = ?"
                );
                $stmtUpd->execute([$trainingRequestedAt, $learningRequestedAt, $idpId]);

                $pdo->commit();
                header('Location: ' . addQueryParams($returnBase, ['ok' => 'requested']));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('IDP repo request_training error: ' . $e->getMessage());
                $code = classifyDbError($e);
                header('Location: ' . addQueryParams($returnBase, ['err' => $code, 'err_msg' => friendlyErrMsg($code)]));
                exit;
            }
        }

        header('Location: individual_development_plans.php?err=invalid');
        exit;
    } catch (Throwable $e) {
        error_log('IDP repo action error: ' . $e->getMessage());
        $code = classifyDbError($e);
        header('Location: ' . addQueryParams($returnBasePost, ['err' => $code, 'err_msg' => friendlyErrMsg($code)]));
        exit;
    }
}

$departments = [];
$roles = [];
try {
    $departments = $pdo->query("SELECT DISTINCT department FROM individual_development_plans WHERE department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
    $roles = $pdo->query("SELECT DISTINCT position FROM individual_development_plans WHERE position IS NOT NULL AND position <> '' ORDER BY position ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $departments = [];
    $roles = [];
}

$where = [];
$params = [$period];
if ($selectedDepartment !== '' && strtolower($selectedDepartment) !== 'all') {
    $where[] = 'idp.department = ?';
    $params[] = $selectedDepartment;
}
if ($selectedRole !== '' && strtolower($selectedRole) !== 'all') {
    $where[] = 'idp.position = ?';
    $params[] = $selectedRole;
}

$sql =
    "SELECT idp.id,
            idp.employee_id,
            idp.employee_name,
            idp.position,
            idp.department,
            COALESCE(gs.competency, 0) AS competency,
            idp.succession_status,
            idp.development_plan,
            idp.target_score,
            idp.target_date,
            idp.idp_status,
            CASE WHEN req.id IS NULL THEN 0 ELSE 1 END AS is_requested,
            idp.training_requested_at,
            idp.created_at,
            idp.updated_at
     FROM individual_development_plans idp
     LEFT JOIN requested_idps_repository req
       ON req.employee_id = idp.employee_id
      AND req.idp_status = 'requested'
     LEFT JOIN (
         SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
         FROM employee_kpi_scores s2
         WHERE s2.evaluation_period = ?
         GROUP BY s2.employee_id
     ) gs ON gs.employee_id = idp.employee_id";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY idp.updated_at DESC, idp.created_at DESC";

$stmt = $pdo->prepare(
    $sql
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statusOrder = ['under_review', 'requested', 'approved', 'for_compliance', 'rejected', 'cancelled'];
$statusCounts = array_fill_keys($statusOrder, 0);
foreach ($rows as $r) {
    $s = (string)($r['idp_status'] ?? '');
    $isRequested = ((int)($r['is_requested'] ?? 0) === 1) || ($s === 'requested');
    if ($isRequested && array_key_exists('requested', $statusCounts)) {
        $statusCounts['requested']++;
        continue;
    }
    if ($s !== '' && array_key_exists($s, $statusCounts)) {
        $statusCounts[$s]++;
    }
}

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function idpStatusLabel($status)
{
    $status = (string)$status;
    $status = str_replace('_', ' ', $status);
    return ucwords($status);
}

function idpBadgeClass($status)
{
    $status = (string)$status;
    switch ($status) {
        case 'requested':
            return 'badge-accent';
        case 'approved':
            return 'badge-success';
        case 'on_hold':
            return 'badge-warning';
        case 'for_compliance':
            return 'badge-info';
        case 'cancelled':
            return 'badge-neutral';
        case 'rejected':
            return 'badge-error';
        case 'under_review':
        default:
            return 'badge-primary';
    }
}
require('../../partials/header.php');
?>

<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php
        // Use relative path or absolute path based on your directory structure
        include '../../USM/sidebarr.php';
        ?>
        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../../USM/navbar.php'; ?>


            <div class="max-w-7xl mx-auto p-6 space-y-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">IDP Repository</h1>
                        <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo count($rows); ?></span></div>
                    </div>
                    <div class="hr2-summary-card rounded-lg px-4 py-3">
                        <div class="text-sm text-gray-500">Total IDPs</div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo count($rows); ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <?php foreach ($statusOrder as $s): ?>
                        <div class="hr2-summary-card rounded-lg p-4">
                            <div class="text-sm text-gray-500"><?php echo h(idpStatusLabel($s)); ?></div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo (int)($statusCounts[$s] ?? 0); ?></div>
                            <div class="text-xs text-gray-400">IDPs</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card bg-base-100 shadow-md">
                    <div class="card-body">
                        <div class="flex flex-col md:flex-row md:items-end gap-3">
                            <div class="flex-1">
                                <label class="label"><span class="label-text">Department</span></label>
                                <select id="filter_department" class="select select-bordered w-full">
                                    <option value="all">All</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo h($d); ?>" <?php echo ($selectedDepartment === $d ? 'selected' : ''); ?>><?php echo h($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="label"><span class="label-text">Role</span></label>
                                <select id="filter_role" class="select select-bordered w-full">
                                    <option value="all">All</option>
                                    <?php foreach ($roles as $rOpt): ?>
                                        <option value="<?php echo h($rOpt); ?>" <?php echo ($selectedRole === $rOpt ? 'selected' : ''); ?>><?php echo h($rOpt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-outline" onclick="applyIdpRepoFilters()">Apply</button>
                                <button type="button" class="btn btn-outline" onclick="clearIdpRepoFilters()">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (count($rows) === 0): ?>
                    <div class="card bg-base-100 shadow-md">
                        <div class="card-body">
                            <div class="opacity-70">No IDPs created yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $status = (string)($r['idp_status'] ?? 'under_review');
                            $isRequested = ((int)($r['is_requested'] ?? 0) === 1) || ($status === 'requested');
                            ?>
                            <div class="card bg-base-100 shadow-md">
                                <div class="card-body">
                                    <div class="flex justify-between items-start">
                                        <h3 class="card-title"><?php echo h($r['employee_name']); ?></h3>
                                        <?php if ($isRequested): ?>
                                            <span class="badge badge-accent badge-outline whitespace-normal text-right">Requested</span>
                                        <?php else: ?>
                                            <div class="badge badge-sm <?php echo h(idpBadgeClass($status)); ?>"><?php echo h(idpStatusLabel($status)); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex flex-wrap gap-2 my-2">
                                        <div class="badge badge-outline"><?php echo h($r['department']); ?></div>
                                        <div class="badge badge-outline"><?php echo h($r['position']); ?></div>
                                        <div class="badge badge-outline"><?php echo number_format((float)($r['competency'] ?? 0), 1); ?>% Skills</div>
                                        <div class="badge badge-outline"><?php echo h($r['succession_status']); ?></div>
                                    </div>

                                    <p class="text-sm text-gray-500">Employee ID: <?php echo h($r['employee_id']); ?></p>
                                    <p class="text-sm text-gray-500">Date Added: <?php echo h(date('Y-m-d', strtotime((string)($r['created_at'] ?? 'now')))); ?></p>

                                    <div class="card-actions justify-end mt-4">
                                        <button type="button" class="btn btn-outline btn-sm" data-view-idp="1" data-idp='<?php echo h(json_encode($r)); ?>'>View</button>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <input type="checkbox" id="idp_view_modal" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box w-11/12 max-w-6xl max-h-[85vh] overflow-y-auto">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-bold text-lg">IDP Details</h3>
                        <span id="idp_view_status_badge" class="badge badge-sm"></span>
                    </div>

                    <div class="mt-4" id="idp_view_body"></div>

                    <div class="mt-4">
                        <div class="text-sm font-semibold">Development Plan</div>
                        <div id="idp_view_plan" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                            <div id="idp_view_plan_bubbles" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="text-sm font-semibold">Skill Gap Analysis</div>
                        <div id="idp_view_gap" class="max-h-96 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                            <div class="text-sm opacity-70">Analyze to compute gaps</div>
                        </div>
                    </div>

                    <div class="modal-action flex flex-wrap justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="idp_view_edit_btn" class="btn btn-warning btn-sm hidden" data-edit-idp="1" data-idp="">Edit</button>

                            <form method="post" id="idp_view_form_request_training" class="inline hidden" data-swal-confirm="Request this IDP?">
                                <input type="hidden" name="action" value="request_training" />
                                <input type="hidden" name="idp_id" id="idp_view_id_request_training" value="" />
                                <button type="submit" class="btn btn-success btn-sm">Request</button>
                            </form>

                            <form method="post" id="idp_view_form_cancel" class="inline hidden" data-swal-confirm="Cancel this Under Review IDP?">
                                <input type="hidden" name="action" value="cancel_under_review" />
                                <input type="hidden" name="idp_id" id="idp_view_id_cancel" value="" />
                                <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                            </form>

                            <form method="post" id="idp_view_form_delete" class="inline hidden" data-swal-confirm="Delete this IDP? This cannot be undone.">
                                <input type="hidden" name="action" value="delete_idp" />
                                <input type="hidden" name="idp_id" id="idp_view_id_delete" value="" />
                                <button type="submit" class="btn btn-error btn-sm">Delete</button>
                            </form>
                        </div>

                        <label for="idp_view_modal" class="btn">Close</label>
                    </div>
                </div>
                <label class="modal-backdrop" for="idp_view_modal">Close</label>
            </div>

            <input type="checkbox" id="idp_edit_modal" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <h3 class="font-bold text-lg">Edit IDP</h3>
                    <form method="post" id="idp_edit_form" class="mt-4" data-swal-confirm="Save changes? Status will be set to Under Review.">
                        <input type="hidden" name="action" value="edit_idp" />
                        <input type="hidden" name="idp_id" id="idp_edit_id" value="" />

                        <div class="form-control mb-3">
                            <label class="label"><span class="label-text">Development Plan</span></label>
                            <textarea class="textarea textarea-bordered" rows="6" name="development_plan" id="idp_edit_plan"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Target Score (%)</span></label>
                                <input type="number" step="0.1" min="0" max="100" class="input input-bordered" name="target_score" id="idp_edit_target_score" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Target Date</span></label>
                                <input type="date" class="input input-bordered" name="target_date" id="idp_edit_target_date" />
                            </div>
                        </div>

                        <div class="modal-action">
                            <label for="idp_edit_modal" class="btn btn-ghost">Close</label>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
                <label class="modal-backdrop" for="idp_edit_modal">Close</label>
            </div>

            <script>
                (function() {
                    var ok = <?php echo json_encode($flashOk); ?>;
                    var err = <?php echo json_encode($flashErr); ?>;
                    var errMsg = <?php echo json_encode($flashErrMsg); ?>;

                    var okMap = {
                        created: 'IDP created successfully.',
                        updated: 'IDP updated successfully.',
                        deleted: 'IDP deleted successfully.',
                        cancelled: 'IDP cancelled.',
                        training_requested: 'Training request sent.',
                        learning_requested: 'Learning request sent.',
                        requested: 'IDP requested.'
                    };

                    if (ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: okMap[ok] || 'Action completed.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    }

                    if (err) {
                        var errMap = {
                            db_table_missing: 'Request repository table is missing in the database.',
                            db_permission_denied: 'Database permission denied while requesting.',
                            db_fk_error: 'Database constraint error. Employee record may be missing.',
                            db_schema_mismatch: 'Database schema mismatch. Columns differ from expected.',
                            db_duplicate: 'This request already exists.',
                            invalid: 'Invalid request.',
                            failed: 'Request failed due to a server/database issue.'
                        };
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errMsg || errMap[err] || 'Something went wrong.'
                        });
                    }

                    window.applyIdpRepoFilters = function() {
                        var dept = document.getElementById('filter_department') ? document.getElementById('filter_department').value : 'all';
                        var role = document.getElementById('filter_role') ? document.getElementById('filter_role').value : 'all';
                        var qs = new URLSearchParams(window.location.search);
                        qs.set('department', dept || 'all');
                        qs.set('role', role || 'all');
                        window.location.search = qs.toString();
                    };

                    window.clearIdpRepoFilters = function() {
                        var qs = new URLSearchParams(window.location.search);
                        qs.delete('department');
                        qs.delete('role');
                        window.location.search = qs.toString();
                    };

                    document.querySelectorAll('form[data-swal-confirm]').forEach(function(form) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            var msg = form.getAttribute('data-swal-confirm') || 'Are you sure?';
                            Swal.fire({
                                icon: 'warning',
                                title: 'Please confirm',
                                text: msg,
                                showCancelButton: true,
                                confirmButtonText: 'Yes',
                                cancelButtonText: 'No'
                            }).then(function(res) {
                                if (res.isConfirmed) {
                                    form.submit();
                                }
                            });
                        });
                    });

                    var modalToggle = document.getElementById('idp_edit_modal');
                    var editId = document.getElementById('idp_edit_id');
                    var editPlan = document.getElementById('idp_edit_plan');
                    var editScore = document.getElementById('idp_edit_target_score');
                    var editDate = document.getElementById('idp_edit_target_date');

                    var viewToggle = document.getElementById('idp_view_modal');
                    var viewBadge = document.getElementById('idp_view_status_badge');
                    var viewBody = document.getElementById('idp_view_body');
                    var viewPlan = document.getElementById('idp_view_plan');
                    var viewGap = document.getElementById('idp_view_gap');
                    var viewEditBtn = document.getElementById('idp_view_edit_btn');
                    var formReq = document.getElementById('idp_view_form_request_training');
                    var formCancel = document.getElementById('idp_view_form_cancel');
                    var formDelete = document.getElementById('idp_view_form_delete');
                    var idReq = document.getElementById('idp_view_id_request_training');
                    var idCancel = document.getElementById('idp_view_id_cancel');
                    var idDelete = document.getElementById('idp_view_id_delete');

                    function esc(s) {
                        return String(s || '').replace(/[&<>"']/g, function(c) {
                            return ({
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '"': '&quot;',
                                '\'': '&#39;'
                            } [c]) || c;
                        });
                    }

                    function loadGapAnalysis(employeeId) {
                        if (viewGap) {
                            viewGap.innerHTML = '<div class="text-sm opacity-70">Loading analysis...</div>';
                        }
                        var url = '../../COMPETENCY/criticalgaps/get_employee_details.php?id=' + encodeURIComponent(String(employeeId || ''));
                        fetch(url).then(function(res) {
                            return res.json();
                        }).then(function(data) {
                            if (!viewGap) return;
                            if (!data || data.error) {
                                viewGap.innerHTML = '<div class="text-sm opacity-70">Failed to load analysis.</div>';
                                return;
                            }
                            var analysis = data.analysis || {};
                            var overall = analysis.overall || {};
                            var computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                            var overallPct = Number(overall.pct || 0);
                            var status = String(overall.status || 'Retrain');
                            var head = document.createElement('div');
                            head.className = 'flex items-center justify-between mb-3';
                            head.innerHTML = '<div><div class="text-xs opacity-70">Overall Competency</div><div class="text-xl font-bold">' + (Number.isFinite(overallPct) ? overallPct.toFixed(1) : '0.0') + '%</div></div>' +
                                '<div class="text-right"><div class="text-xs opacity-70">Status</div><div><span class="badge">' + esc(status) + '</span></div></div>';
                            var tbl = document.createElement('table');
                            tbl.className = 'table table-sm w-full';
                            tbl.innerHTML = '<thead><tr><th>KPI</th><th class="text-right">Actual</th><th class="text-right">Required</th><th class="text-right">Gap</th></tr></thead>';
                            var tb = document.createElement('tbody');
                            if (!computed.length) {
                                tb.innerHTML = '<tr><td colspan="4" class="py-6 text-center opacity-70">No analysis available</td></tr>';
                            } else {
                                computed.forEach(function(r) {
                                    var kpiPct = Number(r.kpi_pct || 0);
                                    var reqPct = Number(r.required_pct || 0);
                                    var gapPct = Number(r.gap_pct || 0);
                                    var tr = document.createElement('tr');
                                    tr.innerHTML =
                                        '<td>' + esc(String(r.kpi_name || '')) + '</td>' +
                                        '<td class="text-right font-semibold">' + (Number.isFinite(kpiPct) ? kpiPct.toFixed(1) : '0.0') + '%</td>' +
                                        '<td class="text-right font-semibold">' + (Number.isFinite(reqPct) ? reqPct.toFixed(1) : '0.0') + '%</td>' +
                                        '<td class="text-right"><span class="badge ' + (gapPct > 0 ? 'badge-error' : 'badge-success') + '">' + (Number.isFinite(gapPct) ? gapPct.toFixed(1) : '0.0') + '%</span></td>';
                                    tb.appendChild(tr);
                                });
                            }
                            tbl.appendChild(tb);
                            viewGap.innerHTML = '';
                            viewGap.appendChild(head);
                            viewGap.appendChild(tbl);
                        }).catch(function() {
                            if (viewGap) viewGap.innerHTML = '<div class="text-sm opacity-70">Failed to load analysis.</div>';
                        });
                    }

                    function statusLabel(status) {
                        status = String(status || '');
                        return status.replace(/_/g, ' ').replace(/\b\w/g, function(m) {
                            return m.toUpperCase();
                        });
                    }

                    function badgeClass(status) {
                        status = String(status || '');
                        switch (status) {
                            case 'approved':
                                return 'badge-success';
                            case 'on_hold':
                                return 'badge-warning';
                            case 'for_compliance':
                                return 'badge-info';
                            case 'cancelled':
                                return 'badge-neutral';
                            case 'rejected':
                                return 'badge-error';
                            case 'under_review':
                            default:
                                return 'badge-primary';
                        }
                    }

                    function box(label, value) {
                        var el = document.createElement('div');
                        el.className = 'rounded-lg bg-base-200 p-3';
                        el.innerHTML = '<div class="text-xs opacity-70">' + label + '</div>' +
                            '<div class="font-semibold whitespace-pre-line">' + value + '</div>';
                        return el;
                    }

                    function renderPlanBubbles(containerEl, planText) {
                        if (!containerEl) return;
                        var raw = String(planText || '');
                        var items = raw
                            .split(/\r?\n/)
                            .map(function(l) {
                                return String(l || '').trim();
                            })
                            .filter(function(l) {
                                return l !== '';
                            })
                            .filter(function(l) {
                                return l.indexOf('- ') === 0;
                            })
                            .map(function(l) {
                                return l.slice(2).trim();
                            })
                            .filter(function(l) {
                                return l !== '';
                            });

                        if (!items.length) {
                            items = raw
                                .split(/\r?\n/)
                                .map(function(l) {
                                    return String(l || '').trim();
                                })
                                .filter(function(l) {
                                    return l !== '';
                                });
                        }

                        containerEl.innerHTML = '';
                        if (!items.length) {
                            var empty = document.createElement('div');
                            empty.className = 'text-sm opacity-70';
                            empty.textContent = 'â€”';
                            containerEl.appendChild(empty);
                            return;
                        }

                        items.forEach(function(t) {
                            var s = document.createElement('span');
                            s.className = 'badge badge-outline whitespace-normal h-auto py-3';
                            s.textContent = t;
                            containerEl.appendChild(s);
                        });
                    }

                    function show(el, on) {
                        if (!el) return;
                        if (on) {
                            el.classList.remove('hidden');
                        } else {
                            el.classList.add('hidden');
                        }
                    }

                    document.querySelectorAll('[data-edit-idp="1"]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var raw = btn.getAttribute('data-idp') || '';
                            try {
                                var r = JSON.parse(raw);
                                var idpId = String(r.id || '');
                                var empId = String(r.employee_id || '');
                                if (!idpId || !empId) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Missing IDP info.'
                                    });
                                    return;
                                }

                                window.location.href = 'individual_dev_plan.php?employee_id=' + encodeURIComponent(empId) + '&idp_id=' + encodeURIComponent(idpId);
                            } catch (e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to open editor.'
                                });
                            }
                        });
                    });

                    document.querySelectorAll('[data-view-idp="1"]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var raw = btn.getAttribute('data-idp') || '';
                            try {
                                var r = JSON.parse(raw);

                                var id = String(r.id || '');
                                var status = String(r.idp_status || 'under_review');
                                var score = r.target_score === null || typeof r.target_score === 'undefined' ? 'â€”' : String(r.target_score);

                                if (viewBody) {
                                    var empName = String(r.employee_name || 'â€”');
                                    var empId = String(r.employee_id || 'â€”');
                                    var dept = String(r.department || 'â€”');
                                    var position = String(r.position || 'â€”');
                                    var successionStatus = String(r.succession_status || 'â€”');
                                    var targetDate = String(r.target_date || 'â€”');
                                    var trainingRequestedAt = String(r.training_requested_at || 'â€”');
                                    var createdAt = String(r.created_at || 'â€”');
                                    var updatedAt = String(r.updated_at || 'â€”');

                                    var compPct = Number(r.competency || 0);
                                    var compFmt = Number.isFinite(compPct) ? compPct.toFixed(1) : '0.0';

                                    var initials = empName
                                        .split(/\s+/)
                                        .filter(function(p) {
                                            return p;
                                        })
                                        .slice(0, 2)
                                        .map(function(p) {
                                            return p.charAt(0).toUpperCase();
                                        })
                                        .join('');
                                    if (!initials) initials = 'IDP';

                                    viewBody.innerHTML =
                                        '<div class="rounded-xl bg-base-200 border border-base-300 p-4">' +
                                        '<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">' +
                                        '<div class="flex items-center gap-4">' +
                                        '<div class="avatar placeholder">' +
                                        '<div class="bg-base-300 text-base-content rounded-full w-14">' +
                                        '<span class="font-bold">' + esc(initials) + '</span>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div>' +
                                        '<div class="text-xl font-bold leading-tight">' + esc(empName) + '</div>' +
                                        '<div class="text-sm text-base-content/70">' + esc(empId) + '</div>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="flex flex-wrap items-center justify-start lg:justify-end gap-2">' +
                                        '<span class="badge badge-outline">' + esc(dept) + '</span>' +
                                        '<span class="badge badge-outline">' + esc(position) + '</span>' +
                                        '<span class="badge badge-outline">' + esc(successionStatus) + '</span>' +
                                        '<span class="badge badge-outline">' + esc(compFmt) + '% Skills</span>' +
                                        '</div>' +
                                        '</div>' +

                                        '<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">IDP STATUS</div>' +
                                        '<div class="font-semibold mt-1">' + esc(statusLabel(status)) + '</div>' +
                                        '</div>' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">TARGET SCORE</div>' +
                                        '<div class="font-semibold mt-1">' + esc(score) + '</div>' +
                                        '</div>' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">TARGET DATE</div>' +
                                        '<div class="font-semibold mt-1">' + esc(targetDate) + '</div>' +
                                        '</div>' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">TRAINING REQUESTED</div>' +
                                        '<div class="font-semibold mt-1">' + esc(trainingRequestedAt) + '</div>' +
                                        '</div>' +
                                        '</div>' +

                                        '<div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">CREATED</div>' +
                                        '<div class="font-semibold mt-1">' + esc(createdAt) + '</div>' +
                                        '</div>' +
                                        '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                        '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">UPDATED</div>' +
                                        '<div class="font-semibold mt-1">' + esc(updatedAt) + '</div>' +
                                        '</div>' +
                                        '</div>' +
                                        '</div>';
                                }

                                if (viewPlan) {
                                    var bubbles = document.getElementById('idp_view_plan_bubbles');
                                    renderPlanBubbles(bubbles, String(r.development_plan || ''));
                                }

                                loadGapAnalysis(String(r.employee_id || ''));

                                if (viewBadge) {
                                    viewBadge.className = 'badge badge-sm ' + badgeClass(status);
                                    viewBadge.textContent = statusLabel(status);
                                }

                                if (idReq) idReq.value = id;
                                if (idCancel) idCancel.value = id;
                                if (idDelete) idDelete.value = id;

                                show(formReq, status === 'approved');
                                show(formCancel, status === 'under_review');
                                show(formDelete, status === 'for_compliance' || status === 'cancelled' || status === 'rejected');

                                if (viewEditBtn) {
                                    viewEditBtn.setAttribute('data-idp', raw);
                                    show(viewEditBtn, status === 'under_review' || status === 'approved');
                                    viewEditBtn.classList.remove('btn-info', 'btn-neutral');
                                    viewEditBtn.classList.add('btn-warning');
                                }

                                if (viewToggle) {
                                    viewToggle.checked = true;
                                }
                            } catch (e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to open viewer.'
                                });
                            }
                        });
                    });
                })();
            </script>
        </div>
    </div>
    <?php require('../../partials/footer.php') ?>
