<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Keep this if your vendor folder is still one directory up

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // <-- FIXED: points to correct folder
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');
?>
