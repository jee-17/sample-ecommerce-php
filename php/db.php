<?php
$host = "localhost";      // ✅ Change if GoDaddy gives a different MySQL host
$user = "root"; // ✅ Your MySQL username
$pass = ""; // ✅ Your MySQL password
$db   = "kcpl";   // ✅ Your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
  http_response_code(500);
  die('DB connect error');
}
$conn->set_charset('utf8mb4');
?>
