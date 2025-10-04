
<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';

    $mail = new PHPMailer(true);

    try {
        // Common mailer settings for all forms
        
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = false;
        $mail->Username   = 'support@kalpakaorganics.com';
        $mail->Password   = 'Kalpaka@123';
        $mail->Port       = 25;
        $mail->setFrom('support@kalpakaorganics.com', 'Kalpaka Organics');
        $mail->addAddress('kalpakaorganics@gmail.com','admin');
        $mail->addBCC('samudhratechsolutions@gmail.com'); // BCC recipient


        $mail->isHTML(true);

        // Build the email body with an HTML table
        $mail->Body = "<h2>Form Submission Details</h2>";
        $mail->Body .= "<table style='border-collapse: collapse; width: 100%; border: 1px solid #ddd;'>";

        foreach ($_POST as $key => $value) {
            // Skip the form_type and _subject fields
            if ($key === 'form_type' || $key === '_subject') {
                continue;
            }
            
            // Format the key to be more readable
            $label = ucwords(str_replace(['_', '-'], ' ', $key));

            // Sanitize and add each field to a table row
            $safe_value = htmlspecialchars($value);

            $mail->Body .= "<tr>";
            $mail->Body .= "<td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>" . $label . "</td>";
            $mail->Body .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . nl2br($safe_value) . "</td>";
            $mail->Body .= "</tr>";
        }

        $mail->Body .= "</table>";
        
        // Set subject based on form type
        switch ($form_type) {
            case 'Supplier Registration Form Submission':
                $mail->Subject = 'New Supplier Registration';
                break;
            case 'Distributor Registration Form Submission':
                $mail->Subject = 'New Distributor Registration';
                break;
            case 'Enquiry Form Submission':
                $mail->Subject = 'New Enquiry Form Submission';
                break;
            default:
                $mail->Subject = 'New General Form Submission';
                break;
        }

        $mail->send();
        echo "<p style='color:green;'>Form submitted successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Submission failed. Mailer Error: {$mail->ErrorInfo}</p>";
    }
} else {
    echo 'Invalid request.';
}
?>
