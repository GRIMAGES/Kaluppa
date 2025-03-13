<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

$timeout_duration = 1000;
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

$adminEmail = $_SESSION['email'] ?? '';
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? '';
$stmt->close();

$templateFiles = array_filter(glob("templates/*.{png,pdf}", GLOB_BRACE));
$uploadMessage = $_SESSION['uploadMessage'] ?? '';
unset($_SESSION['uploadMessage']);

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
// Fetch users who applied to each course
$courseApplicants = [];
$courseApplicantsQuery = "
    SELECT 
        ca.course_id,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS applicant_name,
        ca.applied_at AS application_date
    FROM applications ca
    JOIN user u ON ca.user_id = u.id
    WHERE ca.status = 'enrolled'
";

$courseApplicantsResult = $conn->query($courseApplicantsQuery);
while ($row = $courseApplicantsResult->fetch_assoc()) {
    $courseApplicants[$row['course_id']][] = $row;
}

// Fetch users who applied to each volunteer work
$workApplicants = [];

$workApplicantsQuery = "
SELECT 
    va.work_id,
    CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS applicant_name,
    va.application_date
FROM volunteer_application va
JOIN user u ON va.user_id = u.id
WHERE va.status = 'accepted'
";

$workApplicantsResult = $conn->query($workApplicantsQuery);

