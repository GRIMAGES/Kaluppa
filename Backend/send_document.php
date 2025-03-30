<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';

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

// Prepare email with attachment
$subject = "Your Requested Document";
$message = "Dear Alumni,\n\nPlease find your requested document attached.\n\nBest regards,\nAdmin Team";

// Boundary for the email
$boundary = md5(time());

// Email headers
$headers = "From: admin@kaluppa.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

// Email body
$emailBody = "--$boundary\r\n";
$emailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
$emailBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$emailBody .= $message . "\r\n\r\n";

// Attachment
$fileContent = file_get_contents($documentFile['tmp_name']);
$fileName = $documentFile['name'];
$emailBody .= "--$boundary\r\n";
$emailBody .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
$emailBody .= "Content-Transfer-Encoding: base64\r\n";
$emailBody .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
$emailBody .= chunk_split(base64_encode($fileContent)) . "\r\n";
$emailBody .= "--$boundary--";

// Send the email
if (mail($alumniEmail, $subject, $emailBody, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Document sent successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
}
?>
