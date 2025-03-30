<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json'); // Ensure the response is JSON

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

// Fetch the user's birthdate from the database
$stmt = $conn->prepare("SELECT birthday FROM user WHERE email = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $alumniEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

$user = $result->fetch_assoc();
$birthdate = $user['birthday']; // Use birthdate as the password
$stmt->close();

// Use the uploaded file as the attachment
$uploadedFilePath = $documentFile['tmp_name'];
$uploadedFileName = $documentFile['name'];

// Send the email with the uploaded file
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'wgonzales@kaluppa.org';
    $mail->Password = 'qfsp ihop mdqg ngoy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('wgonzales@kaluppa.org', 'Admin Team');
    $mail->addAddress($alumniEmail);
    $mail->Subject = 'Your Requested Document';
    $mail->Body = "Dear Alumni,\n\nPlease find your requested document attached. The password to open the document is your birthdate (YYYY-MM-DD).\n\nBest regards,\nAdmin Team";

    // Attach the uploaded file
    $mail->addAttachment($uploadedFilePath, $uploadedFileName);
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Document sent successfully.']);
} catch (Exception $e) {
    error_log('PHPMailer Error: ' . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
}
?>
