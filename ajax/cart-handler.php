<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Thêm sản phẩm vào giỏ hàng
if ($action == 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    // Kiểm tra sản phẩm tồn tại
    $check_sql = "SELECT id, stock FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    $product = $result->fetch_assoc();

    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
        exit;
    }

    // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_cart);
        $stmt->bind_param("ii", $user_id, $product_id);
    } else {
        $session_id = getSessionId();
        $check_cart = "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_cart);
        $stmt->bind_param("si", $session_id, $product_id);
    }

    $stmt->execute();
    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows > 0) {
        // Cập nhật số lượng
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;

        if ($new_quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
            exit;
        }

        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_stmt->execute();
    } else {
        // Thêm mới
        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
            $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        } else {
            $session_id = getSessionId();
            $insert_sql = "INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sii", $session_id, $product_id, $quantity);
        }
        $insert_stmt->execute();
    }

    $cart_count = getCartCount();
    echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng', 'cart_count' => $cart_count]);
    exit;
}

// Cập nhật số lượng
if ($action == 'update') {
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($cart_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    // Kiểm tra giỏ hàng
    $check_sql = "SELECT c.*, p.stock, p.price, p.sale_price 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hàng']);
        exit;
    }

    $cart_item = $result->fetch_assoc();

    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
        exit;
    }

    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $quantity, $cart_id);
    $update_stmt->execute();

    $price = $cart_item['sale_price'] ?: $cart_item['price'];
    $subtotal = $price * $quantity;
    $cart_count = getCartCount();

    echo json_encode([
        'success' => true, 
        'message' => 'Đã cập nhật giỏ hàng',
        'subtotal' => formatPrice($subtotal),
        'cart_count' => $cart_count
    ]);
    exit;
}

// Xóa sản phẩm khỏi giỏ hàng
if ($action == 'remove') {
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

    if ($cart_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $delete_sql = "DELETE FROM cart WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();

    $cart_count = getCartCount();

    echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng', 'cart_count' => $cart_count]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
?>

