<?php
/**
 * FixPoint - Helper Functions
 * Contains reusable functions for request validation, duplicate detection, and limits
 */

/**
 * Check for duplicate maintenance requests
 * Prevents duplicate reports for the same location + category that are still open
 * 
 * @param mysqli $conn Database connection
 * @param int $location_id Location ID
 * @param int $category_id Category ID
 * @param int|null $exclude_request_id Request ID to exclude from check (for updates)
 * @return array|null Returns existing request data if duplicate found, null otherwise
 */
function checkDuplicateRequest($conn, $location_id, $category_id, $exclude_request_id = null) {
    // Build query to find similar open requests
    $sql = "SELECT 
                mr.RequestID, 
                mr.Title, 
                s.StatusName,
                mr.SubmittedAt,
                u.Name AS RequesterName
            FROM maintenancerequest mr
            JOIN status s ON mr.StatusID = s.StatusID
            JOIN user u ON mr.UserID = u.UserID
            WHERE mr.LocationID = ? 
            AND mr.CategoryID = ?
            AND s.StatusName NOT IN ('Completed', 'Cancelled')";
    
    // Exclude specific request if provided (useful for updates)
    if ($exclude_request_id !== null) {
        $sql .= " AND mr.RequestID != ?";
    }
    
    $sql .= " ORDER BY mr.SubmittedAt DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_request_id !== null) {
        $stmt->bind_param("iii", $location_id, $category_id, $exclude_request_id);
    } else {
        $stmt->bind_param("ii", $location_id, $category_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc(); // Duplicate found
    }
    
    return null; // No duplicate
}

/**
 * Check if user has reached their request limits
 * Checks both weekly and monthly limits
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check
 * @return array Returns ['can_submit' => bool, 'week_count' => int, 'month_count' => int, 'limits' => array, 'message' => string]
 */
function checkRequestLimits($conn, $user_id) {
    // Get user limits and current request counts
    $sql = "SELECT 
                u.UserID,
                u.MaxRequestsPerWeek,
                u.MaxRequestsPerMonth,
                COUNT(CASE 
                    WHEN mr.SubmittedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                    THEN 1 
                END) AS RequestsThisWeek,
                COUNT(CASE 
                    WHEN mr.SubmittedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    THEN 1 
                END) AS RequestsThisMonth
            FROM user u
            LEFT JOIN maintenancerequest mr ON u.UserID = mr.UserID
            WHERE u.UserID = ?
            GROUP BY u.UserID";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'can_submit' => false,
            'week_count' => 0,
            'month_count' => 0,
            'limits' => ['week' => 0, 'month' => 0],
            'week_remaining' => 0,
            'month_remaining' => 0,
            'message' => 'User not found'
        ];
    }
    
    $data = $result->fetch_assoc();
    
    $week_count = (int)$data['RequestsThisWeek'];
    $month_count = (int)$data['RequestsThisMonth'];
    $week_limit = (int)$data['MaxRequestsPerWeek'];
    $month_limit = (int)$data['MaxRequestsPerMonth'];
    
    // Check if limits exceeded
    $can_submit = ($week_count < $week_limit) && ($month_count < $month_limit);
    
    // Generate message
    $message = '';
    if (!$can_submit) {
        if ($week_count >= $week_limit) {
            $message = "You have reached your weekly limit of {$week_limit} requests. Please try again next week.";
        } elseif ($month_count >= $month_limit) {
            $message = "You have reached your monthly limit of {$month_limit} requests. Please contact admin for assistance.";
        }
    } else {
        $week_remaining = $week_limit - $week_count;
        $month_remaining = $month_limit - $month_count;
        $message = "You can submit {$week_remaining} more request(s) this week and {$month_remaining} this month.";
    }
    
    return [
        'can_submit' => $can_submit,
        'week_count' => $week_count,
        'month_count' => $month_count,
        'limits' => [
            'week' => $week_limit,
            'month' => $month_limit
        ],
        'week_remaining' => max(0, $week_limit - $week_count),
        'month_remaining' => max(0, $month_limit - $month_count),
        'message' => $message
    ];
}

/**
 * Get user's request statistics
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Request counts by status
 */
function getUserRequestStats($conn, $user_id) {
    $sql = "SELECT 
                s.StatusName,
                COUNT(*) as Count
            FROM maintenancerequest mr
            JOIN status s ON mr.StatusID = s.StatusID
            WHERE mr.UserID = ?
            GROUP BY s.StatusName";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [
        'Total' => 0,
        'Pending' => 0,
        'Reviewed' => 0,
        'Assigned' => 0,
        'In Progress' => 0,
        'Completed' => 0,
        'Cancelled' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['StatusName']] = (int)$row['Count'];
        $stats['Total'] += (int)$row['Count'];
    }
    
    return $stats;
}

