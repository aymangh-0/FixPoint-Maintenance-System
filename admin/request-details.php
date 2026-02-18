<?php
/**
 * FixPoint - Admin Request Details
 * View and manage maintenance requests - assign technicians, update status
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
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Assign Technician
    if (isset($_POST['assign_technician'])) {
        $technician_id = (int)$_POST['technician_id'];
        
        if ($technician_id > 0) {
            // Check if already assigned
            $check_sql = "SELECT AssignmentID FROM assignment WHERE RequestID = ? AND TechnicianID = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $request_id, $technician_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert assignment
                $assign_sql = "INSERT INTO assignment (RequestID, TechnicianID, AdminID) VALUES (?, ?, ?)";
                $assign_stmt = $conn->prepare($assign_sql);
                $assign_stmt->bind_param("iii", $request_id, $technician_id, $admin_id);
                
                if ($assign_stmt->execute()) {
                    // Update request status to "Assigned" (StatusID = 3)
                    $update_sql = "UPDATE maintenancerequest SET StatusID = 3 WHERE RequestID = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $request_id);
                    $update_stmt->execute();
                    
                    // Log status change
                    logStatusChange($conn, $request_id, 1, 3, $admin_id);
                    
                    // Get requester and technician info for notifications
                    $req_sql = "SELECT UserID FROM maintenancerequest WHERE RequestID = ?";
                    $req_stmt = $conn->prepare($req_sql);
                    $req_stmt->bind_param("i", $request_id);
                    $req_stmt->execute();
                    $requester_id = $req_stmt->get_result()->fetch_assoc()['UserID'];
                    
                    // Notify requester
                    createNotification($conn, $requester_id, "Your request #$request_id has been assigned to a technician", $request_id);
                    
                    // Notify technician
                    createNotification($conn, $technician_id, "New maintenance request #$request_id has been assigned to you", $request_id);
                    require_once '../config/audit-logger.php';
                    logTechnicianAssignment($conn, $admin_id, $request_id, $technician_id);
                    
                    // Send email notifications
                    require_once '../config/email-service.php';
                    emailTechnicianAssigned($conn, $request_id, $technician_id);
                    emailStatusUpdate($conn, $request_id, $requester_id, 'Assigned');
                    $success = "Technician assigned successfully!";
                } else {
                    $error = "Failed to assign technician.";
                }
            } else {
                $error = "This technician is already assigned to this request.";
            }
        } else {
            $error = "Please select a technician.";
        }
    }
    
    // Update Status
    if (isset($_POST['update_status'])) {
        $new_status_id = (int)$_POST['status_id'];
        
        // Get current status
        $current_sql = "SELECT StatusID FROM maintenancerequest WHERE RequestID = ?";
        $current_stmt = $conn->prepare($current_sql);
        $current_stmt->bind_param("i", $request_id);
        $current_stmt->execute();
        $old_status_id = $current_stmt->get_result()->fetch_assoc()['StatusID'];
        
        if ($new_status_id != $old_status_id) {
            // Update status
            $update_sql = "UPDATE maintenancerequest SET StatusID = ? WHERE RequestID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_status_id, $request_id);
            
            if ($update_stmt->execute()) {
                // If marking as completed, set CompletedAt
                if ($new_status_id == 5) {
                    $complete_sql = "UPDATE maintenancerequest SET CompletedAt = NOW() WHERE RequestID = ?";
                    $complete_stmt = $conn->prepare($complete_sql);
                    $complete_stmt->bind_param("i", $request_id);
                    $complete_stmt->execute();
                }
                
                // Log status change
                logStatusChange($conn, $request_id, $old_status_id, $new_status_id, $admin_id);
                require_once '../config/audit-logger.php';
                logStatusChangeAudit($conn, $admin_id, $request_id, $old_status_name, $new_status_name);
                
                // Notify requester
                $req_sql = "SELECT UserID FROM maintenancerequest WHERE RequestID = ?";
                $req_stmt = $conn->prepare($req_sql);
                $req_stmt->bind_param("i", $request_id);
                $req_stmt->execute();
                $requester_id = $req_stmt->get_result()->fetch_assoc()['UserID'];
                
                $status_name_sql = "SELECT StatusName FROM status WHERE StatusID = ?";
                $status_stmt = $conn->prepare($status_name_sql);
                $status_stmt->bind_param("i", $new_status_id);
                $status_stmt->execute();
                $status_name = $status_stmt->get_result()->fetch_assoc()['StatusName'];
                
                createNotification($conn, $requester_id, "Your request #$request_id status changed to: $status_name", $request_id);

                require_once '../config/email-service.php';
                emailStatusUpdate($conn, $request_id, $requester_id, $new_status_name);
                
                $success = "Status updated successfully!";
            } else {
                $error = "Failed to update status.";
            }
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
    header("Location: all-requests.php");
    exit();
}

$request = $result->fetch_assoc();

// Get assigned technician (if any)
$tech_sql = "SELECT 
                u.UserID,
                u.Name as TechName,
                u.Email as TechEmail,
                u.Phone as TechPhone,
                a.AssignedAt,
                a.StartedAt,
                a.CompletedAt
             FROM assignment a
             JOIN user u ON a.TechnicianID = u.UserID
             WHERE a.RequestID = ?
             ORDER BY a.AssignedAt DESC
             LIMIT 1";

$tech_stmt = $conn->prepare($tech_sql);
$tech_stmt->bind_param("i", $request_id);
$tech_stmt->execute();
$tech_result = $tech_stmt->get_result();
$technician = $tech_result->num_rows > 0 ? $tech_result->fetch_assoc() : null;

// Get all technicians for dropdown
$technicians = getAllTechnicians($conn);

// Get all statuses for dropdown
$statuses = getAllStatuses($conn);

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

// Get feedback if exists
$feedback_sql = "SELECT f.Rating, f.Comment, f.SubmittedAt, u.Name as UserName 
                 FROM feedback f
                 JOIN user u ON f.UserID = u.UserID
                 WHERE f.RequestID = ?";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("i", $request_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedback = $feedback_result->num_rows > 0 ? $feedback_result->fetch_assoc() : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Request #<?php echo $request_id; ?> - Admin</title>
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
        
        .admin-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .action-box {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 2px solid #e2e8f0;
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
            .admin-actions {
                grid-template-columns: 1fr;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .detail-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
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
            
            <!-- Back Button -->
            <div style="margin-bottom: 1rem;">
                <a href="all-requests.php" class="btn btn-outline">← Back to All Requests</a>
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

            <!-- Request Header -->
            <div class="detail-card">
                <div class="detail-header">
                    <div>
                        <div class="detail-title">
                            Request #<?php echo $request['RequestID']; ?>: <?php echo e($request['Title']); ?>
                        </div>
                        <div style="color: #64748b; margin-top: 0.5rem;">
                            Submitted <?php echo formatDate($request['SubmittedAt'], 'M d, Y - H:i'); ?>
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
                        <br>
                        📧 <?php echo e($request['RequesterEmail']); ?>
                        <?php if ($request['RequesterPhone']): ?>
                            <br>📞 <?php echo e($request['RequesterPhone']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">📍 Location</div>
                    <div class="detail-value">
                        <strong><?php echo e($request['BuildingName']); ?></strong> - 
                        Floor: <?php echo e($request['FloorNumber']); ?>, 
                        Room: <?php echo e($request['RoomNumber']); ?>
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
                        - <?php echo e($request['PriorityDescription']); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">📝 Description</div>
                    <div class="detail-value" style="white-space: pre-line;"><?php echo e($request['Description']); ?></div>
                </div>

                <?php if ($request['CompletedAt']): ?>
                <div class="detail-row">
                    <div class="detail-label">✅ Completed At</div>
                    <div class="detail-value"><?php echo formatDate($request['CompletedAt'], 'M d, Y - H:i'); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Admin Actions -->
            <div class="detail-card">
                <h2 class="section-title">⚙️ Admin Actions</h2>
                
                <div class="admin-actions">
                    <!-- Assign Technician -->
                    <div class="action-box">
                        <h3 class="action-title">👨‍🔧 Assign Technician</h3>
                        
                        <?php if ($technician): ?>
                            <div style="background: #d1fae5; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                                <strong>✅ Currently Assigned:</strong>
                                <br><?php echo e($technician['TechName']); ?>
                                <br><small style="color: #065f46;">Assigned on <?php echo formatDate($technician['AssignedAt'], 'M d, Y'); ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="technician_id" class="form-label">Select Technician</label>
                                <select name="technician_id" id="technician_id" class="form-input" required>
                                    <option value="">-- Choose Technician --</option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['UserID']; ?>">
                                            <?php echo e($tech['Name']); ?> (<?php echo e($tech['Email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="assign_technician" class="btn-submit">
                                Assign Technician
                            </button>
                        </form>
                    </div>
                    
                    <!-- Update Status -->
                    <div class="action-box">
                        <h3 class="action-title">📊 Update Status</h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="status_id" class="form-label">Change Status</label>
                                <select name="status_id" id="status_id" class="form-input" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['StatusID']; ?>"
                                            <?php echo ($status['StatusID'] == $request['StatusID']) ? 'selected' : ''; ?>>
                                            <?php echo e($status['StatusName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_status" class="btn-submit">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Assigned Technician Details -->
            <?php if ($technician): ?>
            <div class="detail-card">
                <h2 class="section-title">👨‍🔧 Assigned Technician</h2>
                
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
                    <div class="detail-value"><?php echo formatDate($technician['AssignedAt'], 'M d, Y - H:i'); ?></div>
                </div>

                <?php if ($technician['StartedAt']): ?>
                <div class="detail-row">
                    <div class="detail-label">Started Working</div>
                    <div class="detail-value"><?php echo formatDate($technician['StartedAt'], 'M d, Y - H:i'); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($technician['CompletedAt']): ?>
                <div class="detail-row">
                    <div class="detail-label">Completed</div>
                    <div class="detail-value"><?php echo formatDate($technician['CompletedAt'], 'M d, Y - H:i'); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- User Feedback -->
            <?php if ($feedback): ?>
            <div class="detail-card">
                <h2 class="section-title">⭐ User Feedback</h2>
                
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.75rem;">
                    <div class="detail-row">
                        <div class="detail-label">Submitted By</div>
                        <div class="detail-value"><strong><?php echo e($feedback['UserName']); ?></strong></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Rating</div>
                        <div class="detail-value">
                            <div style="color: #fbbf24; font-size: 1.5rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $feedback['Rating'] ? '⭐' : '☆'; ?>
                                <?php endfor; ?>
                            </div>
                            <span style="color: #64748b;"><?php echo $feedback['Rating']; ?> out of 5 stars</span>
                        </div>
                    </div>
                    
                    <?php if ($feedback['Comment']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Comment</div>
                        <div class="detail-value">
                            <div style="background: white; padding: 1rem; border-radius: 0.5rem; white-space: pre-line;">
                                <?php echo e($feedback['Comment']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-row">
                        <div class="detail-label">Submitted On</div>
                        <div class="detail-value"><?php echo formatDate($feedback['SubmittedAt'], 'M d, Y - H:i'); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Photos -->
            <?php if (count($photos) > 0): ?>
            <div class="detail-card">
                <h2 class="section-title">📷 Photos (<?php echo count($photos); ?>)</h2>
                
                <div class="photos-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-item">
                            <img src="<?php echo e($photo['PhotoPath']); ?>" 
                                 alt="Request photo" 
                                 onclick="window.open('<?php echo e($photo['PhotoPath']); ?>', '_blank')">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p style="color: #64748b; font-size: 0.875rem; margin-top: 1rem;">
                    💡 Click on any photo to view it in full size
                </p>
            </div>
            <?php endif; ?>

            <!-- Status History -->
            <?php if (count($history) > 0): ?>
            <div class="detail-card">
                <h2 class="section-title">📊 Status History</h2>
                
                <div class="timeline">
                    <?php foreach ($history as $item): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo formatDate($item['ChangedAt'], 'M d, Y - H:i A'); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">
                                    <?php if ($item['OldStatus']): ?>
                                        Status changed from 
                                        <span class="status-badge <?php echo getStatusBadgeClass($item['OldStatus']); ?>">
                                            <?php echo e($item['OldStatus']); ?>
                                        </span>
                                        to
                                        <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>">
                                            <?php echo e($item['NewStatus']); ?>
                                        </span>
                                    <?php else: ?>
                                        Request submitted with status 
                                        <span class="status-badge <?php echo getStatusBadgeClass($item['NewStatus']); ?>">
                                            <?php echo e($item['NewStatus']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-user">
                                    by <?php echo e($item['ChangedByName']); ?> (<?php echo e($item['ChangedByRole']); ?>)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>