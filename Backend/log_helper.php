<?php
require_once 'connection.php';

function insertLog($userId, $action, $description, $logType) {
    global $conn;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $query = "INSERT INTO logs (user_id, action, description, ip_address, user_agent, log_type, timestamp) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssss', $userId, $action, $description, $ipAddress, $userAgent, $logType);
    $stmt->execute();
    $stmt->close();
}
?>
