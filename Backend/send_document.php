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
$tempDir = __DIR__ . '/temp';
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        error_log('Failed to create temp directory: ' . $tempDir);
        echo json_encode(['success' => false, 'message' => 'Server error: Unable to create temporary directory.']);
        exit();
    }
}
$preprocessedFilePath = $tempDir . '/' . uniqid('preprocessed_', true) . '.pdf';

$gsCommand = '/usr/bin/gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=' . escapeshellarg($preprocessedFilePath) . ' ' . escapeshellarg($uploadedFilePath);
exec($gsCommand . ' 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    // Log detailed error output for debugging
    error_log('Ghostscript Command: ' . $gsCommand);
    error_log('Ghostscript Output: ' . implode("\n", $output));
    error_log('Ghostscript Return Code: ' . $returnVar);

    echo json_encode(['success' => false, 'message' => 'Failed to preprocess the uploaded file. Please check the server logs for more details.']);
    exit();
}

// Use the preprocessed file for FPDI
$uploadedFilePath = $preprocessedFilePath;

$documentsDir = __DIR__ . '/Documents';
if (!is_dir($documentsDir)) {
    if (!mkdir($documentsDir, 0755, true) && !is_dir($documentsDir)) {
        error_log('Failed to create documents directory: ' . $documentsDir);
        echo json_encode(['success' => false, 'message' => 'Server error: Unable to create documents directory.']);
        exit();
    }
}
$protectedFileName = uniqid('protected_', true) . '.pdf';
$protectedFilePath = $documentsDir . '/' . $protectedFileName;

// Generate public URL for the file
$publicFileUrl = 'https://www.kaluppa.online/Kaluppa/Backend/Documents/' . $protectedFileName;

try {
    $pdf = new Fpdi();
    $pdf->SetProtection(['print', 'copy'], $birthdate); // Password protection using the user's birthday
    $pdf->AddPage();

    // Import the uploaded file
    $pageCount = $pdf->setSourceFile($uploadedFilePath);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $pdf->useTemplate($templateId);
    }

    // Save the password-protected file
    $pdf->Output($protectedFilePath, 'F');

    // Send email to the alumni using PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org';
        $mail->Password = 'qfsp ihop mdqg ngoy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('wgonzales@kaluppa.org', 'Your Team');
        $mail->addAddress($alumniEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Document is Ready';
        $mail->Body = "Dear Alumni,<br><br>Your requested document has been processed and is available for download.<br><br><a href='" . $publicFileUrl . "'>Download Your Document</a><br><br><strong>Note:</strong> The password to open the file is your birthday in the format YYYY-MM-DD.<br><br>Best regards,<br>Your Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Document saved and email sent successfully.', 'file_url' => $publicFileUrl]);
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        echo json_encode(['success' => true, 'message' => 'Document saved successfully, but failed to send email.', 'file_url' => $publicFileUrl]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to process the uploaded file: ' . $e->getMessage()]);
    exit();
}
?>
