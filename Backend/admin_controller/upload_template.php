<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $template_name = trim($_POST['template_name']);
    $file = $_FILES['template_file'];

    $uploadDir = __DIR__ . '/templates/';
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];

    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        echo "❌ Invalid file type. Only JPG, PNG, and PDF are allowed.";
        exit;
    }

    $newFileName = uniqid('template_', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $conn->prepare("INSERT INTO certificate_templates (template_name, template_file) VALUES (?, ?)");
        $stmt->bind_param("ss", $template_name, $newFileName);

        if ($stmt->execute()) {
            echo "<script>alert('✅ Template uploaded successfully.'); window.location.href = '../../Frontend/admin/generate_certificate.php';</script>";
        } else {
            echo "❌ Failed to save to database.";
        }
    } else {
        echo "❌ Failed to upload file.";
    }
} else {
    echo "Invalid request.";
}
?>
