<?php
/**
 * FixPoint - Auto-Assign Technician
 * Automatically assigns the least-busy available technician to a new request
 * 
 * Logic:
 * 1. Find all technicians (RoleID = 2)
 * 2. Count their active tasks (StatusID = 3 or 4 = Assigned/In Progress)
 * 3. Pick the technician with fewest active tasks
 * 4. If all technicians have 5+ active tasks → no auto-assign (stays Pending)
 * 5. Assign the technician and change status to Assigned (StatusID = 3)
 * 
 * Usage: Call autoAssignTechnician($conn, $request_id) after inserting a new request
 */

function autoAssignTechnician($conn, $request_id, $max_active_tasks = 5) {
    
    // Find the least-busy technician
    // Counts only active tasks (Assigned = 3, In Progress = 4)
    $sql = "SELECT 
                u.UserID,
                u.Name,
                COUNT(CASE WHEN mr.StatusID IN (3, 4) THEN 1 END) as ActiveTasks
            FROM user u
            LEFT JOIN assignment a ON u.UserID = a.TechnicianID
            LEFT JOIN maintenancerequest mr ON a.RequestID = mr.RequestID AND mr.StatusID IN (3, 4)
            WHERE u.RoleID = 2
            GROUP BY u.UserID
            HAVING ActiveTasks < ?
            ORDER BY ActiveTasks ASC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $max_active_tasks);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // No available technician
    if ($result->num_rows == 0) {
        return [
            'assigned' => false,
            'reason' => 'No available technician (all have ' . $max_active_tasks . '+ active tasks)'
        ];
    }
    
    $technician = $result->fetch_assoc();
    $tech_id = $technician['UserID'];
    $tech_name = $technician['Name'];
    $stmt->close();   
// Get first admin ID for auto-assignment
    $admin_sql = "SELECT UserID FROM user WHERE RoleID = 1 LIMIT 1";
    $admin_result = $conn->query($admin_sql);
    $admin_id = $admin_result->fetch_assoc()['UserID'];

    $assign_sql = "INSERT INTO assignment (RequestID, TechnicianID, AdminID, AssignedAt) VALUES (?, ?, ?, NOW())";
    $assign_stmt = $conn->prepare($assign_sql);
    $assign_stmt->bind_param("iii", $request_id, $tech_id, $admin_id);
    
    if (!$assign_stmt->execute()) {
        return [
            'assigned' => false,
            'reason' => 'Database error: Could not create assignment'
        ];
    }
    $assign_stmt->close();
    
    // Update request status to Assigned (StatusID = 3)
    $status_sql = "UPDATE maintenancerequest SET StatusID = 3 WHERE RequestID = ?";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $request_id);
    $status_stmt->execute();
    $status_stmt->close();
    
    // Log status change (Pending → Assigned)
	if (function_exists('logStatusChange')) {
    logStatusChange($conn, $request_id, 1, 3, $admin_id);
	}
    
    // Notify the technician
    if (function_exists('createNotification')) {
        createNotification(
            $conn,
            $tech_id,
            "You have been auto-assigned to request #$request_id",
            $request_id
        );
    }
    
    // Notify admins about auto-assignment
    $admin_sql = "SELECT UserID FROM user WHERE RoleID = 1";
    $admin_result = $conn->query($admin_sql);
    while ($admin = $admin_result->fetch_assoc()) {
        if (function_exists('createNotification')) {
            createNotification(
                $conn,
                $admin['UserID'],
                "Request #$request_id was auto-assigned to $tech_name",
                $request_id
            );
        }
    }
    
    // Audit log
    if (function_exists('logAuditAction')) {
        logAuditAction($conn, $admin_id, 'AUTO_ASSIGN', 'assignment', $request_id, null, 
   		 "Auto-assigned to $tech_name (UserID: $tech_id) - Active tasks: " . $technician['ActiveTasks']);
    }
    
    return [
        'assigned' => true,
        'technician_id' => $tech_id,
        'technician_name' => $tech_name,
        'active_tasks' => $technician['ActiveTasks']
    ];
}
?>