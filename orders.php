<?php
require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    showMessage('Vui lòng đăng nhập để xem đơn hàng', 'warning');
    redirect('login.php?redirect=orders.php');
}

$page_title = 'Đơn hàng của tôi';
$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}
$orders_count = count($orders);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center space-x-2 text-sm">
        <li><a href="index.php" class="text-amber-600 hover:text-amber-700 transition-colors">Trang chủ</a></li>
        <li class="text-gray-400">/</li>
        <li class="text-gray-600">Đơn hàng của tôi</li>
    </ol>
</nav>

<h2 class="mb-6 text-2xl font-semibold text-gray-800 flex items-center">
    <i class="fas fa-box mr-3 text-amber-600"></i> Đơn hàng của tôi
</h2>

<?php if ($orders_count > 0): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Mã đơn hàng</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Ngày đặt</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Tổng tiền</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Thanh toán</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <strong class="text-gray-900 font-semibold"><?php echo htmlspecialchars($order['order_code']); ?></strong>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <strong class="text-red-600 font-semibold text-base"><?php echo formatPrice($order['total_amount']); ?></strong>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $status_classes = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'confirmed' => 'bg-blue-100 text-blue-800',
                            'shipping' => 'bg-indigo-100 text-indigo-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                        $status_texts = [
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'shipping' => 'Đang giao',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy'
                        ];
                        $status_class = $status_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                        $status_text = $status_texts[$order['status']] ?? 'Không xác định';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-1">
                            <?php
                            $payment_method_text = [
                                'cod' => 'COD',
                                'vnpay' => 'VNPay',
                                'momo' => 'MoMo',
                                'bank_transfer' => 'Chuyển khoản'
                            ];
                            $method = $payment_method_text[strtolower($order['payment_method'])] ?? $order['payment_method'];
                            ?>
                            <div class="text-sm font-medium text-gray-900"><?php echo $method; ?></div>
                            <?php
                            $payment_status_classes = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'paid' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-gray-100 text-gray-800'
                            ];
                            $payment_status_texts = [
                                'pending' => 'Chờ thanh toán',
                                'paid' => 'Đã thanh toán',
                                'failed' => 'Thất bại',
                                'refunded' => 'Đã hoàn tiền'
                            ];
                            $payment_status_class = $payment_status_classes[$order['payment_status']] ?? 'bg-gray-100 text-gray-800';
                            $payment_status_text = $payment_status_texts[$order['payment_status']] ?? '-';
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $payment_status_class; ?>">
                                <?php echo $payment_status_text; ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <a href="order-detail.php?order_code=<?php echo $order['order_code']; ?>" 
                           class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-eye mr-1"></i> Chi tiết
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-gray-200">
        <?php foreach ($orders as $order): ?>
        <div class="p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($order['order_code']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="text-right">
                    <div class="text-red-600 font-semibold text-base"><?php echo formatPrice($order['total_amount']); ?></div>
                </div>
            </div>
            
            <div class="space-y-2 mb-3">
                <div>
                    <?php
                    $status_classes = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-blue-100 text-blue-800',
                        'shipping' => 'bg-indigo-100 text-indigo-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    $status_texts = [
                        'pending' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'shipping' => 'Đang giao',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy'
                    ];
                    $status_class = $status_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                    $status_text = $status_texts[$order['status']] ?? 'Không xác định';
                    ?>
                    <div class="text-xs text-gray-600 mb-1">Trạng thái:</div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                <div>
                    <?php
                    $payment_method_text = [
                        'cod' => 'COD',
                        'vnpay' => 'VNPay',
                        'momo' => 'MoMo',
                        'bank_transfer' => 'Chuyển khoản'
                    ];
                    $method = $payment_method_text[strtolower($order['payment_method'])] ?? $order['payment_method'];
                    ?>
                    <div class="text-xs text-gray-600 mb-1">Thanh toán:</div>
                    <div class="text-sm font-medium text-gray-900 mb-1"><?php echo $method; ?></div>
                    <?php
                    $payment_status_classes = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'paid' => 'bg-green-100 text-green-800',
                        'failed' => 'bg-red-100 text-red-800',
                        'refunded' => 'bg-gray-100 text-gray-800'
                    ];
                    $payment_status_texts = [
                        'pending' => 'Chờ thanh toán',
                        'paid' => 'Đã thanh toán',
                        'failed' => 'Thất bại',
                        'refunded' => 'Đã hoàn tiền'
                    ];
                    $payment_status_class = $payment_status_classes[$order['payment_status']] ?? 'bg-gray-100 text-gray-800';
                    $payment_status_text = $payment_status_texts[$order['payment_status']] ?? '-';
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $payment_status_class; ?>">
                        <?php echo $payment_status_text; ?>
                    </span>
                </div>
            </div>
            
            <a href="order-detail.php?order_code=<?php echo $order['order_code']; ?>" 
               class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-eye mr-2"></i> Xem chi tiết
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="text-center py-12">
    <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <i class="fas fa-box-open text-4xl text-gray-400"></i>
    </div>
    <h4 class="text-xl font-semibold text-gray-800 mb-2">Bạn chưa có đơn hàng nào</h4>
    <p class="text-gray-500 mb-6">Hãy mua sắm ngay để trải nghiệm dịch vụ của chúng tôi</p>
    <a href="index.php" 
       class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
        <i class="fas fa-shopping-bag mr-2"></i> Mua sắm ngay
    </a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

