<?php
/**
 * MoMo Return Handler
 */

session_start();
require_once '../config/config.php';
require_once '../config/payment-config.php';

$page_title = 'Kết Quả Thanh Toán';
include '../includes/header.php';

// Get parameters
$partnerCode = $_GET['partnerCode'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$requestId = $_GET['requestId'] ?? '';
$amount = $_GET['amount'] ?? '';
$orderInfo = $_GET['orderInfo'] ?? '';
$orderType = $_GET['orderType'] ?? '';
$transId = $_GET['transId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';
$payType = $_GET['payType'] ?? '';
$responseTime = $_GET['responseTime'] ?? '';
$extraData = $_GET['extraData'] ?? '';
$signature = $_GET['signature'] ?? '';

// Verify signature
$rawHash = "accessKey=" . MOMO_ACCESS_KEY .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&message=" . $message .
           "&orderId=" . $orderId .
           "&orderInfo=" . $orderInfo .
           "&orderType=" . $orderType .
           "&partnerCode=" . $partnerCode .
           "&payType=" . $payType .
           "&requestId=" . $requestId .
           "&responseTime=" . $responseTime .
           "&resultCode=" . $resultCode .
           "&transId=" . $transId;

$checkSignature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);

// Get order_id
$order_id = intval(explode('_', $orderId)[0]);

if ($signature == $checkSignature) {
    if ($resultCode == '0') {
        // Thanh toán thành công
        $conn->query("UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE id = $order_id");
        $conn->query("UPDATE payment_transactions SET status = 'completed', transaction_no = '$transId' 
                      WHERE transaction_id = '$orderId'");
        
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-success mb-3">Thanh Toán Thành Công!</h3>
                            <p class="text-muted mb-4">Đơn hàng #<?php echo $order_id; ?> đã được thanh toán qua MoMo.</p>
                            
                            <div class="payment-details mb-4">
                                <table class="table">
                                    <tr>
                                        <td class="text-start">Mã giao dịch:</td>
                                        <td class="text-end"><strong><?php echo $transId; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Số tiền:</td>
                                        <td class="text-end"><strong class="text-danger"><?php echo number_format($amount); ?>đ</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Phương thức:</td>
                                        <td class="text-end"><strong>MoMo</strong></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="../order-detail.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>Xem Chi Tiết Đơn Hàng
                                </a>
                                <a href="../index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Về Trang Chủ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        // Thanh toán thất bại
        $conn->query("UPDATE payment_transactions SET status = 'failed' WHERE transaction_id = '$orderId'");
        
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-times-circle text-danger" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-danger mb-3">Thanh Toán Thất Bại!</h3>
                            <p class="text-muted mb-4"><?php echo $message; ?></p>
                            
                            <div class="d-grid gap-2">
                                <a href="../checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>Thử Lại
                                </a>
                                <a href="../cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-shopping-cart me-2"></i>Về Giỏ Hàng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Chữ ký không hợp lệ!
        </div>
    </div>
    <?php
}

include '../includes/footer.php';
?>

