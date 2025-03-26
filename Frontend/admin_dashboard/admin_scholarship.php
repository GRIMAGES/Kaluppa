<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}


// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Last activity was more than 30 minutes ago
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

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

// Update application status logic
if (isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];

    $update_sql = "UPDATE applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);

    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("si", $new_status, $application_id);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['status_updated'] = true; // ✅ Add this line
        header("Location:https://www.kaluppa.online/Kaluppa/Frontend/admin_dashboard/admin_scholarship.php");
        exit();
    
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
}

$first_name = isset($row['first_name']) ? addslashes($row['first_name']) : '';
$middle_name = isset($row['middle_name']) ? addslashes($row['middle_name']) : '';
$last_name = isset($row['last_name']) ? addslashes($row['last_name']) : '';
$course_name = isset($row['name']) ? addslashes($row['name']) : ''; // Rename from $work_name
$email = isset($row['email']) ? addslashes($row['email']) : '';
$applied_at = isset($row['applied_at']) ? addslashes($row['applied_at']) : '';
$status = isset($row['status']) ? addslashes($row['status']) : '';
$documents = isset($row['documents']) ? addslashes($row['documents']) : '';
$encodedDocument = isset($row['documents']) ? urlencode($row['documents']) : '';
// Define the SQL query with JOIN to get course name from courses table
$sql = "SELECT applications.id, applications.first_name, applications.middle_name, applications.last_name, courses.name, applications.email, applications.status, applications.applied_at, applications.documents, applications.course_id , applications.documents 
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .dataTables_length label,
        .dataTables_filter label {
            color: white; /* Change the color of the text */
        }
        .dataTables_length select,
        .dataTables_filter input {
            color: white; /* Change the color of the text */
        }
        .table-responsive {
            overflow: visible; /* Ensure the table is not scrollable */
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<div class="content" style="margin-left: 250px; padding: 20px;">
    <div class="container mt-5">
        <h2 class="mb-4 text-center" style="color: white;">Scholarship Applications</h2>

        <!-- Course Filter Dropdown -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label for="courseFilter" class="form-label" style="color: white;">Filter by Course:</label>
                <select id="courseFilter" class="form-select form-select-sm" style="width: 200px;" onchange="filterByCourse()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="maxApplications" class="form-label" style="color: white;">Max Applications:</label>
                <input type="number" id="maxApplications" class="form-control form-control-sm" style="width: 100px;" min="1" value="10" onchange="limitApplications()">
            </div>
        </div>

        <div class="table-responsive">
            <table id="scholarshipTable" class="display" style="color: black;">
                <thead style="background-color: #f2f2f2; color: black;">
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Course Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                        <th>Update Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = htmlspecialchars($row['id'] ?? '', ENT_QUOTES);
                        $first_name = htmlspecialchars($row['first_name'] ?? '', ENT_QUOTES);
                        $middle_name = htmlspecialchars($row['middle_name'] ?? '', ENT_QUOTES);
                        $last_name = htmlspecialchars($row['last_name'] ?? '', ENT_QUOTES);
                        $course_name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES); // Updated variable
                        $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES);
                        $status = htmlspecialchars($row['status'] ?? '', ENT_QUOTES);
                        $applied_at = htmlspecialchars($row['applied_at'] ?? '', ENT_QUOTES);

                        // Check if 'documents' exists and is not empty before decoding
                        $documents = isset($row['documents']) && !empty($row['documents']) ? json_decode($row['documents'], true) : [];

                        echo '<tr data-course-id="' . htmlspecialchars($row['course_id']) . '">
                        <td>' . htmlspecialchars($id) . '</td>
                        <td>' . htmlspecialchars($first_name) . '</td>
                        <td>' . htmlspecialchars($middle_name) . '</td>
                        <td>' . htmlspecialchars($last_name) . '</td>
                        <td>' . htmlspecialchars($course_name) . '</td> <!-- Updated variable -->
                        <td>' . htmlspecialchars($email) . '</td>
                        <td>' . htmlspecialchars($status) . '</td>
                        <td>' . htmlspecialchars($applied_at) . '</td>
                        <td>
                            <div class="d-inline-flex gap-2">';
                            
                            // Loop through the documents to generate download buttons
                            if (!empty($documents)) {
                                foreach ($documents as $document) {
                                    $fileName = $document['file_name'] ?? ''; // Extract the file name
                                    $encodedFileName = urlencode($fileName);

                                    // Only show download button if the file name exists
                                    if ($fileName) {
                                        echo '<a href="../../Backend/admin_controller/view_document.php?application_id=' . urlencode($id) . '&download=' . $encodedFileName . '" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>';
                                    }
                                }
                            }

                            echo '</div>
                        </td>
                        <td>
                            <form action="../../Backend/admin_controller/update_application_status.php" method="POST">
                                <input type="hidden" name="application_id" value="' . htmlspecialchars($id) . '">
                                <div class="input-group">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="Pending"' . ($status === 'Pending' ? ' selected' : '') . '>Pending</option>
                                        <option value="Approved"' . ($status === 'Approved' ? ' selected' : '') . '>Approved</option>
                                        <option value="Rejected"' . ($status === 'Rejected' ? ' selected' : '') . '>Rejected</option>
                                        <option value="Under Review"' . ($status === 'Under Review' ? ' selected' : '') . '>Under Review</option>
                                        <option value="Enrolled"' . ($status === 'Enrolled' ? ' selected' : '') . '>Enrolled</option>
                                    </select>
                                </div>
                        </td>
                        <td>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                            </form>
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


