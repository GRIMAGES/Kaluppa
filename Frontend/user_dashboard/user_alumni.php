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
                    <h5 class="card-title" style="color: white;">Career Assistance</h5>
                    <p class="card-text">Financial, Career and other assistances</p>
                </div>
            </div>
        </div>
        <!-- Card 3 -->
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user-graduate card-icon"></i>
                    <h5 class="card-title" style="color: white;">Alumni Name 3</h5>
                    <p class="card-text">Brief description about Alumni 3.</p>
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="careerNameOfSchool" class="form-label"><i class="fas fa-school"></i> Name of School</label>
                            <input type="text" class="form-control" id="careerNameOfSchool" required>
                        </div>
                        <div class="col-md-6">
                            <label for="careerContactPerson" class="form-label"><i class="fas fa-user"></i> Contact Person(s)</label>
                            <input type="text" class="form-control" id="careerContactPerson" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                    </div>
                    <h5 class="mb-3">Project Description</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="projectName" class="form-label">Project Name</label>
                            <input type="text" class="form-control" id="projectName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="projectSummary" class="form-label">Project Summary</label>
                            <textarea class="form-control" id="projectSummary" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="targetAudience" class="form-label">Target Audience</label>
                            <input type="text" class="form-control" id="targetAudience" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="goals" class="form-label">Goals</label>
                            <textarea class="form-control" id="goals" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="evaluation" class="form-label">Evaluation</label>
                            <textarea class="form-control" id="evaluation" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="objectives" class="form-label">Objectives</label>
                            <textarea class="form-control" id="objectives" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="acknowledgement" class="form-label">Acknowledgement</label>
                            <textarea class="form-control" id="acknowledgement" rows="3" required></textarea>
                        </div>
                    </div>
                    <h5 class="mb-3">Project Budget</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="needs" class="form-label">Needs</label>
                            <input type="text" class="form-control" id="needs" required>
                        </div>
                        <div class="col-md-4">
                            <label for="cost" class="form-label">Cost</label>
                            <input type="text" class="form-control" id="cost" required>
                        </div>
                        <div class="col-md-4">
                            <label for="sourceFunding" class="form-label">Source Funding</label>
                            <input type="text" class="form-control" id="sourceFunding" required>
                        </div>
                    </div>
                    <h5 class="mb-3">Eligibility</h5>
                    <div class="mb-3">
                        <label class="form-label">Your project advocates for the following aspects of wellness. Check which apply to you:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="healthyEating">
                            <label class="form-check-label" for="healthyEating">Healthy eating</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activeLifestyle">
                            <label class="form-check-label" for="activeLifestyle">Active lifestyle</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="healthProtection">
                            <label class="form-check-label" for="healthProtection">Health protection</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="pollutionFreeEnvironment">
                            <label class="form-check-label" for="pollutionFreeEnvironment">Pollution-free environment</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="childDevelopment">
                            <label class="form-check-label" for="childDevelopment">Child development</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="mentalHealth">
                            <label class="form-check-label" for="mentalHealth">Mental health</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="other">
                            <label class="form-check-label" for="other">Other:</label>
                            <input type="text" class="form-control mt-2" id="otherText">
                        </div>
                    </div>
                    <h5 class="mb-3">Authorization</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="applicantSignature" class="form-label">Applicant's Signature</label>
                            <input type="text" class="form-control" id="applicantSignature" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contactInformation" class="form-label">Contact Information</label>
                            <input type="text" class="form-control" id="contactInformation" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="witnessSignature" class="form-label">Signature of the Witness</label>
                            <input type="text" class="form-control" id="witnessSignature" required>
                        </div>
                        <div class="col-md-6">
                            <label for="witnessTelephone" class="form-label">Telephone</label>
                            <input type="text" class="form-control" id="witnessTelephone" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="witnessDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="witnessDate" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Career Assistance Request</button>
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
                <h5 class="modal-title" id="financialAssistanceModalLabel"><i class="fas fa-dollar-sign"></i> Financial Assistance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Financial Assistance Form</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="financialNameOfSchool" class="form-label"><i class="fas fa-school"></i> Name of School</label>
                            <input type="text" class="form-control" id="financialNameOfSchool" required>
                        </div>
                        <div class="col-md-6">
                            <label for="financialContactPerson" class="form-label"><i class="fas fa-user"></i> Contact Person(s)</label>
                            <input type="text" class="form-control" id="financialContactPerson" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                    </div>
                    <h5 class="mb-3">Project Description</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="projectName" class="form-label">Project Name</label>
                            <input type="text" class="form-control" id="projectName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="projectSummary" class="form-label">Project Summary</label>
                            <textarea class="form-control" id="projectSummary" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="targetAudience" class="form-label">Target Audience</label>
                            <input type="text" class="form-control" id="targetAudience" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="goals" class="form-label">Goals</label>
                            <textarea class="form-control" id="goals" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="evaluation" class="form-label">Evaluation</label>
                            <textarea class="form-control" id="evaluation" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="objectives" class="form-label">Objectives</label>
                            <textarea class="form-control" id="objectives" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="acknowledgement" class="form-label">Acknowledgement</label>
                            <textarea class="form-control" id="acknowledgement" rows="3" required></textarea>
                        </div>
                    </div>
                    <h5 class="mb-3">Project Budget</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="needs" class="form-label">Needs</label>
                            <input type="text" class="form-control" id="needs" required>
                        </div>
                        <div class="col-md-4">
                            <label for="cost" class="form-label">Cost</label>
                            <input type="text" class="form-control" id="cost" required>
                        </div>
                        <div class="col-md-4">
                            <label for="sourceFunding" class="form-label">Source Funding</label>
                            <input type="text" class="form-control" id="sourceFunding" required>
                        </div>
                    </div>
                    <h5 class="mb-3">Eligibility</h5>
                    <div class="mb-3">
                        <label class="form-label">Your project advocates for the following aspects of wellness. Check which apply to you:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="healthyEating">
                            <label class="form-check-label" for="healthyEating">Healthy eating</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activeLifestyle">
                            <label class="form-check-label" for="activeLifestyle">Active lifestyle</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="healthProtection">
                            <label class="form-check-label" for="healthProtection">Health protection</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="pollutionFreeEnvironment">
                            <label class="form-check-label" for="pollutionFreeEnvironment">Pollution-free environment</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="childDevelopment">
                            <label class="form-check-label" for="childDevelopment">Child development</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="mentalHealth">
                            <label class="form-check-label" for="mentalHealth">Mental health</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="other">
                            <label class="form-check-label" for="other">Other:</label>
                            <input type="text" class="form-control mt-2" id="otherText">
                        </div>
                    </div>
                    <h5 class="mb-3">Authorization</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="applicantSignature" class="form-label">Applicant's Signature</label>
                            <input type="text" class="form-control" id="applicantSignature" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contactInformation" class="form-label">Contact Information</label>
                            <input type="text" class="form-control" id="contactInformation" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="witnessSignature" class="form-label">Signature of the Witness</label>
                            <input type="text" class="form-control" id="witnessSignature" required>
                        </div>
                        <div class="col-md-6">
                            <label for="witnessTelephone" class="form-label">Telephone</label>
                            <input type="text" class="form-control" id="witnessTelephone" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="witnessDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="witnessDate" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Financial Assistance Request</button>
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
