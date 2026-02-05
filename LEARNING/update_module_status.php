<?php
session_start();
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr2_soliera_usm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

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
$status_map = [
    'approved' => 'approved',
    'rejected' => 'rejected', 
    'compliance' => 'for-compliance'
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
        
        $log_sql = "INSERT INTO review_logs (module_id, action, remarks, reviewer_name, reviewer_id) 
                   VALUES (?, ?, ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        
        if ($log_stmt) {
            $log_stmt->bind_param("isssi", $module_id, $action, $remarks, $reviewer_name, $reviewer_id);
            $log_stmt->execute();
            $log_stmt->close();
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