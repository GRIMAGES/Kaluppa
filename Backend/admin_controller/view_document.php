<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['email'])) {
    header("Location: ../../Frontend/multiuserlogin.php");
    exit();
}

$file = isset($_GET['file']) ? $_GET['file'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Define document directory
$documentDir = '../Documents/Scholarship/';
$documentPath = realpath($documentDir . basename($file));

// Validate the file
if (!$file || !file_exists($documentPath)) {
    http_response_code(404);
    echo "File not found.";
    exit();
}

// Serve the file based on the action
header('Content-Type: application/pdf'); // Adjust MIME type if necessary
if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
}

readfile($documentPath);
exit();
?>
