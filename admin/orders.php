<?php
require_once 'includes/auth.php';
require_once '../includes/order-email.php';

$page_title = 'Quản lý đơn hàng';

// Xử lý cập nhật trạng thái
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status']);

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        // Gửi email thông báo cho khách
        sendOrderStatusEmail($order_id, $new_status);
        $_SESSION['message'] = 'Cập nhật trạng thái thành công và đã gửi email thông báo!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Lỗi khi cập nhật trạng thái!';
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: orders.php');
    exit;
}

// Lọc đơn hàng
$where = "1=1";
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = sanitize($_GET['status']);
    $where .= " AND o.status = '$status_filter'";
}

// Lấy danh sách đơn hàng
$sql = "SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE $where
        ORDER BY o.created_at DESC";
$orders = $conn->query($sql);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-shopping-cart mr-3 text-primary"></i> Quản lý đơn hàng
    </h1>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="mb-6 p-4 rounded-xl border-l-4 <?php 
    $alert_classes = [
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'danger' => 'bg-red-50 border-red-500 text-red-800',
        'warning' => 'bg-amber-50 border-amber-500 text-amber-800',
        'info' => 'bg-blue-50 border-blue-500 text-blue-800'
    ];
    $msg_type = $_SESSION['message_type'] ?? 'info';
    echo $alert_classes[$msg_type] ?? $alert_classes['info'];
