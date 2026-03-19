<?php
/**
 * Facebook Login Handler
 */

session_start();
require_once '../config/social-auth-config.php';

// Facebook OAuth URL
$params = [
    'client_id' => FACEBOOK_APP_ID,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'scope' => 'email,public_profile',
    'response_type' => 'code',
    'state' => bin2hex(random_bytes(16))
];

$_SESSION['oauth_state'] = $params['state'];

$facebook_login_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);

header('Location: ' . $facebook_login_url);
exit;
?>

