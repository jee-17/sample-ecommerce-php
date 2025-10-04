<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json;charset=utf-8');
require_once "db.php";
require_once "../config.php";

date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';


// --- Check login ---
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(["success"=>false,"message"=>"Login required"]); 
    exit; 
}


$uid = $_SESSION['user_id'];
$address_id = intval($_POST['address_id'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';
 $upi_id    = trim($_POST['upiId'] ?? '');
$payment_phone  = trim($_POST['payment_phone'] ?? '');



if ($address_id <= 0) { 
    echo json_encode(["success"=>false,"message"=>"Address required"]); 
    exit; 
}

// --- Fetch user ---
$stmtUser = $conn->prepare("SELECT name,email FROM users WHERE id=?");
$stmtUser->bind_param("i", $uid);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$customerName = $userRow['name'] ?? 'Customer';
$customerEmail = $userRow['email'] ?? '';
$stmtUser->close();

// --- Fetch address ---
$stmtAddr = $conn->prepare("SELECT full_name, phone, address_line1, address_line2, city, state, postal_code 
                            FROM addresses WHERE id=? AND user_id=?");
$stmtAddr->bind_param("ii", $address_id, $uid);
$stmtAddr->execute();
$addrRow = $stmtAddr->get_result()->fetch_assoc();
$stmtAddr->close();

$addressText = $addrRow['full_name']."\n".$addrRow['address_line1']." ".$addrRow['address_line2']."\n".
               $addrRow['city'].", ".$addrRow['state']." - ".$addrRow['postal_code']."\nPhone: ".$addrRow['phone'];

// --- Fetch items ---
$items = [];
$subtotal = 0;
$stmt = $conn->prepare("SELECT product_name, package_size, product_image, price, quantity 
                        FROM checkout_items WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
$stmt->close();

if (empty($items)) { 
    echo json_encode(["success"=>false,"message"=>"No items to order"]); 
    exit; 
}

$delivery = ($subtotal >= 999) ? 0 : 40;
$total = $subtotal + $delivery;

// --- Insert order ---
$stmtOrder = $conn->prepare("INSERT INTO orders 
    (user_id, address_id, subtotal, delivery_fee, total_amount, payment_method, upi_id, payment_phone, payment_status, order_status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Processing', NOW())");
$stmtOrder->bind_param("iiidssss", $uid, $address_id, $subtotal, $delivery, $total, $payment_method, $upi_id, $payment_phone);

if (!$stmtOrder->execute()) {
    echo json_encode(["success"=>false,"message"=>"Could not create order","error"=>$stmtOrder->error]);
    exit;
}
$order_id = $stmtOrder->insert_id;
$stmtOrder->close();

// --- Insert order items ---
$stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_name, package_size, product_image, price, quantity)
                            VALUES (?, ?, ?, ?, ?, ?)");
foreach ($items as $it) {
    $stmtItem->bind_param("isssdi", $order_id, $it['product_name'], $it['package_size'], $it['product_image'], $it['price'], $it['quantity']);
    $stmtItem->execute();
}
$stmtItem->close();

// --- Clear checkout ---
$stmtDel = $conn->prepare("DELETE FROM temp_checkout_items WHERE user_id=?");
$stmtDel->bind_param("i", $uid);
$stmtDel->execute();
$stmtDel->close();

// ===================================================
// 1. Telegram Notification to Admin
// ===================================================
$botToken = "8354690416:AAEj7WNJrAtspuXcfNBd8J_PPKzaJn8WSNs";
$chatId   = "8264736599";

$orderText = "🛒 New Order #$order_id\nCustomer: $customerName\nEmail: $customerEmail\n\n📍 Address:\n$addressText\n\nItems:\n";
foreach ($items as $it) {
    $orderText .= "{$it['product_name']} ({$it['package_size']}) x{$it['quantity']} = ₹".($it['price']*$it['quantity'])."\n";
}
$orderText .= "\nSubtotal: ₹$subtotal\nDelivery: ₹$delivery\nTotal: ₹$total\nPayment: $payment_method";

$telegramUrl = "https://api.telegram.org/bot$botToken/sendMessage";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'chat_id' => $chatId,
    'text'    => $orderText,
    'parse_mode' => 'HTML'
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    error_log("Telegram Error: " . curl_error($ch));
}
curl_close($ch);

// ===================================================
// 2. Email to Customer (PHPMailer)
// ===================================================
if (!empty($customerEmail)) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.kalpakaorganics.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@kalpakaorganics.com';      // ✅ your Gmail
        $mail->Password   = 'kalpaka@123';            // ✅ Gmail App Password
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('info@kalpakaorganics.com', 'Kalpaka Organics');
        $mail->addAddress($customerEmail, $customerName);

        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation #$order_id - My Store";

        $body = "<h3>Thank you for your order, $customerName!</h3>";
        $body .= "<p>Your Order ID: <strong>$order_id</strong></p>";
        $body .= "<p><strong>Delivery Address:</strong><br>".nl2br($addressText)."</p>";

        $body .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                    <tr style='background:#f2f2f2;'>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Price</th>
                    </tr>";
        foreach ($items as $it) {
            $imgTag = "<img src='{$it['product_image']}' alt='{$it['product_name']}' width='80' height='80'>";
            $body .= "<tr>
                        <td align='center'>$imgTag</td>
                        <td>{$it['product_name']}</td>
                        <td>{$it['package_size']}</td>
                        <td>{$it['quantity']}</td>
                        <td>₹".($it['price']*$it['quantity'])."</td>
                      </tr>";
        }
        $body .= "</table>";

        $body .= "<p><strong>Total: ₹$total</strong></p>";
        $body .= "<p>Payment Method: $payment_method</p>";
        $body .= "<br><p>We will notify you once your order is shipped 🚚</p>";

        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

/*
// ===================================================
// 3. Email to Admin (PHPMailer)
// ===================================================
$adminEmail = "kalpakaorganics@gmail.com"; // 🔴 Replace with your email

$mailAdmin = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'mail.kalpakaorganics.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@kalpakaorganics.com';      // ✅ your Gmail
    $mail->Password   = 'kalpaka@123';            // ✅ Gmail App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mailAdmin->setFrom('info@kalpakaorganics.com', 'My Store - Orders');
    $mailAdmin->addAddress($adminEmail, 'Store Admin');

    $mailAdmin->isHTML(true);
    $mailAdmin->Subject = "New Order #$order_id Received";

    $adminBody = "<h2>New Order Placed</h2>";
    $adminBody .= "<p><strong>Order ID:</strong> $order_id</p>";
    $adminBody .= "<p><strong>Customer:</strong> $customerName ($customerEmail)</p>";
    $adminBody .= "<p><strong>Address:</strong><br>".nl2br($addressText)."</p>";
    
    $adminBody .= "<h3>Order Items</h3>";
    $adminBody .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                    <tr style='background:#f2f2f2;'>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Price</th>
                    </tr>";
        foreach ($items as $it) {
            $imgTag = "<img src='{$it['product_image']}' alt='{$it['product_name']}' width='80' height='80'>";
            $adminBody .= "<tr>
                        <td align='center'>$imgTag</td>
                        <td>{$it['product_name']}</td>
                        <td>{$it['package_size']}</td>
                        <td>{$it['quantity']}</td>
                        <td>₹".($it['price']*$it['quantity'])."</td>
                      </tr>";
        }
        $adminBody .= "</table>";
    
    $adminBody .= "<p><strong>Total:</strong> ₹$total</p>";
    $adminBody .= "<p><strong>Payment:</strong> $payment_method</p>";

    $mailAdmin->Body = $adminBody;
    $mailAdmin->send();
} catch (Exception $e) {
    error_log("Mailer Error (Admin): " . $mailAdmin->ErrorInfo);
}
*/
$conn->close();

// --- Final Response ---
echo json_encode(["success"=>true,"order_id"=>$order_id]);
?>
