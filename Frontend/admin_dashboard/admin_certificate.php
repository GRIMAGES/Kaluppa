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

// Fetch certificate templates
$templates = [];
$templateQuery = "SELECT id, template_name, file_path FROM certificate_templates";
$templateResult = $conn->query($templateQuery);
while ($row = $templateResult->fetch_assoc()) {
    $templates[] = $row;
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

<?php include 'sidebar.php'; ?>

<div class="certificate-form">
    <h2 class="form-title">Generate Certificates for Completed Students</h2>

    <!-- GENERATE CERTIFICATE FORM -->
    <form method="post" action="../../Backend/admin_controller/generate_certificate.php" target="_blank" enctype="multipart/form-data">
        <!-- Select Existing Template -->
        <div class="form-group mb-3">
            <label for="template_id">Select Certificate Template</label>
            <select class="form-control" name="template_id" id="template_id" required onchange="showTemplatePreview()">
                <option value="">-- Choose Template --</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?= $template['id'] ?>" data-file="<?= $template['file_path'] ?>">

                        <?= htmlspecialchars($template['template_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Preview Container -->
        <div class="form-group mb-4" id="template_preview_container" style="display: none;">
            <label>Template Preview</label>
            <div id="template_preview"></div>
        </div>

        <!-- Certificate Type -->
        <div class="form-group mb-3">
            <label for="certificate_type">Certificate Type</label>
            <select class="form-control" name="certificate_type" id="certificate_type" required>
                <option value="">-- Select Certificate Type --</option>
                <option value="scholarship">Scholarship Certificate</option>
                <option value="volunteer">Volunteer Certificate</option>
                <option value="request_documents">Requested Document Certificate</option>
            </select>
        </div>

        <!-- Scholarship Field -->
        <div class="form-group mb-3 d-none" id="scholarshipField">
            <label for="course_id">Select Scholarship Course</label>
            <select class="form-control" name="course_id" id="course_id">
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Volunteer Field -->
        <div class="form-group mb-3 d-none" id="volunteerField">
            <label for="work_id">Select Volunteer Work</label>
            <select class="form-control" name="work_id" id="work_id">
                <option value="">-- Select Work --</option>
                <?php foreach ($works as $work): ?>
                    <option value="<?= $work['id'] ?>"><?= htmlspecialchars($work['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Requested Document Field -->
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

<!-- UPLOAD CERTIFICATE TEMPLATE SECTION -->
<div class="certificate-form mt-4">
    <h2 class="form-title">Upload New Certificate Template</h2>
    <form action="../../Backend/admin_controller/upload_template.php" method="POST" enctype="multipart/form-data">
        <div class="form-group mb-3">
            <label for="template_name">Template Name</label>
            <input type="text" class="form-control" id="template_name" name="template_name" placeholder="Enter template name" required>
        </div>

        <div class="form-group mb-3">
            <label for="template_file">Select Template File (JPG, PNG, or PDF)</label>
            <input type="file" class="form-control" id="template_file" name="template_file" accept=".jpg, .jpeg, .png, .pdf" required>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-success px-4">Upload Template</button>
        </div>
    </form>
</div>


<script>
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

    // Preview Selected Template
    function showTemplatePreview() {
        const select = document.getElementById('template_id');
        const selectedOption = select.options[select.selectedIndex];
        const filePath = selectedOption.getAttribute('data-file');

        const previewContainer = document.getElementById('template_preview_container');
        const preview = document.getElementById('template_preview');
        preview.innerHTML = '';

        if (filePath) {
            previewContainer.style.display = 'block';

            const ext = filePath.split('.').pop().toLowerCase();
            const fullPath = '../../uploads/certificate_templates/' + filePath;

            if (ext === 'pdf') {
                preview.innerHTML = `<embed src="${fullPath}" type="application/pdf" width="100%" height="400px" style="border:1px solid #ccc; border-radius: 8px;" />`;
            } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
                preview.innerHTML = `<img src="${fullPath}" alt="Template Preview" style="max-width: 100%; border:1px solid #ccc; border-radius: 8px;" />`;
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
