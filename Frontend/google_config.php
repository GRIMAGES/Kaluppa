<?php
require_once __DIR__ . '/../vendor/autoload.php';
 

$client = new Google_Client();
$client->setClientId('28291478845-l3qaf47fh434u9q8ov5upl1979eprm1b.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://kaluppa.online/Kaluppa/Frontend/login_google.php');
$client->addScope('email');
$client->addScope('profile');
?>
