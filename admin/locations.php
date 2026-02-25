<?php
/**
 * FixPoint - Location Management (Admin)
 * Add, edit, and delete campus locations
 */

session_start();
require_once '../config/session-security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';
require_once '../config/audit-logger.php';

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// ============================================
// Handle ADD Location
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_location'])) {
    $building = trim($_POST['building_name']);
    $floor = trim($_POST['floor_number']);
    $room = trim($_POST['room_number']);
    $description = trim($_POST['description']);
    
    if (empty($building) || empty($floor) || empty($room)) {
        $error = "Building name, floor, and room are required.";
    } else {
        $check_sql = "SELECT LocationID FROM location WHERE BuildingName = ? AND FloorNumber = ? AND RoomNumber = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $building, $floor, $room);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "This location already exists.";
        } else {
            $sql = "INSERT INTO location (BuildingName, FloorNumber, RoomNumber, Description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $building, $floor, $room, $description);
            
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $success = "Location added successfully!";
                logAuditAction($conn, $admin_id, 'ADD_LOCATION', 'location', $new_id, null, "$building - $floor - $room");
            } else {
                $error = "Failed to add location.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// ============================================
// Handle EDIT Location
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_location'])) {
    $loc_id = (int)$_POST['location_id'];
    $building = trim($_POST['building_name']);
    $floor = trim($_POST['floor_number']);
    $room = trim($_POST['room_number']);
    $description = trim($_POST['description']);
    
    if (empty($building) || empty($floor) || empty($room)) {
        $error = "Building name, floor, and room are required.";
    } else {
        $old_sql = "SELECT CONCAT(BuildingName, ' - ', FloorNumber, ' - ', RoomNumber) as OldLocation FROM location WHERE LocationID = ?";
        $old_stmt = $conn->prepare($old_sql);
        $old_stmt->bind_param("i", $loc_id);
        $old_stmt->execute();
        $old_val = $old_stmt->get_result()->fetch_assoc()['OldLocation'];
        $old_stmt->close();
        
        $sql = "UPDATE location SET BuildingName = ?, FloorNumber = ?, RoomNumber = ?, Description = ? WHERE LocationID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $building, $floor, $room, $description, $loc_id);
        
        if ($stmt->execute()) {
            $success = "Location updated successfully!";
            logAuditAction($conn, $admin_id, 'EDIT_LOCATION', 'location', $loc_id, $old_val, "$building - $floor - $room");
        } else {
            $error = "Failed to update location.";
        }
        $stmt->close();
    }
}

// ============================================
// Handle DELETE Location
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_location'])) {
    $loc_id = (int)$_POST['location_id'];
    
    // تحقق من وجود طلبات نشطة فقط (مو مكتملة أو ملغية)
    $check_sql = "SELECT COUNT(*) as count FROM maintenancerequest 
                  WHERE LocationID = ? 
                  AND StatusID NOT IN (
                      SELECT StatusID FROM status WHERE StatusName IN ('Completed', 'Cancelled')
                  )";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $loc_id);
    $check_stmt->execute();
    $count = $check_stmt->get_result()->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($count > 0) {
        $error = "Cannot delete this location. It has $count active request(s) still in progress.";
    } else {
        $name_sql = "SELECT CONCAT(BuildingName, ' - ', FloorNumber, ' - ', RoomNumber) as LocName FROM location WHERE LocationID = ?";
        $name_stmt = $conn->prepare($name_sql);
        $name_stmt->bind_param("i", $loc_id);
        $name_stmt->execute();
        $loc_name = $name_stmt->get_result()->fetch_assoc()['LocName'];
        $name_stmt->close();
        
        $sql = "DELETE FROM location WHERE LocationID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loc_id);
        
        if ($stmt->execute()) {
            $success = "Location deleted successfully!";
            logAuditAction($conn, $admin_id, 'DELETE_LOCATION', 'location', $loc_id, $loc_name, null);
        } else {
            $error = "Failed to delete location.";
        }
        $stmt->close();
    }
}

