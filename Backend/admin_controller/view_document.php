<?php
// Start output buffering to prevent any output before headers
ob_start();

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
    $documentDir = realpath('../../Backend/Documents/Scholarship/') . DIRECTORY_SEPARATOR;
    $filePath = $documentDir . $fileName;

    // Check if the file exists and is within the allowed directory
    if (file_exists($filePath) && strpos(realpath($filePath), $documentDir) === 0) {
        // Get file extension to determine content type
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Set appropriate content type based on file extension
        $contentType = 'application/octet-stream'; // Default
        switch ($fileExtension) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'doc':
            case 'docx':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            case 'xls':
            case 'xlsx':
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'jpg':
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            case 'gif':
                $contentType = 'image/gif';
                break;
            case 'txt':
                $contentType = 'text/plain';
                break;
        }
        
        // Clear any previous output
        ob_clean();
        
        // Disable error reporting for this operation
        $errorReporting = error_reporting(0);
        
        // Set headers for file download/view
        if ($action === 'view') {
            // For viewing, set inline disposition
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            // For PDFs and images, we can display them directly
            if (in_array($fileExtension, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
                readfile($filePath);
            } else {
                // For other file types, we'll force download instead
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                readfile($filePath);
            }
        } elseif ($action === 'download') {
            // For downloading, set attachment disposition
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            // Use a more reliable method to read and output the file
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                }
                fclose($handle);
            } else {
                // If we can't open the file, try readfile as a fallback
                readfile($filePath);
            }
        }
        
        // Restore error reporting
        error_reporting($errorReporting);
        
        // Log the action
        insertLog($_SESSION['user_id'], ucfirst($action) . ' Document', "Admin $action document $fileName for application ID $applicationId", 'info');
        
        // End output buffering and flush
        ob_end_flush();
        exit(); // Make sure to exit after sending the file
    } else {
        // Log the error
        insertLog($_SESSION['user_id'], 'File Not Found', "Attempted to $action non-existent file $fileName for application ID $applicationId", 'error');
        echo "<script>alert('File not found.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
?>
