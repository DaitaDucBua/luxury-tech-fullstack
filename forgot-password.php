<?php
session_start();
require_once 'config/config.php';

// Đồng bộ timezone với MySQL
$conn->query("SET time_zone = '+07:00'"); // Giờ Việt Nam

include 'includes/header.php';

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/email-helper.php';
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Vui lòng nhập email!';
        $type = 'danger';
    } else {
        // Kiểm tra email tồn tại
        $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Tạo token (sử dụng thời gian từ MySQL để đảm bảo đồng bộ)
            $token = bin2hex(random_bytes(32));
            $time_result = $conn->query("SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) as expiry");
            $time_row = $time_result->fetch_assoc();
            $expiry = $time_row['expiry'];
            
            // Xóa token cũ
            $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($email) . "'");
            
            // Lưu token mới
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expiry);
            $stmt->execute();
            
            // Gửi email
            $reset_link = SITE_URL . "/reset-password.php?token=" . $token;
            $subject = "🔑 Đặt lại mật khẩu - LuxuryTech";
            
            $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background: #f5f5f5; }
                    .container { max-width: 600px; margin: 0 auto; background: white; }
                    .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; }
                    .header h1 { color: #c9a050; margin: 0; }
                    .content { padding: 40px 30px; }
                    .btn { display: inline-block; background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; }
                    .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 LuxuryTech</h1>
                    </div>
                    <div class='content'>
                        <h2>Xin chào " . htmlspecialchars($user['full_name']) . "!</h2>
                        <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản LuxuryTech.</p>
                        <p>Bấm vào nút bên dưới để đặt lại mật khẩu:</p>
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='$reset_link' class='btn'>Đặt lại mật khẩu</a>
                        </p>
                        <p><strong>⏰ Link có hiệu lực trong 1 giờ.</strong></p>
                        <p style='color: #666; font-size: 14px;'>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                        <p style='color: #999; font-size: 12px;'>Hoặc copy link sau vào trình duyệt:<br>$reset_link</p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " LuxuryTech. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            if (sendEmail($email, $subject, $body)) {
                $message = 'Đã gửi link đặt lại mật khẩu đến email của bạn! Vui lòng kiểm tra hộp thư.';
                $type = 'success';
            } else {
                $message = 'Không thể gửi email. Vui lòng thử lại sau!';
                $type = 'danger';
            }
        } else {
            // Vẫn hiện thông báo thành công để tránh lộ thông tin email
            $message = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được link đặt lại mật khẩu.';
            $type = 'info';
        }
    }
}
?>

<div class="row justify-content-center py-5">
    <div class="col-lg-5 col-md-7">
        <div class="card border-0 shadow-lg" style="border-radius: 16px;">
            <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px 16px 0 0;">
                <div class="mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(201, 160, 80, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-key fa-2x" style="color: #c9a050;"></i>
                </div>
                <h3 class="text-white mb-0">Quên mật khẩu</h3>
                <p class="text-white-50 mb-0 mt-2">Nhập email để nhận link đặt lại mật khẩu</p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Địa chỉ Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" required 
                                   placeholder="Nhập email đã đăng ký"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-lg" style="background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e; font-weight: 600;">
                            <i class="fas fa-paper-plane me-2"></i>Gửi link đặt lại
                        </button>
                    </div>
                </form>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none" style="color: #c9a050;">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