/**
 * Create a notification for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to notify
 * @param string $message Notification message
 * @param int|null $request_id Related request ID (optional)
 * @return bool Success status
 */
function createNotification($conn, $user_id, $message, $request_id = null) {
    $sql = "INSERT INTO notification (UserID, RequestID, Message, IsRead) 
            VALUES (?, ?, ?, 0)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $request_id, $message);
    
    return $stmt->execute();
}

/**
 * Log status change in StatusHistory
 * 
 * @param mysqli $conn Database connection
 * @param int $request_id Request ID
 * @param int $old_status_id Old status ID (can be null for first entry)
 * @param int $new_status_id New status ID
 * @param int $changed_by User ID who made the change
 * @return bool Success status
 */
function logStatusChange($conn, $request_id, $old_status_id, $new_status_id, $changed_by) {
    $sql = "INSERT INTO statushistory (RequestID, OldStatusID, NewStatusID, ChangedBy) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $request_id, $old_status_id, $new_status_id, $changed_by);
    
    return $stmt->execute();
}

/**
 * Get all locations for dropdown
 * 
 * @param mysqli $conn Database connection
 * @return array Array of locations
 */
function getAllLocations($conn) {
    $sql = "SELECT LocationID, 
                   CONCAT(BuildingName, ' - ', FloorNumber, ' - ', RoomNumber) as LocationName,
                   BuildingName,
                   FloorNumber,
                   RoomNumber
            FROM location 
            ORDER BY BuildingName, FloorNumber, RoomNumber";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all categories for dropdown
 * 
 * @param mysqli $conn Database connection
 * @return array Array of categories
 */
function getAllCategories($conn) {
    $sql = "SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all priorities for dropdown
 * 
 * @param mysqli $conn Database connection
 * @return array Array of priorities
 */
function getAllPriorities($conn) {
    $sql = "SELECT PriorityID, PriorityLevel, Description FROM priority ORDER BY PriorityID";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all statuses for dropdown
 * 
 * @param mysqli $conn Database connection
 * @return array Array of statuses
 */
function getAllStatuses($conn) {
    $sql = "SELECT StatusID, StatusName FROM status ORDER BY StatusID";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all technicians for assignment
 * 
 * @param mysqli $conn Database connection
 * @return array Array of technicians
 */
function getAllTechnicians($conn) {
    $sql = "SELECT UserID, Name, Email, Phone 
            FROM user 
            WHERE RoleID = 2 
            ORDER BY Name";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

/**
 * Check if user is technician
 * 
 * @return bool True if user is technician
 */
function isTechnician() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

/**
 * Check if user is regular user (Student/Faculty)
 * 
 * @return bool True if user is regular user
 */
function isUser() {
    return isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4);
}

/**
 * Require login - redirect if not logged in
 * 
 * @param string $redirect_url URL to redirect to after login
 */
function requireLogin($redirect_url = '../auth/login.php') {
    if (!isLoggedIn()) {
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have required role
 * 
 * @param int $required_role_id Required role ID (1=Admin, 2=Technician, 3=Student, 4=Faculty)
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireRole($required_role_id, $redirect_url = '../index.php') {
    requireLogin();
    
    if ($_SESSION['role_id'] != $required_role_id) {
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format (default: 'M d, Y H:i')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Sanitize output for HTML display
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get status badge class for CSS styling
 * 
 * @param string $status_name Status name
 * @return string CSS class name
 */
function getStatusBadgeClass($status_name) {
    $classes = [
        'Pending' => 'status-pending',
        'Reviewed' => 'status-reviewed',
        'Assigned' => 'status-assigned',
        'In Progress' => 'status-in-progress',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled'
    ];
    
    return $classes[$status_name] ?? 'status-default';
}

/**
 * Get priority badge class for CSS styling
 * 
 * @param string $priority_level Priority level
 * @return string CSS class name
 */
function getPriorityBadgeClass($priority_level) {
    $classes = [
        'Low' => 'priority-low',
        'Medium' => 'priority-medium',
        'High' => 'priority-high',
        'Critical' => 'priority-critical'
    ];
    
    return $classes[$priority_level] ?? 'priority-default';
}

?>