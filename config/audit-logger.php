<?php
/**
 * FixPoint - Audit & Login Logging Service
 * Logs every login attempt, request update, and administrative action
 * 
 * USAGE: Include in files that need logging
 * require_once __DIR__ . '/../config/audit-logger.php';  (for subfolders)
 * require_once 'config/audit-logger.php';     (for root)
 */

// ============================================
// LOGIN LOGGING
// ============================================

/**
 * Log a login attempt (success or failure)
 * 
 * @param mysqli $conn Database connection
 * @param string $email Email used for login
 * @param int|null $user_id User ID if found
 * @param string $status 'Success' or 'Failed'
 * @param string|null $fail_reason Reason for failure
 */
function logLoginAttempt($conn, $email, $user_id, $status, $fail_reason = null) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : 'Unknown';
    
    $sql = "INSERT INTO loginlog (Email, UserID, Status, FailReason, IPAddress, UserAgent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissss", $email, $user_id, $status, $fail_reason, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// AUDIT LOGGING
// ============================================

/**
 * Log an administrative or system action
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User who performed the action
 * @param string $action Action description (e.g., 'ASSIGN_TECHNICIAN', 'UPDATE_STATUS')
 * @param string|null $table_name Table affected
 * @param int|null $record_id Record ID affected
 * @param string|null $old_value Previous value (JSON or text)
 * @param string|null $new_value New value (JSON or text)
 */
function logAuditAction($conn, $user_id, $action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    
    $sql = "INSERT INTO auditlog (UserID, Action, TableName, RecordID, OldValue, NewValue, IPAddress) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis" . "ss", $user_id, $action, $table_name, $record_id, $old_value, $new_value, $ip);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// CONVENIENCE FUNCTIONS
// ============================================

/**
 * Log successful login
 */
function logLoginSuccess($conn, $email, $user_id) {
    logLoginAttempt($conn, $email, $user_id, 'Success');
}

/**
 * Log failed login - wrong password
 */
function logLoginFailedPassword($conn, $email, $user_id) {
    logLoginAttempt($conn, $email, $user_id, 'Failed', 'Incorrect password');
}

/**
 * Log failed login - email not found
 */
function logLoginFailedEmail($conn, $email) {
    logLoginAttempt($conn, $email, null, 'Failed', 'Email not found');
}

/**
 * Log technician assignment
 */
function logTechnicianAssignment($conn, $admin_id, $request_id, $technician_id) {
    logAuditAction($conn, $admin_id, 'ASSIGN_TECHNICIAN', 'assignment', $request_id, null, "TechnicianID: $technician_id");
}

/**
 * Log status change
 */
function logStatusChangeAudit($conn, $user_id, $request_id, $old_status, $new_status) {
    logAuditAction($conn, $user_id, 'UPDATE_STATUS', 'maintenancerequest', $request_id, "Status: $old_status", "Status: $new_status");
}

/**
 * Log user management action (create, update, delete)
 */
function logUserManagement($conn, $admin_id, $action, $target_user_id, $details = '') {
    logAuditAction($conn, $admin_id, $action, 'user', $target_user_id, null, $details);
}

/**
 * Log request submission
 */
function logRequestSubmission($conn, $user_id, $request_id) {
    logAuditAction($conn, $user_id, 'SUBMIT_REQUEST', 'maintenancerequest', $request_id);
}

/**
 * Log feedback submission
 */
function logFeedbackSubmission($conn, $user_id, $request_id, $rating) {
    logAuditAction($conn, $user_id, 'SUBMIT_FEEDBACK', 'feedback', $request_id, null, "Rating: $rating");
}

?>