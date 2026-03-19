<?php
/**
 * Order Email Functions
 * Gửi email xác nhận đơn hàng và cập nhật trạng thái
 */

require_once __DIR__ . '/email-helper.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Gửi email xác nhận đơn hàng mới
 */
function sendOrderConfirmationEmail($order_id) {
    global $conn;
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) return false;
    
    // Lấy chi tiết sản phẩm
    $stmt = $conn->prepare("SELECT * FROM order_details WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Tạo HTML sản phẩm
    $items_html = '';
    foreach ($items as $item) {
        $items_html .= "
        <tr>
            <td style='padding: 15px; border-bottom: 1px solid #eee;'>
                <strong>{$item['product_name']}</strong>
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: right;'>" . number_format($item['subtotal'], 0, ',', '.') . "đ</td>
        </tr>";
    }
    
    $payment_method = strtoupper($order['payment_method']) == 'COD' ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng';
    
    $subject = "✅ Xác nhận đơn hàng #{$order['order_code']} - LuxuryTech";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; }
            .header h1 { color: #c9a050; margin: 0; font-size: 28px; }
            .header p { color: #ccc; margin: 10px 0 0; }
            .content { padding: 30px; }
            .order-box { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 25px; }
            .order-code { font-size: 24px; color: #c9a050; font-weight: bold; }
            .info-grid { display: table; width: 100%; }
            .info-item { display: table-row; }
            .info-label { display: table-cell; padding: 8px 0; color: #666; width: 140px; }
            .info-value { display: table-cell; padding: 8px 0; font-weight: 500; }
            .products-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .products-table th { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 12px; text-align: left; }
            .total-row { background: rgba(201, 160, 80, 0.1); }
            .total-row td { padding: 15px; font-weight: bold; font-size: 18px; color: #c9a050; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛒 LuxuryTech</h1>
                <p>Cảm ơn bạn đã đặt hàng!</p>
            </div>
            <div class='content'>
                <div class='order-box'>
                    <p style='margin: 0 0 10px; color: #666;'>Mã đơn hàng:</p>
                    <p class='order-code'>#{$order['order_code']}</p>
                </div>
                
                <h3 style='color: #1a1a2e; border-bottom: 2px solid #c9a050; padding-bottom: 10px;'>📦 Thông tin giao hàng</h3>
                <div class='info-grid'>
                    <div class='info-item'><span class='info-label'>Họ tên:</span><span class='info-value'>{$order['customer_name']}</span></div>
                    <div class='info-item'><span class='info-label'>Điện thoại:</span><span class='info-value'>{$order['customer_phone']}</span></div>
                    <div class='info-item'><span class='info-label'>Email:</span><span class='info-value'>{$order['customer_email']}</span></div>
                    <div class='info-item'><span class='info-label'>Địa chỉ:</span><span class='info-value'>{$order['customer_address']}</span></div>
                    <div class='info-item'><span class='info-label'>Thanh toán:</span><span class='info-value'>{$payment_method}</span></div>
                </div>
                
                <h3 style='color: #1a1a2e; border-bottom: 2px solid #c9a050; padding-bottom: 10px; margin-top: 30px;'>🛍️ Sản phẩm đã đặt</h3>
                <table class='products-table'>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th style='text-align: center;'>SL</th>
                            <th style='text-align: right;'>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                    </tbody>
                    <tfoot>
                        <tr class='total-row'>
                            <td colspan='2' style='text-align: right;'>Tổng cộng:</td>
                            <td style='text-align: right;'>" . number_format($order['total_amount'], 0, ',', '.') . "đ</td>
                        </tr>
                    </tfoot>
                </table>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='" . SITE_URL . "/order-tracking.php?code={$order['order_code']}' class='btn'>📍 Theo dõi đơn hàng</a>
                </p>
                
                <p style='color: #666; font-size: 14px; text-align: center;'>Nếu có thắc mắc, vui lòng liên hệ hotline: <strong>1900 xxxx</strong></p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " LuxuryTech. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($order['customer_email'], $subject, $body);
}

/**
 * Gửi email cập nhật trạng thái đơn hàng
 */
function sendOrderStatusEmail($order_id, $new_status) {
    global $conn;

    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) return false;

    // Map trạng thái
    $status_info = [
        'pending' => ['icon' => '⏳', 'label' => 'Chờ xác nhận', 'color' => '#ffc107', 'message' => 'Đơn hàng của bạn đang chờ xác nhận từ shop.'],
        'confirmed' => ['icon' => '✅', 'label' => 'Đã xác nhận', 'color' => '#17a2b8', 'message' => 'Đơn hàng của bạn đã được xác nhận và đang được chuẩn bị.'],
        'shipping' => ['icon' => '🚚', 'label' => 'Đang giao hàng', 'color' => '#007bff', 'message' => 'Đơn hàng của bạn đang trên đường giao đến bạn!'],
        'completed' => ['icon' => '🎉', 'label' => 'Hoàn thành', 'color' => '#28a745', 'message' => 'Đơn hàng đã được giao thành công. Cảm ơn bạn đã mua sắm!'],
        'cancelled' => ['icon' => '❌', 'label' => 'Đã hủy', 'color' => '#dc3545', 'message' => 'Đơn hàng của bạn đã bị hủy. Liên hệ shop nếu cần hỗ trợ.']
    ];

    $info = $status_info[$new_status] ?? $status_info['pending'];

    $subject = "{$info['icon']} Đơn hàng #{$order['order_code']} - {$info['label']}";

    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; }
            .header h1 { color: #c9a050; margin: 0; font-size: 28px; }
            .content { padding: 30px; text-align: center; }
            .status-box { background: {$info['color']}20; border: 2px solid {$info['color']}; border-radius: 12px; padding: 30px; margin: 20px 0; }
            .status-icon { font-size: 48px; margin-bottom: 15px; }
            .status-label { font-size: 24px; font-weight: bold; color: {$info['color']}; margin: 0; }
            .order-code { font-size: 18px; color: #c9a050; margin: 20px 0; }
            .message { color: #666; font-size: 16px; line-height: 1.6; }
            .btn { display: inline-block; background: linear-gradient(135deg, #c9a050, #dbb970); color: #1a1a2e; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛒 LuxuryTech</h1>
            </div>
            <div class='content'>
                <p>Xin chào <strong>{$order['customer_name']}</strong>,</p>

                <div class='status-box'>
                    <div class='status-icon'>{$info['icon']}</div>
                    <p class='status-label'>{$info['label']}</p>
                </div>

                <p class='order-code'>Mã đơn hàng: <strong>#{$order['order_code']}</strong></p>

                <p class='message'>{$info['message']}</p>

                <a href='" . SITE_URL . "/order-tracking.php?code={$order['order_code']}' class='btn'>📍 Theo dõi đơn hàng</a>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " LuxuryTech. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($order['customer_email'], $subject, $body);
}

