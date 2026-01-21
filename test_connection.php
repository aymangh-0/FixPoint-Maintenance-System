<?php
require_once 'config/database.php';

echo "✅ Database connected successfully!<br>";
echo "📊 Database name: " . DB_NAME . "<br>";
echo "🌐 Character set: " . $conn->character_set_name() . "<br>";

// Test query - count users
$result = $conn->query("SELECT COUNT(*) as total FROM User");
$row = $result->fetch_assoc();
echo "👥 Total users in database: " . $row['total'];
?>