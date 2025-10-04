<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Check login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode([]); 
    exit; 
}



// ✅ Query
$sql = "SELECT id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default
        FROM addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$out = [];
while ($row = $result->fetch_assoc()) {
    $out[] = $row;
}

// ✅ Output JSON
echo json_encode($out);

$stmt->close();
$conn->close();
?>
