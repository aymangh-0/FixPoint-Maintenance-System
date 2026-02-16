<?php
/**
 * FixPoint - Export Reports to CSV
 * Generates a CSV file with all report data
 */

session_start();

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';

// Get date range from GET params
$date_from = isset($_GET['from']) ? $_GET['from'] : '2024-01-01';
$date_to = isset($_GET['to']) ? $_GET['to'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fixpoint_report_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel Arabic support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ============================================
// SECTION 1: Summary Statistics
// ============================================
fputcsv($output, ['=== FixPoint Maintenance Report ===']);
fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
fputcsv($output, ['Date Range:', $date_from . ' to ' . $date_to]);
fputcsv($output, []);

// Total requests
$sql = "SELECT COUNT(*) as Total FROM maintenancerequest WHERE SubmittedAt BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['Total'];

$sql = "SELECT COUNT(*) as Completed FROM maintenancerequest WHERE StatusID = 5 AND SubmittedAt BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$completed = $stmt->get_result()->fetch_assoc()['Completed'];

$sql = "SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, SubmittedAt, CompletedAt)), 1) as AvgHours 
        FROM maintenancerequest WHERE StatusID = 5 AND CompletedAt IS NOT NULL AND SubmittedAt BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$avg_hours = $stmt->get_result()->fetch_assoc()['AvgHours'] ?? 'N/A';

fputcsv($output, ['--- Summary ---']);
fputcsv($output, ['Total Requests', $total]);
fputcsv($output, ['Completed Requests', $completed]);
fputcsv($output, ['Completion Rate', $total > 0 ? round(($completed / $total) * 100, 1) . '%' : '0%']);
fputcsv($output, ['Avg Completion Time (hours)', $avg_hours]);
fputcsv($output, []);

// ============================================
// SECTION 2: Requests by Status
// ============================================
fputcsv($output, ['--- Requests by Status ---']);
fputcsv($output, ['Status', 'Count']);

$sql = "SELECT s.StatusName, COUNT(*) as Count 
        FROM maintenancerequest mr 
        JOIN status s ON mr.StatusID = s.StatusID 
        WHERE mr.SubmittedAt BETWEEN ? AND ? 
        GROUP BY s.StatusName ORDER BY Count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [$row['StatusName'], $row['Count']]);
}
fputcsv($output, []);

// ============================================
// SECTION 3: Requests by Priority
// ============================================
fputcsv($output, ['--- Requests by Priority ---']);
fputcsv($output, ['Priority', 'Count']);

$sql = "SELECT p.PriorityLevel, COUNT(*) as Count 
        FROM maintenancerequest mr 
        JOIN priority p ON mr.PriorityID = p.PriorityID 
        WHERE mr.SubmittedAt BETWEEN ? AND ? 
        GROUP BY p.PriorityLevel ORDER BY p.PriorityID DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [$row['PriorityLevel'], $row['Count']]);
}
fputcsv($output, []);

// ============================================
// SECTION 4: Requests by Category
// ============================================
fputcsv($output, ['--- Requests by Category ---']);
fputcsv($output, ['Category', 'Count']);

$sql = "SELECT c.CategoryName, COUNT(*) as Count 
        FROM maintenancerequest mr 
        JOIN category c ON mr.CategoryID = c.CategoryID 
        WHERE mr.SubmittedAt BETWEEN ? AND ? 
        GROUP BY c.CategoryName ORDER BY Count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [$row['CategoryName'], $row['Count']]);
}
fputcsv($output, []);

// ============================================
// SECTION 5: Requests by Location
// ============================================
fputcsv($output, ['--- Requests by Location ---']);
fputcsv($output, ['Building', 'Count']);

$sql = "SELECT l.BuildingName, COUNT(*) as Count 
        FROM maintenancerequest mr 
        JOIN location l ON mr.LocationID = l.LocationID 
        WHERE mr.SubmittedAt BETWEEN ? AND ? 
        GROUP BY l.BuildingName ORDER BY Count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [$row['BuildingName'], $row['Count']]);
}
fputcsv($output, []);

// ============================================
// SECTION 6: Technician Performance
// ============================================
fputcsv($output, ['--- Technician Performance ---']);
fputcsv($output, ['Technician', 'Assigned', 'Completed', 'Avg Hours']);

$sql = "SELECT u.Name,
            COUNT(DISTINCT a.RequestID) as Assigned,
            COUNT(DISTINCT CASE WHEN mr.StatusID = 5 THEN a.RequestID END) as Completed,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, a.AssignedAt, a.CompletedAt)), 1) as AvgHours
        FROM user u
        LEFT JOIN assignment a ON u.UserID = a.TechnicianID
        LEFT JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        WHERE u.RoleID = 2
        GROUP BY u.UserID ORDER BY Completed DESC";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [$row['Name'], $row['Assigned'], $row['Completed'], $row['AvgHours'] ?? 'N/A']);
}
fputcsv($output, []);

// ============================================
// SECTION 7: All Requests Detail
// ============================================
fputcsv($output, ['--- All Requests Detail ---']);
fputcsv($output, ['Request ID', 'Title', 'Status', 'Priority', 'Category', 'Location', 'Submitted By', 'Submitted At', 'Completed At']);

$sql = "SELECT mr.RequestID, mr.Title, s.StatusName, p.PriorityLevel, c.CategoryName,
            CONCAT(l.BuildingName, ' - ', l.FloorNumber, ' - ', l.RoomNumber) as Location,
            u.Name as Requester, mr.SubmittedAt, mr.CompletedAt
        FROM maintenancerequest mr
        JOIN status s ON mr.StatusID = s.StatusID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN user u ON mr.UserID = u.UserID
        WHERE mr.SubmittedAt BETWEEN ? AND ?
        ORDER BY mr.SubmittedAt DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    fputcsv($output, [
        $row['RequestID'],
        $row['Title'],
        $row['StatusName'],
        $row['PriorityLevel'],
        $row['CategoryName'],
        $row['Location'],
        $row['Requester'],
        $row['SubmittedAt'],
        $row['CompletedAt'] ?? 'Not completed'
    ]);
}

fclose($output);
exit();
?>