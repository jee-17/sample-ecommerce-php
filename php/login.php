<?php
session_start();
header('Content-Type: application/json');
require_once "db.php";

// ✅ Get form data
$username = isset($_POST['name']) ? trim($_POST['name']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($username === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit;
}

// ✅ Query users table
$sql = "SELECT id, name, password, profile_pic, is_blocked FROM users WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // ✅ Check if account is blocked
    if ($user['is_blocked'] == 1) {
        echo json_encode([
            "success" => false, 
            "message" => "Your account is blocked. Please contact the admin."
        ]);
    } elseif ($password === $user['password']) {
        // ✅ Correct password + not blocked
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['profile_pic'] = $user['profile_pic'];

        // ✅ Redirect logic
        $redirect_url = ($user['name'] === 'Admin') ? '../Admin/Admin_home.php' : 'shop.html';

        echo json_encode([
            "success" => true,
            "message" => "Login successful. Welcome, " . $user['name'] . " 🎉",
            "username" => $user['name'],
            "redirect" => $redirect_url,
            "profile_pic" => "uploads/" . $user['profile_pic']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid username or password."]);
}

// ✅ Cleanup
$stmt->close();
$conn->close();
?>
