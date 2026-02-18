<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function columnExists($conn, $table, $column) {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? '';

    if ($dbName === '') {
        return false;
    }

    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->set_charset('utf8mb4');

// Get POST data
$module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
$new_status = trim((string)($_POST['new_status'] ?? ''));
$remarks = (string)($_POST['remarks'] ?? '');

// Validate input
if ($module_id <= 0 || $new_status === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

// Map status to appropriate database values
$new_status = $new_status === 'for-compliance' ? 'compliance' : $new_status;
$status_map = [
    'approved' => 'approved',
    'rejected' => 'rejected', 
    'compliance' => 'compliance'
];

if (!isset($status_map[$new_status])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$db_status = $status_map[$new_status];

try {
    $sel = $conn->prepare("SELECT id, status, remarks FROM learning_modules WHERE id = ? LIMIT 1");
    if (!$sel) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $sel->bind_param("i", $module_id);
    if (!$sel->execute()) {
        throw new Exception('Execute failed: ' . $sel->error);
    }
    $selRes = $sel->get_result();
    $existing = $selRes ? $selRes->fetch_assoc() : null;
    $sel->close();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit();
    }

    $prevStatus = (string)($existing['status'] ?? '');

    // Prepare the update statement
    $sql = "UPDATE learning_modules SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ssi", $db_status, $remarks, $module_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $reviewer_name = $_SESSION['user_name'] ?? 'System Administrator';
    $reviewer_id = $_SESSION['user_id'] ?? 1;
    $action_map = [
        'approved' => 'approved',
        'rejected' => 'rejected',
        'compliance' => 'marked for compliance'
    ];
    $action = $action_map[$new_status] ?? 'updated';

    try {
        $logModuleCol = null;
        foreach (['module_id', 'learning_module_id'] as $candidate) {
            if (columnExists($conn, 'review_logs', $candidate)) {
                $logModuleCol = $candidate;
                break;
            }
        }

        if ($logModuleCol) {
            $log_sql = "INSERT INTO review_logs (" . $logModuleCol . ", action, remarks, reviewer_name, reviewer_id)
                       VALUES (?, ?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);

            if ($log_stmt) {
                $log_stmt->bind_param("isssi", $module_id, $action, $remarks, $reviewer_name, $reviewer_id);
                $log_stmt->execute();
                $log_stmt->close();
            }
        }
    } catch (Throwable $e) {
    }

    echo json_encode([
        'success' => true,
        'message' => ($prevStatus === $db_status ? 'Module status unchanged (already set)' : 'Module status updated successfully'),
        'module_id' => $module_id,
        'previous_status' => $prevStatus,
        'new_status' => $db_status
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
