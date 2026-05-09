<?php
/**
 * FixPoint - Submit Feedback
 * Allow users to rate and review completed maintenance requests
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
$error = '';
$success = '';

// Get request details
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.UserID,
            mr.StatusID,
            mr.CompletedAt,
            s.StatusName
        FROM maintenancerequest mr
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

// Check if request is completed
if ($request['StatusID'] != 5) {
    $error = "Feedback can only be submitted for completed requests.";
}

// Check if feedback already exists
$check_sql = "SELECT FeedbackID FROM feedback WHERE RequestID = ? AND UserID = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $request_id, $user_id);
$check_stmt->execute();
$existing_feedback = $check_stmt->get_result();

$already_submitted = $existing_feedback->num_rows > 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$already_submitted) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    // Validate
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating (1-5 stars)";
    } else {
        // Insert feedback
        $insert_sql = "INSERT INTO feedback (RequestID, UserID, Rating, Comment) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiis", $request_id, $user_id, $rating, $comment);
        
        if ($insert_stmt->execute()) {
            // Notify admin
            $admin_sql = "SELECT UserID FROM user WHERE RoleID = 1";
            $admin_result = $conn->query($admin_sql);
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification(
                    $conn,
                    $admin['UserID'], 
                    "New feedback received for request #$request_id ($rating stars)", 
                    $request_id
                );
            }
            // Send email notification to admins
            require_once __DIR__ . '/../config/email-service.php';
            emailFeedbackReceived($conn, $request_id, $_SESSION['name'], $rating, $comment);
            require_once __DIR__ . '/../config/audit-logger.php';
            logFeedbackSubmission($conn, $user_id, $request_id, $rating);

            $success = "Thank you for your feedback! Your review has been submitted.";
            $already_submitted = true;
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    }
}

// Get existing feedback if already submitted
$feedback = null;
if ($already_submitted) {
    $get_sql = "SELECT Rating, Comment, SubmittedAt FROM feedback WHERE RequestID = ? AND UserID = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("ii", $request_id, $user_id);
    $get_stmt->execute();
    $feedback = $get_stmt->get_result()->fetch_assoc();
}


$current_page = 'my-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .rating-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 2rem 0;
        }
        
        .stars {
            display: flex;
            gap: 0.5rem;
            font-size: 3rem;
            margin: 1rem 0;
        }
        
        .star {
            cursor: pointer;
            color: #cbd5e1;
            transition: all 0.2s;
            user-select: none;
        }
        
        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .star.active {
            animation: starPulse 0.3s ease;
        }
        
        @keyframes starPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .rating-label {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 0.5rem;
        }
        
        .rating-description {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .request-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            border-left: 4px solid #2563eb;
        }
        
        .request-info-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .submitted-feedback {
            background: #d1fae5;
            border: 2px solid #a7f3d0;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
        }
        
        .submitted-feedback h3 {
            color: #065f46;
            margin-bottom: 1rem;
        }
        
        .your-rating {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            font-size: 2rem;
            margin: 1rem 0;
            color: #fbbf24;
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


    <div class="auth-container" style="background: #f8fafc;">
        <div style="max-width: 700px; width: 100%; margin-top: 2rem;">
            
            <!-- Back Button -->
            <div style="margin-bottom: 1rem;">
                <a href="request-details.php?id=<?php echo $request_id; ?>" class="btn btn-outline">← Back to Request</a>
            </div>
            
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">⭐</div>
                    <h1 class="auth-title">Submit Feedback</h1>
                    <p class="auth-subtitle">Rate your experience with this maintenance request</p>
                </div>
                
                <!-- Request Info -->
                <div class="request-info">
                    <div class="request-info-title">Request #<?php echo $request['RequestID']; ?>:</div>
                    <div><?php echo e($request['Title']); ?></div>
                    <?php if ($request['CompletedAt']): ?>
                        <div style="color: #64748b; font-size: 0.875rem; margin-top: 0.5rem;">
                            Completed on <?php echo formatDate($request['CompletedAt'], 'M d, Y'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
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
                    </div>
                <?php endif; ?>
                
                <?php if ($already_submitted && $feedback): ?>
                    <!-- Already Submitted Feedback -->
                    <div class="submitted-feedback">
                        <h3>✅ Feedback Already Submitted</h3>
                        <p style="color: #065f46; margin-bottom: 1.5rem;">
                            Thank you for your feedback! Here's what you submitted:
                        </p>
                        
                        <div class="your-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span><?php echo $i <= $feedback['Rating'] ? '⭐' : '☆'; ?></span>
                            <?php endfor; ?>
                        </div>
                        
                        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; margin-top: 1.5rem; text-align: left;">
                            <strong style="color: #1e293b;">Your Comment:</strong>
                            <p style="color: #64748b; margin-top: 0.5rem;">
                                <?php echo $feedback['Comment'] ? e($feedback['Comment']) : '<em>No comment provided</em>'; ?>
                            </p>
                            <small style="color: #94a3b8;">
                                Submitted on <?php echo formatDate($feedback['SubmittedAt'], 'M d, Y - H:i'); ?>
                            </small>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <a href="my-requests.php" class="btn btn-primary">View My Requests</a>
                        </div>
                    </div>
                    
                <?php elseif ($request['StatusID'] == 5): ?>
                    <!-- Feedback Form -->
                    <form method="POST" action="" id="feedbackForm">
                        <!-- Rating -->
                        <div class="rating-container">
                            <label style="font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">
                                How would you rate the service? *
                            </label>
                            
                            <input type="hidden" name="rating" id="rating" value="0" required>
                            
                            <div class="stars" id="starRating">
                                <span class="star" data-rating="1">☆</span>
                                <span class="star" data-rating="2">☆</span>
                                <span class="star" data-rating="3">☆</span>
                                <span class="star" data-rating="4">☆</span>
                                <span class="star" data-rating="5">☆</span>
                            </div>
                            
                            <div class="rating-label" id="ratingLabel">Select your rating</div>
                            <div class="rating-description" id="ratingDesc"></div>
                        </div>
                        
                        <!-- Comment -->
                        <div class="form-group">
                            <label for="comment" class="form-label">Additional Comments (Optional)</label>
                            <textarea 
                                id="comment" 
                                name="comment" 
                                class="form-input" 
                                rows="5"
                                placeholder="Tell us more about your experience..."
                            ></textarea>
                            <small style="color: #64748b;">Share any additional thoughts about the service quality, response time, or technician performance</small>
                        </div>
                        
                        <button type="submit" class="btn-submit" id="submitBtn">
                            📤 Submit Feedback
                        </button>
                    </form>
                    
                <?php else: ?>
                    <div class="alert alert-error">
                        ⚠️ Feedback can only be submitted for completed requests.
                    </div>
                    <a href="my-requests.php" class="btn btn-secondary">Back to My Requests</a>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingLabel = document.getElementById('ratingLabel');
        const ratingDesc = document.getElementById('ratingDesc');
        const submitBtn = document.getElementById('submitBtn');
        
        const ratingLabels = {
            1: 'Poor',
            2: 'Fair',
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };
        
        const ratingDescriptions = {
            1: 'Service needs significant improvement',
            2: 'Service was below expectations',
            3: 'Service met expectations',
            4: 'Service exceeded expectations',
            5: 'Outstanding service!'
        };
        
        let selectedRating = 0;
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                ratingInput.value = selectedRating;
                updateStars(selectedRating);
                updateLabels(selectedRating);
                submitBtn.disabled = false;
            });
            
            star.addEventListener('mouseenter', function() {
                const hoverRating = parseInt(this.dataset.rating);
                updateStars(hoverRating);
                updateLabels(hoverRating);
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            if (selectedRating > 0) {
                updateStars(selectedRating);
                updateLabels(selectedRating);
            } else {
                resetStars();
            }
        });
        
        function updateStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '⭐';
                    star.classList.add('active');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('active');
                }
            });
        }
        
        function updateLabels(rating) {
            ratingLabel.textContent = ratingLabels[rating];
            ratingDesc.textContent = ratingDescriptions[rating];
        }
        
        function resetStars() {
            stars.forEach(star => {
                star.textContent = '☆';
                star.classList.remove('active');
            });
            ratingLabel.textContent = 'Select your rating';
            ratingDesc.textContent = '';
        }
        
        // Form validation
        document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
            if (selectedRating === 0) {
                e.preventDefault();
                alert('Please select a rating before submitting');
            }
        });
    </script>
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