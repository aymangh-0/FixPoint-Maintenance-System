<?php
/**
 * FixPoint - Submit Maintenance Request
 * Simplified form: Location + Category + Title (optional) + Photo (required)
 * Priority is set by technician/admin, not user
 */

session_start();
require_once __DIR__ . '/../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 4)) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$duplicate_data = null;

$limit_info = checkRequestLimits($conn, $user_id);
$locations = getAllLocations($conn);
$categories = getAllCategories($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location_id = (int)$_POST['location_id'];
    $category_id = (int)$_POST['category_id'];
    $other_category = trim($_POST['other_category'] ?? '');
    
    // Default priority = 2 (Medium) — admin/technician will adjust
    $priority_id = 2;
    
    // Build description from available info
    $desc_parts = [];
    if ($other_category) $desc_parts[] = "Category: " . $other_category;
    if ($title) $desc_parts[] = $title;
    $description = !empty($desc_parts) ? implode(' — ', $desc_parts) : 'No description provided';

    if ($location_id == 0) {
        $error = "Please select a location";
    } 
    elseif ($category_id == 0) {
        $error = "Please select a category";
    }
    else {
        // Check if selected category is "Other/Others" and requires specification
        $cat_check = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = ?");
        $cat_check->bind_param("i", $category_id);
        $cat_check->execute();
        $cat_name = $cat_check->get_result()->fetch_assoc()['CategoryName'] ?? '';
        $cat_lower = strtolower(trim($cat_name));
        if (($cat_lower === 'other' || $cat_lower === 'others' || $cat_lower === 'أخرى') && empty($other_category)) {
            $error = "Please specify the issue type";
        }
    }
    if (empty($error) && (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0)) {
        $error = "Please upload a photo of the issue";
    }
    if (empty($error) && !$limit_info['can_submit']) {
        $error = $limit_info['message'];
    }
    if (empty($error)) {
        $duplicate = checkDuplicateRequest($conn, $location_id, $category_id);
        
        if ($duplicate !== null) {
            $duplicate_data = $duplicate;
            $error = "An active request already exists at this location for this category.";
        } else {
            $insert_sql = "INSERT INTO maintenancerequest 
                        (UserID, LocationID, CategoryID, PriorityID, StatusID, Title, Description) 
                        VALUES (?, ?, ?, ?, 1, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iiiiss", $user_id, $location_id, $category_id, $priority_id, $title, $description);
            
            if ($stmt->execute()) {
                $request_id = $stmt->insert_id;
                require_once __DIR__ . '/../config/audit-logger.php';
                logRequestSubmission($conn, $user_id, $request_id);
                
                // Handle photo upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                    $upload_dir = '../uploads/requests/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['photo']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        if ($_FILES['photo']['size'] <= 20 * 1024 * 1024) {
                            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                            $new_filename = 'request_' . $request_id . '_' . time() . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                                $photo_sql = "INSERT INTO requestphoto (RequestID, PhotoPath) VALUES (?, ?)";
                                $photo_stmt = $conn->prepare($photo_sql);
                                $photo_stmt->bind_param("is", $request_id, $upload_path);
                                $photo_stmt->execute();
                            }
                        }
                    }
                }
                
                logStatusChange($conn, $request_id, null, 1, $user_id);
                
                // Notify admins
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

                // Auto-assign technician FIRST (before sending emails)
                require_once __DIR__ . '/../config/auto-assign.php';
                $assign_result = autoAssignTechnician($conn, $request_id);
                
                // Now send emails (after status is updated by auto-assign)
                require_once __DIR__ . '/../config/email-service.php';
                emailNewRequest($conn, $request_id, $title, $description, $_SESSION['name'], '', '', '');
                emailRequestConfirmation($conn, $request_id, $user_id);
                
                if ($assign_result['assigned']) {
                    // Send email to assigned technician
                    $tech_id_for_email = $assign_result['technician_id'] ?? null;
                    if (!$tech_id_for_email) {
                        // Fallback: get tech ID from assignment table
                        $t = $conn->prepare("SELECT TechnicianID FROM assignment WHERE RequestID = ? ORDER BY AssignedAt DESC LIMIT 1");
                        $t->bind_param("i", $request_id);
                        $t->execute();
                        $tech_row = $t->get_result()->fetch_assoc();
                        $tech_id_for_email = $tech_row ? $tech_row['TechnicianID'] : null;
                    }
                    if ($tech_id_for_email) {
                        emailTechnicianAssigned($conn, $request_id, $tech_id_for_email);
                    }
                    $success = "Request submitted and assigned to " . $assign_result['technician_name'] . "! Request #$request_id. You can edit or delete this request within 10 minutes.";
                } else {
                    $success = "Request submitted successfully! Request #$request_id. You can edit or delete this request within 10 minutes.";
                }
                $new_request_id = $request_id;
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
    <style>
        /* ── Submit Request Page Styles ── */
        .sr-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 60px);
            padding: 40px 24px;
            background: #f8fafc;
        }

        .sr-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 600px;
            padding: 36px 32px 32px;
        }

        .sr-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .sr-header h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .sr-header p {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Alerts */
        .sr-alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .sr-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .sr-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }
        .sr-alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }

        /* Form grid: 2 columns for Location & Category */
        .sr-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .sr-field {
            margin-bottom: 0;
        }
        .sr-field.full {
            grid-column: 1 / -1;
        }
        .sr-field-single {
            margin-bottom: 16px;
        }

        .sr-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .sr-label .req {
            color: #ef4444;
            margin-left: 2px;
        }
        .sr-label .opt {
            color: #94a3b8;
            font-weight: 400;
            font-size: 0.75rem;
        }

        .sr-select,
        .sr-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1e293b;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }
        .sr-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .sr-select:focus,
        .sr-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        /* Photo upload area */
        .sr-upload {
            margin-bottom: 24px;
        }
        .sr-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafbfc;
        }
        .sr-upload-area:hover {
            border-color: #2563eb;
            background: #f8faff;
        }
        .sr-upload-area.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .sr-upload-area.has-file {
            border-color: #16a34a;
            border-style: solid;
            background: #f0fdf4;
        }
        .sr-upload-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .sr-upload-text {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
        }
        .sr-upload-text strong {
            color: #2563eb;
        }
        .sr-upload-hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 4px;
        }
        .sr-upload input[type="file"] {
            display: none;
        }
        .sr-file-name {
            font-size: 0.85rem;
            color: #15803d;
            font-weight: 600;
            margin-top: 8px;
        }

        /* Preview */
        .sr-preview {
            margin-top: 12px;
            display: none;
        }
        .sr-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        /* Submit button */
        .sr-submit {
            width: 100%;
            padding: 14px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: #2563eb;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.2);
        }
        .sr-submit:hover {
            background: #1d4ed8;
            box-shadow: 0 4px 16px rgba(37,99,235,0.3);
            transform: translateY(-1px);
        }
        .sr-submit:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .sr-limit-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 20px;
            padding: 10px 14px;
            background: #f1f5f9;
            border-radius: 8px;
        }
        .sr-limit-bar strong {
            color: #1e293b;
        }

        @media (max-width: 640px) {
            .sr-wrapper { padding: 20px 12px; }
            .sr-card { padding: 28px 20px 24px; }
            .sr-grid { grid-template-columns: 1fr; }
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

        <div class="sr-wrapper">
            <div class="sr-card">
                <div class="sr-header">
                    <h1>Submit Request</h1>
                    <p>Tell us what needs fixing</p>
                </div>

                <!-- Limit Info -->
                <?php if (!$limit_info['can_submit']): ?>
                    <div class="sr-alert sr-alert-error">
                        ⚠️ <strong>Limit Reached:</strong> <?php echo e($limit_info['message']); ?>
                    </div>
                <?php else: ?>
                    <div class="sr-limit-bar">
                        <span>Weekly requests: <strong><?php echo $limit_info['week_count']; ?> / <?php echo ($limit_info['week_count'] + $limit_info['week_remaining']); ?></strong></span>
                        <span><?php echo $limit_info['week_remaining']; ?> remaining</span>
                    </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="sr-alert sr-alert-error">
                        <?php echo e($error); ?>
                        <?php if ($duplicate_data): ?>
                            <br><small>Existing request #<?php echo $duplicate_data['RequestID']; ?> — <?php echo formatDate($duplicate_data['SubmittedAt']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Success Modal -->
                <?php if ($success): ?>
                <div id="successModal" style="
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
                    display: flex; align-items: center; justify-content: center; 
                    z-index: 9999; animation: modalFadeIn 0.3s ease;">
                    <div style="
                        background: white; border-radius: 1.25rem; padding: 2.5rem; 
                        max-width: 440px; width: 90%; text-align: center;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        animation: modalSlideUp 0.4s ease;">
                        
                        <!-- Success Icon -->
                        <div style="
                            width: 80px; height: 80px; border-radius: 50%; 
                            background: linear-gradient(135deg, #10b981, #059669);
                            display: flex; align-items: center; justify-content: center;
                            margin: 0 auto 1.25rem; font-size: 2.5rem;
                            box-shadow: 0 8px 20px rgba(16,185,129,0.3);">
                            ✓
                        </div>
                        
                        <h2 style="color: #1e293b; font-size: 1.4rem; margin-bottom: 0.5rem;">Request Submitted!</h2>
                        <p style="color: #2563eb; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.75rem;">
                            Request #<?php echo $new_request_id ?? ''; ?>
                        </p>
                        
                        <!-- Edit/Delete Notice -->
                        <div style="
                            background: #eff6ff; border: 1.5px solid #bfdbfe; 
                            border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.25rem; margin-bottom: 0.25rem;">⏱️</div>
                            <p style="color: #1e40af; font-size: 0.9rem; font-weight: 500; margin: 0;">
                                You can <strong>edit</strong> or <strong>delete</strong> this request within <strong>10 minutes</strong>
                            </p>
                        </div>
                        
                        <!-- Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                            <?php if (isset($new_request_id)): ?>
                            <a href="request-details.php?id=<?php echo $new_request_id; ?>" style="
                                background: #2563eb; color: white; padding: 0.85rem 1.5rem;
                                border-radius: 0.6rem; text-decoration: none; font-weight: 600;
                                font-size: 0.95rem; display: block; transition: background 0.2s;">
                                ✏️ View / Edit Request
                            </a>
                            <?php endif; ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="my-requests.php" style="
                                    flex: 1; background: #f1f5f9; color: #475569; padding: 0.75rem;
                                    border-radius: 0.6rem; text-decoration: none; font-weight: 600;
                                    font-size: 0.85rem; transition: background 0.2s;">
                                    📋 My Requests
                                </a>
                                <a href="dashboard.php" style="
                                    flex: 1; background: #f1f5f9; color: #475569; padding: 0.75rem;
                                    border-radius: 0.6rem; text-decoration: none; font-weight: 600;
                                    font-size: 0.85rem; transition: background 0.2s;">
                                    🏠 Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
                    @keyframes modalSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
                </style>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="" enctype="multipart/form-data" <?php echo (!$limit_info['can_submit']) ? 'style="pointer-events:none;opacity:0.5;"' : ''; ?>>
                    
                    <!-- Row 1: Location + Category side by side -->
                    <div class="sr-grid">
                        <div class="sr-field">
                            <label for="location_id" class="sr-label">Location <span class="req">*</span></label>
                            <select id="location_id" name="location_id" class="sr-select" required <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                                <option value="">Select location</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo $loc['LocationID']; ?>"
                                        <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $loc['LocationID']) ? 'selected' : ''; ?>>
                                        <?php echo e($loc['LocationName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="sr-field">
                            <label for="category_id" class="sr-label">Category <span class="req">*</span></label>
                            <select id="category_id" name="category_id" class="sr-select" required <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                                <option value="">Select category</option>
                                <?php 
                                // Sort: put "Other/Others" at the end
                                $regular = [];
                                $other = [];
                                foreach ($categories as $cat) {
                                    $catLower = strtolower(trim($cat['CategoryName']));
                                    if ($catLower === 'other' || $catLower === 'others' || $catLower === 'أخرى') {
                                        $other[] = $cat;
                                    } else {
                                        $regular[] = $cat;
                                    }
                                }
                                foreach (array_merge($regular, $other) as $cat): ?>
                                    <option value="<?php echo $cat['CategoryID']; ?>"
                                        data-name="<?php echo e($cat['CategoryName']); ?>"
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                        <?php echo e($cat['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Other Category (appears when "Other" is selected) -->
                    <div class="sr-field-single" id="otherCategoryField" style="display:none;">
                        <label for="other_category" class="sr-label">Please specify <span class="req">*</span></label>
                        <input 
                            type="text" 
                            id="other_category" 
                            name="other_category" 
                            class="sr-input" 
                            placeholder="Describe the type of issue"
                            maxlength="200"
                            value="<?php echo isset($_POST['other_category']) ? e($_POST['other_category']) : ''; ?>"
                        >
                    </div>

                    <!-- Row 2: Description (optional) -->
                    <div class="sr-field-single">
                        <label for="title" class="sr-label">Description <span class="opt">(optional)</span></label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            class="sr-input" 
                            placeholder="e.g. Broken AC in Lab A"
                            maxlength="200"
                            value="<?php echo isset($_POST['title']) ? e($_POST['title']) : ''; ?>"
                            <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>
                        >
                    </div>

                    <!-- Row 3: Photo Upload (required) -->
                    <div class="sr-upload">
                        <label class="sr-label">Photo <span class="req">*</span></label>
                        <div class="sr-upload-area" id="uploadArea">
                            <div class="sr-upload-icon">📷</div>
                            <div class="sr-upload-text"><strong>Click to upload</strong> or drag photo here</div>
                            <div class="sr-upload-hint">JPG, PNG, GIF — Max 20MB</div>
                            <div class="sr-file-name" id="fileName"></div>
                            <div class="sr-preview" id="preview"><img id="previewImg" src="" alt="Preview"></div>
                            <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif" required <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="sr-submit" <?php echo (!$limit_info['can_submit']) ? 'disabled' : ''; ?>>
                        Submit Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sidebar
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow='hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow=''; }
        document.getElementById('hamburgerBtn')?.addEventListener('click', openSidebar);
        document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);
        document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);

        // Photo upload UX
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('photo');
        const fileName = document.getElementById('fileName');
        const preview = document.getElementById('preview');
        const previewImg = document.getElementById('previewImg');

        // Other category toggle
        const categorySelect = document.getElementById('category_id');
        const otherField = document.getElementById('otherCategoryField');
        const otherInput = document.getElementById('other_category');

        function checkOther() {
            const selected = categorySelect.options[categorySelect.selectedIndex];
            const name = (selected?.getAttribute('data-name') || '').toLowerCase().trim();
            const isOther = name === 'other' || name === 'others' || name === 'أخرى';
            
            otherField.style.display = isOther ? 'block' : 'none';
            otherInput.required = isOther;
            if (!isOther) otherInput.value = '';
        }

        categorySelect.addEventListener('change', checkOther);
        // Check on page load (for POST back)
        checkOther();

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) showFile(fileInput.files[0]);
        });

        function showFile(file) {
            fileName.textContent = '✓ ' + file.name;
            uploadArea.classList.add('has-file');
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        // Notification bell
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