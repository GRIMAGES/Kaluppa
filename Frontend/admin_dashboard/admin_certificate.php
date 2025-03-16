<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
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

$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key

// Fetch the admin's full name
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();

// Handle template upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['template'])) {
    $uploadDir = "../../templates/"; // Directory to store templates
    $fileName = basename($_FILES['template']['name']);
    $targetPath = $uploadDir . $fileName;


    if (move_uploaded_file($_FILES['template']['tmp_name'], $targetPath)) {
        list($posFullNameX, $posFullNameY) = explode(',', $_POST['pos_full_name']);
        list($posCourseNameX, $posCourseNameY) = explode(',', $_POST['pos_course_name']);
        list($posCertificateNoX, $posCertificateNoY) = explode(',', $_POST['pos_certificate_no']);

        $query = "INSERT INTO certificate_templates (template_name, file_path, uploaded_by, certificate_type, font_full_name, font_course_name, font_certificate_no, pos_full_name_x, pos_full_name_y, pos_course_name_x, pos_course_name_y, pos_certificate_no_x, pos_certificate_no_y) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssss", $fileName, $targetPath, $_SESSION['email'], $_POST['certificate_type'], $_POST['font_full_name'], $_POST['font_course_name'], $_POST['font_certificate_no'], $posFullNameX, $posFullNameY, $posCourseNameX, $posCourseNameY, $posCertificateNoX, $posCertificateNoY);

        $stmt->execute();
        $stmt->close();

        $_SESSION['upload_success'] = "Template uploaded successfully!";
    } else {
        $_SESSION['upload_error'] = "Failed to upload the file.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch the latest uploaded template
$templateQuery = "SELECT template_name, file_path FROM certificate_templates ORDER BY id DESC LIMIT 1";
$templateResult = $conn->query($templateQuery);
$template = $templateResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Certificate Maker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/admin_scholar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .template-preview img, .template-preview embed {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .template-preview:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .form-section h5 {
            margin-bottom: 15px;
        }
        .draggable {
            position: absolute;
            cursor: move;
            padding: 5px;
            border: 1px dashed #000;
            background-color: rgba(255, 255, 255, 0.7);
        }
        .template-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $(".draggable").draggable({
                containment: ".template-container",
                stop: function(event, ui) {
                    var position = ui.position;
                    $(this).find("input[type='hidden']").val(position.left + "," + position.top);
                }
            });

            // Initialize positions from hidden inputs
            $(".draggable").each(function() {
                var position = $(this).find("input[type='hidden']").val().split(",");
                $(this).css({ left: position[0] + "px", top: position[1] + "px" });
            });
        });

        function generateCertificates() {
            var selectedUsers = [];
            document.querySelectorAll("input[name='user_ids[]']:checked").forEach((checkbox) => {
                selectedUsers.push(checkbox.value);
            });
            if (selectedUsers.length > 0) {
                window.open("../../Backend/admin_controller/generate_certificate.php?user_ids=" + selectedUsers.join(","), "_blank");
            } else {
                alert("Please select at least one user.");
            }
        }
    </script>
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Generate Certificates</h2>

        <?php if (isset($_SESSION['upload_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['upload_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['upload_error']; unset($_SESSION['upload_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="form-section">
                <h5>Upload Certificate Template</h5>
                <div class="mb-3">
                    <label for="template" class="form-label">Upload Certificate Template (PDF, JPG, PNG, etc.):</label>
                    <input type="file" name="template" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.webp" required>
                </div>
                
                <div class="mb-3">
                    <label for="certificate_type" class="form-label">Select Certificate Type:</label>
                    <select name="certificate_type" class="form-select" required>
                        <option value="volunteer">Volunteer Certificate</option>
                        <option value="scholarship">Scholarship Certificate</option>
                        <option value="event">Event Certificate</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h5>Font and Position Settings</h5>
                <div class="template-container">
                    <img src="<?php echo htmlspecialchars($template['file_path']); ?>" alt="Template Preview" class="img-fluid">
                    <div class="draggable" style="left: 50px; top: 110px;">Full Name<input type="hidden" name="pos_full_name" value="50,110"></div>
                    <div class="draggable" style="left: 50px; top: 135px;">Course Name<input type="hidden" name="pos_course_name" value="50,135"></div>
                    <div class="draggable" style="left: 50px; top: 180px;">Certificate No<input type="hidden" name="pos_certificate_no" value="50,180"></div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="font_full_name" class="form-label">Font for Full Name:</label>
                            <select name="font_full_name" class="form-select" required>
                                <option value="PinyonScript">Pinyon Script</option>
                                <option value="Arial">Arial</option>
                                <option value="Times">Times New Roman</option>
                                <option value="Courier">Courier New</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Verdana">Verdana</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="font_course_name" class="form-label">Font for Course Name:</label>
                            <select name="font_course_name" class="form-select" required>
                                <option value="Times">Times New Roman</option>
                                <option value="Arial">Arial</option>
                                <option value="Courier">Courier New</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Verdana">Verdana</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="font_certificate_no" class="form-label">Font for Certificate Number:</label>
                            <select name="font_certificate_no" class="form-select" required>
                                <option value="Times">Times New Roman</option>
                                <option value="Arial">Arial</option>
                                <option value="Courier">Courier New</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Verdana">Verdana</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Upload Template</button>
        </form>

        <?php if (!empty($template)): ?>
            <div class="mt-3">
                <h5>Current Template:</h5>
                <a href="<?php echo htmlspecialchars($template['file_path']); ?>" target="_blank">
                    <?php echo htmlspecialchars($template['template_name']); ?>
                </a>
                <div class="template-preview mt-3">
                    <h5>Template Preview:</h5>
                    <?php if (in_array(pathinfo($template['file_path'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])): ?>
                        <img src="<?php echo htmlspecialchars($template['file_path']); ?>" alt="Template Preview" class="img-fluid">
                    <?php elseif (pathinfo($template['file_path'], PATHINFO_EXTENSION) == 'pdf'): ?>
                        <embed src="<?php echo htmlspecialchars($template['file_path']); ?>" type="application/pdf" width="100%" height="400px" />
                    <?php else: ?>
                        <p>Preview not available for this file type.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <h4>Filter by Course:</h4>
        <form method="GET" class="mb-4">
            <select name="course" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select Course --</option>
                <?php
                $courseQuery = "SELECT id, name FROM courses";
                $courseResult = $conn->query($courseQuery);
                while ($course = $courseResult->fetch_assoc()) {
                    $selected = isset($_GET['course']) && $_GET['course'] == $course['id'] ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($course['id']) . "' $selected>" . htmlspecialchars($course['name']) . "</option>";
                }
                ?>
            </select>
        </form>

        <h4>Select Users:</h4>
        <form>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Select</th>
                            <th>Certificate No.</th>
                            <th>User ID</th>
                            <th>Full Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $courseFilter = isset($_GET['course']) ? "WHERE applications.course_id = '" . $conn->real_escape_string($_GET['course']) . "'" : "";
                        $query = "SELECT user.id, user.first_name, user.last_name, CONCAT('CERT-', LPAD(user.id, 6, '0')) AS certificate_no 
                                  FROM user 
                                  INNER JOIN applications ON user.id = applications.user_id 
                                  $courseFilter";
                        $result = $conn->query($query);
                        while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($user['certificate_no']); ?></td>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-primary mt-3" onclick="generateCertificates()">Generate Certificates</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>