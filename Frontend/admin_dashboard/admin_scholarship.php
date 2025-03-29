<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

function sendEnrollmentNotification($email, $firstName, $courseName, $courseStartDate, $courseEndDate, $courseInstructor) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org'; // Replace with your Gmail address
        $mail->Password = 'qfsp ihop mdqg ngoy'; // Replace with your Gmail password or App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email headers and content
        $mail->setFrom('wgonzales@kaluppa.org', 'Kaluppa Team');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Enrollment Confirmation for $courseName";
        $mail->Body = "Dear $firstName,<br><br>
                       Congratulations! You have been successfully enrolled in the course: <strong>$courseName</strong>.<br><br>
                       <strong>Course Details:</strong><br>
                       - Instructor: $courseInstructor<br>
                       - Start Date: $courseStartDate<br>
                       - End Date: $courseEndDate<br><br>
                       Thank you for choosing Kaluppa.<br><br>
                       Best regards,<br>The Kaluppa Team";

        $mail->send();
        error_log("Enrollment email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        error_log("Enrollment email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

require_once '../../Backend/connection.php';
session_start();

// Set session timeout duration (in seconds)
$timeout_duration = 1000;

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data
    header("Location: /Frontend/index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

// Define the SQL query with JOIN to get course name from courses table
$sql = "SELECT applications.id, applications.first_name, applications.middle_name, applications.last_name, courses.name, applications.email, applications.status, applications.applied_at, applications.documents, applications.course_id 
        FROM applications 
        JOIN courses ON applications.course_id = courses.id";

// Fetch all courses for the dropdown filter
$coursesResult = $conn->query("SELECT id, name FROM courses");
$courses = [];
if ($coursesResult->num_rows > 0) {
    while ($courseRow = $coursesResult->fetch_assoc()) {
        $courses[] = $courseRow;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .dataTables_length label,
        .dataTables_filter label {
            color: white;
        }
        .dataTables_length select,
        .dataTables_filter input {
            color: white;
        }
        .table-responsive {
            overflow: visible;
        }
        .select-student {
            display: inline-block; /* Make checkboxes visible */
            margin: 0; /* Optional: Adjust spacing */
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="content" style="margin-left: 250px; padding: 20px; color: black;">
    <div class="container mt-5">
        <h2 class="mb-4 text-center" style="color: black;">Scholarship Applications</h2>

        <!-- Course Filter Dropdown -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label for="courseFilter" class="form-label" style="color: black;">Filter by Course:</label>
                <select id="courseFilter" class="form-select form-select-sm" style="width: 200px; color: black;" onchange="filterByCourse()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="maxApplications" class="form-label" style="color: black;">Max Applications:</label>
                <input type="number" id="maxApplications" class="form-control form-control-sm" style="width: 100px; color: black;" min="1" value="10" onchange="limitApplications()">
            </div>
            <div>
                <form id="bulkUpdateForm" action="../../Backend/admin_controller/bulk_update_status.php" method="POST" class="d-flex align-items-center gap-2">
                    <select name="bulk_status" class="form-select form-select-sm" style="width: 150px;" required>
                        <option value="" disabled selected>Change Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Enrolled">Enrolled</option>
                        <option value="Waitlisted">Waitlisted</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply to Selected</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table id="scholarshipTable" class="table table-striped table-bordered" style="color: black;">
                <thead style="background-color: #f2f2f2; color: black;">
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        </th>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Course Name</th>
                        <th>Email</th>
                        <th>Current Status</th>
                        <th>Change Status</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = htmlspecialchars($row['id']);
                        $first_name = htmlspecialchars($row['first_name']);
                        $middle_name = htmlspecialchars($row['middle_name']);
                        $last_name = htmlspecialchars($row['last_name']);
                        $course_name = htmlspecialchars($row['name']);
                        $email = htmlspecialchars($row['email']);
                        $status = htmlspecialchars($row['status']);
                        $applied_at = htmlspecialchars($row['applied_at']);
                        $documents = json_decode($row['documents'], true); // Decode documents JSON

                        echo '<tr data-course-id="' . htmlspecialchars($row['course_id']) . '">
                        <td>
                            <input type="checkbox" name="selected_students[]" value="' . $id . '" form="bulkUpdateForm" class="select-student">
                        </td>
                        <td>' . $id . '</td>
                        <td>' . $first_name . '</td>
                        <td>' . $middle_name . '</td>
                        <td>' . $last_name . '</td>
                        <td>' . $course_name . '</td>
                        <td>' . $email . '</td>
                        <td>' . $status . '</td> <!-- Current Status Column -->
                        <td>
                            <form action="../../Backend/admin_controller/update_application_status.php" method="POST">
                                <input type="hidden" name="application_id" value="' . $id . '">
                                <select name="status" class="form-select form-select-sm"> <!-- Change Status Column -->
                                    <option value="Pending" ' . ($status === "Pending" ? "selected" : "") . '>Pending</option>
                                    <option value="Approved" ' . ($status === "Approved" ? "selected" : "") . '>Approved</option>
                                    <option value="Rejected" ' . ($status === "Rejected" ? "selected" : "") . '>Rejected</option>
                                    <option value="Under Review" ' . ($status === "Under Review" ? "selected" : "") . '>Under Review</option>
                                    <option value="Enrolled" ' . ($status === "Enrolled" ? "selected" : "") . '>Enrolled</option>
                                    <option value="Waitlisted" ' . ($status === "Waitlisted" ? "selected" : "") . '>Waitlisted</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary mt-2">Update</button>
                            </form>
                        </td>
                        <td>' . $applied_at . '</td>
                        <td>
                            <div class="d-inline-flex gap-2">';
                                if (!empty($documents)) {
                                    foreach ($documents as $document) {
                                        $file_name = htmlspecialchars($document['file_name']);
                                        echo '<a href="../../Backend/admin_controller/view_document.php?application_id=' . urlencode($id) . '&file=' . urlencode($file_name) . '&action=view" class="btn btn-sm btn-outline-primary" target="_blank">
                                            View ' . htmlspecialchars($file_name) . '
                                        </a>';
                                        echo '<a href="../../Backend/admin_controller/view_document.php?application_id=' . urlencode($id) . '&file=' . urlencode($file_name) . '&action=download" class="btn btn-sm btn-outline-success">
                                            Download ' . htmlspecialchars($file_name) . '
                                        </a>';
                                    }
                                }
                        echo '</div>
                        </td>
                    </tr>';
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function limitApplications() {
    const maxApplications = document.getElementById('maxApplications').value;
    const table = document.getElementById('scholarshipTable');
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach((row, index) => {
        if (index < maxApplications) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByCourse() {
    const courseFilter = document.getElementById('courseFilter').value;
    const rows = document.querySelectorAll('#scholarshipTable tbody tr');

    rows.forEach(row => {
        const courseId = row.getAttribute('data-course-id');
        if (courseFilter === '' || courseFilter === courseId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.select-student');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && $_POST['status'] === 'Enrolled') {
    $applicationId = $_POST['application_id'];

    // Update the application status to "Enrolled"
    $updateQuery = "UPDATE applications SET status = 'Enrolled' WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('i', $applicationId);

    if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
        // Fetch student and course details
        $query = "SELECT applications.first_name, applications.last_name, applications.email, courses.name AS course_name, courses.start_date, courses.end_date, courses.instructor 
                  FROM applications 
                  JOIN courses ON applications.course_id = courses.id 
                  WHERE applications.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $studentName = $row['first_name'] . ' ' . $row['last_name'];
            $studentEmail = $row['email'];
            $courseName = $row['course_name'];
            $courseStartDate = $row['start_date'];
            $courseEndDate = $row['end_date'];
            $courseInstructor = $row['instructor'];

            // Send email using sendEnrollmentNotification function
            if (sendEnrollmentNotification($studentEmail, $studentName, $courseName, $courseStartDate, $courseEndDate, $courseInstructor)) {
                echo "<script>alert('Status updated to Enrolled and email sent successfully to $studentEmail');</script>";
            } else {
                error_log("Failed to send enrollment email to $studentEmail for application ID $applicationId");
                echo "<script>alert('Status updated to Enrolled, but failed to send email to $studentEmail');</script>";
            }
        } else {
            error_log("No application found for ID $applicationId");
            echo "<script>alert('Status updated to Enrolled, but no application details found.');</script>";
        }

        $stmt->close();
    } else {
        error_log("Failed to update status to 'Enrolled' for application ID $applicationId");
        echo "<script>alert('Failed to update status to Enrolled');</script>";
    }

    $updateStmt->close();
}
?>
</body>
</html>