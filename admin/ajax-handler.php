<?php
require_once 'includes/auth.php';
require_once '../includes/order-email.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // Xóa sản phẩm
    case 'delete_product':
        $id = intval($_POST['id']);
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm']);
        }
        break;

    // Xóa danh mục
    case 'delete_category':
        $id = intval($_POST['id']);
        
        // Kiểm tra sản phẩm trong danh mục
        $check_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $count = $check_stmt->get_result()->fetch_assoc()['count'];
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Không thể xóa danh mục này vì có {$count} sản phẩm đang sử dụng"]);
        } else {
            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Xóa danh mục thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa danh mục']);
            }
        }
        break;

    // Xóa người dùng
    case 'delete_user':
        $id = intval($_POST['id']);
        
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của chính bạn']);
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa người dùng']);
            }
        }
        break;

    // Cập nhật role người dùng
    case 'update_user_role':
        $id = intval($_POST['id']);
        $role = sanitize($_POST['role']);
        
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể thay đổi quyền của chính bạn']);
        } else {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $role, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật quyền thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật quyền']);
            }
        }
        break;

    // Cập nhật trạng thái đơn hàng
    case 'update_order_status':
        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);

        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            // Gửi email thông báo trạng thái mới cho khách hàng
            $email_sent = sendOrderStatusEmail($id, $status);
            $message = 'Cập nhật trạng thái thành công';
            if ($email_sent) {
                $message .= ' và đã gửi email thông báo cho khách hàng';
            }
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái']);
        }
        break;

    // Duyệt/Từ chối review
    case 'approve_review':
    case 'reject_review':
        $id = intval($_POST['id']);
        $status = ($action === 'approve_review') ? 'approved' : 'rejected';

        $sql = "UPDATE reviews SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $msg = ($action === 'approve_review') ? 'Đã duyệt đánh giá' : 'Đã từ chối đánh giá';
            echo json_encode(['success' => true, 'message' => $msg, 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật']);
        }
        break;

    // Xóa review
    case 'delete_review':
        $id = intval($_POST['id']);
        $sql = "DELETE FROM reviews WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa đánh giá']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa']);
        }
        break;

    // Duyệt review
    case 'approve_review':
        error_log("=== APPROVE REVIEW REQUEST ===");
        error_log("ID: " . ($_POST['id'] ?? 'none'));

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            error_log("Invalid ID: $id");
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        // Kiểm tra review tồn tại
        $check = $conn->prepare("SELECT id, status FROM reviews WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            error_log("Review not found: $id");
            echo json_encode(['success' => false, 'message' => 'Review không tồn tại']);
            exit;
        }

        $review = $result->fetch_assoc();
        error_log("Current status: " . $review['status']);

        $sql = "UPDATE reviews SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            error_log("Review approved successfully");
            echo json_encode(['success' => true, 'message' => 'Đã duyệt đánh giá']);
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $conn->error]);
        }
        break;

    // Từ chối review
    case 'reject_review':
        $id = intval($_POST['id']);
        $sql = "UPDATE reviews SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã từ chối đánh giá']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi từ chối']);
        }
        break;

    // Xóa review
    case 'delete_review':
        $id = intval($_POST['id']);
        $sql = "DELETE FROM reviews WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa đánh giá']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa']);
        }
        break;

    // Trả lời review
    case 'reply_review':
        $id = intval($_POST['id']);
        $reply = sanitize($_POST['reply']);

        $sql = "UPDATE reviews SET admin_reply = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $reply, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã trả lời đánh giá']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi trả lời']);
        }
        break;

    // Thêm sản phẩm mới
    case 'add_product':
        $name = sanitize($_POST['name']);
        // Tạo slug: loại bỏ dấu, chuyển thành chữ thường, thay khoảng trắng bằng dấu gạch ngang
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        // Loại bỏ dấu gạch ngang liên tiếp
        $slug = preg_replace('/-+/', '-', $slug);
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $slug = trim($slug, '-');
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        $stock = intval($_POST['stock']);
        $description = sanitize($_POST['description']);
        $specifications = sanitize($_POST['specifications']);
        $image = $_POST['image'] ?? '';
        $images = $_POST['images'] ?? '[]'; // Không dùng sanitize để tránh hỏng JSON
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $status = sanitize($_POST['status']);

        $sql = "INSERT INTO products (name, slug, category_id, price, sale_price, stock, description, specifications, image, images, is_featured, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh: ' . $conn->error]);
        } else {
            $stmt->bind_param("ssidsissssss", $name, $slug, $category_id, $price, $sale_price, $stock, $description, $specifications, $image, $images, $is_featured, $status);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm sản phẩm: ' . $stmt->error]);
            }
        }
        break;

    // Cập nhật sản phẩm
    case 'update_product':
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        // Tạo slug: loại bỏ dấu, chuyển thành chữ thường, thay khoảng trắng bằng dấu gạch ngang
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        // Loại bỏ dấu gạch ngang liên tiếp
        $slug = preg_replace('/-+/', '-', $slug);
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $slug = trim($slug, '-');
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $sale_price = (isset($_POST['sale_price']) && $_POST['sale_price'] !== '') ? floatval($_POST['sale_price']) : null;
        $stock = intval($_POST['stock']);
        $description = sanitize($_POST['description']);
        $specifications = sanitize($_POST['specifications']);
        $image = $_POST['image'] ?? '';
        $images = $_POST['images'] ?? '[]'; // Không dùng sanitize để tránh hỏng JSON
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $status = sanitize($_POST['status']);

        $sql = "UPDATE products SET name=?, slug=?, category_id=?, price=?, sale_price=?, stock=?, description=?, specifications=?, image=?, images=?, is_featured=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh: ' . $conn->error]);
        } else {
            $stmt->bind_param("ssidsissssssi", $name, $slug, $category_id, $price, $sale_price, $stock, $description, $specifications, $image, $images, $is_featured, $status, $id);

            if ($stmt->execute()) {
                // Set session message để hiển thị khi redirect về trang danh sách
                $_SESSION['message'] = 'Cập nhật sản phẩm thành công';
                $_SESSION['message_type'] = 'success';
                echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật sản phẩm: ' . $stmt->error]);
            }
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

