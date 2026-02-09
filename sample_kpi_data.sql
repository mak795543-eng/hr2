-- Sample KPI Data for Skill Gap Analysis
-- This file creates employees with different competency statuses to test the system

-- Insert employees with different departments and positions
INSERT IGNORE INTO employees (employee_id, full_name, department, position) VALUES
-- Retrain Status (0-20% competency)
('EMP001', 'John Smith', 'Front Office / Reception', 'Front Desk Agent'),
('EMP002', 'Maria Garcia', 'Housekeeping', 'Room Attendant'),
('EMP003', 'Robert Chen', 'Food & Beverage (F&B)', 'Waiter'),
('EMP004', 'Sarah Johnson', 'Kitchen / Culinary', 'Line Cook'),
('EMP005', 'Michael Brown', 'Sales & Marketing', 'Sales Associate'),

-- Reskilling Status (21-40% competency)
('EMP006', 'Emily Davis', 'Human Resources (HR)', 'HR Assistant'),
('EMP007', 'David Wilson', 'Finance / Accounting', 'Accounting Clerk'),
('EMP008', 'Lisa Anderson', 'Engineering / Maintenance', 'Maintenance Helper'),
('EMP009', 'James Taylor', 'Security', 'Security Guard'),
('EMP010', 'Jennifer Martinez', 'Front Office / Reception', 'Concierge'),

-- Refresher Training Status (41-60% competency)
('EMP011', 'William Thomas', 'Housekeeping', 'Housekeeping Supervisor'),
('EMP012', 'Patricia Jackson', 'Food & Beverage (F&B)', 'Server'),
('EMP013', 'Christopher White', 'Kitchen / Culinary', 'Prep Cook'),
('EMP014', 'Linda Harris', 'Sales & Marketing', 'Marketing Coordinator'),
('EMP015', 'Daniel Martin', 'Human Resources (HR)', 'Recruiter'),

-- Upskilling Status (61-80% competency)
('EMP016', 'Nancy Thompson', 'Finance / Accounting', 'Senior Accountant'),
('EMP017', 'Paul Garcia', 'Engineering / Maintenance', 'Maintenance Technician'),
('EMP018', 'Karen Rodriguez', 'Security', 'Senior Security Officer'),
('EMP019', 'Steven Lee', 'Front Office / Reception', 'Front Office Manager'),
('EMP020', 'Betty Walker', 'Housekeeping', 'Executive Housekeeper'),

-- Succession Ready Status (81-100% competency)
('EMP021', 'Donald Hall', 'Food & Beverage (F&B)', 'Restaurant Manager'),
('EMP022', 'Helen Allen', 'Kitchen / Culinary', 'Executive Chef'),
('EMP023', 'Mark Young', 'Sales & Marketing', 'Sales Director'),
('EMP024', 'Sandra King', 'Human Resources (HR)', 'HR Manager'),
('EMP025', 'Joseph Wright', 'Finance / Accounting', 'Finance Director');

-- Set evaluation period
SET @eval_period = '2026-Q1';

-- Insert KPI scores for each employee with calculated scores to achieve desired competency levels
-- Retrain Status Employees (0-20% = average score 0.4-1.0 out of 5)

