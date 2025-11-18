<?php
// db_connect.php
// Configure for your environment:
// XAMPP default: $host='127.0.0.1', $user='root', $pass='', $port=3306
// MAMP default:  $host='127.0.0.1', $user='root', $pass='root', $port=8889

$host = "127.0.0.1";
$user = "root";
$pass = "";    // change to "root" for MAMP
$db   = "shopapp";
$port = 3306;  // change to 8889 for MAMP

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    // Friendly error in browser with guidance
    die("DB Connection failed: " . $conn->connect_error . ". Check db_connect.php settings.");
}
$conn->set_charset("utf8mb4");
?>
