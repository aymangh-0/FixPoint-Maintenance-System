<?php
/**
 * FixPoint - Request Details (Admin View)
 * Full management: change status, assign technician, cancel request
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
require_once '../config/audit-logger.php';

$admin_id   = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg   = '';

// ============================================================
// POST: Change Status
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'change_status') {
        $new_status_id = (int)$_POST['new_status_id'];

        // Get current status
        $s = $conn->prepare("SELECT StatusID FROM maintenancerequest WHERE RequestID = ?");
        $s->bind_param("i", $request_id);
        $s->execute();
        $old_status_id = $s->get_result()->fetch_assoc()['StatusID'];

        // Update status
        $u = $conn->prepare("UPDATE maintenancerequest SET StatusID = ?, UpdatedAt = NOW() WHERE RequestID = ?");
        $u->bind_param("ii", $new_status_id, $request_id);
        $u->execute();

        // Set CompletedAt if completed
        if ($new_status_id == 5) {
            $c = $conn->prepare("UPDATE maintenancerequest SET CompletedAt = NOW() WHERE RequestID = ? AND CompletedAt IS NULL");
            $c->bind_param("i", $request_id);
            $c->execute();
        }

        // Status history
        $h = $conn->prepare("INSERT INTO statushistory (RequestID, OldStatusID, NewStatusID, ChangedBy, ChangedAt) VALUES (?, ?, ?, ?, NOW())");
        $h->bind_param("iiii", $request_id, $old_status_id, $new_status_id, $admin_id);
        $h->execute();

        // Notify requester
        $ri = $conn->prepare("SELECT mr.UserID, s.StatusName FROM maintenancerequest mr JOIN status s ON mr.StatusID = s.StatusID WHERE mr.RequestID = ?");
        $ri->bind_param("i", $request_id);
        $ri->execute();
        $ri_row = $ri->get_result()->fetch_assoc();
        if ($ri_row) {
            $notif_msg = "Your request #$request_id status has been updated to: " . $ri_row['StatusName'];
            $n = $conn->prepare("INSERT INTO notification (UserID, Title, Message, RequestID, CreatedAt) VALUES (?, 'Request Status Updated', ?, ?, NOW())");
            $n->bind_param("isi", $ri_row['UserID'], $notif_msg, $request_id);
            $n->execute();
        }

        // Audit
        logAuditAction($conn, $admin_id, 'STATUS_CHANGED', 'maintenancerequest', $request_id, "Status: $old_status_id", "Status: $new_status_id");
        $success_msg = "✅ Status updated successfully.";

        header("Location: request-details.php?id=$request_id&success=status");
        exit();
    }

    // ============================================================
    // POST: Assign Technician
    // ============================================================
    if ($_POST['action'] === 'assign_tech') {
        $tech_id = (int)$_POST['technician_id'];

        // Check if already assigned to this tech
        $chk = $conn->prepare("SELECT AssignmentID FROM assignment WHERE RequestID = ? AND TechnicianID = ?");
        $chk->bind_param("ii", $request_id, $tech_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error_msg = "This technician is already assigned to this request.";
        } else {
            // Insert assignment
            $a = $conn->prepare("INSERT INTO assignment (RequestID, TechnicianID, AssignedAt) VALUES (?, ?, NOW())");
            $a->bind_param("ii", $request_id, $tech_id);
            $a->execute();

            // Update status to Assigned (3) if still Pending/Reviewed
            $cur_s = $conn->prepare("SELECT StatusID FROM maintenancerequest WHERE RequestID = ?");
            $cur_s->bind_param("i", $request_id);
            $cur_s->execute();
            $cur_status = $cur_s->get_result()->fetch_assoc()['StatusID'];

            if ($cur_status <= 2) {
                $us = $conn->prepare("UPDATE maintenancerequest SET StatusID = 3, UpdatedAt = NOW() WHERE RequestID = ?");
                $us->bind_param("i", $request_id);
                $us->execute();

                $h = $conn->prepare("INSERT INTO statushistory (RequestID, OldStatusID, NewStatusID, ChangedBy, ChangedAt) VALUES (?, ?, 3, ?, NOW())");
                $h->bind_param("iii", $request_id, $cur_status, $admin_id);
                $h->execute();
            }

            // Notify technician
            $tech_info = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
            $tech_info->bind_param("i", $tech_id);
            $tech_info->execute();
            $tech_name = $tech_info->get_result()->fetch_assoc()['Name'];

            $notif_msg = "You have been assigned to Request #$request_id.";
            $n = $conn->prepare("INSERT INTO notification (UserID, Title, Message, RequestID, CreatedAt) VALUES (?, 'New Task Assigned', ?, ?, NOW())");
            $n->bind_param("isi", $tech_id, $notif_msg, $request_id);
            $n->execute();

            logAuditAction($conn, $admin_id, 'ASSIGN_TECHNICIAN', 'assignment', $request_id, null, "TechnicianID: $tech_id");

            header("Location: request-details.php?id=$request_id&success=assigned");
            exit();
        }
    }

    // ============================================================
    // POST: Cancel Request
    // ============================================================
    if ($_POST['action'] === 'cancel_request') {
        $s = $conn->prepare("SELECT StatusID FROM maintenancerequest WHERE RequestID = ?");
        $s->bind_param("i", $request_id);
        $s->execute();
        $old_status_id = $s->get_result()->fetch_assoc()['StatusID'];

        $u = $conn->prepare("UPDATE maintenancerequest SET StatusID = 6, UpdatedAt = NOW() WHERE RequestID = ?");
        $u->bind_param("i", $request_id);
        $u->execute();

        $h = $conn->prepare("INSERT INTO statushistory (RequestID, OldStatusID, NewStatusID, ChangedBy, ChangedAt) VALUES (?, ?, 6, ?, NOW())");
        $h->bind_param("iii", $request_id, $old_status_id, $admin_id);
        $h->execute();

        // Notify requester
        $ri = $conn->prepare("SELECT UserID FROM maintenancerequest WHERE RequestID = ?");
        $ri->bind_param("i", $request_id);
        $ri->execute();
        $requester_id = $ri->get_result()->fetch_assoc()['UserID'];

        $notif_msg = "Your request #$request_id has been cancelled by admin.";
        $n = $conn->prepare("INSERT INTO notification (UserID, Title, Message, RequestID, CreatedAt) VALUES (?, 'Request Cancelled', ?, ?, NOW())");
        $n->bind_param("isi", $requester_id, $notif_msg, $request_id);
        $n->execute();

        logAuditAction($conn, $admin_id, 'REQUEST_CANCELLED', 'maintenancerequest', $request_id, null, 'Cancelled by admin');

        header("Location: request-details.php?id=$request_id&success=cancelled");
        exit();
    }
}

// Success messages from redirect
if (isset($_GET['success'])) {
    $msgs = [
        'status'    => '✅ Status updated successfully.',
        'assigned'  => '✅ Technician assigned successfully.',
        'cancelled' => '✅ Request cancelled.',
    ];
    $success_msg = $msgs[$_GET['success']] ?? '';
}

// ============================================================
// GET DATA
// ============================================================

// Request details
$sql = "SELECT 
            mr.RequestID, mr.Title, mr.Description,
            mr.SubmittedAt, mr.UpdatedAt, mr.CompletedAt, mr.UserID,
            mr.StatusID, mr.PriorityID,
            u.Name as RequesterName, u.Email as RequesterEmail, u.Phone as RequesterPhone,
            r.RoleName as RequesterRole,
            l.BuildingName, l.FloorNumber, l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel, p.Description as PriorityDescription,
            s.StatusName, s.Description as StatusDescription
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN role r ON u.RoleID = r.RoleID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.RequestID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: all-requests.php");
    exit();
}
$request = $result->fetch_assoc();

// Assigned technician
$tech_stmt = $conn->prepare("SELECT u.UserID, u.Name as TechName, u.Email as TechEmail, u.Phone as TechPhone,
                a.AssignmentID, a.AssignedAt, a.StartedAt, a.CompletedAt
             FROM assignment a JOIN user u ON a.TechnicianID = u.UserID
             WHERE a.RequestID = ? ORDER BY a.AssignedAt DESC LIMIT 1");
$tech_stmt->bind_param("i", $request_id);
$tech_stmt->execute();
$tech_result = $tech_stmt->get_result();
$technician = $tech_result->num_rows > 0 ? $tech_result->fetch_assoc() : null;

// All technicians (for assign dropdown)
$all_techs = $conn->query("SELECT UserID, Name, Email FROM user WHERE RoleID = 2 ORDER BY Name")->fetch_all(MYSQLI_ASSOC);

// All statuses
$all_statuses = $conn->query("SELECT StatusID, StatusName FROM status ORDER BY StatusID")->fetch_all(MYSQLI_ASSOC);

// Photos
$photo_stmt = $conn->prepare("SELECT PhotoID, PhotoPath, UploadedAt FROM requestphoto WHERE RequestID = ?");
$photo_stmt->bind_param("i", $request_id);
$photo_stmt->execute();
$photos = $photo_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status history
$history_stmt = $conn->prepare("SELECT sh.ChangedAt, s_old.StatusName as OldStatus, s_new.StatusName as NewStatus, u.Name as ChangedByName
                FROM statushistory sh
                LEFT JOIN status s_old ON sh.OldStatusID = s_old.StatusID
                JOIN status s_new ON sh.NewStatusID = s_new.StatusID
                JOIN user u ON sh.ChangedBy = u.UserID
                WHERE sh.RequestID = ? ORDER BY sh.ChangedAt DESC");
$history_stmt->bind_param("i", $request_id);
$history_stmt->execute();
$history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Feedback
$fb_stmt = $conn->prepare("SELECT f.Rating, f.Comment, f.SubmittedAt, u.Name as UserName
            FROM feedback f JOIN user u ON f.UserID = u.UserID WHERE f.RequestID = ?");
$fb_stmt->bind_param("i", $request_id);
$fb_stmt->execute();
$fb_result = $fb_stmt->get_result();
$feedback = $fb_result->num_rows > 0 ? $fb_result->fetch_assoc() : null;

$current_page = 'all-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request_id; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .detail-card {
            background: white;
            padding: 1.75rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        .detail-card h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f1f5f9;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 1rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f8fafc;
        }
        .detail-label { font-weight: 600; color: #64748b; font-size: 0.875rem; }
        .detail-value { color: #1e293b; font-size: 0.875rem; }
        .detail-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        /* Admin action cards */
        .action-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
        .action-card h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        .action-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .action-row select, .action-row .form-input {
            flex: 1;
            min-width: 180px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
        }

        /* Timeline */
        .timeline { position: relative; padding-left: 1.75rem; }
        .timeline-item { position: relative; padding-bottom: 1.5rem; }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -1.75rem;
            top: 4px;
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #2563eb;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #2563eb;
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -1.43rem;
            top: 14px;
            width: 2px;
            height: calc(100% - 14px);
            background: #e2e8f0;
        }
        .timeline-item:last-child:after { display: none; }
        .timeline-date { font-size: 0.78rem; color: #94a3b8; margin-bottom: 0.25rem; }
        .timeline-content { background: white; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.85rem; }
        .timeline-user { font-size: 0.78rem; color: #64748b; margin-top: 0.35rem; }

        /* Photos */
        .photos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; margin-top: 0.75rem; }
        .photo-item { border-radius: 0.5rem; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .photo-item img { width: 100%; height: 160px; object-fit: cover; cursor: pointer; transition: transform 0.2s; }
        .photo-item img:hover { transform: scale(1.04); }

        /* Alert messages */
        .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 500; }
        .alert-error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 500; }

        /* Danger zone */
        .danger-zone { border: 2px solid #fca5a5; border-radius: 0.5rem; padding: 1.25rem; background: #fff5f5; }
        .danger-zone h3 { color: #dc2626; font-size: 0.9rem; font-weight: 700; margin-bottom: 0.5rem; }

        @media (max-width: 768px) {
            .detail-row { grid-template-columns: 1fr; gap: 0.25rem; }
            .action-row { flex-direction: column; align-items: stretch; }
            .action-row select, .action-row .form-input { min-width: unset; }
        }
    </style>
</head>
<body class="has-sidebar">

    <!-- SIDEBAR -->
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
            <a href="dashboard.php" class="sidebar-link"><span class="sidebar-icon">📊</span><span>Dashboard</span></a>
            <a href="all-requests.php" class="sidebar-link active"><span class="sidebar-icon">📋</span><span>All Requests</span></a>
            <a href="users.php" class="sidebar-link"><span class="sidebar-icon">👥</span><span>Manage Users</span></a>
            <div class="sidebar-section-label">Management</div>
            <a href="locations.php" class="sidebar-link"><span class="sidebar-icon">📍</span><span>Locations</span></a>
            <a href="reports.php" class="sidebar-link"><span class="sidebar-icon">📈</span><span>Reports</span></a>
            <a href="all-feedback.php" class="sidebar-link"><span class="sidebar-icon">⭐</span><span>Feedback</span></a>
            <a href="audit-logs.php" class="sidebar-link"><span class="sidebar-icon">🔍</span><span>Audit Logs</span></a>
            <a href="backup.php" class="sidebar-link"><span class="sidebar-icon">💾</span><span>Backup</span></a>
            <div class="sidebar-divider"></div>
            <a href="../auth/logout.php" class="sidebar-link sidebar-logout"><span class="sidebar-icon">🚪</span><span>Logout</span></a>
        </nav>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="topbar">
            <button class="hamburger" id="hamburgerBtn">☰</button>
            <div class="topbar-logo"><span>🔧</span><span>FixPoint</span></div>
            <div class="topbar-notif"><?php include '../includes/notification-bell.php'; ?></div>
        </div>

        <div class="dashboard-container">

            <!-- Back + Title -->
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
                <a href="all-requests.php" class="btn btn-outline">← Back</a>
                <div>
                    <div class="detail-title">
                        Request #<?php echo $request['RequestID']; ?>: <?php echo e($request['Title']); ?>
                    </div>
                    <div style="color:#64748b; font-size:0.85rem;">
                        Submitted by <strong><?php echo e($request['RequesterName']); ?></strong>
                        (<?php echo e($request['RequesterRole']); ?>) —
                        <?php echo formatDate($request['SubmittedAt'], 'M d, Y H:i'); ?>
                    </div>
                </div>
                <div style="margin-left:auto;">
                    <span class="status-badge <?php echo getStatusBadgeClass($request['StatusName']); ?>" style="font-size:0.95rem; padding:0.4rem 1rem;">
                        <?php echo e($request['StatusName']); ?>
                    </span>
                    <span class="priority-badge <?php echo getPriorityBadgeClass($request['PriorityLevel']); ?>" style="font-size:0.95rem; padding:0.4rem 1rem; margin-left:0.5rem;">
                        <?php echo e($request['PriorityLevel']); ?>
                    </span>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 340px; gap:1.5rem; align-items:start;">

                <!-- LEFT COLUMN: Info + History -->
                <div>

                    <!-- Request Info -->
                    <div class="detail-card">
                        <h2>📋 Request Information</h2>
                        <div class="detail-row">
                            <div class="detail-label">📍 Location</div>
                            <div class="detail-value">
                                <strong><?php echo e($request['BuildingName']); ?></strong> —
                                Floor <?php echo e($request['FloorNumber']); ?>, Room <?php echo e($request['RoomNumber']); ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📂 Category</div>
                            <div class="detail-value"><?php echo e($request['CategoryName']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">⚡ Priority</div>
                            <div class="detail-value">
                                <span class="priority-badge <?php echo getPriorityBadgeClass($request['PriorityLevel']); ?>">
                                    <?php echo e($request['PriorityLevel']); ?>
                                </span>
                                <?php if ($request['PriorityDescription']): ?>
                                    <span style="color:#64748b; margin-left:0.5rem;"><?php echo e($request['PriorityDescription']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📝 Description</div>
                            <div class="detail-value" style="white-space:pre-line;"><?php echo e($request['Description']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">👤 Requester</div>
                            <div class="detail-value">
                                <?php echo e($request['RequesterName']); ?> (<?php echo e($request['RequesterRole']); ?>)<br>
                                <small style="color:#64748b;">
                                    <?php echo e($request['RequesterEmail']); ?>
                                    <?php if ($request['RequesterPhone']): ?> · <?php echo e($request['RequesterPhone']); ?><?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($request['CompletedAt']): ?>
                        <div class="detail-row">
                            <div class="detail-label">✅ Completed</div>
                            <div class="detail-value"><?php echo formatDate($request['CompletedAt'], 'M d, Y H:i'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Assigned Technician -->
                    <?php if ($technician): ?>
                    <div class="detail-card">
                        <h2>👨‍🔧 Assigned Technician</h2>
                        <div class="detail-row">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><strong><?php echo e($technician['TechName']); ?></strong></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo e($technician['TechEmail']); ?></div>
                        </div>
                        <?php if ($technician['TechPhone']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo e($technician['TechPhone']); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <div class="detail-label">Assigned On</div>
                            <div class="detail-value"><?php echo formatDate($technician['AssignedAt'], 'M d, Y H:i'); ?></div>
                        </div>
                        <?php if ($technician['StartedAt']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Started</div>
                            <div class="detail-value"><?php echo formatDate($technician['StartedAt'], 'M d, Y H:i'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($technician['CompletedAt']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Completed</div>
                            <div class="detail-value"><?php echo formatDate($technician['CompletedAt'], 'M d, Y H:i'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Photos -->
                    <?php if (count($photos) > 0): ?>
                    <div class="detail-card">
                        <h2>📷 Photos (<?php echo count($photos); ?>)</h2>
                        <div class="photos-grid">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo e($photo['PhotoPath']); ?>" alt="photo"
                                         onclick="window.open('<?php echo e($photo['PhotoPath']); ?>', '_blank')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="color:#94a3b8; font-size:0.8rem; margin-top:0.75rem;">💡 Click to view full size</p>
                    </div>
                    <?php endif; ?>

                    <!-- Feedback (if any) -->
                    <?php if ($feedback): ?>
                    <div class="detail-card">
                        <h2>⭐ User Feedback</h2>
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                            <strong style="color:#1e293b;">Rating:</strong>
                            <span style="color:#fbbf24; font-size:1.25rem;">
                                <?php for ($i=1;$i<=5;$i++) echo $i<=$feedback['Rating']?'⭐':'☆'; ?>
                            </span>
                            <span style="color:#64748b; font-size:0.85rem;">(<?php echo $feedback['Rating']; ?>/5)</span>
                        </div>
                        <?php if ($feedback['Comment']): ?>
                        <div style="background:#f8fafc; padding:0.75rem 1rem; border-radius:0.5rem; border-left:3px solid #2563eb; color:#1e293b; font-size:0.875rem; white-space:pre-line;">
                            "<?php echo e($feedback['Comment']); ?>"
                        </div>
                        <?php endif; ?>
                        <div style="color:#94a3b8; font-size:0.8rem; margin-top:0.75rem;">
                            Submitted by <?php echo e($feedback['UserName']); ?> on <?php echo formatDate($feedback['SubmittedAt'], 'M d, Y'); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status History -->
                    <?php if (count($history) > 0): ?>
                    <div class="detail-card">
                        <h2>📊 Status History</h2>
                        <div class="timeline">
                            <?php foreach ($history as $item): ?>
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo formatDate($item['ChangedAt'], 'M d, Y — H:i'); ?></div>
                                <div class="timeline-content">
                                    <?php if ($item['OldStatus']): ?>
                                        <span class="status-badge <?php echo getStatusBadgeClass($item['OldStatus']); ?>"><?php echo e($item['OldStatus']); ?></span>
                                        → <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>"><?php echo e($item['NewStatus']); ?></span>
                                    <?php else: ?>
                                        Request submitted as <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>"><?php echo e($item['NewStatus']); ?></span>
                                    <?php endif; ?>
                                    <div class="timeline-user">by <?php echo e($item['ChangedByName']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- RIGHT COLUMN: Admin Actions -->
                <div>

                    <!-- Change Status -->
                    <?php if ($request['StatusID'] != 6): ?>
                    <div class="detail-card">
                        <h2>⚡ Change Status</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <div class="action-card" style="margin-bottom:0;">
                                <div class="action-row">
                                    <select name="new_status_id" required>
                                        <?php foreach ($all_statuses as $st): ?>
                                            <option value="<?php echo $st['StatusID']; ?>"
                                                <?php echo ($st['StatusID'] == $request['StatusID']) ? 'selected' : ''; ?>>
                                                <?php echo e($st['StatusName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Assign Technician -->
                    <?php if ($request['StatusID'] < 5): ?>
                    <div class="detail-card">
                        <h2>👨‍🔧 Assign Technician</h2>
                        <?php if ($technician): ?>
                            <div style="background:#d1fae5; padding:0.75rem; border-radius:0.5rem; margin-bottom:1rem; font-size:0.875rem;">
                                ✅ Currently: <strong><?php echo e($technician['TechName']); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (count($all_techs) > 0): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="assign_tech">
                            <div class="action-card" style="margin-bottom:0;">
                                <div class="action-row">
                                    <select name="technician_id" required>
                                        <option value="">— Select Technician —</option>
                                        <?php foreach ($all_techs as $tech): ?>
                                            <option value="<?php echo $tech['UserID']; ?>"
                                                <?php echo ($technician && $tech['UserID'] == $technician['UserID']) ? 'selected' : ''; ?>>
                                                <?php echo e($tech['Name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Assign</button>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                            <p style="color:#94a3b8; font-size:0.875rem;">No technicians available.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Cancel Request -->
                    <?php if ($request['StatusID'] != 5 && $request['StatusID'] != 6): ?>
                    <div class="detail-card">
                        <div class="danger-zone">
                            <h3>⚠️ Cancel Request</h3>
                            <p style="color:#64748b; font-size:0.825rem; margin-bottom:1rem;">
                                This will notify the user and cannot be undone easily.
                            </p>
                            <form method="POST" onsubmit="return confirm('Cancel this request?');">
                                <input type="hidden" name="action" value="cancel_request">
                                <button type="submit" class="btn" style="background:#ef4444; color:white; width:100%;">
                                    ❌ Cancel Request
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

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

        // Auto-hide success/error messages after 4s
        setTimeout(() => {
            document.querySelectorAll('.alert-success, .alert-error').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 4000);
    </script>
</body>
</html>