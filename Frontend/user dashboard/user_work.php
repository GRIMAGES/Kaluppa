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
    // Separate first, middle, and last name
    $firstName = $user['first_name'];
    $middleName = $user['middle_name'];
    $lastName = $user['last_name'];
    // Separate address into multiple parts
    $houseNumber = $user['house_number'];
    $street = $user['street'];
    $barangay = $user['barangay'];
    $district = $user['district'];
    $city = $user['city'];
    $region = $user['region'];
    $postalCode = $user['postal_code'];
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
<body style="background: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

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
<div class="main-content">
    <div class="work-container">
        <?php if ($workResult): ?>
            <?php while ($work = mysqli_fetch_assoc($workResult)): ?>
                <div class="work-card">
                    <img src="<?php echo htmlspecialchars($work['image_path'] ?? ''); ?>" class="work-image" alt="<?php echo htmlspecialchars($work['title'] ?? ''); ?>">
                    <div class="work-details">
                        <h3 class="work-title"><?php echo htmlspecialchars($work['title'] ?? ''); ?></h3>
                        <button class="btn btn-outline-light view-details-button" data-bs-toggle="modal" data-bs-target="#workModal<?php echo $work['id']; ?>">View Details</button>
                    </div>
                </div>
                <!-- Modal for work details -->
                <div class="modal fade" id="workModal<?php echo $work['id']; ?>" tabindex="-1" aria-labelledby="workModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="workModalLabel"><?php echo htmlspecialchars($work['title'] ?? ''); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Left Column: Work Details -->
                                    <div class="col-md-6 border-end">
                                        <h4 class="mb-3"><?php echo htmlspecialchars($work['title'] ?? ''); ?></h4>
                                        <div class="mb-2">
                                            <strong>Location:</strong>
                                            <p><?php echo htmlspecialchars($work['location'] ?? ''); ?></p>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Date:</strong>
                                            <p><?php echo htmlspecialchars($work['work_datetime'] ?? ''); ?></p>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Requirements:</strong>
                                            <p><?php echo htmlspecialchars($work['requirements'] ?? ''); ?></p>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Description:</strong>
                                            <p><?php echo htmlspecialchars($work['description'] ?? ''); ?></p>
                                        </div>
                                    </div>

                                    <!-- Right Column: Application Form -->
                                    <div class="col-md-6" style="max-height: 400px; overflow-y: auto;">
                                        <form id="applicationForm" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="work_id" value="<?php echo $work['id']; ?>">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middleName ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="house_number" class="form-label">House Number</label>
                                                <input type="text" class="form-control" id="house_number" name="house_number" value="<?php echo htmlspecialchars($houseNumber ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="street" class="form-label">Street</label>
                                                <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($street ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="barangay" class="form-label">Barangay</label>
                                                <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($barangay ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="district" class="form-label">District</label>
                                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($district ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="region" class="form-label">Region</label>
                                                <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($region ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="postal_code" class="form-label">Postal Code</label>
                                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($postalCode ?? ''); ?>" required>
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
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No volunteer works available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Right Sidebar -->
<div class="right-sidebar">
    <h4>Announcements</h4>
    <?php
    // Fetch published announcements
    $announcementQuery = "SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC";
    $announcementResult = $conn->query($announcementQuery);

    if ($announcementResult->num_rows > 0): ?>
        <?php while ($announcement = mysqli_fetch_assoc($announcementResult)): ?>
            <div class="announcement-item" onclick="showAnnouncementDetails(<?php echo $announcement['id']; ?>)">
                <span class="megaphone">📢</span>
                <h5><?php echo htmlspecialchars($announcement['title'] ?? ''); ?></h5>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No announcements available.</p>
    <?php endif; ?>
</div>

<!-- Announcement Details Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="announcementModalLabel">Announcement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="announcementDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- Success Message Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
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
    function showWorkDetails(workId) {
        var workModal = new bootstrap.Modal(document.getElementById('workModal' + workId));
        workModal.show();
    }

    function showAnnouncementDetails(announcementId) {
        fetch(`../../Backend/user controller/fetch_announcement.php?id=${announcementId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error fetching announcement details:', data.message);
                } else {
                    let imagePath = data.image || '';

                    // Remove any redundant "Frontend/uploads/" or "uploads/uploads/"
                    imagePath = imagePath.replace(/(Frontend\/uploads\/)+/, "Frontend/uploads/").replace(/(uploads\/)+/, "uploads/");

                    // Ensure final path is correct
                    let finalImagePath = imagePath.includes("Frontend/admin dashboard/uploads/")
                        ? "../../" + imagePath
                        : "../../Frontend/admin dashboard/uploads/" + imagePath.replace("Frontend/uploads/", "");

                    console.log("Final Image Path:", finalImagePath); // Debugging

                    var detailsHtml = `
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">${data.title}</h5>
                                <p class="card-text">${data.content}</p>
                                ${data.image ? `<img src="${finalImagePath}" alt="Announcement Image" class="img-fluid rounded">` : ''}
                            </div>
                        </div>
                    `;

                    document.getElementById('announcementDetails').innerHTML = detailsHtml;
                    var announcementModal = new bootstrap.Modal(document.getElementById('announcementModal'));
                    announcementModal.show();
                }
            })
            .catch(error => {
                console.error('Error fetching announcement details:', error);
            });
    }

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
