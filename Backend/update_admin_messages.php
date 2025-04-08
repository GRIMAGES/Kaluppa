<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once 'connection.php';
session_start();

// Set header to indicate JSON response
header('Content-Type: application/json');

// This script should only be run by an admin
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    // Get all admin messages without inquiry type
    $stmt = $conn->prepare("SELECT id, user_id FROM chat_messages WHERE sender = 'Admin' AND (inquiry_type IS NULL OR inquiry_type = '')");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updatedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $messageId = $row['id'];
        $userId = $row['user_id'];
        
        // Get the inquiry type for this user
        $inquiryStmt = $conn->prepare("SELECT inquiry_type FROM conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $inquiryStmt->bind_param("i", $userId);
        $inquiryStmt->execute();
        $inquiryResult = $inquiryStmt->get_result();
        
        if ($inquiryResult->num_rows > 0) {
            $inquiryRow = $inquiryResult->fetch_assoc();
            $inquiryType = $inquiryRow['inquiry_type'];
            
            // Update the message with the inquiry type
            $updateStmt = $conn->prepare("UPDATE chat_messages SET inquiry_type = ? WHERE id = ?");
            $updateStmt->bind_param("si", $inquiryType, $messageId);
            
            if ($updateStmt->execute()) {
                $updatedCount++;
            }
        }
    }
    
    echo json_encode(['success' => true, 'message' => "Updated $updatedCount admin messages with inquiry type"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 