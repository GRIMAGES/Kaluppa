<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
require '../../vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$requestId = $_POST['requestId'] ?? '';
$alumniEmail = $_POST['alumniEmail'] ?? '';
$documentFile = $_FILES['documentFile'] ?? null;

if (empty($requestId) || empty($alumniEmail) || !$documentFile) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

// Validate the uploaded file
if ($documentFile['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error.']);
    exit();
}

try {
    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'wgonzales@kaluppa.org';
    $mail->Password = 'qfsp ihop mdqg ngoy'; // Replace with your email password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email settings
    $mail->setFrom('wgonzales@kaluppa.org', 'Admin Team'); // Replace with your email
    $mail->addAddress($alumniEmail);
    $mail->Subject = 'Your Requested Document';
    $mail->Body = "Dear Alumni,\n\nPlease find your requested document attached.\n\nBest regards,\nAdmin Team";

    // Attach the uploaded file
    $mail->addAttachment($documentFile['tmp_name'], $documentFile['name']);

    // Send the email
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Document sent successfully.']);
} catch (Exception $e) {
    error_log('PHPMailer Error: ' . $mail->ErrorInfo); // Log the error
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
}
?>
