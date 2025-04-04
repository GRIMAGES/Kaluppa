<?php
session_start();
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if (isset($_GET['application_id'], $_GET['file'], $_GET['action'])) {
    $applicationId = intval($_GET['application_id']);
    $fileName = isset($_GET['file']) ? basename($_GET['file']) : ''; // Sanitize and check file name
    $action = $_GET['action'];

    if (empty($fileName)) {
        echo "<script>alert('File parameter is missing.'); window.history.back();</script>";
        exit();
    }

    // Define the directory where documents are stored
    $documentDir = '../../uploads/documents/';
    $filePath = $documentDir . $fileName;

    if (file_exists($filePath)) {
        if ($action === 'view') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            readfile($filePath);
        } elseif ($action === 'download') {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
        }
        // Log the action
        insertLog($_SESSION['user_id'], ucfirst($action) . ' Document', "Admin $action document $fileName for application ID $applicationId", 'info');
    } else {
        // Log the error
        insertLog($_SESSION['user_id'], 'File Not Found', "Attempted to $action non-existent file $fileName for application ID $applicationId", 'error');
        echo "<script>alert('File not found.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
?>
