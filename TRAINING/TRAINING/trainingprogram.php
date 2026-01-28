<?php

require_once __DIR__ . '/db.php';

$getOwnerKey = function(): string {
    $candidates = [
        'user_id' => 'user:',
        'employee_id' => 'emp:',
        'employee_no' => 'empno:',
        'username' => 'user:',
        'email' => 'user:',
    ];
    foreach ($candidates as $k => $prefix) {
        if (isset($_SESSION[$k]) && trim((string)$_SESSION[$k]) !== '') {
            return $prefix . trim((string)$_SESSION[$k]);
        }
    }
    return 'sess:' . session_id();
};

$ownerKey = $getOwnerKey();

$ensureTrainingProgramsTable = function(mysqli $conn): void {
    try {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS training_programs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                training_title VARCHAR(255) NOT NULL,
                training_type VARCHAR(50) NOT NULL,
                training_mode VARCHAR(20) NOT NULL DEFAULT 'Onsite',
                requested_by VARCHAR(100) NULL,
                description TEXT NOT NULL,
                target_audience VARCHAR(100) NOT NULL,
                department_id INT NULL,
                sub_department VARCHAR(150) NULL,
                target_role VARCHAR(100) NULL,
                mentor_id INT NULL,
                category VARCHAR(100) NOT NULL,
                participants_needed INT NOT NULL,
                max_participants INT NULL,
                training_level VARCHAR(50) NULL,
                training_objectives_json TEXT NULL,
                training_objectives_other TEXT NULL,
                start_datetime DATETIME NOT NULL,
                end_datetime DATETIME NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'Under Review',
                status_reason TEXT NULL,
                need_budget TINYINT(1) NOT NULL DEFAULT 0,
                need_items TINYINT(1) NOT NULL DEFAULT 0,
                need_facility TINYINT(1) NOT NULL DEFAULT 0,
                submission_no INT NOT NULL DEFAULT 1,
                financial_budget_amount DECIMAL(12,2) NULL,
                financial_details_json TEXT NULL,
                logistics_items_requested TEXT NULL,
                logistics_details_json TEXT NULL,
                admin_facility_details TEXT NULL,
                admin_details_json TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
    }
};

$ensureTrainingProgramsTable($conn);

