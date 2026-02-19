<?php
/**
 * FixPoint - Admin Dashboard
 * Dashboard for administrators to manage all maintenance requests
 */

session_start();
require_once '../config/session-security.php';

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

require_once '../config/database.php';
require_once '../config/helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get system-wide statistics
$stats = [];

// Total requests
$sql = "SELECT COUNT(*) as total FROM maintenancerequest";
$result = $conn->query($sql);
$stats['total'] = $result->fetch_assoc()['total'];

// Pending requests (need review)
$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID = 1";
$result = $conn->query($sql);
$stats['pending'] = $result->fetch_assoc()['count'];

// In Progress requests
$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID IN (3, 4)";
$result = $conn->query($sql);
$stats['in_progress'] = $result->fetch_assoc()['count'];

// Completed requests
$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID = 5";
$result = $conn->query($sql);
$stats['completed'] = $result->fetch_assoc()['count'];

// Total users
$sql = "SELECT COUNT(*) as count FROM user WHERE RoleID IN (3, 4)";
$result = $conn->query($sql);
$stats['users'] = $result->fetch_assoc()['count'];

// Total technicians
$sql = "SELECT COUNT(*) as count FROM user WHERE RoleID = 2";
$result = $conn->query($sql);
$stats['technicians'] = $result->fetch_assoc()['count'];

// Recent requests (last 10)
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            u.Name as RequesterName,
            l.BuildingName,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            s.StatusName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        ORDER BY mr.SubmittedAt DESC
        LIMIT 10";

$recent_requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Urgent/Critical requests
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.SubmittedAt,
            u.Name as RequesterName,
            l.BuildingName,
            l.RoomNumber,
            c.CategoryName,
            s.StatusName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.PriorityID >= 3 
        AND s.StatusName NOT IN ('Completed', 'Cancelled')
        ORDER BY mr.PriorityID DESC, mr.SubmittedAt ASC
        LIMIT 5";

$urgent_requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">
                    <span class="logo-icon">🔧</span>
                    <span class="logo-text">FixPoint</span>
                    <span class="logo-subtitle">SEU - Admin</span>
                </div>
                <nav class="nav-links">
                    <a href="backup.php" class="nav-link">Backup</a>
                    <a href="locations.php" class="nav-link">Locations</a>
                    <a href="audit-logs.php" class="nav-link">Audit Logs</a>
                    <?php include '../includes/notification-bell.php'; ?>
                    <span style="color: #64748b;">👤 <?php echo e($user_name); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Dashboard Header / Welcome Message -->
            <div class="dashboard-header">
                <h1 class="welcome-text">Admin Dashboard 🛠️</h1>
                <p class="user-info">
                    System overview and management panel | 
                    <strong>Administrator</strong>
                </p>
            </div>

            <!-- System Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total Requests</div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-info">All time submissions</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending Review</div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-info">Awaiting admin action</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-info">Assigned or being worked on</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-info">Successfully resolved</div>
                </div>

                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <div class="stat-label">👥 Total Users</div>
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                    <div class="stat-info">Students & Faculty</div>
                </div>

                <div class="stat-card" style="border-left-color: #06b6d4;">
                    <div class="stat-label">👨‍🔧 Technicians</div>
                    <div class="stat-value"><?php echo $stats['technicians']; ?></div>
                    <div class="stat-info">Active maintenance staff</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="quick-actions-title">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="all-requests.php?status=Pending" class="btn btn-primary btn-large">
                        📋 Review Pending Requests (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="all-requests.php" class="btn btn-secondary btn-large">
                        🔍 View All Requests
                    </a>
                    <a href="users.php" class="btn btn-secondary btn-large">
                        👥 Manage Users
                    </a>
                    <a href="reports.php" class="btn btn-secondary btn-large">
                        📊 Generate Reports
                    </a>
                </div>
            </div>

            <!-- Urgent Requests Alert -->
            <?php if (count($urgent_requests) > 0): ?>
            <div class="requests-section" style="border-left: 4px solid #ef4444;">
                <h2 class="section-title" style="color: #ef4444;">
                    🚨 Urgent/High Priority Requests
                </h2>
                
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Requester</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($urgent_requests as $req): ?>
                                <tr style="background: #fef2f2;">
                                    <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                    <td class="request-title"><?php echo e($req['Title']); ?></td>
                                    <td><?php echo e($req['RequesterName']); ?></td>
                                    <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                    <td><?php echo e($req['CategoryName']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                            <?php echo e($req['StatusName']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y H:i'); ?></td>
                                    <td>
                                        <a href="request-details.php?id=<?php echo $req['RequestID']; ?>" 
                                           class="btn btn-primary" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            ⚡ Handle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Requests -->
            <div class="requests-section">
                <h2 class="section-title">Recent Requests</h2>
                
                <?php if (count($recent_requests) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Requester</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_requests as $req): ?>
                                    <tr>
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        <td class="request-title"><?php echo e($req['Title']); ?></td>
                                        <td><?php echo e($req['RequesterName']); ?></td>
                                        <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                        <td><?php echo e($req['CategoryName']); ?></td>
                                        <td>
                                            <span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>">
                                                <?php echo e($req['PriorityLevel']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                                <?php echo e($req['StatusName']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y H:i'); ?></td>
                                        <td>
                                            <a href="request-details.php?id=<?php echo $req['RequestID']; ?>" 
                                               class="btn btn-primary" 
                                               style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                👁️ View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="all-requests.php" class="btn btn-secondary">
                            View All Requests →
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">📭</div>
                        <h3>No requests yet</h3>
                        <p>No maintenance requests have been submitted to the system.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</body>
</html>