<?php
// Database configuration
$dbPrefix = getenv('DB_PREFIX') ?: '';
$cgHostEnv = getenv('CRITICAL_GAPS_DB_HOST');
$cgHostGlobal = getenv('DB_HOST');
$cgHost = $cgHostEnv !== false ? $cgHostEnv : ($cgHostGlobal !== false ? $cgHostGlobal : 'localhost');

$cgUserEnv = getenv('CRITICAL_GAPS_DB_USER');
$cgUserGlobal = getenv('DB_USER');
$cgUser = $cgUserEnv !== false ? $cgUserEnv : ($cgUserGlobal !== false ? $cgUserGlobal : 'hr2_critical_gaps');

$cgPassEnv = getenv('CRITICAL_GAPS_DB_PASS');
$cgPassGlobal = getenv('DB_PASS');
$cgPass = 'hr2.soliera';
// $cgPass = $cgPassEnv !== false
//     ? $cgPassEnv
//     : ($cgPassGlobal !== false
//         ? $cgPassGlobal
//         : (($cgUser === 'root' && ($cgHost === 'localhost' || $cgHost === '127.0.0.1')) ? '' : 'makmak01'));
$cgName = getenv('CRITICAL_GAPS_DB_NAME') ?: 'hr2_critical_gaps';
if ($dbPrefix !== '' && strpos($cgName, $dbPrefix) !== 0) {
    $cgName = $dbPrefix . $cgName;
}
define('DB_HOST', $cgHost);
define('DB_USER', $cgUser);
define('DB_PASS', 'hr2.soliera');
define('DB_NAME', 'hr2_critical_gaps');

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
function createTablesIfNotExist()
{
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

    CREATE TABLE IF NOT EXISTS pre_promotion_employees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        competency_level VARCHAR(50) NOT NULL,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_pre_promotion_employee (employee_id),
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
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

function seedCompetencyCriteria(): void
{
    global $pdo;

    // Role-specific competency criteria with required levels
    $criteriaBlueprint = [
        'Front Office / Reception' => [
            'Guest Service Excellence' => 85,
            'Operational Efficiency' => 80,
            'Customer Relations' => 85,
            'Hotel Knowledge' => 75,
            'Team Collaboration' => 80,
        ],
        'Housekeeping' => [
            'Quality Standards' => 90,
            'Operational Efficiency' => 80,
            'Safety & Compliance' => 85,
            'Guest Service Support' => 75,
            'Team Coordination' => 75,
        ],
        'Food & Beverage (F&B)' => [
            'Service Excellence' => 85,
            'Product Knowledge' => 80,
            'Operational Efficiency' => 80,
            'Sanitation & Safety' => 90,
            'Revenue Generation' => 75,
        ],
        'Kitchen / Culinary' => [
            'Food Quality' => 90,
            'Kitchen Operations' => 80,
            'Safety & Sanitation' => 90,
            'Menu Knowledge' => 80,
            'Team Leadership' => 75,
        ],
        'Sales & Marketing' => [
            'Sales Performance' => 85,
            'Client Relations' => 85,
            'Market Development' => 75,
            'Communication Skills' => 80,
            'Strategic Planning' => 75,
        ],
        'Human Resources (HR)' => [
            'Employee Relations' => 85,
            'Recruitment & Hiring' => 80,
            'Compliance & Administration' => 90,
            'Training & Development' => 80,
            'Strategic HR' => 75,
        ],
        'Finance / Accounting' => [
            'Financial Accuracy' => 95,
            'Reporting & Analysis' => 85,
            'Compliance & Control' => 90,
            'Operational Efficiency' => 80,
            'Strategic Finance' => 75,
        ],
        'Engineering / Maintenance' => [
            'Technical Performance' => 85,
            'Safety & Compliance' => 90,
            'Operational Efficiency' => 80,
            'Guest Impact Management' => 75,
            'Technical Expertise' => 80,
        ],
        'Security' => [
            'Security Operations' => 85,
            'Risk Management' => 80,
            'Professional Conduct' => 90,
            'Guest & Staff Safety' => 90,
            'Systems & Technology' => 75,
        ],
    ];

    // Default criteria for departments not specifically defined
    $defaultCriteria = [
        'Work Quality' => 80,
        'Productivity' => 75,
        'Teamwork' => 75,
        'Professional Development' => 70,
        'Compliance' => 85,
    ];

    $insertCriteria = $pdo->prepare('INSERT IGNORE INTO competency_criteria (name, description, required_level) VALUES (?, ?, ?)');

    // Insert criteria for each department
    foreach ($criteriaBlueprint as $department => $criteria) {
        foreach ($criteria as $criteriaName => $requiredLevel) {
            $description = "Competency criteria for {$criteriaName} in {$department} department";
            $insertCriteria->execute([$criteriaName, $description, $requiredLevel]);
        }
    }

    // Insert default criteria
    foreach ($defaultCriteria as $criteriaName => $requiredLevel) {
        $description = "General competency criteria for {$criteriaName}";
        $insertCriteria->execute([$criteriaName, $description, $requiredLevel]);
    }
}

function ensureCompetencyCriteriaSchema(): void
{
    global $pdo;

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS competency_criteria (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                description TEXT,
                required_level DECIMAL(5,2) NOT NULL DEFAULT 80,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_competency_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
    }

    // Seed the competency criteria after ensuring the schema
    seedCompetencyCriteria();
}

function ensureGapFormulationSchema(): void
{
    global $pdo;

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS kpi_gap_formulations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id VARCHAR(50) NOT NULL,
                evaluation_period VARCHAR(50) NOT NULL,
                overall_competency DECIMAL(5,2) NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL,
                details_json LONGTEXT,
                forwarded_to_critical TINYINT(1) NOT NULL DEFAULT 0,
                forwarded_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_emp_period (employee_id, evaluation_period),
                INDEX idx_period (evaluation_period),
                INDEX idx_forwarded (forwarded_to_critical, evaluation_period),
                CONSTRAINT fk_gap_emp FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec("ALTER TABLE kpi_gap_formulations ADD COLUMN forwarded_to_critical TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("ALTER TABLE kpi_gap_formulations ADD COLUMN forwarded_at TIMESTAMP NULL DEFAULT NULL");
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("CREATE INDEX idx_forwarded ON kpi_gap_formulations (forwarded_to_critical, evaluation_period)");
    } catch (Throwable $e) {
    }
}

function ensureSchema()
{
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

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pre_promotion_employees (id INT PRIMARY KEY AUTO_INCREMENT, employee_id VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, competency_level VARCHAR(50) NOT NULL, date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_pre_promotion_employee (employee_id), FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE pre_promotion_employees ADD COLUMN promotion_status ENUM('pending','promoted') DEFAULT 'pending'");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE pre_promotion_employees ADD COLUMN promotion_letter TEXT NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE pre_promotion_employees ADD COLUMN promotion_sent_at TIMESTAMP NULL DEFAULT NULL");
    } catch (PDOException $e) {
    }
}

