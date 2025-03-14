<?php
ini_set('display_errors', 1);

// Log errors to a file inside the Backend/logs folder
ini_set("log_errors", 1);
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch all applications for the logged-in user
$query = "SELECT applications.id, applications.status, applications.applied_at, courses.name AS course_name, applications.documents 
          FROM applications 
          JOIN courses ON applications.course_id = courses.id 
          WHERE applications.email = ? 
          ORDER BY applications.applied_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();

// Check for success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_transaction.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- New CSS for smooth animations -->
    <link rel="stylesheet" href="../CSS/user_css/animations.css">
    <style>
        .floating-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"> <!-- Add custom-modal class -->
            <div class="modal-header bg-theme text-white"> <!-- Add bg-theme and text-white classes -->
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>
<!-- Transactions Section -->
<div class="container mt-5 mb-5">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show floating-alert" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="table-container p-4 bg-light rounded shadow-sm">
        <h2 class="mb-4">Your Applications</h2>
        <div class="table-responsive">
            <table id="applicationsTable" class="display table table-bordered">
                <thead style="background-color: #f2f2f2; color: black;">
                    <tr>
                        <th>Course Name</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Document</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($applications)): ?>
                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($application['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($application['status']); ?></td>
                                <td><?php echo htmlspecialchars($application['applied_at']); ?></td>
                                <td>
                                    <a href="/opt/bitnami/apache/htdocs/Kaluppa/Backend/admin_controller/view_document.php/?file=<?php echo urlencode($application['documents']); ?>&action=view" target="_blank">View</a>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDocumentModal" data-application-id="<?php echo $application['id']; ?>" data-document="<?php echo htmlspecialchars($application['documents']); ?>">
                                        Edit Document
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No applications found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDocumentModalLabel">Edit Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDocumentForm" method="POST" enctype="multipart/form-data" action="/opt/bitnami/apache/htdocs/Kaluppa/Backend/user_controller/update_application.php">
                    <input type="hidden" name="application_id" id="applicationId">
                    <div class="mb-3">
                        <label for="document" class="form-label">Upload New Document</label>
                        <input type="file" class="form-control" id="document" name="document" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Document</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#applicationsTable').DataTable();
    });

    document.addEventListener('DOMContentLoaded', function () {
        var editDocumentModal = document.getElementById('editDocumentModal');
        editDocumentModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var applicationId = button.getAttribute('data-application-id');
            var document = button.getAttribute('data-document');
            var modalTitle = editDocumentModal.querySelector('.modal-title');
            var modalBodyInput = editDocumentModal.querySelector('.modal-body input#applicationId');

            modalTitle.textContent = 'Edit Document for Application ID ' + applicationId;
            modalBodyInput.value = applicationId;
        });

        // Auto-hide alert after 5 seconds
        var alert = document.querySelector('.floating-alert');
        if (alert) {
            setTimeout(function() {
                alert.classList.remove('show');
                alert.classList.add('fade');
            }, 5000);
        }
    });

    $(document).ready(function() {
        $("#applicationsTable tbody").sortable();
        $("#applicationsTable tbody").disableSelection();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
