<?php
/**
 * VNPay Payment Handler - Theo mẫu chuẩn VNPay
 */

session_start();
require_once '../config/config.php';
require_once '../config/payment-config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Lấy thông tin đơn hàng
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id === 0) {
    header('Location: ../cart.php');
    exit;
}

$order_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = {$_SESSION['user_id']}";
$order = $conn->query($order_query)->fetch_assoc();

if (!$order) {
    header('Location: ../cart.php');
    exit;
}

// Thiết lập timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_TmnCode = VNPAY_TMN_CODE;
$vnp_HashSecret = VNPAY_HASH_SECRET;
$vnp_Url = VNPAY_URL;
$vnp_Returnurl = VNPAY_RETURN_URL;

$vnp_TxnRef = $order['id'] . '_' . time();
$vnp_OrderInfo = 'Thanh toan don hang ' . $order['order_code'];
$vnp_OrderType = 'billpayment';
$vnp_Amount = intval($order['total_amount']) * 100;
$vnp_Locale = 'vn';

// Lấy IP - xử lý IPv6 localhost
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
if ($vnp_IpAddr == '::1') {
    $vnp_IpAddr = '127.0.0.1';
}

$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef
);

ksort($inputData);

// Cách 1: Hash data KHÔNG encode
$hashdata = http_build_query($inputData, '', '&');
$query = http_build_query($inputData, '', '&');

$vnp_Url = $vnp_Url . "?" . $query;

if ($vnp_HashSecret) {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
}

// Lưu transaction vào database
$stmt = $conn->prepare("INSERT INTO payment_transactions (order_id, payment_method, transaction_id, amount, status) VALUES (?, 'vnpay', ?, ?, 'pending')");
$stmt->bind_param("isd", $order_id, $vnp_TxnRef, $order['total_amount']);
$stmt->execute();

// Redirect to VNPay
header('Location: ' . $vnp_Url);
exit;
?>