-- EMP001 - John Smith (Retrain - ~15% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP001', @eval_period, k.id, criteria, 
CASE 
    WHEN criteria IN ('Professionalism and appearance', 'Guest greeting and welcome') THEN 0.8
    WHEN criteria IN ('Check-in/check-out efficiency', 'Problem resolution skills') THEN 0.6
    ELSE 0.4
END as score
FROM kpis k 
WHERE k.kpi_name IN ('Guest Service Excellence', 'Operational Efficiency', 'Customer Relations', 'Hotel Knowledge', 'Team Collaboration')
AND (k.department = 'Front Office / Reception' OR k.department IS NULL);

-- EMP002 - Maria Garcia (Retrain - ~18% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP002', @eval_period, k.id, criteria, 
CASE 
    WHEN criteria IN ('Room cleanliness scores', 'Attention to detail') THEN 1.0
    WHEN criteria IN ('Sanitation compliance', 'Aesthetic standards') THEN 0.8
    ELSE 0.5
END as score
FROM kpis k 
WHERE k.kpi_name IN ('Quality Standards', 'Operational Efficiency', 'Safety & Compliance', 'Guest Service Support', 'Team Coordination')
AND (k.department = 'Housekeeping' OR k.department IS NULL);

-- EMP003 - Robert Chen (Retrain - ~12% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP003', @eval_period, k.id, criteria, 0.5 as score
FROM kpis k 
WHERE k.kpi_name IN ('Service Excellence', 'Product Knowledge', 'Operational Efficiency', 'Sanitation & Safety', 'Revenue Generation')
AND (k.department = 'Food & Beverage (F&B)' OR k.department IS NULL);

-- EMP004 - Sarah Johnson (Retrain - ~20% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP004', @eval_period, k.id, criteria, 0.6 as score
FROM kpis k 
WHERE k.kpi_name IN ('Food Quality', 'Kitchen Operations', 'Safety & Sanitation', 'Menu Knowledge', 'Team Leadership')
AND (k.department = 'Kitchen / Culinary' OR k.department IS NULL);

-- EMP005 - Michael Brown (Retrain - ~10% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP005', @eval_period, k.id, criteria, 0.4 as score
FROM kpis k 
WHERE k.kpi_name IN ('Sales Performance', 'Client Relations', 'Market Development', 'Communication Skills', 'Strategic Planning')
AND (k.department = 'Sales & Marketing' OR k.department IS NULL);

-- Reskilling Status Employees (21-40% = average score 1.05-2.0 out of 5)

-- EMP006 - Emily Davis (Reskilling - ~35% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP006', @eval_period, k.id, criteria, 1.8 as score
FROM kpis k 
WHERE k.kpi_name IN ('Employee Relations', 'Recruitment & Hiring', 'Compliance & Administration', 'Training & Development', 'Strategic HR')
AND (k.department = 'Human Resources (HR)' OR k.department IS NULL);

-- EMP007 - David Wilson (Reskilling - ~30% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP007', @eval_period, k.id, criteria, 1.5 as score
FROM kpis k 
WHERE k.kpi_name IN ('Financial Accuracy', 'Reporting & Analysis', 'Compliance & Control', 'Operational Efficiency', 'Strategic Finance')
AND (k.department = 'Finance / Accounting' OR k.department IS NULL);

-- EMP008 - Lisa Anderson (Reskilling - ~25% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP008', @eval_period, k.id, criteria, 1.2 as score
FROM kpis k 
WHERE k.kpi_name IN ('Technical Performance', 'Safety & Compliance', 'Operational Efficiency', 'Guest Impact Management', 'Technical Expertise')
AND (k.department = 'Engineering / Maintenance' OR k.department IS NULL);

-- EMP009 - James Taylor (Reskilling - ~38% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP009', @eval_period, k.id, criteria, 1.9 as score
FROM kpis k 
WHERE k.kpi_name IN ('Security Operations', 'Risk Management', 'Professional Conduct', 'Guest & Staff Safety', 'Systems & Technology')
AND (k.department = 'Security' OR k.department IS NULL);

-- EMP010 - Jennifer Martinez (Reskilling - ~32% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP010', @eval_period, k.id, criteria, 1.6 as score
FROM kpis k 
WHERE k.kpi_name IN ('Guest Service Excellence', 'Operational Efficiency', 'Customer Relations', 'Hotel Knowledge', 'Team Collaboration')
AND (k.department = 'Front Office / Reception' OR k.department IS NULL);

-- Refresher Training Status Employees (41-60% = average score 2.05-3.0 out of 5)

-- EMP011 - William Thomas (Refresher Training - ~55% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP011', @eval_period, k.id, criteria, 2.8 as score
FROM kpis k 
WHERE k.kpi_name IN ('Quality Standards', 'Operational Efficiency', 'Safety & Compliance', 'Guest Service Support', 'Team Coordination')
AND (k.department = 'Housekeeping' OR k.department IS NULL);

-- EMP012 - Patricia Jackson (Refresher Training - ~48% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP012', @eval_period, k.id, criteria, 2.4 as score
FROM kpis k 
WHERE k.kpi_name IN ('Service Excellence', 'Product Knowledge', 'Operational Efficiency', 'Sanitation & Safety', 'Revenue Generation')
AND (k.department = 'Food & Beverage (F&B)' OR k.department IS NULL);

-- EMP013 - Christopher White (Refresher Training - ~52% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP013', @eval_period, k.id, criteria, 2.6 as score
FROM kpis k 
WHERE k.kpi_name IN ('Food Quality', 'Kitchen Operations', 'Safety & Sanitation', 'Menu Knowledge', 'Team Leadership')
AND (k.department = 'Kitchen / Culinary' OR k.department IS NULL);

-- EMP014 - Linda Harris (Refresher Training - ~45% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP014', @eval_period, k.id, criteria, 2.2 as score
FROM kpis k 
WHERE k.kpi_name IN ('Sales Performance', 'Client Relations', 'Market Development', 'Communication Skills', 'Strategic Planning')
AND (k.department = 'Sales & Marketing' OR k.department IS NULL);

-- EMP015 - Daniel Martin (Refresher Training - ~58% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP015', @eval_period, k.id, criteria, 2.9 as score
FROM kpis k 
WHERE k.kpi_name IN ('Employee Relations', 'Recruitment & Hiring', 'Compliance & Administration', 'Training & Development', 'Strategic HR')
AND (k.department = 'Human Resources (HR)' OR k.department IS NULL);

-- Upskilling Status Employees (61-80% = average score 3.05-4.0 out of 5)

-- EMP016 - Nancy Thompson (Upskilling - ~75% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP016', @eval_period, k.id, criteria, 3.8 as score
FROM kpis k 
WHERE k.kpi_name IN ('Financial Accuracy', 'Reporting & Analysis', 'Compliance & Control', 'Operational Efficiency', 'Strategic Finance')
AND (k.department = 'Finance / Accounting' OR k.department IS NULL);

-- EMP017 - Paul Garcia (Upskilling - ~68% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP017', @eval_period, k.id, criteria, 3.4 as score
FROM kpis k 
WHERE k.kpi_name IN ('Technical Performance', 'Safety & Compliance', 'Operational Efficiency', 'Guest Impact Management', 'Technical Expertise')
AND (k.department = 'Engineering / Maintenance' OR k.department IS NULL);

-- EMP018 - Karen Rodriguez (Upskilling - ~72% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP018', @eval_period, k.id, criteria, 3.6 as score
FROM kpis k 
WHERE k.kpi_name IN ('Security Operations', 'Risk Management', 'Professional Conduct', 'Guest & Staff Safety', 'Systems & Technology')
AND (k.department = 'Security' OR k.department IS NULL);

-- EMP019 - Steven Lee (Upskilling - ~78% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP019', @eval_period, k.id, criteria, 3.9 as score
FROM kpis k 
WHERE k.kpi_name IN ('Guest Service Excellence', 'Operational Efficiency', 'Customer Relations', 'Hotel Knowledge', 'Team Collaboration')
AND (k.department = 'Front Office / Reception' OR k.department IS NULL);

-- EMP020 - Betty Walker (Upskilling - ~65% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP020', @eval_period, k.id, criteria, 3.2 as score
FROM kpis k 
WHERE k.kpi_name IN ('Quality Standards', 'Operational Efficiency', 'Safety & Compliance', 'Guest Service Support', 'Team Coordination')
AND (k.department = 'Housekeeping' OR k.department IS NULL);

-- Succession Ready Status Employees (81-100% = average score 4.05-5.0 out of 5)

-- EMP021 - Donald Hall (Succession Ready - ~92% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP021', @eval_period, k.id, criteria, 4.6 as score
FROM kpis k 
WHERE k.kpi_name IN ('Service Excellence', 'Product Knowledge', 'Operational Efficiency', 'Sanitation & Safety', 'Revenue Generation')
AND (k.department = 'Food & Beverage (F&B)' OR k.department IS NULL);

-- EMP022 - Helen Allen (Succession Ready - ~95% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP022', @eval_period, k.id, criteria, 4.8 as score
FROM kpis k 
WHERE k.kpi_name IN ('Food Quality', 'Kitchen Operations', 'Safety & Sanitation', 'Menu Knowledge', 'Team Leadership')
AND (k.department = 'Kitchen / Culinary' OR k.department IS NULL);

-- EMP023 - Mark Young (Succession Ready - ~88% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP023', @eval_period, k.id, criteria, 4.4 as score
FROM kpis k 
WHERE k.kpi_name IN ('Sales Performance', 'Client Relations', 'Market Development', 'Communication Skills', 'Strategic Planning')
AND (k.department = 'Sales & Marketing' OR k.department IS NULL);

-- EMP024 - Sandra King (Succession Ready - ~90% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP024', @eval_period, k.id, criteria, 4.5 as score
FROM kpis k 
WHERE k.kpi_name IN ('Employee Relations', 'Recruitment & Hiring', 'Compliance & Administration', 'Training & Development', 'Strategic HR')
AND (k.department = 'Human Resources (HR)' OR k.department IS NULL);

-- EMP025 - Joseph Wright (Succession Ready - ~93% competency)
INSERT IGNORE INTO employee_kpi_scores (employee_id, evaluation_period, kpi_id, criteria, score) 
SELECT 'EMP025', @eval_period, k.id, criteria, 4.7 as score
FROM kpis k 
WHERE k.kpi_name IN ('Financial Accuracy', 'Reporting & Analysis', 'Compliance & Control', 'Operational Efficiency', 'Strategic Finance')
AND (k.department = 'Finance / Accounting' OR k.department IS NULL);

-- Update employee competency and status based on their KPI scores
UPDATE employees e 
SET 
    competency = (
        SELECT ROUND(AVG(COALESCE(s.score, 0)) / 5 * 100, 1)
        FROM employee_kpi_scores s
        WHERE s.employee_id = e.employee_id AND s.evaluation_period = @eval_period
    ),
    status = CASE 
        WHEN (SELECT AVG(COALESCE(s.score, 0)) FROM employee_kpi_scores s WHERE s.employee_id = e.employee_id AND s.evaluation_period = @eval_period) <= 1.0 THEN 'Retrain'
        WHEN (SELECT AVG(COALESCE(s.score, 0)) FROM employee_kpi_scores s WHERE s.employee_id = e.employee_id AND s.evaluation_period = @eval_period) <= 2.0 THEN 'Reskilling'
        WHEN (SELECT AVG(COALESCE(s.score, 0)) FROM employee_kpi_scores s WHERE s.employee_id = e.employee_id AND s.evaluation_period = @eval_period) <= 3.0 THEN 'Refresher Training'
        WHEN (SELECT AVG(COALESCE(s.score, 0)) FROM employee_kpi_scores s WHERE s.employee_id = e.employee_id AND s.evaluation_period = @eval_period) <= 4.0 THEN 'Upskilling'
        ELSE 'Succession Ready'
    END
WHERE e.employee_id IN (
    SELECT DISTINCT employee_id 
    FROM employee_kpi_scores 
    WHERE evaluation_period = @eval_period
);

-- Insert gap formulations for all employees
INSERT IGNORE INTO kpi_gap_formulations (employee_id, evaluation_period, overall_competency, status, details_json)
SELECT 
    e.employee_id,
    @eval_period,
    e.competency,
    e.status,
    JSON_OBJECT(
        'evaluation_period', @eval_period,
        'total_kpis', COUNT(DISTINCT s.kpi_id),
        'total_criteria', COUNT(s.id),
        'average_score', AVG(s.score),
        'generated_at', NOW()
    )
FROM employees e
JOIN employee_kpi_scores s ON e.employee_id = s.employee_id AND s.evaluation_period = @eval_period
GROUP BY e.employee_id, e.competency, e.status;

COMMIT;
