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
$warning = '';
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
    $confirm_duplicate = isset($_POST['confirm_duplicate']) ? $_POST['confirm_duplicate'] : 'no';
    
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
    // Check request limits
    elseif (!$limit_info['can_submit']) {
        $error = $limit_info['message'];
    }
    // Check for duplicate
    else {
        $duplicate = checkDuplicateRequest($conn, $location_id, $category_id);
        
        if ($duplicate !== null && $confirm_duplicate != 'yes') {
            // Duplicate found - show warning
            $warning = "A similar request already exists at this location for this category.";
            $duplicate_data = $duplicate;
        } else {
            // No duplicate OR user confirmed - proceed with submission
            
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
                // Handle photo upload if provided
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
                
                $success = "Request submitted successfully! Request ID: #$request_id";
                
                // Redirect after 2 seconds
                header("refresh:2;url=dashboard.php");
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
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
                    <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>
                    <?php include '../includes/notification-bell.php'; ?>
                    <span style="color: #64748b;">👤 <?php echo e($_SESSION['name']); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

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
                
                <!-- Error Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo e($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo e($success); ?>
                        <br><small>Redirecting to dashboard...</small>
                    </div>
                <?php endif; ?>
                
                <!-- Duplicate Warning -->
                <?php if ($warning && $duplicate_data): ?>
                    <div class="alert alert-warning">
                        ⚠️ <strong><?php echo e($warning); ?></strong>
                        <br><br>
                        <strong>Existing Request #<?php echo $duplicate_data['RequestID']; ?>:</strong> 
                        <?php echo e($duplicate_data['Title']); ?>
                        <br>
                        <strong>Status:</strong> <?php echo e($duplicate_data['StatusName']); ?>
                        <br>
                        <strong>Reported by:</strong> <?php echo e($duplicate_data['RequesterName']); ?>
                        <br>
                        <strong>Date:</strong> <?php echo formatDate($duplicate_data['SubmittedAt']); ?>
                        <br><br>
                        <em>Do you still want to submit this request?</em>
                    </div>
                <?php endif; ?>
                
                <!-- Request Form -->
                <form method="POST" action="" enctype="multipart/form-data" <?php echo (!$limit_info['can_submit']) ? 'style="pointer-events:none;opacity:0.6;"' : ''; ?>>
                    
                    <!-- Hidden field for duplicate confirmation -->
                    <?php if ($warning && $duplicate_data): ?>
                        <input type="hidden" name="confirm_duplicate" value="yes">
                    <?php endif; ?>
                    
                    <!-- Location Selection -->
                    <div class="form-group">
                        <label for="location_id" class="form-label">Location *</label>
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
                        <label for="category_id" class="form-label">Category *</label>
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
                        <label for="priority_id" class="form-label">Priority *</label>
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
                    </div>
                    
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title" class="form-label">Title *</label>
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
                        <label for="description" class="form-label">Description *</label>
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
                    
                    <!-- Photo Upload -->
                    <div class="form-group">
                        <label for="photo" class="form-label">Photo (Optional)</label>
                        <input 
                            type="file" 
                            id="photo" 
                            name="photo" 
                            class="form-input" 
                            accept="image/jpeg,image/jpg,image/png,image/gif"
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                        <small style="color: #64748b; font-size: 0.875rem;">Upload a photo of the issue (JPG, PNG, GIF - Max 20MB)</small>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                        <?php if ($warning && $duplicate_data): ?>
                            ✅ Yes, Submit Anyway
                        <?php else: ?>
                            📤 Submit Request
                        <?php endif; ?>
                    </button>
                    
                    <?php if ($warning && $duplicate_data): ?>
                        <a href="submit-request.php" class="btn-submit" style="background: #64748b; margin-top: 1rem;">
                            ❌ No, Cancel
                        </a>
                    <?php endif; ?>
                </form>
                
            </div>
        </div>
    </div>
    <script src="../assets/js/submit-request-validation.js"></script>
</body>
</html>