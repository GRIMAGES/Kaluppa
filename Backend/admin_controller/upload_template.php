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
        // 🔍 Check if template_name already exists
        $check = $conn->prepare("SELECT id FROM certificate_templates WHERE template_name = ?");
        $check->bind_param("s", $template_name);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            // ✅ Exists — update the existing file_path
            $row = $checkResult->fetch_assoc();
            $existingId = $row['id'];

            $update = $conn->prepare("UPDATE certificate_templates SET file_path = ? WHERE id = ?");
            $update->bind_param("si", $newFileName, $existingId);

            if ($update->execute()) {
                echo "<script>alert('✅ Template updated successfully.'); window.location.href = '../../Frontend/admin_dashboard/admin_certificate.php';</script>";
            } else {
                echo "❌ Failed to update existing template.";
            }
        } else {
            // ❌ Not found — insert as new
            $stmt = $conn->prepare("INSERT INTO certificate_templates (template_name, file_path) VALUES (?, ?)");
            $stmt->bind_param("ss", $template_name, $newFileName);

            if ($stmt->execute()) {
                echo "<script>alert('✅ Template uploaded successfully.'); window.location.href = '../../Frontend/admin_dashboard/admin_certificate.php';</script>";
            } else {
                echo "❌ Failed to save to database.";
            }
        }
    } else {
        echo "❌ Failed to upload file.";
    }
} else {
    echo "Invalid request.";
}
?>
