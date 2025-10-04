<?php

include 'db.php'; // your DB connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Adjust the path if using PHPMailer manually

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        // Store token and expiry in DB
        $conn->query("UPDATE users SET reset_token='$token', reset_expires='$expires' WHERE email='$email'");

        // Create reset link
        $resetLink = "https://kalpakaorganics.com/php/reset_password.php?token=$token";
        
        // Set up PHPMailer
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'mail.kalpakaorganics.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'no-reply@kalpakaorganics.com'; 
            $mail->Password   = 'kalpaka@123';           
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom('no-reply@kalpakaorganics.com', 'Kalpaka Organics');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the link to reset your password: <a href='$resetLink'>$resetLink</a>";

            // Send email
            $mail->send();
            echo "✅ Reset link sent to your email.";
        } catch (Exception $e) {
            echo "❌ Mail could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "❌ Email not found.";
    }
}
?>
