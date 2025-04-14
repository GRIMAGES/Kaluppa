<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the courses page
// Log admin's access to the settings page
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the settings page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Last activity was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data
    header("Location: /Frontend/index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}
// Initialize user name for display
$name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCourse'])) {
    // Get course details from the form
    $name = $_POST['courseName'];
    $start_date = $_POST['courseStartDate'];
    $end_date = $_POST['courseEndDate'];
    $instructor = $_POST['courseInstructor'];
    $capacity = $_POST['courseCapacity'];
    $requisites = $_POST['courseRequisites'];
    $requirements = $_POST['courseRequirements'];
    $description = $_POST['courseDescription'];
    $status = $_POST['courseStatus'];

    // Image upload handling
    $target_dir = "/opt/bitnami/apache/htdocs/Kaluppa/Frontend/Images/";
    $imageName = basename($_FILES["courseImage"]["name"]);
    $target_file = $target_dir . $imageName;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        if (move_uploaded_file($_FILES["courseImage"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO courses (name, image, start_date, end_date, instructor, capacity, requisites, requirements, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $name, $imageName, $start_date, $end_date, $instructor, $capacity, $requisites, $requirements, $description, $status);
            

            if ($stmt->execute()) {
                echo "<script>$('#successModal').modal('show');</script>";
            } else {
                echo "<script>$('#errorModal').modal('show');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>$('#errorModal').modal('show');</script>";
        }
    } else {
        echo "<script>$('#errorModal').modal('show');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editCourse'])) {
    $id = $_POST['id'];
    $name = $_POST['courseName'];
    $start_date = $_POST['courseStartDate'];
    $end_date = $_POST['courseEndDate'];
    $instructor = $_POST['courseInstructor'];
    $capacity = $_POST['courseCapacity'];
    $requisites = $_POST['courseRequisites'];
    $requirements = !empty($_POST['courseRequirements']) ? $_POST['courseRequirements'] : 'No specific requirements';
    $description = $_POST['courseDescription'];
    $status = $_POST['courseStatus'];

    if (!empty($_FILES["courseImage"]["name"])) {
        $target_dir = "/opt/bitnami/apache/htdocs/Kaluppa/Frontend/Images/";
        $imageName = basename($_FILES["courseImage"]["name"]);
        $target_file = $target_dir . $imageName;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["courseImage"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("UPDATE courses SET name = ?, image = ?, start_date = ?, end_date = ?, instructor = ?, capacity = ?, requisites = ?, requirements = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssssssssi", $name, $imageName, $start_date, $end_date, $instructor, $capacity, $requisites, $requirements, $description, $status, $id);
            } else {
                echo "Error uploading image.";
            }
        } else {
            echo "Invalid image type.";
        }
    } else {
        $stmt = $conn->prepare("UPDATE courses SET name = ?, start_date = ?, end_date = ?, instructor = ?, capacity = ?, requisites = ?, requirements = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssssssi", $name, $start_date, $end_date, $instructor, $capacity, $requisites, $requirements, $description, $status, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Course updated successfully!'; // Set success message
        header("Location: admin_courses.php"); // Redirect to refresh the page
        exit();
    } else {
        echo "<script>$('#errorModal').modal('show');</script>";
    }
}

// Archive Course
if (isset($_GET['archive_course'])) {
    $courseId = $_GET['archive_course'];
    $archiveQuery = "UPDATE courses SET status = 'archived' WHERE id = ?";
    $stmt = $conn->prepare($archiveQuery);
    $stmt->bind_param("i", $courseId);
    if ($stmt->execute()) {
        echo "<script>$('#archiveSuccessModal').modal('show');</script>";
    } else {
        echo "<script>$('#archiveErrorModal').modal('show');</script>";
    }
}

// Filter to show only archived courses
$filter = "";
if (isset($_GET['filter']) && $_GET['filter'] == 'archived') {
    $filter = "WHERE status = 'archived'";
} elseif (isset($_GET['filter']) && $_GET['filter'] == 'all') {
    $filter = "";
}

// Fetch the count of approved applications for each course
$approvedApplicationsCountSql = "
    SELECT course_id, COUNT(*) as enrolled_students
    FROM applications
    WHERE LOWER(status) = 'enrolled'
    GROUP BY course_id
";
$approvedApplicationsCountResult = $conn->query($approvedApplicationsCountSql);

if ($approvedApplicationsCountResult->num_rows > 0) {
    while ($row = $approvedApplicationsCountResult->fetch_assoc()) {
        $course_id = $row['course_id'];
        $enrolled_students = $row['enrolled_students'];

        // Update the courses table with the count of enrolled students
        $updateCourseSql = "
            UPDATE courses
            SET enrolled_students = $enrolled_students
            WHERE id = $course_id
        ";
        $conn->query($updateCourseSql);
    }
}

// Fetch courses with optional filter
$courseQuery = "SELECT * FROM courses $filter";
$result = $conn->query($courseQuery);

// Fetch courses
$course_sql = "SELECT * FROM courses";
$course_result = $conn->query($course_sql);

// Fetch approved scholarship applications
$scholarship_sql = "SELECT * FROM applications WHERE status = 'enrolled'";
$scholarship_result = $conn->query($scholarship_sql);

$approvedApplications = [];
if ($scholarship_result->num_rows > 0) {
    while ($application = $scholarship_result->fetch_assoc()) {
        $approvedApplications[$application['course_id']][] = $application;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Course Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

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
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

    <!-- Main Content -->
    <div class="content">
        <!-- Add New Course Button -->
        <h2>Manage Courses</h2>
        <button class="btn btn-primary mb-4" style="background-color: #007bff; border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#addCourseModal">Add New Course</button>
        <!-- Filter Buttons -->
        <div class="filter-buttons" style="margin-bottom: 20px;">
            <a href="admin_courses.php?filter=all" class="btn btn-info" style="background-color: #17a2b8; border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; border-radius: 12px;">Show All Courses</a>
            <a href="admin_courses.php?filter=archived" class="btn btn-info" style="background-color: #17a2b8; border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; border-radius: 12px;">Show Archived Courses</a>
        </div>

<!-- Course List with Advanced Design -->
<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($course = $result->fetch_assoc()): ?>
            <?php
                $isFull = $course['enrolled_students'] >= $course['capacity'];
                $cardClass = $isFull ? 'course-card full' : 'course-card';

                // Fetch enrolled and waitlisted students for the course
                $enrolledQuery = "SELECT first_name, last_name FROM applications WHERE course_id = ? AND status = 'Enrolled'";
                $waitlistedQuery = "SELECT first_name, last_name FROM applications WHERE course_id = ? AND status = 'Waitlisted'";

                $enrolledStmt = $conn->prepare($enrolledQuery);
                $enrolledStmt->bind_param('i', $course['id']);
                $enrolledStmt->execute();
                $enrolledResult = $enrolledStmt->get_result();

                $waitlistedStmt = $conn->prepare($waitlistedQuery);
                $waitlistedStmt->bind_param('i', $course['id']);
                $waitlistedStmt->execute();
                $waitlistedResult = $waitlistedStmt->get_result();
            ?>
            <div class="col-md-4">
                <div class="<?php echo $cardClass; ?>">
                    <div class="card-header">
                        <strong><?php echo htmlspecialchars($course['name']); ?></strong>
                    </div>
                    <div class="card-body">
                        <img src="<?php echo '../Images/' . htmlspecialchars($course['image']); ?>" alt="Course Image" class="img-fluid" />
                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor']); ?></p>
                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($course['start_date']); ?></p>
                        <p><strong>End Date:</strong> <?php echo htmlspecialchars($course['end_date']); ?></p>
                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars($course['capacity']); ?></p>
                        <p><strong>Enrolled Students:</strong> <?php echo htmlspecialchars($course['enrolled_students']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($course['status']); ?></p>
                        <p><strong>Requirements:</strong> <span style="white-space: pre-line;"><?php echo htmlspecialchars($course['requirements']); ?></span></p>

                        <!-- Button to trigger modal -->
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#courseModal<?php echo $course['id']; ?>">
                            View Students
                        </button>
                    </div>
                    <div class="actions">
                        <a href="admin_courses.php?archive_course=<?= $course['id'] ?>" class="btn btn-secondary btn-icon" style="background-color: #6c757d; border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; border-radius: 12px;"><i class="fas fa-archive"></i></a>
                        <button class="btn btn-warning btn-icon" style="background-color: #ffc107; border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; border-radius: 12px;"
                            data-bs-toggle="modal"
                            data-bs-target="#editCourseModal"
                            data-id="<?= $course['id'] ?>"
                            data-name="<?= $course['name'] ?>"
                            data-description="<?= $course['description'] ?>"
                            data-capacity="<?= $course['capacity'] ?>"
                            data-instructor="<?= $course['instructor'] ?>"
                            data-requisites="<?= $course['requisites'] ?>"
                            data-status="<?= $course['status'] ?>"
                            data-startdate="<?= $course['start_date'] ?>"
                            data-enddate="<?= $course['end_date'] ?>"
                            data-requirements="<?= $course['requirements'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal for displaying enrolled and waitlisted students -->
            <div class="modal fade" id="courseModal<?php echo $course['id']; ?>" tabindex="-1" aria-labelledby="courseModalLabel<?php echo $course['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="courseModalLabel<?php echo $course['id']; ?>" style="color:black;">
                                Students for <?php echo htmlspecialchars($course['name']); ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="color:black;">
                            <p><strong>Enrolled Students:</strong></p>
                            <ul>
                                <?php while ($enrolled = $enrolledResult->fetch_assoc()): ?>
                                    <li><?php echo htmlspecialchars($enrolled['first_name'] . ' ' . $enrolled['last_name']); ?></li>
                                <?php endwhile; ?>
                            </ul>
                            <p><strong>Waitlisted Students:</strong></p>
                            <ul>
                                <?php while ($waitlisted = $waitlistedResult->fetch_assoc()): ?>
                                    <li><?php echo htmlspecialchars($waitlisted['first_name'] . ' ' . $waitlisted['last_name']); ?></li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-center">No courses available.</p>
    <?php endif; ?>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCourseModalLabel" style="color:white;">Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="admin_courses.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="course_name" class="form-label" style="color:black;">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="courseName" required>
                    </div>
                    <div class="mb-3">
                        <label for="course_description" class="form-label" style="color:black;">Description</label>
                        <textarea class="form-control" id="course_description" name="courseDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
  <label for="courseStartDate" class="form-label">Start Date</label>
  <input type="date" class="form-control" id="courseStartDate" name="courseStartDate" required>
</div>

<div class="mb-3">
  <label for="courseEndDate" class="form-label">End Date</label>
  <input type="date" class="form-control" id="courseEndDate" name="courseEndDate" required>
</div>
                    <div class="mb-3">
                        <label for="course_capacity" class="form-label" style="color:black;">Capacity</label>
                        <input type="number" class="form-control" id="course_capacity" name="courseCapacity" required>
                    </div>
                    <div class="mb-3">
                        <label for="course_instructor" class="form-label" style="color:black;">Instructor</label>
                        <input type="text" class="form-control" id="course_instructor" name="courseInstructor" required>
                    </div>
                    <div class="mb-3">
                        <label for="courseRequirements" class="form-label" style="color:black;">Requirements</label>
                        <textarea class="form-control" id="courseRequirements" name="courseRequirements" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="course_requisites" class="form-label" style="color:black;">Requisites</label>
                        <input type="text" class="form-control" id="course_requisites" name="courseRequisites" required>
                    </div>
                    <div class="mb-3">
                        <label for="course_status" class="form-label" style="color:black;">Status</label>
                        <select class="form-select" id="course_status" name="courseStatus" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="course_image" class="form-label" style="color:black;">Course Image</label>
                        <input type="file" class="form-control" id="course_image" name="courseImage" required>
                    </div>
                    <button type="submit" name="addCourse" class="btn btn-primary">Add Course</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCourseModalLabel" style="color:white;">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Current Course Card Preview -->
                <div class="mb-4">
                    <h6 class="text-center mb-3" style="color:black;">Current Course Details</h6>
                    <div class="card">
                        <div class="card-header">
                            <strong id="preview_course_name"></strong>
                        </div>
                        <div class="card-body">
                            <img id="preview_course_image" src="" alt="Course Image" class="img-fluid mb-2" style="max-height: 150px; width: auto;" />
                            <p><strong>Instructor:</strong> <span id="preview_course_instructor"></span></p>
                            <p><strong>Start Date:</strong> <span id="preview_course_startdate"></span></p>
                            <p><strong>End Date:</strong> <span id="preview_course_enddate"></span></p>
                            <p><strong>Capacity:</strong> <span id="preview_course_capacity"></span></p>
                            <p><strong>Status:</strong> <span id="preview_course_status"></span></p>
                            <p><strong>Requirements:</strong> <span id="preview_course_requirements" style="white-space: pre-line;"></span></p>
                        </div>
                    </div>
                </div>
                
                <h6 class="text-center mb-3" style="color:black;">Edit Course Details</h6>
                <form action="admin_courses.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_course_id" name="id">
                    <div class="mb-3">
                        <label for="edit_course_name" class="form-label" style="color:black;">Course Name</label>
                        <input type="text" class="form-control" id="edit_course_name" name="courseName" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_description" class="form-label" style="color:black;">Description</label>
                        <textarea class="form-control" id="edit_course_description" name="courseDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editCourseStartDate" class="form-label" style="color:black;">Start Date</label>
                        <input type="date" class="form-control" id="editCourseStartDate" name="courseStartDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCourseEndDate" class="form-label" style="color:black;">End Date</label>
                        <input type="date" class="form-control" id="editCourseEndDate" name="courseEndDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_capacity" class="form-label" style="color:black;">Capacity</label>
                        <input type="number" class="form-control" id="edit_course_capacity" name="courseCapacity" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_instructor" class="form-label" style="color:black;">Instructor</label>
                        <input type="text" class="form-control" id="edit_course_instructor" name="courseInstructor" required>
                    </div>
                    <div class="mb-3">
                        <label for="editcourseRequirements" class="form-label" style="color:black;">Requirements</label>
                        <textarea class="form-control" id="editcourseRequirements" name="courseRequirements" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_requisites" class="form-label" style="color:black;">Requisites</label>
                        <input type="text" class="form-control" id="edit_course_requisites" name="courseRequisites" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_status" class="form-label" style="color:black;">Status</label>
                        <select class="form-select" id="edit_course_status" name="courseStatus" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_image" class="form-label" style="color:black;">Course Image</label>
                        <input type="file" class="form-control" id="edit_course_image" name="courseImage">
                    </div>
                    <button type="submit" name="editCourse" class="btn btn-primary">Update Course</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Approved Users Modal -->
<div class="modal fade" id="approvedUsersModal" tabindex="-1" aria-labelledby="approvedUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvedUsersModalLabel" style="color:white;">Enrolled Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="approvedUsersModalBody" style="color:black;">
                <!-- Approved users will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Ensure modal backdrop is removed when modal is closed
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });

    // Populate Edit Course Modal with existing course data
    const editButtons = document.querySelectorAll('.btn-warning');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
            
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');
            const capacity = this.getAttribute('data-capacity');
            const instructor = this.getAttribute('data-instructor');
            const requisites = this.getAttribute('data-requisites');
            const status = this.getAttribute('data-status');
            const startDate = this.getAttribute('data-startdate');
            const endDate = this.getAttribute('data-enddate');
            const requirements = this.getAttribute('data-requirements');
            const image = this.closest('.course-card').querySelector('img').getAttribute('src');

            // Populate modal fields with existing data
            document.getElementById('edit_course_id').value = id;
            document.getElementById('edit_course_name').value = name;
            document.getElementById('edit_course_description').value = description;
            document.getElementById('edit_course_capacity').value = capacity;
            document.getElementById('edit_course_instructor').value = instructor;
            document.getElementById('edit_course_requisites').value = requisites;
            document.getElementById('edit_course_status').value = status;
            document.getElementById('editCourseStartDate').value = startDate;
            document.getElementById('editCourseEndDate').value = endDate;
            document.getElementById('editcourseRequirements').value = requirements;
            
            // Populate preview card
            document.getElementById('preview_course_name').textContent = name;
            document.getElementById('preview_course_image').src = image;
            document.getElementById('preview_course_instructor').textContent = instructor;
            document.getElementById('preview_course_startdate').textContent = startDate;
            document.getElementById('preview_course_enddate').textContent = endDate;
            document.getElementById('preview_course_capacity').textContent = capacity;
            document.getElementById('preview_course_status').textContent = status;
            document.getElementById('preview_course_requirements').textContent = requirements;

            modal.show();
        });
    });

    function showApprovedUsers(courseId) {
    var approvedUsers = <?php echo json_encode($approvedApplications); ?>;
    var modalBody = document.getElementById('approvedUsersModalBody');
    modalBody.innerHTML = '';

    if (approvedUsers[courseId]) {
        var userList = document.createElement('ul');
        approvedUsers[courseId].forEach(function(application) {
            var listItem = document.createElement('li');
            listItem.textContent = application.full_name;
            userList.appendChild(listItem);
        });
        modalBody.appendChild(userList);
    } else {
        modalBody.textContent = 'No approved users for this course.';
    }
}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>