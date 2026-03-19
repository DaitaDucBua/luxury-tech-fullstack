<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user' => null
];

if (isLoggedIn()) {
    $response['logged_in'] = true;
    $response['user'] = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'] ?? 'customer',
        'email' => $_SESSION['email'] ?? ''
    ];
}

echo json_encode($response);
?>

