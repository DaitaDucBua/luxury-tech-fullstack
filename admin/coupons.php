<?php
session_start();
require_once '../config/config.php';

// Kiểm tra admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Quản Lý Mã Giảm Giá';

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code']));
        $description = $conn->real_escape_string($_POST['description']);
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_order_value = floatval($_POST['min_order_value']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : 'NULL';
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : 'NULL';
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];
        
        $sql = "INSERT INTO coupons (code, description, type, value, min_order_value, max_discount, usage_limit, start_date, end_date, status)
                VALUES ('$code', '$description', '$type', $value, $min_order_value, $max_discount, $usage_limit, '$start_date', '$end_date', '$status')";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = 'Đã thêm mã giảm giá';
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $description = $conn->real_escape_string($_POST['description']);
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_order_value = floatval($_POST['min_order_value']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : 'NULL';
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : 'NULL';
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];
        
        $sql = "UPDATE coupons SET 
                description = '$description',
                type = '$type',
                value = $value,
                min_order_value = $min_order_value,
                max_discount = $max_discount,
                usage_limit = $usage_limit,
                start_date = '$start_date',
                end_date = '$end_date',
                status = '$status'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = 'Đã cập nhật mã giảm giá';
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM coupons WHERE id = $id");
        $_SESSION['success'] = 'Đã xóa mã giảm giá';
    }
    
    header('Location: coupons.php');
    exit;
}

// Lấy danh sách coupons
$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-ticket-alt mr-3 text-primary"></i> Quản Lý Mã Giảm Giá
    </h1>
    <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg" 
            data-bs-toggle="modal" 
            data-bs-target="#addCouponModal">
        <i class="fas fa-plus mr-2"></i> Thêm Mã Giảm Giá
    </button>
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

<!-- Coupons Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách mã giảm giá
        </h2>
        <span class="px-4 py-1 bg-primary/20 text-primary font-semibold rounded-full text-sm">
            <?php echo $coupons->num_rows; ?> mã
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Mã</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Mô tả</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Loại</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-primary uppercase tracking-wider">Giá trị</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Đơn tối thiểu</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider hidden xl:table-cell">Giới hạn</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Đã dùng</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden xl:table-cell">Thời hạn</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($coupon = $coupons->fetch_assoc()): ?>
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <strong class="text-primary font-semibold"><?php echo $coupon['code']; ?></strong>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <?php echo htmlspecialchars($coupon['description']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <?php if ($coupon['type'] === 'percent'): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Phần trăm</span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary">Số tiền</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span class="text-primary font-semibold">
                            <?php if ($coupon['type'] === 'percent'): ?>
                                <?php echo $coupon['value']; ?>%
                            <?php else: ?>
                                <?php echo number_format($coupon['value']); ?>đ
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right hidden lg:table-cell">
                        <?php echo number_format($coupon['min_order_value']); ?>đ
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center hidden xl:table-cell">
                        <?php echo $coupon['usage_limit'] ?: 'Không giới hạn'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <?php echo $coupon['used_count']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 hidden xl:table-cell">
                        <div class="flex flex-col">
                            <span><?php echo date('d/m/Y', strtotime($coupon['start_date'])); ?></span>
                            <span class="text-primary">→</span>
                            <span><?php echo date('d/m/Y', strtotime($coupon['end_date'])); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <?php if ($coupon['status'] === 'active'): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Hoạt động</span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Tạm dừng</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="inline-flex items-center justify-center w-9 h-9 bg-primary text-secondary rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md" 
                                    onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)" 
                                    title="Sửa">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Xác nhận xóa?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
                                <button type="submit" 
                                        class="inline-flex items-center justify-center w-9 h-9 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md" 
                                        title="Xóa">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php if ($coupons->num_rows == 0): ?>
    <div class="p-12 text-center">
        <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Chưa có mã giảm giá nào</p>
    </div>
    <?php endif; ?>
</div>

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <form method="POST">
                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-primary flex items-center">
                        <i class="fas fa-plus mr-2"></i>Thêm Mã Giảm Giá
                    </h5>
                    <button type="button" 
                            class="text-primary hover:text-primary-light transition-colors" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Mã giảm giá <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="code" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Loại <span class="text-red-500">*</span>
                            </label>
                            <select name="type" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                    required>
                                <option value="percent">Phần trăm (%)</option>
                                <option value="fixed">Số tiền cố định (đ)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="2"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Giá trị <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="value" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   step="0.01" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Đơn tối thiểu</label>
                            <input type="number" 
                                   name="min_order_value" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   value="0">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giảm tối đa</label>
                            <input type="number" 
                                   name="max_discount" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   placeholder="Không giới hạn">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giới hạn sử dụng</label>
                            <input type="number" 
                                   name="usage_limit" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   placeholder="Không giới hạn">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ngày bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="start_date" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ngày kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="end_date" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm dừng</option>
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

<!-- Edit Coupon Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <form method="POST" id="editCouponForm">
                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-primary flex items-center">
                        <i class="fas fa-edit mr-2"></i>Sửa Mã Giảm Giá
                    </h5>
                    <button type="button" 
                            class="text-primary hover:text-primary-light transition-colors" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mã giảm giá</label>
                            <input type="text" 
                                   id="edit_code" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed" 
                                   disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Loại <span class="text-red-500">*</span>
                            </label>
                            <select name="type" 
                                    id="edit_type" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                    required>
                                <option value="percent">Phần trăm (%)</option>
                                <option value="fixed">Số tiền cố định (đ)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  id="edit_description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="2"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Giá trị <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="value" 
                                   id="edit_value" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   step="0.01" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Đơn tối thiểu</label>
                            <input type="number" 
                                   name="min_order_value" 
                                   id="edit_min_order_value" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   value="0">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giảm tối đa</label>
                            <input type="number" 
                                   name="max_discount" 
                                   id="edit_max_discount" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   placeholder="Không giới hạn">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giới hạn sử dụng</label>
                            <input type="number" 
                                   name="usage_limit" 
                                   id="edit_usage_limit" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   placeholder="Không giới hạn">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ngày bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="start_date" 
                                   id="edit_start_date" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ngày kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="end_date" 
                                   id="edit_end_date" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                   required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" 
                                id="edit_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm dừng</option>
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
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCoupon(coupon) {
    document.getElementById('edit_id').value = coupon.id;
    document.getElementById('edit_code').value = coupon.code;
    document.getElementById('edit_type').value = coupon.type;
    document.getElementById('edit_description').value = coupon.description || '';
    document.getElementById('edit_value').value = coupon.value;
    document.getElementById('edit_min_order_value').value = coupon.min_order_value || 0;
    document.getElementById('edit_max_discount').value = coupon.max_discount || '';
    document.getElementById('edit_usage_limit').value = coupon.usage_limit || '';
    document.getElementById('edit_status').value = coupon.status;

    // Format datetime for input
    if (coupon.start_date) {
        document.getElementById('edit_start_date').value = coupon.start_date.replace(' ', 'T').substring(0, 16);
    }
    if (coupon.end_date) {
        document.getElementById('edit_end_date').value = coupon.end_date.replace(' ', 'T').substring(0, 16);
    }

    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('editCouponModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>

