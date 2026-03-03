<?php
/**
 * FixPoint - Submit Maintenance Request
 * Allows users to submit new maintenance requests with duplicate detection
 */

session_start();
require_once '../config/session-security.php';


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
$error = '';
$success = '';
$duplicate_data = null;

// Check request limits
$limit_info = checkRequestLimits($conn, $user_id);

// Get dropdown data
$locations = getAllLocations($conn);
$categories = getAllCategories($conn);
$priorities = getAllPriorities($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location_id = (int)$_POST['location_id'];
    $category_id = (int)$_POST['category_id'];
    $priority_id = (int)$_POST['priority_id'];
    
    // Validate inputs
    if (empty($title)) {
        $error = "Title is required";
    } 
    elseif (strlen($title) < 10) {
        $error = "Title must be at least 10 characters long";
    } 
    elseif (strlen($title) > 200) {
        $error = "Title must not exceed 200 characters";
    } 
    elseif (empty($description)) {
        $error = "Description is required";
    } 
    elseif (strlen($description) < 20) {
        $error = "Description must be at least 20 characters";
    } 
    elseif ($location_id == 0) {
        $error = "Please select a location";
    } 
    elseif ($category_id == 0) {
        $error = "Please select a category";
    } 
    elseif ($priority_id == 0) {
        $error = "Please select a priority level";
    }
    // Validate photo is uploaded
    elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
        $error = "Please upload a photo of the issue — it is required";
    }
    // Check request limits
    elseif (!$limit_info['can_submit']) {
        $error = $limit_info['message'];
    }
    // Check for duplicate — BLOCK completely if found
    else {
        $duplicate = checkDuplicateRequest($conn, $location_id, $category_id);
        
        if ($duplicate !== null) {
            // Duplicate found — block submission entirely
            $duplicate_data = $duplicate;
            $error = "Cannot submit request. An active request already exists at this location for this category. Please wait for it to be resolved.";
        } else {
            // No duplicate — proceed with submission
            
            // Insert request
            $insert_sql = "INSERT INTO maintenancerequest 
                        (UserID, LocationID, CategoryID, PriorityID, StatusID, Title, Description) 
                        VALUES (?, ?, ?, ?, 1, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iiiiss", $user_id, $location_id, $category_id, $priority_id, $title, $description);
            
            if ($stmt->execute()) {
                $request_id = $stmt->insert_id;
                require_once '../config/audit-logger.php';
                logRequestSubmission($conn, $user_id, $request_id);
                
                // Handle photo upload (required)
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                    $upload_dir = '../uploads/requests/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['photo']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        // Validate file size (max 20MB)
                        if ($_FILES['photo']['size'] <= 20 * 1024 * 1024) {
                            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                            $new_filename = 'request_' . $request_id . '_' . time() . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                                // Save photo record
                                $photo_sql = "INSERT INTO requestphoto (RequestID, PhotoPath) VALUES (?, ?)";
                                $photo_stmt = $conn->prepare($photo_sql);
                                $photo_stmt->bind_param("is", $request_id, $upload_path);
                                $photo_stmt->execute();
                            }
                        }
                    }
                }
                
                // Log status change
                logStatusChange($conn, $request_id, null, 1, $user_id);
                
                // Create notification for admins
                $admin_sql = "SELECT UserID FROM user WHERE RoleID = 1";
                $admin_result = $conn->query($admin_sql);
                while ($admin = $admin_result->fetch_assoc()) {
                    createNotification(
                        $conn,
                        $admin['UserID'], 
                        "New maintenance request #$request_id submitted by " . $_SESSION['name'], 
                        $request_id
                    );
                }

                require_once '../config/email-service.php';
                emailNewRequest($conn, $request_id, $title, $description, $_SESSION['name'], '', '', '');
                
                // Auto-assign technician
                require_once '../config/auto-assign.php';
                $assign_result = autoAssignTechnician($conn, $request_id);
                if ($assign_result['assigned']) {
                    $success = "Request submitted and auto-assigned to " . $assign_result['technician_name'] . "! Request ID: #$request_id";
                } else {
                    $success = "Request submitted successfully! Request ID: #$request_id (Waiting for admin to assign a technician)";
                }
                
                // Redirect after 2 seconds
                header("refresh:2;url=dashboard.php");
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        }
    }
}

