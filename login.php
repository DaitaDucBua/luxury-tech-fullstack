<?php
require_once 'config/config.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Đăng nhập';

include 'includes/header.php';
?>

<div class="flex justify-center py-8 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Card Header -->
            <div class="text-center py-8 px-6 border-b border-gray-200">
                <div class="mb-4 w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-2">Đăng nhập</h3>
                <p class="text-gray-500 text-sm">Chào mừng bạn quay trở lại</p>
            </div>

            <div class="p-6 md:p-8">
                <div id="loginAlert" class="hidden mb-4 p-4 rounded-lg" role="alert"></div>

                <form id="loginForm" action="ajax/auth.php" method="POST" onsubmit="return handleLogin(event)">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tên đăng nhập hoặc Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="username" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                   required 
                                   placeholder="Nhập tên đăng nhập hoặc email"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mật khẩu
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" 
                                   name="password" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                   required 
                                   placeholder="Nhập mật khẩu">
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                                   id="rememberMe">
                            <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="forgot-password.php" class="text-sm text-amber-600 hover:text-amber-700 transition-colors">
                            Quên mật khẩu?
                        </a>
                    </div>

                    <div class="mb-6">
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-6 py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm"
                                id="loginBtn">
                            <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="flex items-center my-6">
                    <div class="flex-1 border-t border-gray-300"></div>
                    <span class="px-4 text-gray-500 text-sm">Hoặc đăng nhập với</span>
                    <div class="flex-1 border-t border-gray-300"></div>
                </div>

                <!-- Social Login Buttons -->
                <div class="space-y-3 mb-6">
                    <!-- Facebook Login Button -->
                    <a href="auth/facebook-login.php" 
                       class="w-full inline-flex items-center justify-center px-6 py-3 bg-[#1877f2] hover:bg-[#166fe5] text-white font-medium rounded-lg transition-colors shadow-sm">
                        <i class="fab fa-facebook-f mr-2"></i>
                        Đăng nhập với Facebook
                    </a>

                    <!-- Google Login Button -->
                    <a href="google-login.php" 
                       class="w-full inline-flex items-center justify-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border-2 border-gray-300 transition-colors shadow-sm">
                        <img src="https://www.google.com/favicon.ico" alt="Google" class="w-5 h-5 mr-2">
                        Đăng nhập với Google
                    </a>
                </div>

                <div class="text-center">
                    <span class="text-gray-500 text-sm">Chưa có tài khoản?</span>
                    <a href="register.php" class="ml-1 font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                        Đăng ký ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function testLogin() {
    console.log('Testing login with XMLHttpRequest...');

    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('username', 'admin');
    formData.append('password', 'admin123');

    xhr.open('POST', 'ajax/auth.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
        console.log('XHR Status:', xhr.status);
        console.log('XHR Response:', xhr.responseText);

        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                alert('Test Result: ' + JSON.stringify(data, null, 2));
            } catch (e) {
                alert('JSON Parse Error: ' + e.message + '\nRaw Response: ' + xhr.responseText);
            }
        } else {
            alert('HTTP Error: ' + xhr.status + ' - ' + xhr.statusText + '\nResponse: ' + xhr.responseText);
        }
    };

    xhr.onerror = function() {
        console.error('XHR Network Error');
        alert('Network Error: Không thể kết nối đến server');
    };

    xhr.send(formData);
}

function handleLogin(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const button = form.querySelector('button[type="submit"]');
    const alertDiv = document.getElementById('loginAlert');

    // Show loading
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Đang đăng nhập...';

    // Hide previous alerts
    alertDiv.classList.add('hidden');

    console.log('Sending login request...');

    fetch('ajax/auth.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin', // IMPORTANT: Send cookies with request
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        if (data.success) {
            // Show success message
            alertDiv.className = 'bg-green-50 border border-green-200 text-green-800';
            alertDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
            alertDiv.classList.remove('hidden');

            // Redirect after 2 seconds with a reload to ensure session is set
            setTimeout(() => {
                // Add nocache parameter to force reload
                let redirectUrl = data.redirect || 'index.php';
                // Reload with nocache to ensure session is properly set
                window.location.href = redirectUrl + (redirectUrl.indexOf('?') > -1 ? '&' : '?') + '_t=' + Date.now();
            }, 1000);
        } else {
            // Show error message
            alertDiv.className = 'bg-red-50 border border-red-200 text-red-800';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
            alertDiv.classList.remove('hidden');

            // Reset button
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alertDiv.className = 'bg-red-50 border border-red-200 text-red-800';
        alertDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Có lỗi xảy ra: ' + error.message + '<br><small class="text-xs">Vui lòng kiểm tra console (F12) để biết thêm chi tiết.</small>';
        alertDiv.classList.remove('hidden');

        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập';
    });

    return false;
}
</script>

