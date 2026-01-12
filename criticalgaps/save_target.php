<?php
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Save target to database
$success = saveEmployeeTarget($data['employee_id'], [
    'target_score' => $data['target_score'],
    'target_date' => $data['target_date'],
    'development_plan' => $data['development_plan'],
    'created_by' => $data['created_by']
]);

if ($success) {
    // In a real application, you would also save the selected skills
    echo json_encode(['success' => true, 'message' => 'Target saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save target']);
}
?>