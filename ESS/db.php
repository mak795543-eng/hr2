<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ess_ensure_recent_activity_tables($conn): void
{
    if (!$conn) {
        return;
    }

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS recent_activities (\n" .
            "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
            "  employee_id INT NOT NULL,\n" .
            "  activity_type VARCHAR(80) NOT NULL,\n" .
            "  activity_title VARCHAR(255) NOT NULL,\n" .
            "  activity_meta TEXT NULL,\n" .
            "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
            "  INDEX idx_emp_created (employee_id, created_at),\n" .
            "  FOREIGN KEY (employee_id) REFERENCES employees(id)\n" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function ess_ensure_notification_state_tables($conn): void
{
    if (!$conn) {
        return;
    }

    $dbResult = @mysqli_query($conn, 'SELECT DATABASE() AS db');
    $dbRow = $dbResult ? mysqli_fetch_assoc($dbResult) : null;
    $dbName = (string)($dbRow['db'] ?? '');

    $columnExists = static function ($table, $column) use ($conn, $dbName): bool {
        if ($dbName === '') return false;
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'sss', $dbName, $table, $column);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return is_array($row);
    };

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS notification_states (\n" .
            "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
            "  employee_id INT NOT NULL,\n" .
            "  notif_key CHAR(40) NOT NULL,\n" .
            "  status ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',\n" .
            "  deleted TINYINT(1) NOT NULL DEFAULT 0,\n" .
            "  notif_type VARCHAR(60) NULL,\n" .
            "  notif_title VARCHAR(255) NULL,\n" .
            "  notif_meta VARCHAR(255) NULL,\n" .
            "  notif_link VARCHAR(255) NULL,\n" .
            "  notif_date DATETIME NULL,\n" .
            "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
            "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "  UNIQUE KEY uniq_emp_notif (employee_id, notif_key),\n" .
            "  INDEX idx_emp_status (employee_id, status),\n" .
            "  INDEX idx_emp_deleted (employee_id, deleted)\n" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if ($dbName !== '') {
        $cols = [
            ['status', "ALTER TABLE notification_states ADD COLUMN status ENUM('unread','read','archived') NOT NULL DEFAULT 'unread'"],
            ['deleted', "ALTER TABLE notification_states ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0"],
            ['notif_type', "ALTER TABLE notification_states ADD COLUMN notif_type VARCHAR(60) NULL"],
            ['notif_title', "ALTER TABLE notification_states ADD COLUMN notif_title VARCHAR(255) NULL"],
            ['notif_meta', "ALTER TABLE notification_states ADD COLUMN notif_meta VARCHAR(255) NULL"],
            ['notif_link', "ALTER TABLE notification_states ADD COLUMN notif_link VARCHAR(255) NULL"],
            ['notif_date', "ALTER TABLE notification_states ADD COLUMN notif_date DATETIME NULL"],
            ['created_at', "ALTER TABLE notification_states ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
            ['updated_at', "ALTER TABLE notification_states ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"],
        ];
        foreach ($cols as [$col, $sql]) {
            if (!$columnExists('notification_states', $col)) {
                @mysqli_query($conn, $sql);
            }
        }
    }
}

function ess_ensure_leave_tables($conn): void
{
    if (!$conn) {
        return;
    }

    $dbResult = @mysqli_query($conn, 'SELECT DATABASE() AS db');
    $dbRow = $dbResult ? mysqli_fetch_assoc($dbResult) : null;
    $dbName = (string)($dbRow['db'] ?? '');

    $columnExists = static function ($table, $column) use ($conn, $dbName): bool {
        if ($dbName === '') return false;
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'sss', $dbName, $table, $column);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return is_array($row);
    };

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS leave_requests (\n" .
            "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
            "  employee_id INT NOT NULL,\n" .
            "  leave_type VARCHAR(100) NOT NULL,\n" .
            "  start_date DATE NOT NULL,\n" .
            "  end_date DATE NOT NULL,\n" .
            "  reason TEXT,\n" .
            "  status ENUM('Pending','Approved','Rejected','For Compliance') DEFAULT 'Pending',\n" .
            "  remarks TEXT NULL,\n" .
            "  approved_by INT NULL,\n" .
            "  approved_at TIMESTAMP NULL,\n" .
            "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
            "  INDEX idx_emp_created (employee_id, created_at),\n" .
            "  FOREIGN KEY (employee_id) REFERENCES employees(id)\n" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if ($dbName !== '') {
        if (!$columnExists('leave_requests', 'remarks')) {
            @mysqli_query($conn, 'ALTER TABLE leave_requests ADD COLUMN remarks TEXT NULL');
        }
        if (!$columnExists('leave_requests', 'approved_by')) {
            @mysqli_query($conn, 'ALTER TABLE leave_requests ADD COLUMN approved_by INT NULL');
        }
        if (!$columnExists('leave_requests', 'approved_at')) {
            @mysqli_query($conn, 'ALTER TABLE leave_requests ADD COLUMN approved_at TIMESTAMP NULL');
        }

        @mysqli_query(
            $conn,
            "ALTER TABLE leave_requests MODIFY COLUMN status ENUM('Pending','Approved','Rejected','For Compliance') DEFAULT 'Pending'"
        );
    }
}

function ess_ensure_complaint_tables($conn): void
{
    if (!$conn) {
        return;
    }

    $dbResult = @mysqli_query($conn, 'SELECT DATABASE() AS db');
    $dbRow = $dbResult ? mysqli_fetch_assoc($dbResult) : null;
    $dbName = (string)($dbRow['db'] ?? '');

    $columnExists = static function ($table, $column) use ($conn, $dbName): bool {
        if ($dbName === '') return false;
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'sss', $dbName, $table, $column);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return is_array($row);
    };

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS complaints (\n" .
            "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
            "  employee_id INT NOT NULL,\n" .
            "  subject VARCHAR(255) NOT NULL,\n" .
            "  description TEXT NOT NULL,\n" .
            "  status ENUM('Open','In Review','Resolved','Closed') DEFAULT 'Open',\n" .
            "  handled_by INT NULL,\n" .
            "  resolution TEXT NULL,\n" .
            "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
            "  resolved_at TIMESTAMP NULL,\n" .
            "  category VARCHAR(100) NULL,\n" .
            "  category_other VARCHAR(255) NULL,\n" .
            "  incident_date DATE NULL,\n" .
            "  attachment_path VARCHAR(255) NULL,\n" .
            "  workflow_status VARCHAR(30) DEFAULT 'Submitted',\n" .
            "  returned_reason TEXT NULL,\n" .
            "  accepted_by INT NULL,\n" .
            "  accepted_at TIMESTAMP NULL,\n" .
            "  assigned_role VARCHAR(50) NULL,\n" .
            "  assigned_to_employee_no VARCHAR(50) NULL,\n" .
            "  assigned_at TIMESTAMP NULL,\n" .
            "  meeting_date DATE NULL,\n" .
            "  meeting_time TIME NULL,\n" .
            "  meeting_place VARCHAR(255) NULL,\n" .
            "  meeting_scheduled_by INT NULL,\n" .
            "  meeting_scheduled_at TIMESTAMP NULL,\n" .
            "  seen_by_assignee TINYINT(1) DEFAULT 0,\n" .
            "  seen_by_employee TINYINT(1) DEFAULT 0,\n" .
            "  FOREIGN KEY (employee_id) REFERENCES employees(id)\n" .
            ")"
    );

    if ($dbName !== '') {
        $cols = [
            ['category', "ALTER TABLE complaints ADD COLUMN category VARCHAR(100) NULL"],
            ['category_other', "ALTER TABLE complaints ADD COLUMN category_other VARCHAR(255) NULL"],
            ['incident_date', "ALTER TABLE complaints ADD COLUMN incident_date DATE NULL"],
            ['attachment_path', "ALTER TABLE complaints ADD COLUMN attachment_path VARCHAR(255) NULL"],
            ['workflow_status', "ALTER TABLE complaints ADD COLUMN workflow_status VARCHAR(30) DEFAULT 'Submitted'"],
            ['returned_reason', "ALTER TABLE complaints ADD COLUMN returned_reason TEXT NULL"],
            ['accepted_by', "ALTER TABLE complaints ADD COLUMN accepted_by INT NULL"],
            ['accepted_at', "ALTER TABLE complaints ADD COLUMN accepted_at TIMESTAMP NULL"],
            ['assigned_role', "ALTER TABLE complaints ADD COLUMN assigned_role VARCHAR(50) NULL"],
            ['assigned_to_employee_no', "ALTER TABLE complaints ADD COLUMN assigned_to_employee_no VARCHAR(50) NULL"],
            ['assigned_at', "ALTER TABLE complaints ADD COLUMN assigned_at TIMESTAMP NULL"],
            ['meeting_date', "ALTER TABLE complaints ADD COLUMN meeting_date DATE NULL"],
            ['meeting_time', "ALTER TABLE complaints ADD COLUMN meeting_time TIME NULL"],
            ['meeting_place', "ALTER TABLE complaints ADD COLUMN meeting_place VARCHAR(255) NULL"],
            ['meeting_scheduled_by', "ALTER TABLE complaints ADD COLUMN meeting_scheduled_by INT NULL"],
            ['meeting_scheduled_at', "ALTER TABLE complaints ADD COLUMN meeting_scheduled_at TIMESTAMP NULL"],
            ['seen_by_assignee', "ALTER TABLE complaints ADD COLUMN seen_by_assignee TINYINT(1) DEFAULT 0"],
            ['seen_by_employee', "ALTER TABLE complaints ADD COLUMN seen_by_employee TINYINT(1) DEFAULT 0"],
        ];

        foreach ($cols as [$col, $sql]) {
            if (!$columnExists('complaints', $col)) {
                @mysqli_query($conn, $sql);
            }
        }
    }
}

$dbHost = getenv('ESS_DB_HOST');
if ($dbHost === false || $dbHost === '') {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
}

$dbUser = getenv('ESS_DB_USER');
if ($dbUser === false || $dbUser === '') {
    $dbUser = getenv('DB_USER') ?: 'hr2_employee_self_service';
}

$dbPassEnv = getenv('ESS_DB_PASS');
if ($dbPassEnv === false) {
    $dbPassEnv = getenv('DB_PASS');
}

// $dbPass = $dbPassEnv !== false
//     ? $dbPassEnv
//     : (($dbUser === 'root' && ($dbHost === 'localhost' || $dbHost === '127.0.0.1')) ? '' : 'makmak01');
$Pass = 'hr2.soliera';
$dbName = getenv('ESS_DB_NAME');
if ($dbName === false || $dbName === '') {
    $dbName = 'hr2_employee_self_service';
}

$conn = @mysqli_connect($dbHost, $dbUser, $Pass, $dbName);
if ($conn) {
    @mysqli_set_charset($conn, 'utf8mb4');
} elseif (!defined('SUPPRESS_DB_ERRORS')) {
    echo "<div style='padding:10px;border:1px solid #fca5a5;background:#fee2e2;color:#991b1b;border-radius:8px;'>";
    echo "Failed to connect to Employee Self Service database: " . htmlspecialchars(mysqli_connect_error(), ENT_QUOTES);
    echo "</div>";
}

function ess_ensure_profile_tables($conn): void
{
    if (!$conn) {
        return;
    }

    $dbResult = @mysqli_query($conn, 'SELECT DATABASE() AS db');
    $dbRow = $dbResult ? mysqli_fetch_assoc($dbResult) : null;
    $dbName = (string)($dbRow['db'] ?? '');

    $columnExists = static function ($table, $column) use ($conn, $dbName): bool {
        if ($dbName === '') return false;
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'sss', $dbName, $table, $column);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return is_array($row);
    };

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS employee_profiles (\n" .
            "  employee_id INT PRIMARY KEY,\n" .
            "  phone VARCHAR(50) NULL,\n" .
            "  work_location VARCHAR(150) NULL,\n" .
            "  gender VARCHAR(20) NULL,\n" .
            "  age INT NULL,\n" .
            "  birthdate DATE NULL,\n" .
            "  civil_status VARCHAR(50) NULL,\n" .
            "  nationality VARCHAR(100) NULL,\n" .
            "  emergency_name VARCHAR(150) NULL,\n" .
            "  emergency_relationship VARCHAR(100) NULL,\n" .
            "  emergency_phone VARCHAR(50) NULL,\n" .
            "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "  FOREIGN KEY (employee_id) REFERENCES employees(id)\n" .
            ")"
    );

    @mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS profile_update_requests (\n" .
            "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
            "  employee_id INT NOT NULL,\n" .
            "  requested_data TEXT NOT NULL,\n" .
            "  reason_choice VARCHAR(100) NULL,\n" .
            "  reason_text TEXT NULL,\n" .
            "  proof_file_path VARCHAR(255) NULL,\n" .
            "  status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',\n" .
            "  remarks TEXT,\n" .
            "  reviewed_by INT NULL,\n" .
            "  reviewed_at TIMESTAMP NULL,\n" .
            "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
            "  seen_by_employee TINYINT(1) DEFAULT 0,\n" .
            "  FOREIGN KEY (employee_id) REFERENCES employees(id)\n" .
            ")"
    );

    if ($dbName !== '') {
        $profileCols = [
            ['gender', "ALTER TABLE employee_profiles ADD COLUMN gender VARCHAR(20) NULL"],
            ['age', "ALTER TABLE employee_profiles ADD COLUMN age INT NULL"],
            ['birthdate', "ALTER TABLE employee_profiles ADD COLUMN birthdate DATE NULL"],
            ['civil_status', "ALTER TABLE employee_profiles ADD COLUMN civil_status VARCHAR(50) NULL"],
            ['nationality', "ALTER TABLE employee_profiles ADD COLUMN nationality VARCHAR(100) NULL"],
            ['emergency_phone', "ALTER TABLE employee_profiles ADD COLUMN emergency_phone VARCHAR(50) NULL"],
        ];

        foreach ($profileCols as [$col, $sql]) {
            if (!$columnExists('employee_profiles', $col)) {
                @mysqli_query($conn, $sql);
            }
        }

        $requestCols = [
            ['reason_choice', "ALTER TABLE profile_update_requests ADD COLUMN reason_choice VARCHAR(100) NULL"],
            ['reason_text', "ALTER TABLE profile_update_requests ADD COLUMN reason_text TEXT NULL"],
            ['proof_file_path', "ALTER TABLE profile_update_requests ADD COLUMN proof_file_path VARCHAR(255) NULL"],
            ['remarks', "ALTER TABLE profile_update_requests ADD COLUMN remarks TEXT NULL"],
            ['reviewed_by', "ALTER TABLE profile_update_requests ADD COLUMN reviewed_by INT NULL"],
            ['reviewed_at', "ALTER TABLE profile_update_requests ADD COLUMN reviewed_at TIMESTAMP NULL"],
            ['seen_by_employee', "ALTER TABLE profile_update_requests ADD COLUMN seen_by_employee TINYINT(1) DEFAULT 0"],
        ];

        foreach ($requestCols as [$col, $sql]) {
            if (!$columnExists('profile_update_requests', $col)) {
                @mysqli_query($conn, $sql);
            }
        }

        $employeeCols = [
            ['middle_name', "ALTER TABLE employees ADD COLUMN middle_name VARCHAR(100) NULL"],
            ['suffix', "ALTER TABLE employees ADD COLUMN suffix VARCHAR(50) NULL"],
        ];

        foreach ($employeeCols as [$col, $sql]) {
            if (!$columnExists('employees', $col)) {
                @mysqli_query($conn, $sql);
            }
        }

        $stmt = mysqli_prepare(
            $conn,
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0'
        );
        if ($stmt) {
            $tbl = 'employees';
            $col = 'email';
            mysqli_stmt_bind_param($stmt, 'sss', $dbName, $tbl, $col);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $uniqIndexes = [];
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $idx = (string)($r['INDEX_NAME'] ?? '');
                if ($idx !== '' && strtoupper($idx) !== 'PRIMARY') {
                    $uniqIndexes[$idx] = true;
                }
            }
            mysqli_stmt_close($stmt);

            foreach (array_keys($uniqIndexes) as $idxName) {
                @mysqli_query($conn, 'ALTER TABLE employees DROP INDEX `' . mysqli_real_escape_string($conn, $idxName) . '`');
            }
        }
    }
}

