<?php
require_once 'connection.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Print form data for debugging
    var_dump($_POST);

    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $gender = $_POST['gender'];

    // Generate a temporary password
    $tempPassword = bin2hex(random_bytes(4)); // 8 characters long
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Automatically verify the user
    $isVerified = 1;

    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, role, gender, password, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sssssssi', $firstName, $middleName, $lastName, $email, $role, $gender, $hashedPassword, $isVerified);

    if ($stmt->execute()) {
        // Send email notification
        $subject = "Your Account Has Been Created";
        $message = "Hello $firstName,\n\nYour account has been created. Here are your login details:\n\nEmail: $email\nTemporary Password: $tempPassword\n\nPlease change your password after logging in.";

        if (sendEmail($email, $firstName, $subject, $message)) {
            echo 'User added successfully and email sent';
        } else {
            error_log('User added successfully but email sending failed');
            echo 'User added successfully but email sending failed';
        }
    } else {
        error_log('Error adding user: ' . $stmt->error);
        echo 'Error adding user: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}