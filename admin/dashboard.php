<?php
/**
 * FixPoint - Admin Dashboard
 */
session_start();
require_once __DIR__ . '/../config/session-security.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { header("Location: ../index.php"); exit(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$stats = [];

$sql = "SELECT COUNT(*) as total FROM maintenancerequest";
$stats['total'] = $conn->query($sql)->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID = 1";
$stats['pending'] = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID IN (3, 4)";
$stats['in_progress'] = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM maintenancerequest WHERE StatusID = 5";
$stats['completed'] = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM user WHERE RoleID IN (3, 4)";
$stats['users'] = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM user WHERE RoleID = 2";
$stats['technicians'] = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT mr.RequestID, mr.Title, mr.Description, mr.SubmittedAt,
            u.Name as RequesterName, l.BuildingName, l.RoomNumber,
            c.CategoryName, p.PriorityLevel, s.StatusName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        ORDER BY mr.SubmittedAt DESC LIMIT 10";
$recent_requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT mr.RequestID, mr.Title, mr.SubmittedAt,
            u.Name as RequesterName, l.BuildingName, l.RoomNumber,
            c.CategoryName, s.StatusName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.PriorityID >= 3 AND s.StatusName NOT IN ('Completed', 'Cancelled')
        ORDER BY mr.PriorityID DESC, mr.SubmittedAt ASC LIMIT 5";
$urgent_requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body class="has-sidebar">

    <!-- SIDEBAR -->
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
                <span class="sidebar-user-name"><?php echo e($user_name); ?></span>
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
                <?php if ($stats['pending'] > 0): ?>
                    <span class="sidebar-badge"><?php echo $stats['pending']; ?></span>
                <?php endif; ?>
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

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Mobile Topbar -->
        <div class="topbar">
            <button class="hamburger" id="hamburgerBtn">☰</button>
            <div class="topbar-logo">
                <span>🔧</span><span>FixPoint</span>
            </div>
            <div class="topbar-notif">
                <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
            </div>
        </div>

        <div class="dashboard-container">

            <div class="dashboard-header">
                <h1 class="welcome-text">Admin Dashboard 🛠️</h1>
                <p class="user-info">System overview and management panel | <strong>Administrator</strong></p>
            </div>

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
                    <div class="stat-info">Users & Faculty</div>
                </div>
                <div class="stat-card" style="border-left-color: #06b6d4;">
                    <div class="stat-label">👨‍🔧 Technicians</div>
                    <div class="stat-value"><?php echo $stats['technicians']; ?></div>
                    <div class="stat-info">Active maintenance staff</div>
                </div>
            </div>

            <div class="quick-actions">
                <h2 class="quick-actions-title">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="all-requests.php?status=Pending" class="btn btn-primary btn-large">
                        📋 Review Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="all-requests.php" class="btn btn-secondary btn-large">🔍 All Requests</a>
                    <a href="users.php" class="btn btn-secondary btn-large">👥 Manage Users</a>
                    <a href="reports.php" class="btn btn-secondary btn-large">📊 Reports</a>
                </div>
            </div>

            <?php if (count($urgent_requests) > 0): ?>
            <div class="requests-section" style="border-left: 4px solid #ef4444;">
                <h2 class="section-title" style="color: #ef4444;">🚨 Urgent/High Priority Requests</h2>
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr><th>ID</th><th>Title</th><th>Requester</th><th>Location</th><th>Category</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($urgent_requests as $req): ?>
                            <tr style="background: #fef2f2;">
                                <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                <td class="request-title"><?php echo e($req['Title']); ?></td>
                                <td><?php echo e($req['RequesterName']); ?></td>
                                <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                <td><?php echo e($req['CategoryName']); ?></td>
                                <td><span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>"><?php echo e($req['StatusName']); ?></span></td>
                                <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y H:i'); ?></td>
                                <td><a href="request-details.php?id=<?php echo $req['RequestID']; ?>" class="btn btn-primary" style="padding:0.5rem 1rem;font-size:0.875rem;">⚡ Handle</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="requests-section">
                <h2 class="section-title">Recent Requests</h2>
                <?php if (count($recent_requests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr><th>ID</th><th>Title</th><th>Requester</th><th>Location</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $req): ?>
                            <tr>
                                <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                <td class="request-title"><?php echo e($req['Title']); ?></td>
                                <td><?php echo e($req['RequesterName']); ?></td>
                                <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                <td><?php echo e($req['CategoryName']); ?></td>
                                <td><span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>"><?php echo e($req['PriorityLevel']); ?></span></td>
                                <td><span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>"><?php echo e($req['StatusName']); ?></span></td>
                                <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y H:i'); ?></td>
                                <td><a href="request-details.php?id=<?php echo $req['RequestID']; ?>" class="btn btn-primary" style="padding:0.5rem 1rem;font-size:0.875rem;">👁️ View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align:center;margin-top:1.5rem;">
                    <a href="all-requests.php" class="btn btn-secondary">View All Requests →</a>
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

    <script>
        const sidebar  = document.getElementById('sidebar');
        const overlay  = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');
        const closeBtn  = document.getElementById('sidebarClose');

        function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('show');    document.body.style.overflow = 'hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow = ''; }

        hamburger?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        // Fix notification dropdown position inside sidebar
        const notifBell = document.getElementById('notifBell');
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifBell && notifDropdown) {
            notifBell.addEventListener('click', function() {
                if (notifDropdown.classList.contains('show')) {
                    const bellRect = notifBell.getBoundingClientRect();
                    const dropdownHeight = 440;
                    const viewportHeight = window.innerHeight;
                    
                    // إذا ما في مساحة تحت، يطلع فوق
                    let topPos = bellRect.bottom + 8;
                    if (topPos + dropdownHeight > viewportHeight) {
                        topPos = Math.max(8, bellRect.top - dropdownHeight - 8);
                    }
                    notifDropdown.style.top = topPos + 'px';
                }
            });
        }
    </script>
</body>
</html>