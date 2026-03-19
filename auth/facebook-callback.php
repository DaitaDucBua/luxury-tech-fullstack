<?php
/**
 * Facebook OAuth Callback Handler
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/social-auth-config.php';

// Verify state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter');
}

// Get authorization code
$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: ../login.php?error=facebook_auth_failed');
    exit;
}

// Exchange code for access token
$token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
$token_params = [
    'client_id' => FACEBOOK_APP_ID,
    'client_secret' => FACEBOOK_APP_SECRET,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'code' => $code
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url . '?' . http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    header('Location: ../login.php?error=facebook_token_failed');
    exit;
}

$access_token = $token_data['access_token'];

// Get user info
$user_url = 'https://graph.facebook.com/v18.0/me?fields=id,name,email,picture&access_token=' . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

if (!isset($user_data['id'])) {
    header('Location: ../login.php?error=facebook_user_failed');
    exit;
}

// Process user data
$facebook_id = $user_data['id'];
$name = $user_data['name'] ?? '';
$email = $user_data['email'] ?? '';
$avatar = $user_data['picture']['data']['url'] ?? '';

// Validate required data
if (empty($facebook_id) || empty($name)) {
    header('Location: ../login.php?error=facebook_data_invalid');
    exit;
}

// If email is empty, generate a temporary one
if (empty($email)) {
    $email = 'fb_' . $facebook_id . '@facebook.temp';
}

// Check if user exists
$check_query = "SELECT * FROM users WHERE facebook_id = ? OR email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ss", $facebook_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists - login
    $user = $result->fetch_assoc();
    
    // Update Facebook ID if not set
    if (empty($user['facebook_id'])) {
        $update_stmt = $conn->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $facebook_id, $user['id']);
        $update_stmt->execute();
    }
    
    // Update avatar if empty
    if (empty($user['avatar']) && !empty($avatar)) {
        $update_avatar = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $update_avatar->bind_param("si", $avatar, $user['id']);
        $update_avatar->execute();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'] ?? $name;
    $_SESSION['role'] = $user['role'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Write and close session to ensure it's saved
    session_write_close();
    
    header('Location: ../index.php');
    exit;
} else {
    // Create new user
    $username = strtolower(preg_replace('/[^a-z0-9]/', '', $name)) . rand(1000, 9999);
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO users (username, email, password, full_name, facebook_id, avatar, role) 
                     VALUES (?, ?, ?, ?, ?, ?, 'customer')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ssssss", $username, $email, $password, $name, $facebook_id, $avatar);
    
    if ($insert_stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $name;
        $_SESSION['role'] = 'customer';
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Write and close session to ensure it's saved
        session_write_close();
        
        header('Location: ../index.php?welcome=1');
        exit;
    } else {
        header('Location: ../login.php?error=registration_failed');
        exit;
    }
}

exit;
?>

