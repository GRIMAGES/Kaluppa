<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
session_start(); // Start session to store messages if needed

if (isset($_GET['email'], $_GET['otp'])) {
    $email = $_GET['email'];
    $otp = $_GET['otp'];

    // Verify OTP and email in the database with expiry check
    $stmt = $conn->prepare("SELECT otp_expiry FROM user WHERE email = ? AND otp = ? AND is_verified = 0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $otp_expiry = $row['otp_expiry'];

        // Check if OTP has expired
        if (strtotime($otp_expiry) >= time()) {
            // OTP is valid and not expired; mark user as verified
            $updateStmt = $conn->prepare("UPDATE user SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            if ($updateStmt->execute()) {
                $_SESSION['info'] = "Account successfully verified! You can now log in.";
                header("Location: ../Frontend/index.php");
                exit();
            } else {
                $_SESSION['error'] = "Verification failed. Please try again later.";
                header("Location: ../Frontend/index.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "This verification link has expired. Please request a new one.";
            header("Location: ../Frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid or expired verification link.";
        header("Location: ../Frontend/index.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid verification request.";
    header("Location: ../Frontend/index.php");
    exit();
}
?>
