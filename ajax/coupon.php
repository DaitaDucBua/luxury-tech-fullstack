<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Áp dụng mã giảm giá
if ($action === 'apply') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
        exit;
    }
    
    // Tìm mã giảm giá
    $sql = "SELECT * FROM coupons WHERE code = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn']);
        exit;
    }
    
    $coupon = $result->fetch_assoc();
    
    // Kiểm tra thời gian
    $now = date('Y-m-d H:i:s');
    if ($now < $coupon['start_date'] || $now > $coupon['end_date']) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết hạn hoặc chưa có hiệu lực']);
        exit;
    }
    
    // Kiểm tra số lần sử dụng
    if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng']);
        exit;
    }
    
    // Kiểm tra giá trị đơn hàng tối thiểu
    if ($subtotal < $coupon['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng tối thiểu ' . formatPrice($coupon['min_order_value']) . ' để áp dụng mã này'
        ]);
        exit;
    }
    
    // Kiểm tra user đã dùng mã này chưa (nếu đăng nhập)
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $check_sql = "SELECT id FROM coupon_usage WHERE coupon_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $coupon['id'], $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này rồi']);
            exit;
        }
    }
    
    // Tính giảm giá
    if ($coupon['type'] === 'percent') {
        $discount = $subtotal * ($coupon['value'] / 100);
        // Giới hạn giảm giá tối đa
        if ($coupon['max_discount'] !== null && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
        $discount_text = $coupon['value'] . '%';
    } else {
        $discount = $coupon['value'];
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
        $discount_text = formatPrice($coupon['value']);
    }
    
    $total = $subtotal - $discount;
    
    // Lưu vào session
    $_SESSION['applied_coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'type' => $coupon['type'],
        'value' => $coupon['value'],
        'discount' => $discount,
        'description' => $coupon['description']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã giảm giá thành công!',
        'coupon' => [
            'code' => $coupon['code'],
            'type' => $coupon['type'],
            'value' => $coupon['value'],
            'discount' => $discount,
            'discount_formatted' => formatPrice($discount),
            'discount_text' => $discount_text,
            'description' => $coupon['description']
        ],
        'total' => $total,
        'total_formatted' => formatPrice($total)
    ]);
    exit;
}

// Xóa mã giảm giá
if ($action === 'remove') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Đã xóa mã giảm giá']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);

