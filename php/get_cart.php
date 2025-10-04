<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id'])) { 
    echo json_encode([]); 
    exit; 
}

$userId = $_SESSION['user_id'];



// ✅ Fetch cart items for the user
$sql = "SELECT id, product_name, product_image, package_size, price, quantity 
        FROM cart_items 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
}

// ✅ Output JSON
echo json_encode($cart);

// ✅ Cleanup
$stmt->close();
$conn->close();
?>
