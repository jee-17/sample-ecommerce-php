
<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please log in to add items to cart."]);
    exit;
}

$userId = $_SESSION['user_id'];

// Get POST data
$productId    = $_POST['product_id'] ?? 0;
$productName  = $_POST['product_name'] ?? '';
$productImage = $_POST['product_image'] ?? '';
$packageSize  = $_POST['package_size'] ?? '';
$price        = floatval($_POST['price']);
$quantity     = intval($_POST['quantity'] ?? 1);

// --- Check if the item already exists in cart using product_id and package_size ---
$checkSql = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND package_size = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("iis", $userId, $productId, $packageSize);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update existing cart item quantity
    $newQty = $row['quantity'] + $quantity;
    $updateSql = "UPDATE cart_items SET quantity = ?, product_image = ?, price = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($updateSql);
    $stmtUpdate->bind_param("isdi", $newQty, $productImage, $price, $row['id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();
} else {
    // Insert new item
    $insertSql = "INSERT INTO cart_items (user_id, product_id, product_name, package_size, product_image, price, quantity) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($insertSql);
    $stmtInsert->bind_param("iisssdi", $userId, $productId, $productName, $packageSize, $productImage, $price, $quantity);
    $stmtInsert->execute();
    $stmtInsert->close();
}

$stmt->close();
$conn->close();

echo json_encode(["success" => true, "message" => "Product added to cart successfully."]);
?>
