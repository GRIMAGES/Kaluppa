<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../Backend/logs/application_form_errors.log');
require_once '../../Backend/connection.php';
require_once '../../Backend/aes_key.php'; // AES_KEY and AES_IV defined here

require_once '../../vendor/autoload.php';  // PHPMailer, PhpSpreadsheet, TCPDF should be in the Backend vendor folder.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();



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

// Function to send notification email
function sendApplicationNotification($email, $firstName, $courseName) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wgonzales@kaluppa.org';
        $mail->Password = 'qfsp ihop mdqg ngoy'; // Replace with a secure password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email headers and content
        $mail->setFrom('wgonzales@kaluppa.org', 'Kaluppa Team');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Course Application Successful";
        $mail->Body = "Dear $firstName,<br><br>
                       You have successfully applied for the course: <strong>$courseName</strong>.<br>
                       We will review your application and notify you of the next steps.<br><br>
                       Thank you for choosing Kaluppa.<br><br>
                       Best regards,<br>The Kaluppa Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Generate a unique Application ID
function generateUniqueApplicationId($conn) {
    $prefix = 'APP-';
    $maxAttempts = 5; // Limit the number of attempts to avoid infinite loops
    $attempt = 0;

    do {
        $numericId = mt_rand(1, 99999); // Generate a random number
        $newId = $prefix . str_pad($numericId, 5, '0', STR_PAD_LEFT);

        // Check if the ID already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications WHERE id = ?");
        $stmt->bind_param("s", $newId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['total'] == 0) {
            return $newId; // Return the unique ID
        }

        $attempt++;
    } while ($attempt < $maxAttempts);

    throw new Exception("Failed to generate a unique Application ID after $maxAttempts attempts.");
}

