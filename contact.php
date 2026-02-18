<?php
/**
 * FixPoint - Contact Us
 * Contact form and support information
 */

session_start();
require_once 'config/session-security.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['name'] : '';
$user_email = $is_logged_in ? $_SESSION['email'] : '';
$user_role = $is_logged_in ? $_SESSION['role_id'] : 0;

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // In a real application, you would:
        // 1. Send email to support team
        // 2. Store in database
        // 3. Create ticket/notification
        
        // For now, we'll just show success message
        // In production, integrate with email service (PHPMailer, SendGrid, etc.)
        
        $success = "Thank you for contacting us! We'll get back to you within 24-48 hours.";
        
        // Clear form
        $name = '';
        $email = '';
        $subject = '';
        $message = '';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FixPoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .contact-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border-radius: 1rem;
        }
        
        .contact-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .contact-header p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .contact-form-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .contact-info-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .contact-card {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2563eb;
        }
        
        .contact-card-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .contact-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .contact-card-text {
            color: #64748b;
            line-height: 1.6;
        }
        
        .contact-card-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        
        .contact-card-link:hover {
            text-decoration: underline;
        }
        
        .office-hours {
            background: #e0f2fe;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-top: 2rem;
        }
        
        .office-hours h3 {
            color: #0c4a6e;
            margin-bottom: 1rem;
            font-size: 1.125rem;
        }
        
        .office-hours ul {
            list-style: none;
            padding: 0;
        }
        
        .office-hours li {
            color: #0c4a6e;
            padding: 0.5rem 0;
            border-bottom: 1px solid #bae6fd;
        }
        
        .office-hours li:last-child {
            border-bottom: none;
        }
        
        .map-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .map-placeholder {
            width: 100%;
            height: 400px;
            background: #f1f5f9;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1.125rem;
        }
        
        @media (max-width: 768px) {
            .contact-header h1 {
                font-size: 1.75rem;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">
                    <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="logo-icon">🔧</span>
                        <span class="logo-text">FixPoint</span>
                        <span class="logo-subtitle">SEU</span>
                    </a>
                </div>
                <nav class="nav-links">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="help-center.php" class="nav-link">Help Center</a>
                    <a href="contact.php" class="nav-link">Contact Us</a>
                    <?php if ($is_logged_in): ?>
                        <span style="color: #64748b;">👤 <?php echo e($user_name); ?></span>
                        <?php if ($user_role == 1): ?>
                            <a href="admin/dashboard.php" class="btn btn-primary">Dashboard</a>
                        <?php elseif ($user_role == 2): ?>
                            <a href="technician/dashboard.php" class="btn btn-primary">Dashboard</a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="btn btn-primary">Dashboard</a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-outline">Login</a>
                        <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <div class="contact-container">
        <!-- Header -->
        <div class="contact-header">
            <h1>📞 Contact Us</h1>
            <p>Get in touch with our support team. We're here to help!</p>
        </div>

        <!-- Main Content -->
        <div class="contact-content">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2 class="section-title">Send us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo e($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo e($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            value="<?php echo $is_logged_in ? e($user_name) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            value="<?php echo $is_logged_in ? e($user_email) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <select id="subject" name="subject" class="form-input" required>
                            <option value="">-- Select Subject --</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Account Problem">Account Problem</option>
                            <option value="Request Status">Request Status Inquiry</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="Feedback">General Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-input" 
                            rows="6"
                            placeholder="Please provide as much detail as possible..."
                            required
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        📧 Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-info-section">
                <h2 class="section-title">Contact Information</h2>
                
                <div class="contact-card">
                    <div class="contact-card-icon">📧</div>
                    <div class="contact-card-title">Email Support</div>
                    <div class="contact-card-text">
                        General Inquiries: 
                        <a href="mailto:support@fixpoint.seu.edu.sa" class="contact-card-link">
                            support@fixpoint.seu.edu.sa
                        </a>
                        <br>
                        Technical Support: 
                        <a href="mailto:tech@fixpoint.seu.edu.sa" class="contact-card-link">
                            tech@fixpoint.seu.edu.sa
                        </a>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-card-icon">📞</div>
                    <div class="contact-card-title">Phone</div>
                    <div class="contact-card-text">
                        Main Line: <strong>+966 11 XXX XXXX</strong>
                        <br>
                        Support: <strong>+966 11 XXX YYYY</strong>
                        <br>
                        <small style="color: #94a3b8;">Available during office hours</small>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-card-icon">📍</div>
                    <div class="contact-card-title">Location</div>
                    <div class="contact-card-text">
                        Saudi Electronic University
                        <br>
                        FixPoint Support Center
                        <br>
                        Building: IT Services
                        <br>
                        Riyadh, Saudi Arabia
                    </div>
                </div>
                
                <div class="office-hours">
                    <h3>⏰ Office Hours</h3>
                    <ul>
                        <li><strong>Sunday - Thursday:</strong> 8:00 AM - 4:00 PM</li>
                        <li><strong>Friday - Saturday:</strong> Closed</li>
                        <li><strong>Email Support:</strong> 24/7 (response within 24-48 hours)</li>
                    </ul>
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="help-center.php" class="btn btn-secondary" style="display: block; text-align: center;">
                        📚 Visit Help Center
                    </a>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2 class="section-title">📍 Find Us</h2>
            <div class="map-placeholder">
                🗺️ Map Location
                <br>
                <small style="color: #94a3b8;">(Google Maps integration coming soon)</small>
            </div>
            <p style="text-align: center; margin-top: 1rem; color: #64748b;">
                Saudi Electronic University - Main Campus, Riyadh
            </p>
        </div>

        <!-- FAQ Quick Links -->
        <div style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h2 class="section-title">💡 Quick Help</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                    <h4 style="color: #1e293b; margin-bottom: 0.5rem;">📝 Submit Request</h4>
                    <p style="color: #64748b; font-size: 0.875rem;">Learn how to submit maintenance requests</p>
                    <a href="help-center.php#submit" style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Read Guide →</a>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                    <h4 style="color: #1e293b; margin-bottom: 0.5rem;">🔍 Track Status</h4>
                    <p style="color: #64748b; font-size: 0.875rem;">Check your request status</p>
                    <a href="help-center.php#track" style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Read Guide →</a>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                    <h4 style="color: #1e293b; margin-bottom: 0.5rem;">❓ Common Issues</h4>
                    <p style="color: #64748b; font-size: 0.875rem;">Troubleshooting guide</p>
                    <a href="help-center.php#troubleshooting" style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Read Guide →</a>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                    <h4 style="color: #1e293b; margin-bottom: 0.5rem;">👤 Account Help</h4>
                    <p style="color: #64748b; font-size: 0.875rem;">Login and registration help</p>
                    <a href="help-center.php#account" style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Read Guide →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FixPoint</h3>
                    <p>Maintenance management system for Saudi Electronic University</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="index.php">Home</a>
                    <a href="help-center.php">Help Center</a>
                    <a href="contact.php">Contact Us</a>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <p>Email: support@fixpoint.seu.edu.sa</p>
                    <p>Phone: +966 11 XXX XXXX</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 FixPoint - Saudi Electronic University. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>