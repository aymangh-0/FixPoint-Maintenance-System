<?php
/**
 * FixPoint - Shared Reporting Helpers
 *
 * Centralizes dashboard/report queries so on-screen reports, APIs, and exports
 * can use the same filtered totals.
 */

function fp_report_valid_date($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    $parts = explode('-', $value);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function fp_report_int_or_null($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $int_value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $int_value === false ? null : $int_value;
}

function fp_report_get_filters($input = null) {
    $source = $input === null ? $_GET : $input;

    $date_from = isset($source['date_from']) && fp_report_valid_date($source['date_from'])
        ? $source['date_from']
        : '2024-01-01';

    $date_to_display = isset($source['date_to']) && fp_report_valid_date($source['date_to'])
        ? $source['date_to']
        : date('Y-m-d');

    if (strtotime($date_from) > strtotime($date_to_display)) {
        $date_from = $date_to_display;
    }

    return [
        'date_from' => $date_from,
        'date_to' => $date_to_display . ' 23:59:59',
        'date_to_display' => $date_to_display,
        'priority' => fp_report_int_or_null($source['priority'] ?? null),
        'location' => fp_report_int_or_null($source['location'] ?? null),
        'technician' => fp_report_int_or_null($source['technician'] ?? null),
        'category' => fp_report_int_or_null($source['category'] ?? null),
        'status' => fp_report_int_or_null($source['status'] ?? null),
    ];
}

function fp_report_where($filters, $alias = 'mr') {
    $where = ["$alias.SubmittedAt BETWEEN ? AND ?"];
    $types = 'ss';
    $params = [$filters['date_from'], $filters['date_to']];

    if (!empty($filters['priority'])) {
        $where[] = "$alias.PriorityID = ?";
        $types .= 'i';
        $params[] = $filters['priority'];
    }

    if (!empty($filters['location'])) {
        $where[] = "$alias.LocationID = ?";
        $types .= 'i';
        $params[] = $filters['location'];
    }

    if (!empty($filters['category'])) {
        $where[] = "$alias.CategoryID = ?";
        $types .= 'i';
        $params[] = $filters['category'];
    }

    if (!empty($filters['status'])) {
        $where[] = "$alias.StatusID = ?";
        $types .= 'i';
        $params[] = $filters['status'];
    }

    if (!empty($filters['technician'])) {
        $where[] = "EXISTS (
            SELECT 1
            FROM assignment a_filter
            WHERE a_filter.RequestID = $alias.RequestID
            AND a_filter.TechnicianID = ?
        )";
        $types .= 'i';
        $params[] = $filters['technician'];
    }

    return [
        'sql' => implode(' AND ', $where),
        'types' => $types,
        'params' => $params,
    ];
}

function fp_report_execute($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare report query: ' . $conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to execute report query: ' . $stmt->error);
    }

    return $stmt;
}

