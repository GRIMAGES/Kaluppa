<?php
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

    $update_sql = "UPDATE volunteer_application SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);

    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("si", $new_status, $application_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: admin_volunteer.php"); // Refresh page to show updated status
        exit();
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
}

// Fetch volunteer applications
$sql = "
    SELECT 
        a.id,
        a.first_name, 
        a.middle_name, 
        a.last_name, 
        a.email, 
        a.status, 
        a.application_date AS applied_at, 
        a.resume_path AS document 
    FROM volunteer_application a
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/courses.css">
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
        <h2 class="mb-4 text-center" style="color: white;">Volunteer Applications</h2>
        <div class="table-responsive">
            <table id="volunteerTable" class="display" style="color: black;">
                <thead style="background-color: #f2f2f2; color: black;">
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['middle_name']) ?></td>
                                <td><?= htmlspecialchars($row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['applied_at']) ?></td>
                                <td>
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="showApplicationDetails('<?= $row['id'] ?>', '<?= addslashes($row['first_name']) ?>', '<?= addslashes($row['middle_name']) ?>', '<?= addslashes($row['last_name']) ?>', '<?= addslashes($row['email']) ?>', '<?= addslashes($row['applied_at']) ?>', '<?= addslashes($row['status']) ?>', '<?= addslashes($row['document']) ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="<?= '/Backend/admin_controller/view_documents.php?file=' . urlencode($row['document']) . '&action=download' ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <form action="" method="POST">
                                        <input type="hidden" name="application_id" value="<?= $row['id'] ?>">
                                        <div class="input-group">
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="Pending" <?= ($row['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                                <option value="approved" <?= ($row['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                                                <option value="rejected" <?= ($row['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check"></i> Update
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No volunteer applications found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Application Details Modal -->
<div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-labelledby="applicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicationDetailsModalLabel">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>First Name:</strong> <span id="modalFirstName"></span></p>
                <p><strong>Middle Name:</strong> <span id="modalMiddleName"></span></p>
                <p><strong>Last Name:</strong> <span id="modalLastName"></span></p>
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <p><strong>Applied At:</strong> <span id="modalAppliedAt"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p><strong>Document:</strong> <a href="" id="modalDocument" target="_blank">View Document</a></p>
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
    function showApplicationDetails(id, firstName, middleName, lastName, email, appliedAt, status, document) {
        document.getElementById('modalFirstName').textContent = firstName;
        document.getElementById('modalMiddleName').textContent = middleName;
        document.getElementById('modalLastName').textContent = lastName;
        document.getElementById('modalEmail').textContent = email;
        document.getElementById('modalAppliedAt').textContent = appliedAt;
        document.getElementById('modalStatus').textContent = status;
        document.getElementById('modalDocument').href = '/Backend/admin_controller/view_documents.php?file=' + encodeURIComponent(document) + '&action=view';
        var modal = new bootstrap.Modal(document.getElementById('applicationDetailsModal'));
        modal.show();
    }

    $(document).ready(function() {
        $('#volunteerTable').DataTable();
    });

    $(document).ready(function() {
        $("#volunteerTable tbody").sortable();
        $("#volunteerTable tbody").disableSelection();
    });
</script>
</body>
</html>
