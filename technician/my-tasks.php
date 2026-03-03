<?php
/**
 * FixPoint - My Tasks (Technician View)
 * View all assigned tasks with filtering by status
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$tech_id = $_SESSION['user_id'];

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query
$where = "WHERE a.TechnicianID = ?";
if ($filter === 'pending') {
    $where .= " AND mr.StatusID = 3"; // Assigned
} elseif ($filter === 'in_progress') {
    $where .= " AND mr.StatusID = 4"; // In Progress
} elseif ($filter === 'completed') {
    $where .= " AND mr.StatusID = 5"; // Completed
}

// Get all tasks
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.CompletedAt,
            s.StatusName,
            s.StatusID,
            p.PriorityLevel,
            c.CategoryName,
            CONCAT(l.BuildingName, ' - ', l.FloorNumber, ' - ', l.RoomNumber) as Location,
            u.Name as RequesterName,
            a.AssignedAt,
            a.StartedAt,
            a.CompletedAt as TaskCompletedAt
        FROM assignment a
        JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
        JOIN status s ON mr.StatusID = s.StatusID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN user u ON mr.UserID = u.UserID
        $where
        ORDER BY 
            CASE mr.StatusID 
                WHEN 4 THEN 1  
                WHEN 3 THEN 2  
                WHEN 5 THEN 3  
                ELSE 4 
            END,
            p.PriorityID DESC,
            a.AssignedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN mr.StatusID = 3 THEN 1 ELSE 0 END) as assigned,
    SUM(CASE WHEN mr.StatusID = 4 THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN mr.StatusID = 5 THEN 1 ELSE 0 END) as completed
FROM assignment a
JOIN maintenancerequest mr ON a.RequestID = mr.RequestID
WHERE a.TechnicianID = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $tech_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$current_page = 'my-tasks';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .task-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .task-filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: white;
            transition: all 0.2s;
        }
        .task-filter-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        .task-filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .task-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }
        .task-card.urgent {
            border-left: 4px solid #ef4444;
        }
        .task-card.in-progress {
            border-left: 4px solid #f59e0b;
        }
        .task-card.completed {
            border-left: 4px solid #10b981;
        }
        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            gap: 1rem;
        }
        .task-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }
        .task-badges {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.825rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        .task-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .task-description {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 0.75rem;
            max-height: 3em;
            overflow: hidden;
        }
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid #f1f5f9;
        }
        .task-dates {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .btn-view-task {
            padding: 0.4rem 1rem;
            background: #2563eb;
            color: white;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-view-task:hover {
            background: #1d4ed8;
        }
        .empty-tasks {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .empty-tasks-icon { font-size: 3rem; margin-bottom: 1rem; }

        @media (max-width: 768px) {
            .task-card-header { flex-direction: column; }
            .task-meta { gap: 0.75rem; }
            .task-footer { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
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
                    <span class="sidebar-logo-sub">SEU</span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role">Technician</span>
            </div>
            <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">My Work</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🏠</span><span>Dashboard</span>
            </a>
            <a href="my-tasks.php" class="sidebar-link <?php echo $current_page === 'my-tasks' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🔧</span><span>My Tasks</span>
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
                <h1 class="welcome-text">📋 My Tasks</h1>
                <p class="user-info">Manage all your assigned maintenance tasks</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📋 Total Tasks</div>
                    <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Waiting</div>
                    <div class="stat-value"><?php echo $stats['assigned'] ?? 0; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['in_progress'] ?? 0; ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="task-filters">
                <a href="my-tasks.php?filter=all" class="task-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All (<?php echo $stats['total'] ?? 0; ?>)
                </a>
                <a href="my-tasks.php?filter=pending" class="task-filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    ⏳ Waiting (<?php echo $stats['assigned'] ?? 0; ?>)
                </a>
                <a href="my-tasks.php?filter=in_progress" class="task-filter-btn <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">
                    🔧 In Progress (<?php echo $stats['in_progress'] ?? 0; ?>)
                </a>
                <a href="my-tasks.php?filter=completed" class="task-filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    ✅ Completed (<?php echo $stats['completed'] ?? 0; ?>)
                </a>
            </div>

            <!-- Tasks List -->
            <?php if (count($tasks) > 0): ?>
                <?php foreach ($tasks as $task): ?>
                    <?php 
                        $card_class = '';
                        if ($task['StatusID'] == 4) $card_class = 'in-progress';
                        elseif ($task['StatusID'] == 5) $card_class = 'completed';
                        elseif ($task['PriorityLevel'] == 'Critical' || $task['PriorityLevel'] == 'High') $card_class = 'urgent';
                    ?>
                    <div class="task-card <?php echo $card_class; ?>">
                        <div class="task-card-header">
                            <div class="task-title">
                                #<?php echo $task['RequestID']; ?> — <?php echo e($task['Title']); ?>
                            </div>
                            <div class="task-badges">
                                <span class="status-badge <?php echo getStatusBadgeClass($task['StatusName']); ?>">
                                    <?php echo e($task['StatusName']); ?>
                                </span>
                                <span class="status-badge <?php echo getPriorityBadgeClass($task['PriorityLevel']); ?>">
                                    <?php echo e($task['PriorityLevel']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="task-description">
                            <?php echo e($task['Description']); ?>
                        </div>

                        <div class="task-meta">
                            <span class="task-meta-item">📁 <?php echo e($task['CategoryName']); ?></span>
                            <span class="task-meta-item">📍 <?php echo e($task['Location']); ?></span>
                            <span class="task-meta-item">👤 <?php echo e($task['RequesterName']); ?></span>
                        </div>

                        <div class="task-footer">
                            <div class="task-dates">
                                📅 Assigned: <?php echo formatDate($task['AssignedAt'], 'M d, Y'); ?>
                                <?php if ($task['StartedAt']): ?>
                                    · Started: <?php echo formatDate($task['StartedAt'], 'M d, Y'); ?>
                                <?php endif; ?>
                                <?php if ($task['TaskCompletedAt']): ?>
                                    · Done: <?php echo formatDate($task['TaskCompletedAt'], 'M d, Y'); ?>
                                <?php endif; ?>
                            </div>
                            <a href="task-details.php?id=<?php echo $task['RequestID']; ?>" class="btn-view-task">
                                <?php if ($task['StatusID'] == 3): ?>
                                    ▶ Start Task
                                <?php elseif ($task['StatusID'] == 4): ?>
                                    🔧 Continue
                                <?php else: ?>
                                    👁️ View Details
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-tasks">
                    <div class="empty-tasks-icon">📭</div>
                    <h3>No tasks found</h3>
                    <p>
                        <?php if ($filter !== 'all'): ?>
                            No tasks with this filter. <a href="my-tasks.php" style="color: #2563eb;">Show all tasks</a>
                        <?php else: ?>
                            You don't have any assigned tasks yet. Check back later!
                        <?php endif; ?>
                    </p>
                    <a href="dashboard.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
                </div>
            <?php endif; ?>

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