$ensureReviewSchema = function(mysqli $conn): void {
    $tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
        $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    };

    try {
        if (!$tableHasColumn($conn, 'training_programs', 'status_reason')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN status_reason TEXT NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS training_program_status_logs (id INT AUTO_INCREMENT PRIMARY KEY, program_id INT NOT NULL, old_status VARCHAR(50) NULL, new_status VARCHAR(50) NOT NULL, reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_tpl_program (program_id), INDEX idx_tpl_created (created_at), CONSTRAINT fk_tpl_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
};

$ensureReviewSchema($conn);

$ensureDepartmentRequestSchema = function(mysqli $conn): void {
    $tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
        $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    };

    try {
        $conn->query("ALTER TABLE financial_requests MODIFY status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending'");
    } catch (Throwable $e) {
    }
    try {
        $conn->query("ALTER TABLE logistics_requests MODIFY status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending'");
    } catch (Throwable $e) {
    }
    try {
        $conn->query("ALTER TABLE admin_requests MODIFY status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending'");
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, 'training_programs', 'submission_no')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'requested_by')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN requested_by VARCHAR(100) NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'sub_department')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN sub_department VARCHAR(150) NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'mentor_id')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN mentor_id INT NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'financial_budget_amount')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN financial_budget_amount DECIMAL(12,2) NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'financial_details_json')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN financial_details_json TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'logistics_items_requested')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN logistics_items_requested TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'logistics_details_json')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN logistics_details_json TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'admin_facility_details')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN admin_facility_details TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'training_programs', 'admin_details_json')) {
            $conn->query("ALTER TABLE training_programs ADD COLUMN admin_details_json TEXT NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, 'financial_requests', 'submission_no')) {
            $conn->query("ALTER TABLE financial_requests ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
        if (!$tableHasColumn($conn, 'financial_requests', 'budget_amount')) {
            $conn->query("ALTER TABLE financial_requests ADD COLUMN budget_amount DECIMAL(12,2) NULL");
        }
        if (!$tableHasColumn($conn, 'financial_requests', 'details_json')) {
            $conn->query("ALTER TABLE financial_requests ADD COLUMN details_json TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'financial_requests', 'rejection_reason')) {
            $conn->query("ALTER TABLE financial_requests ADD COLUMN rejection_reason TEXT NULL");
        }
    } catch (Throwable $e) {
    }
    try {
        if (!$tableHasColumn($conn, 'logistics_requests', 'submission_no')) {
            $conn->query("ALTER TABLE logistics_requests ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
        if (!$tableHasColumn($conn, 'logistics_requests', 'items_requested')) {
            $conn->query("ALTER TABLE logistics_requests ADD COLUMN items_requested TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'logistics_requests', 'details_json')) {
            $conn->query("ALTER TABLE logistics_requests ADD COLUMN details_json TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'logistics_requests', 'rejection_reason')) {
            $conn->query("ALTER TABLE logistics_requests ADD COLUMN rejection_reason TEXT NULL");
        }
    } catch (Throwable $e) {
    }
    try {
        if (!$tableHasColumn($conn, 'admin_requests', 'submission_no')) {
            $conn->query("ALTER TABLE admin_requests ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
        if (!$tableHasColumn($conn, 'admin_requests', 'facility_details')) {
            $conn->query("ALTER TABLE admin_requests ADD COLUMN facility_details TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'admin_requests', 'details_json')) {
            $conn->query("ALTER TABLE admin_requests ADD COLUMN details_json TEXT NULL");
        }
        if (!$tableHasColumn($conn, 'admin_requests', 'rejection_reason')) {
            $conn->query("ALTER TABLE admin_requests ADD COLUMN rejection_reason TEXT NULL");
        }
    } catch (Throwable $e) {
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS department_request_status_logs (id INT AUTO_INCREMENT PRIMARY KEY, request_type ENUM('financial','logistics','admin') NOT NULL, request_id INT NOT NULL, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, old_status VARCHAR(50) NULL, new_status VARCHAR(50) NOT NULL, reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_drl_program (program_id), INDEX idx_drl_type (request_type), INDEX idx_drl_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    try {
        if (!$tableHasColumn($conn, 'department_request_status_logs', 'submission_no')) {
            $conn->query("ALTER TABLE department_request_status_logs ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
    } catch (Throwable $e) {
    }
};

$ensureDepartmentRequestSchema($conn);

if (isset($_GET['action']) && $_GET['action'] === 'list_programs') {
    header('Content-Type: application/json; charset=utf-8');

    $result = $conn->query("SELECT * FROM training_programs ORDER BY created_at DESC");
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    echo json_encode(['success' => true, 'programs' => $programs]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_program') {
    header('Content-Type: application/json; charset=utf-8');

    $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
    if ($programId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing program_id']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $programId);
        $stmt->execute();
        $program = $stmt->get_result()->fetch_assoc();
        if (!$program) {
            echo json_encode(['success' => false, 'message' => 'Program not found']);
            exit;
        }
        echo json_encode(['success' => true, 'program' => $program]);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load program']);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_program_requests') {
    header('Content-Type: application/json; charset=utf-8');

    $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
    if ($programId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing program_id']);
        exit;
    }

    $submissionNo = 1;
    try {
        $stmtSub = $conn->prepare("SELECT submission_no FROM training_programs WHERE id = ?");
        $stmtSub->bind_param('i', $programId);
        $stmtSub->execute();
        $rowSub = $stmtSub->get_result()->fetch_assoc();
        if ($rowSub && isset($rowSub['submission_no'])) $submissionNo = (int)$rowSub['submission_no'];
        if ($submissionNo <= 0) $submissionNo = 1;
    } catch (Throwable $e) {
        $submissionNo = 1;
    }

    $getOne = function(mysqli $conn, string $table, int $programId, int $submissionNo): ?array {
        $sql = "SELECT id, status, rejection_reason FROM {$table} WHERE program_id = ? AND submission_no = ? ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $programId, $submissionNo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    };

    $financial = null;
    $logistics = null;
    $admin = null;

    try {
        $financial = $getOne($conn, 'financial_requests', $programId, $submissionNo);
    } catch (Throwable $e) {
        $financial = null;
    }
    try {
        $logistics = $getOne($conn, 'logistics_requests', $programId, $submissionNo);
    } catch (Throwable $e) {
        $logistics = null;
    }
    try {
        $admin = $getOne($conn, 'admin_requests', $programId, $submissionNo);
    } catch (Throwable $e) {
        $admin = null;
    }

    $anyRejected = false;
    foreach ([$financial, $logistics, $admin] as $r) {
        if ($r && isset($r['status']) && (string)$r['status'] === 'Rejected') {
            $anyRejected = true;
        }
    }

    if ($anyRejected) {
        try {
            $stmt = $conn->prepare("UPDATE training_programs SET status = 'ON HOLD' WHERE id = ? AND status = 'Approved'");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }

        try {
            $stmtReqHold = $conn->prepare("UPDATE financial_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programId, $submissionNo);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmtReqHold = $conn->prepare("UPDATE logistics_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programId, $submissionNo);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmtReqHold = $conn->prepare("UPDATE admin_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
            $stmtReqHold->bind_param('ii', $programId, $submissionNo);
            $stmtReqHold->execute();
        } catch (Throwable $e) {
        }
    }

    $programStatus = null;
    try {
        $stmt = $conn->prepare("SELECT status FROM training_programs WHERE id = ?");
        $stmt->bind_param('i', $programId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $programStatus = $row ? (string)($row['status'] ?? '') : null;
    } catch (Throwable $e) {
        $programStatus = null;
    }

    echo json_encode([
        'success' => true,
        'program_status' => $programStatus,
        'submission_no' => $submissionNo,
        'requests' => [
            'financial' => $financial,
            'logistics' => $logistics,
            'admin' => $admin
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_program') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
            $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            return (bool)$stmt->get_result()->fetch_row();
        };

        $trainingTitle = trim($_POST['training_title'] ?? '');
        $trainingType = trim($_POST['training_type'] ?? '');
        $trainingMode = trim($_POST['training_mode'] ?? 'Onsite');
        $requestedBy = trim((string)($_POST['requested_by'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $targetAudience = trim($_POST['target_audience'] ?? '');
        $departmentIdRaw = (string)($_POST['department_id'] ?? '');
        $departmentIdRaw = trim($departmentIdRaw);
        $departmentId = $departmentIdRaw !== '' ? (int)$departmentIdRaw : null;
        $subDepartment = trim((string)($_POST['sub_department'] ?? ''));
        $subDepartment = $subDepartment !== '' ? $subDepartment : null;
        $targetRole = trim($_POST['target_role'] ?? '');
        $targetRole = $targetRole !== '' ? $targetRole : null;

        $mentorIdRaw = trim((string)($_POST['mentor_id'] ?? ''));
        $mentorId = $mentorIdRaw !== '' ? (int)$mentorIdRaw : null;
        if ($mentorId !== null && $mentorId <= 0) $mentorId = null;

        $category = trim($_POST['category'] ?? '');
        $participantsNeeded = (int)($_POST['participants_needed'] ?? 1);
        if ($participantsNeeded <= 0) $participantsNeeded = 1;

        $maxParticipantsRaw = trim((string)($_POST['max_participants'] ?? ''));
        $maxParticipants = $maxParticipantsRaw !== '' ? (int)$maxParticipantsRaw : null;
        if ($maxParticipants !== null && $maxParticipants <= 0) $maxParticipants = null;
        $trainingLevel = trim((string)($_POST['training_level'] ?? ''));
        $trainingLevel = $trainingLevel !== '' ? $trainingLevel : null;
        $trainingObjectivesJson = trim((string)($_POST['training_objectives_json'] ?? ''));
        $trainingObjectivesJson = $trainingObjectivesJson !== '' ? $trainingObjectivesJson : null;
        $trainingObjectivesOther = trim((string)($_POST['training_objectives_other'] ?? ''));
        $trainingObjectivesOther = $trainingObjectivesOther !== '' ? $trainingObjectivesOther : null;

        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime = trim($_POST['end_datetime'] ?? '');
        $status = 'Under Review';

        $needBudget = (int)($_POST['need_budget'] ?? 0);
        $needItems = (int)($_POST['need_items'] ?? 0);
        $needFacility = (int)($_POST['need_facility'] ?? 0);

        $budgetAmount = trim((string)($_POST['budget_amount'] ?? ''));
        $financialDetailsJson = trim((string)($_POST['financial_details_json'] ?? ''));
        $itemsRequested = trim((string)($_POST['items_requested'] ?? ''));
        $logisticsDetailsJson = trim((string)($_POST['logistics_details_json'] ?? ''));
        $facilityDetails = trim((string)($_POST['facility_details'] ?? ''));
        $adminDetailsJson = trim((string)($_POST['admin_details_json'] ?? ''));

        if ($trainingTitle === '' || $trainingType === '' || $description === '' || $targetAudience === '' || $startDatetime === '' || $endDatetime === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        $hasTrainingMode = false;
        try {
            $hasTrainingMode = $tableHasColumn($conn, 'training_programs', 'training_mode');
        } catch (Throwable $e) {
            $hasTrainingMode = false;
        }

        if ($hasTrainingMode) {
            $stmt = $conn->prepare("INSERT INTO training_programs (training_title, training_type, training_mode, description, target_audience, department_id, target_role, mentor_id, category, participants_needed, start_datetime, end_datetime, status, need_budget, need_items, need_facility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'sssssisisisssiii',
                $trainingTitle,
                $trainingType,
                $trainingMode,
                $description,
                $targetAudience,
                $departmentId,
                $targetRole,
                $mentorId,
                $category,
                $participantsNeeded,
                $startDatetime,
                $endDatetime,
                $status,
                $needBudget,
                $needItems,
                $needFacility
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO training_programs (training_title, training_type, description, target_audience, department_id, target_role, mentor_id, category, participants_needed, start_datetime, end_datetime, status, need_budget, need_items, need_facility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'ssssisisisssiii',
                $trainingTitle,
                $trainingType,
                $description,
                $targetAudience,
                $departmentId,
                $targetRole,
                $mentorId,
                $category,
                $participantsNeeded,
                $startDatetime,
                $endDatetime,
                $status,
                $needBudget,
                $needItems,
                $needFacility
            );
        }

        $stmt->execute();
        $programId = (int)$conn->insert_id;

        try {
            $set = [];
            $types = '';
            $vals = [];

            if ($tableHasColumn($conn, 'training_programs', 'requested_by')) {
                $set[] = "requested_by = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)$requestedBy;
            }
            if ($tableHasColumn($conn, 'training_programs', 'sub_department')) {
                $set[] = "sub_department = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($subDepartment ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'max_participants')) {
                $set[] = "max_participants = NULLIF(?, '')";
                $types .= 's';
                $vals[] = $maxParticipants !== null ? (string)$maxParticipants : '';
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_level')) {
                $set[] = "training_level = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingLevel ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_objectives_json')) {
                $set[] = "training_objectives_json = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingObjectivesJson ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_objectives_other')) {
                $set[] = "training_objectives_other = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingObjectivesOther ?? '');
            }

            if (!empty($set)) {
                $sql = "UPDATE training_programs SET " . implode(', ', $set) . " WHERE id = ?";
                $types .= 'i';
                $vals[] = $programId;
                $stmtExtra = $conn->prepare($sql);
                $stmtExtra->bind_param($types, ...$vals);
                $stmtExtra->execute();
            }
        } catch (Throwable $e) {
        }

        try {
            $stmtReqPayload = $conn->prepare("UPDATE training_programs SET financial_budget_amount = NULLIF(?, ''), financial_details_json = NULLIF(?, ''), logistics_items_requested = NULLIF(?, ''), logistics_details_json = NULLIF(?, ''), admin_facility_details = NULLIF(?, ''), admin_details_json = NULLIF(?, '') WHERE id = ?");
            $stmtReqPayload->bind_param('ssssssi', $budgetAmount, $financialDetailsJson, $itemsRequested, $logisticsDetailsJson, $facilityDetails, $adminDetailsJson, $programId);
            $stmtReqPayload->execute();
        } catch (Throwable $e) {
        }

        $stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->bind_param('i', $programId);
        $stmt->execute();
        $program = $stmt->get_result()->fetch_assoc();

        echo json_encode(['success' => true, 'program' => $program, 'request_ids' => []]);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_program') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $tableHasColumn = function(mysqli $conn, string $table, string $column): bool {
            $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            return (bool)$stmt->get_result()->fetch_row();
        };

        $programId = (int)($_POST['program_id'] ?? 0);
        if ($programId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing program_id']);
            exit;
        }

        $trainingTitle = trim($_POST['training_title'] ?? '');
        $trainingType = trim($_POST['training_type'] ?? '');
        $trainingMode = trim($_POST['training_mode'] ?? 'Onsite');
        $requestedBy = trim((string)($_POST['requested_by'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $targetAudience = trim($_POST['target_audience'] ?? '');

        $departmentIdRaw = (string)($_POST['department_id'] ?? '');
        $departmentIdRaw = trim($departmentIdRaw);
        $departmentId = $departmentIdRaw !== '' ? (int)$departmentIdRaw : null;
        $subDepartment = trim((string)($_POST['sub_department'] ?? ''));
        $subDepartment = $subDepartment !== '' ? $subDepartment : null;
        $targetRole = trim($_POST['target_role'] ?? '');
        $targetRole = $targetRole !== '' ? $targetRole : null;

        $mentorIdRaw = trim((string)($_POST['mentor_id'] ?? ''));
        $mentorId = $mentorIdRaw !== '' ? (int)$mentorIdRaw : null;
        if ($mentorId !== null && $mentorId <= 0) $mentorId = null;

        $category = trim($_POST['category'] ?? '');
        $participantsNeeded = (int)($_POST['participants_needed'] ?? 1);
        if ($participantsNeeded <= 0) $participantsNeeded = 1;

        $maxParticipantsRaw = trim((string)($_POST['max_participants'] ?? ''));
        $maxParticipants = $maxParticipantsRaw !== '' ? (int)$maxParticipantsRaw : null;
        if ($maxParticipants !== null && $maxParticipants <= 0) $maxParticipants = null;
        $trainingLevel = trim((string)($_POST['training_level'] ?? ''));
        $trainingLevel = $trainingLevel !== '' ? $trainingLevel : null;
        $trainingObjectivesJson = trim((string)($_POST['training_objectives_json'] ?? ''));
        $trainingObjectivesJson = $trainingObjectivesJson !== '' ? $trainingObjectivesJson : null;
        $trainingObjectivesOther = trim((string)($_POST['training_objectives_other'] ?? ''));
        $trainingObjectivesOther = $trainingObjectivesOther !== '' ? $trainingObjectivesOther : null;

        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime = trim($_POST['end_datetime'] ?? '');
        $status = 'Under Review';

        $needBudget = (int)($_POST['need_budget'] ?? 0);
        $needItems = (int)($_POST['need_items'] ?? 0);
        $needFacility = (int)($_POST['need_facility'] ?? 0);

        $budgetAmount = trim((string)($_POST['budget_amount'] ?? ''));
        $financialDetailsJson = trim((string)($_POST['financial_details_json'] ?? ''));
        $itemsRequested = trim((string)($_POST['items_requested'] ?? ''));
        $logisticsDetailsJson = trim((string)($_POST['logistics_details_json'] ?? ''));
        $facilityDetails = trim((string)($_POST['facility_details'] ?? ''));
        $adminDetailsJson = trim((string)($_POST['admin_details_json'] ?? ''));

        if ($trainingTitle === '' || $trainingType === '' || $description === '' || $targetAudience === '' || $startDatetime === '' || $endDatetime === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        $oldStatus = '';
        $canEdit = false;
        try {
            $stmtChk = $conn->prepare("SELECT status FROM training_programs WHERE id = ?");
            $stmtChk->bind_param('i', $programId);
            $stmtChk->execute();
            $row = $stmtChk->get_result()->fetch_assoc();
            if ($row) {
                $canEdit = true;
                $oldStatus = (string)($row['status'] ?? '');
            }
        } catch (Throwable $e) {
            $canEdit = false;
        }

        if (!$canEdit) {
            echo json_encode(['success' => false, 'message' => 'Program not found.']);
            exit;
        }
        if ($oldStatus === 'POSTED') {
            echo json_encode(['success' => false, 'message' => 'Posted trainings cannot be edited.']);
            exit;
        }

        $conn->begin_transaction();

        $hasTrainingMode = false;
        try {
            $hasTrainingMode = $tableHasColumn($conn, 'training_programs', 'training_mode');
        } catch (Throwable $e) {
            $hasTrainingMode = false;
        }

        if ($hasTrainingMode) {
            $stmt = $conn->prepare("UPDATE training_programs SET training_title = ?, training_type = ?, training_mode = ?, description = ?, target_audience = ?, department_id = ?, target_role = ?, mentor_id = ?, category = ?, participants_needed = ?, start_datetime = ?, end_datetime = ?, status = ?, status_reason = NULL WHERE id = ?");
            $stmt->bind_param('sssssisisisssi', $trainingTitle, $trainingType, $trainingMode, $description, $targetAudience, $departmentId, $targetRole, $mentorId, $category, $participantsNeeded, $startDatetime, $endDatetime, $status, $programId);
        } else {
            $stmt = $conn->prepare("UPDATE training_programs SET training_title = ?, training_type = ?, description = ?, target_audience = ?, department_id = ?, target_role = ?, mentor_id = ?, category = ?, participants_needed = ?, start_datetime = ?, end_datetime = ?, status = ?, status_reason = NULL WHERE id = ?");
            $stmt->bind_param('ssssisisisssi', $trainingTitle, $trainingType, $description, $targetAudience, $departmentId, $targetRole, $mentorId, $category, $participantsNeeded, $startDatetime, $endDatetime, $status, $programId);
        }
        $stmt->execute();

        try {
            if ($oldStatus !== 'Under Review' && $oldStatus !== 'Pending') {
                $stmtSub = $conn->prepare("UPDATE training_programs SET submission_no = submission_no + 1 WHERE id = ?");
                $stmtSub->bind_param('i', $programId);
                $stmtSub->execute();
            }
        } catch (Throwable $e) {
        }

        try {
            $set = [];
            $types = '';
            $vals = [];

            if ($tableHasColumn($conn, 'training_programs', 'requested_by')) {
                $set[] = "requested_by = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)$requestedBy;
            }
            if ($tableHasColumn($conn, 'training_programs', 'sub_department')) {
                $set[] = "sub_department = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($subDepartment ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'max_participants')) {
                $set[] = "max_participants = NULLIF(?, '')";
                $types .= 's';
                $vals[] = $maxParticipants !== null ? (string)$maxParticipants : '';
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_level')) {
                $set[] = "training_level = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingLevel ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_objectives_json')) {
                $set[] = "training_objectives_json = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingObjectivesJson ?? '');
            }
            if ($tableHasColumn($conn, 'training_programs', 'training_objectives_other')) {
                $set[] = "training_objectives_other = NULLIF(?, '')";
                $types .= 's';
                $vals[] = (string)($trainingObjectivesOther ?? '');
            }

            if (!empty($set)) {
                $sql = "UPDATE training_programs SET " . implode(', ', $set) . " WHERE id = ?";
                $types .= 'i';
                $vals[] = $programId;
                $stmtExtra = $conn->prepare($sql);
                $stmtExtra->bind_param($types, ...$vals);
                $stmtExtra->execute();
            }
        } catch (Throwable $e) {
        }

        try {
            $stmtReqPayload = $conn->prepare("UPDATE training_programs SET financial_budget_amount = NULLIF(?, ''), financial_details_json = NULLIF(?, ''), logistics_items_requested = NULLIF(?, ''), logistics_details_json = NULLIF(?, ''), admin_facility_details = NULLIF(?, ''), admin_details_json = NULLIF(?, ''), need_budget = ?, need_items = ?, need_facility = ? WHERE id = ?");
            $stmtReqPayload->bind_param('ssssssiiii', $budgetAmount, $financialDetailsJson, $itemsRequested, $logisticsDetailsJson, $facilityDetails, $adminDetailsJson, $needBudget, $needItems, $needFacility, $programId);
            $stmtReqPayload->execute();
        } catch (Throwable $e) {
        }

        try {
            $stmt3 = $conn->prepare("INSERT INTO training_program_status_logs (program_id, old_status, new_status, reason) VALUES (?, ?, ?, NULLIF(?, ''))");
            $reason = 'Edited';
            $stmt3->bind_param('isss', $programId, $oldStatus, $status, $reason);
            $stmt3->execute();
        } catch (Throwable $e) {
        }

        $conn->commit();

        $stmt4 = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt4->bind_param('i', $programId);
        $stmt4->execute();
        $program = $stmt4->get_result()->fetch_assoc();

        echo json_encode(['success' => true, 'program' => $program]);
        exit;
    } catch (Throwable $e) {
        try {
            $conn->rollback();
        } catch (Throwable $e2) {
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_program') {
    header('Content-Type: application/json; charset=utf-8');

    $programId = (int)($_POST['program_id'] ?? 0);
    if ($programId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing program_id']);
        exit;
    }

    try {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("DELETE FROM training_post_assignments WHERE program_id = ?");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmt = $conn->prepare("DELETE FROM training_posts WHERE program_id = ?");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmt = $conn->prepare("DELETE FROM financial_requests WHERE program_id = ?");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmt = $conn->prepare("DELETE FROM logistics_requests WHERE program_id = ?");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }
        try {
            $stmt = $conn->prepare("DELETE FROM admin_requests WHERE program_id = ?");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
        } catch (Throwable $e) {
        }

        $stmt = $conn->prepare("DELETE FROM training_programs WHERE id = ?");
        $stmt->bind_param('i', $programId);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        try {
            $conn->rollback();
        } catch (Throwable $e2) {
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_program_status') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        error_log("update_program_status called: " . print_r($_POST, true));
        
        $programId = (int)($_POST['program_id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));

        error_log("Parsed data: programId=$programId, status=$status, reason=$reason");

        if ($programId <= 0 || $status === '') {
            error_log("Validation failed: missing program_id or status");
            echo json_encode(['success' => false, 'message' => 'Missing program_id or status.']);
            exit;
        }

        $allowed = ['Under Review', 'Pending', 'Approved', 'Rejected', 'For Compliance', 'Planned', 'Scheduled', 'Ongoing', 'Completed', 'Cancelled'];
        $allowed[] = 'ON HOLD';
        if (!in_array($status, $allowed, true)) {
            error_log("Validation failed: invalid status");
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        $ensureReviewSchema($conn);

        $stmt = $conn->prepare("SELECT status, submission_no, need_budget, need_items, need_facility, financial_budget_amount, financial_details_json, logistics_items_requested, logistics_details_json, admin_facility_details, admin_details_json FROM training_programs WHERE id = ?");
        $stmt->bind_param('i', $programId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Program not found.']);
            exit;
        }
        $oldStatus = (string)($row['status'] ?? '');
        $submissionNo = isset($row['submission_no']) ? (int)$row['submission_no'] : 1;
        if ($submissionNo <= 0) $submissionNo = 1;

        $conn->begin_transaction();

        if ($status === 'Under Review' && $oldStatus === 'ON HOLD') {
            $stmt2 = $conn->prepare("UPDATE training_programs SET status = ?, status_reason = NULLIF(?, ''), submission_no = submission_no + 1 WHERE id = ?");
            $stmt2->bind_param('ssi', $status, $reason, $programId);
            $stmt2->execute();
            $submissionNo = $submissionNo + 1;
        } else {
            $stmt2 = $conn->prepare("UPDATE training_programs SET status = ?, status_reason = NULLIF(?, '') WHERE id = ?");
            $stmt2->bind_param('ssi', $status, $reason, $programId);
            $stmt2->execute();
        }

        if ($status === 'ON HOLD') {
            try {
                $stmtReqHold = $conn->prepare("UPDATE financial_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
                $stmtReqHold->bind_param('ii', $programId, $submissionNo);
                $stmtReqHold->execute();
            } catch (Throwable $e) {
            }
            try {
                $stmtReqHold = $conn->prepare("UPDATE logistics_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
                $stmtReqHold->bind_param('ii', $programId, $submissionNo);
                $stmtReqHold->execute();
            } catch (Throwable $e) {
            }
            try {
                $stmtReqHold = $conn->prepare("UPDATE admin_requests SET status = 'ON HOLD' WHERE program_id = ? AND submission_no = ? AND status = 'Pending'");
                $stmtReqHold->bind_param('ii', $programId, $submissionNo);
                $stmtReqHold->execute();
            } catch (Throwable $e) {
            }
        }

        if ($status === 'Approved') {
            $needBudget = (int)($row['need_budget'] ?? 0);
            $needItems = (int)($row['need_items'] ?? 0);
            $needFacility = (int)($row['need_facility'] ?? 0);

            if ($needBudget === 1) {
                $stmtChk = $conn->prepare("SELECT id FROM financial_requests WHERE program_id = ? AND submission_no = ? LIMIT 1");
                $stmtChk->bind_param('ii', $programId, $submissionNo);
                $stmtChk->execute();
                $exists = (bool)$stmtChk->get_result()->fetch_row();
                if (!$exists) {
                    $stmtIns = $conn->prepare("INSERT INTO financial_requests (program_id, submission_no, status, budget_amount, details_json) VALUES (?, ?, 'Pending', NULLIF(?, ''), NULLIF(?, ''))");
                    $ba = (string)($row['financial_budget_amount'] ?? '');
                    $fj = (string)($row['financial_details_json'] ?? '');
                    $stmtIns->bind_param('iiss', $programId, $submissionNo, $ba, $fj);
                    $stmtIns->execute();
                }
            }

            if ($needItems === 1) {
                $stmtChk = $conn->prepare("SELECT id FROM logistics_requests WHERE program_id = ? AND submission_no = ? LIMIT 1");
                $stmtChk->bind_param('ii', $programId, $submissionNo);
                $stmtChk->execute();
                $exists = (bool)$stmtChk->get_result()->fetch_row();
                if (!$exists) {
                    $stmtIns = $conn->prepare("INSERT INTO logistics_requests (program_id, submission_no, status, items_requested, details_json) VALUES (?, ?, 'Pending', NULLIF(?, ''), NULLIF(?, ''))");
                    $ir = (string)($row['logistics_items_requested'] ?? '');
                    $lj = (string)($row['logistics_details_json'] ?? '');
                    $stmtIns->bind_param('iiss', $programId, $submissionNo, $ir, $lj);
                    $stmtIns->execute();
                }
            }

            if ($needFacility === 1) {
                $stmtChk = $conn->prepare("SELECT id FROM admin_requests WHERE program_id = ? AND submission_no = ? LIMIT 1");
                $stmtChk->bind_param('ii', $programId, $submissionNo);
                $stmtChk->execute();
                $exists = (bool)$stmtChk->get_result()->fetch_row();
                if (!$exists) {
                    $stmtIns = $conn->prepare("INSERT INTO admin_requests (program_id, submission_no, status, facility_details, details_json) VALUES (?, ?, 'Pending', NULLIF(?, ''), NULLIF(?, ''))");
                    $fd = (string)($row['admin_facility_details'] ?? '');
                    $aj = (string)($row['admin_details_json'] ?? '');
                    $stmtIns->bind_param('iiss', $programId, $submissionNo, $fd, $aj);
                    $stmtIns->execute();
                }
            }
        }

        $stmt3 = $conn->prepare("INSERT INTO training_program_status_logs (program_id, old_status, new_status, reason) VALUES (?, ?, ?, NULLIF(?, ''))");
        $stmt3->bind_param('isss', $programId, $oldStatus, $status, $reason);
        $stmt3->execute();

        $conn->commit();

        $stmt4 = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt4->bind_param('i', $programId);
        $stmt4->execute();
        $program = $stmt4->get_result()->fetch_assoc();

        echo json_encode(['success' => true, 'program' => $program]);
        exit;
    } catch (Throwable $e) {
        try {
            $conn->rollback();
        } catch (Throwable $e2) {
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$employees = [];
try {
    $resEmp = $conn->query("SELECT id, employee_no, first_name, last_name FROM employees ORDER BY last_name, first_name");
    while ($row = $resEmp->fetch_assoc()) {
        $employees[] = $row;
    }
} catch (Throwable $e) {
    $employees = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Portal - Training Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            if (!window.Swal || window.__SWAL_DAISY_PATCHED__) return;
            window.__SWAL_DAISY_PATCHED__ = true;
            const orig = window.Swal.fire.bind(window.Swal);
            window.Swal.fire = function (opts) {
                const inOpts = opts || {};
                const inCustom = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
                const customClass = {
                    popup: 'bg-base-100 text-base-content rounded-box',
                    title: 'text-base-content',
                    htmlContainer: 'text-base-content',
                    actions: 'flex gap-2',
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-ghost',
                    denyButton: 'btn btn-ghost',
                    ...(inCustom || {})
                };
                return orig({
                    returnFocus: false,
                    buttonsStyling: false,
                    ...inOpts,
                    customClass
                });
            };
        })();
    </script>
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .training-card { transition: all 0.2s ease; }
        
        .training-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .swal2-container { z-index: 2147483647 !important; }
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #10b981;
            transition: width 0.3s ease;
        }
        
        .stats-card {
            transition: all 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        
        .status-planned { background-color: #fef3c7; color: #92400e; }
        .status-scheduled { background-color: #dbeafe; color: #1e40af; }
        .status-ongoing { background-color: #dcfce7; color: #166534; }
        .status-completed { background-color: #f3f4f6; color: #374151; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .status-review { background-color: #e0f2fe; color: #075985; }
        .status-pending { background-color: #fef9c3; color: #854d0e; }
        .status-approved { background-color: #dcfce7; color: #166534; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .status-compliance { background-color: #f3e8ff; color: #6b21a8; }
        .status-onhold { background-color: #ffedd5; color: #9a3412; }
        .status-posted { background-color: #cffafe; color: #155e75; }
        
        .datetime-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        @media (max-width: 640px) {
            .datetime-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" data-owner-key="<?= htmlspecialchars($ownerKey) ?>">
 <body class="bg-gray-50 min-h-screen">
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
    
            
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        <!-- Main Training Programs Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <!-- Action Bar with Filters -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-700">Training Programs</h2>
                    <p class="text-gray-500 text-sm">Manage all training programs across the organization</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter buttons -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <i data-lucide="filter" class="h-4 w-4 mr-2"></i>
                            Filter
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByType('all')">All Types</a></li>
                            <li><a onclick="filterByType('Orientation')">Orientation</a></li>
                            <li><a onclick="filterByType('Training')">Training</a></li>
                            <li><a onclick="filterByType('Seminar')">Seminar</a></li>
                            <li><a onclick="filterByType('Workshop')">Workshop</a></li>
                            <li><a onclick="filterByType('Refresher')">Refresher</a></li>
                        </ul>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            <i data-lucide="list-filter" class="h-4 w-4 mr-2"></i>
                            Status
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a onclick="filterByStatus('all')">All Status</a></li>
                            <li><a onclick="filterByStatus('Under Review')">Under Review</a></li>
                            <li><a onclick="filterByStatus('Pending')">Pending</a></li>
                            <li><a onclick="filterByStatus('Approved')">Approved</a></li>
                            <li><a onclick="filterByStatus('POSTED')">POSTED</a></li>
                            <li><a onclick="filterByStatus('Rejected')">Rejected</a></li>
                            <li><a onclick="filterByStatus('For Compliance')">For Compliance</a></li>
                            <li><a onclick="filterByStatus('ON HOLD')">ON HOLD</a></li>
                            <li><a onclick="filterByStatus('Planned')">Planned</a></li>
                            <li><a onclick="filterByStatus('Scheduled')">Scheduled</a></li>
                            <li><a onclick="filterByStatus('Ongoing')">Ongoing</a></li>
                            <li><a onclick="filterByStatus('Completed')">Completed</a></li>
                        </ul>
                    </div>

                    
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                            Departments
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a href="financial.php">Financial (Budget)</a></li>
                            <li><a href="logistics.php">Logistics (Items)</a></li>
                            <li><a href="admin.php">Admin (Facility)</a></li>
                        </ul>
                    </div>

                    
                    <a id="add-training-btn" href="add_training.php" class="btn btn-primary btn-sm">
                        <i data-lucide="plus" class="h-5 w-5 mr-2"></i>
                        Add Training
                    </a>
                </div>
            </div>

            <!-- Training Cards Container -->
            <div id="training-cards-container" class="fade-in">
                <div id="cards-view">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="training-cards"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Training Modal -->
    <dialog id="training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <h3 class="font-bold text-2xl mb-2" id="modal-title">Create New Training Program</h3>
            <p class="text-gray-600 mb-6" id="modal-subtitle">Fill in all required information to create a new training program</p>
            
            <form id="training-form" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Training Program Title -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Program Title <span class="text-red-500">*</span></span>
                            </label>
                            <input id="training-title" type="text" placeholder="Enter training title" class="input input-bordered w-full" required>
                        </div>
                        
                        <!-- Training Type -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Type <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-type" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select training type</option>
                                <option value="Orientation">Orientation</option>
                                <option value="Training">Training</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Workshop">Workshop</option>
                            </select>
                        </div>

                        <!-- Training Mode -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Mode <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-mode" class="select select-bordered w-full" required>
                                <option value="Onsite" selected>Onsite</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>

                        <!-- Training Category -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Category <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-category" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="Technical Skills">Technical Skills</option>
                                <option value="Soft Skills">Soft Skills</option>
                                <option value="Compliance">Compliance</option>
                                <option value="Leadership">Leadership</option>
                                <option value="Customer Service">Customer Service</option>
                                <option value="Sales & Marketing">Sales & Marketing</option>
                                <option value="Safety & Security">Safety & Security</option>
                                <option value="IT & Digital Literacy">IT & Digital Literacy</option>
                                <option value="Product Knowledge">Product Knowledge</option>
                                <option value="Quality Management">Quality Management</option>
                            </select>
                        </div>

                        <!-- Target Audience -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Target Audience <span class="text-red-500">*</span></span>
                            </label>
                            <select id="target-audience" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select target audience</option>
                                <option value="New Hires">New Hires</option>
                                <option value="Specific Department">Specific Department</option>
                                <option value="Specific Role">Specific Role</option>
                                <option value="Specific Employee">Specific Employee</option>
                                <option value="All Employees">All Employees</option>
                                <option value="Management">Management</option>
                                <option value="Technical Staff">Technical Staff</option>
                                <option value="Customer Service">Customer Service</option>
                                <option value="Mentors">Mentors</option>
                            </select>
                        </div>
                        <!-- Department Selection (shown when Specific Department is selected) -->
                        <div id="department-container" class="form-control fade-in hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Department <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-department" class="select select-bordered w-full">
                                <option value="" selected>Select a department</option>
                                <option value="1">Human Resources</option>
                                <option value="2">Information Technology</option>
                                <option value="3">Marketing</option>
                                <option value="4">Sales</option>
                                <option value="5">Operations</option>
                                <option value="6">Finance</option>
                                <option value="7">Customer Support</option>
                            </select>
                        </div>
                        <!-- Role Selection (shown when Specific Role is selected) -->
                        <div id="role-container" class="form-control fade-in hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Role <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-role" class="select select-bordered w-full">
                                <option value="" selected>Select a role</option>
                                <option value="Manager">Manager</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Team Lead">Team Lead</option>
                                <option value="Executive">Executive</option>
                                <option value="Associate">Associate</option>
                                <option value="Intern">Intern</option>
                            </select>
                        </div>
                        <!-- Employee Selection (shown when Specific Employee is selected) -->
                        <div id="employee-container" class="form-control fade-in hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Employee <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-employee" class="select select-bordered w-full">
                                <option value="" selected>Select employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <?php
                                        $empId = (int)($emp['id'] ?? 0);
                                        $empNo = trim((string)($emp['employee_no'] ?? ''));
                                        $fn = trim((string)($emp['first_name'] ?? ''));
                                        $ln = trim((string)($emp['last_name'] ?? ''));
                                        $label = trim($ln . ', ' . $fn);
                                        if ($empNo !== '') $label .= ' (' . $empNo . ')';
                                    ?>
                                    <option value="<?= htmlspecialchars($empId) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Schedule -->
                        <div class="space-y-4">
                            <div>
                                <label class="label">
                                    <span class="label-text font-semibold">Schedule <span class="text-red-500">*</span></span>
                                </label>
                                <input id="participants-needed" type="hidden" value="1">
                                <div class="datetime-container">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Start Date</span></label>
                                        <input id="start-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Start Time</span></label>
                                        <input id="start-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">End Date</span></label>
                                        <input id="end-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">End Time</span></label>
                                        <input id="end-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-500" id="schedule-validation"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description / Overview -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description / Overview <span class="text-red-500">*</span></span>
                    </label>
                    <textarea id="description" class="textarea textarea-bordered h-32" placeholder="Provide a brief explanation of the training program" required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Budget? (Financial)</span>
                        </label>
                        <select id="need-budget" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Items? (Logistics)</span>
                        </label>
                        <select id="need-items" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Facility? (Admin)</span>
                        </label>
                        <select id="need-facility" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div id="request-summary" class="space-y-4">
                    <div class="text-sm font-semibold text-gray-700">Request Summary</div>
                    <div id="budget-summary" class="hidden"></div>
                    <div id="logistics-summary" class="hidden"></div>
                    <div id="facility-summary" class="hidden"></div>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="cancel-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="save-training-btn" class="btn btn-primary">Save Training Program</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Budget Request Modal -->
    <dialog id="budget-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Budget Request</h3>
                    <p class="text-gray-600">Request budget for training, seminar, or orientation</p>
                </div>
                <button type="button" id="budget-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="budget-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="budget-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="budget-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="budget-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Human Resources</option>
                                <option value="2">Information Technology</option>
                                <option value="3">Marketing</option>
                                <option value="4">Sales</option>
                                <option value="5">Operations</option>
                                <option value="6">Finance</option>
                                <option value="7">Customer Support</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Requested To Department <span class="text-red-500">*</span></span></label>
                            <select id="budget-requested-dept" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Human Resources</option>
                                <option value="2">Information Technology</option>
                                <option value="3">Marketing</option>
                                <option value="4">Sales</option>
                                <option value="5">Operations</option>
                                <option value="6">Finance</option>
                                <option value="7">Customer Support</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="budget-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Budget Items</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-4">
                        <div id="budget-items-container" class="space-y-4"></div>
                        <button type="button" id="budget-add-item-btn" class="btn btn-outline btn-sm w-full">+ Add Another Budget Item</button>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Total Estimated Cost</div>
                            <div class="text-xs text-gray-500">Sum of all budget items</div>
                        </div>
                        <div class="text-lg font-bold text-blue-600">₱<span id="budget-total-cost">0.00</span></div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Justification <span class="text-red-500">*</span></span></label>
                    <textarea id="budget-justification" class="textarea textarea-bordered h-24 w-full" required placeholder="Explain why this budget is needed and how it will benefit the training..."></textarea>
                </div>
                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="budget-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="budget-save-btn" class="btn btn-primary">Save Budget Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Logistics Request Modal -->
    <dialog id="logistics-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Logistics Request</h3>
                    <p class="text-gray-600">Request items for training, seminar, or orientation</p>
                </div>
                <button type="button" id="logistics-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="logistics-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="logistics-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="logistics-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="logistics-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Requested To Department <span class="text-red-500">*</span></span></label>
                            <select id="logistics-requested-dept" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="logistics-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Needed By Date <span class="text-red-500">*</span></span></label>
                            <input id="logistics-needed-by-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Requested Items</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-4">
                        <div id="logistics-items-container" class="space-y-4"></div>
                        <button type="button" id="logistics-add-item-btn" class="btn btn-outline btn-sm w-full">+ Add Another Item</button>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Delivery Information</div>
                    <div class="bg-blue-50 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Delivery Location <span class="text-red-500">*</span></span></label>
                            <input id="logistics-delivery-location" type="text" class="input input-bordered w-full" required placeholder="E.g., Training Room A, 3rd Floor">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Contact Person <span class="text-red-500">*</span></span></label>
                            <input id="logistics-contact-person" type="text" class="input input-bordered w-full" required placeholder="Name of person to receive items">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="logistics-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes, special handling instructions, or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="logistics-save-btn" class="btn btn-primary">Save Logistics Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Facility Request Modal -->
    <dialog id="facility-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Location Request</h3>
                    <p class="text-gray-600">Request venue for training, seminar, or orientation</p>
                </div>
                <button type="button" id="facility-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="facility-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="facility-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="facility-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="facility-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Requested To Department <span class="text-red-500">*</span></span></label>
                            <select id="facility-requested-dept" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="facility-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Location Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Preferred Location <span class="text-red-500">*</span></span></label>
                            <select id="facility-preferred-location" class="select select-bordered w-full" required>
                                <option value="" selected>Select Location</option>
                                <option value="Training Room A">Training Room A</option>
                                <option value="Training Room B">Training Room B</option>
                                <option value="Conference Hall">Conference Hall</option>
                                <option value="Auditorium">Auditorium</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Start Time <span class="text-red-500">*</span></span></label>
                                <input id="facility-start-time" type="time" class="input input-bordered w-full" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">End Time <span class="text-red-500">*</span></span></label>
                                <input id="facility-end-time" type="time" class="input input-bordered w-full" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Special Requirements</span></label>
                    <textarea id="facility-special-requirements" class="textarea textarea-bordered h-24 w-full" placeholder="Audio-visual equipment, seating arrangement, internet access, etc."></textarea>
                </div>
                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="facility-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="facility-save-btn" class="btn btn-primary">Save Location Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- View Training Details Modal -->
    <dialog id="view-training-modal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <h3 class="font-bold text-2xl mb-2" id="view-training-title">Training Program</h3>
            <div class="flex items-center gap-2 mb-4">
                <span id="view-training-type" class="badge badge-outline"></span>
                <span id="view-status" class="badge badge-outline"></span>
            </div>

            <div class="space-y-4" id="view-training-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Category</h4>
                        <p id="view-category" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Target Audience</h4>
                        <p id="view-target-audience" class="text-gray-900"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Duration</h4>
                        <p id="view-duration" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Competency Level</h4>
                        <p id="view-competency-level" class="text-gray-900"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Start Date & Time</h4>
                        <p id="view-start-date" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">End Date & Time</h4>
                        <p id="view-end-date" class="text-gray-900"></p>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-700">Description</h4>
                    <p id="view-description" class="text-gray-900 whitespace-pre-line"></p>
                </div>

                <div id="view-status-reason-container" class="hidden">
                    <h4 class="font-semibold text-gray-700">Reason</h4>
                    <p id="view-status-reason" class="text-gray-900 whitespace-pre-line"></p>
                </div>

                <div id="view-request-statuses-container" class="hidden">
                    <h4 class="font-semibold text-gray-700">Department Request Status</h4>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold text-gray-700">Financial</div>
                            <div class="text-right">
                                <div id="view-financial-status" class="badge badge-outline"></div>
                                <div id="view-financial-reason" class="text-xs text-gray-500 whitespace-pre-line"></div>
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold text-gray-700">Logistics</div>
                            <div class="text-right">
                                <div id="view-logistics-status" class="badge badge-outline"></div>
                                <div id="view-logistics-reason" class="text-xs text-gray-500 whitespace-pre-line"></div>
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold text-gray-700">Admin</div>
                            <div class="text-right">
                                <div id="view-admin-status" class="badge badge-outline"></div>
                                <div id="view-admin-reason" class="text-xs text-gray-500 whitespace-pre-line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Created Date</h4>
                        <p id="view-created-date" class="text-gray-900"></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Last Updated</h4>
                        <p id="view-updated-date" class="text-gray-900"></p>
                    </div>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" id="post-training-btn" class="btn btn-primary hidden">Post Training</button>
                <button type="button" id="resubmit-training-btn" class="btn btn-warning hidden">Resubmit Training Program</button>
                <button type="button" id="close-view-modal" class="btn btn-ghost">Close</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
 <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
    <script src="main.js"></script>
</body>
</html>