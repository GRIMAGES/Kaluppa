<?php
require_once '../../Backend/connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM events WHERE id = $id");
    $event = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $title = $_POST['eventTitle'];
    $eventTime = $_POST['eventTime'];
    $organizer = $_POST['organizerName'];
    $description = $_POST['eventDescription'];

    // Image upload handling (optional)
    if (!empty($_FILES["eventImage"]["name"])) {
        $targetDir = "uploads/";
        $imageName = basename($_FILES["eventImage"]["name"]);
        $targetFilePath = $targetDir . $imageName;
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        if (move_uploaded_file($_FILES["eventImage"]["tmp_name"], $targetFilePath)) {
            $stmt = $conn->prepare("UPDATE events SET title = ?, image = ?, event_time = ?, organizer = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $title, $imageName, $eventTime, $organizer, $description, $id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_time = ?, organizer = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $eventTime, $organizer, $description, $id);
    }

    if ($stmt->execute()) {
        header("Location: admin_event_manager.php?success=Event updated successfully");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
