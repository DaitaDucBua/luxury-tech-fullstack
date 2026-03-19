<?php
// Ensure session is started before requiring config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Determine if user is logged in - use isLoggedIn() function from config
$isLoggedIn = isLoggedIn();
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Initialize session wishlist array for anonymous users
if (!$isLoggedIn && !isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// =====================================================
// TOGGLE WISHLIST
// =====================================================
if ($action === 'toggle') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
        exit;
    }
    
    if ($isLoggedIn) {
        // Database wishlist for logged-in users - use SQL_NO_CACHE to ensure fresh data
        $check = $conn->prepare("SELECT SQL_NO_CACHE id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->bind_param("ii", $user_id, $product_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // Remove from wishlist
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $action_done = 'removed';
            $message = 'Đã xóa khỏi danh sách yêu thích';
        } else {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("ii", $user_id, $product_id);
            if (!$stmt->execute()) {
                // Check if it's a duplicate entry error
                if ($conn->errno == 1062) {
                    // Duplicate entry - already in wishlist, just return success
                    $action_done = 'added';
                    $message = 'Đã có trong danh sách yêu thích';
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi thêm vào wishlist: ' . $conn->error]);
                    exit;
                }
            } else {
                $action_done = 'added';
                $message = 'Đã thêm vào danh sách yêu thích';
            }
        }
        
        // Count wishlist items - use SQL_NO_CACHE to ensure fresh data
        $count_stmt = $conn->prepare("SELECT SQL_NO_CACHE COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count = $count_stmt->get_result()->fetch_assoc()['count'];
    } else {
        // Session wishlist for anonymous users
        if (in_array($product_id, $_SESSION['wishlist'])) {
            // Remove
            $_SESSION['wishlist'] = array_filter($_SESSION['wishlist'], function($id) use ($product_id) {
                return $id !== $product_id;
            });
            $action_done = 'removed';
            $message = 'Đã xóa khỏi danh sách yêu thích';
        } else {
            // Add
            $_SESSION['wishlist'][] = $product_id;
            $action_done = 'added';
            $message = 'Đã thêm vào danh sách yêu thích (Đăng nhập để lưu vĩnh viễn)';
        }
        $count = count($_SESSION['wishlist']);
    }
    
    echo json_encode([
        'success' => true,
        'action' => $action_done,
        'message' => $message,
        'wishlist_count' => $count
    ]);
    exit;
}

// =====================================================
// CHECK IF PRODUCT IS IN WISHLIST
// =====================================================
if ($action === 'check') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($isLoggedIn) {
        $stmt = $conn->prepare("SELECT SQL_NO_CACHE id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $in_wishlist = $stmt->get_result()->num_rows > 0;
    } else {
        $in_wishlist = in_array($product_id, $_SESSION['wishlist'] ?? []);
    }
    
    echo json_encode([
        'success' => true,
        'in_wishlist' => $in_wishlist
    ]);
    exit;
}

// =====================================================
// REMOVE FROM WISHLIST
// =====================================================
if ($action === 'remove') {
    try {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }
        
        if ($isLoggedIn) {
            // Database wishlist for logged-in users
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            if (!$stmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $stmt->bind_param("ii", $user_id, $product_id);
            if (!$stmt->execute()) {
                throw new Exception('Delete error: ' . $stmt->error);
            }
            
            // Verify deletion was successful
            $affected_rows = $stmt->affected_rows;
            
            // Close statement to ensure changes are committed
            $stmt->close();
            
            // Force commit explicitly
            if (!$conn->commit()) {
                $conn->rollback();
                throw new Exception('Commit error: ' . $conn->error);
            }
            
            // Small delay to ensure database commit is complete
            usleep(300000); // 0.3 second
            
            $verify_stmt = $conn->prepare("SELECT SQL_NO_CACHE id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $verify_stmt->bind_param("ii", $user_id, $product_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $still_exists = $verify_result->num_rows > 0;
            $verify_stmt->close();
            
            // If item still exists, try to delete again
            if ($still_exists) {
                error_log("Warning: Item still exists after delete. Retrying delete for user_id=$user_id, product_id=$product_id");
                $retry_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $retry_stmt->bind_param("ii", $user_id, $product_id);
                $retry_stmt->execute();
                $retry_affected = $retry_stmt->affected_rows;
                $retry_stmt->close();
                
                if ($retry_affected === 0) {
                    // Still can't delete, return error
                    throw new Exception('Không thể xóa sản phẩm khỏi wishlist. Vui lòng thử lại.');
                }
            }
            
            // Count wishlist items - use fresh query with SQL_NO_CACHE
            $count_stmt = $conn->prepare("SELECT SQL_NO_CACHE COUNT(*) as count FROM wishlist WHERE user_id = ?");
            if (!$count_stmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count = $count_result->fetch_assoc()['count'];
            $count_stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi danh sách yêu thích',
                'wishlist_count' => intval($count),
                'deleted' => true,
                'affected_rows' => $affected_rows
            ]);
        } else {
            // Session wishlist for anonymous users
            if (isset($_SESSION['wishlist']) && in_array($product_id, $_SESSION['wishlist'])) {
                $_SESSION['wishlist'] = array_filter($_SESSION['wishlist'], function($id) use ($product_id) {
                    return $id !== $product_id;
                });
                $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index array
            }
            $count = count($_SESSION['wishlist'] ?? []);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi danh sách yêu thích',
                'wishlist_count' => $count
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// =====================================================
// GET WISHLIST COUNT
// =====================================================
if ($action === 'get_count') {
    if ($isLoggedIn) {
        $count_stmt = $conn->prepare("SELECT SQL_NO_CACHE COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count = $count_stmt->get_result()->fetch_assoc()['count'];
    } else {
        $count = count($_SESSION['wishlist'] ?? []);
    }
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
?>

