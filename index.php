<?php
require_once 'config/config.php';

// Ngăn cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$page_title = 'Trang chủ';

// Lấy Flash Sale đang active
$current_time = date('Y-m-d H:i:s');
$flash_sale_sql = "SELECT fs.*,
    (SELECT COUNT(*) FROM flash_sale_products WHERE flash_sale_id = fs.id) as product_count
    FROM flash_sales fs
    WHERE fs.status = 'active'
    AND fs.start_time <= '$current_time'
    AND fs.end_time > '$current_time'
    LIMIT 1";
$flash_sale_result = $conn->query($flash_sale_sql);
$active_flash_sale = $flash_sale_result->fetch_assoc();

// Lấy sản phẩm Flash Sale nếu có
$flash_products = [];
if ($active_flash_sale) {
    $flash_products_sql = "SELECT fsp.*, p.name, p.slug, p.image, p.price as product_price
        FROM flash_sale_products fsp
        JOIN products p ON fsp.product_id = p.id
        WHERE fsp.flash_sale_id = {$active_flash_sale['id']}
        AND fsp.quantity_sold < fsp.quantity_limit
        ORDER BY fsp.discount_percent DESC
        LIMIT 4";
    $flash_products = $conn->query($flash_products_sql);
}

// Lấy sản phẩm nổi bật
$featured_sql = "SELECT p.*, c.name as category_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.is_featured = 1 AND p.status = 'active'
                 ORDER BY p.created_at DESC
                 LIMIT 8";
$featured_products = $conn->query($featured_sql);

// Lấy sản phẩm mới (sản phẩm được tạo gần đây)
$new_sql = "SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 8";
$new_products = $conn->query($new_sql);

// Lấy danh mục
$categories_sql = "SELECT * FROM categories LIMIT 6";
$categories = $conn->query($categories_sql);

include 'includes/header.php';
?>

<!-- Hero Banner Slider -->
<div id="bannerCarousel" class="relative mb-8 rounded-xl overflow-hidden shadow-lg max-h-[400px]" data-aos="fade-down" data-aos-duration="1000">
    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 flex gap-2">
        <button type="button" data-slide-to="0" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors active"></button>
        <button type="button" data-slide-to="1" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors"></button>
        <button type="button" data-slide-to="2" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors"></button>
    </div>
    <div class="carousel-inner relative">
        <div class="carousel-item active">
            <img src="images/banners/ban1.jpg" class="w-full h-[400px] object-cover" alt="Banner iPhone">
        </div>
        <div class="carousel-item hidden">
            <img src="images/banners/ban2.jpg" class="w-full h-[400px] object-cover" alt="Banner Samsung">
        </div>
        <div class="carousel-item hidden">
            <img src="images/banners/ban3.jpg" class="w-full h-[400px] object-cover" alt="Banner MacBook">
        </div>
    </div>
    <button class="carousel-control-prev absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/80 hover:bg-white rounded-full flex items-center justify-center transition-colors z-10" type="button" data-slide="prev">
        <i class="fas fa-chevron-left text-gray-700"></i>
    </button>
    <button class="carousel-control-next absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/80 hover:bg-white rounded-full flex items-center justify-center transition-colors z-10" type="button" data-slide="next">
        <i class="fas fa-chevron-right text-gray-700"></i>
    </button>
</div>

<script>
// Simple carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('bannerCarousel');
    if (!carousel) return;
    
    const items = carousel.querySelectorAll('.carousel-item');
    const indicators = carousel.querySelectorAll('[data-slide-to]');
    const prevBtn = carousel.querySelector('[data-slide="prev"]');
    const nextBtn = carousel.querySelector('[data-slide="next"]');
    
    let currentIndex = 0;
    
    function showSlide(index) {
        items.forEach((item, i) => {
            item.classList.toggle('hidden', i !== index);
            item.classList.toggle('active', i === index);
        });
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
            indicator.classList.toggle('bg-white', i === index);
            indicator.classList.toggle('bg-white/50', i !== index);
        });
    }
    
    function nextSlide() {
        currentIndex = (currentIndex + 1) % items.length;
        showSlide(currentIndex);
    }
    
    function prevSlide() {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        showSlide(currentIndex);
    }
    
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            currentIndex = index;
            showSlide(currentIndex);
        });
    });
    
    // Auto slide
    setInterval(nextSlide, 5000);
});
</script>

