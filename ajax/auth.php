<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Debug logging
error_log("AJAX Auth Request - Action: " . ($_POST['action'] ?? 'none') . ", Username: " . ($_POST['username'] ?? 'none'));

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        error_log("=== LOGIN ATTEMPT ===");
        error_log("Username: $username");
        error_log("Password length: " . strlen($password));

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
            exit;
        }

        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            exit;
        }

        $stmt->bind_param("ss", $username, $username);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            exit;
        }

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            error_log("User found - ID: " . $user['id'] . ", Role: " . $user['role']);

            // Debug password verification
            error_log("Stored hash: " . $user['password']);
            error_log("Input password: " . $password);

            $password_verified = password_verify($password, $user['password']);
            error_log("Password verification result: " . ($password_verified ? "true" : "false"));

            if ($password_verified) {
                error_log("Password verified successfully");

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Chuyển giỏ hàng từ session sang user
                $session_id = getSessionId();
                $update_cart = "UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ?";
                $update_stmt = $conn->prepare($update_cart);
                $update_stmt->bind_param("is", $user['id'], $session_id);
                $update_stmt->execute();

                // Redirect based on user role
                if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                    $redirect_url = $_POST['redirect'];
                } elseif ($user['role'] === 'admin') {
                    $redirect_url = SITE_URL . '/admin/index.php';
                } else {
                    $redirect_url = SITE_URL . '/index.php';
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Đăng nhập thành công!',
                    'redirect' => $redirect_url,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role']
                    ]
                ]);
                error_log("Login successful - User ID: " . $user['id']);
            } else {
                error_log("Password verification failed for user: $username");
                echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng']);
            }
        } else {
            error_log("User not found: $username");
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc email không tồn tại']);
        }
        break;

    case 'register':
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = sanitize($_POST['phone'] ?? '');

        // Validation
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
            exit;
        }

        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            exit;
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
            exit;
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
            exit;
        }

        // Insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())");
        $stmt->bind_param("sssss", $full_name, $username, $email, $hashed_password, $phone);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đăng ký thành công! Vui lòng đăng nhập.',
                'redirect' => SITE_URL . '/login.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
        }
        break;

    case 'logout':
        // Clear session
        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Đăng xuất thành công',
            'redirect' => SITE_URL . '/index.php'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>
