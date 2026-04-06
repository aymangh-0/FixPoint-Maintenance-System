<?php
/**
 * FixPoint - My Requests
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 4)) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user_id = $_SESSION['user_id'];

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            s.StatusName,
            (SELECT PhotoPath FROM requestphoto WHERE RequestID = mr.RequestID LIMIT 1) as PhotoPath
        FROM maintenancerequest mr
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.UserID = ?";

if ($status_filter != 'all') {
    $sql .= " AND s.StatusName = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($search)) {
    $sql .= " AND (mr.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR mr.Description LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$sql .= " ORDER BY mr.SubmittedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = getUserRequestStats($conn, $user_id);

// دالة لون الكارد حسب الأولوية
function getPriorityBorderClass($priority) {
    $classes = [
        'Critical' => 'priority-critical-border',
        'High'     => 'priority-high-border',
        'Medium'   => 'priority-medium-border',
        'Low'      => 'priority-low-border',
    ];
    return $classes[$priority] ?? '';
}

$current_page = 'my-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - FixPoint</title>
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
            <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
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
            <div class="topbar-notif"><?php include __DIR__ . '/../includes/notification-bell.php'; ?></div>
        </div>


    <div class="dashboard">
        <div class="dashboard-container">

            <div class="dashboard-header">
                <h1 class="welcome-text">My Requests 📋</h1>
                <p class="user-info">View and track all your maintenance requests</p>
            </div>

            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div style="background: #d1fae5; border: 2px solid #a7f3d0; padding: 1rem 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; color: #065f46; font-weight: 600;">
                    ✅ Request cancelled successfully. Your weekly request limit has been restored.
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total</div>
                    <div class="stat-value"><?php echo $stats['Total']; ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['Pending']; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['In Progress']; ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['Completed']; ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">Filter & Search</h2>
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Filter by Status</label>
                        <select name="status" class="form-input" onchange="this.form.submit()" style="width: 100%;">
                            <option value="all"        <?php echo ($status_filter == 'all')         ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending"    <?php echo ($status_filter == 'Pending')     ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reviewed"   <?php echo ($status_filter == 'Reviewed')    ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="Assigned"   <?php echo ($status_filter == 'Assigned')    ? 'selected' : ''; ?>>Assigned</option>
                            <option value="In Progress"<?php echo ($status_filter == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed"  <?php echo ($status_filter == 'Completed')   ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled"  <?php echo ($status_filter == 'Cancelled')   ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div style="flex: 2; min-width: 300px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Search</label>
                        <input type="text" name="search" class="form-input"
                               placeholder="Search by title or description..."
                               value="<?php echo e($search); ?>" style="width: 100%;">
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="my-requests.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Requests -->
            <div class="requests-section">
                <h2 class="section-title">
                    All Requests
                    <span style="color: #64748b; font-size: 1rem; font-weight: 400;">
                        (<?php echo count($requests); ?> results)
                    </span>
                </h2>

                <?php if (count($requests) > 0): ?>

                    <!-- ===== جدول للكمبيوتر ===== -->
                    <div class="mobile-hide" style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Last Update</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        <td>
                                            <?php if ($req['PhotoPath']): ?>
                                                <img src="<?php echo e($req['PhotoPath']); ?>"
                                                     style="width:50px;height:50px;object-fit:cover;border-radius:0.25rem;">
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">📷 No photo</span>
                                            <?php endif; ?>
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
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></td>
                                        <td>
                                            <?php
                                            echo $req['CompletedAt']
                                                ? formatDate($req['CompletedAt'], 'M d, Y')
                                                : formatDate($req['UpdatedAt'], 'M d, Y');
                                            ?>
                                        </td>
                                        <td>
                                            <a href="request-details.php?id=<?php echo $req['RequestID']; ?>"
                                               class="btn btn-primary"
                                               style="padding:0.5rem 1rem;font-size:0.875rem;">
                                                👁️ View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ===== كاردز للجوال ===== -->
                    <div class="requests-cards">
                        <?php foreach ($requests as $req): ?>
                            <div class="request-card-mobile <?php echo getPriorityBorderClass($req['PriorityLevel']); ?>">
                                
                                <!-- Header: ID + صورة -->
                                <div class="rcm-header">
                                    <div>
                                        <div class="rcm-id">#<?php echo $req['RequestID']; ?></div>
                                    </div>
                                    <?php if ($req['PhotoPath']): ?>
                                        <img src="<?php echo e($req['PhotoPath']); ?>" class="rcm-photo" alt="photo">
                                    <?php else: ?>
                                        <div class="rcm-photo-placeholder">📷</div>
                                    <?php endif; ?>
                                </div>

                                <!-- معلومات -->
                                <div class="rcm-meta">
                                    <span class="rcm-meta-item">📍 <?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></span>
                                    <span class="rcm-meta-item">🔧 <?php echo e($req['CategoryName']); ?></span>
                                </div>

                                <!-- Footer: Status + Priority + زر -->
                                <div class="rcm-footer">
                                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center;">
                                        <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                            <?php echo e($req['StatusName']); ?>
                                        </span>
                                        <span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>">
                                            <?php echo e($req['PriorityLevel']); ?>
                                        </span>
                                    </div>
                                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.25rem;">
                                        <span class="rcm-date">
                                            📅 <?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?>
                                        </span>
                                        <a href="request-details.php?id=<?php echo $req['RequestID']; ?>"
                                           class="btn btn-primary"
                                           style="padding:0.4rem 0.9rem;font-size:0.8rem;">
                                            👁️ View
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
                        <?php if ($status_filter != 'all' || !empty($search)): ?>
                            <p>Try adjusting your filters or search terms.</p>
                            <br>
                            <a href="my-requests.php" class="btn btn-secondary">Clear Filters</a>
                        <?php else: ?>
                            <p>You haven't submitted any maintenance requests yet.</p>
                            <br>
                            <a href="submit-request.php" class="btn btn-primary">Submit Your First Request</a>
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