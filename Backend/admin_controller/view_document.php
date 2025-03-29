<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['application_id'], $_GET['file'], $_GET['action'])) {
    $application_id = $_GET['application_id'];
    $file_name = $_GET['file'];
    $action = $_GET['action'];

    // Fetch the document path from the database
    $stmt = $conn->prepare("SELECT documents FROM applications WHERE id = ?");
    $stmt->bind_param("s", $application_id);
    $stmt->execute();
    $stmt->bind_result($documents_json);
    if ($stmt->fetch()) {
        $stmt->close();
        $documents = json_decode($documents_json, true);
        foreach ($documents as $document) {
            if ($document['file_name'] === $file_name) {
                $file_path = '../../uploads/' . $document['file_path']; // Adjust the path as needed
                if (file_exists($file_path)) {
                    if ($action === 'view') {
                        header('Content-Type: application/pdf'); // Adjust MIME type if not PDF
                        readfile($file_path);
                        exit();
                    } elseif ($action === 'download') {
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file_path));
                        readfile($file_path);
                        exit();
                    }
                } else {
                    die('File not found.');
                }
            }
        }
    } else {
        $stmt->close();
        die('Invalid application ID or file.');
    }
} else {
    die('Invalid request.');
}
?>
