<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
require_once "db.php";

$_SESSION['checkout_source'] = 'temp';

// ✅ Check login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(["success"=>false,"step"=>"session","message"=>"Login required"]); 
    exit; 
}

$uid   = $_SESSION['user_id'];
$name  = $_POST['product_name'] ?? '';
$img   = $_POST['product_image'] ?? '';
$size  = $_POST['package_size'] ?? '';
$price = floatval($_POST['price'] ?? 0);
$qty   = max(1, intval($_POST['quantity'] ?? 1));

$response = [
    "success" => true,
    "steps" => []
];

// ✅ STEP 1: Delete old checkout items
$del = $conn->prepare("DELETE FROM checkout_items WHERE user_id = ?");
$del->bind_param("i", $uid);

if (!$del->execute()) {
    echo json_encode([
        "success" => false,
        "step" => "delete",
        "message" => "Delete failed",
        "errors" => $del->error
    ]);
    exit;
} else {
    $response["steps"][] = "Old checkout items deleted for user $uid";
}
$del->close();

// ✅ STEP 2: Insert new item
$sql = "INSERT INTO checkout_items (user_id, product_name, package_size, product_image, price, quantity) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssdi", $uid, $name, $size, $img, $price, $qty);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "step" => "insert",
        "message" => "Insert failed",
        "params" => [$uid, $name, $size, $img, $price, $qty],
        "errors" => $stmt->error
    ]);
    exit;
} else {
    $response["steps"][] = "New checkout item inserted";
    $response["params"]  = [$uid, $name, $size, $img, $price, $qty];
}
$stmt->close();

$conn->close();

// ✅ Final success response
echo json_encode($response);
?>