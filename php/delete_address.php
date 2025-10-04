<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(["success"=>false]); 
    exit; 
}


$id  = intval($_POST['id'] ?? 0);
$uid = intval($_SESSION['user_id']);

// ✅ Delete address
$stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $uid);
$success = $stmt->execute();

echo json_encode(["success" => $success]);

$stmt->close();
$conn->close();
?>
