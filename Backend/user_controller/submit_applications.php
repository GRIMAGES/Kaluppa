<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';

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

        // Handle file upload with AES-256 Encryption and PDF Conversion (copied from submit_application.php, but using uploads directory)
        $uploadDir = realpath(__DIR__ . '/../../Backend/uploads/');
        if (!is_dir($uploadDir)) {
            error_log("Upload directory does not exist: $uploadDir");
            throw new Exception('Upload directory not found.');
        }

        $uploadedDocuments = [];
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            require_once '../../Backend/aes_key.php'; // AES_KEY and AES_IV defined here
            require_once '../../vendor/autoload.php'; // TCPDF should be in the Backend vendor folder.
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
                error_log("Failed to encrypt file: $name");
                throw new Exception('Failed to encrypt uploaded resume.');
            }

            // Save the encrypted file
            if (file_put_contents($destinationPath, $encryptedData) === false) {
                error_log("Failed to save encrypted file: $name");
                throw new Exception('Failed to save encrypted resume.');
            }

            $uploadedDocuments[] = [
                'file_name' => $safeFileName,
                'file_path' => $destinationPath
            ];
        } else {
            throw new Exception('Resume upload failed.');
        }

        // Serialize the uploaded documents array for storage in DB
        $documentData = json_encode($uploadedDocuments); // Store as JSON string

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO volunteer_application (id, work_id, first_name, middle_name, last_name, email, phone, barangay, province, municipality, facebook_profile, available_days, hours_per_week, resume) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssssssssss", $applicationId, $workId, $firstName, $middleName, $lastName, $email, $phone, $barangay, $province, $municipality, $facebookProfile, $availableDays, $hoursPerWeek, $documentData);

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
