<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// upload_template.php
require_once '../../Backend/connection.php';

if (isset($_FILES['certificate_template']) && $_FILES['certificate_template']['error'] == 0) {
    $file_name = $_FILES['certificate_template']['name'];
    $file_tmp = $_FILES['certificate_template']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_types)) {
        // Check for upload errors
        if ($_FILES['certificate_template']['error'] !== UPLOAD_ERR_OK) {
            echo 'Upload error: ' . $_FILES['certificate_template']['error'];
        } else {
            // File upload process
            $upload_dir = '/templates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
            }
            $new_file_name = uniqid('cert_', true) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // File uploaded successfully
                header("Location: http://kaluppa.online/Kaluppa/Frontend/admin_dashboard/admin_certificate.php?status=success");
                exit();
            } else {
                header("Location: http://kaluppa.online/Kaluppa/Frontend/admin_dashboard/admin_certificate.php?status=error");
                exit();
            }
        }
    } else {
        header("Location: http://kaluppa.online/Kaluppa/Frontend/admin_dashboard/admin_certificate.php?status=invalid");
        exit();
    }
} else {
    header("Location: http://kaluppa.online/Kaluppa/Frontend/admin_dashboard/admin_certificate.php?status=empty");
    exit();
}

?>
