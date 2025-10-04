<?php
session_start();
require_once "db.php"; // your DB connection

header('Content-Type: application/json');

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

// ✅ Check if order exists and belongs to this user
                           // jee change payment_status
$stmt = $conn->prepare("SELECT order_status FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (in_array(strtolower($row['order_status']), ['pending', 'processing'])) {
        // ✅ Update status to Cancelled
        $update = $conn->prepare("UPDATE orders SET order_status='Cancelled' WHERE id=? AND user_id=?");
        $update->bind_param("ii", $order_id, $user_id);
        if ($update->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Database update failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Order cannot be cancelled"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Order not found"]);
}
