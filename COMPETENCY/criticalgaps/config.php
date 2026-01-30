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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_emp_period (employee_id, evaluation_period),
                INDEX idx_period (evaluation_period),
                CONSTRAINT fk_gap_emp FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
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

    $kpiBlueprint = [
        'Work Quality' => [
            'Accuracy of work',
            'Attention to detail',
            'Consistency',
            'Compliance with standards',
            'Cleanliness',
        ],
        'Productivity' => [
            'Time management',
            'Task completion',
            'Meeting deadlines',
        ],
        'Customer Service' => [
            'Professionalism',
            'Responsiveness',
            'Guest handling',
        ],
        'Teamwork' => [
            'Collaboration',
            'Communication',
            'Reliability',
        ],
        'Compliance' => [
            'Policy adherence',
            'Safety & sanitation',
        ],
    ];

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

function employeeHasGaps(string $employeeId): bool
{
    global $pdo;

    ensureKpiSchema();
    ensureCompetencyCriteriaSchema();

    $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
    seedMissingKpiEvaluations($employeeId, $period);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM (
            SELECT k.kpi_name,
                   AVG(COALESCE(s.score, 0)) / 5 * 100 AS kpi_pct
            FROM employee_kpi_scores s
            JOIN kpis k ON k.id = s.kpi_id
            WHERE s.employee_id = ? AND s.evaluation_period = ?
            GROUP BY k.kpi_name
         ) t
         LEFT JOIN competency_criteria cc
           ON cc.name = t.kpi_name
         WHERE (COALESCE(cc.required_level, 80) - COALESCE(t.kpi_pct, 0)) > 0"
    );
    $stmt->execute([$employeeId, $period]);
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

// Initialize database
createTablesIfNotExist();
ensureKpiSchema();
ensureCompetencyCriteriaSchema();
ensureGapFormulationSchema();

// Function to get employees
function getEmployees($filter = 'all', $search = '', $department = 'all')
{
    global $pdo;

    try {
        $sql = "SELECT e.id, e.employee_id, e.full_name, e.position, e.department, e.last_assessment, e.next_review_date,
                       COALESCE(gf.overall_competency, 0) AS competency,
                       COALESCE(gf.status, 'Retrain') AS status

                FROM employees e
                JOIN kpi_gap_formulations gf
                  ON gf.employee_id = e.employee_id
                 AND gf.evaluation_period = ?
                WHERE 1=1
                ";
        $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
        $params = [$period];

        // Apply department filter
        if ($department !== 'all') {
            $sql .= " AND e.department = ?";
            $params[] = $department;
        }

        // Apply status filter
        if ($filter !== 'all') {
            $sql .= " AND gf.status = ?";
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
                WHEN COALESCE(gf.overall_competency, 0) <= 20 THEN 1
                WHEN COALESCE(gf.overall_competency, 0) <= 40 THEN 2
                WHEN COALESCE(gf.overall_competency, 0) <= 60 THEN 3
                WHEN COALESCE(gf.overall_competency, 0) <= 80 THEN 4
                ELSE 5
            END,
            COALESCE(gf.overall_competency, 0) DESC,
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
            ensureKpiSchema();

            $period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);
            seedMissingKpiEvaluations((string)$employee_id, $period);

            $stmtKpis = $pdo->prepare(
                'SELECT k.id, k.kpi_name
                 FROM employee_kpi_scores s
                 JOIN kpis k ON k.id = s.kpi_id
                 WHERE s.employee_id = ? AND s.evaluation_period = ?
                 GROUP BY k.id, k.kpi_name
                 ORDER BY k.kpi_name ASC'
            );
            $stmtKpis->execute([$employee_id, $period]);
            $kpiRows = $stmtKpis->fetchAll();

            $stmtEvals = $pdo->prepare(
                'SELECT k.id AS kpi_id, s.criteria, s.score
                 FROM employee_kpi_scores s
                 JOIN kpis k ON k.id = s.kpi_id
                 WHERE s.employee_id = ? AND s.evaluation_period = ?
                 ORDER BY k.kpi_name ASC, s.id ASC'
            );
            $stmtEvals->execute([$employee_id, $period]);
            $evalRows = $stmtEvals->fetchAll();

            $byKpi = [];
            $sumAll = 0.0;
            $cntAll = 0;
            foreach ($evalRows as $r) {
                $kid = (int)($r['kpi_id'] ?? 0);
                if (!isset($byKpi[$kid])) $byKpi[$kid] = [];
                $score = is_numeric($r['score'] ?? null) ? (float)$r['score'] : 0.0;
                $byKpi[$kid][] = [
                    'criteria' => (string)($r['criteria'] ?? ''),
                    'score' => $score,
                ];
                $sumAll += $score;
                $cntAll++;
            }

            $employee['kpis'] = [];
            foreach ($kpiRows as $k) {
                $kid = (int)($k['id'] ?? 0);
                $employee['kpis'][] = [
                    'kpi_id' => $kid,
                    'kpi_name' => (string)($k['kpi_name'] ?? ''),
                    'evaluations' => $byKpi[$kid] ?? [],
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
