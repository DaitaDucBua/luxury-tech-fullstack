<?php
session_start();
require_once 'config/config.php';

// Đồng bộ timezone với MySQL
$conn->query("SET time_zone = '+07:00'"); // Giờ Việt Nam

include 'includes/header.php';

$message = '';
$type = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

// Kiểm tra token
if (!empty($token)) {
    // Debug: Kiểm tra token có trong database không (không check thời gian)
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reset_data = $result->fetch_assoc();

        // Lấy thời gian hiện tại từ MySQL
        $now_result = $conn->query("SELECT NOW() as now_time");
        $now_row = $now_result->fetch_assoc();
        $mysql_now = $now_row['now_time'];

        // So sánh thời gian
        if (strtotime($reset_data['expiry']) > strtotime($mysql_now)) {
            $valid_token = true;
        }
    }
}

// Xử lý đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự!';
        $type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'Mật khẩu xác nhận không khớp!';
        $type = 'danger';
    } else {
        // Cập nhật mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $reset_data['email']);
        
        if ($stmt->execute()) {
            // Xóa token đã sử dụng
            $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($reset_data['email']) . "'");
            
            $message = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay bây giờ.';
            $type = 'success';
            $valid_token = false; // Ẩn form
        } else {
            $message = 'Có lỗi xảy ra. Vui lòng thử lại!';
            $type = 'danger';
        }
    }
}
?>

<div class="row justify-content-center py-5">
    <div class="col-lg-5 col-md-7">
        <div class="card border-0 shadow-lg" style="border-radius: 16px;">
            <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px 16px 0 0;">
                <div class="mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(201, 160, 80, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-lock fa-2x" style="color: #c9a050;"></i>
                </div>
                <h3 class="text-white mb-0">Đặt lại mật khẩu</h3>
            </div>
            
            <div class="card-body p-4">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $type; ?>">
                    <?php if ($type === 'success'): ?>
                        <i class="fas fa-check-circle me-2"></i>
                    <?php endif; ?>
                    <?php echo $message; ?>
                </div>
                <?php if ($type === 'success'): ?>
                <div class="text-center">
                    <a href="login.php" class="btn btn-lg" style="background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e;">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập ngay
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($valid_token): ?>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Mật khẩu mới</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" minlength="6">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium">Xác nhận mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="confirm_password" class="form-control" required 
                                   placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                    
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-lg" style="background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e; font-weight: 600;">
                            <i class="fas fa-save me-2"></i>Đặt lại mật khẩu
                        </button>
                    </div>
                </form>
                <?php elseif (!$message): ?>
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                    </div>
                    <h4>Link không hợp lệ hoặc đã hết hạn!</h4>
                    <p class="text-muted">Vui lòng yêu cầu link đặt lại mật khẩu mới.</p>
                    <a href="forgot-password.php" class="btn" style="background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e;">
                        <i class="fas fa-redo me-2"></i>Yêu cầu link mới
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none" style="color: #c9a050;">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

