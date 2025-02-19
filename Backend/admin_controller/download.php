<?php
require_once '../../Backend/connection.php';

// Ensure a file parameter is provided
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $fileName = $_GET['file'];
    $documentDirectory = '/xampp/php/KALUPPA/'; // Path to your document directory
    $filePath = $documentDirectory . $fileName;

    // Check if the file exists
    if (file_exists($filePath)) {
        // Send headers for downloading the file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Read the file and output it to the browser
        readfile($filePath);
        exit();
    } else {
        echo "File not found.";
        exit();
    }
} else {
    echo "Invalid file request.";
    exit();
}
?>
