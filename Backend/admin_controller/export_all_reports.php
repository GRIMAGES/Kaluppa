<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Set to 5 minutes

require_once '../../Backend/connection.php';
require_once '../../vendor/autoload.php';
require_once '../../vendor/setasign/fpdf/fpdf.php';
require_once '../../vendor/setasign/fpdi/src/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PdfWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    die("Unauthorized access.");
}

$adminEmail = $_SESSION['email'];
$customFileName = isset($_GET['customTitle']) ? htmlspecialchars($_GET['customTitle']) : 'combined_report';
$fileType = isset($_GET['file_type']) ? strtolower($_GET['file_type']) : 'pdf';

// Fetch admin details
$adminQuery = $conn->prepare("SELECT first_name, middle_name, last_name, birthday FROM user WHERE email = ?");
$adminQuery->bind_param('s', $adminEmail);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$adminRow = $adminResult->fetch_assoc();
$adminQuery->close();

if (!$adminRow) {
    die("Admin not found.");
}

// Format birthday as YYYYMMDD for PDF protection
$birthday = new DateTime($adminRow['birthday']);
$exportPassword = $birthday->format('Ymd');

// Create a new PDF document
$pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4');
$pdf->SetCreator('Report System');
$pdf->SetAuthor($adminEmail);
$pdf->SetTitle($customFileName);

// Use the formatted password for PDF protection
$pdf->SetProtection(
    array('print', 'copy'), 
    $exportPassword,
    $exportPassword,
    3,
    null
);

// Function to add a section to the PDF
function addSectionToPDF($pdf, $title, $data, $columns) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Calculate column widths
    $pageWidth = $pdf->GetPageWidth() - 20; // Leave margins
    $colWidth = $pageWidth / count($columns);
    
    // Add headers
    $pdf->SetFont('helvetica', 'B', 10);
    foreach ($columns as $index => $col) {
        $pdf->Cell($colWidth, 7, $col, 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Add data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($data as $row) {
        foreach ($row as $cell) {
            $pdf->Cell($colWidth, 6, $cell, 1, 0, 'L');
        }
        $pdf->Ln();
    }
}

// 1. Export Scholarship Applications
$scholarshipQuery = "SELECT a.id, a.first_name, a.middle_name, a.last_name, c.name AS course_name, 
                           a.email, a.status, a.applied_at 
                    FROM applications a 
                    JOIN courses c ON a.course_id = c.id
                    ORDER BY a.applied_at DESC";
$scholarshipResult = $conn->query($scholarshipQuery);
if (!$scholarshipResult) {
    die("Scholarship query error: " . $conn->error);
}

$scholarshipData = [];
$scholarshipColumns = ['ID', 'First Name', 'Middle Name', 'Last Name', 'Course', 'Email', 'Status', 'Applied Date'];
while ($row = $scholarshipResult->fetch_assoc()) {
    $scholarshipData[] = array_values($row);
}

// 2. Export Alumni Document Requests
$alumniQuery = "SELECT id, full_name, email, document_type, reason, requested_at, status 
                FROM document_requests 
                ORDER BY requested_at DESC";
$alumniResult = $conn->query($alumniQuery);
if (!$alumniResult) {
    die("Alumni query error: " . $conn->error);
}

$alumniData = [];
$alumniColumns = ['ID', 'Full Name', 'Email', 'Document Type', 'Reason', 'Requested Date', 'Status'];
while ($row = $alumniResult->fetch_assoc()) {
    $alumniData[] = array_values($row);
}

// 3. Export Volunteer Applications
$volunteerQuery = "SELECT id, first_name, middle_name, last_name, email, application_date, status 
                  FROM volunteer_application 
                  ORDER BY application_date DESC";
$volunteerResult = $conn->query($volunteerQuery);
if (!$volunteerResult) {
    die("Volunteer query error: " . $conn->error);
}

$volunteerData = [];
$volunteerColumns = ['ID', 'First Name', 'Middle Name', 'Last Name', 'Email', 'Application Date', 'Status'];
while ($row = $volunteerResult->fetch_assoc()) {
    $volunteerData[] = array_values($row);
}

// Add each section to the PDF
addSectionToPDF($pdf, "Scholarship Applications", $scholarshipData, $scholarshipColumns);
addSectionToPDF($pdf, "Alumni Document Requests", $alumniData, $alumniColumns);
addSectionToPDF($pdf, "Volunteer Applications", $volunteerData, $volunteerColumns);

// Add signature page
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Super Admin Signature', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, '_________________________', 0, 1, 'C');
$pdf->Cell(0, 10, 'Signature', 0, 1, 'C');

// Save the PDF to a temporary file
$tempFilePath = sys_get_temp_dir() . '/' . $customFileName . '.pdf';
$pdf->Output($tempFilePath, 'F');

// Send email with the PDF attachment
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'wgonzales@kaluppa.org';
    $mail->Password = 'qfsp ihop mdqg ngoy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('wgonzales@kaluppa.org', 'Report System');
    $mail->addAddress($adminEmail);
    $mail->addAttachment($tempFilePath);

    $mail->isHTML(true);
    $mail->Subject = "Combined Report: $customFileName";
    $mail->Body = "Hello,<br><br>Your requested combined report has been exported successfully.<br>
                   <strong>Password:</strong> Your birthday in YYYYMMDD format (e.g., 19900101)<br>
                   Best regards,<br>System Admin";

    $mail->send();
    $_SESSION['message'] = "Combined report exported and emailed successfully.";
} catch (Exception $e) {
    $_SESSION['message'] = "Email could not be sent. Error: {$mail->ErrorInfo}";
}

