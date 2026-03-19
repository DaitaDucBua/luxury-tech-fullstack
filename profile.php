<?php
session_start();
require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Thông Tin Cá Nhân';
$user_id = (int)$_SESSION['user_id'];

// Lấy thông tin user - sử dụng prepared statements
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Form được xử lý bằng AJAX

// Lấy lịch sử đơn hàng - sử dụng prepared statements
$orders_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-4">
                <div class="p-6 text-center">
                    <div class="profile-avatar mb-4">
                        <img src="<?php echo $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?: $user['username']) . '&size=150&background=2f80ed&color=fff'; ?>" 
                             alt="Avatar" 
                             class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-100">
                        <button class="mt-3 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors"
                                data-bs-toggle="modal" 
                                data-bs-target="#avatarModal">
                            <i class="fas fa-camera mr-2"></i> Đổi ảnh
                        </button>
                    </div>
                    <h5 class="text-lg font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h5>
                    <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <nav class="p-2">
                    <a href="#profile" 
                       class="flex items-center px-4 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors mb-1 active-tab"
                       data-tab="profile">
                        <i class="fas fa-user w-5 mr-3"></i> 
                        <span>Thông tin cá nhân</span>
                    </a>
                    <a href="#orders" 
                       class="flex items-center px-4 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors mb-1"
                       data-tab="orders">
                        <i class="fas fa-shopping-bag w-5 mr-3"></i> 
                        <span>Đơn hàng của tôi</span>
                    </a>
                    <a href="#password" 
                       class="flex items-center px-4 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors mb-1"
                       data-tab="password">
                        <i class="fas fa-lock w-5 mr-3"></i> 
                        <span>Đổi mật khẩu</span>
                    </a>
                    <a href="#addresses" 
                       class="flex items-center px-4 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors mb-1"
                       data-tab="addresses">
                        <i class="fas fa-map-marker-alt w-5 mr-3"></i> 
                        <span>Địa chỉ</span>
                    </a>
                    <a href="wishlist.php" 
                       class="flex items-center px-4 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors mb-1">
                        <i class="fas fa-heart w-5 mr-3"></i> 
                        <span>Yêu thích</span>
                    </a>
                    <a href="logout.php" 
                       class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt w-5 mr-3"></i> 
                        <span>Đăng xuất</span>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="lg:col-span-9">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center justify-between">
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center justify-between">
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-user mr-2 text-amber-600"></i>Thông Tin Cá Nhân
                            </h5>
                        </div>
                        <div class="p-6">
                            <form data-ajax="true" data-action="update_profile" data-ajax-url="ajax/profile.php">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên đăng nhập</label>
                                        <input type="text" 
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                                               value="<?php echo htmlspecialchars($user['username']); ?>" 
                                               disabled>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Họ và tên <span class="text-amber-600">*</span>
                                        </label>
                                        <input type="text" 
                                               name="full_name" 
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Email <span class="text-amber-600">*</span>
                                        </label>
                                        <input type="email" 
                                               name="email" 
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                                        <input type="tel" 
                                               name="phone" 
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                                    <textarea name="address" 
                                              class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"
                                              rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <button type="submit" 
                                        class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                                    <i class="fas fa-save mr-2"></i>Lưu Thay Đổi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-shopping-bag mr-2 text-amber-600"></i>Đơn Hàng Của Tôi
                            </h5>
                        </div>
                        <div class="p-6">
                            <?php if ($orders->num_rows === 0): ?>
                                <div class="text-center py-12">
                                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-shopping-bag text-3xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 mb-4">Bạn chưa có đơn hàng nào</p>
                                    <a href="products.php" 
                                       class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                                        Mua Sắm Ngay
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Mã đơn</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Ngày đặt</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tổng tiền</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Trạng thái</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php while ($order = $orders->fetch_assoc()): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    #<?php echo $order['id']; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    <?php echo number_format($order['total_amount']); ?>đ
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                    $badge_classes = match($order['status']) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'processing' => 'bg-blue-100 text-blue-800',
                                                        'shipped' => 'bg-indigo-100 text-indigo-800',
                                                        'delivered' => 'bg-green-100 text-green-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge_classes; ?>">
                                                        <?php echo $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                                       class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                        <i class="fas fa-eye mr-1"></i> Chi tiết
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-lock mr-2 text-amber-600"></i>Đổi Mật Khẩu
                            </h5>
                        </div>
                        <div class="p-6">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Mật khẩu hiện tại <span class="text-amber-600">*</span>
                                    </label>
                                    <input type="password" 
                                           name="current_password" 
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           required>
                                </div>
                                
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Mật khẩu mới <span class="text-amber-600">*</span>
                                    </label>
                                    <input type="password" 
                                           name="new_password" 
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           required>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Xác nhận mật khẩu mới <span class="text-amber-600">*</span>
                                    </label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           required>
                                </div>
                                
                                <button type="submit" 
                                        class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                                    <i class="fas fa-key mr-2"></i>Đổi Mật Khẩu
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-amber-600"></i>Địa Chỉ
                            </h5>
                            <button class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>Thêm Địa Chỉ
                            </button>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-500">Chức năng quản lý nhiều địa chỉ đang được phát triển...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white rounded-lg shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h5 class="text-lg font-semibold text-gray-800">Đổi Ảnh Đại Diện</h5>
                <button type="button" class="text-gray-400 hover:text-gray-600" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-500">Chức năng upload avatar đang được phát triển...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Simple tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('[data-tab]');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all links
            tabLinks.forEach(l => {
                l.classList.remove('bg-amber-50', 'text-amber-600');
                l.classList.add('text-gray-700');
            });
            
            // Add active class to clicked link
            this.classList.add('bg-amber-50', 'text-amber-600');
            this.classList.remove('text-gray-700');
            
            // Hide all tab panes
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Show target tab pane
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
    
    // Set initial active tab
    const activeLink = document.querySelector('.active-tab');
    if (activeLink) {
        activeLink.classList.add('bg-amber-50', 'text-amber-600');
        activeLink.classList.remove('text-gray-700');
    }
});
</script>

<?php include 'includes/footer.php'; ?>

