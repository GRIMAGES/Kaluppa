<?php
require_once __DIR__ . '/../vendor/autoload.php';
 

$client = new Google_Client();
$client->setClientId('275298191714-6di06o4lcemkk8rhhvlba5griugtpnj6.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://kaluppa.online/Kaluppa/Frontend/login_google.php');
$client->addScope('email');
$client->addScope('profile');
?>
