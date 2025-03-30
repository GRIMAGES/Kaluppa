<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
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
    </style>
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

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
        <!-- New Card for Inquiries -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#inquiriesModal">
                <div class="card-body">
                    <i class="fas fa-question-circle card-icon"></i>
                    <h5 class="card-title" style="color: white;">Inquiries</h5>
                    <p class="card-text">Submit your questions or concerns.</p>
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

<!-- Inquiries Modal -->
<div class="modal fade" id="inquiriesModal" tabindex="-1" aria-labelledby="inquiriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiriesModalLabel">Inquiries</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="inquirySubject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="inquirySubject" required>
                    </div>
                    <div class="mb-3">
                        <label for="inquiryMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="inquiryMessage" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Inquiry</button>
                </form>
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
                url: '/Kaluppa/Backend/get_user_details.php', // Backend endpoint to fetch user details
                method: 'GET',
                dataType: 'json', // Ensure the response is parsed as JSON
                success: function(response) {
                    console.log(response); // Log the response for debugging
                    if (response.success) {
                        $('#fullName').val(response.full_name);
                        $('#email').val(response.email);
                    } else {
                        alert('Failed to fetch user details: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error); // Log AJAX errors
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
                url: '/Kaluppa/Backend/submit_request.php', // Backend endpoint to handle form submission
                method: 'POST',
                data: formData,
                dataType: 'json', // Ensure the response is parsed as JSON
                success: function(response) {
                    console.log(response); // Log the response for debugging
                    if (response.success) {
                        alert(response.message); // Display the success message
                        $('#requestDocumentsModal').modal('hide');
                        $('#requestForm')[0].reset();
                    } else {
                        alert('Failed to submit request: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error); // Log AJAX errors
                    alert('An error occurred while submitting the request.');
                }
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
