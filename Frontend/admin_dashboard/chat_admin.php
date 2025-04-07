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
<div class="container-fluid mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card dark-card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Conversations</h5>
                </div>
                <div class="list-group list-group-flush dark-list" id="conversation-list">
                    <?php foreach ($messages as $userId => $chat): ?>
                        <a href="#" class="list-group-item list-group-item-action conversation-item" data-user-id="<?php echo $userId; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($chat['user']); ?></h6>
                                <small class="text-muted"><?php echo count($chat['messages']); ?> messages</small>
                            </div>
                            <small class="text-muted">
                                <?php 
                                $lastMessage = end($chat['messages']);
                                echo htmlspecialchars(substr($lastMessage['text'], 0, 30)) . (strlen($lastMessage['text']) > 30 ? '...' : '');
                                ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card dark-card">
                <div class="card-header bg-dark text-white" id="active-chat-header">
                    <h5 class="mb-0">Select a conversation</h5>
                </div>
                <div class="card-body p-0">
                    <div id="chat-container" class="p-3" style="height: 400px; overflow-y: auto;">
                        <div class="text-center text-muted mt-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Select a conversation from the list to start chatting</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form id="message-form" class="d-none">
                            <input type="hidden" name="user_id" id="active-user-id">
                            <div class="input-group">
                                <textarea class="form-control dark-input" name="message" rows="1" placeholder="Type your message..." required></textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alertContainer = document.getElementById('alert-container');
    const chatContainer = document.getElementById('chat-container');
    const messageForm = document.getElementById('message-form');
    const activeUserIdInput = document.getElementById('active-user-id');
    const activeChatHeader = document.getElementById('active-chat-header');
    
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
    
    // Function to load chat messages
    function loadChatMessages(userId, userName) {
        // Clear current chat
        chatContainer.innerHTML = '';
        
        // Update header
        activeChatHeader.innerHTML = `<h5 class="mb-0">Chat with ${userName}</h5>`;
        
        // Show message form
        messageForm.classList.remove('d-none');
        activeUserIdInput.value = userId;
        
        // Fetch messages for this user
        fetch(`/Kaluppa/Backend/get_chat_messages.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${message.sender === 'Admin' ? 'sent' : 'received'} mb-2`;
                        
                        const messageContent = document.createElement('div');
                        messageContent.className = 'message-content';
                        messageContent.innerHTML = `<strong>${message.sender}:</strong> ${message.text}`;
                        
                        const deleteButton = document.createElement('button');
                        deleteButton.className = 'btn btn-danger btn-sm delete-message';
                        deleteButton.setAttribute('data-message-id', message.id);
                        deleteButton.textContent = 'Delete';
                        
                        messageDiv.appendChild(messageContent);
                        messageDiv.appendChild(deleteButton);
                        chatContainer.appendChild(messageDiv);
                    });
                    
                    // Scroll to bottom
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                } else {
                    showAlert('Failed to load messages', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to load messages', 'danger');
            });
    }
    
    // Handle conversation item clicks
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            document.querySelectorAll('.conversation-item').forEach(i => {
                i.classList.remove('active');
            });
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Get user ID and name
            const userId = this.dataset.userId;
            const userName = this.querySelector('h6').textContent;
            
            // Load chat messages
            loadChatMessages(userId, userName);
        });
    });
    
    // Handle message form submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const userId = activeUserIdInput.value;
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
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message sent mb-2';
                
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                messageContent.innerHTML = `<strong>Admin:</strong> ${message}`;
                
                const deleteButton = document.createElement('button');
                deleteButton.className = 'btn btn-danger btn-sm delete-message';
                deleteButton.setAttribute('data-message-id', data.message_id);
                deleteButton.textContent = 'Delete';
                
                messageDiv.appendChild(messageContent);
                messageDiv.appendChild(deleteButton);
                chatContainer.appendChild(messageDiv);
                
                // Scroll to bottom
                chatContainer.scrollTop = chatContainer.scrollHeight;
                
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
    
    // Auto-resize textarea
    const textarea = messageForm.querySelector('textarea');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>

<style>
/* Dark theme styles */
body {
    background-color: #ffffff;
    color: #333333;
}

.dark-card {
    background-color: #ffffff;
    border-color: #dddddd;
}

.dark-card .card-header {
    border-bottom-color: #dddddd;
    background-color: #f8f9fa;
    color: #333333;
}

.dark-list {
    background-color: #ffffff;
}

.dark-list .list-group-item {
    background-color: #ffffff;
    border-color: #dddddd;
    color: #333333;
}

.dark-list .list-group-item:hover {
    background-color: #f8f9fa;
}

.dark-list .list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #ffffff;
}

.dark-input {
    background-color: #ffffff;
    border-color: #dddddd;
    color: #333333;
}

.dark-input:focus {
    background-color: #ffffff;
    border-color: #0d6efd;
    color: #333333;
}

#chat-container {
    background-color: #ffffff;
}

.message {
    margin-bottom: 10px;
    clear: both;
}

.message.sent {
    text-align: right;
}

.message.received {
    text-align: left;
}

.message-content {
    padding: 8px 12px;
    border-radius: 18px;
    max-width: 70%;
    display: inline-block;
    word-break: break-word;
}

.message.sent .message-content {
    background-color: #0d6efd;
    color: white;
    float: right;
    clear: both;
}

.message.received .message-content {
    background-color: #f8f9fa;
    color: #333333;
    float: left;
    clear: both;
}

#message-form textarea {
    resize: none;
    overflow-y: hidden;
}

/* Adjust for sidebar */
@media (min-width: 768px) {
    .container-fluid {
        margin-left: 250px; /* Adjust based on your sidebar width */
        width: calc(100% - 250px);
    }
}
</style>
</body>
</html>
