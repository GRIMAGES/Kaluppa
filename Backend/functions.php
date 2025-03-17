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
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org';
        $mail->Password = 'qfsp ihop mdqg ngoy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA');
        $mail->addAddress($toEmail, $username);
        $mail->addReplyTo('wgonzales@kaluppa.org', 'KALUPPA');

        // Generate verification link
        $verificationLink = "https://kaluppa.online/Kaluppa/Backend/otpverification.php?email=" . urlencode($toEmail) . "&otp=" . urlencode($otp);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "Hello $username,<br><br>Please click the link below to verify your account:<br><a href='$verificationLink'>$verificationLink</a><br><br>Or use the OTP code: $otp";
        $mail->AltBody = "Hello $username,\n\nPlease use this OTP code to verify your account: $otp\n\nOr visit: $verificationLink";

        if(!$mail->send()) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        return true;

    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Generic function to send email
function sendEmail($toEmail, $username, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org';
        $mail->Password = 'qfsp ihop mdqg ngoy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA');
        $mail->addAddress($toEmail, $username);
        $mail->addReplyTo('wgonzales@kaluppa.org', 'KALUPPA');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        if(!$mail->send()) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        return true;

    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generateResetToken() {
    // Generate a random token
    return bin2hex(random_bytes(32)); // Generates a 64-character token
}
?>
