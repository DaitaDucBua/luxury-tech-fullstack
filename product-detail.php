<?php
require_once 'config/config.php';

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    redirect('index.php');
}

// Lấy thông tin sản phẩm
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.slug = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('index.php');
}

$product = $result->fetch_assoc();
$page_title = $product['name'];

// Cập nhật lượt xem
$update_views = "UPDATE products SET views = views + 1 WHERE id = ?";
$update_stmt = $conn->prepare($update_views);
$update_stmt->bind_param("i", $product['id']);
$update_stmt->execute();

// Lấy sản phẩm liên quan
$related_sql = "SELECT * FROM products 
                WHERE category_id = ? AND id != ? 
                ORDER BY RAND() 
                LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['category_id'], $product['id']);
$related_stmt->execute();
$related_products = $related_stmt->get_result();

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center gap-2 text-sm">
        <li><a href="index.php" class="text-primary hover:text-primary-dark transition-colors">Trang chủ</a></li>
        <li><span class="text-gray-400">/</span></li>
        <li><a href="products.php?category=<?php echo $product['category_slug']; ?>" class="text-primary hover:text-primary-dark transition-colors"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
        <li><span class="text-gray-400">/</span></li>
        <li class="text-gray-600"><?php echo htmlspecialchars($product['name']); ?></li>
    </ol>
</nav>

<!-- Product Detail -->
<?php
// Lấy lại dữ liệu ảnh mới nhất từ DB (tránh cache)
$img_sql = "SELECT image, images FROM products WHERE id = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $product['id']);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$img_data = $img_result->fetch_assoc();
$product['image'] = $img_data['image'];
$product['images'] = $img_data['images'];

