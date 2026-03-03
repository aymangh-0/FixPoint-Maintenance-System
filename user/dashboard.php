<?php
/**
 * FixPoint - User Dashboard
 * Dashboard for students and faculty to view their requests and submit new ones
 */

session_start();
require_once '../config/session-security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not a regular user (Student or Faculty)
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 4)) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];

// Get user request limits and stats
$limit_info = checkRequestLimits($conn, $user_id);

// Get next reset time
$reset_label = '';
$reset_sql = "SELECT u.LastResetAt, MIN(mr.SubmittedAt) as OldestRequest
              FROM user u
              LEFT JOIN maintenancerequest mr ON mr.UserID = u.UserID
                  AND mr.SubmittedAt > COALESCE(u.LastResetAt, DATE_SUB(NOW(), INTERVAL 7 DAY))
              WHERE u.UserID = ?
              GROUP BY u.UserID";
$reset_stmt = $conn->prepare($reset_sql);
$reset_stmt->bind_param("i", $user_id);
$reset_stmt->execute();
$reset_row = $reset_stmt->get_result()->fetch_assoc();

// Base: oldest request after last reset, fallback to LastResetAt itself
$base_date = $reset_row['OldestRequest'] ?? $reset_row['LastResetAt'] ?? null;

if ($base_date) {
    $reset_ts = strtotime($base_date) + 7 * 24 * 3600;
    $diff = $reset_ts - time();
    $reset_date_str = date("M d, Y", $reset_ts) . " at " . date("h:i A", $reset_ts);
    if ($diff > 0) {
        $days  = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $mins  = floor(($diff % 3600) / 60);
        if ($days > 0)      $countdown = "{$days}d {$hours}h";
        elseif ($hours > 0) $countdown = "{$hours}h {$mins}m";
        else                $countdown = "{$mins} min";
        $reset_label = "Resets on {$reset_date_str}";
    } else {
        $reset_label = "Limit has reset!";
    }
}
$stats = getUserRequestStats($conn, $user_id);

// Get user's recent requests (last 10)
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            l.BuildingName,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            s.StatusName
        FROM maintenancerequest mr
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.UserID = ?
        ORDER BY mr.SubmittedAt DESC
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body class="has-sidebar">
        <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="sidebar-logo-icon">🔧</span>
                <div>
                    <span class="sidebar-logo-text">FixPoint</span>
                    <span class="sidebar-logo-sub">SEU</span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role">User</span>
            </div>
            <?php include '../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">My Account</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🏠</span><span>Dashboard</span>
            </a>
            <a href="submit-request.php" class="sidebar-link <?php echo $current_page === 'submit-request' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📝</span><span>Submit Request</span>
            </a>
            <a href="my-requests.php" class="sidebar-link <?php echo $current_page === 'my-requests' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📋</span><span>My Requests</span>
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
            <div class="topbar-notif"><?php include '../includes/notification-bell.php'; ?></div>
        </div>


    <div class="dashboard">
        <div class="dashboard-container">
            <!-- Dashboard Header / Welcome Message -->
            <div class="dashboard-header">
                <h1 class="welcome-text">Welcome back, <?php echo e(explode(' ', $user_name)[0]); ?>! 👋</h1>
                <p class="user-info">
                    <?php echo e($user_email); ?> | 
                    <strong>User Dashboard</strong>
                </p>
            </div>

            <!-- Request Limit Alert -->
            <?php if (!$limit_info['can_submit']): ?>
                <div class="limit-alert danger">
                    ⚠️ <strong>Request Limit Reached:</strong> <?php echo e($limit_info['message']); ?>
                    <?php if ($reset_label): ?>
                        <div style="margin-top:0.4rem; font-size:0.85rem;">🕐 <?php echo e($reset_label); ?></div>
                    <?php endif; ?>
                </div>
            <?php elseif ($limit_info['week_remaining'] <= 1): ?>
                <div class="limit-alert">
                    💡 <strong>Reminder:</strong> <?php echo e($limit_info['message']); ?>
                    <?php if ($reset_label): ?>
                        <div style="margin-top:0.4rem; font-size:0.85rem;">🕐 <?php echo e($reset_label); ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="limit-alert success">
                    ✅ <?php echo e($limit_info['message']); ?>
                    <?php if ($reset_label): ?>
                        <div style="margin-top:0.25rem; font-size:0.8rem; opacity:0.75;">🔄 <?php echo e($reset_label); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total Requests</div>
                    <div class="stat-value"><?php echo $stats['Total']; ?></div>
                    <div class="stat-info">All time submissions</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['Pending']; ?></div>
                    <div class="stat-info">Awaiting review</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['In Progress']; ?></div>
                    <div class="stat-info">Being worked on</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['Completed']; ?></div>
                    <div class="stat-info">Issues resolved</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="quick-actions-title">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="submit-request.php" class="btn btn-primary btn-large">
                        ➕ Submit New Request
                    </a>
                    <a href="my-requests.php" class="btn btn-secondary btn-large">
                        📋 View All My Requests
                    </a>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="requests-section">
                <h2 class="section-title">Recent Requests</h2>
                
                <?php if (count($requests) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        <td class="request-title"><?php echo e($req['Title']); ?></td>
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
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">📭</div>
                        <h3>No requests yet</h3>
                        <p>Click "Submit New Request" to report your first maintenance issue.</p>
                        <br>
                        <a href="submit-request.php" class="btn btn-primary">Get Started</a>
                    </div>
                <?php endif; ?>
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