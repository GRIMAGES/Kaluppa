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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php
    // Check for session messages
    if (isset($_SESSION['info'])) {
        echo "<div id='successModal' class='modal'>
                <div class='modal-content'>
                    <span class='close' onclick='closeModal()'>&times;</span>
                    <h4>Success!</h4>
                    <p>{$_SESSION['info']}</p>
                    <button onclick='closeModal()'>Close</button>
                </div>
              </div>";
        unset($_SESSION['info']); // Clear the message after displaying it
    }
    ?>

    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form action="../Backend/Multiuserlogins.php" method="POST">
                <h1>Create Account</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your email for registration</span>
                
                <!-- Scrollable form fields -->
                <div class="signup-form-fields">
                    <input type="text" name="reg_first_name" placeholder="First Name" required />
                    <input type="text" name="reg_middle_name" placeholder="Middle Name (Optional)" />
                    <input type="text" name="reg_last_name" placeholder="Last Name" required />
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="reg_password" placeholder="Password" required onkeyup="checkPasswordStrength()" />
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required />
                    <label for="birthday">Birthday:</label>
                    <input type="date" name="birthday" required />
                    <label for="gender">Gender:</label>
                    <select name="gender" id="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <input type="text" name="phone" placeholder="Phone Number" required />
                    <input type="text" name="house_number" placeholder="House Number" required />
                    <input type="text" name="street" placeholder="Street" required />
                    <input type="text" name="barangay" placeholder="Barangay" required />
                    <input type="text" name="district" placeholder="District" required />
                    <input type="text" name="city" placeholder="City" required />
                    <input type="text" name="region" placeholder="Region" required />
                    <input type="text" name="postal_code" placeholder="Postal Code" required />
                    <input type="checkbox" name="data_privacy" required />
                    <label for="data_privacy">I agree to the <a href="data_privacy_policy.php" target="_blank">Data Privacy Act</a></label>
                </div>
                
                <!-- Button always stays at the bottom -->
                <button type="submit" class="signup-btn">Sign Up</button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="../Backend/Multiuserlogins.php" method="POST">
                <h1>Sign in</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your account</span>
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <a href="forgots.php">Forgot your password?</a>
                <button type="submit">Sign In</button>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Links -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="JS/login_script.js"></script>
    <script>
        // Display popup on page load if it exists
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('successModal')) {
                document.getElementById('successModal').style.display = 'block';
            }
        });

        // Close modal function
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // Switch between login and signup forms
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    </script>
</body>
</html>