<!-- Flash Sale Section -->
<?php if ($active_flash_sale && $flash_products && $flash_products->num_rows > 0): ?>
<section class="mb-8" data-aos="fade-up">
    <div class="bg-gradient-to-r from-pink-500 to-red-500 rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <h4 class="text-2xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-bolt"></i>
                        <?php echo htmlspecialchars($active_flash_sale['name']); ?>
                    </h4>
                    <span class="bg-yellow-400 text-yellow-900 px-3 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1">
                        <i class="fas fa-fire"></i>HOT
                    </span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <span>Kết thúc sau:</span>
                    <div class="flex items-center gap-1" id="flash-countdown" data-end="<?php echo strtotime($active_flash_sale['end_time']) * 1000; ?>">
                        <div class="bg-white/20 backdrop-blur-sm px-3 py-2 rounded-lg text-center min-w-[50px]">
                            <span id="flash-hours" class="text-xl font-bold block">00</span>
                            <small class="text-xs">Giờ</small>
                        </div>
                        <span class="text-xl font-bold">:</span>
                        <div class="bg-white/20 backdrop-blur-sm px-3 py-2 rounded-lg text-center min-w-[50px]">
                            <span id="flash-minutes" class="text-xl font-bold block">00</span>
                            <small class="text-xs">Phút</small>
                        </div>
                        <span class="text-xl font-bold">:</span>
                        <div class="bg-white/20 backdrop-blur-sm px-3 py-2 rounded-lg text-center min-w-[50px]">
                            <span id="flash-seconds" class="text-xl font-bold block">00</span>
                            <small class="text-xs">Giây</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php while ($fp = $flash_products->fetch_assoc()):
                    $percent_sold = ($fp['quantity_sold'] / $fp['quantity_limit']) * 100;
                ?>
                <div class="bg-white rounded-xl overflow-hidden transition-all duration-300 hover:transform hover:-translate-y-2 hover:shadow-xl">
                    <a href="product-detail.php?slug=<?php echo $fp['slug']; ?>" class="block relative">
                        <div class="relative bg-white p-4">
                            <img src="<?php echo $fp['image']; ?>" class="w-full h-[150px] object-contain" alt="<?php echo htmlspecialchars($fp['name']); ?>">
                            <span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded">
                                -<?php echo $fp['discount_percent']; ?>%
                            </span>
                        </div>
                    </a>
                    <div class="p-3 text-center">
                        <h6 class="font-medium text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem]">
                            <a href="product-detail.php?slug=<?php echo $fp['slug']; ?>" class="hover:text-primary transition-colors">
                                <?php echo htmlspecialchars($fp['name']); ?>
                            </a>
                        </h6>
                        <div class="mb-2">
                            <span class="text-red-600 font-bold text-lg"><?php echo formatPrice($fp['flash_price']); ?></span>
                            <div class="text-gray-500 text-sm line-through"><?php echo formatPrice($fp['original_price']); ?></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div class="bg-yellow-400 h-2 rounded-full" style="width: <?php echo $percent_sold; ?>%"></div>
                        </div>
                        <small class="text-gray-600 text-xs block mb-2">Đã bán <?php echo $fp['quantity_sold']; ?>/<?php echo $fp['quantity_limit']; ?></small>
                        
                        <div class="flex gap-1 mt-2">
                            <button class="flex-1 btn btn-sm btn-outline-danger wishlist-toggle-btn" data-product-id="<?php echo $fp['product_id']; ?>" title="Yêu thích">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="flex-1 px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors compare-toggle-btn" data-product-id="<?php echo $fp['product_id']; ?>" title="So sánh sản phẩm">
                                <i class="fas fa-balance-scale"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="text-center mt-6">
                <a href="flash-sale.php" class="inline-flex items-center gap-2 bg-white text-red-600 font-semibold px-8 py-3 rounded-full hover:bg-gray-100 transition-colors">
                    <span>Xem tất cả</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Flash Sale Countdown
