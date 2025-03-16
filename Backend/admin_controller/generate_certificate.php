<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connection.php';
require_once '../../vendor/autoload.php';
use TCPDF;

session_start();

$type = $_POST['certificate_type'] ?? '';
$admin_name = $_SESSION['email'] ?? 'System';

$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KALUPPA');
$pdf->SetTitle('Certificate');
$pdf->SetMargins(20, 20, 20, true);

// For scholarship certificates (based on completed courses & enrolled students)
if ($type === 'scholarship') {
    $course_id = $_POST['course_id'] ?? '';
    
    if ($course_id) {
        // Get course info
        $course_stmt = $conn->prepare("SELECT name, status FROM courses WHERE id = ?");
        $course_stmt->bind_param("i", $course_id);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_name = $course['name'] ?? 'Scholarship Course';

        if ($course['status'] !== 'completed') {
            echo "<h3>Course is not completed yet. No certificates can be generated.</h3>";
            exit;
        }

        // Get enrolled students from applications table
        $students_stmt = $conn->prepare("
            SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name 
            FROM applications a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.course_id = ?
        ");
        $students_stmt->bind_param("i", $course_id);
        $students_stmt->execute();
        $students_result = $students_stmt->get_result();

        if ($students_result->num_rows === 0) {
            echo "<h3>No enrolled students found for this course.</h3>";
            exit;
        }

        // Generate certificates per student
        while ($row = $students_result->fetch_assoc()) {
            $recipient_name = $row['full_name'];

            $pdf->AddPage();
            $html = '';
            $html .= '<h1 style="text-align:center;">Scholarship Certificate</h1>';
            $html .= '<p style="text-align:center;font-size:16px;">This is to certify that <strong>' . htmlspecialchars($recipient_name) . '</strong> has completed the course:</p>';
            $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($course_name)) . '</h2>';
            $pdf->writeHTML($html, true, false, true, false, '');

            // Save to certificate_logs
            $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("ssss", $recipient_name, $type, $course_name, $admin_name);
            $log_stmt->execute();
        }

        $pdf->Output('scholarship_certificates.pdf', 'I');
        exit;
    }
}

// For volunteer certificates
elseif ($type === 'volunteer') {
    $recipient_name = $_POST['recipient_name'] ?? 'Recipient';
    $work_id = $_POST['work_id'] ?? '';

    if ($work_id) {
        $work_stmt = $conn->prepare("SELECT title FROM works WHERE id = ?");
        $work_stmt->bind_param("i", $work_id);
        $work_stmt->execute();
        $work_result = $work_stmt->get_result();
        $work = $work_result->fetch_assoc();
        $reference_title = $work['title'] ?? 'Volunteer Work';

        $pdf->AddPage();
        $html = '<h1 style="text-align:center;">Volunteer Certificate</h1>';
        $html .= '<p style="text-align:center;font-size:16px;">This is to certify that <strong>' . htmlspecialchars($recipient_name) . '</strong> has participated in the volunteer work titled:</p>';
        $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($reference_title)) . '</h2>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // Log to DB
        $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("ssss", $recipient_name, $type, $reference_title, $admin_name);
        $log_stmt->execute();

        $pdf->Output('volunteer_certificate.pdf', 'I');
        exit;
    }
}

// For request_documents certificates
elseif ($type === 'request_documents') {
    $recipient_name = $_POST['recipient_name'] ?? 'Recipient';
    $reference_title = $_POST['document_details'] ?? 'Requested Documents';

    $pdf->AddPage();
    $html = '<h1 style="text-align:center;">Requested Document Certificate</h1>';
    $html .= '<p style="text-align:center;font-size:16px;">This document certifies that <strong>' . htmlspecialchars($recipient_name) . '</strong> has requested the document:</p>';
    $html .= '<h2 style="text-align:center;">' . strtoupper(htmlspecialchars($reference_title)) . '</h2>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Log to DB
    $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("ssss", $recipient_name, $type, $reference_title, $admin_name);
    $log_stmt->execute();

    $pdf->Output('document_certificate.pdf', 'I');
    exit;
}

// Fallback
else {
    echo "<h3>Invalid certificate type selected.</h3>";
    exit;
}
