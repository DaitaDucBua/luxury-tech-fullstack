<?php
require_once 'includes/auth.php';

$page_title = 'Dashboard';

// Thống kê tổng quan
$stats = [];

// Tổng số sản phẩm
$sql = "SELECT COUNT(*) as total FROM products";
$stats['products'] = $conn->query($sql)->fetch_assoc()['total'];

// Tổng số đơn hàng
$sql = "SELECT COUNT(*) as total FROM orders";
$stats['orders'] = $conn->query($sql)->fetch_assoc()['total'];

// Tổng số người dùng
$sql = "SELECT COUNT(*) as total FROM users";
$stats['users'] = $conn->query($sql)->fetch_assoc()['total'];

// Tổng doanh thu
$sql = "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'";
$result = $conn->query($sql)->fetch_assoc();
$stats['revenue'] = $result['total'] ?? 0;

// Đơn hàng chờ xử lý
$sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$stats['pending_orders'] = $conn->query($sql)->fetch_assoc()['total'];

// Sản phẩm sắp hết hàng
$sql = "SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0";
$stats['low_stock'] = $conn->query($sql)->fetch_assoc()['total'];

// Đơn hàng mới nhất
$sql = "SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10";
$recent_orders = $conn->query($sql);

// Sản phẩm bán chạy
$sql = "SELECT p.name, p.image, SUM(od.quantity) as total_sold, SUM(od.subtotal) as revenue
        FROM order_details od
        JOIN products p ON od.product_id = p.id
        GROUP BY od.product_id
        ORDER BY total_sold DESC
        LIMIT 5";
$best_sellers = $conn->query($sql);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-tachometer-alt mr-3 text-primary"></i> Dashboard
    </h1>
    <div class="flex items-center gap-3">
        <a href="../index.php" 
           target="_blank" 
           class="inline-flex items-center px-4 py-2 bg-primary/10 text-primary font-semibold rounded-lg hover:bg-primary/20 transition-colors border border-primary/30">
            <i class="fas fa-external-link-alt mr-2"></i> Xem website
        </a>
        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <i class="fas fa-calendar mr-2"></i> <?php echo date('d/m/Y'); ?>
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Tổng sản phẩm -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary via-primary-light to-primary"></div>
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Tổng sản phẩm</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-primary to-primary-light bg-clip-text text-transparent">
                        <?php echo number_format($stats['products']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <i class="fas fa-box text-primary text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng đơn hàng -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500"></div>
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Tổng đơn hàng</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-emerald-500 bg-clip-text text-transparent">
                        <?php echo number_format($stats['orders']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center group-hover:bg-green-500/20 transition-colors">
                    <i class="fas fa-shopping-cart text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Người dùng -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Người dùng</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-blue-500 to-blue-600 bg-clip-text text-transparent">
                        <?php echo number_format($stats['users']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center group-hover:bg-blue-500/20 transition-colors">
                    <i class="fas fa-users text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Doanh thu -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary via-primary-light to-primary"></div>
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Doanh thu</p>
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-primary to-primary-light bg-clip-text text-transparent">
                        <?php echo formatPrice($stats['revenue']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <i class="fas fa-coins text-primary text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <?php if ($stats['pending_orders'] > 0): ?>
    <div class="bg-white rounded-xl border-l-4 border-amber-500 shadow-md p-4 flex items-center">
        <div class="w-12 h-12 bg-amber-500/10 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-800">
                <span class="text-amber-600"><?php echo $stats['pending_orders']; ?></span> đơn hàng đang chờ xử lý
            </p>
            <a href="orders.php?status=pending" class="text-sm text-primary hover:text-primary-dark font-medium mt-1 inline-block">
                Xem ngay →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($stats['low_stock'] > 0): ?>
    <div class="bg-white rounded-xl border-l-4 border-red-500 shadow-md p-4 flex items-center">
        <div class="w-12 h-12 bg-red-500/10 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-box-open text-red-500 text-xl"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-800">
                <span class="text-red-600"><?php echo $stats['low_stock']; ?></span> sản phẩm sắp hết hàng
            </p>
            <a href="products.php?stock=low" class="text-sm text-primary hover:text-primary-dark font-medium mt-1 inline-block">
                Xem ngay →
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-7 gap-6">
    <!-- Recent Orders -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
                    <i class="fas fa-shopping-cart mr-2"></i> Đơn hàng mới nhất
                </h2>
                <a href="orders.php" class="px-4 py-2 bg-primary text-secondary font-semibold rounded-lg hover:bg-primary-dark transition-colors text-sm">
                    Xem tất cả
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Mã đơn</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Tổng tiền</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-primary"><?php echo htmlspecialchars($order['order_code']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary">
                                <?php echo formatPrice($order['total_amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $badge_classes = [
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'shipping' => 'bg-primary/10 text-primary',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $badge_class = $badge_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                $status_labels = [
                                    'pending' => 'Chờ xử lý',
                                    'confirmed' => 'Đã xác nhận',
                                    'shipping' => 'Đang giao',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                    <?php echo $status_labels[$order['status']] ?? ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Best Sellers -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden h-full">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
                    <i class="fas fa-fire mr-2"></i> Sản phẩm bán chạy
                </h2>
                <a href="products.php" class="px-4 py-2 bg-primary text-secondary font-semibold rounded-lg hover:bg-primary-dark transition-colors text-sm">
                    Xem tất cả
                </a>
            </div>
            <div class="p-6">
                <?php 
                $product_count = 0;
                while ($product = $best_sellers->fetch_assoc()): 
                    $product_count++;
                ?>
                <div class="flex items-center mb-4 pb-4 <?php echo $product_count < 5 ? 'border-b border-primary/15' : ''; ?> last:border-0 last:mb-0 last:pb-0">
                    <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden mr-4 flex-shrink-0">
                        <img src="../<?php echo $product['image'] ?: 'https://via.placeholder.com/60'; ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <h6 class="text-sm font-semibold text-gray-800 mb-1 truncate">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h6>
                        <p class="text-xs text-gray-500 mb-1">
                            Đã bán: <span class="font-semibold text-gray-700"><?php echo $product['total_sold']; ?></span>
                        </p>
                        <p class="text-sm font-bold text-primary">
                            <?php echo formatPrice($product['revenue']); ?>
                        </p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

