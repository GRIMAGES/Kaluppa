<?php
require_once '../connection.php';
session_start();
header('Content-Type: application/json');

// Fetch events from the database
$query = "SELECT id, title AS title, image, event_time AS start, organizer, description FROM events";
$result = $conn->query($query);

$events = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

echo json_encode($events);
?>
