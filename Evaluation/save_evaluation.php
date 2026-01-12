<?php
// save_evaluation.php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'evaluation_id' => null,
    'data' => null
];

try {
    // Validate input
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }
    
    // Sanitize input data
    $data = $config->sanitize($data);
    
    // Validate required fields
    $required_fields = ['employee_id', 'reviewer_name', 'department_id', 'evaluation_date', 'review_period'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: " . str_replace('_', ' ', $field));
        }
    }
    
    // Validate ratings
    $ratings = ['productivity_rating', 'development_rating', 'compliance_rating'];
    foreach ($ratings as $rating) {
        if (!isset($data[$rating]) || $data[$rating] < 1 || $data[$rating] > 5) {
            $data[$rating] = 3; // Default value
        }
    }
    
    // Validate employee exists
    $employee_sql = "SELECT * FROM employees WHERE employee_id = ?";
    $employee = $db->fetchSingle($employee_sql, [$data['employee_id']]);
    
    if (!$employee) {
        throw new Exception('Selected employee does not exist');
    }
    
    // Validate department exists
    $dept_sql = "SELECT * FROM departments WHERE department_id = ?";
    $department = $db->fetchSingle($dept_sql, [$data['department_id']]);
    
    if (!$department) {
        throw new Exception('Selected department does not exist');
    }
    
    // Prepare evaluation data
    $evaluation_data = [
        'employee_id' => $data['employee_id'],
        'reviewer_name' => $data['reviewer_name'],
        'department_id' => $data['department_id'],
        'evaluation_date' => $data['evaluation_date'],
        'review_period' => $data['review_period'],
        'productivity_rating' => $data['productivity_rating'],
        'development_rating' => $data['development_rating'],
        'compliance_rating' => $data['compliance_rating'],
        'additional_feedback' => $data['additional_feedback'] ?? '',
        'status' => 'submitted',
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert evaluation
    $evaluation_id = $db->insert('performance_evaluations', $evaluation_data);
    
    if (!$evaluation_id) {
        throw new Exception('Failed to save evaluation to database');
    }
    
    // Get the saved evaluation with additional info
    $evaluation_sql = "
        SELECT e.*, 
               emp.first_name, 
               emp.last_name, 
               emp.position,
               d.department_name
        FROM performance_evaluations e
        JOIN employees emp ON e.employee_id = emp.employee_id
        JOIN departments d ON e.department_id = d.department_id
        WHERE e.evaluation_id = ?
    ";
    
    $saved_evaluation = $db->fetchSingle($evaluation_sql, [$evaluation_id]);
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Evaluation submitted successfully';
    $response['evaluation_id'] = $evaluation_id;
    $response['data'] = [
        'evaluation' => $saved_evaluation,
        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'department_name' => $department['department_name'],
        'overall_rating' => $saved_evaluation['overall_rating'],
        'submitted_at' => $saved_evaluation['submitted_at']
    ];
    
    // Log successful submission
    $config->logError("Evaluation submitted: ID $evaluation_id for Employee {$data['employee_id']} by {$data['reviewer_name']}");
    
    // Send email notification (optional)
    // $this->sendEvaluationNotification($evaluation_id);
    
} catch (Exception $e) {
    // Error response
    $response['message'] = 'Error: ' . $e->getMessage();
    
    // Log error
    $config->logError("Evaluation submission error: " . $e->getMessage() . " - Data: " . json_encode($data));
}

// Return JSON response
echo json_encode($response);
exit();
?>