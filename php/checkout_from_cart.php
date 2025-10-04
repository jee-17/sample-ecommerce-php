<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(["success"=>false,"message"=>"Login required"]); 
    exit; 
}

$uid = $_SESSION['user_id'];

// ✅ Delete old checkout items
$stmt = $conn->prepare("DELETE FROM checkout_items WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->close();

// ✅ Insert from cart_items to checkout_items
$sql = "INSERT INTO checkout_items (user_id, product_name, package_size, product_image, price, quantity)
        SELECT user_id, product_name, package_size, product_image, price, quantity
        FROM cart_items WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$success = $stmt->execute();
$stmt->close();

$conn->close();

echo json_encode(["success" => $success]);
?>

