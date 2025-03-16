<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define allowed file types
$allowed_types = ['pdf', 'png', 'jpeg', 'jpg'];

// upload_template.php
require_once '../../Backend/connection.php';

// Handle the file upload
if (isset($_POST['submit_certificate'])) {
    if (isset($_FILES['certificate_template']) && $_FILES['certificate_template']['error'] == 0) {
        // Allowed file types
        $file_name = $_FILES['certificate_template']['name'];
        $file_tmp = $_FILES['certificate_template']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Check if the file extension is allowed
        if (in_array($file_ext, $allowed_types)) {
            // Move file to upload directory
            $upload_dir = '/templates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
            }
            $new_file_name = uniqid('cert_', true) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // File uploaded successfully
                header("Location: ../../Frontend/admin_dashboard/admin_certificate.php?status=success");
                exit();
            } else {
                header("Location: ../../Frontend/admin_dashboard/admin_certificate.php?status=error");
                exit();
            }
        } else {
            header("Location: ../../Frontend/admin_dashboard/admin_certificate.php?status=invalid");
            exit();
        }
    } else {
        header("Location: ../../Frontend/admin_dashboard/admin_certificate.php?status=empty");
        exit();
    }
}
?>
