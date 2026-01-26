CREATE DATABASE IF NOT EXISTS job_desc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE job_desc;

CREATE TABLE IF NOT EXISTS departments (
    request_id VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_departments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recruitment_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id VARCHAR(50) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    department_id VARCHAR(50) NOT NULL,
    vacancies INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('SENT','ASSIGNED') NOT NULL DEFAULT 'SENT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recruitment_request_id (request_id),
    INDEX idx_recruitment_department (department_id),
    CONSTRAINT fk_recruitment_requests_department
        FOREIGN KEY (department_id) REFERENCES departments(request_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_roles (
    request_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    department_id VARCHAR(50) NULL,
    vacancies INT UNSIGNED NOT NULL DEFAULT 1,
    description TEXT NOT NULL,
    source_recruitment_request_id VARCHAR(50) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    INDEX idx_job_roles_department (department_id),
    INDEX idx_job_roles_source_recruit (source_recruitment_request_id),
    CONSTRAINT fk_job_roles_department
        FOREIGN KEY (department_id) REFERENCES departments(request_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_titles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_titles_dept_title (department_id, title),
    INDEX idx_job_titles_department (department_id),
    CONSTRAINT fk_job_titles_department
        FOREIGN KEY (department_id) REFERENCES departments(request_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qualifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id VARCHAR(50) NOT NULL,
    qualification TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'Education',
    priority INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_qualifications_request_id (request_id),
    CONSTRAINT fk_qualifications_job_roles
        FOREIGN KEY (request_id) REFERENCES job_roles(request_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_requirements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id VARCHAR(50) NOT NULL,
    requirement TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Skill',
    is_essential TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_job_requirements_request_id (request_id),
    CONSTRAINT fk_job_requirements_job_roles
        FOREIGN KEY (request_id) REFERENCES job_roles(request_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS competency_standards (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category ENUM('core','leadership','technical') NOT NULL DEFAULT 'core',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    description TEXT NOT NULL,
    priority TINYINT UNSIGNED NOT NULL DEFAULT 3,
    role ENUM('hotel','restaurant','both') NOT NULL DEFAULT 'both',
    hotel_context TEXT NULL,
    restaurant_context TEXT NULL,
    education TEXT NULL,
    certifications TEXT NULL,
    tech_skills TEXT NULL,
    soft_skills TEXT NULL,
    experience TEXT NULL,
    physical TEXT NULL,
    qualifications_json TEXT NULL,
    requirements_json TEXT NULL,
    approval_status ENUM('pending','posted','rejected','compliance') NOT NULL DEFAULT 'posted',
    pending_action ENUM('upsert','delete') NOT NULL DEFAULT 'upsert',
    delete_reason TEXT NULL,
    review_reason TEXT NULL,
    requested_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    last_updated DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_competency_role (role),
    INDEX idx_competency_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'approval_status'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN approval_status ENUM('pending','posted','rejected','compliance') NOT NULL DEFAULT 'posted' AFTER requirements_json",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'pending_action'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN pending_action ENUM('upsert','delete') NOT NULL DEFAULT 'upsert' AFTER approval_status",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'delete_reason'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN delete_reason TEXT NULL AFTER pending_action",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'review_reason'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN review_reason TEXT NULL AFTER delete_reason",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'requested_at'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN requested_at TIMESTAMP NULL AFTER review_reason",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'approved_at'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN approved_at TIMESTAMP NULL AFTER requested_at",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'rejected_at'
);
SET @__sql := IF(@__col_exists = 0,
    "ALTER TABLE competency_standards ADD COLUMN rejected_at TIMESTAMP NULL AFTER approved_at",
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'qualifications_json'
);
SET @__sql := IF(@__col_exists = 0,
    'ALTER TABLE competency_standards ADD COLUMN qualifications_json TEXT NULL AFTER physical',
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

SET @__col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'competency_standards'
      AND column_name = 'requirements_json'
);
SET @__sql := IF(@__col_exists = 0,
    'ALTER TABLE competency_standards ADD COLUMN requirements_json TEXT NULL AFTER qualifications_json',
    'SELECT 1'
);
PREPARE __stmt FROM @__sql;
EXECUTE __stmt;
DEALLOCATE PREPARE __stmt;

CREATE TABLE IF NOT EXISTS competency_level_criteria (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    competency_id INT UNSIGNED NOT NULL,
    level TINYINT UNSIGNED NOT NULL,
    criteria_text TEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_comp_level (competency_id, level),
    CONSTRAINT fk_comp_level_competency
        FOREIGN KEY (competency_id) REFERENCES competency_standards(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_criteria_mappings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id VARCHAR(50) NULL,
    job_title_pattern VARCHAR(255) NOT NULL,
    competency_id INT UNSIGNED NOT NULL,
    priority TINYINT UNSIGNED NOT NULL DEFAULT 3,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_job_criteria_dept (department_id),
    INDEX idx_job_criteria_comp (competency_id),
    CONSTRAINT fk_job_criteria_department
        FOREIGN KEY (department_id) REFERENCES departments(request_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_job_criteria_competency
        FOREIGN KEY (competency_id) REFERENCES competency_standards(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_criteria (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id VARCHAR(50) NOT NULL,
    job_title_pattern VARCHAR(255) NOT NULL,
    job_description TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_role_criteria_dept (department_id),
    INDEX idx_role_criteria_active (is_active),
    CONSTRAINT fk_role_criteria_department
        FOREIGN KEY (department_id) REFERENCES departments(request_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_criteria_qualifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    criteria_id INT UNSIGNED NOT NULL,
    qualification TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'Education',
    priority INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_rcq_criteria (criteria_id),
    CONSTRAINT fk_rcq_criteria
        FOREIGN KEY (criteria_id) REFERENCES role_criteria(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_criteria_requirements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    criteria_id INT UNSIGNED NOT NULL,
    requirement TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Skill',
    is_essential TINYINT(1) NOT NULL DEFAULT 1,
    priority INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_rcr_criteria (criteria_id),
    CONSTRAINT fk_rcr_criteria
        FOREIGN KEY (criteria_id) REFERENCES role_criteria(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (request_id, name)
VALUES
    ('DPT-FO', 'Front Office / Reception'),
    ('DPT-HK', 'Housekeeping'),
    ('DPT-FBS', 'Food & Beverage (F&B)'),
    ('DPT-KC', 'Kitchen / Culinary'),
    ('DPT-SM', 'Sales & Marketing'),
    ('DPT-HR', 'Human Resources (HR)'),
    ('DPT-FIN', 'Finance / Accounting'),
    ('DPT-ENG', 'Engineering / Maintenance'),
    ('DPT-SEC', 'Security')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO job_titles (department_id, title)
VALUES
    ('DPT-FO', 'Front Desk Manager'),
    ('DPT-FO', 'Receptionist / Front Desk Officer'),
    ('DPT-FO', 'Guest Service Agent / Concierge'),
    ('DPT-FO', 'Reservation Agent'),
    ('DPT-FO', 'Bellhop / Porter'),

    ('DPT-HK', 'Executive Housekeeper / Housekeeping Manager'),
    ('DPT-HK', 'Floor Supervisor'),
    ('DPT-HK', 'Room Attendant / Housekeeper'),
    ('DPT-HK', 'Laundry Attendant'),
    ('DPT-HK', 'Public Area Attendant'),

    ('DPT-FBS', 'F&B Manager / Director'),
    ('DPT-FBS', 'Restaurant Manager / Captain'),
    ('DPT-FBS', 'Waiter / Waitress / Server'),

    ('DPT-KC', 'Executive Chef / Head Chef'),
    ('DPT-KC', 'Sous Chef (assistant to head chef)'),
    ('DPT-KC', 'Line Cook / Station Chef'),
    ('DPT-KC', 'Pastry Chef / Baker'),
    ('DPT-KC', 'Kitchen Steward / Dishwasher'),

    ('DPT-SM', 'Sales & Marketing Manager'),
    ('DPT-SM', 'Revenue Manager'),
    ('DPT-SM', 'Event / Banquet Sales Coordinator'),
    ('DPT-SM', 'Social Media / Marketing Executive'),

    ('DPT-HR', 'HR Manager / Director'),
    ('DPT-HR', 'Recruitment Officer'),
    ('DPT-HR', 'Training & Development Specialist'),
    ('DPT-HR', 'Payroll / HR Assistant'),

    ('DPT-FIN', 'Finance Manager / Controller'),
    ('DPT-FIN', 'Accountant'),
    ('DPT-FIN', 'Payroll Officer'),
    ('DPT-FIN', 'Cost Controller'),

    ('DPT-ENG', 'Chief Engineer / Engineering Manager'),
    ('DPT-ENG', 'Maintenance Technician'),
    ('DPT-ENG', 'Electrician / Plumber'),
    ('DPT-ENG', 'HVAC Technician'),

    ('DPT-SEC', 'Security Manager / Supervisor'),
    ('DPT-SEC', 'Security Guard'),
    ('DPT-SEC', 'CCTV / Surveillance Officer')
ON DUPLICATE KEY UPDATE
    title = VALUES(title);

INSERT INTO competency_standards
    (id, name, category, status, description, priority, role, hotel_context, restaurant_context, education, certifications, tech_skills, soft_skills, experience, physical, last_updated)
VALUES
    (1, 'Customer Service Excellence', 'core', 'active', 'Providing exceptional service to guests and customers, anticipating needs and resolving issues promptly', 5, 'both',
        'Handling guest check-ins, resolving room issues, providing local recommendations',
        'Managing reservations, handling customer complaints, ensuring dining experience quality',
        'High School Diploma or equivalent',
        'Hospitality Certificate, Customer Service Training',
        'Reservation systems, POS operation, CRM software',
        'Communication, empathy, problem-solving, patience',
        '1+ years in customer-facing role',
        'Able to stand for extended periods',
        '2024-01-15'
    ),
    (2, 'Food Safety & Hygiene', 'technical', 'active', 'Maintaining proper food handling, storage, and preparation techniques to ensure safety', 5, 'restaurant',
        NULL,
        'Following HACCP principles, maintaining kitchen cleanliness, proper food storage',
        'High School Diploma',
        'Food Handler Certificate, HACCP Certification',
        'Temperature monitoring, sanitation procedures, inventory rotation',
        'Attention to detail, compliance mindset, hygiene awareness',
        '6+ months in food service',
        'Able to handle cleaning chemicals safely',
        '2024-01-10'
    ),
    (3, 'Room Service Management', 'technical', 'active', 'Managing efficient and timely delivery of room service orders to hotel guests', 4, 'hotel',
        'Order taking, food preparation coordination, timely delivery, billing accuracy',
        NULL,
        'High School Diploma',
        'Food Safety Certificate',
        'Order management systems, kitchen communication, billing software',
        'Time management, attention to detail, customer interaction',
        '1+ years in hotel service',
        'Able to carry trays, walk extended distances',
        '2024-01-05'
    ),
    (4, 'Team Leadership', 'leadership', 'active', 'Leading and motivating teams to achieve operational excellence', 4, 'both',
        'Leading housekeeping teams, front desk teams, or kitchen staff',
        'Leading wait staff, kitchen crew, or bar team',
        "Associate's Degree or equivalent experience",
        'Leadership Training, Management Certificate',
        'Scheduling software, performance tracking, reporting tools',
        'Communication, motivation, conflict resolution, delegation',
        '2+ years in supervisory role',
        NULL,
        '2024-01-12'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    category = VALUES(category),
    status = VALUES(status),
    description = VALUES(description),
    priority = VALUES(priority),
    role = VALUES(role),
    hotel_context = VALUES(hotel_context),
    restaurant_context = VALUES(restaurant_context),
    education = VALUES(education),
    certifications = VALUES(certifications),
    tech_skills = VALUES(tech_skills),
    soft_skills = VALUES(soft_skills),
    experience = VALUES(experience),
    physical = VALUES(physical),
    last_updated = VALUES(last_updated);

INSERT INTO competency_level_criteria (competency_id, level, criteria_text)
VALUES
    (1, 1, 'Follows basic customer service protocols, requires supervision for complex situations'),
    (1, 2, 'Handles routine customer interactions independently, seeks guidance for difficult cases'),
    (1, 3, 'Manages most customer situations effectively, provides solutions proactively'),
    (1, 4, 'Handles complex customer issues, mentors junior staff, suggests improvements'),
    (1, 5, 'Develops customer service strategies, trains teams, sets service standards'),
    (2, 1, 'Follows basic hygiene rules, requires reminders about safety protocols'),
    (2, 2, 'Maintains personal hygiene, follows standard safety procedures consistently'),
    (2, 3, 'Identifies potential safety hazards, ensures compliance in work area'),
    (2, 4, 'Trains others in safety protocols, implements safety improvements'),
    (2, 5, 'Develops safety policies, conducts audits, ensures regulatory compliance'),
    (3, 1, 'Delivers basic orders with supervision, learns procedures'),
    (3, 2, 'Handles routine orders independently, maintains basic timing'),
    (3, 3, 'Manages multiple orders efficiently, resolves common issues'),
    (3, 4, 'Optimizes delivery routes, trains new staff, improves processes'),
    (3, 5, 'Develops service protocols, analyzes performance metrics, implements improvements'),
    (4, 1, 'Assists team lead, learns basic supervisory tasks'),
    (4, 2, 'Leads small teams, handles routine supervision'),
    (4, 3, 'Manages department shifts, resolves team conflicts'),
    (4, 4, 'Leads multiple teams, develops team members, improves processes'),
    (4, 5, 'Sets departmental strategy, develops leadership pipeline, drives cultural change')
ON DUPLICATE KEY UPDATE
    criteria_text = VALUES(criteria_text);

INSERT INTO job_criteria_mappings (department_id, job_title_pattern, competency_id, priority, is_active)
VALUES
    ('DPT-FO', '%Front Desk%', 1, 5, 0),
    ('DPT-FO', '%Concierge%', 1, 4, 0),
    ('DPT-FO', '%Bell%', 1, 4, 0),
    ('DPT-HK', '%Housekeep%', 4, 4, 0),
    ('DPT-HK', '%Room Attendant%', 1, 3, 0),
    ('DPT-FBS', '%Waiter%', 1, 4, 0),
    ('DPT-FBS', '%Server%', 1, 4, 0),
    ('DPT-FBS', '%Bartender%', 1, 4, 0),
    ('DPT-FBS', '%Restaurant%', 4, 4, 0),
    ('DPT-FBS', '%Banquet%', 4, 3, 0),
    ('DPT-KC', '%Chef%', 2, 5, 0),
    ('DPT-KC', '%Cook%', 2, 5, 0),
    ('DPT-KC', '%Kitchen%', 2, 5, 0),
    ('DPT-SM', '%Sales%', 4, 3, 0),
    ('DPT-SM', '%Marketing%', 4, 3, 0)
ON DUPLICATE KEY UPDATE
    competency_id = VALUES(competency_id),
    priority = VALUES(priority),
    is_active = VALUES(is_active);
