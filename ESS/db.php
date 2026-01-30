<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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
    }
}

if ($conn) {
    ess_ensure_profile_tables($conn);
}

function ess_current_employee_no(): string
{
    return (string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? '');
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

    if ($email !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT * FROM employees WHERE email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
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
