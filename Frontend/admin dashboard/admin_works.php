<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
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

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}
// add work
if (isset($_POST['add_work'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $work_datetime = $_POST['work_datetime'];
    $location = $_POST['location']; // New field
    $requirements = $_POST['requirements']; // New field
    $image = $_FILES['image']['name'];
    $target_dir = "../images/";
    $target_file = $target_dir . basename($image);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $query = "INSERT INTO works (title, description, image_path, work_datetime, location, requirements) 
                  VALUES ('$title', '$description', '$target_file', '$work_datetime', '$location', '$requirements')";
        mysqli_query($conn, $query);
    }
}

// edit work
if (isset($_POST['edit_work'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $work_datetime = $_POST['work_datetime'];
    $location = $_POST['location']; // New field
    $requirements = $_POST['requirements']; // New field
    $image = $_FILES['image']['name'];
    $target_dir = "../images/";
    $target_file = $target_dir . basename($image);

    if ($image) {
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        $query = "UPDATE works 
                  SET title = '$title', description = '$description', image_path = '$target_file', work_datetime = '$work_datetime', location = '$location', requirements = '$requirements' 
                  WHERE id = $id";
    } else {
        $query = "UPDATE works 
                  SET title = '$title', description = '$description', work_datetime = '$work_datetime', location = '$location', requirements = '$requirements' 
                  WHERE id = $id";
    }

    mysqli_query($conn, $query);
}


// Handle Delete Work
if (isset($_POST['delete_work'])) {
    $id = $_POST['work_id'];
    $query = "DELETE FROM works WHERE id = $id";
    mysqli_query($conn, $query);
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
    <link rel="stylesheet" href="../CSS/admin css/works.css">
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
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme" onclick="console.log('Logout link clicked');">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="content" style="margin-left: 270px; padding: 20px;">
    <h2>Works</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addWorkModal">Add Work</button>
    <div class="row">
    <?php while ($row = mysqli_fetch_assoc($works)): ?>
    <div class="col-md-4 mb-3">
        <div class="card">
            <img src="<?php echo $row['image_path']; ?>" class="card-img-top" alt="Work Image">
            <div class="card-body">
                <h5 class="card-title"><?php echo $row['title']; ?></h5>
                <p class="card-text" style="color: black;"><?php echo $row['description']; ?></p>
                <!-- Edit Button -->
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editWorkModal" 
                    data-id="<?php echo $row['id']; ?>" 
                    data-title="<?php echo $row['title']; ?>" 
                    data-description="<?php echo $row['description']; ?>" 
                    data-datetime="<?php echo $row['work_datetime']; ?>" 
                    data-image="<?php echo $row['image_path']; ?>">Edit</button>

                <!-- Delete Button -->
                <form method="POST" class="d-inline">
                    <input type="hidden" name="work_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="delete_work" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Add Work Modal -->
<div class="modal fade" id="addWorkModal" tabindex="-1" aria-labelledby="addWorkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWorkModalLabel">Add Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="text" name="title" class="form-control" id="title" placeholder="Enter work title" required>
                        <label for="title">Title</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="description" class="form-control" id="description" placeholder="Enter work description" style="height: 100px;" required></textarea>
                        <label for="description">Description</label>
                    </div>
                    <div class="mb-3">
    <label for="location" class="form-label">Location</label>
    <input type="text" name="location" id="location" class="form-control" placeholder="Enter location" required>
</div>
<div class="mb-3">
    <label for="requirements" class="form-label">Requirements</label>
    <textarea name="requirements" id="requirements" class="form-control" placeholder="Enter requirements" style="height: 100px;" required></textarea>
</div>

                    <div class="mb-3">
                        <label for="work_datetime" class="form-label">Date and Time</label>
                        <input type="datetime-local" name="work_datetime" id="work_datetime" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Image</label>
                        <div class="custom-file-upload">
                            <input type="file" name="image" id="image" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_work" class="btn btn-primary">Add Work</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Work Modal -->
<div class="modal fade" id="editWorkModal" tabindex="-1" aria-labelledby="editWorkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWorkModalLabel">Edit Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="text" name="title" class="form-control" id="edit-title" placeholder="Enter work title" required>
                        <label for="edit-title">Title</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="description" class="form-control" id="edit-description" placeholder="Enter work description" style="height: 100px;" required></textarea>
                        <label for="edit-description">Description</label>
                    </div>
                    <div class="mb-3">
    <label for="edit-location" class="form-label">Location</label>
    <input type="text" name="location" id="edit-location" class="form-control" placeholder="Enter location" required>
</div>
<div class="mb-3">
    <label for="edit-requirements" class="form-label">Requirements</label>
    <textarea name="requirements" id="edit-requirements" class="form-control" placeholder="Enter requirements" style="height: 100px;" required></textarea>
</div>

                    <div class="mb-3">
                        <label for="edit-work_datetime" class="form-label">Date and Time</label>
                        <input type="datetime-local" name="work_datetime" id="edit-work_datetime" class="form-control" required>
                    </div>
                    
                    <!-- Custom Image Upload Section (Matching Your Design) -->
                    <div class="mb-3">
                        <label for="edit-image" class="form-label">Upload Image</label>
                        <div class="custom-file-upload">
                            <input type="file" name="image" id="edit-image" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-current-image" class="form-label">Current Image</label><br>
                        <img src="" alt="Current Image" id="edit-current-image" style="max-width: 100%; height: auto;">
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" id="edit-id">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_work" class="btn btn-primary">Save Changes</button>
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
