<?php
/**
 * FixPoint - Email Service
 * Handles sending email notifications to users
 */

// ============================================
// EMAIL CONFIGURATION
// ============================================

define('EMAIL_MODE', 'smtp');

define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'a2ab48001@smtp-brevo.com');
define('SMTP_PASSWORD', 'xsmtpsib-7d603282b18175fcb03bb97e7a119161111d3eb0afbfb16364efa6c9cd043dcf-VW5i6AJhdEH9ZE3m');
define('SMTP_FROM_EMAIL', 'a.aalghamdi147@gmail.com');
define('SMTP_FROM_NAME',  'FixPoint - SEU Maintenance');
define('EMAIL_LOG_FILE', __DIR__ . '/../logs/email_log.txt');

// ============================================
// EMAIL TEMPLATES
// ============================================

function getEmailTemplate($type, $data = []) {
    $templates = [
        
        'new_request' => [
            'subject' => '🔔 New Maintenance Request #{request_id} - {title}',
            'body' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">🔧 FixPoint</h1>
                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">University Maintenance System</p>
                    </div>
                    <div style="background: white; padding: 30px; border: 1px solid #e2e8f0;">
                        <h2 style="color: #1e293b; margin-top: 0;">📝 New Maintenance Request</h2>
                        <p style="color: #64748b;">A new maintenance request has been submitted and needs your review.</p>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Request ID:</td><td style="padding: 8px 0; color: #1e293b;">#{request_id}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Title:</td><td style="padding: 8px 0; color: #1e293b;">{title}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Submitted by:</td><td style="padding: 8px 0; color: #1e293b;">{requester_name}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Location:</td><td style="padding: 8px 0; color: #1e293b;">{location}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Category:</td><td style="padding: 8px 0; color: #1e293b;">{category}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Priority:</td><td style="padding: 8px 0; color: #1e293b;">{priority}</td></tr>
                            </table>
                        </div>
                        <p style="color: #64748b;"><strong>Description:</strong><br>{description}</p>
                        <div style="text-align: center; margin-top: 25px;">
                            <a href="{site_url}/admin/request-details.php?id={request_id}" 
                            style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                                📋 Review Request
                            </a>
                        </div>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;">
                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">FixPoint - Saudi Electronic University © 2026</p>
                    </div>
                </div>'
        ],
        
        'status_update' => [
            'subject' => '📊 Request #{request_id} Status Updated - {new_status}',
            'body' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">🔧 FixPoint</h1>
                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">University Maintenance System</p>
                    </div>
                    <div style="background: white; padding: 30px; border: 1px solid #e2e8f0;">
                        <h2 style="color: #1e293b; margin-top: 0;">📊 Request Status Updated</h2>
                        <p style="color: #64748b;">Hi {user_name}, your maintenance request status has been updated.</p>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Request ID:</td><td style="padding: 8px 0; color: #1e293b;">#{request_id}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Title:</td><td style="padding: 8px 0; color: #1e293b;">{title}</td></tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Status:</td>
                                    <td style="padding: 8px 0;">
                                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 600;">{new_status}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 25px;">
                            <a href="{site_url}/user/request-details.php?id={request_id}" 
                                style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                                👁️ View Request Details
                            </a>
                        </div>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;">
                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">FixPoint - Saudi Electronic University © 2026</p>
                    </div>
                </div>'
        ],
        'technician_assigned' => [
            'subject' => '🔧 New Task Assigned - Request #{request_id}',
            'body' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">🔧 FixPoint</h1>
                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">University Maintenance System</p>
                    </div>
                    <div style="background: white; padding: 30px; border: 1px solid #e2e8f0;">
                        <h2 style="color: #1e293b; margin-top: 0;">👨‍🔧 New Task Assigned to You</h2>
                        <p style="color: #64748b;">Hi {tech_name}, you have been assigned a new maintenance task.</p>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Request ID:</td><td style="padding: 8px 0; color: #1e293b;">#{request_id}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Title:</td><td style="padding: 8px 0; color: #1e293b;">{title}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Location:</td><td style="padding: 8px 0; color: #1e293b;">{location}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Category:</td><td style="padding: 8px 0; color: #1e293b;">{category}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Priority:</td><td style="padding: 8px 0; color: #1e293b;"><strong style="color: #ef4444;">{priority}</strong></td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Requester:</td><td style="padding: 8px 0; color: #1e293b;">{requester_name}</td></tr>
                            </table>
                        </div>
                        <p style="color: #64748b;"><strong>Description:</strong><br>{description}</p>
                        <div style="text-align: center; margin-top: 25px;">
                            <a href="{site_url}/technician/task-details.php?id={request_id}" 
                            style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                                🔧 View Task Details
                            </a>
                        </div>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;">
                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">FixPoint - Saudi Electronic University © 2026</p>
                    </div>
                </div>'
        ],
        
        'request_completed' => [
            'subject' => '✅ Request #{request_id} Completed - Please Leave Feedback',
            'body' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">✅ Request Completed!</h1>
                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">FixPoint - University Maintenance System</p>
                    </div>
                    <div style="background: white; padding: 30px; border: 1px solid #e2e8f0;">
                        <h2 style="color: #1e293b; margin-top: 0;">Great news, {user_name}!</h2>
                        <p style="color: #64748b;">Your maintenance request has been completed successfully.</p>
                        <div style="background: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #a7f3d0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 8px 0; color: #065f46; font-weight: 600;">Request ID:</td><td style="padding: 8px 0; color: #065f46;">#{request_id}</td></tr>
                                <tr><td style="padding: 8px 0; color: #065f46; font-weight: 600;">Title:</td><td style="padding: 8px 0; color: #065f46;">{title}</td></tr>
                                <tr><td style="padding: 8px 0; color: #065f46; font-weight: 600;">Status:</td><td style="padding: 8px 0;"><span style="background: #065f46; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">✅ Completed</span></td></tr>
                            </table>
                        </div>
                        <p style="color: #64748b;">We would love to hear your feedback! Please take a moment to rate the service.</p>
                        <div style="text-align: center; margin-top: 25px;">
                            <a href="{site_url}/user/submit-feedback.php?id={request_id}" 
                            style="background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                                ⭐ Leave Feedback
                            </a>
                        </div>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;">
                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">FixPoint - Saudi Electronic University © 2026</p>
                    </div>
                </div>'
        ],
        
        'feedback_received' => [
            'subject' => '⭐ New Feedback for Request #{request_id} - {rating} Stars',
            'body' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">🔧 FixPoint</h1>
                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">University Maintenance System</p>
                    </div>
                    <div style="background: white; padding: 30px; border: 1px solid #e2e8f0;">
                        <h2 style="color: #1e293b; margin-top: 0;">⭐ New Feedback Received</h2>
                        <p style="color: #64748b;">A user has submitted feedback for a completed request.</p>
                        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                            <div style="font-size: 2rem;">{stars}</div>
                            <div style="color: #92400e; font-weight: 600; margin-top: 5px;">{rating} out of 5 Stars</div>
                        </div>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Request:</td><td style="padding: 8px 0; color: #1e293b;">#{request_id} - {title}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">From:</td><td style="padding: 8px 0; color: #1e293b;">{user_name}</td></tr>
                                <tr><td style="padding: 8px 0; color: #64748b; font-weight: 600;">Comment:</td><td style="padding: 8px 0; color: #1e293b;">{comment}</td></tr>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 25px;">
                            <a href="{site_url}/admin/all-feedback.php" 
                            style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                                📊 View All Feedback
                            </a>
                        </div>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;">
                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">FixPoint - Saudi Electronic University © 2026</p>
                    </div>
                </div>'
        ]
    ];
    
    if (!isset($templates[$type])) {
        return null;
    }
    
    $template = $templates[$type];
    $subject = $template['subject'];
    $body = $template['body'];
    
    foreach ($data as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
        $body = str_replace('{' . $key . '}', htmlspecialchars($value), $body);
    }
    
    $site_url = 'http://localhost/fixpoint';
    $subject = str_replace('{site_url}', $site_url, $subject);
    $body = str_replace('{site_url}', $site_url, $body);
    
    return ['subject' => $subject, 'body' => $body];
}

