<?php
/**
 * FixPoint - Forgot Password
 * Verify identity with email + name, then reset password
 */

session_start();
require_once '../config/database.php';

$step = 'verify'; // verify → reset → done
$error = '';
$success = '';
$user_data = null;

// Step 1: Verify identity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    
    if (empty($email) || empty($name)) {
        $error = "Please enter both your email and full name";
    } else {
        $sql = "SELECT UserID, Name, Email FROM user WHERE Email = ? AND Name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user_data['UserID'];
            $_SESSION['reset_email'] = $user_data['Email'];
            $_SESSION['reset_token'] = bin2hex(random_bytes(16));
            $_SESSION['reset_time'] = time();
            $step = 'reset';
        } else {
            $error = "No account found matching this email and name combination";
        }
        $stmt->close();
    }
}

// Step 2: Reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset'])) {
    // Verify session token exists and not expired (15 min)
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_token']) || 
        (time() - $_SESSION['reset_time']) > 900) {
        $error = "Session expired. Please start over.";
        $step = 'verify';
    } else {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in both password fields";
            $step = 'reset';
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long";
            $step = 'reset';
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
            $step = 'reset';
        } else {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET Password = ? WHERE UserID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed, $_SESSION['reset_user_id']);
            
            if ($stmt->execute()) {
                // Log the action
                if (file_exists('../config/audit-logger.php')) {
                    require_once '../config/audit-logger.php';
                    logAuditAction($conn, $_SESSION['reset_user_id'], 'PASSWORD_RESET', 'user', $_SESSION['reset_user_id'], null, 'Password reset via forgot password');
                }
                
                // Clear reset session
                unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['reset_time']);
                
                $success = "Password updated successfully! You can now login with your new password.";
                $step = 'done';
            } else {
                $error = "Failed to update password. Please try again.";
                $step = 'reset';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FixPoint</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s;
        }
        .step-dot.active {
            background: #2563eb;
            transform: scale(1.2);
        }
        .step-dot.done {
            background: #10b981;
        }
        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            color: #64748b;
        }
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        .req-met { color: #10b981; }
        .req-not { color: #94a3b8; }
        .success-box {
            text-align: center;
            padding: 2rem;
        }
        .success-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div style="max-width: 450px; width: 100%;">
            <a href="login.php" class="back-link">← Back to Login</a>
            
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🔑</div>
                    <h1 class="auth-title">Forgot Password</h1>
                    <p class="auth-subtitle">
                        <?php if ($step == 'verify'): ?>
                            Verify your identity to reset your password
                        <?php elseif ($step == 'reset'): ?>
                            Create a new password
                        <?php else: ?>
                            Password reset complete
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step-dot <?php echo $step == 'verify' ? 'active' : 'done'; ?>"></div>
                    <div class="step-dot <?php echo $step == 'reset' ? 'active' : ($step == 'done' ? 'done' : ''); ?>"></div>
                    <div class="step-dot <?php echo $step == 'done' ? 'active done' : ''; ?>"></div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($step == 'verify'): ?>
                <!-- ======================== -->
                <!-- STEP 1: Verify Identity -->
                <!-- ======================== -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your registered email"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            placeholder="Enter your full name exactly as registered"
                            required
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        >
                    </div>
                    
                    <button type="submit" name="verify" class="btn-submit">
                        Verify Identity →
                    </button>
                </form>

                <?php elseif ($step == 'reset'): ?>
                <!-- ======================== -->
                <!-- STEP 2: New Password    -->
                <!-- ======================== -->
                <div style="background: #dbeafe; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1e40af;">
                    ✅ Identity verified for: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
                </div>

                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-input" 
                            placeholder="Enter new password"
                            required
                            minlength="8"
                            oninput="checkPassword()"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Confirm new password"
                            required
                            minlength="8"
                            oninput="checkPassword()"
                        >
                    </div>

                    <div class="password-requirements" id="passReqs">
                        <strong>Password must have:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 0.5rem;">
                            <li id="req-length" class="req-not">○ At least 8 characters</li>
                            <li id="req-upper" class="req-not">○ At least one uppercase letter</li>
                            <li id="req-number" class="req-not">○ At least one number</li>
                            <li id="req-match" class="req-not">○ Passwords match</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="reset" class="btn-submit" id="resetBtn" disabled style="opacity: 0.5;">
                        🔒 Reset Password
                    </button>
                </form>

                <script>
                function checkPassword() {
                    var pass = document.getElementById('new_password').value;
                    var confirm = document.getElementById('confirm_password').value;
                    var btn = document.getElementById('resetBtn');
                    
                    var hasLength = pass.length >= 8;
                    var hasUpper = /[A-Z]/.test(pass);
                    var hasNumber = /[0-9]/.test(pass);
                    var matches = pass === confirm && pass.length > 0;
                    
                    updateReq('req-length', hasLength);
                    updateReq('req-upper', hasUpper);
                    updateReq('req-number', hasNumber);
                    updateReq('req-match', matches);
                    
                    if (hasLength && hasUpper && hasNumber && matches) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    } else {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                    }
                }
                
                function updateReq(id, met) {
                    var el = document.getElementById(id);
                    if (met) {
                        el.className = 'req-met';
                        el.innerHTML = '✅ ' + el.innerHTML.substring(2);
                    } else {
                        el.className = 'req-not';
                        el.innerHTML = '○ ' + el.innerHTML.substring(2);
                    }
                }
                </script>

                <?php else: ?>
                <!-- ======================== -->
                <!-- STEP 3: Success         -->
                <!-- ======================== -->
                <div class="success-box">
                    <div class="success-icon">✅</div>
                    <h2 style="color: #065f46; margin-bottom: 0.5rem;">Password Reset Successfully!</h2>
                    <p style="color: #64748b; margin-bottom: 1.5rem;">
                        Your password has been updated. You can now login with your new password.
                    </p>
                    <a href="login.php" class="btn-submit" style="display: inline-block; text-decoration: none; text-align: center;">
                        🔐 Go to Login
                    </a>
                </div>
                <?php endif; ?>

                <div class="auth-footer">
                    <p style="color: #64748b; margin-bottom: 0.5rem;">Remember your password?</p>
                    <a href="login.php" class="auth-link">← Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>