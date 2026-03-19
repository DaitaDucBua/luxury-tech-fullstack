<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_cart':
        $cart_items = [];
        $total = 0;
        $count = 0;

        if (isLoggedIn()) {
            // Logged in user - get from database
            $user_id = $_SESSION['user_id'];
            $query = "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.slug, p.stock
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.user_id = ?
                      ORDER BY c.created_at DESC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $price = $row['sale_price'] ?: $row['price'];
                $subtotal = $price * $row['quantity'];
                $total += $subtotal;
                $count += $row['quantity'];

                // Thêm cache busting cho ảnh
                $image_url = $row['image'];
                if (strpos($image_url, 'http') !== 0) {
                    $file_path = '../' . $image_url;
                    if (file_exists($file_path)) {
                        $image_url .= '?v=' . filemtime($file_path);
                    }
                }

                $cart_items[] = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $price,
                    'quantity' => $row['quantity'],
                    'image_url' => $image_url,
                    'slug' => $row['slug'],
                    'stock' => $row['stock'],
                    'subtotal' => $subtotal
                ];
            }
        } else {
            // Guest user - get from database using session_id
            $session_id = getSessionId();
            $query = "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.slug, p.stock
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.session_id = ?
                      ORDER BY c.created_at DESC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $session_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $price = $row['sale_price'] ?: $row['price'];
                $subtotal = $price * $row['quantity'];
                $total += $subtotal;
                $count += $row['quantity'];

                // Thêm cache busting cho ảnh
                $image_url = $row['image'];
                if (strpos($image_url, 'http') !== 0) {
                    $file_path = '../' . $image_url;
                    if (file_exists($file_path)) {
                        $image_url .= '?v=' . filemtime($file_path);
                    }
                }

                $cart_items[] = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $price,
                    'quantity' => $row['quantity'],
                    'image_url' => $image_url,
                    'slug' => $row['slug'],
                    'stock' => $row['stock'],
                    'subtotal' => $subtotal
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'items' => $cart_items,
            'total' => $total,
            'count' => $count
        ]);
        break;
        
    case 'update_quantity':
        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($product_id <= 0 || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Check stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
            exit;
        }

        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];

            if ($quantity == 0) {
                // Remove item
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
            } else {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $stmt->execute();
            }
        } else {
            $session_id = getSessionId();

            if ($quantity == 0) {
                // Remove item
                $stmt = $conn->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
                $stmt->bind_param("si", $session_id, $product_id);
                $stmt->execute();
            } else {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?");
                $stmt->bind_param("isi", $quantity, $session_id, $product_id);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng']);
        break;

    case 'remove_item':
        $product_id = intval($_POST['product_id'] ?? 0);

        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        } else {
            $session_id = getSessionId();
            $stmt = $conn->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
            $stmt->bind_param("si", $session_id, $product_id);
            $stmt->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
?>

