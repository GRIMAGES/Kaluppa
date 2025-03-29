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
        $mail->isSMTP(); // Use Gmail's SMTP server
        $mail->Host = 'smtp.gmail.com'; // Use Gmail's SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org'; // Your Gmail address
        $mail->Password = 'qfsp ihop mdqg ngoy'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('wgonzales@kaluppa.org', 'Kaluppa Team');
        $mail->addAddress($email, $firstName);

        $mail->isHTML(true);
        $mail->Subject = 'Enrollment Confirmation';
        $mail->Body = "Dear $firstName,<br><br>You have been successfully enrolled in the course <strong>$courseName</strong>.<br>
                       Course Start Date: $courseStartDate<br>
                       Course End Date: $courseEndDate<br>
                       Instructor: $courseInstructor<br><br>
                       Best regards,<br>Course Administration Team";

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
    if (isset($_POST['selected_students'], $_POST['bulk_status'])) {
        $selectedStudents = $_POST['selected_students'];
        $bulkStatus = $_POST['bulk_status'];
        $successCount = 0;
        $failureCount = 0;

        foreach ($selectedStudents as $studentId) {
            // Get the course ID for the student
            $query = "SELECT course_id FROM applications WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->bind_result($courseId);
            $stmt->fetch();
            $stmt->close();

            // Check the number of enrolled students for the course
            $capacityQuery = "SELECT COUNT(*) AS enrolled_count FROM applications WHERE course_id = ? AND status = 'Enrolled'";
            $capacityStmt = $conn->prepare($capacityQuery);
            $capacityStmt->bind_param('i', $courseId);
            $capacityStmt->execute();
            $capacityStmt->bind_result($enrolledCount);
            $capacityStmt->fetch();
            $capacityStmt->close();

            // Assume course capacity is 5 (can be fetched dynamically if stored in the database)
            $courseCapacity = 5;

            // Automatically waitlist students if the course is full
            if ($bulkStatus === 'Enrolled' && $enrolledCount >= $courseCapacity) {
                $bulkStatus = 'Waitlisted';
            }

            if ($bulkStatus === 'Enrolled' && $enrolledCount < $courseCapacity) {
                // Fetch student and course details for email
                $detailsQuery = "SELECT applications.first_name, applications.email, courses.name AS course_name, courses.start_date, courses.end_date, courses.instructor 
                                 FROM applications 
                                 JOIN courses ON applications.course_id = courses.id 
                                 WHERE applications.id = ?";
                $detailsStmt = $conn->prepare($detailsQuery);
                $detailsStmt->bind_param('i', $studentId);
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
                        error_log("Failed to send enrollment email to $email for application ID $studentId");
                    } else {
                        error_log("Email sent successfully to $email for application ID $studentId");
                    }
                }
                $detailsStmt->close();
            }

            // Update the student's status
            $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('ss', $bulkStatus, $studentId);
            $updateStmt->execute();

            if ($updateStmt->affected_rows > 0) {
                $successCount++;
            } else {
                $failureCount++;
            }

            $updateStmt->close();
        }

        // Set session message
        $_SESSION['status_message'] = "Status updated for $successCount students. Failed for $failureCount students.";
    } else {
        $_SESSION['status_message'] = "No students selected for status update.";
    }
}
header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php");
exit();
?>
