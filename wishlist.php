<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';

// Prevent caching - Force no cache
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('ETag: "' . md5(time()) . '"');
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Danh Sách Yêu Thích';

// Lấy tham số sắp xếp
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$order_by = 'w.created_at DESC';
switch ($sort) {
    case 'name':
        $order_by = 'p.name ASC';
        break;
    case 'price_asc':
        $order_by = 'COALESCE(p.sale_price, p.price) ASC';
        break;
    case 'price_desc':
        $order_by = 'COALESCE(p.sale_price, p.price) DESC';
        break;
    case 'oldest':
        $order_by = 'w.created_at ASC';
        break;
}

// Lấy danh sách wishlist - Thêm cache control để đảm bảo load dữ liệu mới nhất
// Sử dụng DISTINCT để tránh duplicate và chỉ lấy sản phẩm active
// Thêm SQL_NO_CACHE để đảm bảo query luôn fresh
$query = "SELECT SQL_NO_CACHE DISTINCT w.*, p.id as product_id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.status,
          c.name as category_name,
          (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
          FROM wishlist w
          INNER JOIN products p ON w.product_id = p.id AND p.status = 'active'
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE w.user_id = ?
          ORDER BY $order_by";

// Thêm cache-busting parameter để đảm bảo load dữ liệu mới nhất
$cache_bust = isset($_GET['t']) ? intval($_GET['t']) : time();

// Force fresh query - không cache kết quả
// Đảm bảo autocommit được bật
$conn->autocommit(TRUE);

$stmt = $conn->prepare($query);
if (!$stmt) {
    die('Database error: ' . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die('Database error: ' . $stmt->error);
}
$wishlist_items = $stmt->get_result();
if (!$wishlist_items) {
    die('Database error: ' . $conn->error);
}
$total_items = $wishlist_items->num_rows;

// Debug: Log để kiểm tra
if (isset($_GET['debug'])) {
    error_log("Wishlist query executed. User ID: $user_id, Total items: $total_items, Cache bust: $cache_bust");
}

// Reset result pointer để có thể fetch lại
if ($total_items > 0) {
    $wishlist_items->data_seek(0);
}

include 'includes/header.php';
?>

<script>
// Force refresh với cache-busting khi vào lại trang từ trang khác
(function() {
    // Kiểm tra xem có phải là vào lại từ trang khác không
    const referrer = document.referrer;
    const currentHost = window.location.hostname;
    const referrerHost = referrer ? new URL(referrer).hostname : '';
    
    // Nếu referrer là từ cùng domain nhưng không phải từ chính trang wishlist.php
    const isFromOtherPage = referrerHost === currentHost && !referrer.includes('wishlist.php');
    
    const urlParams = new URLSearchParams(window.location.search);
    const hasCacheParam = urlParams.has('t') || urlParams.has('nocache') || urlParams.has('refresh') || urlParams.has('v');
    
    // Nếu vào từ trang khác và không có tham số cache và không phải là reload sau khi xóa hoặc auto-refresh
    if (isFromOtherPage && !hasCacheParam && !sessionStorage.getItem('wishlistJustDeleted') && !sessionStorage.getItem('wishlistAutoRefresh')) {
        try {
            const currentUrl = new URL(window.location.href);
            const basePath = currentUrl.pathname;
            
            // Giữ lại các tham số hiện có (như sort)
            const params = new URLSearchParams(currentUrl.search);
            
            // Thêm cache-busting parameters
            const timestamp = Date.now();
            const random = Math.random();
            params.set('t', timestamp);
            params.set('nocache', random);
            params.set('refresh', random);
            params.set('v', timestamp);
            
            // Tạo URL mới
            const newUrl = basePath + '?' + params.toString();
            
            // Chỉ redirect nếu URL khác
            if (newUrl !== window.location.href) {
                // Đánh dấu là auto-refresh để không trigger lại
                sessionStorage.setItem('wishlistAutoRefresh', 'true');
                window.location.replace(newUrl);
                return;
            }
        } catch (e) {
            console.error('Error creating URL:', e);
            // Fallback: reload với timestamp đơn giản
            const basePath = window.location.pathname;
            const queryString = window.location.search;
            const separator = queryString ? '&' : '?';
            const newUrl = basePath + queryString + separator + 't=' + Date.now() + '&nocache=' + Math.random();
            sessionStorage.setItem('wishlistAutoRefresh', 'true');
            window.location.replace(newUrl);
            return;
        }
    }
    
    // Xóa flag sau khi đã xử lý
    if (sessionStorage.getItem('wishlistJustDeleted')) {
        sessionStorage.removeItem('wishlistJustDeleted');
    }
    if (sessionStorage.getItem('wishlistAutoRefresh')) {
        sessionStorage.removeItem('wishlistAutoRefresh');
    }
})();
</script>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4" data-aos="fade-down">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-heart text-red-500 mr-3"></i> Danh Sách Yêu Thích
                </h2>
                <p class="text-gray-500 text-sm">
                    Bạn có <strong id="wishlist-count" class="text-amber-600 font-semibold"><?php echo $total_items; ?></strong> sản phẩm yêu thích
                </p>
            </div>
            <?php if ($total_items > 0): ?>
            <div>
                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm" id="sort-wishlist">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Tên A-Z</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($total_items === 0): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center" data-aos="fade-up">
            <div class="w-20 h-20 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-heart-broken text-3xl text-blue-600"></i>
            </div>
            <h5 class="text-lg font-semibold text-gray-800 mb-2">Danh sách yêu thích trống</h5>
            <p class="text-gray-600 mb-6">Bạn chưa có sản phẩm yêu thích nào. Hãy thêm sản phẩm vào danh sách!</p>
            <a href="products.php" 
               class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-shopping-bag mr-2"></i> Khám Phá Sản Phẩm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4 md:gap-6" id="wishlist-container">
            <?php 
            $delay = 0;
            if ($wishlist_items && $total_items > 0):
            while ($item = $wishlist_items->fetch_assoc()):
                // Validate item data
                if (empty($item['product_id']) || empty($item['name'])) {
                    continue; // Skip invalid items
                } 
                $discount_percent = 0;
                if ($item['sale_price'] && $item['sale_price'] < $item['price'] && $item['price'] > 0) {
                    $discount_percent = round((($item['price'] - $item['sale_price']) / $item['price']) * 100);
                }
                $avg_rating = round($item['avg_rating'] ?? 0, 1);
            ?>
            <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col product-card transition-all duration-300 hover:shadow-md">
                    <!-- Badge giảm giá -->
                    <?php if ($discount_percent > 0): ?>
                    <span class="absolute top-2 left-2 z-10 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white">
                        -<?php echo $discount_percent; ?>%
                    </span>
                    <?php endif; ?>
                    
                    <!-- Nút xóa -->
                    <button class="absolute top-2 right-2 z-20 w-8 h-8 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition-colors remove-wishlist" 
                            data-product-id="<?php echo $item['product_id']; ?>"
                            title="Xóa khỏi yêu thích">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    
                    <!-- Hình ảnh -->
                    <a href="product-detail.php?slug=<?php echo $item['slug']; ?>" class="block relative overflow-hidden bg-gray-100">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                             class="w-full h-48 object-cover transition-transform duration-300 product-image" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </a>
                    
                    <!-- Thông tin -->
                    <div class="p-4 flex flex-col flex-grow">
                        <small class="text-gray-500 text-xs mb-1"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></small>
                        
                        <h6 class="font-medium text-gray-900 mb-2 min-h-[2.5rem] line-clamp-2">
                            <a href="product-detail.php?slug=<?php echo $item['slug']; ?>" 
                               class="text-gray-900 hover:text-amber-600 transition-colors">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h6>
                        
                        <!-- Rating -->
                        <?php if ($item['review_count'] > 0): ?>
                        <div class="mb-2">
                            <div class="flex items-center text-amber-500 text-xs mb-1">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($avg_rating)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $avg_rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <small class="text-gray-500 text-xs"><?php echo $avg_rating; ?>/5 (<?php echo $item['review_count']; ?>)</small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Giá -->
                        <div class="mb-3">
                            <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                <div class="flex items-center gap-2">
                                    <span class="text-red-600 font-bold text-lg"><?php echo number_format($item['sale_price']); ?>đ</span>
                                    <small class="text-gray-400 line-through text-sm">
                                        <?php echo number_format($item['price']); ?>đ
                                    </small>
                                </div>
                            <?php else: ?>
                                <span class="text-red-600 font-bold text-lg"><?php echo number_format($item['price']); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tình trạng -->
                        <div class="mb-3">
                            <?php if ($item['stock'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Còn hàng (<?php echo $item['stock']; ?>)
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    Hết hàng
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="mt-auto space-y-2">
                            <?php if ($item['stock'] > 0): ?>
                                <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors add-to-cart" 
                                        data-product-id="<?php echo $item['product_id']; ?>">
                                    <i class="fas fa-cart-plus mr-2"></i> Thêm vào giỏ
                                </button>
                            <?php else: ?>
                                <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                                    <i class="fas fa-ban mr-2"></i> Hết hàng
                                </button>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <a href="product-detail.php?slug=<?php echo $item['slug']; ?>" 
                                   class="inline-flex items-center justify-center px-3 py-2 border border-amber-600 text-amber-600 hover:bg-amber-50 text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </a>
                                <button class="inline-flex items-center justify-center px-3 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-medium rounded-lg transition-colors compare-toggle-btn" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        title="So sánh">
                                    <i class="fas fa-balance-scale"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $delay += 50;
            endwhile;
            endif; // End if $wishlist_items && $total_items > 0
            ?>
        </div>
        
        <div class="text-center mt-8" data-aos="fade-up">
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="products.php" 
                   class="inline-flex items-center px-6 py-3 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm Sản Phẩm Khác
                </a>
                <a href="compare.php" 
                   class="inline-flex items-center px-6 py-3 border-2 border-blue-600 text-blue-600 hover:bg-blue-50 font-medium rounded-lg transition-colors">
                    <i class="fas fa-balance-scale mr-2"></i> So Sánh Sản Phẩm
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Đợi jQuery load xong
(function() {
    function initWishlistScript() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            setTimeout(initWishlistScript, 100);
            return;
        }
        
        $(document).ready(function() {
            // Xóa khỏi wishlist
            $(document).on('click', '.remove-wishlist', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const button = $(this);
                const productId = button.data('product-id');
                const item = $(`.wishlist-item[data-product-id="${productId}"]`);
                
                if (!confirm('Xóa sản phẩm khỏi danh sách yêu thích?')) {
                    return;
                }
                
                // Disable button
                button.prop('disabled', true);
                const originalHtml = button.html();
                button.html('<span class="spinner-border spinner-border-sm"></span>');
                
                // Lấy URL từ config hoặc dùng đường dẫn tương đối
                let url = 'ajax/wishlist.php';
                if (typeof window.SITE_CONFIG !== 'undefined' && window.SITE_CONFIG.siteUrl) {
                    url = window.SITE_CONFIG.siteUrl + '/ajax/wishlist.php';
                } else if (typeof SITE_URL !== 'undefined') {
                    url = SITE_URL + '/ajax/wishlist.php';
                }
                
                // Set timeout để tránh spinner quay mãi
                const timeout = setTimeout(function() {
                    button.prop('disabled', false);
                    button.html(originalHtml);
                    if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                        Ajax.showNotification('Request timeout, vui lòng thử lại', 'error');
                    }
                }, 10000); // 10 giây timeout
                
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        action: 'remove',
                        product_id: productId
                    },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        clearTimeout(timeout);
                        
                        // Đảm bảo button được restore trước khi xử lý
                        button.prop('disabled', false);
                        
                        if (response && response.success) {
                            // Update count
                            if (response.wishlist_count !== undefined) {
                                $('#wishlist-count').text(response.wishlist_count);
                            }
                            
                            // Update wishlist count in header
                            if (typeof updateWishlistCount === 'function') {
                                updateWishlistCount();
                            }
                            
                            // Animation xóa
                            item.css({
                                'transform': 'scale(0.8)',
                                'opacity': '0',
                                'transition': 'all 0.3s ease'
                            });
                            
                            setTimeout(function() {
                                item.remove();
                                
                                // Luôn reload để đảm bảo dữ liệu mới nhất từ database
                                // Delay để đảm bảo database đã được cập nhật hoàn toàn
                                setTimeout(function() {
                                    // Reload với cache-busting mạnh và force refresh
                                    // Sử dụng window.location.href để đảm bảo URL đúng
                                    const currentUrl = new URL(window.location.href);
                                    const basePath = currentUrl.pathname;
                                    
                                    // Loại bỏ tất cả các tham số cache cũ
                                    const params = new URLSearchParams(currentUrl.search);
                                    params.delete('t');
                                    params.delete('nocache');
                                    params.delete('_');
                                    params.delete('refresh');
                                    params.delete('v');
                                    
                                    // Thêm cache-busting parameters
                                    const timestamp = Date.now();
                                    const random = Math.random();
                                    params.set('t', timestamp);
                                    params.set('nocache', random);
                                    params.set('_', timestamp);
                                    params.set('refresh', random);
                                    params.set('v', timestamp);
                                    
                                    // Tạo URL mới
                                    const newUrl = basePath + '?' + params.toString();
                                    
                                    // Đánh dấu là vừa xóa để không trigger auto-refresh
                                    sessionStorage.setItem('wishlistJustDeleted', 'true');
                                    // Force reload bằng cách thêm nhiều timestamp vào URL và dùng replace
                                    console.log('Reloading wishlist after delete. URL:', newUrl);
                                    window.location.replace(newUrl); // Dùng replace thay vì href để không lưu vào history
                                }, 2000); // Tăng delay lên 2 giây để đảm bảo database commit hoàn toàn
                            }, 300);
                            
                            // Show notification
                            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                                Ajax.showNotification(response.message || 'Đã xóa khỏi danh sách yêu thích', 'success');
                            } else {
                                alert(response.message || 'Đã xóa khỏi danh sách yêu thích');
                            }
                        } else {
                            button.html(originalHtml);
                            
                            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                                Ajax.showNotification(response.message || 'Có lỗi xảy ra', 'error');
                            } else {
                                alert(response.message || 'Có lỗi xảy ra');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        clearTimeout(timeout);
                        button.prop('disabled', false);
                        button.html(originalHtml);
                        
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        
                        if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                            Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
                        } else {
                            alert('Có lỗi xảy ra: ' + (error || 'Unknown error'));
                        }
                    }
                });
            });
            
            // Sắp xếp wishlist
            $('#sort-wishlist').on('change', function() {
                const sort = $(this).val();
                window.location.href = 'wishlist.php?sort=' + sort;
            });
            
            // Hover effect cho product card
            $('.product-card').hover(
                function() {
                    $(this).find('.product-image').addClass('scale-105');
                },
                function() {
                    $(this).find('.product-image').removeClass('scale-105');
                }
            );
            
            // Tự động kiểm tra và reload khi trang được focus (khi quay lại từ trang khác)
            let lastWishlistCount = <?php echo $total_items; ?>;
            let isChecking = false;
            let lastCheckTime = Date.now();
            
            function checkWishlistUpdate() {
                // Tránh check quá thường xuyên (tối thiểu 2 giây giữa các lần check)
                const now = Date.now();
                if (isChecking || (now - lastCheckTime < 2000)) {
                    return;
                }
                
                lastCheckTime = now;
                isChecking = true;
                
                // Lấy URL từ config hoặc dùng đường dẫn tương đối
                let url = 'ajax/wishlist.php';
                if (typeof window.SITE_CONFIG !== 'undefined' && window.SITE_CONFIG.siteUrl) {
                    url = window.SITE_CONFIG.siteUrl + '/ajax/wishlist.php';
                } else if (typeof SITE_URL !== 'undefined') {
                    url = SITE_URL + '/ajax/wishlist.php';
                }
                
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { action: 'get_count' },
                    dataType: 'json',
                    timeout: 5000,
                    success: function(response) {
                        if (response && response.success && response.count !== undefined) {
                            const currentCount = parseInt(response.count);
                            // Chỉ reload nếu số lượng tăng (thêm sản phẩm)
                            if (currentCount > lastWishlistCount) {
                                lastWishlistCount = currentCount;
                                // Reload với cache-busting
                                const baseUrl = window.location.pathname;
                                const queryString = window.location.search;
                                const separator = queryString ? '&' : '?';
                                window.location.href = baseUrl + queryString + separator + 't=' + Date.now() + '&nocache=' + Math.random();
                                return;
                            } else if (currentCount !== lastWishlistCount) {
                                // Cập nhật số lượng nhưng không reload
                                lastWishlistCount = currentCount;
                            }
                        }
                        isChecking = false;
                    },
                    error: function() {
                        isChecking = false;
                    }
                });
            }
            
            // Kiểm tra khi trang được focus (khi quay lại từ tab/trang khác)
            $(window).on('focus', function() {
                // Delay một chút để tránh reload ngay khi vừa load trang
                setTimeout(function() {
                    checkWishlistUpdate();
                }, 1000);
            });
            
            // Kiểm tra định kỳ mỗi 5 giây khi trang đang active
            setInterval(function() {
                if (!document.hidden) {
                    checkWishlistUpdate();
                }
            }, 5000);
        }); // End of $(document).ready
    }
    
    // Khởi động script
    initWishlistScript();
})();
</script>

<?php include 'includes/footer.php'; ?>

