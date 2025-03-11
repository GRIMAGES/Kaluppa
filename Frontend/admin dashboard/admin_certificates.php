<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_template'])) {
    $uploadDir = "../../certificate_templates/"; // Directory to store templates
    $fileName = basename($_FILES['template']['name']);
    $targetPath = $uploadDir . $fileName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES['template']['tmp_name'], $targetPath)) {
        $_SESSION['template_path'] = $targetPath;
        $_SESSION['template_name'] = $fileName;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['upload_error'] = "Failed to upload the file.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle template selection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['select_template'])) {
    $templateId = $_POST['template_id'];
    $templateQuery = "SELECT * FROM certificate_templates WHERE id = ?";
    $stmt = $conn->prepare($templateQuery);
    $stmt->bind_param('i', $templateId);
    $stmt->execute();
    $templateResult = $stmt->get_result();
    $templateRow = $templateResult->fetch_assoc();
    $stmt->close();

    if ($templateRow) {
        $_SESSION['template_path'] = $templateRow['file_path'];
        $_SESSION['template_name'] = $templateRow['template_name'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch the latest uploaded template
$templatePath = $_SESSION['template_path'] ?? '';
$templateName = $_SESSION['template_name'] ?? '';

// Fetch all templates for selection
$templatesQuery = "SELECT id, template_name FROM certificate_templates";
$templatesResult = $conn->query($templatesQuery);

function saveTemplate($conn) {
    $posFullNameX = $_POST['pos_full_name_x'] ?? 50;
    $posFullNameY = $_POST['pos_full_name_y'] ?? 110;

    $posCourseNameX = $_POST['pos_course_name_x'] ?? 50;
    $posCourseNameY = $_POST['pos_course_name_y'] ?? 135;

    $posCertificateNoX = $_POST['pos_certificate_no_x'] ?? 50;
    $posCertificateNoY = $_POST['pos_certificate_no_y'] ?? 180;

    $posDateX = $_POST['pos_date_x'] ?? 50;
    $posDateY = $_POST['pos_date_y'] ?? 205;

    $certificateType = $_POST['certificate_type'] ?? 'volunteer'; // Default to 'volunteer' if not set
    $fontFullName = $_POST['font_full_name'] ?? 'Times';
    $fontCourseName = $_POST['font_course_name'] ?? 'Times';
    $fontCertificateNo = $_POST['font_certificate_no'] ?? 'Times';
    $fontDate = $_POST['font_date'] ?? 'Times';
    $sizeFullName = $_POST['size_full_name'] ?? 36;
    $sizeCourseName = $_POST['size_course_name'] ?? 16;
    $sizeCertificateNo = $_POST['size_certificate_no'] ?? 12;
    $sizeDate = $_POST['size_date'] ?? 12;

    $templateName = $_POST['template_name'] ?? $_SESSION['template_name'];

    $query = "INSERT INTO certificate_templates (template_name, file_path, uploaded_by, uploaded_at, certificate_type, font_full_name, font_course_name, font_certificate_no, font_date, pos_full_name_x, pos_full_name_y, pos_course_name_x, pos_course_name_y, pos_certificate_no_x, pos_certificate_no_y, pos_date_x, pos_date_y, size_full_name, size_course_name, size_certificate_no, size_date) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssssssssssssssss", $templateName, $_SESSION['template_path'], $_SESSION['email'], $certificateType, $fontFullName, $fontCourseName, $fontCertificateNo, $fontDate, $posFullNameX, $posFullNameY, $posCourseNameX, $posCourseNameY, $posCertificateNoX, $posCertificateNoY, $posDateX, $posDateY, $sizeFullName, $sizeCourseName, $sizeCertificateNo, $sizeDate);
    $stmt->execute();
    $stmt->close();

    unset($_SESSION['template_path']);
    unset($_SESSION['template_name']);

    $_SESSION['upload_success'] = "Template saved successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle saving positions of draggable elements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_template'])) {
    saveTemplate($conn);
}
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
            max-width: 1000px; /* Increased width */
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
                    console.log("Position saved: ", position.left, position.top); // Debugging
                    $(this).find("input[name$='_x']").val(position.left);
                    $(this).find("input[name$='_y']").val(position.top);
                    $(this).find(".coordinates").text("X: " + position.left + ", Y: " + position.top);
                }
            });

            // Initialize positions from hidden inputs
            $(".draggable").each(function() {
                var posX = $(this).find("input[name$='_x']").val();
                var posY = $(this).find("input[name$='_y']").val();
                console.log("Position loaded: ", posX, posY); // Debugging
                $(this).css({ left: posX + "px", top: posY + "px" });
                $(this).find(".coordinates").text("X: " + posX + ", Y: " + posY);
            });
        });

        function savePositions() {
            $(".draggable").each(function() {
                var posX = $(this).css("left").replace("px", "");
                var posY = $(this).css("top").replace("px", "");
                $(this).find("input[name$='_x']").val(posX);
                $(this).find("input[name$='_y']").val(posY);
            });
            document.getElementById('savePositionsForm').submit();
        }

        function generateCertificates() {
            var selectedUsers = [];
            document.querySelectorAll("input[name='user_ids[]']:checked").forEach((checkbox) => {
                selectedUsers.push(checkbox.value);
            });
            if (selectedUsers.length > 0) {
                var url = "../../Backend/admin_controller/generate_certificate.php?user_ids=" + selectedUsers.join(",");
                window.open(url, "_blank");
            } else {
                alert("Please select at least one user.");
            }
        }
    </script>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

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
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme" onclick="console.log('Logout link clicked');">Logout</a>
            </div>
        </div>
    </div>
