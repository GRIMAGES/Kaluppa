<?php
require_once '../Backend/connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="CSS/otpstyle.css">
</head>
<body>

<div class="container">
    <h1 class="h1">OTP Verification</h1>
    <p>Please enter the OTP sent to your email to verify your account.</p>
    
    <!-- Display an alert if OTP is sent successfully -->
    <div class="alert alert-success" id="otpSentAlert" style="display: none;">
        OTP has been sent successfully. Please check your email.
    </div>

    <form action="otpverification.php" method="GET">
        <div class="input-box">
            <input type="text" name="otp" placeholder="Enter OTP" required>
        </div>
        <button type="submit">Verify OTP</button>
    </form>

    <!-- Resend OTP Button -->
    <button class="resend-btn" onclick="resendOTP()">Resend OTP</button>
</div>

<script>
    function resendOTP() {
        // Implement resend OTP logic (e.g., make an AJAX request to send OTP again)
        document.getElementById("otpSentAlert").style.display = "block";
    }
</script>

</body>
</html>
