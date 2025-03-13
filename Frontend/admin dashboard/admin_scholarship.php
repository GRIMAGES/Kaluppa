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

$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key
// Fetch the admin's full name from the user table
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();
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
        // New (clean and correct):
header("Location: https://kaluppa.online/Frontend/admin%20dashboard/admin_scholarship.php");
        exit();
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
}
// Define the SQL query with JOIN to get course name from courses table
$sql = "SELECT applications.id, applications.first_name, applications.middle_name, applications.last_name, courses.name, applications.email, applications.status, applications.applied_at, applications.document 
        FROM applications 
        JOIN courses ON applications.course_id = courses.id";
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
<?php include 'admin_sidebar.php'; ?>

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

 <!-- Main Content -->
 <div class="content" style="margin-left: 250px; padding: 20px;">
        <div class="container mt-5">
            <h2 class="mb-4 text-center" style="color: white;">Scholarship Applications</h2>
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
                                echo '<tr>
                                        <td>' . htmlspecialchars($row['id']) . '</td>
                                        <td>' . htmlspecialchars($row['first_name']) . '</td>
                                        <td>' . htmlspecialchars($row['middle_name']) . '</td>
                                        <td>' . htmlspecialchars($row['last_name']) . '</td>
                                        <td>' . htmlspecialchars($row['name']) . '</td>
                                        <td>' . htmlspecialchars($row['email']) . '</td>
                                        <td>' . htmlspecialchars($row['status']) . '</td>
                                        <td>' . htmlspecialchars($row['applied_at']) . '</td>
                                        <td>
                                            <div class="d-inline-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="showApplicationDetails(' . $row['id'] . ', \'' . addslashes($row['first_name']) . '\', \'' . addslashes($row['middle_name']) . '\', \'' . addslashes($row['last_name']) . '\', \'' . addslashes($row['name']) . '\', \'' . addslashes($row['email']) . '\', \'' . addslashes($row['applied_at']) . '\', \'' . addslashes($row['status']) . '\', \'' . addslashes($row['document']) . '\')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <a href="../../Backend/admin_controller/view_document.php?file=' . urlencode($row['document']) . '&action=download" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <form action="../../Backend/admin_controller/update_application_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="' . $row['id'] . '">
                                                <div class="input-group">
                                                    <select name="status" class="form-select form-select-sm">
                                                        <option value="Pending" ' . ($row['status'] === 'Pending' ? 'selected' : '') . '>Pending</option>
                                                        <option value="Approved" ' . ($row['status'] === 'Approved' ? 'selected' : '') . '>Approved</option>
                                                        <option value="Rejected" ' . ($row['status'] === 'Rejected' ? 'selected' : '') . '>Rejected</option>
                                                        <option value="Under Review" ' . ($row['status'] === 'Under Review' ? 'selected' : '') . '>Under Review</option>
                                                        <option value="Enrolled" ' . ($row['status'] === 'Enrolled' ? 'selected' : '') . '>Enrolled</option>
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

    <!-- Bootstrap JS and Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <!-- Include DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <!-- Include jQuery UI JS for sortable functionality -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- JavaScript for Handling Modal -->
    <script>
        function showApplicationDetails(id, firstName, middleName, lastName, courseName, email, appliedAt, status, documents) {
    console.log("Application details function called"); // Debugging line

    // Prepare details for modal
    var detailsHtml = `
        <p><strong>First Name:</strong> ${firstName}</p>
        <p><strong>Middle Name:</strong> ${middleName}</p>
        <p><strong>Last Name:</strong> ${lastName}</p>
        <p><strong>Course Name:</strong> ${courseName}</p>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Applied At:</strong> ${appliedAt}</p>
        <p><strong>Status:</strong> ${status}</p>
    `;
    
    // Populate modal with application details
    document.getElementById('applicationDetails').innerHTML = detailsHtml;

    // Prepare document links
    var documentLinksHtml = '';
    var documentArray = documents.split(',');
    documentArray.forEach(function(document, index) {   
        documentLinksHtml += `
            <div>
                <a href="../../Backend/admin_controller/view_document.php?file=${encodeURIComponent(document.trim())}&action=view" target="_blank" class="btn btn-primary w-100 mb-2">View Document ${index + 1}</a>
                <a href="../../Backend/admin_controller/view_document.php?file=${encodeURIComponent(document.trim())}&action=download" class="btn btn-success w-100">Download Document ${index + 1}</a>
            </div>
        `;
    });

    // Populate modal with document links
    document.getElementById('documentLinks').innerHTML = documentLinksHtml;
    
    // Show the modal
    var viewApplicationModal = new bootstrap.Modal(document.getElementById('viewApplicationModal'));
    viewApplicationModal.show();

    console.log("Modal shown with ID:", id); // Debugging line
}
$(document).ready(function() {
        $('#scholarshipTable').DataTable();
    });
    $(document).ready(function() {
        $("#scholarshipTable tbody").sortable();
        $("#scholarshipTable tbody").disableSelection();
    });
    </script>
</body>
</html>
