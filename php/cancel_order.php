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
$stmt = $conn->prepare("SELECT order_status FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();


if ($row = $result->fetch_assoc()) {
    if (in_array(strtolower($row['order_status']), ['pending', 'processing'])) {

        $conn->begin_transaction();
        try {
            // --- Get all order items ---
            $sql = "SELECT product_name, package_size, quantity 
                    FROM order_items WHERE order_id=?";
            $stmtItems = $conn->prepare($sql);
            $stmtItems->bind_param("i", $order_id);
            $stmtItems->execute();
            $items = $stmtItems->get_result();

            // --- Restore stock ---
            while ($item = $items->fetch_assoc()) {
                $restore = $conn->prepare("UPDATE product_variants 
                                           SET quantity = quantity + ? 
                                           WHERE product_id = (SELECT id FROM products WHERE name = ?) 
                                             AND weight = ?");
                $restore->bind_param("iss", 
                    $item['quantity'], 
                    $item['product_name'], 
                    $item['package_size']
                );
                $restore->execute();
                $restore->close();
            }
            $stmtItems->close();

            // --- Update order status ---
            $update = $conn->prepare("UPDATE orders SET order_status='Cancelled' WHERE id=? AND user_id=?");
            $update->bind_param("ii", $order_id, $user_id);
            $update->execute();
            $update->close();

            $conn->commit();
            echo json_encode(["success" => true]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Stock restore failed"]);
        }

    } else {
        echo json_encode(["success" => false, "message" => "Order cannot be cancelled"]);
    }
}

