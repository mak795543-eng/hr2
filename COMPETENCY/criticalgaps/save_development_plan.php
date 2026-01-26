<?php
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$result = saveDevelopmentPlan($data);

if ($result['success']) {
    // Update employee's last IDP date
    $conn = getDBConnection();
    $sql = "UPDATE employees SET last_idp_date = CURDATE() WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data['employee_id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Individual Development Plan created successfully',
        'plan_code' => $result['plan_code']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
?>