if ($conn) {
    ess_ensure_profile_tables($conn);
    ess_ensure_complaint_tables($conn);
    ess_ensure_leave_tables($conn);
    ess_ensure_notification_state_tables($conn);
    ess_ensure_recent_activity_tables($conn);
}

function ess_log_activity($conn, int $employeeId, string $type, string $title, string $meta = ''): void
{
    if (!$conn || $employeeId <= 0) {
        return;
    }

    $type = trim($type);
    $title = trim($title);
    $meta = trim($meta);
    if ($type === '' || $title === '') {
        return;
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO recent_activities (employee_id, activity_type, activity_title, activity_meta) VALUES (?, ?, ?, ?)'
    );
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'isss', $employeeId, $type, $title, $meta);
    @mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function ess_current_employee_no(): string
{
    $employeeId = trim((string)($_SESSION['employee_id'] ?? ''));
    if ($employeeId !== '') {
        return $employeeId;
    }
    return trim((string)($_SESSION['user_id'] ?? ''));
}

function ess_current_email(): string
{
    return (string)($_SESSION['email'] ?? '');
}

function ess_current_fullname(): string
{
    return (string)($_SESSION['employee_name'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? '');
}

function ess_split_name(string $full): array
{
    $full = trim($full);
    if ($full === '') {
        return ['first' => 'Employee', 'last' => ''];
    }
    $parts = preg_split('/\s+/', $full);
    $parts = is_array($parts) ? array_values(array_filter($parts, static fn($p) => $p !== '')) : [];
    if (count($parts) <= 1) {
        return ['first' => $parts[0] ?? $full, 'last' => ''];
    }
    return ['first' => $parts[0], 'last' => $parts[count($parts) - 1]];
}

function ess_ensure_employee($conn): ?array
{
    if (!$conn) {
        return null;
    }

    $employeeNo = ess_current_employee_no();
    $email = ess_current_email();

    if ($employeeNo !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT * FROM employees WHERE employee_no = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $employeeNo);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if (is_array($row)) return $row;
        }
    }

    $name = ess_current_fullname();
    $nameParts = ess_split_name($name);

    if ($employeeNo === '') {
        return null;
    }

    if ($email === '') {
        $email = $employeeNo . '+' . bin2hex(random_bytes(3)) . '@example.local';
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO employees (employee_no, first_name, last_name, email, department, position, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return null;
    }

    $dept = (string)($_SESSION['department'] ?? $_SESSION['Dept_id'] ?? '');
    $pos = (string)($_SESSION['position'] ?? '');
    $status = 'Active';

    mysqli_stmt_bind_param(
        $stmt,
        'sssssss',
        $employeeNo,
        $nameParts['first'],
        $nameParts['last'],
        $email,
        $dept,
        $pos,
        $status
    );

    $ok = mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!$ok || !$newId) {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM employees WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $newId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return is_array($row) ? $row : null;
}

function ess_employee_id($conn): ?int
{
    $emp = ess_ensure_employee($conn);
    if (!is_array($emp)) return null;
    $id = $emp['id'] ?? null;
    return is_numeric($id) ? (int)$id : null;
}
