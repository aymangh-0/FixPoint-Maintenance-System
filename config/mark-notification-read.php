<?php
/**
 * FixPoint - Notification Handler (AJAX)
 * Handles: mark as read (single/all), delete (single/all)
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];

// DELETE ALL notifications
if (isset($_POST['delete_all']) && $_POST['delete_all'] == '1') {
    $sql = "DELETE FROM notification WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'All notifications deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete notifications']);
    }
    $stmt->close();
    exit();
}

// DELETE single notification
if (isset($_POST['delete_id'])) {
    $notif_id = (int)$_POST['delete_id'];
    
    $sql = "DELETE FROM notification WHERE NotificationID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notif_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete notification']);
    }
    $stmt->close();
    exit();
}

// MARK ALL as read
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

// MARK single as read
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