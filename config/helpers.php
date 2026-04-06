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
    // Auto-reset: if 7 days have passed since LastResetAt, reset it
    $reset_check = $conn->prepare("SELECT LastResetAt, MaxRequestsPerWeek FROM user WHERE UserID = ?");
    $reset_check->bind_param("i", $user_id);
    $reset_check->execute();
    $reset_data = $reset_check->get_result()->fetch_assoc();
    
    if ($reset_data) {
        $last_reset = $reset_data['LastResetAt'];
        if ($last_reset) {
            $reset_time = strtotime($last_reset);
            $next_reset_time = $reset_time + (7 * 24 * 60 * 60);
            if (time() >= $next_reset_time) {
                // Auto-reset
                $do_reset = $conn->prepare("UPDATE user SET LastResetAt = NOW() WHERE UserID = ?");
                $do_reset->bind_param("i", $user_id);
                $do_reset->execute();
            }
        } else {
            // No LastResetAt set, initialize it
            $do_reset = $conn->prepare("UPDATE user SET LastResetAt = NOW() WHERE UserID = ?");
            $do_reset->bind_param("i", $user_id);
            $do_reset->execute();
        }
    }

    $sql = "SELECT 
                u.UserID,
                u.MaxRequestsPerWeek,
                u.LastResetAt,
                COUNT(CASE 
                    WHEN mr.SubmittedAt > COALESCE(u.LastResetAt, DATE_SUB(NOW(), INTERVAL 7 DAY))
                    AND mr.StatusID != 6
                    THEN 1 
                END) AS RequestsThisWeek
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
            'can_submit'     => false,
            'week_count'     => 0,
            'week_remaining' => 0,
            'message'        => 'User not found'
        ];
    }
    
    $data = $result->fetch_assoc();
    
    $week_count = (int)$data['RequestsThisWeek'];
    $week_limit = (int)$data['MaxRequestsPerWeek'];
    $last_reset = $data['LastResetAt'];
    
    $can_submit = $week_count < $week_limit;
    $week_remaining = max(0, $week_limit - $week_count);
    
    // Calculate next reset time
    $next_reset_ts = null;
    $next_reset = null;
    if ($last_reset) {
        $next_reset_ts = strtotime($last_reset) + (7 * 24 * 60 * 60);
        $next_reset = date('M d, Y \a\t h:i A', $next_reset_ts);
    }
    
    if (!$can_submit) {
        $message = "You have reached your weekly limit of {$week_limit} requests.";
    } else {
        $message = "You can submit {$week_remaining} more request(s) this week.";
    }
    
    return [
        'can_submit'     => $can_submit,
        'week_count'     => $week_count,
        'week_remaining' => $week_remaining,
        'message'        => $message,
        'next_reset_ts'  => $next_reset_ts,
        'next_reset'     => $next_reset,
        'week_limit'     => $week_limit
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