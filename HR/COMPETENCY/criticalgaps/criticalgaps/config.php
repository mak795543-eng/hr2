<?php
// Database configuration
$dbPrefix = getenv('DB_PREFIX') ?: '';
$cgHostEnv = getenv('CRITICAL_GAPS_DB_HOST');
$cgHostGlobal = getenv('DB_HOST');
$cgHost = $cgHostEnv !== false ? $cgHostEnv : ($cgHostGlobal !== false ? $cgHostGlobal : 'localhost');

$cgUserEnv = getenv('CRITICAL_GAPS_DB_USER');
$cgUserGlobal = getenv('DB_USER');
$cgUser = $cgUserEnv !== false ? $cgUserEnv : ($cgUserGlobal !== false ? $cgUserGlobal : 'root');

$cgPassEnv = getenv('CRITICAL_GAPS_DB_PASS');
$cgPassGlobal = getenv('DB_PASS');
$cgPass = $cgPassEnv !== false
    ? $cgPassEnv
    : ($cgPassGlobal !== false
        ? $cgPassGlobal
        : (($cgUser === 'root' && ($cgHost === 'localhost' || $cgHost === '127.0.0.1')) ? '' : 'makmak01'));
$cgName = getenv('CRITICAL_GAPS_DB_NAME') ?: 'critical_gaps';
if ($dbPrefix !== '' && strpos($cgName, $dbPrefix) !== 0) {
    $cgName = $dbPrefix . $cgName;
}
define('DB_HOST', $cgHost);
define('DB_USER', $cgUser);
define('DB_PASS', $cgPass);
define('DB_NAME', $cgName);

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
        status ENUM('Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready') DEFAULT 'Retrain',
        last_assessment DATE,
        next_review_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        skill_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        department VARCHAR(100) DEFAULT NULL,
        description TEXT,
        weight DECIMAL(3,2) DEFAULT 1.0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_skill_department (skill_name, category, department)
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

    CREATE TABLE IF NOT EXISTS succession_submissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        competency DECIMAL(5,2) DEFAULT 0,
        status ENUM('Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready') DEFAULT 'Retrain',
        development_plan TEXT,
        target_score DECIMAL(5,2) DEFAULT NULL,
        target_date DATE DEFAULT NULL,
        idp_status ENUM('Pending', 'Created') DEFAULT 'Pending',
        idp_created_at TIMESTAMP NULL DEFAULT NULL,
        is_pushed TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_employee_submission (employee_id),
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS requested_to_idp (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        status ENUM('Pending', 'Created') NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_requested_employee (employee_id)
    );

    CREATE TABLE IF NOT EXISTS individual_development_plans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        competency DECIMAL(5,2) DEFAULT 0,
        succession_status ENUM('Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready') DEFAULT 'Retrain',
        development_plan TEXT,
        target_score DECIMAL(5,2) DEFAULT NULL,
        target_date DATE DEFAULT NULL,
        delivery_mode ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite',
        requested_training_type VARCHAR(50) DEFAULT NULL,
        requested_training_mode VARCHAR(20) DEFAULT NULL,
        requested_start_datetime DATETIME DEFAULT NULL,
        requested_end_datetime DATETIME DEFAULT NULL,
        idp_status ENUM('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'under_review',
        training_requested_at TIMESTAMP NULL DEFAULT NULL,
        learning_requested_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_employee_idp (employee_id),
        INDEX idx_idp_status (idp_status),
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS requested_idps_repository (
        id INT PRIMARY KEY,
        employee_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        competency DECIMAL(5,2) DEFAULT 0,
        succession_status ENUM('Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready') DEFAULT 'Retrain',
        development_plan TEXT,
        target_score DECIMAL(5,2) DEFAULT NULL,
        target_date DATE DEFAULT NULL,
        delivery_mode ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite',
        requested_training_type VARCHAR(50) DEFAULT NULL,
        requested_training_mode VARCHAR(20) DEFAULT NULL,
        requested_start_datetime DATETIME DEFAULT NULL,
        requested_end_datetime DATETIME DEFAULT NULL,
        idp_status ENUM('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'requested',
        training_requested_at TIMESTAMP NULL DEFAULT NULL,
        learning_requested_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_employee_requested_idp (employee_id),
        INDEX idx_requested_idp_status (idp_status),
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS general_skill_standards (
        id INT PRIMARY KEY AUTO_INCREMENT,
        skill_id INT NOT NULL,
        standard_percentage DECIMAL(5,2) NOT NULL DEFAULT 80,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_skill_standard (skill_id),
        INDEX idx_standard_skill (skill_id),
        CONSTRAINT fk_gss_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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

function ensureSchema() {
    global $pdo;

    try {
        $pdo->exec("ALTER TABLE skills ADD COLUMN department VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE skills ADD UNIQUE KEY unique_skill_department (skill_name, category, department)");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE succession_submissions MODIFY COLUMN status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE succession_submissions ADD COLUMN is_pushed TINYINT(1) NOT NULL DEFAULT 0");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("UPDATE succession_submissions SET is_pushed = 0 WHERE is_pushed IS NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS general_skill_standards (id INT PRIMARY KEY AUTO_INCREMENT, skill_id INT NOT NULL, standard_percentage DECIMAL(5,2) NOT NULL DEFAULT 80, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_skill_standard (skill_id), INDEX idx_standard_skill (skill_id), CONSTRAINT fk_gss_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE general_skill_standards MODIFY COLUMN standard_percentage DECIMAL(5,2) NOT NULL DEFAULT 80");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("INSERT IGNORE INTO general_skill_standards (skill_id, standard_percentage) SELECT id, 80 FROM skills WHERE category = 'General Skills'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN delivery_mode ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans MODIFY COLUMN delivery_mode ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN requested_training_type VARCHAR(50) DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN requested_training_mode VARCHAR(20) DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN requested_start_datetime DATETIME DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN requested_end_datetime DATETIME DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE individual_development_plans ADD COLUMN learning_requested_at TIMESTAMP NULL DEFAULT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("UPDATE employees SET status = 'Retrain' WHERE status = 'Retain'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("UPDATE succession_submissions SET status = 'Retrain' WHERE status = 'Retain'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE succession_submissions MODIFY COLUMN status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain'");
    } catch (PDOException $e) {
    }
}

function insertDefaultDepartments() {
    global $pdo;

    try {
        $departments = [
            'Front Office / Reception',
            'Housekeeping',
            'Food & Beverage (F&B)',
            'Kitchen / Culinary',
            'Sales & Marketing',
            'Human Resources (HR)',
            'Finance / Accounting',
            'Engineering / Maintenance',
            'Security'
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
        foreach ($departments as $dept) {
            $stmt->execute([$dept]);
        }
    } catch (PDOException $e) {
        error_log("Error in insertDefaultDepartments: " . $e->getMessage());
    }
}

function insertDefaultGeneralSkills() {
    global $pdo;

    try {
        $generalSkills = [
            'Front Office / Reception' => [
                ['Customer Service Excellence', 'Delivering courteous, professional, and guest-focused service at all times'],
                ['Effective Communication Skills', 'Clear verbal and written communication with guests and internal teams'],
                ['Problem-Solving & Complaint Handling', 'Managing guest concerns calmly and effectively'],
                ['Hotel Systems & Reservation Knowledge', 'Understanding PMS, booking systems, and guest records'],
                ['Professional Appearance & Etiquette', 'Maintaining grooming standards and hospitality behavior'],
                ['Time Management & Multitasking', 'Handling multiple guest requests efficiently'],
                ['Cultural Awareness & Guest Sensitivity', 'Respecting diverse guest backgrounds and needs'],
            ],
            'Housekeeping' => [
                ['Attention to Detail', 'Ensuring cleanliness, hygiene, and presentation standards'],
                ['Knowledge of Cleaning Procedures & Chemicals', 'Safe and correct use of tools and materials'],
                ['Time Management', 'Completing tasks within operational schedules'],
                ['Health & Safety Awareness', 'Following safety, sanitation, and hazard prevention protocols'],
                ['Teamwork & Coordination', 'Working effectively with supervisors and other departments'],
                ['Physical Endurance & Task Discipline', 'Performing repetitive tasks efficiently'],
                ['Quality Control Awareness', 'Meeting hotel cleanliness and inspection standards'],
            ],
            'Food & Beverage (F&B)' => [
                ['Guest Service & Hospitality Skills', 'Providing friendly, attentive dining service'],
                ['Food Safety & Hygiene Knowledge', 'Compliance with sanitation and food handling standards'],
                ['Product Knowledge', 'Understanding menus, ingredients, beverages, and service styles'],
                ['Communication & Coordination', 'Clear interaction with kitchen and service teams'],
                ['Sales & Upselling Skills', 'Promoting menu items and enhancing guest experience'],
                ['Stress & Time Management', 'Performing under pressure during peak service hours'],
                ['Professional Conduct & Service Etiquette', 'Maintaining service standards'],
            ],
            'Kitchen / Culinary' => [
                ['Food Preparation & Culinary Fundamentals', 'Understanding cooking techniques and recipes'],
                ['Food Safety & Sanitation Compliance', 'Following HACCP and hygiene standards'],
                ['Time & Workflow Management', 'Meeting service timelines during operations'],
                ['Teamwork & Kitchen Coordination', 'Working efficiently within the kitchen brigade'],
                ['Attention to Quality & Presentation', 'Maintaining consistency in food standards'],
                ['Equipment Handling & Safety Awareness', 'Proper use of kitchen tools and machinery'],
                ['Waste Control & Cost Awareness', 'Minimizing food waste'],
            ],
            'Sales & Marketing' => [
                ['Communication & Presentation Skills', 'Effective client and stakeholder interaction'],
                ['Customer Relationship Management (CRM)', 'Building and maintaining client relationships'],
                ['Market & Trend Awareness', 'Understanding customer behavior and industry trends'],
                ['Negotiation & Persuasion Skills', 'Closing deals and partnerships'],
                ['Analytical & Reporting Skills', 'Interpreting sales data and performance metrics'],
                ['Planning & Coordination', 'Executing campaigns and events efficiently'],
                ['Professional Branding Awareness', 'Maintaining hotel brand consistency'],
            ],
            'Human Resources (HR)' => [
                ['Interpersonal & Communication Skills', 'Managing employee relations professionally'],
                ['Confidentiality & Ethical Judgment', 'Handling sensitive employee information'],
                ['Policy & Labor Law Awareness', 'Understanding HR policies and compliance'],
                ['Organizational & Documentation Skills', 'Managing employee records and reports'],
                ['Problem-Solving & Decision-Making', 'Addressing workforce issues effectively'],
                ['Training & Development Awareness', 'Supporting employee growth initiatives'],
                ['System & HRIS Proficiency', 'Using HR software efficiently'],
            ],
            'Finance / Accounting' => [
                ['Financial Accuracy & Attention to Detail', 'Ensuring error-free financial records'],
                ['Numerical & Analytical Skills', 'Interpreting financial data and trends'],
                ['Compliance & Policy Awareness', 'Following accounting standards and regulations'],
                ['Confidentiality & Integrity', 'Handling financial data responsibly'],
                ['Reporting & Documentation Skills', 'Preparing financial statements and reports'],
                ['Time Management & Deadline Control', 'Meeting financial cut-offs'],
                ['Cost Control Awareness', 'Monitoring expenses and budgets'],
            ],
            'Engineering / Maintenance' => [
                ['Technical & Mechanical Knowledge', 'Understanding hotel systems and equipment'],
                ['Preventive Maintenance Skills', 'Conducting routine inspections and repairs'],
                ['Health & Safety Compliance', 'Adhering to safety and operational standards'],
                ['Problem Diagnosis & Troubleshooting', 'Identifying and resolving technical issues'],
                ['Documentation & Reporting Skills', 'Logging maintenance activities'],
                ['Team Coordination & Communication', 'Working with other departments'],
                ['Emergency Response Readiness', 'Handling urgent technical incidents'],
            ],
            'Security' => [
                ['Observation & Situational Awareness', 'Monitoring surroundings effectively'],
                ['Emergency Response & Crisis Handling', 'Managing incidents calmly'],
                ['Communication & Reporting Skills', 'Writing incident and security reports'],
                ['Access Control & Patrol Skills', 'Maintaining property security'],
                ['Conflict Management & De-escalation', 'Handling disturbances professionally'],
                ['Discipline & Professional Conduct', 'Maintaining authority and integrity'],
                ['Safety & Risk Awareness', 'Identifying and mitigating threats'],
            ],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO skills (skill_name, category, department, description, weight) VALUES (?, 'General Skills', ?, ?, 1.0)");
        foreach ($generalSkills as $dept => $skills) {
            foreach ($skills as $skill) {
                $stmt->execute([$skill[0], $dept, $skill[1]]);
            }
        }
    } catch (PDOException $e) {
        error_log("Error in insertDefaultGeneralSkills: " . $e->getMessage());
    }
}

// Initialize database
createTablesIfNotExist();

// Function to get employees
function getEmployees($filter = 'all', $search = '', $department = 'all') {
    global $pdo;
    
    try {
        $sql = "SELECT e.id, e.employee_id, e.full_name, e.position, e.department, e.last_assessment, e.next_review_date,
                       COALESCE(gs.competency, 0) AS competency,
                       CASE
                           WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                           WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                           WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                           WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                           ELSE 'Succession Ready'
                       END AS status
                FROM employees e
                LEFT JOIN (
                    SELECT e2.employee_id, e2.department, AVG(COALESCE(es2.skill_score, 0)) AS competency
                    FROM employees e2
                    JOIN skills s2
                      ON s2.category = 'General Skills'
                     AND s2.department = e2.department
                    LEFT JOIN employee_skills es2
                      ON es2.employee_id = e2.employee_id
                     AND es2.skill_id = s2.id
                    GROUP BY e2.employee_id, e2.department
                ) gs ON gs.employee_id = e.employee_id AND gs.department = e.department
                WHERE 1=1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM individual_development_plans idp
                      WHERE idp.employee_id = e.employee_id
                  )
                  AND NOT EXISTS (
                      SELECT 1
                      FROM requested_idps_repository ridp
                      WHERE ridp.employee_id = e.employee_id
                  )
                  AND NOT EXISTS (
                      SELECT 1
                      FROM requested_to_idp r
                      WHERE r.employee_id = e.employee_id
                        AND r.status = 'Pending'
                  )";
        $params = [];
        
        // Apply department filter
        if ($department !== 'all') {
            $sql .= " AND e.department = ?";
            $params[] = $department;
        }
        
        // Apply status filter
        if ($filter !== 'all') {
            $sql .= " AND (
                CASE
                    WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                    WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                    WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                    WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                    ELSE 'Succession Ready'
                END
            ) = ?";
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
            CASE
                WHEN COALESCE(gs.competency, 0) <= 20 THEN 1
                WHEN COALESCE(gs.competency, 0) <= 40 THEN 2
                WHEN COALESCE(gs.competency, 0) <= 60 THEN 3
                WHEN COALESCE(gs.competency, 0) <= 80 THEN 4
                ELSE 5
            END,
            COALESCE(gs.competency, 0) DESC,
            e.full_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error in getEmployees: " . $e->getMessage());
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
            // Get employee general skills by department
            $sql = "SELECT s.skill_name, s.category, s.description,
                           COALESCE(es.skill_score, 0) AS skill_score,
                           es.skill_score AS raw_skill_score,
                           es.assessment_date, es.assessed_by, es.notes
                    FROM skills s
                    LEFT JOIN employee_skills es
                        ON es.skill_id = s.id AND es.employee_id = ?
                    WHERE s.category = 'General Skills' AND s.department = ?
                    ORDER BY s.skill_name ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id, $employee['department']]);
            $employee['skills'] = $stmt->fetchAll();

            $total = 0.0;
            $count = 0;
            foreach (($employee['skills'] ?? []) as $s) {
                $total += is_numeric($s['skill_score'] ?? null) ? (float)$s['skill_score'] : 0.0;
                $count++;
            }
            $employee['competency'] = $count > 0 ? round($total / $count, 1) : 0;
            if ($employee['competency'] <= 20) {
                $employee['status'] = 'Retrain';
            } elseif ($employee['competency'] <= 40) {
                $employee['status'] = 'Reskilling';
            } elseif ($employee['competency'] <= 60) {
                $employee['status'] = 'Refresher Training';
            } elseif ($employee['competency'] <= 80) {
                $employee['status'] = 'Upskilling';
            } else {
                $employee['status'] = 'Succession Ready';
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
        
        // Total employees and average (computed from General Skills)
        $stmt = $pdo->query(
            "SELECT COUNT(*) AS total, AVG(t.competency) AS average
             FROM (
                SELECT e.employee_id, e.department, AVG(COALESCE(es.skill_score, 0)) AS competency
                FROM employees e
                JOIN skills s
                  ON s.category = 'General Skills'
                 AND s.department = e.department
                LEFT JOIN employee_skills es
                  ON es.employee_id = e.employee_id
                 AND es.skill_id = s.id
                GROUP BY e.employee_id, e.department
             ) t"
        );
        $result = $stmt->fetch();
        $stats['total_employees'] = $result['total'] ?? 0;
        $stats['average_competency'] = round($result['average'] ?? 0, 1);

        // By status (computed)
        $stmt = $pdo->query(
            "SELECT t.status, COUNT(*) AS count
             FROM (
                SELECT e.employee_id,
                       CASE
                           WHEN AVG(COALESCE(es.skill_score, 0)) <= 20 THEN 'Retrain'
                           WHEN AVG(COALESCE(es.skill_score, 0)) <= 40 THEN 'Reskilling'
                           WHEN AVG(COALESCE(es.skill_score, 0)) <= 60 THEN 'Refresher Training'
                           WHEN AVG(COALESCE(es.skill_score, 0)) <= 80 THEN 'Upskilling'
                           ELSE 'Succession Ready'
                       END AS status
                FROM employees e
                JOIN skills s
                  ON s.category = 'General Skills'
                 AND s.department = e.department
                LEFT JOIN employee_skills es
                  ON es.employee_id = e.employee_id
                 AND es.skill_id = s.id
                GROUP BY e.employee_id
             ) t
             GROUP BY t.status
             ORDER BY t.status"
        );
        $stats['by_status'] = $stmt->fetchAll();

        // By department (computed)
        $stmt = $pdo->query(
            "SELECT t.department, COUNT(*) AS count, AVG(t.competency) AS avg_competency
             FROM (
                SELECT e.employee_id, e.department, AVG(COALESCE(es.skill_score, 0)) AS competency
                FROM employees e
                JOIN skills s
                  ON s.category = 'General Skills'
                 AND s.department = e.department
                LEFT JOIN employee_skills es
                  ON es.employee_id = e.employee_id
                 AND es.skill_id = s.id
                GROUP BY e.employee_id, e.department
             ) t
             GROUP BY t.department
             ORDER BY t.department"
        );
        $stats['by_department'] = $stmt->fetchAll();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error in getCompetencyStats: " . $e->getMessage());
        return [];
    }
}
?>