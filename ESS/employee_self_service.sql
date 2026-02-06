-- ============================================
-- EMPLOYEE SELF SERVICE DATABASE
-- ============================================

CREATE DATABASE IF NOT EXISTS hr2_employee_self_service
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hr2_employee_self_service;
-- ============================================
-- 1. EMPLOYEES (CORE TABLE)
-- ============================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_no VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(50) NULL,
    email VARCHAR(150) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. MY ACHIEVEMENTS
-- ============================================
CREATE TABLE employee_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    achievement_date DATE NOT NULL,
    issued_by VARCHAR(150),
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 3. PAYMENT HISTORY
-- ============================================
CREATE TABLE payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    basic_pay DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_pay DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    status ENUM('Paid', 'Pending') DEFAULT 'Paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 4. MY DOCUMENTS
-- ============================================
CREATE TABLE employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 5. SUBMIT DOCUMENT
-- ============================================
CREATE TABLE submitted_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_title VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    remarks TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 6. LEAVE REQUEST
-- ============================================
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'For Compliance') DEFAULT 'Pending',
    remarks TEXT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_created (employee_id, created_at),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6.1 NOTIFICATION STATES
-- ============================================
CREATE TABLE notification_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    notif_key CHAR(40) NOT NULL,
    status ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    notif_type VARCHAR(60) NULL,
    notif_title VARCHAR(255) NULL,
    notif_meta VARCHAR(255) NULL,
    notif_link VARCHAR(255) NULL,
    notif_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_emp_notif (employee_id, notif_key),
    INDEX idx_emp_status (employee_id, status),
    INDEX idx_emp_deleted (employee_id, deleted)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. COMPLAINT
-- ============================================
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Open', 'In Review', 'Resolved', 'Closed') DEFAULT 'Open',
    handled_by INT,
    resolution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    meeting_date DATE NULL,
    meeting_time TIME NULL,
    meeting_place VARCHAR(255) NULL,
    meeting_scheduled_by INT NULL,
    meeting_scheduled_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 8. EMPLOYEE PROFILES (EXTENDED FIELDS)
-- ============================================
CREATE TABLE employee_profiles (
    employee_id INT PRIMARY KEY,
    phone VARCHAR(50) NULL,
    work_location VARCHAR(150) NULL,
    gender VARCHAR(20) NULL,
    age INT NULL,
    birthdate DATE NULL,
    civil_status VARCHAR(50) NULL,
    nationality VARCHAR(100) NULL,
    emergency_name VARCHAR(150) NULL,
    emergency_relationship VARCHAR(100) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- 9. PROFILE UPDATE REQUESTS
-- ============================================
CREATE TABLE profile_update_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    requested_data TEXT NOT NULL,
    reason_choice VARCHAR(100) NULL,
    reason_text TEXT NULL,
    proof_file_path VARCHAR(255) NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'For Compliance') DEFAULT 'Pending',
    remarks TEXT,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seen_by_employee TINYINT(1) DEFAULT 0,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE recent_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    activity_type VARCHAR(80) NOT NULL,
    activity_title VARCHAR(255) NOT NULL,
    activity_meta TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_created (employee_id, created_at),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ============================================
-- END OF EMPLOYEE SELF SERVICE DATABASE
-- ============================================
