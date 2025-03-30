<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/connection.php';

use setasign\Fpdi\Tcpdf\Fpdi;

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
    echo json_encode(['success' => true, 'message' => 'Document saved successfully.', 'file_path' => $protectedFilePath]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to process the uploaded file: ' . $e->getMessage()]);
    exit();
}
?>
