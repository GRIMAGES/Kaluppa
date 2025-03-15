<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../connection.php';
require_once '../aes_key.php'; // contains AES_KEY and AES_IV

// Check if user is authenticated
if (!isset($_SESSION['email'])) {
    header("Location: ../../Frontend/index.php");
    exit();
}

// Validate input parameters
if (!isset($_GET['file']) || !isset($_GET['action'])) {
    die("Invalid request.");
}

// Extract the filename if it's in an array
$file = $_GET['file'];
if (is_array($file)) {
    $file = reset($file);
}

// Sanitize the file name
$file = trim(urldecode($file));
$file = basename($file);

$action = $_GET['action'];

// Define the correct file directory
$file_dir = realpath(__DIR__ . '/opt/bitnami/apache/htdocs/Kaluppa/Backend/Documents/Scholarship/') . DIRECTORY_SEPARATOR;
$file_path = $file_dir . $file;

// Debugging: Check the file path
if (!file_exists($file_path)) {
    die("❌ File not found: " . htmlspecialchars($file_path));
}

// Ensure the file is a valid file and not a directory
if (!is_file($file_path)) {
    die("❌ The path is not a valid file: " . htmlspecialchars($file_path));
}

// Ensure the file is within the allowed directory
if (strpos(realpath($file_path), $file_dir) !== 0) {
    die("🚫 Access denied.");
}

// Generate a public URL for viewing (Modify this for your server setup)
$base_url = "https://www.kaluppa.online/Kaluppa/Backend/Documents/Scholarship";
$file_url = $base_url . '/' . urlencode($file);

// Serve file based on action
if ($action === 'view') {
    // Read and decrypt file content
    $encryptedContent = file_get_contents($file_path);
    $decryptedContent = openssl_decrypt(
        base64_decode($encryptedContent),
        'AES-256-CBC',
        AES_KEY,
        OPENSSL_RAW_DATA,
        AES_IV
    );

    // Check decryption success
    if ($decryptedContent === false) {
        die("❌ Failed to decrypt the file. Please check your AES key and IV.");
    }

    // Detect MIME type manually (optional, but safer)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($decryptedContent);

    $inline_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'text/plain'];

    if (in_array($mime_type, $inline_types)) {
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . strlen($decryptedContent));
        echo $decryptedContent;
        exit();
    } else {
        // Save temporarily if not inline-viewable (e.g., for Word Docs)
        $tempFile = tempnam(sys_get_temp_dir(), 'dec_');
        file_put_contents($tempFile, $decryptedContent);

        // Serve via Google Docs Viewer
        $tempUrl = $base_url . '/' . basename($tempFile); // Adjust this if you're not exposing /tmp
        $google_viewer = "https://docs.google.com/gview?url=$tempUrl&embedded=true";
        header("Location: $google_viewer");
        exit();
    }
} elseif ($action === 'download') {
    // 🔥 NEW: Secure file download function
    $encryptedContent = file_get_contents($file_path);
    $decryptedContent = openssl_decrypt(
        base64_decode($encryptedContent),
        'AES-256-CBC',
        AES_KEY,
        OPENSSL_RAW_DATA,
        AES_IV
    );

    // Ensure decryption is successful
    if ($decryptedContent === false) {
        die("❌ Failed to decrypt the file.");
    }

    // Determine file MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($decryptedContent);

    // Set headers for secure file download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($decryptedContent));

    // Output decrypted file
    echo $decryptedContent;
    exit();
}

die("❌ Invalid action.");
?>
