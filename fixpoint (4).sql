-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 06:23 AM
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
(12, 12, 2, 1, '2026-03-03 00:07:27', NULL, NULL);

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
(36, 1, 'AUTO_ASSIGN', 'assignment', 12, NULL, 'Auto-assigned to Ahmed Hassan (UserID: 2) - Active tasks: 0', '::1', '2026-03-03 00:07:27');

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
(7, 9, 7, 5, '', '2026-03-02 23:34:27');

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
(1, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 13:56:11'),
(2, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 16:30:41'),
(3, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 16:31:28'),
(4, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 16:36:24'),
(5, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 19:26:58'),
(6, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 19:34:35'),
(7, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 19:56:36'),
(8, 'jameel.alhejely@seu.edu.sa', 10, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 19:23:39'),
(9, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 19:24:31'),
(10, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 01:21:40'),
(11, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:55:40'),
(12, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 17:55:06'),
(13, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:05:08'),
(14, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:05:27'),
(15, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:18:25'),
(16, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:19:46'),
(17, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:22:18'),
(18, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:22:53'),
(19, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:32:02'),
(20, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:36:54'),
(21, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:37:21'),
(22, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:37:40'),
(23, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:43:24'),
(24, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:47:52'),
(25, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:48:21'),
(26, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:58:04'),
(27, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 18:59:09'),
(28, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 19:04:39'),
(29, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 19:05:19'),
(30, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 19:05:42'),
(31, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 01:30:25'),
(32, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 01:32:27'),
(33, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 01:38:16'),
(34, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 01:49:07'),
(35, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 20:28:52'),
(36, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 20:42:39'),
(37, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:12:09'),
(38, 'admin@seu.edu.sa', 1, 'Failed', 'Incorrect password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:13:40'),
(39, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:13:49'),
(40, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:30:48'),
(41, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 21:22:13'),
(42, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 21:37:14'),
(43, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 03:05:10'),
(44, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 03:05:34'),
(45, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 03:06:10'),
(46, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 03:18:41'),
(47, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:36:08'),
(48, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:40:12'),
(49, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:47:00'),
(50, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:57:43'),
(51, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 21:40:19'),
(52, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 21:54:54'),
(53, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 21:57:01'),
(54, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:03:46'),
(55, 'ahmed.tech@seu.edu.sa', 2, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:06:20'),
(56, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:06:44'),
(57, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:36:02'),
(58, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:37:21'),
(59, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:39:56'),
(60, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:42:49'),
(61, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:43:40'),
(62, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:44:00'),
(63, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:46:55'),
(64, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:49:20'),
(65, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:52:01'),
(66, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:52:28'),
(67, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:55:13'),
(68, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:58:09'),
(69, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:00:24'),
(70, 'S220043128@gmail.com', NULL, 'Failed', 'Email not found', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:03:18'),
(71, 'S220043128@seu.edu.sa', 7, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:03:28'),
(72, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:07:20'),
(73, 'S220043128@seu.edu.sa', 7, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:24:53'),
(74, 'S220043128@seu.edu.sa', 7, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:28:08'),
(75, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:29:18'),
(76, 'S220043128@seu.edu.sa', 7, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:31:13'),
(77, 'S220034953@seu.edu.sa', 5, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:49:34'),
(78, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:49:51'),
(79, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:50:21'),
(80, 'S220042171@seu.edu.sa', 6, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:54:36'),
(81, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:58:04'),
(82, 'S220053790@seu.edu.sa', 4, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:05:50'),
(83, 'S220020268@seu.edu.sa', 8, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:07:11'),
(84, 'admin@seu.edu.sa', 1, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:15:23'),
(85, 'S220006357@seu.edu.sa', 9, 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:39:52');

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
(12, 8, 6, 6, 2, 5, 'asdfafasfdasdasdasd', 'aerfwaefqaefaefaewfqawfd', '2026-03-03 00:07:26', '2026-03-03 00:15:29', '2026-03-03 00:15:29');

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
(20, 3, '', 4, 'You have been auto-assigned to request #4', 0, '2026-02-17 19:24:03'),
(22, 10, '', 4, 'Your request #4 status changed to: Completed', 0, '2026-02-17 19:24:37'),
(25, 3, '', 5, 'You have been auto-assigned to request #5', 0, '2026-02-18 18:18:54'),
(30, 3, '', 6, 'You have been auto-assigned to request #6', 0, '2026-02-18 19:02:52'),
(38, 3, '', 7, 'You have been auto-assigned to request #7', 0, '2026-03-02 21:58:43'),
(45, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 1, '2026-03-02 22:32:15'),
(47, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 1, '2026-03-02 22:34:23'),
(50, 3, '', 8, 'You have been auto-assigned to request #8', 0, '2026-03-02 22:37:06'),
(52, 4, 'Request Status Updated', 8, 'Your request #8 status has been updated to: Completed', 1, '2026-03-02 22:37:41'),
(57, 2, '', 9, 'You have been auto-assigned to request #9', 0, '2026-03-02 23:28:30'),
(59, 7, 'Request Status Updated', 9, 'Your request #9 status has been updated to: Completed', 1, '2026-03-02 23:29:24'),
(62, 2, '', 10, 'You have been auto-assigned to request #10', 0, '2026-03-02 23:47:19'),
(64, 7, 'Request Status Updated', 10, 'Your request #10 status has been updated to: Completed', 0, '2026-03-02 23:51:47'),
(66, 2, '', 11, 'You have been auto-assigned to request #11', 0, '2026-03-02 23:54:57'),
(68, 6, 'Request Status Updated', 11, 'Your request #11 status has been updated to: Completed', 0, '2026-03-02 23:58:10'),
(69, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: In Progress', 0, '2026-03-03 00:02:32'),
(70, 4, 'Request Status Updated', 7, 'Your request #7 status has been updated to: Completed', 0, '2026-03-03 00:02:39'),
(72, 2, '', 12, 'You have been auto-assigned to request #12', 0, '2026-03-03 00:07:27'),
(74, 8, 'Request Status Updated', 12, 'Your request #12 status has been updated to: Completed', 0, '2026-03-03 00:15:29');

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
(11, 12, '../uploads/requests/request_12_1772496446.png', '2026-03-03 00:07:26');

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
(47, 12, 3, 5, 1, '2026-03-03 00:15:29');

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
(4, 3, 'Ayman Ahmed Alghamdi', 'S220053790@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222221', 2, '2026-01-18 23:12:09', '2026-03-02 23:57:44'),
(5, 3, 'Al-Abbas AlQurashi', 'S220034953@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222222', 2, '2026-01-18 23:12:09', NULL),
(6, 3, 'Omar Marzouq Almutairi', 'S220042171@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222223', 2, '2026-01-18 23:12:09', NULL),
(7, 3, 'Yahya Khalid Makhashin', 'S220043128@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222224', 2, '2026-01-18 23:12:09', NULL),
(8, 3, 'Talal Althubyani', 'S220020268@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222225', 2, '2026-01-18 23:12:09', NULL),
(9, 3, 'Abdulaziz Yousef Alharbi', 'S220006357@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222226', 2, '2026-01-18 23:12:09', NULL),
(10, 4, 'Dr. Jameel Alhejely', 'jameel.alhejely@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966503333333', 5, '2026-01-18 23:12:09', NULL),
(11, 3, 'Ayman', 'a.aalghamdi147@gmail.com', '$2y$10$RwzUVcYoTUQWwnhj.TQlc.BYbB0ETsBanGyBpLYLsUKlwK27oHpRm', '', 2, '2026-01-20 23:27:13', NULL);

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
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `FeedbackID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loginlog`
--
ALTER TABLE `loginlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `priority`
--
ALTER TABLE `priority`
  MODIFY `PriorityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requestphoto`
--
ALTER TABLE `requestphoto`
  MODIFY `PhotoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
