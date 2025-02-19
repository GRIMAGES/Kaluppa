<?php
require_once '../../Backend/connection.php';
require_once '../../Frontend/vendor/autoload.php';  // For PDF generation and PHPMailer autoload

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch user details from the database
    $userId = $_SESSION['user_id'];
    $query = "SELECT * FROM user WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Generate PDF for the certificate
    $dompdf = new Dompdf();
    $html = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; }
            .certificate { border: 2px solid black; padding: 20px; width: 600px; margin: auto; }
        </style>
    </head>
    <body>
        <div class='certificate'>
            <h1>Certificate of Achievement</h1>
            <p>This is to certify that <strong>{$user['name']}</strong> has successfully completed the required course.</p>
            <p>Generated on: " . date('Y-m-d') . "</p>
        </div>
    </body>
    </html>
    ";
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $pdfContent = $dompdf->output();

    // Send certificate via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Set mail server host
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com'; // SMTP username
        $mail->Password = 'your-email-password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@example.com', 'Admin');
        $mail->addAddress($user['email']); // Add recipient's email address
        $mail->Subject = 'Certificate of Achievement';
        $mail->Body    = 'Dear ' . $user['name'] . ",\n\nAttached is your certificate of achievement.";
        $mail->addStringAttachment($pdfContent, 'certificate.pdf', 'application/pdf');

        $mail->send();
        echo 'Certificate has been sent to the user\'s email.';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
