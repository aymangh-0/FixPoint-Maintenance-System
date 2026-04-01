<?php
/**
 * FixPoint - Help Center
 * FAQs, guides, and troubleshooting
 */

session_start();
require_once 'config/session-security.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['name'] : '';
$user_role = $is_logged_in ? $_SESSION['role_id'] : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - FixPoint</title>
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

        /* Page content */
        .help-container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 2rem 24px;
        }
        
        .help-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding: 3rem 2rem;
            background: #0F172A;
            color: white;
            border-radius: 1rem;
            position: relative;
            overflow: hidden;
        }
        .help-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(37,99,235,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .help-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .help-header p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        .help-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .category-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid #e2e8f0;
            text-align: center;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            border-color: #2563eb;
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .category-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .category-desc {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .faq-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 1.25rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            padding: 1.5rem 2rem;
            margin: 0;
            border-bottom: 2px solid #f1f5f9;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
            transition: background 0.2s;
        }
        .section-title:hover {
            background: #f8fafc;
        }
        .section-toggle {
            font-size: 1.25rem;
            color: #94a3b8;
            transition: transform 0.3s;
        }
        .section-title.collapsed .section-toggle {
            transform: rotate(-90deg);
        }
        .section-title.collapsed {
            border-bottom-color: transparent;
        }
        .section-body {
            padding: 0.5rem 2rem 1.5rem;
            transition: all 0.3s;
        }
        .section-body.hidden {
            display: none;
        }
        
        .faq-item {
            margin-bottom: 1rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border-left: 4px solid #2563eb;
        }
        
        .faq-question {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .faq-question:hover {
            color: #2563eb;
        }
        
        .faq-answer {
            color: #64748b;
            line-height: 1.6;
            display: none;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .faq-answer.active {
            display: block;
        }
        
        .faq-toggle {
            font-size: 1.5rem;
            color: #2563eb;
            transition: transform 0.3s;
        }
        
        .faq-toggle.active {
            transform: rotate(180deg);
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2.5rem;
            margin-bottom: 2.5rem;
        }
        
        .quick-link-btn {
            display: block;
            padding: 1rem;
            background: #2563eb;
            color: white;
            text-align: center;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .quick-link-btn:hover {
            background: #1e40af;
        }
        
        @media (max-width: 768px) {
            .help-header h1 {
                font-size: 1.75rem;
            }
            
            .help-categories {
                grid-template-columns: 1fr;
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
                <a href="contact.php" class="h-link">Contact</a>
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

    <div class="help-container">
        <!-- Header -->
        <div class="help-header">
            <h1>📚 Help Center</h1>
            <p>Find answers, guides, and support for FixPoint</p>
        </div>

        <!-- Quick Categories -->
        <div class="help-categories">
            <div class="category-card">
                <div class="category-icon">🚀</div>
                <div class="category-title">Getting Started</div>
                <div class="category-desc">Learn the basics of using FixPoint</div>
            </div>
            
            <div class="category-card">
                <div class="category-icon">📝</div>
                <div class="category-title">Submit Requests</div>
                <div class="category-desc">How to create maintenance requests</div>
            </div>
            
            <div class="category-card">
                <div class="category-icon">👨‍💼</div>
                <div class="category-title">For Admins</div>
                <div class="category-desc">Admin panel and user management</div>
            </div>
            
            <div class="category-card">
                <div class="category-icon">🔧</div>
                <div class="category-title">For Technicians</div>
                <div class="category-desc">Task management and completion</div>
            </div>
        </div>

        <!-- FAQ Section - Getting Started -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">🚀 Getting Started <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I create an account?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p><strong>Step-by-step:</strong></p>
                    <ol>
                        <li>Click "Get Started" or "Register" button</li>
                        <li>Enter your User ID (SXXXXXXXXX) or Faculty ID</li>
                        <li>Fill in your name and SEU email address</li>
                        <li>Create a strong password (8+ characters, uppercase, lowercase, number, special character)</li>
                        <li>Click "Register"</li>
                        <li>You'll be automatically logged in</li>
                    </ol>
                    <p><strong>Note:</strong> Only SEU email addresses (@seu.edu.sa) are accepted.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    What are the different user roles?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>FixPoint has three main user roles:</p>
                    <ul>
                        <li><strong>Users/Faculty:</strong> Can submit maintenance requests, track status, and provide feedback</li>
                        <li><strong>Technicians:</strong> Can view assigned tasks, update progress, and mark requests as complete</li>
                        <li><strong>Admins:</strong> Can manage all requests, assign technicians, manage users, and view reports</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    I forgot my password. What should I do?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Currently, please contact the system administrator to reset your password. They can be reached through the <a href="contact.php" style="color: #2563eb;">Contact Us</a> page.</p>
                    <p><strong>Future Update:</strong> Self-service password reset via email will be available soon.</p>
                </div>
            </div>
            </div>
        </div>

        <!-- FAQ Section - Submitting Requests -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">📝 Submitting Requests <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I submit a maintenance request?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Log in to your account</li>
                        <li>Go to your Dashboard</li>
                        <li>Click "Submit New Request"</li>
                        <li>Select the location (Building, Floor, Room)</li>
                        <li>Choose the category (e.g., Electrical, Plumbing)</li>
                        <li>Set priority level</li>
                        <li>Enter a descriptive title</li>
                        <li>Provide detailed description of the issue</li>
                        <li>Upload a photo (optional but recommended)</li>
                        <li>Click "Submit Request"</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    What are the request limits?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p><strong>Users:</strong></p>
                    <ul>
                        <li>2 requests per week</li>
                        <li>8 requests per month</li>
                    </ul>
                    <p><strong>Faculty:</strong></p>
                    <ul>
                        <li>5 requests per week</li>
                        <li>20 requests per month</li>
                    </ul>
                    <p><strong>Note:</strong> Admins can adjust these limits if needed. Contact them if you need an increase.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    Can I upload photos with my request?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Yes! Photos are highly recommended as they help technicians understand the issue better.</p>
                    <p><strong>Requirements:</strong></p>
                    <ul>
                        <li>Formats: JPG, PNG, GIF</li>
                        <li>Maximum size: 5MB per photo</li>
                        <li>Optional but recommended</li>
                    </ul>
                    <p><strong>Tips:</strong> Take clear, well-lit photos that show the issue from multiple angles.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    What if there's already a request for the same issue?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>FixPoint has a <strong>duplicate detection system</strong> that checks if there's an existing open request for the same location and category.</p>
                    <p>If a duplicate is found:</p>
                    <ol>
                        <li>You'll see a warning with details of the existing request</li>
                        <li>You can choose to submit anyway or cancel</li>
                        <li>If you believe it's a different issue, add details explaining the difference</li>
                    </ol>
                </div>
            </div>
            </div>
        </div>

        <!-- FAQ Section - Tracking Requests -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">🔍 Tracking Requests <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I check the status of my request?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Go to "My Requests" from your dashboard</li>
                        <li>You'll see all your requests with current status</li>
                        <li>Click "View" on any request to see full details</li>
                        <li>Check the "Status History" timeline for complete tracking</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    What do the different statuses mean?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ul>
                        <li><strong>Pending:</strong> Request submitted, waiting for admin review</li>
                        <li><strong>Reviewed:</strong> Admin has reviewed but not yet assigned</li>
                        <li><strong>Assigned:</strong> A technician has been assigned to your request</li>
                        <li><strong>In Progress:</strong> Technician is actively working on it</li>
                        <li><strong>Completed:</strong> Work is finished (please provide feedback!)</li>
                        <li><strong>Cancelled:</strong> Request was cancelled</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I provide feedback after completion?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Once your request is marked as "Completed":</p>
                    <ol>
                        <li>Open the request details</li>
                        <li>Click "Submit Feedback" button</li>
                        <li>Rate the service (1-5 stars)</li>
                        <li>Add optional comments about your experience</li>
                        <li>Submit your feedback</li>
                    </ol>
                    <p><strong>Note:</strong> Feedback helps us improve service quality!</p>
                </div>
            </div>
            </div>
        </div>

        <!-- FAQ Section - For Admins -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">👨‍💼 For Administrators <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I assign a technician to a request?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Go to "All Requests" from admin dashboard</li>
                        <li>Click "Manage" on the request</li>
                        <li>Scroll to "Admin Actions" section</li>
                        <li>Select a technician from the dropdown</li>
                        <li>Click "Assign Technician"</li>
                        <li>The requester and technician will be notified</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I change a user's request limits?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Go to "Users" from admin dashboard</li>
                        <li>Find the user in the list</li>
                        <li>Click "Set Limits" button</li>
                        <li>Enter new weekly and monthly limits</li>
                        <li>Click "Save Limits"</li>
                    </ol>
                    <p><strong>Note:</strong> Weekly limit must be less than or equal to monthly limit.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I change a user's role?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Go to "Users" from admin dashboard</li>
                        <li>Find the user in the list</li>
                        <li>Click "Change Role" button</li>
                        <li>Select new role from dropdown</li>
                        <li>Confirm the change</li>
                    </ol>
                    <p><strong>Note:</strong> You cannot change your own role for security reasons.</p>
                </div>
            </div>
            </div>
        </div>

        <!-- FAQ Section - For Technicians -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">🔧 For Technicians <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I start working on an assigned task?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Go to your Technician Dashboard</li>
                        <li>Find the task in "Active Tasks"</li>
                        <li>Click "Work On It"</li>
                        <li>Click "Start Working on This Task" button</li>
                        <li>Status changes to "In Progress"</li>
                        <li>Requester is notified</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I mark a task as complete?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Open the task details</li>
                        <li>Once work is finished, add completion notes (optional)</li>
                        <li>Click "Mark Task as Complete"</li>
                        <li>Status changes to "Completed"</li>
                        <li>Requester receives notification to provide feedback</li>
                    </ol>
                </div>
            </div>
            </div>
        </div>

        <!-- FAQ Section - Technical Issues -->
        <div class="faq-section">
            <h2 class="section-title" onclick="toggleSection(this)">⚠️ Troubleshooting <span class="section-toggle">▼</span></h2><div class="section-body">
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    I can't log in. What should I do?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p><strong>Check the following:</strong></p>
                    <ul>
                        <li>Verify your email format is correct (@seu.edu.sa)</li>
                        <li>Check for typos in email and password</li>
                        <li>Make sure Caps Lock is off</li>
                        <li>Try clearing your browser cache</li>
                        <li>Use a different browser</li>
                    </ul>
                    <p>If still unable to log in, contact support through the <a href="contact.php" style="color: #2563eb;">Contact Us</a> page.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    My photo won't upload. Why?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p><strong>Common reasons:</strong></p>
                    <ul>
                        <li>File is larger than 5MB - compress or resize it</li>
                        <li>Wrong file format - use JPG, PNG, or GIF only</li>
                        <li>Slow internet connection - wait or try again</li>
                        <li>Browser issue - try different browser</li>
                    </ul>
                    <p><strong>Tip:</strong> Use free online tools to compress images before uploading.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    The page is not loading properly. What can I do?
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p><strong>Try these solutions:</strong></p>
                    <ol>
                        <li>Hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>
                        <li>Clear browser cache and cookies</li>
                        <li>Try incognito/private mode</li>
                        <li>Update your browser to latest version</li>
                        <li>Try different browser (Chrome, Firefox, Edge)</li>
                        <li>Check your internet connection</li>
                    </ol>
                </div>
            </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="contact.php" class="quick-link-btn">📧 Contact Support</a>
            <a href="index.php" class="quick-link-btn">🏠 Back to Home</a>
            <?php if ($is_logged_in): ?>
                <?php if ($user_role == 1): ?>
                    <a href="admin/dashboard.php" class="quick-link-btn">📊 Admin Dashboard</a>
                <?php elseif ($user_role == 2): ?>
                    <a href="technician/dashboard.php" class="quick-link-btn">🔧 Technician Dashboard</a>
                <?php else: ?>
                    <a href="user/dashboard.php" class="quick-link-btn">👤 My Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="auth/login.php" class="quick-link-btn">🚀 Get Started</a>
            <?php endif; ?>
        </div>
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

    <script>
        // Collapse all sections on page load
        document.querySelectorAll('.section-title').forEach(title => {
            title.classList.add('collapsed');
            title.nextElementSibling.classList.add('hidden');
        });

        function toggleSection(element) {
            const body = element.nextElementSibling;
            element.classList.toggle('collapsed');
            body.classList.toggle('hidden');
        }

        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const toggle = element.querySelector('.faq-toggle');
            
            // Close all other FAQs
            document.querySelectorAll('.faq-answer').forEach(item => {
                if (item !== answer) {
                    item.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.faq-toggle').forEach(item => {
                if (item !== toggle) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle current FAQ
            answer.classList.toggle('active');
            toggle.classList.toggle('active');
        }
    </script>
</body>
</html>