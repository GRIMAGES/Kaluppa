<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/connection.php';

use setasign\Fpdi\Tcpdf\Fpdi;
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

// Process the uploaded file and apply password protection
$uploadedFilePath = $documentFile['tmp_name'];

// Preprocess the uploaded file using Ghostscript
$tempDir = sys_get_temp_dir(); // Use system's temporary directory
$preprocessedFilePath = $tempDir . '/' . uniqid('preprocessed_', true) . '.pdf';

$gsCommand = '/usr/bin/gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=' . escapeshellarg($preprocessedFilePath) . ' ' . escapeshellarg($uploadedFilePath);
exec($gsCommand . ' 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    error_log('Ghostscript Error: ' . implode("\n", $output)); // Log detailed error output
    echo json_encode(['success' => false, 'message' => 'Failed to preprocess the uploaded file.']);
    exit();
}

// Use the preprocessed file for FPDI
$uploadedFilePath = $preprocessedFilePath;

$protectedFilePath = __DIR__ . '/Documents/' . uniqid('protected_', true) . '.pdf';

try {
    $pdf = new Fpdi();
    $pdf->SetProtection(['print', 'copy'], $birthdate);
    $pdf->AddPage();

    // Import the uploaded file
    $pageCount = $pdf->setSourceFile($uploadedFilePath);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $pdf->useTemplate($templateId);
    }

    // Save the password-protected file
    $pdf->Output($protectedFilePath, 'F');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to process the uploaded file: ' . $e->getMessage()]);
    exit();
}

// Send the document via email
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0; // Disable verbose debug output
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

    // Attach the password-protected file
    $mail->addAttachment($protectedFilePath, basename($protectedFilePath));
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Document sent successfully.']);
} catch (Exception $e) {
    error_log('PHPMailer Error: ' . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
}
?>
