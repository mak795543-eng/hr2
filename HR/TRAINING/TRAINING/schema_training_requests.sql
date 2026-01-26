CREATE TABLE IF NOT EXISTS training_programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  training_title VARCHAR(255) NOT NULL,
  training_type VARCHAR(50) NOT NULL,
  training_mode VARCHAR(20) NOT NULL DEFAULT 'Onsite',
  description TEXT NOT NULL,
  target_audience VARCHAR(100) NOT NULL,
  department_id INT NULL,
  sub_department VARCHAR(150) NULL,
  target_role VARCHAR(100) NULL,
  mentor_id INT NULL,
  category VARCHAR(100) NOT NULL,
  participants_needed INT NOT NULL,
  max_participants INT NULL,
  training_level VARCHAR(50) NULL,
  training_objectives_json TEXT NULL,
  training_objectives_other TEXT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Under Review',
  status_reason TEXT NULL,
  need_budget TINYINT(1) NOT NULL DEFAULT 0,
  need_items TINYINT(1) NOT NULL DEFAULT 0,
  need_facility TINYINT(1) NOT NULL DEFAULT 0,
  submission_no INT NOT NULL DEFAULT 1,
  financial_budget_amount DECIMAL(12,2) NULL,
  financial_details_json TEXT NULL,
  logistics_items_requested TEXT NULL,
  logistics_details_json TEXT NULL,
  admin_facility_details TEXT NULL,
  admin_details_json TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_program_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  old_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NOT NULL,
  reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tpl_program (program_id),
  INDEX idx_tpl_created (created_at),
  CONSTRAINT fk_tpl_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS financial_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending',
  budget_amount DECIMAL(12,2) NULL,
  details_json TEXT NULL,
  rejection_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_financial_status (status),
  INDEX idx_financial_program (program_id),
  CONSTRAINT fk_financial_requests_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logistics_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending',
  items_requested TEXT NULL,
  details_json TEXT NULL,
  rejection_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_logistics_status (status),
  INDEX idx_logistics_program (program_id),
  CONSTRAINT fk_logistics_requests_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  status ENUM('Pending','Approved','Rejected','Completed','ON HOLD') NOT NULL DEFAULT 'Pending',
  facility_details TEXT NULL,
  details_json TEXT NULL,
  rejection_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_status (status),
  INDEX idx_admin_program (program_id),
  CONSTRAINT fk_admin_requests_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS department_request_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_type ENUM('financial','logistics','admin') NOT NULL,
  request_id INT NOT NULL,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  old_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NOT NULL,
  reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_drl_program (program_id),
  INDEX idx_drl_type (request_type),
  INDEX idx_drl_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS training_program_drafts (
  id VARCHAR(64) PRIMARY KEY,
  session_key VARCHAR(128) NOT NULL,
  title VARCHAR(255) NOT NULL,
  data_json LONGTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tpd_session (session_key),
  INDEX idx_tpd_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mentors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mentor_name VARCHAR(150) NOT NULL,
  expertise VARCHAR(150) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_mentor_name (mentor_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES
('Juan Dela Cruz', 'Leadership'),
('Maria Santos', 'Technical Skills'),
('Jose Reyes', 'Customer Service');

CREATE TABLE IF NOT EXISTS department_heads (
  department_id INT PRIMARY KEY,
  mentor_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_dh_mentor (mentor_id),
  CONSTRAINT fk_department_heads_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS department_managers (
  department_id INT PRIMARY KEY,
  mentor_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_dm_mentor (mentor_id),
  CONSTRAINT fk_department_managers_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES
('Front Office / Reception Department Head', 'Department Head'),
('Housekeeping Department Head', 'Department Head'),
('Food & Beverage (F&B) Department Head', 'Department Head'),
('Kitchen / Culinary Department Head', 'Department Head'),
('Sales & Marketing Department Head', 'Department Head'),
('Human Resources (HR) Department Head', 'Department Head'),
('Finance / Accounting Department Head', 'Department Head'),
('Engineering / Maintenance Department Head', 'Department Head'),
('Security Department Head', 'Department Head');

INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES
('Front Office / Reception Manager', 'Manager'),
('Housekeeping Manager', 'Manager'),
('Food & Beverage (F&B) Manager', 'Manager'),
('Kitchen / Culinary Manager', 'Manager'),
('Sales & Marketing Manager', 'Manager'),
('Human Resources (HR) Manager', 'Manager'),
('Finance / Accounting Manager', 'Manager'),
('Engineering / Maintenance Manager', 'Manager'),
('Security Manager', 'Manager');

INSERT IGNORE INTO department_heads (department_id, mentor_id) VALUES
(1, (SELECT id FROM mentors WHERE mentor_name = 'Front Office / Reception Department Head' LIMIT 1)),
(2, (SELECT id FROM mentors WHERE mentor_name = 'Housekeeping Department Head' LIMIT 1)),
(3, (SELECT id FROM mentors WHERE mentor_name = 'Food & Beverage (F&B) Department Head' LIMIT 1)),
(4, (SELECT id FROM mentors WHERE mentor_name = 'Kitchen / Culinary Department Head' LIMIT 1)),
(5, (SELECT id FROM mentors WHERE mentor_name = 'Sales & Marketing Department Head' LIMIT 1)),
(6, (SELECT id FROM mentors WHERE mentor_name = 'Human Resources (HR) Department Head' LIMIT 1)),
(7, (SELECT id FROM mentors WHERE mentor_name = 'Finance / Accounting Department Head' LIMIT 1)),
(8, (SELECT id FROM mentors WHERE mentor_name = 'Engineering / Maintenance Department Head' LIMIT 1)),
(9, (SELECT id FROM mentors WHERE mentor_name = 'Security Department Head' LIMIT 1))
ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id);

INSERT INTO department_managers (department_id, mentor_id) VALUES
(1, (SELECT id FROM mentors WHERE mentor_name = 'Front Office / Reception Manager' LIMIT 1)),
(2, (SELECT id FROM mentors WHERE mentor_name = 'Housekeeping Manager' LIMIT 1)),
(3, (SELECT id FROM mentors WHERE mentor_name = 'Food & Beverage (F&B) Manager' LIMIT 1)),
(4, (SELECT id FROM mentors WHERE mentor_name = 'Kitchen / Culinary Manager' LIMIT 1)),
(5, (SELECT id FROM mentors WHERE mentor_name = 'Sales & Marketing Manager' LIMIT 1)),
(6, (SELECT id FROM mentors WHERE mentor_name = 'Human Resources (HR) Manager' LIMIT 1)),
(7, (SELECT id FROM mentors WHERE mentor_name = 'Finance / Accounting Manager' LIMIT 1)),
(8, (SELECT id FROM mentors WHERE mentor_name = 'Engineering / Maintenance Manager' LIMIT 1)),
(9, (SELECT id FROM mentors WHERE mentor_name = 'Security Manager' LIMIT 1))
ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id);

ALTER TABLE training_programs
  ADD INDEX idx_tp_mentor_id (mentor_id),
  ADD CONSTRAINT fk_training_programs_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_no VARCHAR(50) NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  department VARCHAR(150) NULL,
  role VARCHAR(150) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_employee_no (employee_no),
  INDEX idx_employee_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_training_post (program_id, submission_no),
  INDEX idx_tp_program (program_id),
  CONSTRAINT fk_tp_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_post_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  submission_no INT NOT NULL DEFAULT 1,
  employee_id INT NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_tpa (program_id, submission_no, employee_id),
  INDEX idx_tpa_program (program_id),
  INDEX idx_tpa_employee (employee_id),
  CONSTRAINT fk_tpa_program FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
  CONSTRAINT fk_tpa_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