// Chuẩn bị danh sách ảnh
$all_images = [];
if (!empty($product['images']) && $product['images'] !== 'null') {
    $all_images = json_decode($product['images'], true) ?: [];
}
// Nếu không có ảnh trong images, dùng ảnh chính
if (empty($all_images) && !empty($product['image'])) {
    $all_images = [$product['image']];
}
// Lọc ảnh trùng và rỗng
$all_images = array_unique(array_filter($all_images));
$all_images = array_values($all_images);
$total_images = count($all_images);
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
    <!-- Product Images -->
    <div>
        <div class="bg-gray-50 rounded-2xl p-6 shadow-sm">
            <!-- Main Image with Navigation -->
            <div class="relative bg-white rounded-xl p-8 mb-4 min-h-[400px] flex items-center justify-center">
                <button class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-colors z-10 gallery-nav gallery-prev" onclick="changeImage(-1)" aria-label="Previous">
                    <i class="fas fa-chevron-left text-gray-700"></i>
                </button>

                <img src="<?php echo htmlspecialchars($all_images[0] ?? 'https://via.placeholder.com/500x500?text=Product'); ?>"
                     class="max-w-full max-h-[380px] object-contain transition-opacity duration-300"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     id="mainImage"
                     onerror="this.src='https://via.placeholder.com/500x500?text=No+Image'">

                <button class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-colors z-10 gallery-nav gallery-next" onclick="changeImage(1)" aria-label="Next">
                    <i class="fas fa-chevron-right text-gray-700"></i>
                </button>

                <!-- Image Counter -->
                <div class="absolute bottom-4 right-4 bg-black/60 text-white px-3 py-1.5 rounded-full text-sm font-medium">
                    <span id="currentImageIndex">1</span>/<span><?php echo $total_images; ?></span>
                </div>
            </div>

            <!-- Thumbnail Strip -->
            <div class="flex items-center gap-2">
                <button class="flex-shrink-0 w-8 h-8 border border-gray-200 bg-white rounded-full flex items-center justify-center hover:border-primary hover:text-primary transition-colors thumb-nav thumb-prev" onclick="scrollThumbnails(-1)" aria-label="Previous thumbnails">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>

                <div class="flex gap-2 overflow-x-auto flex-1 scrollbar-hide" id="thumbnailStrip" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <?php foreach ($all_images as $index => $img): ?>
                    <div class="flex-shrink-0 w-16 h-16 border-2 rounded-lg overflow-hidden cursor-pointer transition-all <?php echo $index === 0 ? 'border-primary shadow-md' : 'border-gray-200 hover:border-primary'; ?> thumb-item"
                         data-index="<?php echo $index; ?>"
                         onclick="goToImage(<?php echo $index; ?>)">
                        <img src="<?php echo htmlspecialchars($img); ?>"
                             class="w-full h-full object-cover"
                             alt="<?php echo htmlspecialchars($product['name']); ?> - Ảnh <?php echo $index + 1; ?>"
                             onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                    </div>
                    <?php endforeach; ?>
                </div>

                <button class="flex-shrink-0 w-8 h-8 border border-gray-200 bg-white rounded-full flex items-center justify-center hover:border-primary hover:text-primary transition-colors thumb-nav thumb-next" onclick="scrollThumbnails(1)" aria-label="Next thumbnails">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Product Info -->
    <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h2>

        <div class="flex gap-2 mb-4">
            <span class="bg-gray-900 text-white px-3 py-1 rounded-lg text-sm font-medium"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <?php if ($product['is_featured']): ?>
            <span class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-gray-900 px-3 py-1 rounded-lg text-sm font-semibold">Nổi bật</span>
            <?php endif; ?>
        </div>

        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-primary p-6 rounded-xl mb-6">
            <?php if ($product['sale_price']): ?>
                <h3 class="text-4xl font-bold text-primary mb-2"><?php echo formatPrice($product['sale_price']); ?></h3>
                <div class="flex items-center gap-3">
                    <span class="text-gray-500 line-through text-lg"><?php echo formatPrice($product['price']); ?></span>
                    <span class="bg-red-600 text-white px-3 py-1 rounded-lg text-sm font-semibold">
                        Giảm <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                    </span>
                </div>
            <?php else: ?>
                <h3 class="text-4xl font-bold text-primary"><?php echo formatPrice($product['price']); ?></h3>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="mb-6">
            <h5 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="fas fa-info-circle text-primary"></i>
                <span>Mô tả sản phẩm</span>
            </h5>
            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>

        <!-- Specifications -->
        <?php if (!empty($product['specifications'])): ?>
        <div class="mb-6">
            <h5 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="fas fa-cogs text-primary"></i>
                <span>Thông số kỹ thuật</span>
            </h5>
            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['specifications'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Stock Status -->
        <div class="mb-6">
            <?php if ($product['stock'] > 0): ?>
                <span class="inline-flex items-center gap-2 bg-green-50 text-green-700 px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-check-circle"></i>
                    <span>Còn hàng (<?php echo $product['stock']; ?> sản phẩm)</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 bg-red-50 text-red-700 px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-times-circle"></i>
                    <span>Hết hàng</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Quantity & Add to Cart -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng:</label>
            <div class="flex items-center gap-2 max-w-[200px]">
                <button class="w-10 h-10 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg flex items-center justify-center transition-colors" type="button" id="decreaseQty">-</button>
                <input type="number" class="flex-1 h-10 text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" value="1" min="1" max="<?php echo $product['stock']; ?>" id="quantity">
                <button class="w-10 h-10 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg flex items-center justify-center transition-colors" type="button" id="increaseQty">+</button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 mb-6">
            <?php if ($product['stock'] > 0): ?>
            <button class="flex-1 min-w-[200px] bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 add-to-cart-detail" data-product-id="<?php echo $product['id']; ?>">
                <i class="fas fa-cart-plus"></i>
                <span>Thêm vào giỏ hàng</span>
            </button>
            <!-- Nút Mua ngay đã được ẩn -->
            <!-- <button class="flex-1 min-w-[200px] bg-gray-900 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-bolt"></i>
                <span>Mua ngay</span>
            </button> -->
            <?php else: ?>
            <button class="flex-1 min-w-[200px] bg-gray-300 text-gray-600 px-6 py-3 rounded-lg font-semibold cursor-not-allowed flex items-center justify-center gap-2" disabled>
                <i class="fas fa-times"></i>
                <span>Hết hàng</span>
            </button>
            <?php endif; ?>
            <button class="px-6 py-3 border-2 border-red-500 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-colors wishlist-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="Yêu thích">
                <i class="far fa-heart text-lg"></i>
            </button>
            <button class="px-6 py-3 border-2 border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors compare-toggle-btn" data-product-id="<?php echo $product['id']; ?>" title="So sánh sản phẩm">
                <i class="fas fa-balance-scale text-lg"></i>
            </button>
        </div>

        <!-- Additional Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 p-4 rounded-xl hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3">
                    <i class="fas fa-shield-alt text-primary text-xl mt-1"></i>
                    <div>
                        <strong class="text-gray-900 block mb-1">Bảo hành chính hãng</strong>
                        <p class="text-sm text-gray-600">12 tháng tại các trung tâm bảo hành</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 p-4 rounded-xl hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3">
                    <i class="fas fa-truck text-primary text-xl mt-1"></i>
                    <div>
                        <strong class="text-gray-900 block mb-1">Giao hàng toàn quốc</strong>
                        <p class="text-sm text-gray-600">Miễn phí vận chuyển cho đơn từ 500k</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if ($related_products->num_rows > 0): ?>
<section class="py-8">
    <h4 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
        <i class="fas fa-box-open text-primary"></i>
        <span>Sản phẩm liên quan</span>
    </h4>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php while ($related = $related_products->fetch_assoc()): ?>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
            <a href="product-detail.php?slug=<?php echo $related['slug']; ?>" class="block bg-gray-50 p-5">
                <img src="<?php echo $related['image'] ?: 'https://via.placeholder.com/300x300?text=Product'; ?>"
                     class="w-full h-44 object-contain" alt="<?php echo htmlspecialchars($related['name']); ?>">
            </a>
            <div class="p-4">
                <h6 class="font-medium text-gray-900 mb-2 line-clamp-2 min-h-[3rem]">
                    <a href="product-detail.php?slug=<?php echo $related['slug']; ?>" class="hover:text-primary transition-colors">
                        <?php echo htmlspecialchars($related['name']); ?>
                    </a>
                </h6>
                <div class="text-primary font-bold text-lg">
                    <?php if ($related['sale_price']): ?>
                        <?php echo formatPrice($related['sale_price']); ?>
                    <?php else: ?>
                        <?php echo formatPrice($related['price']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<!-- Product Reviews Section -->
<section class="product-reviews py-8 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-white border-b border-gray-100 px-6 py-4">
                <h4 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i>
                    <span>Đánh giá sản phẩm</span>
                    <span class="bg-primary text-white px-2 py-1 rounded-lg text-sm font-semibold" id="reviewsCount">0</span>
                </h4>
            </div>
            <div class="p-6">
                        <!-- Add Review Form - Always show -->
                        <div class="mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <?php if (isLoggedIn()): ?>
                                    <button class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center gap-2" onclick="document.getElementById('reviewModal').classList.remove('hidden')">
                                        <i class="fas fa-plus"></i>
                                        <span>Viết đánh giá</span>
                                    </button>
                                    <?php else: ?>
                                    <a href="login.php" class="inline-flex items-center gap-2 border-2 border-primary text-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Đăng nhập để đánh giá</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h6 class="font-semibold text-gray-900 mb-2">Hướng dẫn đánh giá:</h6>
                                        <ul class="text-sm text-gray-600 space-y-1">
                                            <li>• Đánh giá trung thực, khách quan</li>
                                            <li>• Không spam hoặc nội dung không liên quan</li>
                                            <li>• Review sẽ được duyệt trước khi hiển thị</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reviews Content - Only show if there are approved reviews -->
                        <div id="reviewsContent">
                            <!-- Reviews Summary -->
                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="text-center">
                                        <div class="rating-average">
                                            <h2 class="text-4xl font-bold text-yellow-400 mb-2" id="averageRating">0.0</h2>
                                            <div class="rating-stars mb-2 flex justify-center gap-1" id="averageStars">
                                                <!-- Stars sẽ được tạo bằng JavaScript -->
                                            </div>
                                            <small class="text-gray-600" id="totalReviews">0 đánh giá</small>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="rating-distribution space-y-2" id="ratingBars">
                                            <!-- Rating bars sẽ được tạo bằng JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reviews List -->
                            <div class="reviews-list">
                                <div id="reviewsContainer">
                                    <div class="text-center py-8">
                                        <div class="inline-block w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                                        <p class="mt-4 text-gray-600">Đang tải đánh giá...</p>
                                    </div>
                                </div>

                                <!-- Load More Button -->
                                <div class="text-center mt-6 hidden" id="loadMoreContainer">
                                    <button class="border-2 border-primary text-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium flex items-center gap-2 mx-auto" id="loadMoreBtn">
                                        <i class="fas fa-plus"></i>
                                        <span>Xem thêm đánh giá</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No Reviews Message -->
                        <div id="noReviewsMessage" class="text-center py-12">
                            <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                            <h5 class="text-gray-600 text-lg font-semibold mb-2">Chưa có đánh giá nào</h5>
                            <p class="text-gray-500">Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Review Modal -->
<div id="reviewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('reviewModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h5 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i>
                    <span>Viết đánh giá cho: <?php echo htmlspecialchars($product['name']); ?></span>
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('reviewModal').classList.add('hidden')">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="reviewForm">
                <div class="p-6">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                    <!-- Rating Stars -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Đánh giá của bạn <span class="text-red-500">*</span></label>
                        <div class="rating-input">
                            <div class="flex gap-1 mb-2" id="ratingStars">
                                <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="1"></i>
                                <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="2"></i>
                                <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="3"></i>
                                <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="4"></i>
                                <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" required>
                            <div class="rating-text">
                                <span id="ratingText" class="text-gray-600">Chọn số sao</span>
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" name="title" placeholder="Tóm tắt đánh giá của bạn" required maxlength="255">
                    </div>

                    <!-- Comment -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung đánh giá <span class="text-red-500">*</span></label>
                        <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" name="comment" rows="4" placeholder="Hãy chia sẻ trải nghiệm của bạn về sản phẩm này..." required maxlength="1000"></textarea>
                        <div class="text-sm text-gray-500 mt-1">Tối đa 1000 ký tự</div>
                    </div>

                    <!-- Terms -->
                    <div class="mb-6">
                        <div class="flex items-start gap-2">
                            <input class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" type="checkbox" id="termsCheck" required>
                            <label class="text-sm text-gray-600" for="termsCheck">
                                Tôi cam kết đánh giá trung thực và tôn trọng. Review sẽ được kiểm duyệt trước khi hiển thị.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" onclick="document.getElementById('reviewModal').classList.add('hidden')">Hủy</button>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Gửi đánh giá</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Product Gallery
const images = <?php echo json_encode(array_values($all_images)); ?>;
let currentIndex = 0;

function changeImage(direction) {
    currentIndex += direction;
    if (currentIndex < 0) currentIndex = images.length - 1;
    if (currentIndex >= images.length) currentIndex = 0;
    goToImage(currentIndex);
}

function goToImage(index) {
    currentIndex = index;
    const mainImage = document.getElementById('mainImage');
    mainImage.style.opacity = '0.5';

    setTimeout(() => {
        mainImage.src = images[index];
        mainImage.style.opacity = '1';
    }, 150);

    document.getElementById('currentImageIndex').textContent = index + 1;

    // Update active thumbnail
    document.querySelectorAll('.thumb-item').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });

    // Scroll thumbnail into view
    const activeThumb = document.querySelector(`.thumb-item[data-index="${index}"]`);
    if (activeThumb) {
        activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
}

function scrollThumbnails(direction) {
    const strip = document.getElementById('thumbnailStrip');
    const scrollAmount = 160;
    strip.scrollLeft += direction * scrollAmount;
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') changeImage(-1);
    if (e.key === 'ArrowRight') changeImage(1);
});

