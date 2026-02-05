<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr2_soliera_usm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

// Handle AJAX module status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id']) && isset($_POST['new_status'])) {
    $module_id = $_POST['module_id'];
    $new_status = $_POST['new_status'];
    $remarks = $_POST['remarks'] ?? '';
    
    // Validate inputs
    if (empty($module_id) || empty($new_status)) {
        echo json_encode(['success' => false, 'message' => 'Module ID and status are required']);
        exit();
    }
    
    // Update module status
    $sql = "UPDATE learning_modules SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssi", $new_status, $remarks, $module_id);
        
        if ($stmt->execute()) {
            // Check if rows were affected
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Module status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No module found with the specified ID']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating module status: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
exit();
?>