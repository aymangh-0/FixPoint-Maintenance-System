-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 11:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
(1, 1, 2, 1, '2026-01-21 20:02:37', '2026-01-21 20:03:14', '2026-01-21 20:03:24');

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
(1, 1, 4, 5, '', '2026-01-21 20:04:55');

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
(1, 4, 6, 1, 3, 5, 'Broken AC', 'not working', '2026-01-21 19:47:19', '2026-01-21 20:03:24', '2026-01-21 20:03:24');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RequestID` int(11) DEFAULT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`NotificationID`, `UserID`, `RequestID`, `Message`, `IsRead`, `CreatedAt`) VALUES
(1, 1, 1, 'New maintenance request #1 submitted by Ayman Ahmed Alghamdi', 0, '2026-01-21 19:47:19'),
(2, 4, 1, 'Your request #1 has been assigned to a technician', 0, '2026-01-21 20:02:37'),
(3, 2, 1, 'New maintenance request #1 has been assigned to you', 0, '2026-01-21 20:02:37'),
(4, 4, 1, 'Your request #1 is now being worked on by a technician', 0, '2026-01-21 20:03:14'),
(5, 4, 1, 'Your request #1 has been completed! Please review and provide feedback.', 0, '2026-01-21 20:03:24'),
(6, 1, 1, 'New feedback received for request #1 (5 stars)', 0, '2026-01-21 20:04:55');

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
(1, 1, '../uploads/requests/request_1_1769024839.jpg', '2026-01-21 19:47:19');

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
(3, 'Student'),
(2, 'Technician'),
(5, 'Visitor');

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
(4, 1, 4, 5, 2, '2026-01-21 20:03:24');

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
  `MaxRequestsPerMonth` int(11) DEFAULT 8,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `RoleID`, `Name`, `Email`, `Password`, `Phone`, `MaxRequestsPerWeek`, `MaxRequestsPerMonth`, `CreatedAt`) VALUES
(1, 1, 'System Administrator', 'admin@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966500000000', NULL, NULL, '2026-01-18 23:12:09'),
(2, 2, 'Ahmed Hassan', 'ahmed.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111111', NULL, NULL, '2026-01-18 23:12:09'),
(3, 2, 'Khalid Abdullah', 'khalid.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111112', NULL, NULL, '2026-01-18 23:12:09'),
(4, 3, 'Ayman Ahmed Alghamdi', 'S220053790@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222221', 2, 8, '2026-01-18 23:12:09'),
(5, 3, 'Al-Abbas AlQurashi', 'S220034953@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222222', 2, 8, '2026-01-18 23:12:09'),
(6, 3, 'Omar Marzouq Almutairi', 'S220042171@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222223', 2, 8, '2026-01-18 23:12:09'),
(7, 3, 'Yahya Khalid Makhashin', 'S220043128@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222224', 2, 8, '2026-01-18 23:12:09'),
(8, 3, 'Talal Althubyani', 'S220020268@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222225', 2, 8, '2026-01-18 23:12:09'),
(9, 3, 'Abdulaziz Yousef Alharbi', 'S220006357@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222226', 2, 8, '2026-01-18 23:12:09'),
(10, 4, 'Dr. Jameel Alhejely', 'jameel.alhejely@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966503333333', 5, 20, '2026-01-18 23:12:09'),
(11, 3, 'Ayman', 'a.aalghamdi147@gmail.com', '$2y$10$RwzUVcYoTUQWwnhj.TQlc.BYbB0ETsBanGyBpLYLsUKlwK27oHpRm', '', 2, 8, '2026-01-20 23:27:13');

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
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `FeedbackID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `priority`
--
ALTER TABLE `priority`
  MODIFY `PriorityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requestphoto`
--
ALTER TABLE `requestphoto`
  MODIFY `PhotoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

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
