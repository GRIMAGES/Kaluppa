<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

// Redirect if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email']; // Assign session email to a variable

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Combine first, middle, and last name
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
    // Use $user data as needed
} else {
    echo "User not found.";
}

// Fetch data from the works table
$query = "SELECT * FROM works";
$workResult = $conn->query($query);

if (!$workResult) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Work</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_works.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body style="color: #C1E6BA; background-color:#C1E6BA;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<!-- Logout Confirmation Modal -->

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content container mt-4">
    <div class="row gy-4">
        <?php if ($workResult): ?>
            <?php $counter = 0; ?>
            <?php while ($work = mysqli_fetch_assoc($workResult)): ?>
                <?php if ($counter < 2): // Limit to 2 cards ?>
                    <div class="col-md-6 mb-4">
                        <div class="card volunteer-card">
                            <img src="<?php echo $work['image_path']; ?>" class="volunteer-image" alt="<?php echo $work['title']; ?>">
                            <div class="card-body">
                                <h5 class="volunteer-title" style="color:white;"><?php echo $work['title']; ?></h5>
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#workModal<?php echo $work['id']; ?>">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php $counter++; ?>
                <?php endif; ?>
                <!-- Modal for work details -->
                <div class="modal fade" id="workModal<?php echo $work['id']; ?>" tabindex="-1" aria-labelledby="workModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" style="color:white;" id="workModalLabel"><?php echo $work['title']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="volunteer-details">
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($work['location']); ?></p>
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($work['work_datetime']); ?></p>
                                    <p><strong>Requirements:</strong> <?php echo htmlspecialchars($work['requirements']); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($work['description']); ?></p>
                                </div>
                                <div class="application-form">
                                    <form id="applicationForm" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="work_id" value="<?php echo $work['id']; ?>">
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="resume" class="form-label">Upload Resume</label>
                                            <input type="file" class="form-control" id="resume" name="resume" accept=".pdf, .doc, .docx" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No volunteer works available at the moment.</p>
        <?php endif; ?>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- Success Message Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel" style="color:white;">Success</h5>
            </div>
            <div class="modal-body" style="color:black;">
                Your application has been submitted successfully!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript for form submission
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = new FormData(this);

        // Send AJAX request to submit the form
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../Backend/user controller/submit_applications.php', true);

        xhr.onload = function() {
            if (xhr.status === 200) {
                // Show success modal
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                document.getElementById('applicationForm').reset(); // Reset form fields
            } else {
                console.error('Form submission failed');
            }
        };

        xhr.send(formData);
    });
</script>
</body>
</html>
