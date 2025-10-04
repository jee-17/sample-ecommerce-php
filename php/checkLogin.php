<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ✅ Check if session contains username
if (!empty($_SESSION['username'])) {
    echo json_encode([
        "loggedIn" => true,
        "username" => $_SESSION['username']
    ]);
} else {
    echo json_encode([
        "loggedIn" => false
    ]);
}
?>
