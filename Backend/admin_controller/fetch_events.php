<?php
require_once '../../Backend/connection.php';

$query = "SELECT * FROM events ORDER BY event_time ASC";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($event = mysqli_fetch_assoc($result)) {
        $modalId = 'eventModal' . htmlspecialchars($event['id']);
        echo '<div class="col-md-4">';
        echo '<div class="card event-card shadow-lg border-0" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">';
        if (!empty($event['image'])) {
            echo '<img src="../images/' . htmlspecialchars($event['image']) . '" alt="Event Image" class="card-img-top event-img">';
        } else {
            echo '<img src="default-event-image.jpg" alt="Default Event Image" class="card-img-top event-img">';
        }
        echo '<div class="card-body">';
        echo '<h5 class="card-title text-success">' . htmlspecialchars($event['title']) . '</h5>';
        echo '<p class="card-text text-muted"><i class="far fa-clock me-1"></i>' . htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))) . '</p>';
        echo '<p class="card-text text-truncate">' . htmlspecialchars($event['description']) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">';
        echo '<div class="modal-dialog modal-lg">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title" id="' . $modalId . 'Label">' . htmlspecialchars($event['title']) . '</h5>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<img src="../images/' . (empty($event['image']) ? 'default-event-image.jpg' : htmlspecialchars($event['image'])) . '" alt="Event Image" class="img-fluid mb-3">';
        echo '<p><strong>Event Time:</strong> ' . htmlspecialchars(date("F j, Y, g:i a", strtotime($event['event_time']))) . '</p>';
        echo '<p><strong>Organizer:</strong> ' . htmlspecialchars($event['organizer']) . '</p>';
        echo '<p>' . htmlspecialchars($event['description']) . '</p>';
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
