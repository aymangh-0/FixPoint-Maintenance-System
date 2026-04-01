<?php
/**
 * FixPoint - Export Reports to PDF
 * Generates a clean HTML report optimized for PDF saving
 * The browser will prompt to save/print as PDF automatically
 */

session_start();

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Get date range (match parameter names from reports.php form)
$date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : '2024-01-01';
$date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
$date_to_display = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : date('Y-m-d');

// Get additional filters (same as reports.php)
$filter_priority = isset($_GET['priority']) && $_GET['priority'] !== '' ? (int)$_GET['priority'] : null;
$filter_location = isset($_GET['location']) && $_GET['location'] !== '' ? (int)$_GET['location'] : null;
$filter_technician = isset($_GET['technician']) && $_GET['technician'] !== '' ? (int)$_GET['technician'] : null;

// Build dynamic WHERE clause
$where_parts = ["mr.SubmittedAt BETWEEN ? AND ?"];
$where_types = "ss";
$where_params = [$date_from, $date_to];

if ($filter_priority) {
    $where_parts[] = "mr.PriorityID = ?";
    $where_types .= "i";
    $where_params[] = $filter_priority;
}
if ($filter_location) {
    $where_parts[] = "mr.LocationID = ?";
    $where_types .= "i";
    $where_params[] = $filter_location;
}
if ($filter_technician) {
    $where_parts[] = "a_filter.TechnicianID = ?";
    $where_types .= "i";
    $where_params[] = $filter_technician;
}

$where_clause = implode(' AND ', $where_parts);
$tech_join = $filter_technician ? "LEFT JOIN assignment a_filter ON mr.RequestID = a_filter.RequestID" : "";

// Get filter labels for report header
$filter_labels = [];
if ($filter_priority) {
    $r = $conn->prepare("SELECT PriorityLevel FROM priority WHERE PriorityID = ?");
    $r->bind_param("i", $filter_priority); $r->execute();
    $filter_labels[] = "Priority: " . $r->get_result()->fetch_assoc()['PriorityLevel'];
}
if ($filter_location) {
    $r = $conn->prepare("SELECT BuildingName FROM location WHERE LocationID = ?");
    $r->bind_param("i", $filter_location); $r->execute();
    $filter_labels[] = "Building: " . $r->get_result()->fetch_assoc()['BuildingName'];
}
if ($filter_technician) {
    $r = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
    $r->bind_param("i", $filter_technician); $r->execute();
    $filter_labels[] = "Technician: " . $r->get_result()->fetch_assoc()['Name'];
}

// === FETCH ALL DATA (with filters) ===

// Summary
$sql = "SELECT COUNT(*) as Total FROM maintenancerequest mr $tech_join WHERE $where_clause";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['Total'];

$sql = "SELECT COUNT(*) as c FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 5 AND $where_clause";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$completed = $stmt->get_result()->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) as c FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 1 AND $where_clause";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$pending = $stmt->get_result()->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) as c FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 4 AND $where_clause";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$in_progress = $stmt->get_result()->fetch_assoc()['c'];

$sql = "SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, mr.SubmittedAt, mr.CompletedAt)), 1) as h FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 5 AND mr.CompletedAt IS NOT NULL AND $where_clause";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$avg_hours = $stmt->get_result()->fetch_assoc()['h'] ?? 'N/A';

