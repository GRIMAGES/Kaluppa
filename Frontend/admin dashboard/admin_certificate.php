<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();

$timeout_duration = 1000;
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

$adminEmail = $_SESSION['email'] ?? '';
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? '';
$stmt->close();

$courses = [];
$courseQuery = "SELECT id, name FROM courses";
$courseResult = $conn->query($courseQuery);
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}

if (isset($_GET['course_id'])) {
    $courseId = intval($_GET['course_id']);
    $students = [];
    $studentQuery = "
        SELECT a.status, u.last_name, u.middle_name, u.first_name
        FROM scholarship_applications AS a
        JOIN user AS u ON a.user_id = u.id
        WHERE a.course_id = ?
    ";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scholarship Course Applicants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="container mt-5">
    <h2 class="text-center mb-4">Select Scholarship Course</h2>
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <select name="course_id" class="form-select" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php if (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Show Applicants</button>
            </div>
        </div>
    </form>

    <?php if (isset($students) && count($students) > 0): ?>
        <h4 class="mb-3">Applicants for Selected Course</h4>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Status</th>
                    <th>Last Name</th>
                    <th>Middle Name</th>
                    <th>First Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['status']); ?></td>
                        <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['middle_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($_GET['course_id'])): ?>
        <div class="alert alert-warning">No applicants found for this course.</div>
    <?php endif; ?>
</div>
</body>
</html>