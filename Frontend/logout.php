<?php
session_start();
session_unset();
session_destroy();
header("Location: /Kaluppa/Frontend/index.php");
exit();
?>
