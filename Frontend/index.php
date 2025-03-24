<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
                    
                    <!-- Address dropdowns -->
                    <label for="province">Province:</label>
                    <select name="province" id="province" required>
                        <option value="Marinduque">Marinduque</option>
                    </select>
                    <label for="municipality">Municipality:</label>
                    <select name="municipality" id="municipality" required>
                        <option value="">Select Municipality</option>
                        <option value="Boac">Boac</option>
                        <option value="Buenavista">Buenavista</option>
                        <option value="Gasan">Gasan</option>
                        <option value="Mogpog">Mogpog</option>
                        <option value="Santa Cruz">Santa Cruz</option>
                        <option value="Torrijos">Torrijos</option>
                    </select>
                    <label for="barangay">Barangay:</label>
                    <select name="barangay" id="barangay" required>
                        <option value="">Select Barangay</option>
                    </select>
                    
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
                    <a href="login_google.php" class="social" title="Register with Google"><i class="fab fa-google"></i></a>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        // Initialize Toastr options
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Display session messages
        <?php if (isset($_SESSION['info'])): ?>
            toastr.success("<?php echo $_SESSION['info']; ?>");
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['registration_success'])): ?>
            toastr.success("<?php echo $_SESSION['registration_success']; ?>");
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo $_SESSION['error']; ?>");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

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

        // Hardcoded barangays for each municipality
        const barangays = {
            "Boac": ["Agot", "Agumaymayan", "Apitong", "Balagasan", "Bamban", "Bantad", "Batayang", "Binunga", "Caganhao", "Canat", "Catubugan", "Cawit", "Daig", "Duyay", "Hinapulan", "Isok I", "Isok II", "Laylay", "Lubang", "Malbog", "Maligaya", "Malusak", "Mansiwat", "Mogpog", "Murallon", "Paye", "Pili", "Poblacion", "Tabi", "Tabigue", "Tampus", "Tambunan", "Tanza", "Tugos"],
            "Buenavista": ["Bagacay", "Bagtingon", "Bicas-bicas", "Daykitin", "Libas", "Malbog", "Sihi", "Timbo", "Yook"],
            "Gasan": ["Antipolo", "Bacong-Bacong", "Bahi", "Banot", "Banuyo", "Bangbang", "Bognuyan", "Cabugao", "Dili", "Ipil", "Libtangin", "Mampaitan", "Mangiliol", "Pangi", "Pinggan", "Poblacion", "Tabionan", "Tapuyan", "Tiguion", "Tres Reyes", "Yook"],
            "Mogpog": ["Argao", "Balanacan", "Banto", "Bintakay", "Bocboc", "Bonga", "Butansapa", "Candahon", "Danao", "Dulong Bayan", "Gitnang Bayan", "Hinadharan", "Ino", "Janagdong", "Magapua", "Malayak", "Malusak", "Market Site", "Mataas na Bayan", "Nangka I", "Nangka II", "Paye", "Puting Buhangin", "Sayao", "Silangan", "Sumangga"],
            "Santa Cruz": ["Alobo", "Angas", "Aturan", "Bagong Silang", "Baguidbirin", "Balogo", "Banahaw", "Bangcuangan", "Biga", "Botilao", "Buyabod", "Dating Bayan", "Devilla", "Dolores", "Haguimit", "Haguimit", "Ipil", "Jolo", "Kalangkang", "Kaganhao", "Kasily", "Kilo-Kilo", "Kinyaman", "Lamesa", "Libjo", "Lipa", "Lusok", "Lyas", "Maharlika", "Maniwaya", "Marao", "Maribojoc", "Marlangga", "Masaguisi", "Masalukot", "Matalaba", "Nangka", "Pag-Asa", "Pantayin", "Pinamalayan", "Poblacion", "Poctoy", "San Antonio", "San Isidro", "San Lorenzo", "Tagum"],
            "Torrijos": ["Bangwayin", "Bayakbakin", "Bolo", "Buangan", "Cagpo", "Dampulan", "Kay Duke", "Makawayan", "Malibago", "Marlangga", "Matuyatuya", "Nangka", "Paye", "Poblacion", "Sibuyao", "Suha", "Talawan", "Tiguion"]
        };

        document.addEventListener("DOMContentLoaded", function() {
            const municipalitySelect = document.getElementById("municipality");
            const barangaySelect = document.getElementById("barangay");

            municipalitySelect.addEventListener("change", function() {
                const selectedMunicipality = municipalitySelect.value;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>'; // Clear previous options

                if (barangays[selectedMunicipality]) {
                    barangays[selectedMunicipality].forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay;
                        option.textContent = barangay;
                        barangaySelect.appendChild(option);
                    });
                }
            });
        });
    </script>
</body>
</html>