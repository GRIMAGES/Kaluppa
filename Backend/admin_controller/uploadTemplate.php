<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// uploadTemplate.php

// Function to handle file uploads (template upload)
function uploadTemplate($templateName, $file) {
    $allowedTypes = ['image/png', 'image/jpeg', 'application/pdf'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return "Invalid file type. Please upload an image or PDF.";
    }

    // Define the correct upload directory path
    $uploadDir = __DIR__ . '/templates/';  // Corrected to your existing 'templates' folder
    
    // Save the file with its original name (basename())
    $filePath = $uploadDir . basename($file['name']);
    
    // Check if the file already exists, and if so, append a number to avoid overwriting
    if (file_exists($filePath)) {
        $filePath = $uploadDir . time() . "_" . basename($file['name']);
    }

    // Try moving the file to the templates directory
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Insert template into the database
        global $conn;
        $query = "INSERT INTO certificate_templates (template_name, file_path) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $templateName, $filePath);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return "Template uploaded successfully.";
        } else {
            return "Failed to save template to the database.";
        }
    } else {
        return "Failed to upload the file.";
    }
}
?>