// ============================================
// SEND EMAIL FUNCTION
// ============================================

function sendEmail($to_email, $to_name, $subject, $html_body) {
    if (EMAIL_MODE === 'smtp') {
        return sendEmailSMTP($to_email, $to_name, $subject, $html_body);
    }
    return logEmail($to_email, $to_name, $subject, $html_body);
}

function sendEmailSMTP($to_email, $to_name, $subject, $html_body) {
    $phpmailer_path = __DIR__ . '/../vendor/autoload.php';
    
    if (!file_exists($phpmailer_path)) {
        logEmail($to_email, $to_name, $subject, $html_body, '[SMTP FALLBACK - PHPMailer not installed]');
        return false;
    }
    
    require_once $phpmailer_path;
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));
        
        $mail->send();
        logEmail($to_email, $to_name, $subject, '[SENT SUCCESSFULLY VIA SMTP]');
        return true;
        
    } catch (Exception $e) {
        logEmail($to_email, $to_name, $subject, '[SMTP ERROR: ' . $mail->ErrorInfo . ']');
        return false;
    }
}

function logEmail($to_email, $to_name, $subject, $html_body, $note = '') {
    $log_dir = dirname(EMAIL_LOG_FILE);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $separator = str_repeat('=', 60);
    
    $log_entry = "\n$separator\n";
    $log_entry .= "📧 EMAIL LOG - $timestamp\n";
    $log_entry .= "$separator\n";
    if ($note) $log_entry .= "NOTE: $note\n";
    $log_entry .= "TO:      $to_name <$to_email>\n";
    $log_entry .= "SUBJECT: $subject\n";
    $log_entry .= "MODE:    " . EMAIL_MODE . "\n";
    $log_entry .= "STATUS:  " . (EMAIL_MODE === 'log' ? 'Logged (not sent)' : 'Attempted') . "\n";
    $log_entry .= "BODY PREVIEW: " . substr(strip_tags($html_body), 0, 200) . "...\n";
    $log_entry .= "$separator\n";
    
    file_put_contents(EMAIL_LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    return true;
}

// ============================================
// CONVENIENCE FUNCTIONS
// ============================================

function emailNewRequest($conn, $request_id, $title, $description, $requester_name, $location, $category, $priority) {
    $sql = "SELECT Email, Name FROM user WHERE RoleID = 1";
    $result = $conn->query($sql);
    
    while ($admin = $result->fetch_assoc()) {
        $template = getEmailTemplate('new_request', [
            'request_id'     => $request_id,
            'title'          => $title,
            'description'    => $description,
            'requester_name' => $requester_name,
            'location'       => $location,
            'category'       => $category,
            'priority'       => $priority
        ]);
        if ($template) {
            sendEmail($admin['Email'], $admin['Name'], $template['subject'], $template['body']);
        }
    }
}

function emailStatusUpdate($conn, $request_id, $user_id, $new_status) {
    $sql = "SELECT u.Email, u.Name, mr.Title 
            FROM user u 
            JOIN maintenancerequest mr ON mr.UserID = u.UserID 
            WHERE u.UserID = ? AND mr.RequestID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $request_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if ($data) {
        $template = getEmailTemplate('status_update', [
            'request_id' => $request_id,
            'title'      => $data['Title'],
            'user_name'  => $data['Name'],
            'new_status' => $new_status
        ]);
        if ($template) {
            sendEmail($data['Email'], $data['Name'], $template['subject'], $template['body']);
        }
    }
}

function emailTechnicianAssigned($conn, $request_id, $tech_id) {
    $tech_stmt = $conn->prepare("SELECT Email, Name FROM user WHERE UserID = ?");
    $tech_stmt->bind_param("i", $tech_id);
    $tech_stmt->execute();
    $tech = $tech_stmt->get_result()->fetch_assoc();
    
    $req_stmt = $conn->prepare("SELECT mr.Title, mr.Description, u.Name as RequesterName,
                CONCAT(l.BuildingName, ' - Floor ', l.FloorNumber, ' - Room ', l.RoomNumber) as Location,
                c.CategoryName, p.PriorityLevel
                FROM maintenancerequest mr
                JOIN user u ON mr.UserID = u.UserID
                JOIN location l ON mr.LocationID = l.LocationID
                JOIN category c ON mr.CategoryID = c.CategoryID
                JOIN priority p ON mr.PriorityID = p.PriorityID
                WHERE mr.RequestID = ?");
    $req_stmt->bind_param("i", $request_id);
    $req_stmt->execute();
    $req = $req_stmt->get_result()->fetch_assoc();
    
    if ($tech && $req) {
        $template = getEmailTemplate('technician_assigned', [
            'request_id'     => $request_id,
            'title'          => $req['Title'],
            'description'    => $req['Description'],
            'tech_name'      => $tech['Name'],
            'requester_name' => $req['RequesterName'],
            'location'       => $req['Location'],
            'category'       => $req['CategoryName'],
            'priority'       => $req['PriorityLevel']
        ]);
        if ($template) {
            sendEmail($tech['Email'], $tech['Name'], $template['subject'], $template['body']);
        }
    }
}

function emailRequestCompleted($conn, $request_id) {
    $stmt = $conn->prepare("SELECT u.Email, u.Name, mr.Title 
            FROM maintenancerequest mr
            JOIN user u ON mr.UserID = u.UserID
            WHERE mr.RequestID = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if ($data) {
        $template = getEmailTemplate('request_completed', [
            'request_id' => $request_id,
            'title'      => $data['Title'],
            'user_name'  => $data['Name']
        ]);
        if ($template) {
            sendEmail($data['Email'], $data['Name'], $template['subject'], $template['body']);
        }
    }
}

function emailFeedbackReceived($conn, $request_id, $user_name, $rating, $comment) {
    $stmt = $conn->prepare("SELECT Title FROM maintenancerequest WHERE RequestID = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    $stars = str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
    
    $admin_result = $conn->query("SELECT Email, Name FROM user WHERE RoleID = 1");
    
    while ($admin = $admin_result->fetch_assoc()) {
        $template = getEmailTemplate('feedback_received', [
            'request_id' => $request_id,
            'title'      => $req ? $req['Title'] : 'N/A',
            'user_name'  => $user_name,
            'rating'     => $rating,
            'stars'      => $stars,
            'comment'    => $comment ?: 'No comment provided'
        ]);
        if ($template) {
            sendEmail($admin['Email'], $admin['Name'], $template['subject'], $template['body']);
        }
    }
}
?>