<?php
/**
 * FixPoint - Mark Notification as Read (AJAX Handler)
 * Handles marking individual or all notifications as read
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
if (isset($_POST['mark_all']) && $_POST['mark_all'] == '1') {
    $sql = "UPDATE notification SET IsRead = 1 WHERE UserID = ? AND IsRead = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update notifications']);
    }
    $stmt->close();
    exit();
}

// Mark single notification as read
if (isset($_POST['notification_id'])) {
    $notif_id = (int)$_POST['notification_id'];
    
    $sql = "UPDATE notification SET IsRead = 1 WHERE NotificationID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notif_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update notification']);
    }
    $stmt->close();
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>