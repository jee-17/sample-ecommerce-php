<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success"=>false,"message"=>"Login required"]);
    exit;
}


$uid   = $_SESSION['user_id'];
$id    = intval($_POST['id'] ?? 0);

$full  = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$a1    = $_POST['address_line1'] ?? '';
$a2    = $_POST['address_line2'] ?? '';
$city  = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$pc    = $_POST['postal_code'] ?? '';
$isDef = isset($_POST['is_default']) ? 1 : 0;

// ✅ Phone validation
if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
    echo json_encode(["success"=>false,"message"=>"Invalid phone number"]);
    exit;
}

// ✅ Reset previous default if new one is selected
if ($isDef) {
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
}

// ✅ Update the address
$sql = "UPDATE addresses 
        SET full_name=?, phone=?, address_line1=?, address_line2=?, city=?, state=?, postal_code=?, is_default=? 
        WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssiii", $full, $phone, $a1, $a2, $city, $state, $pc, $isDef, $id, $uid);

if ($stmt->execute()) {
    echo json_encode(["success"=>true,"message"=>"Address updated"]);
} else {
    echo json_encode(["success"=>false,"message"=>"Failed to update: ".$stmt->error]);
}

$stmt->close();
$conn->close();
?>
