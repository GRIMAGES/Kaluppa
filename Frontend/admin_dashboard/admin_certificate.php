<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();
if (isset($_SESSION['gen_success'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['gen_success'] . "</div>";
    unset($_SESSION['gen_success']);  // Clear the message after displaying
}
if (isset($_SESSION['gen_error'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['gen_error'] . "</div>";
    unset($_SESSION['gen_error']);
}

if (isset($_SESSION['upload_message'])) {
    echo "<div class='alert alert-info'>" . $_SESSION['upload_message'] . "</div>";
    unset($_SESSION['upload_message']);
}


// Session timeout and logout functionality
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

// Handling success and error messages
$uploadSuccess = isset($_SESSION['upload_success']) ? $_SESSION['upload_success'] : '';
$uploadError = isset($_SESSION['upload_error']) ? $_SESSION['upload_error'] : '';
$genSuccess = isset($_SESSION['gen_success']) ? $_SESSION['gen_success'] : '';
$genError = isset($_SESSION['gen_error']) ? $_SESSION['gen_error'] : '';

// Clear session variables after displaying the message
unset($_SESSION['upload_success'], $_SESSION['upload_error'], $_SESSION['gen_success'], $_SESSION['gen_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Certificate | Admin Panel</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>

    <style>
        body {
            background-color: #f4f7fa;
        }
        .container {
            margin-top: 30px;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        .template-preview {
            margin-top: 15px;
            display: none;
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #f9f9f9;
        }
        .template-selector {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="container">
    <div class="row">
        <!-- Upload Template Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="text-center">Upload Certificate Template</h4>
                </div>
                <div class="card-body">
                    <form action="../../Backend/admin_controller/uploadTemplate.php" method="POST" enctype="multipart/form-data" id="upload-template-form">
                        <div class="form-group">
                            <label for="template_name">Template Name</label>
                            <input type="text" name="template_name" class="form-control" id="template_name" required>
                        </div>
                        <div class="form-group">
                            <label for="template_file">Upload Template (PNG/JPEG/PDF)</label>
                            <input type="file" name="template_file" class="form-control" id="template_file" required onchange="previewTemplate(event)">
                        </div>
                        <div class="form-group template-preview">
                            <label>Preview Template</label>
                            <img id="templatePreview" src="" alt="Template Preview" class="template-preview" />
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" name="upload_template">Upload Template</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Template Selector Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="text-center">Select Certificate Template</h4>
                </div>
                <div class="card-body">
                    <form action="../../Backend/admin_controller/selectTemplate.php" method="POST" id="select-template-form">
                        <div class="form-group">
                            <label for="template_select">Select Template</label>
                            <select name="template_select" class="form-control" id="template_select" required>
                                <?php
                                    $query = "SELECT id, template_name FROM certificate_templates";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['template_name']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" name="select_template">Select Template</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying messages -->
<div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalMessageContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    // Show modal with messages
    <?php if ($uploadSuccess): ?>
        $('#modalMessageContent').text('<?php echo $uploadSuccess; ?>');
        $('#messageModal').modal('show');
    <?php endif; ?>
    <?php if ($uploadError): ?>
        $('#modalMessageContent').text('<?php echo $uploadError; ?>');
        $('#messageModal').modal('show');
    <?php endif; ?>
    <?php if ($genSuccess): ?>
        $('#modalMessageContent').text('<?php echo $genSuccess; ?>');
        $('#messageModal').modal('show');
    <?php endif; ?>
    <?php if ($genError): ?>
        $('#modalMessageContent').text('<?php echo $genError; ?>');
        $('#messageModal').modal('show');
    <?php endif; ?>
  });

  function previewTemplate(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('templatePreview');
            preview.src = e.target.result;
            document.querySelector('.template-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
  }
</script>

</body>
</html>
