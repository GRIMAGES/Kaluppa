<?php
require_once '../connection.php';
require('../lib/fpdf.php'); // Adjust path based on where you put fpdf.php
session_start();

$type = $_POST['certificate_type'] ?? '';
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 24);

switch ($type) {
    case 'scholarship':
        $course_id = $_POST['course_id'] ?? '';
        if ($course_id) {
            $query = $conn->prepare("SELECT name FROM courses WHERE id = ?");
            $query->bind_param("i", $course_id);
            $query->execute();
            $result = $query->get_result();
            $course = $result->fetch_assoc();

            $pdf->Cell(0, 20, "Scholarship Certificate", 0, 1, 'C');
            $pdf->SetFont('Arial', '', 16);
            $pdf->Cell(0, 10, "This is to certify that the student has completed the course:", 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->Cell(0, 10, strtoupper($course['name']), 0, 1, 'C');
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

            $pdf->Cell(0, 20, "Volunteer Certificate", 0, 1, 'C');
            $pdf->SetFont('Arial', '', 16);
            $pdf->Cell(0, 10, "This is to certify participation in the volunteer work titled:", 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->Cell(0, 10, strtoupper($work['title']), 0, 1, 'C');
        }
        break;

    case 'request_documents':
        $details = $_POST['document_details'] ?? '';
        $pdf->Cell(0, 20, "Requested Document Certificate", 0, 1, 'C');
        $pdf->SetFont('Arial', '', 16);
        $pdf->Cell(0, 10, "This document certifies the request for:", 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, strtoupper($details), 0, 1, 'C');
        break;

    default:
        $pdf->Cell(0, 20, "Invalid certificate type selected.", 0, 1, 'C');
        break;
}

$pdf->Output();
exit;
