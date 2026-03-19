<?php
// Tắt hiển thị lỗi nhưng vẫn log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Bắt đầu output buffering để bắt mọi output không mong muốn
ob_start();

session_start();

// Load config nhưng không cho nó set headers (vì chúng ta cần set JSON header)
// Tạm thời disable header setting trong config
$skip_config_headers = true;
require_once '../config/config.php';
require_once '../includes/chat-logger.php';

// Xóa mọi output đã có và set header JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Lấy hoặc tạo conversation
function getOrCreateConversation($conn) {
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    // Tìm conversation đang active
    if ($user_id) {
        $query = "SELECT * FROM chat_conversations 
                  WHERE user_id = $user_id AND status = 'active' 
                  ORDER BY updated_at DESC LIMIT 1";
    } else {
        $query = "SELECT * FROM chat_conversations 
                  WHERE session_id = '$session_id' AND status = 'active' 
                  ORDER BY updated_at DESC LIMIT 1";
    }
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Tạo conversation mới
    $customer_name = $_SESSION['username'] ?? 'Khách';
    $customer_email = $_SESSION['email'] ?? '';
    
    $insert = "INSERT INTO chat_conversations (user_id, session_id, customer_name, customer_email, status) 
               VALUES (" . ($user_id ? $user_id : "NULL") . ", '$session_id', '$customer_name', '$customer_email', 'active')";
    
    if ($conn->query($insert)) {
        $conversation_id = $conn->insert_id;
        
        // Gửi tin nhắn chào mừng từ bot (dùng prepared statement để tránh SQL injection và lỗi encoding)
        $welcome_msg = "Xin chào! Tôi là trợ lý ảo của LuxuryTech. Tôi có thể giúp gì cho bạn? 😊";
        $message_stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (?, 'bot', ?)");
        if ($message_stmt) {
            $message_stmt->bind_param("is", $conversation_id, $welcome_msg);
            if (!$message_stmt->execute()) {
                error_log("Chat: Failed to insert welcome message for conversation $conversation_id: " . $message_stmt->error);
            }
            $message_stmt->close();
        }
        
        return $conn->query("SELECT * FROM chat_conversations WHERE id = $conversation_id")->fetch_assoc();
    }
    
    return null;
}

