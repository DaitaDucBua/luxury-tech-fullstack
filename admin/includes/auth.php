<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__DIR__)) . '/config/config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=admin/index.php');
    exit;
}


$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang này';
    $_SESSION['message_type'] = 'error';
    header('Location: ../index.php');
    exit;
}

// Hàm helper cho admin
function getAdminUser() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>

