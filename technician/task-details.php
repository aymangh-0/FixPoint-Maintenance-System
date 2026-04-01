<?php
/**
 * FixPoint - Task Details (Technician View)
 * View task details and update status
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
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Change Priority
    if (isset($_POST['change_priority'])) {
        $new_priority_id = (int)$_POST['new_priority_id'];
        
        $old_p = $conn->prepare("SELECT PriorityID FROM maintenancerequest WHERE RequestID = ?");
        $old_p->bind_param("i", $request_id);
        $old_p->execute();
        $old_priority_id = $old_p->get_result()->fetch_assoc()['PriorityID'];
        
        if ($new_priority_id !== $old_priority_id) {
            $u = $conn->prepare("UPDATE maintenancerequest SET PriorityID = ?, UpdatedAt = NOW() WHERE RequestID = ?");
            $u->bind_param("ii", $new_priority_id, $request_id);
            $u->execute();
            
            require_once __DIR__ . '/../config/audit-logger.php';
            logAuditAction($conn, $tech_id, 'PRIORITY_CHANGED', 'maintenancerequest', $request_id, "PriorityID: $old_priority_id", "PriorityID: $new_priority_id");
        }
        
        $success = "Priority updated successfully.";
    }
    
    // Start Working
    if (isset($_POST['start_work'])) {
        // Update assignment StartedAt
        $sql = "UPDATE assignment SET StartedAt = NOW() WHERE RequestID = ? AND TechnicianID = ? AND StartedAt IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $request_id, $tech_id);
        $stmt->execute();
        
        // Update request status to "In Progress" (StatusID = 4)
        $update_sql = "UPDATE maintenancerequest SET StatusID = 4 WHERE RequestID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $request_id);
        
        if ($update_stmt->execute()) {
            // Log status change
            logStatusChange($conn, $request_id, 3, 4, $tech_id);
            require_once __DIR__ . '/../config/audit-logger.php';
            logStatusChangeAudit($conn, $tech_id, $request_id, 'Assigned', 'In Progress');

            
            // Notify requester
            $req_sql = "SELECT UserID FROM maintenancerequest WHERE RequestID = ?";
            $req_stmt = $conn->prepare($req_sql);
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $requester_id = $req_stmt->get_result()->fetch_assoc()['UserID'];
            
            createNotification($conn, $requester_id, "Your request #$request_id is now being worked on by a technician", $request_id);

            // Send email notification
            require_once __DIR__ . '/../config/email-service.php';
            emailStatusUpdate($conn, $request_id, $requester_id, 'In Progress');
            
            $success = "Work started! Status updated to 'In Progress'.";
        } else {
            $error = "Failed to start work.";
        }
    }
    
    // Mark as Complete
    if (isset($_POST['mark_complete'])) {
        $notes = trim($_POST['completion_notes']);
        
        // Update assignment CompletedAt
        $sql = "UPDATE assignment SET CompletedAt = NOW() WHERE RequestID = ? AND TechnicianID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $request_id, $tech_id);
        $stmt->execute();
        
        // Update request status to "Completed" (StatusID = 5)
        $update_sql = "UPDATE maintenancerequest SET StatusID = 5, CompletedAt = NOW() WHERE RequestID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $request_id);
        
        if ($update_stmt->execute()) {
            // Log status change with notes
            logStatusChange($conn, $request_id, 4, 5, $tech_id);
            require_once __DIR__ . '/../config/audit-logger.php';
            logStatusChangeAudit($conn, $tech_id, $request_id, 'Assigned', 'In Progress');

            
            // Notify requester
            $req_sql = "SELECT UserID FROM maintenancerequest WHERE RequestID = ?";
            $req_stmt = $conn->prepare($req_sql);
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $requester_id = $req_stmt->get_result()->fetch_assoc()['UserID'];
            
            createNotification($conn, $requester_id, "Your request #$request_id has been completed! Please review and provide feedback.", $request_id);

            // Send email notification
            require_once __DIR__ . '/../config/email-service.php';
            emailRequestCompleted($conn, $request_id);
            
            $success = "Task marked as complete! The requester has been notified.";
        } else {
            $error = "Failed to mark as complete.";
        }
    }
}

// Get request details
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            mr.UserID,
            mr.StatusID,
            u.Name as RequesterName,
            u.Email as RequesterEmail,
            u.Phone as RequesterPhone,
            r.RoleName as RequesterRole,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            l.Description as LocationDescription,
            c.CategoryName,
            p.PriorityLevel,
            p.Description as PriorityDescription,
            s.StatusName,
            s.Description as StatusDescription
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
    header("Location: dashboard.php");
    exit();
}

$request = $result->fetch_assoc();

// Check if this technician is assigned to this request
$assignment_sql = "SELECT AssignedAt, StartedAt, CompletedAt 
                   FROM assignment 
                   WHERE RequestID = ? AND TechnicianID = ?";
$assignment_stmt = $conn->prepare($assignment_sql);
$assignment_stmt->bind_param("ii", $request_id, $tech_id);
$assignment_stmt->execute();
$assignment_result = $assignment_stmt->get_result();

if ($assignment_result->num_rows == 0) {
    // Not assigned to this technician
    header("Location: dashboard.php");
    exit();
}

$assignment = $assignment_result->fetch_assoc();

// All priorities (for change priority dropdown)
$all_priorities = $conn->query("SELECT PriorityID, PriorityLevel FROM priority ORDER BY PriorityID")->fetch_all(MYSQLI_ASSOC);

// Get photos
$photo_sql = "SELECT PhotoID, PhotoPath, UploadedAt FROM requestphoto WHERE RequestID = ?";
$photo_stmt = $conn->prepare($photo_sql);
$photo_stmt->bind_param("i", $request_id);
$photo_stmt->execute();
$photos = $photo_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get status history
$history_sql = "SELECT 
                    sh.ChangedAt,
                    s_old.StatusName as OldStatus,
                    s_new.StatusName as NewStatus,
                    u.Name as ChangedByName,
                    r.RoleName as ChangedByRole
                FROM statushistory sh
                LEFT JOIN status s_old ON sh.OldStatusID = s_old.StatusID
                JOIN status s_new ON sh.NewStatusID = s_new.StatusID
                JOIN user u ON sh.ChangedBy = u.UserID
                JOIN role r ON u.RoleID = r.RoleID
                WHERE sh.RequestID = ?
                ORDER BY sh.ChangedAt DESC";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $request_id);
$history_stmt->execute();
$history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$current_page = 'my-tasks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task #<?php echo $request_id; ?> - Technician</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .detail-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .detail-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-label {
            font-weight: 600;
            color: #64748b;
        }
        
        .detail-value {
            color: #1e293b;
        }
        
        .action-box {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 2px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .action-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .photo-item {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .photo-item img:hover {
            transform: scale(1.05);
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563eb;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #2563eb;
        }
        
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -1.7rem;
            top: 12px;
            width: 2px;
            height: calc(100% - 12px);
            background: #e2e8f0;
        }
        
        .timeline-item:last-child:after {
            display: none;
        }
        
        .timeline-date {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .timeline-content {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .timeline-status {
            font-weight: 600;
            color: #1e293b;
        }
        
        .timeline-user {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .detail-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .two-col-layout {
                grid-template-columns: 1fr !important;
            }
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
<a href="profile.php" class="sidebar-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
    <span class="sidebar-icon">👤</span><span>My Profile</span>
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
            
            <!-- Back Button -->
            <div style="margin-bottom: 1rem;">
                <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
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

            <!-- 2-Column Layout like Admin -->
            <div style="display:grid; grid-template-columns: 1fr 340px; gap:1.5rem; align-items:start;">

                <!-- LEFT COLUMN: Info + Photos + History -->
                <div>

                    <!-- Task Info -->
                    <div class="detail-card">
                        <div class="detail-header">
                            <div>
                                <div class="detail-title">
                                    Task #<?php echo $request['RequestID']; ?>: <?php echo e($request['Title']); ?>
                                </div>
                                <div style="color: #64748b; margin-top: 0.5rem;">
                                    Assigned to you on <?php echo formatDate($assignment['AssignedAt'], 'M d, Y - H:i'); ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge <?php echo getStatusBadgeClass($request['StatusName']); ?>" 
                                      style="font-size: 1rem; padding: 0.5rem 1rem;">
                                    <?php echo e($request['StatusName']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">👤 Requester</div>
                            <div class="detail-value">
                                <strong><?php echo e($request['RequesterName']); ?></strong> (<?php echo e($request['RequesterRole']); ?>)
                                <br>📧 <?php echo e($request['RequesterEmail']); ?>
                                <?php if ($request['RequesterPhone']): ?><br>📞 <?php echo e($request['RequesterPhone']); ?><?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📍 Location</div>
                            <div class="detail-value">
                                <strong><?php echo e($request['BuildingName']); ?></strong> - Floor: <?php echo e($request['FloorNumber']); ?>, Room: <?php echo e($request['RoomNumber']); ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📂 Category</div>
                            <div class="detail-value"><?php echo e($request['CategoryName']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">⚡ Priority</div>
                            <div class="detail-value">
                                <span class="priority-badge <?php echo getPriorityBadgeClass($request['PriorityLevel']); ?>"><?php echo e($request['PriorityLevel']); ?></span>
                                - <?php echo e($request['PriorityDescription']); ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📝 Description</div>
                            <div class="detail-value" style="white-space: pre-line;"><?php echo e($request['Description']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">📅 Submitted</div>
                            <div class="detail-value"><?php echo formatDate($request['SubmittedAt'], 'M d, Y - H:i'); ?></div>
                        </div>
                        <?php if ($assignment['StartedAt']): ?>
                        <div class="detail-row">
                            <div class="detail-label">🔧 Started Work</div>
                            <div class="detail-value"><?php echo formatDate($assignment['StartedAt'], 'M d, Y - H:i'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($request['CompletedAt']): ?>
                        <div class="detail-row">
                            <div class="detail-label">✅ Completed</div>
                            <div class="detail-value"><?php echo formatDate($request['CompletedAt'], 'M d, Y - H:i'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Photos -->
                    <?php if (count($photos) > 0): ?>
                    <div class="detail-card">
                        <h2 class="section-title">📷 Photos (<?php echo count($photos); ?>)</h2>
                        <div class="photos-grid">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo e($photo['PhotoPath']); ?>" alt="Request photo" onclick="window.open('<?php echo e($photo['PhotoPath']); ?>', '_blank')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="color: #64748b; font-size: 0.875rem; margin-top: 1rem;">💡 Click on any photo to view it in full size</p>
                    </div>
                    <?php endif; ?>

                    <!-- Status History -->
                    <?php if (count($history) > 0): ?>
                    <div class="detail-card">
                        <h2 class="section-title">📊 Task History</h2>
                        <div class="timeline">
                            <?php foreach ($history as $item): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date"><?php echo formatDate($item['ChangedAt'], 'M d, Y - H:i A'); ?></div>
                                    <div class="timeline-content">
                                        <div class="timeline-status">
                                            <?php if ($item['OldStatus']): ?>
                                                <span class="status-badge <?php echo getStatusBadgeClass($item['OldStatus']); ?>"><?php echo e($item['OldStatus']); ?></span>
                                                → <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>"><?php echo e($item['NewStatus']); ?></span>
                                            <?php else: ?>
                                                Request submitted as <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>"><?php echo e($item['NewStatus']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="timeline-user">by <?php echo e($item['ChangedByName']); ?> (<?php echo e($item['ChangedByRole']); ?>)</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- RIGHT COLUMN: Actions -->
                <div>

                    <!-- Change Priority -->
                    <?php if ($request['StatusID'] != 5 && $request['StatusID'] != 6): ?>
                    <div class="detail-card">
                        <h2 style="font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:1.25rem; padding-bottom:0.75rem; border-bottom:2px solid #f1f5f9;">🎯 Change Priority</h2>
                        <form method="POST">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem; padding:1.25rem;">
                                <select name="new_priority_id" required style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; background:white; margin-bottom:0.75rem;">
                                    <?php foreach ($all_priorities as $pr): ?>
                                        <option value="<?php echo $pr['PriorityID']; ?>"
                                            <?php echo ($pr['PriorityID'] == $request['PriorityID'] ?? 2) ? 'selected' : ''; ?>>
                                            <?php echo e($pr['PriorityLevel']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="change_priority" style="padding:0.5rem 1.25rem; background:#2563eb; color:white; border:none; border-radius:0.375rem; font-size:0.85rem; font-weight:600; cursor:pointer;">Update</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Task Actions -->
                    <?php if ($request['StatusID'] != 5 && $request['StatusID'] != 6): ?>
                    <div class="detail-card">
                        <h2 style="font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:1.25rem; padding-bottom:0.75rem; border-bottom:2px solid #f1f5f9;">🔧 Task Actions</h2>
                        <?php if (!$assignment['StartedAt']): ?>
                            <form method="POST">
                                <button type="submit" name="start_work" class="btn btn-primary" style="width:100%; padding:0.75rem; font-size:0.9rem;">▶️ Start Working</button>
                            </form>
                        <?php elseif (!$assignment['CompletedAt'] && $request['StatusID'] == 4): ?>
                            <form method="POST">
                                <div style="margin-bottom:0.75rem;">
                                    <label style="font-size:0.825rem; font-weight:600; color:#1e293b; display:block; margin-bottom:0.35rem;">Notes <span style="color:#94a3b8; font-weight:400;">(optional)</span></label>
                                    <textarea name="completion_notes" rows="3" placeholder="Notes about the work..." style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.85rem; font-family:inherit; resize:vertical;"></textarea>
                                </div>
                                <button type="submit" name="mark_complete" class="btn btn-success" style="width:100%; padding:0.75rem; font-size:0.9rem;">✅ Mark as Complete</button>
                            </form>
                            <p style="color:#64748b; margin-top:0.75rem; font-size:0.8rem;">💡 The requester will be notified immediately.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
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