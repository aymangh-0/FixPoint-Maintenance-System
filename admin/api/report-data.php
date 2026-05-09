<?php
/**
 * FixPoint - Admin Smart Reports API
 */

session_start();
require_once __DIR__ . '/../../config/session-security.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/reporting.php';

try {
    $filters = fp_report_get_filters($_GET);
    $data = fp_report_dataset($conn, $filters, true);
    $data['options'] = fp_report_filter_options($conn);

    echo json_encode([
        'success' => true,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Report data API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load report data.',
    ]);
}
?>