if ($workApplicantsResult) {
    while ($row = $workApplicantsResult->fetch_assoc()) {
        $workApplicants[$row['work_id']][] = $row;
    }
} else {
    echo "Query failed: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scholarship Certificate Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/admin_scholar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
<?php include 'admin_sidebar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Scholarship Certificate Generator</h2>

    <?php if (!empty($uploadMessage)): ?>
        <div class="alert alert-info"><?php echo $uploadMessage; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Upload New Template</div>
        <div class="card-body">
            <form action="../../Backend/admin_controller/upload_template.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="templateFile" accept=".png,.pdf" class="form-control mb-3" required>
                <button type="submit" class="btn btn-primary">Upload Template</button>
            </form>
        </div>
    </div>

    <form id="certificateForm" class="mb-5" method="POST" action="../../Backend/admin_controller/generate_certificate.php" target="_blank">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Recipient Name</label>
                <input type="text" class="form-control" name="recipientName" id="recipientName" required>
            </div>

            <div id="volunteerSection" style="display:none;">
    <div class="col-md-6">
        <label class="form-label">Select Volunteer Work</label>
        <select class="form-select" name="workName" id="workName">
            <option value="">-- Select Work --</option>
            <?php foreach ($works as $work): ?>
                <option value="<?= htmlspecialchars($work['id']) ?>"><?= htmlspecialchars($work['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

            <div id="scholarshipSection" style="display:none;">
    <div class="col-md-6">
        <label class="form-label">Select Course</label>
        <select class="form-select" name="courseName" id="courseName">
            <option value="">-- Select Course --</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= htmlspecialchars($course['id']) ?>"><?= htmlspecialchars($course['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="col-md-6">
    <label class="form-label">Select Type</label>
    <select class="form-select" id="typeSelector" onchange="handleTypeSelection()">
        <option value="">-- Select Type --</option>
        <option value="scholarship">Scholarship</option>
        <option value="volunteer">Volunteer</option>
    </select>
</div>

            <div class="col-md-6">
                <label class="form-label">Date Awarded</label>
                <input type="date" class="form-control" name="awardDate" id="awardDate" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Signed By</label>
                <input type="text" class="form-control" name="signedBy" id="signedBy" value="<?php echo htmlspecialchars($adminName); ?>" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Select Template</label>
                <select class="form-select" name="templateSelector" id="templateSelector" onchange="updateTemplatePreview()">
                    <option value="">-- Select Template --</option>
                    <?php foreach ($templateFiles as $file): ?>
                        <option value="<?php echo htmlspecialchars($file); ?>"><?php echo basename($file); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="button" class="btn btn-success mt-4" onclick="generateCertificate()">Generate Certificate</button>
    </form>

    <div id="certificatePreview" class="border shadow">
        <div class="overlay-content">
            <h3><strong>Certificate of Completion</strong></h3>
            <p>This is to certify that</p>
            <h2 id="certName"></h2>```php
<?php
// ... (rest of the code remains the same)

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

// ... (rest of the code remains the same)

// Improved code for template files
$templateFiles = array_filter(glob("templates/*.{png,pdf}", GLOB_BRACE));
$templateOptions = '';
foreach ($templateFiles as $file) {
    $templateOptions .= '<option value="' . htmlspecialchars($file) . '">' . basename($file) . '</option>';
}

// ... (rest of the code remains the same)

?>

<!-- ... (rest of the HTML code remains the same) -->

<div class="col-md-12">
    <label class="form-label">Select Template</label>
    <select class="form-select" name="templateSelector" id="templateSelector" onchange="updateTemplatePreview()">
        <option value="">-- Select Template --</option>
        <?= $templateOptions ?>
    </select>
</div>

<!-- ... (rest of the HTML code remains the same) -->

<script>
    // ... (rest of the JavaScript code remains the same)

    function updateTemplatePreview() {
        const selector = document.getElementById('templateSelector');
        const preview = document.getElementById('certificatePreview');
        const selectedTemplate = selector.value;

        if (selectedTemplate.endsWith('.png')) {
            const img = new Image();
            img.onload = function () {
                const aspectRatio = img.height / img.width;
                preview.style.height = `${preview.offsetWidth * aspectRatio}px`;
                preview.style.backgroundImage = `url('${selectedTemplate}')`;
                preview.style.display = 'block';
            };
            img.src = selectedTemplate;
        } else {
            preview.style.backgroundImage = '';
            preview.style.display = 'none';
            alert('PDF preview not supported. Proceed to print manually.');
        }
    }

    // ... (rest of the JavaScript code remains the same)
</script>
```
            <p>has successfully completed the course</p>
            <h4 id="certCourse"></h4>
            <p id="certWork"></p>
            <p>Date Awarded: <span id="certDate"></span></p>
        </div>
        <div class="signed-by">
            <p>_________________________</p>
            <p id="certSignedBy" style="font-weight: bold;"></p>
            <p>Administrator</p>
        </div>
        <div class="text-center mt-4">
            <button class="btn btn-primary" onclick="window.print()">Print Certificate</button>
        </div>
    </div>
</div>

<script>
    function updateTemplatePreview() {
        const selector = document.getElementById('templateSelector');
        const preview = document.getElementById('certificatePreview');
        const selectedTemplate = selector.value;

        if (selectedTemplate.endsWith('.png')) {
            const img = new Image();
            img.onload = function () {
                const aspectRatio = img.height / img.width;
                preview.style.height = `${preview.offsetWidth * aspectRatio}px`;
                preview.style.backgroundImage = `url('${selectedTemplate}')`;
                preview.style.display = 'block';
            };
            img.src = selectedTemplate;
        } else {
            preview.style.backgroundImage = '';
            preview.style.display = 'none';
            alert('PDF preview not supported. Proceed to print manually.');
        }
    }

    function generateCertificate() {
        const name = document.getElementById('recipientName').value;
        const course = document.getElementById('courseName').value;
        const date = document.getElementById('awardDate').value;
        const work = document.getElementById('workName').value;
        const signed = document.getElementById('signedBy').value;
        const template = document.getElementById('templateSelector').value;

        if (!template || !template.endsWith('.png')) {
            alert('Please select a valid PNG template for preview.');
            return;
        }

        document.getElementById('certName').textContent = name;
        document.getElementById('certCourse').textContent = course;
        document.getElementById('certDate').textContent = date;
        document.getElementById('certSignedBy').textContent = signed;

        if (work) {
            document.getElementById('certWork').textContent = `With Volunteer Work: ${work}`;
        } else {
            document.getElementById('certWork').textContent = '';
        }

        document.getElementById('certificatePreview').style.display = 'block';
    }
   
function handleTypeSelection() {
    const type = document.getElementById('typeSelector').value;
    document.getElementById('scholarshipSection').style.display = (type === 'scholarship') ? 'block' : 'none';
    document.getElementById('volunteerSection').style.display = (type === 'volunteer') ? 'block' : 'none';
}


</script>
</body>
</html>
