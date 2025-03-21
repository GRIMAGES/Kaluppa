<?php
require_once '../../Backend/connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];
// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Combine first, middle, and last name
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
} else {
    echo "User not found.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body style="background: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="event-container">
        <?php
        // Query to fetch events from the database
        $query = "SELECT * FROM events ORDER BY event_time ASC";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
                // Generate a unique ID for the modal
                $modalId = 'eventModal' . htmlspecialchars($event['id']);
                ?>
                <div class="event-card">
                    <img src="<?php echo '../Images/' . htmlspecialchars($event['image']); ?>" class="event-image" alt="Event Image">
                    <div class="event-details">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="event-time"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))); ?></p>
                        <button class="btn btn-outline-light view-details-button" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
                            View Details
                        </button>
                    </div>
                </div>

                <!-- Event Details Modal -->
                <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content shadow-lg rounded-4 custom-modal">
                            <div class="modal-header bg-theme text-white">
                                <h5 class="modal-title" id="<?php echo $modalId; ?>Label"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Left Column: Event Details -->
                                    <div class="col-md-6 border-end">
                                        <div class="event-info-section">
                                            <h4 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <div class="info-group mb-3">
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-calendar-alt me-2"></i>Event Time:</strong>
                                                    <span><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))); ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-user-tie me-2"></i>Organizer:</strong>
                                                    <span><?php echo htmlspecialchars($event['organizer']); ?></span>
                                                </div>
                                            </div>
                                            <div class="info-group mb-3">
                                                <strong><i class="fas fa-align-left me-2"></i>Description:</strong>
                                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Event Image -->
                                    <div class="col-md-6">
                                        <img src="<?php echo '../Images/' . htmlspecialchars($event['image']); ?>" class="img-fluid rounded shadow" alt="Event Image">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p class="text-center">No upcoming events at this time.</p>';
        }

        // Close the database connection
        mysqli_close($conn);
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
