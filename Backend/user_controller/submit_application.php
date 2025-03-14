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
if ($stmt === false) {
    error_log("Error preparing query: " . $conn->error);
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'Database query preparation failed']);
    exit();
}
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if required fields are present in the form
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['course_id'])) {
        
        // Collect form data
        $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
        $middleName = mysqli_real_escape_string($conn, $_POST['middle_name']);
        $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $houseNumber = mysqli_real_escape_string($conn, $_POST['house_number']);
        $street = mysqli_real_escape_string($conn, $_POST['street']);
        $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
        $district = mysqli_real_escape_string($conn, $_POST['district']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $region = mysqli_real_escape_string($conn, $_POST['region']);
        $postalCode = mysqli_real_escape_string($conn, $_POST['postal_code']);
        $courseId = (int)$_POST['course_id']; // Ensure course_id is an integer
        
        // Handle file upload
        if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] == 0) {
            $fileNames = [];
            foreach ($_FILES['documents']['name'] as $key => $name) {
                $targetDir = "uploads/";
                $targetFile = $targetDir . basename($name);
                
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $targetFile)) {
                    $fileNames[] = $targetFile;
                }
            }
            $documentPaths = implode(',', $fileNames); // Store the uploaded file paths in the database
        } else {
            $documentPaths = '';
        }

        // Prepare the query for inserting the application into the database
        $stmt = $conn->prepare("INSERT INTO applications (first_name, middle_name, last_name, email, house_number, street, barangay, district, city, region, postal_code, course_id, documents) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssis", $firstName, $middleName, $lastName, $email, $houseNumber, $street, $barangay, $district, $city, $region, $postalCode, $courseId, $documentPaths);
        
        if ($stmt->execute()) {
            // Success, redirect or display success message
            echo "Your application has been submitted successfully!";
        } else {
            // Error handling
            echo "Error: " . $stmt->error;
        }
        
        // Close the statement and connection
        $stmt->close();
        $conn->close();
    } else {
        echo "Please fill in all required fields.";
    }
} else {
    echo "Invalid request method.";
}
?>

