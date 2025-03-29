<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';
require '../../vendor/autoload.php'; // Ensure PHPMailer is included
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

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

        $mail->setFrom('your_email@example.com', 'Course Admin');
        $mail->addAddress($email, $firstName);

        $mail->isHTML(true);
        $mail->Subject = 'Enrollment Confirmation';
        $mail->Body = "Dear $firstName,<br><br>You have been successfully enrolled in the course <strong>$courseName</strong>.<br>
                       Course Start Date: $courseStartDate<br>
                       Course End Date: $courseEndDate<br>
                       Instructor: $courseInstructor<br><br>
                       Best regards,<br>Course Administration Team";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_students'], $_POST['bulk_status'])) {
        $selectedStudents = $_POST['selected_students'];
        $bulkStatus = $_POST['bulk_status'];

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
                    sendEnrollmentNotification($email, $firstName, $courseName, $courseStartDate, $courseEndDate, $courseInstructor);
                }
                $detailsStmt->close();
            }

            // Update the student's status
            $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('ss', $bulkStatus, $studentId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $_SESSION['message'] = "Status updated for selected students.";
    }
}
header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php");
exit();
?>
