<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connection.php';
require_once '../../vendor/autoload.php';

use TCPDF;

session_start();

$type = $_POST['certificate_type'] ?? '';
$recipient_name = $_POST['recipient_name'] ?? 'Recipient';
$admin_name = $_SESSION['email'] ?? 'System';

$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KALUPPA');
$pdf->SetTitle('Certificate');
$pdf->SetMargins(20, 20, 20, true);
$pdf->AddPage();

$html = '';
$reference_title = '';

switch ($type) {
    case 'scholarship':
        $course_id = $_POST['course_id'] ?? '';
        if ($course_id) {
            $query = $conn->prepare("SELECT name FROM courses WHERE id = ?");
            $query->bind_param("i", $course_id);
            $query->execute();
            $result = $query->get_result();
            $course = $result->fetch_assoc();
            $reference_title = $course['name'] ?? 'Scholarship Course';

            $html .= '<h1 style="text-align:center;">Scholarship Certificate</h1>';
            $html .= '<p style="text-align:center;font-size:16px;">This is to certify that <strong>' . htmlspecialchars($recipient_name) . '</strong> has completed the course:</p>';
            $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($reference_title)) . '</h2>';
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
            $reference_title = $work['title'] ?? 'Volunteer Work';

            $html .= '<h1 style="text-align:center;">Volunteer Certificate</h1>';
            $html .= '<p style="text-align:center;font-size:16px;">This is to certify that <strong>' . htmlspecialchars($recipient_name) . '</strong> has participated in the volunteer work titled:</p>';
            $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($reference_title)) . '</h2>';
        }
        break;

    case 'request_documents':
        $reference_title = $_POST['document_details'] ?? 'Requested Documents';

        $html .= '<h1 style="text-align:center;">Requested Document Certificate</h1>';
        $html .= '<p style="text-align:center;font-size:16px;">This document certifies that <strong>' . htmlspecialchars($recipient_name) . '</strong> has requested the document:</p>';
        $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($reference_title)) . '</h2>';
        break;

    default:
        $html .= '<h1 style="text-align:center;">Invalid Certificate Type</h1>';
        break;
}

// Output the certificate HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Save certificate log to database
if (!empty($reference_title) && !empty($recipient_name)) {
    $stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $recipient_name, $type, $reference_title, $admin_name);
    $stmt->execute();
}

// Output PDF to browser
$pdf->Output('certificate.pdf', 'I');
exit;
