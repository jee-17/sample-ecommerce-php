<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) exit;


$id     = intval($_POST['id'] ?? 0);
$change = intval($_POST['change'] ?? 0);
$uid    = $_SESSION['user_id'];

// ✅ Update cart quantity
$sql = "UPDATE cart_items SET quantity = quantity + ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $change, $id, $uid);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed: ".$stmt->error]);
}

$stmt->close();
$conn->close();
?>
