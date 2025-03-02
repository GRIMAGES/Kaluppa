<?php
require_once 'connection.php';
require_once 'functions.php'; // Include the functions.php file for sendOTPByEmail
session_start();

$errors = [];

// Login Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: ../Frontend/admin dashboard/admin_dashboard.php");
                } else {
                    header("Location: ../Frontend/user dashboard/user_dashboard.php");
                }
                exit();
            } else {
                $_SESSION['error'] = "Account not verified. Please check your email for the verification link.";
            }
        } else {
            $_SESSION['error'] = "Invalid password.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }
    // Redirect to login page to show error message
    header("Location: ../Frontend/Multiuserlogin.php");
    exit();
}

// Registration Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_first_name'], $_POST['reg_password'], $_POST['gender'], $_POST['birthday'])) {
    $first_name = $_POST['reg_first_name'];
    $middle_name = $_POST['reg_middle_name'];
    $last_name = $_POST['reg_last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday']; // Capture birthday
    $house_number = $_POST['house_number'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $district = $_POST['district'];
    $city = $_POST['city'];
    $region = $_POST['region'];
    $postal_code = $_POST['postal_code'];
    $phone = $_POST['phone'];
    $otp = generateOTP();

    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, password, role, gender, birthday, house_number, street, barangay, district, city, region, postal_code, phone, otp, is_verified) VALUES (?, ?, ?, ?, ?, 'user', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

$stmt->bind_param("ssssssssssssssss", $first_name, $middle_name, $last_name, $email, $password, $gender, $birthday, $house_number, $street, $barangay, $district, $city, $region, $postal_code, $phone, $otp);

    if ($stmt->execute()) {
        $subject = "Verify Your Email";
        $message = "Click the link to verify your account: ";
        $message .= "http://localhost:3000/Backend/otpverification.html?email=$email&otp=$otp"; 
        $mailResult = sendOTPByEmail($email, $first_name, $otp, $subject);

        if ($mailResult === true) {
            $_SESSION['info'] = 'Registration successful! Please check your email to verify your account.';
            header("Location: ../Frontend/Multiuserlogin.php"); 
            exit();
        } else {
            $errors[] = "Error sending verification email: " . $mailResult;
        }
    } else {
        $errors[] = "Error in registration. Please try again.";
    }
}

// Optional: Display any errors in the session
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<div class='alert alert-danger'>$error</div>";
    }
}
?>
