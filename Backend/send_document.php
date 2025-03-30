<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/../vendor/autoload.php';
require_once  '/connection.php';

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
$stmt = $conn->prepare("SELECT birthdate FROM user WHERE email = ?");
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
$birthdate = $user['birthdate']; // Use birthdate as the password
$stmt->close();

// Generate a password-protected PDF
require_once '../../vendor/setasign/fpdf/fpdf.php'; // Correct path to FPDF
require_once '../../vendor/setasign/fpdi/src/autoload.php'; // Correct path to FPDI
$pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);
$pdf->Write(10, "This is your requested document.");

// Set password protection
$pdf->SetProtection(['print', 'copy'], $birthdate);

// Save the PDF to a temporary file
$pdfFilePath = sys_get_temp_dir() . '/' . uniqid('document_', true) . '.pdf';
$pdf->Output($pdfFilePath, 'F');

// Send the email with the encrypted PDF
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

    $mail->addAttachment($pdfFilePath, 'Requested_Document.pdf');
    $mail->send();

    // Clean up the temporary file
    unlink($pdfFilePath);

    echo json_encode(['success' => true, 'message' => 'Document sent successfully.']);
} catch (Exception $e) {
    error_log('PHPMailer Error: ' . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
}
?>
