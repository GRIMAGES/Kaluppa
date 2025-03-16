<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connection.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // TCPDF autoload via Composer

use TCPDF;

session_start();

$type = $_POST['certificate_type'] ?? '';
$recipient = $_POST['recipient_name'] ?? 'Recipient Name';

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document info
$pdf->SetCreator('Kaluppa System');
$pdf->SetAuthor('Kaluppa Foundation');
$pdf->SetTitle('Certificate of Recognition');
$pdf->SetSubject('Certificate');
$pdf->SetKeywords('Certificate, Scholarship, Volunteer, Document');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins and add page
$pdf->SetMargins(20, 20, 20, true);
$pdf->AddPage();

// Styles
$titleStyle = 'font-size: 28px; font-weight: bold; text-align: center;';
$subtitleStyle = 'font-size: 18px; text-align: center;';
$contentStyle = 'font-size: 16px; text-align: center; margin-top: 20px;';
$highlightStyle = 'font-size: 20px; font-weight: bold; color: #000066; text-align: center;';

// Build certificate content
$html = "<h1 style='$titleStyle'>CERTIFICATE OF RECOGNITION</h1>";
$html .= "<br><h2 style='$subtitleStyle'>Presented to</h2>";
$html .= "<h2 style='$highlightStyle'>" . strtoupper($recipient) . "</h2>";

switch ($type) {
    case 'scholarship':
        $course_id = $_POST['course_id'] ?? '';
        if ($course_id) {
            $query = $conn->prepare("SELECT name FROM courses WHERE id = ?");
            $query->bind_param("i", $course_id);
            $query->execute();
            $result = $query->get_result();
            $course = $result->fetch_assoc();

            $html .= "<div style='$contentStyle'>In recognition of successfully completing the scholarship course:</div>";
            $html .= "<div style='$highlightStyle'>" . strtoupper($course['name']) . "</div>";
        }
        break;

    case 'volunteer':
        $work_id = $_POST['work_id'] ?? '';
        if ($work_id) {
            $query = $conn->prepare("SELECT title FROM works WHERE id = ?");
            $query->bind_param("i", $work_id);
            $query->execute();
            $result = $query->get_result();
            $work = $result->fetch_assoc();

            $html .= "<div style='$contentStyle'>In appreciation for your voluntary service in the project:</div>";
            $html .= "<div style='$highlightStyle'>" . strtoupper($work['title']) . "</div>";
        }
        break;

    case 'request_documents':
        $details = $_POST['document_details'] ?? '';
        $html .= "<div style='$contentStyle'>This certificate acknowledges the request for:</div>";
        $html .= "<div style='$highlightStyle'>" . strtoupper($details) . "</div>";
        break;

    default:
        $html .= "<div style='$contentStyle'>Invalid certificate type selected.</div>";
        break;
}

// Add date issued
$html .= "<br><div style='$subtitleStyle'>Date Issued: " . date('F j, Y') . "</div>";

// Render to PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('certificate.pdf', 'I');
exit;