// ========================================
// PRODUCT REVIEWS SYSTEM
// ========================================
class ProductReviews {
    constructor(productId) {
        this.productId = productId;
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMore = true;
        this.init();
    }

    init() {
        this.loadReviews();
        this.bindEvents();
    }

    bindEvents() {
        // Rating stars in modal
        this.bindRatingStars();

        // Review form submission
        document.getElementById('reviewForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitReview();
        });

        // Load more button
        document.getElementById('loadMoreBtn')?.addEventListener('click', () => {
            this.loadMoreReviews();
        });
    }

    bindRatingStars() {
        const stars = document.querySelectorAll('#ratingStars i');
        const ratingValue = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');

        const ratingTexts = {
            1: 'Rất tệ',
            2: 'Tệ',
            3: 'Bình thường',
            4: 'Tốt',
            5: 'Xuất sắc'
        };

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.dataset.rating);
                ratingValue.value = rating;

                // Update stars display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.className = 'fas fa-star text-warning';
                    } else {
                        s.className = 'far fa-star';
                    }
                });

                // Update text
                ratingText.textContent = ratingTexts[rating] || 'Chọn số sao';
                ratingText.className = 'text-muted';
            });

            star.addEventListener('mouseenter', () => {
                const rating = parseInt(star.dataset.rating);
                ratingText.textContent = ratingTexts[rating] || 'Chọn số sao';
            });

            star.addEventListener('mouseleave', () => {
                const currentRating = parseInt(ratingValue.value) || 0;
                ratingText.textContent = currentRating > 0 ? ratingTexts[currentRating] : 'Chọn số sao';
            });
        });
    }

    async loadReviews() {
        if (this.isLoading) return;

        this.isLoading = true;
        const container = document.getElementById('reviewsContainer');

        try {
            const formData = new FormData();
            formData.append('action', 'get_reviews');
            formData.append('product_id', this.productId);
            formData.append('page', this.currentPage);

            const response = await fetch('ajax/review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (this.currentPage === 1) {
                    container.innerHTML = '';
                }

                const reviewsContent = document.getElementById('reviewsContent');
                const noReviewsMessage = document.getElementById('noReviewsMessage');

                if (data.reviews.length > 0) {
                    // Có reviews - hiển thị reviews content, ẩn no reviews message
                    if (reviewsContent) reviewsContent.style.display = 'block';
                    if (noReviewsMessage) noReviewsMessage.style.display = 'none';

                    data.reviews.forEach(review => {
                        container.appendChild(this.createReviewElement(review));
                    });

                    // Update summary
                    this.updateReviewsSummary(data.reviews);
                } else if (this.currentPage === 1) {
                    // Không có reviews - ẩn reviews content, hiển thị no reviews message
                    if (reviewsContent) reviewsContent.style.display = 'none';
                    if (noReviewsMessage) noReviewsMessage.style.display = 'block';
                }

                this.hasMore = data.has_more;
                document.getElementById('loadMoreContainer').style.display = this.hasMore ? 'block' : 'none';
            } else {
                if (this.currentPage === 1) {
                    container.innerHTML = `
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                            <div class="flex items-center gap-2 text-red-700">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>${data.message}</span>
                            </div>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
            if (this.currentPage === 1) {
                container.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-center gap-2 text-red-700">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Có lỗi xảy ra khi tải đánh giá</span>
                        </div>
                    </div>
                `;
            }
        } finally {
            this.isLoading = false;
        }
    }

    loadMoreReviews() {
        this.currentPage++;
        this.loadReviews();
    }

    createReviewElement(review) {
        const reviewDiv = document.createElement('div');
        reviewDiv.className = 'border-b border-gray-200 pb-4 mb-4';

        const stars = this.generateStars(review.rating);
        const date = new Date(review.created_at).toLocaleDateString('vi-VN');
        const userReaction = review.user_reaction;

        reviewDiv.innerHTML = `
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                        ${review.username.charAt(0).toUpperCase()}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <strong class="text-gray-900">${review.username}</strong>
                            <small class="text-gray-500">${date}</small>
                        </div>
                        <div class="flex gap-1">
                            ${stars}
                        </div>
                    </div>

                    <h6 class="font-semibold text-gray-900 mb-2">${review.title}</h6>
                    <p class="text-gray-600 mb-3">${review.comment}</p>

                    ${review.admin_reply ? `
                    <div class="bg-gray-50 p-4 rounded-lg mt-3">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-shield-alt text-primary"></i>
                            <strong class="text-gray-900">Phản hồi từ Admin</strong>
                        </div>
                        <p class="text-gray-600 mb-0">${review.admin_reply}</p>
                    </div>
                    ` : ''}

                    <div class="flex items-center gap-2 mt-3">
                        <button class="px-3 py-1.5 border border-green-500 text-green-600 rounded-lg hover:bg-green-500 hover:text-white transition-colors text-sm like-btn ${userReaction === 'like' ? 'bg-green-500 text-white' : ''}"
                                data-review-id="${review.id}" data-action="like">
                            <i class="fas fa-thumbs-up mr-1"></i>
                            Thích <span class="count">(${review.likes})</span>
                        </button>
                        <button class="px-3 py-1.5 border border-red-500 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition-colors text-sm dislike-btn ${userReaction === 'dislike' ? 'bg-red-500 text-white' : ''}"
                                data-review-id="${review.id}" data-action="dislike">
                            <i class="fas fa-thumbs-down mr-1"></i>
                            Không thích <span class="count">(${review.dislikes})</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Bind like/dislike events
        reviewDiv.querySelector('.like-btn').addEventListener('click', () => this.handleLikeDislike(review.id, 'like'));
        reviewDiv.querySelector('.dislike-btn').addEventListener('click', () => this.handleLikeDislike(review.id, 'dislike'));

        return reviewDiv;
    }

    generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-muted'}"></i>`;
        }
        return stars;
    }

    updateReviewsSummary(reviews) {
        if (reviews.length === 0) return;

        // Calculate average rating
        const totalRating = reviews.reduce((sum, review) => sum + review.rating, 0);
        const averageRating = (totalRating / reviews.length).toFixed(1);

        // Update average rating display
        document.getElementById('averageRating').textContent = averageRating;
        document.getElementById('totalReviews').textContent = `${reviews.length} đánh giá`;
        document.getElementById('reviewsCount').textContent = reviews.length;

        // Update average stars
        const averageStarsContainer = document.getElementById('averageStars');
        averageStarsContainer.innerHTML = this.generateStars(Math.round(averageRating));

        // Calculate rating distribution
        const distribution = [0, 0, 0, 0, 0];
        reviews.forEach(review => {
            if (review.rating >= 1 && review.rating <= 5) {
                distribution[review.rating - 1]++;
            }
        });

        // Update rating bars
        const ratingBarsContainer = document.getElementById('ratingBars');
        ratingBarsContainer.innerHTML = '';

        for (let i = 5; i >= 1; i--) {
            const count = distribution[i - 1];
            const percentage = reviews.length > 0 ? (count / reviews.length * 100).toFixed(0) : 0;

            ratingBarsContainer.innerHTML += `
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-12 text-sm">${i} <i class="fas fa-star text-yellow-400"></i></span>
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-400 h-2 rounded-full" style="width: ${percentage}%"></div>
                    </div>
                    <span class="w-12 text-sm text-gray-600 text-right">${count}</span>
                </div>
            `;
        }
    }

    async submitReview() {
        const form = document.getElementById('reviewForm');
        const formData = new FormData(form);

        formData.append('action', 'add_review');

        try {
            const response = await fetch('ajax/review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Close modal
                document.getElementById('reviewModal').classList.add('hidden');

                // Reset form
                form.reset();
                document.getElementById('ratingValue').value = '';
                document.querySelectorAll('#ratingStars i').forEach(star => {
                    star.className = 'far fa-star';
                });
                document.getElementById('ratingText').textContent = 'Chọn số sao';

                // Show success message
                Ajax.showNotification(data.message, 'success');

                // Reload reviews - sẽ hiển thị message pending
                this.currentPage = 1;
                this.loadReviews();
            } else {
                Ajax.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
        }
    }

    async handleLikeDislike(reviewId, action) {
        try {
            const formData = new FormData();
            formData.append('action', action === 'like' ? 'like_review' : 'dislike_review');
            formData.append('review_id', reviewId);

            const response = await fetch('ajax/review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update UI
                const reviewElement = document.querySelector(`[data-review-id="${reviewId}"]`);
                if (reviewElement) {
                    const likeBtn = reviewElement.querySelector('.like-btn');
                    const dislikeBtn = reviewElement.querySelector('.dislike-btn');

                    // Reset both buttons
                    likeBtn.classList.remove('active');
                    dislikeBtn.classList.remove('active');

                    if (data.action === 'added' || data.action === 'changed') {
                        if (action === 'like') {
                            likeBtn.classList.add('active');
                        } else {
                            dislikeBtn.classList.add('active');
                        }
                    }

                    // Reload reviews to update counts
                    this.currentPage = 1;
                    this.loadReviews();
                }
            } else {
                Ajax.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error handling like/dislike:', error);
            Ajax.showNotification('Có lỗi xảy ra', 'error');
        }
    }
}

// Initialize reviews when page loads
document.addEventListener('DOMContentLoaded', function() {
    const productId = <?php echo $product['id']; ?>;
    
    // Ensure reviews section is visible
    const reviewsSection = document.querySelector('.product-reviews');
    if (reviewsSection) {
        reviewsSection.style.display = 'block';
    }

    // Initialize reviews
    try {
        window.productReviews = new ProductReviews(productId);
        
        // Initially hide reviews content and show no reviews message
        const reviewsContent = document.getElementById('reviewsContent');
        const noReviewsMessage = document.getElementById('noReviewsMessage');
        
        if (reviewsContent) {
            reviewsContent.style.display = 'none';
        }
        if (noReviewsMessage) {
            noReviewsMessage.style.display = 'block';
        }
    } catch (error) {
        console.error('Error initializing reviews:', error);
        // Fallback: show no reviews message if JS fails
        const reviewsContent = document.getElementById('reviewsContent');
        const noReviewsMessage = document.getElementById('noReviewsMessage');
        if (reviewsContent) reviewsContent.style.display = 'none';
        if (noReviewsMessage) noReviewsMessage.style.display = 'block';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
