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
    <title>Certificate Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container mt-4">
    <h2>Generate Certificate</h2>
    <form action="../../Backend/admin_controller/generate_certificate.php" method="POST">
        <div class="mb-3">
            <label for="certificateType" class="form-label">Select Certificate Type</label>
            <select name="certificate_type" id="certificateType" class="form-select" required onchange="toggleCertificateOptions()">
                <option value="">-- Select Type --</option>
                <option value="scholarship">Scholarship</option>
                <option value="volunteer">Volunteer</option>
                <option value="request_documents">Request Documents</option>
            </select>
        </div>

        <!-- Scholarship Courses Dropdown -->
        <div class="mb-3" id="scholarshipOptions" style="display:none;">
            <label for="course" class="form-label">Select Course</label>
            <select name="course_id" class="form-select">
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Volunteer Work Dropdown -->
        <div class="mb-3" id="volunteerOptions" style="display:none;">
            <label for="work" class="form-label">Select Volunteer Work</label>
            <select name="work_id" class="form-select">
                <option value="">-- Select Work --</option>
                <?php foreach ($works as $work): ?>
                    <option value="<?= $work['id'] ?>"><?= htmlspecialchars($work['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Request Document Info -->
        <div class="mb-3" id="requestOptions" style="display:none;">
            <label for="doc_details" class="form-label">Document Title</label>
            <input type="text" name="document_details" class="form-control" placeholder="e.g., Certificate of Participation" />
        </div>

        <button type="submit" class="btn btn-primary">Generate Certificate</button>
    </form>
</div>

<script>
    function toggleCertificateOptions() {
        var type = document.getElementById('certificateType').value;
        document.getElementById('scholarshipOptions').style.display = (type === 'scholarship') ? 'block' : 'none';
        document.getElementById('volunteerOptions').style.display = (type === 'volunteer') ? 'block' : 'none';
        document.getElementById('requestOptions').style.display = (type === 'request_documents') ? 'block' : 'none';
    }
</script>
</body>
</html>
