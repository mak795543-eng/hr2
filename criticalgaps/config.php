<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'critical_gaps');

// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to create tables if they don't exist
function createTablesIfNotExist() {
    global $pdo;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS departments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS employees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        competency DECIMAL(5,2) DEFAULT 0,
        status ENUM('Reskilling', 'Retain', 'Upskilling', 'Succession Ready') DEFAULT 'Retain',
        last_assessment DATE,
        next_review_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        skill_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        weight DECIMAL(3,2) DEFAULT 1.0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS employee_skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) NOT NULL,
        skill_id INT NOT NULL,
        skill_score DECIMAL(5,2) DEFAULT 0,
        assessment_date DATE DEFAULT CURRENT_DATE,
        assessed_by VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        UNIQUE KEY unique_employee_skill (employee_id, skill_id)
    );
    ";
    
    // Execute each statement separately
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        if (trim($statement) !== '') {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors for existing tables
                if (strpos($e->getMessage(), 'already exists') === false) {
                    error_log("Table creation error: " . $e->getMessage());
                }
            }
        }
    }
}

// Function to insert sample data if empty
function insertSampleData() {
    global $pdo;
    
    // Check and insert departments
    $stmt = $pdo->query("SELECT COUNT(*) FROM departments");
    if ($stmt->fetchColumn() == 0) {
        $departments = [
            'Engineering',
            'Sales', 
            'Marketing',
            'Human Resources',
            'Finance',
            'Operations',
            'IT',
            'Customer Service',
            'Research & Development',
            'Quality Assurance'
        ];
        
        foreach ($departments as $dept) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
            $stmt->execute([$dept]);
        }
    }
    
    // Check and insert employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    if ($stmt->fetchColumn() == 0) {
        $employees = [
            ['EMP001', 'John Smith', 'Senior Developer', 'Engineering', 75.5, 'Upskilling', '2024-01-15', '2024-07-15'],
            ['EMP002', 'Sarah Johnson', 'Sales Manager', 'Sales', 45.2, 'Retain', '2024-01-10', '2024-07-10'],
            ['EMP003', 'Mike Wilson', 'Marketing Lead', 'Marketing', 88.7, 'Succession Ready', '2024-01-12', '2024-07-12'],
            ['EMP004', 'Lisa Brown', 'HR Specialist', 'Human Resources', 28.9, 'Reskilling', '2024-01-05', '2024-07-05'],
            ['EMP005', 'David Lee', 'Financial Analyst', 'Finance', 65.8, 'Upskilling', '2024-01-20', '2024-07-20'],
            ['EMP006', 'Emma Davis', 'Operations Manager', 'Operations', 52.3, 'Retain', '2024-01-18', '2024-07-18'],
            ['EMP007', 'James Miller', 'IT Support', 'IT', 92.1, 'Succession Ready', '2024-01-22', '2024-07-22'],
            ['EMP008', 'Maria Garcia', 'Customer Service Rep', 'Customer Service', 35.6, 'Reskilling', '2024-01-08', '2024-07-08'],
            ['EMP009', 'Robert Chen', 'Research Scientist', 'Research & Development', 82.4, 'Upskilling', '2024-01-25', '2024-07-25'],
            ['EMP010', 'Jennifer White', 'QA Engineer', 'Quality Assurance', 70.2, 'Upskilling', '2024-01-30', '2024-07-30']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO employees (employee_id, full_name, position, department, competency, status, last_assessment, next_review_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($employees as $emp) {
            $stmt->execute($emp);
        }
    }
    
    // Check and insert skills
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    if ($stmt->fetchColumn() == 0) {
        $skills = [
            // Technical Skills (40% weight)
            ['PHP Development', 'Technical', 'Web development using PHP and frameworks like Laravel', 1.0],
            ['JavaScript', 'Technical', 'Frontend (React/Vue) and backend (Node.js) development', 1.0],
            ['Database Design', 'Technical', 'SQL database design, optimization, and administration', 1.0],
            ['API Development', 'Technical', 'RESTful and GraphQL API design and implementation', 1.0],
            ['System Architecture', 'Technical', 'System design, microservices, and cloud architecture', 1.0],
            ['DevOps', 'Technical', 'CI/CD, Docker, Kubernetes, and deployment automation', 1.0],
            ['Mobile Development', 'Technical', 'iOS/Android app development with React Native/Flutter', 0.8],
            ['Cloud Services', 'Technical', 'AWS/Azure/Google Cloud services and management', 0.8],
            ['Cybersecurity', 'Technical', 'Security protocols, encryption, and threat prevention', 0.8],
            ['Data Analysis', 'Technical', 'Data visualization, SQL queries, and reporting', 0.8],
            
            // Soft Skills (30% weight)
            ['Communication', 'Soft Skills', 'Verbal, written, and presentation skills', 1.0],
            ['Teamwork', 'Soft Skills', 'Collaboration, conflict resolution, and team building', 1.0],
            ['Problem Solving', 'Soft Skills', 'Analytical thinking, creativity, and decision making', 1.0],
            ['Time Management', 'Soft Skills', 'Prioritization, planning, and deadline management', 0.9],
            ['Adaptability', 'Soft Skills', 'Flexibility and ability to handle change', 0.9],
            ['Leadership', 'Soft Skills', 'Initiative, motivation, and guidance', 0.9],
            ['Critical Thinking', 'Soft Skills', 'Logical analysis and evaluation', 0.8],
            ['Emotional Intelligence', 'Soft Skills', 'Self-awareness and empathy', 0.8],
            
            // Leadership & Management (30% weight)
            ['Strategic Planning', 'Leadership', 'Long-term strategy development and execution', 1.0],
            ['Project Management', 'Leadership', 'Agile/Scrum methodologies and resource management', 1.0],
            ['Decision Making', 'Leadership', 'Risk assessment and informed decision making', 1.0],
            ['Mentoring', 'Leadership', 'Coaching and developing team members', 0.9],
            ['Stakeholder Management', 'Leadership', 'Managing internal and external relationships', 0.9],
            ['Budget Management', 'Leadership', 'Financial planning and cost control', 0.8],
            ['Change Management', 'Leadership', 'Managing organizational change', 0.8],
            
            // Industry Knowledge
            ['Industry Trends', 'Industry Knowledge', 'Current industry developments and innovations', 0.7],
            ['Competitor Analysis', 'Industry Knowledge', 'Analyzing competitor strategies and positioning', 0.7],
            ['Market Research', 'Industry Knowledge', 'Conducting and interpreting market research', 0.7],
            
            // Safety & Compliance
            ['Workplace Safety', 'Safety', 'Adhering to safety protocols and regulations', 0.6],
            ['Emergency Procedures', 'Safety', 'Knowledge of emergency response procedures', 0.6],
            ['Risk Assessment', 'Safety', 'Identifying and assessing workplace risks', 0.6]
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO skills (skill_name, category, description, weight) VALUES (?, ?, ?, ?)");
        foreach ($skills as $skill) {
            $stmt->execute($skill);
        }
    }
    
    // Check and insert employee skills
    $stmt = $pdo->query("SELECT COUNT(*) FROM employee_skills");
    if ($stmt->fetchColumn() == 0) {
        // Get some sample employee and skill IDs
        $employees = $pdo->query("SELECT employee_id FROM employees LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        $skills = $pdo->query("SELECT id FROM skills LIMIT 15")->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO employee_skills (employee_id, skill_id, skill_score, assessment_date) VALUES (?, ?, ?, ?)");
        
        foreach ($employees as $empId) {
            foreach ($skills as $skillId) {
                // Generate random scores based on employee competency
                $emp = $pdo->prepare("SELECT competency FROM employees WHERE employee_id = ?");
                $emp->execute([$empId]);
                $competency = $emp->fetchColumn();
                
                // Base score around employee competency with some variation
                $baseScore = $competency;
                $variation = rand(-15, 15);
                $score = max(0, min(100, $baseScore + $variation));
                
                $stmt->execute([
                    $empId,
                    $skillId,
                    $score,
                    date('Y-m-d', strtotime('-' . rand(0, 90) . ' days'))
                ]);
            }
        }
    }
}

// Initialize database
createTablesIfNotExist();
insertSampleData();

// Function to get employees
function getEmployees($filter = 'all', $search = '', $department = 'all') {
    global $pdo;
    
    try {
        $sql = "SELECT e.* FROM employees e WHERE 1=1";
        $params = [];
        
        // Apply department filter
        if ($department !== 'all') {
            $sql .= " AND e.department = ?";
            $params[] = $department;
        }
        
        // Apply status filter
        if ($filter !== 'all') {
            $sql .= " AND e.status = ?";
            $params[] = $filter;
        }
        
        // Apply search
        if (!empty($search)) {
            $sql .= " AND (e.full_name LIKE ? OR e.employee_id LIKE ? OR e.position LIKE ? OR e.department LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY 
            CASE e.status 
                WHEN 'Reskilling' THEN 1
                WHEN 'Retain' THEN 2
                WHEN 'Upskilling' THEN 3
                WHEN 'Succession Ready' THEN 4
                ELSE 5
            END,
            e.competency DESC,
            e.full_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error in getEmployees: " . $e->getMessage());
        return [];
    }
}

// Function to get departments
function getDepartments() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT DISTINCT department FROM employees UNION SELECT name FROM departments ORDER BY 1 ASC");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // If no results from employees table, get from departments table
        if (empty($results)) {
            $stmt = $pdo->query("SELECT name FROM departments ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        
        return array_unique($results);
        
    } catch (PDOException $e) {
        error_log("Error in getDepartments: " . $e->getMessage());
        return [];
    }
}

// Function to get employee details
function getEmployeeDetails($employee_id) {
    global $pdo;
    
    try {
        // Get employee basic info
        $sql = "SELECT * FROM employees WHERE employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        
        if ($employee) {
            // Get employee skills
            $sql = "SELECT s.skill_name, s.category, s.description, 
                           es.skill_score, es.assessment_date, es.assessed_by, es.notes
                    FROM employee_skills es
                    JOIN skills s ON es.skill_id = s.id
                    WHERE es.employee_id = ?
                    ORDER BY 
                        CASE s.category
                            WHEN 'Technical' THEN 1
                            WHEN 'Soft Skills' THEN 2
                            WHEN 'Leadership' THEN 3
                            WHEN 'Industry Knowledge' THEN 4
                            WHEN 'Safety' THEN 5
                            ELSE 6
                        END,
                        s.skill_name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee['skills'] = $stmt->fetchAll();
            
            // Calculate weighted competency
            if (!empty($employee['skills'])) {
                $weightedTotal = 0;
                $totalWeight = 0;
                
                foreach ($employee['skills'] as $skill) {
                    $weight = 1.0; // Default weight
                    switch ($skill['category']) {
                        case 'Technical': $weight = 0.4; break;
                        case 'Soft Skills': $weight = 0.3; break;
                        case 'Leadership': 
                        case 'Industry Knowledge': 
                        case 'Safety': 
                            $weight = 0.3; break;
                    }
                    
                    $weightedTotal += $skill['skill_score'] * $weight;
                    $totalWeight += $weight;
                }
                
                if ($totalWeight > 0) {
                    $employee['calculated_competency'] = round($weightedTotal / $totalWeight, 1);
                }
            }
        }
        
        return $employee;
        
    } catch (PDOException $e) {
        error_log("Error in getEmployeeDetails: " . $e->getMessage());
        return null;
    }
}

// Function to get competency statistics
function getCompetencyStats() {
    global $pdo;
    
    try {
        $stats = [
            'total_employees' => 0,
            'average_competency' => 0,
            'by_status' => [],
            'by_department' => []
        ];
        
        // Total employees and average
        $stmt = $pdo->query("SELECT COUNT(*) as total, AVG(competency) as average FROM employees");
        $result = $stmt->fetch();
        $stats['total_employees'] = $result['total'] ?? 0;
        $stats['average_competency'] = round($result['average'] ?? 0, 1);
        
        // By status
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM employees GROUP BY status ORDER BY status");
        $stats['by_status'] = $stmt->fetchAll();
        
        // By department
        $stmt = $pdo->query("SELECT department, COUNT(*) as count, AVG(competency) as avg_competency FROM employees GROUP BY department ORDER BY department");
        $stats['by_department'] = $stmt->fetchAll();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error in getCompetencyStats: " . $e->getMessage());
        return [];
    }
}
?>