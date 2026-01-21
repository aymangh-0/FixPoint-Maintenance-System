<?php
/**
 * FixPoint - Password Reset Script
 * Generate and update password hashes for demo accounts
 */

require_once 'config/database.php';

// Generate the correct hash for "Admin@123"
$password = "Admin@123";
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>FixPoint - Password Update Script</h2>";
echo "<p><strong>New Password Hash Generated:</strong></p>";
echo "<code style='background: #f1f5f9; padding: 1rem; display: block; word-break: break-all;'>$password_hash</code>";
echo "<br>";

// Update all demo accounts with the new hash
$sql = "UPDATE user SET Password = ? WHERE Email IN (
    'admin@seu.edu.sa',
    'ahmed.tech@seu.edu.sa',
    'khalid.tech@seu.edu.sa',
    'S220053790@seu.edu.sa',
    'S220034953@seu.edu.sa',
    'S220042171@seu.edu.sa',
    'S220043128@seu.edu.sa',
    'S220020268@seu.edu.sa',
    'S220006357@seu.edu.sa',
    'jameel.alhejely@seu.edu.sa'
)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $password_hash);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    echo "<div style='background: #d1fae5; border: 1px solid #a7f3d0; padding: 1rem; border-radius: 0.5rem; color: #065f46;'>";
    echo "✅ <strong>Success!</strong> Updated $affected accounts with new password hash.";
    echo "</div>";
    echo "<br>";
    
    echo "<h3>Demo Accounts (Password: Admin@123)</h3>";
    echo "<ul>";
    echo "<li>👨‍💼 <strong>Admin:</strong> admin@seu.edu.sa</li>";
    echo "<li>👨‍🔧 <strong>Technician:</strong> ahmed.tech@seu.edu.sa</li>";
    echo "<li>👨‍🔧 <strong>Technician:</strong> khalid.tech@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220053790@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220034953@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220042171@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220043128@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220020268@seu.edu.sa</li>";
    echo "<li>👨‍🎓 <strong>Student:</strong> S220006357@seu.edu.sa</li>";
    echo "<li>👨‍🏫 <strong>Faculty:</strong> jameel.alhejely@seu.edu.sa</li>";
    echo "</ul>";
    
    echo "<br>";
    echo "<p><strong>✅ All accounts are now ready to use with password:</strong> <code>Admin@123</code></p>";
    echo "<br>";
    echo "<a href='auth/login.php' style='display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600;'>Go to Login Page →</a>";
} else {
    echo "<div style='background: #fee2e2; border: 1px solid #fecaca; padding: 1rem; border-radius: 0.5rem; color: #991b1b;'>";
    echo "❌ <strong>Error:</strong> Failed to update passwords.";
    echo "</div>";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - FixPoint</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #f8fafc;
        }
        code {
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            color: #1e293b;
        }
    </style>
</head>
<body>
</body>
</html>