<?php
require_once '../Backend/connection.php';
session_start();

if (!isset($_SESSION['admin_email'])) {
    header("Location: /Admin/login.php");
    exit();
}

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
    <title>Admin Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Chat with Alumni</h1>
    <?php foreach ($messages as $userId => $chat): ?>
        <div class="card mb-3">
            <div class="card-header">
                Chat with <?php echo htmlspecialchars($chat['user']); ?>
            </div>
            <div class="card-body">
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                    <?php foreach ($chat['messages'] as $message): ?>
                        <div><strong><?php echo htmlspecialchars($message['sender']); ?>:</strong> <?php echo htmlspecialchars($message['text']); ?></div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" action="/Kaluppa/Backend/send_chat_message_admin.php">
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
</body>
</html>
