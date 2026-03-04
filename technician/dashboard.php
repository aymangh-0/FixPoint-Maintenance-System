<?php
/**
 * FixPoint - Technician Dashboard
 * Dashboard for technicians to view and manage assigned maintenance requests
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
$tech_name = $_SESSION['name'];
$delete_msg = '';

// Handle bulk delete of completed tasks (hides from technician's view)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_completed') {
    $ids = $_POST['selected_ids'] ?? [];
    if (!empty($ids)) {
        $count = 0;
        foreach ($ids as $rid) {
            $rid = (int)$rid;
            // Only allow removing completed/cancelled requests from technician's view
            $del = $conn->prepare("DELETE a FROM assignment a 
                JOIN maintenancerequest mr ON a.RequestID = mr.RequestID 
                WHERE a.RequestID = ? AND a.TechnicianID = ? AND mr.StatusID IN (5, 6)");
            $del->bind_param("ii", $rid, $tech_id);
            $del->execute();
            if ($del->affected_rows > 0) $count++;
        }
        if ($count > 0) {
            $delete_msg = "✅ $count completed task(s) removed from your list.";
        }
    }
}

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


$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - FixPoint</title>
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
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
                    <h2 class="section-title" style="margin-bottom:0;">✅ Recently Completed (<?php echo count($completed_requests); ?>)</h2>
                    <button type="submit" form="deleteForm" onclick="return confirmDelete()" class="btn" id="deleteBtn" style="background:#ef4444; color:white; padding:0.5rem 1rem; font-size:0.85rem; border:none; border-radius:0.5rem; cursor:pointer; display:none;">
                        🗑️ Delete Selected (<span id="deleteCount">0</span>)
                    </button>
                </div>

                <?php if ($delete_msg): ?>
                    <div style="background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; padding:0.75rem 1rem; border-radius:0.5rem; margin-bottom:1rem; font-weight:500;">
                        <?php echo $delete_msg; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_completed">
                <div style="overflow-x: auto;">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" id="selectAll" title="Select all" style="width:16px; height:16px; cursor:pointer;">
                                </th>
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
                                    <td>
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $req['RequestID']; ?>" class="row-check" style="width:16px; height:16px; cursor:pointer;">
                                    </td>
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
                </form>
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

        // Select all / Delete logic for completed tasks
        const selectAll = document.getElementById('selectAll');
        const rowChecks = document.querySelectorAll('.row-check');
        const deleteBtn = document.getElementById('deleteBtn');
        const deleteCount = document.getElementById('deleteCount');

        function updateDeleteBtn() {
            const checked = document.querySelectorAll('.row-check:checked').length;
            if (deleteBtn) {
                deleteBtn.style.display = checked > 0 ? 'inline-block' : 'none';
                deleteCount.textContent = checked;
            }
            if (selectAll) {
                selectAll.checked = rowChecks.length > 0 && checked === rowChecks.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowChecks.forEach(cb => cb.checked = this.checked);
                updateDeleteBtn();
            });
        }

        rowChecks.forEach(cb => cb.addEventListener('change', updateDeleteBtn));

        function confirmDelete() {
            const count = document.querySelectorAll('.row-check:checked').length;
            if (count === 0) return false;
            return confirm('Remove ' + count + ' completed task(s) from your list?');
        }

        // Auto-hide success message
        setTimeout(() => {
            document.querySelectorAll('.alert-success, [style*="d1fae5"]').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>