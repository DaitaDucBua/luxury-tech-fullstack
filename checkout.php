<?php
require_once 'config/config.php';
require_once 'includes/order-email.php';

$page_title = 'Thanh toán';

// Lấy giỏ hàng
$cart_items = [];
$total = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT c.*, p.name, p.price, p.sale_price, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    $session_id = getSessionId();
    $sql = "SELECT c.*, p.name, p.price, p.sale_price, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Kiểm tra Flash Sale
    $flash = getFlashSalePrice($row['product_id']);
    if ($flash) {
        $row['flash_price'] = $flash['flash_price'];
        $row['flash_discount'] = $flash['discount_percent'];
        $row['flash_sale_product_id'] = $flash['fsp_id'];
    }
    $cart_items[] = $row;

    // Ưu tiên: Flash Sale > Sale Price > Price
    if (isset($row['flash_price'])) {
        $price = $row['flash_price'];
    } else {
        $price = $row['sale_price'] ?: $row['price'];
    }
    $total += $price * $row['quantity'];
}

// Nếu giỏ hàng trống
if (count($cart_items) == 0) {
    showMessage('Giỏ hàng của bạn đang trống', 'warning');
    redirect('cart.php');
}

// Lấy thông tin user nếu đã đăng nhập
$user = getCurrentUser();

