<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';
require '../../vendor/autoload.php'; // Ensure PHPMailer is included
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEnrollmentNotification($email, $firstName, $courseName, $courseStartDate, $courseEndDate, $courseInstructor) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org';
        $mail->Password = 'qfsp ihop mdqg ngoy'; // Replace with a secure password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your_email@example.com', 'Your Name');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Enrollment Notification';
        $mail->Body = "Dear $firstName,<br><br>You have been enrolled in the course: $courseName.<br>
                       Start Date: $courseStartDate<br>End Date: $courseEndDate<br>Instructor: $courseInstructor<br><br>
                       Best regards,<br>Your Team";

        $mail->send();
        error_log("Email sent successfully to $email for course $courseName.");
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to $email. Mailer Error: {$mail->ErrorInfo}");
        error_log("Exception Message: {$e->getMessage()}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (!isset($_POST['application_id']) || !isset($_POST['status'])) {
        die("Error: Missing application_id or status in POST data.");
    }

    $application_id = $conn->real_escape_string($_POST['application_id']); // Treat as string
    $new_status = $conn->real_escape_string($_POST['status']);

    // Debugging: Log received data
    error_log("Updating application ID: $application_id to status: $new_status");

    // Update the status in the database
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("ss", $new_status, $application_id); // Use "ss" since both are strings
    if ($stmt->execute()) {
        if ($new_status === 'Enrolled') {
            // Fetch student and course details for email
            $detailsQuery = "SELECT applications.first_name, applications.email, courses.name AS course_name, courses.start_date, courses.end_date, courses.instructor 
                             FROM applications 
                             JOIN courses ON applications.course_id = courses.id 
                             WHERE applications.id = ?";
            $detailsStmt = $conn->prepare($detailsQuery);
            $detailsStmt->bind_param('i', $application_id);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();

            if ($detailsResult->num_rows > 0) {
                $row = $detailsResult->fetch_assoc();
                $firstName = $row['first_name'];
                $email = $row['email'];
                $courseName = $row['course_name'];
                $courseStartDate = $row['start_date'];
                $courseEndDate = $row['end_date'];
                $courseInstructor = $row['instructor'];

                // Send enrollment email
                if (!sendEnrollmentNotification($email, $firstName, $courseName, $courseStartDate, $courseEndDate, $courseInstructor)) {
                    error_log("Failed to send enrollment email to $email for application ID $application_id");
                }
            }
            $detailsStmt->close();

            $_SESSION['status_message'] = "Application ID $application_id successfully updated to 'Enrolled'.";
        } else {
            $_SESSION['status_message'] = "Application ID $application_id successfully updated to '$new_status'.";
        }
        $stmt->close();
        header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php");
        exit();
    } else {
        $_SESSION['status_message'] = "Failed to update application ID $application_id.";
        header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php");
        exit();
    }
} else {
    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?error=invalid_request");
    exit();
}
?>
