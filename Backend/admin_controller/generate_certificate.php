<?php
require_once '../../Backend/connection.php';
require_once '../../vendor/autoload.php'; // If installed via Composer
require_once '../../vendor/setasign/fpdf/fpdf.php';
require_once '../../vendor/setasign/fpdi/src/autoload.php'; // For FPDI (PDF Template Handling)

use setasign\Fpdi\Fpdi;

if (!isset($_GET['user_ids']) || empty($_GET['user_ids'])) {
    die("No user IDs provided.");
}

$user_ids = explode(",", $_GET['user_ids']);

// Fetch uploaded template
$templateQuery = "SELECT file_path, font_full_name, font_course_name, font_certificate_no, font_date, pos_full_name_x, pos_full_name_y, pos_course_name_x, pos_course_name_y, pos_certificate_no_x, pos_certificate_no_y, pos_date_x, pos_date_y, size_full_name, size_course_name, size_certificate_no, size_date FROM certificate_templates ORDER BY uploaded_at DESC LIMIT 1";
$templateResult = $conn->query($templateQuery);
$templateRow = $templateResult->fetch_assoc();
$templatePath = $templateRow ? $templateRow['file_path'] : '';
$fontFullName = $templateRow ? $templateRow['font_full_name'] : 'Times';
$fontCourseName = $templateRow ? $templateRow['font_course_name'] : 'Times';
$fontCertificateNo = $templateRow ? $templateRow['font_certificate_no'] : 'Times';
$fontDate = $templateRow ? $templateRow['font_date'] : 'Times';
$posFullNameX = $templateRow ? $templateRow['pos_full_name_x'] : 50;
$posFullNameY = $templateRow ? $templateRow['pos_full_name_y'] : 110;
$posCourseNameX = $templateRow ? $templateRow['pos_course_name_x'] : 50;
$posCourseNameY = $templateRow ? $templateRow['pos_course_name_y'] : 135;
$posCertificateNoX = $templateRow ? $templateRow['pos_certificate_no_x'] : 50;
$posCertificateNoY = $templateRow ? $templateRow['pos_certificate_no_y'] : 180;
$posDateX = $templateRow ? $templateRow['pos_date_x'] : 50;
$posDateY = $templateRow ? $templateRow['pos_date_y'] : 205;
$sizeFullName = $templateRow ? $templateRow['size_full_name'] : 36;
$sizeCourseName = $templateRow ? $templateRow['size_course_name'] : 16;
$sizeCertificateNo = $templateRow ? $templateRow['size_certificate_no'] : 12;
$sizeDate = $templateRow ? $templateRow['size_date'] : 12;

if (!$templatePath || !file_exists($templatePath)) {
    die("No valid certificate template found.");
}

// Get file extension
$fileExtension = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));

// Use FPDI if the template is a PDF
$isPdfTemplate = ($fileExtension === 'pdf');
$pdf = new Fpdi();

// **Add Custom Fonts**
$pdf->AddFont('PinyonScript', '', 'PinyonScript-Regular.php'); // Pinyon Script for name
$pdf->AddFont('Times', '', 'times.php'); // Times New Roman
$pdf->AddFont('Times', 'B', 'timesb.php'); // Bold Times New Roman
// $pdf->AddFont('Arial', '', 'arial.php'); // Arial (commented out as it's causing issues)
// $pdf->AddFont('Courier', '', 'courier.php'); // Courier New (commented out as it's causing issues)
// $pdf->AddFont('Georgia', '', 'georgia.php'); // Georgia (commented out as it's causing issues)
// $pdf->AddFont('Verdana', '', 'verdana.php'); // Verdana (commented out as it's causing issues)

foreach ($user_ids as $user_id) {
    // Fetch user details
    $query = "SELECT user.first_name, user.last_name, courses.name AS course_name 
              FROM user 
              INNER JOIN applications ON user.id = applications.user_id 
              INNER JOIN courses ON applications.course_id = courses.id
              WHERE user.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) continue;

    $fullName = strtoupper($user['first_name'] . ' ' . $user['last_name']);
    $courseName = ucfirst($user['course_name']);
    $certificateNo = 'CERT-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);

    // Add a new page using the template
    $pdf->AddPage();

    if ($isPdfTemplate) {
        // Use PDF as template
        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $pdf->useTemplate($templateId, 0, 0, 210, 297);
    } else {
        // Use image as template
        $pdf->Image($templatePath, 0, 0, 210, 297);
    }

    // **Apply Font Enhancements**
    $pdf->SetTextColor(50, 50, 50); // Dark gray text

    // **Use selected font for the Recipient Name**
    $pdf->SetFont($fontFullName, '', $sizeFullName); // Selected font and size for the name
    $pdf->SetXY($posFullNameX, $posFullNameY);
    $pdf->Cell(110, 15, $fullName, 0, 1, 'C');

    // **Apply Course Styling using selected font**
    $pdf->SetFont($fontCourseName, '', $sizeCourseName); // Selected font and size for course name
    $pdf->SetTextColor(0, 102, 204); // Blue color for course name
    $pdf->SetXY($posCourseNameX, $posCourseNameY);
    $pdf->Cell(110, 10, "For successfully completing", 0, 1, 'C');

    // **Course Name (Bold selected font)**
    $pdf->SetFont($fontCourseName, 'B', $sizeCourseName + 2); // Bold selected font and size
    $pdf->SetXY($posCourseNameX, $posCourseNameY + 10); // Adjust position for course name
    $pdf->Cell(110, 10, '"' . $courseName . '"', 0, 1, 'C');

    // **Certificate Number**
    $pdf->SetFont($fontCertificateNo, '', $sizeCertificateNo); // Selected font and size for certificate number
    $pdf->SetTextColor(150, 150, 150); // Light gray for a subtle effect
    $pdf->SetXY($posCertificateNoX, $posCertificateNoY);
    $pdf->Cell(110, 10, "Certificate No: " . $certificateNo, 0, 1, 'C');

    // **Date**
    $pdf->SetFont($fontDate, '', $sizeDate); // Selected font and size for date
    $pdf->SetTextColor(50, 50, 50); // Dark gray text
    $pdf->SetXY($posDateX, $posDateY);
    $pdf->Cell(110, 10, "Date: " . date('Y-m-d'), 0, 1, 'C');
}

// **Output the Enhanced PDF**
$pdf->Output('D', 'generated_certificates.pdf');
?>
