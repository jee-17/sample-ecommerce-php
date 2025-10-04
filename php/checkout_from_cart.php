<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success"=>false,"message"=>"Login required"]);
    exit;
}

$uid = $_SESSION['user_id'];

// Get all cart items for user
$sql = "SELECT ci.id, ci.product_id, ci.product_name, ci.package_size, ci.quantity, 
               pv.quantity AS available_stock
        FROM cart_items ci
        JOIN product_variants pv 
          ON pv.product_id = ci.product_id 
         AND pv.weight = ci.package_size
        WHERE ci.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();

$out_of_stock = [];
while ($row = $result->fetch_assoc()) {
    if ($row['quantity'] > $row['available_stock']) {
        $out_of_stock[] = [
            "product_name" => $row['product_name'],
            "package_size" => $row['package_size'],
            "requested" => $row['quantity'],
            "available" => $row['available_stock']
        ];
    }
}
$stmt->close();

if (!empty($out_of_stock)) {
    echo json_encode(["success" => false, "out_of_stock" => $out_of_stock]);
    exit;
}

// ✅ Clear old checkout items
$stmt = $conn->prepare("DELETE FROM checkout_items WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->close();

// ✅ Copy from cart_items to checkout_items
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
