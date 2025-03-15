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
    // Use $user data as needed
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
<body style="color: #C1E6BA; background-color:#C1E6BA;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-black"> <!-- Add custom-modal class -->
            <div class="modal-header bg-theme text-white"> <!-- Add bg-theme and text-white classes -->
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
    <div class="row g-4">
        <?php
        // Query to fetch events from the database
        $query = "SELECT * FROM events ORDER BY event_time ASC";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
                // Generate a unique ID for the modal
                $modalId = 'eventModal' . htmlspecialchars($event['id']);
                
                // Card structure
                echo '<div class="col-md-4">';
                echo '<div class="card event-card shadow-lg border-0" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">';
                if (!empty($event['image'])) {
                    echo '<img src="../Images/' . htmlspecialchars($event['image']) . '" alt="Event Image" class="card-img-top event-img">';
                } else {
                    echo '<img src="default-event-image.jpg" alt="Default Event Image" class="card-img-top event-img">';
                }
                echo '<div class="card-body">';
                echo '<h5 class="card-title" >' . htmlspecialchars($event['title']) . '</h5>';
                echo '<p class="card-text "><i class="far fa-clock me-1"></i>' . htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))) . '</p>';
                echo '<p class="card-text ">' . htmlspecialchars($event['description']) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                // Modal structure
                echo '<div class="modal fade custom-modal" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">'; // Add custom-modal class
                echo '<div class="modal-dialog modal-lg modal-dialog-centered">';
                echo '<div class="modal-content">';
                echo '<div class="modal-header bg-theme text-white">'; // Add bg-theme and text-white classes
                echo '<h5 class="modal-title" id="' . $modalId . 'Label">' . htmlspecialchars($event['title']) . '</h5>';
                echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                echo '</div>';
                echo '<div class="modal-body text-center">';
                echo '<img src="../images/' . (empty($event['image']) ? 'default-event-image.jpg' : htmlspecialchars($event['image'])) . '" alt="Event Image" class="img-fluid mb-3 rounded shadow" style="width: 100%; height: 250px; object-fit: cover;">'; // Consistent image size
                echo '<p><strong>Event Time:</strong> ' . htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))) . '</p>';
                echo '<p><strong>Organizer:</strong> ' . htmlspecialchars($event['organizer']) . '</p>';
                echo '<p><strong>Description:</strong> ' . htmlspecialchars($event['description']) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p class="text-center">No upcoming events at this time.</p>';
        }

        // Close the database connection
        mysqli_close($conn);
        ?>
    </div>
</div>

<script src="JS/user_js.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
