<?php
require_once '../Backend/connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Your existing CSS styles */
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: #C1E6BA;; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .container { background-color: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); width: 90%; max-width: 400px; padding: 30px; text-align: center; transition: transform 0.3s ease; }
        .container:hover { transform: translateY(-5px); }
        h2 { margin-bottom: 20px; font-weight: 600; color: #333; }
        .input-box { position: relative; margin-bottom: 20px; }
        .input-box input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; outline: none; transition: border-color 0.3s, box-shadow 0.3s; }
        .input-box input:focus { border-color: #007bff; box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); }
        .container button { background-color: #023336; color: #ffffff; font-size: 14px; padding: 12px 30px; border: none; border-radius: 5px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin-top: 20px; cursor: pointer; transition: background-color 0.3s, transform 0.3s; }
        .container button:hover { background-color: #C1E6BA; transform: scale(1.05); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 10px; display: none; }
        .alert-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form id="forgotPasswordForm">
            <div class="input-box">
                <input type="email" name="forgot_email" id="forgot_email" placeholder="Enter your email" required>
            </div>
            <button type="submit">Reset Password</button>
            <div id="alert" class="alert"></div>
        </form>
    </div>

    <script>
        document.getElementById("forgotPasswordForm").addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Validate email format
            const emailInput = document.getElementById("forgot_email");
            const alertDiv = document.getElementById("alert");
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(emailInput.value)) {
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = 'Please enter a valid email address.';
                alertDiv.style.display = 'block';
                return;
            }

            // Show "Valid email address. Proceeding..." message
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = 'Valid email address. Proceeding...';
            alertDiv.style.display = 'block';

            // Prepare form data for AJAX request
            const formData = new FormData();
            formData.append("forgot_email", emailInput.value);
            formData.append("forgot_password", true);

            // Perform AJAX request
            fetch("../Backend/forgot.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Update the alert div based on the server response
                if (data.includes("successfully")) {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.innerHTML = 'Reset password link sent successfully.';
                } else {
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = 'No account found with that email address.';
                }
                alertDiv.style.display = 'block';
            })
            .catch(error => {
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = 'An error occurred. Please try again later.';
                alertDiv.style.display = 'block';
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
