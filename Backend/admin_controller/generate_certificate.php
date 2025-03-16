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


// Function to generate certificates for completed courses
function generateCertificates() {
    global $conn;
    
    $query = "SELECT c.id, c.name AS course_name, 
                      a.user_id, 
                      CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) AS user_name
               FROM courses c
               INNER JOIN applications a ON c.id = a.course_id
               WHERE c.status = 'completed' AND a.status = 'enrolled'";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courseId = $row['id'];
            $courseName = $row['course_name'];
            $userId = $row['user_id'];

            $templateQuery = "SELECT * FROM certificate_templates LIMIT 1";
            $templateResult = $conn->query($templateQuery);
            $template = $templateResult->fetch_assoc();

            if ($template) {
                // Simulate certificate creation
                $certificateFile = generateCertificateImage($template['file_path'], $row['user_name'], $courseName);
                
                // Save certificate to database
                $certificateQuery = "INSERT INTO certificates (user_id, course_id, certificate_file) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($certificateQuery);
                $stmt->bind_param("iis", $userId, $courseId, $certificateFile);
                $stmt->execute();
            }
        }
        return true;  // Success
    } else {
        return false;  // No data found
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
        $_SESSION['upload_success'] = "Template uploaded successfully!";
    
    // If there was an error, set error message
    if (isset($_POST['generate_certificates'])) {
        // Handle certificate generation
        $result = generateCertificates();
        
        if ($result) {
            $_SESSION['gen_success'] = "Certificates generated successfully for all completed courses.";
        } else {
            $_SESSION['gen_error'] = "An error occurred while generating certificates.";
        }
        
        header("Location: /Kaluppa/Frontend/admin_dashboard/admin_certificate.php");  // Redirect to refresh and show message
        exit();
    }
}
}
?>
