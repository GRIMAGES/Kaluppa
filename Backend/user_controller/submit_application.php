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
$first_name = isset($_POST['first_name']) ? mysqli_real_escape_string($conn, $_POST['first_name']) : '';
$middle_name = isset($_POST['middle_name']) ? mysqli_real_escape_string($conn, $_POST['middle_name']) : '';
$last_name = isset($_POST['last_name']) ? mysqli_real_escape_string($conn, $_POST['last_name']) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
$house_number = isset($_POST['house_number']) ? mysqli_real_escape_string($conn, $_POST['house_number']) : '';
$street = isset($_POST['street']) ? mysqli_real_escape_string($conn, $_POST['street']) : '';
$barangay = isset($_POST['barangay']) ? mysqli_real_escape_string($conn, $_POST['barangay']) : '';
$district = isset($_POST['district']) ? mysqli_real_escape_string($conn, $_POST['district']) : '';
$city = isset($_POST['city']) ? mysqli_real_escape_string($conn, $_POST['city']) : '';
$region = isset($_POST['region']) ? mysqli_real_escape_string($conn, $_POST['region']) : '';
$postal_code = isset($_POST['postal_code']) ? mysqli_real_escape_string($conn, $_POST['postal_code']) : '';
$course_id = isset($_POST['course_id']) ? mysqli_real_escape_string($conn, $_POST['course_id']) : '';

// Validate form data
if (empty($first_name) || empty($last_name) || empty($email) || empty($house_number) || empty($street) || empty($barangay) || empty($district) || empty($city) || empty($region) || empty($postal_code) || empty($course_id)) {
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

// Generate custom application ID (APP-00001 format)
$query = "SELECT id FROM applications ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row) {
    $lastId = $row['id'];
    $num = (int)substr($lastId, 4); // Extract numeric part
    $newId = 'APP-' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
} else {
    $newId = 'APP-00001'; // First entry
}

error_log("Generated new ID: " . $newId); // Debugging statement

// Handle file upload (optional)
$document_paths = []; // Array to store paths of uploaded files

if (isset($_FILES['documents'])) {
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['documents']['error'][$key] == 0) {
            $document = $_FILES['documents'];
            $document_name = time() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", basename($document['name'][$key]));
            $document_tmp_name = $document['tmp_name'][$key];

            $baseDirectory = realpath(__DIR__ . '/../Documents/Scholarship/');

            if ($baseDirectory === false) {
                error_log("Base directory not found: /../Documents/Scholarship/");
                echo json_encode(['success' => false, 'error_code' => 10, 'message' => 'Server error: Document directory not found.']);
                exit();
            }

            if (!is_dir($baseDirectory) && !mkdir($baseDirectory, 0777, true)) {
                error_log("Failed to create base directory: $baseDirectory");
                echo json_encode(['success' => false, 'error_code' => 11, 'message' => 'Server error: Unable to create document directory.']);
                exit();
            }

            if (!is_writable($baseDirectory)) {
                error_log("Base directory is not writable: $baseDirectory");
                echo json_encode(['success' => false, 'error_code' => 12, 'message' => 'Server error: Document directory not writable.']);
                exit();
            }

            $document_path = $baseDirectory . DIRECTORY_SEPARATOR . $document_name;

            if (!move_uploaded_file($document_tmp_name, $document_path)) {
                error_log("Error uploading document to $document_path");
                echo json_encode(['success' => false, 'error_code' => 7, 'message' => 'Error uploading document']);
                exit();
            }

            $document_paths[] = str_replace('\\', '/', str_replace(realpath(__DIR__ . '/../../'), '', $document_path));
        }
    }
}

$document = implode(',', $document_paths);

// Insert application with custom ID
$application_query = "INSERT INTO applications (id, user_id, course_id, first_name, middle_name, last_name, email, house_number, street, barangay, district, city, region, postal_code, document) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($application_query);
$stmt->bind_param("siissssssssssss", $newId, $user_id, $course_id, $first_name, $middle_name, $last_name, $email, $house_number, $street, $barangay, $district, $city, $region, $postal_code, $document);

// Log query execution for debugging
if (!$stmt->execute()) {
    error_log("Error executing query: " . $stmt->error); // Log error if any
    echo json_encode(['success' => false, 'error_code' => 8, 'message' => 'Error submitting application']);
} else {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
}
?>
