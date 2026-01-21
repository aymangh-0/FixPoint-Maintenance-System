<?php
/**
 * FixPoint - All Feedback (Admin)
 * View all user feedback and ratings
 */

session_start();

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

// Get filter parameters
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT 
            f.FeedbackID,
            f.Rating,
            f.Comment,
            f.SubmittedAt,
            mr.RequestID,
            mr.Title as RequestTitle,
            u.Name as UserName,
            u.Email as UserEmail,
            r.RoleName
        FROM feedback f
        JOIN maintenancerequest mr ON f.RequestID = mr.RequestID
        JOIN user u ON f.UserID = u.UserID
        JOIN role r ON u.RoleID = r.RoleID
        WHERE 1=1";

// Apply rating filter
if ($rating_filter > 0) {
    $sql .= " AND f.Rating = $rating_filter";
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (mr.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%'
              OR f.Comment LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$sql .= " ORDER BY f.SubmittedAt DESC";

$result = $conn->query($sql);
$feedback_list = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [];

// Total feedback
$stats['total'] = count($feedback_list);

// Average rating
$total_rating = 0;
$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

foreach ($feedback_list as $fb) {
    $total_rating += $fb['Rating'];
    $rating_counts[$fb['Rating']]++;
}

$stats['average'] = $stats['total'] > 0 ? round($total_rating / $stats['total'], 1) : 0;
$stats['5_star'] = $rating_counts[5];
$stats['4_star'] = $rating_counts[4];
$stats['3_star'] = $rating_counts[3];
$stats['2_star'] = $rating_counts[2];
$stats['1_star'] = $rating_counts[1];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Feedback - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="all-feedback.php" class="nav-link">Feedback</a>
                    <span style="color: #64748b;">👤 <?php echo e($_SESSION['name']); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-container">
            
            <!-- Page Header -->
            <div class="dashboard-header">
                <h1 class="welcome-text">User Feedback ⭐</h1>
                <p class="user-info">View and analyze all user feedback and ratings</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total Feedback</div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-info">All submissions</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-label">⭐ Average Rating</div>
                    <div class="stat-value"><?php echo $stats['average']; ?></div>
                    <div class="stat-info">Out of 5.0</div>
                </div>

                <div class="stat-card" style="border-left-color: #fbbf24;">
                    <div class="stat-label">🌟 5 Stars</div>
                    <div class="stat-value"><?php echo $stats['5_star']; ?></div>
                    <div class="stat-info">Excellent ratings</div>
                </div>

                <div class="stat-card" style="border-left-color: #a3e635;">
                    <div class="stat-label">⭐ 4 Stars</div>
                    <div class="stat-value"><?php echo $stats['4_star']; ?></div>
                    <div class="stat-info">Very good ratings</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-label">⚠️ 3 Stars or Below</div>
                    <div class="stat-value"><?php echo $stats['3_star'] + $stats['2_star'] + $stats['1_star']; ?></div>
                    <div class="stat-info">Needs attention</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">🔍 Filters & Search</h2>
                
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                    <!-- Rating Filter -->
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Filter by Rating</label>
                        <select name="rating" class="form-input" style="width: 100%;">
                            <option value="0" <?php echo ($rating_filter == 0) ? 'selected' : ''; ?>>All Ratings</option>
                            <option value="5" <?php echo ($rating_filter == 5) ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 Stars</option>
                            <option value="4" <?php echo ($rating_filter == 4) ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 Stars</option>
                            <option value="3" <?php echo ($rating_filter == 3) ? 'selected' : ''; ?>>⭐⭐⭐ 3 Stars</option>
                            <option value="2" <?php echo ($rating_filter == 2) ? 'selected' : ''; ?>>⭐⭐ 2 Stars</option>
                            <option value="1" <?php echo ($rating_filter == 1) ? 'selected' : ''; ?>>⭐ 1 Star</option>
                        </select>
                    </div>
                    
                    <!-- Search Box -->
                    <div style="flex: 2; min-width: 300px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-input" 
                            placeholder="Search by user name, request title, or comment..."
                            value="<?php echo e($search); ?>"
                            style="width: 100%;"
                        >
                    </div>
                    
                    <!-- Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="all-feedback.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Feedback List -->
            <div class="requests-section">
                <h2 class="section-title">
                    Feedback List 
                    <span style="color: #64748b; font-size: 1rem; font-weight: 400;">
                        (<?php echo count($feedback_list); ?> results)
                    </span>
                </h2>
                
                <?php if (count($feedback_list) > 0): ?>
                    <div style="display: grid; gap: 1.5rem;">
                        <?php foreach ($feedback_list as $fb): ?>
                            <div style="background: white; border: 2px solid #e2e8f0; padding: 1.5rem; border-radius: 0.75rem;">
                                <!-- Header -->
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #1e293b;">
                                                <?php echo e($fb['UserName']); ?>
                                            </span>
                                            <span style="background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                                <?php echo e($fb['RoleName']); ?>
                                            </span>
                                        </div>
                                        <div style="color: #64748b; font-size: 0.875rem;">
                                            For Request #<?php echo $fb['RequestID']; ?>: <?php echo e($fb['RequestTitle']); ?>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: right;">
                                        <div style="color: #fbbf24; font-size: 1.5rem; margin-bottom: 0.25rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php echo $i <= $fb['Rating'] ? '⭐' : '☆'; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <div style="color: #64748b; font-size: 0.875rem;">
                                            <?php echo formatDate($fb['SubmittedAt'], 'M d, Y'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Comment -->
                                <?php if ($fb['Comment']): ?>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #2563eb;">
                                    <div style="color: #1e293b; white-space: pre-line;">
                                        "<?php echo e($fb['Comment']); ?>"
                                    </div>
                                </div>
                                <?php else: ?>
                                <div style="color: #94a3b8; font-style: italic; font-size: 0.875rem;">
                                    No additional comment provided
                                </div>
                                <?php endif; ?>
                                
                                <!-- Actions -->
                                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                    <a href="request-details.php?id=<?php echo $fb['RequestID']; ?>" 
                                       class="btn btn-secondary" 
                                       style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        📋 View Request
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">⭐</div>
                        <h3>No feedback found</h3>
                        <?php if ($rating_filter > 0 || !empty($search)): ?>
                            <p>No feedback matches your current filters.</p>
                            <br>
                            <a href="all-feedback.php" class="btn btn-secondary">Clear Filters</a>
                        <?php else: ?>
                            <p>No feedback has been submitted yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</body>
</html>