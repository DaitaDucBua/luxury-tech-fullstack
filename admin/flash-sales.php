<?php
session_start();
require_once '../config/config.php';

// Kiểm tra admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Quản Lý Flash Sale';

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_sale') {
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $status = $_POST['status'];

        $sql = "INSERT INTO flash_sales (name, description, start_time, end_time, status)
                VALUES ('$name', '$description', '$start_time', '$end_time', '$status')";

        if ($conn->query($sql)) {
            $_SESSION['success'] = 'Đã thêm Flash Sale';
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
    } elseif ($action === 'edit_sale') {
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $status = $_POST['status'];

        $sql = "UPDATE flash_sales SET
                name = '$name', description = '$description',
                start_time = '$start_time', end_time = '$end_time', status = '$status'
                WHERE id = $id";

        if ($conn->query($sql)) {
            $_SESSION['success'] = 'Đã cập nhật Flash Sale';
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
    } elseif ($action === 'delete_sale') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM flash_sales WHERE id = $id");
        $_SESSION['success'] = 'Đã xóa Flash Sale';
    } elseif ($action === 'add_product') {
        $flash_sale_id = intval($_POST['flash_sale_id']);
        $product_id = intval($_POST['product_id']);
        $original_price = floatval($_POST['original_price']);
        $flash_price = floatval($_POST['flash_price']);
        $discount_percent = intval($_POST['discount_percent']);
        $quantity_limit = intval($_POST['quantity_limit']);
        $max_per_customer = intval($_POST['max_per_customer']);

        $sql = "INSERT INTO flash_sale_products (flash_sale_id, product_id, original_price, flash_price, discount_percent, quantity_limit, max_per_customer)
                VALUES ($flash_sale_id, $product_id, $original_price, $flash_price, $discount_percent, $quantity_limit, $max_per_customer)";

        if ($conn->query($sql)) {
            $_SESSION['success'] = 'Đã thêm sản phẩm vào Flash Sale';
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
    } elseif ($action === 'delete_product') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM flash_sale_products WHERE id = $id");
        $_SESSION['success'] = 'Đã xóa sản phẩm khỏi Flash Sale';
    } elseif ($action === 'turn_off_all') {
        // Tắt tất cả Flash Sale đang active
        $conn->query("UPDATE flash_sales SET status = 'ended' WHERE status = 'active'");
        $_SESSION['success'] = 'Đã tắt tất cả Flash Sale đang hoạt động';
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $new_status = $_POST['new_status'];
        $conn->query("UPDATE flash_sales SET status = '$new_status' WHERE id = $id");
        $_SESSION['success'] = 'Đã cập nhật trạng thái Flash Sale';
    }

    header('Location: flash-sales.php');
    exit;
}

