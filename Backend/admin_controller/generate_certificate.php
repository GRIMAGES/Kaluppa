<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../connection.php';  // Corrected path to connection.php

function generateCertificate($userId, $templatePath, $outputDir) {
    global $conn;  // Access the global connection object

    // Fetch user data
    $query = "SELECT first_name, last_name, course_name FROM user INNER JOIN applications ON user.id = applications.user_id WHERE user.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Load the template
    $template = imagecreatefrompng($templatePath);
    if (!$template) {
        throw new Exception("Failed to load template. Ensure the template exists and the path is correct.");
    }

    // Define text color and font
    $black = imagecolorallocate($template, 0, 0, 0);
    $fontPath = __DIR__ . '/fonts/arial.ttf'; // Ensure this path is correct
    if (!file_exists($fontPath)) {
        throw new Exception("Font file not found: " . $fontPath);
    }

    // Customize the certificate with user data
    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    $courseName = $user['course_name'];
    imagettftext($template, 20, 0, 100, 150, $black, $fontPath, $fullName);
    imagettftext($template, 20, 0, 100, 200, $black, $fontPath, $courseName);

    // Save the certificate
    if (!is_dir($outputDir)) {
        throw new Exception("Output directory not found: " . $outputDir);
    }
    $outputPath = $outputDir . '/certificate_' . $userId . '.png';
    if (!imagepng($template, $outputPath)) {
        throw new Exception("Failed to save certificate.");
    }

    imagedestroy($template);

    return $outputPath;
}

// Example usage
try {
    $userId = $_GET['user_id']; // Get user ID from request
    $templatePath = '../../Backend/admin_controller/templates/template.png'; // Path to the template
    $outputDir = '../../Backend/admin_controller/generated_certificates'; // Directory to save certificates
    $certificatePath = generateCertificate($userId, $templatePath, $outputDir);
    echo "Certificate generated: " . $certificatePath;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
