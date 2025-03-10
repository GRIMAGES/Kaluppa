<?php
session_start();
include('db_connection.php'); // Adjust this to your DB connection file

if (!isset($_SESSION['email'])) {
    header("Location: login_google.php");
    exit;
}

$email = $_SESSION['email'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Check if user already exists
$query = $conn->prepare("SELECT * FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    // User exists - log them in
    $_SESSION['user'] = $result->fetch_assoc();
    header("Location: user_dashboard.php");
    exit;
} else {
    // User doesn't exist - register
    $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, provider) VALUES (?, ?, ?, 'google')");
    $insert->bind_param("sss", $first_name, $last_name, $email);
    if ($insert->execute()) {
        $_SESSION['user'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'provider' => 'google'
        ];
        header("Location: user_dashboard.php");
        exit;
    } else {
        echo "Registration failed. Please try again.";
    }
}
?>
