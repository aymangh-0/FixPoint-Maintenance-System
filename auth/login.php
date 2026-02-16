<?php
/**
 * FixPoint - Login Page
 * Handles user authentication and redirects based on role
 */

// Start session
session_start();

// Check for timeout message
$timeout_msg = '';
if (isset($_SESSION['timeout_message'])) {
    $timeout_msg = $_SESSION['timeout_message'];
    unset($_SESSION['timeout_message']);
}

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 1) {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role_id'] == 2) {
        header("Location: ../technician/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

// Include database connection
require_once '../config/database.php';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check if user exists
        $sql = "SELECT u.UserID, u.RoleID, u.Name, u.Email, u.Password, r.RoleName 
                FROM user u 
                JOIN role r ON u.RoleID = r.RoleID 
                WHERE u.Email = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['Password'])) {
                // Password correct - create session
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['role_id'] = $user['RoleID'];
                $_SESSION['name'] = $user['Name'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role_name'] = $user['RoleName'];

                // Set session security timestamps
                $_SESSION['last_activity'] = time();
                $_SESSION['last_regeneration'] = time();
                
                // Redirect based on role
                if ($user['RoleID'] == 1) {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['RoleID'] == 2) {
                    header("Location: ../technician/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit();
            } else {
                $error = "Incorrect password";
            }
        } else {
            $error = "No account found with this email";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FixPoint</title>
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
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Login to access your FixPoint account</p>
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

                <?php if ($timeout_msg): ?>
                    <div class="alert alert-warning" style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        ⏰ <?php echo htmlspecialchars($timeout_msg); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your Email"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Login to Dashboard
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p style="color: #64748b; margin-bottom: 0.5rem;">Don't have an account?</p>
                    <a href="register.php" class="auth-link">Create New Account →</a>
                </div>
                
                <!-- Demo Accounts (Remove in production) -->
                <div class="demo-accounts">
                    <div class="demo-title">🔑 Demo Accounts (Password: Admin@123)</div>
                    <div class="demo-account">👨‍💼 Admin: admin@seu.edu.sa</div>
                    <div class="demo-account">👨‍🔧 Technician: ahmed.tech@seu.edu.sa</div>
                    <div class="demo-account">👨‍🎓 Student: S220053790@seu.edu.sa</div>
                    <div class="demo-account">👨‍🏫 Faculty: jameel.alhejely@seu.edu.sa</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>