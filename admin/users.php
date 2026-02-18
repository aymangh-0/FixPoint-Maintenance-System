<?php
/**
 * FixPoint - User Management (Admin)
 * Manage users, change request limits, view activity
 */

session_start();
require_once '../config/session-security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Update Request Limits
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_limits'])) {
    $user_id = (int)$_POST['user_id'];
    $max_week = (int)$_POST['max_week'];
    $max_month = (int)$_POST['max_month'];
    
    if ($max_week > 0 && $max_month > 0 && $max_week <= $max_month) {
        $update_sql = "UPDATE user SET MaxRequestsPerWeek = ?, MaxRequestsPerMonth = ? WHERE UserID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $max_week, $max_month, $user_id);
        
        if ($stmt->execute()) {
            $success = "Request limits updated successfully!";
        } else {
            $error = "Failed to update limits.";
        }
    } else {
        $error = "Invalid limits. Weekly limit must be less than or equal to monthly limit.";
    }
}

// Handle Change Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role_id = (int)$_POST['role_id'];
    
    // Prevent admin from changing their own role
    if ($user_id == $admin_id) {
        $error = "You cannot change your own role!";
    } elseif ($new_role_id >= 1 && $new_role_id <= 5) {
        $update_sql = "UPDATE user SET RoleID = ? WHERE UserID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $new_role_id, $user_id);
        
        if ($stmt->execute()) {
            // If changing to/from Student or Faculty, set appropriate limits
            if ($new_role_id == 3 || $new_role_id == 4) {
                // Set default limits for Students/Faculty
                $default_week = ($new_role_id == 3) ? 2 : 5;  // Student: 2, Faculty: 5
                $default_month = ($new_role_id == 3) ? 8 : 20; // Student: 8, Faculty: 20
                
                $limit_sql = "UPDATE user SET MaxRequestsPerWeek = ?, MaxRequestsPerMonth = ? WHERE UserID = ?";
                $limit_stmt = $conn->prepare($limit_sql);
                $limit_stmt->bind_param("iii", $default_week, $default_month, $user_id);
                $limit_stmt->execute();
            } else {
                // Remove limits for Admin/Technician
                $limit_sql = "UPDATE user SET MaxRequestsPerWeek = NULL, MaxRequestsPerMonth = NULL WHERE UserID = ?";
                $limit_stmt = $conn->prepare($limit_sql);
                $limit_stmt->bind_param("i", $user_id);
                $limit_stmt->execute();
            }
            
            $success = "User role updated successfully!";
        } else {
            $error = "Failed to update role.";
        }
    } else {
        $error = "Invalid role selected.";
    }
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for users
$sql = "SELECT 
            u.UserID,
            u.Name,
            u.Email,
            u.Phone,
            u.CreatedAt,
            u.MaxRequestsPerWeek,
            u.MaxRequestsPerMonth,
            r.RoleID,
            r.RoleName,
            COUNT(DISTINCT mr.RequestID) as TotalRequests,
            COUNT(DISTINCT CASE WHEN mr.SubmittedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN mr.RequestID END) as RequestsThisWeek,
            COUNT(DISTINCT CASE WHEN mr.SubmittedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN mr.RequestID END) as RequestsThisMonth
        FROM user u
        JOIN role r ON u.RoleID = r.RoleID
        LEFT JOIN maintenancerequest mr ON u.UserID = mr.UserID
        WHERE 1=1";