<!-- Application Details Modal -->
<div class="modal fade" id="viewApplicationModal" tabindex="-1" aria-labelledby="viewApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewApplicationModalLabel">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="applicationDetails"></div>
                <hr>
                <div id="documentLinks"></div>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="statusToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Application status updated successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap JS and Dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

<!-- Include Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- Include DataTables JS -->
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<!-- Include jQuery UI JS for sortable functionality -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<!-- JavaScript for Handling Modal -->
<script>
function showApplicationDetails(id, firstName, middleName, lastName, courseName, email, appliedAt, status, documents) {
    console.log("Application details function called");
    console.log("Raw documents string:", documents);

    var detailsHtml = `
        <p><strong>First Name:</strong> ${firstName}</p>
        <p><strong>Middle Name:</strong> ${middleName}</p>
        <p><strong>Last Name:</strong> ${lastName}</p>
        <p><strong>Course Name:</strong> ${courseName}</p>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Applied At:</strong> ${appliedAt}</p>
        <p><strong>Status:</strong> ${status}</p>
    `;
    document.getElementById('applicationDetails').innerHTML = detailsHtml;

    var documentLinksHtml = '';
    // Check if documents is a string that needs to be split
    var documentArray = Array.isArray(documents) ? documents : documents.split(',');

    if (documents.trim() === '' || documentArray.length === 0 || (documentArray.length === 1 && documentArray[0].trim() === '')) {
        documentLinksHtml = `<p class="text-danger">No uploaded documents available.</p>`;
    } else {
        documentArray.forEach(function(document, index) {
            const trimmedDoc = document.trim();
            if (trimmedDoc !== '') {
                documentLinksHtml += `
                    <div>
                        <!-- View Document Link -->
                        <a href="../../Backend/admin_controller/view_document.php?application_id=${encodeURIComponent(id)}&file=${encodeURIComponent(trimmedDoc)}&action=view" target="_blank" class="btn btn-primary w-100 mb-2">View Document ${index + 1}</a>
                        <!-- Download Document Link -->
                        <a href="../../Backend/admin_controller/view_document.php?application_id=${encodeURIComponent(id)}&file=${encodeURIComponent(trimmedDoc)}&action=download" class="btn btn-success w-100">Download Document ${index + 1}</a>
                    </div>
                `;
            }
        });
    }

    document.getElementById('documentLinks').innerHTML = documentLinksHtml;

    var viewApplicationModal = new bootstrap.Modal(document.getElementById('viewApplicationModal'));
    viewApplicationModal.show();

    console.log("Modal shown with ID:", id);
}

function filterByCourse() {
    const courseId = document.getElementById('courseFilter').value.trim(); // Get the selected course ID
    const table = $('#scholarshipTable').DataTable();

    // Apply filter to the table
    if (courseId === "") {
        table.search('').draw(); // Show all rows if "All Courses" is selected
    } else {
        table.column(4).search('^' + courseId + '$', true, false).draw(); // Filter by exact course ID
    }
}

function limitApplications() {
    const maxApplications = parseInt(document.getElementById('maxApplications').value, 10);
    const table = $('#scholarshipTable').DataTable();

    // Reset all rows to visible
    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        this.nodes().to$().removeClass('waitlist').show();
    });

    // Hide rows exceeding the limit and mark them as "Waitlist"
    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        if (rowIdx >= maxApplications) {
            this.nodes().to$().addClass('waitlist').hide();
        }
    });
}

$(document).ready(function() {
    $('#scholarshipTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "lengthChange": true,
        "pageLength": 10,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });

    // Apply initial limit
    limitApplications();
});



</script>
</body>
</html>
