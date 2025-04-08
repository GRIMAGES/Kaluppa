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

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$logType = isset($_GET['log_type']) ? $_GET['log_type'] : null;

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
$validReportTypes = ['enrolled_scholars', 'accepted_volunteers', 'admin_logs', 'performance_analytics', 'scholarship_applications', 'volunteer_applications', 'document_requests'];
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
} elseif ($reportType === 'accepted_volunteers') {
    $query = "SELECT v.id, v.name, v.email, v.application_date, v.status 
              FROM volunteer_application v 
              WHERE v.status = 'approved'
              LIMIT 1000"; // Add limit for safety
    $columns = ['ID', 'Name', 'Email', 'Application Date', 'Status'];
} elseif ($reportType === 'admin_logs') {
    $query = "SELECT logs.id, user.email AS user_email, logs.action, logs.description, logs.ip_address, 
                     logs.user_agent, logs.timestamp, logs.log_type 
              FROM logs 
              LEFT JOIN user ON logs.user_id = user.id 
              WHERE 1=1 ";
    if ($startDate) {
        $query .= " AND logs.timestamp >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $query .= " AND logs.timestamp <= '" . $conn->real_escape_string($endDate) . "'";
    }
    if ($logType) {
        $query .= " AND logs.log_type = '" . $conn->real_escape_string($logType) . "'";
    }
    $query .= " ORDER BY logs.timestamp DESC LIMIT 1000";
    $columns = ['ID', 'User Email', 'Action', 'Description', 'IP Address', 'User Agent', 'Timestamp', 'Log Type'];
} elseif ($reportType === 'performance_analytics') {
    $query = "SELECT Metric, Value FROM performance_data";
    $columns = ['Metric', 'Value'];
} elseif ($reportType === 'scholarship_applications') {
    // Fetch total count of scholarship applications
    $query = "SELECT COUNT(*) AS total_applications FROM applications WHERE status = 'enrolled'";
    $result = $conn->query($query);
    if (!$result) {
        die("Query error: " . $conn->error);
    }
    $row = $result->fetch_assoc();
    $totalApplications = $row['total_applications'];
    
    // Prepare data for the report
    $data = [['Total Applications', $totalApplications]];
    $columns = ['Metric', 'Value'];

    // Generate PDF
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
} elseif ($reportType === 'volunteer_applications') {
    // Fetch total count of volunteer applications
    $query = "SELECT COUNT(*) AS total_volunteers FROM volunteer_application WHERE status = 'approved'";
    $result = $conn->query($query);
    if (!$result) {
        die("Query error: " . $conn->error);
    }
    $row = $result->fetch_assoc();
    $totalVolunteers = $row['total_volunteers'];
    
    // Prepare data for the report
    $data = [['Total Volunteers', $totalVolunteers]];
    $columns = ['Metric', 'Value'];

    // Generate PDF
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
} elseif ($reportType === 'document_requests') {
    // Fetch total count of document requests
    $query = "SELECT COUNT(*) AS total_requests FROM document_requests";
    $result = $conn->query($query);
    if (!$result) {
        die("Query error: " . $conn->error);
    }
    $row = $result->fetch_assoc();
    $totalRequests = $row['total_requests'];
    
    // Prepare data for the report
    $data = [['Total Document Requests', $totalRequests]];
    $columns = ['Metric', 'Value'];

    // Generate PDF
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
}

// Adjust query for log types based on report type
if (strpos($reportType, '_logs') !== false) {
    $logType = strtoupper(str_replace('_logs', '', $reportType));
    $query = "SELECT logs.id, user.email AS user_email, logs.action, logs.description, logs.ip_address, 
                     logs.user_agent, logs.timestamp, logs.log_type 
              FROM logs 
              LEFT JOIN user ON logs.user_id = user.id 
              WHERE logs.log_type = '" . $conn->real_escape_string($logType) . "' 
              ORDER BY logs.timestamp DESC LIMIT 1000";
    $columns = ['ID', 'User Email', 'Action', 'Description', 'IP Address', 'User Agent', 'Timestamp', 'Log Type'];
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

// Add export history section
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Export History', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$historyQuery = "SELECT * FROM export_logs WHERE admin_email = '" . $conn->real_escape_string($adminEmail) . "' ORDER BY export_date DESC LIMIT 10";
$historyResult = $conn->query($historyQuery);
while ($historyRow = $historyResult->fetch_assoc()) {
    $pdf->Cell(0, 10, implode(' | ', $historyRow), 0, 1);
}

// Add super admin signature section
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Super Admin Signature', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, '_________________________', 0, 1, 'C');
$pdf->Cell(0, 10, 'Signature', 0, 1, 'C');

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
