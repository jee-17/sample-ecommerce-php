<?php
session_start();
require_once "db.php";


header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => 'Unknown error'];

// --- Get form values ---
$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$passwordRaw = trim($_POST['password'] ?? '');

if ($name === '' || $email === '' || $passwordRaw === '') {
    echo "Error: Name, email, and password are required.";
    echo json_encode($response);
    exit;
}

// --- ✅ Email validation ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email format.";
    echo json_encode($response);
    exit;
}

// --- Check if email already exists ---
$stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $response['message']=' Email already registered.';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt->close();

$stmt = $conn->prepare("SELECT 1 FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $response['message']=' Name already registered.';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt->close();

// --- Hash password ---
$password = $passwordRaw;

// --- Insert into database ---
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
    $response['success']=true;
    $response['message']='👍 Registration successful.';
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
echo json_encode($response);
exit;
?>