// Xử lý các action với error handling
try {
switch ($action) {
    case 'get_conversation':
        $conversation = getOrCreateConversation($conn);
            if ($conversation) {
        echo json_encode(['success' => true, 'conversation' => $conversation]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể tạo hoặc tải cuộc trò chuyện']);
            }
        break;
        
    case 'get_messages':
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        $last_message_id = intval($_GET['last_message_id'] ?? 0);
        
        if ($conversation_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
            exit;
        }
        
        $query = "SELECT * FROM chat_messages 
                  WHERE conversation_id = $conversation_id";
        
        if ($last_message_id > 0) {
            $query .= " AND id > $last_message_id";
        }
        
        $query .= " ORDER BY created_at ASC";
        
        $result = $conn->query($query);
        $messages = [];
        
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
        
    case 'send_message':
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message is required']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Dùng prepared statement để insert message (không có cột sender_name)
        $stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?, 'customer', ?, ?)");
        if (!$stmt) {
            $error_msg = 'Database error: ' . $conn->error;
            chatLogError('Failed to prepare statement for send_message', [
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'error' => $conn->error
            ]);
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        }
        
        $stmt->bind_param("iis", $conversation_id, $user_id, $message);
        
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            $stmt->close();
            
            // Cập nhật thời gian conversation
            $conn->query("UPDATE chat_conversations SET updated_at = NOW() WHERE id = $conversation_id");
            
            // Lấy tin nhắn user vừa gửi
            $new_message = $conn->query("SELECT * FROM chat_messages WHERE id = $message_id")->fetch_assoc();
            
            // Auto-reply từ bot (AI Ollama)
            try {
                $bot_reply_text = getBotReply($message, $conversation_id, $conn);
                $bot_reply_data = null;
                if ($bot_reply_text) {
                    $bot_stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (?, 'bot', ?)");
                    if ($bot_stmt) {
                        $bot_stmt->bind_param("is", $conversation_id, $bot_reply_text);
                        if ($bot_stmt->execute()) {
                            $bot_reply_id = $conn->insert_id;
                            $bot_reply_data = $conn->query("SELECT * FROM chat_messages WHERE id = $bot_reply_id")->fetch_assoc();
                            chatLogInfo('Bot reply sent successfully', [
                                'conversation_id' => $conversation_id,
                                'bot_reply_id' => $bot_reply_id
                            ]);
                        } else {
                            chatLogError('Failed to insert bot reply', [
                                'conversation_id' => $conversation_id,
                                'error' => $bot_stmt->error
                            ]);
                        }
                        $bot_stmt->close();
                    } else {
                        chatLogError('Failed to prepare bot reply statement', [
                            'conversation_id' => $conversation_id,
                            'error' => $conn->error
                        ]);
                    }
                }
            } catch (Exception $e) {
                chatLogError('Exception in bot reply generation', [
                    'conversation_id' => $conversation_id,
                    'message' => $message
                ], $e);
            }
            
            // Trả về cả tin nhắn user và bot reply (nếu có)
            $response = ['success' => true, 'message' => $new_message];
            if ($bot_reply_data) {
                $response['bot_reply'] = $bot_reply_data;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $stmt->error]);
            $stmt->close();
        }
        break;
        
    case 'upload_image':
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        
        if (!isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            exit;
        }
        
        $upload_dir = '../uploads/chat/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = 'uploads/chat/' . $filename;
            
            $user_id = $_SESSION['user_id'] ?? null;
            $message_text = '[Hình ảnh]';
            
            // Dùng prepared statement (không có cột sender_name và attachment_type)
            $stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message, attachment_url) VALUES (?, 'customer', ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiss", $conversation_id, $user_id, $message_text, $url);
                if ($stmt->execute()) {
                echo json_encode(['success' => true, 'url' => $url]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save message: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        break;
        
    case 'close_conversation':
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        
        if ($conn->query("UPDATE chat_conversations SET status = 'closed' WHERE id = $conversation_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
} catch (Exception $e) {
    // Đảm bảo luôn trả về JSON ngay cả khi có lỗi
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage(),
        'error' => $e->getFile() . ':' . $e->getLine()
    ]);
    error_log('Chat Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

// Lấy thông tin sản phẩm từ database để AI có thể trả lời chính xác - PHIÊN BẢN NÂNG CAO
function getProductInfo($conn, $message, $user_id = null) {
    $info = [];
    $message_lower = strtolower($message);
    
    // ========== 1. SỐ LƯỢNG SẢN PHẨM ==========
    if (preg_match('/(có bao nhiêu|tổng số|số lượng|tổng cộng|tất cả).*(sản phẩm|mặt hàng|hàng)/i', $message)) {
        $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $result = $conn->query($count_query);
        if ($result) {
            $row = $result->fetch_assoc();
            $info['total_products'] = $row['total'];
        }
    }
    
    // ========== 1.5. KIỂM TRA CÂU HỎI VỀ DANH SÁCH SẢN PHẨM ==========
    // Nếu hỏi về danh sách sản phẩm (không cần keyword cụ thể), luôn lấy danh sách
    $is_asking_for_products = preg_match('/(còn|những|mấy|bao nhiêu|danh sách|liệt kê|kể|show|hiển thị|cho xem).*(sản phẩm|mặt hàng|hàng)/i', $message) ||
                              preg_match('/(sản phẩm|mặt hàng|hàng).*(nào|có|đang có|hiện có|gì)/i', $message);
    
    if ($is_asking_for_products && !isset($info['total_products'])) {
        // Lấy tổng số sản phẩm luôn khi hỏi về danh sách
        $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $result = $conn->query($count_query);
        if ($result) {
            $row = $result->fetch_assoc();
            $info['total_products'] = $row['total'];
        }
    }
    
    // ========== 2. TÌM KIẾM SẢN PHẨM NÂNG CAO ==========
    $search_query = "SELECT p.id, p.name, p.price, p.stock, p.slug, p.image, p.description, p.specifications, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.status = 'active'";
    $order_by = " ORDER BY p.created_at DESC";
    $limit = " LIMIT 5"; // Giảm từ 10 xuống 5 để tăng tốc độ
    
    // Tìm theo tên/từ khóa
    $keywords = ['iphone', 'samsung', 'laptop', 'macbook', 'ipad', 'airpods', 'watch', 'dell', 'hp', 'lenovo', 'asus', 'acer', 'xiaomi', 'oppo', 'vivo'];
    $found_keyword = false;
    foreach ($keywords as $keyword) {
        if (stripos($message, $keyword) !== false) {
            $search_query .= " AND p.name LIKE '%$keyword%'";
            $found_keyword = true;
            break;
        }
    }
    
    // Tìm theo giá (dưới X triệu, từ X đến Y)
    if (preg_match('/(dưới|nhỏ hơn|ít hơn|tối đa|max).*?(\d+).*?(triệu|tr|nghìn|k)/i', $message, $matches)) {
        $max_price = intval($matches[2]) * (stripos($matches[3], 'triệu') !== false || stripos($matches[3], 'tr') !== false ? 1000000 : 1000);
        $search_query .= " AND p.price <= $max_price";
        $order_by = " ORDER BY p.price ASC";
    } elseif (preg_match('/(từ|khoảng|tầm|trong khoảng).*?(\d+).*?(đến|tới|-).*?(\d+).*?(triệu|tr|nghìn|k)/i', $message, $matches)) {
        $min_price = intval($matches[2]) * (stripos($matches[5], 'triệu') !== false || stripos($matches[5], 'tr') !== false ? 1000000 : 1000);
        $max_price = intval($matches[4]) * (stripos($matches[5], 'triệu') !== false || stripos($matches[5], 'tr') !== false ? 1000000 : 1000);
        $search_query .= " AND p.price BETWEEN $min_price AND $max_price";
        $order_by = " ORDER BY p.price ASC";
    }
    
    // Tìm theo danh mục
    $categories = ['điện thoại', 'laptop', 'tablet', 'phụ kiện', 'smartwatch', 'tai nghe'];
    foreach ($categories as $cat) {
        if (stripos($message, $cat) !== false) {
            $cat_query = "SELECT id FROM categories WHERE LOWER(name) LIKE '%$cat%' LIMIT 1";
            $cat_result = $conn->query($cat_query);
            if ($cat_result && $cat_result->num_rows > 0) {
                $cat_row = $cat_result->fetch_assoc();
                $search_query .= " AND p.category_id = " . $cat_row['id'];
            }
            break;
        }
    }
    
    // Sắp xếp
    if (preg_match('/(giá.*tăng|rẻ nhất|giá thấp)/i', $message)) {
        $order_by = " ORDER BY p.price ASC";
    } elseif (preg_match('/(giá.*giảm|đắt nhất|giá cao)/i', $message)) {
        $order_by = " ORDER BY p.price DESC";
    } elseif (preg_match('/(mới nhất|mới)/i', $message)) {
        $order_by = " ORDER BY p.created_at DESC";
    }
    
    // Nếu có tìm kiếm, thực hiện query
    // Cải thiện pattern để nhận diện các câu hỏi về danh sách sản phẩm
    $search_patterns = [
        '/(tìm|search|tìm kiếm|danh sách|liệt kê|kể)/i',
        '/(còn|những|mấy|bao nhiêu).*(sản phẩm|mặt hàng|hàng)/i',
        '/(sản phẩm|mặt hàng|hàng).*(nào|có|đang có|hiện có)/i',
        '/(có|đang có|hiện có).*(sản phẩm|mặt hàng|hàng)/i',
        '/(show|hiển thị|cho xem).*(sản phẩm|mặt hàng|hàng)/i'
    ];
    
    $should_search = $found_keyword;
    foreach ($search_patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            $should_search = true;
            break;
        }
    }
    
    // Nếu hỏi về danh sách sản phẩm nhưng không có keyword, lấy tất cả sản phẩm mới nhất
    if ($is_asking_for_products && !$found_keyword && !$should_search) {
        // Reset search query để lấy tất cả sản phẩm
        $search_query = "SELECT p.id, p.name, p.price, p.stock, p.slug, p.image, p.description, p.specifications, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 'active'";
        $order_by = " ORDER BY p.created_at DESC";
        $should_search = true; // Đánh dấu để query
    }
    
    if ($should_search || $is_asking_for_products) {
        $final_query = $search_query . $order_by . $limit;
        $result = $conn->query($final_query);
        if ($result && $result->num_rows > 0) {
            $info['products'] = [];
            while ($row = $result->fetch_assoc()) {
                $info['products'][] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'price' => number_format($row['price'], 0, ',', '.') . ' đ',
                    'stock' => $row['stock'],
                    'slug' => $row['slug'],
                    'image' => $row['image'],
                    'category' => $row['category_name'],
                    'description' => mb_substr(strip_tags($row['description']), 0, 150) . '...',
                    'specifications' => $row['specifications']
                ];
            }
        } elseif ($is_asking_for_products) {
            // Nếu hỏi về sản phẩm nhưng không có kết quả, đánh dấu để AI biết
            $info['no_products_found'] = true;
        }
    }
    
    // ========== 3. CHI TIẾT SẢN PHẨM ==========
    if (preg_match('/(chi tiết|thông tin|mô tả|thông số).*?(sản phẩm|sản phẩm nào|mặt hàng)/i', $message)) {
        // Tìm sản phẩm được đề cập trong câu hỏi
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $detail_query = "SELECT p.*, c.name as category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                WHERE p.status = 'active' AND p.name LIKE '%$keyword%' 
                                ORDER BY p.price ASC LIMIT 1";
                $result = $conn->query($detail_query);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $info['product_detail'] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'price' => number_format($row['price'], 0, ',', '.') . ' đ',
                        'stock' => $row['stock'],
                        'slug' => $row['slug'],
                        'image' => $row['image'],
                        'category' => $row['category_name'],
                        'description' => strip_tags($row['description']),
                        'specifications' => $row['specifications'],
                        'views' => $row['views']
                    ];
                }
                break;
            }
        }
    }
    
    // ========== 4. KIỂM TRA ĐƠN HÀNG ==========
    if (preg_match('/(đơn hàng|order|mã đơn|kiểm tra đơn|tra cứu đơn)/i', $message)) {
        // Tìm mã đơn hàng trong câu
        if (preg_match('/(mã|#|order|đơn).*?(\d+)/i', $message, $matches)) {
            $order_id = intval($matches[2]);
            $order_query = "SELECT o.*, u.username, u.email 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE o.id = $order_id";
            if ($user_id) {
                $order_query .= " AND o.user_id = $user_id";
            }
            $result = $conn->query($order_query);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $info['order'] = [
                    'id' => $row['id'],
                    'status' => $row['status'],
                    'total_amount' => number_format($row['total_amount'], 0, ',', '.') . ' đ',
                    'created_at' => $row['created_at'],
                    'username' => $row['username']
                ];
            }
        } elseif ($user_id) {
            // Lấy lịch sử đơn hàng của user
            $history_query = "SELECT id, status, total_amount, created_at 
                             FROM orders 
                             WHERE user_id = $user_id 
                             ORDER BY created_at DESC LIMIT 5";
            $result = $conn->query($history_query);
            if ($result && $result->num_rows > 0) {
                $info['order_history'] = [];
                while ($row = $result->fetch_assoc()) {
                    $info['order_history'][] = [
                        'id' => $row['id'],
                        'status' => $row['status'],
                        'total_amount' => number_format($row['total_amount'], 0, ',', '.') . ' đ',
                        'created_at' => $row['created_at']
                    ];
                }
            }
        }
    }
    
    // ========== 5. TƯ VẤN SẢN PHẨM THÔNG MINH ==========
    if (preg_match('/(tư vấn|gợi ý|nên mua|khuyên|recommend|suggest)/i', $message)) {
        // Tư vấn theo ngân sách
        if (preg_match('/(ngân sách|budget|tiền|vốn).*?(\d+).*?(triệu|tr|nghìn|k)/i', $message, $matches)) {
            $budget = intval($matches[2]) * (stripos($matches[3], 'triệu') !== false || stripos($matches[3], 'tr') !== false ? 1000000 : 1000);
            $advice_query = "SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.status = 'active' AND p.price <= $budget AND p.stock > 0 
                           ORDER BY p.views DESC, p.price ASC 
                           LIMIT 3";
            $result = $conn->query($advice_query);
            if ($result && $result->num_rows > 0) {
                $info['recommendations'] = [];
                while ($row = $result->fetch_assoc()) {
                    $info['recommendations'][] = [
                        'name' => $row['name'],
                        'price' => number_format($row['price'], 0, ',', '.') . ' đ',
                        'slug' => $row['slug'],
                        'category' => $row['category_name']
                    ];
                }
            }
        }
        
        // Tư vấn theo nhu cầu
        $needs = [
            'gaming' => ['gaming', 'chơi game', 'game'],
            'văn phòng' => ['văn phòng', 'office', 'làm việc'],
            'học tập' => ['học tập', 'học', 'sinh viên', 'student'],
            'đồ họa' => ['đồ họa', 'design', 'thiết kế', 'graphic']
        ];
        foreach ($needs as $need_key => $need_keywords) {
            foreach ($need_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $need_query = "SELECT p.*, c.name as category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.id 
                                 WHERE p.status = 'active' AND p.stock > 0 
                                 AND (p.name LIKE '%gaming%' OR p.name LIKE '%pro%' OR p.name LIKE '%high%' OR c.name LIKE '%laptop%')
                                 ORDER BY p.price ASC 
                                 LIMIT 3"; // Giảm từ 5 xuống 3
                    $result = $conn->query($need_query);
                    if ($result && $result->num_rows > 0) {
                        $info['recommendations'] = [];
                        while ($row = $result->fetch_assoc()) {
                            $info['recommendations'][] = [
                                'name' => $row['name'],
                                'price' => number_format($row['price'], 0, ',', '.') . ' đ',
                                'slug' => $row['slug'],
                                'category' => $row['category_name']
                            ];
                        }
                    }
                    break 2;
                }
            }
        }
    }
    
    // ========== 6. SO SÁNH SẢN PHẨM ==========
    if (preg_match('/(so sánh|compare|khác nhau|giống nhau)/i', $message)) {
        // Tìm 2 sản phẩm được đề cập
        $products_mentioned = [];
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $compare_query = "SELECT name, price, stock, slug FROM products 
                                WHERE status = 'active' AND name LIKE '%$keyword%' 
                                ORDER BY price ASC LIMIT 2";
                $result = $conn->query($compare_query);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $products_mentioned[] = [
                            'name' => $row['name'],
                            'price' => number_format($row['price'], 0, ',', '.') . ' đ',
                            'stock' => $row['stock'],
                            'slug' => $row['slug']
                        ];
                    }
                }
            }
        }
        if (count($products_mentioned) >= 2) {
            $info['compare'] = $products_mentioned;
        }
    }
    
    // ========== 7. KIỂM TRA TỒN KHO ==========
    if (preg_match('/(còn hàng|hết hàng|tồn kho|stock|in stock)/i', $message)) {
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $stock_query = "SELECT name, stock FROM products 
                              WHERE status = 'active' AND name LIKE '%$keyword%' 
                              LIMIT 3"; // Giảm từ 5 xuống 3
                $result = $conn->query($stock_query);
                if ($result && $result->num_rows > 0) {
                    $info['stock_info'] = [];
                    while ($row = $result->fetch_assoc()) {
                        $info['stock_info'][] = [
                            'name' => $row['name'],
                            'stock' => $row['stock'],
                            'available' => $row['stock'] > 0 ? 'Còn hàng' : 'Hết hàng'
                        ];
                    }
                }
                break;
            }
        }
    }
    
    return $info;
}

