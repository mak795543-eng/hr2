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
-- Database: `schema_training_request`
--

-- --------------------------------------------------------

--
-- Table structure for table `department_heads`
--

CREATE TABLE `department_heads` (
  `department_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_heads`
--

INSERT INTO `department_heads` (`department_id`, `mentor_id`, `created_at`, `updated_at`) VALUES
(1, 4, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(2, 5, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(3, 6, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(4, 7, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(5, 8, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(6, 9, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(7, 10, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(8, 11, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(9, 12, '2026-01-22 10:37:11', '2026-01-22 10:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `department_managers`
--

CREATE TABLE `department_managers` (
  `department_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_managers`
--

INSERT INTO `department_managers` (`department_id`, `mentor_id`, `created_at`, `updated_at`) VALUES
(1, 13, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(2, 14, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(3, 15, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(4, 16, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(5, 17, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(6, 18, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(7, 19, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(8, 20, '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(9, 21, '2026-01-22 10:37:11', '2026-01-22 10:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `department_request_status_logs`
--

CREATE TABLE `department_request_status_logs` (
  `id` int(11) NOT NULL,
  `request_type` enum('financial','logistics','admin') NOT NULL,
  `request_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentors`
--

CREATE TABLE `mentors` (
  `id` int(11) NOT NULL,
  `mentor_name` varchar(150) NOT NULL,
  `expertise` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentors`
--

INSERT INTO `mentors` (`id`, `mentor_name`, `expertise`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'Leadership', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(2, 'Maria Santos', 'Technical Skills', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(3, 'Jose Reyes', 'Customer Service', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(4, 'Front Office / Reception Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(5, 'Housekeeping Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(6, 'Food & Beverage (F&B) Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(7, 'Kitchen / Culinary Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(8, 'Sales & Marketing Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(9, 'Human Resources (HR) Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(10, 'Finance / Accounting Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(11, 'Engineering / Maintenance Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(12, 'Security Department Head', 'Department Head', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(13, 'Front Office / Reception Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(14, 'Housekeeping Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(15, 'Food & Beverage (F&B) Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(16, 'Kitchen / Culinary Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(17, 'Sales & Marketing Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(18, 'Human Resources (HR) Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(19, 'Finance / Accounting Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(20, 'Engineering / Maintenance Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11'),
(21, 'Security Manager', 'Manager', '2026-01-22 10:37:11', '2026-01-22 10:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `training_evaluations`
--

CREATE TABLE `training_evaluations` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `employee_id` int(11) NOT NULL,
  `evaluator_name` varchar(150) DEFAULT NULL,
  `performance_rating` tinyint(4) NOT NULL,
  `attendance_rating` tinyint(4) NOT NULL,
  `overall_rating` tinyint(4) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_plan_evaluations`
--

CREATE TABLE `training_plan_evaluations` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `employee_id` int(11) NOT NULL,
  `evaluator_name` varchar(150) DEFAULT NULL,
  `plan_rating_percentage` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_posts`
--

CREATE TABLE `training_posts` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_posts`
--

INSERT INTO `training_posts` (`id`, `program_id`, `submission_no`, `posted_at`) VALUES
(1, 1, 1, '2026-01-23 00:02:05'),
(2, 3, 1, '2026-01-23 12:00:59'),
(3, 4, 1, '2026-01-24 10:46:26'),
(4, 5, 1, '2026-01-24 17:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `training_post_assignments`
--

CREATE TABLE `training_post_assignments` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `employee_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL,
  `training_title` varchar(255) NOT NULL,
  `training_type` varchar(50) NOT NULL,
  `training_mode` varchar(20) NOT NULL DEFAULT 'Onsite',
  `requested_by` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `target_audience` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `sub_department` varchar(150) DEFAULT NULL,
  `target_role` varchar(100) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `participants_needed` int(11) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `training_level` varchar(50) DEFAULT NULL,
  `training_objectives_json` text DEFAULT NULL,
  `training_objectives_other` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Under Review',
  `status_reason` text DEFAULT NULL,
  `need_budget` tinyint(1) NOT NULL DEFAULT 0,
  `need_items` tinyint(1) NOT NULL DEFAULT 0,
  `need_facility` tinyint(1) NOT NULL DEFAULT 0,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `financial_budget_amount` decimal(12,2) DEFAULT NULL,
  `financial_details_json` text DEFAULT NULL,
  `logistics_items_requested` text DEFAULT NULL,
  `logistics_details_json` text DEFAULT NULL,
  `admin_facility_details` text DEFAULT NULL,
  `admin_details_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_programs`
--

INSERT INTO `training_programs` (`id`, `training_title`, `training_type`, `training_mode`, `requested_by`, `description`, `target_audience`, `department_id`, `sub_department`, `target_role`, `mentor_id`, `category`, `participants_needed`, `max_participants`, `training_level`, `training_objectives_json`, `training_objectives_other`, `start_datetime`, `end_datetime`, `status`, `status_reason`, `need_budget`, `need_items`, `need_facility`, `submission_no`, `financial_budget_amount`, `financial_details_json`, `logistics_items_requested`, `logistics_details_json`, `admin_facility_details`, `admin_details_json`, `created_at`, `updated_at`) VALUES
(1, 'IDP Training - Mark Reyes', 'Workshop', 'Onsite', 'IDP', 'Food Preparation & Culinary Fundamentals:\r\n- Basic cooking techniques\r\n\r\nWaste Control & Cost Awareness:\r\n- Standard portioning', 'By Department', 4, NULL, 'Kitchen Helper', 7, 'IDP', 1, NULL, 'Retraining', '[\"Increase productivity\",\"Front desk check-in/check-out excellence\"]', 'lolo mo', '2030-03-17 23:22:00', '2030-06-26 16:48:00', 'POSTED', NULL, 0, 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-23 00:01:06', '2026-01-23 00:02:05'),
(2, 'IDP Training - Eric Ramos', 'Orientation', 'Onsite', 'IDP', 'Preventive Maintenance Skills:\r\n- Predictive maintenance tools\r\n\r\nProblem Diagnosis & Troubleshooting:\r\n- Diagnostic software tools\r\n\r\nTeam Coordination & Communication:\r\n- Project management tools', 'By Department', 8, NULL, 'Maintenance Technician', 11, 'IDP', 1, NULL, 'Reskilling', '[]', NULL, '2029-07-25 16:11:00', '2030-10-21 14:08:00', 'Under Review', NULL, 1, 1, 0, 2, 30000.00, '{\"basic\":{\"title\":\"IDP Training - Eric Ramos\",\"purpose\":\"transpo\",\"department\":\"8\",\"event_date\":\"2029-07-25\",\"justification\":\"steven\",\"remarks\":\"steven\"},\"items\":[{\"category\":\"Transport\",\"description\":\"transpo\",\"quantity\":4,\"unit_cost\":7500,\"remarks\":\"steven\"}],\"total_cost\":30000}', '[{\"category\":\"Supplies\",\"name\":\"steven\",\"quantity\":4,\"unit\":\"Pieces\",\"remarks\":\"steven\"}]', '{\"basic\":{\"title\":\"IDP Training - Eric Ramos\",\"purpose\":\"steven\",\"department\":\"8\",\"event_date\":\"2029-07-25\",\"needed_by_date\":\"2029-07-25\"},\"delivery\":{\"location\":\"steven\",\"contact_person\":\"steven\"},\"items\":[{\"category\":\"Supplies\",\"name\":\"steven\",\"quantity\":4,\"unit\":\"Pieces\",\"remarks\":\"steven\"}],\"remarks\":\"steven\"}', NULL, NULL, '2026-01-23 03:35:02', '2026-01-23 03:37:57'),
(3, 'IDP Training - Joyce Tan', 'Training', 'Online', 'IDP', 'Cultural Awareness & Guest Sensitivity:\r\n- International guest expectations\r\n\r\nEffective Communication Skills:\r\n- Email etiquette\r\n- phone handling\r\n- professional messaging', 'By Department', 1, NULL, 'Reservations Agent', 4, 'IDP', 1, NULL, 'Retraining', '[]', NULL, '2026-01-30 20:00:00', '2026-02-25 20:00:00', 'POSTED', NULL, 0, 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-23 12:00:33', '2026-01-23 12:00:59'),
(4, 'Iure consequat Ulla', 'Workshop', 'Hybrid', 'New Hire Onboarding', 'Pariatur Dolores si', 'Mentor', 6, NULL, 'Payroll / HR Assistant', 9, '', 87, 91, 'Retraining', '[\"Improve service quality\",\"Increase productivity\",\"Food handling and hygiene compliance\",\"Restaurant guest complaint handling\"]', 'Voluptatibus at cons', '2026-09-21 04:09:00', '2026-11-21 13:56:00', 'POSTED', NULL, 0, 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24 10:45:48', '2026-01-24 10:46:26'),
(5, 'IDP Training - Joyce Tan', 'Training', 'Online', 'IDP', 'Cultural Awareness & Guest Sensitivity:\r\n- International guest expectations\r\n\r\nEffective Communication Skills:\r\n- Email etiquette\r\n- phone handling\r\n- professional messaging', 'By Department', 1, NULL, 'Reservations Agent', 4, 'IDP', 1, NULL, 'Retraining', '[]', NULL, '2026-03-25 13:15:00', '2026-05-25 01:16:00', 'POSTED', NULL, 0, 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24 17:15:23', '2026-01-24 17:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `training_program_drafts`
--

CREATE TABLE `training_program_drafts` (
  `id` varchar(64) NOT NULL,
  `session_key` varchar(128) NOT NULL,
  `title` varchar(255) NOT NULL,
  `data_json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_program_drafts`
--

INSERT INTO `training_program_drafts` (`id`, `session_key`, `title`, `data_json`, `created_at`, `updated_at`) VALUES
('d_1769118014781_0565d61ec89a2', 'sess:945f8v2mns4a19pcpvb97qa6ln', 'Untitled Training', '{\"form\":{\"training_title\":\"\",\"training_type\":\"\",\"training_mode\":\"Onsite\",\"requested_by\":\"\",\"category\":\"\",\"description\":\"\",\"target_audience\":\"\",\"department_id\":\"\",\"sub_department\":\"\",\"target_role\":\"\",\"employee_id\":\"\",\"mentor_id\":\"\",\"participants_needed\":\"1\",\"max_participants\":\"1\",\"training_level\":\"Reskilling\",\"objectives\":[],\"objectives_other\":\"\",\"start_date\":\"2026-01-23\",\"start_time\":\"09:00\",\"end_date\":\"2026-01-23\",\"end_time\":\"17:00\",\"need_budget\":\"0\",\"need_items\":\"0\",\"need_facility\":\"0\"},\"requestDraft\":{\"budget\":{\"completed\":false,\"basic\":{\"title\":\"\",\"purpose\":\"\",\"department\":\"\",\"event_date\":\"\",\"justification\":\"\",\"remarks\":\"\"},\"items\":[],\"total_cost\":0},\"logistics\":{\"completed\":false,\"basic\":{\"title\":\"\",\"purpose\":\"\",\"department\":\"\",\"event_date\":\"\",\"needed_by_date\":\"\"},\"items\":[],\"delivery\":{\"location\":\"\",\"contact_person\":\"\"},\"remarks\":\"\"},\"facility\":{\"completed\":false,\"basic\":{\"title\":\"\",\"purpose\":\"\",\"department\":\"\",\"event_date\":\"\"},\"location\":{\"preferred_location\":\"\",\"start_time\":\"\",\"end_time\":\"\"},\"special_requirements\":\"\",\"remarks\":\"\"}}}', '2026-01-22 21:40:14', '2026-01-22 21:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `training_program_evaluations`
--

CREATE TABLE `training_program_evaluations` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `submission_no` int(11) NOT NULL DEFAULT 1,
  `evaluator_name` varchar(150) DEFAULT NULL,
  `overall_percentage` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_program_evaluations`
--

INSERT INTO `training_program_evaluations` (`id`, `program_id`, `submission_no`, `evaluator_name`, `overall_percentage`, `remarks`, `created_at`) VALUES
(1, 1, 1, 'sess:945f8v2mns4a19pcpvb97qa6ln', 63.70, 'Consequatur est est', '2026-01-23 22:07:14'),
(2, 4, 1, 'sess:fig59a2eadveg3pvvb3mn09id8', 52.00, '', '2026-01-24 10:46:49'),
(3, 3, 1, 'Front Office / Reception Department Head', 43.33, 'Corporis aliquid ut', '2026-01-24 10:57:48'),
(4, 4, 1, 'Human Resources (HR) Department Head', 98.00, 'Est voluptatum magna', '2026-01-24 11:05:15'),
(5, 1, 1, 'Kitchen / Culinary Department Head', 41.70, 'Nam dolor ipsum con', '2026-01-24 11:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `training_program_evaluation_items`
--

CREATE TABLE `training_program_evaluation_items` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `item_type` varchar(30) NOT NULL,
  `item_key` varchar(64) NOT NULL,
  `item_label` text NOT NULL,
  `rating_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_program_evaluation_items`
--

INSERT INTO `training_program_evaluation_items` (`id`, `evaluation_id`, `item_type`, `item_key`, `item_label`, `rating_percentage`, `created_at`) VALUES
(1, 1, 'development_plan', 'a0b61ae9d5268f96150895c4007149e5c77b304fd15b5a883a6908c0349d4868', 'Basic cooking techniques', 75.00, '2026-01-23 22:07:14'),
(2, 1, 'development_plan', '785ee265b4298fa8f9cc7e6356eec6f6348c7a30de344551a4a21acd06227897', 'Standard portioning', 88.00, '2026-01-23 22:07:14'),
(3, 1, 'general_skill', 'a380324d805b72b5c2f5939d5c38a59ed8e14073999830039d7d833880edea1e', 'Attention to Quality & Presentation', 40.00, '2026-01-23 22:07:14'),
(4, 1, 'general_skill', '878544b35cd45930eb5eadb7d65fec1034b7a5c0f0d2174d3522eed52e6cda50', 'Equipment Handling & Safety Awareness', 30.00, '2026-01-23 22:07:14'),
(5, 1, 'general_skill', '7984bea38695fe82f49fa99e50099d26fd5da3c9e9bc4a17d64a51db5f82406a', 'Food Preparation & Culinary Fundamentals', 6.00, '2026-01-23 22:07:14'),
(6, 1, 'general_skill', '55d95c127b313463306e5f98dc443a8fda76bf660dc36edc2b42b66b2a3b4ab5', 'Food Safety & Sanitation Compliance', 98.00, '2026-01-23 22:07:14'),
(7, 1, 'general_skill', '7115191bc6b6c1b97019f337c50f1fa5bfff7dbbd08b90711480b1723706a418', 'Sample General Skill', 85.00, '2026-01-23 22:07:14'),
(8, 1, 'general_skill', 'd03cfa1093a217a782e3be54fbc4102198c738d2fa3a8d567aaf56df323d13cf', 'Teamwork & Kitchen Coordination', 91.00, '2026-01-23 22:07:14'),
(9, 1, 'general_skill', '7b46dda48ec6ed48301de90e0ce177e89dec5d4717010576c0c61ff80aaf749e', 'Time & Workflow Management', 62.00, '2026-01-23 22:07:14'),
(10, 1, 'general_skill', '52aa8cb82320da449b9e1b48cf2a52f38eb550161aaabc143a000381c91c24ec', 'Waste Control & Cost Awareness', 62.00, '2026-01-23 22:07:14'),
(11, 2, 'development_plan', 'a70de51b68494d481f48c04e9afbe5c6acb0e596efcdc4b2406aa8e5e21bbd57', 'Pariatur Dolores si', 52.00, '2026-01-24 10:46:49'),
(12, 3, 'development_plan', 'a717970ffb2e996bf8964e7dd7e8a6b2df76913be638bbd73fcc93ab1a698f84', 'International guest expectations', 57.00, '2026-01-24 10:57:48'),
(13, 3, 'development_plan', '6b84747c38fd36cafe5c099905406a433f6349065936c86ff437e10cd3387a4d', 'Email etiquette', 15.00, '2026-01-24 10:57:48'),
(14, 3, 'development_plan', 'ab857701ecf66681c3c1e1105ac569b460f6461c37eb07524325e73609e5c629', 'phone handling', 82.00, '2026-01-24 10:57:48'),
(15, 3, 'development_plan', '641ecf39131e959e1bf090af9e7248ef4c9d3542cb3a6c3cf820d3c2d1b951bf', 'professional messaging', 5.00, '2026-01-24 10:57:48'),
(16, 3, 'general_skill', 'c5cdaa71909731e1984ebad1d5ab1ecfc641bdcfb3d1b5b3ee1e5ea966912e24', 'Cultural Awareness & Guest Sensitivity', 85.00, '2026-01-24 10:57:48'),
(17, 3, 'general_skill', 'd889651817f7c1f7f7fbd201d31582f49bb0fd1fa8281ef87ed49d43c664f702', 'Customer Service Excellence', 20.00, '2026-01-24 10:57:48'),
(18, 3, 'general_skill', 'fc63f8f047e58381280117a378b8fad9ab0b653b17418e3848e36fc75314c76b', 'Effective Communication Skills', 90.00, '2026-01-24 10:57:48'),
(19, 3, 'general_skill', 'd054c387b6a6cc724e919668770c7a6d9d8c3ac1a62b0d0567709c2a098d82db', 'Hotel Systems & Reservation Knowledge', 9.00, '2026-01-24 10:57:48'),
(20, 3, 'general_skill', 'ae780c8e35287ee0c28571ae96141c4a34a407c1118e496a575e0a8c22b57301', 'Problem-Solving & Complaint Handling', 20.00, '2026-01-24 10:57:48'),
(21, 3, 'general_skill', 'c348b04ee1103953277884f33ca88e406359f2088d73219d2793e7329abdc6d7', 'Professional Appearance & Etiquette', 80.00, '2026-01-24 10:57:48'),
(22, 3, 'general_skill', '2a707e96bfe9ecdc4449c1b8de14a14d44aee1e530419a7545a877a0a7a6c442', 'Sample General Skill', 31.00, '2026-01-24 10:57:48'),
(23, 3, 'general_skill', '314d0514855c46b9ff7072dabc510ca515a36eaad0ea9fde70caea41e6b4ea71', 'Time Management & Multitasking', 26.00, '2026-01-24 10:57:48'),
(24, 4, 'development_plan', 'a70de51b68494d481f48c04e9afbe5c6acb0e596efcdc4b2406aa8e5e21bbd57', 'Pariatur Dolores si', 98.00, '2026-01-24 11:05:15'),
(25, 5, 'development_plan', 'a0b61ae9d5268f96150895c4007149e5c77b304fd15b5a883a6908c0349d4868', 'Basic cooking techniques', 59.00, '2026-01-24 11:23:11'),
(26, 5, 'development_plan', '785ee265b4298fa8f9cc7e6356eec6f6348c7a30de344551a4a21acd06227897', 'Standard portioning', 86.00, '2026-01-24 11:23:11'),
(27, 5, 'general_skill', 'a380324d805b72b5c2f5939d5c38a59ed8e14073999830039d7d833880edea1e', 'Attention to Quality & Presentation', 65.00, '2026-01-24 11:23:11'),
(28, 5, 'general_skill', '878544b35cd45930eb5eadb7d65fec1034b7a5c0f0d2174d3522eed52e6cda50', 'Equipment Handling & Safety Awareness', 70.00, '2026-01-24 11:23:11'),
(29, 5, 'general_skill', '7984bea38695fe82f49fa99e50099d26fd5da3c9e9bc4a17d64a51db5f82406a', 'Food Preparation & Culinary Fundamentals', 12.00, '2026-01-24 11:23:11'),
(30, 5, 'general_skill', '55d95c127b313463306e5f98dc443a8fda76bf660dc36edc2b42b66b2a3b4ab5', 'Food Safety & Sanitation Compliance', 13.00, '2026-01-24 11:23:11'),
(31, 5, 'general_skill', '7115191bc6b6c1b97019f337c50f1fa5bfff7dbbd08b90711480b1723706a418', 'Sample General Skill', 42.00, '2026-01-24 11:23:11'),
(32, 5, 'general_skill', 'd03cfa1093a217a782e3be54fbc4102198c738d2fa3a8d567aaf56df323d13cf', 'Teamwork & Kitchen Coordination', 14.00, '2026-01-24 11:23:11'),
(33, 5, 'general_skill', '7b46dda48ec6ed48301de90e0ce177e89dec5d4717010576c0c61ff80aaf749e', 'Time & Workflow Management', 35.00, '2026-01-24 11:23:11'),
(34, 5, 'general_skill', '52aa8cb82320da449b9e1b48cf2a52f38eb550161aaabc143a000381c91c24ec', 'Waste Control & Cost Awareness', 21.00, '2026-01-24 11:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `training_program_status_logs`
--

CREATE TABLE `training_program_status_logs` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_program_status_logs`
--

INSERT INTO `training_program_status_logs` (`id`, `program_id`, `old_status`, `new_status`, `reason`, `created_at`) VALUES
(1, 1, 'Under Review', 'Approved', NULL, '2026-01-23 00:01:37'),
(2, 2, 'Under Review', 'Approved', NULL, '2026-01-23 03:35:23'),
(3, 2, 'Approved', 'Under Review', 'Edited', '2026-01-23 03:37:57'),
(4, 3, 'Under Review', 'Approved', NULL, '2026-01-23 12:00:44'),
(5, 4, 'Under Review', 'Approved', NULL, '2026-01-24 10:46:14'),
(6, 5, 'Under Review', 'Approved', NULL, '2026-01-24 17:16:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `department_heads`
--
ALTER TABLE `department_heads`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `idx_dh_mentor` (`mentor_id`);

--
-- Indexes for table `department_managers`
--
ALTER TABLE `department_managers`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `idx_dm_mentor` (`mentor_id`);

--
-- Indexes for table `department_request_status_logs`
--
ALTER TABLE `department_request_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drl_program` (`program_id`),
  ADD KEY `idx_drl_type` (`request_type`),
  ADD KEY `idx_drl_created` (`created_at`);

--
-- Indexes for table `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_mentor_name` (`mentor_name`);

--
-- Indexes for table `training_evaluations`
--
ALTER TABLE `training_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_te_program` (`program_id`,`submission_no`),
  ADD KEY `idx_te_employee` (`employee_id`);

--
-- Indexes for table `training_plan_evaluations`
--
ALTER TABLE `training_plan_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tpe` (`program_id`,`submission_no`,`employee_id`,`evaluator_name`),
  ADD KEY `idx_tpe_program` (`program_id`,`submission_no`),
  ADD KEY `idx_tpe_employee` (`employee_id`);

--
-- Indexes for table `training_posts`
--
ALTER TABLE `training_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_training_post` (`program_id`,`submission_no`),
  ADD KEY `idx_tp_program` (`program_id`);

--
-- Indexes for table `training_post_assignments`
--
ALTER TABLE `training_post_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tpa` (`program_id`,`submission_no`,`employee_id`),
  ADD KEY `idx_tpa_program` (`program_id`),
  ADD KEY `idx_tpa_employee` (`employee_id`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_program_drafts`
--
ALTER TABLE `training_program_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tpd_session` (`session_key`),
  ADD KEY `idx_tpd_updated` (`updated_at`);

--
-- Indexes for table `training_program_evaluations`
--
ALTER TABLE `training_program_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tpe` (`program_id`,`submission_no`,`evaluator_name`),
  ADD KEY `idx_tpe_program` (`program_id`,`submission_no`);

--
-- Indexes for table `training_program_evaluation_items`
--
ALTER TABLE `training_program_evaluation_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_item` (`evaluation_id`,`item_key`),
  ADD KEY `idx_item_eval` (`evaluation_id`);

--
-- Indexes for table `training_program_status_logs`
--
ALTER TABLE `training_program_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tpl_program` (`program_id`),
  ADD KEY `idx_tpl_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `department_request_status_logs`
--
ALTER TABLE `department_request_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT for table `training_evaluations`
--
ALTER TABLE `training_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_plan_evaluations`
--
ALTER TABLE `training_plan_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_posts`
--
ALTER TABLE `training_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `training_post_assignments`
--
ALTER TABLE `training_post_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `training_program_evaluations`
--
ALTER TABLE `training_program_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `training_program_evaluation_items`
--
ALTER TABLE `training_program_evaluation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `training_program_status_logs`
--
ALTER TABLE `training_program_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_heads`
--
ALTER TABLE `department_heads`
  ADD CONSTRAINT `fk_department_heads_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `department_managers`
--
ALTER TABLE `department_managers`
  ADD CONSTRAINT `fk_department_managers_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_program_evaluation_items`
--
ALTER TABLE `training_program_evaluation_items`
  ADD CONSTRAINT `fk_tpei_eval` FOREIGN KEY (`evaluation_id`) REFERENCES `training_program_evaluations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_program_status_logs`
--
ALTER TABLE `training_program_status_logs`
  ADD CONSTRAINT `fk_tpl_program` FOREIGN KEY (`program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
