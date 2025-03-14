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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if required fields are present in the form
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['course_id'])) {
        
        // Collect form data
        $firstName = $_POST['first_name'];
        $middleName = $_POST['middle_name'] ?? ''; // Set default empty string for optional middle_name
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $houseNumber = $_POST['house_number'] ?? ''; 
        $street = $_POST['street'] ?? ''; 
        $barangay = $_POST['barangay'] ?? ''; 
        $district = $_POST['district'] ?? ''; 
        $city = $_POST['city'] ?? ''; 
        $region = $_POST['region'] ?? ''; 
        $postalCode = $_POST['postal_code'] ?? ''; 
        $courseId = (int)$_POST['course_id']; // Ensure course_id is an integer
        
        // Handle file upload
        $fileNames = [];
        if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
            foreach ($_FILES['documents']['name'] as $key => $name) {
                error_log("Processing file: $name");

                // Define the target directory
                $targetDir = "/opt/bitnami/apache/htdocs/Kaluppa/Backend/Documents/Scholarship/";
                $targetFile = $targetDir . basename($name);

                if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                    if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $targetFile)) {
                        $fileNames[] = $targetFile;
                        error_log("File uploaded successfully: $targetFile");
                    } else {
                        error_log("Failed to move file: $name");
                    }
                } else {
                    error_log("File upload error for $name: " . $_FILES['documents']['error'][$key]);
                }
            }
        } else {
            error_log("No files uploaded or error during file upload.");
        }

        $documentPaths = implode(',', $fileNames);
        error_log("Document paths: " . $documentPaths);

        // Insert data into the database
        $stmt = $conn->prepare("INSERT INTO applications (id, user_id, first_name, middle_name, last_name, email, house_number, street, barangay, district, city, region, postal_code, course_id, documents) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Bind parameters correctly
        $stmt->bind_param("sisssssssssssis", $newId, $user_id, $firstName, $middleName, $lastName, $email, $houseNumber, $street, $barangay, $district, $city, $region, $postalCode, $courseId, $documentPaths);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Your application has been submitted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error_code' => 3, 'message' => 'Error: ' . $stmt->error]);
            error_log("Database insert error: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'error_code' => 4, 'message' => 'Please fill in all required fields.']);
        error_log("Required fields missing in the form.");
    }
} else {
    echo json_encode(['success' => false, 'error_code' => 5, 'message' => 'Invalid request method.']);
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}
?>
