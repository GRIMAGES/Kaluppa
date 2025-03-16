<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connection.php';
require_once '../../vendor/autoload.php';

use setasign\Fpdi\TcpdfFpdi;

session_start();

$type = $_POST['certificate_type'] ?? '';
$admin_name = $_SESSION['email'] ?? 'System';

// Initialize PDF
$pdf = new TcpdfFpdi('L', 'mm', 'A4');
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetFont('Helvetica', '', 16);

// Define your custom certificate template path
$templatePath = '../../cert_templates/certificate_template.pdf'; // Make sure this exists

if (!file_exists($templatePath)) {
    echo "<h3>Certificate template not found at: {$templatePath}</h3>";
    exit;
}

// --- Scholarship Certificate ---
if ($type === 'scholarship') {
    $course_id = $_POST['course_id'] ?? '';

    if ($course_id) {
        $stmt = $conn->prepare("SELECT name, status FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result->fetch_assoc();
        $course_name = $course['name'] ?? 'Scholarship Course';

        if ($course['status'] !== 'completed') {
            echo "<h3>Course is not completed yet. No certificates can be generated.</h3>";
            exit;
        }

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

        while ($row = $students_result->fetch_assoc()) {
            $recipient_name = $row['full_name'];

            // Import and use template
            $pdf->AddPage();
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, 297);

            // Write Recipient Name
            $pdf->SetXY(20, 100);
            $pdf->SetFont('Helvetica', '', 20);
            $pdf->Cell(0, 10, $recipient_name, 0, 1, 'C');

            // Write Course Name
            $pdf->SetXY(20, 115);
            $pdf->SetFont('Helvetica', '', 16);
            $pdf->Cell(0, 10, strtoupper($course_name), 0, 1, 'C');

            // Log generation
            $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("ssss", $recipient_name, $type, $course_name, $admin_name);
            $log_stmt->execute();
        }

        $pdf->Output('scholarship_certificates.pdf', 'I');
        exit;
    }
}

// --- Volunteer Certificate ---
elseif ($type === 'volunteer') {
    $recipient_name = $_POST['recipient_name'] ?? 'Recipient';
    $work_id = $_POST['work_id'] ?? '';

    if ($work_id) {
        $stmt = $conn->prepare("SELECT title FROM works WHERE id = ?");
        $stmt->bind_param("i", $work_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $work = $result->fetch_assoc();
        $reference_title = $work['title'] ?? 'Volunteer Work';

        $pdf->AddPage();
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx, 0, 0, 297);

        $pdf->SetXY(20, 100);
        $pdf->SetFont('Helvetica', '', 20);
        $pdf->Cell(0, 10, $recipient_name, 0, 1, 'C');

        $pdf->SetXY(20, 115);
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->Cell(0, 10, strtoupper($reference_title), 0, 1, 'C');

        $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("ssss", $recipient_name, $type, $reference_title, $admin_name);
        $log_stmt->execute();

        $pdf->Output('volunteer_certificate.pdf', 'I');
        exit;
    }
}

// --- Request Document Certificate ---
elseif ($type === 'request_documents') {
    $recipient_name = $_POST['recipient_name'] ?? 'Recipient';
    $reference_title = $_POST['document_details'] ?? 'Requested Document';

    $pdf->AddPage();
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 297);

    $pdf->SetXY(20, 100);
    $pdf->SetFont('Helvetica', '', 20);
    $pdf->Cell(0, 10, $recipient_name, 0, 1, 'C');

    $pdf->SetXY(20, 115);
    $pdf->SetFont('Helvetica', '', 16);
    $pdf->Cell(0, 10, strtoupper($reference_title), 0, 1, 'C');

    $log_stmt = $conn->prepare("INSERT INTO certificate_logs (recipient_name, certificate_type, reference_title, generated_by) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("ssss", $recipient_name, $type, $reference_title, $admin_name);
    $log_stmt->execute();

    $pdf->Output('request_document_certificate.pdf', 'I');
    exit;
}

// --- Invalid Request Fallback ---
else {
    echo "<h3>Invalid certificate type selected.</h3>";
    exit;
}
?>
