<?php
/**
 * FixPoint - Helper Functions
 */

function checkDuplicateRequest($conn, $location_id, $category_id, $exclude_request_id = null) {
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
        return $result->fetch_assoc();
    }
    
    return null;
}

function checkRequestLimits($conn, $user_id) {
    // First, check if a week has passed since LastResetAt — if so, reset automatically
    $reset_check = $conn->prepare("SELECT LastResetAt, MaxRequestsPerWeek FROM user WHERE UserID = ?");
    $reset_check->bind_param("i", $user_id);
    $reset_check->execute();
    $user_data = $reset_check->get_result()->fetch_assoc();
    
    if (!$user_data) {
        return [
            'can_submit'     => false,
            'week_count'     => 0,
            'week_remaining' => 0,
            'message'        => 'User not found'
        ];
    }
    
    $last_reset = $user_data['LastResetAt'];
    $week_limit = (int)$user_data['MaxRequestsPerWeek'];
    
    // Auto-reset: if LastResetAt is NULL or more than 7 days ago, update it to NOW
    if ($last_reset === null || (time() - strtotime($last_reset)) >= 7 * 24 * 3600) {
        $update_reset = $conn->prepare("UPDATE user SET LastResetAt = NOW() WHERE UserID = ?");
        $update_reset->bind_param("i", $user_id);
        $update_reset->execute();
        $last_reset = date('Y-m-d H:i:s'); // use current time
    }
    
    // Count requests submitted after the (possibly just-updated) LastResetAt
    $count_sql = $conn->prepare("SELECT COUNT(*) as cnt FROM maintenancerequest WHERE UserID = ? AND SubmittedAt > ?");
    $count_sql->bind_param("is", $user_id, $last_reset);
    $count_sql->execute();
    $week_count = (int)$count_sql->get_result()->fetch_assoc()['cnt'];
    
    $can_submit = $week_count < $week_limit;
    $week_remaining = max(0, $week_limit - $week_count);
    
    // Calculate next reset time
    $next_reset_ts = strtotime($last_reset) + 7 * 24 * 3600;
    $next_reset_str = date("M d, Y", $next_reset_ts) . " at " . date("h:i A", $next_reset_ts);
    
    if (!$can_submit) {
        $message = "You have reached your weekly limit of {$week_limit} requests";
    } else {
        $message = "You can submit {$week_remaining} more request(s) this week.";
    }
    
    return [
        'can_submit'     => $can_submit,
        'week_count'     => $week_count,
        'week_remaining' => $week_remaining,
        'week_limit'     => $week_limit,
        'next_reset'     => $next_reset_str,
        'next_reset_ts'  => $next_reset_ts,
        'message'        => $message
    ];
}

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
        'Total'       => 0,
        'Pending'     => 0,
        'Reviewed'    => 0,
        'Assigned'    => 0,
        'In Progress' => 0,
        'Completed'   => 0,
        'Cancelled'   => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['StatusName']] = (int)$row['Count'];
        $stats['Total'] += (int)$row['Count'];
    }
    
    return $stats;
}

function createNotification($conn, $user_id, $message, $request_id = null) {
    $sql = "INSERT INTO notification (UserID, RequestID, Message, IsRead) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $request_id, $message);
    return $stmt->execute();
}

function logStatusChange($conn, $request_id, $old_status_id, $new_status_id, $changed_by) {
    $sql = "INSERT INTO statushistory (RequestID, OldStatusID, NewStatusID, ChangedBy) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $request_id, $old_status_id, $new_status_id, $changed_by);
    return $stmt->execute();
}

function getAllLocations($conn) {
    $sql = "SELECT LocationID, 
                   CONCAT(BuildingName, ' - ', FloorNumber, ' - ', RoomNumber) as LocationName,
                   BuildingName, FloorNumber, RoomNumber
            FROM location 
            ORDER BY BuildingName, FloorNumber, RoomNumber";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getAllCategories($conn) {
    return $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName")->fetch_all(MYSQLI_ASSOC);
}

function getAllPriorities($conn) {
    return $conn->query("SELECT PriorityID, PriorityLevel, Description FROM priority ORDER BY PriorityID")->fetch_all(MYSQLI_ASSOC);
}

function getAllStatuses($conn) {
    return $conn->query("SELECT StatusID, StatusName FROM status ORDER BY StatusID")->fetch_all(MYSQLI_ASSOC);
}

function getAllTechnicians($conn) {
    $sql = "SELECT UserID, Name, Email, Phone FROM user WHERE RoleID = 2 ORDER BY Name";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function isTechnician() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function isUser() {
    return isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4);
}

function requireLogin($redirect_url = '../auth/login.php') {
    if (!isLoggedIn()) {
        header("Location: " . $redirect_url);
        exit();
    }
}

function requireRole($required_role_id, $redirect_url = '../index.php') {
    requireLogin();
    if ($_SESSION['role_id'] != $required_role_id) {
        header("Location: " . $redirect_url);
        exit();
    }
}

function formatDate($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function getStatusBadgeClass($status_name) {
    $classes = [
        'Pending'     => 'status-pending',
        'Reviewed'    => 'status-reviewed',
        'Assigned'    => 'status-assigned',
        'In Progress' => 'status-in-progress',
        'Completed'   => 'status-completed',
        'Cancelled'   => 'status-cancelled'
    ];
    return $classes[$status_name] ?? 'status-default';
}

function getPriorityBadgeClass($priority_level) {
    $classes = [
        'Low'      => 'priority-low',
        'Medium'   => 'priority-medium',
        'High'     => 'priority-high',
        'Critical' => 'priority-critical'
    ];
    return $classes[$priority_level] ?? 'priority-default';
}
?>