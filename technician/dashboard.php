<?php
/**
 * FixPoint - Technician Dashboard
 * Dashboard for technicians to view and manage assigned maintenance requests
 */

session_start();
require_once '../config/session-security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not technician
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';

$tech_id = $_SESSION['user_id'];
$tech_name = $_SESSION['name'];

// Get technician's statistics
$stats = [];

// Total assigned requests
$sql = "SELECT COUNT(DISTINCT a.RequestID) as total 
        FROM assignment a 
        WHERE a.TechnicianID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stats['total_assigned'] = $stmt->get_result()->fetch_assoc()['total'];

// Active requests (not completed/cancelled)
$sql = "SELECT COUNT(DISTINCT a.RequestID) as total 
        FROM assignment a
        JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        WHERE a.TechnicianID = ? AND mr.StatusID NOT IN (5, 6)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stats['active'] = $stmt->get_result()->fetch_assoc()['total'];

// Completed requests
$sql = "SELECT COUNT(DISTINCT a.RequestID) as total 
        FROM assignment a
        JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        WHERE a.TechnicianID = ? AND mr.StatusID = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stats['completed'] = $stmt->get_result()->fetch_assoc()['total'];

// In Progress requests
$sql = "SELECT COUNT(DISTINCT a.RequestID) as total 
        FROM assignment a
        JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        WHERE a.TechnicianID = ? AND mr.StatusID = 4";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stats['in_progress'] = $stmt->get_result()->fetch_assoc()['total'];

// Get assigned requests (active ones first)
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            u.Name as RequesterName,
            u.Email as RequesterEmail,
            u.Phone as RequesterPhone,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            p.PriorityID,
            s.StatusName,
            s.StatusID,
            a.AssignedAt,
            a.StartedAt,
            a.CompletedAt as AssignmentCompletedAt
        FROM assignment a
        JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        JOIN user u ON mr.UserID = u.UserID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE a.TechnicianID = ?
        ORDER BY 
            CASE WHEN mr.StatusID IN (5, 6) THEN 1 ELSE 0 END,
            p.PriorityID DESC,
            mr.SubmittedAt DESC
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$assigned_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Separate active and completed
$active_requests = [];
$completed_requests = [];

foreach ($assigned_requests as $req) {
    if ($req['StatusID'] == 5 || $req['StatusID'] == 6) {
        $completed_requests[] = $req;
    } else {
        $active_requests[] = $req;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - FixPoint</title>
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
                    <span class="logo-subtitle">Technician</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="my-tasks.php" class="nav-link">My Tasks</a>
                    <?php include '../includes/notification-bell.php'; ?>
                    <span style="color: #64748b;">👤 <?php echo e($tech_name); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="welcome-text">Welcome back, <?php echo e(explode(' ', $tech_name)[0]); ?>! 🔧</h1>
                <p class="user-info">
                    Technician Dashboard | View and manage your assigned tasks
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total Assigned</div>
                    <div class="stat-value"><?php echo $stats['total_assigned']; ?></div>
                    <div class="stat-info">All time tasks</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-label">🔔 Active Tasks</div>
                    <div class="stat-value"><?php echo $stats['active']; ?></div>
                    <div class="stat-info">Pending your work</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-info">Currently working on</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-info">Successfully finished</div>
                </div>
            </div>

            <!-- Active/Pending Requests -->
            <?php if (count($active_requests) > 0): ?>
            <div class="requests-section">
                <h2 class="section-title">🔔 Active Tasks (<?php echo count($active_requests); ?>)</h2>
                
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
                                <th>Assigned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_requests as $req): ?>
                                <tr style="<?php echo ($req['PriorityID'] >= 3) ? 'background: #fef2f2;' : ''; ?>">
                                    <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                    
                                    <!-- Title -->
                                    <td class="request-title">
                                        <?php echo e($req['Title']); ?>
                                        <?php if ($req['PriorityID'] == 4): ?>
                                            <span style="color: #ef4444;">🚨</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Requester -->
                                    <td>
                                        <strong><?php echo e($req['RequesterName']); ?></strong>
                                        <?php if ($req['RequesterPhone']): ?>
                                            <br><small style="color: #64748b;">📞 <?php echo e($req['RequesterPhone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Location -->
                                    <td>
                                        <strong><?php echo e($req['BuildingName']); ?></strong>
                                        <br>
                                        <small style="color: #64748b;">
                                            Floor: <?php echo e($req['FloorNumber']); ?>, 
                                            Room: <?php echo e($req['RoomNumber']); ?>
                                        </small>
                                    </td>
                                    
                                    <!-- Category -->
                                    <td><?php echo e($req['CategoryName']); ?></td>
                                    
                                    <!-- Priority -->
                                    <td>
                                        <span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>">
                                            <?php echo e($req['PriorityLevel']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Status -->
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                            <?php echo e($req['StatusName']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Assigned Date -->
                                    <td><?php echo formatDate($req['AssignedAt'], 'M d, Y'); ?></td>
                                    
                                    <!-- Actions -->
                                    <td>
                                        <a href="task-details.php?id=<?php echo $req['RequestID']; ?>" 
                                           class="btn btn-primary" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            🔧 Work On It
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="requests-section">
                <div class="no-requests">
                    <div class="no-requests-icon">🎉</div>
                    <h3>No active tasks!</h3>
                    <p>You don't have any pending tasks at the moment. Great job!</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recently Completed -->
            <?php if (count($completed_requests) > 0): ?>
            <div class="requests-section">
                <h2 class="section-title">✅ Recently Completed (<?php echo count($completed_requests); ?>)</h2>
                
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Assigned</th>
                                <th>Completed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_requests as $req): ?>
                                <tr>
                                    <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                    <td class="request-title"><?php echo e($req['Title']); ?></td>
                                    <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                    <td><?php echo e($req['CategoryName']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                            <?php echo e($req['StatusName']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($req['AssignedAt'], 'M d, Y'); ?></td>
                                    <td>
                                        <?php if ($req['CompletedAt']): ?>
                                            <?php echo formatDate($req['CompletedAt'], 'M d, Y'); ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="task-details.php?id=<?php echo $req['RequestID']; ?>" 
                                           class="btn btn-secondary" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            👁️ View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>