<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the reports page
$stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    insertLog($admin_id, 'View', 'Admin accessed the reports page', 'info'); // Log admin action
}
$stmt->close();

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

$adminEmail = $_SESSION['email'] ?? ''; // Initialize adminEmail from session data

// Fetch the exported reports for the logged-in admin
$query = "SELECT * FROM export_logs WHERE admin_email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: white;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        table {
            width: 100%;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
        }
        .form-label {
            font-weight: bold;
            color: white;
        }
        .form-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .form-control {
            background-color: #2c2f38;
            color: white;
            border: 1px solid #444;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .container {
            background-color: #1f1f1f;
            border-radius: 10px;
            padding: 20px;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head> 
<body>
<?php include 'sidebar.php'; ?>

<!-- Toast Success Message -->
<div class="toast" id="successToast" style="display: none;">
    <div class="toast-body bg-success text-white">
        Report generated successfully!
    </div>
</div>

<!-- Main Content -->
<div class="content">
    <h1 class="mb-3">Reports</h1>
    <p>Export various reports such as:</p>
    <ul>
        <li>Enrolled Scholars</li>
        <li>Accepted Volunteers</li>
    </ul>

    <div class="container">
        <form action="../../Backend/admin_controller/export_reports.php" method="GET">
            <div class="mb-3">
                <label for="reportType" class="form-label">Select Report Type</label>
                <select class="form-select form-select-sm" id="reportType" name="report_type">
                    <option value="enrolled_scholars">Enrolled Scholars</option>
                    <option value="accepted_volunteers">Accepted Volunteers</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="fileType" class="form-label">Select File Type</label>
                <select class="form-select form-select-sm" id="fileType" name="file_type">
                    <option value="pdf">PDF</option> <!-- Only PDF remains -->
                </select>
            </div>
            <div class="form-group">
                <label for="customTitle" class="form-label">Custom Title</label>
                <input type="text" class="form-control form-control-sm" id="customTitle" name="customTitle" placeholder="Enter custom title for the report">
            </div>
            <div class="form-group">
                <label for="adminName" class="form-label">Admin Name</label>
                <input type="text" class="form-control form-control-sm" id="adminName" name="adminName" value="<?php echo htmlspecialchars($adminName); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="adminEmail" class="form-label">Admin Email</label>
                <input type="email" class="form-control form-control-sm" id="adminEmail" name="adminEmail" value="<?php echo htmlspecialchars($adminEmail); ?>" readonly>
            </div>
            <button type="submit" class="btn btn-primary" onclick="showToast()">Generate Report</button>
        </form>
    </div>

    <h2 class="mt-5">Exported Logs</h2>
    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Admin Name</th>
                <th>Admin Email</th>
                <th>Report Type</th>
                <th>File Name</th>
                <th>File Type</th>
                <th>Generated Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($logs->num_rows > 0) {
                while ($row = $logs->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['admin_email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['report_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['file_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['file_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['export_date']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>No exported logs found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap and Toast JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    function showToast() {
        var toast = document.getElementById('successToast');
        toast.style.display = 'block';
        setTimeout(function() {
            toast.style.display = 'none';
        }, 3000); // Hide toast after 3 seconds
    }
</script>
</body>
</html>
