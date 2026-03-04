<?php
require_once 'error-handler.php';
// config/database.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fixpoint";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>