// Log the export
$logQuery = $conn->prepare("INSERT INTO export_logs (admin_email, admin_name, report_type, file_name, file_type) VALUES (?, ?, ?, ?, ?)");
$reportType = 'combined_report';
$logQuery->bind_param('sssss', $adminEmail, $adminEmail, $reportType, $customFileName, $fileType);
$logQuery->execute();

// Clean up
unlink($tempFilePath);
$conn->close();

// Redirect back to the reports page
header("Location: /Frontend/admin_dashboard/reports.php");
exit();

// Check if this is a combined report request
if (isset($_POST['report_type']) && $_POST['report_type'] === 'combined_report') {
    // Get admin information from the form
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_position = $_POST['admin_position'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $report_title = $_POST['report_title'] ?? 'Combined Report';

    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $report_title, 0, 1, 'C');
    $pdf->Ln(10);

    // Add admin information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Generated by: ' . $admin_name, 0, 1);
    $pdf->Cell(0, 10, 'Position: ' . $admin_position, 0, 1);
    $pdf->Cell(0, 10, 'Email: ' . $admin_email, 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(10);

    // Add sections
    addSectionToPDF($pdf, 'Scholarship Applications', $scholarship_data, $conn);
    addSectionToPDF($pdf, 'Alumni Document Requests', $alumni_data, $conn);
    addSectionToPDF($pdf, 'Volunteer Applications', $volunteer_data, $conn);

    // Save PDF
    $filename = 'combined_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output('F', $filename);

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with actual email
        $mail->Password = 'your-password'; // Replace with actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'System');
        $mail->addAddress($admin_email, $admin_name);
        $mail->Subject = 'Combined Report - ' . date('Y-m-d');
        $mail->Body = "Dear $admin_name,\n\nPlease find attached the combined report.\n\nBest regards,\nSystem";
        $mail->addAttachment($filename);

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }

    // Log the export
    $stmt = $conn->prepare("INSERT INTO export_logs (admin_id, report_type, export_date) VALUES (?, 'combined_report', NOW())");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();

    // Clean up
    unlink($filename);
    header('Location: ../admin_dashboard/reports.php?success=1');
    exit();
}
?> 