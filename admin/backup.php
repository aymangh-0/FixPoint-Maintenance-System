<?php
/**
 * FixPoint - Database Backup System
 * Creates automatic daily backups of the database
 * 
 * USAGE:
 * 1. Manual: Open in browser → http://localhost/fixpoint/admin/backup.php
 * 2. Scheduled: Use Windows Task Scheduler or cron job
 * 
 * Only accessible by Admin users
 */

session_start();

// Allow CLI execution (for scheduled tasks)
$is_cli = (php_sapi_name() === 'cli');

// If not CLI, check admin authentication
if (!$is_cli) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
        header("Location: ../index.php");
        exit();
    }
}

require_once '../config/database.php';
require_once '../config/helpers.php';

// ============================================
// BACKUP CONFIGURATION
// ============================================

$backup_dir = __DIR__ . '/../backups/';
$db_name = 'fixpoint';
$max_backups = 30;  // Keep last 30 backups

// Create backup directory if not exists
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// ============================================
// BACKUP FUNCTIONS
// ============================================

/**
 * Create a full database backup (SQL dump)
 */
function createBackup($conn, $db_name, $backup_dir) {
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "fixpoint_backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;
    
    $output = "";
    
    // Header
    $output .= "-- ============================================\n";
    $output .= "-- FixPoint Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: {$db_name}\n";
    $output .= "-- ============================================\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES FROM `{$db_name}`");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $create_result = $conn->query("SHOW CREATE TABLE `{$table}`");
        $create_row = $create_result->fetch_row();
        
        $output .= "-- ============================================\n";
        $output .= "-- Table: {$table}\n";
        $output .= "-- ============================================\n";
        $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $output .= $create_row[1] . ";\n\n";
        
        // Get table data
        $data_result = $conn->query("SELECT * FROM `{$table}`");
        
        if ($data_result->num_rows > 0) {
            // Get column names
            $fields = $data_result->fetch_fields();
            $column_names = [];
            foreach ($fields as $field) {
                $column_names[] = "`{$field->name}`";
            }
            
            $output .= "INSERT INTO `{$table}` (" . implode(', ', $column_names) . ") VALUES\n";
            
            $rows = [];
            while ($row = $data_result->fetch_row()) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            
            $output .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $output .= "-- ============================================\n";
    $output .= "-- Backup Complete\n";
    $output .= "-- ============================================\n";
    
    // Write to file
    if (file_put_contents($filepath, $output, LOCK_EX)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'tables' => count($tables),
            'timestamp' => $timestamp
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to write backup file'];
}

/**
 * Delete old backups, keep only the latest $max_backups
 */
function cleanOldBackups($backup_dir, $max_backups) {
    $files = glob($backup_dir . 'fixpoint_backup_*.sql');
    
    if (count($files) > $max_backups) {
        // Sort by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $to_delete = count($files) - $max_backups;
        $deleted = 0;
        for ($i = 0; $i < $to_delete; $i++) {
            if (unlink($files[$i])) {
                $deleted++;
            }
        }
        return $deleted;
    }
    
    return 0;
}

/**
 * Get list of existing backups
 */
function getBackupList($backup_dir) {
    $files = glob($backup_dir . 'fixpoint_backup_*.sql');
    $backups = [];
    
    // Sort newest first
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'size_formatted' => formatFileSize(filesize($file)),
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'age' => getTimeAgo(filemtime($file))
        ];
    }
    
    return $backups;
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

function getTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return floor($diff / 604800) . ' weeks ago';
}

// ============================================
// HANDLE ACTIONS
// ============================================

$message = '';
$message_type = '';

// Create backup
if (isset($_POST['create_backup']) || $is_cli) {
    $result = createBackup($conn, $db_name, $backup_dir);
    
    if ($result['success']) {
        $deleted = cleanOldBackups($backup_dir, $max_backups);
        $message = "Backup created successfully! File: {$result['filename']} ({$result['tables']} tables, " . formatFileSize($result['size']) . ")";
        $message_type = 'success';
        
        if ($is_cli) {
            echo "[" . date('Y-m-d H:i:s') . "] $message\n";
            exit(0);
        }
    } else {
        $message = "Backup failed: " . ($result['error'] ?? 'Unknown error');
        $message_type = 'error';
        
        if ($is_cli) {
            echo "[" . date('Y-m-d H:i:s') . "] ERROR: $message\n";
            exit(1);
        }
    }
}

