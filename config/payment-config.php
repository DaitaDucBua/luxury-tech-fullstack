<?php
/**
 * Payment Gateway Configuration
 */

// =====================================================
// VNPAY CONFIGURATION
// =====================================================
define('VNPAY_TMN_CODE', 'W3J39388'); // Mã website
define('VNPAY_HASH_SECRET', 'D72LIEHMXVSRMCKBZIQW6TGP57E08TR7'); // Chuỗi bí mật
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // URL thanh toán (sandbox)
define('VNPAY_RETURN_URL', 'http://localhost/LUXURYTECH/payment/vnpay-return.php'); // URL return
define('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction');

// =====================================================
// MOMO CONFIGURATION
// =====================================================
define('MOMO_PARTNER_CODE', 'YOUR_MOMO_PARTNER_CODE');
define('MOMO_ACCESS_KEY', 'YOUR_MOMO_ACCESS_KEY');
define('MOMO_SECRET_KEY', 'YOUR_MOMO_SECRET_KEY');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'); // Test endpoint
define('MOMO_RETURN_URL', 'http://localhost/LUXURYTECH/payment/momo-return.php');
define('MOMO_NOTIFY_URL', 'http://localhost/LUXURYTECH/payment/momo-notify.php');

// =====================================================
// ZALOPAY CONFIGURATION
// =====================================================
define('ZALOPAY_APP_ID', 'YOUR_ZALOPAY_APP_ID');
define('ZALOPAY_KEY1', 'YOUR_ZALOPAY_KEY1');
define('ZALOPAY_KEY2', 'YOUR_ZALOPAY_KEY2');
define('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create'); // Sandbox
define('ZALOPAY_CALLBACK_URL', 'http://localhost/LUXURYTECH/payment/zalopay-callback.php');

// =====================================================
// PAYPAL CONFIGURATION
// =====================================================
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'
define('PAYPAL_RETURN_URL', 'http://localhost/LUXURYTECH/payment/paypal-return.php');
define('PAYPAL_CANCEL_URL', 'http://localhost/LUXURYTECH/payment/paypal-cancel.php');

/**
 * HƯỚNG DẪN CẤU HÌNH:
 * 
 * 1. VNPAY:
 *    - Đăng ký tại: https://vnpay.vn/
 *    - Lấy TMN Code và Hash Secret từ merchant portal
 *    - Test với sandbox trước khi lên production
 * 
 * 2. MOMO:
 *    - Đăng ký tại: https://business.momo.vn/
 *    - Lấy Partner Code, Access Key, Secret Key
 *    - Cấu hình IPN URL trong merchant portal
 * 
 * 3. ZALOPAY:
 *    - Đăng ký tại: https://zalopay.vn/
 *    - Lấy App ID, Key1, Key2
 *    - Test với sandbox
 * 
 * 4. PAYPAL:
 *    - Đăng ký tại: https://developer.paypal.com/
 *    - Tạo app và lấy Client ID, Secret
 *    - Sử dụng sandbox cho testing
 */

// =====================================================
// PAYMENT STATUS
// =====================================================
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PROCESSING', 'processing');
define('PAYMENT_STATUS_COMPLETED', 'completed');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// =====================================================
// CURRENCY
// =====================================================
define('CURRENCY_VND', 'VND');
define('CURRENCY_USD', 'USD');
?>

