<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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

        // Handle file upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resumePath = '../../Backend/uploads/' . basename($_FILES['resume']['name']);
            move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath);
        } else {
            throw new Exception('Resume upload failed.');
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO volunteer_application (work_id, first_name, middle_name, last_name, email, phone, barangay, province, municipality, facebook_profile, available_days, hours_per_week, resume) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssssss", $workId, $firstName, $middleName, $lastName, $email, $phone, $barangay, $province, $municipality, $facebookProfile, $availableDays, $hoursPerWeek, $resumePath);

        if ($stmt->execute()) {
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
