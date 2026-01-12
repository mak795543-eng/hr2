<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "job_desc";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// Handle different endpoints
switch($endpoint) {
    case 'job-roles':
        handleJobRoles($method, $conn);
        break;
    case 'departments':
        handleDepartments($method, $conn);
        break;
    case 'statistics':
        handleStatistics($method, $conn);
        break;
    case 'search':
        handleSearch($method, $conn);
        break;
    default:
        echo json_encode(["error" => "Invalid endpoint"]);
        break;
}

$conn->close();

// Job Roles endpoints
function handleJobRoles($method, $conn) {
    switch($method) {
        case 'GET':
            $request_id = $_GET['id'] ?? null;
            $department_id = $_GET['department_id'] ?? null;
            if ($request_id) {
                getJobRole($conn, $request_id);
            } else {
                getAllJobRoles($conn, $department_id);
            }
            break;
        case 'PUT':
            updateJobRole($conn);
            break;
        default:
            echo json_encode(["error" => "Method not allowed"]);
    }
}

// Departments endpoints
function handleDepartments($method, $conn) {
    if ($method === 'GET') {
        getAllDepartments($conn);
    } else {
        echo json_encode(["error" => "Method not allowed"]);
    }
}

// Statistics endpoint
function handleStatistics($method, $conn) {
    if ($method === 'GET') {
        $department_id = $_GET['department_id'] ?? null;
        getStatistics($conn, $department_id);
    } else {
        echo json_encode(["error" => "Method not allowed"]);
    }
}

// Search endpoint
function handleSearch($method, $conn) {
    if ($method === 'GET') {
        $search_query = $_GET['query'] ?? '';
        $department_id = $_GET['department_id'] ?? null;
        searchJobRoles($conn, $search_query, $department_id);
    } else {
        echo json_encode(["error" => "Method not allowed"]);
    }
}

// Get all job roles with optional department filter
function getAllJobRoles($conn, $department_id = null) {
    $sql = "SELECT jr.*, d.name as department_name 
            FROM job_roles jr 
            LEFT JOIN departments d ON jr.department_id = d.request_id";
    
    $params = [];
    $types = "";
    
    if ($department_id) {
        $sql .= " WHERE jr.department_id = ?";
        $params[] = $department_id;
        $types .= "s";
    }
    
    $sql .= " ORDER BY jr.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $jobRoles = [];
    while($row = $result->fetch_assoc()) {
        $request_id = $row['request_id'];
        
        // Get qualifications
        $qual_sql = "SELECT qualification as text, type 
                     FROM qualifications 
                     WHERE request_id = ? 
                     ORDER BY priority, created_at";
        $qual_stmt = $conn->prepare($qual_sql);
        $qual_stmt->bind_param("s", $request_id);
        $qual_stmt->execute();
        $qual_result = $qual_stmt->get_result();
        $qualifications = [];
        while($qual_row = $qual_result->fetch_assoc()) {
            $qualifications[] = $qual_row;
        }
        $qual_stmt->close();
        
        // Get requirements
        $req_sql = "SELECT requirement as text, category, is_essential as essential 
                    FROM job_requirements 
                    WHERE request_id = ? 
                    ORDER BY created_at";
        $req_stmt = $conn->prepare($req_sql);
        $req_stmt->bind_param("s", $request_id);
        $req_stmt->execute();
        $req_result = $req_stmt->get_result();
        $requirements = [];
        while($req_row = $req_result->fetch_assoc()) {
            $requirements[] = $req_row;
        }
        $req_stmt->close();
        
        $row['qualifications'] = $qualifications;
        $row['requirements'] = $requirements;
        $jobRoles[] = $row;
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    echo json_encode($jobRoles);
}

// Get single job role
function getJobRole($conn, $request_id) {
    // Get job role
    $sql = "SELECT jr.*, d.name as department_name 
            FROM job_roles jr 
            LEFT JOIN departments d ON jr.department_id = d.request_id
            WHERE jr.request_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Get qualifications
        $qual_sql = "SELECT qualification as text, type 
                     FROM qualifications 
                     WHERE request_id = ? 
                     ORDER BY priority, created_at";
        $qual_stmt = $conn->prepare($qual_sql);
        $qual_stmt->bind_param("s", $request_id);
        $qual_stmt->execute();
        $qual_result = $qual_stmt->get_result();
        $qualifications = [];
        while($qual_row = $qual_result->fetch_assoc()) {
            $qualifications[] = $qual_row;
        }
        $qual_stmt->close();
        
        // Get requirements
        $req_sql = "SELECT requirement as text, category, is_essential as essential 
                    FROM job_requirements 
                    WHERE request_id = ? 
                    ORDER BY created_at";
        $req_stmt = $conn->prepare($req_sql);
        $req_stmt->bind_param("s", $request_id);
        $req_stmt->execute();
        $req_result = $req_stmt->get_result();
        $requirements = [];
        while($req_row = $req_result->fetch_assoc()) {
            $requirements[] = $req_row;
        }
        $req_stmt->close();
        
        $row['qualifications'] = $qualifications;
        $row['requirements'] = $requirements;
        
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Job role not found"]);
    }
    $stmt->close();
}