// Gọi Ollama AI để trả lời
function callOllama($message, $conversation_id = null, $conn = null) {
    // Kiểm tra xem Ollama có được bật không
    if (!defined('OLLAMA_ENABLED') || !OLLAMA_ENABLED) {
        return null;
    }
    
    // Lấy thông tin sản phẩm từ database
    $product_info = [];
    $user_id = $_SESSION['user_id'] ?? null;
    if ($conn) {
        $product_info = getProductInfo($conn, $message, $user_id);
    }
    
    // Lấy context từ conversation (5 tin nhắn gần nhất để giảm thời gian xử lý)
    $context = '';
    if ($conversation_id && $conn) {
        $context_query = "SELECT sender_type, message FROM chat_messages 
                         WHERE conversation_id = $conversation_id 
                         ORDER BY created_at DESC LIMIT 5";
        $context_result = $conn->query($context_query);
        $context_messages = [];
        while ($row = $context_result->fetch_assoc()) {
            $context_messages[] = $row;
        }
        $context_messages = array_reverse($context_messages);
        
        foreach ($context_messages as $msg) {
            $sender = $msg['sender_type'] === 'bot' ? 'Bot' : 'Khách';
            // Rút gọn tin nhắn dài
            $msg_text = mb_strlen($msg['message']) > 100 ? mb_substr($msg['message'], 0, 100) . '...' : $msg['message'];
            $context .= "$sender: $msg_text\n";
        }
    }
    
    // Tạo prompt cho AI - TỐI ƯU TỐC ĐỘ
    $system_prompt = "Bạn là trợ lý ảo của LuxuryTech - cửa hàng công nghệ cao cấp.\n\n";
    $system_prompt .= "QUY TẮC:\n";
    $system_prompt .= "1. Trả lời ĐÚNG câu hỏi, ngắn gọn (50-100 từ)\n";
    $system_prompt .= "2. Dùng thông tin từ database (không bịa đặt)\n";
    $system_prompt .= "3. Tiếng Việt tự nhiên, thân thiện\n";
    $system_prompt .= "4. Đề cập link sản phẩm nếu có\n\n";
    $system_prompt .= "CÁC CÂU HỎI THƯỜNG GẶP:\n";
    $system_prompt .= "- \"còn những sản phẩm nào\" / \"những sản phẩm nào\" / \"còn sản phẩm nào\" = hỏi danh sách sản phẩm → PHẢI liệt kê sản phẩm từ database\n";
    $system_prompt .= "- \"có bao nhiêu sản phẩm\" = hỏi số lượng → trả lời số lượng từ database\n";
    $system_prompt .= "- \"tìm iPhone\" = tìm sản phẩm theo tên → liệt kê kết quả tìm kiếm\n";
    $system_prompt .= "- \"sản phẩm nào\" = hỏi danh sách → liệt kê sản phẩm\n\n";
    
    // Thêm thông tin sản phẩm vào prompt - PHIÊN BẢN NÂNG CAO
    $product_data = "";
    if (!empty($product_info)) {
        $product_data = "\n=== THÔNG TIN TỪ DATABASE ===\n";
        
        // Tổng số sản phẩm
        if (isset($product_info['total_products'])) {
            $product_data .= "📊 Tổng số sản phẩm: " . $product_info['total_products'] . " sản phẩm\n\n";
        }
        
        // Danh sách sản phẩm
        if (isset($product_info['products']) && !empty($product_info['products'])) {
            // Giới hạn chỉ 5 sản phẩm đầu tiên để giảm prompt length
            $products_display = array_slice($product_info['products'], 0, 5);
            $product_data .= "📦 DANH SÁCH SẢN PHẨM (Bạn PHẢI liệt kê các sản phẩm này trong câu trả lời):\n";
            foreach ($products_display as $idx => $product) {
                $product_data .= ($idx + 1) . ". " . $product['name'] . " - " . $product['price'] . " - " . ($product['stock'] > 0 ? "Còn hàng" : "Hết") . "\n";
                $product_data .= "   Link: " . SITE_URL . "/product-detail.php?slug=" . $product['slug'] . "\n";
            }
            if (count($product_info['products']) > 5) {
                $product_data .= "... và " . (count($product_info['products']) - 5) . " sản phẩm khác. Xem thêm tại: " . SITE_URL . "/products.php\n";
            }
            $product_data .= "\n";
        }
        
        // Chi tiết sản phẩm
        if (isset($product_info['product_detail'])) {
            $p = $product_info['product_detail'];
            $product_data .= "🔍 " . $p['name'] . ": " . $p['price'] . " - " . ($p['stock'] > 0 ? "Còn hàng" : "Hết") . "\n";
            if (!empty($p['description'])) {
                $product_data .= "Mô tả: " . mb_substr($p['description'], 0, 150) . "...\n";
            }
            $product_data .= "Link: " . SITE_URL . "/product-detail.php?slug=" . $p['slug'] . "\n\n";
        }
        
        // Đơn hàng
        if (isset($product_info['order'])) {
            $o = $product_info['order'];
            $product_data .= "📋 Đơn hàng #" . $o['id'] . ":\n";
            $product_data .= "Trạng thái: " . $o['status'] . "\n";
            $product_data .= "Tổng tiền: " . $o['total_amount'] . "\n";
            $product_data .= "Ngày đặt: " . $o['created_at'] . "\n\n";
        }
        
        // Lịch sử đơn hàng
        if (isset($product_info['order_history']) && !empty($product_info['order_history'])) {
            $product_data .= "📋 Lịch sử đơn hàng:\n";
            foreach ($product_info['order_history'] as $idx => $order) {
                $product_data .= ($idx + 1) . ". Đơn #" . $order['id'] . " - " . $order['total_amount'] . " - " . $order['status'] . " - " . $order['created_at'] . "\n";
            }
            $product_data .= "\n";
        }
        
        // Gợi ý sản phẩm (giới hạn 3 sản phẩm)
        if (isset($product_info['recommendations']) && !empty($product_info['recommendations'])) {
            $recs_display = array_slice($product_info['recommendations'], 0, 3);
            $product_data .= "💡 Gợi ý:\n";
            foreach ($recs_display as $idx => $rec) {
                $product_data .= ($idx + 1) . ". " . $rec['name'] . " - " . $rec['price'] . "\n";
                $product_data .= "   " . SITE_URL . "/product-detail.php?slug=" . $rec['slug'] . "\n";
            }
            $product_data .= "\n";
        }
        
        // So sánh sản phẩm
        if (isset($product_info['compare']) && !empty($product_info['compare'])) {
            $product_data .= "⚖️ So sánh sản phẩm:\n";
            foreach ($product_info['compare'] as $idx => $comp) {
                $product_data .= ($idx + 1) . ". " . $comp['name'] . " - " . $comp['price'] . " - " . ($comp['stock'] > 0 ? "Còn hàng" : "Hết hàng") . "\n";
            }
            $product_data .= "\n";
        }
        
        // Thông tin tồn kho
        if (isset($product_info['stock_info']) && !empty($product_info['stock_info'])) {
            $product_data .= "📊 Thông tin tồn kho:\n";
            foreach ($product_info['stock_info'] as $stock) {
                $product_data .= "- " . $stock['name'] . ": " . $stock['available'] . ($stock['stock'] > 0 ? " (" . $stock['stock'] . " sản phẩm)" : "") . "\n";
            }
            $product_data .= "\n";
        }
        
        $product_data .= "=== HẾT THÔNG TIN DATABASE ===\n\n";
        $product_data .= "⚠️ QUAN TRỌNG: Bạn PHẢI sử dụng thông tin trên để trả lời chính xác. Không được bịa đặt số liệu.\n";
        $product_data .= "Nếu có link sản phẩm, hãy đề cập đến link đó trong câu trả lời.\n\n";
    }
    
    // Rút gọn prompt để tăng tốc độ
    $user_prompt = $context ? "Lịch sử:\n$context\n\n" : "";
    $user_prompt .= $product_data;
    // Thêm hướng dẫn cụ thể cho các câu hỏi về danh sách sản phẩm
    if (preg_match('/(còn|những|mấy).*(sản phẩm|mặt hàng|hàng)/i', $message) || 
        preg_match('/(sản phẩm|mặt hàng|hàng).*(nào|có)/i', $message)) {
        if (empty($product_data) || (!isset($product_info['products']) && !isset($product_info['total_products']))) {
            // Nếu không có thông tin sản phẩm, hướng dẫn AI trả lời phù hợp
            $user_prompt .= "\n⚠️ QUAN TRỌNG: Khách hàng đang hỏi về DANH SÁCH SẢN PHẨM. ";
            $user_prompt .= "Bạn PHẢI trả lời bằng cách hướng dẫn khách xem trang sản phẩm tại " . SITE_URL . "/products.php ";
            $user_prompt .= "hoặc hỏi khách muốn tìm sản phẩm gì cụ thể. ";
            $user_prompt .= "KHÔNG được nói \"chưa hiểu\" hoặc \"mô tả cụ thể hơn\".\n\n";
        } else {
            $user_prompt .= "\n⚠️ QUAN TRỌNG: Khách hàng đang hỏi về DANH SÁCH SẢN PHẨM. ";
            $user_prompt .= "Bạn PHẢI liệt kê các sản phẩm ở trên với đầy đủ link. ";
            $user_prompt .= "KHÔNG được nói \"chưa hiểu\" hoặc \"mô tả cụ thể hơn\". ";
            $user_prompt .= "Nếu có nhiều sản phẩm, hãy liệt kê ít nhất 3-5 sản phẩm đầu tiên.\n\n";
        }
    }
    
    $user_prompt .= "Khách: $message\n\nTrả lời ngắn gọn:";
    
    $full_prompt = $system_prompt . $user_prompt;
    
    // Lấy cấu hình từ config
    $ollama_url = defined('OLLAMA_URL') ? OLLAMA_URL : 'http://localhost:11434/api/generate';
    $ollama_model = defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'llama3';
    
        $data = [
        "model" => $ollama_model,
        "prompt" => $full_prompt,
        "stream" => false,
        "options" => [
            "temperature" => 0.2, // Giảm xuống để trả lời nhanh và chính xác hơn
            "top_p" => 0.85,
            "max_tokens" => 150, // Giảm xuống để trả lời nhanh hơn (đủ cho 50-100 từ)
            "num_predict" => 150 // Giới hạn số token dự đoán
        ]
    ];
    
    $ch = curl_init($ollama_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 15, // Giảm timeout xuống 15 giây để phản hồi nhanh hơn
        CURLOPT_CONNECTTIMEOUT => 3 // Giảm connect timeout
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Xử lý response
    if ($response === false || $http_code !== 200) {
        chatLogError('Ollama API request failed', [
            'http_code' => $http_code,
            'curl_error' => $curl_error,
            'url' => $ollama_url,
            'model' => $ollama_model
        ]);
        return null; // Trả về null để fallback về logic cũ
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['response'])) {
        $ai_reply = trim($result['response']);
        chatLogDebug('Ollama API response received', [
            'response_length' => strlen($ai_reply),
            'model' => $ollama_model
        ]);
        // Loại bỏ các ký tự không mong muốn
        $ai_reply = preg_replace('/^(Bot|Khách hàng):\s*/i', '', $ai_reply);
        return $ai_reply;
    }
    
    return null;
}

