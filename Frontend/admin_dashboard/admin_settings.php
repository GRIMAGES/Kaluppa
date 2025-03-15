<?php
require_once '../../Backend/connection.php';
session_start();

// Session timeout
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

// Fetch admin info
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name, first_name, middle_name, last_name, house_number, region, street, barangay, district, phone, profile_picture FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? '';
$stmt->close();

// Update Profile Info
if (isset($_POST['update_profile'])) {
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];
    $phone = $_POST['phone'];

    $query = "UPDATE user SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssss', $firstName, $middleName, $lastName, $phone, $adminEmail);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_settings.php?update=success");
    exit();
}

// Update Profile Picture
if (isset($_POST['update_profile_picture'])) {
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../../Frontend/admin_dashboard/uploads/profile_pics/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $profilePicture = basename($_FILES['profile_picture']['name']);
        $targetFile = $targetDir . $profilePicture;

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $query = "UPDATE user SET profile_picture = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $profilePicture, $adminEmail);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: admin_settings.php?upload=success");
        exit();
    }
}

// Update Address
if (isset($_POST['update_address'])) {
    $houseNumber = $_POST['house_number'];
    $region = $_POST['region'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $district = $_POST['district'];

    $query = "UPDATE user SET house_number = ?, region = ?, street = ?, barangay = ?, district = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssss', $houseNumber, $region, $street, $barangay, $district, $adminEmail);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_settings.php?address=success");
    exit();
}

// Change Password
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $query = "SELECT password FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $adminEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminPass = $result->fetch_assoc();
    $stmt->close();

    if (password_verify($currentPassword, $adminPass['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE user SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $hashedPassword, $adminEmail);
            $stmt->execute();
            $stmt->close();

            session_destroy();
            header("Location: /Frontend/multiuserlogin.php?password=changed");
            exit();
        } else {
            $error = "New password and confirm password do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/admin_settings.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <!-- Profile Picture Update -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4>Update Profile Picture</h4></div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <img src="../../Frontend/admin_dashboard/uploads/profile_pics/<?php echo htmlspecialchars($admin['profile_picture']); ?>" class="rounded-circle" width="120" height="120" alt="Profile Picture">
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="profile_picture" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="update_profile_picture">Update Picture</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile Info Update -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4>Update Profile Info</h4></div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($admin['middle_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100" name="update_profile">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Update -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h4>Update Address</h4></div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>House Number</label>
                                <input type="text" class="form-control" name="house_number" value="<?php echo htmlspecialchars($admin['house_number']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Street</label>
                                <input type="text" class="form-control" name="street" value="<?php echo htmlspecialchars($admin['street']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Barangay</label>
                                <input type="text" class="form-control" name="barangay" value="<?php echo htmlspecialchars($admin['barangay']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>District</label>
                                <input type="text" class="form-control" name="district" value="<?php echo htmlspecialchars($admin['district']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Region</label>
                                <input type="text" class="form-control" name="region" value="<?php echo htmlspecialchars($admin['region']); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info w-100" name="update_address">Update Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h4>Change Password</h4></div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label>Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100" name="change_password">Change Password</button>
                        <?php if (isset($error)) echo "<div class='text-danger mt-2'>$error</div>"; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
