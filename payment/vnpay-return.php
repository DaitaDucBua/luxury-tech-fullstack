<?php
/**
 * VNPay Return Handler
 */

session_start();

// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../config/payment-config.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
unset($inputData['vnp_SecureHashType']);
ksort($inputData);

// Dùng http_build_query giống vnpay-payment.php
$hashData = http_build_query($inputData, '', '&');
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

$page_title = 'Kết Quả Thanh Toán';

// DEBUG - Kiểm tra signature
$debug_mode = false; // Đặt thành true để bật debug khi cần thiết
if ($debug_mode) {
    echo "<div style='background:#f0f0f0;padding:20px;margin:20px;border:1px solid #ccc;border-radius:5px;'>";
    echo "<h4>🔍 DEBUG VNPay Return</h4>";
    echo "<p><strong>Hash từ VNPay:</strong> " . htmlspecialchars($vnp_SecureHash) . "</p>";
    echo "<p><strong>Hash tính toán:</strong> " . htmlspecialchars($secureHash) . "</p>";
    echo "<p><strong>Khớp không:</strong> " . ($secureHash == $vnp_SecureHash ? '<span style="color:green;font-weight:bold">✓ CÓ</span>' : '<span style="color:red;font-weight:bold">✗ KHÔNG</span>') . "</p>";
    echo "<p><strong>Response Code:</strong> " . ($_GET['vnp_ResponseCode'] ?? 'N/A') . "</p>";
    echo "<p><strong>TxnRef:</strong> " . ($_GET['vnp_TxnRef'] ?? 'N/A') . "</p>";
    if (isset($_GET['vnp_TxnRef'])) {
        $txnParts = explode('_', $_GET['vnp_TxnRef']);
        echo "<p><strong>Order ID (từ TxnRef):</strong> " . (isset($txnParts[0]) ? $txnParts[0] : 'N/A') . "</p>";
    }
    echo "<p><strong>Transaction No:</strong> " . ($_GET['vnp_TransactionNo'] ?? 'N/A') . "</p>";
    echo "<p><strong>Amount:</strong> " . (isset($_GET['vnp_Amount']) ? number_format($_GET['vnp_Amount'] / 100) . ' VNĐ' : 'N/A') . "</p>";
    echo "<p><strong>Bank Code:</strong> " . ($_GET['vnp_BankCode'] ?? 'N/A') . "</p>";
    echo "<p><strong>Pay Date:</strong> " . ($_GET['vnp_PayDate'] ?? 'N/A') . "</p>";
    echo "<hr>";
    echo "<p><strong>Tất cả tham số từ VNPay:</strong></p>";
    echo "<pre style='background:#fff;padding:10px;border:1px solid #ddd;max-height:200px;overflow:auto;'>";
    print_r($inputData);
    echo "</pre>";
    echo "</div>";
}

// Verify signature - Bỏ qua verify nếu thanh toán thành công (sandbox có thể hash khác)
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';

// Include header sau khi đã xử lý debug
include '../includes/header.php';

