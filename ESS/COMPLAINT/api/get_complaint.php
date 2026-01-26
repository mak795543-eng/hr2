<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Complaint ID is required']);
    exit();
}

$id = (int)$_GET['id'];

// Get complaint details
$sql = "SELECT 
            c.*,
            cat.name as category_name,
            u.first_name as employee_first_name,
            u.last_name as employee_last_name,
            u.email as employee_email,
            u.department as employee_department,
            u.position as employee_position,
            a.first_name as assigned_first_name,
            a.last_name as assigned_last_name,
            a.email as assigned_email
        FROM complaints c
        LEFT JOIN complaint_categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.employee_id = u.id
        LEFT JOIN users a ON c.assigned_to = a.id
        WHERE c.id = :id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

if ($complaint) {
    echo json_encode($complaint);
} else {
    echo json_encode(['error' => 'Complaint not found']);
}
?>