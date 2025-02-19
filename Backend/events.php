<?php
require_once 'connection.php';

$query = "SELECT title, start, end FROM events";
$result = $conn->query($query);

$events = array();
while ($row = $result->fetch_assoc()) {
    $events[] = array(
        'title' => $row['title'],
        'start' => $row['start'],
        'end' => $row['end']
    );
}

echo json_encode($events);
?>
