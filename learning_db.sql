-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 24, 2026 at 06:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `learning_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_employee_competency` (IN `p_employee_id` VARCHAR(50))   BEGIN
    DECLARE v_weighted_score DECIMAL(10,2);
    DECLARE v_total_weight DECIMAL(10,2);
    DECLARE v_new_competency DECIMAL(5,2);
    DECLARE v_new_status VARCHAR(50);
    
    -- Calculate weighted average of skills
    SELECT 
        SUM(es.skill_score * 
            CASE s.category
                WHEN 'Technical' THEN 0.4
                WHEN 'Soft Skills' THEN 0.3
                WHEN 'Leadership' THEN 0.15
                WHEN 'Industry Knowledge' THEN 0.1
                WHEN 'Safety' THEN 0.05
                ELSE 0.1
            END),
        SUM(CASE s.category
                WHEN 'Technical' THEN 0.4
                WHEN 'Soft Skills' THEN 0.3
                WHEN 'Leadership' THEN 0.15
                WHEN 'Industry Knowledge' THEN 0.1
                WHEN 'Safety' THEN 0.05
                ELSE 0.1
            END)
    INTO v_weighted_score, v_total_weight
    FROM employee_skills es
    JOIN skills s ON es.skill_id = s.id
    WHERE es.employee_id = p_employee_id;
    
    -- Calculate new competency
    IF v_total_weight > 0 THEN
        SET v_new_competency = ROUND(v_weighted_score / v_total_weight, 2);
    ELSE
        SET v_new_competency = 0;
    END IF;
    
    -- Determine status based on competency
    IF v_new_competency <= 30 THEN
        SET v_new_status = 'Reskilling';
    ELSEIF v_new_competency <= 60 THEN
        SET v_new_status = 'Retain';
    ELSEIF v_new_competency <= 85 THEN
        SET v_new_status = 'Upskilling';
    ELSE
        SET v_new_status = 'Succession Ready';
    END IF;
    
    -- Update employee record
    UPDATE employees 
    SET 
        competency = v_new_competency,
        status = v_new_status,
        last_assessment = CURDATE(),
        next_review_date = DATE_ADD(CURDATE(), INTERVAL 6 MONTH),
        updated_at = NOW()
    WHERE employee_id = p_employee_id;
    
    -- Insert into history
    INSERT INTO competency_history (employee_id, assessment_date, overall_competency, status)
    VALUES (p_employee_id, CURDATE(), v_new_competency, v_new_status);
    
    SELECT 'Success' as result, v_new_competency as new_competency, v_new_status as new_status;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Engineering', 'Software development and engineering department', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(2, 'Sales', 'Sales and business development team', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(3, 'Marketing', 'Marketing and communications department', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(4, 'Human Resources', 'Human resources and talent management', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(5, 'Finance', 'Financial planning and accounting', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(6, 'Operations', 'Business operations and administration', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(7, 'IT', 'Information technology and support', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(8, 'Customer Service', 'Customer support and service', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(9, 'Research & Development', 'Research and product development', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09'),
