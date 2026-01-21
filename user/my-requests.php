<?php
/**
 * FixPoint - My Requests
 * View all maintenance requests submitted by the user
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            s.StatusName,
            (SELECT PhotoPath FROM requestphoto WHERE RequestID = mr.RequestID LIMIT 1) as PhotoPath
        FROM maintenancerequest mr
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE mr.UserID = ?";

// Add status filter
if ($status_filter != 'all') {
    $sql .= " AND s.StatusName = '" . $conn->real_escape_string($status_filter) . "'";
}

// Add search filter
if (!empty($search)) {
    $sql .= " AND (mr.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR mr.Description LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$sql .= " ORDER BY mr.SubmittedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = getUserRequestStats($conn, $user_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - FixPoint</title>
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
                    <span class="logo-subtitle">SEU</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="submit-request.php" class="btn btn-primary">➕ New Request</a>
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
                <h1 class="welcome-text">My Requests 📋</h1>
                <p class="user-info">View and track all your maintenance requests</p>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Total</div>
                    <div class="stat-value"><?php echo $stats['Total']; ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['Pending']; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['In Progress']; ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['Completed']; ?></div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">Filter & Search</h2>
                
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                    <!-- Status Filter -->
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Filter by Status</label>
                        <select name="status" class="form-input" onchange="this.form.submit()" style="width: 100%;">
                            <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reviewed" <?php echo ($status_filter == 'Reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="Assigned" <?php echo ($status_filter == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                            <option value="In Progress" <?php echo ($status_filter == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($status_filter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($status_filter == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Search Box -->
                    <div style="flex: 2; min-width: 300px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-input" 
                            placeholder="Search by title or description..."
                            value="<?php echo e($search); ?>"
                            style="width: 100%;"
                        >
                    </div>
                    
                    <!-- Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="my-requests.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Requests Table -->
            <div class="requests-section">
                <h2 class="section-title">
                    All Requests 
                    <span style="color: #64748b; font-size: 1rem; font-weight: 400;">
                        (<?php echo count($requests); ?> results)
                    </span>
                </h2>
                
                <?php if (count($requests) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Last Update</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        
                                        <!-- Photo -->
                                        <td>
                                            <?php if ($req['PhotoPath']): ?>
                                                <img src="<?php echo e($req['PhotoPath']); ?>" 
                                                     alt="Request photo" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.25rem;">
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">📷 No photo</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Title -->
                                        <td class="request-title"><?php echo e($req['Title']); ?></td>
                                        
                                        <!-- Location -->
                                        <td><?php echo e($req['BuildingName'] . ' - ' . $req['RoomNumber']); ?></td>
                                        
                                        <!-- Category -->
                                        <td><?php echo e($req['CategoryName']); ?></td>
                                        
                                        <!-- Priority -->
                                        <td>
                                            <span class="priority-badge <?php echo getPriorityBadgeClass($req['PriorityLevel']); ?>">
                                                <?php echo e($req['PriorityLevel']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td>
                                            <span class="status-badge <?php echo getStatusBadgeClass($req['StatusName']); ?>">
                                                <?php echo e($req['StatusName']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Submitted Date -->
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></td>
                                        
                                        <!-- Last Update -->
                                        <td>
                                            <?php 
                                            if ($req['CompletedAt']) {
                                                echo formatDate($req['CompletedAt'], 'M d, Y');
                                            } else {
                                                echo formatDate($req['UpdatedAt'], 'M d, Y');
                                            }
                                            ?>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td>
                                            <a href="request-details.php?id=<?php echo $req['RequestID']; ?>" 
                                               class="btn btn-primary" 
                                               style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                👁️ View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">🔍</div>
                        <h3>No requests found</h3>
                        <?php if ($status_filter != 'all' || !empty($search)): ?>
                            <p>Try adjusting your filters or search terms.</p>
                            <br>
                            <a href="my-requests.php" class="btn btn-secondary">Clear Filters</a>
                        <?php else: ?>
                            <p>You haven't submitted any maintenance requests yet.</p>
                            <br>
                            <a href="submit-request.php" class="btn btn-primary">Submit Your First Request</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</body>
</html>