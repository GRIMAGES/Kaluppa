<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the connection file
include('../../Backend/connection.php'); // Ensure the connection is included
require_once '../../Backend/log_helper.php'; // Include log_helper.php

session_start();

// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the works page
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the works page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
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

// Initialize user name for display
$name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest';

// ADD WORK
if (isset($_POST['add_work'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $workDatetime = $_POST['work_datetime'];
    $location = $_POST['location'];
    $requirements = $_POST['requirements'];
    $status = $_POST['status'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        $imageName = basename($image['name']);
        $imageTmp = $image['tmp_name'];
        $uploadPath = '../Images/' . $imageName;

        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($imageTmp, $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO works (title, description, work_datetime, location, requirements, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $title, $description, $workDatetime, $location, $requirements, $imageName, $status);
                if ($stmt->execute()) {
                    $_SESSION['toast_success'] = "✅ Work added successfully!";
                } else {
                    $_SESSION['toast_success'] = "❌ Failed to add work: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['toast_success'] = "❌ Failed to upload image.";
            }
        } else {
            $_SESSION['toast_success'] = "❌ Invalid image file type.";
        }
    } else {
        $_SESSION['toast_success'] = "❌ Please upload an image.";
    }

    header("Location: admin_works.php");
    exit;
}

// EDIT WORK
if (isset($_POST['edit_work'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $workDatetime = $_POST['work_datetime'];
    $location = $_POST['location'];
    $requirements = $_POST['requirements'];
    $status = $_POST['status'];
    $existingImage = $_POST['existing_image']; // hidden input from form
    $imagePath = $existingImage;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        $imageName = basename($image['name']);
        $imageTmp = $image['tmp_name'];
        $uploadPath = '../Images/' . $imageName;

        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($imageTmp, $uploadPath)) {
                $imagePath = $imageName;
            } else {
                $_SESSION['toast_success'] = "❌ Failed to upload new image.";
                header("Location: admin_works.php");
                exit;
            }
        } else {
            $_SESSION['toast_success'] = "❌ Invalid image file type.";
            header("Location: admin_works.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE works SET title=?, description=?, work_datetime=?, location=?, requirements=?, image=?, status=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("sssssssi", $title, $description, $workDatetime, $location, $requirements, $imagePath, $status, $id);
        if ($stmt->execute()) {
            $_SESSION['toast_success'] = "✅ Work updated successfully!";
        } else {
            $_SESSION['toast_success'] = "❌ Error updating work: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['toast_success'] = "❌ Failed to prepare update statement.";
    }

    header("Location: admin_works.php");
    exit;
}

// DELETE WORK (using POST method)
if (isset($_POST['delete_work'])) {
    $id = $_POST['work_id'];

    // Optional: Get image name first to delete it from folder
    $res = $conn->query("SELECT image FROM works WHERE id=$id");
    $row = $res->fetch_assoc();
    $imageName = $row['image'];
    if (file_exists("../Images/" . $imageName)) {
        unlink("../Images/" . $imageName);
    }

    $stmt = $conn->prepare("DELETE FROM works WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['toast_success'] = "✅ Work deleted successfully!";
    } else {
        $_SESSION['toast_success'] = "❌ Error deleting the work.";
    }
    $stmt->close();

    header("Location: admin_works.php");
    exit;
}

// Fetch works for display (no pre-fetching)
$works = mysqli_query($conn, "SELECT * FROM works");

if (isset($_GET['id'])) {
    $workId = $_GET['id'];
    $sql = "SELECT * FROM works WHERE id = ?";
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("i", $workId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $existingImage = $row['image_path'];  // Use this in case no new image is uploaded
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Work Managers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/works.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<?php if (isset($_SESSION['toast_success'])): ?>
    <div id="toastMessage" class="alert alert-success text-center position-fixed top-0 start-50 translate-middle-x mt-3 w-50 shadow rounded" role="alert" style="z-index: 9999;">
        <?php 
            echo htmlspecialchars($_SESSION['toast_success']); 
            unset($_SESSION['toast_success']); 
        ?>
    </div>
    <script>
        setTimeout(function() {
            const toast = document.getElementById('toastMessage');
            if (toast) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }
        }, 3000);
    </script>
<?php endif; ?>



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
<div class="content" style="margin-left: 270px; padding: 20px;">
    <h2>Works</h2>
    <button class="btn mb-3" style="background-color: black; color: white; font-weight: 600; padding: 10px 20px; border: none; border-radius: 10px; font-size: 15px; box-shadow: 0 0 10px rgba(255, 255, 255, 0.15);" data-bs-toggle="modal" data-bs-target="#addWorkModal">
    <i class="fas fa-plus"></i> Add Work
</button>

<div class="row">
    <?php while ($row = mysqli_fetch_assoc($works)): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="../Images/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="Work Image">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>

                    <!-- Edit Button -->
                    <button class="btn btn-custom-edit mb-1" data-bs-toggle="modal" data-bs-target="#editWorkModal" 
                        data-id="<?php echo $row['id']; ?>" 
                        data-title="<?php echo htmlspecialchars($row['title']); ?>" 
                        data-description="<?php echo htmlspecialchars($row['description']); ?>" 
                        data-datetime="<?php echo $row['work_datetime']; ?>" 
                        data-image="<?php echo $row['image']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <!-- Delete Button -->
                    <<form method="POST" class="d-inline">
    <input type="hidden" name="work_id" value="<?php echo $row['id']; ?>">
    <button type="submit" name="delete_work" class="btn btn-custom-delete" onclick="return confirm('Are you sure you want to delete this work?');">

        <i class="fas fa-trash-alt"></i> Delete
    </button>
</form>

                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- Add Work Modal -->
<div class="modal fade" id="addWorkModal" tabindex="-1" aria-labelledby="addWorkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <form action="admin_works.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="addWorkModalLabel">Add Work</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label text-light fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" id="title" placeholder="Enter title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label text-light fw-semibold">Description</label>
                        <textarea name="description" class="form-control" id="description" rows="3" placeholder="Enter description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="work_datetime" class="form-label text-light fw-semibold">Work Date & Time</label>
                        <input type="datetime-local" name="work_datetime" class="form-control" id="work_datetime" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label text-light fw-semibold">Location</label>
                        <input type="text" name="location" class="form-control" id="location" placeholder="Enter location" required>
                    </div>
                    <div class="mb-3">
                        <label for="requirements" class="form-label text-light fw-semibold">Requirements</label>
                        <textarea name="requirements" class="form-control" id="requirements" rows="3" placeholder="Enter requirements" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label text-light fw-semibold">Status</label>
                        <select name="status" class="form-control" id="status" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label text-light fw-semibold">Upload Image</label>
                        <input type="file" name="image" id="image" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="submit" name="add_work" class="btn btn-outline-light px-4">Add Work</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Work Modal -->
<div class="modal fade" id="editWorkModal" tabindex="-1" aria-labelledby="editWorkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="editWorkModalLabel">Edit Work</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label for="edit-title" class="form-label text-light fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" id="edit-title" placeholder="Enter title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-description" class="form-label text-light fw-semibold">Description</label>
                        <textarea name="description" class="form-control" id="edit-description" rows="3" placeholder="Enter description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit-datetime" class="form-label text-light fw-semibold">Work Date & Time</label>
                        <input type="datetime-local" name="work_datetime" class="form-control" id="edit-datetime" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-location" class="form-label text-light fw-semibold">Location</label>
                        <input type="text" name="location" class="form-control" id="edit-location" placeholder="Enter location" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-requirements" class="form-label text-light fw-semibold">Requirements</label>
                        <textarea name="requirements" class="form-control" id="edit-requirements" rows="3" placeholder="Enter requirements" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit-status" class="form-label text-light fw-semibold">Status</label>
                        <select name="status" class="form-control" id="edit-status" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-image" class="form-label text-light fw-semibold">Change Image</label>
                        <input type="file" name="image" id="edit-image" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="submit" name="edit_work" class="btn btn-outline-light px-4">Save Changes</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="toastNotification" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage">Success message here</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('image').addEventListener('change', function () {
    const fileName = this.files[0] ? this.files[0].name : 'No file selected';
    const label = document.querySelector('.custom-file-upload::before');
    label.content = fileName;
});

    window.addEventListener('DOMContentLoaded', (event) => {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.map(function (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        });
    });


    // Set the minimum date/time for the input field
    const now = new Date();
    const formattedDateTime = now.toISOString().slice(0, 16);
    document.getElementById('work_datetime').min = formattedDateTime;

// Populate Edit Modal with data
const editButtons = document.querySelectorAll('[data-bs-target="#editWorkModal"]');
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-title').value = button.getAttribute('data-title');
            document.getElementById('edit-description').value = button.getAttribute('data-description');
            document.getElementById('edit-work_datetime').value = button.getAttribute('data-datetime');
        });
    });

    function showToast(message) {
    const toastEl = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
  }

</script>
</body>
</html>
