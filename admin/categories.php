<?php
require_once 'includes/auth.php';

$page_title = 'Quản lý danh mục';

// Xử lý thêm danh mục
if (isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $description = sanitize($_POST['description']);
    $image = sanitize($_POST['image']);
    
    $sql = "INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $slug, $description, $image);
    
    if ($stmt->execute()) {
        showMessage('Thêm danh mục thành công', 'success');
    } else {
        showMessage('Lỗi khi thêm danh mục', 'error');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Xử lý xóa danh mục
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Kiểm tra xem có sản phẩm nào thuộc danh mục này không
    $check_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $count = $check_stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        showMessage("Không thể xóa danh mục này vì có {$count} sản phẩm đang sử dụng", 'error');
    } else {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            showMessage('Xóa danh mục thành công', 'success');
        } else {
            showMessage('Lỗi khi xóa danh mục', 'error');
        }
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Xử lý cập nhật danh mục
if (isset($_POST['update_category'])) {
    $id = intval($_POST['id']);
    $name = sanitize($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $description = sanitize($_POST['description']);
    $image = sanitize($_POST['image']);
    
    $sql = "UPDATE categories SET name=?, slug=?, description=?, image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $slug, $description, $image, $id);
    
    if ($stmt->execute()) {
        showMessage('Cập nhật danh mục thành công', 'success');
    } else {
        showMessage('Lỗi khi cập nhật danh mục', 'error');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Lấy danh sách danh mục
$sql = "SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name";
$categories = $conn->query($sql);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-tags mr-3 text-primary"></i> Quản lý danh mục
    </h1>
    <div class="flex items-center">
        <button type="button" 
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg" 
                data-bs-toggle="modal" 
                data-bs-target="#addCategoryModal">
            <i class="fas fa-plus mr-2"></i> Thêm danh mục
        </button>
    </div>
</div>

<!-- Categories Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách danh mục
        </h2>
        <span class="px-4 py-1 bg-primary/20 text-primary font-semibold rounded-full text-sm">
            <?php echo $categories->num_rows; ?> danh mục
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Hình ảnh</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Tên danh mục</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden md:table-cell">Slug</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Mô tả</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Số sản phẩm</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($category = $categories->fetch_assoc()): ?>
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img src="../<?php echo $category['image'] ?: 'https://via.placeholder.com/60'; ?>"
                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            <strong class="text-gray-800 font-semibold"><?php echo htmlspecialchars($category['name']); ?></strong>
                            <code class="text-xs text-gray-500 md:hidden px-2 py-1 bg-primary/10 text-primary rounded"><?php echo htmlspecialchars($category['slug']); ?></code>
                            <span class="text-xs text-gray-500 lg:hidden line-clamp-2"><?php echo htmlspecialchars($category['description']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm hidden md:table-cell">
                        <code class="px-3 py-1 bg-primary/10 text-primary rounded-lg text-xs font-mono"><?php echo htmlspecialchars($category['slug']); ?></code>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 hidden lg:table-cell">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <?php echo $category['product_count']; ?> sp
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-9 h-9 bg-primary text-secondary rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editCategoryModal<?php echo $category['id']; ?>" 
                                    title="Sửa">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-9 h-9 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md delete-category" 
                                    data-id="<?php echo $category['id']; ?>" 
                                    title="Xóa">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
                                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                                    <h5 class="text-lg font-semibold text-primary flex items-center">
                                        <i class="fas fa-edit mr-2"></i>Sửa danh mục
                                    </h5>
                                    <button type="button" 
                                            class="text-primary hover:text-primary-light transition-colors" 
                                            data-bs-dismiss="modal">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <form method="POST">
                                    <div class="p-6 space-y-4">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Tên danh mục <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" 
                                                   name="name" 
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                                   value="<?php echo htmlspecialchars($category['name']); ?>" 
                                                   required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                                            <textarea name="description" 
                                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                                      rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL hình ảnh</label>
                                            <input type="text" 
                                                   name="image" 
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                                   value="<?php echo htmlspecialchars($category['image']); ?>">
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                        <button type="button" 
                                                class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                                                data-bs-dismiss="modal">
                                            Đóng
                                        </button>
                                        <button type="submit" 
                                                name="update_category" 
                                                class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                                            <i class="fas fa-save mr-2"></i> Cập nhật
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php if ($categories->num_rows == 0): ?>
        <div class="p-12 text-center">
            <i class="fas fa-tags text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Chưa có danh mục nào</p>
            <button type="button" 
                    class="mt-4 inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg" 
                    data-bs-toggle="modal" 
                    data-bs-target="#addCategoryModal">
                <i class="fas fa-plus mr-2"></i> Thêm danh mục đầu tiên
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                <h5 class="text-lg font-semibold text-primary flex items-center">
                    <i class="fas fa-plus mr-2"></i>Thêm danh mục mới
                </h5>
                <button type="button" 
                        class="text-primary hover:text-primary-light transition-colors" 
                        data-bs-dismiss="modal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Tên danh mục <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="3"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">URL hình ảnh</label>
                        <input type="text" 
                               name="image" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               placeholder="images/categories/category.jpg">
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                            data-bs-dismiss="modal">
                        Đóng
                    </button>
                    <button type="submit" 
                            name="add_category" 
                            class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i> Thêm danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

