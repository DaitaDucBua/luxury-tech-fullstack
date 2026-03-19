<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =====================================================
// SEARCH AUTOCOMPLETE
// =====================================================
if ($action === 'autocomplete') {
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'results' => []]);
        exit;
    }
    
    $search = "%{$query}%";
    $stmt = $conn->prepare("SELECT id, name, slug, price, sale_price, image 
                            FROM products 
                            WHERE (name LIKE ? OR description LIKE ?) 
                            AND status = 'active'
                            ORDER BY views DESC
                            LIMIT 10");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'price' => $row['sale_price'] ?: $row['price'],
            'image' => $row['image']
        ];
    }
    
    echo json_encode(['success' => true, 'results' => $products]);
    exit;
}

// =====================================================
// ADVANCED SEARCH
// =====================================================
if ($action === 'search') {
    $query = trim($_POST['query'] ?? '');
    $category = intval($_POST['category'] ?? 0);
    $min_price = floatval($_POST['min_price'] ?? 0);
    $max_price = floatval($_POST['max_price'] ?? 0);
    $sort = $_POST['sort'] ?? 'newest';
    $page = intval($_POST['page'] ?? 1);
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where = ["status = 'active'"];
    $params = [];
    $types = "";
    
    if (!empty($query)) {
        $where[] = "(name LIKE ? OR description LIKE ?)";
        $search = "%{$query}%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    if ($category > 0) {
        $where[] = "category_id = ?";
        $params[] = $category;
        $types .= "i";
    }
    
    if ($min_price > 0) {
        $where[] = "COALESCE(sale_price, price) >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price > 0) {
        $where[] = "COALESCE(sale_price, price) <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    $where_clause = implode(" AND ", $where);
    
    // Sort
    $order_by = match($sort) {
        'price_asc' => 'COALESCE(sale_price, price) ASC',
        'price_desc' => 'COALESCE(sale_price, price) DESC',
        'name_asc' => 'name ASC',
        'name_desc' => 'name DESC',
        'popular' => 'views DESC',
        default => 'created_at DESC'
    };
    
    // Get products
    $sql = "SELECT * FROM products WHERE {$where_clause} ORDER BY {$order_by} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE {$where_clause}";
    $count_stmt = $conn->prepare($count_sql);
    
    if (!empty($params) && count($params) > 2) {
        $count_types = substr($types, 0, -2); // Remove last 'ii' (limit, offset)
        $count_params = array_slice($params, 0, -2);
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);
    exit;
}

// =====================================================
// GET FILTER OPTIONS
// =====================================================
if ($action === 'get_filters') {
    // Get price range
    $price_query = "SELECT MIN(COALESCE(sale_price, price)) as min_price, 
                           MAX(COALESCE(sale_price, price)) as max_price 
                    FROM products WHERE status = 'active'";
    $price_result = $conn->query($price_query);
    $price_range = $price_result->fetch_assoc();
    
    // Get categories
    $cat_query = "SELECT c.id, c.name, COUNT(p.id) as product_count 
                  FROM categories c 
                  LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                  GROUP BY c.id 
                  ORDER BY c.name";
    $cat_result = $conn->query($cat_query);
    
    $categories = [];
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'price_range' => $price_range,
        'categories' => $categories
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);

