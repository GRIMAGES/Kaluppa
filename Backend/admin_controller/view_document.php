<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/aes_key.php'; // AES_KEY and AES_IV defined here
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../Backend/logs/view_document_errors.log');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error_code' => 1, 'message' => 'User not logged in']);
    exit();
}

// Get logged-in user info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'User not found']);
    exit();
}
$user_id = $user['id'];

// Get Application ID from GET request
if (!isset($_GET['application_id'])) {
    echo json_encode(['success' => false, 'error_code' => 3, 'message' => 'Application ID is required']);
    exit();
}

$application_id = $_GET['application_id'];

// Fetch encrypted documents from database
$query = "SELECT documents FROM applications WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    echo json_encode(['success' => false, 'error_code' => 4, 'message' => 'Application not found']);
    exit();
}

// Decrypt documents and prepare for download
$encryptedDocuments = json_decode($application['documents'], true);
$documents = [];

foreach ($encryptedDocuments as $document) {
    $decryptedData = openssl_decrypt(
        base64_decode($document['file_data']),
        'AES-256-CBC',
        AES_KEY,
        OPENSSL_RAW_DATA,
        AES_IV
    );

    if ($decryptedData === false) {
        echo json_encode(['success' => false, 'error_code' => 5, 'message' => 'Failed to decrypt document']);
        exit();
    }

    $documents[] = [
        'file_name' => $document['file_name'],
        'file_data' => $decryptedData
    ];
}

// Handle download
if (isset($_GET['download'])) {
    $fileName = $_GET['download'];

    // Search for the requested file in the decrypted documents
    foreach ($documents as $document) {
        if ($document['file_name'] === $fileName) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo $document['file_data'];
            exit();
        }
    }

    echo json_encode(['success' => false, 'error_code' => 6, 'message' => 'Document not found']);
    exit();
}

// Return documents information if not downloading
echo json_encode([
    'success' => true,
    'documents' => array_map(function ($document) {
        return $document['file_name'];
    }, $documents)
]);
?>