</div>

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
                <button type="submit" name="upload_template" class="btn btn-primary">Upload Template</button>
            </div>
        </form>

        <form method="POST" class="mb-4">
            <div class="form-section">
                <h5>Select Certificate Template</h5>
                <div class="mb-3">
                    <label for="template_id" class="form-label">Choose Template:</label>
                    <select name="template_id" class="form-select" required>
                        <?php while ($template = $templatesResult->fetch_assoc()): ?>
                            <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['template_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="select_template" class="btn btn-primary">Select Template</button>
            </div>
        </form>

        <?php if (!empty($templatePath)): ?>
            <div class="mt-3">
                <h5>Current Template:</h5>
                <a href="<?php echo htmlspecialchars($templatePath); ?>" target="_blank">
                    <?php echo htmlspecialchars($templateName); ?>
                </a>
                <div class="template-preview mt-3">
                    <h5>Template Preview:</h5>
                    <div class="template-container">
                        <?php if (in_array(pathinfo($templatePath, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])): ?>
                            <img src="<?php echo htmlspecialchars($templatePath); ?>" alt="Template Preview" class="img-fluid">
                        <?php elseif (pathinfo($templatePath, PATHINFO_EXTENSION) == 'pdf'): ?>
                            <embed src="<?php echo htmlspecialchars($templatePath); ?>" type="application/pdf" width="100%" height="400px" />
                        <?php else: ?>
                            <p>Preview not available for this file type.</p>
                        <?php endif; ?>
                        <div class="draggable" style="left: 50px; top: 110px;">Full Name<input type="hidden" name="pos_full_name_x" value="50"><input type="hidden" name="pos_full_name_y" value="110"><span class="coordinates"></span></div>
                        <div class="draggable" style="left: 50px; top: 135px;">Course Name<input type="hidden" name="pos_course_name_x" value="50"><input type="hidden" name="pos_course_name_y" value="135"><span class="coordinates"></span></div>
                        <div class="draggable" style="left: 50px; top: 180px;">Certificate No<input type="hidden" name="pos_certificate_no_x" value="50"><input type="hidden" name="pos_certificate_no_y" value="180"><span class="coordinates"></span></div>
                        <div class="draggable" style="left: 50px; top: 205px;">Date<input type="hidden" name="pos_date_x" value="50"><input type="hidden" name="pos_date_y" value="205"><span class="coordinates"></span></div>
                    </div>
                </div>
            </div>

            <form method="POST" id="savePositionsForm">
                <div class="form-section">
                    <h5>Font and Position Settings</h5>
                    <div class="mb-3">
                        <label for="template_name" class="form-label">Template Name:</label>
                        <input type="text" name="template_name" class="form-control" value="<?php echo htmlspecialchars($templateName); ?>" required>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="font_date" class="form-label">Font for Date:</label> <!-- New font selection for date -->
                                <select name="font_date" class="form-select" required>
                                    <option value="Times">Times New Roman</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Courier">Courier New</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="size_full_name" class="form-label">Font Size for Full Name:</label>
                                <input type="number" name="size_full_name" class="form-control" value="36" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="size_course_name" class="form-label">Font Size for Course Name:</label>
                                <input type="number" name="size_course_name" class="form-control" value="16" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="size_certificate_no" class="form-label">Font Size for Certificate Number:</label>
                                <input type="number" name="size_certificate_no" class="form-control" value="12" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="size_date" class="form-label">Font Size for Date:</label> <!-- New font size input for date -->
                                <input type="number" name="size_date" class="form-control" value="12" required>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_template" class="btn btn-success">Save Template</button>
            </form>
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

        <form method="POST" id="savePositionsForm">
            <input type="hidden" name="save_positions" value="1">
            <input type="hidden" name="pos_full_name_x" value="50">
            <input type="hidden" name="pos_full_name_y" value="110">
            <input type="hidden" name="pos_course_name_x" value="50">
            <input type="hidden" name="pos_course_name_y" value="135">
            <input type="hidden" name="pos_certificate_no_x" value="50">
            <input type="hidden" name="pos_certificate_no_y" value="180">
            <input type="hidden" name="pos_date_x" value="50">
            <input type="hidden" name="pos_date_y" value="205">
            <button type="button" class="btn btn-secondary mt-3" onclick="savePositions()">Save Positions</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
