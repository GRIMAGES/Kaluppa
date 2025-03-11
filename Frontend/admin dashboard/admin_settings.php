<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Last activity was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}


$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key
// Fetch the admin's full name from the user table
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name, first_name, middle_name, last_name, house_number, region, street, barangay, district, phone AS phone, profile_picture FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();

// Handle profile update
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

    // Refresh the page to reflect changes
    header("Location: admin_settings.php");
    exit();
}

// Handle profile picture update
if (isset($_POST['update_profile_picture'])) {
    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../../Frontend/admin dashboard/uploads/profile_pics/";
        $targetFile = $targetDir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile);
        $profilePicture = basename($_FILES['profile_picture']['name']);

        $query = "UPDATE user SET profile_picture = ? WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $profilePicture, $adminEmail);
        $stmt->execute();
        $stmt->close();

        // Refresh the page to reflect changes
        header("Location: admin_settings.php");
        exit();
    }
}

// Handle address update
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

    // Refresh the page to reflect changes
    header("Location: admin_settings.php");
    exit();
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch the current password from the database
    $query = "SELECT password FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $adminEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    // Verify current password
    if (password_verify($currentPassword, $admin['password'])) {
        // Check if new password and confirm password match
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password in the database
            $query = "UPDATE user SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $hashedPassword, $adminEmail);
            $stmt->execute();
            $stmt->close();

            // Redirect to login page after password change
            session_destroy();
            header("Location: /Frontend/multiuserlogin.php");
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
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../CSS/admin_css/admin_settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme" onclick="console.log('Logout link clicked');">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Update Profile Picture</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <img src="/Frontend/admin dashboard/uploads/profile_pics/<?php echo htmlspecialchars($admin['profile_picture']); ?>" alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px;">
                        </div>
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="update_profile_picture">Update Profile Picture</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Update Profile</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($admin['middle_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($adminEmail); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="update_profile">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-4">
            <div class="card">
                <div class="card-header">
                    <h2>Update Address</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label for="house_number" class="form-label">House Number</label>
                            <input type="text" class="form-control" id="house_number" name="house_number" value="<?php echo htmlspecialchars($admin['house_number']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="region" class="form-label">Region</label>
                            <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($admin['region']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="street" class="form-label">Street</label>
                            <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($admin['street']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($admin['barangay']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="district" class="form-label">District</label>
                            <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($admin['district']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="update_address">Update Address</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-4">
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="change_password">Change Password</button>
                    </form>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

