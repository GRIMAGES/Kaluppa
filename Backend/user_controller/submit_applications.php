<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

require_once '../../Backend/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['work_id'])) {

    if (!isset($_SESSION['email'])) {
        error_log("User not logged in.");
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit();
    }

    // Fetch user info
    $userQuery = "SELECT id, first_name, middle_name, last_name, email FROM user WHERE email = ?";
    $stmt = $conn->prepare($userQuery);
    if (!$stmt) {
        error_log("User fetch prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit();
    }

    $stmt->bind_param('s', $_SESSION['email']);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();

    if (!$user) {
        error_log("User not found with email: " . $_SESSION['email']);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $user_id = $user['id'];
    $first_name = $_POST['first_name'] ?? $user['first_name'];
    $middle_name = $_POST['middle_name'] ?? $user['middle_name'];
    $last_name = $_POST['last_name'] ?? $user['last_name'];
    $email = $_POST['email'] ?? $user['email'];
    $work_id = $_POST['work_id'];
    $phone = $_POST['phone'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $province = $_POST['province'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $facebook_profile = $_POST['facebook_profile'] ?? '';
    $available_days = isset($_POST['available_days']) ? implode(',', $_POST['available_days']) : '';
    error_log("Available Days: " . $available_days); // Log the value of available_days
    $hours_per_week = $_POST['hours_per_week'] ?? 0;

    // Upload Directory
    $uploadDir = realpath(__DIR__ . '/../../Backend/Documents/Volunteer/');
    if (!$uploadDir || !is_dir($uploadDir)) {
        $uploadDir = __DIR__ . '/../../Backend/Documents/Volunteer/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    }

    // File Validation
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        error_log("Resume upload error. Code: " . ($_FILES['resume']['error'] ?? 'Not set'));
        echo "<script>var errorToast = new bootstrap.Toast(document.getElementById('errorToast')); errorToast.show();</script>";
        exit();
    }

    $allowedMime = ['application/pdf'];
    $allowedExt = ['pdf'];
    $fileTmpPath = $_FILES['resume']['tmp_name'];
    $fileName = basename($_FILES['resume']['name']);
    $fileMime = mime_content_type($fileTmpPath);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileMime, $allowedMime) || !in_array($fileExt, $allowedExt)) {
        error_log("Invalid file type uploaded: $fileMime.$fileExt");
        echo "<script>var errorToast = new bootstrap.Toast(document.getElementById('errorToast')); errorToast.show();</script>";
        exit();
    }

    $uploadFilePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    $resumePathToSave = 'Backend/Documents/Volunteer/' . $fileName;

    if (!move_uploaded_file($fileTmpPath, $uploadFilePath)) {
        error_log("File move failed to: $uploadFilePath");
        echo "<script>var errorToast = new bootstrap.Toast(document.getElementById('errorToast')); errorToast.show();</script>";
        exit();
    }

    // Generate Volunteer ID
    $idQuery = "SELECT id FROM volunteer_application ORDER BY id DESC LIMIT 1";
    $idResult = $conn->query($idQuery);
    if ($idResult && $idResult->num_rows > 0) {
        $lastIdRow = $idResult->fetch_assoc();
        $lastId = $lastIdRow['id'];
        $lastNum = (int)substr($lastId, 4);
        $newId = 'VOL-' . str_pad($lastNum + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $newId = 'VOL-00001';
    }

    // Insert into DB
    $insertQuery = "INSERT INTO volunteer_application (
        id, work_id, user_id, first_name, middle_name, last_name, email,
        phone, barangay, province, municipality, facebook_profile, available_days, hours_per_week, resume
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) {
        error_log("Insert prepare failed: " . $conn->error);
        echo "<script>var errorToast = new bootstrap.Toast(document.getElementById('errorToast')); errorToast.show();</script>";
        exit();
    }

    $stmt->bind_param(
        'siissssssssssis',
        $newId,
        $work_id,
        $user_id,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $phone,
        $barangay,
        $province,
        $municipality,
        $facebook_profile,
        $available_days,
        $hours_per_week,
        $resumePathToSave
    );

    // Execute and check success
    if ($stmt->execute()) {
        $_SESSION['success'] = "Your application has been submitted!";

        // Log the volunteer application submission
        require_once '../../Backend/log_helper.php';
        insertLog($user_id, 'VOLUNTEER_APPLICATION_SUBMISSION', "User submitted a volunteer application for work ID: $work_id", 'SUBMISSION');
    } else {
        error_log("Insert execution failed: " . $stmt->error); // Log the error
        $_SESSION['error'] = "Something went wrong. Please try again.";
        echo json_encode(['success' => false, 'message' => 'Database insertion failed.']);
        exit();
    }

    exit();
}
?>
