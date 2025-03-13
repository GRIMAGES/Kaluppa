<?php
session_start();
$uploadDir = '../../Frontend/admin_dashboard/templates/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['templateFile'])) {
    $file = $_FILES['templateFile'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    $allowedTypes = ['image/png', 'application/pdf'];
    if (in_array($file['type'], $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['uploadMessage'] = "Template uploaded successfully!";
        } else {
            $_SESSION['uploadMessage'] = "Failed to upload template.";
        }
    } else {
        $_SESSION['uploadMessage'] = "Only PNG and PDF templates are allowed.";
    }
    header("Location: ../../Frontend/admin_dashboard/admin_certificate.php");
    exit();
}
?>
