<?php
session_start();
require_once 'config/config.php';

$page_title = 'Flash Sale';

// Lấy flash sale đang active hoặc sắp diễn ra
$current_time = date('Y-m-d H:i:s');

$flash_sale_query = "SELECT * FROM flash_sales 
                     WHERE (status = 'active' OR status = 'upcoming') 
                     AND end_time > '$current_time'
                     ORDER BY start_time ASC 
                     LIMIT 1";

$flash_sale_result = $conn->query($flash_sale_query);

// Kiểm tra lỗi query
if (!$flash_sale_result) {
    die("Lỗi truy vấn: " . $conn->error);
}

include 'includes/header.php';
?>

<div class="flash-sale-page">
    <?php if ($flash_sale_result->num_rows === 0): ?>
        <!-- Không có Flash Sale -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-bolt text-4xl text-gray-400"></i>
                    </div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">Hiện tại không có Flash Sale nào</h2>
                    <p class="text-gray-600 mb-6">
                        Hiện tại không có chương trình Flash Sale nào đang diễn ra hoặc sắp diễn ra.
                        <br>Vui lòng quay lại sau để xem các chương trình khuyến mãi hấp dẫn!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="index.php" 
                           class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                            <i class="fas fa-home mr-2"></i>Về trang chủ
                        </a>
                        <a href="products.php" 
                           class="inline-flex items-center px-6 py-3 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>Xem sản phẩm
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php
        $flash_sale = $flash_sale_result->fetch_assoc();
        $flash_sale_id = $flash_sale['id'];

        // Lấy sản phẩm flash sale
        $products_query = "SELECT fsp.*, p.name, p.image, p.slug, p.rating, p.reviews_count
                           FROM flash_sale_products fsp
                           JOIN products p ON fsp.product_id = p.id
                           WHERE fsp.flash_sale_id = $flash_sale_id
                           ORDER BY fsp.discount_percent DESC";

        $products = $conn->query($products_query);
        
        // Kiểm tra lỗi query sản phẩm
        if (!$products) {
            die("Lỗi truy vấn sản phẩm: " . $conn->error);
        }
        ?>
        
        <!-- Flash Sale Header -->
        <div class="bg-gradient-to-r from-red-500 to-pink-600 text-white py-12 mb-8">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h1 class="flash-sale-title text-3xl md:text-4xl font-bold mb-3 flex items-center">
                            <i class="fas fa-bolt text-yellow-300 mr-3 animate-pulse"></i>
                            <?php echo htmlspecialchars($flash_sale['name']); ?>
                        </h1>
                        <p class="flash-sale-description text-lg opacity-90">
                            <?php echo htmlspecialchars($flash_sale['description']); ?>
                        </p>
                    </div>
                    <div>
                        <div class="flash-sale-countdown bg-white/20 backdrop-blur-sm rounded-xl p-6 text-center" 
                             data-start="<?php echo $flash_sale['start_time']; ?>"
                             data-end="<?php echo $flash_sale['end_time']; ?>">
                            <div class="countdown-label text-base mb-4 font-medium">
                                <?php if ($flash_sale['status'] === 'upcoming'): ?>
                                    Bắt đầu sau:
                                <?php else: ?>
                                    Kết thúc sau:
                                <?php endif; ?>
                            </div>
                            <div class="countdown-timer flex items-center justify-center gap-2">
                                <div class="countdown-item bg-white/30 rounded-lg px-4 py-3 min-w-[70px]">
                                    <span class="countdown-value block text-2xl font-bold" id="hours">00</span>
                                    <span class="countdown-label text-sm opacity-90">Giờ</span>
                                </div>
                                <div class="countdown-separator text-2xl font-bold">:</div>
                                <div class="countdown-item bg-white/30 rounded-lg px-4 py-3 min-w-[70px]">
                                    <span class="countdown-value block text-2xl font-bold" id="minutes">00</span>
                                    <span class="countdown-label text-sm opacity-90">Phút</span>
                                </div>
                                <div class="countdown-separator text-2xl font-bold">:</div>
                                <div class="countdown-item bg-white/30 rounded-lg px-4 py-3 min-w-[70px]">
                                    <span class="countdown-value block text-2xl font-bold" id="seconds">00</span>
                                    <span class="countdown-label text-sm opacity-90">Giây</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Flash Sale Products -->
        <div class="container mx-auto px-4 py-8">
            <?php if ($products->num_rows === 0): ?>
                <div class="max-w-2xl mx-auto">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Flash Sale này chưa có sản phẩm nào.
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 md:gap-6">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <?php
                        $percent_sold = ($product['quantity_sold'] / $product['quantity_limit']) * 100;
                        $remaining = $product['quantity_limit'] - $product['quantity_sold'];
                        ?>
                        <div class="flash-sale-product-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col hover:shadow-lg transition-shadow relative">
                            <div class="flash-sale-badge absolute top-2 right-2 z-10 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-500 text-white">
                                -<?php echo $product['discount_percent']; ?>%
                            </div>
                            
                            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="block relative overflow-hidden bg-gray-100">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="flash-sale-product-image w-full h-48 object-cover transition-transform duration-300 hover:scale-105">
                            </a>
                            
                            <div class="flash-sale-product-info p-4 flex flex-col flex-grow">
                                <h5 class="flash-sale-product-name font-semibold text-gray-900 mb-3 min-h-[2.5rem] line-clamp-2">
                                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" 
                                       class="text-gray-900 hover:text-amber-600 transition-colors">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h5>
                                
                                <div class="flash-sale-prices mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flash-sale-price text-red-600 font-bold text-lg"><?php echo number_format($product['flash_price']); ?>đ</span>
                                        <span class="flash-sale-original-price text-gray-400 line-through text-sm"><?php echo number_format($product['original_price']); ?>đ</span>
                                    </div>
                                </div>
                                
                                <div class="flash-sale-progress mb-3">
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                        <div class="progress-bar bg-gradient-to-r from-red-500 to-pink-500 h-2 rounded-full transition-all duration-300" 
                                             role="progressbar" 
                                             style="width: <?php echo $percent_sold; ?>%"
                                             aria-valuenow="<?php echo $percent_sold; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="flash-sale-stock text-xs text-gray-600 flex items-center">
                                        <?php if ($remaining > 0): ?>
                                            <i class="fas fa-fire text-orange-500 mr-1"></i> Còn <?php echo $remaining; ?> sản phẩm
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-red-500 mr-1"></i> Đã hết hàng
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($remaining > 0): ?>
                                    <button class="btn btn-flash-sale w-full mt-auto inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold rounded-lg transition-all duration-300 shadow-md hover:shadow-lg" 
                                            onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-shopping-cart mr-2"></i> Mua Ngay
                                    </button>
                                <?php else: ?>
                                    <button class="w-full mt-auto inline-flex items-center justify-center px-4 py-2.5 bg-gray-300 text-gray-500 font-medium rounded-lg cursor-not-allowed" disabled>
                                        Hết Hàng
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="assets/css/flash-sale.css">
<script src="assets/js/flash-sale.js"></script>

<?php include 'includes/footer.php'; ?>

