<?php
require_once '../../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if ($event_id <= 0 || empty($name) || empty($email)) {
        echo "Invalid registration data.";
        exit;
    }

    // Optional: Check for duplicate registration
    $stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ?");
    $stmt->bind_param("is", $event_id, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "You have already registered for this event.";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Insert registration
    $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, name, email, registered_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $event_id, $name, $email);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Registration failed. Please try again.";
    }
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
