-- ============================================
-- FixPoint Database Backup
-- Generated: 2026-02-16 14:56:52
-- Database: fixpoint
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================
-- Table: assignment
-- ============================================
DROP TABLE IF EXISTS `assignment`;
CREATE TABLE `assignment` (
  `AssignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` int(11) NOT NULL,
  `TechnicianID` int(11) NOT NULL,
  `AdminID` int(11) NOT NULL,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `StartedAt` timestamp NULL DEFAULT NULL,
  `CompletedAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`AssignmentID`),
  KEY `AdminID` (`AdminID`),
  KEY `idx_request` (`RequestID`),
  KEY `idx_technician` (`TechnicianID`),
  CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  CONSTRAINT `assignment_ibfk_2` FOREIGN KEY (`TechnicianID`) REFERENCES `user` (`UserID`),
  CONSTRAINT `assignment_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `user` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assignment` (`AssignmentID`, `RequestID`, `TechnicianID`, `AdminID`, `AssignedAt`, `StartedAt`, `CompletedAt`) VALUES
('1', '1', '2', '1', '2026-01-21 23:02:37', '2026-01-21 23:03:14', '2026-01-21 23:03:24'),
('2', '2', '2', '1', '2026-02-16 01:00:25', '2026-02-16 01:01:03', '2026-02-16 01:01:08');

-- ============================================
-- Table: auditlog
-- ============================================
DROP TABLE IF EXISTS `auditlog`;
CREATE TABLE `auditlog` (
  `AuditID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(100) NOT NULL,
  `TableName` varchar(100) DEFAULT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `OldValue` text DEFAULT NULL,
  `NewValue` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `PerformedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AuditID`),
  KEY `idx_user` (`UserID`),
  KEY `idx_action` (`Action`),
  KEY `idx_table` (`TableName`),
  KEY `idx_date` (`PerformedAt`),
  CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: category
