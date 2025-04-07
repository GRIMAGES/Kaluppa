<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php

if (!function_exists('insertLog')) {
    error_log("insertLog function is not defined. Please check log_helper.php.");
    die("Error: insertLog function is missing. Contact the administrator.");
}

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the reports page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
}


// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

$adminEmail = $_SESSION['email'] ?? ''; // Initialize adminEmail from session data
// Fetch all chat messages grouped by user
$chatStmt = $conn->prepare("
    SELECT u.id AS user_id, u.first_name, u.last_name, c.sender, c.text, c.created_at
    FROM chat_messages c
    JOIN user u ON c.user_id = u.id
    ORDER BY c.created_at ASC
");
$chatStmt->execute();
$chatResult = $chatStmt->get_result();

$messages = [];
while ($row = $chatResult->fetch_assoc()) {
    $messages[$row['user_id']]['user'] = $row['first_name'] . ' ' . $row['last_name'];
    $messages[$row['user_id']]['messages'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head> 
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <h1>Chat with Alumni</h1>
    <div id="alert-container"></div>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    <?php foreach ($messages as $userId => $chat): ?>
        <div class="card mb-3">
            <div class="card-header">
                Chat with <?php echo htmlspecialchars($chat['user']); ?>
            </div>
            <div class="card-body">
                <div id="chat-messages-<?php echo $userId; ?>" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                    <?php foreach ($chat['messages'] as $message): ?>
                        <div><strong><?php echo htmlspecialchars($message['sender']); ?>:</strong> <?php echo htmlspecialchars($message['text']); ?></div>
                    <?php endforeach; ?>
                </div>
                <form class="chat-form" data-user-id="<?php echo $userId; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <div class="mb-3">
                        <textarea class="form-control" name="message" rows="3" placeholder="Type your message..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alertContainer = document.getElementById('alert-container');
    
    // Function to show alerts
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
    
    // Handle form submissions
    document.querySelectorAll('.chat-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = this.dataset.userId;
            const messageInput = this.querySelector('textarea[name="message"]');
            const message = messageInput.value;
            
            if (!message.trim()) return;
            
            // Create form data
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('message', message);
            
            // Send AJAX request
            fetch('/Kaluppa/Backend/send_chat_message_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new message to the chat
                    const chatMessages = document.getElementById(`chat-messages-${userId}`);
                    const newMessage = document.createElement('div');
                    newMessage.innerHTML = `<strong>Admin:</strong> ${message}`;
                    chatMessages.appendChild(newMessage);
                    
                    // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    
                    // Clear the input
                    messageInput.value = '';
                    
                    // Show success message
                    showAlert('Message sent successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to send message', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to send message. Please try again.', 'danger');
            });
        });
    });
});
</script>
</body>
</html>
