<?php
require_once '../../Backend/connection.php';

// Initialize arrays
$userData = [];
$applicationData = [];
$dates = [];

// Fetch user creation data grouped by date
$queryUsers = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM user GROUP BY DATE(created_at) ORDER BY DATE(created_at)";
$resultUsers = $conn->query($queryUsers);
while ($row = $resultUsers->fetch_assoc()) {
    $dates[] = $row['date'];
    $userData[$row['date']] = (int)$row['count'];
}

// Fetch scholarship applications data grouped by date
$queryApplications = "SELECT DATE(applied_at) AS date, COUNT(*) AS count FROM application GROUP BY DATE(applied_at) ORDER BY DATE(applied_at)";
$resultApplications = $conn->query($queryApplications);
while ($row = $resultApplications->fetch_assoc()) {
    if (!in_array($row['date'], $dates)) {
        $dates[] = $row['date'];
    }
    $applicationData[$row['date']] = (int)$row['count'];
}

// Sort dates and fill missing values with 0
sort($dates);
foreach ($dates as $date) {
    $userData[$date] = $userData[$date] ?? 0;
    $applicationData[$date] = $applicationData[$date] ?? 0;
}

// Send JSON response
echo json_encode([
    'dates' => $dates,
    'userData' => array_values($userData),
    'applicationData' => array_values($applicationData),
]);
?>