-- ============================================
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `CategoryName` (`CategoryName`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`) VALUES
('1', 'Electrical', 'Electrical systems and lighting issues'),
('2', 'Plumbing', 'Water, drainage, and plumbing problems'),
('3', 'HVAC', 'Heating, ventilation, and air conditioning'),
('4', 'IT Equipment', 'Computers, projectors, network issues'),
('5', 'Furniture', 'Desks, chairs, and furniture repairs'),
('6', 'Cleaning', 'Cleaning and sanitation requests'),
('7', 'Safety', 'Safety hazards and emergency issues'),
('8', 'Other', 'Other maintenance needs');

-- ============================================
-- Table: feedback
-- ============================================
DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `FeedbackID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`FeedbackID`),
  KEY `UserID` (`UserID`),
  KEY `idx_request` (`RequestID`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `feedback` (`FeedbackID`, `RequestID`, `UserID`, `Rating`, `Comment`, `SubmittedAt`) VALUES
('1', '1', '4', '5', '', '2026-01-21 23:04:55'),
('2', '2', '4', '5', 'Thanks', '2026-02-16 01:02:26');

-- ============================================
-- Table: location
-- ============================================
DROP TABLE IF EXISTS `location`;
CREATE TABLE `location` (
  `LocationID` int(11) NOT NULL AUTO_INCREMENT,
  `BuildingName` varchar(100) NOT NULL,
  `FloorNumber` varchar(20) DEFAULT NULL,
  `RoomNumber` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`LocationID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `location` (`LocationID`, `BuildingName`, `FloorNumber`, `RoomNumber`, `Description`) VALUES
('1', 'Main Building', 'Ground Floor', '101', 'Main entrance hall'),
('2', 'Main Building', 'First Floor', '201', 'Computer Lab 1'),
('3', 'Main Building', 'Second Floor', '301', 'Lecture Hall A'),
('4', 'Library Building', 'Ground Floor', 'Reading Hall', 'Main reading area'),
('5', 'Library Building', 'First Floor', 'Study Rooms', 'Group study area'),
('6', 'Engineering Building', 'Ground Floor', 'Lab-A', 'Engineering Laboratory'),
('7', 'Student Center', 'Ground Floor', 'Cafeteria', 'Student cafeteria');

-- ============================================
-- Table: loginlog
-- ============================================
DROP TABLE IF EXISTS `loginlog`;
CREATE TABLE `loginlog` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
  `Email` varchar(255) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Status` enum('Success','Failed') NOT NULL,
  `FailReason` varchar(255) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` varchar(500) DEFAULT NULL,
  `AttemptedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`LogID`),
  KEY `idx_email` (`Email`),
  KEY `idx_user` (`UserID`),
  KEY `idx_status` (`Status`),
  KEY `idx_date` (`AttemptedAt`),
  CONSTRAINT `loginlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `loginlog` (`LogID`, `Email`, `UserID`, `Status`, `FailReason`, `IPAddress`, `UserAgent`, `AttemptedAt`) VALUES
('1', 'admin@seu.edu.sa', '1', 'Success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 16:56:11');

-- ============================================
-- Table: maintenancerequest
-- ============================================
DROP TABLE IF EXISTS `maintenancerequest`;
CREATE TABLE `maintenancerequest` (
  `RequestID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `LocationID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `PriorityID` int(11) NOT NULL,
  `StatusID` int(11) NOT NULL DEFAULT 1,
  `Title` varchar(200) NOT NULL,
  `Description` text NOT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CompletedAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`RequestID`),
  KEY `PriorityID` (`PriorityID`),
  KEY `idx_user` (`UserID`),
  KEY `idx_location` (`LocationID`),
  KEY `idx_category` (`CategoryID`),
  KEY `idx_status` (`StatusID`),
  KEY `idx_submitted` (`SubmittedAt`),
  KEY `idx_location_category` (`LocationID`,`CategoryID`),
  CONSTRAINT `maintenancerequest_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  CONSTRAINT `maintenancerequest_ibfk_2` FOREIGN KEY (`LocationID`) REFERENCES `location` (`LocationID`),
  CONSTRAINT `maintenancerequest_ibfk_3` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`),
  CONSTRAINT `maintenancerequest_ibfk_4` FOREIGN KEY (`PriorityID`) REFERENCES `priority` (`PriorityID`),
  CONSTRAINT `maintenancerequest_ibfk_5` FOREIGN KEY (`StatusID`) REFERENCES `status` (`StatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `maintenancerequest` (`RequestID`, `UserID`, `LocationID`, `CategoryID`, `PriorityID`, `StatusID`, `Title`, `Description`, `SubmittedAt`, `UpdatedAt`, `CompletedAt`) VALUES
('1', '4', '6', '1', '3', '5', 'Broken AC', 'not working', '2026-01-21 22:47:19', '2026-01-21 23:03:24', '2026-01-21 23:03:24'),
('2', '4', '4', '3', '1', '5', 'sdasqwdqwd', 'dqwdqwdqwdqwdqwdqwdqwdqwdqwd', '2026-02-16 00:59:27', '2026-02-16 01:01:08', '2026-02-16 01:01:08'),
('3', '4', '6', '6', '2', '1', 'asdasdasdasd', 'asdasdasdasdasdasdasd', '2026-02-16 04:23:08', '2026-02-16 04:23:08', NULL);

-- ============================================
-- Table: notification
-- ============================================
DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `RequestID` int(11) DEFAULT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `RequestID` (`RequestID`),
  KEY `idx_user` (`UserID`),
  KEY `idx_read` (`IsRead`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notification` (`NotificationID`, `UserID`, `RequestID`, `Message`, `IsRead`, `CreatedAt`) VALUES
('1', '1', '1', 'New maintenance request #1 submitted by Ayman Ahmed Alghamdi', '1', '2026-01-21 22:47:19'),
('2', '4', '1', 'Your request #1 has been assigned to a technician', '1', '2026-01-21 23:02:37'),
('3', '2', '1', 'New maintenance request #1 has been assigned to you', '1', '2026-01-21 23:02:37'),
('4', '4', '1', 'Your request #1 is now being worked on by a technician', '1', '2026-01-21 23:03:14'),
('5', '4', '1', 'Your request #1 has been completed! Please review and provide feedback.', '1', '2026-01-21 23:03:24'),
('6', '1', '1', 'New feedback received for request #1 (5 stars)', '1', '2026-01-21 23:04:55'),
('7', '1', '2', 'New maintenance request #2 submitted by Ayman Ahmed Alghamdi', '1', '2026-02-16 00:59:27'),
('8', '4', '2', 'Your request #2 has been assigned to a technician', '1', '2026-02-16 01:00:25'),
('9', '2', '2', 'New maintenance request #2 has been assigned to you', '1', '2026-02-16 01:00:25'),
('10', '4', '2', 'Your request #2 is now being worked on by a technician', '1', '2026-02-16 01:01:03'),
('11', '4', '2', 'Your request #2 has been completed! Please review and provide feedback.', '1', '2026-02-16 01:01:08'),
('12', '1', '2', 'New feedback received for request #2 (5 stars)', '1', '2026-02-16 01:02:26'),
('13', '1', '3', 'New maintenance request #3 submitted by Ayman Ahmed Alghamdi', '0', '2026-02-16 04:23:08');

-- ============================================
-- Table: priority
-- ============================================
DROP TABLE IF EXISTS `priority`;
CREATE TABLE `priority` (
  `PriorityID` int(11) NOT NULL AUTO_INCREMENT,
  `PriorityLevel` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`PriorityID`),
  UNIQUE KEY `PriorityLevel` (`PriorityLevel`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `priority` (`PriorityID`, `PriorityLevel`, `Description`) VALUES
('1', 'Low', 'Non-urgent issues, can wait up to a week'),
('2', 'Medium', 'Should be addressed within 2 days'),
('3', 'High', 'Urgent issue requiring next-day attention'),
('4', 'Critical', 'Emergency requiring immediate attention');

-- ============================================
-- Table: requestphoto
-- ============================================
DROP TABLE IF EXISTS `requestphoto`;
CREATE TABLE `requestphoto` (
  `PhotoID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` int(11) NOT NULL,
  `PhotoPath` varchar(255) NOT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`PhotoID`),
  KEY `idx_request` (`RequestID`),
  CONSTRAINT `requestphoto_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `requestphoto` (`PhotoID`, `RequestID`, `PhotoPath`, `UploadedAt`) VALUES
('1', '1', '../uploads/requests/request_1_1769024839.jpg', '2026-01-21 22:47:19'),
('2', '2', '../uploads/requests/request_2_1771192767.png', '2026-02-16 00:59:27');

-- ============================================
-- Table: role
-- ============================================
DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `RoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(50) NOT NULL,
  PRIMARY KEY (`RoleID`),
  UNIQUE KEY `RoleName` (`RoleName`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role` (`RoleID`, `RoleName`) VALUES
('1', 'Admin'),
('4', 'Faculty'),
('3', 'Student'),
('2', 'Technician'),
('5', 'Visitor');

-- ============================================
-- Table: status
-- ============================================
DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `StatusID` int(11) NOT NULL AUTO_INCREMENT,
  `StatusName` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`StatusID`),
  UNIQUE KEY `StatusName` (`StatusName`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `status` (`StatusID`, `StatusName`, `Description`) VALUES
('1', 'Pending', 'Request submitted, awaiting review'),
('2', 'Reviewed', 'Request reviewed by admin'),
('3', 'Assigned', 'Assigned to technician'),
('4', 'In Progress', 'Technician working on the issue'),
('5', 'Completed', 'Issue resolved successfully'),
('6', 'Cancelled', 'Request cancelled');

-- ============================================
-- Table: statushistory
-- ============================================
DROP TABLE IF EXISTS `statushistory`;
CREATE TABLE `statushistory` (
  `HistoryID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` int(11) NOT NULL,
  `OldStatusID` int(11) DEFAULT NULL,
  `NewStatusID` int(11) NOT NULL,
  `ChangedBy` int(11) NOT NULL,
  `ChangedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`HistoryID`),
  KEY `OldStatusID` (`OldStatusID`),
  KEY `NewStatusID` (`NewStatusID`),
  KEY `ChangedBy` (`ChangedBy`),
  KEY `idx_request` (`RequestID`),
  CONSTRAINT `statushistory_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `maintenancerequest` (`RequestID`),
  CONSTRAINT `statushistory_ibfk_2` FOREIGN KEY (`OldStatusID`) REFERENCES `status` (`StatusID`),
  CONSTRAINT `statushistory_ibfk_3` FOREIGN KEY (`NewStatusID`) REFERENCES `status` (`StatusID`),
  CONSTRAINT `statushistory_ibfk_4` FOREIGN KEY (`ChangedBy`) REFERENCES `user` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `statushistory` (`HistoryID`, `RequestID`, `OldStatusID`, `NewStatusID`, `ChangedBy`, `ChangedAt`) VALUES
('1', '1', NULL, '1', '4', '2026-01-21 22:47:19'),
('2', '1', '1', '3', '1', '2026-01-21 23:02:37'),
('3', '1', '3', '4', '2', '2026-01-21 23:03:14'),
('4', '1', '4', '5', '2', '2026-01-21 23:03:24'),
('5', '2', NULL, '1', '4', '2026-02-16 00:59:27'),
('6', '2', '1', '3', '1', '2026-02-16 01:00:25'),
('7', '2', '3', '4', '2', '2026-02-16 01:01:03'),
('8', '2', '4', '5', '2', '2026-02-16 01:01:08'),
('9', '3', NULL, '1', '4', '2026-02-16 04:23:08');

-- ============================================
-- Table: user
-- ============================================
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `MaxRequestsPerWeek` int(11) DEFAULT 2,
  `MaxRequestsPerMonth` int(11) DEFAULT 8,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `idx_email` (`Email`),
  KEY `idx_role` (`RoleID`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `role` (`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user` (`UserID`, `RoleID`, `Name`, `Email`, `Password`, `Phone`, `MaxRequestsPerWeek`, `MaxRequestsPerMonth`, `CreatedAt`) VALUES
('1', '1', 'System Administrator', 'admin@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966500000000', NULL, NULL, '2026-01-19 02:12:09'),
('2', '2', 'Ahmed Hassan', 'ahmed.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111111', NULL, NULL, '2026-01-19 02:12:09'),
('3', '2', 'Khalid Abdullah', 'khalid.tech@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966501111112', NULL, NULL, '2026-01-19 02:12:09'),
('4', '3', 'Ayman Ahmed Alghamdi', 'S220053790@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222221', '2', '8', '2026-01-19 02:12:09'),
('5', '3', 'Al-Abbas AlQurashi', 'S220034953@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222222', '2', '8', '2026-01-19 02:12:09'),
('6', '3', 'Omar Marzouq Almutairi', 'S220042171@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222223', '2', '8', '2026-01-19 02:12:09'),
('7', '3', 'Yahya Khalid Makhashin', 'S220043128@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222224', '2', '8', '2026-01-19 02:12:09'),
('8', '3', 'Talal Althubyani', 'S220020268@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222225', '2', '8', '2026-01-19 02:12:09'),
('9', '3', 'Abdulaziz Yousef Alharbi', 'S220006357@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966502222226', '2', '8', '2026-01-19 02:12:09'),
('10', '4', 'Dr. Jameel Alhejely', 'jameel.alhejely@seu.edu.sa', '$2y$10$/harbUMb9yj/uEpdNVSXYuwh21NeedO06y/OazYTBXMw00z45BkxS', '+966503333333', '5', '20', '2026-01-19 02:12:09'),
('11', '3', 'Ayman', 'a.aalghamdi147@gmail.com', '$2y$10$RwzUVcYoTUQWwnhj.TQlc.BYbB0ETsBanGyBpLYLsUKlwK27oHpRm', '', '2', '8', '2026-01-21 02:27:13');

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================
-- Backup Complete
-- ============================================
