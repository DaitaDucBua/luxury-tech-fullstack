<?php
/**
 * Google OAuth Callback Handler
 */

require_once 'config/config.php';

// Verify state
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid state parameter');
}

// Get authorization code
$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: login.php?error=google_auth_failed');
    exit;
}

// Exchange code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'code' => $code,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    header('Location: login.php?error=google_token_failed');
    exit;
}

$access_token = $token_data['access_token'];

// Get user info
$user_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

if (!isset($user_data['id'])) {
    header('Location: login.php?error=google_user_failed');
    exit;
}

// Process user data
$google_id = $conn->real_escape_string($user_data['id']);
$name = $conn->real_escape_string($user_data['name'] ?? '');
$email = $conn->real_escape_string($user_data['email'] ?? '');
$avatar = $conn->real_escape_string($user_data['picture'] ?? '');

// Check if user exists
$check_query = "SELECT * FROM users WHERE google_id = '$google_id' OR email = '$email'";
$result = $conn->query($check_query);

if ($result->num_rows > 0) {
    // User exists - login
    $user = $result->fetch_assoc();
    
    // Update Google ID if not set
    if (empty($user['google_id'])) {
        $conn->query("UPDATE users SET google_id = '$google_id' WHERE id = {$user['id']}");
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];

    header('Location: index.php');
} else {
    // Create new user
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . rand(1000, 9999);
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO users (username, email, password, full_name, google_id, avatar, role) 
                     VALUES ('$username', '$email', '$password', '$name', '$google_id', '$avatar', 'customer')";
    
    if ($conn->query($insert_query)) {
        $user_id = $conn->insert_id;
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'customer';
        $_SESSION['full_name'] = $name;

        header('Location: index.php?welcome=1');
    } else {
        header('Location: login.php?error=registration_failed');
    }
}

exit;
?>

