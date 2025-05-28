<?php
// Start output buffering to prevent any output before headers
ob_start();

session_start();
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';
require_once '../../Backend/aes_key.php'; // Include the file with encryption constants

if (!isset($_SESSION['email'])) {
    header("Location: ../../Frontend/multiuserlogin.php");
    exit();
}

if (isset($_GET['file'], $_GET['action'])) {
    $fileName = isset($_GET['file']) ? basename($_GET['file']) : ''; // Sanitize and check file name
    $action = $_GET['action'];

    if (empty($fileName)) {
        echo "<script>alert('File parameter is missing.'); window.history.back();</script>";
        exit();
    }

    // Define the directory where volunteer documents are stored
    $documentDir = realpath('../../Backend/Documents/Volunteer/') . DIRECTORY_SEPARATOR;
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
        
        // Read the encrypted file content
        $encryptedContent = file_get_contents($filePath);
        
        // Decrypt the content
        $decryptedContent = openssl_decrypt(
            $encryptedContent,
            'AES-256-CBC',
            AES_KEY,
            OPENSSL_RAW_DATA,
            AES_IV
        );
        
        if ($decryptedContent === false) {
            // If decryption fails, try to use the file directly (in case it's not encrypted)
            $decryptedContent = $encryptedContent;
            error_log("Decryption failed for file: $fileName. Using raw content instead.");
        }
        
        // Set headers for file download/view
        if ($action === 'view') {
            // For viewing, set inline disposition
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Content-Length: ' . strlen($decryptedContent));
            
            // Output the decrypted content
            echo $decryptedContent;
        } elseif ($action === 'download') {
            // For downloading, set attachment disposition
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Content-Length: ' . strlen($decryptedContent));
            
            // Output the decrypted content
            echo $decryptedContent;
        }
        
        // Restore error reporting
        error_reporting($errorReporting);
        
        // Log the action
        insertLog($_SESSION['user_id'], ucfirst($action) . ' Document', "Admin $action volunteer document $fileName", 'info');
        
        // End output buffering and flush
        ob_end_flush();
        exit(); // Make sure to exit after sending the file
    } else {
        // Log the error
        insertLog($_SESSION['user_id'], 'File Not Found', "Attempted to $action non-existent volunteer file $fileName", 'error');
        echo "<script>alert('File not found.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
?>
