<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    echo 'Error preparing statement: ' . $conn->error;
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Combine first, middle, and last name
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
} else {
    echo "User not found.";
    exit();
}

// Fetch alumni data from the alumni table
$alumni_stmt = $conn->prepare("
    SELECT a.first_name, a.middle_name, a.last_name, 'Course' AS category, c.name AS details, a.status
    FROM alumni a
    JOIN courses c ON a.user_id = (SELECT user_id FROM applications WHERE course_id = c.id GROUP BY user_id)
    WHERE a.status = 'completed'
    UNION
    SELECT a.first_name, a.middle_name, a.last_name, 'Volunteer' AS category, w.title AS details, a.status
    FROM alumni a
    JOIN works w ON a.user_id = (SELECT user_id FROM volunteer_application WHERE work_id = w.id GROUP BY user_id)
    WHERE a.status = 'completed'
");
if (!$alumni_stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    echo 'Error preparing statement: ' . $conn->error;
    exit();
}
$alumni_stmt->execute();
$alumni_result = $alumni_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .alumni-section {
            margin-top: 100px;
        }
        .card {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5); /* Deeper shadow */
            transition: transform 0.2s;
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
            margin: 20px auto; /* Center the cards horizontally with margin */
            border-radius: 15px; /* Rounded corners */
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-icon {
            font-size: 100px;
            color: #ddead1;
            margin: 20px 0;
        }
        .card-title, .card-text {
            color: white;
        }
        .card-title{
            color: white;
        }
        .modal-title {
            color: white;
        }
        table.dataTable thead {
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .modal-header {
            background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
            color: white;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5);
        }
        .modal-body {
            background-color: #ddead1;
        }
        .form-label {
            color: rgb(2, 61, 15);
            font-weight: bold;
        }
        .btn-primary {
            background-color: rgb(2, 61, 15);
            border: none;
        }
        .btn-primary:hover {
            background-color: rgb(26, 70, 41);
        }
    </style>
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Alumni Section -->
<div class="container alumni-section">
    <div class="row justify-content-center">
        <!-- Card 1 -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#requestDocumentsModal">
                <div class="card-body">
                    <i class="fas fa-file-alt card-icon"></i>
                    <h5 class="card-title" style="color: white;">Request Documents</h5>
                    <p class="card-text">Requesting Documents, Certificates and etc.</p>
                </div>
            </div>
        </div>
        <!-- Card 2 -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#careerAssistanceModal">
                <div class="card-body">
                    <i class="fas fa-briefcase card-icon"></i>
                    <h5 class="card-title" style="color: white;">Financial Assistance</h5>
                    <p class="card-text">Financial, Career and other assistances</p>
                </div>
            </div>
        </div>
        <!-- Card 3 -->
        <div class="col-md-4">
            <div class="card text-center" data-bs-toggle="modal" data-bs-target="#financialAssistanceModal">
                <div class="card-body">
                    <i class="fas fa-dollar-sign card-icon"></i>
                    <h5 class="card-title" style="color: white;">Carreer Assistance</h5>
                    <p class="card-text">Financial support and funding opportunities.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <table id="alumniTable" class="display">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alumni = $alumni_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['middle_name'] . ' ' . $alumni['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['category']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['details']); ?></td>
                            <td><?php echo htmlspecialchars($alumni['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Request Documents Modal -->
<div class="modal fade" id="requestDocumentsModal" tabindex="-1" aria-labelledby="requestDocumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestDocumentsModalLabel">Request Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="documentType" class="form-label">Document Type</label>
                        <input type="text" class="form-control" id="documentType" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Career Assistance Modal -->
<div class="modal fade" id="careerAssistanceModal" tabindex="-1" aria-labelledby="careerAssistanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="careerAssistanceModalLabel"><i class="fas fa-briefcase"></i> Career Assistance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Career Assistance Form</h5>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="currentAddress" class="form-label">Current Address</label>
                        <input type="text" class="form-control" id="currentAddress" required>
                    </div>
                    <div class="mb-3">
                        <label for="occupation" class="form-label">Current Occupation</label>
                        <input type="text" class="form-control" id="occupation" required>
                    </div>
                    <div class="mb-3">
                        <label for="assistanceType" class="form-label">Type of Assistance Needed</label>
                        <select class="form-control" id="assistanceType" required>
                            <option value="financial">Financial Assistance</option>
                            <option value="career">Career Guidance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description of Need</label>
                        <textarea class="form-control" id="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Financial Assistance Modal -->
<div class="modal fade" id="financialAssistanceModal" tabindex="-1" aria-labelledby="financialAssistanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="financialAssistanceModalLabel"><i class="fas fa-dollar-sign"></i> Career Assistance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Career Assistance Form</h5>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <input type="text" class="form-control" id="gender" required>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" required>
                    </div>
                    <div class="mb-3">
                        <label for="currentAddress" class="form-label">Current Address</label>
                        <input type="text" class="form-control" id="currentAddress" required>
                    </div>
                    <div class="mb-3">
                        <label for="permanentAddress" class="form-label">Permanent Address</label>
                        <input type="text" class="form-control" id="permanentAddress" required>
                    </div>
                    <div class="mb-3">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" class="form-control" id="age" required>
                    </div>
                    <div class="mb-3">
                        <label for="occupation" class="form-label">Occupation</label>
                        <input type="text" class="form-control" id="occupation" required>
                    </div>
                    <div class="mb-3">
                        <label for="civilStatus" class="form-label">Civil Status</label>
                        <input type="text" class="form-control" id="civilStatus" required>
                    </div>
                    <div class="mb-3">
                        <label for="placeOfBirth" class="form-label">Place of Birth</label>
                        <input type="text" class="form-control" id="placeOfBirth" required>
                    </div>
                    <div class="mb-3">
                        <label for="height" class="form-label">Height</label>
                        <input type="text" class="form-control" id="height" required>
                    </div>
                    <div class="mb-3">
                        <label for="weight" class="form-label">Weight</label>
                        <input type="text" class="form-control" id="weight" required>
                    </div>
                    <div class="mb-3">
                        <label for="fatherName" class="form-label">Father's Name</label>
                        <input type="text" class="form-control" id="fatherName" required>
                    </div>
                    <div class="mb-3">
                        <label for="motherName" class="form-label">Mother's Name</label>
                        <input type="text" class="form-control" id="motherName" required>
                    </div>
                    <div class="mb-3">
                        <label for="language" class="form-label">Language or dialect spoken</label>
                        <input type="text" class="form-control" id="language" required>
                    </div>
                    <div class="mb-3">
                        <label for="emergencyContact" class="form-label">Person to be contacted in case of emergency</label>
                        <input type="text" class="form-control" id="emergencyContact" required>
                    </div>
                    <div class="mb-3">
                        <label for="emergencyAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="emergencyAddress" required>
                    </div>
                    <div class="mb-3">
                        <label for="emergencyContactNo" class="form-label">Contact No.</label>
                        <input type="text" class="form-control" id="emergencyContactNo" required>
                    </div>
                    <h5 class="mb-3">Educational Background</h5>
                    <div class="mb-3">
                        <label for="elementary" class="form-label">Elementary</label>
                        <input type="text" class="form-control" id="elementary" required>
                    </div>
                    <div class="mb-3">
                        <label for="elementaryYear" class="form-label">Year Graduated</label>
                        <input type="text" class="form-control" id="elementaryYear" required>
                    </div>
                    <div class="mb-3">
                        <label for="highSchool" class="form-label">High School</label>
                        <input type="text" class="form-control" id="highSchool" required>
                    </div>
                    <div class="mb-3">
                        <label for="highSchoolYear" class="form-label">Year Graduated</label>
                        <input type="text" class="form-control" id="highSchoolYear" required>
                    </div>
                    <div class="mb-3">
                        <label for="college" class="form-label">College</label>
                        <input type="text" class="form-control" id="college" required>
                    </div>
                    <div class="mb-3">
                        <label for="collegeYear" class="form-label">Year Graduated</label>
                        <input type="text" class="form-control" id="collegeYear" required>
                    </div>
                    <div class="mb-3">
                        <label for="certification" class="form-label">Certification</label>
                        <textarea class="form-control" id="certification" rows="3" required>I hereby certify that the facts contained in this bio-data are true and complete to the best of my knowledge.</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="signature" class="form-label">Signature</label>
                        <input type="text" class="form-control" id="signature" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Career Assistance Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#alumniTable').DataTable();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
