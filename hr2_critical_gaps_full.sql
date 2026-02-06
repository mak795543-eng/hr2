CREATE DATABASE IF NOT EXISTS `hr2_critical_gaps` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hr2_critical_gaps`;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `competency` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `last_assessment` DATE,
  `next_review_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `skill_name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `weight` DECIMAL(3,2) DEFAULT 1.0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_skill_department` (`skill_name`, `category`, `department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `competency_criteria` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `required_level` DECIMAL(5,2) NOT NULL DEFAULT 80,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_competency_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kpis` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `kpi_name` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_kpi` (`kpi_name`, `department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employee_skills` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `skill_id` INT NOT NULL,
  `skill_score` DECIMAL(5,2) DEFAULT 0,
  `assessment_date` DATE DEFAULT CURRENT_DATE,
  `assessed_by` VARCHAR(100),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employee_skill` (`employee_id`, `skill_id`),
  CONSTRAINT `fk_emp_skill_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_emp_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employee_kpi_scores` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `evaluation_period` VARCHAR(50) NOT NULL,
  `kpi_id` INT NOT NULL,
  `criteria` VARCHAR(255) NOT NULL,
  `score` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `assessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_emp_kpi_criteria` (`employee_id`, `evaluation_period`, `kpi_id`, `criteria`),
  INDEX `idx_emp_period` (`employee_id`, `evaluation_period`),
  CONSTRAINT `fk_emp_kpi_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_emp_kpi_kpi` FOREIGN KEY (`kpi_id`) REFERENCES `kpis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kpi_gap_formulations` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `evaluation_period` VARCHAR(50) NOT NULL,
  `overall_competency` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL,
  `details_json` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_emp_period` (`employee_id`, `evaluation_period`),
  INDEX `idx_period` (`evaluation_period`),
  CONSTRAINT `fk_gap_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `succession_submissions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `employee_name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `competency` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` TEXT,
  `target_score` DECIMAL(5,2) DEFAULT NULL,
  `target_date` DATE DEFAULT NULL,
  `idp_status` ENUM('Pending','Created') DEFAULT 'Pending',
  `idp_created_at` TIMESTAMP NULL DEFAULT NULL,
  `is_pushed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employee_submission` (`employee_id`),
  CONSTRAINT `fk_succ_sub_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `requested_to_idp` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `employee_name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `status` ENUM('Pending','Created') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_requested_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `individual_development_plans` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `employee_name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `competency` DECIMAL(5,2) DEFAULT 0,
  `succession_status` ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` TEXT,
  `target_score` DECIMAL(5,2) DEFAULT NULL,
  `target_date` DATE DEFAULT NULL,
  `delivery_mode` ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite',
  `requested_training_type` VARCHAR(50) DEFAULT NULL,
  `requested_training_mode` VARCHAR(20) DEFAULT NULL,
  `requested_start_datetime` DATETIME DEFAULT NULL,
  `requested_end_datetime` DATETIME DEFAULT NULL,
  `idp_status` ENUM('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'under_review',
  `training_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `learning_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employee_idp` (`employee_id`),
  INDEX `idx_idp_status` (`idp_status`),
  CONSTRAINT `fk_idp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `requested_idps_repository` (
  `id` INT PRIMARY KEY,
  `employee_id` VARCHAR(50) NOT NULL,
  `employee_name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `competency` DECIMAL(5,2) DEFAULT 0,
  `succession_status` ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` TEXT,
  `target_score` DECIMAL(5,2) DEFAULT NULL,
  `target_date` DATE DEFAULT NULL,
  `delivery_mode` ENUM('Online','Onsite','Hybrid') DEFAULT 'Onsite',
  `requested_training_type` VARCHAR(50) DEFAULT NULL,
  `requested_training_mode` VARCHAR(20) DEFAULT NULL,
  `requested_start_datetime` DATETIME DEFAULT NULL,
  `requested_end_datetime` DATETIME DEFAULT NULL,
  `idp_status` ENUM('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'requested',
  `training_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `learning_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employee_requested_idp` (`employee_id`),
  INDEX `idx_requested_idp_status` (`idp_status`),
  CONSTRAINT `fk_req_idp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_skill_standards` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `skill_id` INT NOT NULL,
  `standard_percentage` DECIMAL(5,2) NOT NULL DEFAULT 80,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_skill_standard` (`skill_id`),
  INDEX `idx_standard_skill` (`skill_id`),
  CONSTRAINT `fk_gss_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pre_promotion_employees` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `employee_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `competency_level` VARCHAR(50) NOT NULL,
  `date_added` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_pre_promotion_employee` (`employee_id`),
  CONSTRAINT `fk_pre_promotion_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `development_plan_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `department` VARCHAR(100) NOT NULL,
  `role` VARCHAR(100) NOT NULL DEFAULT '',
  `skill_id` INT NOT NULL,
  `status` ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL,
  `plan_text` TEXT NOT NULL,
  `delivery_mode` ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite',
  `target_percentage` DECIMAL(5,2) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_devplan` (`department`, `role`, `skill_id`, `status`),
  INDEX `idx_dept_status` (`department`, `status`),
  CONSTRAINT `fk_devplan_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO `departments` (`name`) VALUES
('Front Office / Reception'),
('Housekeeping'),
('Food & Beverage (F&B)'),
('Kitchen / Culinary'),
('Sales & Marketing'),
('Human Resources (HR)'),
('Finance / Accounting'),
('Engineering / Maintenance'),
('Security');

INSERT IGNORE INTO `skills` (`skill_name`, `category`, `department`, `description`, `weight`) VALUES
('Customer Service Excellence', 'General Skills', 'Front Office / Reception', 'Delivering courteous, professional, and guest-focused service at all times', 1.0),
('Effective Communication Skills', 'General Skills', 'Front Office / Reception', 'Clear verbal and written communication with guests and internal teams', 1.0),
('Problem-Solving & Complaint Handling', 'General Skills', 'Front Office / Reception', 'Managing guest concerns calmly and effectively', 1.0),
('Hotel Systems & Reservation Knowledge', 'General Skills', 'Front Office / Reception', 'Understanding PMS, booking systems, and guest records', 1.0),
('Professional Appearance & Etiquette', 'General Skills', 'Front Office / Reception', 'Maintaining grooming standards and hospitality behavior', 1.0),
('Time Management & Multitasking', 'General Skills', 'Front Office / Reception', 'Handling multiple guest requests efficiently', 1.0),
('Cultural Awareness & Guest Sensitivity', 'General Skills', 'Front Office / Reception', 'Respecting diverse guest backgrounds and needs', 1.0),
('Attention to Detail', 'General Skills', 'Housekeeping', 'Ensuring cleanliness, hygiene, and presentation standards', 1.0),
('Knowledge of Cleaning Procedures & Chemicals', 'General Skills', 'Housekeeping', 'Safe and correct use of tools and materials', 1.0),
('Time Management', 'General Skills', 'Housekeeping', 'Completing tasks within operational schedules', 1.0),
('Health & Safety Awareness', 'General Skills', 'Housekeeping', 'Following safety, sanitation, and hazard prevention protocols', 1.0),
('Teamwork & Coordination', 'General Skills', 'Housekeeping', 'Working effectively with supervisors and other departments', 1.0),
('Physical Endurance & Task Discipline', 'General Skills', 'Housekeeping', 'Performing repetitive tasks efficiently', 1.0),
('Quality Control Awareness', 'General Skills', 'Housekeeping', 'Meeting hotel cleanliness and inspection standards', 1.0),
('Guest Service & Hospitality Skills', 'General Skills', 'Food & Beverage (F&B)', 'Providing friendly, attentive dining service', 1.0),
('Food Safety & Hygiene Knowledge', 'General Skills', 'Food & Beverage (F&B)', 'Compliance with sanitation and food handling standards', 1.0),
('Product Knowledge', 'General Skills', 'Food & Beverage (F&B)', 'Understanding menus, ingredients, beverages, and service styles', 1.0),
('Communication & Coordination', 'General Skills', 'Food & Beverage (F&B)', 'Clear interaction with kitchen and service teams', 1.0),
('Sales & Upselling Skills', 'General Skills', 'Food & Beverage (F&B)', 'Promoting menu items and enhancing guest experience', 1.0),
('Stress & Time Management', 'General Skills', 'Food & Beverage (F&B)', 'Performing under pressure during peak service hours', 1.0),
('Professional Conduct & Service Etiquette', 'General Skills', 'Food & Beverage (F&B)', 'Maintaining service standards', 1.0),
('Food Preparation & Culinary Fundamentals', 'General Skills', 'Kitchen / Culinary', 'Understanding cooking techniques and recipes', 1.0),
('Food Safety & Sanitation Compliance', 'General Skills', 'Kitchen / Culinary', 'Following HACCP and hygiene standards', 1.0),
('Time & Workflow Management', 'General Skills', 'Kitchen / Culinary', 'Meeting service timelines during operations', 1.0),
('Teamwork & Kitchen Coordination', 'General Skills', 'Kitchen / Culinary', 'Working efficiently within the kitchen brigade', 1.0),
('Attention to Quality & Presentation', 'General Skills', 'Kitchen / Culinary', 'Maintaining consistency in food standards', 1.0),
('Equipment Handling & Safety Awareness', 'General Skills', 'Kitchen / Culinary', 'Proper use of kitchen tools and machinery', 1.0),
('Waste Control & Cost Awareness', 'General Skills', 'Kitchen / Culinary', 'Minimizing food waste', 1.0),
('Communication & Presentation Skills', 'General Skills', 'Sales & Marketing', 'Effective client and stakeholder interaction', 1.0),
('Customer Relationship Management (CRM)', 'General Skills', 'Sales & Marketing', 'Building and maintaining client relationships', 1.0),
('Market & Trend Awareness', 'General Skills', 'Sales & Marketing', 'Understanding customer behavior and industry trends', 1.0),
('Negotiation & Persuasion Skills', 'General Skills', 'Sales & Marketing', 'Closing deals and partnerships', 1.0),
('Analytical & Reporting Skills', 'General Skills', 'Sales & Marketing', 'Interpreting sales data and performance metrics', 1.0),
('Planning & Coordination', 'General Skills', 'Sales & Marketing', 'Executing campaigns and events efficiently', 1.0),
('Professional Branding Awareness', 'General Skills', 'Sales & Marketing', 'Maintaining hotel brand consistency', 1.0),
('Interpersonal & Communication Skills', 'General Skills', 'Human Resources (HR)', 'Managing employee relations professionally', 1.0),
('Confidentiality & Ethical Judgment', 'General Skills', 'Human Resources (HR)', 'Handling sensitive employee information', 1.0),
('Policy & Labor Law Awareness', 'General Skills', 'Human Resources (HR)', 'Understanding HR policies and compliance', 1.0),
('Organizational & Documentation Skills', 'General Skills', 'Human Resources (HR)', 'Managing employee records and reports', 1.0),
('Problem-Solving & Decision-Making', 'General Skills', 'Human Resources (HR)', 'Addressing workforce issues effectively', 1.0),
('Training & Development Awareness', 'General Skills', 'Human Resources (HR)', 'Supporting employee growth initiatives', 1.0),
('System & HRIS Proficiency', 'General Skills', 'Human Resources (HR)', 'Using HR software efficiently', 1.0),
('Financial Accuracy & Attention to Detail', 'General Skills', 'Finance / Accounting', 'Ensuring error-free financial records', 1.0),
('Numerical & Analytical Skills', 'General Skills', 'Finance / Accounting', 'Interpreting financial data and trends', 1.0),
('Compliance & Policy Awareness', 'General Skills', 'Finance / Accounting', 'Following accounting standards and regulations', 1.0),
('Confidentiality & Integrity', 'General Skills', 'Finance / Accounting', 'Handling financial data responsibly', 1.0),
('Reporting & Documentation Skills', 'General Skills', 'Finance / Accounting', 'Preparing financial statements and reports', 1.0),
('Time Management & Deadline Control', 'General Skills', 'Finance / Accounting', 'Meeting financial cut-offs', 1.0),
('Cost Control Awareness', 'General Skills', 'Finance / Accounting', 'Monitoring expenses and budgets', 1.0),
('Technical & Mechanical Knowledge', 'General Skills', 'Engineering / Maintenance', 'Understanding hotel systems and equipment', 1.0),
('Preventive Maintenance Skills', 'General Skills', 'Engineering / Maintenance', 'Conducting routine inspections and repairs', 1.0),
('Health & Safety Compliance', 'General Skills', 'Engineering / Maintenance', 'Adhering to safety and operational standards', 1.0),
('Problem Diagnosis & Troubleshooting', 'General Skills', 'Engineering / Maintenance', 'Identifying and resolving technical issues', 1.0),
('Documentation & Reporting Skills', 'General Skills', 'Engineering / Maintenance', 'Logging maintenance activities', 1.0),
('Team Coordination & Communication', 'General Skills', 'Engineering / Maintenance', 'Working with other departments', 1.0),
('Emergency Response Readiness', 'General Skills', 'Engineering / Maintenance', 'Handling urgent technical incidents', 1.0),
('Observation & Situational Awareness', 'General Skills', 'Security', 'Monitoring surroundings effectively', 1.0),
('Emergency Response & Crisis Handling', 'General Skills', 'Security', 'Managing incidents calmly', 1.0),
('Communication & Reporting Skills', 'General Skills', 'Security', 'Writing incident and security reports', 1.0),
('Access Control & Patrol Skills', 'General Skills', 'Security', 'Maintaining property security', 1.0),
('Conflict Management & De-escalation', 'General Skills', 'Security', 'Handling disturbances professionally', 1.0),
('Discipline & Professional Conduct', 'General Skills', 'Security', 'Maintaining authority and integrity', 1.0),
('Safety & Risk Awareness', 'General Skills', 'Security', 'Identifying and mitigating threats', 1.0);