// Update job role
function updateJobRole($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $request_id = $data['request_id'] ?? null;
    
    if (!$request_id) {
        echo json_encode(["error" => "request_id is required"]);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update job description
        $sql = "UPDATE job_roles SET description = ?, updated_at = NOW() WHERE request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $data['description'], $request_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete existing qualifications
        $del_qual_sql = "DELETE FROM qualifications WHERE request_id = ?";
        $del_qual_stmt = $conn->prepare($del_qual_sql);
        $del_qual_stmt->bind_param("s", $request_id);
        $del_qual_stmt->execute();
        $del_qual_stmt->close();
        
        // Insert new qualifications
        if (!empty($data['qualifications'])) {
            $qual_sql = "INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)";
            $qual_stmt = $conn->prepare($qual_sql);
            $priority = 1;
            foreach ($data['qualifications'] as $qual) {
                $qual_stmt->bind_param("sssi", $request_id, $qual['text'], $qual['type'], $priority);
                $qual_stmt->execute();
                $priority++;
            }
            $qual_stmt->close();
        }
        
        // Delete existing requirements
        $del_req_sql = "DELETE FROM job_requirements WHERE request_id = ?";
        $del_req_stmt = $conn->prepare($del_req_sql);
        $del_req_stmt->bind_param("s", $request_id);
        $del_req_stmt->execute();
        $del_req_stmt->close();
        
        // Insert new requirements
        if (!empty($data['requirements'])) {
            $req_sql = "INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)";
            $req_stmt = $conn->prepare($req_sql);
            foreach ($data['requirements'] as $req) {
                $req_stmt->bind_param("sssi", $request_id, $req['text'], $req['category'], $req['essential']);
                $req_stmt->execute();
            }
            $req_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(["success" => true, "message" => "Job role updated successfully"]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(["error" => "Failed to update job role: " . $e->getMessage()]);
    }
}

// Get all departments
function getAllDepartments($conn) {
    $sql = "SELECT * FROM departments ORDER BY name";
    $result = $conn->query($sql);
    
    $departments = [];
    while($row = $result->fetch_assoc()) {
        // Get job count for each department
        $count_sql = "SELECT COUNT(*) as job_count FROM job_roles WHERE department_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("s", $row['request_id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $row['job_count'] = $count_row['job_count'];
        $count_stmt->close();
        
        $departments[] = $row;
    }
    
    echo json_encode($departments);
}

// Get statistics with optional department filter
function getStatistics($conn, $department_id = null) {
    $stats = [];
    
    // Build WHERE clause for department filter
    $where_clause = "";
    $params = [];
    $types = "";
    
    if ($department_id) {
        $where_clause = " WHERE department_id = ?";
        $params[] = $department_id;
        $types .= "s";
    }
    
    // Total job roles
    $sql1 = "SELECT COUNT(*) as total FROM job_roles" . $where_clause;
    if (!empty($params)) {
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param($types, ...$params);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $stats['totalRoles'] = $result1->fetch_assoc()['total'];
        $stmt1->close();
    } else {
        $result1 = $conn->query($sql1);
        $stats['totalRoles'] = $result1->fetch_assoc()['total'];
    }
    
    // Total vacancies
    $sql2 = "SELECT SUM(vacancies) as total FROM job_roles" . $where_clause;
    if (!empty($params)) {
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param($types, ...$params);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $stats['totalVacancies'] = $result2->fetch_assoc()['total'] ?? 0;
        $stmt2->close();
    } else {
        $result2 = $conn->query($sql2);
        $stats['totalVacancies'] = $result2->fetch_assoc()['total'] ?? 0;
    }
    
    // Total departments (only when not filtered)
    if (!$department_id) {
        $sql3 = "SELECT COUNT(*) as total FROM departments";
        $result3 = $conn->query($sql3);
        $stats['totalDepartments'] = $result3->fetch_assoc()['total'];
    } else {
        $stats['totalDepartments'] = 1; // When filtered, only showing one department
    }
    
    // Total requirements (with department filter)
    if ($department_id) {
        $sql4 = "SELECT COUNT(*) as total FROM job_requirements jr 
                 JOIN job_roles j ON jr.request_id = j.request_id 
                 WHERE j.department_id = ?";
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bind_param("s", $department_id);
        $stmt4->execute();
        $result4 = $stmt4->get_result();
        $stats['totalRequirements'] = $result4->fetch_assoc()['total'];
        $stmt4->close();
    } else {
        $sql4 = "SELECT COUNT(*) as total FROM job_requirements";
        $result4 = $conn->query($sql4);
        $stats['totalRequirements'] = $result4->fetch_assoc()['total'];
    }
    
    echo json_encode($stats);
}

// Search job roles
function searchJobRoles($conn, $search_query, $department_id = null) {
    $search_query = trim($search_query);
    
    if (empty($search_query)) {
        // If search query is empty, return all job roles with department filter
        getAllJobRoles($conn, $department_id);
        return;
    }
    
    // Build search query
    $sql = "SELECT jr.*, d.name as department_name 
            FROM job_roles jr 
            LEFT JOIN departments d ON jr.department_id = d.request_id
            WHERE (jr.name LIKE ? OR jr.description LIKE ?";
    
    // Add department filter if specified
    if ($department_id) {
        $sql .= " AND jr.department_id = ?";
    }
    
    $sql .= ") ORDER BY jr.created_at DESC";
    
    $search_term = "%" . $search_query . "%";
    $params = [$search_term, $search_term];
    $types = "ss";
    
    if ($department_id) {
        $params[] = $department_id;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobRoles = [];
    while($row = $result->fetch_assoc()) {
        $request_id = $row['request_id'];
        
        // Get qualifications
        $qual_sql = "SELECT qualification as text, type 
                     FROM qualifications 
                     WHERE request_id = ? 
                     ORDER BY priority, created_at";
        $qual_stmt = $conn->prepare($qual_sql);
        $qual_stmt->bind_param("s", $request_id);
        $qual_stmt->execute();
        $qual_result = $qual_stmt->get_result();
        $qualifications = [];
        while($qual_row = $qual_result->fetch_assoc()) {
            $qualifications[] = $qual_row;
        }
        $qual_stmt->close();
        
        // Get requirements
        $req_sql = "SELECT requirement as text, category, is_essential as essential 
                    FROM job_requirements 
                    WHERE request_id = ? 
                    ORDER BY created_at";
        $req_stmt = $conn->prepare($req_sql);
        $req_stmt->bind_param("s", $request_id);
        $req_stmt->execute();
        $req_result = $req_stmt->get_result();
        $requirements = [];
        while($req_row = $req_result->fetch_assoc()) {
            $requirements[] = $req_row;
        }
        $req_stmt->close();
        
        $row['qualifications'] = $qualifications;
        $row['requirements'] = $requirements;
        $jobRoles[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode($jobRoles);
}
?>