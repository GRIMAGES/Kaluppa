<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../lib/fpdf.php';  // Make sure path is correct
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['recipientName'] ?? '';
    $course = $_POST['courseName'] ?? '';
    $awardDate = $_POST['awardDate'] ?? '';
    $signedBy = $_POST['signedBy'] ?? '';
    $templatePath = $_POST['template'] ?? '';

    if (!$templatePath || !file_exists("../../Frontend/admin_dashboard/" . $templatePath)) {
        die("Invalid or missing template.");
    }

    $fullTemplatePath = "../../Frontend/admin_dashboard/" . $templatePath;

    // === PDF Generation ===
    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape A4
    $pdf->AddPage();

    // Add background template
    $pdf->Image($fullTemplatePath, 0, 0, 297, 210); // full A4

    // Set font
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(0, 0, 0);

    // Recipient Name
    $pdf->SetXY(30, 80);
    $pdf->Cell(237, 10, $name, 0, 1, 'C');

    // Course Name
    $pdf->SetFont('Arial', '', 20);
    $pdf->SetXY(30, 95);
    $pdf->Cell(237, 10, "for completing: " . $course, 0, 1, 'C');

    // Date
    $pdf->SetXY(30, 110);
    $pdf->Cell(237, 10, "Date Awarded: " . $awardDate, 0, 1, 'C');

    // Signed By
    $pdf->SetXY(190, 160);
    $pdf->SetFont('Arial', 'I', 16);
    $pdf->Cell(80, 10, "Signed by: $signedBy", 0, 1, 'C');

    // === Steganography (Optional Hidden Text Embedding) ===
    $hideData = "Recipient: $name | Course: $course | Date: $awardDate | Signed by: $signedBy";
    $pdf->SetFont('Arial', '', 6); // Tiny invisible text
    $pdf->SetTextColor(255, 255, 255); // White on white
    $pdf->SetXY(5, 205);
    $pdf->Cell(0, 5, $hideData, 0, 0, 'L');

    // Output PDF
    $fileName = "certificate_" . str_replace(" ", "_", $name) . ".pdf";
    $pdf->Output('D', $fileName); // Force download
    exit();
}
?>
