<?php
require_once '../../Backend/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['template'])) {
    $uploadDir = '../../Backend_admin_dashboard/certificate_templates/';
    $fileName = basename($_FILES['template']['name']);
    $targetFilePath = $uploadDir . $fileName;
    
    // Allow only PDF uploads
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    if ($fileType != 'pdf') {
        die("Only PDF files are allowed.");
    }

    if (move_uploaded_file($_FILES['template']['tmp_name'], $targetFilePath)) {
        // Store in DB
        $query = "INSERT INTO certificate_templates (template_path) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $targetFilePath);
        $stmt->execute();
        $stmt->close();

        echo "Template uploaded successfully.";
    } else {
        echo "Error uploading file.";
    }
}
?>
