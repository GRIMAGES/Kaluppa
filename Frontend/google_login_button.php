<?php
require_once 'google_config.php'; // This should include autoload already

$login_url = $client->createAuthUrl(); 
?>

<a href="<?php echo htmlspecialchars($login_url); ?>">
    <button>Login with Google</button>
</a>
