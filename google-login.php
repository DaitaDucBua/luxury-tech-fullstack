<?php
/**
 * Google Login Handler
 */

require_once 'config/config.php';

// Google OAuth URL
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'scope' => 'email profile',
    'response_type' => 'code',
    'access_type' => 'online',
    'state' => bin2hex(random_bytes(16))
];

$_SESSION['oauth_state'] = $params['state'];

$google_login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $google_login_url);
exit;
?>

