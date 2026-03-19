<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

header('Content-Type: application/json');
// Strong cache prevention headers
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Khởi tạo compare list trong session
if (!isset($_SESSION['compare_list'])) {
    $_SESSION['compare_list'] = [];
}

switch ($action) {
    case 'add':
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
            exit;
        }
        
        // Giới hạn tối đa 4 sản phẩm
        if (count($_SESSION['compare_list']) >= 4) {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể so sánh tối đa 4 sản phẩm']);
            exit;
        }
        
        // Kiểm tra đã tồn tại chưa
        if (in_array($product_id, $_SESSION['compare_list'])) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã có trong danh sách so sánh']);
            exit;
        }
        
        // Lấy thông tin sản phẩm
        $query = "SELECT id, name, category_id FROM products WHERE id = $product_id";
        $result = $conn->query($query);
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        
        // Kiểm tra cùng danh mục (bắt buộc)
        if (count($_SESSION['compare_list']) > 0) {
            $first_product_id = $_SESSION['compare_list'][0];
            $first_product = $conn->query("SELECT category_id, name FROM products WHERE id = $first_product_id")->fetch_assoc();
            
            if ($product['category_id'] != $first_product['category_id']) {
                // Chặn sản phẩm khác danh mục
                $first_category = $conn->query("SELECT name FROM categories WHERE id = {$first_product['category_id']}")->fetch_assoc();
                $current_category = $conn->query("SELECT name FROM categories WHERE id = {$product['category_id']}")->fetch_assoc();
                
                echo json_encode([
                    'success' => false, 
                    'message' => 'Chỉ có thể so sánh sản phẩm cùng danh mục. Sản phẩm hiện tại thuộc "' . $current_category['name'] . '", nhưng danh sách so sánh đang có sản phẩm thuộc "' . $first_category['name'] . '".'
                ]);
                exit;
            }
        }
        
        $_SESSION['compare_list'][] = $product_id;
        
        $count = count($_SESSION['compare_list']);
        $message = 'Đã thêm vào danh sách so sánh';
        
        if ($count < 2) {
            $message .= ' (Cần ít nhất 2 sản phẩm để so sánh)';
        } else {
            $message .= " ({$count}/4 sản phẩm)";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'count' => $count,
            'product' => $product
        ]);
        break;
        
    case 'remove':
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
            exit;
        }
        
        $key = array_search($product_id, $_SESSION['compare_list']);
        if ($key !== false) {
            unset($_SESSION['compare_list'][$key]);
            $_SESSION['compare_list'] = array_values($_SESSION['compare_list']); // Re-index
            
            // Force session write
            session_write_close();
            session_start();
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi danh sách so sánh',
                'count' => count($_SESSION['compare_list'])
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong danh sách']);
        }
        exit;
        
    case 'clear':
        $_SESSION['compare_list'] = [];
        // Force session write to ensure it's saved immediately
        session_write_close();
        session_start();
        // Verify it's cleared
        $_SESSION['compare_list'] = [];
        echo json_encode([
            'success' => true, 
            'message' => 'Đã xóa tất cả sản phẩm so sánh',
            'count' => 0
        ]);
        exit; // Use exit to ensure response is sent
        
    case 'get_list':
        $product_ids = $_SESSION['compare_list'];
        
        if (empty($product_ids)) {
            echo json_encode(['success' => true, 'products' => [], 'count' => 0]);
            exit;
        }
        
        $ids_str = implode(',', $product_ids);
        $query = "SELECT p.*, c.name as category_name,
                  (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
                  (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id IN ($ids_str)
                  ORDER BY FIELD(p.id, $ids_str)";
        
        $result = $conn->query($query);
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            // Parse specifications
            $specs = [];
            if (!empty($row['specifications'])) {
                $lines = explode("\n", $row['specifications']);
                foreach ($lines as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $value) = explode(':', $line, 2);
                        $specs[trim($key)] = trim($value);
                    }
                }
            }
            $row['specs_array'] = $specs;
            $row['avg_rating'] = round($row['avg_rating'] ?? 0, 1);
            // Calculate discount percent: if sale_price exists, calculate from price
            $row['discount_percent'] = 0;
            if (!empty($row['sale_price']) && $row['sale_price'] < $row['price'] && $row['price'] > 0) {
                $row['discount_percent'] = round((($row['price'] - $row['sale_price']) / $row['price']) * 100);
            }
            
            $products[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'count' => count($products)
        ]);
        break;
        
    case 'check':
        $product_id = intval($_GET['product_id'] ?? 0);
        $in_compare = in_array($product_id, $_SESSION['compare_list']);
        
        echo json_encode([
            'success' => true,
            'in_compare' => $in_compare,
            'count' => count($_SESSION['compare_list'])
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
?>