// Apply role filter
if ($role_filter != 'all') {
    $sql .= " AND r.RoleName = '" . $conn->real_escape_string($role_filter) . "'";
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (u.Name LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR u.Email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$sql .= " GROUP BY u.UserID ORDER BY u.CreatedAt DESC";

$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [];

// Total users by role
$roles_sql = "SELECT r.RoleName, COUNT(*) as Count 
              FROM user u 
              JOIN role r ON u.RoleID = r.RoleID 
              GROUP BY r.RoleName";
$roles_result = $conn->query($roles_sql);
while ($row = $roles_result->fetch_assoc()) {
    $stats[$row['RoleName']] = $row['Count'];
}

$stats['Total'] = array_sum($stats);

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
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .close-btn {
            font-size: 2rem;
            color: #64748b;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        
        .close-btn:hover {
            color: #1e293b;
        }
        
        .user-stats {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .user-stats-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .user-stats-item:last-child {
            border-bottom: none;
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
                    <a href="reports.php" class="nav-link">Reports</a>
                    <a href="backup.php" class="nav-link">Backup</a>
                    <a href="locations.php" class="nav-link">Locations</a>
                    <a href="audit-logs.php" class="nav-link">Audit Logs</a>
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
                <h1 class="welcome-text">User Management 👥</h1>
                <p class="user-info">Manage users, set request limits, and view activity</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?php echo e($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">👥 Total Users</div>
                    <div class="stat-value"><?php echo $stats['Total']; ?></div>
                    <div class="stat-info">All registered users</div>
                </div>
                <div class="stat-card" style="border-left-color: #ef4444;">
                    <div class="stat-label">👨‍💼 Admins</div>
                    <div class="stat-value"><?php echo isset($stats['Admin']) ? $stats['Admin'] : 0; ?></div>
                    <div class="stat-info">System administrators</div>
                </div>
                <div class="stat-card" style="border-left-color: #f59e0b;">
                    <div class="stat-label">👨‍🔧 Technicians</div>
                    <div class="stat-value"><?php echo isset($stats['Technician']) ? $stats['Technician'] : 0; ?></div>
                    <div class="stat-info">Maintenance staff</div>
                </div>
                <div class="stat-card" style="border-left-color: #10b981;">
                    <div class="stat-label">👨‍🎓 Students</div>
                    <div class="stat-value"><?php echo isset($stats['Student']) ? $stats['Student'] : 0; ?></div>
                    <div class="stat-info">Registered students</div>
                </div>
                <div class="stat-card" style="border-left-color: #3b82f6;">
                    <div class="stat-label">👨‍🏫 Faculty</div>
                    <div class="stat-value"><?php echo isset($stats['Faculty']) ? $stats['Faculty'] : 0; ?></div>
                    <div class="stat-info">Faculty members</div>
                </div>
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <div class="stat-label">📊 Showing</div>
                    <div class="stat-value"><?php echo count($users); ?></div>
                    <div class="stat-info">Filtered results</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">🔍 Filters & Search</h2>
                
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                    <!-- Role Filter -->
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Filter by Role</label>
                        <select name="role" class="form-input" style="width: 100%;">
                            <option value="all" <?php echo ($role_filter == 'all') ? 'selected' : ''; ?>>All Roles</option>
                            <option value="Admin" <?php echo ($role_filter == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="Technician" <?php echo ($role_filter == 'Technician') ? 'selected' : ''; ?>>Technician</option>
                            <option value="Student" <?php echo ($role_filter == 'Student') ? 'selected' : ''; ?>>Student</option>
                            <option value="Faculty" <?php echo ($role_filter == 'Faculty') ? 'selected' : ''; ?>>Faculty</option>
                        </select>
                    </div>
                    
                    <!-- Search Box -->
                    <div style="flex: 2; min-width: 300px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-input" 
                            placeholder="Search by name or email..."
                            value="<?php echo e($search); ?>"
                            style="width: 100%;"
                        >
                    </div>
                    
                    <!-- Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="users.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="requests-section">
                <h2 class="section-title">Users List</h2>
                
                <?php if (count($users) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Total Requests</th>
                                    <th>This Week</th>
                                    <th>This Month</th>
                                    <th>Limits (W/M)</th>
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
                                        <td>
                                            <span class="status-badge" style="
                                                <?php 
                                                switch($user['RoleName']) {
                                                    case 'Admin': echo 'background: #fee2e2; color: #991b1b;'; break;
                                                    case 'Technician': echo 'background: #fef3c7; color: #92400e;'; break;
                                                    case 'Student': echo 'background: #d1fae5; color: #065f46;'; break;
                                                    case 'Faculty': echo 'background: #dbeafe; color: #1e40af;'; break;
                                                }
                                                ?>
                                            ">
                                                <?php echo e($user['RoleName']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['Phone'] ? e($user['Phone']) : '<span style="color: #94a3b8;">N/A</span>'; ?></td>
                                        <td><strong><?php echo $user['TotalRequests']; ?></strong></td>
                                        <td>
                                            <?php 
                                            $week_pct = $user['MaxRequestsPerWeek'] > 0 ? ($user['RequestsThisWeek'] / $user['MaxRequestsPerWeek']) * 100 : 0;
                                            $week_color = $week_pct >= 80 ? '#ef4444' : ($week_pct >= 50 ? '#f59e0b' : '#10b981');
                                            ?>
                                            <span style="color: <?php echo $week_color; ?>; font-weight: 600;">
                                                <?php echo $user['RequestsThisWeek']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $month_pct = $user['MaxRequestsPerMonth'] > 0 ? ($user['RequestsThisMonth'] / $user['MaxRequestsPerMonth']) * 100 : 0;
                                            $month_color = $month_pct >= 80 ? '#ef4444' : ($month_pct >= 50 ? '#f59e0b' : '#10b981');
                                            ?>
                                            <span style="color: <?php echo $month_color; ?>; font-weight: 600;">
                                                <?php echo $user['RequestsThisMonth']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['MaxRequestsPerWeek']): ?>
                                                <strong><?php echo $user['MaxRequestsPerWeek']; ?></strong> / 
                                                <strong><?php echo $user['MaxRequestsPerMonth']; ?></strong>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">∞ / ∞</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($user['CreatedAt'], 'M d, Y'); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <!-- Change Role Button -->
                                                <?php if ($user['UserID'] != $admin_id): ?>
                                                    <button 
                                                        onclick="openRoleModal(<?php echo $user['UserID']; ?>, '<?php echo e($user['Name']); ?>', <?php echo $user['RoleID']; ?>)"
                                                        class="btn btn-secondary" 
                                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                        👤 Change Role
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Set Limits Button (only for Students/Faculty) -->
                                                <?php if ($user['RoleID'] == 3 || $user['RoleID'] == 4): ?>
                                                    <button 
                                                        onclick="openLimitModal(<?php echo $user['UserID']; ?>, '<?php echo e($user['Name']); ?>', <?php echo $user['MaxRequestsPerWeek']; ?>, <?php echo $user['MaxRequestsPerMonth']; ?>)"
                                                        class="btn btn-primary" 
                                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                        ⚙️ Set Limits
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['UserID'] == $admin_id): ?>
                                                    <span style="color: #64748b; font-size: 0.875rem;">👑 You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">🔍</div>
                        <h3>No users found</h3>
                        <p>No users match your current filters.</p>
                        <br>
                        <a href="users.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>

    <!-- Edit Limits Modal -->
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
                    <label for="max_week" class="form-label">Maximum Requests Per Week</label>
                    <input 
                        type="number" 
                        id="max_week" 
                        name="max_week" 
                        class="form-input" 
                        min="1"
                        max="100"
                        required
                    >
                    <small style="color: #64748b;">Number of requests allowed per 7-day period</small>
                </div>
                
                <div class="form-group">
                    <label for="max_month" class="form-label">Maximum Requests Per Month</label>
                    <input 
                        type="number" 
                        id="max_month" 
                        name="max_month" 
                        class="form-input" 
                        min="1"
                        max="200"
                        required
                    >
                    <small style="color: #64748b;">Number of requests allowed per 30-day period</small>
                </div>
                
                <button type="submit" name="update_limits" class="btn-submit">
                    💾 Save Limits
                </button>
            </form>
        </div>
    </div>

    <!-- Change Role Modal -->
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
            
            <div style="background: #fef3c7; border: 1px solid #fde68a; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; color: #92400e;">
                ⚠️ <strong>Warning:</strong> Changing a user's role will affect their permissions and access level.
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.875rem;">
                    <li><strong>Admin:</strong> Full system access</li>
                    <li><strong>Technician:</strong> Can view and work on assigned requests</li>
                    <li><strong>Student:</strong> Can submit requests (2/week, 8/month)</li>
                    <li><strong>Faculty:</strong> Can submit requests (5/week, 20/month)</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="role-modal-user-id">
                
                <div class="form-group">
                    <label for="role_id" class="form-label">Select New Role</label>
                    <select 
                        id="role_id" 
                        name="role_id" 
                        class="form-input" 
                        required
                    >
                        <option value="1">👨‍💼 Admin - Full system access</option>
                        <option value="2">👨‍🔧 Technician - Maintenance staff</option>
                        <option value="3">👨‍🎓 Student - Can submit requests (2/week, 8/month)</option>
                        <option value="4">👨‍🏫 Faculty - Can submit requests (5/week, 20/month)</option>
                    </select>
                </div>
                
                <button type="submit" name="change_role" class="btn-submit">
                    💾 Change Role
                </button>
            </form>
        </div>
    </div>

    <script>
        // Limits Modal Functions
        function openLimitModal(userId, userName, maxWeek, maxMonth) {
            document.getElementById('modal-user-id').value = userId;
            document.getElementById('modal-user-name').textContent = userName;
            document.getElementById('max_week').value = maxWeek || 2;
            document.getElementById('max_month').value = maxMonth || 8;
            document.getElementById('limitModal').style.display = 'block';
        }
        
        function closeLimitModal() {
            document.getElementById('limitModal').style.display = 'none';
        }
        
        // Role Modal Functions
        function openRoleModal(userId, userName, currentRoleId) {
            document.getElementById('role-modal-user-id').value = userId;
            document.getElementById('role-modal-user-name').textContent = userName;
            document.getElementById('role_id').value = currentRoleId;
            document.getElementById('roleModal').style.display = 'block';
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const limitModal = document.getElementById('limitModal');
            const roleModal = document.getElementById('roleModal');
            
            if (event.target == limitModal) {
                closeLimitModal();
            }
            if (event.target == roleModal) {
                closeRoleModal();
            }
        }
    </script>
</body>
</html>