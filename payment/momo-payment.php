<?php
/**
 * MoMo Payment Handler
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

// MoMo payment parameters
$orderId = $order['id'] . '_' . time();
$orderInfo = 'Thanh toan don hang #' . $order['id'];
$amount = (string)$order['total_amount'];
$requestId = time() . "";
$extraData = "";

// Create signature
$rawHash = "accessKey=" . MOMO_ACCESS_KEY .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&ipnUrl=" . MOMO_NOTIFY_URL .
           "&orderId=" . $orderId .
           "&orderInfo=" . $orderInfo .
           "&partnerCode=" . MOMO_PARTNER_CODE .
           "&redirectUrl=" . MOMO_RETURN_URL .
           "&requestId=" . $requestId .
           "&requestType=captureWallet";

$signature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);

$data = array(
    'partnerCode' => MOMO_PARTNER_CODE,
    'partnerName' => "LuxuryTech",
    'storeId' => "LuxuryTech",
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => MOMO_RETURN_URL,
    'ipnUrl' => MOMO_NOTIFY_URL,
    'lang' => 'vi',
    'extraData' => $extraData,
    'requestType' => 'captureWallet',
    'signature' => $signature
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, MOMO_ENDPOINT);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
));

$result = curl_exec($ch);
curl_close($ch);

$jsonResult = json_decode($result, true);

if (isset($jsonResult['payUrl'])) {
    // Lưu transaction
    $conn->query("INSERT INTO payment_transactions (order_id, payment_method, transaction_id, amount, status) 
                  VALUES ($order_id, 'momo', '$orderId', {$order['total_amount']}, 'pending')");
    
    // Redirect to MoMo
    header('Location: ' . $jsonResult['payUrl']);
} else {
    header('Location: ../checkout.php?error=momo_failed');
}

exit;
?>

