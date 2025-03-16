<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

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
                    <form action="generate_certificate.php" method="POST" enctype="multipart/form-data" id="upload-template-form">
                        <div class="form-group">
                            <label for="template_name">Template Name</label>
                            <input type="text" name="template_name" class="form-control" id="template_name" required>
                        </div>
                        <div class="form-group">
                            <label for="template_file">Upload Template (PNG/JPEG/PDF)</label>
                            <input type="file" name="template_file" class="form-control" id="template_file" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" name="upload_template">Upload Template</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Generate Certificates Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="text-center">Generate Certificates for Completed Courses</h4>
                </div>
                <div class="card-body">
                    <form action="generate_certificate.php" method="POST" id="generate-certificates-form">
                        <button type="submit" class="btn btn-success btn-block" name="generate_certificates">Generate Certificates</button>
                    </form>
                    <hr>
                    <h5>Completed Courses</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course Name</th>
                                <th>Student Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetching completed courses
                            $query = "SELECT c.id, c.name AS course_name, 
                            CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) AS user_name
                     FROM courses c
                     INNER JOIN applications a ON c.id = a.course_id
                     WHERE c.status = 'completed' AND a.status = 'enrolled'";
           
           
                            $result = $conn->query($query);
                            $counter = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$counter}</td>
                                        <td>{$row['course_name']}</td>
                                        <td>{$row['name']}</td>
                                        <td>
                                            <a href='generate_certificate.php?generate={$row['id']}' class='btn btn-info btn-sm'>Generate Certificate</a>
                                        </td>
                                      </tr>";
                                $counter++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
