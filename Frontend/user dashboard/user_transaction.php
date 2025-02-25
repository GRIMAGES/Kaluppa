<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch all applications for the logged-in user
$query = "SELECT applications.id, applications.status, applications.applied_at, courses.name AS course_name, applications.document 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_calendar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Section -->
<div class="container mt-5">
    <h2 class="mb-4">Your Applications</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
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
                                <a href="/Backend/admin_controller/view_document.php?file=<?php echo urlencode($application['document']); ?>&action=view" target="_blank">View</a>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDocumentModal" data-application-id="<?php echo $application['id']; ?>" data-document="<?php echo htmlspecialchars($application['document']); ?>">
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

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDocumentModalLabel">Edit Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDocumentForm" method="POST" enctype="multipart/form-data" action="/Backend/user_controller/update_document.php">
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

<script>
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
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
