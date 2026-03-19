<?php
require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    showMessage('Vui lòng đăng nhập để xem đơn hàng', 'warning');
    redirect('login.php?redirect=order-detail.php');
}

$page_title = 'Chi tiết đơn hàng';
$order_code = isset($_GET['order_code']) ? sanitize($_GET['order_code']) : '';

if (empty($order_code)) {
    redirect('orders.php');
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$sql = "SELECT * FROM orders WHERE order_code = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $order_code, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    showMessage('Không tìm thấy đơn hàng', 'error');
    redirect('orders.php');
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

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="orders.php">Đơn hàng của tôi</a></li>
        <li class="breadcrumb-item active">Chi tiết đơn hàng</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Đơn hàng #<?php echo htmlspecialchars($order['order_code']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <?php
                            $status_badges = [
                                'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
                                'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
                                'shipping' => '<span class="badge bg-primary">Đang giao</span>',
                                'completed' => '<span class="badge bg-success">Hoàn thành</span>',
                                'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
                            ];
                            echo $status_badges[$order['status']] ?? '';
                            ?>
                        </p>
                        <p><strong>Thanh toán:</strong> <?php echo strtoupper($order['payment_method']) == 'COD' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    </div>
                </div>

                <p><strong>Địa chỉ giao hàng:</strong><br>
                <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>

                <?php if ($order['note']): ?>
                <p><strong>Ghi chú:</strong><br>
                <?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
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

        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
        </a>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Trạng thái đơn hàng</h6>
            </div>
            <div class="card-body">
                <div class="order-timeline">
                    <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'confirmed', 'shipping', 'completed']) ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Đơn hàng đã đặt</strong>
                            <br><small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'shipping', 'completed']) ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Đã xác nhận</strong>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo in_array($order['status'], ['shipping', 'completed']) ? 'active' : ''; ?>">
                        <i class="fas fa-shipping-fast"></i>
                        <div>
                            <strong>Đang giao hàng</strong>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $order['status'] == 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i>
                        <div>
                            <strong>Đã giao hàng</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.order-timeline .timeline-item {
    padding: 15px 0;
    border-left: 2px solid #ddd;
    padding-left: 30px;
    position: relative;
    color: #999;
}

.order-timeline .timeline-item i {
    position: absolute;
    left: -10px;
    background: white;
    color: #ddd;
}

.order-timeline .timeline-item.active {
    border-left-color: #0d6efd;
    color: #333;
}

.order-timeline .timeline-item.active i {
    color: #0d6efd;
}
</style>

<?php include 'includes/footer.php'; ?>

