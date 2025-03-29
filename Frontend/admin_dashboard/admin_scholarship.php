<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
            display: none; /* Hide checkboxes initially */
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
        </div>

        <div class="table-responsive">
            <form id="updateStatusForm" action="../../Backend/admin_controller/update_application_status.php" method="POST">
                <table id="scholarshipTable" class="table table-striped table-bordered" style="color: black;">
                    <thead style="background-color: #f2f2f2; color: black;">
                        <tr>
                            <th>Select</th>
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
                                <input type="checkbox" name="selected_students[]" value="' . $id . '" class="select-student">
                            </td>
                            <td>' . $id . '</td>
                            <td>' . $first_name . '</td>
                            <td>' . $middle_name . '</td>
                            <td>' . $last_name . '</td>
                            <td>' . $course_name . '</td>
                            <td>' . $email . '</td>
                            <td>' . $status . '</td> <!-- Current Status Column -->
                            <td>
                                <select name="status[' . $id . ']" class="form-select form-select-sm"> <!-- Change Status Column -->
                                    <option value="Pending" ' . ($status === "Pending" ? "selected" : "") . '>Pending</option>
                                    <option value="Approved" ' . ($status === "Approved" ? "selected" : "") . '>Approved</option>
                                    <option value="Rejected" ' . ($status === "Rejected" ? "selected" : "") . '>Rejected</option>
                                    <option value="Under Review" ' . ($status === "Under Review" ? "selected" : "") . '>Under Review</option>
                                    <option value="Enrolled" ' . ($status === "Enrolled" ? "selected" : "") . '>Enrolled</option>
                                </select>
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
                <button type="submit" class="btn btn-primary mt-3">Update Status</button>
            </form>
        </div>

        <!-- Process Enrollment Form -->
        <div class="d-flex justify-content-end mt-3">
            <form id="enrollmentForm" action="../../Backend/admin_controller/process_enrollment.php" method="POST">
                <button type="button" id="showCheckboxes" class="btn btn-primary me-2">Process Enrollment</button>
                <button type="submit" id="submitEnrollment" class="btn btn-success" style="display: none;">Submit Enrollment</button>
            </form>
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

// Show checkboxes when "Process Enrollment" is clicked
document.getElementById('showCheckboxes').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.select-student');
    checkboxes.forEach(checkbox => {
        checkbox.style.display = 'inline-block';
    });
    document.getElementById('showCheckboxes').style.display = 'none';
    document.getElementById('submitEnrollment').style.display = 'inline-block';
});

// Add validation only for the "Submit Enrollment" button
document.getElementById('enrollmentForm').addEventListener('submit', function(event) {
    const checkboxes = document.querySelectorAll('.select-student:checked');
    if (checkboxes.length === 0) {
        event.preventDefault();
        alert('Please select at least one student to process enrollment.');
    }
});
</script>
</body>
</html>