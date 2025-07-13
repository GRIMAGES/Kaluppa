<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once '../connection.php';

// Create the event_registrations table if it doesn't exist
$tableCreationQuery = "CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_email (event_id, email),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
)";

if ($conn->query($tableCreationQuery) !== TRUE) {
    echo "Error creating table: " . $conn->error;
}

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
        $response = [
            "success" => false,
            "message" => "You have already registered for this event."
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Insert registration
    $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, name, email, registered_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $event_id, $name, $email);

    if ($stmt->execute()) {
        $response = [
            "success" => true,
            "message" => "Registration successful!"
        ];
    } else {
        $response = [
            "success" => false,
            "message" => "Registration failed. Please try again."
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    $stmt->close();
    $conn->close();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['event_id'])) {
    // Return registrations for a specific event as JSON
    $event_id = intval($_GET['event_id']);
    $stmt = $conn->prepare("SELECT name, email, registered_at FROM event_registrations WHERE event_id = ? ORDER BY registered_at DESC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($registrations);
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