$current_page = 'submit-request';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
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
            <?php include '../includes/notification-bell.php'; ?>
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
            <div class="topbar-notif"><?php include '../includes/notification-bell.php'; ?></div>
        </div>


    <div class="auth-container" style="background: #f8fafc;">
        <div style="max-width: 800px; width: 100%; margin-top: 2rem;">
            
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">📝</div>
                    <h1 class="auth-title">Submit Maintenance Request</h1>
                    <p class="auth-subtitle">Report an issue and help us maintain our campus</p>
                </div>
                
                <!-- Request Limit Info -->
                <?php if (!$limit_info['can_submit']): ?>
                    <div class="alert alert-error">
                        ⚠️ <strong>Request Limit Reached:</strong> <?php echo e($limit_info['message']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        ✅ <?php echo e($limit_info['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Error / Duplicate Block Message -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo e($error); ?>
                        <?php if ($duplicate_data): ?>
                            <br><br>
                            <strong>Existing Request Details:</strong><br>
                            🔢 Request #<?php echo $duplicate_data['RequestID']; ?><br>


                            📅 Date: <?php echo formatDate($duplicate_data['SubmittedAt']); ?>
                            <br><br>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo e($success); ?>
                        <br><small>Redirecting to dashboard...</small>
                    </div>
                <?php endif; ?>
                
                <!-- Request Form -->
                <form method="POST" action="" enctype="multipart/form-data" <?php echo (!$limit_info['can_submit']) ? 'style="pointer-events:none;opacity:0.6;"' : ''; ?>>
                    
                    <!-- Location Selection -->
                    <div class="form-group">
                        <label for="location_id" class="form-label">Location <span style="color: #ef4444;">*</span></label>
                        <select 
                            id="location_id" 
                            name="location_id" 
                            class="form-input" 
                            required
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                            <option value="">-- Select Location --</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['LocationID']; ?>"
                                    <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['LocationID']) ? 'selected' : ''; ?>>
                                    <?php echo e($location['LocationName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #64748b; font-size: 0.875rem;">Select the building and room where the issue is located</small>
                    </div>
                    
                    <!-- Category Selection -->
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category <span style="color: #ef4444;">*</span></label>
                        <select 
                            id="category_id" 
                            name="category_id" 
                            class="form-input" 
                            required
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['CategoryID']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['CategoryID']) ? 'selected' : ''; ?>>
                                    <?php echo e($category['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #64748b; font-size: 0.875rem;">What type of issue is this?</small>
                    </div>
                    
                    <!-- Priority Selection -->
                    <div class="form-group">
                        <label for="priority_id" class="form-label">Priority <span style="color: #ef4444;">*</span></label>
                        <select 
                            id="priority_id" 
                            name="priority_id" 
                            class="form-input" 
                            required
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                            <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo $priority['PriorityID']; ?>"
                                    <?php echo (!isset($_POST['priority_id']) && $priority['PriorityID'] == 2) ? 'selected' : ''; ?>
                                    <?php echo (isset($_POST['priority_id']) && $_POST['priority_id'] == $priority['PriorityID']) ? 'selected' : ''; ?>>
                                    <?php echo e($priority['PriorityLevel']); ?> - <?php echo e($priority['Description']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #64748b; font-size: 0.875rem;">How urgent is this issue?</small>
                        <div style="margin-top:0.6rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.8rem;color:#64748b;line-height:1.9;">
                            <strong style="color:#1e293b;">Priority Guide:</strong><br>
                            🟢 <strong>Low</strong> &mdash; e.g. burnt-out light bulb, minor paint damage, broken blinds, loose door handle.<br>
                            🟡 <strong>Medium</strong> &mdash; e.g. faulty power outlet, slow drain, flickering lights, broken chair.<br>
                            🔴 <strong>High</strong> &mdash; e.g. AC failure, water leak, elevator malfunction, internet outage.<br>
                            🚨 <strong>Critical</strong> &mdash; e.g. electrical hazard, broken door lock, gas leak, ceiling collapse.
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title" class="form-label">Title <span style="color: #ef4444;">*</span></label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            class="form-input" 
                            placeholder="Brief description of the issue"
                            required
                            maxlength="200"
                            value="<?php echo isset($_POST['title']) ? e($_POST['title']) : ''; ?>"
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                        <small style="color: #64748b; font-size: 0.875rem;">Example: "Broken AC in Lab A" or "Leaking faucet in restroom"</small>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="form-label">Description <span style="color: #ef4444;">*</span></label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-input" 
                            placeholder="Provide detailed information about the issue..."
                            required
                            rows="5"
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        ><?php echo isset($_POST['description']) ? e($_POST['description']) : ''; ?></textarea>
                        <small style="color: #64748b; font-size: 0.875rem;">Include as much detail as possible to help technicians understand the problem</small>
                    </div>
                    
                    <!-- Photo Upload (Required) -->
                    <div class="form-group">
                        <label for="photo" class="form-label">Upload Photo <span style="color: #ef4444;">*</span></label>
                        <input 
                            type="file" 
                            id="photo" 
                            name="photo" 
                            class="form-input" 
                            accept="image/jpeg,image/jpg,image/png,image/gif"
                            required
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                        <small style="color: #64748b; font-size: 0.875rem;">📷 A photo of the issue is required (JPG, PNG, GIF - Max 20MB)</small>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                        📤 Submit Request
                    </button>
                    
                </form>
                
            </div>
        </div>
    </div>
    <script src="../assets/js/submit-request-validation.js"></script>
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