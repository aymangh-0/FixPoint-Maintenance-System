-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 08:45 PM
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
-- Database: `fixpoint`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `AssignmentID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `TechnicianID` int(11) NOT NULL,
  `AdminID` int(11) NOT NULL,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `StartedAt` timestamp NULL DEFAULT NULL,
  `CompletedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignment`
--

INSERT INTO `assignment` (`AssignmentID`, `RequestID`, `TechnicianID`, `AdminID`, `AssignedAt`, `StartedAt`, `CompletedAt`) VALUES
(1, 1, 2, 1, '2026-01-21 20:02:37', '2026-01-21 20:03:14', '2026-01-21 20:03:24'),
(2, 2, 2, 1, '2026-02-15 22:00:25', '2026-02-15 22:01:03', '2026-02-15 22:01:08'),
(3, 3, 2, 1, '2026-02-16 16:30:54', '2026-02-16 16:31:34', '2026-02-16 16:31:51'),
(4, 4, 3, 1, '2026-02-17 19:24:03', NULL, NULL),
(5, 5, 3, 1, '2026-02-18 18:18:54', NULL, NULL),
(6, 6, 3, 1, '2026-02-18 19:02:52', NULL, NULL),
(7, 7, 3, 1, '2026-03-02 21:58:43', NULL, NULL),
(8, 8, 3, 1, '2026-03-02 22:37:06', NULL, NULL),
(9, 9, 2, 1, '2026-03-02 23:28:30', NULL, NULL),
(10, 10, 2, 1, '2026-03-02 23:47:19', NULL, NULL),
(11, 11, 2, 1, '2026-03-02 23:54:57', NULL, NULL),
(12, 12, 2, 1, '2026-03-03 00:07:27', NULL, NULL),
(13, 13, 2, 1, '2026-03-03 21:22:54', '2026-03-04 18:37:22', '2026-03-04 18:37:39'),
(14, 14, 2, 1, '2026-03-04 18:22:45', NULL, NULL),
(15, 15, 2, 1, '2026-03-17 17:51:09', '2026-03-17 17:51:42', '2026-03-17 17:51:50'),
(16, 16, 2, 1, '2026-03-31 19:36:00', NULL, NULL),
(17, 17, 3, 1, '2026-03-31 20:17:12', '2026-04-09 13:31:08', '2026-04-09 13:31:33'),
(18, 18, 2, 1, '2026-04-01 00:22:33', NULL, NULL),
(19, 19, 2, 1, '2026-04-01 00:45:04', NULL, NULL),
(20, 20, 2, 1, '2026-04-04 17:36:55', NULL, NULL),
(21, 21, 3, 1, '2026-04-04 17:38:08', NULL, NULL),
(33, 33, 3, 1, '2026-04-09 13:52:41', '2026-04-09 13:53:49', '2026-04-09 13:54:19'),
(35, 35, 3, 1, '2026-04-12 01:25:10', '2026-04-12 01:27:34', '2026-04-12 01:28:08'),
(36, 36, 3, 1, '2026-05-09 14:19:23', '2026-05-09 14:20:41', '2026-05-09 14:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `AuditID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(100) NOT NULL,
  `TableName` varchar(100) DEFAULT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `OldValue` text DEFAULT NULL,
  `NewValue` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `PerformedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auditlog`
--

