<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Login required"]);
    exit;
}


$id  = intval($_POST['id'] ?? 0);
$uid = intval($_SESSION['user_id']);

// --- Delete item ---
$stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $uid);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
