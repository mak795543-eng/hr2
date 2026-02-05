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
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$exam_id = $_POST['exam_id'] ?? null;
$new_status = $_POST['new_status'] ?? null;
$remarks = $_POST['remarks'] ?? '';

if (!$exam_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get the exam data from examinations table
    $sql = "SELECT * FROM examinations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();
    $stmt->close();
    
    if (!$exam) {
        throw new Exception("Examination not found");
    }
    
    // 2. Update the status in examinations table
    $update_sql = "UPDATE examinations SET status = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $exam_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // 3. Check if exam already exists in repository
    $check_sql = "SELECT id FROM exam_repository WHERE original_exam_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $exam_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $exists = $check_result->num_rows > 0;
    $check_stmt->close();
    
    if ($exists) {
        // Update existing record in repository
        $repo_sql = "UPDATE exam_repository SET 
                     status = ?, 
                     remarks = ?,
                     updated_at = NOW() 
                     WHERE original_exam_id = ?";
        $repo_stmt = $conn->prepare($repo_sql);
        $repo_stmt->bind_param("ssi", $new_status, $remarks, $exam_id);
    } else {
        // Insert new record into repository
        $repo_sql = "INSERT INTO exam_repository 
                     (original_exam_id, title, description, department, roles, 
                      duration, passing_score, module_id, status, remarks, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $repo_stmt = $conn->prepare($repo_sql);
        $repo_stmt->bind_param("issssiisss", 
            $exam_id,
            $exam['title'],
            $exam['description'],
            $exam['department'],
            $exam['roles'],
            $exam['duration'],
            $exam['passing_score'],
            $exam['module_id'],
            $new_status,
            $remarks
        );
    }
    
    $repo_stmt->execute();
    $repo_stmt->close();
    
    // 4. Add to audit trail
    $audit_sql = "INSERT INTO exam_audit_trail 
                  (exam_id, action, status_before, status_after, remarks, user_id, created_at) 
                  VALUES (?, 'status_update', ?, ?, ?, ?, NOW())";
    $audit_stmt = $conn->prepare($audit_sql);
    $user_id = $_SESSION['user_id'] ?? 0;
    $audit_stmt->bind_param("isssi", $exam_id, $exam['status'], $new_status, $remarks, $user_id);
    $audit_stmt->execute();
    $audit_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Examination status updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>