?>">
    <div class="flex items-start justify-between">
        <p class="font-semibold flex-1"><?php echo $_SESSION['message']; ?></p>
        <button type="button" class="ml-4 text-gray-500 hover:text-gray-700" data-bs-dismiss="alert">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md mb-6 overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
            <i class="fas fa-filter mr-2"></i> Lọc đơn hàng
        </h2>
    </div>
    <div class="p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-10">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                <select name="status" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                        onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Chờ xác nhận</option>
                    <option value="confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'confirmed') ? 'selected' : ''; ?>>Đã xác nhận</option>
                    <option value="shipping" <?php echo (isset($_GET['status']) && $_GET['status'] == 'shipping') ? 'selected' : ''; ?>>Đang giao</option>
                    <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <a href="orders.php" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách đơn hàng
        </h2>
        <span class="px-4 py-1 bg-primary/20 text-primary font-semibold rounded-full text-sm">
            <?php echo $orders->num_rows; ?> đơn hàng
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-900">
                <tr>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Mã đơn hàng</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden md:table-cell">Số điện thoại</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Tổng tiền</th>
                    <th class="px-4 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Thanh toán</th>
                    <th class="px-4 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden xl:table-cell">Ngày đặt</th>
                    <th class="px-4 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($order = $orders->fetch_assoc()): ?>
                <tr data-order-id="<?php echo $order['id']; ?>" class="hover:bg-primary/5 transition-colors">
                    <td class="px-4 py-4 whitespace-nowrap">
                        <strong class="text-primary font-semibold"><?php echo htmlspecialchars($order['order_code']); ?></strong>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <strong class="text-primary font-semibold"><?php echo formatPrice($order['total_amount']); ?></strong>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-center hidden lg:table-cell">
                        <?php if (strtoupper($order['payment_method']) == 'COD'): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">COD</span>
                        <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary">Chuyển khoản</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-center status-cell">
                        <?php
                        $badge_classes = [
                            'pending' => 'bg-amber-100 text-amber-800',
                            'confirmed' => 'bg-blue-100 text-blue-800',
                            'shipping' => 'bg-primary/10 text-primary',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                        $labels = [
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'shipping' => 'Đang giao',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy'
                        ];
                        $badge_class = $badge_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                        $label = $labels[$order['status']] ?? $order['status'];
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 hidden xl:table-cell">
                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-center">
                        <button type="button" 
                                class="inline-flex items-center px-3 py-1.5 bg-primary text-secondary font-semibold rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md text-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#orderModal<?php echo $order['id']; ?>">
                            <i class="fas fa-eye mr-1.5"></i> Chi tiết
                        </button>
                    </td>
                </tr>
                <?php
                // Lưu thông tin order để render modal sau
                $orders_data[] = $order;
                endwhile;
                ?>
            </tbody>
        </table>
    </div>
    <?php if ($orders->num_rows == 0): ?>
    <div class="p-12 text-center">
        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Chưa có đơn hàng nào</p>
    </div>
    <?php endif; ?>
</div>

<!-- Order Modals (render bên ngoài table) -->
<?php
// Reset lại pointer và lấy dữ liệu cho modals
$orders->data_seek(0);
while ($order = $orders->fetch_assoc()):
?>
<div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                <h5 class="text-lg font-semibold text-primary flex items-center">
                    <i class="fas fa-receipt mr-2"></i>Chi tiết đơn hàng 
                    <span class="ml-2 text-primary-light">#<?php echo htmlspecialchars($order['order_code']); ?></span>
                </h5>
                <button type="button" 
                        class="text-primary hover:text-primary-light transition-colors" 
                        data-bs-dismiss="modal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 max-h-[80vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <h6 class="text-primary font-semibold mb-3 flex items-center">
                            <i class="fas fa-user mr-2"></i>Thông tin khách hàng
                        </h6>
                        <div class="space-y-2 text-sm">
                            <p><strong class="text-gray-700">Tên:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_name']); ?></span></p>
                            <p><strong class="text-gray-700">Email:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_email']); ?></span></p>
                            <p><strong class="text-gray-700">SĐT:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_phone']); ?></span></p>
                            <p><strong class="text-gray-700">Địa chỉ:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_address']); ?></span></p>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <h6 class="text-primary font-semibold mb-3 flex items-center">
                            <i class="fas fa-file-invoice mr-2"></i>Thông tin đơn hàng
                        </h6>
                        <div class="space-y-2 text-sm">
                            <p><strong class="text-gray-700">Mã đơn:</strong> <span class="text-primary font-semibold"><?php echo htmlspecialchars($order['order_code']); ?></span></p>
                            <p><strong class="text-gray-700">Ngày đặt:</strong> <span class="text-gray-600"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span></p>
                            <p><strong class="text-gray-700">Thanh toán:</strong> <span class="text-gray-600"><?php echo strtoupper($order['payment_method']) == 'COD' ? 'COD' : 'Chuyển khoản'; ?></span></p>
                            <p><strong class="text-gray-700">Ghi chú:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($order['note']) ?: '-'; ?></span></p>
                        </div>
                    </div>
                </div>

                <?php
                $detail_sql = "SELECT od.*, p.name, p.image FROM order_details od LEFT JOIN products p ON od.product_id = p.id WHERE od.order_id = ?";
                $detail_stmt = $conn->prepare($detail_sql);
                $detail_stmt->bind_param("i", $order['id']);
                $detail_stmt->execute();
                $details = $detail_stmt->get_result();
                ?>

                <h6 class="text-primary font-semibold mb-3 flex items-center">
                    <i class="fas fa-box mr-2"></i>Sản phẩm
                </h6>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Sản phẩm</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-primary uppercase tracking-wider">Đơn giá</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase tracking-wider">SL</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-primary uppercase tracking-wider">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($detail = $details->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right"><?php echo formatPrice($detail['price']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-center"><?php echo $detail['quantity']; ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary text-right"><?php echo formatPrice($detail['subtotal']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="bg-primary/10">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Tổng cộng:</td>
                                <td class="px-4 py-3 text-right">
                                    <strong class="text-primary text-lg"><?php echo formatPrice($order['total_amount']); ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <form method="POST" class="p-4 bg-gray-50 rounded-xl update-status-form">
                    <h6 class="text-primary font-semibold mb-3 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Cập nhật trạng thái
                    </h6>
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                        <div class="md:col-span-8">
                            <select name="status" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                <option value="shipping" <?php echo $order['status'] == 'shipping' ? 'selected' : ''; ?>>Đang giao</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="md:col-span-4">
                            <button type="submit" 
                                    name="update_status" 
                                    class="w-full px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i> Cập nhật
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php include 'includes/footer.php'; ?>

