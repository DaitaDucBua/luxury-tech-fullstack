<?php
require_once 'config/config.php';

$page_title = 'Sản phẩm';

// Lấy tham số lọc
$category_slug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$featured = isset($_GET['featured']) ? 1 : 0;
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Build query
$where = [];
$params = [];
$types = '';

if ($category_slug) {
    $where[] = "c.slug = ?";
    $params[] = $category_slug;
    $types .= 's';
    
    // Lấy tên danh mục
    $cat_sql = "SELECT name FROM categories WHERE slug = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->bind_param("s", $category_slug);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category_info = $cat_result->fetch_assoc();
    $page_title = $category_info['name'] ?? 'Sản phẩm';
}

if ($featured) {
    $where[] = "p.is_featured = 1";
    $page_title = 'Sản phẩm nổi bật';
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
    $page_title = 'Tìm kiếm: ' . $search;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Sắp xếp
$order_by = 'p.created_at DESC';
switch ($sort) {
    case 'price_asc':
        $order_by = 'COALESCE(p.sale_price, p.price) ASC';
        break;
    case 'price_desc':
        $order_by = 'COALESCE(p.sale_price, p.price) DESC';
        break;
    case 'name':
        $order_by = 'p.name ASC';
        break;
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              {$where_clause}";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($types)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_sql);
}

$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Lấy sản phẩm
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        {$where_clause}
        ORDER BY {$order_by}
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-6">
    <ol class="flex items-center gap-2 text-sm">
        <li><a href="index.php" class="text-primary hover:text-primary-dark transition-colors">Trang chủ</a></li>
        <li><span class="text-gray-400">/</span></li>
        <li class="text-gray-600"><?php echo htmlspecialchars($page_title); ?></li>
    </ol>
</nav>

<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h4 class="text-2xl font-semibold text-gray-900 mb-1">
            <?php echo htmlspecialchars($page_title); ?>
        </h4>
        <p class="text-sm text-gray-600">
            <span class="font-medium"><?php echo $total_products; ?></span> sản phẩm
        </p>
    </div>

    <!-- Sort -->
    <div class="sort-options">
        <select class="px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white text-gray-900 font-medium min-w-[180px]" id="sortSelect" onchange="window.location.href=this.value">
            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Tên A-Z</option>
        </select>
    </div>
</div>

<!-- Products Grid -->
<?php if ($products->num_rows > 0): ?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <?php while ($product = $products->fetch_assoc()): ?>
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-2 flex flex-col">
        <div class="relative">
            <?php if ($product['sale_price']): ?>
            <span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded z-10">
                -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
            </span>
            <?php endif; ?>

            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="block bg-gray-50 p-5">
                <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/300x300?text=Product'; ?>"
                     class="w-full h-44 object-contain" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </a>
        </div>

        <div class="p-4 flex-1 flex flex-col">
            <small class="text-xs text-gray-500 mb-1"><?php echo htmlspecialchars($product['category_name']); ?></small>
            <h6 class="font-medium text-gray-900 mb-2 line-clamp-2 min-h-[3rem]">
                <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="hover:text-primary transition-colors">
                    <?php echo htmlspecialchars($product['name']); ?>
                </a>
            </h6>

            <div class="mb-3 mt-auto">
                <?php if ($product['sale_price']): ?>
                    <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['sale_price']); ?></span>
                    <div class="text-gray-500 text-sm line-through"><?php echo formatPrice($product['price']); ?></div>
                <?php else: ?>
                    <span class="text-primary font-bold text-lg"><?php echo formatPrice($product['price']); ?></span>
                <?php endif; ?>
            </div>

            <div class="space-y-2">
                <button class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium flex items-center justify-center gap-2 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-cart-plus"></i>
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
    <?php endwhile; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="mb-8">
    <ul class="flex justify-center items-center gap-2 flex-wrap">
        <?php if ($page > 1): ?>
        <li>
            <a class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-primary hover:text-primary transition-colors font-medium" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                <i class="fas fa-chevron-left mr-1"></i>Trước
            </a>
        </li>
        <?php endif; ?>

        <?php
        // Hiển thị tối đa 7 số trang
        $start = max(1, $page - 3);
        $end = min($total_pages, $page + 3);
        
        if ($start > 1): ?>
        <li>
            <a class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-primary hover:text-primary transition-colors" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
        </li>
        <?php if ($start > 2): ?>
        <li><span class="px-2 text-gray-400">...</span></li>
        <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li>
            <a class="px-4 py-2 border rounded-lg transition-colors <?php echo $i == $page ? 'bg-primary text-white border-primary font-semibold' : 'border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-primary hover:text-primary'; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?>
        <li><span class="px-2 text-gray-400">...</span></li>
        <?php endif; ?>
        <li>
            <a class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-primary hover:text-primary transition-colors" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
        </li>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
        <li>
            <a class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-primary hover:text-primary transition-colors font-medium" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                Sau<i class="fas fa-chevron-right ml-1"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-16">
    <div class="w-24 h-24 mx-auto mb-6 bg-blue-50 rounded-full flex items-center justify-center">
        <i class="fas fa-search text-4xl text-primary"></i>
    </div>
    <h5 class="text-xl font-semibold text-gray-900 mb-2">Không tìm thấy sản phẩm nào</h5>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">Vui lòng thử lại với từ khóa khác hoặc xem các danh mục sản phẩm</p>
    <a href="index.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-dark transition-colors font-medium">
        <i class="fas fa-home"></i>
        <span>Về trang chủ</span>
    </a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

