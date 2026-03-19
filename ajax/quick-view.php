<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

// Lấy thông tin sản phẩm
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug,
          (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    exit;
}

$product = $result->fetch_assoc();

// Format data
$product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
$product['review_count'] = intval($product['review_count']);
$product['discount_percent'] = 0;

if ($product['sale_price'] && $product['sale_price'] < $product['price']) {
    $product['discount_percent'] = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
}

// Đảm bảo image path đúng
if (!empty($product['image']) && strpos($product['image'], 'http') !== 0) {
    $product['image'] = SITE_URL . '/' . ltrim($product['image'], '/');
}

// Parse specifications - hỗ trợ cả format dấu phẩy và xuống dòng
$product['specs_array'] = [];
if (!empty($product['specifications'])) {
    // Thử parse theo dấu phẩy trước (format: Tên:Giá trị, Tên:Giá trị)
    if (strpos($product['specifications'], ',') !== false) {
        $specs = explode(',', $product['specifications']);
    } else {
        // Nếu không có dấu phẩy, dùng xuống dòng
        $specs = explode("\n", $product['specifications']);
    }
    
    foreach ($specs as $spec) {
        $spec = trim($spec);
        if (empty($spec)) continue;
        
        if (strpos($spec, ':') !== false) {
            list($key, $value) = explode(':', $spec, 2);
            $product['specs_array'][] = [
                'key' => trim($key),
                'value' => trim($value)
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'product' => $product
]);

