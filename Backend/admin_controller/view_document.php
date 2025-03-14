<?php
session_start();
require_once '../connection.php';

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

// Ensure the file is within the allowed directory
if (strpos(realpath($file_path), $file_dir) !== 0) {
    die("🚫 Access denied.");
}

// Generate a public URL for viewing (Modify this for your server setup)
$base_url = "https://www.kaluppa.online/Kaluppa/Backend/Documents/Scholarship";
$file_url = $base_url . urlencode($file);

// Serve file based on action
if ($action === 'view') {
    $mime_type = mime_content_type($file_path);

    // Directly open PDFs, images, and text files in the browser
    $inline_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'text/plain'];
    
    if (in_array($mime_type, $inline_types)) {
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit();
    } else {
        // Redirect to Google Docs Viewer for unsupported types
        $google_viewer = "https://docs.google.com/gview?url=$file_url&embedded=true";
        header("Location: $google_viewer");
        exit();
    }
} elseif ($action === 'download') {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit();
} else {
    die("❌ Invalid action.");
}
?>
