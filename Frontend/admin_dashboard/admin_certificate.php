<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

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

// Fetch courses
$courses = [];
$courseQuery = "SELECT id, name FROM courses";
$courseResult = $conn->query($courseQuery);
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch volunteer works
$works = [];
$worksQuery = "SELECT id, title FROM works";
$worksResult = $conn->query($worksQuery);
while ($row = $worksResult->fetch_assoc()) {
    $works[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Certificate | Admin Panel</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body {
            background-color: #f9f9f9;
        }
        .certificate-form {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-title {
            font-size: 26px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
            color: #333;
        }
        .form-group label {
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="certificate-form">
    <h2 class="form-title">Generate Certificate</h2>
    <form method="post" action="../../Backend/admin_controller/generate_certificate.php" target="_blank">

        <div class="form-group mb-3">
            <label for="recipient_name">Recipient Name</label>
            <input type="text" class="form-control" name="recipient_name" id="recipient_name" required placeholder="Enter recipient full name">
        </div>

        <div class="form-group mb-3">
            <label for="certificate_type">Certificate Type</label>
            <select class="form-control" name="certificate_type" id="certificate_type" required>
                <option value="">-- Select Certificate Type --</option>
                <option value="scholarship">Scholarship Certificate</option>
                <option value="volunteer">Volunteer Certificate</option>
                <option value="request_documents">Requested Document Certificate</option>
            </select>
        </div>

        <div class="form-group mb-3 d-none" id="scholarshipField">
            <label for="course_id">Select Scholarship Course</label>
            <select class="form-control" name="course_id" id="course_id">
                <option value="">-- Select Course --</option>
                <?php
                $courseQuery = $conn->query("SELECT id, name FROM courses");
                while ($course = $courseQuery->fetch_assoc()) {
                    echo "<option value='{$course['id']}'>{$course['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group mb-3 d-none" id="volunteerField">
            <label for="work_id">Select Volunteer Work</label>
            <select class="form-control" name="work_id" id="work_id">
                <option value="">-- Select Work --</option>
                <?php
                $workQuery = $conn->query("SELECT id, title FROM works");
                while ($work = $workQuery->fetch_assoc()) {
                    echo "<option value='{$work['id']}'>{$work['title']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group mb-3 d-none" id="documentField">
            <label for="document_details">Document Details</label>
            <input type="text" class="form-control" name="document_details" id="document_details" placeholder="Enter document request details">
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary px-4">Generate Certificate</button>
        </div>
    </form>
</div>

<script>
    const certType = document.getElementById('certificate_type');
    const scholarshipField = document.getElementById('scholarshipField');
    const volunteerField = document.getElementById('volunteerField');
    const documentField = document.getElementById('documentField');

    certType.addEventListener('change', function () {
        const value = this.value;

        scholarshipField.classList.add('d-none');
        volunteerField.classList.add('d-none');
        documentField.classList.add('d-none');

        if (value === 'scholarship') {
            scholarshipField.classList.remove('d-none');
        } else if (value === 'volunteer') {
            volunteerField.classList.remove('d-none');
        } else if (value === 'request_documents') {
            documentField.classList.remove('d-none');
        }
    });
</script>

</body>
</html>