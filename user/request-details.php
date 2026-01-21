<?php
/**
 * FixPoint - Request Details (User View)
 * View detailed information about a specific maintenance request
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not a regular user (Student or Faculty)
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 4)) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get request details
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            mr.UserID,
            u.Name as RequesterName,
            u.Email as RequesterEmail,
            u.Phone as RequesterPhone,
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
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.RequestID = ? AND mr.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my-requests.php");
    exit();
}

$request = $result->fetch_assoc();

// Get assigned technician (if any)
$tech_sql = "SELECT 
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
                    u.Name as ChangedByName
                FROM statushistory sh
                LEFT JOIN status s_old ON sh.OldStatusID = s_old.StatusID
                JOIN status s_new ON sh.NewStatusID = s_new.StatusID
                JOIN user u ON sh.ChangedBy = u.UserID
                WHERE sh.RequestID = ?
                ORDER BY sh.ChangedAt DESC";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $request_id);
$history_stmt->execute();
$history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if feedback already submitted
$feedback_sql = "SELECT FeedbackID, Rating, Comment, CreatedAt FROM feedback WHERE RequestID = ? AND UserID = ?";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("ii", $request_id, $user_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedback = $feedback_result->num_rows > 0 ? $feedback_result->fetch_assoc() : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request_id; ?> - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                    <span class="logo-subtitle">SEU</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="my-requests.php" class="nav-link">My Requests</a>
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
                <a href="my-requests.php" class="btn btn-outline">← Back to My Requests</a>
            </div>

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

            <!-- Assigned Technician -->
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

            <!-- Feedback Section -->
            <?php if ($request['StatusName'] == 'Completed'): ?>
            <div class="detail-card">
                <h2 class="section-title">⭐ Your Feedback</h2>
                
                <?php if ($feedback): ?>
                    <!-- Display Submitted Feedback -->
                    <div style="background: #d1fae5; padding: 1.5rem; border-radius: 0.75rem; border: 2px solid #a7f3d0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <strong style="color: #065f46;">Your Rating:</strong>
                            <div style="color: #fbbf24; font-size: 1.5rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $feedback['Rating'] ? '⭐' : '☆'; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <?php if ($feedback['Comment']): ?>
                        <div style="background: white; padding: 1rem; border-radius: 0.5rem;">
                            <strong style="color: #1e293b;">Your Comment:</strong>
                            <p style="color: #64748b; margin-top: 0.5rem; white-space: pre-line;">
                                <?php echo e($feedback['Comment']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div style="color: #065f46; font-size: 0.875rem; margin-top: 1rem;">
                            ✅ Feedback submitted on <?php echo formatDate($feedback['CreatedAt'], 'M d, Y - H:i'); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Feedback Not Submitted -->
                    <div style="background: #fef3c7; padding: 1.5rem; border-radius: 0.75rem; border: 2px solid #fde68a; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">💬</div>
                        <p style="color: #92400e; margin-bottom: 1.5rem;">
                            This request has been completed. We'd love to hear your feedback!
                        </p>
                        <a href="submit-feedback.php?id=<?php echo $request['RequestID']; ?>" class="btn btn-primary btn-large">
                            ⭐ Submit Feedback
                        </a>
                    </div>
                <?php endif; ?>
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
                                    by <?php echo e($item['ChangedByName']); ?>
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