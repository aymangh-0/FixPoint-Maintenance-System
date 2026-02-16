<?php
/**
 * FixPoint - Error Handling Module
 * Logs all runtime exceptions and errors to file
 * Alerts administrator on critical errors
 * 
 * USAGE: Include this file at the TOP of config/database.php
 * require_once 'error-handler.php';
 */

// ============================================
// ERROR CONFIGURATION
// ============================================

// Log file path
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error_log.txt');

// Show errors on screen (true for development, false for production)
define('SHOW_ERRORS', false);

// Error reporting level (log everything)
error_reporting(E_ALL);

// Don't display errors to users (security)
ini_set('display_errors', SHOW_ERRORS ? '1' : '0');

// Enable error logging
ini_set('log_errors', '1');

// ============================================
// CUSTOM ERROR HANDLER
// ============================================

/**
 * Custom error handler - converts PHP errors to logged entries
 */
function fixpointErrorHandler($severity, $message, $file, $line) {
    
    // Don't handle errors suppressed with @
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Map severity to readable name
    $error_types = [
        E_ERROR             => 'FATAL ERROR',
        E_WARNING           => 'WARNING',
        E_NOTICE            => 'NOTICE',
        E_USER_ERROR        => 'USER ERROR',
        E_USER_WARNING      => 'USER WARNING',
        E_USER_NOTICE       => 'USER NOTICE',
        E_STRICT            => 'STRICT',
        E_DEPRECATED        => 'DEPRECATED',
        E_USER_DEPRECATED   => 'USER DEPRECATED',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
    ];
    
    $type = isset($error_types[$severity]) ? $error_types[$severity] : 'UNKNOWN ERROR';
    
    // Log the error
    logError($type, $message, $file, $line);
    
    // For fatal/critical errors, show user-friendly page
    if (in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        showErrorPage();
        exit();
    }
    
    // Return true to prevent PHP's default error handler
    return true;
}

// ============================================
// CUSTOM EXCEPTION HANDLER
// ============================================

/**
 * Custom exception handler - catches all uncaught exceptions
 */
function fixpointExceptionHandler($exception) {
    
    logError(
        'UNCAUGHT EXCEPTION',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    showErrorPage();
    exit();
}

// ============================================
// SHUTDOWN HANDLER (catches fatal errors)
// ============================================

/**
 * Shutdown handler - catches fatal errors that bypass error handler
 */
function fixpointShutdownHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        logError(
            'FATAL SHUTDOWN ERROR',
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        showErrorPage();
    }
}

// ============================================
// LOGGING FUNCTION
// ============================================

/**
 * Log error to file with full details
 */
function logError($type, $message, $file, $line, $trace = '') {
    $log_dir = dirname(ERROR_LOG_FILE);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $separator = str_repeat('-', 60);
    
    // Get user info if available
    $user_info = 'Not logged in';
    if (isset($_SESSION['user_id'])) {
        $user_info = "UserID: {$_SESSION['user_id']}, Name: {$_SESSION['name']}, Role: {$_SESSION['role_name']}";
    }
    
    // Get request info
    $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A';
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A';
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A';
    
    $log_entry = "\n$separator\n";
    $log_entry .= "🚨 [$type] - $timestamp\n";
    $log_entry .= "$separator\n";
    $log_entry .= "MESSAGE:  $message\n";
    $log_entry .= "FILE:     $file\n";
    $log_entry .= "LINE:     $line\n";
    $log_entry .= "URL:      $request_method $request_url\n";
    $log_entry .= "IP:       $ip_address\n";
    $log_entry .= "USER:     $user_info\n";
    
    if ($trace) {
        $log_entry .= "TRACE:\n$trace\n";
    }
    
    $log_entry .= "$separator\n";
    
    file_put_contents(ERROR_LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    
    // For critical errors, also notify admin via notification system
    if (in_array($type, ['FATAL ERROR', 'UNCAUGHT EXCEPTION', 'FATAL SHUTDOWN ERROR', 'USER ERROR'])) {
        notifyAdminOfError($type, $message, $file, $line);
    }
}

// ============================================
// ADMIN NOTIFICATION
// ============================================

/**
 * Notify admin about critical errors via in-app notification
 */
function notifyAdminOfError($type, $message, $file, $line) {
    try {
        // Only if database connection is available
        global $conn;
        
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $short_file = basename($file);
            $notif_message = "⚠️ System Error [$type]: $message (in $short_file line $line)";
            
            // Truncate if too long
            if (strlen($notif_message) > 500) {
                $notif_message = substr($notif_message, 0, 497) . '...';
            }
            
            // Get all admin user IDs
            $admin_sql = "SELECT UserID FROM user WHERE RoleID = 1";
            $admin_result = $conn->query($admin_sql);
            
            if ($admin_result) {
                while ($admin = $admin_result->fetch_assoc()) {
                    $insert_sql = "INSERT INTO notification (UserID, Message, IsRead) VALUES (?, ?, 0)";
                    $stmt = $conn->prepare($insert_sql);
                    if ($stmt) {
                        $stmt->bind_param("is", $admin['UserID'], $notif_message);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail - don't create infinite loop
    }
}

// ============================================
// USER-FRIENDLY ERROR PAGE
// ============================================

/**
 * Show a user-friendly error page instead of raw PHP errors
 */
function showErrorPage() {
    // Clear any output that was already sent
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Error - FixPoint</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: Arial, sans-serif; 
                background: #f8fafc; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                min-height: 100vh;
                padding: 2rem;
            }
            .error-container {
                background: white;
                border-radius: 1rem;
                padding: 3rem;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 1px solid #e2e8f0;
            }
            .error-icon { font-size: 4rem; margin-bottom: 1rem; }
            .error-title { color: #1e293b; font-size: 1.5rem; margin-bottom: 0.75rem; }
            .error-message { color: #64748b; line-height: 1.6; margin-bottom: 1.5rem; }
            .error-btn {
                display: inline-block;
                background: #2563eb;
                color: white;
                padding: 0.75rem 2rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.3s;
            }
            .error-btn:hover { background: #1d4ed8; }
            .error-ref { color: #94a3b8; font-size: 0.8rem; margin-top: 1.5rem; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">Something went wrong</h1>
            <p class="error-message">
                We encountered an unexpected error. The system administrator has been 
                notified and will look into this issue. Please try again later.
            </p>
            <a href="javascript:history.back()" class="error-btn">← Go Back</a>
            <p class="error-ref">Error logged at: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';
}

// ============================================
// REGISTER HANDLERS
// ============================================

set_error_handler('fixpointErrorHandler');
set_exception_handler('fixpointExceptionHandler');
register_shutdown_function('fixpointShutdownHandler');

?>