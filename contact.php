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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; color: #1e293b; background: #f8fafc; line-height: 1.6; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1140px; margin: 0 auto; padding: 0 24px; }

        /* Header */
        .header { padding: 14px 0; background: #0F172A; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 9px; }
        .logo-dot { width: 30px; height: 30px; background: #2563EB; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .logo-dot svg { width: 15px; height: 15px; stroke: #fff; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .logo-name { font-size: 1.05rem; font-weight: 700; color: #fff; }
        .logo-badge { font-size: 0.6rem; font-weight: 700; color: #2563EB; background: rgba(37,99,235,0.15); padding: 2px 7px; border-radius: 4px; }
        .header-right { display: flex; align-items: center; gap: 6px; }
        .h-link { font-size: 0.825rem; font-weight: 500; color: rgba(255,255,255,0.6); padding: 7px 14px; border-radius: 7px; transition: all 0.2s; }
        .h-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .h-btn { font-size: 0.825rem; font-weight: 600; color: #fff; background: #2563EB; padding: 7px 18px; border-radius: 7px; transition: all 0.2s; }
        .h-btn:hover { background: #1D4ED8; }

        /* Footer */
        .ft { background: #080E1A; padding: 44px 0 22px; color: rgba(255,255,255,0.5); }
        .ft-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.2fr; gap: 28px; margin-bottom: 28px; }
        .ft-brand { font-size: 0.84rem; line-height: 1.7; margin-top: 10px; }
        .ft h4 { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }
        .ft ul { list-style: none; } .ft ul li { margin-bottom: 7px; font-size: 0.84rem; }
        .ft ul a { color: rgba(255,255,255,0.45); transition: color 0.2s; } .ft ul a:hover { color: #fff; }
        .ft-line { border-top: 1px solid rgba(255,255,255,0.06); padding-top: 18px; display: flex; justify-content: space-between; font-size: 0.75rem; flex-wrap: wrap; gap: 6px; }

        /* Form */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.85rem; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.625rem; font-size: 0.925rem; font-family: inherit; color: #1e293b; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-submit { width: 100%; padding: 0.85rem; background: #2563eb; color: white; border: none; border-radius: 0.625rem; font-size: 0.95rem; font-weight: 700; font-family: inherit; cursor: pointer; transition: all 0.25s; box-shadow: 0 2px 8px rgba(37,99,235,0.2); }
        .btn-submit:hover { background: #1d4ed8; transform: translateY(-1px); }
        .alert { padding: 0.85rem 1rem; border-radius: 0.625rem; margin-bottom: 1.25rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .contact-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding: 3rem 2rem;
            background: #0F172A;
            color: white;
            border-radius: 1rem;
            position: relative;
            overflow: hidden;
        }
        .contact-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(37,99,235,0.1) 0%, transparent 70%);
            pointer-events: none;
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
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">
                <div class="logo-dot"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
                <span class="logo-name">FixPoint</span>
                <span class="logo-badge">SEU</span>
            </a>
            <nav class="header-right">
                <a href="index.php" class="h-link">Home</a>
                <a href="help-center.php" class="h-link">Help</a>
                <?php if ($is_logged_in): ?>
                    <?php if ($user_role == 1): ?>
                        <a href="admin/dashboard.php" class="h-btn">Dashboard</a>
                    <?php elseif ($user_role == 2): ?>
                        <a href="technician/dashboard.php" class="h-btn">Dashboard</a>
                    <?php else: ?>
                        <a href="user/dashboard.php" class="h-btn">Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>

                    <a href="auth/login.php" class="h-btn">Log in</a>
                <?php endif; ?>
            </nav>
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

    </div>

    <footer class="ft">
        <div class="container">
            <div class="ft-grid">
                <div>
                    <a href="index.php" class="logo">
                        <div class="logo-dot"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
                        <span class="logo-name">FixPoint</span>
                    </a>
                    <p class="ft-brand">Making university maintenance simple, transparent, and efficient.</p>
                </div>
                <div>
                    <h4>Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help-center.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4>University</h4>
                    <ul>
                        <li>Saudi Electronic University</li>
                        <li>Senior Project — 2026</li>
                    </ul>
                </div>
            </div>
            <div class="ft-line">
                <span>&copy; 2026 FixPoint — Saudi Electronic University</span>
                <span>Ayman, Al-Abbas, Omar, Yahya, Talal, Abdulaziz</span>
            </div>
        </div>
    </footer>
</body>
</html>