<?php
require_once 'config/config.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Đăng ký';

include 'includes/header.php';
?>

<div class="flex justify-center py-8 px-4">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Card Header -->
            <div class="text-center py-8 px-6 border-b border-gray-200">
                <div class="mb-4 w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-plus text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-2">Đăng ký tài khoản</h3>
                <p class="text-gray-500 text-sm">Tạo tài khoản để trải nghiệm mua sắm tốt hơn</p>
            </div>

            <div class="p-6 md:p-8">
                <div id="registerAlert" class="hidden mb-4 p-4 rounded-lg" role="alert"></div>

                <form data-ajax="true" data-action="register" id="registerForm" data-ajax-url="ajax/auth.php">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tên đăng nhập <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="username" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       placeholder="Nhập tên đăng nhập"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" 
                                       name="email" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       placeholder="Nhập email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Họ và tên <span class="text-amber-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="full_name" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                   required 
                                   placeholder="Nhập họ và tên"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Số điện thoại
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input type="tel" 
                                   name="phone" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                   placeholder="Nhập số điện thoại"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Mật khẩu <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" 
                                       name="password" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       minlength="6" 
                                       placeholder="Tối thiểu 6 ký tự">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Xác nhận mật khẩu <span class="text-amber-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" 
                                       name="confirm_password" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       required 
                                       minlength="6" 
                                       placeholder="Nhập lại mật khẩu">
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" 
                                   class="mt-1 w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                                   id="agreeTerms" 
                                   required>
                            <span class="ml-2 text-sm text-gray-600">
                                Tôi đồng ý với <a href="#" class="text-amber-600 hover:text-amber-700 underline">Điều khoản sử dụng</a> và <a href="#" class="text-amber-600 hover:text-amber-700 underline">Chính sách bảo mật</a>
                            </span>
                        </label>
                    </div>

                    <div class="mb-6">
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-6 py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                            <i class="fas fa-user-plus mr-2"></i> Đăng ký
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <span class="text-gray-500 text-sm">Đã có tài khoản?</span>
                    <a href="login.php" class="ml-1 font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                        Đăng nhập ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

