-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 24, 2026 at 06:56 PM
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
-- Database: `critical_gaps`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Front Office / Reception', '2026-01-22 09:04:37'),
(2, 'Housekeeping', '2026-01-22 09:04:37'),
(3, 'Food & Beverage (F&B)', '2026-01-22 09:04:37'),
(4, 'Kitchen / Culinary', '2026-01-22 09:04:37'),
(5, 'Human Resources (HR)', '2026-01-22 09:04:37'),
(10, 'Sales & Marketing', '2026-01-22 09:10:32'),
(12, 'Finance / Accounting', '2026-01-22 09:10:32'),
(13, 'Engineering / Maintenance', '2026-01-22 09:10:32'),
(14, 'Security', '2026-01-22 09:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `development_plan_items`
--

CREATE TABLE `development_plan_items` (
  `id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL DEFAULT '',
  `skill_id` int(11) NOT NULL,
  `status` enum('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL,
  `plan_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_mode` enum('Onsite','Online') NOT NULL DEFAULT 'Onsite',
  `target_percentage` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `development_plan_items`
--

INSERT INTO `development_plan_items` (`id`, `department`, `role`, `skill_id`, `status`, `plan_text`, `created_at`, `updated_at`, `delivery_mode`, `target_percentage`) VALUES
(1, 'Front Office / Reception', '', 6, 'Retrain', 'Customer service policies, routine guest interactions, etiquette refresh', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(2, 'Front Office / Reception', '', 6, 'Refresher Training', 'Role-playing difficult scenarios, updated service standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(3, 'Front Office / Reception', '', 6, 'Reskilling', 'Digital concierge tools, online guest interactions', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(4, 'Front Office / Reception', '', 6, 'Upskilling', 'VIP protocols, personalized guest experience strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(5, 'Front Office / Reception', '', 6, 'Succession Ready', 'Leading guest service operations, mentoring junior staff', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(6, 'Front Office / Reception', '', 7, 'Retrain', 'Verbal and written communication workshops', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(7, 'Front Office / Reception', '', 7, 'Refresher Training', 'Email etiquette, phone handling, professional messaging', '2026-01-22 09:11:48', '2026-01-23 04:43:45', 'Online', NULL),
(8, 'Front Office / Reception', '', 7, 'Reskilling', 'Social media and digital communication skills', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(9, 'Front Office / Reception', '', 7, 'Upskilling', 'Public speaking, cross-cultural communication', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(10, 'Front Office / Reception', '', 7, 'Succession Ready', 'Leading team briefings, executive guest interactions', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(11, 'Front Office / Reception', '', 8, 'Retrain', 'Standard complaint handling', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(12, 'Front Office / Reception', '', 8, 'Refresher Training', 'Case studies and escalation procedures', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(13, 'Front Office / Reception', '', 8, 'Reskilling', 'Data-driven problem solving', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(14, 'Front Office / Reception', '', 8, 'Upskilling', 'Advanced negotiation and emotional intelligence', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(15, 'Front Office / Reception', '', 8, 'Succession Ready', 'Policy development, service improvement leadership', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(16, 'Front Office / Reception', '', 9, 'Retrain', 'PMS basics, reservation logging', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(17, 'Front Office / Reception', '', 9, 'Refresher Training', 'Updated booking systems, troubleshooting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(18, 'Front Office / Reception', '', 9, 'Reskilling', 'Channel management, online bookings', '2026-01-22 09:11:48', '2026-01-23 04:26:58', 'Online', 90.00),
(19, 'Front Office / Reception', '', 9, 'Upskilling', 'Data reporting, guest analytics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(20, 'Front Office / Reception', '', 9, 'Succession Ready', 'Leading system training and technology upgrades', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(21, 'Front Office / Reception', '', 10, 'Retrain', 'Uniform standards, grooming, basic etiquette', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(22, 'Front Office / Reception', '', 10, 'Refresher Training', 'Updated guest interaction protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(23, 'Front Office / Reception', '', 10, 'Reskilling', 'Cultural etiquette for international guests', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(24, 'Front Office / Reception', '', 10, 'Upskilling', 'Executive presentation, VIP hosting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(25, 'Front Office / Reception', '', 10, 'Succession Ready', 'Coaching staff on professional standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(26, 'Front Office / Reception', '', 11, 'Retrain', 'Prioritizing daily tasks', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(27, 'Front Office / Reception', '', 11, 'Refresher Training', 'Efficient check-in/out', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(28, 'Front Office / Reception', '', 11, 'Reskilling', 'Digital scheduling tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(29, 'Front Office / Reception', '', 11, 'Upskilling', 'High-volume multitasking strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(30, 'Front Office / Reception', '', 11, 'Succession Ready', 'Leading front office workflow optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(31, 'Front Office / Reception', '', 12, 'Retrain', 'Basic diversity awareness', '2026-01-22 09:11:48', '2026-01-23 04:25:21', 'Online', 90.00),
(32, 'Front Office / Reception', '', 12, 'Refresher Training', 'International guest expectations', '2026-01-22 09:11:48', '2026-01-23 04:43:29', 'Online', NULL),
(33, 'Front Office / Reception', '', 12, 'Reskilling', 'Basic language skills', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(34, 'Front Office / Reception', '', 12, 'Upskilling', 'Inclusion strategies, VIP profiling', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(35, 'Front Office / Reception', '', 12, 'Succession Ready', 'Cross-cultural training leadership', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(36, 'Housekeeping', '', 13, 'Retrain', 'Room cleaning standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(37, 'Housekeeping', '', 13, 'Refresher Training', 'Spot checks, visual inspection', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(38, 'Housekeeping', '', 13, 'Reskilling', 'Digital tracking tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(39, 'Housekeeping', '', 13, 'Upskilling', 'Quality auditing, performance metrics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(40, 'Housekeeping', '', 13, 'Succession Ready', 'Supervising quality control', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(41, 'Housekeeping', '', 14, 'Retrain', 'Correct chemical and tool usage', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(42, 'Housekeeping', '', 14, 'Refresher Training', 'Updated chemicals and safety', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(43, 'Housekeeping', '', 14, 'Reskilling', 'Eco-friendly cleaning methods', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(44, 'Housekeeping', '', 14, 'Upskilling', 'Process optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(45, 'Housekeeping', '', 14, 'Succession Ready', 'Leading procedural updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(46, 'Housekeeping', '', 15, 'Retrain', 'Standard room turnaround', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(47, 'Housekeeping', '', 15, 'Refresher Training', 'Prioritization at peak occupancy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(48, 'Housekeeping', '', 15, 'Reskilling', 'Workflow scheduling tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(49, 'Housekeeping', '', 15, 'Upskilling', 'Advanced task management', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(50, 'Housekeeping', '', 15, 'Succession Ready', 'Planning housekeeping schedules', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(51, 'Housekeeping', '', 16, 'Retrain', 'Fire, hazard, sanitation basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(52, 'Housekeeping', '', 16, 'Refresher Training', 'Updated occupational safety', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(53, 'Housekeeping', '', 16, 'Reskilling', 'Emergency drills, accident prevention', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(54, 'Housekeeping', '', 16, 'Upskilling', 'Safety audits, compliance leadership', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(55, 'Housekeeping', '', 16, 'Succession Ready', 'Policy creation and team safety training', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(56, 'Housekeeping', '', 17, 'Retrain', 'Collaboration exercises', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(57, 'Housekeeping', '', 17, 'Refresher Training', 'Cross-department interaction', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(58, 'Housekeeping', '', 17, 'Reskilling', 'Conflict resolution, collaborative tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(59, 'Housekeeping', '', 17, 'Upskilling', 'Resource allocation and efficiency', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(60, 'Housekeeping', '', 17, 'Succession Ready', 'Mentoring supervisors', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(61, 'Housekeeping', '', 18, 'Retrain', 'Correct posture, lifting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(62, 'Housekeeping', '', 18, 'Refresher Training', 'Ergonomics and repetitive task safety', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(63, 'Housekeeping', '', 18, 'Reskilling', 'Automation tools to reduce strain', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(64, 'Housekeeping', '', 18, 'Upskilling', 'Process efficiency, workload management', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(65, 'Housekeeping', '', 18, 'Succession Ready', 'Coaching and long-term health practices', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(66, 'Housekeeping', '', 19, 'Retrain', 'Standard inspections', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(67, 'Housekeeping', '', 19, 'Refresher Training', 'Audit updates, scoring systems', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(68, 'Housekeeping', '', 19, 'Reskilling', 'Data analysis for recurring issues', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(69, 'Housekeeping', '', 19, 'Upskilling', 'Advanced QC techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(70, 'Housekeeping', '', 19, 'Succession Ready', 'Leading audits and improvement initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(71, 'Food & Beverage (F&B)', '', 20, 'Retrain', 'Basic service protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(72, 'Food & Beverage (F&B)', '', 20, 'Refresher Training', 'Handling complaints, upselling techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(73, 'Food & Beverage (F&B)', '', 20, 'Reskilling', 'Digital ordering systems', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(74, 'Food & Beverage (F&B)', '', 20, 'Upskilling', 'Fine dining and VIP service', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(75, 'Food & Beverage (F&B)', '', 20, 'Succession Ready', 'Leading service standards and mentoring staff', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(76, 'Food & Beverage (F&B)', '', 21, 'Retrain', 'Basic food safety rules', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(77, 'Food & Beverage (F&B)', '', 21, 'Refresher Training', 'Updated sanitation standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(78, 'Food & Beverage (F&B)', '', 21, 'Reskilling', 'HACCP and allergen management', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(79, 'Food & Beverage (F&B)', '', 21, 'Upskilling', 'Advanced hygiene audits', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(80, 'Food & Beverage (F&B)', '', 21, 'Succession Ready', 'Policy leadership, team training', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(81, 'Food & Beverage (F&B)', '', 22, 'Retrain', 'Menu item review', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(82, 'Food & Beverage (F&B)', '', 22, 'Refresher Training', 'Ingredient updates, new dishes', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(83, 'Food & Beverage (F&B)', '', 22, 'Reskilling', 'Digital POS systems, inventory tracking', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(84, 'Food & Beverage (F&B)', '', 22, 'Upskilling', 'Beverage pairing, wine/spirits expertise', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(85, 'Food & Beverage (F&B)', '', 22, 'Succession Ready', 'Menu strategy and training leadership', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(86, 'Food & Beverage (F&B)', '', 23, 'Retrain', 'Kitchen-service coordination basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(87, 'Food & Beverage (F&B)', '', 23, 'Refresher Training', 'Shift handovers, reporting issues', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(88, 'Food & Beverage (F&B)', '', 23, 'Reskilling', 'Team collaboration tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(89, 'Food & Beverage (F&B)', '', 23, 'Upskilling', 'Conflict resolution, high-pressure communication', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(90, 'Food & Beverage (F&B)', '', 23, 'Succession Ready', 'Leading service and kitchen coordination', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(91, 'Food & Beverage (F&B)', '', 24, 'Retrain', 'Standard upselling techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(92, 'Food & Beverage (F&B)', '', 24, 'Refresher Training', 'Promotional campaigns, suggestive selling', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(93, 'Food & Beverage (F&B)', '', 24, 'Reskilling', 'Digital sales tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(94, 'Food & Beverage (F&B)', '', 24, 'Upskilling', 'Revenue maximization strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(95, 'Food & Beverage (F&B)', '', 24, 'Succession Ready', 'Sales strategy planning, coaching juniors', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(96, 'Food & Beverage (F&B)', '', 25, 'Retrain', 'Task prioritization under normal service', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(97, 'Food & Beverage (F&B)', '', 25, 'Refresher Training', 'Peak hour handling techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(98, 'Food & Beverage (F&B)', '', 25, 'Reskilling', 'Workflow optimization tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(99, 'Food & Beverage (F&B)', '', 25, 'Upskilling', 'Advanced multitasking strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(100, 'Food & Beverage (F&B)', '', 25, 'Succession Ready', 'Leading efficiency improvement initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(101, 'Food & Beverage (F&B)', '', 26, 'Retrain', 'Service manners', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(102, 'Food & Beverage (F&B)', '', 26, 'Refresher Training', 'Modern etiquette updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(103, 'Food & Beverage (F&B)', '', 26, 'Reskilling', 'Cultural service standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(104, 'Food & Beverage (F&B)', '', 26, 'Upskilling', 'VIP hosting and fine dining protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(105, 'Food & Beverage (F&B)', '', 26, 'Succession Ready', 'Mentoring staff, enforcing service excellence', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(106, 'Kitchen / Culinary', '', 27, 'Retrain', 'Basic cooking techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(107, 'Kitchen / Culinary', '', 27, 'Refresher Training', 'Recipe updates, portion control', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(108, 'Kitchen / Culinary', '', 27, 'Reskilling', 'Modern cooking methods, equipment use', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(109, 'Kitchen / Culinary', '', 27, 'Upskilling', 'Advanced culinary techniques, plating design', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(110, 'Kitchen / Culinary', '', 27, 'Succession Ready', 'Leading kitchen innovation and mentoring chefs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(111, 'Kitchen / Culinary', '', 28, 'Retrain', 'Hygiene basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(112, 'Kitchen / Culinary', '', 28, 'Refresher Training', 'HACCP updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(113, 'Kitchen / Culinary', '', 28, 'Reskilling', 'Allergen and dietary compliance', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(114, 'Kitchen / Culinary', '', 28, 'Upskilling', 'Kitchen audits, safety inspections', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(115, 'Kitchen / Culinary', '', 28, 'Succession Ready', 'Policy leadership, training kitchen staff', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(116, 'Kitchen / Culinary', '', 29, 'Retrain', 'Standard prep and service timings', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(117, 'Kitchen / Culinary', '', 29, 'Refresher Training', 'Coordination under peak hours', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(118, 'Kitchen / Culinary', '', 29, 'Reskilling', 'Digital kitchen management', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(119, 'Kitchen / Culinary', '', 29, 'Upskilling', 'Advanced scheduling and multitasking', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(120, 'Kitchen / Culinary', '', 29, 'Succession Ready', 'Kitchen operational planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(121, 'Kitchen / Culinary', '', 30, 'Retrain', 'Brigade collaboration basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(122, 'Kitchen / Culinary', '', 30, 'Refresher Training', 'Shift coordination', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(123, 'Kitchen / Culinary', '', 30, 'Reskilling', 'Conflict resolution', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(124, 'Kitchen / Culinary', '', 30, 'Upskilling', 'Leadership and delegation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(125, 'Kitchen / Culinary', '', 30, 'Succession Ready', 'Leading kitchen operations, mentoring juniors', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(126, 'Kitchen / Culinary', '', 31, 'Retrain', 'Plating and portion standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(127, 'Kitchen / Culinary', '', 31, 'Refresher Training', 'Consistency checks', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(128, 'Kitchen / Culinary', '', 31, 'Reskilling', 'Digital feedback tracking', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(129, 'Kitchen / Culinary', '', 31, 'Upskilling', 'Creative presentation techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(130, 'Kitchen / Culinary', '', 31, 'Succession Ready', 'Leading quality improvement initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(131, 'Kitchen / Culinary', '', 32, 'Retrain', 'Basic equipment use', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(132, 'Kitchen / Culinary', '', 32, 'Refresher Training', 'Updated safety protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(133, 'Kitchen / Culinary', '', 32, 'Reskilling', 'Advanced machinery operation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(134, 'Kitchen / Culinary', '', 32, 'Upskilling', 'Equipment maintenance planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(135, 'Kitchen / Culinary', '', 32, 'Succession Ready', 'Leading safety programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(136, 'Kitchen / Culinary', '', 33, 'Retrain', 'Standard portioning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(137, 'Kitchen / Culinary', '', 33, 'Refresher Training', 'Waste reduction techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(138, 'Kitchen / Culinary', '', 33, 'Reskilling', 'Inventory tracking tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(139, 'Kitchen / Culinary', '', 33, 'Upskilling', 'Cost optimization strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(140, 'Kitchen / Culinary', '', 33, 'Succession Ready', 'Leading procurement and cost control programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(141, 'Sales & Marketing', '', 34, 'Retrain', 'Basic client interactions', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(142, 'Sales & Marketing', '', 34, 'Refresher Training', 'Presentation updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(143, 'Sales & Marketing', '', 34, 'Reskilling', 'Digital presentation tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(144, 'Sales & Marketing', '', 34, 'Upskilling', 'Public speaking and persuasion', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(145, 'Sales & Marketing', '', 34, 'Succession Ready', 'Leading client presentations, coaching juniors', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(146, 'Sales & Marketing', '', 35, 'Retrain', 'Basic client records', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(147, 'Sales & Marketing', '', 35, 'Refresher Training', 'CRM tool usage', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(148, 'Sales & Marketing', '', 35, 'Reskilling', 'Advanced CRM analytics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(149, 'Sales & Marketing', '', 35, 'Upskilling', 'Strategic relationship building', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(150, 'Sales & Marketing', '', 35, 'Succession Ready', 'Leading CRM implementation and team training', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(151, 'Sales & Marketing', '', 36, 'Retrain', 'Industry basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(152, 'Sales & Marketing', '', 36, 'Refresher Training', 'Competitor analysis updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(153, 'Sales & Marketing', '', 36, 'Reskilling', 'Digital trend monitoring tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(154, 'Sales & Marketing', '', 36, 'Upskilling', 'Market forecasting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(155, 'Sales & Marketing', '', 36, 'Succession Ready', 'Strategy planning and mentorship', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(156, 'Sales & Marketing', '', 37, 'Retrain', 'Standard negotiation techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(157, 'Sales & Marketing', '', 37, 'Refresher Training', 'Advanced deal closing scenarios', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(158, 'Sales & Marketing', '', 37, 'Reskilling', 'Digital negotiation simulations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(159, 'Sales & Marketing', '', 37, 'Upskilling', 'Cross-cultural negotiation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(160, 'Sales & Marketing', '', 37, 'Succession Ready', 'Leading contract negotiations, coaching juniors', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(161, 'Sales & Marketing', '', 38, 'Retrain', 'Basic data reporting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(162, 'Sales & Marketing', '', 38, 'Refresher Training', 'Dashboard updates, KPI tracking', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(163, 'Sales & Marketing', '', 38, 'Reskilling', 'Analytics software usage', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(164, 'Sales & Marketing', '', 38, 'Upskilling', 'Data interpretation for strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(165, 'Sales & Marketing', '', 38, 'Succession Ready', 'Leading reporting processes, decision-making guidance', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(166, 'Sales & Marketing', '', 39, 'Retrain', 'Event and campaign basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(167, 'Sales & Marketing', '', 39, 'Refresher Training', 'Updated workflows', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(168, 'Sales & Marketing', '', 39, 'Reskilling', 'Project management tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(169, 'Sales & Marketing', '', 39, 'Upskilling', 'Strategic campaign planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(170, 'Sales & Marketing', '', 39, 'Succession Ready', 'Leading marketing strategy implementation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(171, 'Sales & Marketing', '', 40, 'Retrain', 'Brand guidelines', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(172, 'Sales & Marketing', '', 40, 'Refresher Training', 'Brand updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(173, 'Sales & Marketing', '', 40, 'Reskilling', 'Digital branding tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(174, 'Sales & Marketing', '', 40, 'Upskilling', 'Campaign development and brand strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(175, 'Sales & Marketing', '', 40, 'Succession Ready', 'Leading brand management initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(176, 'Human Resources (HR)', '', 41, 'Retrain', 'Employee interaction basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(177, 'Human Resources (HR)', '', 41, 'Refresher Training', 'Feedback and appraisal communication', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(178, 'Human Resources (HR)', '', 41, 'Reskilling', 'Digital HR communication platforms', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(179, 'Human Resources (HR)', '', 41, 'Upskilling', 'Conflict management, negotiation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(180, 'Human Resources (HR)', '', 41, 'Succession Ready', 'Leading employee relations strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(181, 'Human Resources (HR)', '', 42, 'Retrain', 'Data privacy basics\nhey', '2026-01-22 09:11:48', '2026-01-22 21:58:01', 'Online', NULL),
(182, 'Human Resources (HR)', '', 42, 'Refresher Training', 'HR ethical policies updates\nyo', '2026-01-22 09:11:48', '2026-01-22 21:58:55', 'Online', NULL),
(183, 'Human Resources (HR)', '', 42, 'Reskilling', 'Digital HRIS security', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(184, 'Human Resources (HR)', '', 42, 'Upskilling', 'Advanced compliance management', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(185, 'Human Resources (HR)', '', 42, 'Succession Ready', 'Policy creation and enforcement', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(186, 'Human Resources (HR)', '', 43, 'Retrain', 'Basic HR policies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(187, 'Human Resources (HR)', '', 43, 'Refresher Training', 'Updated labor laws', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(188, 'Human Resources (HR)', '', 43, 'Reskilling', 'Compliance tracking tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(189, 'Human Resources (HR)', '', 43, 'Upskilling', 'Strategic HR policy planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(190, 'Human Resources (HR)', '', 43, 'Succession Ready', 'Leading policy development', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(191, 'Human Resources (HR)', '', 44, 'Retrain', 'Employee record keeping', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(192, 'Human Resources (HR)', '', 44, 'Refresher Training', 'Reporting updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(193, 'Human Resources (HR)', '', 44, 'Reskilling', 'Digital documentation systems', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(194, 'Human Resources (HR)', '', 44, 'Upskilling', 'HR workflow optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(195, 'Human Resources (HR)', '', 44, 'Succession Ready', 'Leading documentation process improvements', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(196, 'Human Resources (HR)', '', 45, 'Retrain', 'Routine HR issue handling', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(197, 'Human Resources (HR)', '', 45, 'Refresher Training', 'Complex scenario roleplay', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(198, 'Human Resources (HR)', '', 45, 'Reskilling', 'Data-driven decision-making tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(199, 'Human Resources (HR)', '', 45, 'Upskilling', 'Strategic HR problem solving', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(200, 'Human Resources (HR)', '', 45, 'Succession Ready', 'Leading HR policy and workforce planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(201, 'Human Resources (HR)', '', 46, 'Retrain', 'Employee onboarding', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(202, 'Human Resources (HR)', '', 46, 'Refresher Training', 'Updated learning programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(203, 'Human Resources (HR)', '', 46, 'Reskilling', 'Digital LMS platforms', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(204, 'Human Resources (HR)', '', 46, 'Upskilling', 'Learning pathway design', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(205, 'Human Resources (HR)', '', 46, 'Succession Ready', 'Leading organizational development strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(206, 'Human Resources (HR)', '', 47, 'Retrain', 'Basic HRIS usage', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(207, 'Human Resources (HR)', '', 47, 'Refresher Training', 'System updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(208, 'Human Resources (HR)', '', 47, 'Reskilling', 'Advanced HRIS analytics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(209, 'Human Resources (HR)', '', 47, 'Upskilling', 'Workflow integration', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(210, 'Human Resources (HR)', '', 47, 'Succession Ready', 'Leading HR technology adoption', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(211, 'Finance / Accounting', '', 48, 'Retrain', 'Basic accounting principles', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(212, 'Finance / Accounting', '', 48, 'Refresher Training', 'Error-checking techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(213, 'Finance / Accounting', '', 48, 'Reskilling', 'Accounting software updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(214, 'Finance / Accounting', '', 48, 'Upskilling', 'Advanced reconciliation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(215, 'Finance / Accounting', '', 48, 'Succession Ready', 'Financial audit leadership', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(216, 'Finance / Accounting', '', 49, 'Retrain', 'Core calculations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(217, 'Finance / Accounting', '', 49, 'Refresher Training', 'KPI monitoring', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(218, 'Finance / Accounting', '', 49, 'Reskilling', 'Data analysis tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(219, 'Finance / Accounting', '', 49, 'Upskilling', 'Forecasting and scenario planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(220, 'Finance / Accounting', '', 49, 'Succession Ready', 'Strategic financial decision-making', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(221, 'Finance / Accounting', '', 50, 'Retrain', 'Standard accounting policies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(222, 'Finance / Accounting', '', 50, 'Refresher Training', 'Regulatory updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(223, 'Finance / Accounting', '', 50, 'Reskilling', 'Digital compliance tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(224, 'Finance / Accounting', '', 50, 'Upskilling', 'Risk assessment', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(225, 'Finance / Accounting', '', 50, 'Succession Ready', 'Policy leadership and oversight', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(226, 'Finance / Accounting', '', 51, 'Retrain', 'Data handling basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(227, 'Finance / Accounting', '', 51, 'Refresher Training', 'Updated financial ethics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(228, 'Finance / Accounting', '', 51, 'Reskilling', 'Digital confidentiality tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(229, 'Finance / Accounting', '', 51, 'Upskilling', 'Fraud detection and prevention', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(230, 'Finance / Accounting', '', 51, 'Succession Ready', 'Leading financial governance', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(231, 'Finance / Accounting', '', 52, 'Retrain', 'Standard reports', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(232, 'Finance / Accounting', '', 52, 'Refresher Training', 'Updated reporting formats', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(233, 'Finance / Accounting', '', 52, 'Reskilling', 'Digital reporting systems', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(234, 'Finance / Accounting', '', 52, 'Upskilling', 'Data interpretation for decision-making', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(235, 'Finance / Accounting', '', 52, 'Succession Ready', 'Leading finance reporting strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(236, 'Finance / Accounting', '', 53, 'Retrain', 'Meeting monthly deadlines', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(237, 'Finance / Accounting', '', 53, 'Refresher Training', 'Process optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(238, 'Finance / Accounting', '', 53, 'Reskilling', 'Automated scheduling tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(239, 'Finance / Accounting', '', 53, 'Upskilling', 'Strategic planning for peak periods', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(240, 'Finance / Accounting', '', 53, 'Succession Ready', 'Leading financial operations planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(241, 'Finance / Accounting', '', 54, 'Retrain', 'Expense monitoring basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(242, 'Finance / Accounting', '', 54, 'Refresher Training', 'Budget reviews', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(243, 'Finance / Accounting', '', 54, 'Reskilling', 'Cost-tracking tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(244, 'Finance / Accounting', '', 54, 'Upskilling', 'Advanced financial optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(245, 'Finance / Accounting', '', 54, 'Succession Ready', 'Leading budget planning initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(246, 'Engineering / Maintenance', '', 55, 'Retrain', 'Equipment operation basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(247, 'Engineering / Maintenance', '', 55, 'Refresher Training', 'Updated technical procedures', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(248, 'Engineering / Maintenance', '', 55, 'Reskilling', 'Digital diagnostic tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(249, 'Engineering / Maintenance', '', 55, 'Upskilling', 'Advanced mechanical troubleshooting', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(250, 'Engineering / Maintenance', '', 55, 'Succession Ready', 'Leading technical strategy and team mentoring', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(251, 'Engineering / Maintenance', '', 56, 'Retrain', 'Routine checks', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(252, 'Engineering / Maintenance', '', 56, 'Refresher Training', 'Updated maintenance schedules', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(253, 'Engineering / Maintenance', '', 56, 'Reskilling', 'Predictive maintenance tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(254, 'Engineering / Maintenance', '', 56, 'Upskilling', 'Maintenance optimization', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(255, 'Engineering / Maintenance', '', 56, 'Succession Ready', 'Leading preventive maintenance planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(256, 'Engineering / Maintenance', '', 57, 'Retrain', 'Safety procedures basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(257, 'Engineering / Maintenance', '', 57, 'Refresher Training', 'Updated safety regulations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(258, 'Engineering / Maintenance', '', 57, 'Reskilling', 'Emergency response simulations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(259, 'Engineering / Maintenance', '', 57, 'Upskilling', 'Safety audits', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(260, 'Engineering / Maintenance', '', 57, 'Succession Ready', 'Leading compliance programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(261, 'Engineering / Maintenance', '', 58, 'Retrain', 'Basic fault detection', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(262, 'Engineering / Maintenance', '', 58, 'Refresher Training', 'Complex problem handling', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(263, 'Engineering / Maintenance', '', 58, 'Reskilling', 'Diagnostic software tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(264, 'Engineering / Maintenance', '', 58, 'Upskilling', 'Advanced troubleshooting methodologies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(265, 'Engineering / Maintenance', '', 58, 'Succession Ready', 'Leading technical problem-solving initiatives', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(266, 'Engineering / Maintenance', '', 59, 'Retrain', 'Maintenance logs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(267, 'Engineering / Maintenance', '', 59, 'Refresher Training', 'Updated reporting procedures', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(268, 'Engineering / Maintenance', '', 59, 'Reskilling', 'Digital documentation systems\nDocumentation & Reporting Skills Rerfesh', '2026-01-22 09:11:48', '2026-01-22 21:54:34', 'Online', NULL),
(269, 'Engineering / Maintenance', '', 59, 'Upskilling', 'Analytical reporting for operations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(270, 'Engineering / Maintenance', '', 59, 'Succession Ready', 'Leading maintenance reporting strategy', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(271, 'Engineering / Maintenance', '', 60, 'Retrain', 'Basic team collaboration', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(272, 'Engineering / Maintenance', '', 60, 'Refresher Training', 'Cross-department coordination', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(273, 'Engineering / Maintenance', '', 60, 'Reskilling', 'Project management tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(274, 'Engineering / Maintenance', '', 60, 'Upskilling', 'Leadership in team operations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(275, 'Engineering / Maintenance', '', 60, 'Succession Ready', 'Leading departmental communication', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(276, 'Engineering / Maintenance', '', 61, 'Retrain', 'Standard emergency procedures', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(277, 'Engineering / Maintenance', '', 61, 'Refresher Training', 'Drills and simulations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(278, 'Engineering / Maintenance', '', 61, 'Reskilling', 'Crisis management software', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(279, 'Engineering / Maintenance', '', 61, 'Upskilling', 'Strategic emergency planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(280, 'Engineering / Maintenance', '', 61, 'Succession Ready', 'Leading emergency preparedness programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(281, 'Security', '', 62, 'Retrain', 'Patrolling basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(282, 'Security', '', 62, 'Refresher Training', 'Updated monitoring techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(283, 'Security', '', 62, 'Reskilling', 'CCTV and digital surveillance', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(284, 'Security', '', 62, 'Upskilling', 'Threat assessment and prevention strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(285, 'Security', '', 62, 'Succession Ready', 'Leading security operations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(286, 'Security', '', 63, 'Retrain', 'Fire and safety drills', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(287, 'Security', '', 63, 'Refresher Training', 'Updated response protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(288, 'Security', '', 63, 'Reskilling', 'Coordinated crisis simulations', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(289, 'Security', '', 63, 'Upskilling', 'Risk management strategies', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(290, 'Security', '', 63, 'Succession Ready', 'Leading emergency planning and team training', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(291, 'Security', '', 64, 'Retrain', 'Incident reporting basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(292, 'Security', '', 64, 'Refresher Training', 'Updated protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(293, 'Security', '', 64, 'Reskilling', 'Digital reporting tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(294, 'Security', '', 64, 'Upskilling', 'Analytical reporting and briefings', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(295, 'Security', '', 64, 'Succession Ready', 'Leading security reporting standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(296, 'Security', '', 65, 'Retrain', 'Access procedures basics', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(297, 'Security', '', 65, 'Refresher Training', 'Updated security protocols', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(298, 'Security', '', 65, 'Reskilling', 'Digital access systems', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(299, 'Security', '', 65, 'Upskilling', 'Strategic security planning', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(300, 'Security', '', 65, 'Succession Ready', 'Leading access and patrol policy implementation', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(301, 'Security', '', 66, 'Retrain', 'Basic conflict resolution', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(302, 'Security', '', 66, 'Refresher Training', 'Updated de-escalation techniques', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(303, 'Security', '', 66, 'Reskilling', 'Behavioral analysis', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(304, 'Security', '', 66, 'Upskilling', 'Advanced negotiation skills', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(305, 'Security', '', 66, 'Succession Ready', 'Leading conflict management training', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(306, 'Security', '', 67, 'Retrain', 'Code of conduct', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(307, 'Security', '', 67, 'Refresher Training', 'Updated standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(308, 'Security', '', 67, 'Reskilling', 'Leadership principles', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(309, 'Security', '', 67, 'Upskilling', 'Professional mentoring', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(310, 'Security', '', 67, 'Succession Ready', 'Leading security team conduct standards', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(311, 'Security', '', 68, 'Retrain', 'Hazard identification', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(312, 'Security', '', 68, 'Refresher Training', 'Risk mitigation updates', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(313, 'Security', '', 68, 'Reskilling', 'Digital risk monitoring tools', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(314, 'Security', '', 68, 'Upskilling', 'Strategic risk assessment', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL),
(315, 'Security', '', 68, 'Succession Ready', 'Leading safety and risk programs', '2026-01-22 09:11:48', '2026-01-22 09:11:48', 'Onsite', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `competency` decimal(5,2) DEFAULT 0.00,
  `status` enum('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `last_assessment` date DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `last_idp_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `position`, `department`, `competency`, `status`, `last_assessment`, `next_review_date`, `last_idp_date`, `created_at`, `updated_at`) VALUES
(26, 'CR-RT-001', 'Juan Dela Cruz', 'Front Desk Officer', 'Front Office / Reception', 10.00, 'Retrain', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(27, 'CR-RT-002', 'Maria Santos', 'Room Attendant', 'Housekeeping', 18.00, 'Retrain', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(28, 'CR-RT-003', 'Mark Reyes', 'Kitchen Helper', 'Kitchen / Culinary', 5.00, 'Retrain', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(29, 'CR-RT-004', 'Anna Garcia', 'Service Crew', 'Food & Beverage (F&B)', 12.00, 'Retrain', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(30, 'CR-RT-005', 'Leo Mendoza', 'Security Guard', 'Security', 20.00, 'Retrain', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(31, 'CR-RS-001', 'Paolo Lim', 'Concierge', 'Front Office / Reception', 25.00, 'Reskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(32, 'CR-RS-002', 'Karla Cruz', 'Laundry Attendant', 'Housekeeping', 35.00, 'Reskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(33, 'CR-RS-003', 'James Torres', 'Line Cook', 'Kitchen / Culinary', 40.00, 'Reskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(34, 'CR-RS-004', 'Nina Flores', 'Waiter', 'Food & Beverage (F&B)', 28.00, 'Reskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(35, 'CR-RS-005', 'Eric Ramos', 'Maintenance Technician', 'Engineering / Maintenance', 33.00, 'Reskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(36, 'CR-RF-001', 'Joyce Tan', 'Reservations Agent', 'Front Office / Reception', 45.00, 'Refresher Training', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(37, 'CR-RF-002', 'Cathy Aquino', 'Housekeeping Supervisor', 'Housekeeping', 55.00, 'Refresher Training', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(38, 'CR-RF-003', 'Allan Yu', 'Sous Chef', 'Kitchen / Culinary', 60.00, 'Refresher Training', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(39, 'CR-RF-004', 'Grace Bautista', 'Bar Staff', 'Food & Beverage (F&B)', 50.00, 'Refresher Training', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(40, 'CR-RF-005', 'Ronnie Diaz', 'Accountant', 'Finance / Accounting', 58.00, 'Refresher Training', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(41, 'CR-UP-001', 'Mika Villanueva', 'Front Office Supervisor', 'Front Office / Reception', 65.00, 'Upskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(42, 'CR-UP-002', 'Sophie Navarro', 'Housekeeping Manager', 'Housekeeping', 72.00, 'Upskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(43, 'CR-UP-003', 'Daniel Perez', 'Chef de Partie', 'Kitchen / Culinary', 80.00, 'Upskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(44, 'CR-UP-004', 'Trisha Ong', 'F&B Supervisor', 'Food & Beverage (F&B)', 70.00, 'Upskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(45, 'CR-UP-005', 'Kevin Sy', 'HR Assistant', 'Human Resources (HR)', 78.00, 'Upskilling', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(46, 'CR-SR-001', 'Angela Co', 'Front Office Manager', 'Front Office / Reception', 85.00, 'Succession Ready', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(47, 'CR-SR-002', 'Patricia Lee', 'Executive Housekeeper', 'Housekeeping', 90.00, 'Succession Ready', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(48, 'CR-SR-003', 'Miguel Chua', 'Executive Chef', 'Kitchen / Culinary', 95.00, 'Succession Ready', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(49, 'CR-SR-004', 'Carlo Bautista', 'Restaurant Manager', 'Food & Beverage (F&B)', 88.00, 'Succession Ready', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49'),
(50, 'CR-SR-005', 'Jasper Tan', 'Chief Engineer / Engineering Manager', 'Engineering / Maintenance', 92.00, 'Succession Ready', '2026-01-23', '2026-04-23', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49');

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `skill_score` decimal(5,2) DEFAULT 0.00,
  `assessment_date` date DEFAULT curdate(),
  `assessed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_skills`
--

INSERT INTO `employee_skills` (`id`, `employee_id`, `skill_id`, `skill_score`, `assessment_date`, `assessed_by`, `notes`, `created_at`) VALUES
(596, 'CR-RS-005', 59, 75.00, '2026-01-23', NULL, NULL, '2026-01-23 09:25:04'),
(598, 'CR-RS-005', 57, 20.00, '2026-01-23', NULL, NULL, '2026-01-23 09:25:20'),
(599, 'CR-RS-005', 61, 75.00, '2026-01-23', NULL, NULL, '2026-01-23 09:25:31'),
(603, 'CR-RF-005', 50, 95.00, '2026-01-23', NULL, NULL, '2026-01-23 10:26:04'),
(605, 'CR-RS-005', 55, 30.00, '2026-01-23', NULL, NULL, '2026-01-23 10:44:48'),
(606, 'CR-RS-005', 56, 55.00, '2026-01-23', NULL, NULL, '2026-01-23 10:44:48'),
(608, 'CR-RS-005', 58, 72.00, '2026-01-23', NULL, NULL, '2026-01-23 10:44:48'),
(610, 'CR-RS-005', 60, 39.00, '2026-01-23', NULL, NULL, '2026-01-23 10:44:48'),
(612, 'CR-RF-005', 48, 85.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(613, 'CR-RF-005', 49, 10.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(615, 'CR-RF-005', 51, 70.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(616, 'CR-RF-005', 52, 20.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(617, 'CR-RF-005', 53, 10.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(618, 'CR-RF-005', 54, 54.00, '2026-01-23', NULL, NULL, '2026-01-23 11:35:50'),
(619, 'CR-RF-003', 4, 3.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(620, 'CR-RF-003', 27, 25.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(621, 'CR-RF-003', 28, 11.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(622, 'CR-RF-003', 29, 55.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(623, 'CR-RF-003', 30, 60.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(624, 'CR-RF-003', 31, 91.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(625, 'CR-RF-003', 32, 43.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(626, 'CR-RF-003', 33, 37.00, '2026-01-23', NULL, NULL, '2026-01-23 11:52:59'),
(627, 'CR-SR-005', 55, 27.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(628, 'CR-SR-005', 56, 31.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(629, 'CR-SR-005', 57, 89.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(630, 'CR-SR-005', 58, 68.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(631, 'CR-SR-005', 59, 43.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(632, 'CR-SR-005', 60, 87.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(633, 'CR-SR-005', 61, 53.00, '2026-01-24', NULL, NULL, '2026-01-23 14:22:33'),
(641, 'CR-RT-004', 3, 7.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(642, 'CR-RT-004', 20, 11.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(643, 'CR-RT-004', 21, 57.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(644, 'CR-RT-004', 22, 61.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(645, 'CR-RT-004', 23, 30.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(646, 'CR-RT-004', 24, 14.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(647, 'CR-RT-004', 25, 43.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10'),
(648, 'CR-RT-004', 26, 56.00, '2026-01-24', NULL, NULL, '2026-01-24 14:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `general_skill_standards`
--

CREATE TABLE `general_skill_standards` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `standard_percentage` decimal(5,2) NOT NULL DEFAULT 80.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_skill_standards`
--

INSERT INTO `general_skill_standards` (`id`, `skill_id`, `standard_percentage`, `created_at`, `updated_at`) VALUES
(1, 65, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(2, 38, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(3, 13, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(4, 31, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(5, 23, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(6, 34, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(7, 64, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(8, 50, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(9, 42, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(10, 51, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(11, 66, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(12, 54, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(13, 12, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(14, 35, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(15, 6, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(16, 67, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(17, 59, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(18, 7, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(19, 63, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(20, 61, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(21, 32, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(22, 48, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(23, 27, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(24, 21, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(25, 28, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(26, 20, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(27, 16, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(28, 57, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(29, 9, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(30, 41, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(31, 14, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(32, 36, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(33, 37, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(34, 49, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(35, 62, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(36, 44, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(37, 18, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(38, 39, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(39, 43, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(40, 56, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(41, 58, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(42, 8, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(43, 45, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(44, 22, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(45, 10, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(46, 40, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(47, 26, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(48, 19, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(49, 52, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(50, 68, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(51, 24, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(52, 3, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(53, 1, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(54, 2, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(55, 5, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(56, 4, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(57, 25, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(58, 47, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(59, 60, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(60, 17, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(61, 30, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(62, 55, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(63, 29, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(64, 15, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(65, 53, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(66, 11, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(67, 46, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18'),
(68, 33, 80.00, '2026-01-23 01:37:18', '2026-01-23 01:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `individual_development_plans`
--

CREATE TABLE `individual_development_plans` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `competency` decimal(5,2) DEFAULT 0.00,
  `succession_status` enum('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` text DEFAULT NULL,
  `target_score` decimal(5,2) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `idp_status` enum('approved','on_hold','for_compliance','cancelled','rejected','under_review') DEFAULT 'under_review',
  `training_requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_mode` enum('Online','Onsite','Hybrid') DEFAULT 'Onsite',
  `learning_requested_at` timestamp NULL DEFAULT NULL,
  `requested_training_type` varchar(50) DEFAULT NULL,
  `requested_training_mode` varchar(20) DEFAULT NULL,
  `requested_start_datetime` datetime DEFAULT NULL,
  `requested_end_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `individual_development_plans`
--

INSERT INTO `individual_development_plans` (`id`, `employee_id`, `employee_name`, `position`, `department`, `competency`, `succession_status`, `development_plan`, `target_score`, `target_date`, `idp_status`, `training_requested_at`, `created_at`, `updated_at`, `delivery_mode`, `learning_requested_at`, `requested_training_type`, `requested_training_mode`, `requested_start_datetime`, `requested_end_datetime`) VALUES
(6, 'CR-RT-003', 'Mark Reyes', 'Kitchen Helper', 'Kitchen / Culinary', 5.00, 'Retrain', 'Food Preparation & Culinary Fundamentals:\r\n- Basic cooking techniques\r\n\r\nWaste Control & Cost Awareness:\r\n- Standard portioning', 75.00, NULL, 'approved', '2026-01-22 23:53:47', '2026-01-22 23:53:25', '2026-01-22 23:53:47', 'Onsite', NULL, 'Workshop', 'Onsite', '2030-03-17 23:22:00', '2030-06-26 16:48:00'),
(7, 'CR-RS-005', 'Eric Ramos', 'Maintenance Technician', 'Engineering / Maintenance', 52.29, 'Refresher Training', 'Emergency Response Readiness:\r\n- Drills and simulations\r\n\r\nTechnical & Mechanical Knowledge:\r\n- Updated technical procedures', 27.00, NULL, '', '2026-01-23 11:54:46', '2026-01-23 03:32:58', '2026-01-23 11:54:46', 'Onsite', NULL, 'Training', 'Online', '2026-01-21 00:29:00', '2026-08-13 17:00:00'),
(8, 'CR-RF-002', 'Cathy Aquino', 'Housekeeping Supervisor', 'Housekeeping', 55.00, 'Refresher Training', 'Attention to Detail:\r\n- Spot checks\r\n\r\nKnowledge of Cleaning Procedures & Chemicals:\r\n- Updated chemicals and safety\r\n\r\nPhysical Endurance & Task Discipline:\r\n- Ergonomics and repetitive task safety\r\n\r\nTeamwork & Coordination:\r\n- Cross-department interaction\r\n\r\nTime Management:\r\n- Prioritization at peak occupancy', 85.00, NULL, 'approved', '2026-01-23 04:16:09', '2026-01-23 04:14:18', '2026-01-23 04:16:09', 'Onsite', NULL, NULL, 'Hybrid', '2026-04-23 12:13:00', '2026-05-23 12:14:00'),
(10, 'CR-RS-001', 'Paolo Lim', 'Concierge', 'Front Office / Reception', 25.00, 'Reskilling', 'Effective Communication Skills:\r\n- Social media and digital communication skills\r\n\r\nHotel Systems & Reservation Knowledge:\r\n- online bookings\r\n\r\nProfessional Appearance & Etiquette:\r\n- Cultural etiquette for international guests\r\n\r\nTime Management & Multitasking:\r\n- Digital scheduling tools', 83.00, NULL, 'approved', '2026-01-23 04:27:19', '2026-01-23 04:15:33', '2026-01-23 04:27:19', 'Onsite', NULL, 'Refresher', 'Onsite', '2028-10-29 12:56:00', '2030-09-06 18:14:00'),
(11, 'CR-RF-005', 'Ronnie Diaz', 'Accountant', 'Finance / Accounting', 58.00, 'Refresher Training', 'Compliance & Policy Awareness:\r\n- Regulatory updates\r\n\r\nFinancial Accuracy & Attention to Detail:\r\n- Error-checking techniques', 85.00, NULL, 'approved', '2026-01-23 04:17:46', '2026-01-23 04:17:22', '2026-01-23 04:17:46', 'Onsite', NULL, 'Training', 'Hybrid', NULL, NULL),
(12, 'CR-UP-003', 'Daniel Perez', 'Chef de Partie', 'Kitchen / Culinary', 80.00, 'Upskilling', 'Attention to Quality & Presentation:\r\n- Creative presentation techniques\r\n\r\nEquipment Handling & Safety Awareness:\r\n- Equipment maintenance planning', 90.00, NULL, 'approved', '2026-01-23 04:23:27', '2026-01-23 04:23:18', '2026-01-23 04:23:27', 'Onsite', NULL, NULL, 'Onsite', NULL, NULL),
(13, 'CR-RF-001', 'Joyce Tan', 'Reservations Agent', 'Front Office / Reception', 45.00, 'Refresher Training', 'Cultural Awareness & Guest Sensitivity:\r\n- International guest expectations\r\n\r\nEffective Communication Skills:\r\n- Email etiquette\r\n- phone handling\r\n- professional messaging', 85.00, NULL, 'approved', '2026-01-23 04:45:07', '2026-01-23 04:44:33', '2026-01-23 04:45:07', 'Onsite', NULL, NULL, 'Online', NULL, NULL),
(18, 'CR-SR-005', 'Jasper Tan', 'Chief Engineer / Engineering Manager', 'Engineering / Maintenance', 56.86, 'Refresher Training', 'Documentation & Reporting Skills:\r\n- Updated reporting procedures\r\n\r\nEmergency Response Readiness:\r\n- Drills and simulations\r\n\r\nHealth & Safety Compliance:\r\n- Updated safety regulations\r\n\r\nPreventive Maintenance Skills:\r\n- Updated maintenance schedules', 70.00, NULL, '', '2026-01-24 10:28:43', '2026-01-24 10:26:51', '2026-01-24 10:28:43', 'Onsite', NULL, 'Training', 'Onsite', NULL, NULL),
(19, 'CR-RT-004', 'Anna Garcia', 'Service Crew', 'Food & Beverage (F&B)', 34.88, 'Reskilling', 'Food Safety & Hygiene Knowledge:\r\n- HACCP and allergen management\r\n\r\nProfessional Conduct & Service Etiquette:\r\n- Cultural service standards\r\n\r\nSales & Upselling Skills:\r\n- Digital sales tools', 75.00, NULL, '', '2026-01-24 15:23:25', '2026-01-24 14:33:47', '2026-01-24 15:23:25', 'Onsite', NULL, 'Training', 'Onsite', '2026-02-11 22:41:00', '2026-01-31 22:41:00');

-- --------------------------------------------------------

--
-- Table structure for table `requested_idps_repository`
--

CREATE TABLE `requested_idps_repository` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `competency` decimal(5,2) DEFAULT 0.00,
  `succession_status` enum('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` text DEFAULT NULL,
  `target_score` decimal(5,2) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `delivery_mode` enum('Online','Onsite','Hybrid') DEFAULT 'Onsite',
  `requested_training_type` varchar(50) DEFAULT NULL,
  `requested_training_mode` varchar(20) DEFAULT NULL,
  `requested_start_datetime` datetime DEFAULT NULL,
  `requested_end_datetime` datetime DEFAULT NULL,
  `idp_status` enum('approved','on_hold','for_compliance','cancelled','rejected','under_review','requested') DEFAULT 'requested',
  `training_requested_at` timestamp NULL DEFAULT NULL,
  `learning_requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requested_to_idp`
--

CREATE TABLE `requested_to_idp` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` enum('Pending','Created') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requested_to_idp`
--

INSERT INTO `requested_to_idp` (`id`, `employee_id`, `employee_name`, `position`, `department`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CR-RS-005', 'Eric Ramos', 'Maintenance Technician', 'Engineering / Maintenance', 'Created', '2026-01-23 11:34:10', '2026-01-23 11:54:21'),
(2, 'CR-RF-003', 'Allan Yu', 'Sous Chef', 'Kitchen / Culinary', 'Pending', '2026-01-23 11:53:23', '2026-01-23 11:53:23'),
(3, 'CR-RT-001', 'Juan Dela Cruz', 'Front Desk Officer', 'Front Office / Reception', 'Pending', '2026-01-23 12:35:56', '2026-01-23 12:35:56'),
(4, 'CR-SR-005', 'Jasper Tan', 'Chief Engineer / Engineering Manager', 'Engineering / Maintenance', 'Created', '2026-01-24 10:25:11', '2026-01-24 10:26:51'),
(5, 'CR-RT-004', 'Anna Garcia', 'Service Crew', 'Food & Beverage (F&B)', 'Created', '2026-01-24 14:15:53', '2026-01-24 14:41:21');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(3,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `skill_name`, `category`, `department`, `description`, `weight`, `created_at`) VALUES
(1, 'Sample General Skill', 'General Skills', 'Front Office / Reception', 'Sample skill', 1.00, '2026-01-22 09:04:37'),
(2, 'Sample General Skill', 'General Skills', 'Housekeeping', 'Sample skill', 1.00, '2026-01-22 09:04:37'),
(3, 'Sample General Skill', 'General Skills', 'Food & Beverage (F&B)', 'Sample skill', 1.00, '2026-01-22 09:04:37'),
(4, 'Sample General Skill', 'General Skills', 'Kitchen / Culinary', 'Sample skill', 1.00, '2026-01-22 09:04:37'),
(5, 'Sample General Skill', 'General Skills', 'Human Resources (HR)', 'Sample skill', 1.00, '2026-01-22 09:04:37'),
(6, 'Customer Service Excellence', 'General Skills', 'Front Office / Reception', 'Delivering courteous, professional, and guest-focused service at all times', 1.00, '2026-01-22 09:10:32'),
(7, 'Effective Communication Skills', 'General Skills', 'Front Office / Reception', 'Clear verbal and written communication with guests and internal teams', 1.00, '2026-01-22 09:10:32'),
(8, 'Problem-Solving & Complaint Handling', 'General Skills', 'Front Office / Reception', 'Managing guest concerns calmly and effectively', 1.00, '2026-01-22 09:10:32'),
(9, 'Hotel Systems & Reservation Knowledge', 'General Skills', 'Front Office / Reception', 'Understanding PMS, booking systems, and guest records', 1.00, '2026-01-22 09:10:32'),
(10, 'Professional Appearance & Etiquette', 'General Skills', 'Front Office / Reception', 'Maintaining grooming standards and hospitality behavior', 1.00, '2026-01-22 09:10:32'),
(11, 'Time Management & Multitasking', 'General Skills', 'Front Office / Reception', 'Handling multiple guest requests efficiently', 1.00, '2026-01-22 09:10:32'),
(12, 'Cultural Awareness & Guest Sensitivity', 'General Skills', 'Front Office / Reception', 'Respecting diverse guest backgrounds and needs', 1.00, '2026-01-22 09:10:32'),
(13, 'Attention to Detail', 'General Skills', 'Housekeeping', 'Ensuring cleanliness, hygiene, and presentation standards', 1.00, '2026-01-22 09:10:32'),
(14, 'Knowledge of Cleaning Procedures & Chemicals', 'General Skills', 'Housekeeping', 'Safe and correct use of tools and materials', 1.00, '2026-01-22 09:10:32'),
(15, 'Time Management', 'General Skills', 'Housekeeping', 'Completing tasks within operational schedules', 1.00, '2026-01-22 09:10:32'),
(16, 'Health & Safety Awareness', 'General Skills', 'Housekeeping', 'Following safety, sanitation, and hazard prevention protocols', 1.00, '2026-01-22 09:10:32'),
(17, 'Teamwork & Coordination', 'General Skills', 'Housekeeping', 'Working effectively with supervisors and other departments', 1.00, '2026-01-22 09:10:32'),
(18, 'Physical Endurance & Task Discipline', 'General Skills', 'Housekeeping', 'Performing repetitive tasks efficiently', 1.00, '2026-01-22 09:10:32'),
(19, 'Quality Control Awareness', 'General Skills', 'Housekeeping', 'Meeting hotel cleanliness and inspection standards', 1.00, '2026-01-22 09:10:32'),
(20, 'Guest Service & Hospitality Skills', 'General Skills', 'Food & Beverage (F&B)', 'Providing friendly, attentive dining service', 1.00, '2026-01-22 09:10:32'),
(21, 'Food Safety & Hygiene Knowledge', 'General Skills', 'Food & Beverage (F&B)', 'Compliance with sanitation and food handling standards', 1.00, '2026-01-22 09:10:32'),
(22, 'Product Knowledge', 'General Skills', 'Food & Beverage (F&B)', 'Understanding menus, ingredients, beverages, and service styles', 1.00, '2026-01-22 09:10:32'),
(23, 'Communication & Coordination', 'General Skills', 'Food & Beverage (F&B)', 'Clear interaction with kitchen and service teams', 1.00, '2026-01-22 09:10:32'),
(24, 'Sales & Upselling Skills', 'General Skills', 'Food & Beverage (F&B)', 'Promoting menu items and enhancing guest experience', 1.00, '2026-01-22 09:10:32'),
(25, 'Stress & Time Management', 'General Skills', 'Food & Beverage (F&B)', 'Performing under pressure during peak service hours', 1.00, '2026-01-22 09:10:32'),
(26, 'Professional Conduct & Service Etiquette', 'General Skills', 'Food & Beverage (F&B)', 'Maintaining service standards', 1.00, '2026-01-22 09:10:32'),
(27, 'Food Preparation & Culinary Fundamentals', 'General Skills', 'Kitchen / Culinary', 'Understanding cooking techniques and recipes', 1.00, '2026-01-22 09:10:32'),
(28, 'Food Safety & Sanitation Compliance', 'General Skills', 'Kitchen / Culinary', 'Following HACCP and hygiene standards', 1.00, '2026-01-22 09:10:32'),
(29, 'Time & Workflow Management', 'General Skills', 'Kitchen / Culinary', 'Meeting service timelines during operations', 1.00, '2026-01-22 09:10:32'),
(30, 'Teamwork & Kitchen Coordination', 'General Skills', 'Kitchen / Culinary', 'Working efficiently within the kitchen brigade', 1.00, '2026-01-22 09:10:32'),
(31, 'Attention to Quality & Presentation', 'General Skills', 'Kitchen / Culinary', 'Maintaining consistency in food standards', 1.00, '2026-01-22 09:10:32'),
(32, 'Equipment Handling & Safety Awareness', 'General Skills', 'Kitchen / Culinary', 'Proper use of kitchen tools and machinery', 1.00, '2026-01-22 09:10:32'),
(33, 'Waste Control & Cost Awareness', 'General Skills', 'Kitchen / Culinary', 'Minimizing food waste', 1.00, '2026-01-22 09:10:32'),
(34, 'Communication & Presentation Skills', 'General Skills', 'Sales & Marketing', 'Effective client and stakeholder interaction', 1.00, '2026-01-22 09:10:32'),
(35, 'Customer Relationship Management (CRM)', 'General Skills', 'Sales & Marketing', 'Building and maintaining client relationships', 1.00, '2026-01-22 09:10:32'),
(36, 'Market & Trend Awareness', 'General Skills', 'Sales & Marketing', 'Understanding customer behavior and industry trends', 1.00, '2026-01-22 09:10:32'),
(37, 'Negotiation & Persuasion Skills', 'General Skills', 'Sales & Marketing', 'Closing deals and partnerships', 1.00, '2026-01-22 09:10:32'),
(38, 'Analytical & Reporting Skills', 'General Skills', 'Sales & Marketing', 'Interpreting sales data and performance metrics', 1.00, '2026-01-22 09:10:32'),
(39, 'Planning & Coordination', 'General Skills', 'Sales & Marketing', 'Executing campaigns and events efficiently', 1.00, '2026-01-22 09:10:32'),
(40, 'Professional Branding Awareness', 'General Skills', 'Sales & Marketing', 'Maintaining hotel brand consistency', 1.00, '2026-01-22 09:10:32'),
(41, 'Interpersonal & Communication Skills', 'General Skills', 'Human Resources (HR)', 'Managing employee relations professionally', 1.00, '2026-01-22 09:10:32'),
(42, 'Confidentiality & Ethical Judgment', 'General Skills', 'Human Resources (HR)', 'Handling sensitive employee information', 1.00, '2026-01-22 09:10:32'),
(43, 'Policy & Labor Law Awareness', 'General Skills', 'Human Resources (HR)', 'Understanding HR policies and compliance', 1.00, '2026-01-22 09:10:32'),
(44, 'Organizational & Documentation Skills', 'General Skills', 'Human Resources (HR)', 'Managing employee records and reports', 1.00, '2026-01-22 09:10:32'),
(45, 'Problem-Solving & Decision-Making', 'General Skills', 'Human Resources (HR)', 'Addressing workforce issues effectively', 1.00, '2026-01-22 09:10:32'),
(46, 'Training & Development Awareness', 'General Skills', 'Human Resources (HR)', 'Supporting employee growth initiatives', 1.00, '2026-01-22 09:10:32'),
(47, 'System & HRIS Proficiency', 'General Skills', 'Human Resources (HR)', 'Using HR software efficiently', 1.00, '2026-01-22 09:10:32'),
(48, 'Financial Accuracy & Attention to Detail', 'General Skills', 'Finance / Accounting', 'Ensuring error-free financial records', 1.00, '2026-01-22 09:10:32'),
(49, 'Numerical & Analytical Skills', 'General Skills', 'Finance / Accounting', 'Interpreting financial data and trends', 1.00, '2026-01-22 09:10:32'),
(50, 'Compliance & Policy Awareness', 'General Skills', 'Finance / Accounting', 'Following accounting standards and regulations', 1.00, '2026-01-22 09:10:32'),
(51, 'Confidentiality & Integrity', 'General Skills', 'Finance / Accounting', 'Handling financial data responsibly', 1.00, '2026-01-22 09:10:32'),
(52, 'Reporting & Documentation Skills', 'General Skills', 'Finance / Accounting', 'Preparing financial statements and reports', 1.00, '2026-01-22 09:10:32'),
(53, 'Time Management & Deadline Control', 'General Skills', 'Finance / Accounting', 'Meeting financial cut-offs', 1.00, '2026-01-22 09:10:32'),
(54, 'Cost Control Awareness', 'General Skills', 'Finance / Accounting', 'Monitoring expenses and budgets', 1.00, '2026-01-22 09:10:32'),
(55, 'Technical & Mechanical Knowledge', 'General Skills', 'Engineering / Maintenance', 'Understanding hotel systems and equipment', 1.00, '2026-01-22 09:10:32'),
(56, 'Preventive Maintenance Skills', 'General Skills', 'Engineering / Maintenance', 'Conducting routine inspections and repairs', 1.00, '2026-01-22 09:10:32'),
(57, 'Health & Safety Compliance', 'General Skills', 'Engineering / Maintenance', 'Adhering to safety and operational standards', 1.00, '2026-01-22 09:10:32'),
(58, 'Problem Diagnosis & Troubleshooting', 'General Skills', 'Engineering / Maintenance', 'Identifying and resolving technical issues', 1.00, '2026-01-22 09:10:32'),
(59, 'Documentation & Reporting Skills', 'General Skills', 'Engineering / Maintenance', 'Logging maintenance activities', 1.00, '2026-01-22 09:10:32'),
(60, 'Team Coordination & Communication', 'General Skills', 'Engineering / Maintenance', 'Working with other departments', 1.00, '2026-01-22 09:10:32'),
(61, 'Emergency Response Readiness', 'General Skills', 'Engineering / Maintenance', 'Handling urgent technical incidents', 1.00, '2026-01-22 09:10:32'),
(62, 'Observation & Situational Awareness', 'General Skills', 'Security', 'Monitoring surroundings effectively', 1.00, '2026-01-22 09:10:32'),
(63, 'Emergency Response & Crisis Handling', 'General Skills', 'Security', 'Managing incidents calmly', 1.00, '2026-01-22 09:10:32'),
(64, 'Communication & Reporting Skills', 'General Skills', 'Security', 'Writing incident and security reports', 1.00, '2026-01-22 09:10:32'),
(65, 'Access Control & Patrol Skills', 'General Skills', 'Security', 'Maintaining property security', 1.00, '2026-01-22 09:10:32'),
(66, 'Conflict Management & De-escalation', 'General Skills', 'Security', 'Handling disturbances professionally', 1.00, '2026-01-22 09:10:32'),
(67, 'Discipline & Professional Conduct', 'General Skills', 'Security', 'Maintaining authority and integrity', 1.00, '2026-01-22 09:10:32'),
(68, 'Safety & Risk Awareness', 'General Skills', 'Security', 'Identifying and mitigating threats', 1.00, '2026-01-22 09:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `succession_submissions`
--

CREATE TABLE `succession_submissions` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `competency` decimal(5,2) DEFAULT 0.00,
  `status` enum('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') DEFAULT 'Retrain',
  `development_plan` text DEFAULT NULL,
  `target_score` decimal(5,2) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `idp_status` enum('Pending','Created') DEFAULT 'Pending',
  `idp_created_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_pushed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `succession_submissions`
--

INSERT INTO `succession_submissions` (`id`, `employee_id`, `employee_name`, `position`, `department`, `competency`, `status`, `development_plan`, `target_score`, `target_date`, `idp_status`, `idp_created_at`, `created_at`, `updated_at`, `is_pushed`) VALUES
(5, 'CR-RT-001', 'Juan Dela Cruz', 'Front Desk Officer', 'Front Office / Reception', 0.00, 'Retrain', '- Customer Service Basics\n- Front Office SOP Review', 75.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-23 12:35:56', 1),
(6, 'CR-RT-002', 'Maria Santos', 'Room Attendant', 'Housekeeping', 18.00, 'Retrain', '- Cleaning Standards Refresher\n- Time Management', 75.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(7, 'CR-RT-003', 'Mark Reyes', 'Kitchen Helper', 'Kitchen / Culinary', 5.00, 'Retrain', 'Food Preparation & Culinary Fundamentals:\r\n- Basic cooking techniques\r\n\r\nWaste Control & Cost Awareness:\r\n- Standard portioning', 75.00, '2026-03-24', 'Created', '2026-01-22 23:53:25', '2026-01-22 23:08:49', '2026-01-22 23:53:25', 1),
(8, 'CR-RT-004', 'Anna Garcia', 'Service Crew', 'Food & Beverage (F&B)', 34.88, 'Reskilling', 'Food Safety & Hygiene Knowledge:\r\n- HACCP and allergen management\r\n\r\nProfessional Conduct & Service Etiquette:\r\n- Cultural service standards\r\n\r\nSales & Upselling Skills:\r\n- Digital sales tools', 75.00, '2026-03-24', 'Created', '2026-01-24 14:41:21', '2026-01-22 23:08:49', '2026-01-24 14:41:21', 1),
(9, 'CR-RT-005', 'Leo Mendoza', 'Security Guard', 'Security', 20.00, 'Retrain', '- Incident Reporting\n- Emergency Response', 75.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(10, 'CR-RS-001', 'Paolo Lim', 'Concierge', 'Front Office / Reception', 25.00, 'Reskilling', 'Effective Communication Skills:\r\n- Social media and digital communication skills\r\n\r\nHotel Systems & Reservation Knowledge:\r\n- online bookings\r\n\r\nProfessional Appearance & Etiquette:\r\n- Cultural etiquette for international guests\r\n\r\nTime Management & Multitasking:\r\n- Digital scheduling tools', 83.00, '2026-03-24', 'Created', '2026-01-23 04:15:33', '2026-01-22 23:08:49', '2026-01-23 04:15:33', 1),
(11, 'CR-RS-002', 'Karla Cruz', 'Laundry Attendant', 'Housekeeping', 35.00, 'Reskilling', '- Laundry Machine Operations\n- Quality Control', 80.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(12, 'CR-RS-003', 'James Torres', 'Line Cook', 'Kitchen / Culinary', 40.00, 'Reskilling', '- Cooking Techniques\n- Plating Standards', 80.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:36:33', 0),
(13, 'CR-RS-004', 'Nina Flores', 'Waiter', 'Food & Beverage (F&B)', 28.00, 'Reskilling', '- Upselling\n- Service Recovery', 80.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(14, 'CR-RS-005', 'Eric Ramos', 'Maintenance Technician', 'Engineering / Maintenance', 52.29, 'Refresher Training', 'Emergency Response Readiness:\r\n- Drills and simulations\r\n\r\nTechnical & Mechanical Knowledge:\r\n- Updated technical procedures', 27.00, '2026-03-24', 'Created', '2026-01-23 11:54:21', '2026-01-22 23:08:49', '2026-01-23 11:54:21', 1),
(15, 'CR-RF-001', 'Joyce Tan', 'Reservations Agent', 'Front Office / Reception', 45.00, 'Refresher Training', 'Cultural Awareness & Guest Sensitivity:\r\n- International guest expectations\r\n\r\nEffective Communication Skills:\r\n- Email etiquette\r\n- phone handling\r\n- professional messaging', 85.00, '2026-03-24', 'Created', '2026-01-23 04:44:37', '2026-01-22 23:08:49', '2026-01-23 04:44:37', 1),
(16, 'CR-RF-002', 'Cathy Aquino', 'Housekeeping Supervisor', 'Housekeeping', 55.00, 'Refresher Training', 'Attention to Detail:\r\n- Spot checks\r\n\r\nKnowledge of Cleaning Procedures & Chemicals:\r\n- Updated chemicals and safety\r\n\r\nPhysical Endurance & Task Discipline:\r\n- Ergonomics and repetitive task safety\r\n\r\nTeamwork & Coordination:\r\n- Cross-department interaction\r\n\r\nTime Management:\r\n- Prioritization at peak occupancy', 85.00, '2026-03-24', 'Created', '2026-01-23 04:14:23', '2026-01-22 23:08:49', '2026-01-23 04:14:23', 1),
(17, 'CR-RF-003', 'Allan Yu', 'Sous Chef', 'Kitchen / Culinary', 40.63, 'Refresher Training', '- Menu Planning\n- Cost Control', 85.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-23 11:53:23', 1),
(18, 'CR-RF-004', 'Grace Bautista', 'Bar Staff', 'Food & Beverage (F&B)', 50.00, 'Refresher Training', '- Beverage Knowledge\n- Hygiene & Sanitation', 85.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(19, 'CR-RF-005', 'Ronnie Diaz', 'Accountant', 'Finance / Accounting', 58.00, 'Refresher Training', 'Compliance & Policy Awareness:\r\n- Regulatory updates\r\n\r\nFinancial Accuracy & Attention to Detail:\r\n- Error-checking techniques', 85.00, '2026-03-24', 'Created', '2026-01-23 04:17:22', '2026-01-22 23:08:49', '2026-01-23 04:17:22', 1),
(20, 'CR-UP-001', 'Mika Villanueva', 'Front Office Supervisor', 'Front Office / Reception', 65.00, 'Upskilling', '- Supervisory Skills\n- Service Excellence', 90.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(21, 'CR-UP-002', 'Sophie Navarro', 'Housekeeping Manager', 'Housekeeping', 72.00, 'Upskilling', '- Department Management\n- Coaching & Feedback', 90.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(22, 'CR-UP-003', 'Daniel Perez', 'Chef de Partie', 'Kitchen / Culinary', 80.00, 'Upskilling', 'Attention to Quality & Presentation:\r\n- Creative presentation techniques\r\n\r\nEquipment Handling & Safety Awareness:\r\n- Equipment maintenance planning', 90.00, '2026-03-24', 'Created', '2026-01-23 04:23:18', '2026-01-22 23:08:49', '2026-01-23 04:23:18', 1),
(23, 'CR-UP-004', 'Trisha Ong', 'F&B Supervisor', 'Food & Beverage (F&B)', 70.00, 'Upskilling', '- Team Handling\n- Guest Experience', 90.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(24, 'CR-UP-005', 'Kevin Sy', 'HR Assistant', 'Human Resources (HR)', 78.00, 'Upskilling', '- Recruitment Process\n- Employee Relations', 90.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(25, 'CR-SR-001', 'Angela Co', 'Front Office Manager', 'Front Office / Reception', 85.00, 'Succession Ready', '- Leadership Development\n- Strategic Planning', 95.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(26, 'CR-SR-002', 'Patricia Lee', 'Executive Housekeeper', 'Housekeeping', 90.00, 'Succession Ready', '- Executive Leadership\n- Process Optimization', 95.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(27, 'CR-SR-003', 'Miguel Chua', 'Executive Chef', 'Kitchen / Culinary', 95.00, 'Succession Ready', '- Innovation & Menu Engineering\n- Cost Leadership', 95.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(28, 'CR-SR-004', 'Carlo Bautista', 'Restaurant Manager', 'Food & Beverage (F&B)', 88.00, 'Succession Ready', '- Strategic Guest Experience\n- Revenue Growth', 95.00, '2026-03-24', 'Pending', NULL, '2026-01-22 23:08:49', '2026-01-22 23:08:49', 0),
(29, 'CR-SR-005', 'Jasper Tan', 'Chief Engineer / Engineering Manager', 'Engineering / Maintenance', 56.86, 'Refresher Training', 'Documentation & Reporting Skills:\r\n- Updated reporting procedures\r\n\r\nEmergency Response Readiness:\r\n- Drills and simulations\r\n\r\nHealth & Safety Compliance:\r\n- Updated safety regulations\r\n\r\nPreventive Maintenance Skills:\r\n- Updated maintenance schedules', 70.00, '2026-03-24', 'Created', '2026-01-24 10:26:51', '2026-01-22 23:08:49', '2026-01-24 10:26:51', 1);

-- --------------------------------------------------------

--
-- Table structure for table `competency_criteria`
--

CREATE TABLE `competency_criteria` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `required_level` decimal(5,2) NOT NULL DEFAULT 80.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpis`
--

CREATE TABLE `kpis` (
  `id` int(11) NOT NULL,
  `kpi_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_kpi_scores`
--

CREATE TABLE `employee_kpi_scores` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `evaluation_period` varchar(50) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `criteria` varchar(255) NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `assessed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gap_formulations`
--

CREATE TABLE `kpi_gap_formulations` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `evaluation_period` varchar(50) NOT NULL,
  `overall_competency` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL,
  `details_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `competency_criteria`
--
ALTER TABLE `competency_criteria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_competency_name` (`name`);

--
-- Indexes for table `development_plan_items`
--
ALTER TABLE `development_plan_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_devplan` (`department`,`role`,`skill_id`,`status`),
  ADD KEY `idx_dept_status` (`department`,`status`),
  ADD KEY `fk_devplan_skill` (`skill_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_kpi_scores`
--
ALTER TABLE `employee_kpi_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_kpi_criteria` (`employee_id`,`evaluation_period`,`kpi_id`,`criteria`),
  ADD KEY `idx_emp_period` (`employee_id`,`evaluation_period`),
  ADD KEY `idx_emp_kpi_kpi` (`kpi_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_skill` (`employee_id`,`skill_id`),
  ADD KEY `fk_es_skill` (`skill_id`);

--
-- Indexes for table `general_skill_standards`
--
ALTER TABLE `general_skill_standards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_skill_standard` (`skill_id`),
  ADD KEY `idx_standard_skill` (`skill_id`);

--
-- Indexes for table `kpi_gap_formulations`
--
ALTER TABLE `kpi_gap_formulations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_period` (`employee_id`,`evaluation_period`),
  ADD KEY `idx_period` (`evaluation_period`),
  ADD KEY `idx_gap_employee` (`employee_id`);

--
-- Indexes for table `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kpi` (`kpi_name`,`department`);

--
-- Indexes for table `individual_development_plans`
--
ALTER TABLE `individual_development_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_idp` (`employee_id`),
  ADD KEY `idx_idp_status` (`idp_status`);

--
-- Indexes for table `requested_idps_repository`
--
ALTER TABLE `requested_idps_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_requested_idp` (`employee_id`),
  ADD KEY `idx_requested_idp_status` (`idp_status`);

--
-- Indexes for table `requested_to_idp`
--
ALTER TABLE `requested_to_idp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_requested_employee` (`employee_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_skill_department` (`skill_name`,`category`,`department`);

--
-- Indexes for table `succession_submissions`
--
ALTER TABLE `succession_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_submission` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2175;

--
-- AUTO_INCREMENT for table `development_plan_items`
--
ALTER TABLE `development_plan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=325;

--
-- AUTO_INCREMENT for table `competency_criteria`
--
ALTER TABLE `competency_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=649;

--
-- AUTO_INCREMENT for table `employee_kpi_scores`
--
ALTER TABLE `employee_kpi_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_skill_standards`
--
ALTER TABLE `general_skill_standards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=274;

--
-- AUTO_INCREMENT for table `individual_development_plans`
--
ALTER TABLE `individual_development_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `kpi_gap_formulations`
--
ALTER TABLE `kpi_gap_formulations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpis`
--
ALTER TABLE `kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requested_to_idp`
--
ALTER TABLE `requested_to_idp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15252;

--
-- AUTO_INCREMENT for table `succession_submissions`
--
ALTER TABLE `succession_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `development_plan_items`
--
ALTER TABLE `development_plan_items`
  ADD CONSTRAINT `fk_devplan_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `fk_es_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_es_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_kpi_scores`
--
ALTER TABLE `employee_kpi_scores`
  ADD CONSTRAINT `fk_emp_kpi_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_emp_kpi_kpi` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `general_skill_standards`
--
ALTER TABLE `general_skill_standards`
  ADD CONSTRAINT `fk_gss_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_gap_formulations`
--
ALTER TABLE `kpi_gap_formulations`
  ADD CONSTRAINT `fk_gap_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `individual_development_plans`
--
ALTER TABLE `individual_development_plans`
  ADD CONSTRAINT `fk_idp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `requested_idps_repository`
--
ALTER TABLE `requested_idps_repository`
  ADD CONSTRAINT `requested_idps_repository_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `succession_submissions`
--
ALTER TABLE `succession_submissions`
  ADD CONSTRAINT `fk_ss_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
