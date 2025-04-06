<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
require_once '../../Backend/aes_key.php'; // Include aes_key.php
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
function encryptData($data) {
    $iv = AES_IV;
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', AES_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Function to decrypt data
function decryptData($data) {
    $data = base64_decode($data);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encrypted, 'aes-256-cbc', AES_KEY, 0, $iv);
}

// Function to create a backup with a custom filename
function createBackup($conn, $key, $customFilename = null) {
    // Fetch data from the database
    $query = "SELECT * FROM backups"; // Replace 'your_table' with your actual table name
    $result = $conn->query($query);
    $data = json_encode($result->fetch_all(MYSQLI_ASSOC));

    // Encrypt the data
    $encryptedData = encryptData($data);

    // Determine the backup filename
    $filename = $customFilename ? $customFilename : 'backup_' . date('Y-m-d_H-i-s');
    $backupDir = '../../Backend/backups/';
    $backupFile = $backupDir . $filename . '.bak';

    // Check if the Backups directory exists, if not, create it
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    // Save the encrypted data to a file
    file_put_contents($backupFile, $encryptedData);

    // Store backup metadata in the database
    $stmt = $conn->prepare("INSERT INTO backups (filename) VALUES (?)");
    $stmt->bind_param('s', $filename);
    $stmt->execute();

    return $backupFile;
}

// Function to get backup versions
function getBackupVersions($conn) {
    $query = "SELECT filename, created_at FROM backups ORDER BY created_at DESC";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle AJAX request to fetch backup versions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_backups'])) {
    $backups = getBackupVersions($conn);
    echo json_encode($backups);
    exit();
}

// Function to restore from a backup
function restoreBackup($conn, $backupFile, $key) {
    // Read the encrypted data from the file
    $encryptedData = file_get_contents($backupFile);

    // Decrypt the data
    $data = decryptData($encryptedData);

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
        $customFilename = $_POST['custom_filename'] ?? null;
        $backupFile = createBackup($conn, $key, $customFilename);
        echo "Backup created: " . basename($backupFile);
    } elseif (isset($_POST['restore_backup'])) {
        $backupFile = $_POST['backup_file'];
        restoreBackup($conn, $backupFile, $key);
        echo "Backup restored from: " . basename($backupFile);
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
            <input type="text" id="backupFilename" class="form-control mb-3" placeholder="Enter custom filename (optional)">
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
    function fetchBackupVersions() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'admin_backup.php?fetch_backups=true', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const backups = JSON.parse(xhr.responseText);
                const backupSelect = document.getElementById('backupVersions');
                backupSelect.innerHTML = '<option selected>Select Backup Version</option>';
                backups.forEach(backup => {
                    const option = document.createElement('option');
                    option.value = backup.filename;
                    option.textContent = `${backup.filename} - ${new Date(backup.created_at).toLocaleString()}`;
                    backupSelect.appendChild(option);
                });
            }
        };
        xhr.send();
    }

    document.addEventListener('DOMContentLoaded', fetchBackupVersions);

    document.getElementById('createBackupBtn').addEventListener('click', function() {
        const customFilename = document.getElementById('backupFilename').value;
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'admin_backup.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                showAlert('Backup created successfully!', 'success');
                document.getElementById('lastBackupInfo').textContent = 'Last Backup: ' + new Date().toLocaleString();
                document.getElementById('downloadBackupBtn').style.display = 'block';
            }
        };
        xhr.send('create_backup=true&custom_filename=' + encodeURIComponent(customFilename));
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

    document.getElementById('downloadBackupBtn').addEventListener('click', function() {
        // Fetch the latest backup file from the server
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'admin_backup.php?fetch_backups=true', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const backups = JSON.parse(xhr.responseText);
                if (backups.length > 0) {
                    const latestBackup = backups[0]; // Assuming the first one is the latest due to ordering
                    const downloadLink = document.createElement('a');
                    downloadLink.href = '../../Backend/backups/' + latestBackup.filename + '.bak';
                    downloadLink.download = latestBackup.filename + '.bak';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                } else {
                    showAlert('No backups available for download.', 'warning');
                }
            }
        };
        xhr.send();
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