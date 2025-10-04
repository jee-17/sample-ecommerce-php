<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Login required"]);
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid order ID"]);
    exit;
}

// ✅ Fetch items
$sqlItems = "SELECT product_name, package_size, product_image, price, quantity 
             FROM order_items WHERE order_id = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();

$items = [];
$subtotal = 0;
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
$stmtItems->close();

// ✅ Fetch order details
$sqlOrder = "SELECT subtotal, delivery_fee, total_amount 
             FROM orders WHERE id = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("i", $order_id);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$order = $resultOrder->fetch_assoc();
$stmtOrder->close();

// ✅ Build response
$response = [
    "items"        => $items,
    "subtotal"     => $order['subtotal'] ?? $subtotal,
    "delivery_fee" => $order['delivery_fee'] ?? 0,
    "total_amount" => $order['total_amount'] ?? $subtotal
];

echo json_encode($response);

// ✅ Close DB
$conn->close();
?>
