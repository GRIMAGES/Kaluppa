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
            $_SESSION['toast_success'] = "Event added successfully!";
header("Location: admin_events.php");
exit();

            ;
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
            $_SESSION['toast_success'] = "Event updated successfully!";
            header("Location: admin_events.php");
            exit();
            
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
        $_SESSION['toast_success'] = "Event deleted successfully!";
        header("Location: admin_events.php");
        exit();
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
    <link rel="stylesheet" href="../CSS/admin_css/event.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
                <<a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>
    <!-- Main Content -->
    <div class="content" style="margin-left: 250px; padding: 20px;">
      

        <h1>Event Manager</h1>

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="eventTitle" class="form-label">Event Title</label>
                                <input type="text" class="form-control" name="eventTitle" id="eventTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventImage" class="form-label" >Event Image</label>
                                <input type="file" class="form-control" name="eventImage" id="eventImage" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventTime" class="form-label" >Event Time</label>
                                <input type="datetime-local" class="form-control" name="eventTime" id="eventTime" required>
                            </div>
                            <div class="mb-3">
                                <label for="organizerName" class="form-label" >Organizer Name</label>
                                <input type="text" class="form-control" name="organizerName" id="organizerName" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventDescription" class="form-label">Description</label>
                                <textarea class="form-control" name="eventDescription" id="eventDescription" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="addEvent" class="btn btn-success">Add Event</button>
                        </form>
                    </div>
                </div>
            </div>
        </div> 

        <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="text-white">Event Listings</h2>
    <a href="#" class="btn btn-add-event text-white" data-bs-toggle="modal" data-bs-target="#addEventModal">
        <i class="fas fa-plus-circle me-1"></i> Add Event
    </a>
</div>


    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card event-card h-100 border-0">
                        <div class="card-img-top-wrapper">
                            <img src="../Images/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="Event Image">
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                <p class="card-text mb-1"><i class="fas fa-calendar-alt me-1"></i> <?php echo date("F j, Y, g:i a", strtotime($row['event_time'])); ?></p>
                                <p class="card-text"><i class="fas fa-user me-1"></i> <strong>Organizer:</strong> <?php echo htmlspecialchars($row['organizer']); ?></p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                            </div>
                            <div class="mt-3 d-flex justify-content-between">
                                <a href="#" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $row['id']; ?>"><i class="fas fa-edit me-1"></i>Edit</a>
                                <a href="admin_events.php?delete_event=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?');"><i class="fas fa-trash-alt me-1"></i>Delete</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Event Modal (Move this inside the loop) -->
                <div class="modal fade" id="editEventModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editEventModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editEventModalLabel<?php echo $row['id']; ?>" style="color:black;">Edit Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="admin_events.php?edit_event=<?php echo $row['id']; ?>" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="eventTitle<?php echo $row['id']; ?>" class="form-label" style="color:black;">Event Title</label>
                                        <input type="text" class="form-control" name="eventTitle" id="eventTitle<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventImage<?php echo $row['id']; ?>" class="form-label" style="color:black;">Event Image</label>
                                        <input type="file" class="form-control" name="eventImage" id="eventImage<?php echo $row['id']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventTime<?php echo $row['id']; ?>" class="form-label" style="color:black;">Event Time</label>
                                        <input type="datetime-local" class="form-control" name="eventTime" id="eventTime<?php echo $row['id']; ?>" value="<?php echo date('Y-m-d\TH:i', strtotime($row['event_time'])); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="organizerName<?php echo $row['id']; ?>" class="form-label" style="color:black;">Organizer Name</label>
                                        <input type="text" class="form-control" name="organizerName" id="organizerName<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['organizer']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventDescription<?php echo $row['id']; ?>" class="form-label" style="color:black;">Description</label>
                                        <textarea class="form-control" name="eventDescription" id="eventDescription<?php echo $row['id']; ?>" rows="4" required><?php echo htmlspecialchars($row['description']); ?></textarea>
                                    </div>
                                    <button type="submit" name="editEvent" class="btn btn-success">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p>No events found.</p>
        <?php endif; ?>
    </div>
</div>



            
           <!-- Bootstrap Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="toastMessage" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php
                if (isset($_SESSION['toast_success'])) {
                    echo $_SESSION['toast_success'];
                    unset($_SESSION['toast_success']);
                }
                ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var toastEl = document.getElementById('toastMessage');
        if (toastEl && toastEl.querySelector('.toast-body').innerText.trim() !== "") {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>