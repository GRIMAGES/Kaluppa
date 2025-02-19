<?php
require_once '../../Backend/connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key

// Fetch the admin's full name from the user table
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();

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
    <link rel="stylesheet" href="../CSS/admin css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .content {
            margin-left: 250px;
            padding: 15px;
        }
        table {
            width: 100%;
            margin-top: 15px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
        }
        .form-label {
            font-weight: bold;
        }
        .form-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header, .modal-footer {
            display: flex;
            justify-content: space-between;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
    </style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="color: red;">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="logoutForm" action="admin_scholarship.php" method="POST">
                        <button type="submit" name="logout" class="btn btn-danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h1 class="mb-3">Reports</h1>
        <p>Export various reports such as:</p>
        <ul>
            <li>Accepted Scholars</li>
            <li>Accepted Volunteers</li>
        </ul>
        <form action="../../Backend/admin_controller/export_reports.php" method="GET">
            <div class="mb-3">
                <label for="reportType" class="form-label">Select Report Type</label>
                <select class="form-select form-select-sm" id="reportType" name="report_type">
                    <option value="accepted_scholars">Accepted Scholars</option>
                    <option value="accepted_volunteers">Accepted Volunteers</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="fileType" class="form-label">Select File Type</label>
                <select class="form-select form-select-sm" id="fileType" name="file_type">
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                    <option value="pdf">PDF</option>
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
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>

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

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
