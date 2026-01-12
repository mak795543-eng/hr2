<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '/db_config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$request_id = $_GET['id'] ?? '';

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Main API router
switch($endpoint) {
    case 'requests':
        handleRequests($method, $request_id);
        break;
    case 'location-request':
        handleLocationRequest($method, $request_id);
        break;
    case 'budget-request':
        handleBudgetRequest($method, $request_id);
        break;
    case 'logistics-request':
        handleLogisticsRequest($method, $request_id);
        break;
    case 'departments':
        handleDepartments($method);
        break;
    case 'locations':
        handleLocations($method);
        break;
    case 'logistics-items':
        handleLogisticsItems($method);
        break;
    case 'generate-id':
        generateRequestId();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// ============================================================================
// REQUEST HANDLERS
// ============================================================================

function handleRequests($method, $id) {
    $conn = getDBConnection();
    
    switch($method) {
        case 'GET':
            if ($id) {
                // Get single request
                getRequest($conn, $id);
            } else {
                // Get all requests
                getRequests($conn);
            }
            break;
            
        case 'POST':
            // Create new request
            createRequest($conn);
            break;
            
        case 'PUT':
            // Update request status
            updateRequestStatus($conn, $id);
            break;
            
        case 'DELETE':
            // Delete request (draft only)
            deleteRequest($conn, $id);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
    $conn->close();
}

function handleLocationRequest($method, $id) {
    $conn = getDBConnection();
    
    switch($method) {
        case 'POST':
            createLocationRequest($conn);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
    $conn->close();
}

function handleBudgetRequest($method, $id) {
    $conn = getDBConnection();
    
    switch($method) {
        case 'POST':
            createBudgetRequest($conn);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
    $conn->close();
}

function handleLogisticsRequest($method, $id) {
    $conn = getDBConnection();
    
    switch($method) {
        case 'POST':
            createLogisticsRequest($conn);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
    $conn->close();
}

// ============================================================================
// REQUEST FUNCTIONS
// ============================================================================

function getRequests($conn) {
    // Get user ID from session (in real app, this would be from authentication)
    $user_id = 1; // Default for demo
    
    $sql = "SELECT 
                r.request_id,
                r.request_type,
                r.reference_type,
                r.reference_id,
                r.request_status,
                r.purpose,
                r.remarks,
                r.created_at,
                r.updated_at,
                db.department_name as requested_by_department,
                dt.department_name as requested_to_department
            FROM request r
            LEFT JOIN department db ON r.requested_by_department_id = db.department_id
            LEFT JOIN department dt ON r.requested_to_department_id = dt.department_id
            WHERE r.requested_by_user_id = ?
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while($row = $result->fetch_assoc()) {
        // Get additional details based on request type
        $request_id = $row['request_id'];
        
        switch($row['request_type']) {
            case 'LOCATION':
                $location_details = getLocationRequestDetails($conn, $request_id);
                $row['location_details'] = $location_details;
                $row['training_title'] = $location_details['training_title'] ?? '';
                break;
                
            case 'BUDGET':
                $budget_details = getBudgetRequestDetails($conn, $request_id);
                $row['budget_details'] = $budget_details;
                $row['training_title'] = $budget_details['training_title'] ?? '';
                break;
                
            case 'LOGISTICS':
                $logistics_details = getLogisticsRequestDetails($conn, $request_id);
                $row['logistics_details'] = $logistics_details;
                $row['training_title'] = $logistics_details['training_title'] ?? '';
                break;
        }
        
        $requests[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $requests
    ]);
}

function getRequest($conn, $id) {
    $sql = "SELECT * FROM request WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Request not found'
        ]);
    }
}

function createRequest($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // In real app, get from session
    $user_id = 1;
    $department_id = getDepartmentId($conn, $data['department']);
    
    $sql = "INSERT INTO request (
                request_type,
                reference_type,
                reference_id,
                requested_by_user_id,
                requested_by_department_id,
                requested_to_department_id,
                request_status,
                purpose,
                remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssiiiiiss",
        $data['request_type'],
        $data['reference_type'],
        $data['reference_id'],
        $user_id,
        $department_id,
        $data['requested_to_department_id'],
        $data['status'],
        $data['purpose'],
        $data['remarks']
    );
    
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'request_id' => $request_id,
            'message' => 'Request created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create request'
        ]);
    }
}

