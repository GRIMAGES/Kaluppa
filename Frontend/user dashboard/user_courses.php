<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email'];


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
$courseQuery = "SELECT * FROM courses";
$courseResult = mysqli_query($conn, $courseQuery);

if (!$courseResult) {
    die("Error fetching courses: " . mysqli_error($conn));
}

// Function to categorize courses
function categorizeCourses($courses) {
    $categories = [
        'upcoming' => [],
        'ongoing' => [],
        'completed' => []
    ];

    while ($course = mysqli_fetch_assoc($courses)) {
        switch ($course['status']) {
            case 'upcoming':
                $categories['upcoming'][] = $course;
                break;
            case 'ongoing':
                $categories['ongoing'][] = $course;
                break;
            case 'completed':
                $categories['completed'][] = $course;
                break;
        }
    }

    return $categories;
}

$categorizedCourses = categorizeCourses($courseResult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>

<body style="color: #C1E6BA; background-color:#C1E6BA;">

<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
<div class="main-content">
    <div class="course-container">
        <?php foreach ($categorizedCourses as $category => $courses): ?>
            <?php foreach ($courses as $course): 
                $isFull = $course['enrolled_students'] >= $course['capacity'];
                $availabilityText = $isFull ? "Full" : "Available";
                $availabilityClass = $isFull ? "full" : "available";
                $clickableClass = $isFull ? "unclickable" : "";
            ?>
            <div class="course-card <?php echo $availabilityClass . ' ' . $clickableClass; ?>" <?php echo $isFull ? '' : 'onclick="showCourseDetails(' . $course['id'] . ')"'; ?>>
                <img src="<?php echo '../images/' . htmlspecialchars($course['image']); ?>" class="course-image" alt="Course Image">
                <div class="course-details">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['name']); ?></h3>
                    <p class="course-status <?php echo $availabilityClass; ?>"><?php echo $availabilityText; ?></p>
                    <?php if (!$isFull): ?>
                        <button class="btn btn-outline-light view-details-button">View Details</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Combined Course Details and Application Modal -->
<div class="modal fade" id="courseApplicationModal" tabindex="-1" aria-labelledby="courseApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title" id="courseApplicationModalLabel">Course Details and Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column: Course Details -->
                    <div class="col-md-6 border-end">
                        <h4 class="mb-3" id="courseName"></h4>
                        <div class="mb-2">
                            <strong>Duration:</strong>
                            <span id="courseDuration"></span> hours
                        </div>
                        <div class="mb-2">
                            <strong>Description:</strong>
                            <p id="courseDescription" class="mb-0"></p>
                        </div>
                        <div class="mb-2">
                            <strong>Prerequisites:</strong>
                            <p id="coursePrerequisites" class="mb-0"></p>
                        </div>
                        <div class="mb-2">
                            <strong>Instructor:</strong>
                            <span id="courseInstructor"></span>
                        </div>
                    </div>

                    <!-- Right Column: Application Form -->
                    <div class="col-md-6" style="max-height: 400px; overflow-y: auto;">
                        <form id="applicationForm" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middleName); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="house_number" class="form-label">House Number</label>
                                <input type="text" class="form-control" id="house_number" name="house_number" value="<?php echo htmlspecialchars($houseNumber); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($street); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($barangay); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="district" class="form-label">District</label>
                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($district); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="region" class="form-label">Region</label>
                                <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($region); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($postalCode); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="document" class="form-label">Upload Document</label>
                                <input type="file" class="form-control" id="document" name="document" required>
                            </div>
                            <input type="hidden" id="course_id" name="course_id">
                            <button type="submit" class="btn btn-success w-100">Submit Application</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Application Submitted Successfully</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Your application has been submitted successfully. You will be notified once it is processed.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Right Sidebar -->
