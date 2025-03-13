<?php
require_once '../connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];

    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/../../Backend/documents/Scholarship/";
        $file_tmp = $_FILES['document']['tmp_name'];
        $file_name = basename($_FILES['document']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate file
        $file_type = mime_content_type($file_tmp);
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file_type, $allowed_types)) {
            echo "Error: Invalid file type.";
            exit;
        }

        // Move file
        if (move_uploaded_file($file_tmp, $target_file)) {
            // Update database with new file name
            $query = "UPDATE applications SET document = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $file_name, $application_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Document updated successfully.";
                header("Location: /Frontend/user_dashboard/user_transactions.php");
                exit();
            } else {
                echo "Error: Failed to update document in database.";
            }
        } else {
            echo "Error: Failed to move uploaded file.";
        }
    } else {
        echo "Error: No file uploaded or temporary file missing.";
    }
} else {
    echo "Error: Invalid request method.";
}
?>
