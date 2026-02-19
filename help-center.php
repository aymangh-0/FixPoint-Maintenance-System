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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .help-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .help-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border-radius: 1rem;
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
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
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
            margin-top: 2rem;
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
            <h2 class="section-title">🚀 Getting Started</h2>
            
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

        <!-- FAQ Section - Submitting Requests -->
        <div class="faq-section">
            <h2 class="section-title">📝 Submitting Requests</h2>
            
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

        <!-- FAQ Section - Tracking Requests -->
        <div class="faq-section">
            <h2 class="section-title">🔍 Tracking Requests</h2>
            
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

        <!-- FAQ Section - For Admins -->
        <div class="faq-section">
            <h2 class="section-title">👨‍💼 For Administrators</h2>
            
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

        <!-- FAQ Section - For Technicians -->
        <div class="faq-section">
            <h2 class="section-title">🔧 For Technicians</h2>
            
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

        <!-- FAQ Section - Technical Issues -->
        <div class="faq-section">
            <h2 class="section-title">⚠️ Troubleshooting</h2>
            
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
                <a href="auth/register.php" class="quick-link-btn">🚀 Get Started</a>
            <?php endif; ?>
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

    <script>
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