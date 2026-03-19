<?php
require_once 'config/config.php';

// Xóa tất cả session variables
$_SESSION = array();

// Xóa session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Xóa session
session_destroy();

// Start session mới và set flag
session_start();
$_SESSION['just_logged_out'] = true;

// Redirect về trang chủ
header("Location: index.php");
exit();
?>

