<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../connection.php';  // Corrected path to connection.php

// Debugging: Print the GET variables to check if user_id is passed
var_dump($_GET);

function generateCertificate($userId, $templatePath, $outputDir) {
    global $conn;  // Access the global connection object

    // Fetch user data
    $query = "SELECT user.first_name, user.last_name, courses.name AS course_name FROM user 
              INNER JOIN applications ON user.id = applications.user_id 
              INNER JOIN courses ON applications.course_id = courses.id 
              WHERE user.id = ?";
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
    $fontPath =  '/../../vendor/tecnickcom/tcpdf/fonts/helvetica.ttf'; // Update to use helvetica font
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

// Fetch the template path from the database
$templateQuery = "SELECT file_path FROM certificate_templates ORDER BY id DESC LIMIT 1";
$templateResult = $conn->query($templateQuery);
$templateData = $templateResult->fetch_assoc();
if (!$templateData) {
    throw new Exception("Template not found in the database.");
}
$templatePath = $templateData['file_path'];

// Example usage
try {
    // Check if user_id is set
    if (!isset($_GET['user_id'])) {
        die("Error: user_id not provided.");
    }

    $userId = $_GET['user_id']; // Get user ID from request
    $outputDir = '../../Backend/admin_controller/generated_certificates'; // Directory to save certificates
    $certificatePath = generateCertificate($userId, $templatePath, $outputDir);
    echo "Certificate generated: " . $certificatePath;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
