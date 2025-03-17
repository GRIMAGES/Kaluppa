<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// OTP Generation
function generateOTP() {
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send OTP via email with a verification link
function sendOTPByEmail($toEmail, $username, $otp, $subject) {
    $mail = new PHPMailer(true);

    try {
        // Configure PHPMailer for SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'wgonzales@kaluppa.org'; // Replace with your Gmail address
        $mail->Password   = 'qfsp ihop mdqg ngoy'; // Replace with your Gmail app-specific password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Set email details
        $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA'); // Replace with your name
        $mail->addAddress($toEmail, $username);

        // Generate the verification link
        $verificationLink = "https://kaluppa.online/Kaluppa/Backend/otpverification.php?email=" . urlencode($toEmail) . "&otp=" . urlencode($otp);


        // Set email content with the verification link
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "Hello $username,<br><br>Please click the link below to verify your account:<br><a href='$verificationLink'>$verificationLink</a><br><br>Or use the OTP code: $otp";

        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Generic function to send email
function sendEmail($toEmail, $username, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Configure PHPMailer for SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'wgonzales@kaluppa.org'; // Replace with your Gmail address
        $mail->Password   = 'qfsp ihop mdqg ngoy'; // Replace with your Gmail app-specific password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Set email details
        $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA'); // Replace with your name
        $mail->addAddress($toEmail, $username);

        // Set email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function generateResetToken() {
    // Generate a random token
    return bin2hex(random_bytes(32)); // Generates a 64-character token
}
?>
