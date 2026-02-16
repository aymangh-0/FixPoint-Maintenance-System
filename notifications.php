<?php
/**
 * FixPoint - All Notifications Page
 * View all notifications for the logged-in user
 */

session_start();
require_once 'config/session-security.php';

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

// Build query based on filter
$where = "WHERE n.UserID = ?";
if ($filter === 'unread') {
    $where .= " AND n.IsRead = 0";
} elseif ($filter === 'read') {
    $where .= " AND n.IsRead = 1";
}

// Get notifications
$sql = "SELECT 
            n.NotificationID,
            n.Message,
            n.IsRead,
            n.CreatedAt,
            n.RequestID
        FROM notification n
        $where
        ORDER BY n.CreatedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count stats
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN IsRead = 0 THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN IsRead = 1 THEN 1 ELSE 0 END) as read_count
FROM notification WHERE UserID = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();

// Determine base path for links
$base_path = '';
if ($role_id == 1) {
    $details_path = 'admin/request-details.php?id=';
    $dashboard_path = 'admin/dashboard.php';
} elseif ($role_id == 2) {
    $details_path = 'technician/task-details.php?id=';
    $dashboard_path = 'technician/dashboard.php';
} else {
    $details_path = 'user/request-details.php?id=';
    $dashboard_path = 'user/dashboard.php';
}