$error = '';
$success = '';

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize($_POST['customer_name']);
    $customer_email = sanitize($_POST['customer_email']);
    $customer_phone = sanitize($_POST['customer_phone']);
    $customer_address = sanitize($_POST['customer_address']);
    $payment_method = sanitize($_POST['payment_method']);
    $note = sanitize($_POST['note']);

    // Lấy thông tin mã giảm giá
    $coupon_code = sanitize($_POST['coupon_code'] ?? '');
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);

    // Validate
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Tạo mã đơn hàng
        $order_code = 'LT' . date('YmdHis') . rand(100, 999);

        // Tính tổng tiền sau giảm giá
        $final_total = $total - $discount_amount;
        if ($final_total < 0) $final_total = 0;

        // Thêm đơn hàng
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        $insert_order = "INSERT INTO orders (user_id, order_code, customer_name, customer_email, customer_phone, customer_address, total_amount, discount_amount, coupon_code, payment_method, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $order_stmt = $conn->prepare($insert_order);
        $order_stmt->bind_param("isssssddsss", $user_id, $order_code, $customer_name, $customer_email, $customer_phone, $customer_address, $final_total, $discount_amount, $coupon_code, $payment_method, $note);

        if ($order_stmt->execute()) {
            $order_id = $order_stmt->insert_id;

            // Cập nhật số lần sử dụng coupon và ghi nhận usage
            if (!empty($coupon_code)) {
                // Tăng used_count
                $update_coupon = "UPDATE coupons SET used_count = used_count + 1 WHERE code = ?";
                $coupon_stmt = $conn->prepare($update_coupon);
                $coupon_stmt->bind_param("s", $coupon_code);
                $coupon_stmt->execute();

                // Ghi nhận coupon usage nếu user đăng nhập
                if (isLoggedIn()) {
                    $coupon_id = isset($_SESSION['applied_coupon']['id']) ? $_SESSION['applied_coupon']['id'] : 0;
                    if ($coupon_id > 0) {
                        $insert_usage = "INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?, ?, ?)";
                        $usage_stmt = $conn->prepare($insert_usage);
                        $usage_stmt->bind_param("iii", $coupon_id, $user_id, $order_id);
                        $usage_stmt->execute();
                    }
                }

                // Xóa session coupon
                unset($_SESSION['applied_coupon']);
            }

            // Thêm chi tiết đơn hàng
            foreach ($cart_items as $item) {
                // Ưu tiên: Flash Sale > Sale Price > Price
                if (isset($item['flash_price'])) {
                    $price = $item['flash_price'];
                } else {
                    $price = $item['sale_price'] ?: $item['price'];
                }
                $subtotal = $price * $item['quantity'];
                $product_image = $item['image'] ?? '';

                $insert_detail = "INSERT INTO order_details (order_id, product_id, product_name, product_image, price, quantity, subtotal)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                $detail_stmt = $conn->prepare($insert_detail);
                $detail_stmt->bind_param("iissdid", $order_id, $item['product_id'], $item['name'], $product_image, $price, $item['quantity'], $subtotal);
                $detail_stmt->execute();

                // Giảm số lượng tồn kho
                $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stock_stmt = $conn->prepare($update_stock);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();

                // Cập nhật quantity_sold cho Flash Sale
                if (isset($item['flash_sale_product_id'])) {
                    $update_flash = "UPDATE flash_sale_products SET quantity_sold = quantity_sold + ? WHERE id = ?";
                    $flash_stmt = $conn->prepare($update_flash);
                    $flash_stmt->bind_param("ii", $item['quantity'], $item['flash_sale_product_id']);
                    $flash_stmt->execute();
                }
            }

            // Xóa giỏ hàng
            if (isLoggedIn()) {
                $delete_cart = "DELETE FROM cart WHERE user_id = ?";
                $delete_stmt = $conn->prepare($delete_cart);
                $delete_stmt->bind_param("i", $user_id);
            } else {
                $session_id = getSessionId();
                $delete_cart = "DELETE FROM cart WHERE session_id = ?";
                $delete_stmt = $conn->prepare($delete_cart);
                $delete_stmt->bind_param("s", $session_id);
            }
            $delete_stmt->execute();

            // Gửi email xác nhận đơn hàng
            sendOrderConfirmationEmail($order_id);

            // Nếu chọn VNPay thì redirect sang trang thanh toán VNPay
            if ($payment_method === 'vnpay') {
                redirect('payment/vnpay-payment.php?order_id=' . $order_id);
            }

            showMessage('Đặt hàng thành công! Mã đơn hàng: ' . $order_code, 'success');
            redirect('order-success.php?order_code=' . $order_code);
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center space-x-2 text-sm">
        <li><a href="index.php" class="text-amber-600 hover:text-amber-700 transition-colors">Trang chủ</a></li>
        <li class="text-gray-400">/</li>
        <li><a href="cart.php" class="text-amber-600 hover:text-amber-700 transition-colors">Giỏ hàng</a></li>
        <li class="text-gray-400">/</li>
        <li class="text-gray-600">Thanh toán</li>
    </ol>
</nav>

<h2 class="mb-6 text-2xl font-semibold text-gray-800 flex items-center">
    <i class="fas fa-credit-card mr-3 text-amber-600"></i> Thanh toán
</h2>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start" role="alert">
    <i class="fas fa-exclamation-circle mr-3 text-red-600 mt-0.5"></i>
    <div class="text-red-800"><?php echo $error; ?></div>
</div>
<?php endif; ?>

<form method="POST" action="">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Thông tin khách hàng -->
        <div class="lg:col-span-7">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                    <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-user mr-2 text-amber-600"></i>Thông tin người nhận
                    </h5>
                </div>
                <div class="p-6">
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Họ và tên <span class="text-amber-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="customer_name" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                   required 
                                   placeholder="Nhập họ và tên"
                                   value="<?php echo $user ? htmlspecialchars($user['full_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" 
                                       name="customer_email" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       placeholder="Nhập email"
                                       value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Số điện thoại <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" 
                                       name="customer_phone" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       placeholder="Nhập số điện thoại"
                                       value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Địa chỉ nhận hàng <span class="text-amber-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-3 pointer-events-none">
                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <textarea name="customer_address" 
                                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"
                                      rows="3" 
                                      required 
                                      placeholder="Nhập địa chỉ nhận hàng"><?php echo $user ? htmlspecialchars($user['address']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ghi chú
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-3 pointer-events-none">
                                <i class="fas fa-sticky-note text-gray-400"></i>
                            </div>
                            <textarea name="note" 
                                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"
                                      rows="2" 
                                      placeholder="Ghi chú về đơn hàng, ví dụ: thời gian hay chỉ dẫn địa điểm giao hàng chi tiết hơn"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Phương thức thanh toán <span class="text-amber-600">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input class="sr-only peer" type="radio" name="payment_method" id="cod" value="cod" checked>
                                <div class="p-4 bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-xl border-2 border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500 peer-checked:ring-offset-2 transition-all hover:shadow-md">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-money-bill-wave text-amber-600 text-xl"></i>
                                        <div>
                                            <div class="font-semibold text-gray-900">Thanh toán khi nhận hàng</div>
                                            <div class="text-xs text-gray-600 mt-0.5">COD - Cash on Delivery</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input class="sr-only peer" type="radio" name="payment_method" id="vnpay" value="vnpay">
                                <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-300 peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500 peer-checked:ring-offset-2 transition-all hover:shadow-md">
                                    <div class="flex items-center space-x-3">
                                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Icon-VNPAY-QR.png" alt="VNPay" class="h-6">
                                        <div>
                                            <div class="font-semibold text-gray-900">VNPay</div>
                                            <div class="text-xs text-gray-600 mt-0.5">Thanh toán qua VNPay QR</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đơn hàng -->
        <div class="lg:col-span-5">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden sticky top-4">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                    <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-shopping-bag mr-2 text-amber-600"></i>Đơn hàng của bạn
                    </h5>
                </div>
                <div class="p-6">
                    <div class="space-y-4 mb-6">
                        <?php foreach ($cart_items as $item): ?>
                        <?php
                        // Ưu tiên: Flash Sale > Sale Price > Price
                        if (isset($item['flash_price'])) {
                            $price = $item['flash_price'];
                            $is_flash = true;
                        } else {
                            $price = $item['sale_price'] ?: $item['price'];
                            $is_flash = false;
                        }
                        $subtotal = $price * $item['quantity'];
                        ?>
                        <div class="flex justify-between items-start pb-4 border-b border-gray-200 last:border-0">
                            <div class="flex-1 min-w-0 pr-4">
                                <div class="flex items-center flex-wrap gap-2 mb-1">
                                    <strong class="text-gray-900 text-sm"><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if ($is_flash): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-bolt mr-1"></i> -<?php echo $item['flash_discount']; ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-gray-500 text-xs">Số lượng: <?php echo $item['quantity']; ?></small>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-amber-600 font-semibold text-sm"><?php echo formatPrice($subtotal); ?></div>
                                <?php if ($is_flash): ?>
                                <small class="text-gray-400 text-xs line-through"><?php echo formatPrice($item['price'] * $item['quantity']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Tạm tính:</span>
                            <strong id="subtotal-display" class="text-gray-900 font-semibold"><?php echo formatPrice($total); ?></strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Phí vận chuyển:</span>
                            <strong class="text-green-600 font-semibold text-sm flex items-center">
                                <i class="fas fa-check-circle mr-1"></i>Miễn phí
                            </strong>
                        </div>
                    </div>

                    <!-- Mã giảm giá -->
                    <div class="mb-4 p-4 bg-amber-50 rounded-lg border border-dashed border-amber-200">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-ticket-alt mr-1 text-amber-600"></i> Mã giảm giá
                        </label>
                        <div class="flex gap-2" id="coupon-input-group">
                            <input type="text" 
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors uppercase"
                                   id="coupon-code" 
                                   placeholder="Nhập mã giảm giá">
                            <button type="button" 
                                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors"
                                    id="apply-coupon-btn">
                                Áp dụng
                            </button>
                        </div>
                        <div id="coupon-message" class="mt-2 text-xs"></div>

                        <!-- Hiển thị mã đã áp dụng -->
                        <div id="applied-coupon" class="mt-2 hidden">
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                    <span id="applied-coupon-code" class="font-bold text-amber-600"></span>
                                    <small class="text-gray-600 text-xs" id="applied-coupon-desc"></small>
                                </div>
                                <button type="button" 
                                        class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                                        id="remove-coupon-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Dòng giảm giá (ẩn mặc định) -->
                    <div class="flex justify-between items-center mb-2 hidden" id="discount-row">
                        <span class="text-gray-600 text-sm">Giảm giá:</span>
                        <strong class="text-green-600 font-semibold text-sm" id="discount-display">-0đ</strong>
                    </div>

                    <input type="hidden" name="coupon_code" id="coupon-code-input" value="">
                    <input type="hidden" name="discount_amount" id="discount-amount-input" value="0">

                    <hr class="border-gray-200 my-4">
                    <div class="flex justify-between items-center mb-6">
                        <h5 class="text-lg font-semibold text-gray-900">Tổng cộng:</h5>
                        <h5 id="total-display" class="text-xl font-bold text-amber-600"><?php echo formatPrice($total); ?></h5>
                    </div>

                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center px-6 py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                        <i class="fas fa-check-circle mr-2"></i> Đặt hàng
                    </button>

                    <div class="mt-6 p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-shield-alt mr-3 text-amber-600"></i>
                            <span class="text-sm text-gray-700">Thanh toán an toàn 100%</span>
                        </div>
                        <div class="flex items-center mb-3">
                            <i class="fas fa-truck mr-3 text-amber-600"></i>
                            <span class="text-sm text-gray-700">Giao hàng nhanh chóng</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-undo mr-3 text-amber-600"></i>
                            <span class="text-sm text-gray-700">Đổi trả trong 7 ngày</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const subtotal = <?php echo $total; ?>;

document.getElementById('apply-coupon-btn').addEventListener('click', function() {
    const code = document.getElementById('coupon-code').value.trim();
    if (!code) {
        showCouponMessage('Vui lòng nhập mã giảm giá', 'danger');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('ajax/coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=apply&code=${encodeURIComponent(code)}&subtotal=${subtotal}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showCouponMessage(data.message, 'success');
            document.getElementById('coupon-input-group').classList.add('hidden');
            document.getElementById('applied-coupon').classList.remove('hidden');
            document.getElementById('applied-coupon-code').textContent = data.coupon.code;
            document.getElementById('applied-coupon-desc').textContent = '(-' + data.coupon.discount_text + ')';

            document.getElementById('discount-row').classList.remove('hidden');
            document.getElementById('discount-display').textContent = '-' + data.coupon.discount_formatted;
            document.getElementById('total-display').textContent = data.total_formatted;

            document.getElementById('coupon-code-input').value = data.coupon.code;
            document.getElementById('discount-amount-input').value = data.coupon.discount;
        } else {
            showCouponMessage(data.message, 'danger');
        }
    })
    .catch(() => showCouponMessage('Có lỗi xảy ra', 'danger'))
    .finally(() => {
        this.disabled = false;
        this.innerHTML = 'Áp dụng';
    });
});

document.getElementById('remove-coupon-btn').addEventListener('click', function() {
    fetch('ajax/coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove'
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('coupon-input-group').classList.remove('hidden');
        document.getElementById('applied-coupon').classList.add('hidden');
        document.getElementById('coupon-code').value = '';
        document.getElementById('discount-row').classList.add('hidden');
        document.getElementById('total-display').textContent = '<?php echo formatPrice($total); ?>';
        document.getElementById('coupon-code-input').value = '';
        document.getElementById('discount-amount-input').value = '0';
        document.getElementById('coupon-message').innerHTML = '';
    });
});

document.getElementById('coupon-code').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('apply-coupon-btn').click();
    }
});

function showCouponMessage(msg, type) {
    const el = document.getElementById('coupon-message');
    const colorClass = type === 'success' ? 'text-green-600' : 'text-red-600';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    el.innerHTML = `<span class="${colorClass}"><i class="fas fa-${icon} mr-1"></i>${msg}</span>`;
}
</script>

<?php include 'includes/footer.php'; ?>

