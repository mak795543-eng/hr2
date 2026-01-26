<?php
session_start();
header('Content-Type: application/json');

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
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->set_charset('utf8mb4');

// Get POST data
$module_id = $_POST['module_id'] ?? null;
$new_status = $_POST['new_status'] ?? null;
$remarks = $_POST['remarks'] ?? '';

// Validate input
if (!$module_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
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
    // Prepare the update statement
    $sql = "UPDATE learning_modules SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ssi", $db_status, $remarks, $module_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        // Get user info from session or use default
        $reviewer_name = $_SESSION['user_name'] ?? 'System Administrator';
        $reviewer_id = $_SESSION['user_id'] ?? 1;
        
        // Log the action in review_logs table
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
            'message' => 'Module status updated successfully',
            'module_id' => $module_id,
            'new_status' => $db_status
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No module found with the provided ID']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>