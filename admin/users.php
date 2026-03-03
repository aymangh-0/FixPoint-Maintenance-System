<?php
/**
 * FixPoint - User Management (Admin)
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Success/Error من Redirect
if (isset($_GET['success']) && $_GET['success'] == 'reset') {
    $success = "User reset successfully! Their request count is now 0.";
}
if (isset($_GET['success']) && $_GET['success'] == 'limits') {
    $success = "Request limits updated successfully!";
}
if (isset($_GET['success']) && $_GET['success'] == 'role') {
    $success = "User role updated successfully!";
}
if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        'reset_failed'  => "Failed to reset user.",
        'limits_failed' => "Failed to update limits.",
        'role_failed'   => "Failed to update role.",
        'own_role'      => "You cannot change your own role!",
        'invalid_limits'=> "Invalid limits. Weekly limit must be at least 1.",
        'invalid_role'  => "Invalid role selected.",
        default         => "An error occurred."
    };
}

// Handle Update Request Limits
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_limits'])) {
    $user_id   = (int)$_POST['user_id'];
    $max_week  = (int)$_POST['max_week'];
    
    if ($max_week > 0) {
        $stmt = $conn->prepare("UPDATE user SET MaxRequestsPerWeek = ? WHERE UserID = ?");
        $stmt->bind_param("ii", $max_week, $user_id);
        if ($stmt->execute()) {
            header("Location: users.php?success=limits");
        } else {
            header("Location: users.php?error=limits_failed");
        }
    } else {
        header("Location: users.php?error=invalid_limits");
    }
    exit();
}


// Handle Change Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $user_id     = (int)$_POST['user_id'];
    $new_role_id = (int)$_POST['role_id'];
    
    if ($user_id == $admin_id) {
        header("Location: users.php?error=own_role");
        exit();
    }
    
    if ($new_role_id >= 1 && $new_role_id <= 4) {
        $stmt = $conn->prepare("UPDATE user SET RoleID = ? WHERE UserID = ?");
        $stmt->bind_param("ii", $new_role_id, $user_id);
        
        if ($stmt->execute()) {
            if ($new_role_id == 3 || $new_role_id == 4) {
                $default_week  = ($new_role_id == 3) ? 2 : 5;
                $limit_stmt = $conn->prepare("UPDATE user SET MaxRequestsPerWeek = ? WHERE UserID = ?");
                $limit_stmt->bind_param("ii", $default_week, $user_id);
                $limit_stmt->execute();
            } else {
                $limit_stmt = $conn->prepare("UPDATE user SET MaxRequestsPerWeek = NULL WHERE UserID = ?");
                $limit_stmt->bind_param("i", $user_id);
                $limit_stmt->execute();
            }
            header("Location: users.php?success=role");
        } else {
            header("Location: users.php?error=role_failed");
        }
    } else {
        header("Location: users.php?error=invalid_role");
    }
    exit();
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$base_sql = "SELECT 
        u.UserID, u.Name, u.Email, u.Phone, u.CreatedAt,
        u.MaxRequestsPerWeek, u.LastResetAt,
        r.RoleID, r.RoleName,
        COUNT(DISTINCT mr.RequestID) as TotalRequests,
        COUNT(DISTINCT CASE 
            WHEN mr.SubmittedAt > COALESCE(u.LastResetAt, DATE_SUB(NOW(), INTERVAL 7 DAY))
            THEN mr.RequestID END) as RequestsThisWeek
    FROM user u
    JOIN role r ON u.RoleID = r.RoleID
    LEFT JOIN maintenancerequest mr ON u.UserID = mr.UserID";

$search_condition = '';
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $search_condition = " AND (u.Name LIKE '%$s%' OR u.Email LIKE '%$s%')";
}

function fetchUsersByRole($conn, $base_sql, $role_id, $search_condition) {
    $sql = $base_sql . " WHERE r.RoleID = $role_id" . $search_condition . " GROUP BY u.UserID ORDER BY u.CreatedAt DESC";
    $result = $conn->query($sql);
    if (!$result) { die("SQL Error: " . $conn->error . "<br><pre>" . $sql . "</pre>"); }
    return $result->fetch_all(MYSQLI_ASSOC);
}

$admins      = fetchUsersByRole($conn, $base_sql, 1, $search_condition);
$technicians = fetchUsersByRole($conn, $base_sql, 2, $search_condition);
$users    = fetchUsersByRole($conn, $base_sql, 3, $search_condition);
$faculty     = fetchUsersByRole($conn, $base_sql, 4, $search_condition);

// Stats
$stats = [];
$roles_result = $conn->query("SELECT r.RoleName, COUNT(*) as Count FROM user u JOIN role r ON u.RoleID = r.RoleID GROUP BY r.RoleName");
while ($row = $roles_result->fetch_assoc()) {
    $stats[$row['RoleName']] = $row['Count'];
}
$stats['Total'] = array_sum($stats);

function renderUsersTable($users, $admin_id, $show_limits = false) {
    if (empty($users)) {
        echo '<p style="color:#94a3b8; padding:1rem 0;">No users found in this group.</p>';
        return;
    }
    ?>
    <div style="overflow-x:auto;">
        <table class="requests-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Total</th>
                    <th>This Week</th>
                                        <?php if ($show_limits): ?><th>Limits (W/M)</th><?php endif; ?>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong>#<?php echo $user['UserID']; ?></strong></td>
                        <td class="request-title"><?php echo e($user['Name']); ?></td>
                        <td><?php echo e($user['Email']); ?></td>
                        <td><?php echo $user['Phone'] ? e($user['Phone']) : '<span style="color:#94a3b8;">N/A</span>'; ?></td>
                        <td><strong><?php echo $user['TotalRequests']; ?></strong></td>
                        <td>
                            <?php
                            $week_pct = $user['MaxRequestsPerWeek'] > 0 ? ($user['RequestsThisWeek'] / $user['MaxRequestsPerWeek']) * 100 : 0;
                            $week_color = $week_pct >= 80 ? '#ef4444' : ($week_pct >= 50 ? '#f59e0b' : '#10b981');
                            ?>
                            <span style="color:<?php echo $week_color; ?>; font-weight:600;"><?php echo $user['RequestsThisWeek']; ?></span>
                        </td>

                        <?php if ($show_limits): ?>
                            <td>
                                <?php if ($user['MaxRequestsPerWeek']): ?>
                                    <strong><?php echo $user['MaxRequestsPerWeek']; ?></strong> 
                                <?php else: ?>
                                    <span style="color:#94a3b8;">∞ / ∞</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td><?php echo formatDate($user['CreatedAt'], 'M d, Y'); ?></td>
                        <td>
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <?php if ($user['UserID'] == $admin_id): ?>
                                    <span style="color:#64748b; font-size:0.875rem;">👑 You</span>
                                <?php else: ?>
                                    <button onclick="openRoleModal(<?php echo $user['UserID']; ?>, '<?php echo e($user['Name']); ?>', <?php echo $user['RoleID']; ?>)"
                                        class="btn btn-secondary" style="padding:0.4rem 0.75rem; font-size:0.8rem;">
                                        👤 Role
                                    </button>
                                    <?php if ($show_limits): ?>
                                        <button onclick="openLimitModal(<?php echo $user['UserID']; ?>, '<?php echo e($user['Name']); ?>', <?php echo $user['MaxRequestsPerWeek']; ?>)"
                                            class="btn btn-primary" style="padding:0.4rem 0.75rem; font-size:0.8rem;">
                                            ⚙️ Limits
                                        
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

$current_page = 'users';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .modal {
            display:none; position:fixed; z-index:1000;
            left:0; top:0; width:100%; height:100%;
            background-color:rgba(0,0,0,0.5); animation:fadeIn 0.3s;
        }
        .modal-content {
            background-color:white; margin:5% auto; padding:2rem;
            border-radius:1rem; width:90%; max-width:500px;
            box-shadow:0 4px 20px rgba(0,0,0,0.3); animation:slideIn 0.3s;
        }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        @keyframes slideIn { from{transform:translateY(-50px);opacity:0;} to{transform:translateY(0);opacity:1;} }
        .modal-header {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:2px solid #e2e8f0;
        }
        .modal-title { font-size:1.5rem; font-weight:700; color:#1e293b; }
        .close-btn { font-size:2rem; color:#64748b; cursor:pointer; background:none; border:none; padding:0; line-height:1; }
        .close-btn:hover { color:#1e293b; }
        .user-stats { background:#f8fafc; padding:1rem; border-radius:0.5rem; margin-bottom:1rem; }
        .user-stats-item { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #e2e8f0; }
        .user-stats-item:last-child { border-bottom:none; }
        .group-section {
            background:white; border-radius:1rem; padding:1.5rem;
            margin-bottom:2rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);
        }
        .group-header {
            display:flex; align-items:center; gap:0.75rem;
            margin-bottom:1rem; padding-bottom:0.75rem;
            border-bottom:2px solid #e2e8f0;
        }
        .group-header h2 { font-size:1.1rem; font-weight:700; color:#1e293b; margin:0; }
        .group-count {
            background:#f1f5f9; color:#475569;
            padding:0.2rem 0.6rem; border-radius:1rem;
            font-size:0.8rem; font-weight:600;
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
            <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
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
            <div class="topbar-notif"><?php include __DIR__ . '/../includes/notification-bell.php'; ?></div>
        </div>



    <div class="dashboard">
        <div class="dashboard-container">

            <div class="dashboard-header">
                <h1 class="welcome-text">User Management 👥</h1>
                <p class="user-info">Manage users, set request limits, and view activity</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo e($error); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">👥 Total Users</div>
                    <div class="stat-value"><?php echo $stats['Total']; ?></div>
                </div>
                <div class="stat-card" style="border-left-color:#ef4444;">
                    <div class="stat-label">👨‍💼 Admins</div>
                    <div class="stat-value"><?php echo $stats['Admin'] ?? 0; ?></div>
                </div>
                <div class="stat-card" style="border-left-color:#f59e0b;">
                    <div class="stat-label">👨‍🔧 Technicians</div>
                    <div class="stat-value"><?php echo $stats['Technician'] ?? 0; ?></div>
                </div>
                <div class="stat-card" style="border-left-color:#10b981;">
                    <div class="stat-label">👤 Users</div>
                    <div class="stat-value"><?php echo $stats['User'] ?? 0; ?></div>
                </div>
                <div class="stat-card" style="border-left-color:#3b82f6;">
                    <div class="stat-label">👨‍🏫 Faculty</div>
                    <div class="stat-value"><?php echo $stats['Faculty'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Search -->
            <div class="requests-section" style="margin-bottom:2rem;">
                <form method="GET" action="" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                    <div style="flex:2; min-width:300px;">
                        <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1e293b;">Search</label>
                        <input type="text" name="search" class="form-input" placeholder="Search by name or email..."
                               value="<?php echo e($search); ?>" style="width:100%;">
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="users.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Admins -->
            <div class="group-section">
                <div class="group-header">
                    <span style="font-size:1.3rem;">👨‍💼</span>
                    <h2>Admins</h2>
                    <span class="group-count"><?php echo count($admins); ?></span>
                </div>
                <?php renderUsersTable($admins, $admin_id, false); ?>
            </div>

            <!-- Technicians -->
            <div class="group-section">
                <div class="group-header">
                    <span style="font-size:1.3rem;">👨‍🔧</span>
                    <h2>Technicians</h2>
                    <span class="group-count"><?php echo count($technicians); ?></span>
                </div>
                <?php renderUsersTable($technicians, $admin_id, false); ?>
            </div>

            <!-- Users -->
            <div class="group-section">
                <div class="group-header">
                    <span style="font-size:1.3rem;">👨‍🎓</span>
                    <h2>Users</h2>
                    <span class="group-count"><?php echo count($users); ?></span>
                </div>
                <?php renderUsersTable($users, $admin_id, true); ?>
            </div>

            <!-- Faculty -->
            <div class="group-section">
                <div class="group-header">
                    <span style="font-size:1.3rem;">👨‍🏫</span>
                    <h2>Faculty</h2>
                    <span class="group-count"><?php echo count($faculty); ?></span>
                </div>
                <?php renderUsersTable($faculty, $admin_id, true); ?>
            </div>

        </div>
    </div>

    <!-- Limits Modal -->
    <div id="limitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">⚙️ Set Request Limits</h2>
                <button class="close-btn" onclick="closeLimitModal()">&times;</button>
            </div>
            <div class="user-stats">
                <div class="user-stats-item">
                    <strong>User:</strong>
                    <span id="modal-user-name"></span>
                </div>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="modal-user-id">
                <div class="form-group">
                    <label class="form-label">Maximum Requests Per Week</label>
                    <input type="number" id="max_week" name="max_week" class="form-input" min="1" max="100" required>
                    <small style="color:#64748b;">Requests allowed per 7-day period</small>
                </div>
                <button type="submit" name="update_limits" class="btn-submit">💾 Save Limits</button>
            </form>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">👤 Change User Role</h2>
                <button class="close-btn" onclick="closeRoleModal()">&times;</button>
            </div>
            <div class="user-stats">
                <div class="user-stats-item">
                    <strong>User:</strong>
                    <span id="role-modal-user-name"></span>
                </div>
            </div>
            <div style="background:#fef3c7; border:1px solid #fde68a; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; color:#92400e;">
                ⚠️ <strong>Warning:</strong> Changing a user's role will affect their permissions and access level.
                <ul style="margin:0.5rem 0 0 1.5rem; font-size:0.875rem;">
                    <li><strong>Admin:</strong> Full system access</li>
                    <li><strong>Technician:</strong> Can view and work on assigned requests</li>
                    <li><strong>User:</strong> Can submit requests (2/week)</li>
                    <li><strong>Faculty:</strong> Can submit requests (5/week)</li>
                </ul>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="role-modal-user-id">
                <div class="form-group">
                    <label class="form-label">Select New Role</label>
                    <select id="role_id" name="role_id" class="form-input" required>
                        <option value="1">👨‍💼 Admin</option>
                        <option value="2">👨‍🔧 Technician</option>
                        <option value="3">👨‍🎓 User (2/week)</option>
                        <option value="4">👨‍🏫 Faculty (5/week)</option>
                    </select>
                </div>
                <button type="submit" name="change_role" class="btn-submit">💾 Change Role</button>
            </form>
        </div>
    </div>

    <script>
        function openLimitModal(userId, userName, maxWeek) {
            document.getElementById('modal-user-id').value = userId;
            document.getElementById('modal-user-name').textContent = userName;
            document.getElementById('max_week').value = maxWeek || 2;
            
            document.getElementById('limitModal').style.display = 'block';
        }
        function closeLimitModal() { document.getElementById('limitModal').style.display = 'none'; }

        function openRoleModal(userId, userName, currentRoleId) {
            document.getElementById('role-modal-user-id').value = userId;
            document.getElementById('role-modal-user-name').textContent = userName;
            document.getElementById('role_id').value = currentRoleId;
            document.getElementById('roleModal').style.display = 'block';
        }
        function closeRoleModal() { document.getElementById('roleModal').style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target == document.getElementById('limitModal')) closeLimitModal();
            if (event.target == document.getElementById('roleModal')) closeRoleModal();
        }
    </script>
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