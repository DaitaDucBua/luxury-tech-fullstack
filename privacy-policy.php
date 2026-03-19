<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4" style="color: #c9a050;">
                        <i class="fas fa-shield-alt me-2"></i>Chính Sách Bảo Mật
                    </h1>
                    
                    <p class="text-muted text-center mb-5">Cập nhật lần cuối: <?php echo date('d/m/Y'); ?></p>

                    <h4>1. Thu thập thông tin</h4>
                    <p>Chúng tôi thu thập thông tin khi bạn đăng ký, đăng nhập hoặc sử dụng dịch vụ của chúng tôi, bao gồm:</p>
                    <ul>
                        <li>Họ tên, email, số điện thoại</li>
                        <li>Thông tin tài khoản mạng xã hội (khi đăng nhập qua Facebook/Google)</li>
                        <li>Địa chỉ giao hàng</li>
                        <li>Lịch sử đơn hàng</li>
                    </ul>

                    <h4 class="mt-4">2. Sử dụng thông tin</h4>
                    <p>Thông tin của bạn được sử dụng để:</p>
                    <ul>
                        <li>Xử lý đơn hàng và giao hàng</li>
                        <li>Liên hệ hỗ trợ khách hàng</li>
                        <li>Gửi thông tin khuyến mãi (nếu bạn đồng ý)</li>
                        <li>Cải thiện trải nghiệm người dùng</li>
                    </ul>

                    <h4 class="mt-4">3. Bảo vệ thông tin</h4>
                    <p>Chúng tôi cam kết bảo vệ thông tin của bạn bằng các biện pháp bảo mật phù hợp. Thông tin cá nhân không được chia sẻ cho bên thứ ba trừ khi có sự đồng ý của bạn hoặc theo yêu cầu pháp luật.</p>

                    <h4 class="mt-4">4. Đăng nhập mạng xã hội</h4>
                    <p>Khi bạn đăng nhập qua Facebook hoặc Google, chúng tôi chỉ truy cập:</p>
                    <ul>
                        <li>Thông tin hồ sơ công khai (tên, ảnh đại diện)</li>
                        <li>Địa chỉ email</li>
                    </ul>
                    <p>Chúng tôi không đăng bài hoặc truy cập danh sách bạn bè của bạn.</p>

                    <h4 class="mt-4">5. Quyền của bạn</h4>
                    <p>Bạn có quyền:</p>
                    <ul>
                        <li>Truy cập và chỉnh sửa thông tin cá nhân</li>
                        <li>Yêu cầu xóa tài khoản</li>
                        <li>Hủy đăng ký nhận email marketing</li>
                    </ul>

                    <h4 class="mt-4">6. Liên hệ</h4>
                    <p>Nếu có thắc mắc về chính sách bảo mật, vui lòng liên hệ:</p>
                    <ul>
                        <li>Email: <?php echo SITE_EMAIL; ?></li>
                        <li>Hotline: <?php echo SITE_PHONE; ?></li>
                    </ul>

                    <div class="text-center mt-5">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