// ============================================
// Get all locations with request counts + active counts
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT 
            l.LocationID,
            l.BuildingName,
            l.FloorNumber,
            l.RoomNumber,
            l.Description,
            COUNT(mr.RequestID) as RequestCount,
            SUM(CASE WHEN mr.StatusID NOT IN (
                SELECT StatusID FROM status WHERE StatusName IN ('Completed', 'Cancelled')
            ) THEN 1 ELSE 0 END) as ActiveCount
        FROM location l
        LEFT JOIN maintenancerequest mr ON l.LocationID = mr.LocationID
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (l.BuildingName LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR l.FloorNumber LIKE '%" . $conn->real_escape_string($search) . "%'
              OR l.RoomNumber LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$sql .= " GROUP BY l.LocationID ORDER BY l.BuildingName, l.FloorNumber, l.RoomNumber";

$locations = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$buildings_sql = "SELECT DISTINCT BuildingName FROM location ORDER BY BuildingName";
$buildings = $conn->query($buildings_sql)->fetch_all(MYSQLI_ASSOC);

$total_locations = count($locations);
$total_buildings = count($buildings);

$current_page = 'locations';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Management - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-header h2 { font-size: 1.25rem; color: #1e293b; }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            padding: 0.25rem;
        }
        .modal-close:hover { color: #ef4444; }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-size: 0.875rem;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .loc-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .action-btn {
            padding: 0.4rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-edit { background: #dbeafe; color: #1e40af; }
        .btn-edit:hover { background: #bfdbfe; }
        .btn-delete { background: #fee2e2; color: #991b1b; }
        .btn-delete:hover { background: #fecaca; }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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
                    <span class="sidebar-logo-sub">SEU Admin</span>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($_SESSION['name']); ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
            <?php include '../includes/notification-bell.php'; ?>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📊</span><span>Dashboard</span>
            </a>
            <a href="all-requests.php" class="sidebar-link <?php echo $current_page === 'all-requests' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📋</span><span>All Requests</span>
            </a>
            <a href="users.php" class="sidebar-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <span class="sidebar-icon">👥</span><span>Manage Users</span>
            </a>
            <div class="sidebar-section-label">Management</div>
            <a href="locations.php" class="sidebar-link <?php echo $current_page === 'locations' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📍</span><span>Locations</span>
            </a>
            <a href="reports.php" class="sidebar-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📈</span><span>Reports</span>
            </a>
            <a href="all-feedback.php" class="sidebar-link <?php echo $current_page === 'all-feedback' ? 'active' : ''; ?>">
                <span class="sidebar-icon">⭐</span><span>Feedback</span>
            </a>
            <a href="audit-logs.php" class="sidebar-link <?php echo $current_page === 'audit-logs' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🔍</span><span>Audit Logs</span>
            </a>
            <a href="backup.php" class="sidebar-link <?php echo $current_page === 'backup' ? 'active' : ''; ?>">
                <span class="sidebar-icon">💾</span><span>Backup</span>
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


    <div class="dashboard">
        <div class="dashboard-container">
            
            <div class="dashboard-header">
                <h1 class="welcome-text">📍 Location Management</h1>
                <p class="user-info">Add, edit, and manage campus locations</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📍 Total Locations</div>
                    <div class="stat-value"><?php echo $total_locations; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">🏢 Buildings</div>
                    <div class="stat-value"><?php echo $total_buildings; ?></div>
                </div>
            </div>

            <div class="requests-section" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <form method="GET" action="" style="display: flex; gap: 0.5rem; flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-input" placeholder="Search locations..." 
                               value="<?php echo e($search); ?>" style="flex: 1; padding: 0.625rem; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="locations.php" class="btn btn-outline">Reset</a>
                        <?php endif; ?>
                    </form>
                    <button class="btn btn-primary btn-large" onclick="openAddModal()">➕ Add New Location</button>
                </div>
            </div>

            <div class="requests-section">
                <h2 class="section-title">All Locations (<?php echo count($locations); ?>)</h2>
                
                <?php if (count($locations) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Building</th>
                                    <th>Floor</th>
                                    <th>Room</th>
                                    <th>Description</th>
                                    <th>Requests</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locations as $loc): ?>
                                    <tr>
                                        <td><strong>#<?php echo $loc['LocationID']; ?></strong></td>
                                        <td><strong><?php echo e($loc['BuildingName']); ?></strong></td>
                                        <td><?php echo e($loc['FloorNumber']); ?></td>
                                        <td><?php echo e($loc['RoomNumber']); ?></td>
                                        <td>
                                            <?php if ($loc['Description']): ?>
                                                <span style="color: #64748b; font-size: 0.85rem;"><?php echo e($loc['Description']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="loc-badge"><?php echo $loc['RequestCount']; ?> request(s)</span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button class="action-btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($loc)); ?>)">
                                                    ✏️ Edit
                                                </button>
                                                <?php if ($loc['ActiveCount'] == 0): ?>
                                                    <button class="action-btn btn-delete" onclick="confirmDelete(<?php echo $loc['LocationID']; ?>, '<?php echo e($loc['BuildingName'] . ' - ' . $loc['RoomNumber']); ?>')">
                                                        🗑️ Delete
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn" style="background: #f1f5f9; color: #94a3b8; cursor: not-allowed;" disabled title="Has active requests">
                                                        🔒 In Use
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">📍</div>
                        <h3>No locations found</h3>
                        <p>Click "Add New Location" to create your first location.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Location</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Building Name *</label>
                    <input type="text" name="building_name" required placeholder="e.g., Main Building" list="buildingList">
                    <datalist id="buildingList">
                        <?php foreach ($buildings as $b): ?>
                            <option value="<?php echo e($b['BuildingName']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Floor *</label>
                    <input type="text" name="floor_number" required placeholder="e.g., Ground Floor">
                </div>
                <div class="form-group">
                    <label>Room / Area *</label>
                    <input type="text" name="room_number" required placeholder="e.g., Room 101">
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Brief description of this location"></textarea>
                </div>
                <button type="submit" name="add_location" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                    ➕ Add Location
                </button>
            </form>
        </div>
    </div>

    <!-- EDIT Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Location</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="location_id" id="edit_location_id">
                <div class="form-group">
                    <label>Building Name *</label>
                    <input type="text" name="building_name" id="edit_building" required list="buildingList">
                </div>
                <div class="form-group">
                    <label>Floor *</label>
                    <input type="text" name="floor_number" id="edit_floor" required>
                </div>
                <div class="form-group">
                    <label>Room / Area *</label>
                    <input type="text" name="room_number" id="edit_room" required>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" id="edit_description" rows="2"></textarea>
                </div>
                <button type="submit" name="edit_location" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                    💾 Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- DELETE Form (hidden) -->
    <form method="POST" action="" id="deleteForm" style="display: none;">
        <input type="hidden" name="location_id" id="delete_location_id">
        <input type="hidden" name="delete_location" value="1">
    </form>

    <script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    function openEditModal(loc) {
        document.getElementById('edit_location_id').value = loc.LocationID;
        document.getElementById('edit_building').value = loc.BuildingName;
        document.getElementById('edit_floor').value = loc.FloorNumber;
        document.getElementById('edit_room').value = loc.RoomNumber;
        document.getElementById('edit_description').value = loc.Description || '';
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function confirmDelete(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"? This cannot be undone.')) {
            document.getElementById('delete_location_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
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