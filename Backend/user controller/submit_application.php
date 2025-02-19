<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file inside the Backend/logs folder
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error_code' => 1, 'message' => 'User not logged in']);
    error_log("User not logged in. Session email is not set.");
    exit();
}

// Fetch logged-in user's data
$email = $_SESSION['email'];
$query = "SELECT id FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'User not found']);
    error_log("User not found with email: $email");
    exit();
}

$user_id = $user['id']; // Get the logged-in user ID

// Get form data
$full_name = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, $_POST['full_name']) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
$dob = isset($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : '';
$address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
$course_id = isset($_POST['course_id']) ? mysqli_real_escape_string($conn, $_POST['course_id']) : '';

// Validate form data
if (empty($full_name) || empty($email) || empty($dob) || empty($address) || empty($course_id)) {
    echo json_encode(['success' => false, 'error_code' => 9, 'message' => 'All fields are required']);
    error_log("Missing required fields in form data.");
    exit();
}

// Check if the course exists
$course_check_query = "SELECT id FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_check_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course_check_result = $stmt->get_result();

if (mysqli_num_rows($course_check_result) === 0) {
    echo json_encode(['success' => false, 'error_code' => 3, 'message' => 'Invalid course ID']);
    error_log("Invalid course ID: $course_id");
    exit();
}

// Check if the user already applied for the course
$check_existing_application_query = "SELECT * FROM applications WHERE user_id = ? AND course_id = ?";
$stmt = $conn->prepare($check_existing_application_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$check_existing_result = $stmt->get_result();

if (mysqli_num_rows($check_existing_result) > 0) {
    echo json_encode(['success' => false, 'error_code' => 4, 'message' => 'You have already applied for this course']);
    error_log("User with ID $user_id already applied for course ID $course_id");
    exit();
}

// Handle file upload (optional)
$document_path = null; // Default in case no file is uploaded

if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
    $document = $_FILES['document'];
    $document_name = time() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", basename($document['name']));
    $document_tmp_name = $document['tmp_name'];

    // Define the base directory
    $baseDirectory = realpath(__DIR__ . '/../Documents/Scholarship/');

    if ($baseDirectory === false) {
        error_log("Base directory not found: /../Documents/Scholarship/");
        echo json_encode(['success' => false, 'error_code' => 10, 'message' => 'Server error: Document directory not found.']);
        exit();
    }

    // Ensure directory exists and is writable
    if (!is_dir($baseDirectory)) {
        if (!mkdir($baseDirectory, 0777, true)) {
            error_log("Failed to create base directory: $baseDirectory");
            echo json_encode(['success' => false, 'error_code' => 11, 'message' => 'Server error: Unable to create document directory.']);
            exit();
        }
    }

    if (!is_writable($baseDirectory)) {
        error_log("Base directory is not writable: $baseDirectory");
        echo json_encode(['success' => false, 'error_code' => 12, 'message' => 'Server error: Document directory not writable.']);
        exit();
    }

    // Set the full path for the document
    $document_path = $baseDirectory . DIRECTORY_SEPARATOR . $document_name;

    // Move the uploaded file
    if (!move_uploaded_file($document_tmp_name, $document_path)) {
        error_log("Error uploading document to $document_path");
        echo json_encode(['success' => false, 'error_code' => 7, 'message' => 'Error uploading document']);
        exit();
    }

    // Convert path to relative for database storage
    $document_path = str_replace(realpath(__DIR__ . '/../../'), '', $document_path);

    // Ensure the path uses front slashes for web compatibility
    $document_path = str_replace('\\', '/', $document_path);
}

// Insert application data into the database
$application_query = "INSERT INTO applications (user_id, course_id, full_name, email, dob, address, document) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($application_query);
$stmt->bind_param("iisssss", $user_id, $course_id, $full_name, $email, $dob, $address, $document_path);

// Debugging: Log the query to see what’s being executed
error_log("Executing query: $application_query with values: $user_id, $course_id, $full_name, $email, $dob, $address, $document_path");

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} else {
    
}
?>
