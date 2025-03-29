<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Adjust path if needed

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'wgonzales@kaluppa.org'; // Replace with your Gmail address
    $mail->Password = 'qfsp ihop mdqg ngoy'; // Replace with your Gmail password or App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA');
    $mail->addAddress('recipient@example.com', 'Recipient Name'); // Replace with recipient email

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email sent using PHPMailer.';
    $mail->AltBody = 'This is a test email sent using PHPMailer.';

    // Send email
    $mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
    error_log("Mailer Error: {$mail->ErrorInfo}");
}
?>
