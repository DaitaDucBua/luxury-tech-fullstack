<?php
require_once 'config/config.php';

$page_title = 'Đặt hàng thành công';

$order_code = isset($_GET['order_code']) ? sanitize($_GET['order_code']) : '';

if (empty($order_code)) {
    redirect('index.php');
}

// Lấy thông tin đơn hàng
$sql = "SELECT * FROM orders WHERE order_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('index.php');
}

$order = $result->fetch_assoc();

// Lấy chi tiết đơn hàng
$detail_sql = "SELECT * FROM order_details WHERE order_id = ?";
$detail_stmt = $conn->prepare($detail_sql);
$detail_stmt->bind_param("i", $order['id']);
$detail_stmt->execute();
$order_details = $detail_stmt->get_result();

include 'includes/header.php';
?>

<div class="text-center mb-5">
    <div class="success-icon mb-4">
        <i class="fas fa-check-circle fa-5x text-success"></i>
    </div>
    <h2 class="text-success mb-3">Đặt hàng thành công!</h2>
    <p class="lead">Cảm ơn bạn đã đặt hàng tại <?php echo SITE_NAME; ?></p>
    <p>Mã đơn hàng của bạn là: <strong class="text-danger"><?php echo htmlspecialchars($order['order_code']); ?></strong></p>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Người nhận:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                        <strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['customer_address']); ?><br>
                        <strong>Phương thức thanh toán:</strong> <?php echo strtoupper($order['payment_method']) == 'COD' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?><br>
                        <strong>Trạng thái:</strong> <span class="badge bg-warning">Đang xử lý</span>
                    </div>
                </div>

                <?php if ($order['note']): ?>
                <div class="mb-3">
                    <strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['note']); ?>
                </div>
                <?php endif; ?>

                <hr>

                <h6 class="mb-3">Chi tiết sản phẩm:</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($detail = $order_details->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                <td><?php echo formatPrice($detail['price']); ?></td>
                                <td><?php echo $detail['quantity']; ?></td>
                                <td><?php echo formatPrice($detail['subtotal']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td><strong class="text-danger"><?php echo formatPrice($order['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Chúng tôi đã gửi email xác nhận đơn hàng đến <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong>. 
            Vui lòng kiểm tra hộp thư của bạn.
        </div>

        <div class="text-center">
            <a href="index.php" class="btn btn-danger me-2">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
            <?php if (isLoggedIn()): ?>
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="fas fa-box"></i> Xem đơn hàng của tôi
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

