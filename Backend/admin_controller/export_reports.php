<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php'; // This is correct, assuming the path to connection.php is right.
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

if (!isset($_SESSION['email'])) {
    die("Unauthorized access.");
}

$reportType = isset($_GET['report_type']) ? htmlspecialchars($_GET['report_type']) : 'unknown';
$customFileName = isset($_GET['customTitle']) ? htmlspecialchars($_GET['customTitle']) : 'report';
$fileType = isset($_GET['file_type']) ? strtolower($_GET['file_type']) : 'xlsx';  // Ensure $fileType is always set.
$adminEmail = $_SESSION['email'];

// Password for encryption
$exportPassword = bin2hex(random_bytes(4)); // Example: "d1e2f3a4"

/// Fetch admin name and birthdate
$adminQuery = $conn->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name, birthdate FROM user WHERE email = ?");
$adminQuery->bind_param('s', $adminEmail);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
if ($adminRow = $adminResult->fetch_assoc()) {
    $adminName = $adminRow['full_name'];
    $adminBirthdate = $adminRow['birthdate']; // Fetching birthdate
} else {
    die("Admin not found.");
}
$adminQuery->close();

// Format the birthdate (MM-DD-YYYY)
$formattedBirthdate = date('m-d-Y', strtotime($adminBirthdate));  // e.g., '03-13-1990'

// Extract and trim the last name
$lastName = trim(explode(" ", $adminName)[2]);  // Assuming the full name is in "First Middle Last" format

// Format the password to include last name and admin birthdate
$password = strtoupper($lastName) . "_" . $formattedBirthdate;  // Format: LASTNAME_MM-DD-YYYY

// Validate report types
$validReportTypes = ['enrolled_scholars', 'accepted_volunteers'];
if (!in_array($reportType, $validReportTypes)) die("Invalid report type.");

// Fetch data
if ($reportType === 'enrolled_scholars') {
    $query = "SELECT a.id, a.last_name, a.middle_name, a.first_name, c.name AS course_name, a.email, a.status, a.document 
              FROM applications a 
              INNER JOIN courses c ON a.course_id = c.id 
              WHERE a.status = 'enrolled'";
    $columns = ['ID', 'Last Name', 'Middle Name', 'First Name', 'Course Name', 'Email', 'Status', 'Document'];
} else {
    $query = "SELECT v.id, v.name, v.email, v.application_date, v.status, v.resume_path, v.phone 
              FROM volunteer_application v 
              WHERE v.status = 'approved'";
    $columns = ['ID', 'Name', 'Email', 'Application Date', 'Status', 'Resume Path', 'Phone'];
}

$result = $conn->query($query);
if (!$result) die("Query error: " . $conn->error);

// Store result in array
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = array_values($row);
}

// Generate file
$tempFilePath = "/mnt/data/{$customFileName}." . ($fileType === 'pdf' ? 'pdf' : 'xlsx');

// Ensure that the file path is valid and the directory is writable
if (!is_writable('/mnt/data/')) {
    die("Directory is not writable. Please check the directory permissions.");
}

if ($fileType === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($columns, NULL, 'A1');
    $sheet->fromArray($data, NULL, 'A2');

    $writer = new Xlsx($spreadsheet);

    // Remove Excel encryption (no direct support for .xlsx)
    $writer->save($tempFilePath);

} elseif ($fileType === 'pdf') {
    // Create PDF manually using FPDF + FPDI
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($adminName);
    $pdf->SetTitle($customFileName);
    $pdf->SetSubject($reportType);

    // PDF Password Protection
    $pdf->SetProtection(['print', 'copy'], $exportPassword, null, 0, null);

    $pdf->AddPage();
    $html = "<h3>{$customFileName}</h3><table border='1' cellspacing='0' cellpadding='4'><thead><tr>";
    foreach ($columns as $col) {
        $html .= "<th>$col</th>";
    }
    $html .= "</tr></thead><tbody>";

    foreach ($data as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
            $html .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    $pdf->writeHTML($html);

    // Ensure the file path is correct and the directory is writable
    $pdf->Output($tempFilePath, 'F');
} else {
    die("Invalid file type.");
}

// Send email with attachment
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
    $mail->SMTPAuth = true;
    $mail->Username = 'wgonzales@kaluppa.org'; // Sender email
    $mail->Password = 'ngqt vydl kvjz lgsl';    // Sender email password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('your_email@example.com', 'Report System');
    $mail->addAddress($adminEmail, $adminName);
    $mail->addAttachment($tempFilePath);

    $mail->isHTML(true);
    $mail->Subject = "Exported Report: $customFileName";
    $mail->Body = "Hello $adminName,<br><br>Your requested <strong>$reportType</strong> report has been exported successfully.<br>
    <strong>File Type:</strong> $fileType<br>
    <strong>Password:</strong> <code>$password</code><br><br>
    Please use this password to unlock the file.<br><br>Best regards,<br>System Admin";

    $mail->send();
    echo "Report exported and emailed successfully.";
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}

// Log export
$logQuery = $conn->prepare("INSERT INTO export_logs (admin_email, admin_name, report_type, file_name, file_type) VALUES (?, ?, ?, ?, ?)");
$logQuery->bind_param('sssss', $adminEmail, $adminName, $reportType, $customFileName, $fileType);
$logQuery->execute();
$conn->close();

?>