function insertDefaultDepartments()
{
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

function insertDefaultGeneralSkills()
{
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

function ensureKpiSchema(): void
{
    global $pdo;

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS kpis (
                id INT PRIMARY KEY AUTO_INCREMENT,
                kpi_name VARCHAR(150) NOT NULL,
                department VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_kpi (kpi_name, department)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS employee_kpi_scores (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id VARCHAR(50) NOT NULL,
                evaluation_period VARCHAR(50) NOT NULL,
                kpi_id INT NOT NULL,
                criteria VARCHAR(255) NOT NULL,
                score DECIMAL(5,2) NOT NULL DEFAULT 0,
                assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_emp_kpi_criteria (employee_id, evaluation_period, kpi_id, criteria),
                INDEX idx_emp_period (employee_id, evaluation_period),
                CONSTRAINT fk_emp_kpi_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
                CONSTRAINT fk_emp_kpi_kpi FOREIGN KEY (kpi_id) REFERENCES kpis(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
    }
}

function seedMissingKpiEvaluations(string $employeeId, string $evaluationPeriod): bool
{
    global $pdo;

    $employeeId = trim($employeeId);
    $evaluationPeriod = trim($evaluationPeriod);
    if ($employeeId === '' || $evaluationPeriod === '') return false;

    $stmtEmp = $pdo->prepare('SELECT department FROM employees WHERE employee_id = ? LIMIT 1');
    $stmtEmp->execute([$employeeId]);
    $dept = (string)($stmtEmp->fetchColumn() ?: '');

    $check = $pdo->prepare('SELECT COUNT(*) FROM employee_kpi_scores WHERE employee_id = ? AND evaluation_period = ?');
    $check->execute([$employeeId, $evaluationPeriod]);
    $has = (int)($check->fetchColumn() ?? 0);
    if ($has > 0) return false;

    // Role-specific KPI blueprint based on department
    $kpiBlueprint = [
        'Front Office / Reception' => [
            'Guest Service Excellence' => [
                'Professionalism and appearance',
                'Guest greeting and welcome',
                'Check-in/check-out efficiency',
                'Problem resolution skills',
                'Communication clarity',
            ],
            'Operational Efficiency' => [
                'Reservation management',
                'Payment processing accuracy',
                'Room assignment accuracy',
                'Information management',
                'Time management',
            ],
            'Customer Relations' => [
                'Guest satisfaction scores',
                'Complaint handling',
                'Service recovery',
                'Personalized service',
                'Follow-up procedures',
            ],
            'Hotel Knowledge' => [
                'Property facilities knowledge',
                'Local area information',
                'Service standards awareness',
                'Emergency procedures',
                'Brand standards compliance',
            ],
            'Team Collaboration' => [
                'Inter-department coordination',
                'Communication with housekeeping',
                'Maintenance reporting',
                'F&B coordination',
                'Shift handover effectiveness',
            ],
        ],
        'Housekeeping' => [
            'Quality Standards' => [
                'Room cleanliness scores',
                'Attention to detail',
                'Sanitation compliance',
                'Aesthetic standards',
                'Inspection pass rates',
            ],
            'Operational Efficiency' => [
                'Room cleaning time',
                'Task completion rates',
                'Supply management',
                'Equipment maintenance',
                'Productivity metrics',
            ],
            'Safety & Compliance' => [
                'Chemical handling safety',
                'Health protocol adherence',
                'Emergency response knowledge',
                'OSHA compliance',
                'Personal safety practices',
            ],
            'Guest Service Support' => [
                'Guest interaction quality',
                'Request response time',
                'Problem reporting',
                'Communication skills',
                'Professional conduct',
            ],
            'Team Coordination' => [
                'Supervisor communication',
                'Team collaboration',
                'Shift coordination',
                'Training participation',
                'Quality reporting',
            ],
        ],
        'Food & Beverage (F&B)' => [
            'Service Excellence' => [
                'Table service quality',
                'Guest interaction skills',
                'Order accuracy',
                'Upselling effectiveness',
                'Service timing',
            ],
            'Product Knowledge' => [
                'Menu knowledge',
                'Beverage expertise',
                'Ingredient awareness',
                'Allergy information',
                'Pairing recommendations',
            ],
            'Operational Efficiency' => [
                'Table turnover rate',
                'Order processing speed',
                'Payment handling accuracy',
                'Station organization',
                'Side work completion',
            ],
            'Sanitation & Safety' => [
                'Food safety compliance',
                'Personal hygiene standards',
                'Cleaning procedures',
                'Temperature control',
                'Cross-contamination prevention',
            ],
            'Revenue Generation' => [
                'Sales per cover',
                'Guest satisfaction scores',
                'Repeat customer rate',
                'Promotion participation',
                'Cost control awareness',
            ],
        ],
        'Kitchen / Culinary' => [
            'Food Quality' => [
                'Taste consistency',
                'Presentation standards',
                'Recipe adherence',
                'Portion control',
                'Quality inspection scores',
            ],
            'Kitchen Operations' => [
                'Preparation efficiency',
                'Cooking time standards',
                'Station organization',
                'Inventory management',
                'Waste reduction',
            ],
            'Safety & Sanitation' => [
                'Food safety protocols',
                'HACCP compliance',
                'Kitchen cleanliness',
                'Equipment sanitation',
                'Personal hygiene standards',
            ],
            'Menu Knowledge' => [
                'Recipe mastery',
                'Ingredient knowledge',
                'Allergy awareness',
                'Dietary accommodations',
                'Menu development input',
            ],
            'Team Leadership' => [
                'Line coordination',
                'Training junior staff',
                'Communication skills',
                'Quality control oversight',
                'Cost management awareness',
            ],
        ],
        'Sales & Marketing' => [
            'Sales Performance' => [
                'Revenue targets achievement',
                'Client acquisition rate',
                'Conversion rates',
                'Average deal size',
                'Sales growth metrics',
            ],
            'Client Relations' => [
                'Client satisfaction scores',
                'Relationship building',
                'Follow-up effectiveness',
                'Complaint resolution',
                'Retention rates',
            ],
            'Market Development' => [
                'Market research analysis',
                'Campaign effectiveness',
                'Lead generation quality',
                'Brand awareness metrics',
                'Competitive analysis',
            ],
            'Communication Skills' => [
                'Presentation quality',
                'Proposal writing',
                'Negotiation effectiveness',
                'Digital communication',
                'Public speaking ability',
            ],
            'Strategic Planning' => [
                'Sales strategy execution',
                'Budget management',
                'Forecasting accuracy',
                'Planning organization',
                'Goal achievement tracking',
            ],
        ],
        'Human Resources (HR)' => [
            'Employee Relations' => [
                'Employee satisfaction scores',
                'Conflict resolution effectiveness',
                'Communication clarity',
                'Policy enforcement consistency',
                'Employee engagement levels',
            ],
            'Recruitment & Hiring' => [
                'Time-to-fill metrics',
                'Quality of hires',
                'Cost-per-hire management',
                'Source effectiveness',
                'Onboarding success rates',
            ],
            'Compliance & Administration' => [
                'Policy compliance rate',
                'Record-keeping accuracy',
                'Reporting timeliness',
                'Legal requirement adherence',
                'Audit preparation quality',
            ],
            'Training & Development' => [
                'Training program effectiveness',
                'Skill development tracking',
                'Performance management quality',
                'Career path support',
                'Knowledge transfer success',
            ],
            'Strategic HR' => [
                'Workforce planning',
                'Talent management strategy',
                'Compensation analysis',
                'HR metrics reporting',
                'Organizational development',
            ],
        ],
        'Finance / Accounting' => [
            'Financial Accuracy' => [
                'Transaction processing accuracy',
                'Financial statement correctness',
                'Variance analysis quality',
                'Audit compliance rate',
                'Error reduction metrics',
            ],
            'Reporting & Analysis' => [
                'Report timeliness',
                'Data analysis accuracy',
                'Financial insight quality',
                'Dashboard effectiveness',
                'Stakeholder communication',
            ],
            'Compliance & Control' => [
                'Regulatory compliance',
                'Internal control effectiveness',
                'Policy adherence',
                'Risk management quality',
                'Documentation completeness',
            ],
            'Operational Efficiency' => [
                'Process improvement initiatives',
                'Cost reduction achievements',
                'Workflow optimization',
                'System utilization efficiency',
                'Time management metrics',
            ],
            'Strategic Finance' => [
                'Budgeting accuracy',
                'Forecasting reliability',
                'Investment analysis quality',
                'Cash flow management',
                'Financial planning effectiveness',
            ],
        ],
        'Engineering / Maintenance' => [
            'Technical Performance' => [
                'Preventive maintenance completion',
                'Repair response time',
                'Equipment uptime percentage',
                'Quality of repairs',
                'Technical problem resolution',
            ],
            'Safety & Compliance' => [
                'Safety protocol adherence',
                'Regulatory compliance',
                'Incident reduction rate',
                'Safety training completion',
                'Risk assessment quality',
            ],
            'Operational Efficiency' => [
                'Work order completion rate',
                'Parts inventory management',
                'Energy cost reduction',
                'Preventive maintenance scheduling',
                'Resource utilization',
            ],
            'Guest Impact Management' => [
                'Guest disruption minimization',
                'Communication effectiveness',
                'Service recovery coordination',
                'Guest satisfaction impact',
                'Emergency response quality',
            ],
            'Technical Expertise' => [
                'System knowledge depth',
                'Troubleshooting skills',
                'Equipment proficiency',
                'Technology adaptation',
                'Training effectiveness',
            ],
        ],
        'Security' => [
            'Security Operations' => [
                'Patrol effectiveness',
                'Incident response time',
                'Surveillance monitoring quality',
                'Access control enforcement',
                'Emergency preparedness',
            ],
            'Risk Management' => [
                'Threat assessment accuracy',
                'Vulnerability identification',
                'Risk mitigation effectiveness',
                'Security protocol compliance',
                'Incident prevention rate',
            ],
            'Professional Conduct' => [
                'Professional appearance standards',
                'Communication skills',
                'Conflict resolution ability',
                'Report writing quality',
                'Ethical conduct adherence',
            ],
            'Guest & Staff Safety' => [
                'Guest safety assurance',
                'Staff protection effectiveness',
                'Emergency response coordination',
                'Safety training delivery',
                'Incident documentation quality',
            ],
            'Systems & Technology' => [
                'Security system operation',
                'Monitoring technology proficiency',
                'Data security awareness',
                'Communication equipment usage',
                'Technical skill maintenance',
            ],
        ],
    ];

    // Default KPIs for departments not specifically defined
    $defaultKpiBlueprint = [
        'Work Quality' => [
            'Accuracy of work',
            'Attention to detail',
            'Consistency',
            'Compliance with standards',
            'Professional conduct',
        ],
        'Productivity' => [
            'Time management',
            'Task completion',
            'Meeting deadlines',
            'Efficiency metrics',
        ],
        'Teamwork' => [
            'Collaboration',
            'Communication',
            'Reliability',
            'Support to colleagues',
        ],
        'Professional Development' => [
            'Skill improvement',
            'Learning initiative',
            'Adaptability',
            'Knowledge sharing',
        ],
        'Compliance' => [
            'Policy adherence',
            'Safety awareness',
            'Quality standards',
            'Ethical conduct',
        ],
    ];

    $kpiBlueprint = $kpiBlueprint[$dept] ?? $defaultKpiBlueprint;

    $insertKpi = $pdo->prepare('INSERT INTO kpis (kpi_name, department) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $insertScore = $pdo->prepare('INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) VALUES (?, ?, ?, ?, ?)');

    foreach ($kpiBlueprint as $kpiName => $criteriaList) {
        $insertKpi->execute([$kpiName, $dept !== '' ? $dept : null]);
        $kpiId = (int)$pdo->lastInsertId();
        if ($kpiId <= 0) {
            $fallback = $pdo->prepare('SELECT id FROM kpis WHERE kpi_name = ? AND ((department IS NULL AND ? IS NULL) OR department = ?) LIMIT 1');
            $fallback->execute([$kpiName, $dept !== '' ? $dept : null, $dept !== '' ? $dept : null]);
            $kpiId = (int)($fallback->fetchColumn() ?? 0);
        }
        if ($kpiId <= 0) continue;

        foreach ($criteriaList as $crit) {
            $seed = crc32($employeeId . '|' . $evaluationPeriod . '|' . $kpiName . '|' . $crit);
            $score = (float)((($seed % 5) + 1));
            $insertScore->execute([$employeeId, $evaluationPeriod, $kpiId, $crit, $score]);
        }
    }

    return true;
}

function mapCompetencyToStatus(float $pct): string
{
    if ($pct <= 20) return 'Retrain';
    if ($pct <= 40) return 'Reskilling';
    if ($pct <= 60) return 'Refresher Training';
    if ($pct <= 80) return 'Upskilling';
    return 'Succession Ready';
}

function computeEmployeeCompetency(string $employeeId): array
{
    global $pdo;

    ensureKpiSchema();

    $deptStmt = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ? LIMIT 1");
    $deptStmt->execute([$employeeId]);
    $dept = (string)($deptStmt->fetchColumn() ?: '');

    $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
    seedMissingKpiEvaluations($employeeId, $period);

    $stmt = $pdo->prepare(
        "SELECT AVG(COALESCE(score, 0)) AS avg_score
         FROM employee_kpi_scores
         WHERE employee_id = ? AND evaluation_period = ?"
    );
    $stmt->execute([$employeeId, $period]);
    $avg = $stmt->fetchColumn();
    $avgScore = is_numeric($avg) ? (float)$avg : 0.0;
    if ($avgScore < 0) $avgScore = 0.0;
    if ($avgScore > 5) $avgScore = 5.0;
    $pct = round(($avgScore / 5.0) * 100.0, 1);

    return [
        'competency' => $pct,
        'status' => mapCompetencyToStatus($pct),
        'department' => $dept,
        'evaluation_period' => $period,
    ];
}

function computeEmployeeKpiAnalysis(string $employeeId, string $period): array
{
    global $pdo;

    $employeeId = trim($employeeId);
    $period = trim($period);
    if ($employeeId === '' || $period === '') {
        return ['computed' => [], 'overall' => ['avg' => 0.0, 'pct' => 0.0, 'status' => 'Retrain']];
    }

    ensureKpiSchema();
    ensureCompetencyCriteriaSchema();
    seedMissingKpiEvaluations($employeeId, $period);

    $required = [];
    try {
        $stmt = $pdo->query('SELECT name, required_level FROM competency_criteria');
        $rows = $stmt ? $stmt->fetchAll() : [];
        foreach ($rows as $r) {
            $required[(string)($r['name'] ?? '')] = (float)($r['required_level'] ?? 0);
        }
    } catch (Throwable $e) {
    }

    $stmtKpis = $pdo->prepare(
        'SELECT k.id, k.kpi_name
         FROM employee_kpi_scores s
         JOIN kpis k ON k.id = s.kpi_id
         WHERE s.employee_id = ? AND s.evaluation_period = ?
         GROUP BY k.id, k.kpi_name
         ORDER BY k.kpi_name ASC'
    );
    $stmtKpis->execute([$employeeId, $period]);
    $kpiRows = $stmtKpis->fetchAll();

    $stmtEvals = $pdo->prepare(
        'SELECT k.id AS kpi_id, k.kpi_name, s.criteria, s.score
         FROM employee_kpi_scores s
         JOIN kpis k ON k.id = s.kpi_id
         WHERE s.employee_id = ? AND s.evaluation_period = ?
         ORDER BY k.kpi_name ASC, s.id ASC'
    );
    $stmtEvals->execute([$employeeId, $period]);
    $evalRows = $stmtEvals->fetchAll();

    $byKpi = [];
    foreach ($evalRows as $r) {
        $kid = (int)($r['kpi_id'] ?? 0);
        if (!isset($byKpi[$kid])) $byKpi[$kid] = ['kpi_name' => (string)($r['kpi_name'] ?? ''), 'evals' => []];
        $byKpi[$kid]['evals'][] = [
            'criteria' => (string)($r['criteria'] ?? ''),
            'score' => is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0.0,
        ];
    }

    $maxScore = 5.0;
    $computed = [];
    $overallScores = [];
    foreach ($kpiRows as $k) {
        $kid = (int)($k['id'] ?? 0);
        $kpiName = (string)($k['kpi_name'] ?? '');
        $evals = $byKpi[$kid]['evals'] ?? [];
        $scores = array_values(array_map(static fn($e) => (float)($e['score'] ?? 0), $evals));
        $avg = count($scores) ? (array_sum($scores) / count($scores)) : 0.0;
        $pct = round(max(0.0, min(100.0, ($avg / $maxScore) * 100.0)), 1);
        $req = isset($required[$kpiName]) ? (float)$required[$kpiName] : 80.0;
        if ($req < 0) $req = 0.0;
        if ($req > 100) $req = 100.0;
        $gap = round(max(0.0, $req - $pct), 1);

        $computed[] = [
            'kpi_name' => $kpiName,
            'avg' => round($avg, 2),
            'kpi_pct' => $pct,
            'required_pct' => round($req, 1),
            'gap_pct' => $gap,
            'evaluations' => $evals,
        ];
        foreach ($scores as $s) $overallScores[] = $s;
    }

    $overallAvg = count($overallScores) ? (array_sum($overallScores) / count($overallScores)) : 0.0;
    $overallPct = round(max(0.0, min(100.0, ($overallAvg / $maxScore) * 100.0)), 1);

    return [
        'computed' => $computed,
        'overall' => [
            'avg' => round($overallAvg, 2),
            'pct' => $overallPct,
            'status' => mapCompetencyToStatus($overallPct),
        ],
        'required_levels' => $required,
    ];
}

function employeeHasGaps(string $employeeId): bool
{
    global $pdo;

    ensureKpiSchema();
    ensureCompetencyCriteriaSchema();

    $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
    seedMissingKpiEvaluations($employeeId, $period);

    $deptStmt = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ? LIMIT 1");
    $deptStmt->execute([$employeeId]);
    $dept = (string)($deptStmt->fetchColumn() ?: '');

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM (
            SELECT k.kpi_name,
                   AVG(COALESCE(s.score, 0)) / 5 * 100 AS kpi_pct
            FROM employee_kpi_scores s
            JOIN kpis k ON k.id = s.kpi_id
            WHERE s.employee_id = ? AND s.evaluation_period = ?
              AND (k.department IS NULL OR k.department = ?)
            GROUP BY k.kpi_name
         ) t
         LEFT JOIN competency_criteria cc
           ON cc.name = t.kpi_name
         WHERE (COALESCE(cc.required_level, 80) - COALESCE(t.kpi_pct, 0)) > 0"
    );
    $stmt->execute([$employeeId, $period, $dept]);
    $cnt = (int)($stmt->fetchColumn() ?? 0);
    return $cnt > 0;
}

function updateEmployeeCompetencyRow(string $employeeId): array
{
    global $pdo;

    $r = computeEmployeeCompetency($employeeId);
    $upd = $pdo->prepare("UPDATE employees SET competency = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE employee_id = ?");
    $upd->execute([(float)$r['competency'], (string)$r['status'], $employeeId]);
    return $r;
}

function syncPrePromotionEmployee(string $employeeId): void
{
    global $pdo;

    $r = computeEmployeeCompetency($employeeId);
    $eligible = ((string)$r['status'] === 'Succession Ready') && !employeeHasGaps($employeeId);

    if ($eligible) {
        $stmt = $pdo->prepare(
            "INSERT INTO pre_promotion_employees (employee_id, name, competency_level)
             SELECT employee_id, full_name, ?
             FROM employees
             WHERE employee_id = ?
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                competency_level = VALUES(competency_level)"
        );
        $stmt->execute([(string)$r['status'], $employeeId]);
        return;
    }

    $pdo->prepare("DELETE FROM pre_promotion_employees WHERE employee_id = ?")->execute([$employeeId]);
}

function computeEmployeeDevelopmentStatus(string $employeeId): string
{
    global $pdo;

    try {
        $stmtRepo = $pdo->prepare(
            "SELECT idp_status, training_requested_at
             FROM requested_idps_repository
             WHERE employee_id = ?
             LIMIT 1"
        );
        $stmtRepo->execute([$employeeId]);
        $repo = $stmtRepo->fetch(PDO::FETCH_ASSOC);

        if ($repo) {
            $idpStatus = (string)($repo['idp_status'] ?? '');
            $trainingRequestedAt = $repo['training_requested_at'] ?? null;

            if ($trainingRequestedAt !== null) {
                if (in_array($idpStatus, ['approved', 'under_review', 'on_hold', 'for_compliance'], true)) {
                    return 'On-going Training';
                }

                if ($idpStatus === 'requested') {
                    return 'Training Requested';
                }

                return 'Training Requested';
            }

            return 'IDP Created';
        }

        $stmtIdp = $pdo->prepare(
            "SELECT idp_status, training_requested_at
             FROM individual_development_plans
             WHERE employee_id = ?
             LIMIT 1"
        );
        $stmtIdp->execute([$employeeId]);
        $idp = $stmtIdp->fetch(PDO::FETCH_ASSOC);

        if ($idp) {
            $idpStatus = (string)($idp['idp_status'] ?? '');
            $trainingRequestedAt = $idp['training_requested_at'] ?? null;

            if ($trainingRequestedAt !== null) {
                if (in_array($idpStatus, ['approved', 'under_review', 'on_hold', 'for_compliance'], true)) {
                    return 'On-going Training';
                }

                if ($idpStatus === 'requested') {
                    return 'Training Requested';
                }

                return 'Training Requested';
            }

            return 'IDP Created';
        }

        $stmtReq = $pdo->prepare(
            "SELECT status
             FROM requested_to_idp
             WHERE employee_id = ?
             LIMIT 1"
        );
        $stmtReq->execute([$employeeId]);
        $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

        if ($req && (string)($req['status'] ?? '') === 'Pending') {
            return 'Forwarded for IDP';
        }

        return '';
    } catch (Throwable $e) {
        error_log('computeEmployeeDevelopmentStatus error: ' . $e->getMessage());
        return '';
    }
}

createTablesIfNotExist();
ensureKpiSchema();
ensureCompetencyCriteriaSchema();
ensureGapFormulationSchema();

function getEmployees($filter = 'all', $search = '', $department = 'all')
{
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
                INNER JOIN (
                    SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
                    FROM employee_kpi_scores s2
                    WHERE s2.evaluation_period = ?
                    GROUP BY s2.employee_id
                    HAVING COUNT(*) > 0
                ) gs ON gs.employee_id = e.employee_id
                WHERE 1=1
                  AND EXISTS (
                      SELECT 1
                      FROM kpi_gap_formulations g
                      WHERE g.employee_id = e.employee_id
                        AND g.evaluation_period = ?
                        AND COALESCE(g.forwarded_to_critical, 0) = 1
                  )";
        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
        $params = [$period, $period];

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
function getEmployeeDetails($employee_id)
{
    global $pdo;

    try {
        // Get employee basic info
        $sql = "SELECT * FROM employees WHERE employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();

        if ($employee) {
            $empDept = (string)($employee['department'] ?? '');
            ensureKpiSchema();

            $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
            seedMissingKpiEvaluations((string)$employee_id, $period);

            $analysis = computeEmployeeKpiAnalysis((string)$employee_id, $period);
            $employee['required_levels'] = $analysis['required_levels'] ?? [];
            $employee['analysis'] = [
                'computed' => $analysis['computed'] ?? [],
                'overall' => $analysis['overall'] ?? ['avg' => 0.0, 'pct' => 0.0, 'status' => 'Retrain'],
            ];

            $stmtKpis = $pdo->prepare(
                'SELECT k.kpi_name
                 FROM employee_kpi_scores s
                 JOIN kpis k ON k.id = s.kpi_id
                 WHERE s.employee_id = ? AND s.evaluation_period = ?
                   AND (k.department IS NULL OR k.department = ?)
                 GROUP BY k.kpi_name
                 ORDER BY k.kpi_name ASC'
            );
            $stmtKpis->execute([$employee_id, $period, $empDept]);
            $kpiRows = $stmtKpis->fetchAll();

            $stmtEvals = $pdo->prepare(
                'SELECT k.kpi_name, s.criteria, s.score
                 FROM employee_kpi_scores s
                 JOIN kpis k ON k.id = s.kpi_id
                 WHERE s.employee_id = ? AND s.evaluation_period = ?
                   AND (k.department IS NULL OR k.department = ?)
                 ORDER BY k.kpi_name ASC, s.id ASC'
            );
            $stmtEvals->execute([$employee_id, $period, $empDept]);
            $evalRows = $stmtEvals->fetchAll();

            $byKpi = [];
            $sumAll = 0.0;
            $cntAll = 0;
            foreach ($evalRows as $r) {
                $kName = (string)($r['kpi_name'] ?? '');
                if ($kName === '') {
                    continue;
                }
                if (!isset($byKpi[$kName])) $byKpi[$kName] = [];
                $score = is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0.0;
                $byKpi[$kName][] = [
                    'criteria' => (string)($r['criteria'] ?? ''),
                    'score' => $score,
                ];
                $sumAll += $score;
                $cntAll++;
            }

            $employee['kpis'] = [];
            foreach ($kpiRows as $k) {
                $kpiName = (string)($k['kpi_name'] ?? '');
                $employee['kpis'][] = [
                    'kpi_id' => 0,
                    'kpi_name' => $kpiName,
                    'evaluations' => $kpiName !== '' ? ($byKpi[$kpiName] ?? []) : [],
                ];
            }

            $avgScore = $cntAll > 0 ? ($sumAll / $cntAll) : 0.0;
            if ($avgScore < 0) $avgScore = 0.0;
            if ($avgScore > 5) $avgScore = 5.0;
            $pct = round(($avgScore / 5.0) * 100.0, 1);
            $employee['competency'] = $pct;
            $employee['status'] = mapCompetencyToStatus($pct);
            $employee['evaluation_period'] = $period;
        }

        return $employee;
    } catch (PDOException $e) {
        error_log("Error in getEmployeeDetails: " . $e->getMessage());
        return null;
    }
}

// Function to get competency statistics
function getCompetencyStats()
{
    global $pdo;

    try {
        $stats = [
            'total_employees' => 0,
            'average_competency' => 0,
            'by_status' => [],
            'by_department' => []
        ];

        ensureKpiSchema();

        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total, AVG(t.competency) AS average
             FROM (
                SELECT e.employee_id, e.department, COALESCE(gs.competency, 0) AS competency
                FROM employees e
                LEFT JOIN (
                    SELECT employee_id, AVG(COALESCE(score, 0)) / 5 * 100 AS competency
                    FROM employee_kpi_scores
                    WHERE evaluation_period = ?
                    GROUP BY employee_id
                ) gs ON gs.employee_id = e.employee_id
             ) t"
        );
        $stmt->execute([$period]);
        $result = $stmt->fetch();
        $stats['total_employees'] = $result['total'] ?? 0;
        $stats['average_competency'] = round($result['average'] ?? 0, 1);

        $stmt = $pdo->prepare(
            "SELECT t.status, COUNT(*) AS count
             FROM (
                SELECT e.employee_id,
                       CASE
                           WHEN COALESCE(gs.competency, 0) <= 20 THEN 'Retrain'
                           WHEN COALESCE(gs.competency, 0) <= 40 THEN 'Reskilling'
                           WHEN COALESCE(gs.competency, 0) <= 60 THEN 'Refresher Training'
                           WHEN COALESCE(gs.competency, 0) <= 80 THEN 'Upskilling'
                           ELSE 'Succession Ready'
                       END AS status
                FROM employees e
                LEFT JOIN (
                    SELECT employee_id, AVG(COALESCE(score, 0)) / 5 * 100 AS competency
                    FROM employee_kpi_scores
                    WHERE evaluation_period = ?
                    GROUP BY employee_id
                ) gs ON gs.employee_id = e.employee_id
             ) t
             GROUP BY t.status
             ORDER BY t.status"
        );
        $stmt->execute([$period]);
        $stats['by_status'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT t.department, COUNT(*) AS count, AVG(t.competency) AS avg_competency
             FROM (
                SELECT e.employee_id, e.department, COALESCE(gs.competency, 0) AS competency
                FROM employees e
                LEFT JOIN (
                    SELECT employee_id, AVG(COALESCE(score, 0)) / 5 * 100 AS competency
                    FROM employee_kpi_scores
                    WHERE evaluation_period = ?
                    GROUP BY employee_id
                ) gs ON gs.employee_id = e.employee_id
             ) t
             GROUP BY t.department
             ORDER BY t.department"
        );
        $stmt->execute([$period]);
        $stats['by_department'] = $stmt->fetchAll();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error in getCompetencyStats: " . $e->getMessage());
        return [];
    }
}
