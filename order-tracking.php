<?php
require_once 'config/config.php';
$page_title = 'Theo dõi đơn hàng';

$order = null;
$order_details = [];
$error = '';

// Lấy mã đơn hàng từ URL hoặc form
$order_code = trim($_GET['code'] ?? $_POST['code'] ?? '');

if (!empty($order_code)) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Lấy chi tiết đơn hàng
        $stmt = $conn->prepare("SELECT od.*, p.image, p.slug FROM order_details od LEFT JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
        $stmt->bind_param("i", $order['id']);
        $stmt->execute();
        $order_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = 'Không tìm thấy đơn hàng với mã này!';
    }
}

// Map trạng thái
$status_map = [
    'pending' => ['icon' => 'clock', 'label' => 'Chờ xác nhận', 'color' => 'warning', 'step' => 1],
    'confirmed' => ['icon' => 'check-circle', 'label' => 'Đã xác nhận', 'color' => 'info', 'step' => 2],
    'shipping' => ['icon' => 'truck', 'label' => 'Đang giao hàng', 'color' => 'primary', 'step' => 3],
    'completed' => ['icon' => 'check-double', 'label' => 'Hoàn thành', 'color' => 'success', 'step' => 4],
    'cancelled' => ['icon' => 'times-circle', 'label' => 'Đã hủy', 'color' => 'danger', 'step' => 0]
];

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Form tìm kiếm -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3"><i class="fas fa-search me-2" style="color: #c9a050;"></i>Tra cứu đơn hàng</h4>
                    <form method="GET" class="row g-3">
                        <div class="col-md-9">
                            <input type="text" name="code" class="form-control form-control-lg" 
                                   placeholder="Nhập mã đơn hàng (VD: LT20231207...)" 
                                   value="<?php echo htmlspecialchars($order_code); ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-lg w-100" style="background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e;">
                                <i class="fas fa-search"></i> Tìm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
            <!-- Thông tin đơn hàng -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-3" style="background: linear-gradient(135deg, #1a1a2e, #16213e);">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-receipt me-2"></i>Đơn hàng 
                        <span style="color: #c9a050;">#<?php echo htmlspecialchars($order['order_code']); ?></span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Trạng thái -->
                    <?php $status = $status_map[$order['status']] ?? $status_map['pending']; ?>
                    <div class="text-center mb-4 p-4" style="background: rgba(201, 160, 80, 0.1); border-radius: 12px;">
                        <i class="fas fa-<?php echo $status['icon']; ?> fa-3x mb-3 text-<?php echo $status['color']; ?>"></i>
                        <h4 class="text-<?php echo $status['color']; ?> mb-0"><?php echo $status['label']; ?></h4>
                    </div>
                    
                    <!-- Timeline -->
                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="d-flex justify-content-between mb-4 position-relative">
                        <div class="progress position-absolute" style="height: 4px; top: 15px; left: 10%; width: 80%; z-index: 0;">
                            <div class="progress-bar" style="width: <?php echo ($status['step'] - 1) * 33.33; ?>%; background: #c9a050;"></div>
                        </div>
                        <?php 
                        $steps = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Hoàn thành'];
                        $i = 1;
                        foreach ($steps as $key => $label): 
                            $active = $status['step'] >= $i;
                        ?>
                        <div class="text-center" style="z-index: 1;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" 
                                 style="width: 35px; height: 35px; background: <?php echo $active ? '#c9a050' : '#ddd'; ?>; color: <?php echo $active ? '#fff' : '#999'; ?>;">
                                <?php echo $i; ?>
                            </div>
                            <small class="<?php echo $active ? 'fw-bold' : 'text-muted'; ?>" style="font-size: 11px;"><?php echo $label; ?></small>
                        </div>
                        <?php $i++; endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <!-- Thông tin giao hàng -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 style="color: #c9a050;"><i class="fas fa-user me-2"></i>Người nhận</h6>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                            <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p class="mb-0"><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($order['customer_email']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 style="color: #c9a050;"><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ giao hàng</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Sản phẩm -->
                    <h6 style="color: #c9a050;"><i class="fas fa-box me-2"></i>Sản phẩm</h6>
                    <?php foreach ($order_details as $item): ?>
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <img src="<?php echo $item['image'] ?: 'https://via.placeholder.com/60x60?text=No+Image'; ?>"
                             alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;" class="me-3">
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-medium"><?php echo htmlspecialchars($item['product_name']); ?></p>
                            <small class="text-muted">SL: <?php echo $item['quantity']; ?> x <?php echo number_format($item['price'], 0, ',', '.'); ?>đ</small>
                        </div>
                        <strong style="color: #c9a050;"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</strong>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Tổng tiền -->
                    <div class="d-flex justify-content-between align-items-center mt-4 p-3" style="background: rgba(201, 160, 80, 0.1); border-radius: 8px;">
                        <h5 class="mb-0">Tổng cộng:</h5>
                        <h4 class="mb-0" style="color: #c9a050;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</h4>
                    </div>
                    
                    <p class="text-muted mt-3 mb-0">
                        <i class="fas fa-calendar me-2"></i>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

