<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$uid = $_SESSION['user_id'];
$from_date = $_GET['from'] ?? null;
$to_date = $_GET['to'] ?? null;

// Build the base SQL query
$sql = "SELECT id, created_at, order_status, payment_status, payment_method, cancelled_reason, delivery_status, total_amount
        FROM orders
        WHERE user_id = ?";

// Add date filter if dates are provided
if ($from_date && $to_date) {
    $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// Bind parameters based on whether dates are provided
if ($from_date && $to_date) {
    $stmt->bind_param("iss", $uid, $from_date, $to_date);
} else {
    $stmt->bind_param("i", $uid);
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        "id"             => $row["id"],
        "created_at"     => date("c", strtotime($row["created_at"])),
        "order_status"   => $row["order_status"],
        "payment_status" => $row["payment_status"],
        "payment_method" => $row["payment_method"],
        "delivery_status" => $row["delivery_status"],
        "cancelled_reason" => $row["cancelled_reason"],
        "total_amount"   => $row["total_amount"]
    ];
}

echo json_encode($orders);

$stmt->close();
$conn->close();
?>