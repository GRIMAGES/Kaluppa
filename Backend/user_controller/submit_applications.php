<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';
require_once '../../Backend/aes_key.php'; // AES_KEY and AES_IV defined here
require_once '../../Backend/vendor/autoload.php'; // TCPDF autoload

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate a unique ID for the application (11 characters max)
        $applicationId = 'VOL-' . substr(uniqid(), -7);

        // Collect form data
        $workId = $_POST['work_id'];
        $firstName = $_POST['first_name'];
        $middleName = $_POST['middle_name'] ?? null;
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $barangay = $_POST['barangay'];
        $province = $_POST['province'];
        $municipality = $_POST['municipality'];
        $facebookProfile = $_POST['facebook_profile'];
        $availableDays = implode(',', $_POST['available_days'] ?? []);
        $hoursPerWeek = $_POST['hours_per_week'];

        // Handle file upload with PDF conversion and AES encryption
        $uploadDir = realpath(__DIR__ . '/../../Backend/Documents/Volunteer/');
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }

        $uploadedResume = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $name = $_FILES['resume']['name'];
            $tmpFilePath = $_FILES['resume']['tmp_name'];
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
                throw new Exception('Failed to encrypt uploaded resume.');
            }

            // Save the encrypted file
            if (file_put_contents($destinationPath, $encryptedData) === false) {
                throw new Exception('Failed to save encrypted resume.');
            }

            $uploadedResume = [
                'file_name' => $safeFileName,
                'file_path' => $destinationPath
            ];
        } else {
            throw new Exception('Resume upload failed.');
        }

        // Store the resume info as JSON in the database
        $resumePath = json_encode($uploadedResume);

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO volunteer_application (id, work_id, first_name, middle_name, last_name, email, phone, barangay, province, municipality, facebook_profile, available_days, hours_per_week, resume) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssssssssss", $applicationId, $workId, $firstName, $middleName, $lastName, $email, $phone, $barangay, $province, $municipality, $facebookProfile, $availableDays, $hoursPerWeek, $resumePath);

        if ($stmt->execute()) {
            sleep(3); // Simulate a 3-second delay
            echo "Application submitted successfully!";
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
