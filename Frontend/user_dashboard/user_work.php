<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
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
    $barangay = isset($user['barangay']) ? $user['barangay'] : '';
    $province = isset($user['province']) ? $user['province'] : '';
    $municipality = isset($user['municipality']) ? $user['municipality'] : '';
    insertLog($user['id'], 'View', 'User accessed the work page', 'info'); // Log user action
} else {
    echo "User not found.";
}

// Fetch data from the works table
$query = "SELECT * FROM works WHERE status != 'archived'";
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
    <link rel="stylesheet" href="../CSS/user_css/user_work.css">
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
                    <img src="<?php echo '../Images/' . htmlspecialchars($work['image']); ?>" class="work-image" alt="Work Image">
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
                                    <div class="col-md-6 border-end" style="max-height: 500px; overflow-y: auto;">
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
                                                <p style="white-space: pre-line; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($work['description'])); ?></p>
                                            </div>
                                            <div class="info-group mb-3">
                                                <strong><i class="fas fa-info-circle me-2"></i>Status:</strong>
                                                <span><?php echo htmlspecialchars($work['status']); ?></span>
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
                                                <label class="form-label">Barangay</label>
                                                <input type="text" class="form-control" name="barangay" value="<?php echo htmlspecialchars($barangay ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($province ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Municipality</label>
                                                <input type="text" class="form-control" name="municipality" value="<?php echo htmlspecialchars($municipality ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Upload Resume</label>
                                                <input type="file" class="form-control" name="resume" accept=".pdf, .doc, .docx" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Facebook Profile</label>
                                                <input type="text" class="form-control" name="facebook_profile" placeholder="Name in your Facebook account" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Days of the Week</label>
                                                <div>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Monday"> Monday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Tuesday"> Tuesday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Wednesday"> Wednesday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Thursday"> Thursday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Friday"> Friday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Saturday"> Saturday
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox" name="available_days[]" value="Sunday"> Sunday
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Hours per Week</label>
                                                <input type="number" class="form-control" name="hours_per_week" min="1" required>
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
<div class="right-sidebar">
    <h4>Works Overview</h4>
    <div class="work-category">
        <h5>Upcoming Works</h5>
        <?php
        // Fetch and display upcoming works
        $upcomingWorksQuery = "SELECT * FROM works WHERE status = 'upcoming'";
        $upcomingWorksResult = $conn->query($upcomingWorksQuery);

        if ($upcomingWorksResult->num_rows > 0):
            while ($work = $upcomingWorksResult->fetch_assoc()): ?>
                <div class="work-item upcoming">
                    <?php echo htmlspecialchars($work['title']); ?>
                </div>
            <?php endwhile;
        else: ?>
            <p>No upcoming works available.</p>
        <?php endif; ?>
    </div>
    <div class="work-category">
        <h5>Ongoing Works</h5>
        <?php
        // Fetch and display ongoing works
        $ongoingWorksQuery = "SELECT * FROM works WHERE status = 'ongoing'";
        $ongoingWorksResult = $conn->query($ongoingWorksQuery);

        if ($ongoingWorksResult->num_rows > 0):
            while ($work = $ongoingWorksResult->fetch_assoc()): ?>
                <div class="work-item ongoing">
                    <?php echo htmlspecialchars($work['title']); ?>
                </div>
            <?php endwhile;
        else: ?>
            <p>No ongoing works available.</p>
        <?php endif; ?>
    </div>
    <div class="work-category">
        <h5>Completed Works</h5>
        <?php
        // Fetch and display completed works
        $completedWorksQuery = "SELECT * FROM works WHERE status = 'completed'";
        $completedWorksResult = $conn->query($completedWorksQuery);

        if ($completedWorksResult->num_rows > 0):
            while ($work = $completedWorksResult->fetch_assoc()): ?>
                <div class="work-item completed">
                    <?php echo htmlspecialchars($work['title']); ?>
                </div>
            <?php endwhile;
        else: ?>
            <p>No completed works available.</p>
        <?php endif; ?>
    </div>
    <!-- Legend -->
    <div class="legend mt-4">
        <h5>Legend</h5>
        <div class="d-flex align-items-center mb-2">
            <div class="legend-color" style="width: 20px; height: 20px; background-color: #f39c12; margin-right: 10px;"></div>
            <span>Upcoming</span>
        </div>
        <div class="d-flex align-items-center mb-2">
            <div class="legend-color" style="width: 20px; height: 20px; background-color: #3498db; margin-right: 10px;"></div>
            <span>Ongoing</span>
        </div>
        <div class="d-flex align-items-center">
            <div class="legend-color" style="width: 20px; height: 20px; background-color: #2ecc71; margin-right: 10px;"></div>
            <span>Completed</span>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
    <div id="ajaxToast" class="toast text-white bg-success" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ajaxToastMessage">Application submitted successfully!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll('.application-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('/Kaluppa/Backend/user_controller/submit_applications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.text();
                })
                .then(data => {
                    // Show success toast
                    const toastEl = document.getElementById('ajaxToast');
                    const toastBody = document.getElementById('ajaxToastMessage');
                    toastEl.classList.remove('bg-danger');
                    toastEl.classList.add('bg-success');
                    toastBody.textContent = data;
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();

                    // Reset form and close modal
                    this.reset();
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    modal.hide();
                })
                .catch(error => {
                    // Show error toast
                    const toastEl = document.getElementById('ajaxToast');
                    const toastBody = document.getElementById('ajaxToastMessage');
                    toastEl.classList.remove('bg-success');
                    toastEl.classList.add('bg-danger');
                    toastBody.textContent = error.message;
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                });
            });
        });

        const checkboxes = document.querySelectorAll(".day-checkbox");

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener("change", () => {
                // Removed code for updating `availableDaysInput` as it is no longer needed.
            });
        });
    });
</script>
</body>
</html>
