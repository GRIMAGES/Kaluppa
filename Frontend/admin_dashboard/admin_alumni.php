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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Document Requests</h2>
    <table id="documentRequestsTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Document Type</th>
                <th>Reason</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch document requests from the database
            $stmt = $conn->prepare("SELECT id, full_name, email, document_type, reason, requested_at, status FROM document_requests ORDER BY requested_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['full_name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['document_type']) . "</td>
                        <td>" . htmlspecialchars($row['reason']) . "</td>
                        <td>" . htmlspecialchars($row['requested_at']) . "</td>
                        <td>
                            <select class='form-select status-select' data-id='" . htmlspecialchars($row['id']) . "'>
                                <option value='pending'" . ($row['status'] === 'pending' ? ' selected' : '') . ">Pending</option>
                                <option value='approved'" . ($row['status'] === 'approved' ? ' selected' : '') . ">Approved</option>
                                <option value='rejected'" . ($row['status'] === 'rejected' ? ' selected' : '') . ">Rejected</option>
                            </select>
                        </td>
                        <td>
                            <button class='btn btn-primary btn-sm save-status' data-id='" . htmlspecialchars($row['id']) . "'>Save</button>
                            <button class='btn btn-success btn-sm send-document' data-id='" . htmlspecialchars($row['id']) . "' data-email='" . htmlspecialchars($row['email']) . "' data-name='" . htmlspecialchars($row['full_name']) . "'>Send Document</button>
                        </td>
                      </tr>";
            }

            $stmt->close();
            ?>
        </tbody>
    </table>
</div>

<!-- Send Document Modal -->
<div class="modal fade" id="sendDocumentModal" tabindex="-1" aria-labelledby="sendDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendDocumentModalLabel">Send Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sendDocumentForm" enctype="multipart/form-data">
                    <input type="hidden" id="requestId" name="requestId">
                    <input type="hidden" id="alumniEmail" name="alumniEmail">
                    <div class="mb-3">
                        <label for="documentFile" class="form-label">Upload Document</label>
                        <input type="file" class="form-control" id="documentFile" name="documentFile" required>
                    </div>
                    <button type="submit" class="btn btn-success">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#documentRequestsTable').DataTable();

        // Handle status change
        $('.save-status').on('click', function() {
            const requestId = $(this).data('id');
            const newStatus = $(`.status-select[data-id="${requestId}"]`).val();

            $.ajax({
                url: '/Kaluppa/Backend/update_request_status.php',
                method: 'POST',
                data: { id: requestId, status: newStatus },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Status updated successfully.');
                    } else {
                        alert('Failed to update status: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while updating the status.');
                }
            });
        });

        // Open Send Document Modal
        $('.send-document').on('click', function() {
            const requestId = $(this).data('id');
            const alumniEmail = $(this).data('email');
            $('#requestId').val(requestId);
            $('#alumniEmail').val(alumniEmail);
            $('#sendDocumentModal').modal('show');
        });

        // Handle Send Document Form Submission
        $('#sendDocumentForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            $.ajax({
                url: '/Kaluppa/Backend/send_document.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.success) {
                        alert('Document sent successfully.');
                        $('#sendDocumentModal').modal('hide');
                        $('#sendDocumentForm')[0].reset();
                    } else {
                        alert('Failed to send document: ' + res.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while sending the document.');
                }
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

</body>
</html>