(function() {
    const countdownEl = document.getElementById('flash-countdown');
    if (!countdownEl) return;

    const endTime = parseInt(countdownEl.dataset.end);

    function updateCountdown() {
        const now = Date.now();
        const diff = endTime - now;

        if (diff <= 0) {
            location.reload();
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('flash-hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('flash-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('flash-seconds').textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>
<?php endif; ?>

<!-- Danh mục sản phẩm -->
<section class="mb-8">
    <h4 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center gap-2" data-aos="fade-right">
        <i class="fas fa-th-large text-primary"></i>
        <span>Danh mục sản phẩm</span>
    </h4>
    <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
        <?php
        $delay = 0;
        // Gán icon theo tên category
        $categoryIcons = [
            'Điện thoại' => 'fa-mobile-alt',
            'Laptop' => 'fa-laptop',
            'Tablet' => 'fa-mobile',
            'Tai nghe' => 'fa-headphones',
            'Smartwatch' => 'fa-circle-notch',
            'Camera' => 'fa-camera',
            'Phụ kiện' => 'fa-microchip',
            'TV' => 'fa-tv',
        ];
        $i = 0;
        $categories_list = [];
        $categories->data_seek(0);
        while ($category = $categories->fetch_assoc()):
            $categories_list[] = $category['name'];
            $icon = isset($categoryIcons[$category['name']]) ? $categoryIcons[$category['name']] : 'fa-box';
        ?>
        <a href="products.php?category=<?php echo $category['slug']; ?>" class="group" data-aos="zoom-in" data-aos-delay="<?php echo $delay; ?>">
            <div class="bg-white rounded-xl p-6 text-center border border-gray-100 hover:border-primary hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                <div class="w-16 h-16 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center group-hover:bg-primary group-hover:scale-110 transition-all">
                    <i class="fas <?php echo $icon; ?> text-2xl text-primary group-hover:text-white transition-colors"></i>
                </div>
                <h6 class="text-sm font-medium text-gray-900 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($category['name']); ?></h6>
            </div>
        </a>
        <?php
        $delay += 100;
        $i++;
        endwhile;
        ?>
    </div>
</section>

<?php
// In ra 6 danh mục để debug
echo "<!-- 6 Danh mục sản phẩm: " . implode(", ", $categories_list) . " -->";
?>

<script>
    console.log('6 Danh mục sản phẩm:', <?php echo json_encode($categories_list); ?>);
</script>

<!-- Sản phẩm nổi bật -->
<section class="mb-8 bg-white rounded-xl p-6 shadow-sm">
    <h4 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center gap-2" data-aos="fade-right">
        <i class="fas fa-crown text-primary"></i>
        <span>Sản phẩm nổi bật</span>
    </h4>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $delay = 0;
        $featured_products->data_seek(0);
        while ($product = $featured_products->fetch_assoc()):
        ?>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="relative">
                <?php if ($product['sale_price']): ?>
                <span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded z-10">
                    -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                </span>
                <?php endif; ?>

                <span class="absolute top-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-1 rounded z-10 flex items-center gap-1">
                    <i class="fas fa-star"></i>Nổi bật
                </span>

                <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="block relative bg-gray-50 p-6">
                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/300x300?text=Product'; ?>"
                         class="w-full h-48 object-contain" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </a>
            </div>

            <div class="p-4">
                <h6 class="font-medium text-gray-900 mb-3 line-clamp-2 min-h-[3rem]">
                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="hover:text-primary transition-colors">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </a>
                </h6>

                <div class="mb-3">
                    <?php if ($product['sale_price']): ?>
                        <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['sale_price']); ?></span>
                        <div class="text-gray-500 text-sm line-through"><?php echo formatPrice($product['price']); ?></div>
                    <?php else: ?>
                        <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="space-y-2">
                    <button class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium flex items-center justify-center gap-2 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Thêm vào giỏ</span>
                    </button>
                    <div class="flex gap-1">
                        <button class="flex-1 px-3 py-2 border border-red-500 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-colors wishlist-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="Yêu thích">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="flex-1 px-3 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors compare-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="So sánh sản phẩm">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $delay += 100;
        endwhile;
        ?>
    </div>

    <div class="text-center mt-8" data-aos="fade-up">
        <a href="products.php?featured=1" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-3 rounded-lg hover:bg-primary-dark transition-colors font-medium">
            <span>Xem tất cả sản phẩm nổi bật</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- Sản phẩm mới -->
<section class="mb-8">
    <h4 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center gap-2" data-aos="fade-right">
        <i class="fas fa-bolt text-primary"></i>
        <span>Sản phẩm mới</span>
    </h4>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $delay = 0;
        $new_products->data_seek(0);
        while ($product = $new_products->fetch_assoc()):
        ?>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="relative">
                <span class="absolute top-2 left-2 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded z-10 flex items-center gap-1">
                    <i class="fas fa-sparkles"></i>Mới
                </span>

                <?php if ($product['sale_price']): ?>
                <span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded z-10">
                    -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                </span>
                <?php endif; ?>

                <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="block relative bg-gray-50 p-6">
                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/300x300?text=Product'; ?>"
                         class="w-full h-48 object-contain" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </a>
            </div>

            <div class="p-4">
                <h6 class="font-medium text-gray-900 mb-3 line-clamp-2 min-h-[3rem]">
                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="hover:text-primary transition-colors">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </a>
                </h6>

                <div class="mb-3">
                    <?php if ($product['sale_price']): ?>
                        <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['sale_price']); ?></span>
                        <div class="text-gray-500 text-sm line-through"><?php echo formatPrice($product['price']); ?></div>
                    <?php else: ?>
                        <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="space-y-2">
                    <button class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium flex items-center justify-center gap-2 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Thêm vào giỏ</span>
                    </button>
                    <div class="flex gap-1">
                        <button class="flex-1 px-3 py-2 border border-red-500 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-colors wishlist-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="Yêu thích">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="flex-1 px-3 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors compare-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="So sánh sản phẩm">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $delay += 100;
        endwhile;
        ?>
    </div>
</section>

<!-- Statistics Counter Section -->
<section class="bg-white rounded-xl border border-gray-100 py-12 mb-8 shadow-sm" data-aos="fade-up">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div data-aos="zoom-in" data-aos-delay="0">
                <div class="w-20 h-20 mx-auto mb-4 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-2xl text-primary"></i>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-2 counter" data-target="50000">0</h2>
                <p class="text-gray-600 text-sm">Khách hàng tin tưởng</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="100">
                <div class="w-20 h-20 mx-auto mb-4 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-box text-2xl text-primary"></i>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-2 counter" data-target="10000">0</h2>
                <p class="text-gray-600 text-sm">Sản phẩm chính hãng</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="200">
                <div class="w-20 h-20 mx-auto mb-4 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-2xl text-primary"></i>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-2 counter" data-target="100">0</h2>
                <p class="text-gray-600 text-sm">Cửa hàng toàn quốc</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="300">
                <div class="w-20 h-20 mx-auto mb-4 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-award text-2xl text-primary"></i>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-2 counter" data-target="15">0</h2>
                <p class="text-gray-600 text-sm">Năm kinh nghiệm</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