INSERT INTO `auditlog` (`AuditID`, `UserID`, `Action`, `TableName`, `RecordID`, `OldValue`, `NewValue`, `IPAddress`, `PerformedAt`) VALUES
(1, 1, 'ASSIGN_TECHNICIAN', 'assignment', 3, NULL, 'TechnicianID: 2', '::1', '2026-02-16 16:30:54'),
(2, 2, 'UPDATE_STATUS', 'maintenancerequest', 3, 'Status: Assigned', 'Status: In Progress', '::1', '2026-02-16 16:31:34'),
(3, 2, 'UPDATE_STATUS', 'maintenancerequest', 3, 'Status: Assigned', 'Status: In Progress', '::1', '2026-02-16 16:31:51'),
(4, 4, 'SUBMIT_FEEDBACK', 'feedback', 3, NULL, 'Rating: 5', '::1', '2026-02-16 16:37:00'),
(5, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 4, NULL, NULL, '::1', '2026-02-17 19:24:03'),
(6, 1, 'AUTO_ASSIGN', 'assignment', 4, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-02-17 19:24:03'),
(7, 1, 'UPDATE_STATUS', 'maintenancerequest', 4, 'Status: ', 'Status: ', '::1', '2026-02-17 19:24:37'),
(8, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 5, NULL, NULL, '::1', '2026-02-18 18:18:53'),
(9, 1, 'AUTO_ASSIGN', 'assignment', 5, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-02-18 18:18:54'),
(10, 1, 'UPDATE_STATUS', 'maintenancerequest', 5, 'Status: ', 'Status: ', '::1', '2026-02-18 18:20:32'),
(11, 4, 'SUBMIT_FEEDBACK', 'feedback', 5, NULL, 'Rating: 5', '::1', '2026-02-18 18:22:30'),
(12, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 6, NULL, NULL, '::1', '2026-02-18 19:02:51'),
(13, 1, 'AUTO_ASSIGN', 'assignment', 6, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-02-18 19:02:52'),
(14, 1, 'UPDATE_STATUS', 'maintenancerequest', 6, 'Status: ', 'Status: ', '::1', '2026-02-18 19:04:49'),
(15, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 7, NULL, NULL, '::1', '2026-03-02 21:58:41'),
(16, 1, 'AUTO_ASSIGN', 'assignment', 7, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-03-02 21:58:43'),
(17, 1, 'STATUS_CHANGED', 'maintenancerequest', 7, 'Status: 5', 'Status: 5', '::1', '2026-03-02 22:34:23'),
(18, 4, 'SUBMIT_FEEDBACK', 'feedback', 7, NULL, 'Rating: 5', '::1', '2026-03-02 22:36:14'),
(19, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 8, NULL, NULL, '::1', '2026-03-02 22:37:05'),
(20, 1, 'AUTO_ASSIGN', 'assignment', 8, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-03-02 22:37:06'),
(21, 1, 'STATUS_CHANGED', 'maintenancerequest', 8, 'Status: 3', 'Status: 5', '::1', '2026-03-02 22:37:41'),
(22, 4, 'SUBMIT_FEEDBACK', 'feedback', 8, NULL, 'Rating: 5', '::1', '2026-03-02 22:40:16'),
(23, 7, 'SUBMIT_REQUEST', 'maintenancerequest', 9, NULL, NULL, '::1', '2026-03-02 23:28:28'),
(24, 1, 'AUTO_ASSIGN', 'assignment', 9, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-02 23:28:30'),
(25, 1, 'STATUS_CHANGED', 'maintenancerequest', 9, 'Status: 3', 'Status: 5', '::1', '2026-03-02 23:29:24'),
(26, 7, 'SUBMIT_FEEDBACK', 'feedback', 9, NULL, 'Rating: 5', '::1', '2026-03-02 23:34:29'),
(27, 7, 'SUBMIT_REQUEST', 'maintenancerequest', 10, NULL, NULL, '::1', '2026-03-02 23:47:18'),
(28, 1, 'AUTO_ASSIGN', 'assignment', 10, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-02 23:47:19'),
(29, 1, 'STATUS_CHANGED', 'maintenancerequest', 10, 'Status: 3', 'Status: 5', '::1', '2026-03-02 23:51:47'),
(30, 6, 'SUBMIT_REQUEST', 'maintenancerequest', 11, NULL, NULL, '::1', '2026-03-02 23:54:56'),
(31, 1, 'AUTO_ASSIGN', 'assignment', 11, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-02 23:54:57'),
(32, 1, 'STATUS_CHANGED', 'maintenancerequest', 11, 'Status: 3', 'Status: 5', '::1', '2026-03-02 23:58:10'),
(33, 1, 'STATUS_CHANGED', 'maintenancerequest', 7, 'Status: 5', 'Status: 4', '::1', '2026-03-03 00:02:32'),
(34, 1, 'STATUS_CHANGED', 'maintenancerequest', 7, 'Status: 4', 'Status: 5', '::1', '2026-03-03 00:02:39'),
(35, 8, 'SUBMIT_REQUEST', 'maintenancerequest', 12, NULL, NULL, '::1', '2026-03-03 00:07:26'),
(36, 1, 'AUTO_ASSIGN', 'assignment', 12, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-03 00:07:27'),
(38, 1, 'STATUS_CHANGED', 'maintenancerequest', 12, 'Status: 5', 'Status: 3', '::1', '2026-03-03 20:33:30'),
(39, 1, 'STATUS_CHANGED', 'maintenancerequest', 12, 'Status: 3', 'Status: 5', '::1', '2026-03-03 20:35:08'),
(40, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 13, NULL, NULL, '::1', '2026-03-03 21:22:52'),
(41, 1, 'AUTO_ASSIGN', 'assignment', 13, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-03 21:22:54'),
(42, 2, 'UPDATE_STATUS', 'maintenancerequest', 13, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-03 21:23:36'),
(43, 2, 'UPDATE_STATUS', 'maintenancerequest', 13, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-03 21:23:46'),
(44, 1, 'STATUS_CHANGED', 'maintenancerequest', 13, 'Status: 5', 'Status: 1', '::1', '2026-03-03 21:31:42'),
(45, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 14, NULL, NULL, '::1', '2026-03-04 18:22:43'),
(46, 1, 'AUTO_ASSIGN', 'assignment', 14, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-04 18:22:45'),
(47, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 1', 'PriorityID: 2', '::1', '2026-03-04 18:26:23'),
(48, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 18:26:41'),
(49, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 18:26:47'),
(50, 1, 'STATUS_CHANGED', 'maintenancerequest', 13, 'Status: 1', 'Status: 5', '::1', '2026-03-04 18:30:25'),
(51, 1, 'STATUS_CHANGED', 'maintenancerequest', 13, 'Status: 5', 'Status: 1', '::1', '2026-03-04 18:30:34'),
(52, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 2', 'PriorityID: 1', '::1', '2026-03-04 18:37:03'),
(53, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 1', 'PriorityID: 2', '::1', '2026-03-04 18:37:09'),
(54, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 2', 'PriorityID: 3', '::1', '2026-03-04 18:37:13'),
(55, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 3', 'PriorityID: 4', '::1', '2026-03-04 18:37:17'),
(56, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 13, 'PriorityID: 4', 'PriorityID: 2', '::1', '2026-03-04 18:37:21'),
(57, 2, 'UPDATE_STATUS', 'maintenancerequest', 13, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 18:37:22'),
(58, 2, 'UPDATE_STATUS', 'maintenancerequest', 13, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 18:37:39'),
(59, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 5', 'Status: 1', '::1', '2026-03-04 20:27:06'),
(60, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 2', 'PriorityID: 1', '::1', '2026-03-04 20:27:15'),
(61, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 1', 'PriorityID: 2', '::1', '2026-03-04 20:27:21'),
(62, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 20:27:48'),
(63, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 4', 'Status: 3', '::1', '2026-03-04 20:28:15'),
(64, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 20:28:44'),
(65, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-04 20:29:27'),
(66, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 5', 'Status: 3', '::1', '2026-03-05 05:03:42'),
(67, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 2', 'PriorityID: 3', '::1', '2026-03-05 05:03:58'),
(68, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 3', 'PriorityID: 2', '::1', '2026-03-05 05:04:01'),
(69, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 2', 'PriorityID: 4', '::1', '2026-03-05 05:04:22'),
(70, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 4', 'PriorityID: 2', '::1', '2026-03-05 05:04:24'),
(71, 2, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 2', 'PriorityID: 1', '::1', '2026-03-05 05:06:08'),
(72, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-05 05:06:40'),
(73, 2, 'UPDATE_STATUS', 'maintenancerequest', 14, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-05 05:06:50'),
(74, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 5', 'Status: 3', '::1', '2026-03-11 04:00:13'),
(75, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 1', 'PriorityID: 2', '::1', '2026-03-11 04:00:17'),
(76, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 2', 'PriorityID: 3', '::1', '2026-03-11 04:00:20'),
(77, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 3', 'Status: 5', '::1', '2026-03-11 04:00:27'),
(78, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 15, NULL, NULL, '::1', '2026-03-17 17:51:07'),
(79, 1, 'AUTO_ASSIGN', 'assignment', 15, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-17 17:51:09'),
(80, 2, 'UPDATE_STATUS', 'maintenancerequest', 15, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-17 17:51:42'),
(81, 2, 'UPDATE_STATUS', 'maintenancerequest', 15, 'Status: Assigned', 'Status: In Progress', '::1', '2026-03-17 17:51:50'),
(82, 10, 'SUBMIT_FEEDBACK', 'feedback', 15, NULL, 'Rating: 5', '::1', '2026-03-17 17:52:22'),
(83, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 5', 'Status: 3', '::1', '2026-03-17 20:08:19'),
(84, 1, 'PRIORITY_CHANGED', 'maintenancerequest', 14, 'PriorityID: 3', 'PriorityID: 4', '::1', '2026-03-17 20:08:22'),
(85, 1, 'STATUS_CHANGED', 'maintenancerequest', 14, 'Status: 3', 'Status: 5', '::1', '2026-03-17 20:08:41'),
(86, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 16, NULL, NULL, '::1', '2026-03-31 19:35:55'),
(87, 1, 'AUTO_ASSIGN', 'assignment', 16, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-31 19:36:00'),
(88, 1, 'STATUS_CHANGED', 'maintenancerequest', 16, 'Status: 3', 'Status: 6', '::1', '2026-03-31 19:38:33'),
(89, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 17, NULL, NULL, '::1', '2026-03-31 20:17:10'),
(90, 1, 'AUTO_ASSIGN', 'assignment', 17, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-03-31 20:17:12'),
(91, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 18, NULL, NULL, '::1', '2026-04-01 00:22:31'),
(92, 1, 'AUTO_ASSIGN', 'assignment', 18, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-01 00:22:33'),
(93, 1, 'STATUS_CHANGED', 'maintenancerequest', 18, 'Status: 3', 'Status: 4', '::1', '2026-04-01 00:27:12'),
(94, 1, 'STATUS_CHANGED', 'maintenancerequest', 18, 'Status: 4', 'Status: 5', '::1', '2026-04-01 00:27:52'),
(95, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 19, NULL, NULL, '::1', '2026-04-01 00:45:00'),
(96, 1, 'AUTO_ASSIGN', 'assignment', 19, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-01 00:45:04'),
(97, 1, 'STATUS_CHANGED', 'maintenancerequest', 19, 'Status: 3', 'Status: 5', '::1', '2026-04-01 00:45:52'),
(98, 1, 'STATUS_CHANGED', 'maintenancerequest', 17, 'Status: 3', 'Status: 5', '::1', '2026-04-01 00:46:15'),
(99, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 20, NULL, NULL, '::1', '2026-04-04 17:36:52'),
(100, 1, 'AUTO_ASSIGN', 'assignment', 20, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-04 17:36:55'),
(101, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 21, NULL, NULL, '::1', '2026-04-04 17:38:05'),
(102, 1, 'AUTO_ASSIGN', 'assignment', 21, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-04 17:38:08'),
(103, 1, 'STATUS_CHANGED', 'maintenancerequest', 21, 'Status: 3', 'Status: 5', '::1', '2026-04-04 17:56:05'),
(104, 1, 'STATUS_CHANGED', 'maintenancerequest', 20, 'Status: 3', 'Status: 5', '::1', '2026-04-04 17:56:15'),
(105, 6, 'SUBMIT_REQUEST', 'maintenancerequest', 22, NULL, NULL, '::1', '2026-04-05 18:43:25'),
(106, 1, 'AUTO_ASSIGN', 'assignment', 22, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-05 18:43:28'),
(107, 6, 'SUBMIT_REQUEST', 'maintenancerequest', 23, NULL, NULL, '::1', '2026-04-05 18:47:36'),
(108, 1, 'AUTO_ASSIGN', 'assignment', 23, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-05 18:47:39'),
(109, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 24, NULL, NULL, '::1', '2026-04-05 22:31:49'),
(110, 1, 'AUTO_ASSIGN', 'assignment', 24, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-05 22:31:52'),
(111, 10, 'SUBMIT_REQUEST', 'maintenancerequest', 25, NULL, NULL, '::1', '2026-04-05 22:34:39'),
(112, 1, 'AUTO_ASSIGN', 'assignment', 25, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-05 22:34:44'),
(113, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 26, NULL, NULL, '::1', '2026-04-06 13:22:22'),
(114, 1, 'AUTO_ASSIGN', 'assignment', 26, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-06 13:22:26'),
(115, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 27, NULL, NULL, '::1', '2026-04-06 13:35:48'),
(116, 1, 'AUTO_ASSIGN', 'assignment', 27, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-06 13:35:52'),
(117, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 28, NULL, NULL, '::1', '2026-04-06 13:38:13'),
(118, 1, 'AUTO_ASSIGN', 'assignment', 28, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-06 13:38:16'),
(119, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 29, NULL, NULL, '::1', '2026-04-06 13:54:19'),
(120, 1, 'AUTO_ASSIGN', 'assignment', 29, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-06 13:54:19'),
(121, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 30, NULL, NULL, '::1', '2026-04-06 17:48:00'),
(122, 1, 'AUTO_ASSIGN', 'assignment', 30, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-06 17:48:00'),
(123, 4, 'REQUEST_CANCELLED_BY_USER', 'maintenancerequest', 30, 'Status: 3', 'Status: 6 (Cancelled)', '::1', '2026-04-06 17:48:26'),
(124, 12, 'PASSWORD_RESET', 'user', 12, NULL, 'Password reset via forgot password', '::1', '2026-04-08 19:12:42'),
(125, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 31, NULL, NULL, '::1', '2026-04-08 20:59:42'),
(126, 1, 'AUTO_ASSIGN', 'assignment', 31, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-08 20:59:42'),
(127, 4, 'REQUEST_CANCELLED_BY_USER', 'maintenancerequest', 31, 'Status: 3', 'Status: 6 (Cancelled)', '::1', '2026-04-08 21:00:17'),
(128, 12, 'PASSWORD_RESET', 'user', 12, NULL, 'Password reset via forgot password', '::1', '2026-04-08 21:31:49'),
(129, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 32, NULL, NULL, '::1', '2026-04-08 21:32:48'),
(130, 1, 'AUTO_ASSIGN', 'assignment', 32, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-08 21:32:48'),
(131, 12, 'REQUEST_CANCELLED_BY_USER', 'maintenancerequest', 32, 'Status: 3', 'Status: 6 (Cancelled)', '::1', '2026-04-08 21:33:25'),
(132, 1, 'STATUS_CHANGED', 'maintenancerequest', 17, 'Status: 5', 'Status: 1', '::1', '2026-04-08 22:49:07'),
(133, 1, 'STATUS_CHANGED', 'maintenancerequest', 17, 'Status: 1', 'Status: 3', '::1', '2026-04-08 22:56:08'),
(134, 3, 'UPDATE_STATUS', 'maintenancerequest', 17, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-09 13:31:08'),
(135, 3, 'UPDATE_STATUS', 'maintenancerequest', 17, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-09 13:31:33'),
(136, 12, 'SUBMIT_REQUEST', 'maintenancerequest', 33, NULL, NULL, '::1', '2026-04-09 13:52:41'),
(137, 1, 'AUTO_ASSIGN', 'assignment', 33, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-09 13:52:41'),
(138, 3, 'UPDATE_STATUS', 'maintenancerequest', 33, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-09 13:53:49'),
(139, 3, 'UPDATE_STATUS', 'maintenancerequest', 33, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-09 13:54:19'),
(140, 4, 'SUBMIT_FEEDBACK', 'feedback', 17, NULL, 'Rating: 5', '::1', '2026-04-12 01:10:36'),
(141, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 34, NULL, NULL, '::1', '2026-04-12 01:12:04'),
(142, 1, 'AUTO_ASSIGN', 'assignment', 34, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-04-12 01:12:04'),
(143, 4, 'REQUEST_CANCELLED_BY_USER', 'maintenancerequest', 34, 'Status: 3', 'Status: 6 (Cancelled)', '::1', '2026-04-12 01:12:24'),
(144, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 35, NULL, NULL, '::1', '2026-04-12 01:25:10'),
(145, 1, 'AUTO_ASSIGN', 'assignment', 35, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-04-12 01:25:10'),
(146, 3, 'UPDATE_STATUS', 'maintenancerequest', 35, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-12 01:27:34'),
(147, 3, 'UPDATE_STATUS', 'maintenancerequest', 35, 'Status: Assigned', 'Status: In Progress', '::1', '2026-04-12 01:28:08'),
(148, 4, 'SUBMIT_FEEDBACK', 'feedback', 35, NULL, 'Rating: 5', '::1', '2026-05-09 04:34:50'),
(149, 4, 'SUBMIT_REQUEST', 'maintenancerequest', 36, NULL, NULL, '::1', '2026-05-09 14:19:23'),
(150, 1, 'AUTO_ASSIGN', 'assignment', 36, NULL, 'Auto-assigned to Khalid Abdullah (UserID: 3) - Active tasks: 0', '::1', '2026-05-09 14:19:23'),
(151, 3, 'UPDATE_STATUS', 'maintenancerequest', 36, 'Status: Assigned', 'Status: In Progress', '::1', '2026-05-09 14:20:41'),
(152, 3, 'UPDATE_STATUS', 'maintenancerequest', 36, 'Status: In Progress', 'Status: Completed', '::1', '2026-05-09 14:20:53'),
(153, 4, 'SUBMIT_FEEDBACK', 'feedback', 36, NULL, 'Rating: 5', '::1', '2026-05-09 14:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`) VALUES
(1, 'Electrical', 'Electrical systems and lighting issues'),
(2, 'Plumbing', 'Water, drainage, and plumbing problems'),
(3, 'HVAC', 'Heating, ventilation, and air conditioning'),
(4, 'IT Equipment', 'Computers, projectors, network issues'),
(5, 'Furniture', 'Desks, chairs, and furniture repairs'),
(6, 'Cleaning', 'Cleaning and sanitation requests'),
(7, 'Safety', 'Safety hazards and emergency issues'),
(8, 'Other', 'Other maintenance needs');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `FeedbackID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`FeedbackID`, `RequestID`, `UserID`, `Rating`, `Comment`, `SubmittedAt`) VALUES
(1, 1, 4, 5, '', '2026-01-21 20:04:55'),
(2, 2, 4, 5, 'Thanks', '2026-02-15 22:02:26'),
(3, 3, 4, 5, 'Thanks', '2026-02-16 16:37:00'),
(4, 5, 4, 5, '', '2026-02-18 18:22:29'),
(5, 7, 4, 5, '', '2026-03-02 22:36:13'),
(6, 8, 4, 5, 'GOOD', '2026-03-02 22:40:15'),
(7, 9, 7, 5, '', '2026-03-02 23:34:27'),
(8, 15, 10, 5, '', '2026-03-17 17:52:20'),
(9, 17, 4, 5, '', '2026-04-12 01:10:34'),
(10, 35, 4, 5, '', '2026-05-09 04:34:48'),
(11, 36, 4, 5, 'Thanks', '2026-05-09 14:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `LocationID` int(11) NOT NULL,
  `BuildingName` varchar(100) NOT NULL,
  `FloorNumber` varchar(20) DEFAULT NULL,
  `RoomNumber` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`LocationID`, `BuildingName`, `FloorNumber`, `RoomNumber`, `Description`) VALUES
(1, 'Main Building', 'Ground Floor', '101', 'Main entrance hall'),
(2, 'Main Building', 'First Floor', '201', 'Computer Lab 1'),
(3, 'Main Building', 'Second Floor', '301', 'Lecture Hall A'),
(4, 'Library Building', 'Ground Floor', 'Reading Hall', 'Main reading area'),
(5, 'Library Building', 'First Floor', 'Study Rooms', 'Group study area'),
(6, 'Engineering Building', 'Ground Floor', 'Lab-A', 'Engineering Laboratory'),
(7, 'Student Center', 'Ground Floor', 'Cafeteria', 'Student cafeteria');

-- --------------------------------------------------------

--
-- Table structure for table `loginlog`
--

CREATE TABLE `loginlog` (
  `LogID` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Status` enum('Success','Failed') NOT NULL,
  `FailReason` varchar(255) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` varchar(500) DEFAULT NULL,
  `AttemptedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loginlog`
--

INSERT INTO `loginlog` (`LogID`, `Email`, `UserID`, `Status`, `FailReason`, `IPAddress`, `UserAgent`, `AttemptedAt`) VALUES
(211, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:26:51'),
(212, 'khalid.tech@seu.edu.sa', 3, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:27:15'),
(213, 'user12342188@gmail.com', 12, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:39:49'),
(214, 'khalid.tech@seu.edu.sa', 3, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:53:45'),
(215, 'user12342188@gmail.com', 12, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:56:51'),
(216, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 14:44:43'),
(217, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-12 01:06:47'),
(218, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-12 01:18:51'),
(219, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-12 01:24:43'),
(220, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-12 01:25:02'),
(221, 'khalid.tech@seu.edu.sa', 3, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-12 01:25:30'),
(222, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 04:11:54'),
(224, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:10:47'),
(225, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:38:17'),
(226, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:41:06'),
(227, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:43:38'),
(228, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:44:27'),
(229, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:45:27'),
(230, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:46:31'),
(231, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 01:46:46'),
(232, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 02:25:16'),
(233, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 03:07:51'),
(234, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-26 14:21:13'),
(235, 'Admin@123', NULL, 'Failed', 'Email not found', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-27 21:48:19'),
(236, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-27 21:48:33'),
(237, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 02:39:26'),
(238, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 02:50:14'),
(239, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 02:50:31'),
(240, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 02:58:48'),
(241, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 03:10:40'),
(242, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 03:11:08'),
(243, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 03:11:32'),
(244, 'admin@seu.edu.sa', 1, 'Failed', 'Incorrect password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 03:53:29'),
(245, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 03:53:36'),
(246, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 04:34:30'),
(247, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:17:58'),
(248, 'khalid.tech@seu.edu.sa', 3, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:20:20'),
(249, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:21:29'),
(250, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:22:23'),
(251, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:42:09'),
(252, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:45:54'),
(253, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:48:01'),
(254, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:48:28'),
(255, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:48:43'),
(256, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:49:08'),
(257, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 18:35:12'),
(258, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 18:35:43'),
(259, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 18:36:02'),
(260, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 18:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `maintenancerequest`
--

CREATE TABLE `maintenancerequest` (
  `RequestID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `LocationID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `PriorityID` int(11) NOT NULL,
  `StatusID` int(11) NOT NULL DEFAULT 1,
  `Title` varchar(200) NOT NULL,
  `Description` text NOT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CompletedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenancerequest`
--

INSERT INTO `maintenancerequest` (`RequestID`, `UserID`, `LocationID`, `CategoryID`, `PriorityID`, `StatusID`, `Title`, `Description`, `SubmittedAt`, `UpdatedAt`, `CompletedAt`) VALUES
(1, 4, 6, 1, 3, 5, 'Broken AC', 'not working', '2026-01-21 19:47:19', '2026-01-21 20:03:24', '2026-01-21 20:03:24'),
(2, 4, 4, 3, 1, 5, 'sdasqwdqwd', 'dqwdqwdqwdqwdqwdqwdqwdqwdqwd', '2026-02-15 21:59:27', '2026-02-15 22:01:08', '2026-02-15 22:01:08'),
(3, 4, 6, 6, 2, 5, 'asdasdasdasd', 'asdasdasdasdasdasdasd', '2026-02-16 01:23:08', '2026-02-16 16:31:51', '2026-02-16 16:31:51'),
(4, 10, 6, 6, 2, 5, 'asdasdasdasdasd', 'asdasdasdasdasdasdasd', '2026-02-17 19:24:03', '2026-02-17 19:24:37', '2026-02-17 19:24:37'),
(5, 4, 6, 6, 2, 5, 'asdjhaslidjasd', 'asijdiasjdasjdasjdasjd', '2025-12-31 21:00:00', '2026-02-18 19:04:24', '2026-02-18 18:20:32'),
(6, 4, 1, 1, 3, 5, 'شسييشسيشسيشسيشسي', 'شسيشسيشسيشسيشسيشسيشيشس', '2026-02-18 19:02:51', '2026-02-18 19:04:49', '2026-02-18 19:04:49'),
(7, 4, 6, 6, 2, 5, 'asdasdasdasdasd', 'asdasdasdasdasdasdasdasda', '2026-03-02 21:58:41', '2026-03-03 00:02:39', '2026-03-02 22:25:09'),
(8, 4, 6, 6, 2, 5, 'يبليسبلشسثبشسثبشصثسبشصث', 'ثبلشسثبشصثقبصشثقبشصثق', '2026-03-02 22:37:05', '2026-03-02 22:37:41', '2026-03-02 22:37:41'),
(9, 7, 6, 3, 2, 5, 'srfwsaerfqweafweas', 'wefwefwefqwefwefwefwefwe', '2026-03-02 23:28:28', '2026-03-02 23:29:24', '2026-03-02 23:29:24'),
(10, 7, 6, 3, 2, 5, 'حخلتسبخحهلمتسيبخكلتسيقبلكخت', 'سيلظمنسىثتةلمنستيلهمتسيلنمتس', '2026-03-02 23:47:18', '2026-03-02 23:51:47', '2026-03-02 23:51:47'),
(11, 6, 6, 6, 2, 5, 'asdasdasdasdasdasd', 'asdasdasdasdasdasdasdas', '2026-03-02 23:54:56', '2026-03-02 23:58:10', '2026-03-02 23:58:10'),
(12, 8, 6, 6, 2, 5, 'asdfafasfdasdasdasd', 'aerfwaefqaefaefaewfqawfd', '2026-03-03 00:07:26', '2026-03-03 20:35:08', '2026-03-03 00:15:29'),
(13, 12, 6, 1, 2, 5, 'asdhjashdjashhj', 'akjsdhasjkhdaksdjahjh', '2026-03-03 21:22:52', '2026-03-04 18:37:39', '2026-03-04 18:37:39'),
(14, 12, 6, 6, 4, 5, '', 'No description provided', '2026-03-04 18:22:43', '2026-03-17 20:08:41', '2026-03-17 20:08:41'),
(15, 10, 6, 6, 2, 5, 'dfsdfs', 'dfsdfs', '2026-03-17 17:51:07', '2026-03-17 17:51:50', '2026-03-17 17:51:50'),
(16, 4, 6, 6, 2, 6, '', 'No description provided', '2026-03-31 19:35:55', '2026-03-31 19:38:33', NULL),
(17, 4, 6, 6, 2, 5, '', 'No description provided', '2026-03-31 20:17:10', '2026-04-09 13:31:33', '2026-04-09 13:31:33'),
(18, 12, 6, 7, 2, 5, '', 'No description provided', '2026-04-01 00:22:31', '2026-04-01 00:27:52', '2026-04-01 00:27:52'),
(19, 12, 6, 5, 2, 5, '', 'No description provided', '2026-04-01 00:45:00', '2026-04-01 00:45:52', '2026-04-01 00:45:52'),
(20, 10, 6, 6, 2, 5, '', 'No description provided', '2026-04-04 17:36:52', '2026-04-04 17:56:15', '2026-04-04 17:56:15'),
(21, 10, 7, 5, 2, 5, '', 'No description provided', '2026-04-04 17:38:05', '2026-04-04 17:56:05', '2026-04-04 17:56:05'),
(26, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-06 13:22:22', '2026-04-06 13:22:56', NULL),
(27, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-06 13:35:48', '2026-04-06 13:36:07', NULL),
(28, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-06 13:38:13', '2026-04-06 13:38:36', NULL),
(29, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-06 13:54:19', '2026-04-06 13:54:49', NULL),
(30, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-06 17:48:00', '2026-04-06 17:48:26', NULL),
(31, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-08 20:59:42', '2026-04-08 21:00:17', NULL),
(32, 12, 6, 6, 2, 6, '', 'No description provided', '2026-04-08 21:32:48', '2026-04-08 21:33:25', NULL),
(33, 12, 6, 6, 2, 5, '', 'No description provided', '2026-04-09 13:52:41', '2026-04-09 13:54:19', '2026-04-09 13:54:19'),
(34, 4, 6, 6, 2, 6, '', 'No description provided', '2026-04-12 01:12:04', '2026-04-12 01:12:24', NULL),
(35, 4, 6, 6, 2, 5, '', 'No description provided', '2026-04-12 01:25:10', '2026-04-12 01:28:08', '2026-04-12 01:28:08'),
(36, 4, 1, 1, 2, 5, 'The air conditioner switch is not working', 'The air conditioner switch is not working', '2026-05-09 14:19:23', '2026-05-09 14:20:53', '2026-05-09 14:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `RequestID` int(11) DEFAULT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`NotificationID`, `UserID`, `Title`, `RequestID`, `Message`, `IsRead`, `CreatedAt`) VALUES
(3, 2, '', 1, 'New maintenance request #1 has been assigned to you', 1, '2026-01-21 20:02:37'),
(9, 2, '', 2, 'New maintenance request #2 has been assigned to you', 1, '2026-02-15 22:00:25'),
(15, 2, '', 3, 'New maintenance request #3 has been assigned to you', 1, '2026-02-16 16:30:54'),
(20, 3, '', 4, 'You have been auto-assigned to request #4', 1, '2026-02-17 19:24:03'),
(22, 10, '', 4, 'Your request #4 status changed to: Completed', 1, '2026-02-17 19:24:37'),
(25, 3, '', 5, 'You have been auto-assigned to request #5', 1, '2026-02-18 18:18:54'),
(30, 3, '', 6, 'You have been auto-assigned to request #6', 1, '2026-02-18 19:02:52'),
(38, 3, '', 7, 'You have been auto-assigned to request #7', 1, '2026-03-02 21:58:43'),
(45, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 1, '2026-03-02 22:32:15'),
(47, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 1, '2026-03-02 22:34:23'),
(50, 3, '', 8, 'You have been auto-assigned to request #8', 1, '2026-03-02 22:37:06'),
(52, 4, 'Request Status Updated', 8, 'Your request #8 status has been updated to: Completed', 1, '2026-03-02 22:37:41'),
(57, 2, '', 9, 'You have been auto-assigned to request #9', 1, '2026-03-02 23:28:30'),
(59, 7, 'Request Status Updated', 9, 'Your request #9 status has been updated to: Completed', 1, '2026-03-02 23:29:24'),
(62, 2, '', 10, 'You have been auto-assigned to request #10', 1, '2026-03-02 23:47:19'),
(64, 7, 'Request Status Updated', 10, 'Your request #10 status has been updated to: Completed', 0, '2026-03-02 23:51:47'),
(66, 2, '', 11, 'You have been auto-assigned to request #11', 1, '2026-03-02 23:54:57'),
(68, 6, 'Request Status Updated', 11, 'Your request #11 status has been updated to: Completed', 1, '2026-03-02 23:58:10'),
(69, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: In Progress', 1, '2026-03-03 00:02:32'),
(70, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 1, '2026-03-03 00:02:39'),
(72, 2, '', 12, 'You have been auto-assigned to request #12', 1, '2026-03-03 00:07:27'),
(74, 8, 'Request Status Updated', 12, 'Your request #12 status has been updated to: Completed', 0, '2026-03-03 00:15:29'),
(76, 8, 'Request Status Updated', 12, 'Your request #12 status has been updated to: Assigned', 0, '2026-03-03 20:33:30'),
(77, 8, 'Request Status Updated', 12, 'Your request #12 status has been updated to: Completed', 0, '2026-03-03 20:35:08'),
(79, 2, '', 13, 'You have been auto-assigned to request #13', 1, '2026-03-03 21:22:54'),
(81, 12, '', 13, 'Your request #13 is now being worked on by a technician', 1, '2026-03-03 21:23:36'),
(82, 12, '', 13, 'Your request #13 has been completed! Please review and provide feedback.', 1, '2026-03-03 21:23:46'),
(83, 12, 'Request Status Updated', 13, 'Your request #13 status has been updated to: Pending', 1, '2026-03-03 21:31:42'),
(85, 2, '', 14, 'You have been auto-assigned to request #14', 1, '2026-03-04 18:22:45'),
(87, 12, '', 14, 'Your request #14 is now being worked on by a technician', 1, '2026-03-04 18:26:41'),
(88, 12, '', 14, 'Your request #14 has been completed! Please review and provide feedback.', 1, '2026-03-04 18:26:47'),
(89, 12, 'Request Status Updated', 13, 'Your request #13 status has been updated to: Completed', 1, '2026-03-04 18:30:25'),
(90, 12, 'Request Status Updated', 13, 'Your request #13 status has been updated to: Pending', 1, '2026-03-04 18:30:34'),
(91, 12, '', 13, 'Your request #13 is now being worked on by a technician', 1, '2026-03-04 18:37:22'),
(92, 12, '', 13, 'Your request #13 has been completed! Please review and provide feedback.', 1, '2026-03-04 18:37:39'),
(93, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Pending', 1, '2026-03-04 20:27:06'),
(94, 12, '', 14, 'Your request #14 is now being worked on by a technician', 1, '2026-03-04 20:27:48'),
(95, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Assigned', 1, '2026-03-04 20:28:15'),
(96, 12, '', 14, 'Your request #14 is now being worked on by a technician', 1, '2026-03-04 20:28:44'),
(97, 12, '', 14, 'Your request #14 has been completed! Please review and provide feedback.', 1, '2026-03-04 20:29:27'),
(98, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Assigned', 1, '2026-03-05 05:03:42'),
(99, 12, '', 14, 'Your request #14 is now being worked on by a technician', 1, '2026-03-05 05:06:40'),
(100, 12, '', 14, 'Your request #14 has been completed! Please review and provide feedback.', 1, '2026-03-05 05:06:50'),
(101, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Assigned', 1, '2026-03-11 04:00:13'),
(102, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Completed', 1, '2026-03-11 04:00:27'),
(104, 2, '', 15, 'You have been auto-assigned to request #15', 1, '2026-03-17 17:51:09'),
(106, 10, '', 15, 'Your request #15 is now being worked on by a technician', 1, '2026-03-17 17:51:42'),
(107, 10, '', 15, 'Your request #15 has been completed! Please review and provide feedback.', 1, '2026-03-17 17:51:50'),
(109, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Assigned', 1, '2026-03-17 20:08:19'),
(110, 12, 'Request Status Updated', 14, 'Your request #14 status has been updated to: Completed', 1, '2026-03-17 20:08:41'),
(112, 2, '', 16, 'You have been auto-assigned to request #16', 1, '2026-03-31 19:36:00'),
(114, 4, 'Request Status Updated', 16, 'Your request #16 status has been updated to: Cancelled', 1, '2026-03-31 19:38:33'),
(116, 3, '', 17, 'You have been auto-assigned to request #17', 1, '2026-03-31 20:17:12'),
(119, 2, '', 18, 'You have been auto-assigned to request #18', 1, '2026-04-01 00:22:33'),
(121, 12, 'Request Status Updated', 18, 'Your request #18 status has been updated to: In Progress', 1, '2026-04-01 00:27:12'),
(122, 12, 'Request Status Updated', 18, 'Your request #18 status has been updated to: Completed', 1, '2026-04-01 00:27:52'),
(123, 1, '', 19, 'New maintenance request #19 submitted by ayn', 1, '2026-04-01 00:45:00'),
(124, 2, '', 19, 'You have been auto-assigned to request #19', 1, '2026-04-01 00:45:04'),
(125, 1, '', 19, 'Request #19 was auto-assigned to Ahmed Hassan', 1, '2026-04-01 00:45:04'),
(126, 12, 'Request Status Updated', 19, 'Your request #19 status has been updated to: Completed', 1, '2026-04-01 00:45:52'),
(127, 4, 'Request Status Updated', 17, 'Your request #17 status has been updated to: Completed', 1, '2026-04-01 00:46:15'),
(128, 1, '', 20, 'New maintenance request #20 submitted by Dr. Jameel Alhejely', 1, '2026-04-04 17:36:52'),
(129, 2, '', 20, 'You have been auto-assigned to request #20', 0, '2026-04-04 17:36:55'),
(130, 1, '', 20, 'Request #20 was auto-assigned to Ahmed Hassan', 1, '2026-04-04 17:36:55'),
(131, 1, '', 21, 'New maintenance request #21 submitted by Dr. Jameel Alhejely', 1, '2026-04-04 17:38:05'),
(132, 3, '', 21, 'You have been auto-assigned to request #21', 1, '2026-04-04 17:38:08'),
(133, 1, '', 21, 'Request #21 was auto-assigned to Khalid Abdullah', 1, '2026-04-04 17:38:08'),
(134, 10, 'Request Status Updated', 21, 'Your request #21 status has been updated to: Completed', 1, '2026-04-04 17:56:05'),
(135, 10, 'Request Status Updated', 20, 'Your request #20 status has been updated to: Completed', 1, '2026-04-04 17:56:15'),
(148, 1, '', 26, 'New maintenance request #26 submitted by Ayman GH', 1, '2026-04-06 13:22:22'),
(149, 3, '', 26, 'You have been auto-assigned to request #26', 1, '2026-04-06 13:22:26'),
(150, 1, '', 26, 'Request #26 was auto-assigned to Khalid Abdullah', 1, '2026-04-06 13:22:26'),
(151, 1, '', 27, 'New maintenance request #27 submitted by Ayman GH', 1, '2026-04-06 13:35:48'),
(152, 3, '', 27, 'You have been auto-assigned to request #27', 1, '2026-04-06 13:35:52'),
(153, 1, '', 27, 'Request #27 was auto-assigned to Khalid Abdullah', 1, '2026-04-06 13:35:52'),
(154, 1, '', 28, 'New maintenance request #28 submitted by Ayman GH', 1, '2026-04-06 13:38:13'),
(155, 2, '', 28, 'You have been auto-assigned to request #28', 0, '2026-04-06 13:38:16'),
(156, 1, '', 28, 'Request #28 was auto-assigned to Ahmed Hassan', 1, '2026-04-06 13:38:16'),
(157, 1, '', 29, 'New maintenance request #29 submitted by Ayman GH', 1, '2026-04-06 13:54:19'),
(158, 2, '', 29, 'You have been auto-assigned to request #29', 0, '2026-04-06 13:54:19'),
(159, 1, '', 29, 'Request #29 was auto-assigned to Ahmed Hassan', 1, '2026-04-06 13:54:19'),
(160, 1, 'Request Cancelled by User', 29, 'Request #29 has been cancelled by the user within the 10-minute edit window.', 1, '2026-04-06 13:54:50'),
(161, 1, '', 30, 'New maintenance request #30 submitted by Ayman GH', 1, '2026-04-06 17:48:00'),
(162, 3, '', 30, 'You have been auto-assigned to request #30', 1, '2026-04-06 17:48:00'),
(163, 1, '', 30, 'Request #30 was auto-assigned to Khalid Abdullah', 1, '2026-04-06 17:48:00'),
(164, 1, 'Request Cancelled by User', 30, 'Request #30 has been cancelled by the user within the 10-minute edit window.', 1, '2026-04-06 17:48:28'),
(165, 1, '', 31, 'New maintenance request #31 submitted by Ayman GH', 1, '2026-04-08 20:59:42'),
(166, 2, '', 31, 'You have been auto-assigned to request #31', 0, '2026-04-08 20:59:42'),
(167, 1, '', 31, 'Request #31 was auto-assigned to Ahmed Hassan', 1, '2026-04-08 20:59:42'),
(168, 1, 'Request Cancelled by User', 31, 'Request #31 has been cancelled by the user within the 10-minute edit window.', 1, '2026-04-08 21:00:19'),
(169, 1, '', 32, 'New maintenance request #32 submitted by ayn', 1, '2026-04-08 21:32:48'),
(170, 2, '', 32, 'You have been auto-assigned to request #32', 0, '2026-04-08 21:32:48'),
(171, 1, '', 32, 'Request #32 was auto-assigned to Ahmed Hassan', 1, '2026-04-08 21:32:48'),
(172, 1, 'Request Cancelled by User', 32, 'Request #32 has been cancelled by the user within the 10-minute edit window.', 1, '2026-04-08 21:33:27'),
(173, 4, 'Request Status Updated', 17, 'Your request #17 status has been updated to: Pending', 1, '2026-04-08 22:49:07'),
(174, 4, 'Request Status Updated', 17, 'Your request #17 status has been updated to: Assigned', 1, '2026-04-08 22:56:07'),
(175, 4, '', 17, 'Your request #17 is now being worked on by a technician', 1, '2026-04-09 13:31:08'),
(176, 4, '', 17, 'Your request #17 has been completed! Please review and provide feedback.', 1, '2026-04-09 13:31:33'),
(177, 1, '', 33, 'New maintenance request #33 submitted by ayn', 1, '2026-04-09 13:52:41'),
(178, 3, '', 33, 'You have been auto-assigned to request #33', 1, '2026-04-09 13:52:41'),
(179, 1, '', 33, 'Request #33 was auto-assigned to Khalid Abdullah', 1, '2026-04-09 13:52:41'),
(180, 12, '', 33, 'Your request #33 is now being worked on by a technician', 1, '2026-04-09 13:53:49'),
(181, 12, '', 33, 'Your request #33 has been completed! Please review and provide feedback.', 1, '2026-04-09 13:54:19'),
(182, 1, '', 17, 'New feedback received for request #17 (5 stars)', 1, '2026-04-12 01:10:34'),
(183, 1, '', 34, 'New maintenance request #34 submitted by Ayman GH', 1, '2026-04-12 01:12:04'),
(184, 2, '', 34, 'You have been auto-assigned to request #34', 0, '2026-04-12 01:12:04'),
(185, 1, '', 34, 'Request #34 was auto-assigned to Ahmed Hassan', 1, '2026-04-12 01:12:04'),
(186, 1, 'Request Cancelled by User', 34, 'Request #34 has been cancelled by the user within the 10-minute edit window.', 1, '2026-04-12 01:12:25'),
(187, 1, '', 35, 'New maintenance request #35 submitted by Ayman GH', 1, '2026-04-12 01:25:10'),
(188, 3, '', 35, 'You have been auto-assigned to request #35', 1, '2026-04-12 01:25:10'),
(189, 1, '', 35, 'Request #35 was auto-assigned to Khalid Abdullah', 1, '2026-04-12 01:25:10'),
(190, 4, '', 35, 'Your request #35 is now being worked on by a technician', 1, '2026-04-12 01:27:34'),
(191, 4, '', 35, 'Your request #35 has been completed! Please review and provide feedback.', 1, '2026-04-12 01:28:08'),
(192, 1, '', 35, 'New feedback received for request #35 (5 stars)', 1, '2026-05-09 04:34:48'),
(193, 1, '', 36, 'New maintenance request #36 submitted by Ayman GH', 1, '2026-05-09 14:19:23'),
(194, 3, '', 36, 'You have been auto-assigned to request #36', 1, '2026-05-09 14:19:23'),
(195, 1, '', 36, 'Request #36 was auto-assigned to Khalid Abdullah', 1, '2026-05-09 14:19:23'),
(196, 4, '', 36, 'Your request #36 is now being worked on by a technician', 1, '2026-05-09 14:20:41'),
(197, 4, '', 36, 'Your request #36 has been completed! Please review and provide feedback.', 1, '2026-05-09 14:20:53'),
(198, 1, '', 36, 'New feedback received for request #36 (5 stars)', 1, '2026-05-09 14:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `priority`
--

CREATE TABLE `priority` (
  `PriorityID` int(11) NOT NULL,
  `PriorityLevel` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `priority`
--

INSERT INTO `priority` (`PriorityID`, `PriorityLevel`, `Description`) VALUES
(1, 'Low', 'Non-urgent issues, can wait up to a week'),
(2, 'Medium', 'Should be addressed within 2 days'),
(3, 'High', 'Urgent issue requiring next-day attention'),
(4, 'Critical', 'Emergency requiring immediate attention');

-- --------------------------------------------------------

--
-- Table structure for table `requestphoto`
--

CREATE TABLE `requestphoto` (
  `PhotoID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `PhotoPath` varchar(255) NOT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `requestphoto`
--

INSERT INTO `requestphoto` (`PhotoID`, `RequestID`, `PhotoPath`, `UploadedAt`) VALUES
(1, 1, '../uploads/requests/request_1_1769024839.jpg', '2026-01-21 19:47:19'),
(2, 2, '../uploads/requests/request_2_1771192767.png', '2026-02-15 21:59:27'),
(3, 4, '../uploads/requests/request_4_1771356243.png', '2026-02-17 19:24:03'),
(4, 5, '../uploads/requests/request_5_1771438733.jpg', '2026-02-18 18:18:53'),
(5, 6, '../uploads/requests/request_6_1771441371.png', '2026-02-18 19:02:51'),
(6, 7, '../uploads/requests/request_7_1772488721.png', '2026-03-02 21:58:41'),
(7, 8, '../uploads/requests/request_8_1772491025.png', '2026-03-02 22:37:05'),
(8, 9, '../uploads/requests/request_9_1772494108.png', '2026-03-02 23:28:28'),
(9, 10, '../uploads/requests/request_10_1772495238.png', '2026-03-02 23:47:18'),
(10, 11, '../uploads/requests/request_11_1772495696.png', '2026-03-02 23:54:56'),
(11, 12, '../uploads/requests/request_12_1772496446.png', '2026-03-03 00:07:26'),
(12, 13, '../uploads/requests/request_13_1772572972.png', '2026-03-03 21:22:52'),
(13, 14, '../uploads/requests/request_14_1772648563.png', '2026-03-04 18:22:43'),
(14, 15, '../uploads/requests/request_15_1773769867.png', '2026-03-17 17:51:07'),
(15, 16, '../uploads/requests/request_16_1774985755.png', '2026-03-31 19:35:55'),
(16, 17, '../uploads/requests/request_17_1774988230.png', '2026-03-31 20:17:10'),
(17, 18, '../uploads/requests/request_18_1775002951.png', '2026-04-01 00:22:31'),
(18, 19, '../uploads/requests/request_19_1775004300.png', '2026-04-01 00:45:00'),
(19, 20, '../uploads/requests/request_20_1775324212.png', '2026-04-04 17:36:52'),
(20, 21, '../uploads/requests/request_21_1775324285.png', '2026-04-04 17:38:05'),
(25, 26, '../uploads/requests/request_26_1775481742.png', '2026-04-06 13:22:22'),
(26, 27, '../uploads/requests/request_27_1775482548.png', '2026-04-06 13:35:48'),
(27, 28, '../uploads/requests/request_28_1775482693.png', '2026-04-06 13:38:13'),
(28, 29, '../uploads/requests/request_29_1775483659.png', '2026-04-06 13:54:19'),
(29, 30, '../uploads/requests/request_30_1775497680.png', '2026-04-06 17:48:00'),
(30, 31, '../uploads/requests/request_31_1775681982.png', '2026-04-08 20:59:42'),
(31, 32, '../uploads/requests/request_32_1775683968.png', '2026-04-08 21:32:48'),
(32, 33, '../uploads/requests/request_33_1775742761.png', '2026-04-09 13:52:41'),
(33, 34, '../uploads/requests/request_34_1775956324.png', '2026-04-12 01:12:04'),
(34, 35, '../uploads/requests/request_35_1775957110.png', '2026-04-12 01:25:10'),
(35, 36, '../uploads/requests/request_36_1778336363.png', '2026-05-09 14:19:23');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`RoleID`, `RoleName`) VALUES
(1, 'Admin'),
(4, 'Faculty'),
(2, 'Technician'),
(3, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `StatusID` int(11) NOT NULL,
  `StatusName` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`StatusID`, `StatusName`, `Description`) VALUES
(1, 'Pending', 'Request submitted, awaiting review'),
(2, 'Reviewed', 'Request reviewed by admin'),
(3, 'Assigned', 'Assigned to technician'),
(4, 'In Progress', 'Technician working on the issue'),
(5, 'Completed', 'Issue resolved successfully'),
(6, 'Cancelled', 'Request cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `statushistory`
--

CREATE TABLE `statushistory` (
  `HistoryID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `OldStatusID` int(11) DEFAULT NULL,
  `NewStatusID` int(11) NOT NULL,
  `ChangedBy` int(11) NOT NULL,
  `ChangedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statushistory`
--

INSERT INTO `statushistory` (`HistoryID`, `RequestID`, `OldStatusID`, `NewStatusID`, `ChangedBy`, `ChangedAt`) VALUES
(1, 1, NULL, 1, 4, '2026-01-21 19:47:19'),
(2, 1, 1, 3, 1, '2026-01-21 20:02:37'),
(3, 1, 3, 4, 2, '2026-01-21 20:03:14'),
(4, 1, 4, 5, 2, '2026-01-21 20:03:24'),
(5, 2, NULL, 1, 4, '2026-02-15 21:59:27'),
(6, 2, 1, 3, 1, '2026-02-15 22:00:25'),
(7, 2, 3, 4, 2, '2026-02-15 22:01:03'),
(8, 2, 4, 5, 2, '2026-02-15 22:01:08'),
(9, 3, NULL, 1, 4, '2026-02-16 01:23:08'),
(10, 3, 1, 3, 1, '2026-02-16 16:30:54'),
(11, 3, 3, 4, 2, '2026-02-16 16:31:34'),
(12, 3, 4, 5, 2, '2026-02-16 16:31:51'),
(13, 4, NULL, 1, 10, '2026-02-17 19:24:03'),
(14, 4, 1, 3, 1, '2026-02-17 19:24:03'),
(15, 4, 3, 5, 1, '2026-02-17 19:24:37'),
(16, 5, NULL, 1, 4, '2026-02-18 18:18:53'),
(17, 5, 1, 3, 1, '2026-02-18 18:18:54'),
(18, 5, 3, 5, 1, '2026-02-18 18:20:32'),
(19, 6, NULL, 1, 4, '2026-02-18 19:02:51'),
(20, 6, 1, 3, 1, '2026-02-18 19:02:52'),
(21, 6, 3, 5, 1, '2026-02-18 19:04:49'),
(22, 7, NULL, 1, 4, '2026-03-02 21:58:41'),
(23, 7, 1, 3, 1, '2026-03-02 21:58:43'),
(24, 7, 3, 5, 1, '2026-03-02 22:25:09'),
(25, 7, 5, 5, 1, '2026-03-02 22:26:15'),
(26, 7, 5, 5, 1, '2026-03-02 22:26:19'),
(27, 7, 5, 5, 1, '2026-03-02 22:27:48'),
(28, 7, 5, 5, 1, '2026-03-02 22:29:56'),
(29, 7, 5, 5, 1, '2026-03-02 22:32:15'),
(30, 7, 5, 5, 1, '2026-03-02 22:34:23'),
(31, 8, NULL, 1, 4, '2026-03-02 22:37:05'),
(32, 8, 1, 3, 1, '2026-03-02 22:37:06'),
(33, 8, 3, 5, 1, '2026-03-02 22:37:41'),
(34, 9, NULL, 1, 7, '2026-03-02 23:28:28'),
(35, 9, 1, 3, 1, '2026-03-02 23:28:30'),
(36, 9, 3, 5, 1, '2026-03-02 23:29:24'),
(37, 10, NULL, 1, 7, '2026-03-02 23:47:18'),
(38, 10, 1, 3, 1, '2026-03-02 23:47:19'),
(39, 10, 3, 5, 1, '2026-03-02 23:51:47'),
(40, 11, NULL, 1, 6, '2026-03-02 23:54:56'),
(41, 11, 1, 3, 1, '2026-03-02 23:54:57'),
(42, 11, 3, 5, 1, '2026-03-02 23:58:10'),
(43, 7, 5, 4, 1, '2026-03-03 00:02:32'),
(44, 7, 4, 5, 1, '2026-03-03 00:02:39'),
(45, 12, NULL, 1, 8, '2026-03-03 00:07:26'),
(46, 12, 1, 3, 1, '2026-03-03 00:07:27'),
(47, 12, 3, 5, 1, '2026-03-03 00:15:29'),
(48, 12, 5, 3, 1, '2026-03-03 20:33:30'),
(49, 12, 3, 5, 1, '2026-03-03 20:35:08'),
(50, 13, NULL, 1, 12, '2026-03-03 21:22:52'),
(51, 13, 1, 3, 1, '2026-03-03 21:22:54'),
(52, 13, 3, 4, 2, '2026-03-03 21:23:36'),
(53, 13, 4, 5, 2, '2026-03-03 21:23:46'),
(54, 13, 5, 1, 1, '2026-03-03 21:31:42'),
(55, 14, NULL, 1, 12, '2026-03-04 18:22:43'),
(56, 14, 1, 3, 1, '2026-03-04 18:22:45'),
(57, 14, 3, 4, 2, '2026-03-04 18:26:41'),
(58, 14, 4, 5, 2, '2026-03-04 18:26:47'),
(59, 13, 1, 5, 1, '2026-03-04 18:30:25'),
(60, 13, 5, 1, 1, '2026-03-04 18:30:34'),
(61, 13, 3, 4, 2, '2026-03-04 18:37:22'),
(62, 13, 4, 5, 2, '2026-03-04 18:37:39'),
(63, 14, 5, 1, 1, '2026-03-04 20:27:06'),
(64, 14, 3, 4, 2, '2026-03-04 20:27:48'),
(65, 14, 4, 3, 1, '2026-03-04 20:28:15'),
(66, 14, 3, 4, 2, '2026-03-04 20:28:44'),
(67, 14, 4, 5, 2, '2026-03-04 20:29:27'),
(68, 14, 5, 3, 1, '2026-03-05 05:03:42'),
(69, 14, 3, 4, 2, '2026-03-05 05:06:40'),
(70, 14, 4, 5, 2, '2026-03-05 05:06:50'),
(71, 14, 5, 3, 1, '2026-03-11 04:00:13'),
(72, 14, 3, 5, 1, '2026-03-11 04:00:27'),
(73, 15, NULL, 1, 10, '2026-03-17 17:51:07'),
(74, 15, 1, 3, 1, '2026-03-17 17:51:09'),
(75, 15, 3, 4, 2, '2026-03-17 17:51:42'),
(76, 15, 4, 5, 2, '2026-03-17 17:51:50'),
(77, 14, 5, 3, 1, '2026-03-17 20:08:19'),
(78, 14, 3, 5, 1, '2026-03-17 20:08:41'),
(79, 16, NULL, 1, 4, '2026-03-31 19:35:55'),
(80, 16, 1, 3, 1, '2026-03-31 19:36:00'),
(81, 16, 3, 6, 1, '2026-03-31 19:38:33'),
(82, 17, NULL, 1, 4, '2026-03-31 20:17:10'),
(83, 17, 1, 3, 1, '2026-03-31 20:17:12'),
(84, 18, NULL, 1, 12, '2026-04-01 00:22:31'),
(85, 18, 1, 3, 1, '2026-04-01 00:22:33'),
(86, 18, 3, 4, 1, '2026-04-01 00:27:12'),
(87, 18, 4, 5, 1, '2026-04-01 00:27:52'),
(88, 19, NULL, 1, 12, '2026-04-01 00:45:00'),
(89, 19, 1, 3, 1, '2026-04-01 00:45:04'),
(90, 19, 3, 5, 1, '2026-04-01 00:45:52'),
(91, 17, 3, 5, 1, '2026-04-01 00:46:15'),
(92, 20, NULL, 1, 10, '2026-04-04 17:36:52'),
(93, 20, 1, 3, 1, '2026-04-04 17:36:55'),
(94, 21, NULL, 1, 10, '2026-04-04 17:38:05'),
(95, 21, 1, 3, 1, '2026-04-04 17:38:08'),
(96, 21, 3, 5, 1, '2026-04-04 17:56:05'),
(97, 20, 3, 5, 1, '2026-04-04 17:56:15'),
(106, 26, NULL, 1, 4, '2026-04-06 13:22:22'),
(107, 26, 1, 3, 1, '2026-04-06 13:22:26'),
(108, 26, 3, 6, 4, '2026-04-06 13:22:56'),
(109, 27, NULL, 1, 4, '2026-04-06 13:35:48'),
(110, 27, 1, 3, 1, '2026-04-06 13:35:52'),
(111, 27, 3, 6, 4, '2026-04-06 13:36:07'),
(112, 28, NULL, 1, 4, '2026-04-06 13:38:13'),
(113, 28, 1, 3, 1, '2026-04-06 13:38:16'),
(114, 28, 3, 6, 4, '2026-04-06 13:38:36'),
(115, 29, NULL, 1, 4, '2026-04-06 13:54:19'),
(116, 29, 1, 3, 1, '2026-04-06 13:54:19'),
(117, 29, 3, 6, 4, '2026-04-06 13:54:49'),
(118, 30, NULL, 1, 4, '2026-04-06 17:48:00'),
(119, 30, 1, 3, 1, '2026-04-06 17:48:00'),
(120, 30, 3, 6, 4, '2026-04-06 17:48:26'),
(121, 31, NULL, 1, 4, '2026-04-08 20:59:42'),
(122, 31, 1, 3, 1, '2026-04-08 20:59:42'),
(123, 31, 3, 6, 4, '2026-04-08 21:00:17'),
(124, 32, NULL, 1, 12, '2026-04-08 21:32:48'),
(125, 32, 1, 3, 1, '2026-04-08 21:32:48'),
(126, 32, 3, 6, 12, '2026-04-08 21:33:25'),
(127, 17, 5, 1, 1, '2026-04-08 22:49:07'),
(128, 17, 1, 3, 1, '2026-04-08 22:56:07'),
(129, 17, 3, 4, 3, '2026-04-09 13:31:08'),
(130, 17, 4, 5, 3, '2026-04-09 13:31:33'),
(131, 33, NULL, 1, 12, '2026-04-09 13:52:41'),
(132, 33, 1, 3, 1, '2026-04-09 13:52:41'),
(133, 33, 3, 4, 3, '2026-04-09 13:53:49'),
(134, 33, 4, 5, 3, '2026-04-09 13:54:19'),
(135, 34, NULL, 1, 4, '2026-04-12 01:12:04'),
(136, 34, 1, 3, 1, '2026-04-12 01:12:04'),
(137, 34, 3, 6, 4, '2026-04-12 01:12:24'),
(138, 35, NULL, 1, 4, '2026-04-12 01:25:10'),
(139, 35, 1, 3, 1, '2026-04-12 01:25:10'),
(140, 35, 3, 4, 3, '2026-04-12 01:27:34'),
(141, 35, 4, 5, 3, '2026-04-12 01:28:08'),
(142, 36, NULL, 1, 4, '2026-05-09 14:19:23'),
(143, 36, 1, 3, 1, '2026-05-09 14:19:23'),
(144, 36, 3, 4, 3, '2026-05-09 14:20:41'),
(145, 36, 4, 5, 3, '2026-05-09 14:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `MaxRequestsPerWeek` int(11) DEFAULT 2,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `LastResetAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `RoleID`, `Name`, `Email`, `Password`, `Phone`, `MaxRequestsPerWeek`, `CreatedAt`, `LastResetAt`) VALUES
(1, 1, 'System Administrator', 'admin@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966500000000', NULL, '2026-01-18 23:12:09', NULL),
(2, 2, 'Ahmed Hassan', 'ahmed.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111111', NULL, '2026-01-18 23:12:09', NULL),
(3, 2, 'Khalid Abdullah', 'khalid.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111112', NULL, '2026-01-18 23:12:09', NULL),
(4, 3, 'Ayman GH', 'S220053790@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222221', 3, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(5, 3, 'Al-Abbas AlQurashi', 'S220034953@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222222', 2, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(6, 3, 'Omar Marzouq Almutairi', 'S220042171@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222223', 2, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(7, 3, 'Yahya Khalid Makhashin', 'S220043128@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222224', 2, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(8, 3, 'Talal Althubyani', 'S220020268@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222225', 2, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(9, 3, 'Abdulaziz Yousef Alharbi', 'S220006357@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222226', 2, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(10, 4, 'Dr. Jameel Alhejely', 'a.ym_95@hotmail.com', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966503333333', 5, '2026-01-18 23:12:09', '2026-05-09 17:41:30'),
(11, 3, 'Ayman', 'a.aalghamdi147@gmail.com', '$2y$10$RwzUVcYoTUQWwnhj.TQlc.BYbB0ETsBanGyBpLYLsUKlwK27oHpRm', '', 2, '2026-01-20 23:27:13', '2026-05-09 17:41:30'),
(12, 3, 'ayn', 'user12342188@gmail.com', '$2y$10$mgALkx3uidmYkuHVMnvW5OlsQBp8nMiYa7RgWizrUdaf.0JNmYEY2', '0509681670', 2, '2026-03-03 18:22:31', '2026-05-09 17:41:30'),
(13, 3, 'Ayman', 'user1232188@gmail.com', '$2y$10$AVo.lMMBI8/5Jc9V2BqePef2lGUws6ikah4pnPJYvSJMTUh9Ph5iq', '', 2, '2026-04-08 18:05:06', '2026-05-09 17:41:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `AdminID` (`AdminID`),
  ADD KEY `idx_request` (`RequestID`),
  ADD KEY `idx_technician` (`TechnicianID`);

--
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`AuditID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_action` (`Action`),
  ADD KEY `idx_table` (`TableName`),
  ADD KEY `idx_date` (`PerformedAt`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `CategoryName` (`CategoryName`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`FeedbackID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `idx_request` (`RequestID`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `loginlog`
--
ALTER TABLE `loginlog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_date` (`AttemptedAt`);

--
-- Indexes for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `PriorityID` (`PriorityID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_location` (`LocationID`),
  ADD KEY `idx_category` (`CategoryID`),
  ADD KEY `idx_status` (`StatusID`),
  ADD KEY `idx_submitted` (`SubmittedAt`),
  ADD KEY `idx_location_category` (`LocationID`,`CategoryID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `RequestID` (`RequestID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_read` (`IsRead`);

--
-- Indexes for table `priority`
--
ALTER TABLE `priority`
  ADD PRIMARY KEY (`PriorityID`),
  ADD UNIQUE KEY `PriorityLevel` (`PriorityLevel`);

--
-- Indexes for table `requestphoto`
--
ALTER TABLE `requestphoto`
  ADD PRIMARY KEY (`PhotoID`),
  ADD KEY `idx_request` (`RequestID`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`StatusID`),
  ADD UNIQUE KEY `StatusName` (`StatusName`);

--
-- Indexes for table `statushistory`
--
ALTER TABLE `statushistory`
  ADD PRIMARY KEY (`HistoryID`),
  ADD KEY `OldStatusID` (`OldStatusID`),
  ADD KEY `NewStatusID` (`NewStatusID`),
  ADD KEY `ChangedBy` (`ChangedBy`),
  ADD KEY `idx_request` (`RequestID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_role` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `FeedbackID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loginlog`
--
ALTER TABLE `loginlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

--
-- AUTO_INCREMENT for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `priority`
--
ALTER TABLE `priority`
  MODIFY `PriorityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requestphoto`
--
ALTER TABLE `requestphoto`
  MODIFY `PhotoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `statushistory`
--
ALTER TABLE `statushistory`
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  ADD CONSTRAINT `assignment_ibfk_2` FOREIGN KEY (`TechnicianID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `assignment_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `loginlog`
--
ALTER TABLE `loginlog`
  ADD CONSTRAINT `loginlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  ADD CONSTRAINT `maintenancerequest_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_2` FOREIGN KEY (`LocationID`) REFERENCES `location` (`LocationID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_3` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_4` FOREIGN KEY (`PriorityID`) REFERENCES `priority` (`PriorityID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_5` FOREIGN KEY (`StatusID`) REFERENCES `status` (`StatusID`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`) ON DELETE SET NULL;

--
-- Constraints for table `requestphoto`
--
ALTER TABLE `requestphoto`
  ADD CONSTRAINT `requestphoto_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`) ON DELETE CASCADE;

--
-- Constraints for table `statushistory`
--
ALTER TABLE `statushistory`
  ADD CONSTRAINT `statushistory_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  ADD CONSTRAINT `statushistory_ibfk_2` FOREIGN KEY (`OldStatusID`) REFERENCES `status` (`StatusID`),
  ADD CONSTRAINT `statushistory_ibfk_3` FOREIGN KEY (`NewStatusID`) REFERENCES `status` (`StatusID`),
  ADD CONSTRAINT `statushistory_ibfk_4` FOREIGN KEY (`ChangedBy`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `role` (`RoleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
