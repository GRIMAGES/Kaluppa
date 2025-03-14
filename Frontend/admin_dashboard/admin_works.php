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

// Handle form submissions for adding new work applications
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_work'])) {
    // Get work details from the form
    $title = $_POST['workTitle'];
    $description = $_POST['workDescription'];
    $work_datetime = $_POST['workDatetime'];
    $location = $_POST['workLocation'];
    $requirements = $_POST['workRequirements'];

    // Handle image upload
    $target_dir = "/opt/bitnami/apache/htdocs/Kaluppa/Frontend/images/"; // Path to your image folder
    $imageName = basename($_FILES["workImage"]["name"]);
    $target_file = $target_dir . $imageName;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Initialize message variable
    $message = "";

    // Check if the file is a valid image
    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Try to upload the file
        if (move_uploaded_file($_FILES["workImage"]["tmp_name"], $target_file)) {
            // Proceed with database insertion
            $stmt = $conn->prepare("INSERT INTO works (title, description, image, work_datetime, location, requirements) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $title, $imageName, $description, $work_datetime, $location, $requirements);

            // Execute the query
            if ($stmt->execute()) {
                $message = "Work application added successfully!";
                echo "<script>$('#successModal').modal('show');</script>";
            } else {
                $message = "Error adding work application: " . $stmt->error;
                echo "<script>$('#errorModal').modal('show');</script>";
            }
            $stmt->close();
        } else {
            $message = "Error uploading the image.";
            echo "<script>$('#errorModal').modal('show');</script>";
        }
    } else {
        $message = "Invalid image format. Only JPG, JPEG, PNG, or GIF allowed.";
        echo "<script>$('#errorModal').modal('show');</script>";
    }
    echo $message;
}

// Handle work updates (editing work with or without image change)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_work'])) {
    $id = $_POST['id']; // Work ID to update
    $title = $_POST['workTitle'];
    $description = $_POST['workDescription'];
    $work_datetime = $_POST['workDatetime'];
    $location = $_POST['workLocation'];
    $requirements = $_POST['workRequirements'];

    // Check if an image is uploaded
    if (!empty($_FILES["workImage"]["name"])) {
        // Image upload logic (if new image uploaded)
        $target_dir = "/opt/bitnami/apache/htdocs/Kaluppa/Frontend/Images/";
        $imageName = basename($_FILES["workImage"]["name"]);
        $target_file = $target_dir . $imageName;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check file type
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES["workImage"]["tmp_name"], $target_file)) {
                // Update the work record with the new image
                $stmt = $conn->prepare("UPDATE works SET title = ?, image = ?, description = ?, work_datetime = ?, location = ?, requirements = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $title, $imageName, $description, $work_datetime, $location, $requirements, $id);
            } else {
                echo "Error uploading image.";
            }
        } else {
            echo "Invalid image type.";
        }
    } else {
        // If no new image, update work without changing the image
        $stmt = $conn->prepare("UPDATE works SET title = ?, description = ?, work_datetime = ?, location = ?, requirements = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $description, $work_datetime, $location, $requirements, $id);
    }

    if ($stmt->execute()) {
        echo "<script>$('#successModal').modal('show');</script>";
    } else {
        echo "<script>$('#errorModal').modal('show');</script>";
    }
}

// Handle work deletion
if (isset($_POST['delete_work'])) {
    $workId = $_POST['work_id'];
    $deleteQuery = "DELETE FROM works WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $workId);
    if ($stmt->execute()) {
        echo "<script>$('#deleteSuccessModal').modal('show');</script>";
    } else {
        echo "<script>$('#deleteErrorModal').modal('show');</script>";
    }
}

// Fetch works for display (no pre-fetching)
$works = mysqli_query($conn, "SELECT * FROM works");

// Fetch specific work details when editing
if (isset($_GET['edit_work'])) {
    $workId = $_GET['edit_work'];
    $workQuery = "SELECT * FROM works WHERE id = ?";
    $stmt = $conn->prepare($workQuery);
    $stmt->bind_param("i", $workId);
    $stmt->execute();
    $workResult = $stmt->get_result();
    $workData = $workResult->fetch_assoc();
    $stmt->close();
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

<?php if (isset($_SESSION['toast_success'])): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo $_SESSION['toast_success']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>

    <div class="row">
    <?php while ($row = mysqli_fetch_assoc($works)): ?>
    <div class="col-md-4 mb-4">
        <div class="card">
            <img src="<?php echo '../Images/' . htmlspecialchars($work['image_path']); ?>" class="card-img-top" alt="Work Image">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                
                <!-- Edit Button -->
<button class="btn btn-custom-edit mb-1" data-bs-toggle="modal" data-bs-target="#editWorkModal" 
    data-id="<?php echo $row['id']; ?>" 
    data-title="<?php echo htmlspecialchars($row['title']); ?>" 
    data-description="<?php echo htmlspecialchars($row['description']); ?>" 
    data-datetime="<?php echo $row['work_datetime']; ?>" 
    data-image="<?php echo $row['image_path']; ?>">
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

<!-- Add Work Modal -->
<div class="modal fade" id="addWorkModal" tabindex="-1" aria-labelledby="addWorkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white" style="border-radius: 15px; border: 1px solid #444;">
            <form method="POST" enctype="multipart/form-data">
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
</script>
</body>
</html>
