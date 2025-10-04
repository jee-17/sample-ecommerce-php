<?php
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (strtotime($row['reset_expires']) > time()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $newPass = trim($_POST['password'] ?? '');
                $id = $row['id'];
                $conn->query("UPDATE users SET password='$newPass', reset_token=NULL, reset_expires=NULL WHERE id=$id");
                
                // Show a JavaScript popup message and redirect to login
                echo "<script>
                    alert('Password reset successful. You can now log in.');
                    window.location.href = '../shop.html';
                    </script>";
                exit;
            } 
        } else {
            echo "<script>
                alert('❌ Reset link expired.');
                window.location.href = '../shop.html'; // Or any other page
                </script>";
            exit;
        }
    } else {
        echo "<script>
            alert('❌ Invalid reset token.');
            window.location.href = '../shop.html'; // Or any other page
            </script>";
        exit;
    }
} else {
    echo "<script>
        alert('❌ No token provided.');
        window.location.href = '../shop.html'; // Or any other page
        </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kalpaka Organics</title>
    <link href="https://fonts.googleapis.com/css2?family=Alice&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Alice', serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f4f4f4;
        }
        .reset-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #43b04a;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #378d3b;
        }
        .message {
            color: red;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <form method="POST">
    <h2>Set New Password</h2>
    <input type="password" name="password" placeholder="New password" required>
    <button type="submit">Reset Password</button>
</form>
</div>
</body>
</html>
