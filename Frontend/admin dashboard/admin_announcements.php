<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/announcement_errors.log");

// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Last activity was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
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

function addAnnouncement($title, $content, $image, $status = 'published') {
    global $conn;
    $sql = "INSERT INTO announcements (title, content, image, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $title, $content, $image, $status);
    
    if ($stmt->execute()) {
        return "Announcement added successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
}

/**
 * Update an announcement
 */
function updateAnnouncement($id, $title, $content, $image, $status) {
    global $conn;
    $sql = "UPDATE announcements SET title=?, content=?, image=?, status=?, updated_at=NOW() WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $content, $image, $status, $id);
    
    if ($stmt->execute()) {
        return "Announcement updated successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
}

/**
 * Fetch all announcements
 */
function getAnnouncements() {
    global $conn;
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    return $announcements;
}

/**
 * Fetch a single announcement by ID
 */
function getAnnouncementById($id) {
    global $conn;
    $sql = "SELECT * FROM announcements WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Delete an announcement
 */
function deleteAnnouncement($id) {
    global $conn;
    $sql = "DELETE FROM announcements WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return "Announcement deleted successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
}

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $status = $_POST['status'];
        $image = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = 'uploads/' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image);
        }

        $message = addAnnouncement($title, $content, $image, $status);
    } elseif (isset($_POST['update_announcement'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $status = $_POST['status'];
        $image = $_POST['existing_image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = 'uploads/' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image);
        }

        $message = updateAnnouncement($id, $title, $content, $image, $status);
    } elseif (isset($_POST['delete_announcement'])) {
        $id = $_POST['id'];
        $message = deleteAnnouncement($id);
    }
}

// Fetch all announcements
$announcements = getAnnouncements();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Include Custom CSS -->
    <link rel="stylesheet" href="../CSS/admin_css/admin_announcements.css">
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
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>


<div class="content" style="margin-left: 250px; padding: 20px;">
    <div class="container mt-5">
        <h2 class="mb-4 text-center" style="color: white;">Manage Announcements</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">Add Announcement</button>
        </div>
        <div class="table-responsive">
            <table id="announcementsTable" class="display table table-bordered" style="color: black;">
                <thead style="background-color: #f2f2f2; color: black;">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($announcement['id']); ?></td>
                            <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                            <td><?php echo htmlspecialchars($announcement['content']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($announcement['image']); ?>" alt="Image" style="width: 100px;"></td>
                            <td><?php echo htmlspecialchars($announcement['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal<?php echo $announcement['id']; ?>">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" name="delete_announcement" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Announcement Modal -->
                        <div class="modal fade" id="editAnnouncementModal<?php echo $announcement['id']; ?>" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Title</label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="content" class="form-label">Content</label>
                                                <textarea class="form-control" id="content" name="content" rows="3" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="image" class="form-label">Image</label>
                                                <input type="file" class="form-control" id="image" name="image">
                                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($announcement['image']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="published" <?php echo $announcement['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                                    <option value="draft" <?php echo $announcement['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="archived" <?php echo $announcement['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </div>
                                            <button type="submit" name="update_announcement" class="btn btn-primary">Update Announcement</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAnnouncementModalLabel">Add Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary">Add Announcement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#announcementsTable').DataTable();
    });
</script>
</body>
</html>
