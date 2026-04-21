<?php
/**
 * FixPoint - Reports & Analytics (Admin)
 * Generate statistics, view analytics, and export data
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$admin_id = $_SESSION['user_id'];

// Get date range filter (fix: match form field names)
$date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : '2024-01-01';
$date_to   = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
$date_to_display = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : date('Y-m-d');

// Get additional filters
$filter_priority = isset($_GET['priority']) && $_GET['priority'] !== '' ? (int)$_GET['priority'] : null;
$filter_location = isset($_GET['location']) && $_GET['location'] !== '' ? (int)$_GET['location'] : null;
$filter_technician = isset($_GET['technician']) && $_GET['technician'] !== '' ? (int)$_GET['technician'] : null;

// Build dynamic WHERE clause for filtered queries
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

// Fetch dropdown options for filters
$priorities_list = $conn->query("SELECT PriorityID, PriorityLevel FROM priority ORDER BY PriorityID")->fetch_all(MYSQLI_ASSOC);
$locations_list = $conn->query("SELECT DISTINCT LocationID, BuildingName FROM location ORDER BY BuildingName")->fetch_all(MYSQLI_ASSOC);
$technicians_list = $conn->query("SELECT UserID, Name FROM user WHERE RoleID = 2 ORDER BY Name")->fetch_all(MYSQLI_ASSOC);

// === OVERALL STATISTICS (filtered by date range + filters) ===
$stats = [];

// Total requests (filtered)
$sql = "SELECT COUNT(*) as total FROM maintenancerequest mr $tech_join WHERE $where_clause";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$stats['total_requests'] = $stmt->get_result()->fetch_assoc()['total'];

// Total users
$sql = "SELECT COUNT(*) as total FROM user WHERE RoleID IN (3, 4)";
$result = $conn->query($sql);
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total technicians
$sql = "SELECT COUNT(*) as total FROM user WHERE RoleID = 2";
$result = $conn->query($sql);
$stats['total_technicians'] = $result->fetch_assoc()['total'];

// Completed requests (filtered)
$sql = "SELECT COUNT(*) as total FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 5 AND $where_clause";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$stats['completed'] = $stmt->get_result()->fetch_assoc()['total'];

// Pending requests (filtered)
$sql = "SELECT COUNT(*) as total FROM maintenancerequest mr $tech_join WHERE mr.StatusID = 1 AND $where_clause";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$stats['pending'] = $stmt->get_result()->fetch_assoc()['total'];

// === REQUESTS BY STATUS ===
$sql = "SELECT s.StatusName, COUNT(*) as Count 
        FROM maintenancerequest mr
        JOIN status s ON mr.StatusID = s.StatusID
        $tech_join
        WHERE $where_clause
        GROUP BY s.StatusName
        ORDER BY Count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$by_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === REQUESTS BY PRIORITY ===
$sql = "SELECT p.PriorityLevel, COUNT(*) as Count 
        FROM maintenancerequest mr
        JOIN priority p ON mr.PriorityID = p.PriorityID
        $tech_join
        WHERE $where_clause
        GROUP BY p.PriorityLevel
        ORDER BY p.PriorityID DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$by_priority = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === REQUESTS BY CATEGORY ===
$sql = "SELECT c.CategoryName, COUNT(*) as Count 
        FROM maintenancerequest mr
        JOIN category c ON mr.CategoryID = c.CategoryID
        $tech_join
        WHERE $where_clause
        GROUP BY c.CategoryName
        ORDER BY Count DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$by_category = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === REQUESTS BY LOCATION ===
$sql = "SELECT l.BuildingName, COUNT(*) as Count 
        FROM maintenancerequest mr
        JOIN location l ON mr.LocationID = l.LocationID
        $tech_join
        WHERE $where_clause
        GROUP BY l.BuildingName
        ORDER BY Count DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$by_location = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === TOP REQUESTERS ===
$sql = "SELECT u.Name, u.Email, r.RoleName, COUNT(*) as RequestCount 
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN role r ON u.RoleID = r.RoleID
        $tech_join
        WHERE $where_clause
        GROUP BY u.UserID
        ORDER BY RequestCount DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$top_requesters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === TECHNICIAN PERFORMANCE ===
$sql = "SELECT 
            u.Name,
            COUNT(DISTINCT a.RequestID) as AssignedRequests,
            COUNT(DISTINCT CASE WHEN mr.StatusID = 5 THEN a.RequestID END) as CompletedRequests,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, a.AssignedAt, a.CompletedAt)), 1) as AvgCompletionHours
        FROM user u
        LEFT JOIN assignment a ON u.UserID = a.TechnicianID
        LEFT JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        WHERE u.RoleID = 2
        GROUP BY u.UserID
        ORDER BY CompletedRequests DESC";
$tech_performance = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// === REQUESTS OVER TIME (Last 30 days) ===
$sql = "SELECT 
            DATE(SubmittedAt) as Date,
            COUNT(*) as Count
        FROM maintenancerequest
        WHERE SubmittedAt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(SubmittedAt)
        ORDER BY Date ASC";
$requests_timeline = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// === AVERAGE COMPLETION TIME ===
$sql = "SELECT 
            ROUND(AVG(TIMESTAMPDIFF(HOUR, mr.SubmittedAt, mr.CompletedAt)), 1) as AvgHours
        FROM maintenancerequest mr
        $tech_join
        WHERE mr.StatusID = 5 
        AND mr.CompletedAt IS NOT NULL
        AND $where_clause";
$stmt = $conn->prepare($sql);
$stmt->bind_param($where_types, ...$where_params);
$stmt->execute();
$avg_completion = $stmt->get_result()->fetch_assoc();
$stats['avg_completion_hours'] = $avg_completion['AvgHours'] ?? 0;


$current_page = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .report-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .chart-container {
            margin-top: 1.5rem;
        }
        
        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-label {
            min-width: 150px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .chart-bar-container {
            flex: 1;
            height: 30px;
            background: #f1f5f9;
            border-radius: 0.25rem;
            overflow: hidden;
            position: relative;
        }
        
        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 0.5rem;
            transition: width 0.3s ease;
        }
        
        .chart-value {
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .export-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 2px dashed #cbd5e1;
            text-align: center;
        }
        
        .date-filters {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body class="has-sidebar">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="sidebar-logo-icon">🔧</span>
                <div>
                    <span class="sidebar-logo-text">FixPoint</span>
                    <span class="sidebar-logo-sub">SEU Admin</span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
            <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📊</span><span>Dashboard</span>
            </a>
            <a href="all-requests.php" class="sidebar-link <?php echo $current_page === 'all-requests' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📋</span><span>All Requests</span>
            </a>
            <a href="users.php" class="sidebar-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <span class="sidebar-icon">👥</span><span>Manage Users</span>
            </a>
            <div class="sidebar-section-label">Management</div>
            <a href="locations.php" class="sidebar-link <?php echo $current_page === 'locations' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📍</span><span>Locations</span>
            </a>
            <a href="reports.php" class="sidebar-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📈</span><span>Reports</span>
            </a>
            <a href="all-feedback.php" class="sidebar-link <?php echo $current_page === 'all-feedback' ? 'active' : ''; ?>">
                <span class="sidebar-icon">⭐</span><span>Feedback</span>
            </a>
            <a href="audit-logs.php" class="sidebar-link <?php echo $current_page === 'audit-logs' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🔍</span><span>Audit Logs</span>
            </a>
            <a href="backup.php" class="sidebar-link <?php echo $current_page === 'backup' ? 'active' : ''; ?>">
                <span class="sidebar-icon">💾</span><span>Backup</span>
            </a>

            <div class="sidebar-divider"></div>
            <a href="../auth/logout.php" class="sidebar-link sidebar-logout">
                <span class="sidebar-icon">🚪</span><span>Logout</span>
            </a>
        </nav>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="main-content">
        <div class="topbar">
            <button class="hamburger" id="hamburgerBtn">☰</button>
            <div class="topbar-logo"><span>🔧</span><span>FixPoint</span></div>
            <div class="topbar-notif"><?php include __DIR__ . '/../includes/notification-bell.php'; ?></div>
        </div>


    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Page Header -->
            <div class="dashboard-header">
                <h1 class="welcome-text">Reports & Analytics 📊</h1>
                <p class="user-info">System statistics, trends, and performance metrics</p>
            </div>

            <!-- Filters -->
            <div class="report-card">
                <h2 class="section-title">🔍 Filter Reports</h2>
                
                <form method="GET" action="" class="date-filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">From Date</label>
                        <input 
                            type="date" 
                            name="date_from" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($date_from); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">To Date</label>
                        <input 
                            type="date" 
                            name="date_to" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($date_to_display); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Priority</label>
                        <select name="priority" class="form-input">
                            <option value="">All Priorities</option>
                            <?php foreach ($priorities_list as $p): ?>
                                <option value="<?php echo $p['PriorityID']; ?>" <?php echo $filter_priority == $p['PriorityID'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['PriorityLevel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Building</label>
                        <select name="location" class="form-input">
                            <option value="">All Buildings</option>
                            <?php foreach ($locations_list as $loc): ?>
                                <option value="<?php echo $loc['LocationID']; ?>" <?php echo $filter_location == $loc['LocationID'] ? 'selected' : ''; ?>>
                                    <?php echo e($loc['BuildingName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Technician</label>
                        <select name="technician" class="form-input">
                            <option value="">All Technicians</option>
                            <?php foreach ($technicians_list as $tech): ?>
                                <option value="<?php echo $tech['UserID']; ?>" <?php echo $filter_technician == $tech['UserID'] ? 'selected' : ''; ?>>
                                    <?php echo e($tech['Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">📊 Apply</button>
                        <a href="reports.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
                
                <p style="color: #64748b; margin-top: 1rem; font-size: 0.875rem;">
                    Showing data from <strong><?php echo date('M d, Y', strtotime($date_from)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($date_to_display)); ?></strong>
                    <?php if ($filter_priority): ?>
                        | Priority: <strong><?php foreach ($priorities_list as $p) { if ($p['PriorityID'] == $filter_priority) echo e($p['PriorityLevel']); } ?></strong>
                    <?php endif; ?>
                    <?php if ($filter_location): ?>
                        | Building: <strong><?php foreach ($locations_list as $loc) { if ($loc['LocationID'] == $filter_location) echo e($loc['BuildingName']); } ?></strong>
                    <?php endif; ?>
                    <?php if ($filter_technician): ?>
                        | Technician: <strong><?php foreach ($technicians_list as $tech) { if ($tech['UserID'] == $filter_technician) echo e($tech['Name']); } ?></strong>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total Requests</div>
                    <div class="stat-value"><?php echo $stats['total_requests']; ?></div>
                    <div class="stat-info">In selected range</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-info">
                        <?php 
                        $completion_rate = $stats['total_requests'] > 0 ? round(($stats['completed'] / $stats['total_requests']) * 100) : 0;
                        echo $completion_rate; 
                        ?>% completion rate
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-info">Awaiting action</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">⏱️ Avg. Completion</div>
                    <div class="stat-value"><?php echo $stats['avg_completion_hours']; ?>h</div>
                    <div class="stat-info">Average time to complete</div>
                </div>
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <div class="stat-label">👥 Total Users</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-info">Users & Faculty</div>
                </div>
                <div class="stat-card" style="border-left-color: #f59e0b;">
                    <div class="stat-label">👨‍🔧 Technicians</div>
                    <div class="stat-value"><?php echo $stats['total_technicians']; ?></div>
                    <div class="stat-info">Active staff</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                
                <!-- Requests by Status -->
                <div class="report-card">
                    <h2 class="section-title">📊 Requests by Status</h2>
                    <div class="chart-container">
                        <?php 
                        $max_count = !empty($by_status) ? max(array_column($by_status, 'Count')) : 0;
                        foreach ($by_status as $item): 
                            $percentage = $max_count > 0 ? ($item['Count'] / $max_count) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="chart-label"><?php echo e($item['StatusName']); ?></div>
                                <div class="chart-bar-container">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%;">
                                        <span class="chart-value"><?php echo $item['Count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_status)): ?>
                            <p style="text-align: center; color: #64748b; padding: 2rem;">No data available for the selected date range.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requests by Priority -->
                <div class="report-card">
                    <h2 class="section-title">⚡ Requests by Priority</h2>
                    <div class="chart-container">
                        <?php 
                        $max_count = !empty($by_priority) ? max(array_column($by_priority, 'Count')) : 0;
                        foreach ($by_priority as $item): 
                            $percentage = $max_count > 0 ? ($item['Count'] / $max_count) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="chart-label"><?php echo e($item['PriorityLevel']); ?></div>
                                <div class="chart-bar-container">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%;">
                                        <span class="chart-value"><?php echo $item['Count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_priority)): ?>
                            <p style="text-align: center; color: #64748b; padding: 2rem;">No data available for the selected date range.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requests by Category -->
                <div class="report-card">
                    <h2 class="section-title">📂 Top Categories</h2>
                    <div class="chart-container">
                        <?php 
                        $max_count = count($by_category) > 0 ? max(array_column($by_category, 'Count')) : 1;
                        foreach ($by_category as $item): 
                            $percentage = $max_count > 0 ? ($item['Count'] / $max_count) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="chart-label"><?php echo e($item['CategoryName']); ?></div>
                                <div class="chart-bar-container">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%;">
                                        <span class="chart-value"><?php echo $item['Count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Requests by Location -->
                <div class="report-card">
                    <h2 class="section-title">📍 Top Locations</h2>
                    <div class="chart-container">
                        <?php 
                        $max_count = count($by_location) > 0 ? max(array_column($by_location, 'Count')) : 1;
                        foreach ($by_location as $item): 
                            $percentage = $max_count > 0 ? ($item['Count'] / $max_count) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="chart-label"><?php echo e($item['BuildingName']); ?></div>
                                <div class="chart-bar-container">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%;">
                                        <span class="chart-value"><?php echo $item['Count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Top Requesters -->
            <div class="report-card">
                <h2 class="section-title">👥 Top Requesters</h2>
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Total Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_requesters as $user): ?>
                                <tr>
                                    <td><strong>#<?php echo $rank++; ?></strong></td>
                                    <td class="request-title"><?php echo e($user['Name']); ?></td>
                                    <td><?php echo e($user['Email']); ?></td>
                                    <td><?php echo e($user['RoleName']); ?></td>
                                    <td><strong><?php echo $user['RequestCount']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Technician Performance -->
            <div class="report-card">
                <h2 class="section-title">👨‍🔧 Technician Performance</h2>
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Assigned Requests</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                                <th>Avg. Completion Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tech_performance as $tech): ?>
                                <tr>
                                    <td class="request-title"><?php echo e($tech['Name']); ?></td>
                                    <td><strong><?php echo $tech['AssignedRequests']; ?></strong></td>
                                    <td><strong><?php echo $tech['CompletedRequests']; ?></strong></td>
                                    <td>
                                        <?php 
                                        $rate = $tech['AssignedRequests'] > 0 ? round(($tech['CompletedRequests'] / $tech['AssignedRequests']) * 100) : 0;
                                        $color = $rate >= 80 ? '#10b981' : ($rate >= 50 ? '#f59e0b' : '#ef4444');
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                            <?php echo $rate; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $tech['AvgCompletionHours'] ? $tech['AvgCompletionHours'] . ' hours' : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Section -->
            <div class="export-section">
                <h3 style="color: #1e293b; margin-bottom: 0.5rem;">📥 Export Data</h3>
                <p style="color: #64748b; margin-bottom: 1.5rem;">
                    Export reports and statistics for further analysis
                </p>
                <?php
                $export_params = http_build_query(array_filter([
                    'date_from' => $date_from,
                    'date_to' => $date_to_display,
                    'priority' => $filter_priority,
                    'location' => $filter_location,
                    'technician' => $filter_technician,
                ]));
                ?>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <button onclick="window.print()" class="btn btn-primary">
                        🖨️ Print Report
                    </button>
                    <a href="export-csv.php?<?php echo $export_params; ?>" class="btn btn-secondary">
                        📊 Export to CSV
                    </a>
                    <a href="export-pdf.php?<?php echo $export_params; ?>" target="_blank" class="btn btn-secondary">
                        📄 Export to PDF
                    </a>
                </div>
            </div>

        </div>
    </div>
    </div><!-- end main-content -->

    <script>
        const sidebar   = document.getElementById('sidebar');
        const overlay   = document.getElementById('sidebarOverlay');
        const notifBell = document.getElementById('notifBell');
        const notifDropdown = document.getElementById('notifDropdown');

        function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('show');    document.body.style.overflow='hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow=''; }

        document.getElementById('hamburgerBtn')?.addEventListener('click', openSidebar);
        document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);
        document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);

        if (notifBell && notifDropdown) {
            notifBell.addEventListener('click', function() {
                if (notifDropdown.classList.contains('show')) {
                    const rect = notifBell.getBoundingClientRect();
                    let top = rect.bottom + 8;
                    if (top + 440 > window.innerHeight) top = Math.max(8, rect.top - 448);
                    notifDropdown.style.top = top + 'px';
                }
            });
        }
    </script>
</body>
</html>