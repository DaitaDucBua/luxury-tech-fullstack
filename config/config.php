<?php
// Khởi động session TRƯỚC TIÊN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ngăn browser cache cho các trang PHP động (skip nếu đang trong AJAX request)
if (!headers_sent() && !isset($skip_config_headers)) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'luxurytech');

// Cấu hình website
define('SITE_NAME', 'LuxuryTech');
define('SITE_URL', 'http://localhost/LUXURYTECH');
define('SITE_EMAIL', 'contact@luxurytech.com');
define('SITE_PHONE', '1900 1234');

// Cấu hình Ollama AI Chatbot
define('OLLAMA_URL', 'http://localhost:11434/api/generate');
// Trên Ollama Windows, tên model thường có tag như "llama3:latest"
define('OLLAMA_MODEL', 'qwen2.5:7b'); // Model hỗ trợ tiếng Việt tốt hơn llama3
define('OLLAMA_ENABLED', true); // Bật/tắt AI (false = dùng logic cũ)

// Cấu hình Google OAuth
// Cấu hình Google OAuth
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/google-callback.php');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/google-callback.php');

// Hàm lấy cache version từ file modification time
function getAssetVersion($file_path) {
    $full_path = __DIR__ . '/../' . $file_path;
    if (file_exists($full_path)) {
        return filemtime($full_path);
    }
    return time();
}

// Kết nối database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    // Đảm bảo autocommit được bật để commit ngay lập tức
    $conn->autocommit(TRUE);
} catch (Exception $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Hàm helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    global $conn;
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

function getSessionId() {
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
    }
    return $_SESSION['session_id'];
}

function getCartCount() {
    global $conn;
    $count = 0;
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $session_id = getSessionId();
        $sql = "SELECT SUM(quantity) as total FROM cart WHERE session_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $session_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['total'] ?? 0;
    
    return $count;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function showMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

/**
 * Lấy giá Flash Sale nếu sản phẩm đang trong Flash Sale active
 * @param int $product_id ID sản phẩm
 * @return array|null Trả về [flash_price, discount_percent, flash_sale_id, flash_sale_product_id] hoặc null
 */
function getFlashSalePrice($product_id) {
    global $conn;
    $current_time = date('Y-m-d H:i:s');

    $sql = "SELECT fsp.flash_price, fsp.discount_percent, fsp.id as fsp_id, fsp.flash_sale_id,
                   fsp.quantity_limit, fsp.quantity_sold, fsp.max_per_customer
            FROM flash_sale_products fsp
            JOIN flash_sales fs ON fsp.flash_sale_id = fs.id
            WHERE fsp.product_id = ?
              AND fs.status = 'active'
              AND fs.start_time <= ?
              AND fs.end_time > ?
              AND fsp.quantity_sold < fsp.quantity_limit
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $product_id, $current_time, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Lấy giá thực của sản phẩm (ưu tiên Flash Sale > Sale Price > Price)
 * @param array $product Mảng thông tin sản phẩm
 * @return float Giá thực
 */
function getProductActualPrice($product) {
    // Kiểm tra Flash Sale trước
    $flash = getFlashSalePrice($product['id']);
    if ($flash) {
        return $flash['flash_price'];
    }
    // Sau đó check sale_price
    if (!empty($product['sale_price']) && $product['sale_price'] > 0) {
        return $product['sale_price'];
    }
    // Cuối cùng là giá gốc
    return $product['price'];
}
?>