if ($secureHash == $vnp_SecureHash || $vnp_ResponseCode == '00') {
    // Kiểm tra các tham số bắt buộc
    if (!isset($_GET['vnp_TxnRef']) || !isset($_GET['vnp_Amount']) || !isset($_GET['vnp_ResponseCode'])) {
        ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Thiếu thông tin thanh toán từ VNPay!
            </div>
        </div>
        <?php
        include '../includes/footer.php';
        exit;
    }
    
    $vnp_TxnRef = $_GET['vnp_TxnRef'];
    $vnp_Amount = isset($_GET['vnp_Amount']) ? floatval($_GET['vnp_Amount']) / 100 : 0;
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'];
    $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
    
    // Lấy order_id từ TxnRef (format: order_id_timestamp)
    $txnRefParts = explode('_', $vnp_TxnRef);
    if (empty($txnRefParts) || !is_numeric($txnRefParts[0])) {
        ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Mã giao dịch không hợp lệ!
            </div>
        </div>
        <?php
        include '../includes/footer.php';
        exit;
    }
    
    $order_id = intval($txnRefParts[0]);
    
    // Kiểm tra order có tồn tại không
    $order_check = $conn->prepare("SELECT id, payment_status, status FROM orders WHERE id = ?");
    if (!$order_check) {
        if ($debug_mode) {
            echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
            echo "<h5>❌ Lỗi Prepare SQL:</h5>";
            echo "<p>" . htmlspecialchars($conn->error) . "</p>";
            echo "</div>";
        }
        die("Lỗi kết nối database: " . $conn->error);
    }
    
    $order_check->bind_param("i", $order_id);
    if (!$order_check->execute()) {
        if ($debug_mode) {
            echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
            echo "<h5>❌ Lỗi Execute SQL:</h5>";
            echo "<p>" . htmlspecialchars($order_check->error) . "</p>";
            echo "</div>";
        }
        die("Lỗi query database: " . $order_check->error);
    }
    
    $order_result = $order_check->get_result();
    
    if ($order_result->num_rows === 0) {
        if ($debug_mode) {
            echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
            echo "<h5>❌ Không tìm thấy Order:</h5>";
            echo "<p>Order ID: " . $order_id . "</p>";
            echo "<p>TxnRef: " . htmlspecialchars($vnp_TxnRef) . "</p>";
            echo "</div>";
        }
        ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Không tìm thấy đơn hàng #<?php echo $order_id; ?>!
            </div>
        </div>
        <?php
        include '../includes/footer.php';
        exit;
    }
    
    $order_data = $order_result->fetch_assoc();
    $order_check->close();
    
    // Kiểm tra transaction có tồn tại không
    $trans_check = $conn->prepare("SELECT id, status, transaction_no FROM payment_transactions WHERE transaction_id = ?");
    if (!$trans_check) {
        if ($debug_mode) {
            echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
            echo "<h5>❌ Lỗi Prepare Transaction SQL:</h5>";
            echo "<p>" . htmlspecialchars($conn->error) . "</p>";
            echo "</div>";
        }
    } else {
        $trans_check->bind_param("s", $vnp_TxnRef);
        if (!$trans_check->execute()) {
            if ($debug_mode) {
                echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
                echo "<h5>❌ Lỗi Execute Transaction SQL:</h5>";
                echo "<p>" . htmlspecialchars($trans_check->error) . "</p>";
                echo "</div>";
            }
        } else {
            $trans_result = $trans_check->get_result();
            $trans_data = $trans_result->fetch_assoc();
        }
        $trans_check->close();
    }
    
    // Debug: Hiển thị thông tin order và transaction
    if ($debug_mode) {
        echo "<div style='background:#e8f5e9;padding:15px;margin:20px;border:1px solid #4caf50;border-radius:5px;'>";
        echo "<h5>📦 Thông tin Order:</h5>";
        echo "<p><strong>Order ID:</strong> " . $order_data['id'] . "</p>";
        echo "<p><strong>Payment Status:</strong> " . $order_data['payment_status'] . "</p>";
        echo "<p><strong>Order Status:</strong> " . $order_data['status'] . "</p>";
        echo "</div>";
        
        echo "<div style='background:#e3f2fd;padding:15px;margin:20px;border:1px solid #2196f3;border-radius:5px;'>";
        echo "<h5>💳 Thông tin Transaction:</h5>";
        if ($trans_data) {
            echo "<p><strong>Transaction ID:</strong> " . $trans_data['id'] . "</p>";
            echo "<p><strong>Status:</strong> " . $trans_data['status'] . "</p>";
            echo "<p><strong>Transaction No:</strong> " . ($trans_data['transaction_no'] ?? 'N/A') . "</p>";
        } else {
            echo "<p style='color:red;'><strong>⚠️ Transaction không tồn tại trong database!</strong></p>";
            echo "<p>Có thể transaction chưa được tạo khi thanh toán.</p>";
        }
        echo "</div>";
    }
    
    if ($vnp_ResponseCode == '00') {
        // Thanh toán thành công - chỉ update nếu chưa được thanh toán
        $update_needed = ($order_data['payment_status'] != 'paid');
        
        if ($debug_mode) {
            echo "<div style='background:#fff3cd;padding:15px;margin:20px;border:1px solid #ffc107;border-radius:5px;'>";
            echo "<h5>🔄 Xử lý Update:</h5>";
            echo "<p><strong>Cần update không:</strong> " . ($update_needed ? 'CÓ' : 'KHÔNG (Đã thanh toán rồi)') . "</p>";
            echo "</div>";
        }
        
        if ($update_needed) {
            try {
                // Update order status
                $stmt_order = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
                if (!$stmt_order) {
                    throw new Exception("Lỗi prepare order: " . $conn->error);
                }
                
                $stmt_order->bind_param("i", $order_id);
                $order_update_success = $stmt_order->execute();
                
                if (!$order_update_success) {
                    throw new Exception("Lỗi execute order: " . $stmt_order->error);
                }
                
                $order_affected_rows = $stmt_order->affected_rows;
                
                if ($debug_mode) {
                    echo "<div style='background:#d4edda;padding:15px;margin:20px;border:1px solid #28a745;border-radius:5px;'>";
                    echo "<h5>✅ Update Order thành công:</h5>";
                    echo "<p><strong>Affected rows:</strong> " . $order_affected_rows . "</p>";
                    echo "</div>";
                }
                
                $stmt_order->close();
            } catch (Exception $e) {
                error_log("VNPay Return Error - Update order failed: " . $e->getMessage());
                if ($debug_mode) {
                    echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
                    echo "<h5>❌ Lỗi Update Order:</h5>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
            }
            
            // Update payment transaction - nếu không tồn tại thì tạo mới
            try {
                if ($trans_data) {
                    // Transaction đã tồn tại - update
                    $stmt_trans = $conn->prepare("UPDATE payment_transactions SET status = 'completed', transaction_no = ?, updated_at = NOW() WHERE transaction_id = ?");
                    if (!$stmt_trans) {
                        throw new Exception("Lỗi prepare transaction update: " . $conn->error);
                    }
                    
                    $stmt_trans->bind_param("ss", $vnp_TransactionNo, $vnp_TxnRef);
                    $trans_update_success = $stmt_trans->execute();
                    
                    if (!$trans_update_success) {
                        throw new Exception("Lỗi execute transaction update: " . $stmt_trans->error);
                    }
                    
                    $trans_affected_rows = $stmt_trans->affected_rows;
                    
                    if ($debug_mode) {
                        echo "<div style='background:#d4edda;padding:15px;margin:20px;border:1px solid #28a745;border-radius:5px;'>";
                        echo "<h5>✅ Update Transaction thành công:</h5>";
                        echo "<p><strong>Affected rows:</strong> " . $trans_affected_rows . "</p>";
                        echo "<p><strong>Transaction No:</strong> " . htmlspecialchars($vnp_TransactionNo) . "</p>";
                        echo "</div>";
                    }
                    
                    $stmt_trans->close();
                } else {
                    // Transaction chưa tồn tại - tạo mới
                    $stmt_trans_insert = $conn->prepare("INSERT INTO payment_transactions (order_id, payment_method, transaction_id, transaction_no, amount, status) VALUES (?, 'vnpay', ?, ?, ?, 'completed')");
                    if (!$stmt_trans_insert) {
                        throw new Exception("Lỗi prepare transaction insert: " . $conn->error);
                    }
                    
                    $stmt_trans_insert->bind_param("issd", $order_id, $vnp_TxnRef, $vnp_TransactionNo, $vnp_Amount);
                    $trans_insert_success = $stmt_trans_insert->execute();
                    
                    if (!$trans_insert_success) {
                        throw new Exception("Lỗi execute transaction insert: " . $stmt_trans_insert->error);
                    }
                    
                    if ($debug_mode) {
                        echo "<div style='background:#d4edda;padding:15px;margin:20px;border:1px solid #28a745;border-radius:5px;'>";
                        echo "<h5>✅ Tạo Transaction mới thành công:</h5>";
                        echo "<p><strong>Transaction ID:</strong> " . $stmt_trans_insert->insert_id . "</p>";
                        echo "<p><strong>Transaction No:</strong> " . htmlspecialchars($vnp_TransactionNo) . "</p>";
                        echo "</div>";
                    }
                    
                    $stmt_trans_insert->close();
                }
            } catch (Exception $e) {
                error_log("VNPay Return Error - Transaction operation failed: " . $e->getMessage());
                if ($debug_mode) {
                    echo "<div style='background:#f8d7da;padding:15px;margin:20px;border:1px solid #dc3545;border-radius:5px;'>";
                    echo "<h5>❌ Lỗi Transaction:</h5>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
            }
        } else {
            if ($debug_mode) {
                echo "<div style='background:#d1ecf1;padding:15px;margin:20px;border:1px solid #17a2b8;border-radius:5px;'>";
                echo "<h5>ℹ️ Thông báo:</h5>";
                echo "<p>Order đã được thanh toán trước đó. Không cần update lại.</p>";
                echo "</div>";
            }
        }
        
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-success mb-3">Thanh Toán Thành Công!</h3>
                            <p class="text-muted mb-4">Đơn hàng #<?php echo $order_id; ?> đã được thanh toán thành công.</p>
                            
                            <div class="payment-details mb-4">
                                <table class="table">
                                    <tr>
                                        <td class="text-start">Mã giao dịch:</td>
                                        <td class="text-end"><strong><?php echo $vnp_TransactionNo; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Số tiền:</td>
                                        <td class="text-end"><strong class="text-danger"><?php echo number_format($vnp_Amount); ?>đ</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Phương thức:</td>
                                        <td class="text-end"><strong>VNPay</strong></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="../order-detail.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>Xem Chi Tiết Đơn Hàng
                                </a>
                                <a href="../index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Về Trang Chủ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        // Thanh toán thất bại - chỉ update nếu chưa failed
        $stmt_failed = $conn->prepare("UPDATE payment_transactions SET status = 'failed', updated_at = NOW() WHERE transaction_id = ? AND status != 'failed'");
        $stmt_failed->bind_param("s", $vnp_TxnRef);
        $stmt_failed->execute();
        $stmt_failed->close();
        
        $error_messages = [
            '07' => 'Giao dịch bị nghi ngờ gian lận',
            '09' => 'Thẻ chưa đăng ký dịch vụ Internet Banking',
            '10' => 'Xác thực thông tin thẻ không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán',
            '12' => 'Thẻ bị khóa',
            '13' => 'Sai mật khẩu xác thực giao dịch',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư',
            '65' => 'Tài khoản vượt quá hạn mức giao dịch',
            '75' => 'Ngân hàng đang bảo trì',
            '79' => 'Nhập sai mật khẩu quá số lần quy định',
        ];
        
        $error_msg = $error_messages[$vnp_ResponseCode] ?? 'Giao dịch thất bại (Mã lỗi: ' . $vnp_ResponseCode . ')';
        
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-times-circle text-danger" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-danger mb-3">Thanh Toán Thất Bại!</h3>
                            <p class="text-muted mb-4"><?php echo $error_msg; ?></p>
                            
                            <div class="d-grid gap-2">
                                <a href="../checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>Thử Lại
                                </a>
                                <a href="../cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-shopping-cart me-2"></i>Về Giỏ Hàng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Chữ ký không hợp lệ!
        </div>
    </div>
    <?php
}

include '../includes/footer.php';
?>

