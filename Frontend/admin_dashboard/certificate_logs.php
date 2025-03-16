<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

$timeout_duration = 1000;
if (!isset($_SESSION['email'])) {
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Kaluppa/Frontend/index.php");
    exit();
}

// Fetch certificate logs
$logs = [];
$query = "SELECT certificate_type, related_title, issued_to, issued_by, issued_at FROM certificate_logs ORDER BY issued_at DESC";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Logs | Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body {
            padding: 30px;
        }
        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<div class="container table-container">
    <h2>Certificate Logs</h2>
    <?php if (!empty($logs)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Certificate Type</th>
                        <th>Related Title</th>
                        <th>Issued To</th>
                        <th>Issued By</th>
                        <th>Issued At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($log['certificate_type'])) ?></td>
                            <td><?= htmlspecialchars($log['related_title']) ?></td>
                            <td><?= htmlspecialchars($log['issued_to']) ?></td>
                            <td><?= htmlspecialchars($log['issued_by']) ?></td>
                            <td><?= htmlspecialchars(date("F d, Y h:i A", strtotime($log['issued_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No certificate logs available.</div>
    <?php endif; ?>
</div>

</body>
</html>
