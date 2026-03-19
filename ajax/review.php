<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// =====================================================
// THÊM REVIEW MỚI
// =====================================================
if ($action === 'add_review') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    // Validate
    if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($title) || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    // Kiểm tra đã review chưa
    $check = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
    $check->bind_param("ii", $product_id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
        exit;
    }
    
    // Thêm review
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiiss", $product_id, $user_id, $rating, $title, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đánh giá của bạn đang chờ duyệt. Cảm ơn bạn!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
    }
    exit;
}

// =====================================================
// LIKE/DISLIKE REVIEW
// =====================================================
if ($action === 'like_review' || $action === 'dislike_review') {
    $review_id = intval($_POST['review_id'] ?? 0);
    $type = $action === 'like_review' ? 'like' : 'dislike';
    
    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Review không hợp lệ']);
        exit;
    }
    
    // Kiểm tra đã like/dislike chưa
    $check = $conn->prepare("SELECT type FROM review_likes WHERE review_id = ? AND user_id = ?");
    $check->bind_param("ii", $review_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        
        // Nếu click lại cùng loại -> xóa
        if ($existing['type'] === $type) {
            $delete = $conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?");
            $delete->bind_param("ii", $review_id, $user_id);
            $delete->execute();
            
            // Giảm count
            $column = $type === 'like' ? 'likes' : 'dislikes';
            $conn->query("UPDATE reviews SET $column = $column - 1 WHERE id = $review_id");
            
            echo json_encode(['success' => true, 'action' => 'removed', 'type' => $type]);
        } else {
            // Đổi từ like sang dislike hoặc ngược lại
            $update = $conn->prepare("UPDATE review_likes SET type = ? WHERE review_id = ? AND user_id = ?");
            $update->bind_param("sii", $type, $review_id, $user_id);
            $update->execute();
            
            // Cập nhật count
            $old_column = $type === 'like' ? 'dislikes' : 'likes';
            $new_column = $type === 'like' ? 'likes' : 'dislikes';
            $conn->query("UPDATE reviews SET $old_column = $old_column - 1, $new_column = $new_column + 1 WHERE id = $review_id");
            
            echo json_encode(['success' => true, 'action' => 'changed', 'type' => $type]);
        }
    } else {
        // Thêm mới
        $insert = $conn->prepare("INSERT INTO review_likes (review_id, user_id, type) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $review_id, $user_id, $type);
        $insert->execute();
        
        // Tăng count
        $column = $type === 'like' ? 'likes' : 'dislikes';
        $conn->query("UPDATE reviews SET $column = $column + 1 WHERE id = $review_id");
        
        echo json_encode(['success' => true, 'action' => 'added', 'type' => $type]);
    }
    exit;
}

// =====================================================
// LẤY DANH SÁCH REVIEWS
// =====================================================
if ($action === 'get_reviews') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $page = intval($_POST['page'] ?? 1);
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT r.*, u.username, u.full_name,
              (SELECT type FROM review_likes WHERE review_id = r.id AND user_id = ?) as user_reaction
              FROM reviews r
              JOIN users u ON r.user_id = u.id
              WHERE r.product_id = ? AND r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $product_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    // Đếm tổng số reviews
    $count_query = "SELECT COUNT(*) as total FROM reviews WHERE product_id = ? AND status = 'approved'";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $product_id);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'total' => $total,
        'has_more' => ($offset + $limit) < $total
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);

