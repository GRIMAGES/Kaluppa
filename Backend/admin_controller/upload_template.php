<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$uploadDir = '../../Frontend/admin dashboard/templates/';

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
    header("Location: ../../Frontend/admin dashboard/admin_certificate.php");
    exit();
}
?>