try {
    // Handle POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate required fields
        if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['course_id'])) {
            // Collect data
            $firstName = $_POST['first_name'];
            $middleName = $_POST['middle_name'] ?? '';
            $lastName = $_POST['last_name'];
            $email = $_POST['email'];
            $barangay = $_POST['barangay'] ?? '';
            $province = $_POST['province'] ?? '';
            $municipality = $_POST['municipality'] ?? '';
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

            // Handle File Upload with AES-256 Encryption and PDF Conversion
            $uploadDir = realpath(__DIR__ . '/../../Backend/Documents/Scholarship/');
            if (!is_dir($uploadDir)) {
                error_log("Upload directory does not exist: $uploadDir");
                echo json_encode(['success' => false, 'error_code' => 16, 'message' => 'Upload directory not found.']);
                exit();
            }

            $uploadedDocuments = [];
            if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                foreach ($_FILES['documents']['name'] as $key => $name) {
                    if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpFilePath = $_FILES['documents']['tmp_name'][$key];
                        $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $safeFileName = uniqid() . '_' . pathinfo($name, PATHINFO_FILENAME) . '.pdf'; // Ensure the file name ends with .pdf
                        $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

                        // Convert file to PDF if not already a PDF
                        $pdfContent = '';
                        if ($fileExtension !== 'pdf') {
                            $pdf = new \TCPDF();
                            $pdf->AddPage();
                            $pdf->SetFont('helvetica', '', 12);
                            $fileContent = file_get_contents($tmpFilePath);
                            $pdf->Write(0, $fileContent);
                            $pdfContent = $pdf->Output('', 'S'); // Get PDF content as a string
                        } else {
                            $pdfContent = file_get_contents($tmpFilePath); // Use the original PDF content
                        }

                        // Encrypt the PDF content
                        $encryptedData = openssl_encrypt(
                            $pdfContent,
                            'AES-256-CBC',
                            AES_KEY,
                            OPENSSL_RAW_DATA,
                            AES_IV
                        );

                        if ($encryptedData === false) {
                            error_log("Failed to encrypt file: $name");
                            echo json_encode(['success' => false, 'error_code' => 18, 'message' => 'Failed to encrypt uploaded documents.']);
                            exit();
                        }

                        // Save the encrypted file
                        if (file_put_contents($destinationPath, $encryptedData) === false) {
                            error_log("Failed to save encrypted file: $name");
                            echo json_encode(['success' => false, 'error_code' => 19, 'message' => 'Failed to save encrypted documents.']);
                            exit();
                        }

                        $uploadedDocuments[] = [
                            'file_name' => $safeFileName,
                            'file_path' => $destinationPath
                        ];
                    }
                }
            }

            // Serialize the uploaded documents array for storage in DB
            $documentData = json_encode($uploadedDocuments); // Store as JSON string

            // Call the function to generate a unique ID
            try {
                $newId = generateUniqueApplicationId($conn);
            } catch (Exception $e) {
                error_log("Application ID Generation Error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error_code' => 15, 'message' => 'Failed to generate a unique Application ID.']);
                exit();
            }

            // Correct the SQL query to include the `id` column
            $insertStmt = $conn->prepare("INSERT INTO applications (id, user_id, first_name, middle_name, last_name, email, barangay, province, municipality, course_id, documents)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$insertStmt) {
                error_log("Database Prepare Error: " . $conn->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 8, 'message' => 'Failed to prepare SQL statement.']);
                exit();
            }
            
            // Correct the bind_param statement to include the `id` parameter
            if (!$insertStmt->bind_param(
                "sisssssssis",
                $newId,
                $user_id,
                $firstName,
                $middleName,
                $lastName,
                $email,
                $barangay,
                $province,
                $municipality,
                $courseId,
                $documentData
            )) {
                error_log("Bind Param Error: " . $insertStmt->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 9, 'message' => 'Failed to bind parameters.']);
                exit();
            }
            
            // Execute the query and handle errors
            if (!$insertStmt->execute()) {
                // Log detailed error information
                error_log("Database Insert Error: " . $insertStmt->error); 
                error_log("Query Parameters: id=$newId, user_id=$user_id, first_name=$firstName, middle_name=$middleName, last_name=$lastName, email=$email, barangay=$barangay, province=$province, municipality=$municipality, course_id=$courseId, documents=" . json_encode($documentData));

                // Check for specific MySQL errors
                if ($conn->errno) {
                    error_log("MySQL Error Code: " . $conn->errno);
                    error_log("MySQL Error Message: " . $conn->error);
                }

                // Return a detailed error response
                echo json_encode([
                    'success' => false,
                    'error_code' => 7,
                    'message' => 'Failed to submit application.',
                    'mysql_error' => $conn->error, // Include MySQL error in the response for debugging
                    'mysql_error_code' => $conn->errno
                ]);
                exit();
            }

            // Fetch course name for the email
            $courseStmt = $conn->prepare("SELECT name FROM courses WHERE id = ?");
            if (!$courseStmt) {
                error_log("Course Query Prepare Error: " . $conn->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 11, 'message' => 'Failed to fetch course details.']);
                exit();
            }

            if (!$courseStmt->bind_param("i", $courseId)) {
                error_log("Course Query Bind Error: " . $courseStmt->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 12, 'message' => 'Failed to bind course ID.']);
                exit();
            }

            if (!$courseStmt->execute()) {
                error_log("Course Query Execute Error: " . $courseStmt->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 13, 'message' => 'Failed to execute course query.']);
                exit();
            }

            $courseResult = $courseStmt->get_result();
            if (!$courseResult) {
                error_log("Course Query Result Error: " . $conn->error); // Log detailed error
                echo json_encode(['success' => false, 'error_code' => 14, 'message' => 'Failed to fetch course result.']);
                exit();
            }

            $course = $courseResult->fetch_assoc();
            $courseName = $course['name'] ?? 'the course';

            // Send notification email
            $emailResult = sendApplicationNotification($email, $firstName, $courseName);

            if ($emailResult) {
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully. Notification email sent.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully, but failed to send notification email.']);
            }

            // Log the application submission
            require_once '../../Backend/log_helper.php';
            insertLog($user_id, 'APPLICATION_SUBMISSION', "User submitted an application for course ID: $courseId", 'SUBMISSION');

            $insertStmt->close();
            $conn->close();
        } else {
            echo json_encode(['success' => false, 'error_code' => 4, 'message' => 'Missing required fields.']);
        }
    } else {
        echo json_encode(['success' => false, 'error_code' => 5, 'message' => 'Invalid request method.']);
    }
} catch (Throwable $e) {
    // Catch unexpected errors
    error_log("Unexpected Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error_code' => 500, 'message' => 'Internal Server Error.']);
}
?>
