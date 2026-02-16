<?php
/**
 * FixPoint - Session Security
 * Handles session timeout (15 minutes) and session security
 * 
 * USAGE: Include this file AFTER session_start() in every page
 * require_once '../config/session-security.php';  (for files in subfolders)
 * require_once 'config/session-security.php';     (for files in root)
 */

// Set session timeout to 15 minutes (900 seconds)
define('SESSION_TIMEOUT', 900);

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    
    // Check if last activity timestamp exists
    if (isset($_SESSION['last_activity'])) {
        
        $inactive_time = time() - $_SESSION['last_activity'];
        
        // If inactive for more than 15 minutes
        if ($inactive_time > SESSION_TIMEOUT) {
            
            // Save timeout flag before destroying session
            $was_timed_out = true;
            
            // Destroy session
            session_unset();
            session_destroy();
            
            // Start new session for flash message
            session_start();
            $_SESSION['timeout_message'] = "Your session has expired due to 15 minutes of inactivity. Please log in again.";
            
            // Redirect to login
            header("Location: " . getLoginPath());
            exit();
        }
    }
    
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID every 5 minutes to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Determine the correct login path based on current file location
 */
function getLoginPath() {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    
    if (in_array($current_dir, ['admin', 'user', 'technician'])) {
        return '../auth/login.php';
    } elseif ($current_dir === 'auth' || $current_dir === 'config') {
        return '../auth/login.php';
    } else {
        return 'auth/login.php';
    }
}

?>