function createLocationRequest($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // First create the main request
    $request_sql = "INSERT INTO request (
                        request_type,
                        reference_type,
                        reference_id,
                        requested_by_user_id,
                        requested_by_department_id,
                        requested_to_department_id,
                        request_status,
                        purpose,
                        remarks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $user_id = 1; // From session
    $department_id = getDepartmentId($conn, $data['department']);
    
    $stmt = $conn->prepare($request_sql);
    $stmt->bind_param(
        "ssiiiiiss",
        'LOCATION',
        $data['reference_type'],
        $data['reference_id'],
        $user_id,
        $department_id,
        $data['requested_to_department_id'],
        $data['status'],
        $data['purpose'],
        $data['remarks']
    );
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create request'
        ]);
        return;
    }
    
    $request_id = $conn->insert_id;
    
    // Create location request
    $location_sql = "INSERT INTO location_request (
                        request_id,
                        location_id,
                        event_date,
                        start_time,
                        end_time,
                        location_status
                    ) VALUES (?, ?, ?, ?, ?, 'PENDING')";
    
    $stmt = $conn->prepare($location_sql);
    $stmt->bind_param(
        "iisss",
        $request_id,
        $data['location_id'],
        $data['event_date'],
        $data['start_time'],
        $data['end_time']
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'request_id' => $request_id,
            'message' => 'Location request created successfully'
        ]);
    } else {
        // Rollback request if location request fails
        $conn->query("DELETE FROM request WHERE request_id = $request_id");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create location request'
        ]);
    }
}

function createBudgetRequest($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create main request
        $user_id = 1;
        $department_id = getDepartmentId($conn, $data['department']);
        
        $request_sql = "INSERT INTO request (
                            request_type,
                            reference_type,
                            reference_id,
                            requested_by_user_id,
                            requested_by_department_id,
                            requested_to_department_id,
                            request_status,
                            purpose,
                            remarks
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($request_sql);
        $stmt->bind_param(
            "ssiiiiiss",
            'BUDGET',
            $data['reference_type'],
            $data['reference_id'],
            $user_id,
            $department_id,
            $data['requested_to_department_id'],
            $data['status'],
            $data['purpose'],
            $data['remarks']
        );
        $stmt->execute();
        $request_id = $conn->insert_id;
        
        // Create budget request
        $budget_sql = "INSERT INTO budget_request (
                          request_id,
                          estimated_amount,
                          financial_status
                      ) VALUES (?, ?, 'PENDING')";
        
        $stmt = $conn->prepare($budget_sql);
        $stmt->bind_param(
            "id",
            $request_id,
            $data['estimated_amount']
        );
        $stmt->execute();
        $budget_request_id = $conn->insert_id;
        
        // Create budget items
        foreach ($data['budget_items'] as $item) {
            $item_sql = "INSERT INTO budget_request_item (
                            budget_request_id,
                            expense_category,
                            description,
                            quantity,
                            unit_cost,
                            total_cost
                        ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($item_sql);
            $total_cost = $item['quantity'] * $item['unit_cost'];
            $stmt->bind_param(
                "issidd",
                $budget_request_id,
                $item['category'],
                $item['description'],
                $item['quantity'],
                $item['unit_cost'],
                $total_cost
            );
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'request_id' => $request_id,
            'message' => 'Budget request created successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create budget request: ' . $e->getMessage()
        ]);
    }
}

function createLogisticsRequest($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create main request
        $user_id = 1;
        $department_id = getDepartmentId($conn, $data['department']);
        
        $request_sql = "INSERT INTO request (
                            request_type,
                            reference_type,
                            reference_id,
                            requested_by_user_id,
                            requested_by_department_id,
                            requested_to_department_id,
                            request_status,
                            purpose,
                            remarks
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($request_sql);
        $stmt->bind_param(
            "ssiiiiiss",
            'LOGISTICS',
            $data['reference_type'],
            $data['reference_id'],
            $user_id,
            $department_id,
            $data['requested_to_department_id'],
            $data['status'],
            $data['purpose'],
            $data['remarks']
        );
        $stmt->execute();
        $request_id = $conn->insert_id;
        
        // Create logistics request
        $logistics_sql = "INSERT INTO logistics_request (
                             request_id,
                             borrow_date,
                             return_date,
                             event_location,
                             logistics_status
                         ) VALUES (?, ?, ?, ?, 'PENDING')";
        
        $stmt = $conn->prepare($logistics_sql);
        $stmt->bind_param(
            "isss",
            $request_id,
            $data['borrow_date'],
            $data['return_date'],
            $data['event_location']
        );
        $stmt->execute();
        $logistics_request_id = $conn->insert_id;
        
        // Create logistics items
        foreach ($data['logistics_items'] as $item) {
            $item_sql = "INSERT INTO logistics_request_item (
                            logistics_request_id,
                            item_id,
                            quantity_requested,
                            item_status
                        ) VALUES (?, ?, ?, 'PENDING')";
            
            $stmt = $conn->prepare($item_sql);
            $stmt->bind_param(
                "iii",
                $logistics_request_id,
                $item['item_id'],
                $item['quantity']
            );
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'request_id' => $request_id,
            'message' => 'Logistics request created successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create logistics request: ' . $e->getMessage()
        ]);
    }
}

