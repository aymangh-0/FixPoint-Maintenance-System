<?php
/**
 * FixPoint - Audit Log Viewer (Admin)
 * View all audit logs and login logs
 */

session_start();
require_once '../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';

// Current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'audit';

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'all';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

// ============================================
// AUDIT LOG TAB
// ============================================
$audit_logs = [];
if ($tab === 'audit') {
    $sql = "SELECT 
                al.AuditID,
                al.Action,
                al.TableName,
                al.RecordID,
                al.OldValue,
                al.NewValue,
                al.IPAddress,
                al.PerformedAt,
                u.Name as UserName,
                u.Email as UserEmail
            FROM auditlog al
            LEFT JOIN user u ON al.UserID = u.UserID
            WHERE 1=1";
    
    if (!empty($search)) {
        $sql .= " AND (al.Action LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR al.TableName LIKE '%" . $conn->real_escape_string($search) . "%'
                  OR al.NewValue LIKE '%" . $conn->real_escape_string($search) . "%'
                  OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    if ($action_filter !== 'all') {
        $sql .= " AND al.Action = '" . $conn->real_escape_string($action_filter) . "'";
    }
    
    if (!empty($date_from)) {
        $sql .= " AND al.PerformedAt >= '" . $conn->real_escape_string($date_from) . " 00:00:00'";
    }
    if (!empty($date_to)) {
        $sql .= " AND al.PerformedAt <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
    }
    
    $sql .= " ORDER BY al.PerformedAt DESC LIMIT 200";
    $audit_logs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    
    // Get unique actions for filter
    $actions_sql = "SELECT DISTINCT Action FROM auditlog ORDER BY Action";
    $actions = $conn->query($actions_sql)->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// LOGIN LOG TAB
// ============================================
$login_logs = [];
if ($tab === 'login') {
    $sql = "SELECT 
                ll.LogID,
                ll.Email,
                ll.Status,
                ll.FailReason,
                ll.IPAddress,
                ll.UserAgent,
                ll.AttemptedAt,
                u.Name as UserName
            FROM loginlog ll
            LEFT JOIN user u ON ll.UserID = u.UserID
            WHERE 1=1";
    
    if (!empty($search)) {
        $sql .= " AND (ll.Email LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR ll.IPAddress LIKE '%" . $conn->real_escape_string($search) . "%'
                  OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    if ($action_filter === 'success') {
        $sql .= " AND ll.Status = 'Success'";
    } elseif ($action_filter === 'failed') {
        $sql .= " AND ll.Status = 'Failed'";
    }
    
    if (!empty($date_from)) {
        $sql .= " AND ll.AttemptedAt >= '" . $conn->real_escape_string($date_from) . " 00:00:00'";
    }
    if (!empty($date_to)) {
        $sql .= " AND ll.AttemptedAt <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
    }
    
    $sql .= " ORDER BY ll.AttemptedAt DESC LIMIT 200";
    $login_logs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// Stats
// ============================================
$audit_count = $conn->query("SELECT COUNT(*) as c FROM auditlog")->fetch_assoc()['c'];
$login_count = $conn->query("SELECT COUNT(*) as c FROM loginlog")->fetch_assoc()['c'];
$failed_logins = $conn->query("SELECT COUNT(*) as c FROM loginlog WHERE Status = 'Failed'")->fetch_assoc()['c'];
$today_actions = $conn->query("SELECT COUNT(*) as c FROM auditlog WHERE DATE(PerformedAt) = CURDATE()")->fetch_assoc()['c'];

// Action icon helper
function getActionIcon($action) {
    $icons = [
        'SUBMIT_REQUEST' => '📝',
        'ASSIGN_TECHNICIAN' => '👨‍🔧',
        'AUTO_ASSIGN' => '⚡',
        'UPDATE_STATUS' => '🔄',
        'SUBMIT_FEEDBACK' => '⭐',
        'PASSWORD_RESET' => '🔑',
        'ADD_LOCATION' => '📍',
        'EDIT_LOCATION' => '✏️',
        'DELETE_LOCATION' => '🗑️',
    ];
    return $icons[$action] ?? '📋';
}

function getActionColor($action) {
    if (strpos($action, 'DELETE') !== false) return '#fee2e2';
    if (strpos($action, 'ADD') !== false || strpos($action, 'SUBMIT') !== false) return '#d1fae5';
    if (strpos($action, 'ASSIGN') !== false) return '#dbeafe';
    if (strpos($action, 'UPDATE') !== false || strpos($action, 'EDIT') !== false) return '#fef3c7';
    if (strpos($action, 'PASSWORD') !== false) return '#e0e7ff';
    return '#f1f5f9';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .tab-nav {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            font-weight: 600;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .tab-btn:hover { color: #2563eb; }
        .tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .log-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: background 0.2s;
        }
        .log-card:hover { background: #f8fafc; }
        .log-icon {
            font-size: 1.25rem;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .log-content { flex: 1; min-width: 0; }
        .log-action {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .log-details {
            color: #64748b;
            font-size: 0.825rem;
            line-height: 1.5;
        }
        .log-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.35rem;
            flex-wrap: wrap;
        }
        .log-tag {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .login-success { background: #d1fae5; color: #065f46; }
        .login-failed { background: #fee2e2; color: #991b1b; }
        .filter-section {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 1.5rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
        }
        .filter-group input, .filter-group select {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.85rem;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #2563eb;
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
                    <span class="logo-subtitle">Admin</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="all-requests.php" class="nav-link">All Requests</a>
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="locations.php" class="nav-link">Locations</a>
                    <a href="backup.php" class="nav-link">Backup</a>
                    <?php include '../includes/notification-bell.php'; ?>
                    <span style="color: #64748b;">👤 <?php echo e($_SESSION['name']); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Page Header -->
            <div class="dashboard-header">
                <h1 class="welcome-text">📋 Audit Logs</h1>
                <p class="user-info">Monitor all system activity, login attempts, and administrative actions</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📋 Total Audit Entries</div>
                    <div class="stat-value"><?php echo $audit_count; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔐 Login Attempts</div>
                    <div class="stat-value"><?php echo $login_count; ?></div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-label">⚠️ Failed Logins</div>
                    <div class="stat-value"><?php echo $failed_logins; ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">📅 Today's Actions</div>
                    <div class="stat-value"><?php echo $today_actions; ?></div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tab-nav">
                <a href="audit-logs.php?tab=audit" class="tab-btn <?php echo $tab === 'audit' ? 'active' : ''; ?>">
                    📋 Audit Log (<?php echo $audit_count; ?>)
                </a>
                <a href="audit-logs.php?tab=login" class="tab-btn <?php echo $tab === 'login' ? 'active' : ''; ?>">
                    🔐 Login Log (<?php echo $login_count; ?>)
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
                <div class="filter-section">
                    <div class="filter-group" style="flex: 2; min-width: 200px;">
                        <label>🔍 Search</label>
                        <input type="text" name="search" value="<?php echo e($search); ?>" 
                               placeholder="<?php echo $tab === 'audit' ? 'Search by action, table, user...' : 'Search by email, IP, name...'; ?>">
                    </div>
                    
                    <div class="filter-group" style="min-width: 150px;">
                        <label>📂 Filter</label>
                        <select name="action">
                            <?php if ($tab === 'audit'): ?>
                                <option value="all">All Actions</option>
                                <?php foreach ($actions ?? [] as $act): ?>
                                    <option value="<?php echo e($act['Action']); ?>" <?php echo $action_filter === $act['Action'] ? 'selected' : ''; ?>>
                                        <?php echo e($act['Action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="success" <?php echo $action_filter === 'success' ? 'selected' : ''; ?>>✅ Success</option>
                                <option value="failed" <?php echo $action_filter === 'failed' ? 'selected' : ''; ?>>❌ Failed</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 From</label>
                        <input type="date" name="from" value="<?php echo e($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 To</label>
                        <input type="date" name="to" value="<?php echo e($date_to); ?>">
                    </div>
                    
                    <div class="filter-group" style="justify-content: flex-end;">
                        <label>&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">🔍 Filter</button>
                            <a href="audit-logs.php?tab=<?php echo e($tab); ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">Reset</a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ============================================ -->
            <!-- AUDIT LOG TAB -->
            <!-- ============================================ -->
            <?php if ($tab === 'audit'): ?>
                <?php if (count($audit_logs) > 0): ?>
                    <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 1rem;">
                        Showing <?php echo count($audit_logs); ?> entries (max 200)
                    </div>
                    <?php foreach ($audit_logs as $log): ?>
                        <div class="log-card">
                            <div class="log-icon" style="background: <?php echo getActionColor($log['Action']); ?>;">
                                <?php echo getActionIcon($log['Action']); ?>
                            </div>
                            <div class="log-content">
                                <div class="log-action">
                                    <?php echo e($log['Action']); ?>
                                    <?php if ($log['TableName']): ?>
                                        <span style="color: #64748b; font-weight: 400;">on</span> 
                                        <span style="color: #2563eb;"><?php echo e($log['TableName']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($log['RecordID']): ?>
                                        <span style="color: #64748b; font-weight: 400;">#<?php echo $log['RecordID']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="log-details">
                                    <?php if ($log['OldValue']): ?>
                                        <span style="color: #ef4444;">Old:</span> <?php echo e($log['OldValue']); ?> → 
                                    <?php endif; ?>
                                    <?php if ($log['NewValue']): ?>
                                        <span style="color: #10b981;">New:</span> <?php echo e($log['NewValue']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="log-meta">
                                    <span>👤 <?php echo $log['UserName'] ? e($log['UserName']) : 'System'; ?></span>
                                    <span>🌐 <?php echo e($log['IPAddress']); ?></span>
                                    <span>🕐 <?php echo formatDate($log['PerformedAt']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">📋</div>
                        <h3>No audit logs found</h3>
                        <p>No actions match your current filters.</p>
                    </div>
                <?php endif; ?>

            <!-- ============================================ -->
            <!-- LOGIN LOG TAB -->
            <!-- ============================================ -->
            <?php else: ?>
                <?php if (count($login_logs) > 0): ?>
                    <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 1rem;">
                        Showing <?php echo count($login_logs); ?> entries (max 200)
                    </div>
                    <?php foreach ($login_logs as $log): ?>
                        <div class="log-card">
                            <div class="log-icon" style="background: <?php echo $log['Status'] === 'Success' ? '#d1fae5' : '#fee2e2'; ?>;">
                                <?php echo $log['Status'] === 'Success' ? '✅' : '❌'; ?>
                            </div>
                            <div class="log-content">
                                <div class="log-action">
                                    <?php echo $log['UserName'] ? e($log['UserName']) : 'Unknown User'; ?>
                                    <span class="log-tag <?php echo $log['Status'] === 'Success' ? 'login-success' : 'login-failed'; ?>">
                                        <?php echo e($log['Status']); ?>
                                    </span>
                                </div>
                                <div class="log-details">
                                    📧 <?php echo e($log['Email']); ?>
                                    <?php if ($log['FailReason']): ?>
                                        — <span style="color: #ef4444;"><?php echo e($log['FailReason']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="log-meta">
                                    <span>🌐 <?php echo e($log['IPAddress']); ?></span>
                                    <span>🕐 <?php echo formatDate($log['AttemptedAt']); ?></span>
                                    <?php if ($log['UserAgent']): ?>
                                        <span title="<?php echo e($log['UserAgent']); ?>">
                                            💻 <?php echo e(substr($log['UserAgent'], 0, 50)); ?>...
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">🔐</div>
                        <h3>No login logs found</h3>
                        <p>No login attempts match your current filters.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>