// Download backup
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']); // Prevent directory traversal
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath) && strpos($filename, 'fixpoint_backup_') === 0) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

// Delete backup
if (isset($_POST['delete_backup'])) {
    $filename = basename($_POST['delete_backup']);
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath) && strpos($filename, 'fixpoint_backup_') === 0) {
        if (unlink($filepath)) {
            $message = "Backup deleted: $filename";
            $message_type = 'success';
        } else {
            $message = "Failed to delete backup";
            $message_type = 'error';
        }
    }
}

// Get backup list
$backups = getBackupList($backup_dir);

// Don't render HTML for CLI
if ($is_cli) exit(0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Admin</title>
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
                    <a href="backup.php" class="nav-link">Backup</a>
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
                <h1 class="welcome-text">💾 Database Backup</h1>
                <p class="user-info">Create and manage database backups to prevent data loss</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <?php if ($message_type === 'success'): ?>
                    <div style="padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; background: #d1fae5; border: 1px solid #10b981; color: #065f46;">
                        ✅<?php echo e($message); ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b;">
                        ❌ <?php echo e($message); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">💾 Total Backups</div>
                    <div class="stat-value"><?php echo count($backups); ?></div>
                    <div class="stat-info">Stored locally</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">📅 Latest Backup</div>
                    <div class="stat-value"><?php echo count($backups) > 0 ? $backups[0]['age'] : 'None'; ?></div>
                    <div class="stat-info"><?php echo count($backups) > 0 ? $backups[0]['date'] : 'No backups yet'; ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-label">📦 Total Size</div>
                    <div class="stat-value"><?php 
                        $total_size = array_sum(array_map(function($b) { return $b['size']; }, $backups));
                        echo formatFileSize($total_size);
                    ?></div>
                    <div class="stat-info">All backup files</div>
                </div>
            </div>

            <!-- Create Backup -->
            <div class="requests-section" style="margin-bottom: 2rem;">
                <h2 class="section-title">⚡ Create New Backup</h2>
                <p style="color: #64748b; margin-bottom: 1rem;">
                    This will create a full backup of all database tables including all data.
                </p>
                <form method="POST" action="">
                    <button type="submit" name="create_backup" class="btn btn-primary btn-large">
                        💾 Create Backup Now
                    </button>
                </form>
            </div>

            <!-- Backup List -->
            <div class="requests-section">
                <h2 class="section-title">📋 Backup History</h2>
                
                <?php if (count($backups) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Age</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $index => $backup): ?>
                                    <tr <?php echo $index === 0 ? 'style="background: #f0fdf4;"' : ''; ?>>
                                        <td>
                                            <strong><?php echo $index + 1; ?></strong>
                                            <?php if ($index === 0): ?>
                                                <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">LATEST</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.85rem;">
                                            <?php echo e($backup['filename']); ?>
                                        </td>
                                        <td><?php echo $backup['size_formatted']; ?></td>
                                        <td><?php echo $backup['date']; ?></td>
                                        <td><?php echo $backup['age']; ?></td>
                                        <td style="display: flex; gap: 0.5rem;">
                                            <a href="backup.php?download=<?php echo urlencode($backup['filename']); ?>" 
                                               class="btn btn-primary" 
                                               style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                ⬇️ Download
                                            </a>
                                            <form method="POST" action="" style="display: inline;" 
                                                onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                                <button type="submit" name="delete_backup" 
                                                        value="<?php echo e($backup['filename']); ?>"
                                                        class="btn btn-outline" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #ef4444; border-color: #ef4444;">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-requests">
                        <div class="no-requests-icon">💾</div>
                        <h3>No backups yet</h3>
                        <p>Click "Create Backup Now" to create your first database backup.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Restore Instructions -->
            <div class="requests-section">
                <h2 class="section-title">🔄 How to Restore a Backup</h2>
                <div style="color: #64748b; line-height: 1.8;">
                    <p><strong>1.</strong> Download the backup file you want to restore</p>
                    <p><strong>2.</strong> Open <strong>phpMyAdmin</strong> (http://localhost/phpmyadmin)</p>
                    <p><strong>3.</strong> Select the <strong>fixpoint</strong> database</p>
                    <p><strong>4.</strong> Click the <strong>Import</strong> tab</p>
                    <p><strong>5.</strong> Choose the downloaded <strong>.sql</strong> file</p>
                    <p><strong>6.</strong> Click <strong>Go</strong> to restore</p>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>