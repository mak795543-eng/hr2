<?php
// api/idp_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->connect();

// Handle different HTTP methods
switch($method) {
    case 'GET':
        getPlans($conn);
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['action']) && $data['action'] === 'delete') {
            deletePlan($conn, $data['id']);
        } else {
            createOrUpdatePlan($conn, $data);
        }
        break;
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        createOrUpdatePlan($conn, $data);
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        deletePlan($conn, $data['id']);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all plans
function getPlans($conn) {
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        if (!empty($search)) {
            $query = "SELECT * FROM development_plans 
                     WHERE employee LIKE :search 
                     OR dev_area LIKE :search 
                     OR training LIKE :search 
                     OR responsible LIKE :search 
                     OR status LIKE :search 
                     ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm);
        } else {
            $query = "SELECT * FROM development_plans ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $plans = $stmt->fetchAll();
        
        // Format dates for JSON
        foreach ($plans as &$plan) {
            $plan['startDate'] = $plan['start_date'];
            $plan['endDate'] = $plan['end_date'];
            unset($plan['start_date'], $plan['end_date']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $plans
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Create or update plan
function createOrUpdatePlan($conn, $data) {
    try {
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing plan
            $query = "UPDATE development_plans SET 
                     employee = :employee,
                     dev_area = :dev_area,
                     training = :training,
                     timeline = :timeline,
                     responsible = :responsible,
                     status = :status,
                     start_date = :start_date,
                     end_date = :end_date,
                     notes = :notes
                     WHERE id = :id";
                     
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $message = 'Plan updated successfully';
        } else {
            // Create new plan
            $query = "INSERT INTO development_plans 
                     (employee, dev_area, training, timeline, responsible, status, start_date, end_date, notes) 
                     VALUES 
                     (:employee, :dev_area, :training, :timeline, :responsible, :status, :start_date, :end_date, :notes)";
                     
            $stmt = $conn->prepare($query);
            $message = 'Plan created successfully';
        }
        
        $stmt->bindParam(':employee', $data['employee']);
        $stmt->bindParam(':dev_area', $data['devArea']);
        $stmt->bindParam(':training', $data['training']);
        $stmt->bindParam(':timeline', $data['timeline']);
        $stmt->bindParam(':responsible', $data['responsible']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':start_date', $data['startDate']);
        $stmt->bindParam(':end_date', $data['endDate']);
        $stmt->bindParam(':notes', $data['notes']);
        
        $stmt->execute();
        
        if (!isset($data['id'])) {
            $data['id'] = $conn->lastInsertId();
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'id' => $data['id']
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Delete plan
function deletePlan($conn, $id) {
    try {
        $query = "DELETE FROM development_plans WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Plan deleted successfully'
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>