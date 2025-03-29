<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'connection.php';
require_once 'functions.php'; // Include the functions.php file for sendOTPByEmail
require_once '../vendor/autoload.php'; // if using composer
session_start();

$errors = [];

// Function to resend confirmation link
function resendConfirmationLink($email, $first_name, $otp) {
    $subject = "Resend: Verify Your Email";
    $message = "Click the link to verify your account: ";
    $message .= "https://kaluppa.online/Kaluppa/Backend/otpverification.php?email=$email&otp=$otp"; 
    return sendOTPByEmail($email, $first_name, $otp, $subject);
}

// Login Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email is locked
    $query = "SELECT failed_attempts, locked_until, password, is_verified, role FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $failedAttempts = $user['failed_attempts'];
        $lockedUntil = $user['locked_until'];

        // Check if the account is locked
        if ($lockedUntil && strtotime($lockedUntil) > time()) {
            $_SESSION['error'] = "This email is locked. Try again after " . date("H:i:s", strtotime($lockedUntil));
            header("Location: ../Frontend/index.php");
            exit();
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset failed attempts on successful login
            $query = "UPDATE user SET failed_attempts = 0, locked_until = NULL WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            if ($user['is_verified'] == 1) {
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'superadmin' || $user['role'] === 'admin') {
                    header("Location: ../Frontend/admin_dashboard/admin_dashboard.php");
                    exit();
                } else if ($user['role'] === 'user' || $user['role'] === 'alumni') {
                    header("Location: ../Frontend/user_dashboard/user_dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Account not verified. Please check your email for the verification link.";
            }
        } else {
            // Increment failed attempts
            $failedAttempts++;
            $lockedUntil = null;

            if ($failedAttempts >= 5) {
                $lockedUntil = date("Y-m-d H:i:s", strtotime("+15 minutes")); // Lock for 15 minutes
                $_SESSION['error'] = "Too many failed attempts for this email. It is locked for 15 minutes.";
            } else {
                $_SESSION['error'] = "Invalid email or password.";
            }

            $query = "UPDATE user SET failed_attempts = ?, locked_until = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $failedAttempts, $lockedUntil, $email);
            $stmt->execute();

            header("Location: ../Frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: ../Frontend/index.php");
        exit();
    }
}

// Registration Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_first_name'], $_POST['reg_password'], $_POST['gender'], $_POST['birthday'],)) {
    $first_name = $_POST['reg_first_name'];
    $middle_name = $_POST['reg_middle_name'];
    $last_name = $_POST['reg_last_name'];
    $email = $_POST['email'];
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday']; // Capture birthday
    $phone = $_POST['phone'];
    $province = $_POST['province'];
    $municipality = $_POST['municipality'];
    $barangay = $_POST['barangay'];
    $otp = generateOTP();

    if (strlen($password) < 10 || 
    !preg_match('/[A-Z]/', $password) || 
    !preg_match('/[a-z]/', $password) || 
    !preg_match('/[0-9]/', $password) || 
    !preg_match('/[@$!%*?&#^+=._-]/', $password)) {
    
    $_SESSION['error'] = "Password must be at least 10 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    header("Location: ../Frontend/index.php");
    exit();
}

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../Frontend/index.php");
        exit();
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    $otp_expiry = date('Y-m-d H:i:s', strtotime('+2 hour'));

    $stmt = $conn->prepare("INSERT INTO user (
        first_name, middle_name, last_name, email, password, role, gender,
        birthday, province, municipality, barangay, phone, otp, otp_expiry, is_verified
    ) VALUES (?, ?, ?, ?, ?, 'user', ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    
    $stmt->bind_param(
        "sssssssssssss",  // 14 values (excluding hardcoded ones)
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $password_hashed,
        $gender,
        $birthday,
        $province,
        $municipality,
        $barangay,
        $phone,
        $otp,
        $otp_expiry
    );

    if ($stmt->execute()) {
        $subject = "Verify Your Email";
        $message = "Click the link to verify your account: ";
        $message .= "https://kaluppa.online/Kaluppa/Backend/otpverification.php?email=$email&otp=$otp"; 
        $mailResult = sendOTPByEmail($email, $first_name, $otp, $subject);

        if ($mailResult === true) {
            $_SESSION['registration_success'] = 'Registration successful! Please check your email to verify your account.';
            $_SESSION['resend_email'] = $email;
            $_SESSION['resend_first_name'] = $first_name;
            $_SESSION['resend_otp'] = $otp;
            header("Location: ../Frontend/index.php"); 
            exit();
        } else {
            $_SESSION['error'] = "Error sending verification email: " . $mailResult;
            header("Location: ../Frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Error in registration. Please try again.";
        header("Location: ../Frontend/index.php");
        exit();
    }
}

// Optional: Display any errors in the session
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<div class='alert alert-danger'>$error</div>";
    }
}

// Resend confirmation link handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_confirmation'])) {
    $email = $_SESSION['resend_email'];
    $first_name = $_SESSION['resend_first_name'];
    $otp = $_SESSION['resend_otp'];

    $mailResult = resendConfirmationLink($email, $first_name, $otp);

    if ($mailResult === true) {
        $_SESSION['info'] = 'Verification email resent successfully! Please check your email.';
    } else {
        $_SESSION['error'] = "Error resending verification email: " . $mailResult;
    }
    header("Location: ../Frontend/index.php");
    exit();
}
?>
