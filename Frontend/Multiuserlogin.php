<?php
require_once '../Backend/connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALUPPA - Multiuser Login & Register</title>

    <!-- CSS Links -->
    <link rel="stylesheet" href="CSS/login_style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    
</head>

<body>
    <?php
    // Check for session messages
    if (isset($_SESSION['info'])) {
        echo "<div class='popup' id='popupMessage'>
                <button class='close-btn' onclick='closePopup()'>&times;</button>
                <h4>Success!</h4>
                <p>{$_SESSION['info']}</p>
              </div>
              <div class='overlay' id='popupOverlay' onclick='closePopup()'></div>";
        unset($_SESSION['info']); // Clear the message after displaying it
    }
    ?>

    <div class="container" id="container">
        <!-- Registration Form -->
        <div class="form-container sign-up" id="signupContainer">
            <form action="../Backend/Multiuserlogins.php" method="POST">
                <h2>Create Account</h2>
                <div class="input-box">
                    <input class="form-control" type="text" name="reg_first_name" placeholder="First Name" required>
                </div>
                <div class="input-box">
                    <input class="form-control" type="text" name="reg_middle_name" placeholder="Middle Name (Optional)">
                </div>
                <div class="input-box">
                    <input class="form-control" type="text" name="reg_last_name" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <input class="form-control" type="password" id="password" name="reg_password" placeholder="Password" required>
                    <small class="form-text text-muted">Password must be 8-20 characters and include special and uppercase characters.</small>
                </div>
                <div class="form-group">
                    <input class="form-control" type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="input-box">
                    <input class="form-control" type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <label for="birthday">Birthday:</label>
                    <input class="form-control" type="date" name="birthday" required>
                </div>
                <div class="input-box">
                    <label for="gender">Gender:</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit" style="background-color: #023336; border-color: #023336; color: white;">Signup</button>
                </div>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-container sign-in">
            <form action="../Backend/Multiuserlogins.php" method="POST">
                <img src="kaalu.png" alt="Your Logo" class="logo">
                <h1>Login</h1>
                <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); // Clear the error after displaying ?>
        <?php endif; ?>
                
                
                
                <div class="input-box">
                    <input class="form-control" type="text" name="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <input class="form-control" type="password" name="password" placeholder="Password" required>
                </div>
                <a href="forgots.php">Forgot Your Password?</a>
                <button type="submit" class="btn btn-primary" style="background-color: #023336; border-color: #023336; color: white;">Login</button>

            </form>
        </div>

        <!-- Toggle Panels for Login and Signup -->
        <div class="toggle-container">
    <div class="toggle">
        <div class="toggle-panel toggle-left">
            <h1>Good Day</h1>
            <p>Enter your personal details to access the site features.</p>
            <button class="btn" id="login" style="background-color: #28a745; border-color: #28a745; color: white;">Login</button>
        </div>
        <div class="toggle-panel toggle-right">
            <h1>Good Day</h1>
            <p>Register with your details to use all of the site’s features.</p>
            <button class="btn" id="sign_up" style="background-color: #28a745; border-color: #28a745; color: white;">Register</button>
        </div>
    </div>
</div>

    <!-- JS Links -->
    <script src="JS/login_script.js"></script>
    <script>
        // Display popup on page load if it exists
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('popupMessage')) {
                document.getElementById('popupMessage').style.display = 'block';
                document.getElementById('popupOverlay').style.display = 'block';
            }
        });

        // Close popup function
        function closePopup() {
            document.getElementById('popupMessage').style.display = 'none';
            document.getElementById('popupOverlay').style.display = 'none';
        }
;

// Close error popup function

    </script>
</body>

</html>