// Lấy danh sách flash sales
$flash_sales = $conn->query("SELECT fs.*,
    (SELECT COUNT(*) FROM flash_sale_products WHERE flash_sale_id = fs.id) as product_count,
    (SELECT SUM(quantity_sold) FROM flash_sale_products WHERE flash_sale_id = fs.id) as total_sold
    FROM flash_sales fs ORDER BY fs.created_at DESC");

// Lấy danh sách sản phẩm để thêm vào flash sale
$products = $conn->query("SELECT id, name, price FROM products WHERE status = 'active' ORDER BY name");

include 'includes/header.php';
?>

<?php
// Đếm số Flash Sale đang active
$active_count = $conn->query("SELECT COUNT(*) as cnt FROM flash_sales WHERE status = 'active'")->fetch_assoc()['cnt'];
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-bolt mr-3 text-amber-500"></i> Quản Lý Flash Sale
    </h1>
    <div class="flex items-center gap-2 flex-wrap">
        <?php if ($active_count > 0): ?>
        <form method="POST" class="inline" onsubmit="return confirm('Tắt tất cả <?php echo $active_count; ?> Flash Sale đang hoạt động?')">
            <input type="hidden" name="action" value="turn_off_all">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors shadow-md hover:shadow-lg">
                <i class="fas fa-power-off mr-2"></i> Tắt tất cả (<?php echo $active_count; ?> đang bật)
            </button>
        </form>
        <?php else: ?>
        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed" disabled>
            <i class="fas fa-moon mr-2"></i> Không có Flash Sale nào đang bật
        </button>
        <?php endif; ?>
        <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg" 
                data-bs-toggle="modal" 
                data-bs-target="#addSaleModal">
            <i class="fas fa-plus mr-2"></i> Thêm Flash Sale
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl">
        <div class="flex items-start justify-between">
            <p class="text-green-800 font-semibold flex items-center">
                <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </p>
            <button type="button" class="ml-4 text-green-500 hover:text-green-700" data-bs-dismiss="alert">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl">
        <div class="flex items-start justify-between">
            <p class="text-red-800 font-semibold flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </p>
            <button type="button" class="ml-4 text-red-500 hover:text-red-700" data-bs-dismiss="alert">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Flash Sales List -->
<?php while ($sale = $flash_sales->fetch_assoc()): ?>
<div class="bg-white rounded-2xl border border-gray-200 shadow-md mb-6 overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3 flex-wrap">
            <i class="fas fa-bolt text-amber-500 text-xl"></i>
            <strong class="text-primary font-semibold text-lg"><?php echo htmlspecialchars($sale['name']); ?></strong>
            <?php
            $status_classes = [
                'upcoming' => 'bg-blue-100 text-blue-800',
                'active' => 'bg-green-100 text-green-800',
                'ended' => 'bg-gray-100 text-gray-800'
            ];
            $status_texts = [
                'upcoming' => 'Sắp diễn ra',
                'active' => 'Đang diễn ra',
                'ended' => 'Đã kết thúc'
            ];
            $status_class = $status_classes[$sale['status']] ?? 'bg-gray-100 text-gray-800';
            $status_text = $status_texts[$sale['status']] ?? $sale['status'];
            ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Toggle bật/tắt -->
            <?php if ($sale['status'] === 'active'): ?>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                <input type="hidden" name="new_status" value="ended">
                <button type="submit" 
                        class="inline-flex items-center px-3 py-1.5 bg-amber-500 text-white font-semibold rounded-lg hover:bg-amber-600 transition-colors shadow-sm hover:shadow-md text-sm" 
                        title="Tắt Flash Sale">
                    <i class="fas fa-pause mr-1.5"></i> Tắt
                </button>
            </form>
            <?php else: ?>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                <input type="hidden" name="new_status" value="active">
                <button type="submit" 
                        class="inline-flex items-center px-3 py-1.5 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors shadow-sm hover:shadow-md text-sm" 
                        title="Bật Flash Sale">
                    <i class="fas fa-play mr-1.5"></i> Bật
                </button>
            </form>
            <?php endif; ?>

            <button class="inline-flex items-center px-3 py-1.5 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition-colors shadow-sm hover:shadow-md text-sm" 
                    onclick="editSale(<?php echo htmlspecialchars(json_encode($sale)); ?>)">
                <i class="fas fa-edit mr-1.5"></i> Sửa
            </button>
            <button class="inline-flex items-center px-3 py-1.5 bg-primary text-secondary font-semibold rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md text-sm" 
                    onclick="addProduct(<?php echo $sale['id']; ?>, '<?php echo htmlspecialchars($sale['name']); ?>')">
                <i class="fas fa-plus mr-1.5"></i> Thêm SP
            </button>
            <form method="POST" class="inline" onsubmit="return confirm('Xác nhận xóa Flash Sale này?')">
                <input type="hidden" name="action" value="delete_sale">
                <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                <button type="submit" 
                        class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="p-3 bg-gray-50 rounded-lg">
                <small class="text-gray-500 font-semibold block mb-1">Thời gian:</small>
                <div class="flex items-center text-sm text-gray-700">
                    <i class="fas fa-clock text-primary mr-2"></i>
                    <span><?php echo date('d/m/Y H:i', strtotime($sale['start_time'])); ?> - <?php echo date('d/m/Y H:i', strtotime($sale['end_time'])); ?></span>
                </div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <small class="text-gray-500 font-semibold block mb-1">Số sản phẩm:</small>
                <div class="flex items-center text-sm text-gray-700">
                    <i class="fas fa-box text-green-500 mr-2"></i>
                    <span><?php echo $sale['product_count'] ?? 0; ?> sản phẩm</span>
                </div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <small class="text-gray-500 font-semibold block mb-1">Đã bán:</small>
                <div class="flex items-center text-sm text-gray-700">
                    <i class="fas fa-shopping-cart text-amber-500 mr-2"></i>
                    <span><?php echo $sale['total_sold'] ?? 0; ?> sản phẩm</span>
                </div>
            </div>
        </div>

        <?php
        // Lấy sản phẩm trong flash sale này
        $sale_products = $conn->query("SELECT fsp.*, p.name, p.image
            FROM flash_sale_products fsp
            JOIN products p ON fsp.product_id = p.id
            WHERE fsp.flash_sale_id = {$sale['id']}");
        ?>

        <?php if ($sale_products->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">Sản phẩm</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-primary uppercase tracking-wider">Giá gốc</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-primary uppercase tracking-wider">Giá Flash</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase tracking-wider">Giảm</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase tracking-wider">SL giới hạn</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase tracking-wider">Đã bán</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($prod = $sale_products->fetch_assoc()): ?>
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <img src="<?php echo $prod['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                                     class="w-10 h-10 rounded-lg object-cover">
                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars(mb_substr($prod['name'], 0, 40)); ?>...</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <del class="text-gray-400"><?php echo number_format($prod['original_price']); ?>đ</del>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-red-500 font-bold"><?php echo number_format($prod['flash_price']); ?>đ</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">-<?php echo $prod['discount_percent']; ?>%</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            <?php echo $prod['quantity_limit']; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center">
                                <div class="w-24 bg-gray-200 rounded-full h-5 overflow-hidden">
                                    <?php $percent = ($prod['quantity_sold'] / $prod['quantity_limit']) * 100; ?>
                                    <div class="bg-amber-500 h-full flex items-center justify-center text-xs font-semibold text-white" 
                                         style="width: <?php echo min($percent, 100); ?>%">
                                        <?php if ($percent > 10): ?><?php echo $prod['quantity_sold']; ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" class="inline" onsubmit="return confirm('Xóa sản phẩm này?')">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                <button type="submit" 
                                        class="inline-flex items-center justify-center w-8 h-8 border-2 border-red-500 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-colors">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>Chưa có sản phẩm nào</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>

<!-- Add Flash Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <form method="POST">
                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-primary flex items-center">
                        <i class="fas fa-bolt mr-2"></i>Thêm Flash Sale
                    </h5>
                    <button type="button" 
                            class="text-primary hover:text-primary-light transition-colors" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add_sale">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Tên Flash Sale <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               required 
                               placeholder="VD: Flash Sale 12h">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="2" 
                                  placeholder="Giảm giá sốc..."></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="start_time" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="end_time" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                            <option value="upcoming">Sắp diễn ra</option>
                            <option value="active">Đang diễn ra</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                            data-bs-dismiss="modal">
                        Đóng
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                        Thêm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Flash Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <form method="POST">
                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-primary flex items-center">
                        <i class="fas fa-edit mr-2"></i>Sửa Flash Sale
                    </h5>
                    <button type="button" 
                            class="text-primary hover:text-primary-light transition-colors" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="edit_sale">
                    <input type="hidden" name="id" id="edit_sale_id">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Tên Flash Sale <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="edit_sale_name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  id="edit_sale_desc" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="start_time" 
                                   id="edit_sale_start" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="end_time" 
                                   id="edit_sale_end" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" 
                                id="edit_sale_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                            <option value="upcoming">Sắp diễn ra</option>
                            <option value="active">Đang diễn ra</option>
                            <option value="ended">Đã kết thúc</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                            data-bs-dismiss="modal">
                        Đóng
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <form method="POST">
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-plus mr-2"></i>Thêm sản phẩm vào <span id="add_prod_sale_name" class="ml-1"></span>
                    </h5>
                    <button type="button" 
                            class="text-white hover:text-gray-200 transition-colors" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add_product">
                    <input type="hidden" name="flash_sale_id" id="add_prod_sale_id">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Chọn sản phẩm <span class="text-red-500">*</span>
                        </label>
                        <select name="product_id" 
                                id="add_prod_select" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                required 
                                onchange="updatePrices()">
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php
                            $products->data_seek(0);
                            while ($p = $products->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> - <?php echo number_format($p['price']); ?>đ
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giá gốc</label>
                            <input type="number" 
                                   name="original_price" 
                                   id="add_prod_original" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Giá Flash <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="flash_price" 
                                   id="add_prod_flash" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required 
                                   onchange="calcDiscount()">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">% Giảm</label>
                            <input type="number" 
                                   name="discount_percent" 
                                   id="add_prod_percent" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                SL giới hạn <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="quantity_limit" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   value="50" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Max/khách</label>
                            <input type="number" 
                                   name="max_per_customer" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   value="2">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                            data-bs-dismiss="modal">
                        Đóng
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors shadow-md hover:shadow-lg">
                        Thêm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSale(sale) {
    document.getElementById('edit_sale_id').value = sale.id;
    document.getElementById('edit_sale_name').value = sale.name;
    document.getElementById('edit_sale_desc').value = sale.description || '';
    document.getElementById('edit_sale_status').value = sale.status;
    document.getElementById('edit_sale_start').value = sale.start_time.replace(' ', 'T').substring(0, 16);
    document.getElementById('edit_sale_end').value = sale.end_time.replace(' ', 'T').substring(0, 16);
    new bootstrap.Modal(document.getElementById('editSaleModal')).show();
}

function addProduct(saleId, saleName) {
    document.getElementById('add_prod_sale_id').value = saleId;
    document.getElementById('add_prod_sale_name').textContent = saleName;
    new bootstrap.Modal(document.getElementById('addProductModal')).show();
}

function updatePrices() {
    const select = document.getElementById('add_prod_select');
    const price = select.options[select.selectedIndex].dataset.price || 0;
    document.getElementById('add_prod_original').value = price;
    document.getElementById('add_prod_flash').value = Math.round(price * 0.8);
    calcDiscount();
}

function calcDiscount() {
    const original = parseFloat(document.getElementById('add_prod_original').value) || 0;
    const flash = parseFloat(document.getElementById('add_prod_flash').value) || 0;
    const percent = original > 0 ? Math.round((1 - flash / original) * 100) : 0;
    document.getElementById('add_prod_percent').value = percent;
}
</script>

<?php include 'includes/footer.php'; ?>
