<?php
/**
 * FixPoint - Request Details (User View)
 * View detailed information about a specific maintenance request
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

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

// Check if request is editable (within 10 minutes of submission AND still Pending or Assigned)
// Use database time to avoid timezone mismatch between PHP and MySQL
$time_sql = "SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) as diff";
$time_stmt = $conn->prepare($time_sql);
$time_stmt->bind_param("s", $request['SubmittedAt']);
$time_stmt->execute();
$time_diff = $time_stmt->get_result()->fetch_assoc()['diff'];
$edit_window = 10 * 60; // 10 minutes in seconds
$editable_statuses = ['Pending', 'Reviewed', 'Assigned'];
$can_edit = ($time_diff < $edit_window) && in_array($request['StatusName'], $editable_statuses);
$remaining_seconds = max(0, $edit_window - $time_diff);

// Fetch locations and categories for edit form
$locations = [];
$categories = [];
if ($can_edit) {
    $loc_sql = "SELECT LocationID, BuildingName, FloorNumber, RoomNumber FROM location ORDER BY BuildingName, FloorNumber, RoomNumber";
    $locations = $conn->query($loc_sql)->fetch_all(MYSQLI_ASSOC);
    
    $cat_sql = "SELECT CategoryID, CategoryName FROM category ORDER BY CASE WHEN LOWER(CategoryName) IN ('other','others','أخرى') THEN 1 ELSE 0 END, CategoryName";
    $categories = $conn->query($cat_sql)->fetch_all(MYSQLI_ASSOC);
}

// Handle edit form submission
$edit_success = '';
$edit_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_request']) && $can_edit) {
    $new_location_id = (int)$_POST['location_id'];
    $new_category_id = (int)$_POST['category_id'];
    $new_other_category = trim($_POST['other_category'] ?? '');
    $new_description = trim($_POST['description'] ?? '');
    
    // Validate
    if ($new_location_id == 0) {
        $edit_error = "Please select a location.";
    } elseif ($new_category_id == 0) {
        $edit_error = "Please select a category.";
    } else {
        // Check if "Other" category needs specification
        $cat_check = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = ?");
        $cat_check->bind_param("i", $new_category_id);
        $cat_check->execute();
        $cat_name = $cat_check->get_result()->fetch_assoc()['CategoryName'] ?? '';
        $cat_lower = strtolower(trim($cat_name));
        if (($cat_lower === 'other' || $cat_lower === 'others' || $cat_lower === 'أخرى') && empty($new_other_category)) {
            $edit_error = "Please specify the issue type.";
        }
    }
    
    if (empty($edit_error)) {
        // Build description
        $desc_parts = [];
        if ($new_other_category) $desc_parts[] = "Category: " . $new_other_category;
        if ($new_description) $desc_parts[] = $new_description;
        $final_desc = !empty($desc_parts) ? implode(' — ', $desc_parts) : 'No description provided';
        
        // Update the request
        $update_sql = "UPDATE maintenancerequest SET LocationID = ?, CategoryID = ?, Description = ?, UpdatedAt = NOW() WHERE RequestID = ? AND UserID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iisii", $new_location_id, $new_category_id, $final_desc, $request_id, $user_id);
        
        if ($update_stmt->execute()) {
            // Handle new photo upload (optional — replaces existing)
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 20 * 1024 * 1024;
                if (in_array($_FILES['photo']['type'], $allowed) && $_FILES['photo']['size'] <= $max_size) {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'request_' . $request_id . '_edit_' . time() . '.' . $ext;
                    $upload_dir = __DIR__ . '/../uploads/requests/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $filepath = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                        $photo_path = '../uploads/requests/' . $filename;
                        $photo_sql = "INSERT INTO requestphoto (RequestID, PhotoPath, UploadedAt) VALUES (?, ?, NOW())";
                        $photo_stmt = $conn->prepare($photo_sql);
                        $photo_stmt->bind_param("is", $request_id, $photo_path);
                        $photo_stmt->execute();
                    }
                }
            }
            
            $edit_success = "Request updated successfully.";
            // Refresh page to show updated data
            header("Location: request-details.php?id=" . $request_id . "&edited=1");
            exit();
        } else {
            $edit_error = "Failed to update request. Please try again.";
        }
    }
}

// Check for edit success message from redirect
if (isset($_GET['edited']) && $_GET['edited'] == 1) {
    $edit_success = "Request updated successfully.";
    // Re-fetch request data after edit
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    // Recalculate edit window using database time
    $time_stmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) as diff");
    $time_stmt->bind_param("s", $request['SubmittedAt']);
    $time_stmt->execute();
    $time_diff = $time_stmt->get_result()->fetch_assoc()['diff'];
    $can_edit = ($time_diff < $edit_window) && in_array($request['StatusName'], $editable_statuses);
    $remaining_seconds = max(0, $edit_window - $time_diff);
}

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
$feedback_sql = "SELECT FeedbackID, Rating, Comment, SubmittedAt FROM feedback WHERE RequestID = ? AND UserID = ?";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("ii", $request_id, $user_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedback = $feedback_result->num_rows > 0 ? $feedback_result->fetch_assoc() : null;


$current_page = 'my-requests';
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
<a href="profile.php" class="sidebar-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
    <span class="sidebar-icon">👤</span><span>My Profile</span>
</a>
<a href="../auth/logout.php" class="sidebar-link sidebar-logout"></a>
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

            <!-- Edit Success/Error Messages -->
            <?php if ($edit_success): ?>
                <div style="background: #d1fae5; border: 2px solid #a7f3d0; padding: 1rem 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; color: #065f46; font-weight: 600;">
                    ✅ <?php echo $edit_success; ?>
                </div>
            <?php endif; ?>
            <?php if ($edit_error): ?>
                <div style="background: #fee2e2; border: 2px solid #fecaca; padding: 1rem 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; color: #991b1b; font-weight: 600;">
                    ❌ <?php echo $edit_error; ?>
                </div>
            <?php endif; ?>

            <!-- Edit Request Section (within 10 minutes) -->
            <?php if ($can_edit): ?>
            <div class="detail-card" style="border: 2px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 class="section-title" style="margin-bottom: 0;">✏️ Edit Request</h2>
                    <div style="background: #eff6ff; padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.85rem; color: #1d4ed8; font-weight: 600;">
                        ⏱️ <span id="edit-countdown"><?php echo floor($remaining_seconds / 60) . ':' . str_pad($remaining_seconds % 60, 2, '0', STR_PAD_LEFT); ?></span> remaining
                    </div>
                </div>
                <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem;">
                    You can edit this request within 10 minutes of submission while it has not been started by a technician.
                </p>

                <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="edit_request" value="1">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">📍 Location *</label>
                            <select name="location_id" class="form-input" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo $loc['LocationID']; ?>" 
                                        <?php echo ($loc['BuildingName'] == $request['BuildingName'] && $loc['FloorNumber'] == $request['FloorNumber'] && $loc['RoomNumber'] == $request['RoomNumber']) ? 'selected' : ''; ?>>
                                        <?php echo e($loc['BuildingName'] . ' - Floor ' . $loc['FloorNumber'] . ' - Room ' . $loc['RoomNumber']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">📂 Category *</label>
                            <select name="category_id" id="editCategory" class="form-input" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;" onchange="toggleEditOther()">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['CategoryID']; ?>"
                                        <?php echo ($cat['CategoryName'] == $request['CategoryName']) ? 'selected' : ''; ?>>
                                        <?php echo e($cat['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="editOtherField" style="margin-bottom: 1rem; display: none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Please specify *</label>
                        <input type="text" name="other_category" class="form-input" placeholder="Describe the issue type..." 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">📝 Description (optional)</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Describe the issue..."
                                  style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; resize: vertical;"><?php echo e($request['Description']); ?></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">📷 Add Another Photo (optional)</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif" class="form-input"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                        <p style="color: #64748b; font-size: 0.8rem; margin-top: 0.25rem;">Max 20MB. JPEG, PNG, or GIF. This will add a new photo, not replace existing ones.</p>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                            💾 Save Changes
                        </button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('editForm').parentElement.style.display='none';">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
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
                            ✅ Feedback submitted on <?php echo formatDate($feedback['SubmittedAt'], 'M d, Y - H:i'); ?>
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

        // Edit countdown timer
        <?php if ($can_edit): ?>
        (function() {
            let remaining = <?php echo $remaining_seconds; ?>;
            const countdownEl = document.getElementById('edit-countdown');
            const editForm = document.getElementById('editForm');
            const submitBtn = document.getElementById('editSubmitBtn');
            
            const timer = setInterval(function() {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(timer);
                    if (countdownEl) countdownEl.textContent = '0:00';
                    if (editForm) {
                        editForm.parentElement.innerHTML = '<div style="background: #fef3c7; padding: 1.5rem; border-radius: 0.75rem; text-align: center; color: #92400e;">' +
                            '<strong>⏰ Edit window has expired.</strong><br>The 10-minute editing period has ended. This request can no longer be modified.' +
                            '</div>';
                    }
                    return;
                }
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                if (countdownEl) countdownEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            }, 1000);
        })();

        // Toggle "Other" category field
        function toggleEditOther() {
            const sel = document.getElementById('editCategory');
            const field = document.getElementById('editOtherField');
            if (!sel || !field) return;
            const text = sel.options[sel.selectedIndex]?.text.toLowerCase().trim() || '';
            field.style.display = (text === 'other' || text === 'others' || text === 'أخرى') ? 'block' : 'none';
        }
        toggleEditOther();
        <?php endif; ?>
    </script>
</body>
</html>