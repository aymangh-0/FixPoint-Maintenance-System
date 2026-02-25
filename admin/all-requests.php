<?php
/**
 * FixPoint - All Requests (Admin)
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

$admin_id = $_SESSION['user_id'];

$status_filter   = isset($_GET['status'])   ? $_GET['status']         : 'all';
$priority_filter = isset($_GET['priority']) ? $_GET['priority']       : 'all';
$category_filter = isset($_GET['category']) ? $_GET['category']       : 'all';
$search          = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$sort_by         = isset($_GET['sort'])     ? $_GET['sort']           : 'newest';

$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            u.Name as RequesterName,
            u.Email as RequesterEmail,
            r.RoleName as RequesterRole,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            p.PriorityID,
            s.StatusName,
            s.StatusID,
            (SELECT Name FROM user WHERE UserID = (SELECT TechnicianID FROM assignment WHERE RequestID = mr.RequestID ORDER BY AssignedAt DESC LIMIT 1)) as TechnicianName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN role r ON u.RoleID = r.RoleID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE 1=1";

if ($status_filter != 'all') {
    $sql .= " AND s.StatusName = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($priority_filter != 'all') {
    $sql .= " AND p.PriorityLevel = '" . $conn->real_escape_string($priority_filter) . "'";
}
if ($category_filter != 'all') {
    $sql .= " AND c.CategoryName = '" . $conn->real_escape_string($category_filter) . "'";
}
if (!empty($search)) {
    $sql .= " AND (mr.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
            OR mr.Description LIKE '%" . $conn->real_escape_string($search) . "%'
            OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%')";
}

switch ($sort_by) {
    case 'oldest':   $sql .= " ORDER BY mr.SubmittedAt ASC"; break;
    case 'priority': $sql .= " ORDER BY p.PriorityID DESC, mr.SubmittedAt DESC"; break;
    case 'updated':  $sql .= " ORDER BY mr.UpdatedAt DESC"; break;
    default:         $sql .= " ORDER BY mr.SubmittedAt DESC"; break;
}

$result   = $conn->query($sql);
$requests = $result->fetch_all(MYSQLI_ASSOC);

$stats = ['total' => count($requests), 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
foreach ($requests as $req) {
    if ($req['StatusName'] == 'Pending') $stats['pending']++;
    if (in_array($req['StatusName'], ['In Progress', 'Assigned'])) $stats['in_progress']++;
    if ($req['StatusName'] == 'Completed') $stats['completed']++;
}

$categories = getAllCategories($conn);
$priorities = getAllPriorities($conn);

function getPriorityBorderClass($priority) {
    $classes = [
        'Critical' => 'priority-critical-border',
        'High'     => 'priority-high-border',
        'Medium'   => 'priority-medium-border',
        'Low'      => 'priority-low-border',
    ];
    return $classes[$priority] ?? '';
}

$current_page = 'all-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Requests - Admin</title>
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
                    <span class="sidebar-logo-sub">SEU Admin</span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
            <?php include '../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📊</span><span>Dashboard</span>
            </a>
            <a href="all-requests.php" class="sidebar-link <?php echo $current_page === 'all-requests' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📋</span><span>All Requests</span>
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
            <a href="all-feedback.php" class="sidebar-link <?php echo $current_page === 'all-feedback' ? 'active' : ''; ?>">
                <span class="sidebar-icon">⭐</span><span>Feedback</span>
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
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="main-content">
        <div class="topbar">
            <button class="hamburger" id="hamburgerBtn">☰</button>
            <div class="topbar-logo"><span>🔧</span><span>FixPoint</span></div>
            <div class="topbar-notif"><?php include '../includes/notification-bell.php'; ?></div>
        </div>


    <div class="dashboard">
        <div class="dashboard-container">

            <!-- Page Header -->
            <div class="dashboard-header">
                <h1 class="welcome-text">All Maintenance Requests 📋</h1>
                <p class="user-info">View, filter, and manage all system requests</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Showing</div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-info">Filtered results</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">🔍 Filters & Search</h2>
                <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1e293b;">Status</label>
                        <select name="status" class="form-input" style="width:100%;">
                            <option value="all"        <?php echo ($status_filter=='all')        ?'selected':''; ?>>All Status</option>
                            <option value="Pending"    <?php echo ($status_filter=='Pending')    ?'selected':''; ?>>Pending</option>
                            <option value="Reviewed"   <?php echo ($status_filter=='Reviewed')   ?'selected':''; ?>>Reviewed</option>
                            <option value="Assigned"   <?php echo ($status_filter=='Assigned')   ?'selected':''; ?>>Assigned</option>
                            <option value="In Progress"<?php echo ($status_filter=='In Progress')?'selected':''; ?>>In Progress</option>
                            <option value="Completed"  <?php echo ($status_filter=='Completed')  ?'selected':''; ?>>Completed</option>
                            <option value="Cancelled"  <?php echo ($status_filter=='Cancelled')  ?'selected':''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1e293b;">Priority</label>
                        <select name="priority" class="form-input" style="width:100%;">
                            <option value="all" <?php echo ($priority_filter=='all')?'selected':''; ?>>All Priorities</option>
                            <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo e($priority['PriorityLevel']); ?>"
                                    <?php echo ($priority_filter==$priority['PriorityLevel'])?'selected':''; ?>>
                                    <?php echo e($priority['PriorityLevel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1e293b;">Category</label>
                        <select name="category" class="form-input" style="width:100%;">
                            <option value="all" <?php echo ($category_filter=='all')?'selected':''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e($category['CategoryName']); ?>"
                                    <?php echo ($category_filter==$category['CategoryName'])?'selected':''; ?>>
                                    <?php echo e($category['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1e293b;">Sort By</label>
                        <select name="sort" class="form-input" style="width:100%;">
                            <option value="newest"   <?php echo ($sort_by=='newest')  ?'selected':''; ?>>Newest First</option>
                            <option value="oldest"   <?php echo ($sort_by=='oldest')  ?'selected':''; ?>>Oldest First</option>
                            <option value="priority" <?php echo ($sort_by=='priority')?'selected':''; ?>>Priority (High to Low)</option>
                            <option value="updated"  <?php echo ($sort_by=='updated') ?'selected':''; ?>>Recently Updated</option>
                        </select>
                    </div>

                    <div style="grid-column: span 2;">
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1e293b;">Search</label>
                        <input type="text" name="search" class="form-input"
                            placeholder="Search title, description, or requester..."
                            value="<?php echo e($search); ?>" style="width:100%;">
                    </div>

                    <div style="display:flex;gap:0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Apply</button>
                        <a href="all-requests.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Requests -->
            <div class="requests-section">
                <h2 class="section-title">Requests List</h2>

                <?php if (count($requests) > 0): ?>

                    <!-- ===== جدول للكمبيوتر ===== -->
                    <div class="admin-mobile-hide" style="overflow-x:auto;">
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
                                    <th>Technician</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr style="<?php echo ($req['PriorityID'] >= 3 && $req['StatusID'] < 5) ? 'background:#fef2f2;' : ''; ?>">
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        <td class="request-title">
                                            <?php echo e($req['Title']); ?>
                                            <?php if ($req['PriorityID'] == 4): ?><span style="color:#ef4444;">🚨</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo e($req['RequesterName']); ?></strong><br>
                                            <small style="color:#64748b;"><?php echo e($req['RequesterRole']); ?></small>
                                        </td>
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
                                        <td>
                                            <?php if ($req['TechnicianName']): ?>
                                                <span style="color:#10b981;">✅ <?php echo e($req['TechnicianName']); ?></span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">⚠️ Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></td>
                                        <td>
                                            <a href="request-details.php?id=<?php echo $req['RequestID']; ?>"
                                            class="btn btn-primary"
                                            style="padding:0.5rem 1rem;font-size:0.875rem;">
                                                ⚙️ Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ===== كاردز للجوال ===== -->
                    <div class="admin-requests-cards">
                        <?php foreach ($requests as $req): ?>
                            <div class="request-card-mobile <?php echo getPriorityBorderClass($req['PriorityLevel']); ?>">

                                <!-- Header -->
                                <div class="rcm-header">
                                    <div>
                                        <div class="rcm-id">
                                            #<?php echo $req['RequestID']; ?>
                                            <?php if ($req['PriorityID'] == 4): ?>🚨<?php endif; ?>
                                        </div>
                                        <div class="rcm-title"><?php echo e($req['Title']); ?></div>
                                        <div style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">
                                            👤 <?php echo e($req['RequesterName']); ?>
                                            <span style="color:#94a3b8;">(<?php echo e($req['RequesterRole']); ?>)</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- معلومات -->
                                <div class="rcm-meta">
                                    <span class="rcm-meta-item">📍 <?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></span>
                                    <span class="rcm-meta-item">🔧 <?php echo e($req['CategoryName']); ?></span>
                                    <span class="rcm-meta-item">
                                        👨‍🔧 <?php echo $req['TechnicianName'] ? e($req['TechnicianName']) : '⚠️ Unassigned'; ?>
                                    </span>
                                </div>

                                <!-- Footer -->
                                <div class="rcm-footer">
                                    <div style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
                                        <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                            <?php echo e($req['StatusName']); ?>
                                        </span>
                                        <span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>">
                                            <?php echo e($req['PriorityLevel']); ?>
                                        </span>
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.25rem;">
                                        <span class="rcm-date">📅 <?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></span>
                                        <a href="request-details.php?id=<?php echo $req['RequestID']; ?>"
                                        class="btn btn-primary"
                                        style="padding:0.4rem 0.9rem;font-size:0.8rem;">
                                            ⚙️ Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">🔍</div>
                        <h3>No requests found</h3>
                        <?php if ($status_filter != 'all' || $priority_filter != 'all' || $category_filter != 'all' || !empty($search)): ?>
                            <p>No requests match your current filters.</p><br>
                            <a href="all-requests.php" class="btn btn-secondary">Clear All Filters</a>
                        <?php else: ?>
                            <p>No maintenance requests have been submitted yet.</p>
                        <?php endif; ?>
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