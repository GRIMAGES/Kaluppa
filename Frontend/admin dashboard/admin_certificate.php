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

// Steganography upload handling (QR code removed)
$uploadStatus = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadStego'])) {
    if (isset($_FILES['stegoCertificate']) && $_FILES['stegoCertificate']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['stegoCertificate']['tmp_name'];
        $fileName = $_FILES['stegoCertificate']['name'];
        $uploadDir = 'uploads/';
        $targetPath = $uploadDir . basename($fileName);

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move uploaded stego certificate
        if (move_uploaded_file($fileTmp, $targetPath)) {
            $uploadStatus = "Steganographed Certificate uploaded successfully.";
        } else {
            $uploadStatus = "Error uploading the certificate.";
        }
    } else {
        $uploadStatus = "No file uploaded or upload error occurred.";
    }
}
?>

<!-- HTML content below remains -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/admin_scholar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="container mt-5">
    <h2 class="text-center mb-4">Certificate Generator</h2>

    <?php if (!empty($uploadMessage)): ?>
        <div class="alert alert-info"><?php echo $uploadMessage; ?></div>
    <?php endif; ?>

    <!-- ✅ Stego Certificate Upload Status -->
    <?php if (!empty($uploadStatus)): ?>
        <div class="alert alert-info"><?php echo $uploadStatus; ?></div>
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

    <!-- ✅ Updated: Steganography Upload Only -->
    <div class="card mb-4">
        <div class="card-header">Upload Steganographed Certificate</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="stegoCertificate" accept=".png,.jpg,.jpeg" class="form-control mb-3" required>
                <button type="submit" name="uploadStego" class="btn btn-dark">Upload Certificate</button>
            </form>
        </div>
    </div>
</div>
    <form id="certificateForm" class="mb-5" method="POST" action="../../Backend/admin_controller/generate_certificate.php" target="_blank">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Certificate Type</label>
                <select class="form-select" id="certificateType" onchange="toggleRecipientDropdown()" required>
                    <option value="">-- Select Type --</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="volunteer">Volunteer</option>
                </select>
            </div>

            <div class="col-md-6" id="courseContainer" style="display:none;">
                <label class="form-label">Select Course</label>
                <select class="form-select" id="courseSelect">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6" id="workContainer" style="display:none;">
                <label class="form-label">Select Volunteer Work</label>
                <select class="form-select" id="workSelect">
                    <option value="">-- Select Work --</option>
                    <?php foreach ($works as $work): ?>
                        <option value="<?php echo $work['id']; ?>"><?php echo htmlspecialchars($work['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Recipient Name</label>
                <input type="text" class="form-control" id="recipientName" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Date Awarded</label>
                <input type="date" class="form-control" name="awardDate" id="awardDate" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Signed By</label>
                <input type="text" class="form-control" name="signedBy" value="<?php echo htmlspecialchars($adminName); ?>" required>
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
            <h2 id="certName"></h2>
            <p>has successfully completed</p>
            <h4 id="certDetail"></h4>
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
    function toggleRecipientDropdown() {
        const type = document.getElementById('certificateType').value;
        document.getElementById('courseContainer').style.display = type === 'scholarship' ? 'block' : 'none';
        document.getElementById('workContainer').style.display = type === 'volunteer' ? 'block' : 'none';
    }

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
        const course = document.getElementById('courseSelect').selectedOptions[0]?.text || '';
        const work = document.getElementById('workSelect').selectedOptions[0]?.text || '';
        const type = document.getElementById('certificateType').value;
        const date = document.getElementById('awardDate').value;
        const signed = document.getElementById('signedBy').value;

        document.getElementById('certName').textContent = name;
        document.getElementById('certSignedBy').textContent = signed;
        document.getElementById('certDate').textContent = date;

        if (type === 'scholarship') {
            document.getElementById('certDetail').textContent = `the scholarship course: ${course}`;
        } else if (type === 'volunteer') {
            document.getElementById('certDetail').textContent = `the volunteer work: ${work}`;
        } else {
            document.getElementById('certDetail').textContent = '';
        }

        document.getElementById('certificatePreview').style.display = 'block';
    }
    
</script>
</body>
</html>
