<?php
require_once '../../Backend/connection.php';
require_once '../../Frontend/vendor/autoload.php'; // For PDF generation and PHPMailer autoload

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

if (!isset($_SESSION['email'])) {
    die("Unauthorized access.");
}

// Collect data from the request
$reportType = isset($_GET['report_type']) ? htmlspecialchars($_GET['report_type']) : 'unknown';
$customFileName = isset($_GET['customTitle']) ? htmlspecialchars($_GET['customTitle']) : 'report';
$adminEmail = $_SESSION['email'];

// Validate report type
$validReportTypes = ['accepted_scholars', 'accepted_volunteers'];
if (!in_array($reportType, $validReportTypes)) {
    die("Invalid report type.");
}

// Fetch the admin's full name from the user table
$adminQuery = $conn->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM user WHERE email = ?");
$adminQuery->bind_param('s', $adminEmail);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$adminRow = $adminResult->fetch_assoc();
$adminName = $adminRow['full_name'];
$adminQuery->close();

// Determine file type based on the selected type
$fileType = strtolower($_GET['file_type']);

// Set headers for download
switch ($fileType) {
    case 'csv':
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=$customFileName.csv");
        break;
    case 'excel':
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=$customFileName.xls");
        break;
    case 'pdf':
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=$customFileName.pdf");
        break;
    default:
        die("Unsupported file type.");
}
header('Cache-Control: max-age=0');

// Fetch data based on the report type
$query = '';
$columns = [];
if ($reportType === 'accepted_scholars') {
    $query = "
        SELECT 
            a.full_name, 
            c.name AS course_name, 
            a.address, 
            a.email, 
            a.status, 
            a.applied_at, 
            a.document 
        FROM 
            applications a 
        INNER JOIN 
            courses c 
        ON 
            a.course_id = c.id 
        WHERE 
            a.status = 'approved'
    ";
    $columns = ['Full Name', 'Course Name', 'Address', 'Email', 'Status', 'Applied At', 'Document'];
} elseif ($reportType === 'accepted_volunteers') {
    $query = "
        SELECT 
            v.id,
            v.name, 
            v.email, 
            v.application_date, 
            v.status, 
            v.resume_path, 
            v.phone 
        FROM 
            volunteer_application v 
        WHERE 
            v.status = 'approved'
    ";
    $columns = ['ID', 'Name', 'Email', 'Application Date', 'Status', 'Resume Path', 'Phone'];
} else {
    die("Invalid report type.");
}

$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

// Generate output for the selected file type
if ($fileType === 'csv') {
    $output = fopen('php://output', 'w');
    fputcsv($output, $columns);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
} elseif ($fileType === 'excel') {
    echo "<table border='1'>";
    
    // Conditional headers for Excel
    if ($reportType === 'accepted_scholars') {
        echo "<tr><th>Full Name</th><th>Course Name</th><th>Address</th><th>Email</th><th>Status</th><th>Applied At</th><th>Document</th></tr>";
    } elseif ($reportType === 'accepted_volunteers') {
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Application Date</th><th>Status</th><th>Resume Path</th><th>Phone</th></tr>";
    }
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} elseif ($fileType === 'pdf') {
    $dompdf = new Dompdf();
    ob_start();
    echo "<table border='1'>";
    
    // Conditional headers for PDF
    if ($reportType === 'accepted_scholars') {
        echo "<tr><th>Full Name</th><th>Course Name</th><th>Address</th><th>Email</th><th>Status</th><th>Applied At</th><th>Document</th></tr>";
    } elseif ($reportType === 'accepted_volunteers') {
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Application Date</th><th>Status</th><th>Resume Path</th><th>Phone</th></tr>";
    }
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($customFileName . '.pdf');
}

// Log the export with the full admin name and file type
$logQuery = $conn->prepare("INSERT INTO export_logs (admin_email, admin_name, report_type, file_name, file_type) VALUES (?, ?, ?, ?, ?)");
$logQuery->bind_param('sssss', $adminEmail, $adminName, $reportType, $customFileName, $fileType);
$logQuery->execute();

$conn->close();
exit();
?>
