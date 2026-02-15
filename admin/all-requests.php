<?php
/**
 * FixPoint - All Requests (Admin)
 * View and manage all maintenance requests with filters and search
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

$admin_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : 'all';
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$sql = "SELECT 
            mr.RequestID,
            mr.Title,
            mr.Description,
            mr.SubmittedAt,
            mr.UpdatedAt,
            mr.CompletedAt,
            u.Name as RequesterName,
            u.Email as RequesterEmail,
            r.RoleName as RequesterRole,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            c.CategoryName,
            p.PriorityLevel,
            p.PriorityID,
            s.StatusName,
            s.StatusID,
            (SELECT Name FROM user WHERE UserID = (SELECT TechnicianID FROM assignment WHERE RequestID = mr.RequestID ORDER BY AssignedAt DESC LIMIT 1)) as TechnicianName
        FROM maintenancerequest mr
        JOIN user u ON mr.UserID = u.UserID
        JOIN role r ON u.RoleID = r.RoleID
        JOIN location l ON mr.LocationID = l.LocationID
        JOIN category c ON mr.CategoryID = c.CategoryID
        JOIN priority p ON mr.PriorityID = p.PriorityID
        JOIN status s ON mr.StatusID = s.StatusID
        WHERE 1=1";

// Apply filters
if ($status_filter != 'all') {
    $sql .= " AND s.StatusName = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($priority_filter != 'all') {
    $sql .= " AND p.PriorityLevel = '" . $conn->real_escape_string($priority_filter) . "'";
}

if ($category_filter != 'all') {
    $sql .= " AND c.CategoryName = '" . $conn->real_escape_string($category_filter) . "'";
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (mr.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR mr.Description LIKE '%" . $conn->real_escape_string($search) . "%'
              OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Apply sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY mr.SubmittedAt ASC";
        break;
    case 'priority':
        $sql .= " ORDER BY p.PriorityID DESC, mr.SubmittedAt DESC";
        break;
    case 'updated':
        $sql .= " ORDER BY mr.UpdatedAt DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY mr.SubmittedAt DESC";
        break;
}

$result = $conn->query($sql);
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics for current filters
$stats = [];
$stats['total'] = count($requests);
$stats['pending'] = 0;
$stats['in_progress'] = 0;
$stats['completed'] = 0;

foreach ($requests as $req) {
    if ($req['StatusName'] == 'Pending') $stats['pending']++;
    if ($req['StatusName'] == 'In Progress' || $req['StatusName'] == 'Assigned') $stats['in_progress']++;
    if ($req['StatusName'] == 'Completed') $stats['completed']++;
}

// Get dropdown data
$categories = getAllCategories($conn);
$priorities = getAllPriorities($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Requests - Admin</title>
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
                    <?php include '../includes/notification-bell.php'; ?>
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
                <h1 class="welcome-text">All Maintenance Requests 📋</h1>
                <p class="user-info">View, filter, and manage all system requests</p>
            </div>

            <!-- Filter Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📊 Showing</div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-info">Filtered results</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-label">⏳ Pending</div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🔧 In Progress</div>
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">✅ Completed</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-info">In results</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">🔍 Filters & Search</h2>
                
                <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    
                    <!-- Status Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Status</label>
                        <select name="status" class="form-input" style="width: 100%;">
                            <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reviewed" <?php echo ($status_filter == 'Reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="Assigned" <?php echo ($status_filter == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                            <option value="In Progress" <?php echo ($status_filter == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($status_filter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($status_filter == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Priority Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Priority</label>
                        <select name="priority" class="form-input" style="width: 100%;">
                            <option value="all" <?php echo ($priority_filter == 'all') ? 'selected' : ''; ?>>All Priorities</option>
                            <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo e($priority['PriorityLevel']); ?>" 
                                    <?php echo ($priority_filter == $priority['PriorityLevel']) ? 'selected' : ''; ?>>
                                    <?php echo e($priority['PriorityLevel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Category</label>
                        <select name="category" class="form-input" style="width: 100%;">
                            <option value="all" <?php echo ($category_filter == 'all') ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e($category['CategoryName']); ?>" 
                                    <?php echo ($category_filter == $category['CategoryName']) ? 'selected' : ''; ?>>
                                    <?php echo e($category['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Sort By -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Sort By</label>
                        <select name="sort" class="form-input" style="width: 100%;">
                            <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="priority" <?php echo ($sort_by == 'priority') ? 'selected' : ''; ?>>Priority (High to Low)</option>
                            <option value="updated" <?php echo ($sort_by == 'updated') ? 'selected' : ''; ?>>Recently Updated</option>
                        </select>
                    </div>
                    
                    <!-- Search Box -->
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-input" 
                            placeholder="Search title, description, or requester..."
                            value="<?php echo e($search); ?>"
                            style="width: 100%;"
                        >
                    </div>
                    
                    <!-- Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Apply</button>
                        <a href="all-requests.php" class="btn btn-outline">🔄 Reset</a>
                    </div>
                </form>
            </div>

            <!-- Requests Table -->
            <div class="requests-section">
                <h2 class="section-title">
                    Requests List
                </h2>
                
                <?php if (count($requests) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Requester</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Technician</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr style="<?php echo ($req['PriorityID'] >= 3 && $req['StatusID'] < 5) ? 'background: #fef2f2;' : ''; ?>">
                                        <td><strong>#<?php echo $req['RequestID']; ?></strong></td>
                                        
                                        <!-- Title -->
                                        <td class="request-title">
                                            <?php echo e($req['Title']); ?>
                                            <?php if ($req['PriorityID'] == 4): ?>
                                                <span style="color: #ef4444;">🚨</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Requester -->
                                        <td>
                                            <strong><?php echo e($req['RequesterName']); ?></strong>
                                            <br>
                                            <small style="color: #64748b;"><?php echo e($req['RequesterRole']); ?></small>
                                        </td>
                                        
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
                                        
                                        <!-- Technician -->
                                        <td>
                                            <?php if ($req['TechnicianName']): ?>
                                                <span style="color: #10b981;">✅ <?php echo e($req['TechnicianName']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">⚠️ Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Submitted Date -->
                                        <td><?php echo formatDate($req['SubmittedAt'], 'M d, Y'); ?></td>
                                        
                                        <!-- Actions -->
                                        <td>
                                            <a href="request-details.php?id=<?php echo $req['RequestID']; ?>" 
                                               class="btn btn-primary" 
                                               style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                ⚙️ Manage
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
                        <?php if ($status_filter != 'all' || $priority_filter != 'all' || $category_filter != 'all' || !empty($search)): ?>
                            <p>No requests match your current filters.</p>
                            <br>
                            <a href="all-requests.php" class="btn btn-secondary">Clear All Filters</a>
                        <?php else: ?>
                            <p>No maintenance requests have been submitted to the system yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</body>
</html>