(10, 'Quality Assurance', 'Quality control and testing', NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09');

-- --------------------------------------------------------

--
-- Table structure for table `development_plans`
--

CREATE TABLE `development_plans` (
  `id` int(11) NOT NULL,
  `plan_id` varchar(50) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Draft','Active','Completed','On Hold','Cancelled') DEFAULT 'Draft',
  `start_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `development_plans`
--

INSERT INTO `development_plans` (`id`, `plan_id`, `employee_id`, `title`, `description`, `status`, `start_date`, `target_date`, `actual_completion_date`, `created_by`, `approved_by`, `approval_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'DP001', 'EMP004', 'Technical Skills Development Plan', 'Comprehensive plan to improve technical competencies for HR role', 'Active', '2024-02-01', '2024-08-01', NULL, NULL, NULL, NULL, NULL, '2026-01-12 11:34:09', '2026-01-12 11:34:09');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `email`, `department`, `position`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'john.doe@company.com', 'human-resources', 'HR Manager', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(2, 'Jane', 'Smith', 'jane.smith@company.com', 'operations', 'Operations Supervisor', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(3, 'Mike', 'Johnson', 'mike.johnson@company.com', 'information-technology', 'IT Specialist', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(4, 'Sarah', 'Wilson', 'sarah.wilson@company.com', 'front-office', 'Receptionist', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(5, 'David', 'Brown', 'david.brown@company.com', 'kitchen', 'Chef', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(6, 'Emily', 'Davis', 'emily.davis@company.com', 'sales-marketing', 'Sales Executive', 'active', '2026-01-09 09:51:37', '2026-01-09 09:51:37'),
(7, 'Marco', 'Reyes', 'marco.reyes@company.com', 'kitchen', 'Executive Chef / Head Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(8, 'Isabella', 'Cruz', 'isabella.cruz@company.com', 'kitchen', 'Executive Chef / Head Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(9, 'Paolo', 'Santos', 'paolo.santos@company.com', 'kitchen', 'Sous Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(10, 'Angela', 'Lopez', 'angela.lopez@company.com', 'kitchen', 'Sous Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(11, 'Joshua', 'Garcia', 'joshua.garcia@company.com', 'kitchen', 'Line Cook / Station Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(12, 'Mika', 'Delos Reyes', 'mika.delosreyes@company.com', 'kitchen', 'Line Cook / Station Chef', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(13, 'Frances', 'Navarro', 'frances.navarro@company.com', 'kitchen', 'Pastry Chef / Baker', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(14, 'Diego', 'Tan', 'diego.tan@company.com', 'kitchen', 'Pastry Chef / Baker', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(15, 'Noah', 'Fernandez', 'noah.fernandez@company.com', 'kitchen', 'Kitchen Steward / Dishwasher', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24'),
(16, 'Lia', 'Mendoza', 'lia.mendoza@company.com', 'kitchen', 'Kitchen Steward / Dishwasher', 'active', '2026-01-16 05:22:24', '2026-01-16 05:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `examinations`
--

CREATE TABLE `examinations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `question_count` int(11) DEFAULT 0,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `passing_score` decimal(5,2) NOT NULL COMMENT 'Passing percentage',
  `status` enum('draft','pending','approved','rejected','hold','compliance','for-compliance','posted','cancelled') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `posted_at` timestamp NULL DEFAULT NULL,
  `roles` varchar(255) DEFAULT 'All Roles',
  `module_id` int(11) DEFAULT NULL,
  `module_title` varchar(255) DEFAULT NULL,
  `answer_keys` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_keys`)),
  `total_points` int(11) DEFAULT 0,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `examinations`
--

INSERT INTO `examinations` (`id`, `title`, `description`, `department`, `question_count`, `duration`, `passing_score`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`, `posted_at`, `roles`, `module_id`, `module_title`, `answer_keys`, `total_points`, `reviewed_at`) VALUES
(67, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 0, 60, 70.00, '', '', 0, '2026-01-15 17:10:19', '2026-01-15 18:18:14', NULL, 'Finance Manager / Controller', 45, 'Financial Leadership & Control Systems', NULL, 5, '2026-01-16 01:12:10'),
(68, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-15 17:19:43', '2026-01-15 18:18:01', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 5, '2026-01-16 01:30:23'),
(69, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-15 17:20:01', '2026-01-15 18:17:30', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 5, '2026-01-16 01:30:15'),
(70, 'Advanced Culinary Leadership & Menu Strategy', 'Focuses on kitchen leadership, menu innovation, food cost control, and culinary quality assurance.', 'kitchen', 0, 60, 70.00, '', '', 0, '2026-01-15 17:29:52', '2026-01-15 18:17:07', NULL, 'Executive Chef / Head Chef', 42, 'Advanced Culinary Leadership & Menu Strategy', NULL, 5, '2026-01-16 01:30:10'),
(71, 'Hospitality Sales Strategy & Market Growth', 'Develops skills in sales planning, market analysis, client relations, and revenue growth strategies.', 'sales-marketing', 0, 60, 70.00, '', '', 0, '2026-01-16 13:31:44', '2026-01-16 13:33:50', NULL, 'Sales & Marketing Manager', 43, 'Hospitality Sales Strategy & Market Growth', NULL, 5, '2026-01-16 21:32:55'),
(72, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-17 05:41:22', '2026-01-17 05:43:42', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 1, '2026-01-17 13:43:06'),
(73, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-21 20:51:49', '2026-01-22 00:56:21', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 1, '2026-01-22 08:56:21'),
(74, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-21 21:57:20', '2026-01-22 00:20:04', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 1, '2026-01-22 08:20:04'),
(75, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 0, 60, 70.00, '', '', 0, '2026-01-21 22:12:32', '2026-01-22 00:15:46', NULL, 'Finance Manager / Controller', 45, 'Financial Leadership & Control Systems', NULL, 1, '2026-01-22 08:15:46'),
(76, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 0, 60, 70.00, '', '', 0, '2026-01-21 23:10:33', '2026-01-22 00:15:27', NULL, 'HR Manager / Director', 44, 'Strategic HR Leadership in Hospitality', NULL, 1, '2026-01-22 08:15:27'),
(77, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 0, 60, 70.00, 'cancelled', '', 0, '2026-01-22 00:57:12', '2026-01-22 01:01:45', NULL, 'Finance Manager / Controller', 45, 'Financial Leadership & Control Systems', NULL, 1, '2026-01-22 09:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `examination_drafts`
--

CREATE TABLE `examination_drafts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `roles` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT 60,
  `passing_score` int(11) DEFAULT 70,
  `question_count` int(11) DEFAULT 0,
  `exam_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exam_data`)),
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_questions`
--

CREATE TABLE `examination_questions` (
  `id` int(11) NOT NULL,
  `examination_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `question_type` enum('multiple','truefalse','shortanswer','identification') NOT NULL,
  `question_text` text NOT NULL,
  `options` text DEFAULT NULL,
  `expected_answer` text DEFAULT NULL,
  `answer_key` text DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `examination_questions`
--

INSERT INTO `examination_questions` (`id`, `examination_id`, `question_number`, `question_type`, `question_text`, `options`, `expected_answer`, `answer_key`, `points`, `created_at`) VALUES
(48, 67, 1, 'truefalse', 'This executive-level module is designed for financial leaders in the hospitality industry, focusing on the strategic oversight and control of financial resources', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-15 17:10:19'),
(49, 67, 2, 'multiple', 'Upholding the highest standards of financial integrity and transparency.', '[\"Ethics\",\"Governance \",\" Fiduciary Responsibility\",\"Technology \"]', '', '{\"points\":1,\"correctAnswers\":[\"Ethics\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:10:19'),
(50, 67, 3, 'multiple', 'Selecting and visualizing the 10-15 most critical metrics for executive decision-making.', '[\" Creating a Management KPI Dashboard\",\"Cash Flow Management & Forecasting\",\" Ownership & Lender Reporting\"]', '', '{\"points\":1,\"correctAnswers\":[\" Creating a Management KPI Dashboard\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:10:19'),
(51, 67, 4, 'multiple', 'Ownership & Lender Reporting', '[\" Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).\",\" Cash Flow Management & Forecasting\",\"Creating a Management KPI Dashboard\",\"Key Financial Ratios & Benchmarking\"]', '', '{\"points\":1,\"correctAnswers\":[\" Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:10:19'),
(52, 67, 5, 'multiple', 'Principles of COSO (Committee of Sponsoring Organizations).', '[\"Designing an Effective Internal Control Framework\",\" Control Activities for Key Hospitality Processe\",\"Managing the Audit Process\",\"Compliance & Regulatory Reporting\"]', '', '{\"points\":1,\"correctAnswers\":[\"Designing an Effective Internal Control Framework\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:10:20'),
(53, 68, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-15 17:19:43'),
(54, 68, 2, 'multiple', ' The Hospitality Labor Landscape: ', '[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Evaluating and implementing HRIS, talent analytics platforms, and AI tools for strategic advantage.\",\"Aligning staffing models with business cycles and growth plans.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:19:43'),
(55, 68, 3, 'multiple', 'Aligning staffing models with business cycles and growth plans.', '[\"Demand Forecasting\",\" Strategic Talent Acquisition & Employer Branding\",\"Succession Planning & Leadership Development\",\"Strategic Learning & Development\"]', '', '{\"points\":1,\"correctAnswers\":[\"Demand Forecasting\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:19:43'),
(56, 68, 4, 'multiple', 'Talent Mobility & Retention Strategie', '[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\",\"Moving from annual reviews to continuous feedback, coaching, and development-focused conversations.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:19:43'),
(57, 68, 5, 'multiple', 'Advanced techniques for handling sensitive complaints (harassment, discrimination, theft).', '[\"Leading Complex Investigations & Dispute Resolution\",\" Labor Relations Strategy (Union & Non-Union)\",\" Designing & Governing HR Policies\"]', '', '{\"points\":1,\"correctAnswers\":[\"Leading Complex Investigations & Dispute Resolution\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:19:43'),
(58, 69, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-15 17:20:01'),
(59, 69, 2, 'multiple', ' The Hospitality Labor Landscape: ', '[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Evaluating and implementing HRIS, talent analytics platforms, and AI tools for strategic advantage.\",\"Aligning staffing models with business cycles and growth plans.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:20:02'),
(60, 69, 3, 'multiple', 'Aligning staffing models with business cycles and growth plans.', '[\"Demand Forecasting\",\" Strategic Talent Acquisition & Employer Branding\",\"Succession Planning & Leadership Development\",\"Strategic Learning & Development\"]', '', '{\"points\":1,\"correctAnswers\":[\"Demand Forecasting\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:20:02'),
(61, 69, 4, 'multiple', 'Talent Mobility & Retention Strategie', '[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\",\"Moving from annual reviews to continuous feedback, coaching, and development-focused conversations.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:20:02'),
(62, 69, 5, 'multiple', 'Advanced techniques for handling sensitive complaints (harassment, discrimination, theft).', '[\"Leading Complex Investigations & Dispute Resolution\",\" Labor Relations Strategy (Union & Non-Union)\",\" Designing & Governing HR Policies\"]', '', '{\"points\":1,\"correctAnswers\":[\"Leading Complex Investigations & Dispute Resolution\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:20:02'),
(63, 70, 1, 'truefalse', 'Articulate and implement a culinary vision and leadership philosophy that inspires a team, drives culture, and aligns with business goals.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-15 17:29:52'),
(64, 70, 2, 'truefalse', 'This high-level module equips experienced culinary leaders with the strategic mindset and practical tools to lead a profitable, innovative, and excellence-driven kitchen.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-15 17:29:52'),
(65, 70, 3, 'multiple', 'Labor Cost Optimization in the Kitchen', '[\"Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.\",\"Developing detailed production guides with photos, weights, and plating specs.\",\"The Quality Line Check: Implementing a pre-service tasting and inspection ritual for every station.\",\" Setting and auditing supplier specifications, conducting facility visits, and building partnerships for quality.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:29:52'),
(66, 70, 4, 'multiple', 'Sustainable & Ethical Sourcing Strategy', '[\"Building a program that is both principled and practical. Communicating your story.\",\"Balancing innovation with core offerings.\",\"Evaluating and implementing tech for inventory, ordering, recipe costing, and waste tracking.\"]', '', '{\"points\":1,\"correctAnswers\":[\"Building a program that is both principled and practical. Communicating your story.\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:29:52'),
(67, 70, 5, 'multiple', 'Develop a comprehensive business plan for a new restaurant concept or a major repositioning of an existing one.', '[\" The Challenge\",\"Culinary Leadership Labs\",\"Financial Modeling Workshops\",\"Case Studies\"]', '', '{\"points\":1,\"correctAnswers\":[\" The Challenge\"],\"questionType\":\"multiple\"}', 1, '2026-01-15 17:29:52'),
(68, 71, 1, 'truefalse', 'This advanced strategic module equips hospitality leaders with the frameworks and skills to drive sustainable market growth through analytical sales planning and strategic client management.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-16 13:31:44'),
(69, 71, 2, 'multiple', 'Motivating, coaching, and managing a metrics-driven sales culture.', '[\"Leading a High-Performance Sales Team\",\"Sales Technology Stack Strategy\"]', '', '{\"points\":1,\"correctAnswers\":[\"Leading a High-Performance Sales Team\"],\"questionType\":\"multiple\"}', 1, '2026-01-16 13:31:44'),
(70, 71, 3, 'truefalse', 'Identifying direct & indirect competitors.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-16 13:31:44'),
(71, 71, 4, 'truefalse', 'Unit 3: Strategic Client Relationship & Account Management (8 hours)', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-16 13:31:44'),
(72, 71, 5, 'truefalse', '4.3 Digital Lead Generation & Social Selling: Using LinkedIn, industry forums, and content marketing to build pipeline.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-16 13:31:44'),
(73, 72, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-17 05:41:22'),
(74, 73, 1, 'truefalse', 'Strategic HR Leadership in Hospitality', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-21 20:51:49'),
(75, 74, 1, 'truefalse', 'Strategic HR Leadership in Hospitality', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-21 21:57:20'),
(76, 75, 1, 'truefalse', 'Financial Leadership & Control Systems', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-21 22:12:33'),
(77, 76, 1, 'truefalse', 'Strategic HR Leadership in Hospitality', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-21 23:10:33'),
(78, 77, 1, 'truefalse', 'Financial Leadership & Control Systems', '[\"True\",\"False\"]', '', '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', 1, '2026-01-22 00:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `examination_statistics`
--

CREATE TABLE `examination_statistics` (
  `id` int(11) NOT NULL,
  `examination_id` int(11) NOT NULL,
  `total_attempts` int(11) DEFAULT 0,
  `completed_attempts` int(11) DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `pass_rate` decimal(5,2) DEFAULT 0.00,
  `best_score` decimal(5,2) DEFAULT 0.00,
  `worst_score` decimal(5,2) DEFAULT 0.00,
  `average_time` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_assignments`
--

CREATE TABLE `exam_assignments` (
  `id` int(11) NOT NULL,
  `examination_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `time_limit` int(11) NOT NULL COMMENT 'Time limit in minutes',
  `instructions` text DEFAULT NULL,
  `status` enum('assigned','in_progress','completed','expired','cancelled') DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `result` enum('pass','fail','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_audit_trail`
--

CREATE TABLE `exam_audit_trail` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `status_before` varchar(50) DEFAULT NULL,
  `status_after` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_audit_trail`
--

INSERT INTO `exam_audit_trail` (`id`, `exam_id`, `action`, `status_before`, `status_after`, `remarks`, `user_id`, `created_at`) VALUES
(1, 46, 'status_update', 'pending', 'approved', '', 0, '2025-12-22 04:32:35');

-- --------------------------------------------------------

--
-- Table structure for table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `question_type` enum('multiple','truefalse','shortanswer','identification') NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 1,
  `expected_answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_question_options`
--

CREATE TABLE `exam_question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `option_order` int(11) DEFAULT 0,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_repository`
--

CREATE TABLE `exam_repository` (
  `exam_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `original_exam_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `roles` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `passing_score` int(11) DEFAULT 70,
  `module_id` int(11) DEFAULT NULL,
  `module_title` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','hold','compliance','posted','cancelled') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_repository`
--

INSERT INTO `exam_repository` (`exam_id`, `id`, `original_exam_id`, `title`, `description`, `department`, `roles`, `duration`, `passing_score`, `module_id`, `module_title`, `status`, `remarks`, `created_by`, `approved_by`, `posted_by`, `posted_at`, `created_at`, `updated_at`) VALUES
(0, 2, 46, 'Labore laborum Amet', 'Eligendi voluptas ei', 'engineering', 'HVAC Technician', 60, 70, 24, NULL, 'posted', '', NULL, NULL, 1, '2025-12-22 12:42:49', '2025-12-22 04:32:35', '2025-12-22 04:42:49'),
(0, 3, 64, 'mark nathaniel evora', 'safsdaf', 'food-beverage', 'Waiter / Waitress / Server', 60, 70, 32, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-14 18:19:59', '2026-01-14 09:21:52', '2026-01-14 10:19:59'),
(0, 4, 39, 'Deleniti labore omni', 'Nulla mollitia harum', 'engineering', 'HVAC Technician', 60, 70, 24, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-14 19:38:17', '2025-12-09 14:50:04', '2026-01-14 11:38:17'),
(0, 5, 65, 'Dolores id accusamus', 'Aliquip asperiores e', 'engineering', 'HVAC Technician', 60, 70, 24, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-14 23:12:11', '2026-01-14 15:11:39', '2026-01-14 15:12:11'),
(0, 6, 66, 'Pano mag bukas ng electrican ', 'electricfan', 'housekeeping', 'Floor Supervisor', 60, 70, 35, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-15 15:27:41', '2026-01-14 18:15:38', '2026-01-15 07:27:41'),
(0, 7, 67, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 'Finance Manager / Controller', 60, 70, 45, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-16 02:18:13', '2026-01-15 17:10:19', '2026-01-15 18:18:13'),
(0, 8, 70, 'Advanced Culinary Leadership & Menu Strategy', 'Focuses on kitchen leadership, menu innovation, food cost control, and culinary quality assurance.', 'kitchen', 'Executive Chef / Head Chef', 60, 70, 42, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-16 02:17:07', '2026-01-15 17:29:52', '2026-01-15 18:17:07'),
(0, 9, 69, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 'HR Manager / Director', 60, 70, 44, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-16 02:17:30', '2026-01-15 17:20:01', '2026-01-15 18:17:30'),
(0, 10, 68, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 'HR Manager / Director', 60, 70, 44, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-16 02:18:01', '2026-01-15 17:19:43', '2026-01-15 18:18:01'),
(0, 11, 71, 'Hospitality Sales Strategy & Market Growth', 'Develops skills in sales planning, market analysis, client relations, and revenue growth strategies.', 'sales-marketing', 'Sales & Marketing Manager', 60, 70, 43, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-16 21:33:50', '2026-01-16 13:31:44', '2026-01-16 13:33:50'),
(0, 12, 72, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 'HR Manager / Director', 60, 70, 44, NULL, 'posted', NULL, NULL, NULL, 1, '2026-01-17 13:43:42', '2026-01-17 05:41:22', '2026-01-17 05:43:42'),
(0, 13, 77, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 'Finance Manager / Controller', 60, 70, 45, NULL, 'cancelled', NULL, NULL, NULL, NULL, NULL, '2026-01-22 00:57:12', '2026-01-22 01:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `exam_repository_assignments`
--

CREATE TABLE `exam_repository_assignments` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `audience` enum('applicant','employee') NOT NULL,
  `department` varchar(100) NOT NULL,
  `role` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `assigned_by` varchar(50) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_repository_assignments`
--

INSERT INTO `exam_repository_assignments` (`id`, `exam_id`, `audience`, `department`, `role`, `status`, `assigned_by`, `assigned_at`) VALUES
(1, 5, 'employee', 'engineering', 'HVAC Technician', 'active', '', '2026-01-14 20:44:51'),
(2, 5, 'applicant', 'engineering', 'Chief Engineer / Engineering Manager', 'active', '', '2026-01-14 20:45:50'),
(3, 5, 'applicant', 'engineering', 'Maintenance Technician', 'active', '', '2026-01-14 20:45:50'),
(4, 5, 'applicant', 'engineering', 'Electrician / Plumber', 'active', '', '2026-01-14 20:45:50'),
(5, 5, 'applicant', 'engineering', 'HVAC Technician', 'active', '', '2026-01-14 20:45:50'),
(6, 3, 'applicant', 'food-beverage', 'Waiter / Waitress / Server', 'active', '', '2026-01-14 20:48:05'),
(11, 6, 'applicant', 'housekeeping', 'Floor Supervisor', 'active', '', '2026-01-15 07:27:53'),
(12, 8, 'applicant', 'kitchen', 'Executive Chef / Head Chef', 'active', '', '2026-01-15 18:17:18'),
(14, 7, 'applicant', 'finance', 'Finance Manager / Controller', 'active', '', '2026-01-15 18:37:48'),
(15, 7, 'applicant', 'finance', 'Accountant', 'active', '', '2026-01-15 18:37:48'),
(16, 7, 'applicant', 'finance', 'Payroll Officer', 'active', '', '2026-01-15 18:37:48'),
(17, 7, 'applicant', 'finance', 'Cost Controller', 'active', '', '2026-01-15 18:37:48'),
(19, 11, 'applicant', 'sales-marketing', 'Sales & Marketing Manager', 'active', '', '2026-01-16 13:35:31'),
(20, 11, 'applicant', 'sales-marketing', 'Revenue Manager', 'active', '', '2026-01-16 13:35:31'),
(21, 11, 'applicant', 'sales-marketing', 'Event / Banquet Sales Coordinator', 'active', '', '2026-01-16 13:35:31'),
(22, 11, 'applicant', 'sales-marketing', 'Social Media / Marketing Executive', 'active', '', '2026-01-16 13:35:31'),
(23, 12, 'applicant', 'human-resources', 'HR Manager / Director', 'active', '', '2026-01-17 05:44:26'),
(24, 12, 'employee', 'human-resources', 'HR Manager / Director', 'active', '', '2026-01-17 05:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `exam_repository_questions`
--

CREATE TABLE `exam_repository_questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `question_type` enum('multiple','truefalse','shortanswer','identification') NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 1,
  `answer_key` text DEFAULT NULL,
  `options` text DEFAULT NULL,
  `expected_answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_repository_questions`
--

INSERT INTO `exam_repository_questions` (`id`, `exam_id`, `question_number`, `question_type`, `question_text`, `points`, `answer_key`, `options`, `expected_answer`) VALUES
(1, 3, 1, 'truefalse', 'xzc', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(2, 4, 1, 'truefalse', 'Quia non et duis cor', 1, '{\"correctAnswers\":[\"True\"],\"points\":1}', '[\"True\",\"False\"]', NULL),
(3, 4, 2, 'multiple', 'Aut sint eiusmod hi', 1, '{\"correctAnswers\":[\"rttttt\"],\"points\":1}', '[\"rttttt\",\"rrrrrrrrrrr\"]', NULL),
(4, 4, 3, 'multiple', '2. Learning Objectives', 1, '{\"correctAnswers\":[\"Demonstrate advanced knife skills and cooking techniques used in professional kitchens.\"],\"points\":1}', '[\"By the end of this module, the learner should be abl\",\"Demonstrate advanced knife skills and cooking techniques used in professional kitchens.\"]', NULL),
(5, 5, 1, 'multiple', 'Tama ba na mali ka', 1, '{\"points\":1,\"correctAnswers\":[\"oo\"],\"questionType\":\"multiple\"}', '[\"syempre naman sana kahapon pa\",\"oo\",\"siguro\",\"kanina\"]', ''),
(6, 5, 2, 'multiple', 'saan ka nag kulang?', 1, '{\"points\":1,\"correctAnswers\":[\"oras\",\"understanding\",\"attention\",\"aruga\"],\"questionType\":\"multiple\"}', '[\"oras\",\"understanding\",\"attention\",\"aruga\"]', ''),
(7, 5, 3, 'multiple', 'mahal mo pa?', 1, '{\"points\":1,\"correctAnswers\":[\"aray\"],\"questionType\":\"multiple\"}', '[\"hindi na\",\"oo \",\"pwede\",\"aray\"]', ''),
(8, 5, 4, 'multiple', 'umaasa ka pa ba?', 1, '{\"points\":1,\"correctAnswers\":[\"sakit mo\"],\"questionType\":\"multiple\"}', '[\"hindi na\",\"oo\",\"sakit mo\"]', ''),
(9, 5, 5, 'multiple', 'If infinite is an endless loop, bakit may igit ka sa tewup?', 1, '{\"points\":1,\"correctAnswers\":[\"bongbong marcos\"],\"questionType\":\"multiple\"}', '[\"yuck\",\"di ko napigilan\",\"23\",\"bongbong marcos\"]', ''),
(10, 5, 6, 'truefalse', 'masaya na ba siya sa iba?', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(11, 5, 7, 'truefalse', 'siya parin ba?', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(12, 5, 8, 'multiple', 'bakit?', 1, '{\"points\":1,\"correctAnswers\":[\"red ford white \",\"bong bong marcos\",\"many pacman paq U\",\"guzman\"],\"questionType\":\"multiple\"}', '[\"red ford white \",\"bong bong marcos\",\"many pacman paq U\",\"guzman\"]', ''),
(13, 5, 9, 'truefalse', '2+3=?', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(14, 5, 10, 'truefalse', 'kung ang airpalin ay lumilipad ano naman ang barko?', 1, '{\"points\":1,\"correctAnswers\":[\"False\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(15, 5, 11, 'multiple', 'penge', 1, '{\"points\":1,\"correctAnswers\":[\"mark\"],\"questionType\":\"multiple\"}', '[\"mark\",\"than\",\"james\",\"rain\"]', ''),
(16, 5, 12, 'multiple', 'bampira', 1, '{\"points\":1,\"correctAnswers\":[\"than\"],\"questionType\":\"multiple\"}', '[\"than\",\"mark\",\"tay\",\"sir jam\"]', ''),
(17, 5, 13, 'multiple', 'masarap mag luto', 1, '{\"points\":1,\"correctAnswers\":[\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\"],\"questionType\":\"multiple\"}', '[\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\"]', ''),
(18, 5, 14, 'multiple', 'ano masarap pag madaling araw?', 1, '{\"points\":1,\"correctAnswers\":[\"double dutch\"],\"questionType\":\"multiple\"}', '[\"double dutch\",\"leadership\",\"Clear verbal communication\",\"Adjusting \"]', ''),
(19, 5, 15, 'multiple', 'making my way down town...', 1, '{\"points\":1,\"correctAnswers\":[\"walking fast\"],\"questionType\":\"multiple\"}', '[\"Organizing \",\"Identifying \",\"walking fast\",\"contamination \"]', ''),
(20, 6, 1, 'multiple', 'ay isa sa pinakamahalagang imbensyon ng sangkatauhan, lalo na sa Pilipinas kung saan ang init ay parang libreng trial ng impyerno. ', 1, '{\"points\":1,\"correctAnswers\":[\"electric fan\"],\"questionType\":\"multiple\"}', '[\"electric fan\",\"refrigirator\",\"washing machine\",\"aircon\"]', ''),
(21, 6, 2, 'multiple', 'mas maganda kung gumagana', 1, '{\"points\":1,\"correctAnswers\":[\"Saksakan ng kuryente\"],\"questionType\":\"multiple\"}', '[\"Saksakan ng kuryente\",\"electric fan\",\"Daliri ng tao\",\"Isang taong naiinitan\"]', ''),
(22, 6, 3, 'multiple', 'Dahan-dahang isaksak ang plug. Kapag may spark, huwag kabahan—konting dasal lang at ituloy ang eksperimento.', 1, '{\"points\":1,\"correctAnswers\":[\"Isaksak ang plug sa saksakan\"],\"questionType\":\"multiple\"}', '[\"Hanapin ang electric fan\",\"Isaksak ang plug sa saksakan\",\"Pindutin ang “ON” o Number 1\",\"Ayusin ang direksyon ng hangin\"]', ''),
(23, 6, 4, 'multiple', ' ito ay mahalaga sa pang-araw-araw na buhay, lalo na tuwing tanghali at kapag may brownout anxiety.', 1, '{\"points\":1,\"correctAnswers\":[\"electric fan\"],\"questionType\":\"multiple\"}', '[\"electric fan\",\"fan electric\"]', ''),
(24, 6, 5, 'truefalse', 'Ang electric fan ay isa sa pinakamahalagang imbensyon ng sangkatauhan, lalo na sa Pilipinas kung saan ang init ay parang libreng trial ng impyerno. ', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(27, 7, 1, 'truefalse', 'This executive-level module is designed for financial leaders in the hospitality industry, focusing on the strategic oversight and control of financial resources', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(28, 7, 2, 'multiple', 'Upholding the highest standards of financial integrity and transparency.', 1, '{\"points\":1,\"correctAnswers\":[\"Ethics\"],\"questionType\":\"multiple\"}', '[\"Ethics\",\"Governance \",\" Fiduciary Responsibility\",\"Technology \"]', ''),
(29, 7, 3, 'multiple', 'Selecting and visualizing the 10-15 most critical metrics for executive decision-making.', 1, '{\"points\":1,\"correctAnswers\":[\" Creating a Management KPI Dashboard\"],\"questionType\":\"multiple\"}', '[\" Creating a Management KPI Dashboard\",\"Cash Flow Management & Forecasting\",\" Ownership & Lender Reporting\"]', ''),
(30, 7, 4, 'multiple', 'Ownership & Lender Reporting', 1, '{\"points\":1,\"correctAnswers\":[\" Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).\"],\"questionType\":\"multiple\"}', '[\" Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).\",\" Cash Flow Management & Forecasting\",\"Creating a Management KPI Dashboard\",\"Key Financial Ratios & Benchmarking\"]', ''),
(31, 7, 5, 'multiple', 'Principles of COSO (Committee of Sponsoring Organizations).', 1, '{\"points\":1,\"correctAnswers\":[\"Designing an Effective Internal Control Framework\"],\"questionType\":\"multiple\"}', '[\"Designing an Effective Internal Control Framework\",\" Control Activities for Key Hospitality Processe\",\"Managing the Audit Process\",\"Compliance & Regulatory Reporting\"]', ''),
(34, 8, 1, 'truefalse', 'Articulate and implement a culinary vision and leadership philosophy that inspires a team, drives culture, and aligns with business goals.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(35, 8, 2, 'truefalse', 'This high-level module equips experienced culinary leaders with the strategic mindset and practical tools to lead a profitable, innovative, and excellence-driven kitchen.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(36, 8, 3, 'multiple', 'Labor Cost Optimization in the Kitchen', 1, '{\"points\":1,\"correctAnswers\":[\"Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.\"],\"questionType\":\"multiple\"}', '[\"Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.\",\"Developing detailed production guides with photos, weights, and plating specs.\",\"The Quality Line Check: Implementing a pre-service tasting and inspection ritual for every station.\",\" Setting and auditing supplier specifications, conducting facility visits, and building partnerships for quality.\"]', ''),
(37, 8, 4, 'multiple', 'Sustainable & Ethical Sourcing Strategy', 1, '{\"points\":1,\"correctAnswers\":[\"Building a program that is both principled and practical. Communicating your story.\"],\"questionType\":\"multiple\"}', '[\"Building a program that is both principled and practical. Communicating your story.\",\"Balancing innovation with core offerings.\",\"Evaluating and implementing tech for inventory, ordering, recipe costing, and waste tracking.\"]', ''),
(38, 8, 5, 'multiple', 'Develop a comprehensive business plan for a new restaurant concept or a major repositioning of an existing one.', 1, '{\"points\":1,\"correctAnswers\":[\" The Challenge\"],\"questionType\":\"multiple\"}', '[\" The Challenge\",\"Culinary Leadership Labs\",\"Financial Modeling Workshops\",\"Case Studies\"]', ''),
(41, 9, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(42, 9, 2, 'multiple', ' The Hospitality Labor Landscape: ', 1, '{\"points\":1,\"correctAnswers\":[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\"],\"questionType\":\"multiple\"}', '[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Evaluating and implementing HRIS, talent analytics platforms, and AI tools for strategic advantage.\",\"Aligning staffing models with business cycles and growth plans.\"]', ''),
(43, 9, 3, 'multiple', 'Aligning staffing models with business cycles and growth plans.', 1, '{\"points\":1,\"correctAnswers\":[\"Demand Forecasting\"],\"questionType\":\"multiple\"}', '[\"Demand Forecasting\",\" Strategic Talent Acquisition & Employer Branding\",\"Succession Planning & Leadership Development\",\"Strategic Learning & Development\"]', ''),
(44, 9, 4, 'multiple', 'Talent Mobility & Retention Strategie', 1, '{\"points\":1,\"correctAnswers\":[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\"],\"questionType\":\"multiple\"}', '[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\",\"Moving from annual reviews to continuous feedback, coaching, and development-focused conversations.\"]', ''),
(45, 9, 5, 'multiple', 'Advanced techniques for handling sensitive complaints (harassment, discrimination, theft).', 1, '{\"points\":1,\"correctAnswers\":[\"Leading Complex Investigations & Dispute Resolution\"],\"questionType\":\"multiple\"}', '[\"Leading Complex Investigations & Dispute Resolution\",\" Labor Relations Strategy (Union & Non-Union)\",\" Designing & Governing HR Policies\"]', ''),
(48, 10, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(49, 10, 2, 'multiple', ' The Hospitality Labor Landscape: ', 1, '{\"points\":1,\"correctAnswers\":[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\"],\"questionType\":\"multiple\"}', '[\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.\",\"Evaluating and implementing HRIS, talent analytics platforms, and AI tools for strategic advantage.\",\"Aligning staffing models with business cycles and growth plans.\"]', ''),
(50, 10, 3, 'multiple', 'Aligning staffing models with business cycles and growth plans.', 1, '{\"points\":1,\"correctAnswers\":[\"Demand Forecasting\"],\"questionType\":\"multiple\"}', '[\"Demand Forecasting\",\" Strategic Talent Acquisition & Employer Branding\",\"Succession Planning & Leadership Development\",\"Strategic Learning & Development\"]', ''),
(51, 10, 4, 'multiple', 'Talent Mobility & Retention Strategie', 1, '{\"points\":1,\"correctAnswers\":[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\"],\"questionType\":\"multiple\"}', '[\"Designing career paths, cross-training programs, and retention initiatives for high-potential employees.\",\"Moving from annual reviews to continuous feedback, coaching, and development-focused conversations.\"]', ''),
(52, 10, 5, 'multiple', 'Advanced techniques for handling sensitive complaints (harassment, discrimination, theft).', 1, '{\"points\":1,\"correctAnswers\":[\"Leading Complex Investigations & Dispute Resolution\"],\"questionType\":\"multiple\"}', '[\"Leading Complex Investigations & Dispute Resolution\",\" Labor Relations Strategy (Union & Non-Union)\",\" Designing & Governing HR Policies\"]', ''),
(55, 11, 1, 'truefalse', 'This advanced strategic module equips hospitality leaders with the frameworks and skills to drive sustainable market growth through analytical sales planning and strategic client management.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(56, 11, 2, 'multiple', 'Motivating, coaching, and managing a metrics-driven sales culture.', 1, '{\"points\":1,\"correctAnswers\":[\"Leading a High-Performance Sales Team\"],\"questionType\":\"multiple\"}', '[\"Leading a High-Performance Sales Team\",\"Sales Technology Stack Strategy\"]', ''),
(57, 11, 3, 'truefalse', 'Identifying direct & indirect competitors.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(58, 11, 4, 'truefalse', 'Unit 3: Strategic Client Relationship & Account Management (8 hours)', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(59, 11, 5, 'truefalse', '4.3 Digital Lead Generation & Social Selling: Using LinkedIn, industry forums, and content marketing to build pipeline.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(62, 12, 1, 'truefalse', 'This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry.', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', ''),
(63, 13, 1, 'truefalse', 'Financial Leadership & Control Systems', 1, '{\"points\":1,\"correctAnswers\":[\"True\"],\"questionType\":\"truefalse\"}', '[\"True\",\"False\"]', '');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `time_taken` int(11) NOT NULL COMMENT 'Time taken in seconds',
  `completed_at` datetime NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `taker_type` enum('applicant','employee') DEFAULT 'employee',
  `taker_department` varchar(100) DEFAULT NULL,
  `taker_role` varchar(255) DEFAULT NULL,
  `taker_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `employee_id`, `exam_id`, `score`, `total_questions`, `passed`, `time_taken`, `completed_at`, `attempt_number`, `created_at`, `taker_type`, `taker_department`, `taker_role`, `taker_name`) VALUES
(19, 'unknown', 8, 100.00, 5, 1, 14, '2026-01-16 02:19:44', 1, '2026-01-15 18:19:44', 'applicant', 'kitchen', 'Executive Chef / Head Chef', 'Xyrus Abano'),
(20, 'unknown', 7, 40.00, 5, 0, 7, '2026-01-16 02:38:13', 1, '2026-01-15 18:38:13', 'applicant', 'finance', 'Finance Manager / Controller', 'Jerson Bero'),
(21, 'unknown', 7, 100.00, 5, 1, 11, '2026-01-16 12:42:33', 2, '2026-01-16 04:42:33', 'applicant', 'finance', 'Finance Manager / Controller', 'Princess Fenella Cruz'),
(22, 'unknown', 7, 60.00, 5, 0, 9, '2026-01-16 12:46:27', 3, '2026-01-16 04:46:27', 'applicant', 'finance', 'Finance Manager / Controller', 'Mark Louie Diaz Bade'),
(23, 'unknown', 8, 100.00, 5, 1, 8, '2026-01-16 13:40:54', 2, '2026-01-16 05:40:54', 'applicant', 'kitchen', 'Executive Chef / Head Chef', 'Matthew Louis Diaz Bade'),
(24, 'unknown', 11, 100.00, 5, 1, 20, '2026-01-16 21:37:31', 1, '2026-01-16 13:37:31', 'applicant', 'sales-marketing', 'Sales & Marketing Manager', 'Berson Jero'),
(25, 'unknown', 5, 46.67, 15, 0, 9, '2026-01-17 23:50:31', 1, '2026-01-17 15:50:31', 'applicant', 'engineering', 'Electrician / Plumber', 'Berson Jero'),
(26, 'unknown', 11, 100.00, 5, 1, 12, '2026-01-23 16:43:21', 2, '2026-01-23 08:43:21', 'applicant', 'sales-marketing', 'Sales & Marketing Manager', 'ulan'),
(27, 'unknown', 12, 100.00, 1, 1, 25, '2026-01-24 13:00:54', 1, '2026-01-24 05:00:54', 'applicant', 'human-resources', 'HR Manager / Director', 'Berson Jero'),
(28, 'unknown', 12, 100.00, 1, 1, 22, '2026-01-24 13:01:16', 2, '2026-01-24 05:01:16', 'applicant', 'human-resources', 'HR Manager / Director', 'Berson Jero'),
(29, 'M251002', 12, 100.00, 1, 1, 545, '2026-01-24 17:53:44', 1, '2026-01-24 09:53:44', 'applicant', 'human-resources', 'HR Manager / Director', 'Berson Jero'),
(30, 'M251002', 11, 20.00, 5, 0, 614, '2026-01-24 18:22:26', 1, '2026-01-24 10:22:26', 'applicant', 'sales-marketing', 'Sales & Marketing Manager', 'mark'),
(31, 'M251002', 11, 0.00, 5, 0, 12, '2026-01-24 18:23:11', 2, '2026-01-24 10:23:11', 'applicant', 'sales-marketing', 'Sales & Marketing Manager', 'MATT');

-- --------------------------------------------------------

--
-- Table structure for table `exam_result_answers`
--

CREATE TABLE `exam_result_answers` (
  `id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question_number` int(11) DEFAULT NULL,
  `question_type` varchar(50) DEFAULT NULL,
  `user_answer` text DEFAULT NULL,
  `correct_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_possible` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_result_answers`
--

INSERT INTO `exam_result_answers` (`id`, `result_id`, `question_id`, `question_number`, `question_type`, `user_answer`, `correct_answer`, `is_correct`, `points_possible`, `points_earned`, `created_at`) VALUES
(111, 19, 34, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-15 18:19:44'),
(112, 19, 35, 2, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-15 18:19:44'),
(113, 19, 36, 3, 'multiple', 'Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.', 'Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.', 1, 1, 1, '2026-01-15 18:19:44'),
(114, 19, 37, 4, 'multiple', 'Building a program that is both principled and practical. Communicating your story.', 'Building a program that is both principled and practical. Communicating your story.', 1, 1, 1, '2026-01-15 18:19:44'),
(115, 19, 38, 5, 'multiple', 'The Challenge', 'The Challenge', 1, 1, 1, '2026-01-15 18:19:44'),
(116, 20, 27, 1, 'truefalse', 'False', 'True', 0, 1, 0, '2026-01-15 18:38:13'),
(117, 20, 28, 2, 'multiple', 'Fiduciary Responsibility', 'Ethics', 0, 1, 0, '2026-01-15 18:38:13'),
(118, 20, 29, 3, 'multiple', 'Creating a Management KPI Dashboard', 'Creating a Management KPI Dashboard', 1, 1, 1, '2026-01-15 18:38:13'),
(119, 20, 30, 4, 'multiple', 'Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).', 'Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).', 1, 1, 1, '2026-01-15 18:38:13'),
(120, 20, 31, 5, 'multiple', 'Managing the Audit Process', 'Designing an Effective Internal Control Framework', 0, 1, 0, '2026-01-15 18:38:13'),
(121, 21, 27, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 04:42:33'),
(122, 21, 28, 2, 'multiple', 'Ethics', 'Ethics', 1, 1, 1, '2026-01-16 04:42:33'),
(123, 21, 29, 3, 'multiple', 'Creating a Management KPI Dashboard', 'Creating a Management KPI Dashboard', 1, 1, 1, '2026-01-16 04:42:33'),
(124, 21, 30, 4, 'multiple', 'Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).', 'Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).', 1, 1, 1, '2026-01-16 04:42:33'),
(125, 21, 31, 5, 'multiple', 'Designing an Effective Internal Control Framework', 'Designing an Effective Internal Control Framework', 1, 1, 1, '2026-01-16 04:42:33'),
(126, 22, 27, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 04:46:27'),
(127, 22, 28, 2, 'multiple', 'Governance', 'Ethics', 0, 1, 0, '2026-01-16 04:46:27'),
(128, 22, 29, 3, 'multiple', 'Creating a Management KPI Dashboard', 'Creating a Management KPI Dashboard', 1, 1, 1, '2026-01-16 04:46:27'),
(129, 22, 30, 4, 'multiple', 'Cash Flow Management & Forecasting', 'Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).', 0, 1, 0, '2026-01-16 04:46:27'),
(130, 22, 31, 5, 'multiple', 'Designing an Effective Internal Control Framework', 'Designing an Effective Internal Control Framework', 1, 1, 1, '2026-01-16 04:46:27'),
(131, 23, 34, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 05:40:54'),
(132, 23, 35, 2, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 05:40:54'),
(133, 23, 36, 3, 'multiple', 'Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.', 'Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.', 1, 1, 1, '2026-01-16 05:40:54'),
(134, 23, 37, 4, 'multiple', 'Building a program that is both principled and practical. Communicating your story.', 'Building a program that is both principled and practical. Communicating your story.', 1, 1, 1, '2026-01-16 05:40:54'),
(135, 23, 38, 5, 'multiple', 'The Challenge', 'The Challenge', 1, 1, 1, '2026-01-16 05:40:54'),
(136, 24, 55, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 13:37:31'),
(137, 24, 56, 2, 'multiple', 'Leading a High-Performance Sales Team', 'Leading a High-Performance Sales Team', 1, 1, 1, '2026-01-16 13:37:31'),
(138, 24, 57, 3, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 13:37:31'),
(139, 24, 58, 4, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 13:37:31'),
(140, 24, 59, 5, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-16 13:37:31'),
(141, 25, 5, 1, 'multiple', 'oo', 'oo', 1, 1, 1, '2026-01-17 15:50:31'),
(142, 25, 6, 2, 'multiple', 'understanding', '[\"oras\",\"understanding\",\"attention\",\"aruga\"]', 1, 1, 1, '2026-01-17 15:50:31'),
(143, 25, 7, 3, 'multiple', 'aray', 'aray', 1, 1, 1, '2026-01-17 15:50:31'),
(144, 25, 8, 4, 'multiple', 'oo', 'sakit mo', 0, 1, 0, '2026-01-17 15:50:31'),
(145, 25, 9, 5, 'multiple', 'di ko napigilan', 'bongbong marcos', 0, 1, 0, '2026-01-17 15:50:31'),
(146, 25, 10, 6, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-17 15:50:31'),
(147, 25, 11, 7, 'truefalse', 'False', 'True', 0, 1, 0, '2026-01-17 15:50:31'),
(148, 25, 12, 8, 'multiple', 'guzman', '[\"red ford white\",\"bong bong marcos\",\"many pacman paq U\",\"guzman\"]', 1, 1, 1, '2026-01-17 15:50:31'),
(149, 25, 13, 9, 'truefalse', 'False', 'True', 0, 1, 0, '2026-01-17 15:50:31'),
(150, 25, 14, 10, 'truefalse', 'True', 'False', 0, 1, 0, '2026-01-17 15:50:31'),
(151, 25, 15, 11, 'multiple', 'rain', 'mark', 0, 1, 0, '2026-01-17 15:50:31'),
(152, 25, 16, 12, 'multiple', 'tay', 'than', 0, 1, 0, '2026-01-17 15:50:31'),
(153, 25, 17, 13, 'multiple', 'ma\'am rose', '[\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\",\"ma\'am rose\"]', 1, 1, 1, '2026-01-17 15:50:31'),
(154, 25, 18, 14, 'multiple', 'double dutch', 'double dutch', 1, 1, 1, '2026-01-17 15:50:31'),
(155, 25, 19, 15, 'multiple', 'contamination', 'walking fast', 0, 1, 0, '2026-01-17 15:50:31'),
(156, 26, 55, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-23 08:43:21'),
(157, 26, 56, 2, 'multiple', 'Leading a High-Performance Sales Team', 'Leading a High-Performance Sales Team', 1, 1, 1, '2026-01-23 08:43:21'),
(158, 26, 57, 3, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-23 08:43:21'),
(159, 26, 58, 4, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-23 08:43:21'),
(160, 26, 59, 5, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-23 08:43:21'),
(161, 27, 62, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-24 05:00:54'),
(162, 28, 62, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-24 05:01:16'),
(163, 29, 62, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-24 09:53:44'),
(164, 30, 55, 1, 'truefalse', 'True', 'True', 1, 1, 1, '2026-01-24 10:22:26'),
(165, 30, 56, 2, 'multiple', '', 'Leading a High-Performance Sales Team', 0, 1, 0, '2026-01-24 10:22:26'),
(166, 30, 57, 3, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:22:26'),
(167, 30, 58, 4, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:22:26'),
(168, 30, 59, 5, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:22:26'),
(169, 31, 55, 1, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:23:11'),
(170, 31, 56, 2, 'multiple', '', 'Leading a High-Performance Sales Team', 0, 1, 0, '2026-01-24 10:23:11'),
(171, 31, 57, 3, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:23:11'),
(172, 31, 58, 4, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:23:11'),
(173, 31, 59, 5, 'truefalse', '', 'True', 0, 1, 0, '2026-01-24 10:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `exam_reviews`
--

CREATE TABLE `exam_reviews` (
  `id` int(11) NOT NULL,
  `examination_id` int(11) NOT NULL,
  `reviewed_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','compliance') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_review_logs`
--

CREATE TABLE `exam_review_logs` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `reviewer_name` varchar(100) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_review_logs`
--

INSERT INTO `exam_review_logs` (`id`, `exam_id`, `action`, `remarks`, `reviewer_name`, `reviewer_id`, `created_at`) VALUES
(1, 43, 'rejected', 'sdfg', 'System Administrator', 1, '2025-12-14 14:11:01'),
(2, 44, 'approved', '', 'System Administrator', 1, '2025-12-14 14:43:14'),
(3, 42, 'approved', '', 'System Administrator', 1, '2025-12-14 15:10:10'),
(4, 41, 'approved', '', 'System Administrator', 1, '2025-12-14 15:18:45'),
(5, 39, 'approved', '', 'System Administrator', 1, '2025-12-14 15:30:18'),
(6, 45, 'approved', '', 'System Administrator', 1, '2025-12-22 02:22:39');

-- --------------------------------------------------------

--
-- Table structure for table `exam_violation_logs`
--

CREATE TABLE `exam_violation_logs` (
  `id` int(11) NOT NULL,
  `taker_id` varchar(50) DEFAULT NULL,
  `taker_type` enum('applicant','employee') NOT NULL DEFAULT 'applicant',
  `exam_id` int(11) NOT NULL,
  `violation_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_violation_logs`
--

INSERT INTO `exam_violation_logs` (`id`, `taker_id`, `taker_type`, `exam_id`, `violation_type`, `details`, `user_agent`, `ip_address`, `created_at`) VALUES
(1, NULL, 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:36'),
(2, NULL, 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:36'),
(3, NULL, 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:36'),
(4, NULL, 'applicant', 12, 'exit_fullscreen', 'Fullscreen exited', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:40'),
(5, NULL, 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:54'),
(6, NULL, 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:00:54'),
(7, NULL, 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '::1', '2026-01-24 05:01:16'),
(8, 'M251002', 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:44:55'),
(9, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:44:55'),
(10, 'M251002', 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:04'),
(11, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:04'),
(12, 'M251002', 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:10'),
(13, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:10'),
(14, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:17'),
(15, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:19'),
(16, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:21'),
(17, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:22'),
(18, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:29'),
(19, 'M251002', 'applicant', 12, 'select', 'Selection blocked', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:34'),
(20, 'M251002', 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:52'),
(21, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:45:53'),
(22, 'M251002', 'applicant', 12, 'blur', 'Window lost focus', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:49:01'),
(23, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:49:02'),
(24, 'M251002', 'applicant', 12, 'visibility', 'Tab hidden / switched', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 09:53:44'),
(25, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:12:01'),
(26, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:12:07'),
(27, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:12:28'),
(28, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:13:35'),
(29, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:14:14'),
(30, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:22:26'),
(31, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:22:26'),
(32, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:22:27'),
(33, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:22:27'),
(34, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:04'),
(35, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:05'),
(36, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:06'),
(37, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:06'),
(38, 'M251002', 'applicant', 11, 'copy', 'Attempted to copy selected text', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:11'),
(39, 'M251002', 'applicant', 11, 'app_switch', 'Attempted to switch apps/tabs', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '127.0.0.1', '2026-01-24 10:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `idp_learning_module_assignments`
--

CREATE TABLE `idp_learning_module_assignments` (
  `id` int(11) NOT NULL,
  `idp_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `module_id` int(11) NOT NULL,
  `assigned_by` varchar(50) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_modules`
--

CREATE TABLE `learning_modules` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `department` enum('front-office','housekeeping','food-beverage','kitchen','sales-marketing','hr','finance','engineering','security') NOT NULL,
  `roles` varchar(100) NOT NULL,
  `content` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','compliance','hold','posted','draft') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `compliance_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_modules`
--

INSERT INTO `learning_modules` (`id`, `title`, `topic`, `department`, `roles`, `content`, `file_path`, `file_type`, `file_size`, `status`, `remarks`, `rejection_reason`, `compliance_reason`, `created_by`, `approved_by`, `created_at`, `updated_at`, `module_id`) VALUES
(36, 'Front Office Leadership & Service Excellence', 'Develops leadership, service quality management, complaint resolution, KPI monitoring, and front office operations control to ensure consistent guest satisfaction and team performance.', 'front-office', 'Front Desk Manager', '\r\n                        <p><div style=\"text-align: center;\"><b style=\"font-size: 11pt;\"><font size=\"7\"><span style=\"font-size: 14pt;\"><i>Front Office Leadership &amp; Service Excellence</i></span></font></b></div><b>Module Description</b></p><p>This module equips participants with the essential leadership skills and operational knowledge required to manage a modern hotel front office. It focuses on cultivating a culture of service excellence, managing team performance, resolving guest complaints effectively, monitoring Key Performance Indicators (KPIs), and controlling daily front office operations to ensure consistent guest satisfaction and operational efficiency.</p><p><b>Learning Outcomes</b></p><p>Upon successful completion of this module, participants will be able to:</p><p>Apply leadership principles to motivate, coach, and develop a high-performing front office team.</p><p>Design and implement strategies to deliver and consistently measure exceptional guest service.</p><p>Employ structured techniques to handle guest complaints and turn negative experiences into opportunities for loyalty.</p><p>Analyze key front office KPIs to assess performance, identify trends, and make data-driven decisions.</p><p>Oversee and control all critical front office operational cycles, including arrival, stay, and departure.</p><p>Module Content</p><p>Unit 1: The Front Office Leader (8 hours)</p><p>1.1 Leadership vs. Management: Defining your role as a leader.</p><p>1.2 Leadership Styles &amp; Emotional Intelligence: Adapting your style to different situations and team members.</p><p>1.3 Team Building &amp; Motivation: Creating a positive, collaborative, and goal-oriented team culture.</p><p>1.4 Effective Communication &amp; Briefings: Conducting impactful shift briefings and departmental meetings.</p><p>1.5 Coaching, Mentoring, &amp; Performance Feedback: Techniques for developing team member skills and conducting constructive reviews.</p><p>1.6 Conflict Resolution within the Team: Managing interpersonal conflicts professionally.</p><p>Unit 2: Managing Service Quality &amp; Excellence (8 hours)</p><p>2.1 Defining Service Excellence in the Front Office: Setting service standards (e.g., 10/5 rule, personalized service).</p><p>2.2 The Service Recovery Paradox: Understanding the value of effective complaint handling.</p><p>2.3 Creating Memorable Guest Experiences: From transactional to experiential service.</p><p>2.4 Empowering Your Team: Granting authority to resolve issues within defined boundaries.</p><p>2.5 Quality Assurance Tools: Using checklists, mystery shopper reports, and guest comment analysis.</p><p>Unit 3: Mastering Complaint Resolution &amp; Guest Recovery (6 hours)</p><p>3.1 The Guest Complaint Mindset: Viewing complaints as feedback and opportunity.</p><p>3.2 The LAERC Model (Listen, Apologize, Empathize, React, Compensate): A structured approach to handling complaints.</p><p>3.3 De-escalation Techniques: Managing emotional guests with professionalism.</p><p>3.4 Appropriate Compensation Strategies: From apologies to upgrades, refunds, and amenities.</p><p>3.5 Documentation &amp; Follow-Up: Logging incidents and ensuring closure to prevent recurrence.</p><p>Unit 4: KPI Monitoring &amp; Data-Driven Decision Making (6 hours)</p><p>4.1 Introduction to Front Office KPIs: Understanding their purpose and impact on revenue and satisfaction.</p><p>4.2 Key Operational KPIs: Analyzing Occupancy %, ADR (Average Daily Rate), RevPAR (Revenue Per Available Room).</p><p>4.3 Key Service &amp; Efficiency KPIs: Tracking Check-in/Check-out time, Up-sell success rate, Guest Satisfaction Score (GSS/NPS).</p><p>4.4 Daily, Weekly, and Monthly Reporting: Understanding reports from the PMS (Property Management System).</p><p>4.5 Using Data to Improve: Identifying performance gaps and implementing corrective actions.</p><p>Unit 5: Front Office Operations Control (8 hours)</p><p>5.1 Managing the Guest Cycle: Pre-arrival, arrival, occupancy, departure.</p><p>5.2 Room Inventory &amp; Rate Management: Understanding yield management basics and managing walk-ins/overbookings.</p><p>5.3 Cashiering &amp; Financial Control: Handling floats, petty cash, billing inquiries, and audit procedures.</p><p>5.4 Inter-Departmental Coordination: Seamless communication with Housekeeping, Maintenance, F&amp;B, and Sales.</p><p>5.5 Technology in the Front Office: Leveraging the PMS, CRM, and channel manager effectively.</p><p>5.6 Safety, Security, &amp; Emergency Procedures: Role in ensuring guest and employee safety.</p><p>Unit 6: Capstone Project &amp; Synthesis (4 hours)</p><p>6.1 Scenario-Based Simulations: Leading a team through a complex check-in crisis, handling a major complaint, analyzing a poor KPI report.</p><p>6.2 Development of an Action Plan: Participants create a 30-day \"Service Excellence &amp; Performance\" plan for a hypothetical front office.</p><p>6.3 Final Assessment &amp; Peer Feedback.</p><p>Teaching and Learning Methodology</p><p>Interactive Lectures: For theoretical concepts.</p><p>Case Studies: Real-world scenarios from the hospitality industry.</p><p>Role-Playing Exercises: Particularly for complaint handling and coaching conversations.</p><p>Group Workshops: To analyze KPI data and develop team strategies.</p><p>Software Demonstration: Hands-on with PMS reporting dashboards (if available).</p><p>Guest Speakers: Experienced Front Office or Rooms Division Managers.</p><p>Assessment Strategy</p><p>Participation &amp; In-Class Exercises: 20%</p><p>Written Assignment (Complaint Analysis &amp; Recovery Plan): 30%</p><p>KPI Analysis &amp; Presentation: 25%</p><p>Final Capstone Project &amp; Action Plan: 25%</p><p>Recommended Resources</p><p>Text: Check-In Check-Out: Managing Hotel Operations by Gary K. Vallen &amp; Jerome J. Vallen.</p><p>Text: The Heart of Hospitality: Great Hotel and Restaurant Leaders Share Their Secrets by Micah Solomon.</p><p>Industry Reports: STR reports on hotel performance metrics.</p><p>Software: Materials from major PMS providers (Opera, Cloudbeds, etc.).</p>                    ', NULL, NULL, NULL, 'approved', '', NULL, NULL, NULL, NULL, '2026-01-15 14:40:02', '2026-01-15 16:51:52', NULL),
(37, 'Professional Front Desk Operations', 'Covers check-in/check-out procedures, guest communication etiquette, system usage (PMS), billing basics, and service recovery skills.', 'front-office', 'Receptionist / Front Desk Officer', '\r\n                        <p><b><font size=\"7\">Professional Front Desk Operations</font></b></p><div><br></div><p><b>Module Description</b></p><p>This foundational module provides comprehensive training in the core technical and soft skills required for professional front desk operations. Participants will master standard check-in/check-out procedures, learn effective guest communication and telephone etiquette, gain proficiency in Property Management System (PMS) usage, understand basic billing and cashiering functions, and develop essential service recovery skills to handle routine guest issues confidently.</p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p>Execute efficient and welcoming arrival, registration, and departure procedures.</p><p>Communicate with guests professionally using appropriate verbal, non-verbal, and written etiquette in person and via telephone.</p><p>Navigate core functions of a standard PMS to manage guest profiles, reservations, and room status.</p><p>Process payments, explain charges, handle cash, and complete basic end-of-shift reports.</p><p>Apply a structured service recovery framework to resolve common guest complaints and service failures.</p><p>Module Content</p><p>Unit 1: Mastering the Check-In &amp; Arrival Experience (8 hours)</p><p>1.1 The First Impression: The 90-Second Rule – greeting, appearance, and body language.</p><p>1.2 Pre-Arrival Preparation: Reviewing arrival reports, preparing registration cards/key packets, and blocking special requests.</p><p>1.3 The Registration Process:</p><p>Verification of ID and reservation.</p><p>Complete and accurate data entry into PMS.</p><p>Securing method of payment and obtaining signatures.</p><p>Room assignment and upgrade techniques.</p><p>1.4 Welcome &amp; Orientation: Presenting keys, explaining hotel amenities, providing directions.</p><p>1.5 Special Situations: Handling walk-ins, guests without reservations, VIP arrivals, and group check-ins.</p><p>Unit 2: Perfecting the Check-Out &amp; Departure Process (6 hours)</p><p>2.1 Pre-Departure Preparation: Reviewing folios the night before and preparing invoices.</p><p>2.2 Efficient Check-Out Procedures: In-person, express, and video check-out options.</p><p>2.3 Folio Presentation &amp; Explanation: Reviewing charges clearly, resolving minor billing queries.</p><p>2.4 Payment Processing: Accepting cash, processing credit card authorizations, handling direct bill settlements.</p><p>2.5 Farewell &amp; Invitation to Return: The lasting impression. Inquiring about the stay and future reservation assistance.</p><p>Unit 3: Professional Guest Communication &amp; Etiquette (6 hours)</p><p>3.1 The Art of Verbal Communication: Tone, pace, clarity, and using the guest\'s name.</p><p>2.2 Non-Verbal Communication: Eye contact, smiling, posture, and professional demeanor.</p><p>3.3 Telephone Etiquette &amp; Call Handling:</p><p>Answering standards (greeting, department, name, offer of assistance).</p><p>Placing calls on hold, transferring, and taking messages.</p><p>Handling multiple lines and internal calls.</p><p>3.4 Written Communication: Professional email structure, internal log entries, and guest note-taking.</p><p>3.5 Cross-Cultural Awareness: Basic principles for interacting with international guests.</p><p>Unit 4: Property Management System (PMS) Proficiency (8 hours)</p><p>4.1 Introduction to PMS: Understanding its role as the operational hub.</p><p>4.2 Core Navigation: Logging in, understanding the main dashboard, and basic menu structure.</p><p>4.3 Reservation Management: Searching, retrieving, modifying, and creating reservations.</p><p>4.4 Front Desk Module: Performing check-in/check-out, posting charges, making comments, and changing room status (clean/dirty/out-of-order).</p><p>4.5 Guest Profile Management: Creating and updating guest history profiles.</p><p>4.6 Basic Reporting: Printing daily reports (arrival, departure, in-house).</p><p>Unit 5: Billing Basics &amp; Cashiering (4 hours)</p><p>5.1 Understanding the Guest Folio: Charges (room, tax, F&amp;B, phone), credits, and allowances.</p><p>5.2 Posting Manual Charges &amp; Adjustments: Correct procedures and authorization requirements.</p><p>5.3 Payment Methods: Handling cash (foreign currency basics), credit/debit cards, vouchers, and company direct billing.</p><p>5.4 Shift Closing &amp; Cashier’s Report: Counting the bank, reconciling transactions, preparing drop envelope.</p><p>5.5 Fraud Prevention Basics: Checking card signatures, identifying suspicious currency.</p><p>Unit 6: Foundational Service Recovery Skills (4 hours)</p><p>6.1 The Mindset for Recovery: Every complaint is an opportunity.</p><p>6.2 The HEAT Model (Hear, Empathize, Apologize, Take Action):</p><p>Hear: Listen actively without interrupting.</p><p>Empathize: Show genuine understanding (\"I understand why that\'s frustrating\").</p><p>Apologize: Offer a sincere apology for the situation.</p><p>Take Action: Resolve within your empowered limits or escalate appropriately.</p><p>6.3 Common Front Desk Scenarios: Practice resolving issues like slow check-in, wrong room type, billing errors, and noise complaints.</p><p>6.4 Documentation: Logging incidents in the PMS or logbook for management follow-up.</p><p>Teaching and Learning Methodology</p><p>Demonstrations &amp; Step-by-Step Guides: For procedural tasks (PMS, check-in).</p><p>Drills &amp; Role-Plays: Intensive practice for check-in/out, phone calls, and service recovery scenarios.</p><p>Simulation Software: Use of a PMS training database for hands-on system practice.</p><p>Video Analysis: Reviewing examples of excellent and poor guest interactions.</p><p>Checklists &amp; Job Aids: Providing quick-reference tools for procedures.</p><p>Assessment Strategy</p><p>Practical Skills Demonstration (Check-in/Check-out Simulation): 40%</p><p>PMS Proficiency Test (Computer-Based): 25%</p><p>Written Exam (Procedures &amp; Etiquette): 20%</p><p>Role-Play Assessment (Service Recovery Scenario): 15%</p><p>Required Materials &amp; Tools</p><p>Training PMS Access (e.g., Opera Simulation, Cloudbeds Training Mode)</p><p>Registration Cards, Key Cards, Cashier\'s Bank (training currency)</p><p>Sample Folios, Arrival/Departure Lists</p><p>Telephones for call handling practice</p><p>Role-Play Scenario Cards</p>                    ', NULL, NULL, NULL, 'approved', '', NULL, NULL, NULL, NULL, '2026-01-15 16:30:17', '2026-01-17 05:42:58', NULL),
(38, 'Housekeeping Operations & Quality Control', 'Covers departmental leadership, scheduling, inventory control, hygiene standards, audits, and interdepartmental coordination.', 'housekeeping', 'Executive Housekeeper / Housekeeping Manager', '\r\n                        \r\n                        \r\n                        \r\n                        \r\n                        \r\n                        \r\n                        \r\n                        \r\n                        <p style=\"text-align: center;\"><b style=\"font-size: xxx-large;\">&nbsp;Housekeeping Operations &amp; Quality Control</b></p><p style=\"text-align: center;\"><br></p><p>Module Description</p><p>This comprehensive module provides the operational and managerial framework for running an efficient, quality-driven housekeeping department. It focuses on developing leadership skills specific to housekeeping teams, mastering scheduling and payroll control, implementing inventory and supply chain systems, enforcing strict hygiene and safety standards, conducting effective quality audits, and fostering seamless interdepartmental coordination to support overall hotel operations.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Lead, motivate, and train a diverse housekeeping team using effective leadership and communication techniques.</p><p><br></p><p>Create efficient staff schedules, manage labor costs, and control payroll within budgetary guidelines.</p><p><br></p><p>Implement inventory control systems for linens, amenities, and cleaning supplies to minimize loss and optimize par levels.</p><p><br></p><p>Enforce and monitor compliance with health, safety, and hygiene standards (including COVID-19 protocols).</p><p><br></p><p>Conduct systematic room and public area inspections using standardized checklists and scoring systems.</p><p><br></p><p>Coordinate effectively with Front Office, Maintenance, and Laundry departments to ensure operational fluidity.</p><p><br></p><p>Module Content</p><p>Unit 1: Leadership in the Housekeeping Department (8 hours)</p><p>1.1 The Housekeeping Leader\'s Role: Strategic importance to guest satisfaction and hotel reputation.</p><p><br></p><p>1.2 Motivating a Diverse Team: Techniques for room attendants, housemen, and linen porters. Overcoming language and cultural barriers.</p><p><br></p><p>1.3 Daily Briefings &amp; Communication: Conducting effective morning briefings, using radio etiquette, and maintaining communication boards.</p><p><br></p><p>1.4 Training &amp; Skill Development: Developing SOPs (Standard Operating Procedures), conducting on-the-job training for cleaning techniques, and cross-training.</p><p><br></p><p>1.5 Performance Management: Setting productivity standards (rooms per hour), providing constructive feedback, and handling disciplinary issues.</p><p><br></p><p>Unit 2: Scheduling, Productivity &amp; Labor Cost Control (6 hours)</p><p>2.1 Forecasting Room Occupancy: Using Front Office data to predict workload.</p><p><br></p><p>2.2 Creating Master Schedules &amp; Rota Planning: Balancing full-time, part-time, and contract staff. Managing shift patterns (AM, PM, turndown).</p><p><br></p><p>2.3 Daily Assignment Sheets: Preparing and distributing task lists for room attendants and public area cleaners.</p><p><br></p><p>2.4 Managing Overtime &amp; Absenteeism: Strategies for control and contingency planning.</p><p><br></p><p>2.5 Productivity Metrics: Understanding and managing key metrics: Rooms Cleaned per Hour, Cost per Occupied Room, Labor Cost Percentage.</p><p><br></p><p>Unit 3: Inventory &amp; Supply Chain Management (6 hours)</p><p>3.1 Linen Management:</p><p><br></p><p>Establishing par levels (soiled, clean, in-use).</p><p><br></p><p>Linen room organization and security.</p><p><br></p><p>Conducting linen inventories and reconciling with laundry.</p><p><br></p><p>Controlling pilferage and managing discards.</p><p><br></p><p>3.2 Guest Supplies &amp; Amenities Control: Par stock management, requisitioning, and storage (FIFO method).</p><p><br></p><p>3.3 Cleaning Supplies &amp; Equipment:</p><p><br></p><p>Chemical safety (MSDS), dilution control, and eco-friendly alternatives.</p><p><br></p><p>Equipment maintenance logs for vacuums, floor machines, etc.</p><p><br></p><p>3.4 Purchase Requisitions &amp; Vendor Relations: Basic procurement processes.</p><p><br></p><p>Unit 4: Hygiene, Safety &amp; Compliance Standards (8 hours)</p><p>4.1 Health &amp; Safety Regulations: OSHA/local standards, hazard communication.</p><p><br></p><p>4.2 Sanitation Protocols: Cross-contamination prevention, color-coded cleaning systems (red for bathrooms, etc.).</p><p><br></p><p>4.3 Deep Cleaning &amp; Periodic Schedules: Carpet shampooing, mattress flipping, window cleaning.</p><p><br></p><p>4.4 Lost &amp; Found Procedure: Legal aspects, logging, storage, and disposal.</p><p><br></p><p>4.5 Security Procedures: Key control, master key security, reporting suspicious items.</p><p><br></p><p>4.6 Emergency Procedures: Role in fire evacuation, accident reporting, and first aid.</p><p><br></p><p>Unit 5: Quality Control &amp; Inspection Systems (8 hours)</p><p>5.1 The Philosophy of Quality Assurance: Consistency as the goal.</p><p><br></p><p>5.2 Developing Inspection Checklists: Room categories (check-out, stayover, VIP), public areas, back-of-house.</p><p><br></p><p>5.3 The Systematic Inspection Process: The \"S-Shaped\" or \"Clockwise\" room inspection method.</p><p><br></p><p>5.4 Scoring &amp; Reporting: Using a numeric or pass/fail system (e.g., 10-point scale). Digital inspection tools (tablet apps).</p><p><br></p><p>5.5 Providing Feedback: Coaching room attendants based on inspection findings—focus on correction, not blame.</p><p><br></p><p>5.6 Analyzing Quality Trends: Identifying chronic issues and implementing corrective training.</p><p><br></p><p>Unit 6: Interdepartmental Coordination (4 hours)</p><p>6.1 The Housekeeping-Front Office Nexus: The Room Status Discrepancy.</p><p><br></p><p>Real-time communication of room status (Vacant &amp; Ready, Occupied, Dirty, Out-of-Order).</p><p><br></p><p>Managing early check-ins, late check-outs, and rush rooms.</p><p><br></p><p>6.2 Coordination with Maintenance: Reporting repairs via work orders, prioritizing VIP and OOO rooms.</p><p><br></p><p>6.3 Coordination with Laundry: Managing linen flow, reporting quality issues, and scheduling.</p><p><br></p><p>6.4 Special Events &amp; Turnaround: Working with Banquets for post-event cleanup.</p><p><br></p><p>Unit 7: Capstone Practical &amp; Final Assessment (2 hours)</p><p>7.1 Live Inspection Exercise: Participants inspect a prepared guest room and public area, complete a checklist, and deliver feedback to a \"room attendant\" (role-play).</p><p><br></p><p>7.2 Scheduling Scenario: Create a weekly schedule based on a given occupancy forecast and labor budget.</p><p><br></p><p>7.3 Final Written Assessment.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Classroom Instruction: For theory, procedures, and calculations.</p><p><br></p><p>Practical Demonstrations: Proper cleaning techniques, chemical handling, bed making.</p><p><br></p><p>Walk-Through Inspections: In a hotel environment or mock-up room.</p><p><br></p><p>Case Studies: Analyzing real-world scenarios (e.g., norovirus outbreak, major linen loss).</p><p><br></p><p>Software Training: Introduction to housekeeping management modules within a PMS.</p><p><br></p><p>Group Workshops: Creating schedules, par levels, and inspection checklists.</p><p><br></p><p>Assessment Strategy</p><p>Practical Room Inspection &amp; Coaching Role-Play: 30%</p><p><br></p><p>Inventory &amp; Scheduling Project: 25%</p><p><br></p><p>Written Exams (Mid-Term &amp; Final): 30%</p><p><br></p><p>Class Participation &amp; Case Study Analysis: 15%</p><p><br></p><p>Recommended Resources &amp; Tools</p><p>Text: Managing Housekeeping Operations by Aleta A. Nitschke &amp; William D. Frye.</p><p><br></p><p>Tools: Room inspection checklists, sample assignment sheets, inventory forms, MSDS sheets.</p><p><br></p><p>Equipment: Training on carpet extractors, floor buffers, and spray-and-vac systems.</p><p><br></p><p>Software: Exposure to housekeeping modules of Opera, HotSOS, or similar.</p>                                                                                                                                                                                    ', NULL, NULL, NULL, 'draft', '', NULL, NULL, NULL, NULL, '2026-01-15 16:33:42', '2026-01-24 04:55:02', NULL),
(39, 'Housekeeping Operations & Quality Control', 'Covers departmental leadership, scheduling, inventory control, hygiene standards, audits, and interdepartmental coordination.', 'housekeeping', 'Executive Housekeeper / Housekeeping Manager', '\r\n                        <p style=\"text-align: center;\"><font size=\"7\"><b>&nbsp;Housekeeping Operations &amp; Quality Control</b></font></p><p style=\"text-align: center;\"><br></p><p>Module Description</p><p>This comprehensive module provides the operational and managerial framework for running an efficient, quality-driven housekeeping department. It focuses on developing leadership skills specific to housekeeping teams, mastering scheduling and payroll control, implementing inventory and supply chain systems, enforcing strict hygiene and safety standards, conducting effective quality audits, and fostering seamless interdepartmental coordination to support overall hotel operations.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Lead, motivate, and train a diverse housekeeping team using effective leadership and communication techniques.</p><p><br></p><p>Create efficient staff schedules, manage labor costs, and control payroll within budgetary guidelines.</p><p><br></p><p>Implement inventory control systems for linens, amenities, and cleaning supplies to minimize loss and optimize par levels.</p><p><br></p><p>Enforce and monitor compliance with health, safety, and hygiene standards (including COVID-19 protocols).</p><p><br></p><p>Conduct systematic room and public area inspections using standardized checklists and scoring systems.</p><p><br></p><p>Coordinate effectively with Front Office, Maintenance, and Laundry departments to ensure operational fluidity.</p><p><br></p><p>Module Content</p><p>Unit 1: Leadership in the Housekeeping Department (8 hours)</p><p>1.1 The Housekeeping Leader\'s Role: Strategic importance to guest satisfaction and hotel reputation.</p><p><br></p><p>1.2 Motivating a Diverse Team: Techniques for room attendants, housemen, and linen porters. Overcoming language and cultural barriers.</p><p><br></p><p>1.3 Daily Briefings &amp; Communication: Conducting effective morning briefings, using radio etiquette, and maintaining communication boards.</p><p><br></p><p>1.4 Training &amp; Skill Development: Developing SOPs (Standard Operating Procedures), conducting on-the-job training for cleaning techniques, and cross-training.</p><p><br></p><p>1.5 Performance Management: Setting productivity standards (rooms per hour), providing constructive feedback, and handling disciplinary issues.</p><p><br></p><p>Unit 2: Scheduling, Productivity &amp; Labor Cost Control (6 hours)</p><p>2.1 Forecasting Room Occupancy: Using Front Office data to predict workload.</p><p><br></p><p>2.2 Creating Master Schedules &amp; Rota Planning: Balancing full-time, part-time, and contract staff. Managing shift patterns (AM, PM, turndown).</p><p><br></p><p>2.3 Daily Assignment Sheets: Preparing and distributing task lists for room attendants and public area cleaners.</p><p><br></p><p>2.4 Managing Overtime &amp; Absenteeism: Strategies for control and contingency planning.</p><p><br></p><p>2.5 Productivity Metrics: Understanding and managing key metrics: Rooms Cleaned per Hour, Cost per Occupied Room, Labor Cost Percentage.</p><p><br></p><p>Unit 3: Inventory &amp; Supply Chain Management (6 hours)</p><p>3.1 Linen Management:</p><p><br></p><p>Establishing par levels (soiled, clean, in-use).</p><p><br></p><p>Linen room organization and security.</p><p><br></p><p>Conducting linen inventories and reconciling with laundry.</p><p><br></p><p>Controlling pilferage and managing discards.</p><p><br></p><p>3.2 Guest Supplies &amp; Amenities Control: Par stock management, requisitioning, and storage (FIFO method).</p><p><br></p><p>3.3 Cleaning Supplies &amp; Equipment:</p><p><br></p><p>Chemical safety (MSDS), dilution control, and eco-friendly alternatives.</p><p><br></p><p>Equipment maintenance logs for vacuums, floor machines, etc.</p><p><br></p><p>3.4 Purchase Requisitions &amp; Vendor Relations: Basic procurement processes.</p><p><br></p><p>Unit 4: Hygiene, Safety &amp; Compliance Standards (8 hours)</p><p>4.1 Health &amp; Safety Regulations: OSHA/local standards, hazard communication.</p><p><br></p><p>4.2 Sanitation Protocols: Cross-contamination prevention, color-coded cleaning systems (red for bathrooms, etc.).</p><p><br></p><p>4.3 Deep Cleaning &amp; Periodic Schedules: Carpet shampooing, mattress flipping, window cleaning.</p><p><br></p><p>4.4 Lost &amp; Found Procedure: Legal aspects, logging, storage, and disposal.</p><p><br></p><p>4.5 Security Procedures: Key control, master key security, reporting suspicious items.</p><p><br></p><p>4.6 Emergency Procedures: Role in fire evacuation, accident reporting, and first aid.</p><p><br></p><p>Unit 5: Quality Control &amp; Inspection Systems (8 hours)</p><p>5.1 The Philosophy of Quality Assurance: Consistency as the goal.</p><p><br></p><p>5.2 Developing Inspection Checklists: Room categories (check-out, stayover, VIP), public areas, back-of-house.</p><p><br></p><p>5.3 The Systematic Inspection Process: The \"S-Shaped\" or \"Clockwise\" room inspection method.</p><p><br></p><p>5.4 Scoring &amp; Reporting: Using a numeric or pass/fail system (e.g., 10-point scale). Digital inspection tools (tablet apps).</p><p><br></p><p>5.5 Providing Feedback: Coaching room attendants based on inspection findings—focus on correction, not blame.</p><p><br></p><p>5.6 Analyzing Quality Trends: Identifying chronic issues and implementing corrective training.</p><p><br></p><p>Unit 6: Interdepartmental Coordination (4 hours)</p><p>6.1 The Housekeeping-Front Office Nexus: The Room Status Discrepancy.</p><p><br></p><p>Real-time communication of room status (Vacant &amp; Ready, Occupied, Dirty, Out-of-Order).</p><p><br></p><p>Managing early check-ins, late check-outs, and rush rooms.</p><p><br></p><p>6.2 Coordination with Maintenance: Reporting repairs via work orders, prioritizing VIP and OOO rooms.</p><p><br></p><p>6.3 Coordination with Laundry: Managing linen flow, reporting quality issues, and scheduling.</p><p><br></p><p>6.4 Special Events &amp; Turnaround: Working with Banquets for post-event cleanup.</p><p><br></p><p>Unit 7: Capstone Practical &amp; Final Assessment (2 hours)</p><p>7.1 Live Inspection Exercise: Participants inspect a prepared guest room and public area, complete a checklist, and deliver feedback to a \"room attendant\" (role-play).</p><p><br></p><p>7.2 Scheduling Scenario: Create a weekly schedule based on a given occupancy forecast and labor budget.</p><p><br></p><p>7.3 Final Written Assessment.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Classroom Instruction: For theory, procedures, and calculations.</p><p><br></p><p>Practical Demonstrations: Proper cleaning techniques, chemical handling, bed making.</p><p><br></p><p>Walk-Through Inspections: In a hotel environment or mock-up room.</p><p><br></p><p>Case Studies: Analyzing real-world scenarios (e.g., norovirus outbreak, major linen loss).</p><p><br></p><p>Software Training: Introduction to housekeeping management modules within a PMS.</p><p><br></p><p>Group Workshops: Creating schedules, par levels, and inspection checklists.</p><p><br></p><p>Assessment Strategy</p><p>Practical Room Inspection &amp; Coaching Role-Play: 30%</p><p><br></p><p>Inventory &amp; Scheduling Project: 25%</p><p><br></p><p>Written Exams (Mid-Term &amp; Final): 30%</p><p><br></p><p>Class Participation &amp; Case Study Analysis: 15%</p><p><br></p><p>Recommended Resources &amp; Tools</p><p>Text: Managing Housekeeping Operations by Aleta A. Nitschke &amp; William D. Frye.</p><p><br></p><p>Tools: Room inspection checklists, sample assignment sheets, inventory forms, MSDS sheets.</p><p><br></p><p>Equipment: Training on carpet extractors, floor buffers, and spray-and-vac systems.</p><p><br></p><p>Software: Exposure to housekeeping modules of Opera, HotSOS, or similar.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:35:02', '2026-01-15 16:57:03', NULL),
(40, 'Housekeeping Supervision & Inspection', 'Develops room inspection skills, staff supervision, workflow coordination, and issue reporting.', 'housekeeping', 'Floor Supervisor', '\r\n                        <p style=\"text-align: center;\"><b><font size=\"7\">Housekeeping Supervision &amp; Inspection</font></b></p><p style=\"text-align: center;\"><br></p><p>Module Description</p><p>This hands-on, practical module is designed specifically for Floor Supervisors to develop the essential frontline leadership and quality assurance skills required for daily operations. Participants will master systematic room and public area inspection techniques, learn to effectively supervise and support room attendants, coordinate daily workflow and room status, and properly document and report maintenance, safety, and guest service issues. This module bridges the gap between room-level execution and departmental management.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Conduct detailed, consistent, and efficient room inspections using a standardized scoring system to ensure quality standards.</p><p><br></p><p>Provide constructive, real-time feedback and coaching to room attendants to correct deficiencies and improve performance.</p><p><br></p><p>Coordinate the daily workflow on an assigned floor/section, managing room status, prioritizing tasks, and adapting to changes.</p><p><br></p><p>Accurately report and follow up on maintenance issues, lost &amp; found items, safety hazards, and guest requests using proper protocols.</p><p><br></p><p>Demonstrate professional supervisory conduct and resolve common floor-level challenges (e.g., guest interactions, staff conflicts).</p><p><br></p><p>Module Content</p><p>Unit 1: The Role of the Floor Supervisor (4 hours)</p><p>1.1 Position &amp; Responsibilities: The link between management and the operational team.</p><p><br></p><p>1.2 Key Performance Metrics: Productivity (rooms/hour), quality scores, guest satisfaction, supply usage.</p><p><br></p><p>1.3 Professional Conduct &amp; Attitude: Leading by example, appearance, and ethical behavior.</p><p><br></p><p>1.4 Tools of the Trade: Master keys, radios, assignment sheets, inspection checklists, work order pads, lost &amp; found tags.</p><p><br></p><p>Unit 2: Mastering Systematic Room Inspections (10 hours)</p><p>2.1 The Philosophy of Inspection: Ensuring guest safety, consistency, and protecting assets.</p><p><br></p><p>2.2 Pre-Inspection Briefing: Reviewing assignment sheets, VIP lists, and special requests.</p><p><br></p><p>2.3 The Inspection Process – A Methodical Approach:</p><p><br></p><p>The 5-Zone Method: Entryway/Living Area, Sleeping Area, Bathroom, Closet/Storage, Balcony/Terrace.</p><p><br></p><p>The \"S-Pattern\" or \"Clockwise\" Technique: Ensuring no area is missed.</p><p><br></p><p>2.4 Inspection Criteria &amp; Checklist Mastery:</p><p><br></p><p>Cleanliness: Dust, streaks, spots, hair, odors.</p><p><br></p><p>Functionality: Lights, TV, plumbing, AC, locks, appliances.</p><p><br></p><p>Amenities &amp; Supplies: Correct count, placement, condition.</p><p><br></p><p>Linen &amp; Bed Making: Standards for folds, tightness, pillow placement.</p><p><br></p><p>Final Presentation: Overall impression and \"wow\" factor.</p><p><br></p><p>2.5 Scoring &amp; Documentation: Using a 10-point or pass/fail system. Recording findings clearly.</p><p><br></p><p>2.6 Practical Workshop: Live inspections of \"problem\" mock-up rooms.</p><p><br></p><p>Unit 3: Supervising &amp; Coaching Room Attendants (6 hours)</p><p>3.1 The Art of Supportive Supervision: Balancing quality control with team support.</p><p><br></p><p>3.2 Effective Morning Briefings: Communicating daily priorities, VIPs, and special projects.</p><p><br></p><p>3.3 Providing Constructive Feedback: The \"Feedback Sandwich\" technique (Positive → Corrective → Encouraging).</p><p><br></p><p>3.4 On-the-Spot Coaching: Demonstrating correct techniques (e.g., shower door polishing, bed making speed).</p><p><br></p><p>3.5 Handling Performance Issues: Addressing repeated mistakes, missed items, and time management issues professionally.</p><p><br></p><p>3.6 Role-Play Scenarios: Practice coaching conversations for common inspection failures.</p><p><br></p><p>Unit 4: Daily Workflow &amp; Room Status Coordination (6 hours)</p><p>4.1 Managing the Assignment Sheet: Distributing workloads fairly, considering experience and speed.</p><p><br></p><p>4.2 Room Status Management:</p><p><br></p><p>Updating status in the system or via radio (Dirty → Clean → Inspected → Ready).</p><p><br></p><p>The critical role in preventing front desk discrepancies.</p><p><br></p><p>4.3 Priority Room Sequencing: VIP arrivals, early check-ins, stay-over vs. check-out rooms.</p><p><br></p><p>4.4 Handling Operational Challenges: Rush rooms, assisting behind-schedule attendants, managing equipment shortages.</p><p><br></p><p>4.5 End-of-Shift Procedures: Verifying all rooms are completed, collecting keys and checklists, preparing end-of-day reports.</p><p><br></p><p>Unit 5: Reporting &amp; Interdepartmental Communication (4 hours)</p><p>5.1 Maintenance Reporting: Writing clear, specific work orders (e.g., not \"AC broken,\" but \"AC in 402 blowing warm air, noise from unit\").</p><p><br></p><p>5.2 Lost &amp; Found Procedure: Proper tagging, bagging, logging, and storage.</p><p><br></p><p>5.3 Safety &amp; Hazard Reporting: Wet floors, broken glass, damaged furniture, suspicious items.</p><p><br></p><p>5.4 Guest Requests &amp; \"While You Were Out\" Slips: Accurate logging and follow-up.</p><p><br></p><p>5.5 Communication with Front Desk: Real-time updates on room availability, guest requests, and issues.</p><p><br></p><p>5.6 Escalation Protocols: Knowing when and how to involve the Executive Housekeeper or Manager.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Intensive Practical Labs: 50% of module time spent in mock guest rooms or actual hotel environment performing inspections.</p><p><br></p><p>Role-Playing &amp; Simulations: Coaching conversations, guest interactions, morning briefings.</p><p><br></p><p>Checklist Development: Participants create and refine their own inspection checklists.</p><p><br></p><p>Case Study Analysis: Reviewing inspection reports and determining root causes of failures.</p><p><br></p><p>Shadowing: Following an experienced supervisor during a live shift (if possible).</p><p><br></p><p>Assessment Strategy</p><p>Practical Mastery Exam (Live Room Inspection): 40%</p><p><br></p><p>Participant must inspect a room within a time limit, identify all defects, complete the checklist, and provide verbal feedback to an actor playing a room attendant.</p><p><br></p><p>Coaching &amp; Communication Role-Play: 25%</p><p><br></p><p>Written Exam (Procedures &amp; Protocols): 25%</p><p><br></p><p>Daily Participation &amp; In-Class Exercises: 10%</p><p><br></p><p>Required Materials &amp; Tools</p><p>Training Guest Room(s) or detailed mock-up.</p><p><br></p><p>Sample Inspection Checklists (blank and completed).</p><p><br></p><p>Assignment Sheets, Work Order Pads, Lost &amp; Found Tags.</p><p><br></p><p>Radios for communication practice.</p><p><br></p><p>Common \"Defect\" Props: Fake dust, smudges, missing amenities, malfunctioning lamp.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:36:49', '2026-01-15 16:56:59', NULL),
(41, 'Food & Beverage Strategic Management', 'Develops leadership in F&B operations, cost control, quality assurance, menu strategy, and customer satisfaction metrics.', 'food-beverage', 'F&B Manager / Director', '\r\n                        <p style=\"text-align: center;\"><b><font size=\"7\">Food &amp; Beverage Strategic Management</font></b></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This advanced strategic module focuses on developing the leadership skills and business acumen required to manage and grow a profitable, quality-driven Food &amp; Beverage operation. It moves beyond daily operations to encompass the analysis, planning, and execution of strategies across cost control, financial performance, quality assurance, menu engineering, and comprehensive customer experience management. Participants will learn to make data-driven decisions that align F&amp;B operations with overall business goals.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Formulate and articulate a strategic vision for an F&amp;B operation that aligns with the brand\'s positioning and financial objectives.</p><p><br></p><p>Analyze and control prime costs (food, beverage, and labor) using key performance indicators to maximize profitability.</p><p><br></p><p>Design and implement a holistic quality assurance program covering food safety, consistency, and culinary excellence.</p><p><br></p><p>Apply menu engineering and pricing strategies to optimize sales mix, profitability, and guest perception.</p><p><br></p><p>Develop and monitor a customer satisfaction measurement system using quantitative and qualitative metrics to drive continuous improvement.</p><p><br></p><p>Module Content</p><p>Unit 1: The Strategic F&amp;B Leader (8 hours)</p><p>1.1 The Evolving Role of the F&amp;B Manager: From operator to strategic business leader and experience curator.</p><p><br></p><p>1.2 Vision, Mission, and Goal Setting: Creating a strategic plan for an F&amp;B department or outlet.</p><p><br></p><p>1.3 Financial Acumen for F&amp;B Leaders: Understanding P&amp;L statements, balance sheet impact, and ROI analysis for F&amp;B projects.</p><p><br></p><p>1.4 Leadership Styles in a Culinary Environment: Leading chefs, service professionals, and multi-cultural teams.</p><p><br></p><p>1.5 Innovation and Trends Analysis: Incorporating market trends, technology, and sustainability into strategic planning.</p><p><br></p><p>Unit 2: Advanced Cost Control &amp; Financial Performance (10 hours)</p><p>2.1 Mastering Prime Cost Management:</p><p><br></p><p>Food Cost: Theoretical vs. actual cost calculation, yield testing, and recipe standardization.</p><p><br></p><p>Beverage Cost: Liquor, wine, and non-alcoholic beverage cost control systems (par stocks, perpetual inventory).</p><p><br></p><p>Labor Cost: Scheduling optimization, productivity metrics (covers per labor hour, sales per labor hour), and managing overtime.</p><p><br></p><p>2.2 Key F&amp;B Ratios and KPIs: Analyzing Food Cost Percentage, Beverage Cost %, Labor Cost %, Average Check, and Profit per Seat.</p><p><br></p><p>2.3 Budgeting and Forecasting: Developing annual operating budgets, sales forecasts, and capital expenditure plans.</p><p><br></p><p>2.4 Purchasing and Supplier Management: Strategic sourcing, negotiation techniques, and supply chain resilience.</p><p><br></p><p>2.5 Waste Management &amp; Sustainability: Implementing systems to reduce waste and its financial and environmental impact.</p><p><br></p><p>Unit 3: Quality Assurance &amp; Operational Excellence (8 hours)</p><p>3.1 Beyond HACCP: Building a Culture of Food Safety: Leadership\'s role in enforcing standards and audits.</p><p><br></p><p>3.2 Standard Operating Procedures (SOPs): Development, documentation, and training for consistency across all stations.</p><p><br></p><p>3.3 The Culinary Excellence Framework: Implementing a structured recipe development, tasting, and approval process.</p><p><br></p><p>3.4 Beverage Program Quality: Wine list curation, cocktail program consistency, and non-alcoholic beverage standards.</p><p><br></p><p>3.5 Mystery Shopper Programs &amp; Internal Audits: Designing and using audit tools to measure compliance and service quality.</p><p><br></p><p>Unit 4: Menu Strategy, Engineering &amp; Pricing (10 hours)</p><p>4.1 Menu as a Strategic Marketing Tool: Aligning menu with concept, brand, and target market.</p><p><br></p><p>4.2 Menu Engineering Analysis:</p><p><br></p><p>Categorizing items as Stars, Plowhorses, Puzzles, or Dogs based on profitability and popularity.</p><p><br></p><p>Using sales mix data to make strategic decisions on pricing, placement, and promotion.</p><p><br></p><p>4.3 Psychology of Menu Design: Layout, description writing, and visual design to influence guest choice and perception of value.</p><p><br></p><p>4.4 Strategic Pricing Models: Cost-plus, competitive, value-based, and psychological pricing strategies.</p><p><br></p><p>4.5 New Menu Launch Strategy: Planning, costing, training, marketing, and post-launch analysis.</p><p><br></p><p>Unit 5: Customer Experience &amp; Satisfaction Metrics (6 hours)</p><p>5.1 Designing the End-to-End F&amp;B Experience: From reservation to departure.</p><p><br></p><p>5.2 Measuring Satisfaction: Moving beyond comment cards. Utilizing Net Promoter Score (NPS), online review analysis (Revinate, etc.), and real-time feedback tools.</p><p><br></p><p>5.3 Data-Driven Service Recovery: Analyzing complaint trends to address systemic issues, not just individual incidents.</p><p><br></p><p>5.4 Building Loyalty and Repeat Business: Strategic loyalty programs, CRM for F&amp;B, and personalized service initiatives.</p><p><br></p><p>5.5 Connecting Metrics to Action: Translating guest feedback into actionable improvements in menu, service, or ambiance.</p><p><br></p><p>Unit 6: Capstone Strategic Project (3 hours)</p><p>6.1 Project Brief: Participants analyze a comprehensive case study of an underperforming F&amp;B outlet.</p><p><br></p><p>6.2 Deliverable: Creation of a Strategic Turnaround Plan covering:</p><p><br></p><p>Vision and Goals</p><p><br></p><p>Prime Cost Analysis &amp; Action Plan</p><p><br></p><p>Menu Re-engineering Proposal</p><p><br></p><p>Quality Assurance Initiative</p><p><br></p><p>Customer Satisfaction Measurement Plan</p><p><br></p><p>6.3 Final Presentation &amp; Peer Review.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Case Study Analysis: Deep dives into real-world F&amp;B success and failure stories.</p><p><br></p><p>Financial Workshops: Hands-on exercises with P&amp;L statements, menu engineering spreadsheets, and budgeting tools.</p><p><br></p><p>Simulation Software: Use of F&amp;B costing and inventory software (e.g., ChefTec, Inventory Express).</p><p><br></p><p>Guest Speakers: Successful F&amp;B Directors, Culinary Leaders, and Restaurant Consultants.</p><p><br></p><p>Strategic Debates: Topics such as discounting strategies, third-party delivery impact, and sustainability investments.</p><p><br></p><p>Project-Based Learning: The capstone project synthesizes all module components.</p><p><br></p><p>Assessment Strategy</p><p>Capstone Strategic Turnaround Plan &amp; Presentation: 40%</p><p><br></p><p>Financial Analysis &amp; Menu Engineering Portfolio: 30%</p><p><br></p><p>Case Study Analysis Papers: 20%</p><p><br></p><p>Class Participation &amp; Contribution: 10%</p><p><br></p><p>Recommended Resources</p><p>Text: Food and Beverage Management by Bernard Davis, Andrew Lockwood, and Peter Alcott.</p><p><br></p><p>Text: Restaurant Financial Basics by Raymond S. Schmidgall.</p><p><br></p><p>Software: Demonstrations of leading POS (Micros, Toast) reporting and analytics dashboards.</p><p><br></p><p>Industry Publications: Restaurant Business, Nation\'s Restaurant News, The Perfect Spot analytics reports.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:39:36', '2026-01-15 16:56:36', NULL);
INSERT INTO `learning_modules` (`id`, `title`, `topic`, `department`, `roles`, `content`, `file_path`, `file_type`, `file_size`, `status`, `remarks`, `rejection_reason`, `compliance_reason`, `created_by`, `approved_by`, `created_at`, `updated_at`, `module_id`) VALUES
(42, 'Advanced Culinary Leadership & Menu Strategy', 'Focuses on kitchen leadership, menu innovation, food cost control, and culinary quality assurance.', 'kitchen', 'Executive Chef / Head Chef', '\r\n                        <p style=\"text-align: center;\"><font size=\"7\"><b style=\"\"><span style=\"font-size: 20pt;\">Advanced Culinary Leadership &amp; Menu Strategy</span></b></font></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This high-level module equips experienced culinary leaders with the strategic mindset and practical tools to lead a profitable, innovative, and excellence-driven kitchen. It focuses on the transition from operational management to visionary leadership, emphasizing the creation of a culinary culture, the strategic development of financially sound and market-responsive menus, and the implementation of systems that ensure consistent quality while controlling costs. Participants will learn to lead with creativity and analytics.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Articulate and implement a culinary vision and leadership philosophy that inspires a team, drives culture, and aligns with business goals.</p><p><br></p><p>Lead the end-to-end menu strategy process, from concept ideation and R&amp;D to financial modeling, launch, and lifecycle management.</p><p><br></p><p>Design and enforce robust food cost control systems, including yield optimization, waste tracking, and inventory intelligence.</p><p><br></p><p>Develop and manage a comprehensive culinary quality assurance program that ensures consistency, safety, and excellence from receiving to plate.</p><p><br></p><p>Foster innovation, manage culinary talent, and navigate the challenges of sustainability and modern food trends within a strategic framework.</p><p><br></p><p>Module Content</p><p>Unit 1: The Modern Culinary Leader (10 hours)</p><p>1.1 From Chef to Culinary CEO: Defining strategic leadership beyond the expediter window.</p><p><br></p><p>1.2 Creating a Culinary Culture: Building a kitchen based on respect, continuous learning, and excellence. The leader as coach and mentor.</p><p><br></p><p>1.3 Talent Management &amp; Succession Planning: Recruiting, developing, and retaining top culinary talent. Building a leadership pipeline within the brigade.</p><p><br></p><p>1.4 Communication &amp; Influence: Leading kitchen briefings, communicating vision to FOH and management, and handling critical conversations.</p><p><br></p><p>1.5 The Chef as Brand Ambassador: Representing the culinary vision to guests, media, and the community.</p><p><br></p><p>Unit 2: Strategic Menu Innovation &amp; Development (12 hours)</p><p>2.1 The Menu as a Business Plan: Aligning the menu with concept, market positioning, and financial targets.</p><p><br></p><p>2.2 The Ideation &amp; R&amp;D Process: Structured creativity. Trend forecasting, seasonal sourcing, and creating a pipeline of new dishes.</p><p><br></p><p>2.3 Plate Cost Architecture &amp; Engineering:</p><p><br></p><p>Advanced Plate Costing: Incorporating all costs (garnish, fuel, smallwares).</p><p><br></p><p>Menu Engineering 2.0: Advanced analysis of contribution margin, menu mix impact, and strategic item placement.</p><p><br></p><p>Pricing for Profit &amp; Perception: Value-based pricing strategies for different menu segments.</p><p><br></p><p>2.4 Menu Design Psychology &amp; Storytelling: How menu layout, language, and imagery influence sales and guest experience.</p><p><br></p><p>2.5 The Menu Launch Playbook: Phasing, cross-departmental training, marketing integration, and post-launch analysis.</p><p><br></p><p>Unit 3: Advanced Food Cost Control &amp; Kitchen Economics (10 hours)</p><p>3.1 Systems-Based Cost Control: Moving from reactive to proactive management.</p><p><br></p><p>3.2 The Power of Yield: Standardized yield tests for all proteins and produce. Implementing butchery and fabrication programs.</p><p><br></p><p>3.3 Intelligent Inventory Management:</p><p><br></p><p>Perpetual vs. periodic inventory for high-cost items.</p><p><br></p><p>Implementing PAR levels and just-in-time ordering to reduce waste and capital tied up in stock.</p><p><br></p><p>Analyzing variance reports to identify theft, waste, or costing errors.</p><p><br></p><p>3.4 Waste Auditing &amp; the \"Root Cause\" Analysis: Tracking waste by category (spoilage, prep, plate waste) to drive targeted reduction initiatives.</p><p><br></p><p>3.5 Labor Cost Optimization in the Kitchen: Analyzing covers per cook hour, scheduling for efficiency, and managing overtime strategically.</p><p><br></p><p>Unit 4: Culinary Quality Assurance Systems (8 hours)</p><p>4.1 Beyond the Recipe: Standardizing Excellence: Developing detailed production guides with photos, weights, and plating specs.</p><p><br></p><p>4.2 The Quality Line Check: Implementing a pre-service tasting and inspection ritual for every station.</p><p><br></p><p>4.3 Supplier Quality Management: Setting and auditing supplier specifications, conducting facility visits, and building partnerships for quality.</p><p><br></p><p>4.4 The Receiving Inspection: Empowering teams to reject substandard product.</p><p><br></p><p>4.5 Consistency Audits: Implementing a \"secret diner\" style plate audit to ensure specs are followed during service.</p><p><br></p><p>Unit 5: Leading in a Changing Landscape (6 hours)</p><p>5.1 Sustainable &amp; Ethical Sourcing Strategy: Building a program that is both principled and practical. Communicating your story.</p><p><br></p><p>5.2 Navigating Food Trends vs. Creating Classics: Balancing innovation with core offerings.</p><p><br></p><p>5.3 Technology in the Kitchen: Evaluating and implementing tech for inventory, ordering, recipe costing, and waste tracking.</p><p><br></p><p>5.4 Culinary Wellness &amp; Dietary Programming: Strategically developing menus for health-conscious guests and major dietary trends (plant-forward, keto, allergen-friendly).</p><p><br></p><p>Unit 6: Capstone Culinary Business Plan (2 hours)</p><p>6.1 The Challenge: Develop a comprehensive business plan for a new restaurant concept or a major repositioning of an existing one.</p><p><br></p><p>6.2 Deliverables Must Include:</p><p><br></p><p>Culinary Vision &amp; Concept Statement</p><p><br></p><p>Sample Menu with full plate costs, pricing rationale, and engineering analysis</p><p><br></p><p>Opening Inventory Budget &amp; Proposed Supplier List</p><p><br></p><p>Quality Assurance Framework</p><p><br></p><p>First 90-Day Operational &amp; Marketing Launch Plan</p><p><br></p><p>6.3 Final Presentation to a \"Investment Panel\" (instructors &amp; peers).</p><p><br></p><p>Teaching and Learning Methodology</p><p>Culinary Leadership Labs: Scenarios on handling brigade conflict, conducting R&amp;D sessions, and leading pre-service meetings.</p><p><br></p><p>Financial Modeling Workshops: Hands-on with recipe costing software and complex menu engineering spreadsheets.</p><p><br></p><p>Case Studies: Analysis of successful and failed menu launches, restaurant turnarounds, and chef-led brands.</p><p><br></p><p>Supplier Simulation: Negotiation role-play and specification writing exercises.</p><p><br></p><p>Guest Chef Speakers: Sessions with renowned Executive Chefs and Culinary Directors on strategy and leadership.</p><p><br></p><p>Project-Based Learning: The capstone business plan is the central integrating project.</p><p><br></p><p>Assessment Strategy</p><p>Capstone Culinary Business Plan &amp; Presentation: 40%</p><p><br></p><p>Menu Engineering &amp; Costing Portfolio: 30%</p><p><br></p><p>Complete costing, engineering, and pricing analysis for a proposed menu.</p><p><br></p><p>Strategic Initiative Proposal: 20%</p><p><br></p><p>A written plan for implementing one major module component (e.g., a waste reduction program, a new QA system).</p><p><br></p><p>Peer &amp; Instructor Evaluation of Leadership Labs: 10%</p><p><br></p><p>Required Resources &amp; Tools</p><p>Culinary Costing Software (e.g., ChefTec, Optimum Control) or advanced spreadsheet templates.</p><p><br></p><p>Access to a test kitchen for R&amp;D and presentation practice.</p><p><br></p><p>Sample Spec Books, Production Guides, and Inventory Reports from various operations.</p><p><br></p><p>Industry Data: Reports from Datassential, The NPD Group, or similar on food trends.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:41:39', '2026-01-15 16:56:32', NULL),
(43, 'Hospitality Sales Strategy & Market Growth', 'Develops skills in sales planning, market analysis, client relations, and revenue growth strategies.', 'sales-marketing', 'Sales & Marketing Manager', '\r\n                        <p style=\"text-align: center;\"><font size=\"7\"><b>Hospitality Sales Strategy &amp; Market Growth</b></font></p><p style=\"text-align: center;\">Module Title: Hospitality Sales Strategy &amp; Market Growth</p><p>Module Code: HSS-401</p><p>Duration: 42 hours (strategic workshops, market analysis, and project-based learning)</p><p>Target Audience: Sales Directors, Revenue Managers, Marketing Managers, General Managers, Business Development Managers, Senior Account Managers</p><p><br></p><p>Module Description</p><p>This advanced strategic module equips hospitality leaders with the frameworks and skills to drive sustainable market growth through analytical sales planning and strategic client management. It moves beyond transactional selling to focus on developing a comprehensive sales strategy based on deep market analysis, competitive positioning, and long-term client relationship cultivation. Participants will learn to build and execute plans that align sales efforts with overall business objectives for maximum revenue impact.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Develop a data-driven, comprehensive annual sales and marketing plan aligned with the property\'s financial and positioning goals.</p><p><br></p><p>Conduct sophisticated market analysis, including segmentation, competitive benchmarking, and identification of growth opportunities.</p><p><br></p><p>Design and implement strategic account management programs to maximize client lifetime value and foster long-term partnerships.</p><p><br></p><p>Formulate and execute proactive strategies for penetrating new market segments and generating new business.</p><p><br></p><p>Measure sales effectiveness through key performance indicators (KPIs) and adapt strategies based on performance analytics.</p><p><br></p><p>Module Content</p><p>Unit 1: Strategic Sales Planning &amp; Leadership (8 hours)</p><p>1.1 The Role of the Strategic Sales Leader: Evolving from order-taker to market shaper and revenue architect.</p><p><br></p><p>1.2 The Anatomy of a Sales &amp; Marketing Plan:</p><p><br></p><p>Situational Analysis (SWOT, PESTLE)</p><p><br></p><p>Goal Setting (SMART objectives for room nights, revenue, market share)</p><p><br></p><p>Strategy Formulation (Target markets, value propositions)</p><p><br></p><p>Action Plans &amp; Tactics</p><p><br></p><p>Budgeting &amp; Resource Allocation</p><p><br></p><p>Measurement &amp; Control</p><p><br></p><p>1.3 Leading a High-Performance Sales Team: Motivating, coaching, and managing a metrics-driven sales culture.</p><p><br></p><p>1.4 Sales Technology Stack Strategy: Evaluating and leveraging CRM, sales enablement tools, and data analytics platforms.</p><p><br></p><p>Unit 2: Advanced Market Analysis &amp; Competitive Intelligence (10 hours)</p><p>2.1 Market Segmentation &amp; Targeting: Defining and prioritizing segments (Corporate, Group, Leisure, SMERF, etc.) based on profitability and potential.</p><p><br></p><p>2.2 The Strategic Marketing Mix (7 Ps) for Hospitality: Product, Price, Place, Promotion, People, Process, Physical Evidence.</p><p><br></p><p>2.3 Competitive Benchmarking &amp; Positioning:</p><p><br></p><p>Identifying direct &amp; indirect competitors.</p><p><br></p><p>Conducting SWOT analysis on competitors.</p><p><br></p><p>Developing a unique value proposition and market positioning map.</p><p><br></p><p>2.4 Utilizing Market Data &amp; Intelligence: Interpreting STR reports, destination trends, economic indicators, and feeder market analysis.</p><p><br></p><p>2.5 Identifying White Space &amp; Growth Opportunities: Analyzing unmet needs in the market and adjacencies for expansion.</p><p><br></p><p>Unit 3: Strategic Client Relationship &amp; Account Management (8 hours)</p><p>3.1 From Sales to Strategic Partnerships: The client relationship lifecycle.</p><p><br></p><p>3.2 Key Account Management (KAM) Framework: Identifying, tiering, and developing strategic plans for top accounts.</p><p><br></p><p>3.3 Consultative Selling &amp; Needs Analysis: Advanced questioning techniques to uncover client\'s deeper business objectives and pain points.</p><p><br></p><p>3.4 Negotiation Strategy for Long-Term Value: Moving beyond rate haggling to creating win-win agreements on value, terms, and partnerships.</p><p><br></p><p>3.5 Building Loyalty &amp; Defending Against Competition: Creating \"stickiness\" through exceptional service, added value, and relationship depth.</p><p><br></p><p>Unit 4: Proactive Business Generation &amp; New Market Penetration (8 hours)</p><p>4.1 New Business Development Process: Prospecting, qualifying, approaching, and converting new accounts.</p><p><br></p><p>4.2 Strategic Networking &amp; Industry Presence: Building a personal and property brand within key industries and associations.</p><p><br></p><p>4.3 Digital Lead Generation &amp; Social Selling: Using LinkedIn, industry forums, and content marketing to build pipeline.</p><p><br></p><p>4.4 RFPs &amp; Proposals as Strategic Tools: Crafting compelling, tailored responses that sell value, not just space.</p><p><br></p><p>4.5 Managing the Sales Pipeline &amp; Forecasting: Using CRM data to accurately forecast business and identify pipeline gaps.</p><p><br></p><p>Unit 5: Measuring Performance &amp; Driving Revenue Growth (6 hours)</p><p>5.1 Sales &amp; Marketing KPIs Dashboard: Tracking lead conversion rate, sales cycle length, account penetration, cost of customer acquisition (CAC), and customer lifetime value (CLV).</p><p><br></p><p>5.2 Analyzing Sales Performance Data: Using data to diagnose problems, celebrate wins, and redirect efforts.</p><p><br></p><p>5.3 Aligning Sales Strategy with Revenue Management: Collaborative strategy sessions on pricing, inventory allocation, and displacement analysis.</p><p><br></p><p>5.4 Presenting to Stakeholders: Communicating sales strategy, results, and ROI effectively to ownership and general management.</p><p><br></p><p>Unit 6: Capstone Strategic Sales Plan (2 hours)</p><p>6.1 The Challenge: Develop a full Annual Strategic Sales &amp; Marketing Plan for a given hotel or hospitality business scenario.</p><p><br></p><p>6.2 Plan Must Include:</p><p><br></p><p>Executive Summary &amp; Business Goals</p><p><br></p><p>Detailed Market &amp; Competitive Analysis</p><p><br></p><p>Target Segment Strategies</p><p><br></p><p>Key Account Plans</p><p><br></p><p>New Business Development Initiatives</p><p><br></p><p>Tactical Calendar &amp; Budget</p><p><br></p><p>Measurement Framework &amp; KPIs</p><p><br></p><p>6.3 Final \"Boardroom\" Presentation to a panel of instructors and peers acting as ownership/management.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Case Study Deep Dives: Analysis of successful and failed sales turnarounds or market entries.</p><p><br></p><p>Market Analysis Workshops: Hands-on sessions with real STR data, competitor sets, and destination reports.</p><p><br></p><p>CRM Simulation: Using a CRM platform to manage accounts, pipeline, and forecasting.</p><p><br></p><p>Negotiation &amp; Pitching Role-Plays: Simulated client meetings and RFP presentations.</p><p><br></p><p>Guest Speakers: Industry leaders in hotel sales, destination marketing, and hospitality consulting.</p><p><br></p><p>Project-Based Learning: The capstone sales plan is the central, integrative output of the module.</p><p><br></p><p>Assessment Strategy</p><p>Capstone Annual Strategic Sales &amp; Marketing Plan: 45%</p><p><br></p><p>Competitive Analysis &amp; Positioning Report: 25%</p><p><br></p><p>Key Account Management Simulation (Role-Play &amp; Written Plan): 20%</p><p><br></p><p>Class Participation &amp; Workshop Contributions: 10%</p><p><br></p><p>Recommended Resources</p><p>Text: Hotel Sales and Operations by David M. Stipanuk and Michael L. Kasavana.</p><p><br></p><p>Industry Tools: STR report samples, CVENT/RFP process simulations, Salesforce or similar CRM demo.</p><p><br></p><p>Data Sources: Introduction to destination marketing organization (DMO) data, AirDNA (for alternative accommodation analysis), and travel trend reports (Skift, PhocusWire).</p><p><br></p><p>Publications: HSMAI resources, Hotel Business.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:44:02', '2026-01-15 16:56:28', NULL),
(44, 'Strategic HR Leadership in Hospitality', 'Covers workforce planning, performance management, employee relations, and policy governance.', 'hr', 'HR Manager / Director', '\r\n                        <p style=\"text-align: center;\"><font size=\"7\"><b>Strategic HR Leadership in Hospitality</b></font></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This senior-level module moves beyond operational HR tasks to focus on the strategic role of the HR leader as a key business partner in the hospitality industry. It equips participants to align human capital strategy with organizational goals, design and implement systemic workforce solutions, and lead with a focus on culture, talent sustainability, and organizational resilience. The module addresses the unique challenges of managing a diverse, often transient, and service-focused workforce at a strategic level.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Develop and execute a strategic human capital plan that supports the organization\'s financial and operational goals in a dynamic hospitality environment.</p><p><br></p><p>Design and govern equitable, compliant, and effective performance management and total rewards systems that drive productivity and engagement.</p><p><br></p><p>Lead complex employee relations initiatives, foster a positive organizational culture, and manage risk through proactive policy governance.</p><p><br></p><p>Analyze workforce metrics and HR analytics to forecast talent needs, diagnose issues, and measure the ROI of HR initiatives.</p><p><br></p><p>Advocate for and lead Diversity, Equity, Inclusion, and Belonging (DEIB) initiatives as a core business strategy for innovation and guest satisfaction.</p><p><br></p><p>Module Content</p><p>Unit 1: The Strategic HR Business Partner in Hospitality (8 hours)</p><p>1.1 The Evolution of HR: From Administrator to Strategic Leader. The HR Leader as Culture Architect, Talent Scout, and Compliance Guardian.</p><p><br></p><p>1.2 Aligning HR Strategy with Business Strategy: Translating business goals (e.g., revenue growth, brand repositioning, new openings) into a human capital plan.</p><p><br></p><p>1.3 The Hospitality Labor Landscape: Strategic analysis of generational shifts, gig economy impacts, skills gaps, and global talent mobility.</p><p><br></p><p>1.4 Building Credibility &amp; Influence: Partnering effectively with Operations, Finance, and Ownership/Executive Committees.</p><p><br></p><p>1.5 HR Technology &amp; Data Strategy: Evaluating and implementing HRIS, talent analytics platforms, and AI tools for strategic advantage.</p><p><br></p><p>Unit 2: Strategic Workforce Planning &amp; Talent Management (10 hours)</p><p>2.1 Workforce Planning as a Strategic Process:</p><p><br></p><p>Demand Forecasting: Aligning staffing models with business cycles and growth plans.</p><p><br></p><p>Supply Analysis: Internal talent pipelines vs. external market sourcing.</p><p><br></p><p>Gap Analysis &amp; Strategy Development: Addressing surpluses and deficits.</p><p><br></p><p>2.2 Strategic Talent Acquisition &amp; Employer Branding: Building a talent attraction strategy that goes beyond job boards. Crafting a compelling Employee Value Proposition (EVP) for hospitality.</p><p><br></p><p>2.3 Succession Planning &amp; Leadership Development: Creating robust pipelines for critical roles, from department head to General Manager.</p><p><br></p><p>2.4 Strategic Learning &amp; Development: Linking training investments to business outcomes (e.g., reducing turnover, improving guest scores, driving sales).</p><p><br></p><p>2.5 Talent Mobility &amp; Retention Strategies: Designing career paths, cross-training programs, and retention initiatives for high-potential employees.</p><p><br></p><p>Unit 3: Performance &amp; Total Rewards Systems (8 hours)</p><p>3.1 Designing a Modern Performance Management Cycle: Moving from annual reviews to continuous feedback, coaching, and development-focused conversations.</p><p><br></p><p>3.2 Goal Setting &amp; Alignment: Cascading organizational objectives (OKRs/KPIs) to departmental and individual goals.</p><p><br></p><p>3.3 Strategic Compensation &amp; Benefits Planning:</p><p><br></p><p>Conducting compensation benchmarking and designing competitive, equitable pay structures.</p><p><br></p><p>Developing strategic benefits packages (wellness, financial, work-life) that attract and retain talent.</p><p><br></p><p>Variable Pay &amp; Incentive Design: Aligning bonus structures with strategic goals.</p><p><br></p><p>3.4 Legal Compliance &amp; Governance in Compensation: Ensuring pay equity, FLSA compliance, and transparent communication.</p><p><br></p><p>Unit 4: Employee Relations, Culture &amp; Policy Governance (10 hours)</p><p>4.1 Proactive Employee Relations Strategy: Building trust, preventing issues, and creating effective communication channels.</p><p><br></p><p>4.2 Leading Complex Investigations &amp; Dispute Resolution: Advanced techniques for handling sensitive complaints (harassment, discrimination, theft).</p><p><br></p><p>4.3 Labor Relations Strategy (Union &amp; Non-Union): Understanding the strategic implications of union campaigns, collective bargaining preparation, and maintaining positive labor climates.</p><p><br></p><p>4.4 Designing &amp; Governing HR Policies: Creating policy frameworks that are compliant, support the culture, and provide managerial guidance. Policy communication and training.</p><p><br></p><p>4.5 Cultivating Organizational Culture &amp; Engagement: Measuring engagement (e.g., eNPS), designing action plans, and leading culture-change initiatives. The role of HR in embedding core values.</p><p><br></p><p>Unit 5: HR Analytics, Risk Management &amp; the Future of Work (6 hours)</p><p>5.1 HR Metrics &amp; Analytics for Decision-Making: Moving beyond headcount to analyzing turnover costs, time-to-fill, training ROI, and predictive attrition models.</p><p><br></p><p>5.2 Strategic Risk Management: Mitigating risks related to workplace safety, harassment claims, wage &amp; hour violations, and data privacy (GDPR/CCPA).</p><p><br></p><p>5.3 Leading DEIB as a Business Imperative: Developing a strategic DEIB roadmap. Linking diversity to innovation, market reach, and guest experience.</p><p><br></p><p>5.4 The Future of Work in Hospitality: Strategic planning for automation, hybrid roles, flexible work models, and the evolving psychological contract with employees.</p><p><br></p><p>Unit 6: Capstone HR Strategic Plan (2 hours)</p><p>6.1 The Challenge: Develop a 3-Year Strategic Human Capital Plan for a multi-property hospitality group or a large flagship hotel facing specific business challenges (e.g., high turnover, new market entry, post-merger integration).</p><p><br></p><p>6.2 Plan Must Include:</p><p><br></p><p>External &amp; Internal Environmental Scan</p><p><br></p><p>Strategic HR Pillars &amp; Priority Initiatives (e.g., Talent, Culture, Performance)</p><p><br></p><p>Workforce Planning Forecast &amp; Gap Analysis</p><p><br></p><p>Key Initiatives with Timelines, Resource Requirements, and Success Metrics</p><p><br></p><p>Communication &amp; Change Management Strategy</p><p><br></p><p>Dashboard of Key HR &amp; Business Metrics</p><p><br></p><p>6.3 Final Presentation to the \"Executive Committee.\"</p><p><br></p><p>Teaching and Learning Methodology</p><p>Harvard-Style Case Studies: Analysis of real-world hospitality HR dilemmas (e.g., unionization drive, #MeToo crisis, mass turnover post-pandemic).</p><p><br></p><p>Strategic Workshops: Workforce planning simulations, compensation structure design, and policy drafting.</p><p><br></p><p>Legal &amp; Compliance Scenarios: Role-playing complex investigations and negotiations.</p><p><br></p><p>Data Analysis Labs: Working with sample HR metrics to diagnose problems and propose solutions.</p><p><br></p><p>Guest Leaders: Sessions with CHROs from major hotel brands and employment law attorneys.</p><p><br></p><p>Peer Consulting: Participants act as strategic HR consultants for each other\'s capstone projects.</p><p><br></p><p>Assessment Strategy</p><p>Capstone 3-Year Strategic Human Capital Plan: 40%</p><p><br></p><p>Case Study Analysis Papers (2 x 15%): 30%</p><p><br></p><p>Policy Governance &amp; Investigation Simulation: 20%</p><p><br></p><p>Class Participation &amp; Peer Feedback: 10%</p><p><br></p><p>Recommended Resources</p><p>Text: Human Resource Management in the Hospitality Industry by David K. Hayes and Jack D. Ninemeier.</p><p><br></p><p>Legal Resources: SHRM resources, updates on NLRB rulings, and ADA/FMLA guidelines.</p><p><br></p><p>Industry Data: AHLA workforce reports, turnover and compensation surveys (e.g., HVS, Aon).</p><p><br></p><p>Technology: Demonstrations of leading HRIS (Workday, Oracle HCM) analytics dashboards.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:45:55', '2026-01-15 16:56:25', NULL),
(45, 'Financial Leadership & Control Systems', 'Focuses on budgeting, financial reporting, audits, and strategic financial planning.', 'finance', 'Finance Manager / Controller', '\r\n                        <p style=\"text-align: center;\"><b><font size=\"7\">Financial Leadership &amp; Control Systems</font></b></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This executive-level module is designed for financial leaders in the hospitality industry, focusing on the strategic oversight and control of financial resources. It delves into advanced budgeting, forecasting, and financial reporting systems that drive business performance. Participants will master the design and implementation of internal controls, audit processes, and strategic financial planning frameworks to ensure fiscal integrity, optimize capital allocation, and create sustainable value for stakeholders.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Design and implement comprehensive, driver-based budgeting and rolling forecasting models that align operational activity with financial targets.</p><p><br></p><p>Synthesize and interpret complex financial reports and KPIs to provide strategic insights and recommendations to executive leadership and ownership.</p><p><br></p><p>Develop and govern robust internal control systems to safeguard assets, ensure financial accuracy, and mitigate operational and compliance risks.</p><p><br></p><p>Lead the strategic financial planning process, including capital budgeting, investment analysis, and long-term financial modeling for growth or restructuring.</p><p><br></p><p>Manage the audit process (internal and external) and effectively communicate financial performance and strategy to non-financial stakeholders.</p><p><br></p><p>Module Content</p><p>Unit 1: The Strategic Financial Leader in Hospitality (8 hours)</p><p>1.1 The CFO as Strategic Business Partner: Evolving from bean-counter to value creator and strategic advisor to the CEO/GM.</p><p><br></p><p>1.2 The Hospitality Financial Ecosystem: Understanding ownership structures (REITs, private equity, franchisors), management agreements, and lease dynamics.</p><p><br></p><p>1.3 Financial Leadership &amp; Communication: Translating complex financial data into compelling narratives for operations teams, ownership groups, and lenders.</p><p><br></p><p>1.4 Ethics, Governance &amp; Fiduciary Responsibility: Upholding the highest standards of financial integrity and transparency.</p><p><br></p><p>1.5 Technology &amp; Digital Finance: Leading the implementation of ERP systems, advanced analytics (BI tools), and automation in finance functions.</p><p><br></p><p>Unit 2: Advanced Budgeting, Forecasting &amp; Performance Management (12 hours)</p><p>2.1 Strategic Planning &amp; The Annual Operating Budget (AOB):</p><p><br></p><p>Top-down vs. bottom-up approaches. Linking the budget to the strategic plan.</p><p><br></p><p>Building driver-based budgets (e.g., covers per room, spa treatments per occupied room).</p><p><br></p><p>2.2 Rolling Forecasts &amp; Re-forecasting: Creating agile, quarterly rolling forecasts to adapt to market volatility. Scenario planning (best-case/worst-case).</p><p><br></p><p>2.3 Departmental Financial Management: Deep dive into major cost centers: rooms, F&amp;B, spa, golf. Analyzing and managing payroll, COGS, and other expenses.</p><p><br></p><p>2.4 Capital Expenditure (CapEx) Budgeting: Planning for FF&amp;E (Furniture, Fixtures &amp; Equipment), renovations, and technology investments. The reserve for replacement.</p><p><br></p><p>2.5 Variance Analysis &amp; Management Reporting: Developing insightful monthly commentary. Moving beyond \"variance to budget\" to \"root cause and corrective action.\"</p><p><br></p><p>Unit 3: Financial Reporting, Analysis &amp; KPI Architecture (8 hours)</p><p>3.1 Hospitality-Specific Financial Statements: Mastering the Uniform System of Accounts for the Lodging Industry (USALI).</p><p><br></p><p>3.2 Key Financial Ratios &amp; Benchmarking:</p><p><br></p><p>Profitability Ratios (GOPPAR, EBITDA, Net Operating Income - NOI)</p><p><br></p><p>Liquidity &amp; Solvency Ratios</p><p><br></p><p>Efficiency Ratios (RevPAR, TRevPAR, Flow-Through)</p><p><br></p><p>3.3 Creating a Management KPI Dashboard: Selecting and visualizing the 10-15 most critical metrics for executive decision-making.</p><p><br></p><p>3.4 Cash Flow Management &amp; Forecasting: The strategic importance of cash flow. Managing working capital (AR, AP, inventory).</p><p><br></p><p>3.5 Ownership &amp; Lender Reporting: Tailoring reports and presentations for different stakeholder needs (bank covenants, investor updates).</p><p><br></p><p>Unit 4: Internal Control Systems &amp; Audit Management (10 hours)</p><p>4.1 Designing an Effective Internal Control Framework: Principles of COSO (Committee of Sponsoring Organizations).</p><p><br></p><p>4.2 Control Activities for Key Hospitality Processes:</p><p><br></p><p>Revenue Cycle (Front Desk, POS, Night Audit)</p><p><br></p><p>Expenditure Cycle (Purchasing, Receiving, Payroll)</p><p><br></p><p>Inventory Cycle (F&amp;B, Retail, Operating Supplies)</p><p><br></p><p>4.3 Fraud Prevention, Detection &amp; Investigation: Common hospitality fraud schemes (skimming, phantom employees, kickbacks). Implementing controls and whistleblower policies.</p><p><br></p><p>4.4 Managing the Audit Process:</p><p><br></p><p>Internal Audit: Establishing the function, planning audits, and reporting findings.</p><p><br></p><p>External Audit: Preparing for and facilitating the annual audit. Liaising with auditors.</p><p><br></p><p>4.5 Compliance &amp; Regulatory Reporting: Ensuring compliance with tax regulations, sales &amp; use tax, and other statutory requirements.</p><p><br></p><p>Unit 5: Strategic Financial Planning &amp; Capital Strategy (6 hours)</p><p>5.1 Long-Range Financial Planning (LRFP): Developing 3-5 year financial models linked to strategic initiatives.</p><p><br></p><p>5.2 Investment Appraisal &amp; Capital Budgeting: Advanced techniques for evaluating projects: Net Present Value (NPV), Internal Rate of Return (IRR), and payback period.</p><p><br></p><p>5.3 Financing &amp; Capital Structure Strategy: Evaluating debt vs. equity financing. Refinancing strategies and negotiating with lenders.</p><p><br></p><p>5.4 Asset Management &amp; Disposition Strategy: Maximizing property value. Analyzing hold vs. sell decisions.</p><p><br></p><p>5.5 M&amp;A, Due Diligence &amp; Integration: The financial leader\'s role in mergers, acquisitions, and management contract negotiations.</p><p><br></p><p>Unit 6: Capstone Financial Leadership Simulation (2 hours)</p><p>6.1 The Challenge: Act as the CFO for a hotel portfolio facing a strategic crossroads (e.g., major renovation, market downturn, acquisition opportunity).</p><p><br></p><p>6.2 Deliverables:</p><p><br></p><p>A Strategic Financial Review memo analyzing current performance and risks.</p><p><br></p><p>A 3-Year Financial Forecast &amp; Capital Plan with multiple scenarios.</p><p><br></p><p>A Board Presentation Deck recommending a strategic course of action, supported by financial rationale and risk assessment.</p><p><br></p><p>6.3 Final Presentation to the \"Board of Directors.\"</p><p><br></p><p>Teaching and Learning Methodology</p><p>Financial Modeling Workshops: Hands-on Excel modeling for budgeting, forecasting, and investment appraisal.</p><p><br></p><p>Case Study Analysis: Real-world scenarios involving financial turnarounds, fraud cases, and capital investment decisions.</p><p><br></p><p>Audit Simulation: Participants walk through a mock audit of a revenue cycle, identifying control weaknesses.</p><p><br></p><p>Boardroom Role-Plays: Presenting financial results and strategic recommendations under pressure.</p><p><br></p><p>Guest Speakers: CFOs from major hotel groups, asset managers, and audit partners.</p><p><br></p><p>Software Exposure: Demonstrations of advanced hotel-specific accounting software (Oracle Hospitality, M3, etc.) and BI tools (Tableau, Power BI).</p><p><br></p><p>Assessment Strategy</p><p>Capstone Financial Leadership Simulation Package: 45%</p><p><br></p><p>Financial Model &amp; Budget Build Exercise: 25%</p><p><br></p><p>Internal Control Design &amp; Audit Case Study: 20%</p><p><br></p><p>Class Participation &amp; Peer Discussion Leadership: 10%</p><p><br></p><p>Required Resources &amp; Tools</p><p>Text: Hotel Asset Management: Principles &amp; Practices by David M. Stipanuk (sections on finance) and Uniform System of Accounts for the Lodging Industry (USALI).</p><p><br></p><p>Software: Advanced Excel proficiency is required. Exposure to financial modeling templates.</p><p><br></p><p>Industry Data: STR P&amp;L benchmark reports, PKF/Host reports, and valuation guidelines.</p><p><br></p><p>Regulatory Framework: Overview of SOX requirements for public companies and GAAP/IFRS principles relevant to hospitality.</p>                    ', NULL, NULL, NULL, 'posted', '', NULL, NULL, NULL, NULL, '2026-01-15 16:47:47', '2026-01-15 16:56:18', NULL),
(46, 'Facility Management & Engineering Leadership', 'Covers asset management, preventive maintenance planning, safety compliance, and team leadership.', 'engineering', 'Chief Engineer / Engineering Manager', '\r\n                        <p style=\"text-align: center;\"><b><font size=\"7\">Facility Management &amp; Engineering Leadership</font></b></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This advanced module equips facility and engineering leaders with the strategic and operational expertise to preserve and enhance the physical asset while optimizing life-cycle costs and ensuring uninterrupted operations. It focuses on developing a leadership mindset that balances technical knowledge with business acumen, emphasizing asset lifecycle management, data-driven preventive maintenance, rigorous safety and compliance systems, and effective leadership of a skilled technical team.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Develop and implement a comprehensive asset management strategy that aligns maintenance and capital planning with the property\'s financial and brand objectives.</p><p><br></p><p>Design, schedule, and manage a preventive maintenance program using technology to maximize equipment uptime, efficiency, and lifespan.</p><p><br></p><p>Establish and enforce a culture of safety, ensuring 100% compliance with all regulatory standards (OSHA, EPA, building codes) and leading emergency preparedness.</p><p><br></p><p>Lead, motivate, and develop a multi-skilled engineering team, manage contractors, and control departmental budgets and inventories.</p><p><br></p><p>Analyze energy and utility consumption to implement sustainability initiatives and cost-saving projects with a clear ROI.</p><p><br></p><p>Module Content</p><p>Unit 1: The Strategic Facility Leader (8 hours)</p><p>1.1 The Role of the Chief Engineer as a Business Leader: Moving from fixer to asset strategist and operational partner.</p><p><br></p><p>1.2 Asset Lifecycle Management Framework:</p><p><br></p><p>Understanding the total cost of ownership (TCO).</p><p><br></p><p>Capital Planning &amp; the Reserve for Replacement (RFR): Prioritizing projects and justifying capital requests.</p><p><br></p><p>1.3 Financial Acumen for Engineers: Reading P&amp;L statements, managing departmental budgets (payroll, inventory, contracts), and calculating ROI on projects.</p><p><br></p><p>1.4 Interdepartmental Leadership: Partnering effectively with GM, Rooms, F&amp;B, and Sales to support operations and guest experience.</p><p><br></p><p>1.5 Technology Strategy for Facilities: Evaluating and implementing CMMS (Computerized Maintenance Management Systems), IoT sensors, and building automation systems.</p><p><br></p><p>Unit 2: Strategic Asset &amp; Preventive Maintenance Planning (10 hours)</p><p>2.1 Developing the Master Maintenance Plan: Integrating daily, weekly, monthly, and annual tasks for all major systems (HVAC, plumbing, electrical, kitchen, laundry, pool).</p><p><br></p><p>2.2 Implementing a CMMS: Work order generation, scheduling, parts tracking, and performance reporting. Moving from reactive to predictive maintenance.</p><p><br></p><p>2.3 Critical Systems Deep Dive &amp; PM Optimization:</p><p><br></p><p>HVAC: Chiller/boiler maintenance, BMS optimization.</p><p><br></p><p>Kitchen Equipment: Critical PMs for health, safety, and uptime.</p><p><br></p><p>Laundry: Maximizing linen life and equipment efficiency.</p><p><br></p><p>2.4 Managing Backlog &amp; Prioritizing Work Orders: Balancing preventive tasks with guest-facing reactive repairs.</p><p><br></p><p>2.5 Inventory &amp; Parts Management: Establishing min/max levels, organizing storerooms, and controlling costs.</p><p><br></p><p>Unit 3: Safety, Compliance &amp; Risk Mitigation (8 hours)</p><p>3.1 Building a Proactive Safety Culture: Leading safety meetings, conducting job safety analyses (JSAs), and implementing behavior-based safety programs.</p><p><br></p><p>3.2 Regulatory Compliance Mastery:</p><p><br></p><p>OSHA standards (Lockout/Tagout, HazCom, Confined Space).</p><p><br></p><p>EPA regulations (refrigerant management, chemical handling).</p><p><br></p><p>Local building, fire, and health codes.</p><p><br></p><p>3.3 Life Safety Systems Leadership: In-depth knowledge and testing protocols for fire alarm, sprinkler, emergency power, and elevator recall systems.</p><p><br></p><p>3.4 Emergency Preparedness &amp; Business Continuity: Developing and drilling emergency plans for power failure, water outage, natural disasters, and critical system failures.</p><p><br></p><p>3.5 Contractor Management &amp; Site Safety: Vetting, supervising, and ensuring compliance for all third-party contractors on site.</p><p><br></p><p>Unit 4: Leading the Technical Team &amp; Operations (8 hours)</p><p>4.1 Leadership in a Technical Environment: Motivating and communicating with engineers, technicians, and grounds staff.</p><p><br></p><p>4.2 Talent Development &amp; Skills Matrix: Assessing team competencies, creating training plans, and developing future leaders.</p><p><br></p><p>4.3 Daily Operations &amp; Shift Management: Running effective shift briefings, dispatching, and managing the daily work flow.</p><p><br></p><p>4.4 Performance Management &amp; Accountability: Setting clear expectations, providing feedback, and managing performance issues within a union environment (if applicable).</p><p><br></p><p>4.5 Managing Major Projects &amp; Renovations: Acting as the owner\'s representative during renovations: planning, contractor oversight, budgeting, and minimizing guest disruption.</p><p><br></p><p>Unit 5: Energy Management &amp; Sustainability Strategy (6 hours)</p><p>5.1 Utility Budgeting &amp; Consumption Analysis: Benchmarking energy use (kBtu/sq ft), tracking trends, and identifying anomalies.</p><p><br></p><p>5.2 Conducting Energy Audits: Identifying top opportunities for savings in lighting, HVAC, water, and kitchen operations.</p><p><br></p><p>5.3 Implementing Sustainability Projects: Leading projects in LED retrofits, water conservation, waste reduction, and renewable energy. Calculating payback periods.</p><p><br></p><p>5.4 Green Building Certifications: Understanding frameworks like LEED, Green Key, and ENERGY STAR and their operational implications.</p><p><br></p><p>5.5 Guest-Facing Sustainability: Managing initiatives that enhance brand image without compromising comfort (linen reuse, EV charging stations).</p><p><br></p><p>Unit 6: Capstone Facility Strategic Plan (2 hours)</p><p>6.1 The Challenge: Develop a 3-Year Facility Strategic Plan for an aging hotel requiring a significant capital refresh and operational overhaul.</p><p><br></p><p>6.2 Plan Must Include:</p><p><br></p><p>Current State Analysis (Condition Audit of major systems)</p><p><br></p><p>Safety &amp; Compliance Gap Analysis</p><p><br></p><p>3-Year Capital Project Plan with Phasing &amp; Budget</p><p><br></p><p>Revised PM Program &amp; CMMS Implementation Roadmap</p><p><br></p><p>Energy &amp; Sustainability Initiative Plan</p><p><br></p><p>Team Development &amp; Training Strategy</p><p><br></p><p>6.3 Final Presentation to the \"Ownership Group.\"</p><p><br></p><p>Teaching and Learning Methodology</p><p>Technical Workshops: Hands-on sessions reading blueprints, conducting mock energy audits, and using CMMS software.</p><p><br></p><p>Case Studies: Analysis of major facility failures, successful renovation projects, and safety incidents.</p><p><br></p><p>Site Walk-Through Simulation: Using photos/videos of hotel systems to identify defects and prioritize repairs.</p><p><br></p><p>Leadership Role-Plays: Conducting safety briefings, performance reviews, and capital budget justifications.</p><p><br></p><p>Vendor &amp; Expert Sessions: Demos from CMMS providers, fire safety companies, and energy consultants.</p><p><br></p><p>Project-Based Learning: The capstone strategic plan integrates all technical and leadership components.</p><p><br></p><p>Assessment Strategy</p><p>Capstone 3-Year Facility Strategic Plan: 40%</p><p><br></p><p>Preventive Maintenance Program Design Project: 25%</p><p><br></p><p>Safety &amp; Compliance Audit Report: 20%</p><p><br></p><p>Leadership Simulation (Budget &amp; Team Meeting): 15%</p><p><br></p><p>Recommended Resources &amp; Tools</p><p>Text: Hotel Facility Management by David M. Stipanuk.</p><p><br></p><p>Standards: OSHA 1910/1926 guides, NFPA codes, EPA Section 608 certification materials.</p><p><br></p><p>Software: Demonstrations of leading CMMS (Fiix, Maintenance Connection, UpKeep) and energy management platforms.</p><p><br></p><p>Reference: ASHRAE handbooks for HVAC, plumbing, and refrigeration standards.</p>                    ', NULL, NULL, NULL, 'rejected', 'Not connected to the department and roles', NULL, NULL, NULL, NULL, '2026-01-15 16:49:29', '2026-01-15 16:55:16', NULL);
INSERT INTO `learning_modules` (`id`, `title`, `topic`, `department`, `roles`, `content`, `file_path`, `file_type`, `file_size`, `status`, `remarks`, `rejection_reason`, `compliance_reason`, `created_by`, `approved_by`, `created_at`, `updated_at`, `module_id`) VALUES
(47, 'Hospitality Security Management & Risk Control', 'Develops leadership in security planning, incident response, and safety compliance.', 'security', 'Security Manager / Supervisor', '\r\n                        <p style=\"text-align: center;\"><b><font size=\"7\">Hospitality Security Management &amp; Risk Control</font></b></p><p style=\"text-align: center;\"><br></p><p><br></p><p>Module Description</p><p>This advanced leadership module equips security and risk professionals with the strategic and operational expertise to protect guests, employees, assets, and brand reputation. It moves beyond traditional guard-post duties to focus on developing a comprehensive security strategy, designing proactive risk control systems, leading effective incident response, and ensuring organizational compliance with safety regulations. The module emphasizes leadership, crisis communication, and integrating security seamlessly into the guest experience.</p><p><br></p><p>Learning Outcomes</p><p>Upon successful completion, participants will be able to:</p><p><br></p><p>Develop, implement, and audit a comprehensive security management plan aligned with the property\'s size, location, and risk profile.</p><p><br></p><p>Lead all phases of incident response, from initial crisis management to investigation, documentation, and post-incident recovery.</p><p><br></p><p>Design and manage proactive risk control systems for key areas: life safety, asset protection, guest/employee safety, and cybersecurity.</p><p><br></p><p>Ensure 100% compliance with relevant safety, security, and regulatory standards (OSHA, fire codes, data privacy laws).</p><p><br></p><p>Build a security-conscious culture through effective leadership, training, and collaboration with internal departments and external agencies.</p><p><br></p><p>Module Content</p><p>Unit 1: The Strategic Security Leader (8 hours)</p><p>1.1 The Evolving Role: From Guard Force Manager to Chief Risk Officer. Security as a strategic business function protecting people, property, and profit.</p><p><br></p><p>1.2 Conducting a Comprehensive Risk Assessment: Identifying and prioritizing threats (crime, terrorism, natural disasters, internal theft, guest incidents, cyber).</p><p><br></p><p>1.3 Developing the Security Master Plan: A living document covering philosophy, organization, procedures, and technology.</p><p><br></p><p>1.4 Budgeting &amp; Resource Justification: Building a business case for security investments (technology, training, personnel). Calculating ROI through loss prevention.</p><p><br></p><p>1.5 Interdepartmental Integration: Building trusted partnerships with Front Office, Housekeeping, Engineering, HR, and Sales to create a unified safety net.</p><p><br></p><p>Unit 2: Security Operations &amp; Proactive Systems (10 hours)</p><p>2.1 Physical Security &amp; Access Control: Designing layered security (perimeter, building, key areas). Managing key card systems, CCTV analytics, and patrol routines.</p><p><br></p><p>2.2 Technology Strategy for Security: Evaluating and integrating systems: video surveillance (VMS), access control, intrusion detection, mass notification, and incident reporting software.</p><p><br></p><p>2.3 Asset Protection &amp; Loss Prevention:</p><p><br></p><p>Internal Controls for cash, inventory (F&amp;B, retail), and supplies.</p><p><br></p><p>Investigating internal theft (fraud, pilferage) and external theft.</p><p><br></p><p>2.4 Life Safety Systems Management: Deep collaboration with Engineering on fire alarm, sprinkler, emergency lighting, and PA systems. Conducting regular drills and inspections.</p><p><br></p><p>2.5 Guest Room &amp; Public Area Security: Protocols for guest room safety, in-room safes, parking lot security, and pool safety.</p><p><br></p><p>Unit 3: Incident Response &amp; Crisis Leadership (10 hours)</p><p>3.1 The Incident Command System (ICS) for Hospitality: Establishing clear command, control, and communication during a crisis.</p><p><br></p><p>3.2 Crisis Scenario Management: Step-by-step protocols for:</p><p><br></p><p>Medical Emergencies &amp; Death on Premises</p><p><br></p><p>Fire &amp; Natural Disasters</p><p><br></p><p>Violent Acts (Active Assailant, Terrorism)</p><p><br></p><p>Civil Unrest &amp; Protests</p><p><br></p><p>Missing Persons &amp; Child Abduction</p><p><br></p><p>3.3 Investigation &amp; Documentation: Conducting lawful and effective interviews, evidence preservation, report writing, and case management.</p><p><br></p><p>3.4 Crisis Communication: Internal communication (staff) and external communication (guests, media, families, authorities). The role of the security leader as a spokesperson.</p><p><br></p><p>3.5 Post-Incident Recovery &amp; Business Continuity: Debriefing, trauma support for staff/guests, system reviews, and resuming normal operations.</p><p><br></p><p>Unit 4: Compliance, Legal &amp; Human Factors (6 hours)</p><p>4.1 Legal Framework &amp; Liability: Understanding duty of care, negligence, premises liability, and use of force. Working effectively with law enforcement.</p><p><br></p><p>4.2 Regulatory Compliance: Adherence to OSHA (workplace violence prevention), fire codes, ADA, and local security ordinances.</p><p><br></p><p>4.3 Cybersecurity &amp; Data Privacy: Protecting guest PII (Personal Identifiable Information) and payment data. Responding to data breaches. Compliance with PCI-DSS and GDPR/CCPA.</p><p><br></p><p>4.4 Employee Safety &amp; Workplace Violence Prevention: Developing policies, threat assessment teams, and de-escalation training.</p><p><br></p><p>4.5 Managing Sensitive Situations: Handling VIPs, celebrities, dignitaries, and unruly guests with discretion and professionalism.</p><p><br></p><p>Unit 5: Building a Security-Conscious Culture (4 hours)</p><p>5.1 Leadership &amp; Team Development: Recruiting, training, and motivating security personnel. Ethical conduct and appearance.</p><p><br></p><p>5.2 Enterprise-Wide Security Training: Developing engaging training for all staff on security awareness, emergency procedures, and reporting protocols (\"See Something, Say Something\").</p><p><br></p><p>5.3 Measuring Security Performance: Key Performance Indicators (KPIs) such as incident frequency/severity, response times, training completion rates, and audit results.</p><p><br></p><p>5.4 Community &amp; Agency Relations: Building proactive relationships with local police, fire, EMS, and emergency management officials.</p><p><br></p><p>Unit 6: Capstone Crisis Simulation &amp; Security Audit (2 hours)</p><p>6.1 The Challenge: Participants face a multi-phase, unfolding crisis simulation (e.g., a weather event leading to a power outage, followed by a security incident).</p><p><br></p><p>6.2 Simulation Tasks: Must demonstrate command of ICS, make critical decisions, communicate effectively, and manage resources.</p><p><br></p><p>6.3 Deliverable: Submission of a Post-Incident After-Action Report and a Security Vulnerability Assessment for a hypothetical property.</p><p><br></p><p>6.4 Debrief &amp; Lessons Learned.</p><p><br></p><p>Teaching and Learning Methodology</p><p>Crisis Simulation Exercises: Immersive, real-time scenarios using role-players and simulated news feeds.</p><p><br></p><p>Table-Top Exercises: Walk-throughs of emergency plans for various scenarios.</p><p><br></p><p>Technology Demos: Hands-on with VMS, access control software, and incident reporting platforms.</p><p><br></p><p>Case Study Deep Dives: Analysis of high-profile hospitality security incidents (terrorist attacks, active shooters, major fraud).</p><p><br></p><p>Guest Experts: Sessions with former law enforcement, FBI specialists, cybersecurity experts, and crisis communication consultants.</p><p><br></p><p>Audit Workshops: Practicing how to conduct a physical security survey and risk assessment.</p><p><br></p><p>Assessment Strategy</p><p>Capstone Crisis Simulation Performance &amp; After-Action Report: 40%</p><p><br></p><p>Security Master Plan Outline &amp; Risk Assessment: 30%</p><p><br></p><p>Compliance &amp; Legal Case Study Analysis: 20%</p><p><br></p><p>Training Module Design Project: 10%</p><p><br></p><p>Recommended Resources</p><p>Text: Hotel Security: Managing for a Safe and Secure Operation by Harvey Burstein.</p><p><br></p><p>Standards: ASIS International resources (Chief Security Officer Guideline), NFPA life safety codes, PCI-DSS standards.</p><p><br></p><p>Government Resources: DHS resources for active shooter preparedness, FBI crime prevention publications.</p><p><br></p><p>Industry Groups: International Association of Hospitality Security &amp; Safety Professionals (IAHSS) standards and certification.</p>                    ', NULL, NULL, NULL, 'compliance', 'The Heading is too BIG', NULL, NULL, NULL, NULL, '2026-01-15 16:51:34', '2026-01-15 16:53:33', NULL),
(51, 'Rerum exercitation a', 'Qui facilis nisi sun', 'kitchen', 'Executive Chef / Head Chef', '\r\n                        <p>Start typing your document here...</p>                    ', NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '2026-01-24 04:23:14', '2026-01-24 04:23:14', NULL),
(52, 'Deserunt sint et et ', 'Velit ullam blandit', 'food-beverage', 'Select Role', '\r\n                        <p>Start typing your document here...</p>                    ', NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '2026-01-24 14:05:21', '2026-01-24 14:05:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `examination_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','essay','fill_blank') DEFAULT 'multiple_choice',
  `points` decimal(5,2) DEFAULT 1.00,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_logs`
--

CREATE TABLE `review_logs` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('module','exam') NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `action` enum('approved','rejected','for_compliance') NOT NULL,
  `remarks` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_department_name` (`name`);

--
-- Indexes for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_id` (`plan_id`),
  ADD KEY `idx_plan_employee` (`employee_id`),
  ADD KEY `idx_plan_status` (`status`),
  ADD KEY `idx_plan_dates` (`start_date`,`target_date`),
  ADD KEY `idx_development_plans_status` (`status`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_examinations_status` (`status`),
  ADD KEY `idx_examinations_department` (`department`),
  ADD KEY `idx_examinations_created_by` (`created_by`);

--
-- Indexes for table `examination_drafts`
--
ALTER TABLE `examination_drafts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `examination_questions`
--
ALTER TABLE `examination_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examination_id` (`examination_id`);

--
-- Indexes for table `examination_statistics`
--
ALTER TABLE `examination_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `examination_id` (`examination_id`);

--
-- Indexes for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_exam_assignments_examination_id` (`examination_id`),
  ADD KEY `idx_exam_assignments_employee_id` (`employee_id`),
  ADD KEY `idx_exam_assignments_status` (`status`);

--
-- Indexes for table `exam_audit_trail`
--
ALTER TABLE `exam_audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_question_options`
--
ALTER TABLE `exam_question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `exam_repository`
--
ALTER TABLE `exam_repository`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `original_exam_id` (`original_exam_id`);

--
-- Indexes for table `exam_repository_assignments`
--
ALTER TABLE `exam_repository_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_exam_audience_dept_role` (`exam_id`,`audience`,`department`,`role`),
  ADD KEY `idx_audience_dept_role` (`audience`,`department`,`role`);

--
-- Indexes for table `exam_repository_questions`
--
ALTER TABLE `exam_repository_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_id` (`exam_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_exam` (`employee_id`,`exam_id`),
  ADD KEY `idx_exam_id` (`exam_id`);

--
-- Indexes for table `exam_result_answers`
--
ALTER TABLE `exam_result_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_result_question` (`result_id`,`question_id`),
  ADD KEY `idx_result` (`result_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `exam_reviews`
--
ALTER TABLE `exam_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examination_id` (`examination_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `exam_review_logs`
--
ALTER TABLE `exam_review_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_violation_logs`
--
ALTER TABLE `exam_violation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam` (`exam_id`),
  ADD KEY `idx_taker` (`taker_id`);

--
-- Indexes for table `idp_learning_module_assignments`
--
ALTER TABLE `idp_learning_module_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_idp` (`idp_id`),
  ADD KEY `idx_emp` (`employee_id`),
  ADD KEY `idx_mod` (`module_id`);

--
-- Indexes for table `learning_modules`
--
ALTER TABLE `learning_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_examination_id` (`examination_id`);

--
-- Indexes for table `review_logs`
--
ALTER TABLE `review_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item` (`item_id`,`item_type`),
  ADD KEY `idx_reviewer` (`reviewer_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_reviewed_at` (`reviewed_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `development_plans`
--
ALTER TABLE `development_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `examination_drafts`
--
ALTER TABLE `examination_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examination_questions`
--
ALTER TABLE `examination_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `examination_statistics`
--
ALTER TABLE `examination_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_audit_trail`
--
ALTER TABLE `exam_audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_question_options`
--
ALTER TABLE `exam_question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_repository`
--
ALTER TABLE `exam_repository`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `exam_repository_assignments`
--
ALTER TABLE `exam_repository_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `exam_repository_questions`
--
ALTER TABLE `exam_repository_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `exam_result_answers`
--
ALTER TABLE `exam_result_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT for table `exam_reviews`
--
ALTER TABLE `exam_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_review_logs`
--
ALTER TABLE `exam_review_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_violation_logs`
--
ALTER TABLE `exam_violation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `idp_learning_module_assignments`
--
ALTER TABLE `idp_learning_module_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_modules`
--
ALTER TABLE `learning_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `review_logs`
--
ALTER TABLE `review_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `examination_questions`
--
ALTER TABLE `examination_questions`
  ADD CONSTRAINT `examination_questions_ibfk_1` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examination_statistics`
--
ALTER TABLE `examination_statistics`
  ADD CONSTRAINT `examination_statistics_ibfk_1` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_question_options`
--
ALTER TABLE `exam_question_options`
  ADD CONSTRAINT `exam_question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
