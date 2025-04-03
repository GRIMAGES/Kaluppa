<?php
require_once 'connection.php';

function insertLog($userId, $action, $description, $logType, $ipAddress, $userAgent) {
    global $conn;

    // Clear any unprocessed results from the connection
    while ($conn->more_results() && $conn->next_result()) {
        $conn->store_result();
    }

    $query = "INSERT INTO logs (user_id, action, description, ip_address, user_agent, log_type, timestamp) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }

    $stmt->bind_param("isssss", $userId, $action, $description, $ipAddress, $userAgent, $logType);

    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}
?>
