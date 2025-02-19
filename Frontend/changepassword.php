<?php
session_start();
require_once '../Backend/connection.php';

// Check if the reset token is set in the URL
if (!isset($_GET['reset_token']) || empty($_GET['reset_token'])) {
    echo "Invalid access. Please request a password reset.";
    exit;
}

// Validate the reset token with the database
$resetToken = $_GET['reset_token'];
$stmt = $conn->prepare("SELECT id FROM user WHERE reset_token = ?");
$stmt->bind_param("s", $resetToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invalid or expired token.";
    exit;
}

// If valid, fetch user information and set session variable
$user = $result->fetch_assoc();
$_SESSION['id'] = $user['id']; // Store user ID in session
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="CSS/change_style.css"> <!-- Update path as necessary -->
    <style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6); /* Darker overlay */
        padding-top: 60px;
    }
    
    .modal-content {
        background-color: #ffffff;
        margin: auto;
        padding: 30px;
        border-radius: 10px; /* Rounded corners */
        width: 60%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); /* Soft shadow */
        text-align: center;
        animation: fadeIn 0.5s; /* Fade-in animation */
    }

    .modal-content span {
        font-size: 18px;
        color: #333;
        font-weight: 500;
    }

    /* Button style for consistency */
    button {
        background-color: #023336; /* Green background */
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #45a049; /* Slightly darker on hover */
    }

    /* Fade-in animation for modal */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

    <script>
        function validatePassword() {
            const newPassword = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            const requirements = document.querySelectorAll('.requirement');

            const lengthValid = newPassword.value.length >= 12 && newPassword.value.length <= 16;
            const upperCaseValid = /[A-Z]/.test(newPassword.value);
            const lowerCaseValid = /[a-z]/.test(newPassword.value);
            const specialCharValid = /[!@#$%^&*(),.?":{}|<>]/.test(newPassword.value);

            requirements[0].className = lengthValid ? 'requirement valid' : 'requirement';
            requirements[1].className = upperCaseValid ? 'requirement valid' : 'requirement';
            requirements[2].className = lowerCaseValid ? 'requirement valid' : 'requirement';
            requirements[3].className = specialCharValid ? 'requirement valid' : 'requirement';

            if (!lengthValid || !upperCaseValid || !lowerCaseValid || !specialCharValid) {
                alert("Password does not meet the required criteria.");
                return false;
            }

            if (newPassword.value !== confirmPassword.value) {
                alert("New passwords do not match.");
                return false;
            }

            return true;
        }

        function showModal(message) {
            const modal = document.getElementById("myModal");
            document.getElementById("modalMessage").innerText = message;
            modal.style.display = "block";
            setTimeout(function() {
                modal.style.display = "none";
                window.location.href = '../Frontend/multiuserlogin.php';
            }, 3000);
        }

        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form");
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                if (validatePassword()) {
                    const formData = new FormData(form);
                    fetch('../Backend/forgotpassword.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showModal(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        <form>
            <div class="input-box">
                <input type="password" name="new_password" placeholder="New Password" required>
                <div class="password-requirements">
                    <div class="requirement">12-16 characters long</div>
                    <div class="requirement">At least one uppercase letter</div>
                    <div class="requirement">At least one lowercase letter</div>
                    <div class="requirement">At least one special character</div>
                </div>
            </div>
            <div class="input-box">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <button type="submit" name="change_password" style="background-color: #28a745; border-color: #28a745; color: white;">Change Password</button>
        </form>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span id="modalMessage"></span>
        </div>
    </div>
</body>
</html>
