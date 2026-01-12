<?php
// config.php - API Endpoints
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once 'db.php';

// Function to get all data
function getAllData($conn) {
    $data = [];
    
    // Get employees
    $result = $conn->query("SELECT * FROM employees ORDER BY name");
    $data['employees'] = [];
    while($row = $result->fetch_assoc()) {
        $data['employees'][] = $row;
    }
    
    // Get competencies
    $result = $conn->query("SELECT * FROM competencies ORDER BY type, competency_name");
    $data['competencies'] = [];
    while($row = $result->fetch_assoc()) {
        $data['competencies'][] = $row;
    }
    
    // Get employee competencies with calculated gap
    $result = $conn->query("
        SELECT ec.*, 
               e.name as employee_name, 
               e.department, 
               e.position,
               c.competency_name,
               c.type as competency_type,
               c.category,
               c.required_level,
               c.description,
               ap.plan_name,
               ap.action_type,
               ap.status as plan_status,
               ap.progress_percentage
        FROM employee_competencies ec
        JOIN employees e ON ec.employee_id = e.id
        JOIN competencies c ON ec.competency_id = c.id
        LEFT JOIN action_plans ap ON ec.id = ap.employee_competency_id
        ORDER BY ec.priority DESC, ec.gap_score DESC
    ");
    $data['employee_competencies'] = [];
    while($row = $result->fetch_assoc()) {
        $data['employee_competencies'][] = $row;
    }
    
    // Get action plans
    $result = $conn->query("
        SELECT ap.*,
               e.name as employee_name,
               c.competency_name
        FROM action_plans ap
        JOIN employee_competencies ec ON ap.employee_competency_id = ec.id
        JOIN employees e ON ec.employee_id = e.id
        JOIN competencies c ON ec.competency_id = c.id
        ORDER BY ap.status, ap.start_date
    ");
    $data['action_plans'] = [];
    while($row = $result->fetch_assoc()) {
        $data['action_plans'][] = $row;
    }
    
    return $data;
}

// Function to get filtered data
function getFilteredData($conn, $filters) {
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Build WHERE clause based on filters
    if (!empty($filters['department']) && $filters['department'] !== 'all') {
        $whereConditions[] = "e.department = ?";
        $params[] = $filters['department'];
        $types .= "s";
    }
    
    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $whereConditions[] = "c.type = ?";
        $params[] = $filters['type'];
        $types .= "s";
    }
    
    if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
        $whereConditions[] = "ec.priority = ?";
        $params[] = $filters['priority'];
        $types .= "s";
    }
    
    // Build SQL query
    $sql = "
        SELECT 
            ec.id as gap_id,
            e.name as employee_name,
            e.department as employee_department,
            e.position,
            c.competency_name,
            c.type as competency_type,
            c.category,
            c.required_level,
            ec.actual_level,
            ec.target_level,
            ec.gap_score,
            ec.priority,
            ec.status as gap_status,
            ec.last_assessment_date,
            ec.notes as gap_notes,
            ap.plan_name,
            ap.action_type,
            ap.status as plan_status,
            ap.progress_percentage
        FROM employee_competencies ec
        JOIN employees e ON ec.employee_id = e.id
        JOIN competencies c ON ec.competency_id = c.id
        LEFT JOIN action_plans ap ON ec.id = ap.employee_competency_id
    ";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY ec.priority DESC, ec.gap_score DESC";
    
    // Prepare and execute statement
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }
    
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get action plan details
function getActionPlanDetails($conn, $gap_id) {
    $sql = "
        SELECT ap.*, 
               e.name as employee_name,
               c.competency_name
        FROM action_plans ap
        JOIN employee_competencies ec ON ap.employee_competency_id = ec.id
        JOIN employees e ON ec.employee_id = e.id
        JOIN competencies c ON ec.competency_id = c.id
        WHERE ap.employee_competency_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gap_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

// Function to save or update action plan
function saveActionPlan($conn, $data) {
    if (empty($data['gap_id'])) {
        return ['error' => 'Gap ID is required'];
    }
    
    $gap_id = $data['gap_id'];
    $plan_name = $data['plan_name'];
    $action_type = $data['action_type'];
    $description = $data['description'];
    $resources = $data['resources'];
    $start_date = $data['start_date'];
    $end_date = $data['end_date'];
    $estimated_hours = $data['estimated_hours'];
    $created_by = $data['created_by'];
    
    // Check if action plan already exists
    $check_sql = "SELECT id FROM action_plans WHERE employee_competency_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $gap_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing plan
        $row = $check_result->fetch_assoc();
        $plan_id = $row['id'];
        
        $sql = "UPDATE action_plans SET 
                plan_name = ?, 
                action_type = ?, 
                description = ?, 
                resources_needed = ?, 
                start_date = ?, 
                end_date = ?, 
                estimated_hours = ?,
                created_by = ?,
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssisi", 
            $plan_name, $action_type, $description, $resources, 
            $start_date, $end_date, $estimated_hours, $created_by, $plan_id);
    } else {
        // Insert new plan
        $sql = "INSERT INTO action_plans 
                (employee_competency_id, plan_name, action_type, description, 
                 resources_needed, start_date, end_date, estimated_hours, 
                 created_by, status, progress_percentage) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Planned', 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssis", 
            $gap_id, $plan_name, $action_type, $description, 
            $resources, $start_date, $end_date, $estimated_hours, $created_by);
    }
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Action plan saved successfully'];
    } else {
        return ['error' => 'Failed to save action plan: ' . $stmt->error];
    }
}

