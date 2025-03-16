<?php
require_once '../../Backend/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_template'])) {
    $uploadDir = '../../uploads/templates/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $file = $_FILES['new_template'];
    $filename = basename($file['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $conn->prepare("INSERT INTO certificate_templates (id, template_name, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $filename, $targetPath);
        $stmt->execute();
        header("Location: {$_SERVER['HTTP_REFERER']}?upload=success");
        exit;
    } else {
        echo "Failed to upload file.";
    }
}
?>
