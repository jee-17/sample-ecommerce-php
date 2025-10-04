<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check if logged in
if (!isset($_SESSION['user_id'])) { 
    echo json_encode([]); 
    exit; 
}

$userId = $_SESSION['user_id'];


// ✅ Fetch data from Temp_checkout_items
$sql = "SELECT id, product_name, package_size, product_image, price, quantity 
        FROM checkout_items WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$out = [];
while ($r = $result->fetch_assoc()) {
    $out[] = $r;
}

// ✅ Return JSON
echo json_encode($out);

// ✅ Cleanup
$stmt->close();
$conn->close();

?>