function updateRequestStatus($conn, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "UPDATE request SET 
                request_status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE request_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data['status'], $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Request status updated'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update request status'
        ]);
    }
}

function deleteRequest($conn, $id) {
    // Only allow deletion of draft requests
    $sql = "DELETE FROM request WHERE request_id = ? AND request_status = 'DRAFT'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Request deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete request or request is not a draft'
        ]);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function getLocationRequestDetails($conn, $request_id) {
    $sql = "SELECT lr.*, l.location_name, l.location_type, l.capacity
            FROM location_request lr
            JOIN location l ON lr.location_id = l.location_id
            WHERE lr.request_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function getBudgetRequestDetails($conn, $request_id) {
    $sql = "SELECT br.*, 
                   (SELECT SUM(total_cost) FROM budget_request_item WHERE budget_request_id = br.budget_request_id) as total_amount
            FROM budget_request br
            WHERE br.request_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget_details = $result->fetch_assoc();
    
    // Get budget items
    if ($budget_details) {
        $items_sql = "SELECT * FROM budget_request_item WHERE budget_request_id = ?";
        $stmt = $conn->prepare($items_sql);
        $stmt->bind_param("i", $budget_details['budget_request_id']);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        $items = [];
        while($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $budget_details['items'] = $items;
    }
    
    return $budget_details;
}

function getLogisticsRequestDetails($conn, $request_id) {
    $sql = "SELECT lr.* FROM logistics_request lr WHERE lr.request_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $logistics_details = $result->fetch_assoc();
    
    // Get logistics items
    if ($logistics_details) {
        $items_sql = "SELECT lri.*, li.item_name, li.item_category
                      FROM logistics_request_item lri
                      JOIN logistics_item li ON lri.item_id = li.item_id
                      WHERE lri.logistics_request_id = ?";
        
        $stmt = $conn->prepare($items_sql);
        $stmt->bind_param("i", $logistics_details['logistics_request_id']);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        $items = [];
        while($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $logistics_details['items'] = $items;
    }
    
    return $logistics_details;
}

function getDepartmentId($conn, $department_name) {
    // Map department names to IDs
    $department_map = [
        'hr' => 1,
        'it' => 2,
        'operations' => 3,
        'marketing' => 4,
        'sales' => 5,
        'admin' => 6,
        'finance' => 7,
        'logistics' => 8,
        'facilities' => 9
    ];
    
    return $department_map[strtolower($department_name)] ?? 1;
}

function handleDepartments($method) {
    $departments = [
        ['id' => 1, 'name' => 'HR Department', 'code' => 'hr'],
        ['id' => 2, 'name' => 'IT Department', 'code' => 'it'],
        ['id' => 3, 'name' => 'Operations', 'code' => 'operations'],
        ['id' => 4, 'name' => 'Marketing', 'code' => 'marketing'],
        ['id' => 5, 'name' => 'Sales', 'code' => 'sales'],
        ['id' => 6, 'name' => 'Admin Department', 'code' => 'admin'],
        ['id' => 7, 'name' => 'Finance Department', 'code' => 'finance'],
        ['id' => 8, 'name' => 'Logistics Department', 'code' => 'logistics'],
        ['id' => 9, 'name' => 'Facilities Management', 'code' => 'facilities']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
}

function handleLocations($method) {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM location WHERE availability_status = 'AVAILABLE'";
    $result = $conn->query($sql);
    
    $locations = [];
    while($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $locations
    ]);
}

function handleLogisticsItems($method) {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM logistics_item WHERE available_quantity > 0";
    $result = $conn->query($sql);
    
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
}

function generateRequestId() {
    // This would generate a proper request ID based on your business logic
    // For now, return a timestamp-based ID
    $prefix = $_GET['type'] ?? 'REQ';
    $id = $prefix . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'request_id' => $id
    ]);
}
?>