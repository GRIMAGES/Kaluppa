<?php
    require_once '../../Backend/connection.php';
    session_start();

    // Redirect if not logged in
    if (!isset($_SESSION['email'])) {
        header("Location: /Frontend/multiuserlogin.php");
        exit();
    }

    $email = $_SESSION['email'];
    $message = '';

    // Fetch user details
    $query = "SELECT id, first_name, middle_name, last_name, email, profile_picture, password FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $first_name, $middle_name, $last_name, $email, $profile_picture, $password);
    $stmt->fetch();
    $stmt->close();

    $target_dir = __DIR__ . "/../../admin dashboard/uploads/profile_pics/";

    // Ensure directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_picture'])) {
        if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] === UPLOAD_ERR_OK) {
            
            $upload_dir = "C:/xampp/php/Kaluppa/Frontend/admin dashboard/uploads/profile_pics/";
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
        <style>
            body {
                background: linear-gradient(135deg, #e3f2fd, #ffffff);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .container {
                max-width: 1100px;
            }
            .card {
                border-radius: 15px;
                padding: 20px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                background: #ffffff;
            }
            .profile-picture {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .profile-picture img {
                border-radius: 50%;
                border: 4px solid #007bff;
                width: 120px;
                height: 120px;
                object-fit: cover;
            }
            .form-control {
                border-radius: 10px;
                padding: 10px;
            }
            .btn {
                border-radius: 10px;
                padding: 10px;
            }
            img.rounded-circle {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #ddd; /* Optional */
    }
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        </style>
    </head>
    <body>

    <div class="container">
        <h2 class="text-center mb-4">Profile Settings</h2>
        <div id="message" class="text-center"><?php if ($message) echo '<div class="alert alert-success">' . $message . '</div>'; ?></div>

        <!-- Back to Dashboard button placed lower and left-aligned -->
        <div class="mb-3">
            <a href="user_dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">⬅ Back to Dashboard</a>
        </div>

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
                        <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
        <div class="card shadow p-3">
            <h4 class="mb-3 text-center">Profile Picture</h4>
            <div class="text-center">
            <img src="/Frontend/admin dashboard/uploads/profile_pics/<?php echo htmlspecialchars($profile_picture); ?>" 
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
