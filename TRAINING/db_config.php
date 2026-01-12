<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'training_program');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Initialize database with sample data (run this once)
function initializeDatabase() {
    $conn = getDBConnection();
    
    // Check if training_programs table exists
    $result = $conn->query("SHOW TABLES LIKE 'training_programs'");
    
    if ($result->num_rows == 0) {
        // Tables don't exist, import the SQL file
        echo "Initializing database...<br>";
        
        // Read SQL file
        $sql = file_get_contents(__DIR__ . '/../training_program.sql');
        
        // Execute multi-query
        if ($conn->multi_query($sql)) {
            do {
                // Store first result set
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            echo "Database initialized successfully!<br>";
        } else {
            echo "Error initializing database: " . $conn->error . "<br>";
        }
    } else {
        echo "Database already initialized.<br>";
    }
    
    $conn->close();
}

// Helper function to fetch data
function fetchData($query) {
    $conn = getDBConnection();
    $result = $conn->query($query);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $conn->close();
    return $data;
}

// Helper function to execute query
function executeQuery($query) {
    $conn = getDBConnection();
    $result = $conn->query($query);
    $conn->close();
    return $result;
}

// Helper function to prepare and execute statement
function executeStatement($query, $params = [], $types = '') {
    $conn = getDBConnection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to get all departments
function getDepartments() {
    $query = "SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    return fetchData($query);
}

// Function to get all training programs
function getTrainingPrograms() {
    $query = "SELECT 
                tp.*,
                d.department_name 
              FROM training_programs tp
              LEFT JOIN departments d ON tp.department_id = d.id
              ORDER BY tp.start_date DESC";
    return fetchData($query);
}

// Function to create new training program
function createTrainingProgram($data) {
    $conn = getDBConnection();
    
    // Prepare the query
    $query = "INSERT INTO training_programs (
                training_title, training_type, description, target_audience, 
                department_id, target_role, category, participants_needed,
                start_date, end_date, status, training_location, 
                trainer_name, budget, created_by
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    // Bind parameters
    $stmt->bind_param(
        "ssssississsssdi",
        $data['training_title'],
        $data['training_type'],
        $data['description'],
        $data['target_audience'],
        $data['department_id'],
        $data['target_role'],
        $data['category'],
        $data['participants_needed'],
        $data['start_date'],
        $data['end_date'],
        $data['status'],
        $data['training_location'],
        $data['trainer_name'],
        $data['budget'],
        $data['created_by']
    );
    
    $result = $stmt->execute();
    $newId = $stmt->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $result ? $newId : false;
}

// Function to update training program
function updateTrainingProgram($id, $data) {
    $conn = getDBConnection();
    
    $query = "UPDATE training_programs SET
                training_title = ?,
                training_type = ?,
                description = ?,
                target_audience = ?,
                department_id = ?,
                target_role = ?,
                category = ?,
                participants_needed = ?,
                start_date = ?,
                end_date = ?,
                status = ?,
                training_location = ?,
                trainer_name = ?,
                budget = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param(
        "ssssississssdii",
        $data['training_title'],
        $data['training_type'],
        $data['description'],
        $data['target_audience'],
        $data['department_id'],
        $data['target_role'],
        $data['category'],
        $data['participants_needed'],
        $data['start_date'],
        $data['end_date'],
        $data['status'],
        $data['training_location'],
        $data['trainer_name'],
        $data['budget'],
        $id
    );
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to delete training program
function deleteTrainingProgram($id) {
    $query = "DELETE FROM training_programs WHERE id = ?";
    $result = executeStatement($query, [$id], 'i');
    return $result;
}

// Function to get training statistics
function getTrainingStatistics() {
    $conn = getDBConnection();
    
    $stats = [];
    
    // Total trainings
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs");
    $stats['total_trainings'] = $result->fetch_assoc()['total'];
    
    // Active trainings
    $result = $conn->query("SELECT COUNT(*) as active FROM training_programs WHERE status IN ('Planned', 'Scheduled', 'Ongoing')");
    $stats['active_trainings'] = $result->fetch_assoc()['active'];
    
    // Total participants needed
    $result = $conn->query("SELECT SUM(participants_needed) as total_participants FROM training_programs");
    $stats['total_participants'] = $result->fetch_assoc()['total_participants'] ?: 0;
    
    // Completed trainings
    $result = $conn->query("SELECT COUNT(*) as completed FROM training_programs WHERE status = 'Completed'");
    $stats['completed_trainings'] = $result->fetch_assoc()['completed'];
    
    // Upcoming trainings (next 7 days)
    $result = $conn->query("SELECT COUNT(*) as upcoming FROM training_programs WHERE start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Planned', 'Scheduled')");
    $stats['upcoming_trainings'] = $result->fetch_assoc()['upcoming'];
    
    // Trainings this week
    $result = $conn->query("SELECT COUNT(*) as week_trainings FROM training_programs WHERE start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stats['week_trainings'] = $result->fetch_assoc()['week_trainings'];
    
    // Registered participants (estimated)
    $result = $conn->query("SELECT COUNT(*) as registered FROM training_participants WHERE attendance_status = 'Registered'");
    $stats['registered_participants'] = $result->fetch_assoc()['registered'];
    
    // Open registrations
    $result = $conn->query("SELECT COUNT(*) as open_reg FROM training_programs WHERE status = 'Planned'");
    $stats['open_registrations'] = $result->fetch_assoc()['open_reg'];
    
    $conn->close();
    
    return $stats;
}
?>