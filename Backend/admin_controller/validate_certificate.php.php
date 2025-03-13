<?php
require_once '../../Backend/connection.php';
require_once 'stegano.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cert'])) {
    $imgPath = $_FILES['cert']['tmp_name'];
    $hidden = decodeSteganography($imgPath);

    echo "<h3>Extracted Info:</h3>";
    echo "<pre>$hidden</pre>";

    // Extract values
    parse_str(str_replace(";", "&", $hidden), $parsed);

    // Sample validation (you can check DB)
    $name = $parsed['name'] ?? '';
    $course = $parsed['course'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM applications WHERE applicant_name=? AND course=? 
        UNION SELECT * FROM volunteer_application WHERE applicant_name=? AND course=?");
    $stmt->bind_param('ssss', $name, $course, $name, $course);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        echo "<p style='color:green;'>✅ Certificate is VALID!</p>";
    } else {
        echo "<p style='color:red;'>❌ Certificate is INVALID!</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <label>Upload Certificate to Validate:</label><br>
    <input type="file" name="cert" required><br><br>
    <button type="submit">Validate Certificate</button>
</form>
