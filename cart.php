<?php
require_once 'config/config.php';

$page_title = 'Giỏ hàng';

// Lấy giỏ hàng
$cart_items = [];
$total = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.image, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    $session_id = getSessionId();
    $sql = "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.image, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $flash = getFlashSalePrice($row['product_id']);
    if ($flash) {
        $row['flash_price'] = $flash['flash_price'];
        $row['flash_discount'] = $flash['discount_percent'];
    }
    $cart_items[] = $row;

    if (isset($row['flash_price'])) {
        $price = $row['flash_price'];
    } else {
        $price = $row['sale_price'] ?: $row['price'];
    }
    $total += $price * $row['quantity'];
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center space-x-2 text-sm">
        <li><a href="index.php" class="text-amber-600 hover:text-amber-700 transition-colors">Trang chủ</a></li>
        <li class="text-gray-400">/</li>
        <li class="text-gray-600">Giỏ hàng</li>
    </ol>
</nav>

<h2 class="mb-6 text-2xl font-semibold text-gray-800 flex items-center">
    <i class="fas fa-shopping-bag mr-3 text-amber-600"></i> Giỏ hàng của bạn
</h2>

<?php if (count($cart_items) > 0): ?>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Cart Items Table -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Sản phẩm</th>
                            <th class="px-4 py-4 text-center text-sm font-semibold text-gray-700">Đơn giá</th>
                            <th class="px-4 py-4 text-center text-sm font-semibold text-gray-700 w-40">Số lượng</th>
                            <th class="px-4 py-4 text-right text-sm font-semibold text-gray-700">Thành tiền</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700 w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                        <tr data-cart-id="<?php echo $item['id']; ?>" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-4">
                                    <?php
                                    $cart_image = $item['image'] ?: 'https://via.placeholder.com/80x80?text=Product';
                                    if (strpos($cart_image, 'http') !== 0) {
                                        if (file_exists($cart_image)) {
                                            $cart_image .= '?v=' . filemtime($cart_image);
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($cart_image); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="w-20 h-20 object-contain bg-gray-50 rounded-lg p-2 border border-gray-100">
                                    <div class="flex-1 min-w-0">
                                        <a href="product-detail.php?slug=<?php echo $item['slug']; ?>" 
                                           class="text-gray-900 font-medium hover:text-amber-600 transition-colors block mb-1 line-clamp-2">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                        <?php if ($is_flash): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-bolt mr-1"></i>Flash Sale -<?php echo $item['flash_discount']; ?>%
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($item['quantity'] > $item['stock']): ?>
                                        <p class="text-red-600 text-xs mt-1 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>Chỉ còn <?php echo $item['stock']; ?> sản phẩm
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <strong class="text-amber-600 font-semibold text-base"><?php echo formatPrice($price); ?></strong>
                                    <?php if ($is_flash || $item['sale_price']): ?>
                                    <small class="text-gray-400 text-xs line-through mt-1"><?php echo formatPrice($item['price']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                        <button class="update-cart-qty px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors border-r border-gray-300" 
                                                data-action="decrease" 
                                                type="button">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" 
                                               class="cart-quantity w-16 text-center border-0 focus:ring-0 focus:outline-none py-2 text-sm"
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1"
                                               max="<?php echo $item['stock']; ?>"
                                               data-product-id="<?php echo $item['product_id']; ?>">
                                        <button class="update-cart-qty px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors border-l border-gray-300" 
                                                data-action="increase" 
                                                type="button">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <strong class="item-subtotal text-amber-600 font-semibold text-base"><?php echo formatPrice($subtotal); ?></strong>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button class="remove-from-cart p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                        data-cart-id="<?php echo $item['id']; ?>"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden divide-y divide-gray-200">
                <?php foreach ($cart_items as $item): ?>
                <?php
                if (isset($item['flash_price'])) {
                    $price = $item['flash_price'];
                    $is_flash = true;
                } else {
                    $price = $item['sale_price'] ?: $item['price'];
                    $is_flash = false;
                }
                $subtotal = $price * $item['quantity'];
                ?>
                <div data-cart-id="<?php echo $item['id']; ?>" class="p-4">
                    <div class="flex space-x-4 mb-4">
                        <?php
                        $cart_image = $item['image'] ?: 'https://via.placeholder.com/80x80?text=Product';
                        if (strpos($cart_image, 'http') !== 0) {
                            if (file_exists($cart_image)) {
                                $cart_image .= '?v=' . filemtime($cart_image);
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($cart_image); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="w-20 h-20 object-contain bg-gray-50 rounded-lg p-2 border border-gray-100 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <a href="product-detail.php?slug=<?php echo $item['slug']; ?>" 
                               class="text-gray-900 font-medium hover:text-amber-600 transition-colors block mb-2 line-clamp-2">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                            <?php if ($is_flash): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800 mb-2">
                                <i class="fas fa-bolt mr-1"></i>Flash Sale -<?php echo $item['flash_discount']; ?>%
                            </span>
                            <?php endif; ?>
                            <?php if ($item['quantity'] > $item['stock']): ?>
                            <p class="text-red-600 text-xs flex items-center mb-2">
                                <i class="fas fa-exclamation-circle mr-1"></i>Chỉ còn <?php echo $item['stock']; ?> sản phẩm
                            </p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <strong class="text-amber-600 font-semibold text-base block"><?php echo formatPrice($price); ?></strong>
                                    <?php if ($is_flash || $item['sale_price']): ?>
                                    <small class="text-gray-400 text-xs line-through"><?php echo formatPrice($item['price']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <button class="remove-from-cart p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                        data-cart-id="<?php echo $item['id']; ?>"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Số lượng:</span>
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                            <button class="update-cart-qty px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors border-r border-gray-300" 
                                    data-action="decrease" 
                                    type="button">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" 
                                   class="cart-quantity w-16 text-center border-0 focus:ring-0 focus:outline-none py-2 text-sm"
                                   value="<?php echo $item['quantity']; ?>"
                                   min="1"
                                   max="<?php echo $item['stock']; ?>"
                                   data-product-id="<?php echo $item['product_id']; ?>">
                            <button class="update-cart-qty px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors border-l border-gray-300" 
                                    data-action="increase" 
                                    type="button">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                        <strong class="item-subtotal text-amber-600 font-semibold text-base"><?php echo formatPrice($subtotal); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-6">
            <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Tiếp tục mua hàng
            </a>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden sticky top-4">
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-receipt mr-2 text-amber-600"></i>Thông tin đơn hàng
                </h5>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-600">Tạm tính:</span>
                    <strong id="cart-subtotal" class="text-gray-900 font-semibold"><?php echo formatPrice($total); ?></strong>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-600">Phí vận chuyển:</span>
                    <strong class="text-green-600 font-semibold flex items-center">
                        <i class="fas fa-check-circle mr-1"></i>Miễn phí
                    </strong>
                </div>
                <hr class="border-gray-200 my-4">
                <div class="flex justify-between items-center mb-6">
                    <h5 class="text-lg font-semibold text-gray-900">Tổng cộng:</h5>
                    <h5 id="cart-total" class="text-xl font-bold text-amber-600"><?php echo formatPrice($total); ?></h5>
                </div>

                <a href="checkout.php" class="w-full inline-flex items-center justify-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-credit-card mr-2"></i> Thanh toán
                </a>

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

<?php else: ?>
<div class="text-center py-12">
    <div class="mb-6 w-32 h-32 mx-auto bg-amber-50 rounded-full flex items-center justify-center">
        <i class="fas fa-shopping-bag text-5xl text-amber-600"></i>
    </div>
    <h4 class="text-2xl font-semibold text-gray-800 mb-3">Giỏ hàng của bạn đang trống</h4>
    <p class="text-gray-600 mb-6">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
    <a href="index.php" class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
        <i class="fas fa-shopping-bag mr-2"></i> Tiếp tục mua hàng
    </a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

