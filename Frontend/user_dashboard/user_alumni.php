<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    echo 'Error preparing statement: ' . $conn->error;
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Combine first, middle, and last name
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
    insertLog($user['id'], 'View', 'User accessed the alumni page', 'info'); // Log user action
} else {
    echo "User not found.";
    exit();
}

// Fetch alumni data from the alumni table
$alumni_stmt = $conn->prepare("
    SELECT a.first_name, a.middle_name, a.last_name, 'Course' AS category, c.name AS details, a.status
    FROM alumni a
    JOIN courses c ON a.user_id = (SELECT user_id FROM applications WHERE course_id = c.id GROUP BY user_id)
    WHERE a.status = 'completed'
    UNION
    SELECT a.first_name, a.middle_name, a.last_name, 'Volunteer' AS category, w.title AS details, a.status
    FROM alumni a
    JOIN works w ON a.user_id = (SELECT user_id FROM volunteer_application WHERE work_id = w.id GROUP BY user_id)
    WHERE a.status = 'completed'
");
if (!$alumni_stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    echo 'Error preparing statement: ' . $conn->error;
    exit();
}
$alumni_stmt->execute();
$alumni_result = $alumni_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .alumni-section {
            margin-top: 100px;
        }
        .card {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5); /* Deeper shadow */
            transition: transform 0.2s;
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
            margin: 20px auto; /* Center the cards horizontally with margin */
            border-radius: 15px; /* Rounded corners */
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-icon {
            font-size: 100px;
            color: #ddead1;
            margin: 20px 0;
        }
        .card-title, .card-text {
            color: white;
        }
        .card-title{
            color: white;
        }
        .modal-title {
            color: white;
        }
        table.dataTable thead {
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .modal-header {
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5);
        }
        .modal-body {
            background-color: #ddead1;
        }
        .form-label {
            color: rgb(2, 61, 15);
            font-weight: bold;
        }
        .btn-primary {
            background-color: rgb(2, 61, 15);
            border: none;
        }
        .btn-primary:hover {
            background-color: rgb(26, 70, 41);
        }
        #chatMessages div {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 20px;
            max-width: 70%;
        }
        #chatMessages div strong {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        #chatMessages div.user-message {
            background-color: #d1e7dd;
            align-self: flex-end;
            text-align: right;
        }
        #chatMessages div.admin-message {
            background-color: #f8d7da;
            align-self: flex-start;
            text-align: left;
        }
    </style>
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Request submitted successfully.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Alumni Section -->
<div class="container alumni-section">
    <div class="row justify-content-center">
        <!-- Card 1 -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#requestDocumentsModal">
                <div class="card-body">
                    <i class="fas fa-file-alt card-icon"></i>
                    <h5 class="card-title" style="color: white;">Request Documents</h5>
                    <p class="card-text">Requesting Documents, Certificates and etc.</p>
                </div>
            </div>
        </div>
        <!-- Add a new card in the alumni section -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#inquiryTypeModal">
                <div class="card-body">
                    <i class="fas fa-info-circle card-icon"></i>
                    <h5 class="card-title" style="color: white;">Inquiries</h5>
                    <p class="card-text">Select an inquiry type to start chatting with the admin.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <table id="alumniTable" class="display">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alumni = $alumni_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['middle_name'] . ' ' . $alumni['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['category']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['details']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Request Documents Modal -->
<div class="modal fade" id="requestDocumentsModal" tabindex="-1" aria-labelledby="requestDocumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestDocumentsModalLabel">Request a Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <form id="requestForm">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name:</label>
                            <input type="text" class="form-control" id="fullName" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="documentType" class="form-label">Document Type:</label>
                            <select class="form-select" id="documentType" required>
                                <option value="">Select a document</option>
                                <option value="birth_certificate">Birth Certificate</option>
                                <option value="transcript_records">Transcript of Records</option>
                                <option value="certificate_completion">Certificate of Completion</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Request:</label>
                            <textarea class="form-control" id="reason" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inquiry Type Modal -->
<div class="modal fade" id="inquiryTypeModal" tabindex="-1" aria-labelledby="inquiryTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiryTypeModalLabel">Select Inquiry Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <select class="form-select" id="selectedInquiryType" required>
                    <option value="">Select Type</option>
                    <option value="general">General</option>
                    <option value="technical">Technical</option>
                    <option value="billing">Billing</option>
                    <option value="feedback">Feedback</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="startChatButton" disabled>Start Chat</button>
            </div>
        </div>
    </div>
</div>

<!-- Inquiries Modal -->
<div class="modal fade" id="inquiriesModal" tabindex="-1" aria-labelledby="inquiriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiriesModalLabel">Inquiries</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="chatContainer" style="display: flex; flex-direction: column; height: 400px; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                    <div id="chatMessages" style="flex-grow: 1; overflow-y: auto; padding: 10px; background-color: #f1f1f1;">
                        <!-- Messages will be dynamically loaded here -->
                    </div>
                    <form id="chatForm" style="display: flex; align-items: center; padding: 10px; background-color: #fff; border-top: 1px solid #ccc;">
                        <textarea class="form-control" id="chatMessage" rows="1" placeholder="Type your message..." required style="flex-grow: 1; resize: none; margin-right: 10px;"></textarea>
                        <button type="submit" class="btn btn-primary" style="flex-shrink: 0;">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#alumniTable').DataTable();

        // Fetch user details and autofill the form
        $('#requestDocumentsModal').on('show.bs.modal', function() {
            $.ajax({
                url: '/Kaluppa/Backend/get_user_details.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#fullName').val(response.full_name);
                        $('#email').val(response.email);
                    } else {
                        alert('Failed to fetch user details: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while fetching user details.');
                }
            });
        });

        // Handle form submission
        $('#requestForm').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                fullName: $('#fullName').val(),
                email: $('#email').val(),
                documentType: $('#documentType').val(),
                reason: $('#reason').val()
            };

            $.ajax({
                url: '/Kaluppa/Backend/submit_request.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#requestDocumentsModal').modal('hide');
                        $('#requestForm')[0].reset();
                        // Show toast notification
                        const toast = new bootstrap.Toast(document.getElementById('successToast'));
                        toast.show();
                    } else {
                        alert('Failed to submit request: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while submitting the request.');
                }
            });
        });

        // Ensure modal backdrop is removed properly
        $('#requestDocumentsModal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
        });

        // Check for existing conversations on page load
        checkForExistingConversations();

        // Enable the 'Start Chat' button when an inquiry type is selected
        $('#selectedInquiryType').on('change', function() {
            const inquiryType = $(this).val();
            console.log('Selected Inquiry Type:', inquiryType); // Debugging line
            $('#startChatButton').prop('disabled', !inquiryType);
        });

        // Handle 'Start Chat' button click
        $('#startChatButton').on('click', function() {
            const inquiryType = $('#selectedInquiryType').val();
            console.log('Starting chat with Inquiry Type:', inquiryType); // Debugging line
            if (inquiryType) {
                $.ajax({
                    url: '/Kaluppa/Backend/start_conversation.php', // Ensure this is the correct endpoint
                    method: 'POST',
                    data: { inquiry_type: inquiryType },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Start conversation response:', response); // Debugging line
                        if (response.success) {
                            $('#inquiryTypeModal').modal('hide');
                            $('#inquiriesModal').modal('show'); // Show the chat modal
                            $('#chatForm').show();
                            $('#chatMessage').prop('disabled', false);
                            $('#chatForm button[type="submit"]').prop('disabled', false);
                            $('#chatForm').data('inquiryType', inquiryType);
                            loadMessages(inquiryType);
                        } else {
                            alert('Failed to start conversation: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error starting conversation:', error); // Debugging line
                        alert('An error occurred while starting the conversation.');
                    }
                });
            }
        });

        // Function to check for existing conversations
        function checkForExistingConversations() {
            console.log('Checking for existing conversations...'); // Debugging line
            $.ajax({
                url: '/Kaluppa/Backend/check_existing_conversations.php',
                method: 'GET',
                data: { user_id: <?php echo $user['id']; ?> },
                dataType: 'json',
                success: function(response) {
                    console.log('Check existing conversations response:', response); // Debugging line
                    if (response.success && response.inquiry_type) {
                        console.log('Existing conversation found for Inquiry Type:', response.inquiry_type); // Debugging line
                        $('#inquiriesModal').modal('show'); // Show the chat modal
                        $('#chatForm').show();
                        $('#chatMessage').prop('disabled', false);
                        $('#chatForm button[type="submit"]').prop('disabled', false);
                        $('#chatForm').data('inquiryType', response.inquiry_type);
                        loadMessages(response.inquiry_type);
                    } else {
                        console.log('No existing conversation found.'); // Debugging line
                        $('#inquiryTypeModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking for existing conversations:', error); // Debugging line
                }
            });
        }

        // Load chat messages
        function loadMessages(inquiryType) {
            console.log('Loading messages for Inquiry Type:', inquiryType); // Debugging line
            $.ajax({
                url: '/Kaluppa/Backend/get_chat_messages.php',
                method: 'GET',
                data: { user_id: <?php echo $user['id']; ?>, inquiry_type: inquiryType },
                dataType: 'json',
                success: function(response) {
                    console.log('Server Response:', response); // Debugging line
                    if (response.success) {
                        const chatMessages = $('#chatMessages');
                        chatMessages.empty();
                        
                        if (response.messages.length === 0) {
                            chatMessages.append('<div class="text-center text-muted">No messages yet. Start the conversation!</div>');
                        } else {
                            response.messages.forEach(function(message) {
                                console.log('Appending message:', message); // Debugging line
                                // Check if the message is from the user (Alumni) or from the admin
                                const messageClass = message.sender === 'Alumni' ? 'user-message' : 'admin-message';
                                let messageElement = `<div id="message-${message.id}" class="message ${messageClass}"><strong>${message.sender}:</strong> ${message.text}</div>`;
                                
                                // Only add delete button for user's own messages
                                if (message.sender === 'Alumni') {
                                    messageElement += `<button class="btn btn-danger btn-sm delete-message" data-message-id="${message.id}">Delete</button>`;
                                }
                                
                                chatMessages.append(messageElement);
                            });
                            
                            // Scroll to the bottom of the chat
                            chatMessages.scrollTop(chatMessages[0].scrollHeight);
                        }
                    } else {
                        alert('Failed to load messages: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading messages:', error); // Debugging line
                    alert('An error occurred while loading messages.');
                }
            });
        }

        // Handle delete message button click
        $(document).on('click', '.delete-message', function() {
            const messageId = $(this).data('message-id');
            console.log('Attempting to delete message ID:', messageId); // Debugging line
            $.ajax({
                url: '/Kaluppa/Backend/delete_user_message.php',
                method: 'POST',
                data: { message_id: messageId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the message from the UI
                        $(`#message-${messageId}`).remove();
                    } else {
                        alert('Failed to delete message: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while deleting the message.');
                }
            });
        });

        // Handle chat form submission
        $('#chatForm').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            const message = $('#chatMessage').val();
            const inquiryType = $('#chatForm').data('inquiryType');

            if (message.trim() === '') {
                alert('Message cannot be empty');
                return;
            }

            console.log('Sending message:', message, 'for inquiry type:', inquiryType); // Debugging line

            $.ajax({
                url: '/Kaluppa/Backend/send_chat_message.php',
                method: 'POST',
                data: { message: message, inquiry_type: inquiryType },
                dataType: 'json',
                success: function(response) {
                    console.log('Send message response:', response); // Debugging line
                    if (response.success) {
                        $('#chatMessage').val(''); // Clear the input field
                        loadMessages(inquiryType); // Reload messages to include the new one
                    } else {
                        alert('Failed to send message: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error sending message:', error); // Debugging line
                    alert('An error occurred while sending the message.');
                }
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
