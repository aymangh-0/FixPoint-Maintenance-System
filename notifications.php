<?php
/**
 * FixPoint - Notifications Page
 * View all notifications for the logged-in user
 */

session_start();
require_once '../config/session-security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'config/database.php';
require_once 'config/helpers.php';

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query
$sql = "SELECT 
            n.NotificationID,
            n.Message,
            n.IsRead,
            n.CreatedAt,
            n.RequestID,
            mr.Title as RequestTitle,
            mr.StatusID
        FROM notification n
        LEFT JOIN maintenancerequest mr ON n.RequestID = mr.RequestID
        WHERE n.UserID = ?";

if ($filter == 'unread') {
    $sql .= " AND n.IsRead = 0";
} elseif ($filter == 'read') {
    $sql .= " AND n.IsRead = 1";
}

$sql .= " ORDER BY n.CreatedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count unread
$unread_sql = "SELECT COUNT(*) as unread FROM notification WHERE UserID = ? AND IsRead = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];

// Determine dashboard link based on role
$dashboard_link = 'index.php';
if ($role_id == 1) $dashboard_link = 'admin/dashboard.php';
elseif ($role_id == 2) $dashboard_link = 'technician/dashboard.php';
elseif ($role_id == 3 || $role_id == 4) $dashboard_link = 'user/dashboard.php';

// Determine request detail link based on role
function getRequestLink($request_id, $role_id) {
    if ($role_id == 1) return 'admin/request-details.php?id=' . $request_id;
    elseif ($role_id == 2) return 'technician/task-details.php?id=' . $request_id;
    else return 'user/request-details.php?id=' . $request_id;
}

// Time ago helper
function timeAgoFull($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) {
        if ($diff->d == 1) return 'Yesterday';
        if ($diff->d < 7) return $diff->d . ' days ago';
        return floor($diff->d / 7) . ' week' . (floor($diff->d / 7) > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - FixPoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .notif-page-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .notif-page-item:hover {
            background: #f8fafc;
        }
        
        .notif-page-item.unread {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
        }
        
        .notif-page-item.unread:hover {
            background: #dbeafe;
        }
        
        .notif-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
        }
        
        .notif-page-item.unread .notif-icon {
            background: #dbeafe;
        }
        
        .notif-body {
            flex: 1;
            min-width: 0;
        }
        
        .notif-page-message {
            font-size: 0.95rem;
            color: #1e293b;
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }
        
        .notif-page-item.unread .notif-page-message {
            font-weight: 600;
        }
        
        .notif-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .notif-request-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .notif-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2563eb;
            flex-shrink: 0;
            margin-top: 0.5rem;
        }
        
        .notif-page-item:not(.unread) .notif-dot {
            background: transparent;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            color: #64748b;
            background: #f1f5f9;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            background: #e2e8f0;
        }
        
        .filter-tab.active {
            background: #2563eb;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">
                    <span class="logo-icon">🔧</span>
                    <span class="logo-text">FixPoint</span>
                    <span class="logo-subtitle">SEU</span>
                </div>
                <nav class="nav-links">
                    <a href="<?php echo $dashboard_link; ?>" class="nav-link">← Dashboard</a>
                    <span style="color: #64748b;">👤 <?php echo e($_SESSION['name']); ?></span>
                    <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Page Header -->
            <div class="dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1 class="welcome-text">🔔 Notifications</h1>
                        <p class="user-info">
                            <?php if ($unread_count > 0): ?>
                                You have <strong><?php echo $unread_count; ?></strong> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                All caught up! No unread notifications.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($unread_count > 0): ?>
                        <button onclick="markAllNotificationsRead()" class="btn btn-outline" id="markAllBtn">
                            ✓ Mark All as Read
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total</div>
                    <div class="stat-value"><?php echo count($notifications); ?></div>
                    <div class="stat-info">All notifications</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">🔔 Unread</div>
                    <div class="stat-value"><?php echo $unread_count; ?></div>
                    <div class="stat-info">Need attention</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Read</div>
                    <div class="stat-value"><?php echo count($notifications) - $unread_count; ?></div>
                    <div class="stat-info">Already seen</div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="requests-section">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="notifications.php?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                        All
                    </a>
                    <a href="notifications.php?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                        🔵 Unread (<?php echo $unread_count; ?>)
                    </a>
                    <a href="notifications.php?filter=read" class="filter-tab <?php echo $filter == 'read' ? 'active' : ''; ?>">
                        Read
                    </a>
                </div>
                
                <?php if (count($notifications) > 0): ?>
                    <div style="border: 1px solid #e2e8f0; border-radius: 0.75rem; overflow: hidden;">
                        <?php foreach ($notifications as $notif): ?>
                            <a href="<?php 
                                if ($notif['RequestID']) {
                                    echo getRequestLink($notif['RequestID'], $role_id);
                                } else {
                                    echo '#';
                                }
                            ?>" 
                               class="notif-page-item <?php echo $notif['IsRead'] ? '' : 'unread'; ?>"
                               onclick="markSingleRead(<?php echo $notif['NotificationID']; ?>)"
                               id="notif-<?php echo $notif['NotificationID']; ?>">
                                
                                <div class="notif-icon">
                                    <?php
                                    // Choose icon based on message content
                                    $msg = strtolower($notif['Message']);
                                    if (strpos($msg, 'completed') !== false) echo '✅';
                                    elseif (strpos($msg, 'assigned') !== false) echo '👨‍🔧';
                                    elseif (strpos($msg, 'submitted') !== false || strpos($msg, 'new maintenance') !== false) echo '📝';
                                    elseif (strpos($msg, 'in progress') !== false || strpos($msg, 'working') !== false) echo '🔧';
                                    elseif (strpos($msg, 'feedback') !== false) echo '⭐';
                                    elseif (strpos($msg, 'cancelled') !== false) echo '❌';
                                    elseif (strpos($msg, 'reviewed') !== false) echo '👁️';
                                    else echo '🔔';
                                    ?>
                                </div>
                                
                                <div class="notif-body">
                                    <div class="notif-page-message">
                                        <?php echo e($notif['Message']); ?>
                                    </div>
                                    <div class="notif-meta">
                                        <span><?php echo timeAgoFull($notif['CreatedAt']); ?></span>
                                        <span>•</span>
                                        <span><?php echo formatDate($notif['CreatedAt'], 'M d, Y - h:i A'); ?></span>
                                        <?php if ($notif['RequestTitle']): ?>
                                            <span class="notif-request-tag">
                                                Request #<?php echo $notif['RequestID']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="notif-dot"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">🔔</div>
                        <h3>No notifications</h3>
                        <?php if ($filter != 'all'): ?>
                            <p>No <?php echo $filter; ?> notifications found.</p>
                            <br>
                            <a href="notifications.php" class="btn btn-secondary">View All</a>
                        <?php else: ?>
                            <p>You don't have any notifications yet. They'll appear here when there are updates on your requests.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>

    <script>
    function markSingleRead(notifId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'config/mark-notification-read.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('notification_id=' + notifId);
    }

    function markAllNotificationsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'config/mark-notification-read.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Remove unread styling
                var items = document.querySelectorAll('.notif-page-item.unread');
                items.forEach(function(item) {
                    item.classList.remove('unread');
                });
                // Update button
                var btn = document.getElementById('markAllBtn');
                if (btn) {
                    btn.textContent = '✓ All Marked as Read';
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                }
            }
        };
        xhr.send('mark_all=1');
    }
    </script>
</body>
</html>