// Simple chatbot logic với tích hợp Ollama AI
function getBotReply($message, $conversation_id = null, $conn = null) {
    // Thử gọi Ollama AI trước
    $ai_reply = callOllama($message, $conversation_id, $conn);
    
    if ($ai_reply && !empty(trim($ai_reply))) {
        return $ai_reply;
    }
    
    // Fallback về logic cũ nếu Ollama không hoạt động
    $message_lower = strtolower($message);
    
    // Greeting
    if (preg_match('/(xin chào|chào|hello|hi)/i', $message)) {
        return 'Xin chào! Tôi có thể giúp gì cho bạn? 😊';
    }
    
    // Product inquiry
    if (preg_match('/(iphone|samsung|laptop|điện thoại|máy tính)/i', $message)) {
        return 'Bạn muốn biết thông tin về sản phẩm nào? Tôi sẽ tư vấn chi tiết cho bạn.';
    }
    
    // Price inquiry
    if (preg_match('/(giá|bao nhiêu|price)/i', $message)) {
        return 'Bạn vui lòng cho tôi biết sản phẩm cụ thể để tôi báo giá chính xác nhé.';
    }
    
    // Order inquiry
    if (preg_match('/(đơn hàng|order|kiểm tra)/i', $message)) {
        return 'Bạn vui lòng cung cấp mã đơn hàng để tôi kiểm tra giúp bạn nhé.';
    }
    
    // Shipping
    if (preg_match('/(giao hàng|ship|vận chuyển)/i', $message)) {
        return 'Chúng tôi giao hàng toàn quốc, miễn phí ship cho đơn từ 500.000đ. Thời gian giao hàng 2-3 ngày.';
    }
    
    // Payment
    if (preg_match('/(thanh toán|payment|trả tiền)/i', $message)) {
        return 'Chúng tôi hỗ trợ thanh toán COD, chuyển khoản và các ví điện tử.';
    }
    
    // Warranty
    if (preg_match('/(bảo hành|warranty)/i', $message)) {
        return 'Tất cả sản phẩm đều được bảo hành chính hãng 12 tháng.';
    }
    
    // Thank you
    if (preg_match('/(cảm ơn|thanks|thank you)/i', $message)) {
        return 'Rất vui được hỗ trợ bạn! Nếu cần gì thêm, đừng ngại chat lại nhé! 😊';
    }
    
    // Nếu không match gì và Ollama cũng không trả lời được
    return 'Xin lỗi, tôi chưa hiểu rõ câu hỏi của bạn. Bạn có thể mô tả cụ thể hơn không? Hoặc liên hệ hotline để được hỗ trợ tốt hơn.';
}
?>

