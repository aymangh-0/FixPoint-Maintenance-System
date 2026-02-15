<?php
/**
 * FixPoint - Notification Bell Component
 * Include this file in any page header to show the notification bell icon
 * 
 * Requirements:
 * - Session must be started
 * - Database connection ($conn) must be available
 * - User must be logged in ($_SESSION['user_id'])
 */

// Get unread notification count for current user
$_notif_count = 0;
$_recent_notifications = [];

if (isset($_SESSION['user_id'])) {
    // Count unread notifications
    $notif_count_sql = "SELECT COUNT(*) as unread FROM notification WHERE UserID = ? AND IsRead = 0";
    $notif_count_stmt = $conn->prepare($notif_count_sql);
    $notif_count_stmt->bind_param("i", $_SESSION['user_id']);
    $notif_count_stmt->execute();
    $_notif_count = $notif_count_stmt->get_result()->fetch_assoc()['unread'];
    $notif_count_stmt->close();
    
    // Get latest 5 notifications for dropdown preview
    $notif_sql = "SELECT 
                    n.NotificationID,
                    n.Message,
                    n.IsRead,
                    n.CreatedAt,
                    n.RequestID
                  FROM notification n
                  WHERE n.UserID = ?
                  ORDER BY n.CreatedAt DESC
                  LIMIT 5";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("i", $_SESSION['user_id']);
    $notif_stmt->execute();
    $_recent_notifications = $notif_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $notif_stmt->close();
}

// Determine the correct path prefix based on current file location
$_notif_base_path = '';
$_current_dir = basename(dirname($_SERVER['PHP_SELF']));
if (in_array($_current_dir, ['admin', 'user', 'technician', 'auth', 'config'])) {
    $_notif_base_path = '../';
}
?>

<!-- Notification Bell Styles -->
<style>
.notif-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.notif-bell {
    position: relative;
    cursor: pointer;
    font-size: 1.4rem;
    padding: 0.25rem 0.5rem;
    text-decoration: none;
    color: #64748b;
    transition: color 0.3s;
    background: none;
    border: none;
    line-height: 1;
}

.notif-bell:hover {
    color: #2563eb;
}

.notif-badge {
    position: absolute;
    top: -4px;
    right: -2px;
    background: #ef4444;
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    line-height: 1;
    border: 2px solid white;
    animation: notif-pulse 2s infinite;
}

@keyframes notif-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notif-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    width: 360px;
    max-height: 440px;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid #e2e8f0;
    z-index: 9999;
    overflow: hidden;
    margin-top: 0.5rem;
}

.notif-dropdown.show {
    display: block;
}

.notif-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.notif-dropdown-title {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.95rem;
}

.notif-mark-all {
    font-size: 0.8rem;
    color: #2563eb;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    background: none;
    border: none;
    padding: 0;
}

.notif-mark-all:hover {
    text-decoration: underline;
}

.notif-list {
    max-height: 320px;
    overflow-y: auto;
}

.notif-item {
    display: block;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    cursor: pointer;
}

.notif-item:hover {
    background: #f8fafc;
}

.notif-item.unread {
    background: #eff6ff;
    border-left: 3px solid #2563eb;
}

.notif-item.unread:hover {
    background: #dbeafe;
}

.notif-message {
    font-size: 0.875rem;
    color: #1e293b;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.notif-item.unread .notif-message {
    font-weight: 600;
}

.notif-time {
    font-size: 0.75rem;
    color: #94a3b8;
}

.notif-empty {
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
}

.notif-empty-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.notif-dropdown-footer {
    padding: 0.75rem 1.25rem;
    text-align: center;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.notif-dropdown-footer a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.notif-dropdown-footer a:hover {
    text-decoration: underline;
}
</style>

<!-- Notification Bell HTML -->
<div class="notif-wrapper" id="notifWrapper">
    <button class="notif-bell" id="notifBell" onclick="toggleNotifDropdown(event)" title="Notifications">
        🔔
        <?php if ($_notif_count > 0): ?>
            <span class="notif-badge" id="notifBadge"><?php echo $_notif_count > 99 ? '99+' : $_notif_count; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
            <span class="notif-dropdown-title">Notifications</span>
            <?php if ($_notif_count > 0): ?>
                <button class="notif-mark-all" onclick="markAllRead(event)">Mark all as read</button>
            <?php endif; ?>
        </div>
        
        <div class="notif-list">
            <?php if (count($_recent_notifications) > 0): ?>
                <?php foreach ($_recent_notifications as $notif): ?>
                    <a href="<?php 
                        if ($notif['RequestID']) {
                            // Link to appropriate details page based on role
                            if ($_SESSION['role_id'] == 1) {
                                echo $_notif_base_path . 'admin/request-details.php?id=' . $notif['RequestID'];
                            } elseif ($_SESSION['role_id'] == 2) {
                                echo $_notif_base_path . 'technician/task-details.php?id=' . $notif['RequestID'];
                            } else {
                                echo $_notif_base_path . 'user/request-details.php?id=' . $notif['RequestID'];
                            }
                        } else {
                            echo '#';
                        }
                    ?>" 
                       class="notif-item <?php echo $notif['IsRead'] ? '' : 'unread'; ?>"
                       onclick="markAsRead(<?php echo $notif['NotificationID']; ?>)">
                        <div class="notif-message"><?php echo htmlspecialchars($notif['Message']); ?></div>
                        <div class="notif-time"><?php echo timeAgo($notif['CreatedAt']); ?></div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notif-empty">
                    <div class="notif-empty-icon">🔔</div>
                    <div>No notifications yet</div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($_recent_notifications) > 0): ?>
            <div class="notif-dropdown-footer">
                <a href="<?php echo $_notif_base_path; ?>notifications.php">View All Notifications →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Bell JavaScript -->
<script>
function toggleNotifDropdown(e) {
    e.stopPropagation();
    var dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var wrapper = document.getElementById('notifWrapper');
    var dropdown = document.getElementById('notifDropdown');
    if (wrapper && !wrapper.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

function markAsRead(notifId) {
    // Send AJAX request to mark notification as read
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo $_notif_base_path; ?>config/mark-notification-read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('notification_id=' + notifId);
}

function markAllRead(e) {
    e.stopPropagation();
    e.preventDefault();
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo $_notif_base_path; ?>config/mark-notification-read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Remove badge
            var badge = document.getElementById('notifBadge');
            if (badge) badge.remove();
            
            // Remove unread styling from all items
            var items = document.querySelectorAll('.notif-item.unread');
            items.forEach(function(item) {
                item.classList.remove('unread');
            });
            
            // Hide "Mark all as read" button
            var markAllBtn = document.querySelector('.notif-mark-all');
            if (markAllBtn) markAllBtn.style.display = 'none';
        }
    };
    xhr.send('mark_all=1');
}
</script>

<?php
/**
 * Helper: Time ago function for notification timestamps
 */
function timeAgo($datetime) {
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