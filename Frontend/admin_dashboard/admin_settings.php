<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name, first_name, middle_name, last_name, barangay, province, municipality, phone, profile_picture, birthday FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? '';
$currentAddress = [
    'province' => $admin['province'] ?? '',
    'municipality' => $admin['municipality'] ?? '',
    'barangay' => $admin['barangay'] ?? ''
];
$currentBirthday = $admin['birthday'] ?? '';
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

    $_SESSION['success_message'] = "Profile updated successfully!";
    header("Location: admin_settings.php");
    exit();
}

// Update Profile Picture
if (isset($_POST['update_profile_picture'])) {
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../../Frontend/admin_dashboard/uploads/profile_pics/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        chmod($targetDir, 0777); // Ensure the directory has the correct permissions

        $profilePicture = basename($_FILES['profile_picture']['name']);
        $targetFile = $targetDir . $profilePicture;

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $query = "UPDATE user SET profile_picture = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $profilePicture, $adminEmail);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Profile picture updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to upload profile picture. Please check directory permissions.";
        }

        header("Location: admin_settings.php");
        exit();
    }
}

// Update Address
if (isset($_POST['update_address'])) {
    $barangay = $_POST['barangay'];
    $province = $_POST['province'];
    $municipality = $_POST['municipality'];

    $query = "UPDATE user SET barangay = ?, province = ?, municipality = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $barangay, $province, $municipality, $adminEmail);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Address updated successfully!";
    header("Location: admin_settings.php");
    exit();
}

// Update Birthday
if (isset($_POST['update_birthday'])) {
    $birthday = $_POST['birthday'];

    $query = "UPDATE user SET birthday = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $birthday, $adminEmail);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Birthday updated successfully!";
    header("Location: admin_settings.php");
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
    <script>
        const municipalities = {
            "Marinduque": ["Boac", "Buenavista", "Gasan", "Mogpog", "Santa Cruz", "Torrijos"]
        };

        const barangays = {
            "Boac": ["Agot", "Agumaymayan", "Apitong", "Balagasan", "Bamban", "Bantad", "Batayang", "Binunga", "Caganhao", "Canat", "Catubugan", "Cawit", "Daig", "Duyay", "Hinapulan", "Isok I", "Isok II", "Laylay", "Lubang", "Malbog", "Maligaya", "Malusak", "Mansiwat", "Mogpog", "Murallon", "Paye", "Pili", "Poblacion", "Tabi", "Tabigue", "Tampus", "Tambunan", "Tanza", "Tugos"],
            "Buenavista": ["Bagacay", "Bagtingon", "Bicas-bicas", "Daykitin", "Libas", "Malbog", "Sihi", "Timbo", "Yook"],
            "Gasan": ["Antipolo", "Bacong-Bacong", "Bahi", "Banot", "Banuyo", "Bangbang", "Bognuyan", "Cabugao", "Dili", "Ipil", "Libtangin", "Mampaitan", "Mangiliol", "Pangi", "Pinggan", "Poblacion", "Tabionan", "Tapuyan", "Tiguion", "Tres Reyes", "Yook"],
            "Mogpog": ["Argao", "Balanacan", "Banto", "Bintakay", "Bocboc", "Bonga", "Butansapa", "Candahon", "Danao", "Dulong Bayan", "Gitnang Bayan", "Hinadharan", "Ino", "Janagdong", "Magapua", "Malayak", "Malusak", "Market Site", "Mataas na Bayan", "Nangka I", "Nangka II", "Paye", "Puting Buhangin", "Sayao", "Silangan", "Sumangga"],
            "Santa Cruz": ["Alobo", "Angas", "Aturan", "Bagong Silang", "Baguidbirin", "Balogo", "Banahaw", "Bangcuangan", "Biga", "Botilao", "Buyabod", "Dating Bayan", "Devilla", "Dolores", "Haguimit", "Haguimit", "Ipil", "Jolo", "Kalangkang", "Kaganhao", "Kasily", "Kilo-Kilo", "Kinyaman", "Lamesa", "Libjo", "Lipa", "Lusok", "Lyas", "Maharlika", "Maniwaya", "Marao", "Maribojoc", "Marlangga", "Masaguisi", "Masalukot", "Matalaba", "Nangka", "Pag-Asa", "Pantayin", "Pinamalayan", "Poblacion", "Poctoy", "San Antonio", "San Isidro", "San Lorenzo", "Tagum"],
            "Torrijos": ["Bangwayin", "Bayakbakin", "Bolo", "Buangan", "Cagpo", "Dampulan", "Kay Duke", "Makawayan", "Malibago", "Marlangga", "Matuyatuya", "Nangka", "Paye", "Poblacion", "Sibuyao", "Suha", "Talawan", "Tiguion"]
        };

        document.addEventListener("DOMContentLoaded", function () {
            const provinceSelect = document.getElementById("province");
            const municipalitySelect = document.getElementById("municipality");
            const barangaySelect = document.getElementById("barangay");

            provinceSelect.addEventListener("change", function () {
                const selectedProvince = provinceSelect.value;
                municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                if (municipalities[selectedProvince]) {
                    municipalities[selectedProvince].forEach(municipality => {
                        const option = document.createElement("option");
                        option.value = municipality;
                        option.textContent = municipality;
                        municipalitySelect.appendChild(option);
                    });
                }
            });

            municipalitySelect.addEventListener("change", function () {
                const selectedMunicipality = municipalitySelect.value;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                if (barangays[selectedMunicipality]) {
                    barangays[selectedMunicipality].forEach(barangay => {
                        const option = document.createElement("option");
                        option.value = barangay;
                        option.textContent = barangay;
                        barangaySelect.appendChild(option);
                    });
                }
            });
        });
    </script>
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
                                <label>Province</label>
                                <select id="province" name="province" class="form-control" required>
                                    <option value="">Select Province</option>
                                    <option value="Marinduque" <?php echo ($admin['province'] ?? '') === 'Marinduque' ? 'selected' : ''; ?>>Marinduque</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Municipality</label>
                                <select id="municipality" name="municipality" class="form-control" required>
                                    <option value="">Select Municipality</option>
                                    <!-- Dynamically populated -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Barangay</label>
                                <select id="barangay" name="barangay" class="form-control" required>
                                    <option value="">Select Barangay</option>
                                    <!-- Dynamically populated -->
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info w-100" name="update_address">Update Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Current Address -->
    <div class="card mt-4">
        <div class="card-header"><h4>Current Address</h4></div>
        <div class="card-body">
            <p><strong>Province:</strong> <?php echo htmlspecialchars($currentAddress['province']); ?></p>
            <p><strong>Municipality:</strong> <?php echo htmlspecialchars($currentAddress['municipality']); ?></p>
            <p><strong>Barangay:</strong> <?php echo htmlspecialchars($currentAddress['barangay']); ?></p>
        </div>
    </div>

    <!-- Display Current Birthday -->
    <div class="card mt-4">
        <div class="card-header"><h4>Current Birthday</h4></div>
        <div class="card-body">
            <p><strong>Birthday:</strong> <?php echo htmlspecialchars($currentBirthday); ?></p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success mt-3">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Update Birthday -->
    <div class="card mt-4">
        <div class="card-header"><h4>Update Birthday</h4></div>
        <div class="card-body">
            <form method="POST" action="admin_settings.php">
                <div class="mb-3">
                    <label for="birthday">New Birthday</label>
                    <input type="date" class="form-control" name="birthday" value="<?php echo htmlspecialchars($currentBirthday); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" name="update_birthday">Update Birthday</button>
            </form>
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