<div class="right-sidebar">
    <h4>Courses Overview</h4>
    <div class="course-category">
        <h5>Upcoming Courses</h5>
        <?php foreach ($categorizedCourses['upcoming'] as $course): ?>
            <div class="course-item upcoming">
                <?php echo htmlspecialchars($course['name']); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="course-category">
        <h5>Ongoing Courses</h5>
        <?php foreach ($categorizedCourses['ongoing'] as $course): ?>
            <div class="course-item ongoing">
                <?php echo htmlspecialchars($course['name']); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="course-category">
        <h5>Completed Courses</h5>
        <?php foreach ($categorizedCourses['completed'] as $course): ?>
            <div class="course-item completed">
                <?php echo htmlspecialchars($course['name']); ?>
            </div>
        <?php endforeach; ?>
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
function showCourseDetails(courseId) {
    fetch(`../../Backend/user controller/fetch_course.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                // Replace alert with a Bootstrap modal
                const errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
                document.getElementById("errorModalMessage").textContent = "Error loading course details. Please try again.";
                errorModal.show();
            } else {
                // Populate course details
                document.getElementById("courseName").textContent = data.name;
                document.getElementById("courseDuration").textContent = data.duration;
                document.getElementById("courseDescription").textContent = data.description;
                document.getElementById("coursePrerequisites").textContent = data.requisites;
                document.getElementById("courseInstructor").textContent = data.instructor;

                // Update hidden input with course ID for the form
                document.getElementById("course_id").value = courseId;

                // Show the combined modal
                const courseApplicationModal = new bootstrap.Modal(document.getElementById("courseApplicationModal"));
                courseApplicationModal.show();
            }
        })
        .catch(error => {
            console.error("Error fetching course details:", error);
            const errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
            document.getElementById("errorModalMessage").textContent = "An unexpected error occurred. Please try again later.";
            errorModal.show();
        });
}

// Handle application form submission
document.getElementById("applicationForm").addEventListener("submit", function (event) {
    event.preventDefault();

    // Disable the submit button to prevent multiple submissions
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = "Submitting...";

    const formData = new FormData(this);
    const errorContainer = document.getElementById("error-message"); // Assuming an error container exists

    fetch("../../Backend/user controller/submit_application.php", {
        method: "POST",
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById("successModal"));
            successModal.show();

            // Close application modal
            const applicationModal = bootstrap.Modal.getInstance(document.getElementById("applicationModal"));
            applicationModal.hide();

            // Remove the backdrop manually
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }

            // Reset form
            document.getElementById("applicationForm").reset();

            // Hide success modal after 3 seconds
            setTimeout(() => {
                successModal.hide();
            }, 3000);

            // Clear any previous error message
            if (errorContainer) {
                errorContainer.textContent = "";
                errorContainer.style.display = "none";
            }
        } else {
            if (errorContainer) {
                errorContainer.textContent = "Error submitting application. Please try again later.";
                errorContainer.style.display = "block";
            }
        }
    })
    .catch(error => {
        if (errorContainer) {
            errorContainer.textContent = "Error submitting application. Please check your connection and try again.";
            errorContainer.style.display = "block";
        }
        console.error("Form submission error:", error);
    })
    .finally(() => {
        // Re-enable the submit button
        submitButton.disabled = false;
        submitButton.textContent = "Submit Application";
    });

});

// Reset form when application modal is closed
document.getElementById("applicationModal").addEventListener("hidden.bs.modal", function () {
    // Get the modal instance
    const applicationModal = bootstrap.Modal.getInstance(document.getElementById("applicationModal"));

    // Hide the modal and clear the backdrop
    applicationModal.hide();

    // Remove the backdrop manually by removing it from the DOM
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }

    // Reset the form
    document.getElementById("applicationForm").reset();
});

// Remove backdrop after closing course details modal
document.getElementById("courseModal").addEventListener("hidden.bs.modal", function () {
    // Get the modal instance
    const courseModal = bootstrap.Modal.getInstance(document.getElementById("courseModal"));

    // Hide the modal
    courseModal.hide();

    // Remove the backdrop manually by removing it from the DOM
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
});

</script>
</body>
</html>
