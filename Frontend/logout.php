<?php
session_start();
session_unset();  // Clear all session data
session_destroy();  // Destroy the session

// Redirect to the login page (or home page)
header("Location: /Frontend/multiuserlogin.php");
exit();
?>
