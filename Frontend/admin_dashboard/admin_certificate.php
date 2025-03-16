<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

// Session timeout
$timeout_duration = 1000;
if (!isset($_SESSION['email'])) {
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}

// Handle the file upload here if the form is submitted
if (isset($_POST['submit_certificate'])) {
    if (isset($_FILES['certificate_template']) && $_FILES['certificate_template']['error'] == 0) {
        // Allowed file types
        $allowed_types = ['pdf', 'png', 'jpeg', 'jpg'];
        $file_name = $_FILES['certificate_template']['name'];
        $file_tmp = $_FILES['certificate_template']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
            // Move file to upload directory
            $upload_dir = 'uploads/';
            $new_file_name = uniqid('cert_', true) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // File uploaded successfully
                echo "File uploaded successfully!";
            } else {
                echo "Error uploading the file.";
            }
        } else {
            echo "Invalid file type. Only PDF, PNG, and JPEG are allowed.";
        }
    } else {
        echo "Please upload a valid file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Certificate | Admin Panel</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="container mt-5">
    <h3>Generate Certificate</h3>
    <form action="../../admin_controller/generate_certificate.php" method="POST" id="certificateForm" enctype="multipart/form-data">
        <!-- Certificate Type -->
        <div class="form-group">
            <label for="certificate_type">Certificate Type</label>
            <select class="form-control" name="certificate_type" id="certificate_type" required>
                <option value="scholarship">Scholarship</option>
                <option value="volunteer">Volunteer</option>
                <option value="request_documents">Requested Document</option>
            </select>
        </div>

        <!-- File Upload for Template -->
        <div class="form-group">
            <label for="certificate_template">Upload Certificate Template (PDF, PNG, JPEG)</label>
            <input type="file" class="form-control-file" name="certificate_template" id="certificate_template" required>
        </div>

        <!-- Scholarship Section -->
        <div class="form-group" id="course_section" style="display:none;">
            <label for="course_id">Select Course</label>
            <select class="form-control" name="course_id" id="course_id">
                <?php
                    // Fetch courses from the database and populate options here
                    $result = $conn->query("SELECT id, name FROM courses");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                    }
                ?>
            </select>
        </div>

        <!-- Volunteer Section -->
        <div class="form-group" id="volunteer_section" style="display:none;">
            <label for="recipient_name">Recipient Name</label>
            <input type="text" class="form-control" name="recipient_name" id="recipient_name" required>
            <label for="work_id">Work Title</label>
            <select class="form-control" name="work_id" id="work_id">
                <?php
                    // Fetch volunteer works from the database
                    $result = $conn->query("SELECT id, title FROM works");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['title'] . "</option>";
                    }
                ?>
            </select>
        </div>

        <!-- Document Request Section -->
        <div class="form-group" id="document_section" style="display:none;">
            <label for="recipient_name_doc">Recipient Name</label>
            <input type="text" class="form-control" name="recipient_name" id="recipient_name_doc" required>
            <label for="document_details">Document Details</label>
            <input type="text" class="form-control" name="document_details" id="document_details" required>
        </div>

        <!-- Submit Button -->
        <div class="form-group">
            <button type="submit" class="btn btn-primary" name="submit_certificate">Generate Certificate</button>
        </div>
    </form>
</div>

<script>
    // Show and hide sections based on certificate type
    $(document).ready(function() {
        $('#certificate_type').change(function() {
            var selectedType = $(this).val();
            if (selectedType === 'scholarship') {
                $('#course_section').show();
                $('#volunteer_section').hide();
                $('#document_section').hide();
            } else if (selectedType === 'volunteer') {
                $('#volunteer_section').show();
                $('#course_section').hide();
                $('#document_section').hide();
            } else if (selectedType === 'request_documents') {
                $('#document_section').show();
                $('#course_section').hide();
                $('#volunteer_section').hide();
            }
        });

        // Trigger change to show default section
        $('#certificate_type').trigger('change');
    });
</script>

</body>
</html>
