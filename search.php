<?php
require_once 'config/config.php';

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page_title = 'Tìm kiếm: ' . $search;

if (empty($search)) {
    redirect('index.php');
}

// Tìm kiếm sản phẩm
$search_term = "%{$search}%";
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.name LIKE ? OR p.description LIKE ? OR p.specifications LIKE ?
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search_term, $search_term, $search_term);
$stmt->execute();
$products = $stmt->get_result();

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center space-x-2 text-sm">
        <li><a href="index.php" class="text-amber-600 hover:text-amber-700 transition-colors">Trang chủ</a></li>
        <li class="text-gray-400">/</li>
        <li class="text-gray-600">Tìm kiếm</li>
    </ol>
</nav>

<div class="mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-2">
        Kết quả tìm kiếm cho: <span class="text-amber-600">"<?php echo htmlspecialchars($search); ?>"</span>
    </h2>
    <p class="text-gray-500 text-sm">
        Tìm thấy <strong class="text-amber-600 font-semibold"><?php echo $products->num_rows; ?></strong> sản phẩm
    </p>
</div>

<?php if ($products->num_rows > 0): ?>
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4 md:gap-6">
    <?php while ($product = $products->fetch_assoc()): ?>
    <div class="product-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col hover:shadow-md transition-shadow">
        <?php if ($product['sale_price']): ?>
        <span class="absolute top-2 right-2 z-10 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white">
            -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
        </span>
        <?php endif; ?>
        
        <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="block relative overflow-hidden bg-gray-100">
            <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/300x300?text=Product'; ?>" 
                 class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        </a>
        
        <div class="p-4 flex flex-col flex-grow">
            <small class="text-gray-500 text-xs mb-1"><?php echo htmlspecialchars($product['category_name']); ?></small>
            
            <h6 class="font-medium text-gray-900 mb-2 min-h-[2.5rem] line-clamp-2">
                <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" 
                   class="text-gray-900 hover:text-amber-600 transition-colors">
                    <?php echo htmlspecialchars($product['name']); ?>
                </a>
            </h6>
            
            <div class="mb-3 flex-grow">
                <?php if ($product['sale_price']): ?>
                    <div class="flex items-center gap-2">
                        <span class="text-red-600 font-bold text-lg"><?php echo formatPrice($product['sale_price']); ?></span>
                        <small class="text-gray-400 line-through text-sm"><?php echo formatPrice($product['price']); ?></small>
                    </div>
                <?php else: ?>
                    <span class="text-red-600 font-bold text-lg"><?php echo formatPrice($product['price']); ?></span>
                <?php endif; ?>
            </div>
            
            <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors add-to-cart" 
                    data-product-id="<?php echo $product['id']; ?>">
                <i class="fas fa-cart-plus mr-2"></i> Thêm vào giỏ
            </button>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
    <div class="w-20 h-20 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
        <i class="fas fa-search text-3xl text-blue-600"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Không tìm thấy sản phẩm</h3>
    <p class="text-gray-600 mb-6">
        Không tìm thấy sản phẩm nào phù hợp với từ khóa <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
    </p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="products.php" 
           class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-shopping-bag mr-2"></i> Xem Tất Cả Sản Phẩm
        </a>
        <a href="index.php" 
           class="inline-flex items-center px-6 py-3 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-colors">
            <i class="fas fa-home mr-2"></i> Về Trang Chủ
        </a>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

