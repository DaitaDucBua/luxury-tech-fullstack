<?php
require_once 'includes/auth.php';

$page_title = 'Quản lý người dùng';

// Xử lý xóa người dùng
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Không cho phép xóa chính mình
    if ($id == $_SESSION['user_id']) {
        showMessage('Không thể xóa tài khoản của chính bạn', 'error');
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            showMessage('Xóa người dùng thành công', 'success');
        } else {
            showMessage('Lỗi khi xóa người dùng', 'error');
        }
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Xử lý cập nhật role
if (isset($_POST['update_role'])) {
    $id = intval($_POST['user_id']);
    $role = sanitize($_POST['role']);
    
    // Không cho phép thay đổi role của chính mình
    if ($id == $_SESSION['user_id']) {
        showMessage('Không thể thay đổi quyền của chính bạn', 'error');
    } else {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $role, $id);
        if ($stmt->execute()) {
            showMessage('Cập nhật quyền thành công', 'success');
        } else {
            showMessage('Lỗi khi cập nhật quyền', 'error');
        }
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Lọc người dùng
$where = "1=1";
if (isset($_GET['role']) && $_GET['role'] != '') {
    $role_filter = sanitize($_GET['role']);
    $where .= " AND role = '$role_filter'";
}

// Lấy danh sách người dùng
$sql = "SELECT u.*, 
        COUNT(DISTINCT o.id) as order_count,
        SUM(o.total_amount) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE $where
        GROUP BY u.id
        ORDER BY u.created_at DESC";
$users = $conn->query($sql);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-users mr-3 text-primary"></i> Quản lý người dùng
    </h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md mb-6 overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
            <i class="fas fa-filter mr-2"></i> Lọc người dùng
        </h2>
    </div>
    <div class="p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-10">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Quyền</label>
                <select name="role" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                        onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="customer" <?php echo (isset($_GET['role']) && $_GET['role'] == 'customer') ? 'selected' : ''; ?>>Khách hàng</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <a href="users.php" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách người dùng
        </h2>
        <span class="px-4 py-1 bg-primary/20 text-primary font-semibold rounded-full text-sm">
            <?php echo $users->num_rows; ?> người dùng
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Username</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden md:table-cell">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Họ tên</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Số đơn hàng</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-primary uppercase tracking-wider hidden xl:table-cell">Tổng chi tiêu</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Quyền</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden xl:table-cell">Ngày đăng ký</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($user = $users->fetch_assoc()): ?>
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $user['id']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1">
                            <strong class="text-gray-800 font-semibold"><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary w-fit">
                                Bạn
                            </span>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500 md:hidden"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden lg:table-cell">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <?php echo $user['order_count']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary text-right hidden xl:table-cell">
                        <?php echo formatPrice($user['total_spent'] ?? 0); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <?php if ($user['role'] == 'admin'): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary">
                            Admin
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Khách hàng
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden xl:table-cell">
                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-9 h-9 bg-primary text-secondary rounded-lg hover:bg-primary-dark transition-colors shadow-sm hover:shadow-md" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#userModal<?php echo $user['id']; ?>" 
                                    title="Chi tiết">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-9 h-9 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md delete-user" 
                                    data-id="<?php echo $user['id']; ?>" 
                                    title="Xóa">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                    <!-- User Detail Modal -->
                    <div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
                                <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                                    <h5 class="text-lg font-semibold text-primary flex items-center">
                                        <i class="fas fa-user mr-2"></i>Chi tiết người dùng
                                    </h5>
                                    <button type="button" 
                                            class="text-primary hover:text-primary-light transition-colors" 
                                            data-bs-dismiss="modal">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="p-4 bg-gray-50 rounded-xl">
                                        <h6 class="text-primary font-semibold mb-3 flex items-center">
                                            <i class="fas fa-user-circle mr-2"></i>Thông tin cá nhân
                                        </h6>
                                        <div class="space-y-2 text-sm">
                                            <p><strong class="text-gray-700">Username:</strong> <span class="text-primary font-semibold"><?php echo htmlspecialchars($user['username']); ?></span></p>
                                            <p><strong class="text-gray-700">Email:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></span></p>
                                            <p><strong class="text-gray-700">Họ tên:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($user['full_name']); ?></span></p>
                                            <p><strong class="text-gray-700">Số điện thoại:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($user['phone']); ?></span></p>
                                            <p><strong class="text-gray-700">Địa chỉ:</strong> <span class="text-gray-600"><?php echo htmlspecialchars($user['address']); ?></span></p>
                                        </div>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl">
                                        <h6 class="text-primary font-semibold mb-3 flex items-center">
                                            <i class="fas fa-chart-line mr-2"></i>Thống kê
                                        </h6>
                                        <div class="space-y-2 text-sm">
                                            <p><strong class="text-gray-700">Số đơn hàng:</strong> <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?php echo $user['order_count']; ?></span></p>
                                            <p><strong class="text-gray-700">Tổng chi tiêu:</strong> <span class="text-primary font-semibold"><?php echo formatPrice($user['total_spent'] ?? 0); ?></span></p>
                                            <p><strong class="text-gray-700">Ngày đăng ký:</strong> <span class="text-gray-600"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span></p>
                                        </div>
                                    </div>

                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <div class="p-4 bg-gray-50 rounded-xl">
                                        <h6 class="text-primary font-semibold mb-3 flex items-center">
                                            <i class="fas fa-user-shield mr-2"></i>Cập nhật quyền
                                        </h6>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors update-role" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-original="<?php echo $user['role']; ?>">
                                            <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php if ($users->num_rows == 0): ?>
        <div class="p-12 text-center">
            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Chưa có người dùng nào</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