// Helper function for time ago (if not already defined)
if (!function_exists('notifTimeAgo')) {
    function notifTimeAgo($datetime) {
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
}

// Get notification icon based on message content
function getNotifIcon($message) {
    if (strpos($message, 'assigned') !== false) return '👨‍🔧';
    if (strpos($message, 'completed') !== false) return '✅';
    if (strpos($message, 'progress') !== false || strpos($message, 'working') !== false) return '🔧';
    if (strpos($message, 'feedback') !== false || strpos($message, 'stars') !== false) return '⭐';
    if (strpos($message, 'submitted') !== false || strpos($message, 'new') !== false) return '📝';
    if (strpos($message, 'cancelled') !== false) return '❌';
    if (strpos($message, 'reviewed') !== false) return '👁️';
    if (strpos($message, 'Error') !== false || strpos($message, '⚠️') !== false) return '⚠️';
    return '🔔';
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
        .notif-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .notif-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .notif-filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: white;
            transition: all 0.2s;
        }
        .notif-filter-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        .notif-filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .notif-actions {
            display: flex;
            gap: 0.5rem;
        }
        .notif-action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-mark-all {
            background: #dbeafe;
            color: #2563eb;
        }
        .btn-mark-all:hover {
            background: #bfdbfe;
        }
        .btn-delete-all {
            background: #fee2e2;
            color: #ef4444;
        }
        .btn-delete-all:hover {
            background: #fecaca;
        }
        .notif-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 0.75rem;
            transition: all 0.3s;
        }
        .notif-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .notif-card.unread {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
        }
        .notif-card-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 50%;
        }
        .notif-card.unread .notif-card-icon {
            background: #dbeafe;
        }
        .notif-card-content {
            flex: 1;
            min-width: 0;
        }
        .notif-card-message {
            color: #1e293b;
            font-size: 0.925rem;
            line-height: 1.5;
            margin-bottom: 0.375rem;
        }
        .notif-card.unread .notif-card-message {
            font-weight: 600;
        }
        .notif-card-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .notif-card-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
            align-items: center;
        }
        .notif-card-btn {
            padding: 0.35rem 0.7rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }
        .notif-card-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        .notif-card-btn.delete-btn {
            color: #ef4444;
            border-color: #fecaca;
        }
        .notif-card-btn.delete-btn:hover {
            background: #fee2e2;
            border-color: #ef4444;
        }
        .notif-card-btn.view-btn {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .notif-card-btn.view-btn:hover {
            background: #1d4ed8;
        }
        .notif-empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .notif-empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .notif-page-header { flex-direction: column; align-items: flex-start; }
            .notif-card { flex-direction: column; }
            .notif-card-actions { width: 100%; justify-content: flex-end; }
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
                </div>
                <nav class="nav-links">
                    <a href="<?php echo $dashboard_path; ?>" class="nav-link">Dashboard</a>
                    <span style="color: #64748b;">👤 <?php echo e($_SESSION['name']); ?></span>
                    <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Page Header with Filters and Actions -->
            <div class="notif-page-header">
                <div>
                    <h1 class="welcome-text">🔔 Notifications</h1>
                    <p style="color: #64748b; margin-top: 0.25rem;">
                        <?php echo $counts['total']; ?> total · <?php echo $counts['unread']; ?> unread
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <!-- Filters -->
                    <div class="notif-filters">
                        <a href="notifications.php?filter=all" class="notif-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All (<?php echo $counts['total']; ?>)
                        </a>
                        <a href="notifications.php?filter=unread" class="notif-filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                            Unread (<?php echo $counts['unread']; ?>)
                        </a>
                        <a href="notifications.php?filter=read" class="notif-filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">
                            Read (<?php echo $counts['read_count']; ?>)
                        </a>
                    </div>
                    
                    <!-- Action Buttons -->
                    <?php if ($counts['total'] > 0): ?>
                        <div class="notif-actions">
                            <?php if ($counts['unread'] > 0): ?>
                                <button class="notif-action-btn btn-mark-all" onclick="pageMarkAllRead()">
                                    ✓ Mark all read
                                </button>
                            <?php endif; ?>
                            <button class="notif-action-btn btn-delete-all" onclick="pageDeleteAll()">
                                🗑️ Delete all
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications List -->
            <div id="notifPageList">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notif-card <?php echo $notif['IsRead'] ? '' : 'unread'; ?>" id="page-notif-<?php echo $notif['NotificationID']; ?>">
                            <div class="notif-card-icon">
                                <?php echo getNotifIcon($notif['Message']); ?>
                            </div>
                            <div class="notif-card-content">
                                <div class="notif-card-message"><?php echo e($notif['Message']); ?></div>
                                <div class="notif-card-meta">
                                    <span>🕐 <?php echo notifTimeAgo($notif['CreatedAt']); ?></span>
                                    <span><?php echo formatDate($notif['CreatedAt']); ?></span>
                                    <?php if (!$notif['IsRead']): ?>
                                        <span style="color: #2563eb; font-weight: 600;">● New</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notif-card-actions">
                                <?php if ($notif['RequestID']): ?>
                                    <a href="<?php echo $details_path . $notif['RequestID']; ?>" class="notif-card-btn view-btn">
                                        View →
                                    </a>
                                <?php endif; ?>
                                <button class="notif-card-btn delete-btn" onclick="pageDeleteOne(<?php echo $notif['NotificationID']; ?>)">
                                    ✕ Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notif-empty-state">
                        <div class="notif-empty-state-icon">🔔</div>
                        <h3>No notifications</h3>
                        <p>
                            <?php if ($filter === 'unread'): ?>
                                You've read all your notifications!
                            <?php elseif ($filter === 'read'): ?>
                                No read notifications yet.
                            <?php else: ?>
                                You don't have any notifications yet.
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo $dashboard_path; ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                            ← Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
    function pageDeleteOne(notifId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'config/mark-notification-read.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var card = document.getElementById('page-notif-' + notifId);
                if (card) {
                    card.style.transition = 'opacity 0.3s, margin 0.3s, padding 0.3s, height 0.3s';
                    card.style.opacity = '0';
                    card.style.marginBottom = '0';
                    card.style.padding = '0';
                    card.style.height = '0';
                    card.style.overflow = 'hidden';
                    setTimeout(function() { 
                        card.remove(); 
                        checkPageEmpty();
                    }, 300);
                }
            }
        };
        xhr.send('delete_id=' + notifId);
    }

    function pageDeleteAll() {
        if (!confirm('Are you sure you want to delete all notifications?')) return;
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'config/mark-notification-read.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('notifPageList').innerHTML = 
                    '<div class="notif-empty-state">' +
                    '<div class="notif-empty-state-icon">🔔</div>' +
                    '<h3>All clear!</h3>' +
                    '<p>All notifications have been deleted.</p>' +
                    '<a href="<?php echo $dashboard_path; ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>' +
                    '</div>';
                
                var actions = document.querySelector('.notif-actions');
                if (actions) actions.remove();
            }
        };
        xhr.send('delete_all=1');
    }

    function pageMarkAllRead() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'config/mark-notification-read.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var unreadCards = document.querySelectorAll('.notif-card.unread');
                unreadCards.forEach(function(card) {
                    card.classList.remove('unread');
                    var newBadge = card.querySelector('.notif-card-meta span[style*="color: #2563eb"]');
                    if (newBadge) newBadge.remove();
                });
                
                var markBtn = document.querySelector('.btn-mark-all');
                if (markBtn) markBtn.remove();
            }
        };
        xhr.send('mark_all=1');
    }

    function checkPageEmpty() {
        var list = document.getElementById('notifPageList');
        var cards = list.querySelectorAll('.notif-card');
        if (cards.length === 0) {
            list.innerHTML = 
                '<div class="notif-empty-state">' +
                '<div class="notif-empty-state-icon">🔔</div>' +
                '<h3>No notifications</h3>' +
                '<p>All notifications have been removed.</p>' +
                '<a href="<?php echo $dashboard_path; ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>' +
                '</div>';
        }
    }
    </script>

</body>
</html>