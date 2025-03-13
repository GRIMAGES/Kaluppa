<?php
    require_once '../../Backend/connection.php';
    session_start();

    // Redirect if not logged in
    if (!isset($_SESSION['email'])) {
        header("Location: /Frontend/index.php");
        exit();
    }

    $email = $_SESSION['email'];
    $message = '';

    // Fetch user details
    $query = "SELECT id, first_name, middle_name, last_name, email, profile_picture, password, house_number, street, barangay, district, city, region, postal_code FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $first_name, $middle_name, $last_name, $email, $profile_picture, $password, $house_number, $street, $barangay, $district, $city, $region, $postal_code);
    $stmt->fetch();
    $stmt->close();

    $target_dir = __DIR__ . "/../../admin_dashboard/uploads/profile_pics/";

    // Ensure directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_picture'])) {
        if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] === UPLOAD_ERR_OK) {
            
            $upload_dir = "https://kaluppa.online/Kaluppa/Frontend/admin dashboard/uploads/profile_pics/";
            $file_tmp = $_FILES["profile_picture"]["tmp_name"];
            $file_name = basename($_FILES["profile_picture"]["name"]);
            $target_file = $upload_dir . $file_name;
    
            // Validate file
            $check = getimagesize($file_tmp);
            if ($check === false) {
                echo "Error: File is not an image.";
                exit;
            }
    
            // Move file
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Update database with new file name
                $query = "UPDATE user SET profile_picture = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('si', $file_name, $user_id);
                $stmt->execute();
                $message = "Profile picture updated successfully.";
            } else {
                $message = "Error: Failed to move uploaded file.";
            }
        } else {
            $message = "Error: No file uploaded or temporary file missing.";
        }
    }
    
    // Update profile
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];

        $query = "UPDATE user SET first_name = ?, middle_name = ?, last_name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssi', $first_name, $middle_name, $last_name, $email, $user_id);
        $message = $stmt->execute() ? 'Profile updated successfully.' : 'Error updating profile.';
    }

    // Update address
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address'])) {
        $house_number = $_POST['house_number'];
        $street = $_POST['street'];
        $barangay = $_POST['barangay'];
        $district = $_POST['district'];
        $city = $_POST['city'];
        $region = $_POST['region'];
        $postal_code = $_POST['postal_code'];

        $query = "UPDATE user SET house_number = ?, street = ?, barangay = ?, district = ?, city = ?, region = ?, postal_code = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssssi', $house_number, $street, $barangay, $district, $city, $region, $postal_code, $user_id);
        $message = $stmt->execute() ? 'Address updated successfully.' : 'Error updating address.';
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $password)) {
            if ($new_password === $confirm_password) {
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE user SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('si', $new_password_hashed, $user_id);
                $message = $stmt->execute() ? 'Password changed successfully.' : 'Error changing password.';
            } else {
                $message = 'New passwords do not match.';
            }
        } else {
            $message = 'Current password is incorrect.';
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profile Settings</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../CSS/user_css/user_settings.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            .form-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .form-row .form-group {
                flex: 1;
                min-width: 200px;
            }
            .container {
                margin-top: 80px; /* Adjust this value to move the cards lower */
            }
        </style>
    </head>
    <body style="background-color: #ddead1;">
    <?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-black"> <!-- Add custom-modal class -->
            <div class="modal-header bg-theme text-white"> <!-- Add bg-theme and text-white classes -->
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

    <div class="container"> 
        <div id="message" class="text-center"><?php if ($message) echo '<div class="alert alert-success">' . $message . '</div>'; ?></div>

        <!-- Back to Dashboard button placed lower and left-aligned -->

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow p-3">
                    <h4 class="mb-3">Update Profile</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">First Name:</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo $first_name; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name:</label>
                            <input type="text" name="middle_name" class="form-control" value="<?php echo $middle_name; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name:</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo $last_name; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <div class="card shadow mt-4 p-3">
                    <h4 class="mb-3">Change Password</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password:</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password:</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password:</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
        <div class="card shadow p-3">
            <h4 class="mb-3 text-center">Profile Picture</h4>
            <div class="text-center">
            <img src="/Frontend/admin_dashboard/uploads/profile_pics/<?php echo htmlspecialchars($profile_picture); ?>" 
     alt="Profile Picture" class="rounded-circle img-fluid" 
     style="max-width: 200px;">

            </div>
            <form method="POST" enctype="multipart/form-data" class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Upload New Picture:</label>
                    <input type="file" name="profile_picture" class="form-control">
                </div>
                <button type="submit" name="upload_picture" class="btn btn-success w-100">Upload</button>
            </form>
        </div>

        <div class="card shadow mt-4 p-3">
            <h4 class="mb-3">Update Address</h4>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">House Number:</label>
                        <input type="text" name="house_number" class="form-control" value="<?php echo $house_number; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Street:</label>
                        <input type="text" name="street" class="form-control" value="<?php echo $street; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Barangay:</label>
                        <input type="text" name="barangay" class="form-control" value="<?php echo $barangay; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">District:</label>
                        <input type="text" name="district" class="form-control" value="<?php echo $district; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City:</label>
                        <input type="text" name="city" class="form-control" value="<?php echo $city; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Region:</label>
                        <input type="text" name="region" class="form-control" value="<?php echo $region; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Postal Code:</label>
                        <input type="text" name="postal_code" class="form-control" value="<?php echo $postal_code; ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_address" class="btn btn-info mt-3">Update Address</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        }
    });
    </script>
    </body>
    </html>
