<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/aes_key.php'; // AES_KEY and AES_IV defined here
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../Backend/logs/application_form_errors.log');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error_code' => 1, 'message' => 'User not logged in']);
    exit();
}

// Get logged-in user info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'User not found']);
    exit();
}
$user_id = $user['id'];

// Generate Application ID
$res = $conn->query("SELECT id FROM applications ORDER BY id DESC LIMIT 1");
$row = $res->fetch_assoc();
if ($row && isset($row['id'])) {
    $lastNumericId = (int)substr($row['id'], 4);
    $newNumericId = $lastNumericId + 1;
    $newId = 'APP-' . str_pad($newNumericId, 5, '0', STR_PAD_LEFT);
} else {
    $newId = 'APP-00001';
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (
        isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['course_id'])
    ) {
        // Collect data
        $firstName = $_POST['first_name'];
        $middleName = $_POST['middle_name'] ?? '';
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $houseNumber = $_POST['house_number'] ?? '';
        $street = $_POST['street'] ?? '';
        $barangay = $_POST['barangay'] ?? '';
        $district = $_POST['district'] ?? '';
        $city = $_POST['city'] ?? '';
        $region = $_POST['region'] ?? '';
        $postalCode = $_POST['postal_code'] ?? '';
        $courseId = intval($_POST['course_id']);

        // Check for duplicate application
        $checkStmt = $conn->prepare("SELECT COUNT(*) as total FROM applications WHERE user_id = ? AND course_id = ?");
        $checkStmt->bind_param("ii", $user_id, $courseId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();

        if ($checkResult['total'] > 0) {
            echo json_encode(['success' => false, 'error_code' => 6, 'message' => 'You have already applied for this course.']);
            exit();
        }

        // Handle File Upload with AES-256 Encryption
        $encryptedDocuments = [];

        if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
            foreach ($_FILES['documents']['name'] as $key => $name) {
                if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalContent = file_get_contents($_FILES['documents']['tmp_name'][$key]);
        
                    // Encrypt the file content
                    $encryptedData = openssl_encrypt(
                        $originalContent,
                        'AES-256-CBC',
                        AES_KEY,
                        OPENSSL_RAW_DATA,
                        AES_IV
                    );
        
                    // Base64 encode it for safe storage in TEXT column
                    $encodedData = base64_encode($encryptedData);
        
                    // Optional: store file name and encoded content as key-value pair
                    $encryptedDocuments[] = [
                        'file_name' => $name,
                        'file_data' => $encodedData
                    ];
                }
            }
        }
        
        // Serialize the encrypted documents array for storage in DB
        $documentData = json_encode($encryptedDocuments);  // Store as JSON string

        $insertStmt = $conn->prepare("INSERT INTO applications (id, user_id, first_name, middle_name, last_name, email, house_number, street, barangay, district, city, region, postal_code, course_id, documents)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$insertStmt) {
            error_log("Database Prepare Error: " . $conn->error);
            echo json_encode(['success' => false, 'error_code' => 8, 'message' => 'Failed to prepare SQL statement.']);
            exit();
        }
        
        if (!$insertStmt->bind_param(
            "sisssssssssssis",
            $newId,
            $user_id,
            $firstName,
            $middleName,
            $lastName,
            $email,
            $houseNumber,
            $street,
            $barangay,
            $district,
            $city,
            $region,
            $postalCode,
            $courseId,
            $documentData
        )) {
            error_log("Bind Param Error: " . $insertStmt->error);
            echo json_encode(['success' => false, 'error_code' => 9, 'message' => 'Failed to bind parameters.']);
            exit();
        }
        
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Application submitted successfully.']);
        } else {
            error_log("Database Insert Error: " . $insertStmt->error);
            echo json_encode(['success' => false, 'error_code' => 7, 'message' => 'Failed to submit application.']);
        }

        $insertStmt->close();
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'error_code' => 4, 'message' => 'Missing required fields.']);
    }
} else {
    echo json_encode(['success' => false, 'error_code' => 5, 'message' => 'Invalid request method.']);
}
?>
