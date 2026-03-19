<?php
// Ensure session is started FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Debug logging
error_log("Profile AJAX - Action: " . $action);
error_log("Profile AJAX - POST data: " . json_encode($_POST));

switch ($action) {
    case 'update_profile':
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate required fields
        if (empty($full_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            exit;
        }

        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi tài khoản khác']);
            exit;
        }

        // Handle password change
        $update_password = false;
        if (!empty($new_password)) {
            if (empty($current_password)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mật khẩu hiện tại']);
                exit;
            }

            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!password_verify($current_password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
                exit;
            }

            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
                exit;
            }

            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
                exit;
            }

            $update_password = true;
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }

        // Update profile
        if ($update_password) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        }

        if ($stmt->execute()) {
            // Update session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công',
                'user' => [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
        }
        break;

    case 'update_address':
        $address = sanitize($_POST['address'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $district = sanitize($_POST['district'] ?? '');
        $ward = sanitize($_POST['ward'] ?? '');

        // Check if address exists
        $stmt = $conn->prepare("SELECT id FROM user_addresses WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE user_addresses SET address = ?, city = ?, district = ?, ward = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $address, $city, $district, $ward, $user_id);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address, city, district, ward) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $address, $city, $district, $ward);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật địa chỉ thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>
