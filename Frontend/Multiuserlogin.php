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
    <!-- Toast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
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

    if (isset($_SESSION['registration_success'])) {
        echo "<div id='registrationSuccessModal' class='modal'>
                <div class='modal-content'>
                    <span class='close' onclick='closeModal()'>&times;</span>
                    <h4>Registration Successful!</h4>
                    <p>{$_SESSION['registration_success']}</p>
                    <button onclick='closeModal()'>Close</button>
                    <form action='../Backend/Multiuserlogins.php' method='POST'>
                        <input type='hidden' name='resend_confirmation' value='1'>
                        <button type='submit' class='resend-btn'>Resend Confirmation Link</button>
                    </form>
                </div>
              </div>";
        unset($_SESSION['registration_success']); // Clear the message after displaying it
    }

    if (isset($_SESSION['error'])) {
        echo "<div id='errorModal' class='modal'>
                <div class='modal-content'>
                    <span class='close' onclick='closeModal()'>&times;</span>
                    <h4>Error!</h4>
                    <p>{$_SESSION['error']}</p>
                    <button onclick='closeModal()'>Close</button>
                </div>
              </div>";
        unset($_SESSION['error']); // Clear the message after displaying it
    }
    ?>

    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form action="../Backend/Multiuserlogins.php" method="POST">
                <h1>Create Account</h1>
                <div class="social-container">
                <a href="register_facebook.php" class="social" title="Register with Facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="register_google.php" class="social" title="Register with Google"><i class="fab fa-google"></i></a>
    <a href="register_qr.php" class="social" title="Register with QR Code"><i class="fas fa-qrcode"></i></a>
                </div>
                <span>or use your email for registration</span>
                
                <!-- Scrollable form fields -->
                <div class="signup-form-fields">
                    <input type="text" name="reg_first_name" placeholder="First Name" required />
                    <input type="text" name="reg_middle_name" placeholder="Middle Name (Optional)" />
                    <input type="text" name="reg_last_name" placeholder="Last Name" required />
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="reg_password" placeholder="Password" required onkeyup="checkPasswordStrength()" />
                    
                    <progress id="strengthMeter" max="5" value="0" style="width: 100%; margin-top: 5px;"></progress>
                    <span id="strengthText"></span>

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
                <a href="" class="social" title="Register with Facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="google_login_button.php" class="social" title="Register with Google"><i class="fab fa-google"></i></a>
    <a href="" class="social" title="Register with QR Code"><i class="fas fa-qrcode"></i></a>
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
    <!-- Toast JS -->
    
    <script>
    
        // Display popup on page load if it exists
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('successModal')) {
                document.getElementById('successModal').style.display = 'block';
            }
            if (document.getElementById('registrationSuccessModal')) {
                document.getElementById('registrationSuccessModal').style.display = 'block';
            }
            if (document.getElementById('errorModal')) {
                document.getElementById('errorModal').style.display = 'block';
            }
        });

        // Close modal function
        function closeModal() {
            if (document.getElementById('successModal')) {
                document.getElementById('successModal').style.display = 'none';
            }
            if (document.getElementById('registrationSuccessModal')) {
                document.getElementById('registrationSuccessModal').style.display = 'none';
            }
            if (document.getElementById('errorModal')) {
                document.getElementById('errorModal').style.display = 'none';
            }
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
        
        // Password Strength Checker Function
        function checkPasswordStrength() {
            const password = document.querySelector('input[name="reg_password"]').value;
            const strengthMeter = document.getElementById("strengthMeter");
            const strengthText = document.getElementById("strengthText");

            let strength = 0;
            if (password.length >= 10 && password.length <= 16) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[@$!%*?&#^+=._-]/.test(password)) strength++;

            strengthMeter.value = strength;

            const strengthLabels = {
                0: "Very Weak",
                1: "Weak",
                2: "Moderate",
                3: "Good",
                4: "Strong",
                5: "Very Strong"
            };

            strengthText.textContent = strengthLabels[strength];
            strengthText.style.color = strength >= 3 ? "green" : "red";
        }
    </script>
</body>
</html>
