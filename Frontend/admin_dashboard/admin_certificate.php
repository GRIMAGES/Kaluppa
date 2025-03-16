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
    <title>Certificate Management | Admin Panel</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body {
            background-color: #f9f9f9;
        }
        .container-box {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 50px;
        }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
            color: #333;
        }
        .form-group label {
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- 🔸 Import Certificate Template Section -->
<div class="container-box">
    <h2 class="section-title">Import Certificate Template</h2>
    <form method="post" action="../../Backend/admin_controller/import_template.php" enctype="multipart/form-data">
        <div class="form-group mb-3">
            <label for="template_file">Upload Template File</label>
            <input type="file" class="form-control" name="template_file" id="template_file" accept=".jpg,.jpeg,.png,.pdf" onchange="previewTemplate(event)" required>
            <small class="text-muted">Supported formats: JPG, PNG, or PDF</small>
        </div>

        <div class="form-group mb-4" id="template_preview_container" style="display: none;">
            <label>Template Preview</label>
            <div id="template_preview"></div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-success px-4">Import Template</button>
        </div>
    </form>
</div>

<!-- 🔸 Generate Certificates Section -->
<div class="container-box">
    <h2 class="section-title">Generate Certificates for Completed Students</h2>
    <form method="post" action="../../Backend/admin_controller/generate_certificate.php" target="_blank">
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
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-3 d-none" id="volunteerField">
            <label for="work_id">Select Volunteer Work</label>
            <select class="form-control" name="work_id" id="work_id">
                <option value="">-- Select Work --</option>
                <?php foreach ($works as $work): ?>
                    <option value="<?= $work['id'] ?>"><?= htmlspecialchars($work['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-3 d-none" id="documentField">
            <label for="document_details">Document Details</label>
            <input type="text" class="form-control" name="document_details" id="document_details"
                   placeholder="Enter document request details" maxlength="255">
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary px-4">Generate Certificates</button>
        </div>
    </form>
</div>

<script>
    // Show/hide fields based on selected certificate type
    const certType = document.getElementById('certificate_type');
    const scholarshipField = document.getElementById('scholarshipField');
    const volunteerField = document.getElementById('volunteerField');
    const documentField = document.getElementById('documentField');

    function toggleFields() {
        const value = certType.value;
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
    }

    certType.addEventListener('change', toggleFields);
    window.addEventListener('DOMContentLoaded', toggleFields);

    // Template preview logic
    function previewTemplate(event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById('template_preview_container');
        const preview = document.getElementById('template_preview');
        preview.innerHTML = '';

        if (file) {
            previewContainer.style.display = 'block';
            const fileURL = URL.createObjectURL(file);
            const fileType = file.type;

            if (fileType === 'application/pdf') {
                preview.innerHTML = `<embed src="${fileURL}" type="application/pdf" width="100%" height="400px" style="border:1px solid #ccc; border-radius: 8px;" />`;
            } else if (fileType.startsWith('image/')) {
                preview.innerHTML = `<img src="${fileURL}" alt="Template Preview" style="max-width: 100%; border:1px solid #ccc; border-radius: 8px;" />`;
            } else {
                preview.innerHTML = `<p class="text-danger">Unsupported file format. Only JPG, PNG, or PDF are allowed.</p>`;
            }
        } else {
            previewContainer.style.display = 'none';
        }
    }
</script>

</body>
</html>
