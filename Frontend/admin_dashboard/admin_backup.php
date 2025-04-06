<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the events page
// Log admin's access to the settings page
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the settings page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
}
// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

// Function to encrypt data
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Function to decrypt data
function decryptData($data, $key) {
    $data = base64_decode($data);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

// Function to create a backup
function createBackup($conn, $key) {
    // Fetch data from the database
    $query = "SELECT * FROM your_table"; // Replace 'your_table' with your actual table name
    $result = $conn->query($query);
    $data = json_encode($result->fetch_all(MYSQLI_ASSOC));

    // Encrypt the data
    $encryptedData = encryptData($data, $key);

    // Save the encrypted data to a file
    $backupFile = '../../Backups/backup_' . date('Y-m-d_H-i-s') . '.bak';
    file_put_contents($backupFile, $encryptedData);

    return $backupFile;
}

// Function to restore from a backup
function restoreBackup($conn, $backupFile, $key) {
    // Read the encrypted data from the file
    $encryptedData = file_get_contents($backupFile);

    // Decrypt the data
    $data = decryptData($encryptedData, $key);

    // Convert JSON data back to array
    $dataArray = json_decode($data, true);

    // Restore data to the database
    foreach ($dataArray as $row) {
        // Insert data back into the database
        // Replace 'your_table' and column names with your actual table and column names
        $query = "INSERT INTO your_table (column1, column2) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $row['column1'], $row['column2']);
        $stmt->execute();
    }
}

// Example usage
$key = 'your-encryption-key'; // Use a secure key
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $backupFile = createBackup($conn, $key);
        echo "Backup created: " . $backupFile;
    } elseif (isset($_POST['restore_backup'])) {
        $backupFile = $_POST['backup_file'];
        restoreBackup($conn, $backupFile, $key);
        echo "Backup restored from: " . $backupFile;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Backup & Restore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/event.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

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
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h1>System Backup & Restore</h1>
    <p>Backup and restore system data for business continuity.</p>

    <!-- Backup Section -->
    <div class="card mb-4">
        <div class="card-header">Backup</div>
        <div class="card-body">
            <button class="btn btn-primary" id="createBackupBtn">Create Backup</button>
            <div class="progress mt-3" id="backupProgress" style="display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <p class="mt-3" id="lastBackupInfo">Last Backup: Not available</p>
            <button class="btn btn-secondary" id="downloadBackupBtn" style="display: none;">Download Latest Backup</button>
        </div>
    </div>

    <!-- Restore Section -->
    <div class="card mb-4">
        <div class="card-header">Restore</div>
        <div class="card-body">
            <button class="btn btn-warning" id="restoreBackupBtn">Restore Backup</button>
            <select class="form-select mt-3" id="backupVersions">
                <option selected>Select Backup Version</option>
                <!-- Options will be populated dynamically -->
            </select>
            <div class="progress mt-3" id="restoreProgress" style="display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div class="mt-3" id="restoreLogs">Restore Logs: No logs available</div>
        </div>
    </div>

    <!-- System Alerts & Notifications -->
    <div id="alerts"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('createBackupBtn').addEventListener('click', function() {
        // Simulate backup process
        document.getElementById('backupProgress').style.display = 'block';
        let progressBar = document.querySelector('#backupProgress .progress-bar');
        let progress = 0;
        let interval = setInterval(function() {
            if (progress >= 100) {
                clearInterval(interval);
                document.getElementById('lastBackupInfo').textContent = 'Last Backup: ' + new Date().toLocaleString();
                document.getElementById('downloadBackupBtn').style.display = 'block';
                showAlert('Backup Successful', 'success');
            } else {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressBar.textContent = progress + '%';
            }
        }, 500);
    });

    document.getElementById('restoreBackupBtn').addEventListener('click', function() {
        // Simulate restore process
        document.getElementById('restoreProgress').style.display = 'block';
        let progressBar = document.querySelector('#restoreProgress .progress-bar');
        let progress = 0;
        let interval = setInterval(function() {
            if (progress >= 100) {
                clearInterval(interval);
                document.getElementById('restoreLogs').textContent = 'Restore Logs: Restore completed successfully.';
                showAlert('Restore Successful', 'success');
            } else {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressBar.textContent = progress + '%';
            }
        }, 500);
    });

    function showAlert(message, type) {
        let alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        document.getElementById('alerts').appendChild(alertDiv);
    }
</script>
</body>
</html>