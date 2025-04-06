<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
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
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the announcements page
// Log admin's access to the settings page
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the settings page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
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


function addAnnouncement($title, $content, $image, $status = 'published') {
    global $conn;
    $sql = "INSERT INTO announcements (title, content, image, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $title, $content, $image, $status);
    
    if ($stmt->execute()) {
      $_SESSION['toast'] = "Announcement added successfully!";
      echo "<script>window.location.href='admin_announcements.php';</script>";
      exit;;
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
      $_SESSION['toast'] = "Announcement updated successfully!";
      echo "<script>window.location.href='admin_announcements.php';</script>";
      exit;
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
      $_SESSION['toast'] = "Announcement deleted successfully!";
      echo "<script>window.location.href='admin_announcements.php';</script>";
      exit;
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
            $image = 'Images/' . basename($_FILES['image']['name']);
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
            $image = 'Images/' . basename($_FILES['image']['name']);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Include Custom CSS -->
    <link rel="stylesheet" href="../CSS/admin_css/admin_announcement.css">
    
</head>
<body>
<?php include 'sidebar.php'; ?>


<!-- Toast Notification Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $_SESSION['toast'] ?? ''; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
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
        <h2 class="mb-4 text-center" style="color: black;">Manage Announcements</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">Add Announcement</button>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($announcements as $announcement): ?>
        <div class="col">
            <div class="card h-100 shadow-lg border-0">
                <?php if (!empty($announcement['image'])): ?>
                    <img src="<?php echo htmlspecialchars($announcement['image']); ?>" class="card-img-top" alt="Announcement Image" style="height: 200px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                    <span class="badge bg-<?php echo $announcement['status'] === 'published' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($announcement['status']); ?>
                    </span>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
    <!-- Edit Button with Icon -->
    <button class="btn btn-sm btn-outline-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal<?php echo $announcement['id']; ?>">
        <i class="bi bi-pencil-square me-1"></i> Edit
    </button>

    <!-- Delete Form with Icon Button -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
        <button type="submit" name="delete_announcement" class="btn btn-sm btn-outline-danger d-flex align-items-center">
            <i class="bi bi-trash3 me-1"></i> Delete
        </button>
    </form>
</div>
            </div>
        </div>

 <!-- Enhanced Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal<?php echo $announcement['id']; ?>" tabindex="-1" aria-labelledby="editAnnouncementModalLabel<?php echo $announcement['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content dark-modal">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header border-bottom">
          <h5 class="modal-title text-dark" id="editAnnouncementModalLabel<?php echo $announcement['id']; ?>">
            <i class="bi bi-pencil-square me-2"></i> Edit Announcement
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">

          <div class="mb-3">
            <label class="form-label text-dark"><i class="bi bi-type me-1"></i> Title</label>
            <input type="text" name="title" class="form-control dark-input" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark"><i class="bi bi-card-text me-1"></i> Content</label>
            <textarea name="content" class="form-control dark-input" rows="4" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark"><i class="bi bi-image me-1"></i> Image</label>
            <input type="file" name="image" class="form-control dark-input">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($announcement['image']); ?>">
            <?php if ($announcement['image']): ?>
              <img src="<?php echo htmlspecialchars($announcement['image']); ?>" alt="Current Image" class="mt-2 rounded shadow" style="max-width: 150px;">
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark"><i class="bi bi-toggle-on me-1"></i> Status</label>
            <select name="status" class="form-select dark-input">
              <option value="published" <?php echo $announcement['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
              <option value="draft" <?php echo $announcement['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
            </select>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Cancel
          </button>
          <button type="submit" name="update_announcement" class="btn btn-success">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
    <?php endforeach; ?>
</div>


<!-- Enhanced Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content dark-modal">
      <div class="modal-header border-bottom">
        <h5 class="modal-title text-dark" id="addAnnouncementModalLabel">
          <i class="bi bi-plus-circle me-2"></i> Add Announcement
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="title" class="form-label text-dark">
              <i class="bi bi-type me-1"></i> Title
            </label>
            <input type="text" class="form-control dark-input" id="title" name="title" required>
          </div>
          <div class="mb-3">
            <label for="content" class="form-label text-dark">
              <i class="bi bi-card-text me-1"></i> Content
            </label>
            <textarea class="form-control dark-input" id="content" name="content" rows="4" required></textarea>
          </div>
          <div class="mb-3">
            <label for="image" class="form-label text-dark">
              <i class="bi bi-image me-1"></i> Image
            </label>
            <input type="file" class="form-control dark-input" id="image" name="image">
          </div>
          <div class="mb-3">
            <label for="status" class="form-label text-dark">
              <i class="bi bi-toggle-on me-1"></i> Status
            </label>
            <select class="form-select dark-input" id="status" name="status" required>
              <option value="published">Published</option>
              <option value="draft">Draft</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-outline-dark me-2" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
            <button type="submit" name="add_announcement" class="btn btn-success">
              <i class="bi bi-plus-lg me-1"></i> Add Announcement
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Toast Notification Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $_SESSION['toast'] ?? ''; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php if (isset($_SESSION['toast'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        toastr.success("<?= $_SESSION['toast']; ?>");
    });
</script>
<?php unset($_SESSION['toast']); endif; ?>

</body>
</html>
