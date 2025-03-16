<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection and vendor autoload (if you use any libraries like FPDF or GD)
require_once '../connection.php';
require_once '../../vendor/autoload.php';

// Function to handle file uploads (template upload)
function uploadTemplate($templateName, $file) {
    $allowedTypes = ['image/png', 'image/jpeg', 'application/pdf'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return "Invalid file type. Please upload an image or PDF.";
    }

    // Define the upload directory
    $uploadDir = 'uploads/templates/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Save the file with a unique name
    $filePath = $uploadDir . uniqid() . "_" . basename($file['name']);
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

// Function to generate certificates for completed courses
function generateCertificates() {
    global $conn;
    
    // Get all completed courses
    $query = "SELECT c.id, c.course_name, u.name FROM courses c
              INNER JOIN users u ON c.user_id = u.id
              WHERE c.completion_status = 'completed'";

    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $courseId = $row['id'];
        $courseName = $row['course_name'];
        $userName = $row['name'];

        // Fetch a template (for now, using the first template in the DB)
        $templateQuery = "SELECT * FROM certificate_templates LIMIT 1";
        $templateResult = $conn->query($templateQuery);
        $template = $templateResult->fetch_assoc();

        if ($template) {
            // Generate the certificate (here you would integrate actual image/PDF generation)
            $templatePath = $template['file_path'];

            // For this example, we’ll simulate certificate generation
            $certificateFile = generateCertificateImage($templatePath, $userName, $courseName);

            // Save the generated certificate in the database (optional)
            $certificateQuery = "INSERT INTO certificates (user_id, course_id, certificate_file) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($certificateQuery);
            $stmt->bind_param("iis", $row['user_id'], $courseId, $certificateFile);
            $stmt->execute();
        }
    }
}

// Helper function to simulate generating a certificate image (you can integrate FPDF or GD here)
function generateCertificateImage($templatePath, $userName, $courseName) {
    // This function simulates certificate creation.
    // Replace this with actual image/PDF generation logic.
    $certificateFile = 'certificates/' . uniqid() . '.png';
    return $certificateFile;
}

// Handle actions (Backend processing)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_template'])) {
        // Handle template upload
        $templateName = $_POST['template_name'];
        $file = $_FILES['template_file'];
        $uploadMessage = uploadTemplate($templateName, $file);
    }

    if (isset($_POST['generate_certificates'])) {
        // Handle certificate generation
        generateCertificates();
        $generateMessage = "Certificates generated for all completed courses.";
    }
}
?>
