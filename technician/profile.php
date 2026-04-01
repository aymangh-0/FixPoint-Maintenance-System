<?php
/**
 * FixPoint - User Profile
 * View and edit personal information (Name, Email, Phone, Password)
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user data
$sql = "SELECT u.Name, u.Email, u.Phone, u.RoleID, r.RoleName, u.CreatedAt, u.MaxRequestsPerWeek
        FROM user u JOIN role r ON u.RoleID = r.RoleID WHERE u.UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Update Name & Email & Phone
    if ($_POST['action'] === 'update_info') {
        $new_name = trim($_POST['name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');

        if (empty($new_name)) {
            $error_msg = "Name cannot be empty.";
        } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } else {
            // Check if email is already used by another user
            $check = $conn->prepare("SELECT UserID FROM user WHERE Email = ? AND UserID != ?");
            $check->bind_param("si", $new_email, $user_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error_msg = "This email is already registered to another account.";
            } else {
                $update = $conn->prepare("UPDATE user SET Name = ?, Email = ?, Phone = ? WHERE UserID = ?");
                $update->bind_param("sssi", $new_name, $new_email, $new_phone, $user_id);
                if ($update->execute()) {
                    // Update session data
                    $_SESSION['name'] = $new_name;
                    $_SESSION['email'] = $new_email;
                    $user['Name'] = $new_name;
                    $user['Email'] = $new_email;
                    $user['Phone'] = $new_phone;
                    $success_msg = "Profile updated successfully.";
                } else {
                    $error_msg = "Failed to update profile. Please try again.";
                }
            }
        }
    }

    // Change Password
    if ($_POST['action'] === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $error_msg = "All password fields are required.";
        } elseif (strlen($new_pass) < 6) {
            $error_msg = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm_pass) {
            $error_msg = "New password and confirmation do not match.";
        } else {
            // Verify current password
            $pass_check = $conn->prepare("SELECT Password FROM user WHERE UserID = ?");
            $pass_check->bind_param("i", $user_id);
            $pass_check->execute();
            $stored_pass = $pass_check->get_result()->fetch_assoc()['Password'];

            if (!password_verify($current_pass, $stored_pass)) {
                $error_msg = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update_pass = $conn->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
                $update_pass->bind_param("si", $hashed, $user_id);
                if ($update_pass->execute()) {
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Failed to change password. Please try again.";
                }
            }
        }
    }
}

// Get request stats
$stats_sql = "SELECT COUNT(*) as total FROM maintenancerequest WHERE UserID = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$total_requests = $stats_stmt->get_result()->fetch_assoc()['total'];

// Determine sidebar based on role
$is_admin = ($_SESSION['role_id'] == 1);
$is_tech = ($_SESSION['role_id'] == 2);
$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }
        .profile-name { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .profile-role {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .profile-meta { color: #64748b; font-size: 0.85rem; margin-top: 0.25rem; }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            color: #1e293b;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .info-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.75rem;
            text-align: center;
        }
        .info-item .info-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
        .info-item .info-label { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }
        .alert-success {
            background: #d1fae5; border: 2px solid #a7f3d0; padding: 1rem 1.5rem;
            border-radius: 0.75rem; color: #065f46; font-weight: 600; margin-bottom: 1.5rem;
        }
        .alert-error {
            background: #fee2e2; border: 2px solid #fecaca; padding: 1rem 1.5rem;
            border-radius: 0.75rem; color: #991b1b; font-weight: 600; margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.25rem;
        }
        .btn-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover { background: #1d4ed8; }
        .divider { height: 1px; background: #e2e8f0; margin: 2rem 0; }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body class="has-sidebar">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="sidebar-logo-icon">🔧</span>
                <div>
                    <span class="sidebar-logo-text">FixPoint</span>
                    <span class="sidebar-logo-sub">SEU<?php echo $is_admin ? ' Admin' : ($is_tech ? ' Tech' : ''); ?></span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role"><?php echo e($user['RoleName']); ?></span>
            </div>
            <?php include __DIR__ . '/../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <?php if ($is_admin): ?>
                <div class="sidebar-section-label">Main</div>
                <a href="dashboard.php" class="sidebar-link"><span class="sidebar-icon">📊</span><span>Dashboard</span></a>
                <a href="all-requests.php" class="sidebar-link"><span class="sidebar-icon">📋</span><span>All Requests</span></a>
                <a href="users.php" class="sidebar-link"><span class="sidebar-icon">👥</span><span>Manage Users</span></a>
                <div class="sidebar-section-label">Management</div>
                <a href="locations.php" class="sidebar-link"><span class="sidebar-icon">📍</span><span>Locations</span></a>
                <a href="reports.php" class="sidebar-link"><span class="sidebar-icon">📈</span><span>Reports</span></a>
                <a href="all-feedback.php" class="sidebar-link"><span class="sidebar-icon">⭐</span><span>Feedback</span></a>
                <a href="audit-logs.php" class="sidebar-link"><span class="sidebar-icon">🔍</span><span>Audit Logs</span></a>
                <a href="backup.php" class="sidebar-link"><span class="sidebar-icon">💾</span><span>Backup</span></a>
            <?php elseif ($is_tech): ?>
                <div class="sidebar-section-label">My Tasks</div>
                <a href="dashboard.php" class="sidebar-link"><span class="sidebar-icon">🏠</span><span>Dashboard</span></a>
                <a href="my-tasks.php" class="sidebar-link"><span class="sidebar-icon">🔧</span><span>My Tasks</span></a>
            <?php else: ?>
                <div class="sidebar-section-label">My Account</div>
                <a href="dashboard.php" class="sidebar-link"><span class="sidebar-icon">🏠</span><span>Dashboard</span></a>
                <a href="submit-request.php" class="sidebar-link"><span class="sidebar-icon">📝</span><span>Submit Request</span></a>
                <a href="my-requests.php" class="sidebar-link"><span class="sidebar-icon">📋</span><span>My Requests</span></a>
            <?php endif; ?>
            <div class="sidebar-divider"></div>
            <a href="profile.php" class="sidebar-link active"><span class="sidebar-icon">👤</span><span>My Profile</span></a>
            <a href="../auth/logout.php" class="sidebar-link sidebar-logout"><span class="sidebar-icon">🚪</span><span>Logout</span></a>
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

            <!-- Messages -->
            <?php if ($success_msg): ?>
                <div class="alert-success">✅ <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert-error">❌ <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['Name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="profile-name"><?php echo e($user['Name']); ?></div>
                        <span class="profile-role"><?php echo e($user['RoleName']); ?></span>
                        <div class="profile-meta">Member since <?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-value"><?php echo $total_requests; ?></div>
                        <div class="info-label">Total Requests</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo $user['MaxRequestsPerWeek'] ?? 'Unlimited'; ?></div>
                        <div class="info-label">Weekly Limit</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo e($user['RoleName']); ?></div>
                        <div class="info-label">Account Type</div>
                    </div>
                </div>
            </div>

            <!-- Edit Personal Information -->
            <div class="profile-card">
                <h2 class="section-title">✏️ Personal Information</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_info">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="<?php echo e($user['Name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="<?php echo e($user['Email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="max-width: 50%;">
                        <label>Phone Number (optional)</label>
                        <input type="tel" name="phone" value="<?php echo e($user['Phone'] ?? ''); ?>" placeholder="+966xxxxxxxxx">
                    </div>
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h2 class="section-title">🔒 Change Password</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group" style="max-width: 50%;">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" required placeholder="Enter current password">
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter new password">
                        </div>
                    </div>
                    <button type="submit" class="btn-save">🔒 Change Password</button>
                </form>
            </div>

        </div>
    </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        function openSidebar() { sidebar.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow='hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow=''; }
        document.getElementById('hamburgerBtn')?.addEventListener('click', openSidebar);
        document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);
        document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);

        const notifBell = document.getElementById('notifBell');
        const notifDropdown = document.getElementById('notifDropdown');
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