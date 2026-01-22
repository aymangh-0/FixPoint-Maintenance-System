<?php
// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>FixPoint Error Diagnostic</h2>";

// Check PHP version
echo "✅ PHP Version: " . phpversion() . "<br>";
echo "✅ Server: " . $_SERVER['SERVER_NAME'] . "<br><br>";

// Check if config files exist
echo "<h3>Checking Files:</h3>";
if (file_exists('config/database.php')) {
    echo "✅ config/database.php exists<br>";
} else {
    echo "❌ config/database.php NOT FOUND<br>";
}

if (file_exists('config/helpers.php')) {
    echo "✅ config/helpers.php exists<br>";
} else {
    echo "❌ config/helpers.php NOT FOUND<br>";
}

// Test database connection
echo "<h3>Testing Database:</h3>";
try {
    require_once 'config/database.php';
    if ($conn) {
        echo "✅ Database connected!<br>";
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) as count FROM user");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Found " . $row['count'] . " users in database<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test helpers
echo "<h3>Testing Helpers:</h3>";
try {
    require_once 'config/helpers.php';
    echo "✅ helpers.php loaded<br>";
    
    // Test a helper function
    if (function_exists('e')) {
        echo "✅ e() function exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>✅ Diagnostic Complete!</h3>";
?>