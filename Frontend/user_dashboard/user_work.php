<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Log errors to a file
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

// Redirect if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
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
    <style>
        .modal-body p {
            margin-bottom: 1rem; /* Add spacing between paragraphs */
        }
    </style>
</head>
<body style="background: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="feedbackToast" class="toast align-items-center text-white <?= isset($_SESSION['success']) ? 'bg-success' : 'bg-danger' ?>" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= $_SESSION['success'] ?? $_SESSION['error']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const toastEl = document.getElementById('feedbackToast');
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    </script>
    <?php unset($_SESSION['success'], $_SESSION['error']); ?>
<?php endif; ?>

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

<div class="main-content">
    <div class="work-container">
        <?php if ($workResult): ?>
            <?php while ($work = mysqli_fetch_assoc($workResult)): ?>
                <div class="work-card">
                    <img src="<?php echo '../admin_dashboard/uploads/' . htmlspecialchars($work['image']); ?>" class="work-image" alt="Work Image">
                    <div class="work-details">
                        <h3 class="work-title"><?php echo htmlspecialchars($work['title']); ?></h3>
                        <button class="btn btn-outline-light view-details-button" data-bs-toggle="modal" data-bs-target="#workModal<?php echo $work['id']; ?>">
                            View Details
                        </button>
                    </div>
                </div>

                <!-- ✅ MODAL (inside the loop) -->
                <div class="modal fade" id="workModal<?php echo $work['id']; ?>" tabindex="-1" aria-labelledby="workModalLabel<?php echo $work['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content shadow-lg rounded-4 custom-modal">
                            <div class="modal-header">
                                <h5 class="modal-title" id="workModalLabel<?php echo $work['id']; ?>"><?php echo htmlspecialchars($work['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Left Column: Work Details -->
                                    <div class="col-md-6 border-end">
                                        <div class="work-info-section">
                                            <h4 class="mb-3"><?php echo htmlspecialchars($work['title']); ?></h4>
                                            <div class="info-group mb-3">
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-map-marker-alt me-2"></i>Location:</strong>
                                                    <span><?php echo htmlspecialchars($work['location']); ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-calendar-alt me-2"></i>Date:</strong>
                                                    <span><?php echo htmlspecialchars($work['work_datetime']); ?></span>
                                                </div>
                                            </div>
                                            <div class="info-group mb-3">
                                                <strong><i class="fas fa-list-ul me-2"></i>Requirements:</strong>
                                                <p><?php echo htmlspecialchars($work['requirements']); ?></p>
                                            </div>
                                            <div class="info-group mb-3">
                                                <strong><i class="fas fa-align-left me-2"></i>Description:</strong>
                                                <p><?php echo htmlspecialchars($work['description']); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Application Form -->
                                    <div class="col-md-6" style="max-height: 500px; overflow-y: auto;">
                                        <form id="applicationForm<?php echo $work['id']; ?>" class="application-form" enctype="multipart/form-data">
                                            <input type="hidden" name="work_id" value="<?php echo $work['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($middleName ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">House Number</label>
                                                <input type="text" class="form-control" name="house_number" value="<?php echo htmlspecialchars($houseNumber ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Street</label>
                                                <input type="text" class="form-control" name="street" value="<?php echo htmlspecialchars($street ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Barangay</label>
                                                <input type="text" class="form-control" name="barangay" value="<?php echo htmlspecialchars($barangay ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">District</label>
                                                <input type="text" class="form-control" name="district" value="<?php echo htmlspecialchars($district ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Region</label>
                                                <input type="text" class="form-control" name="region" value="<?php echo htmlspecialchars($region ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Postal Code</label>
                                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($postalCode ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Upload Resume</label>
                                                <input type="file" class="form-control" name="resume" accept=".pdf, .doc, .docx" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END MODAL -->
            <?php endwhile; ?>
        <?php else: ?>
            <p>No volunteer works available at the moment.</p>
        <?php endif; ?>
    </div>
</div>



<!-- Right Sidebar -->
<div class="right-sidebar p-3 bg-light border rounded shadow-sm">
    <h4 class="text-primary">Announcements</h4>
    <?php
    // Fetch published announcements
    $announcementQuery = "SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC";
    $announcementResult = $conn->query($announcementQuery);

    if ($announcementResult->num_rows > 0): ?>
        <?php while ($announcement = mysqli_fetch_assoc($announcementResult)): ?>
            <div class="announcement-item p-2 mb-2 border-bottom" onclick="showAnnouncementDetails(<?php echo $announcement['id']; ?>)">
                <span class="megaphone">📢</span>
                <h5 class="text-dark"><?php echo htmlspecialchars($announcement['title'] ?? ''); ?></h5>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No announcements available.</p>
    <?php endif; ?>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
    <div id="ajaxToast" class="toast text-white bg-success" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ajaxToastMessage">Application submitted successfully!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<!-- Announcement Details Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41)); color: white;">
                <h5 class="modal-title" id="announcementModalLabel">Announcement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-header-overlay"></div>
            </div>
            <div class="modal-body p-4 bg-light border rounded shadow-sm">
                <div id="announcementDetails" class="p-3 bg-light border rounded shadow-sm"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <div class="toast align-items-center text-white <?php echo $successMessage ? 'bg-success' : 'bg-danger'; ?> border-0"
         role="alert" aria-live="assertive" aria-atomic="true"
         data-bs-delay="3000" data-bs-autohide="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $successMessage ?: $errorMessage; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    function showWorkDetails(workId) {
        var workModal = new bootstrap.Modal(document.getElementById('workModal' + workId));
        workModal.show();
    }

    function showAnnouncementDetails(announcementId) {
        fetch(`../../Backend/user_controller/fetch_announcement.php?id=${announcementId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error fetching announcement details:', data.message);
                } else {
                    let imagePath = data.image || '';

                    // Clean image path
                    imagePath = imagePath.replace(/(Frontend\/uploads\/)+/, "Frontend/uploads/").replace(/(uploads\/)+/, "uploads/");
                    let finalImagePath = imagePath.includes("Frontend/admin_dashboard/uploads/")
                        ? "../../" + imagePath
                        : "../../Frontend/admin_dashboard/uploads/" + imagePath.replace("Frontend/uploads/", "");

                    var detailsHtml = `
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">${data.title}</h5>
                                <p class="card-text">${data.content.replace(/\n/g, '<br>')}</p>
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

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll('.application-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('/Kaluppa/Backend/user_controller/submit_applications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Show toast
                    const toastEl = document.getElementById('ajaxToast');
                    const toastBody = document.getElementById('ajaxToastMessage');
                    toastEl.classList.remove('bg-danger');
                    toastEl.classList.add('bg-success');
                    toastBody.textContent = "Application submitted successfully!";
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();

                    // Reset form and close modal
                    this.reset();
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    modal.hide();

                    // Remove backdrop manually (optional cleanup)
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                })
                .catch(error => {
                    console.error('Submission error:', error);
                    const toastEl = document.getElementById('ajaxToast');
                    const toastBody = document.getElementById('ajaxToastMessage');
                    toastEl.classList.remove('bg-success');
                    toastEl.classList.add('bg-danger');
                    toastBody.textContent = "Failed to submit application. Please try again.";
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                });
            });
        });
    });

   
</script>
</body>
</html>
