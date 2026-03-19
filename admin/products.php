<?php
require_once 'includes/auth.php';

$page_title = 'Quản lý sản phẩm';

// Hiển thị thông báo từ session (sau khi redirect từ trang edit)
if (isset($_SESSION['message'])) {
    showMessage($_SESSION['message'], $_SESSION['message_type'] ?? 'info');
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        showMessage('Xóa sản phẩm thành công', 'success');
    } else {
        showMessage('Lỗi khi xóa sản phẩm', 'error');
    }
    redirect(SITE_URL . '/admin/products.php');
}

// Lọc sản phẩm
$where = "1=1";
$params = [];
$types = "";

if (isset($_GET['category']) && $_GET['category'] != '') {
    $where .= " AND category_id = ?";
    $params[] = intval($_GET['category']);
    $types .= "i";
}

if (isset($_GET['stock']) && $_GET['stock'] == 'low') {
    $where .= " AND stock < 10 AND stock > 0";
}

// Lấy danh sách sản phẩm
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where
        ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query($sql);
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-box mr-3 text-primary"></i> Quản lý sản phẩm
    </h1>
    <div class="flex items-center">
        <a href="product-add.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md mb-6 overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
            <i class="fas fa-filter mr-2"></i> Bộ lọc
        </h2>
    </div>
    <div class="p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Danh mục</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                    <option value="">Tất cả danh mục</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tồn kho</label>
                <select name="stock" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                    <option value="">Tất cả</option>
                    <option value="low" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'low') ? 'selected' : ''; ?>>Sắp hết hàng</option>
                </select>
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-search mr-2"></i> Lọc
                </button>
                <a href="products.php" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách sản phẩm
        </h2>
        <span class="px-4 py-1 bg-primary/20 text-primary font-semibold rounded-full text-sm">
            <?php echo $products->num_rows; ?> sản phẩm
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Hình ảnh</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden md:table-cell">Danh mục</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Giá</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Giá KM</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Tồn kho</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden sm:table-cell">Trạng thái</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($product = $products->fetch_assoc()): ?>
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img src="../<?php echo $product['image'] ?: 'https://via.placeholder.com/60'; ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            <strong class="text-gray-800 font-semibold"><?php echo htmlspecialchars($product['name']); ?></strong>
                            <?php if ($product['is_featured']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary w-fit">
                                Nổi bật
                            </span>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500 md:hidden"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">
                        <?php echo formatPrice($product['price']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm hidden lg:table-cell">
                        <?php if ($product['sale_price']): ?>
                        <span class="font-semibold text-primary"><?php echo formatPrice($product['sale_price']); ?></span>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($product['stock'] == 0): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Hết hàng</span>
                        <?php elseif ($product['stock'] < 10): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"><?php echo $product['stock']; ?> sp</span>
                        <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800"><?php echo $product['stock']; ?> sp</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                        <?php if ($product['status'] == 'active'): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Hoạt động</span>
                        <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                               class="inline-flex items-center justify-center w-9 h-9 bg-primary text-secondary rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md" 
                               title="Sửa">
                                <i class="fas fa-edit text-sm"></i>
                            </a>
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-9 h-9 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md" 
                                    data-ajax-action="delete_product" 
                                    data-id="<?php echo $product['id']; ?>" 
                                    data-confirm="Bạn có chắc muốn xóa sản phẩm này?" 
                                    title="Xóa">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php if ($products->num_rows == 0): ?>
    <div class="p-12 text-center">
        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Không tìm thấy sản phẩm nào</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

