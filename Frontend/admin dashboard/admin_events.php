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

// Fetch events from the database
$query = "SELECT * FROM events ORDER BY event_time DESC";
$result = $conn->query($query);

// Handle form submissions for adding new events
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addEvent'])) {
    $title = $_POST['eventTitle'];
    $eventTime = $_POST['eventTime'];
    $organizer = $_POST['organizerName'];
    $description = $_POST['eventDescription'];

    // Handle image upload
    $targetDir = "../images/";
    $imageName = basename($_FILES["eventImage"]["name"]);
    $targetFilePath = $targetDir . $imageName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Validate and move image file
    if (move_uploaded_file($_FILES["eventImage"]["tmp_name"], $targetFilePath)) {
        $stmt = $conn->prepare("INSERT INTO events (title, image, event_time, organizer, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $imageName, $eventTime, $organizer, $description);
        
        if ($stmt->execute()) {
            // Redirect to trigger the modal after event is added
            echo "<script>window.location.href='admin_events.php?success=true';</script>";
        } else {
            echo "<div class='alert alert-danger'>Error adding event: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Error uploading image.</div>";
    }
}

// Edit Event Logic
if (isset($_GET['edit_event'])) {
    $eventId = $_GET['edit_event'];
    $eventQuery = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($eventQuery);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $eventResult = $stmt->get_result();
    $eventData = $eventResult->fetch_assoc();
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editEvent'])) {
        $updatedTitle = $_POST['eventTitle'];
        $updatedEventTime = $_POST['eventTime'];
        $updatedOrganizer = $_POST['organizerName'];
        $updatedDescription = $_POST['eventDescription'];

        // Handle image upload (optional: keep old image if not updated)
        $imageName = $_FILES["eventImage"]["name"] ? basename($_FILES["eventImage"]["name"]) : $eventData['image'];
        $targetFilePath = "../images/" . $imageName;

        if ($_FILES["eventImage"]["name"]) {
            move_uploaded_file($_FILES["eventImage"]["tmp_name"], $targetFilePath);
        }

        $updateQuery = "UPDATE events SET title = ?, image = ?, event_time = ?, organizer = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssi", $updatedTitle, $imageName, $updatedEventTime, $updatedOrganizer, $updatedDescription, $eventId);
        
        if ($stmt->execute()) {
            echo "<script>window.location.href='admin_events.php?edit_success=true';</script>";
        } else {
            echo "<div class='alert alert-danger'>Error editing event: " . $stmt->error . "</div>";
        }
    }
}

// Delete Event
if (isset($_GET['delete_event'])) {
    $eventId = $_GET['delete_event'];
    $deleteQuery = "DELETE FROM events WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $eventId);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Event deleted successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error deleting event: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Event Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    <!-- Main Content -->
    <div class="content" style="margin-left: 250px; padding: 20px;">
      

        <h1>Event Manager</h1>

        <!-- Button to Trigger Add Event Modal -->
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEventModalLabel" style="color:black;">Add New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="eventTitle" class="form-label" style="color:black;">Event Title</label>
                                <input type="text" class="form-control" name="eventTitle" id="eventTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventImage" class="form-label" style="color:black;">Event Image</label>
                                <input type="file" class="form-control" name="eventImage" id="eventImage" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventTime" class="form-label" sstyle="color:black;">Event Time</label>
                                <input type="datetime-local" class="form-control" name="eventTime" id="eventTime" required>
                            </div>
                            <div class="mb-3">
                                <label for="organizerName" class="form-label" style="color:black;">Organizer Name</label>
                                <input type="text" class="form-control" name="organizerName" id="organizerName" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventDescription" class="form-label" style="color:black;">Description</label>
                                <textarea class="form-control" name="eventDescription" id="eventDescription" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="addEvent" class="btn btn-success">Add Event</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

          <!-- Event List as Cards -->
        <h2 class="mt-4">List of Events</h2>
        <div class="event-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="event-card card">
                    <img src="../images/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="Event Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?></p>
                        <a href="admin_events.php?edit_event=<?php echo $row['id']; ?>" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $row['id']; ?>">Edit</a>
                        <a href="admin_events.php?delete_event=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                    </div>
                </div>

                <!-- Edit Event Modal -->
                <div class="modal fade" id="editEventModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editEventModalLabel" style="color:black;">Edit Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="admin_events.php?edit_event=<?php echo $row['id']; ?>" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="eventTitle" class="form-label" style="color:black;">Event Title</label>
                                        <input type="text" class="form-control" name="eventTitle" id="eventTitle" value="<?php echo $row['title']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventImage" class="form-label" style="color:black;">Event Image</label>
                                        <input type="file" class="form-control" name="eventImage" id="eventImage">
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventTime" class="form-label" style="color:black;">Event Time</label>
                                        <input type="datetime-local" class="form-control" name="eventTime" id="eventTime" value="<?php echo date('Y-m-d\TH:i', strtotime($row['event_time'])); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="organizerName" class="form-label" style="color:black;">Organizer Name</label>
                                        <input type="text" class="form-control" name="organizerName" id="organizerName" value="<?php echo $row['organizer']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventDescription" class="form-label" style="color:black;">Description</label>
                                        <textarea class="form-control" name="eventDescription" id="eventDescription" rows="4" required><?php echo $row['description']; ?></textarea>
                                    </div>
                                    <button type="submit" name="editEvent" class="btn btn-success">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Success Modal -->
        <?php if (isset($_GET['edit_success'])): ?>
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="successModalLabel">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Event updated successfully!
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            </script>
        <?php endif; ?>

    </div>
    
    <!-- Success Modal (Add Event) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Event added successfully!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal (Delete Event) -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSuccessModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Event deleted successfully!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check URL parameters to show modals
        <?php if (isset($_GET['success'])): ?>
            // Show success modal for adding event
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            // Show success modal for deleting event
            var deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
            deleteSuccessModal.show();
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>