<?php
session_start(); // Start session to store error/success messages
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection and vendor autoload
require_once '../connection.php';
require_once '../../vendor/autoload.php';

// Function to generate certificates for completed courses
function generateCertificates() {
    global $conn;
    
    // Query to fetch completed courses and corresponding users
    $query = "SELECT c.id, c.name AS course_name, 
                      a.user_id, 
                      CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) AS user_name
               FROM courses c
               INNER JOIN applications a ON c.id = a.course_id
               WHERE c.status = 'completed' AND a.status = 'enrolled'";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // Loop through each row and generate certificates
        while ($row = $result->fetch_assoc()) {
            $courseId = $row['id'];
            $courseName = $row['course_name'];
            $userId = $row['user_id'];
            $userName = $row['user_name'];

            $templateQuery = "SELECT * FROM certificate_templates LIMIT 1";  // Get the latest template
            $templateResult = $conn->query($templateQuery);
            $template = $templateResult->fetch_assoc();

            if ($template) {
                // Generate the certificate and directly output it to the browser
                $certificateFile = generateCertificateWithTemplate($template['file_path'], $userName, $courseName);
                
                // If certificate generation is successful, set success message
                if ($certificateFile) {
                    $_SESSION['gen_success'] = "Certificates generated successfully for all completed courses.";
                } else {
                    $_SESSION['gen_error'] = "Failed to generate the certificate image for user: $userName";
                }
            }
        }
        return true;  // Success
    } else {
        $_SESSION['gen_error'] = "No completed courses found for certificate generation.";
        return false;  // No data found
    }
}

// Helper function to generate certificate with the uploaded template
function generateCertificateWithTemplate($templatePath, $userName, $courseName) {
    // Extract the base filename from the database file path
    $templateFileName = basename($templatePath);  // This will extract the file name (e.g., template.pdf)

    // Assuming the templates are stored in a directory relative to this script
    $templateDir = __DIR__ . '/templates/';  // Adjust this path to where your templates are stored

    // Combine the template directory and file name to get the full path
    $fullTemplatePath = $templateDir . $templateFileName;

    // Debugging: Log the full path of the template
    error_log("Looking for template at: " . $fullTemplatePath);

    // Check if the template file exists
    if (!file_exists($fullTemplatePath)) {
        error_log("Template file does not exist: " . $fullTemplatePath);
        return false; // Template file does not exist
    }

    // Create a new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Set font and color (adjust the font and size as needed)
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 0); // Black text

    // Define positions for the text (adjust these based on the template layout)
    $pdf->SetXY(50, 60);  // Position for the user's name
    $pdf->Cell(0, 10, $userName, 0, 1, 'C'); // Center the user's name

    $pdf->SetXY(50, 100); // Position for the course name
    $pdf->Cell(0, 10, $courseName, 0, 1, 'C'); // Center the course name

    // Output the PDF directly to the browser (this will trigger a download prompt)
    $pdf->Output('D', 'certificate_' . uniqid() . '.pdf');
    return true;  // Return true if the PDF is generated successfully
}

// Handle certificate generation POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_certificates'])) {
    $result = generateCertificates();
    
    if ($result) {
        $_SESSION['gen_success'] = "Certificates generated successfully for all completed courses.";
    } else {
        $_SESSION['gen_error'] = "An error occurred while generating certificates.";
    }
}

// Redirect back to the previous page after processing
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
