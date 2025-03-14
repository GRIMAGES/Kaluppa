<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the connection file
include('../../Backend/connection.php'); // Ensure the connection is included

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

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        $imageName = basename($image['name']);
        $imageTmp = $image['tmp_name'];
        $uploadPath = 'uploads/' . $imageName;

        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($imageTmp, $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO works (title, description, work_datetime, location, requirements, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $title, $description, $workDatetime, $location, $requirements, $imageName);
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
    $existingImage = $_POST['existing_image']; // hidden input from form
    $imagePath = $existingImage;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        $imageName = basename($image['name']);
        $imageTmp = $image['tmp_name'];
        $uploadPath = 'uploads/' . $imageName;

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

    $stmt = $conn->prepare("UPDATE works SET title=?, description=?, work_datetime=?, location=?, requirements=?, image=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("ssssssi", $title, $description, $workDatetime, $location, $requirements, $imagePath, $id);
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

// DELETE WORK
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Optional: Get image name first to delete it from folder
    $res = $conn->query("SELECT image FROM works WHERE id=$id");
    $row = $res->fetch_assoc();
    $imageName = $row['image'];
    if (file_exists("uploads/" . $imageName)) {
        unlink("uploads/" . $imageName);
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
    <link rel="stylesheet" href="../CSS/admin_css/work.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom Styling for datetime-local input */
        input[type="datetime-local"] {
            border: 2px solid #007bff;
            border-radius: 5px;
            padding: 10px;
            font-size: 1rem;
        }

        input[type="datetime-local"]:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            border-color: #0056b3;
        }
    </style>
</head>
<body>
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
<div class="content" style="margin-left: 270px; padding: 20px;">
    <h2>Works</h2>
    <button class="btn mb-3" style="background-color: black; color: white; font-weight: 600; padding: 10px 20px; border: none; border-radius: 10px; font-size: 15px; box-shadow: 0 0 10px rgba(255, 255, 255, 0.15);" data-bs-toggle="modal" data-bs-target="#addWorkModal">
    <i class="fas fa-plus"></i> Add Work
</button>

<div class="row">
    <?php while ($row = mysqli_fetch_assoc($works)): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="Work Image">
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
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="work_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_work" class="btn btn-custom-delete">
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
        <div class="modal-content bg-dark text-white" style="border-radius: 15px; border: 1px solid #444;">
        <form action="admin_works.php" method="POST" enctype="multipart/form-data">

                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="addWorkModalLabel">Add Work</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="text" name="title" class="form-control bg-dark text-white border-secondary" id="title" placeholder="Enter title" required>
                        <label for="title" class="text-light">Title</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="description" class="form-control bg-dark text-white border-secondary" id="description" placeholder="Enter description" style="height: 100px;" required></textarea>
                        <label for="description" class="text-light">Description</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="datetime-local" name="work_datetime" class="form-control bg-dark text-white border-secondary" id="work_datetime" required>
                        <label for="work_datetime" class="text-light">Work Date & Time</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="location" class="form-control bg-dark text-white border-secondary" id="location" placeholder="Location" required>
                        <label for="location" class="text-light">Location</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="requirements" class="form-control bg-dark text-white border-secondary" id="requirements" placeholder="Enter requirements" style="height: 100px;" required></textarea>
                        <label for="requirements" class="text-light">Requirements</label>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label text-light">Upload Image</label>
                        <input type="file" name="image" id="image" class="form-control bg-dark text-white border-secondary">
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
        <div class="modal-content bg-dark text-white" style="border-radius: 15px; border: 1px solid #444;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="editWorkModalLabel">Edit Work</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">

                    <div class="form-floating mb-3">
                        <input type="text" name="title" class="form-control bg-dark text-white border-secondary" id="edit-title" placeholder="Enter title" required>
                        <label for="edit-title" class="text-light">Title</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="description" class="form-control bg-dark text-white border-secondary" id="edit-description" placeholder="Enter description" style="height: 100px;" required></textarea>
                        <label for="edit-description" class="text-light">Description</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="datetime-local" name="work_datetime" class="form-control bg-dark text-white border-secondary" id="edit-datetime" required>
                        <label for="edit-datetime" class="text-light">Work Date & Time</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="location" class="form-control bg-dark text-white border-secondary" id="edit-location" placeholder="Location" required>
                        <label for="edit-location" class="text-light">Location</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="requirements" class="form-control bg-dark text-white border-secondary" id="edit-requirements" placeholder="Enter requirements" style="height: 100px;" required></textarea>
                        <label for="edit-requirements" class="text-light">Requirements</label>
                    </div>
                    <div class="mb-3">
                        <label for="edit-image" class="form-label">Change Image</label>
                        <input type="file" name="image" id="edit-image" class="form-control bg-dark text-white border-secondary">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="submit" name="edit_work" class="btn btn-outline-light px-4">Save Changes</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
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