// By Status
$sql = "SELECT s.StatusName, COUNT(*) as Count FROM maintenancerequest mr JOIN status s ON mr.StatusID = s.StatusID $tech_join WHERE $where_clause GROUP BY s.StatusName ORDER BY Count DESC";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$by_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// By Priority
$sql = "SELECT p.PriorityLevel, COUNT(*) as Count FROM maintenancerequest mr JOIN priority p ON mr.PriorityID = p.PriorityID $tech_join WHERE $where_clause GROUP BY p.PriorityLevel ORDER BY p.PriorityID DESC";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$by_priority = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// By Category
$sql = "SELECT c.CategoryName, COUNT(*) as Count FROM maintenancerequest mr JOIN category c ON mr.CategoryID = c.CategoryID $tech_join WHERE $where_clause GROUP BY c.CategoryName ORDER BY Count DESC LIMIT 10";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$by_category = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// By Location
$sql = "SELECT l.BuildingName, COUNT(*) as Count FROM maintenancerequest mr JOIN location l ON mr.LocationID = l.LocationID $tech_join WHERE $where_clause GROUP BY l.BuildingName ORDER BY Count DESC LIMIT 10";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$by_location = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Technician Performance
$sql = "SELECT u.Name, COUNT(DISTINCT a.RequestID) as Assigned, COUNT(DISTINCT CASE WHEN mr.StatusID = 5 THEN a.RequestID END) as Completed, ROUND(AVG(TIMESTAMPDIFF(HOUR, a.AssignedAt, a.CompletedAt)), 1) as AvgHours FROM user u LEFT JOIN assignment a ON u.UserID = a.TechnicianID LEFT JOIN maintenancerequest mr ON a.RequestID = mr.RequestID WHERE u.RoleID = 2 GROUP BY u.UserID ORDER BY Completed DESC";
$tech_performance = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// All Requests
$sql = "SELECT mr.RequestID, mr.Title, s.StatusName, p.PriorityLevel, c.CategoryName, CONCAT(l.BuildingName, ' - ', l.FloorNumber, ' - ', l.RoomNumber) as Location, u.Name as Requester, mr.SubmittedAt, mr.CompletedAt FROM maintenancerequest mr JOIN status s ON mr.StatusID = s.StatusID JOIN priority p ON mr.PriorityID = p.PriorityID JOIN category c ON mr.CategoryID = c.CategoryID JOIN location l ON mr.LocationID = l.LocationID JOIN user u ON mr.UserID = u.UserID $tech_join WHERE $where_clause ORDER BY mr.SubmittedAt DESC";
$stmt = $conn->prepare($sql); $stmt->bind_param($where_types, ...$where_params); $stmt->execute();
$all_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$completion_rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FixPoint Report - <?php echo $date_from; ?> to <?php echo $date_to_display; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: Arial, sans-serif; 
            color: #1e293b; 
            line-height: 1.6;
            padding: 0;
        }
        
        /* Auto-trigger print dialog */
        @media screen {
            body { padding: 2rem; background: #f1f5f9; }
            .report { 
                max-width: 210mm; 
                margin: 0 auto; 
                background: white; 
                padding: 2rem;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border-radius: 0.5rem;
            }
            .no-print { 
                text-align: center; 
                margin-bottom: 1.5rem;
            }
            .no-print button {
                background: #2563eb;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 0.5rem;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                margin: 0 0.5rem;
            }
            .no-print button:hover { background: #1d4ed8; }
            .no-print .btn-back {
                background: #64748b;
            }
        }
        
        @media print {
            body { padding: 0; background: white; }
            .report { max-width: 100%; padding: 1rem; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
        
        .report-header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .report-logo { font-size: 2rem; margin-bottom: 0.5rem; }
        .report-title { font-size: 1.5rem; color: #1e293b; margin-bottom: 0.25rem; }
        .report-subtitle { color: #64748b; font-size: 0.9rem; }
        
        .section { margin-bottom: 2rem; }
        .section-title { 
            font-size: 1.1rem; 
            color: #2563eb; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 0.5rem; 
            margin-bottom: 1rem; 
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }
        .summary-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .summary-label { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
            padding: 0.6rem 0.75rem;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        td {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
        }
        tr:nth-child(even) { background: #f8fafc; }
        
        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .report-footer {
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
            margin-top: 2rem;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
    </style>
</head>
<body>

    <!-- Download/Print buttons (hidden when printing) -->
    <div class="no-print">
        <button onclick="window.print()">📄 Save as PDF / Print</button>
        <button class="btn-back" onclick="window.close(); history.back();">← Back to Reports</button>
    </div>

    <div class="report">
        
        <!-- Header -->
        <div class="report-header">
            <div class="report-logo">🔧</div>
            <h1 class="report-title">FixPoint Maintenance Report</h1>
            <p class="report-subtitle">
                Saudi Electronic University · 
                <?php echo date('M d, Y', strtotime($date_from)); ?> — <?php echo date('M d, Y', strtotime($date_to_display)); ?>
            </p>
            <?php if (!empty($filter_labels)): ?>
                <p class="report-subtitle" style="margin-top: 0.25rem;">
                    Filters: <?php echo htmlspecialchars(implode(' | ', $filter_labels)); ?>
                </p>
            <?php endif; ?>
            <p class="report-subtitle">Generated: <?php echo date('M d, Y H:i'); ?> by <?php echo htmlspecialchars($_SESSION['name']); ?></p>
        </div>

        <!-- Summary Cards -->
        <div class="section">
            <h2 class="section-title">📊 Summary Overview</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-value"><?php echo $total; ?></div>
                    <div class="summary-label">Total Requests</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?php echo $completed; ?></div>
                    <div class="summary-label">Completed</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?php echo $completion_rate; ?>%</div>
                    <div class="summary-label">Completion Rate</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?php echo $avg_hours; ?>h</div>
                    <div class="summary-label">Avg Completion</div>
                </div>
            </div>
        </div>

        <!-- By Status & Priority (side by side) -->
        <div class="two-col">
            <div class="section">
                <h2 class="section-title">📋 By Status</h2>
                <table>
                    <thead><tr><th>Status</th><th>Count</th><th>%</th></tr></thead>
                    <tbody>
                        <?php foreach ($by_status as $row): ?>
                        <tr>
                            <td><?php echo $row['StatusName']; ?></td>
                            <td><?php echo $row['Count']; ?></td>
                            <td><?php echo $total > 0 ? round(($row['Count'] / $total) * 100, 1) : 0; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="section">
                <h2 class="section-title">⚡ By Priority</h2>
                <table>
                    <thead><tr><th>Priority</th><th>Count</th><th>%</th></tr></thead>
                    <tbody>
                        <?php foreach ($by_priority as $row): ?>
                        <tr>
                            <td><?php echo $row['PriorityLevel']; ?></td>
                            <td><?php echo $row['Count']; ?></td>
                            <td><?php echo $total > 0 ? round(($row['Count'] / $total) * 100, 1) : 0; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- By Category & Location -->
        <div class="two-col">
            <div class="section">
                <h2 class="section-title">📁 By Category</h2>
                <table>
                    <thead><tr><th>Category</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($by_category as $row): ?>
                        <tr>
                            <td><?php echo $row['CategoryName']; ?></td>
                            <td><?php echo $row['Count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="section">
                <h2 class="section-title">📍 By Location</h2>
                <table>
                    <thead><tr><th>Building</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($by_location as $row): ?>
                        <tr>
                            <td><?php echo $row['BuildingName']; ?></td>
                            <td><?php echo $row['Count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Technician Performance -->
        <div class="section">
            <h2 class="section-title">👨‍🔧 Technician Performance</h2>
            <table>
                <thead>
                    <tr><th>Technician</th><th>Assigned</th><th>Completed</th><th>Avg Hours</th><th>Rate</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tech_performance as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                        <td><?php echo $row['Assigned']; ?></td>
                        <td><?php echo $row['Completed']; ?></td>
                        <td><?php echo $row['AvgHours'] ?? 'N/A'; ?></td>
                        <td><?php echo $row['Assigned'] > 0 ? round(($row['Completed'] / $row['Assigned']) * 100) : 0; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="page-break"></div>

        <!-- All Requests Detail -->
        <div class="section">
            <h2 class="section-title">📝 All Requests Detail (<?php echo count($all_requests); ?> requests)</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Requester</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_requests as $row): ?>
                    <tr>
                        <td><?php echo $row['RequestID']; ?></td>
                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                        <td><?php echo $row['StatusName']; ?></td>
                        <td><?php echo $row['PriorityLevel']; ?></td>
                        <td><?php echo $row['CategoryName']; ?></td>
                        <td style="font-size: 0.75rem;"><?php echo htmlspecialchars($row['Location']); ?></td>
                        <td><?php echo htmlspecialchars($row['Requester']); ?></td>
                        <td style="font-size: 0.75rem;"><?php echo date('M d, Y', strtotime($row['SubmittedAt'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="report-footer">
            <p>FixPoint Maintenance Management System · Saudi Electronic University</p>
            <p>This report was auto-generated. For questions, contact the system administrator.</p>
        </div>

    </div>

    <script>
        // Auto-trigger print dialog when page loads
        window.onload = function() {
            window.print();
        };
    </script>

</body>
</html>