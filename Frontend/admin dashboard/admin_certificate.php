<?php
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scholarship Certificate Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #certificatePreview {
            position: relative;
            width: 100%;
            max-width: 800px;
            height: auto;
            background-size: cover;
            background-position: center;
            padding: 60px;
            margin: auto;
            color: #000;
        }

        .overlay-content {
            position: absolute;
            width: 100%;
            text-align: center;
            top: 30%;
        }

        .signed-by {
            position: absolute;
            bottom: 20px;
            right: 50px;
            text-align: right;
        }
    </style>
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Scholarship Certificate Generator</h2>

    <?php if (!empty($uploadMessage)): ?>
        <div class="alert alert-info"> <?php echo $uploadMessage; ?> </div>
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
            <div class="col-md-6">
                <label class="form-label">Course / Program</label>
                <input type="text" class="form-control" name="courseName" id="courseName" required>
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

    <div id="certificatePreview" class="border shadow" style="display: none;">
        <div class="overlay-content">
            <h3><strong>Certificate of Completion</strong></h3>
            <p>This is to certify that</p>
            <h2 id="certName"></h2>
            <p>has successfully completed the course</p>
            <h4 id="certCourse"></h4>
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
            preview.style.backgroundImage = `url('${selectedTemplate}')`;
            preview.style.display = 'block';
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
        const signed = document.getElementById('signedBy').value;
        const template = document.getElementById('templateSelector').value;

        if (!template) {
            alert('Please select a template!');
            return;
        }

        if (!template.endsWith('.png')) {
            alert("Only PNG templates can be previewed. PDFs should be printed manually.");
            return;
        }

        document.getElementById('certName').textContent = name;
        document.getElementById('certCourse').textContent = course;
        document.getElementById('certDate').textContent = date;
        document.getElementById('certSignedBy').textContent = signed;
        document.getElementById('certificatePreview').style.display = 'block';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>