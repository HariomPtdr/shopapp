<?php
// db_connect.php
// Place this file in your project root (shopapp/).
// Edit $host, $user, $pass, $db and $port to match your environment.
//
// Common settings:
//  - XAMPP on Windows:    host=127.0.0.1, user=root, pass="",    port=3306
//  - XAMPP on mac (XAMPP app): host=127.0.0.1, user=root, pass="", port=3306
//  - MAMP on mac:         host=127.0.0.1, user=root, pass=root,  port=8889
//  - If you changed MySQL root password, put it in $pass.
//
// This file also enables helpful error output while you're developing.
// Remove or comment out the error display lines before production.

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ====== EDIT THESE ======
$host = "127.0.0.1";
$user = "root";
$pass = "";        // <--- set to "root" for MAMP, "" (empty) for many XAMPP installs
$db   = "shopapp";
$port = 3306;      // <--- change to 8889 for MAMP if needed
// ========================

/* Attempt connection */
$conn = new mysqli($host, $user, $pass, $db, $port);

/* Helpful error message for dev */
if ($conn->connect_error) {
    // Show a clear, actionable message
    http_response_code(500);
    echo "<h2>Database connection failed</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>";
    echo "<p>Check these:</p>";
    echo "<ul>";
    echo "<li>Are MySQL/Apache running in XAMPP or MAMP?</li>";
    echo "<li>Is your <code>shopapp</code> database created? (import <code>shopapp.sql</code>)</li>";
    echo "<li>Are the host/user/password/port values correct in <code>db_connect.php</code>?</li>";
    echo "<li>If using MAMP, try port 8889 and password 'root'.</li>";
    echo "</ul>";
    exit;
}

/* Use UTF-8 */
$conn->set_charset("utf8mb4");