function fp_report_fetch_all($conn, $sql, $types = '', $params = []) {
    $stmt = fp_report_execute($conn, $sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function fp_report_fetch_one($conn, $sql, $types = '', $params = []) {
    $stmt = fp_report_execute($conn, $sql, $types, $params);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: [];
}

function fp_report_count_where($conn, $filters, $extra_where = '') {
    $where = fp_report_where($filters);
    $sql = "SELECT COUNT(DISTINCT mr.RequestID) AS total
            FROM maintenancerequest mr
            WHERE {$where['sql']}";

    if ($extra_where !== '') {
        $sql .= " AND $extra_where";
    }

    $row = fp_report_fetch_one($conn, $sql, $where['types'], $where['params']);
    return (int)($row['total'] ?? 0);
}

function fp_report_summary($conn, $filters) {
    $total = fp_report_count_where($conn, $filters);
    $completed = fp_report_count_where($conn, $filters, 'mr.StatusID = 5');
    $pending = fp_report_count_where($conn, $filters, 'mr.StatusID = 1');
    $in_progress = fp_report_count_where($conn, $filters, 'mr.StatusID IN (3, 4)');
    $cancelled = fp_report_count_where($conn, $filters, 'mr.StatusID = 6');

    $where = fp_report_where($filters);
    $avg_row = fp_report_fetch_one(
        $conn,
        "SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, mr.SubmittedAt, mr.CompletedAt)), 1) AS avg_hours
         FROM maintenancerequest mr
         WHERE mr.StatusID = 5
         AND mr.CompletedAt IS NOT NULL
         AND {$where['sql']}",
        $where['types'],
        $where['params']
    );

    $feedback_row = fp_report_fetch_one(
        $conn,
        "SELECT ROUND(AVG(f.Rating), 1) AS avg_rating,
                COUNT(DISTINCT f.FeedbackID) AS feedback_count
         FROM feedback f
         JOIN maintenancerequest mr ON f.RequestID = mr.RequestID
         WHERE {$where['sql']}",
        $where['types'],
        $where['params']
    );

    $users_row = fp_report_fetch_one($conn, "SELECT COUNT(*) AS total FROM user WHERE RoleID IN (3, 4)");
    $techs_row = fp_report_fetch_one($conn, "SELECT COUNT(*) AS total FROM user WHERE RoleID = 2");

    return [
        'total_requests' => $total,
        'completed' => $completed,
        'pending' => $pending,
        'in_progress' => $in_progress,
        'cancelled' => $cancelled,
        'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        'avg_completion_hours' => $avg_row['avg_hours'] !== null ? (float)$avg_row['avg_hours'] : 0,
        'avg_rating' => $feedback_row['avg_rating'] !== null ? (float)$feedback_row['avg_rating'] : 0,
        'feedback_count' => (int)($feedback_row['feedback_count'] ?? 0),
        'total_users' => (int)($users_row['total'] ?? 0),
        'total_technicians' => (int)($techs_row['total'] ?? 0),
    ];
}

function fp_report_breakdown($conn, $filters, $join, $label_column, $group_column, $order_column = 'count DESC', $limit = null) {
    $where = fp_report_where($filters);
    $limit_sql = $limit ? ' LIMIT ' . (int)$limit : '';

    return fp_report_fetch_all(
        $conn,
        "SELECT $label_column AS label,
                COUNT(DISTINCT mr.RequestID) AS value
         FROM maintenancerequest mr
         $join
         WHERE {$where['sql']}
         GROUP BY $group_column, $label_column
         ORDER BY $order_column
         $limit_sql",
        $where['types'],
        $where['params']
    );
}

function fp_report_requests_over_time($conn, $filters) {
    $where = fp_report_where($filters);

    return fp_report_fetch_all(
        $conn,
        "SELECT DATE(mr.SubmittedAt) AS date,
                COUNT(DISTINCT mr.RequestID) AS count,
                COUNT(DISTINCT CASE WHEN mr.StatusID = 5 THEN mr.RequestID END) AS completed
         FROM maintenancerequest mr
         WHERE {$where['sql']}
         GROUP BY DATE(mr.SubmittedAt)
         ORDER BY date ASC",
        $where['types'],
        $where['params']
    );
}

function fp_report_feedback_ratings($conn, $filters) {
    $where = fp_report_where($filters);

    return fp_report_fetch_all(
        $conn,
        "SELECT CONCAT(f.Rating, ' Stars') AS label,
                COUNT(DISTINCT f.FeedbackID) AS value
         FROM feedback f
         JOIN maintenancerequest mr ON f.RequestID = mr.RequestID
         WHERE {$where['sql']}
         GROUP BY f.Rating
         ORDER BY f.Rating DESC",
        $where['types'],
        $where['params']
    );
}

function fp_report_technician_performance($conn, $filters) {
    $where = fp_report_where($filters);
    $tech_where = '';
    $types = $where['types'];
    $params = $where['params'];

    if (!empty($filters['technician'])) {
        $tech_where = ' AND u.UserID = ?';
        $types .= 'i';
        $params[] = $filters['technician'];
    }

    return fp_report_fetch_all(
        $conn,
        "SELECT u.UserID AS technician_id,
                u.Name AS name,
                COUNT(DISTINCT mr.RequestID) AS assigned,
                COUNT(DISTINCT CASE WHEN mr.StatusID = 5 THEN mr.RequestID END) AS completed,
                ROUND(AVG(CASE
                    WHEN mr.StatusID = 5 AND mr.CompletedAt IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, mr.SubmittedAt, mr.CompletedAt)
                END), 1) AS avg_completion_hours,
                ROUND(AVG(f.Rating), 1) AS avg_rating
         FROM user u
         LEFT JOIN assignment a ON u.UserID = a.TechnicianID
         LEFT JOIN maintenancerequest mr ON a.RequestID = mr.RequestID AND {$where['sql']}
         LEFT JOIN feedback f ON mr.RequestID = f.RequestID
         WHERE u.RoleID = 2
         $tech_where
         GROUP BY u.UserID, u.Name
         ORDER BY completed DESC, assigned DESC, u.Name ASC",
        $types,
        $params
    );
}

function fp_report_top_requesters($conn, $filters, $limit = 10) {
    $where = fp_report_where($filters);

    return fp_report_fetch_all(
        $conn,
        "SELECT u.UserID AS user_id,
                u.Name AS name,
                u.Email AS email,
                r.RoleName AS role,
                COUNT(DISTINCT mr.RequestID) AS request_count
         FROM maintenancerequest mr
         JOIN user u ON mr.UserID = u.UserID
         JOIN role r ON u.RoleID = r.RoleID
         WHERE {$where['sql']}
         GROUP BY u.UserID, u.Name, u.Email, r.RoleName
         ORDER BY request_count DESC, u.Name ASC
         LIMIT " . (int)$limit,
        $where['types'],
        $where['params']
    );
}

function fp_report_urgent_requests($conn, $filters, $limit = 5) {
    $where = fp_report_where($filters);

    return fp_report_fetch_all(
        $conn,
        "SELECT mr.RequestID AS request_id,
                mr.Title AS title,
                mr.SubmittedAt AS submitted_at,
                u.Name AS requester,
                l.BuildingName AS building,
                l.RoomNumber AS room,
                c.CategoryName AS category,
                p.PriorityLevel AS priority,
                s.StatusName AS status
         FROM maintenancerequest mr
         JOIN user u ON mr.UserID = u.UserID
         JOIN location l ON mr.LocationID = l.LocationID
         JOIN category c ON mr.CategoryID = c.CategoryID
         JOIN priority p ON mr.PriorityID = p.PriorityID
         JOIN status s ON mr.StatusID = s.StatusID
         WHERE {$where['sql']}
         AND mr.PriorityID >= 3
         AND mr.StatusID NOT IN (5, 6)
         ORDER BY mr.PriorityID DESC, mr.SubmittedAt ASC
         LIMIT " . (int)$limit,
        $where['types'],
        $where['params']
    );
}

function fp_report_filter_options($conn) {
    return [
        'priorities' => fp_report_fetch_all($conn, "SELECT PriorityID AS id, PriorityLevel AS label FROM priority ORDER BY PriorityID"),
        'locations' => fp_report_fetch_all($conn, "SELECT LocationID AS id, BuildingName AS label FROM location ORDER BY BuildingName, FloorNumber, RoomNumber"),
        'technicians' => fp_report_fetch_all($conn, "SELECT UserID AS id, Name AS label FROM user WHERE RoleID = 2 ORDER BY Name"),
        'categories' => fp_report_fetch_all($conn, "SELECT CategoryID AS id, CategoryName AS label FROM category ORDER BY CategoryName"),
        'statuses' => fp_report_fetch_all($conn, "SELECT StatusID AS id, StatusName AS label FROM status ORDER BY StatusID"),
    ];
}

function fp_report_dataset($conn, $filters, $include_tables = true) {
    $data = [
        'filters' => [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to_display'],
            'priority' => $filters['priority'],
            'location' => $filters['location'],
            'technician' => $filters['technician'],
            'category' => $filters['category'],
            'status' => $filters['status'],
        ],
        'summary' => fp_report_summary($conn, $filters),
        'series' => [
            'requests_over_time' => fp_report_requests_over_time($conn, $filters),
            'by_status' => fp_report_breakdown($conn, $filters, 'JOIN status s ON mr.StatusID = s.StatusID', 's.StatusName', 's.StatusID', 's.StatusID ASC'),
            'by_priority' => fp_report_breakdown($conn, $filters, 'JOIN priority p ON mr.PriorityID = p.PriorityID', 'p.PriorityLevel', 'p.PriorityID', 'p.PriorityID DESC'),
            'by_category' => fp_report_breakdown($conn, $filters, 'JOIN category c ON mr.CategoryID = c.CategoryID', 'c.CategoryName', 'c.CategoryID', 'value DESC, label ASC', 10),
            'by_location' => fp_report_breakdown($conn, $filters, 'JOIN location l ON mr.LocationID = l.LocationID', 'l.BuildingName', 'l.LocationID', 'value DESC, label ASC', 10),
            'feedback_ratings' => fp_report_feedback_ratings($conn, $filters),
        ],
    ];

    if ($include_tables) {
        $data['tables'] = [
            'technician_performance' => fp_report_technician_performance($conn, $filters),
            'top_requesters' => fp_report_top_requesters($conn, $filters),
            'urgent_requests' => fp_report_urgent_requests($conn, $filters),
        ];
    }

    return $data;
}
?>
