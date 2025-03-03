<?php
session_start();
require_once 'connection.php';
require_once 'functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$emailFound = false; // Track if email is found in the database

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    if (isset($_POST['forgot_email'])) {
        $forgot_email = filter_var(trim($_POST['forgot_email']), FILTER_SANITIZE_EMAIL);

        // Query the database for the user
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $forgot_email);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $emailFound = $user ? true : false; // Determine if email was found
        }
        $stmt->close();
    }

    if ($emailFound) {
        $resetToken = bin2hex(random_bytes(32));

        // Update token in database
        $stmt = $conn->prepare("UPDATE user SET reset_token = ? WHERE email = ?");
        $stmt->bind_param("ss", $resetToken, $forgot_email);
        $stmt->execute();
        $stmt->close();

        $_SESSION['resetEmail'] = $forgot_email;

        // Attempt to send the email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wgonzales@kaluppa.org';
            $mail->Password   = 'ngqt vydl kvjz lgsl';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('wgonzales@kaluppa.org', 'KALUPPA | RESET PASSWORD');
            $mail->addAddress($forgot_email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $mail->Body    = 'Click the following link to reset your password: <a href="http://localhost:3000/Frontend/changepassword.php?reset_token=' . $resetToken . '">Reset Password</a>';

            $mail->send();

            $message = 'Reset password link sent successfully.';
        } catch (Exception $e) {
            // Capture the exception in the message for error feedback
            $message = 'Error sending the password reset link: ' . $mail->ErrorInfo;
        }
    } else {
        // Set the error message only if email not found
        $message = 'No account found with that email address.';
    }
}

$conn->close();
echo $message; // Output the message directly for the AJAX response
?>
