<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'google_config.php';
require_once '../Backend/connection.php'; // DB connection
require_once '../Backend/log_helper.php'; // Include log helper

session_start(); // Start session at the top

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo "Error fetching token: " . htmlspecialchars($token['error']);
        exit;
    }

    // Validate the token to ensure it is issued by Google
    if (!$client->verifyIdToken($token['id_token'])) {
        echo "Invalid ID token.";
        exit;
    }

    $client->setAccessToken($token);

    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    // Extract Google user info
    $email = filter_var($userInfo->email, FILTER_SANITIZE_EMAIL);
    $first_name = htmlspecialchars($userInfo->givenName);
    $last_name = htmlspecialchars($userInfo->familyName);
    $profile_picture = filter_var($userInfo->picture, FILTER_SANITIZE_URL);

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    if (!$stmt) {
        error_log("Database error: " . $conn->error);
        exit("An error occurred. Please try again later.");
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing user
        $user = $result->fetch_assoc();
        session_regenerate_id(true); // Prevent session fixation attacks
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['role'] = $user['role'];

        // Log the login event for existing user
        $ipAddress = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // Get the user's browser information
        insertLog($user['id'], 'Login', 'User logged in using Google', 'info'); // Log login action
    } else {
        // New user registration
        $role = 'user'; // Default role
        $created_at = date('Y-m-d H:i:s');

        $insert = $conn->prepare("INSERT INTO user (first_name, last_name, email, profile_picture, role, created_at, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $insert->bind_param("ssssss", $first_name, $last_name, $email, $profile_picture, $role, $created_at);
        $insert->execute();

        $newUserId = $conn->insert_id;

        session_regenerate_id(true); // Prevent session fixation attacks
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['role'] = $role;

        // Log the login event for new user
        $ipAddress = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // Get the user's browser information
        insertLog($newUserId, 'Login', 'New user registered and logged in using Google', 'info'); // Log login action
    }

    // ✅ ROLE-BASED REDIRECTION
    if ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin') {
        error_log("Redirecting to admin dashboard");
        header("Location: ../Frontend/admin_dashboard/admin_dashboard.php");
        exit();
    } else if ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'alumni') {
        error_log("Redirecting to user dashboard");
        header("Location: ../Frontend/user_dashboard/user_dashboard.php");
        exit();
    } else {
        // Optional: Redirect other roles or unknown roles
        header("Location: ../Frontend/index.php");
        exit();
    }
} else {
    // 🔁 Redirect to Google Auth if code is not yet present
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}
?>
