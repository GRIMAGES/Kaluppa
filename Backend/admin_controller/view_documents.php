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
$documentDir = '/../Documents/Volunteer/';
$documentPath = realpath($documentDir . basename($file));

// Validate the file
if (!$file || !file_exists($documentPath)) {
    http_response_code(404);
    echo "File not found.";
    exit();
}

// Serve the file based on the action
if ($action === 'download') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    readfile($documentPath);
} elseif ($action === 'view') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
    readfile($documentPath);
    exit();
} else {
    http_response_code(400);
    echo "Invalid action.";
    exit();
}