// Function to get summary statistics
function getSummaryStats($conn) {
    $stats = [];
    
    // Total employees
    $result = $conn->query("SELECT COUNT(*) as total FROM employees");
    $stats['totalEmployees'] = $result->fetch_assoc()['total'];
    
    // Average gap
    $result = $conn->query("SELECT AVG(gap_score) as avg_gap FROM employee_competencies");
    $avg = $result->fetch_assoc()['avg_gap'];
    $stats['averageGap'] = $avg ? round($avg, 1) : 0;
    
    // Critical gaps
    $result = $conn->query("SELECT COUNT(*) as critical FROM employee_competencies WHERE priority = 'Critical'");
    $stats['criticalGaps'] = $result->fetch_assoc()['critical'];
    
    // Active action plans
    $result = $conn->query("SELECT COUNT(*) as active FROM action_plans WHERE status IN ('Planned', 'In Progress')");
    $stats['activePlans'] = $result->fetch_assoc()['active'];
    
    // Departments list
    $result = $conn->query("SELECT DISTINCT department FROM employees ORDER BY department");
    $stats['departments'] = [];
    while($row = $result->fetch_assoc()) {
        $stats['departments'][] = $row['department'];
    }
    
    // Competency types
    $result = $conn->query("SELECT DISTINCT type FROM competencies ORDER BY type");
    $stats['types'] = [];
    while($row = $result->fetch_assoc()) {
        $stats['types'][] = $row['type'];
    }
    
    return $stats;
}

// Function to get chart data
function getChartData($conn, $filters = []) {
    $whereConditions = [];
    $params = [];
    $types = "";
    
    if (!empty($filters['department']) && $filters['department'] !== 'all') {
        $whereConditions[] = "e.department = ?";
        $params[] = $filters['department'];
        $types .= "s";
    }
    
    // Data for bar chart (average gap by competency)
    $sql = "
        SELECT c.competency_name, 
               AVG(ec.target_level) as avg_target,
               AVG(ec.actual_level) as avg_actual,
               AVG(ec.gap_score) as avg_gap,
               COUNT(*) as count
        FROM employee_competencies ec
        JOIN competencies c ON ec.competency_id = c.id
        JOIN employees e ON ec.employee_id = e.id
    ";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " GROUP BY c.id, c.competency_name ORDER BY avg_gap DESC LIMIT 8";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }
    
    $chartData['barChart'] = [];
    while($row = $result->fetch_assoc()) {
        $chartData['barChart'][] = $row;
    }
    
    // Data for radar chart (average gap by competency type)
    $sql = "
        SELECT c.type, 
               AVG(ec.gap_score) as avg_gap,
               COUNT(*) as count
        FROM employee_competencies ec
        JOIN competencies c ON ec.competency_id = c.id
        JOIN employees e ON ec.employee_id = e.id
    ";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " GROUP BY c.type ORDER BY c.type";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }
    
    $chartData['radarChart'] = [];
    while($row = $result->fetch_assoc()) {
        $chartData['radarChart'][] = $row;
    }
    
    return $chartData;
}

// Main API routing
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getAllData':
                echo json_encode(getAllData($conn));
                break;
                
            case 'getFilteredData':
                $filters = [
                    'department' => $_GET['department'] ?? 'all',
                    'type' => $_GET['type'] ?? 'all',
                    'priority' => $_GET['priority'] ?? 'all'
                ];
                echo json_encode(getFilteredData($conn, $filters));
                break;
                
            case 'getActionPlan':
                if (isset($_GET['gap_id'])) {
                    echo json_encode(getActionPlanDetails($conn, $_GET['gap_id']));
                } else {
                    echo json_encode(['error' => 'gap_id parameter is required']);
                }
                break;
                
            case 'getSummaryStats':
                echo json_encode(getSummaryStats($conn));
                break;
                
            case 'getChartData':
                $filters = [
                    'department' => $_GET['department'] ?? 'all'
                ];
                echo json_encode(getChartData($conn, $filters));
                break;
                
            case 'test':
                echo json_encode(['status' => 'success', 'message' => 'API is working']);
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['status' => 'API is running', 'endpoints' => [
            'getAllData', 'getFilteredData', 'getActionPlan', 'getSummaryStats', 'getChartData'
        ]]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'saveActionPlan') {
        $data = [
            'gap_id' => $input['gap_id'] ?? '',
            'plan_name' => $input['plan_name'] ?? '',
            'action_type' => $input['action_type'] ?? '',
            'description' => $input['description'] ?? '',
            'resources' => $input['resources'] ?? '',
            'start_date' => $input['start_date'] ?? '',
            'end_date' => $input['end_date'] ?? '',
            'estimated_hours' => $input['estimated_hours'] ?? 0,
            'created_by' => $input['created_by'] ?? 'System'
        ];
        
        echo json_encode(saveActionPlan($conn, $data));
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
}

$conn->close();
?>