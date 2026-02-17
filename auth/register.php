<?php
/**
 * FixPoint - Registration Page
 * Allows new users to create accounts
 */

// Start session
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check_sql = "SELECT UserID FROM user WHERE Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "An account with this email already exists";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            // RoleID = 3 (User), Default limits: 2/week, 8/month
            $insert_sql = "INSERT INTO user (RoleID, Name, Email, Password, Phone, MaxRequestsPerWeek, MaxRequestsPerMonth) 
                          VALUES (3, ?, ?, ?, ?, 2, 8)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $name, $email, $password_hash, $phone);
            
            if ($insert_stmt->execute()) {
                $success = "Account created successfully! Redirecting to login...";
                // Redirect to login after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div style="max-width: 450px; width: 100%;">
            <a href="../index.php" class="back-link">← Back to Home</a>
            
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🔧</div>
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Join FixPoint to start reporting maintenance issues</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            placeholder="Enter your full name"
                            required
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your email"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                        <small style="color: #64748b; font-size: 0.875rem;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input" 
                            placeholder="+966 5XX XXX XXX"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="At least 6 characters"
                            required
                            minlength="6"
                        >
                        <div class="password-strength" id="passwordStrength" style="display:none;">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small id="strengthText" style="color: #64748b; font-size: 0.875rem;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Re-enter your password"
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <div class="form-checkbox">
                            <input type="checkbox" id="terms" required>
                            <label for="terms" style="color: #64748b; font-size: 0.95rem;">
                                I agree to the Terms of Service and Privacy Policy
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Create Account
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p style="color: #64748b; margin-bottom: 0.5rem;">Already have an account?</p>
                    <a href="login.php" class="auth-link">Login Here →</a>
                </div>
                
                <!-- Account Info -->
                <div class="demo-accounts">
                    <div class="demo-title">📋 Account Information</div>
                    <div class="demo-account">• All new accounts start with User role</div>
                    <div class="demo-account">• Default limit: 2 requests per week, 8 per month</div>
                    <div class="demo-account">• Admin can adjust your limits if needed</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- External JavaScript -->
    <script src="../assets/js/auth.js"></script>
</body>
</html>