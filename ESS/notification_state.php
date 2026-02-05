<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);
if (!$conn || !is_int($employeeId) || $employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$key = trim((string)($_POST['key'] ?? ''));

if ($key === '' || !preg_match('/^[a-f0-9]{40}$/i', $key)) {
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

if (!in_array($action, ['read', 'archive', 'delete'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    if ($action === 'delete') {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO notification_states (employee_id, notif_key, status, deleted)
             VALUES (?, ?, 'read', 1)
             ON DUPLICATE KEY UPDATE deleted = 1, notif_date = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            throw new RuntimeException('DB error');
        }
        mysqli_stmt_bind_param($stmt, 'is', $employeeId, $key);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'status' => 'deleted']);
        exit;
    }

    if ($action === 'archive') {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO notification_states (employee_id, notif_key, status, deleted)
             VALUES (?, ?, 'archived', 0)
             ON DUPLICATE KEY UPDATE status = 'archived', deleted = 0, notif_date = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            throw new RuntimeException('DB error');
        }
        mysqli_stmt_bind_param($stmt, 'is', $employeeId, $key);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'status' => 'archived']);
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO notification_states (employee_id, notif_key, status, deleted)
         VALUES (?, ?, 'read', 0)
         ON DUPLICATE KEY UPDATE status = 'read', deleted = 0, notif_date = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP"
    );
    if (!$stmt) {
        throw new RuntimeException('DB error');
    }
    mysqli_stmt_bind_param($stmt, 'is', $employeeId, $key);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['success' => true, 'status' => 'read']);
    exit;
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
