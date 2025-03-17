<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Set to 5 minutes

require_once '../../Backend/connection.php'; // Path to connection.php
require_once '../../vendor/autoload.php';  // PHPMailer, PhpSpreadsheet, TCPDF should be in the Backend vendor folder.
require_once '../../vendor/setasign/fpdf/fpdf.php'; // Correct path to FPDF
require_once '../../vendor/setasign/fpdi/src/autoload.php'; // Correct path to FPDI

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PdfWriter;  // Correct import for Mpdf
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    die("Unauthorized access.");
}

$reportType = isset($_GET['report_type']) ? htmlspecialchars($_GET['report_type']) : 'unknown';
$customFileName = isset($_GET['customTitle']) ? htmlspecialchars($_GET['customTitle']) : 'report';
$fileType = isset($_GET['file_type']) ? strtolower($_GET['file_type']) : 'pdf';
$adminEmail = $_SESSION['email'];

// Fetch admin details and format birthday properly
$adminQuery = $conn->prepare("SELECT first_name, middle_name, last_name, birthday FROM user WHERE email = ?");
$adminQuery->bind_param('s', $adminEmail);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$adminRow = $adminResult->fetch_assoc();
$adminQuery->close();

if (!$adminRow) {
    die("Admin not found.");
}

// Format birthday as YYYYMMDD, ensuring proper date handling
$birthday = new DateTime($adminRow['birthday']);
$exportPassword = $birthday->format('Ymd');

// Validate report type
$validReportTypes = ['enrolled_scholars', 'accepted_volunteers'];
if (!in_array($reportType, $validReportTypes)) {
    die("Invalid report type.");
}

// Optimize queries to fetch only necessary data
if ($reportType === 'enrolled_scholars') {
    $query = "SELECT a.id, a.last_name, a.middle_name, a.first_name, c.name AS course_name 
              FROM applications a 
              INNER JOIN courses c ON a.course_id = c.id 
              WHERE a.status = 'enrolled'
              LIMIT 1000"; // Add limit for safety
    $columns = ['ID', 'Last Name', 'Middle Name', 'First Name', 'Course Name'];
} else {
    $query = "SELECT v.id, v.name, v.email, v.application_date, v.status 
              FROM volunteer_application v 
              WHERE v.status = 'approved'
              LIMIT 1000"; // Add limit for safety
    $columns = ['ID', 'Name', 'Email', 'Application Date', 'Status'];
}

$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = array_values($row);
}

// Use system temp directory instead of /mnt/data/
$tempFilePath = sys_get_temp_dir() . '/' . $customFileName . '.pdf';

// Generate PDF more efficiently
$pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
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

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);

// Calculate column widths
$pageWidth = $pdf->GetPageWidth() - 20; // Leave margins
$colWidth = $pageWidth / count($columns);

// Add headers
foreach ($columns as $index => $col) {
    $pdf->Cell($colWidth, 10, $col, 1, 0, 'C');
}
$pdf->Ln();

// Add data
$pdf->SetFont('helvetica', '', 10);
foreach ($data as $row) {
    foreach ($row as $cell) {
        $pdf->Cell($colWidth, 10, $cell, 1, 0, 'L');
    }
    $pdf->Ln();
}

$pdf->Output($tempFilePath, 'F');

// Send email
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
    $mail->Subject = "Exported Report: $customFileName";
    $mail->Body = "Hello,<br><br>Your requested report has been exported successfully.<br>
                   <strong>Password:</strong> Your birthday in YYYYMMDD format (e.g., 19900101)<br>
                   <strong>Your Password:</strong> {$exportPassword}<br><br>
                   Best regards,<br>System Admin";

    $mail->send();
    $_SESSION['message'] = "Report exported and emailed successfully.";
} catch (Exception $e) {
    $_SESSION['message'] = "Email could not be sent. Error: {$mail->ErrorInfo}";
}

// Log export
$logQuery = $conn->prepare("INSERT INTO export_logs (admin_email, admin_name, report_type, file_name, file_type) VALUES (?, ?, ?, ?, ?)");
$logQuery->bind_param('sssss', $adminEmail, $adminEmail, $reportType, $customFileName, $fileType);
$logQuery->execute();

// Clean up
unlink($tempFilePath);
$conn->close();

header("Location: {$_SERVER['HTTP_REFERER']}");
exit();
?>
